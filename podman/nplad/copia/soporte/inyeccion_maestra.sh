#!/bin/bash
set -e
set -x  # MODO DEBUG: Imprime cada comando antes de ejecutarlo

# ==============================================================================
# SCRIPT MAESTRO DE INYECCIÓN DE MOODLE (VERSIÓN CON CARPETA DE RESPALDOS)
# ==============================================================================

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}   🚀 INICIANDO INYECCIÓN EN SERVIDOR DE PRUEBAS${NC}"
echo -e "${BLUE}======================================================${NC}"

# Carpeta donde se ubican las copias de seguridad
BACKUP_DIR="copias de seguridad de producción"

# Usuario del servidor web dentro del contenedor. 
WEB_USER="www-data" 

# Permite pasar la contraseña de root de MariaDB como primer argumento.
if [ -z "$1" ]; then
    echo -e "${YELLOW}⚠️ ADVERTENCIA: Usando contraseña hardcodeada para MariaDB.${NC}"
    ROOT_PW='A2$kLp9#Zm8@qX1vTrC'
else
    ROOT_PW="$1"
fi

# 1. VERIFICAR QUE TODOS LOS ARCHIVOS EXISTAN EN LA NUEVA CARPETA
ARCHIVOS_REQUERIDOS=(
    "$BACKUP_DIR/moodle_old_2025_2.sql" "$BACKUP_DIR/moodle_new.sql" 
    "$BACKUP_DIR/codigo_moodle_old.tar.gz" "$BACKUP_DIR/codigo_moodle_new.tar.gz" 
    "$BACKUP_DIR/moodle_old_light.tar.gz" "$BACKUP_DIR/moodle_new_light.tar.gz"
)

for file in "${ARCHIVOS_REQUERIDOS[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ ERROR: No se encuentra el archivo $file.${NC}"
        exit 1
    fi
done

# ==============================================================================
# FUNCIÓN PRINCIPAL DE DESPLIEGUE
# ==============================================================================
inyectar_moodle() {
    local CONTAINER=$1
    local DB_NAME=$2
    local URL_TEST=$3        # URL que usará el servidor de pruebas
    local URL_ORIGINAL=$4    # URL que usaba el servidor de producción
    local SQL_FILE=$5
    local CODE_FILE=$6
    local DATA_FILE=$7
    
    echo -e "\n${YELLOW}▶️ PROCESANDO CONTENEDOR: ${CONTAINER} (BD: ${DB_NAME})${NC}"
    
    # PASO 1: Respaldar el config.php actual
    echo -e "   [1/6] 📄 Respaldando config.php local..."
    podman exec "$CONTAINER" cat /var/www/html/config.php > "config_${CONTAINER}.php.bak"
    
    # PASO 2: Inyectar código fuente (con limpieza profunda)
    echo -e "   [2/6] 📦 Inyectando código fuente: ${CODE_FILE}..."
    podman cp "$CODE_FILE" "$CONTAINER":/tmp/codigo.tar.gz
    podman exec -u root "$CONTAINER" bash -c "
        find /var/www/html -mindepth 1 -delete
        tar -xzf /tmp/codigo.tar.gz -C /var/www/html/
        rm /tmp/codigo.tar.gz
    "    
    podman cp "config_${CONTAINER}.php.bak" "$CONTAINER":/var/www/html/config.php
    
    # PASO 3: Inyectar datos en moodledata
    echo -e "   [3/6] 📂 Inyectando datos estructurales: ${DATA_FILE}..."
    podman cp "$DATA_FILE" "$CONTAINER":/tmp/datos.tar.gz
    podman exec -u root "$CONTAINER" bash -c "
        tar -xzf /tmp/datos.tar.gz -C /var/www/moodledata/
        rm /tmp/datos.tar.gz
    "
    
    # PASO 4: Ajustar Permisos
    echo -e "   [4/6] 🔧 Sincronizando permisos para el usuario ${WEB_USER}..."
    podman exec -u root "$CONTAINER" bash -c "
        chown -R ${WEB_USER}:${WEB_USER} /var/www/html
        chown -R ${WEB_USER}:${WEB_USER} /var/www/moodledata
    "
    
    # PASO 5: Inyectar Base de Datos
    echo -e "   [5/6] 💾 Importando configuraciones: ${SQL_FILE} en BD ${DB_NAME}..."
    if ! podman exec -i mariadb mysql -u root -p"$ROOT_PW" "$DB_NAME" < "$SQL_FILE"; then
        echo -e "\n${RED}❌ ERROR FATAL al importar el archivo SQL en la base de datos $DB_NAME.${NC}"
        exit 1
    fi
    
    # PASO 6: Reemplazo de URLs nativo y Actualización CLI segura
    echo -e "   [6/6] ⚙️  Actualizando URLs y purgando cachés bajo el usuario ${WEB_USER}..."
    echo -e "         🔗 Validando hipervínculos de $URL_ORIGINAL a $URL_TEST"
    
    podman exec --user "$WEB_USER" --workdir /var/www/html "$CONTAINER" php admin/tool/replace/cli/replace.php --search="$URL_ORIGINAL" --replace="$URL_TEST" --non-interactive
    
    podman exec --user "$WEB_USER" --workdir /var/www/html "$CONTAINER" php admin/cli/upgrade.php --non-interactive --allow-unstable
    podman exec --user "$WEB_USER" --workdir /var/www/html "$CONTAINER" php admin/cli/purge_caches.php
    
    echo -e "${GREEN}✅ CONTENEDOR ${CONTAINER} COMPLETADO EXITOSAMENTE.${NC}"
}

# ==============================================================================
# EJECUCIÓN SECUENCIAL
# ORDEN: CONTENEDOR | BASE_DATOS | URL_PRUEBAS | URL_PRODUCCIÓN_ORIGINAL | SQL | CODIGO | DATOS
# ==============================================================================

# 1. Inyectar Moodle Antiguo
inyectar_moodle "moodle_old" "2025_2" "https://nplad2.ufps.edu.co" "https://nplad2.ufps.edu.co" "$BACKUP_DIR/moodle_old_2025_2.sql" "$BACKUP_DIR/codigo_moodle_old.tar.gz" "$BACKUP_DIR/moodle_old_light.tar.gz"

# 2. Inyectar Moodle Nuevo
inyectar_moodle "moodle_new" "moodle_new" "https://nplad.ufps.edu.co" "https://nplad.ufps.edu.co" "$BACKUP_DIR/moodle_new.sql" "$BACKUP_DIR/codigo_moodle_new.tar.gz" "$BACKUP_DIR/moodle_new_light.tar.gz"

# Limpieza de backups temporales locales
rm -f config_moodle_old.php.bak config_moodle_new.php.bak

echo -e "\n${BLUE}======================================================${NC}"
echo -e "${GREEN}🎉 ¡AMBOS ENTORNOS HAN SIDO SINCRONIZADOS Y CONFIGURADOS!${NC}"
echo -e "${BLUE}======================================================${NC}"