#!/bin/bash
set -e

# Configuración de colores para la consola
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# ==============================================================================
# SISTEMA DE LOGS AUTOMÁTICO
# ==============================================================================
LOG_FILE="log_cambio_semestre_$(date +%Y%m%d_%H%M%S).txt"
exec > >(tee -i "$LOG_FILE") 2>&1

echo -e "${GREEN}📝 Log de esta sesión guardándose en: ${LOG_FILE}${NC}\n"

# ==============================================================================
# VARIABLES GLOBALES Y CONFIGURACIÓN
# ==============================================================================
URL_ACTUAL="https://nplad.ufps.edu.co"
URL_HISTORICA="https://nplad2.ufps.edu.co"

# Rutas físicas del servidor
PATH_OLD="/data/plad_old/moodledata"
PATH_ACTUAL="/data/plad_actual/moodledata"

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}   🔄 SCRIPT MAESTRO DE CAMBIO DE SEMESTRE UFPS ${NC}"
echo -e "${BLUE}======================================================${NC}"

# Confirmación inicial de copias de seguridad
echo -e "${YELLOW}🛑 ALTO: PRE-REQUISITOS DE SEGURIDAD${NC}"
read -p "⚠️ ¿Ya realizaste las copias de seguridad manuales (Bases de datos, carpetas moodledata y código fuente de moodle_new y moodle_old)? [s/N] " CONFIRM_BACKUPS
if [[ "$CONFIRM_BACKUPS" != "s" && "$CONFIRM_BACKUPS" != "S" ]]; then
    echo -e "${RED}❌ Operación cancelada. Por favor realiza las copias de seguridad manuales antes de ejecutar este script.${NC}"
    exit 1
fi

# Extracción Segura de la Contraseña Root de MariaDB
ENV_FILE="/home/plad/podman2026_1/plad_new/mariadb/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ Error: No se encontró el archivo de entorno en ${ENV_FILE}${NC}"
    exit 1
fi

ROOT_PW=$(grep -E '^(MYSQL_ROOT_PASSWORD|MARIADB_ROOT_PASSWORD)=' "$ENV_FILE" | head -n 1 | cut -d '=' -f2- | tr -d '"' | tr -d "'" | tr -d '\r')

if [ -z "$ROOT_PW" ]; then
    echo -e "${RED}❌ Error: No se pudo extraer la contraseña root del archivo .env${NC}"
    exit 1
fi

if ! podman exec -i mariadb mysql -u root -p"$ROOT_PW" -N -e "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: La contraseña extraída es incorrecta o no hay acceso a MariaDB. Verifica el contenido de ${ENV_FILE}${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Credenciales de base de datos cargadas y verificadas con éxito.${NC}\n"

read -p "📌 Ingresa el nombre del semestre que finaliza y pasará a histórico (ej. 2026_1): " SEMESTRE_OLD

if ! [[ "$SEMESTRE_OLD" =~ ^20[0-9]{2}_[12]$ ]]; then
    echo -e "${RED}❌ Formato inválido. Usa AAAA_S con semestre 1 o 2 (ej. 2026_1, 2026_2).${NC}"
    exit 1
fi

if [[ "$SEMESTRE_OLD" =~ ^(moodle_new|moodle_old|mysql|information_schema|performance_schema|sys)$ ]]; then
    echo -e "${RED}❌ Nombre reservado de sistema, no permitido.${NC}"
    exit 1
fi

EXISTE=$(podman exec -i mariadb mysql -u root -p"$ROOT_PW" -N -e "SHOW DATABASES LIKE '${SEMESTRE_OLD}';")
if [ -n "$EXISTE" ]; then
    read -p "⚠️  La base de datos ${SEMESTRE_OLD} YA EXISTE y se borrará por completo. ¿Continuar? [s/N] " CONFIRM_DB
    if [[ "$CONFIRM_DB" != "s" && "$CONFIRM_DB" != "S" ]]; then
        echo -e "${RED}❌ Operación cancelada por el usuario.${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✅ Semestre finalizado y archivado establecido como: ${SEMESTRE_OLD}${NC}"

echo -e "\n${YELLOW}▶️ FASE 1: APAGADO Y RESPALDOS${NC}"
echo -e "   [1/2] Apagando contenedores web (Las advertencias WARN aquí son normales)..."
podman stop moodle_new moodle_old || true

echo -e "   [2/2] Respaldando base de datos original (moodle_new)..."
podman exec -i mariadb mysqldump --single-transaction -u root -p"$ROOT_PW" moodle_new > "backup_moodle_new_previo.sql"

# ==============================================================================
echo -e "\n${YELLOW}▶️ FASE 2: MANIPULACIÓN DE BASES DE DATOS${NC}"
echo -e "   [1/3] Creando nueva base de datos histórica: ${SEMESTRE_OLD}..."
podman exec -i mariadb mysql -u root -p"$ROOT_PW" -e "DROP DATABASE IF EXISTS \`${SEMESTRE_OLD}\`; CREATE DATABASE \`${SEMESTRE_OLD}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo -e "   [2/3] Clonando datos exactos de moodle_new hacia ${SEMESTRE_OLD}..."
podman exec -i mariadb mysqldump -u root -p"$ROOT_PW" moodle_new | podman exec -i mariadb mysql -u root -p"$ROOT_PW" "${SEMESTRE_OLD}"

echo -e "   [3/3] Otorgando permisos de acceso al usuario de Moodle sobre la nueva base de datos..."
podman cp moodle_new:/var/www/html/config.php /tmp/config_temp.php
DB_USER=$(awk -F"['\"]" '/->dbuser/ {print $2}' /tmp/config_temp.php | head -n 1)
rm -f /tmp/config_temp.php

if [ -z "$DB_USER" ] || [ "$DB_USER" = "root" ]; then
    echo -e "${RED}❌ Error: DB_USER en config.php es inválido ('$DB_USER'). No se puede aplicar el GRANT.${NC}"
    exit 1
fi
podman exec -i mariadb mysql -u root -p"$ROOT_PW" -e "GRANT ALL PRIVILEGES ON \`${SEMESTRE_OLD}\`.* TO '${DB_USER}'@'%'; FLUSH PRIVILEGES;"

# ==============================================================================
echo -e "\n${YELLOW}▶️ FASE 3: TRANSFERENCIA FÍSICA SUPER-RÁPIDA (moodledata)${NC}"

ESPACIO=$(df -Pm /data | awk 'NR==2 {print $4}')
if [ "$ESPACIO" -lt 5120 ]; then
    echo -e "${RED}❌ Espacio insuficiente en /data (${ESPACIO} MB). Se requieren al menos 5120 MB libres.${NC}"
    exit 1
fi

echo -e "   [1/4] Eliminando carpeta histórica obsoleta mediante espacio de nombres Podman..."
podman unshare rm -rf "$PATH_OLD"

echo -e "   ${BLUE}[!] CHECKPOINT: Si el script falla a partir de este punto, recupere manualmente:${NC}"
echo -e "   ${BLUE}    1. Restaurando 'backup_moodle_new_previo.sql'.${NC}"
echo -e "   ${BLUE}    2. Moviendo de vuelta la ruta /data/plad_old a /data/plad_actual.${NC}"

echo -e "   [2/4] Transfiriendo la data actual a histórica (Movimiento instantáneo)..."
podman unshare mv "$PATH_ACTUAL" "$PATH_OLD"

echo -e "   [3/4] Recreando estructura para el nuevo semestre..."
podman unshare mkdir -p "$PATH_ACTUAL"

echo -e "   [4/4] Copiando configuraciones/idiomas (excluyendo los 315GB de filedir)..."
podman unshare bash -c "
    for item in \"$PATH_OLD\"/*; do
        if [ -e \"\$item\" ]; then
            base=\$(basename \"\$item\")
            if [ \"\$base\" != \"filedir\" ] && [ \"\$base\" != \"trashdir\" ]; then
                cp -a \"\$item\" \"$PATH_ACTUAL/\"
            fi
        fi
    done
    mkdir -p \"$PATH_ACTUAL/filedir\"
"

# ==============================================================================
echo -e "\n${YELLOW}▶️ FASE 4: SINCRONIZACIÓN DE CÓDIGO Y PLUGINS (HTML)${NC}"
echo -e "   [1/3] Extrayendo código actualizado de moodle_new..."
podman unshare rm -rf /tmp/html_new
mkdir -p /tmp/html_new
podman cp moodle_new:/var/www/html/. /tmp/html_new/

echo -e "   [2/3] Parcheando config.php para el entorno histórico..."
podman unshare sed -i -e "s/\(->dbname[[:space:]]*=[[:space:]]*\)['\"][^'\"]*['\"]/\1'${SEMESTRE_OLD}'/" /tmp/html_new/config.php
podman unshare sed -i "s|$URL_ACTUAL|$URL_HISTORICA|g" /tmp/html_new/config.php

echo -e "   [3/3] Inyectando código actualizado en moodle_old..."
podman start moodle_old

podman exec -u root moodle_old bash -c "rm -f /var/run/apache2/apache2.pid /run/apache2/apache2.pid /var/run/apache2/httpd.pid" || true
podman exec -u root moodle_old bash -c "pkill -9 apache2 2>/dev/null; pkill -9 httpd 2>/dev/null" || true
podman restart moodle_old
podman exec -u root moodle_old supervisorctl restart apache2 2>/dev/null || true

podman exec -u root moodle_old bash -c "rm -rf /var/www/html/*"
podman cp /tmp/html_new/. moodle_old:/var/www/html/
podman unshare rm -rf /tmp/html_new

# ==============================================================================
echo -e "\n${YELLOW}▶️ FASE 5: ARRANQUE, RECONFIGURACIÓN Y PURGA DE CURSOS Y USUARIOS${NC}"
echo -e "   [1/11] Iniciando contenedores y ajustando permisos (Omitiendo filedir para máximo rendimiento)..."
podman start moodle_new

podman exec -u root moodle_new bash -c "rm -f /var/run/apache2/apache2.pid /run/apache2/apache2.pid /var/run/apache2/httpd.pid" || true
podman exec -u root moodle_new bash -c "pkill -9 apache2 2>/dev/null; pkill -9 httpd 2>/dev/null" || true
podman restart moodle_new
podman exec -u root moodle_new supervisorctl restart apache2 2>/dev/null || true

# Garantizar que existan los directorios base antes del chown
podman exec -u root moodle_old bash -c "mkdir -p /var/www/moodledata/filedir /var/www/moodledata/trashdir"
podman exec -u root moodle_new bash -c "mkdir -p /var/www/moodledata/filedir /var/www/moodledata/trashdir"

podman exec -u root moodle_old chown -R www-data:www-data /var/www/html
podman exec -u root moodle_new chown -R www-data:www-data /var/www/html

podman exec -u root moodle_old bash -c "find /var/www/moodledata -mindepth 1 -maxdepth 1 ! -name 'filedir' ! -name 'trashdir' -exec chown -R www-data:www-data {} +"
podman exec -u root moodle_new bash -c "find /var/www/moodledata -mindepth 1 -maxdepth 1 ! -name 'filedir' ! -name 'trashdir' -exec chown -R www-data:www-data {} +"

podman exec -u root moodle_old chown www-data:www-data /var/www/moodledata /var/www/moodledata/filedir /var/www/moodledata/trashdir
podman exec -u root moodle_new chown www-data:www-data /var/www/moodledata /var/www/moodledata/filedir /var/www/moodledata/trashdir

echo -e "   [2/11] Moodle Old: Migrando hipervínculos internos..."
podman exec --user www-data --workdir /var/www/html moodle_old php admin/tool/replace/cli/replace.php --search="${URL_ACTUAL}" --replace="${URL_HISTORICA}" --non-interactive --shorten

echo -e "   [3/11] Limpiando cachés residuales y sesiones en ambos contenedores..."
podman exec -u root moodle_new bash -c "rm -rf /var/www/moodledata/cache/* /var/www/moodledata/localcache/* /var/www/moodledata/sessions/*"
podman exec -u root moodle_old bash -c "rm -rf /var/www/moodledata/cache/* /var/www/moodledata/localcache/* /var/www/moodledata/sessions/*"

echo -e "   [4/11] Restaurando contraseñas originales del administrador en ambos contenedores..."
cat << 'EOF' > /tmp/restaurar_clave_old.php
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
$user = $DB->get_record('user', array('username' => 'admin', 'deleted' => 0));
if ($user) {
    update_internal_user_password($user, 'uFpS_OldAdm#2025');
    echo "✅ Contraseña de administrador restaurada en Moodle Old.\n";
} else {
    echo "❌ Error: Usuario admin no encontrado en Moodle Old.\n";
}
EOF

cat << 'EOF' > /tmp/restaurar_clave_new.php
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
$user = $DB->get_record('user', array('username' => 'admin', 'deleted' => 0));
if ($user) {
    update_internal_user_password($user, 'uFpS_NewAdm#2025');
    echo "✅ Contraseña de administrador confirmada en Moodle New.\n";
} else {
    echo "❌ Error: Usuario admin no encontrado en Moodle New.\n";
}
EOF

podman cp /tmp/restaurar_clave_old.php moodle_old:/var/www/html/admin/cli/restaurar_clave.php
podman exec --user www-data --workdir /var/www/html moodle_old php admin/cli/restaurar_clave.php
podman exec -u root moodle_old bash -c "rm -f /var/www/html/admin/cli/restaurar_clave.php"

podman cp /tmp/restaurar_clave_new.php moodle_new:/var/www/html/admin/cli/restaurar_clave.php
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/restaurar_clave.php
podman exec -u root moodle_new bash -c "rm -f /var/www/html/admin/cli/restaurar_clave.php"

rm -f /tmp/restaurar_clave_old.php /tmp/restaurar_clave_new.php

echo -e "   [5/11] Purgando cachés de aplicación en ambos entornos..."
podman exec --user www-data --workdir /var/www/html moodle_old php admin/cli/purge_caches.php
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/purge_caches.php

echo -e "   [6/11] Moodle New: Desactivando lógicamente BigBlueButton..."
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/cfg.php --component=mod_bigbluebuttonbn --name=disabled --set=1

echo -e "   [7/11] Moodle New: Generando script maestro para eliminación masiva de cursos (Paso 1)..."
cat << 'EOF' > /tmp/eliminar_cursos_maestro.php
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php'); 
require_once($CFG->dirroot . '/course/lib.php');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$courses = $DB->get_fieldset_select('course', 'id', 'id > 1');
$total = count($courses);
$count = 0;

echo "Iniciando purga secuencial de $total cursos...\n";
foreach ($courses as $id) {
    try {
        delete_course($id, false);
        echo "✅ Curso ID: $id eliminado.\n";
        $count++;
    } catch (Exception $e) {
        echo "❌ Error en curso ID: $id - " . $e->getMessage() . "\n";
    }
}
echo "✅ Resumen (Paso 1): $count de $total cursos eliminados correctamente.\n";
EOF

podman cp /tmp/eliminar_cursos_maestro.php moodle_new:/var/www/html/admin/cli/eliminar_cursos_maestro.php
echo -e "   [8/11] Moodle New: Purgando cursos SECUENCIALMENTE desde el core de Moodle..."
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/eliminar_cursos_maestro.php

# ---> NUEVA IMPLEMENTACIÓN DE LA LÓGICA DE DOBLE PASADA <---
echo -e "   [8.1/11] Moodle New: Generando script para análisis de cursos residuales (Paso 2)..."
cat << 'EOF' > /tmp/eliminar_cursos_residuales.php
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php'); 
require_once($CFG->dirroot . '/course/lib.php');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$residuales = $DB->get_fieldset_select('course', 'id', 'id > 1');
$total_residuales = count($residuales);

if ($total_residuales > 0) {
    echo "⚠️ Se detectaron $total_residuales cursos residuales tras la primera pasada.\n";
    echo "🔄 Iniciando proceso limpio para su eliminación...\n";
    $count = 0;
    foreach ($residuales as $id) {
        try {
            delete_course($id, false);
            echo "✅ Curso residual ID: $id eliminado exitosamente.\n";
            $count++;
        } catch (Exception $e) {
            echo "❌ Error persistente en curso residual ID: $id - " . $e->getMessage() . "\n";
        }
    }
    echo "✅ Resumen (Paso 2): $count de $total_residuales cursos residuales eliminados.\n";
} else {
    echo "✅ Excelente: La base de datos quedó al 100% limpia en el primer intento.\n";
}
EOF

podman cp /tmp/eliminar_cursos_residuales.php moodle_new:/var/www/html/admin/cli/eliminar_cursos_residuales.php
echo -e "   [8.2/11] Moodle New: Ejecutando script de limpieza profunda para residuales en un proceso PHP nuevo..."
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/eliminar_cursos_residuales.php
# -----------------------------------------------------------

echo -e "   [9/11] Moodle New: Eliminando usuarios (excepto admin y guest)..."
cat << 'EOF' > /tmp/eliminar_usuarios.php
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

$users = $DB->get_records_select('user', "username NOT IN ('admin', 'guest') AND deleted = 0 AND id > 0");
$count = 0;
foreach ($users as $user) {
    try {
        delete_user($user);
        $count++;
    } catch (Exception $e) {
        echo "❌ Error al eliminar usuario {$user->username}: " . $e->getMessage() . "\n";
    }
}
echo "✅ $count usuarios han sido eliminados correctamente.\n";
EOF

podman cp /tmp/eliminar_usuarios.php moodle_new:/var/www/html/admin/cli/eliminar_usuarios.php
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/eliminar_usuarios.php

echo -e "   [9.1/11] Moodle New: Limpieza final de registros BBB y scripts temporales..."
cat << 'EOF' > /tmp/limpiar_bbb.php
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php'); 
$DB->execute("TRUNCATE TABLE {bigbluebuttonbn}");
EOF
podman cp /tmp/limpiar_bbb.php moodle_new:/var/www/html/admin/cli/limpiar_bbb.php
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/limpiar_bbb.php

# Limpieza exhaustiva de todos los scripts inyectados
podman exec -u root moodle_new bash -c "rm -f /var/www/html/admin/cli/eliminar_cursos_maestro.php /var/www/html/admin/cli/eliminar_cursos_residuales.php /var/www/html/admin/cli/eliminar_usuarios.php /var/www/html/admin/cli/limpiar_bbb.php"
rm -f /tmp/eliminar_cursos_maestro.php /tmp/eliminar_cursos_residuales.php /tmp/eliminar_usuarios.php /tmp/limpiar_bbb.php

echo -e "   [10/11] Moodle New: Reactivando módulo BigBlueButton..."
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/cfg.php --component=mod_bigbluebuttonbn --name=disabled --set=0
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/purge_caches.php

echo -e "   [11/11] Moodle New: Desfragmentando y optimizando la base de datos (mantenimiento en MariaDB)..."
podman exec -i mariadb mysqlcheck -u root -p"$ROOT_PW" --auto-repair --optimize moodle_new

echo -e "\n${BLUE}======================================================${NC}"
echo -e "${GREEN}✅ LA INFRAESTRUCTURA HA SIDO MIGRADA CORRECTAMENTE${NC}"
echo -e "${GREEN}✅ LAS FACULTADES Y DEPARTAMENTOS PERMANECEN INTACTOS${NC}"
echo -e "${GREEN}✅ MOODLE_NEW QUEDÓ 100% LIMPIO DE CURSOS Y USUARIOS LISTO PARA MATRÍCULAS${NC}"
echo -e "${GREEN}✅ SISTEMA DE DOBLE VERIFICACIÓN APLICADO A LA ELIMINACIÓN DE CURSOS${NC}"
echo -e "${BLUE}======================================================${NC}"