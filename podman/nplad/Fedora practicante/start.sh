#!/bin/bash
set -e

# Ruta base absoluta
BASE_DIR="./data"

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Verificar herramientas necesarias
for cmd in podman podman-compose; do
    if ! command -v $cmd &> /dev/null; then
        echo "❌ Error: '$cmd' no está instalado."
        exit 1
    fi
done

# Rutas principales
IMAGES_DIR="$SCRIPT_DIR/imagenes"
PLAD_OLD_DIR="$BASE_DIR/plad_old"
PLAD_ACTUAL_DIR="$BASE_DIR/plad_actual"
MARIADB_DATA="$BASE_DIR/mariadb_data"

# Moodle (solo datos, sin código fuente)
MOODLEDATA_OLD="$PLAD_OLD_DIR/moodledata"
MOODLEDATA_NEW="$PLAD_ACTUAL_DIR/moodledata"

# Crear carpetas necesarias
echo "📁 Verificando carpetas de datos..."
mkdir -p "$MOODLEDATA_OLD" "$MOODLEDATA_NEW" "$MARIADB_DATA"
echo "✅ Carpetas listas."

# Cargar imágenes necesarias
declare -A IMAGES=(
    ["localhost/mariadb:10.11"]="$IMAGES_DIR/mariadb-10.11.tar"
    ["localhost/phpmyadmin:5.2.2"]="$IMAGES_DIR/phpmyadmin-5.2.2.tar"
    ["localhost/moodle-cron:5.0"]="$IMAGES_DIR/moodle_cron.tar"
)

for image in "${!IMAGES[@]}"; do
    TAR="${IMAGES[$image]}"
    if [[ ! -f "$TAR" ]]; then
        echo "❌ Error: Archivo $TAR no encontrado."
        exit 1
    fi
    if podman image exists "$image"; then
        echo "✅ Imagen $image ya cargada."
    else
        echo "📦 Cargando imagen desde $TAR..."
        output=$(podman load -i "$TAR")
        image_id=$(echo "$output" | awk '/Loaded image:/ {print $3}')
        podman tag "$image_id" "$image"
        echo "✅ Imagen $image cargada y etiquetada como $image"
    fi
done

# Verificar y crear red si no existe
echo "🌐 Verificando red fedorapracticante_default..."
if ! podman network ls --format '{{.Name}}' | grep -q "^fedorapracticante_default$"; then
    echo "    → Creando red fedorapracticante_default..."
    podman network create fedorapracticante_default
else
    echo "✅ Red fedorapracticante_default lista."
fi

# Iniciar servicios con podman-compose
echo "🚀 Iniciando servicios con podman-compose..."
podman-compose -f "$SCRIPT_DIR/podman-compose.yml" up -d

# Esperar que MariaDB arranque
echo "⏳ Esperando que MariaDB esté lista..."
for i in {1..30}; do
    if podman exec mariadb sh -c 'mysqladmin --protocol=TCP -h127.0.0.1 -P3306 ping --silent' >/dev/null 2>&1; then
        echo "✅ MariaDB responde al ping."
        break
    fi
    echo "... esperando (${i}s)"
    sleep 1
    if [ $i -eq 30 ]; then
        echo "❌ Tiempo de espera agotado para MariaDB."
        exit 1
    fi
done

# -------------------------------
# Función robusta para cargar .env
# -------------------------------
load_env_file() {
    local envfile=$1
    if [[ ! -f "$envfile" ]]; then
        echo "❌ Error: No se encontró el archivo $envfile"
        exit 1
    fi
    while IFS='=' read -r key rest; do
        [[ -z "$key" || "$key" =~ ^[[:space:]]*# ]] && continue
        local value="$rest"
        key="$(echo -n "$key" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
        value="$(echo -n "$value" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
        if [[ "$value" =~ ^\".*\"$ ]]; then
            value="${value:1:${#value}-2}"
        elif [[ "$value" =~ ^\'.*\'$ ]]; then
            value="${value:1:${#value}-2}"
        fi
        value="${value%$'\r'}"
        export "$key=$value"
    done < "$envfile"
}

# Cargar variables de mariadb
load_env_file "$SCRIPT_DIR/mariadb/.env"
DB_USER="root"
DB_PASS="$MYSQL_ROOT_PASSWORD"

# Reintentar conexión real con root hasta 120s
echo "⏳ Probando conexión a MariaDB con $DB_USER..."
for i in {1..60}; do
    if podman exec -i mariadb mysql --protocol=TCP -h127.0.0.1 -P3306 -u"$DB_USER" --password="$DB_PASS" -e 'SELECT 1;' 2>/tmp/mariadb_error.log; then
        echo "✅ Conexión con $DB_USER exitosa."
        break
    else
        echo "... aún no disponible (${i}s)."
        cat /tmp/mariadb_error.log || true
    fi
    sleep 2
    if [ $i -eq 60 ]; then
        echo "❌ Error: No se pudo conectar con $DB_USER tras 120s."
        exit 1
    fi
done

# Variables de moodle_old
load_env_file "$SCRIPT_DIR/moodle_old/.env"
OLD_DBNAME="$MOODLE_DB_NAME"
OLD_DBUSER="$MOODLE_DB_USER"
OLD_DBPASS="$MOODLE_DB_PASS"
OLD_ADMINUSER="$MOODLE_ADMIN_USER"
OLD_ADMINPASS="$MOODLE_ADMIN_PASS"
OLD_ADMINEMAIL="$MOODLE_ADMIN_EMAIL"

# Variables de moodle_new
load_env_file "$SCRIPT_DIR/moodle_new/.env"
NEW_DBNAME="$MOODLE_DB_NAME"
NEW_DBUSER="$MOODLE_DB_USER"
NEW_DBPASS="$MOODLE_DB_PASS"
NEW_ADMINUSER="$MOODLE_ADMIN_USER"
NEW_ADMINPASS="$MOODLE_ADMIN_PASS"
NEW_ADMINEMAIL="$MOODLE_ADMIN_EMAIL"

# Crear DBs y usuarios
echo "🛠️ Verificando y creando bases de datos en MariaDB..."
SQL_COMMANDS=$(cat <<EOF
CREATE DATABASE IF NOT EXISTS \`$OLD_DBNAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`$NEW_DBNAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$OLD_DBUSER'@'%' IDENTIFIED BY '$OLD_DBPASS';
CREATE USER IF NOT EXISTS '$NEW_DBUSER'@'%' IDENTIFIED BY '$NEW_DBPASS';
GRANT ALL PRIVILEGES ON \`$OLD_DBNAME\`.* TO '$OLD_DBUSER'@'%';
GRANT ALL PRIVILEGES ON \`$NEW_DBNAME\`.* TO '$NEW_DBUSER'@'%';
FLUSH PRIVILEGES;
EOF
)
podman exec -i mariadb mysql \
    --protocol=TCP -h127.0.0.1 -P3306 \
    -u"$DB_USER" -p"$DB_PASS" <<EOF
$SQL_COMMANDS
EOF
echo "✅ Bases de datos listas."

# -------------------------------
# Función instalación Moodle
# -------------------------------
instalar_moodle_si_falta() {
    CONTAINER=$1
    WWWROOT=$2
    DBNAME=$3
    DBUSER=$4
    DBPASS=$5
    ADMINUSER=$6
    ADMINPASS=$7
    ADMINEMAIL=$8

    echo "⚙️ Verificando si Moodle ya está instalado en $CONTAINER..."
    if ! podman exec "$CONTAINER" test -f /var/www/html/config.php || ! podman exec "$CONTAINER" php admin/cli/isinstalled.php; then
        echo "🚀 Instalando Moodle en $CONTAINER..."
        podman exec "$CONTAINER" php admin/cli/install.php \
            --non-interactive \
            --agree-license \
            --lang=es_co \
            --wwwroot="http://$WWWROOT" \
            --dataroot=/var/www/moodledata \
            --dbtype=mariadb \
            --dbhost=mariadb \
            --dbname="$DBNAME" \
            --dbuser="$DBUSER" \
            --dbpass="$DBPASS" \
            --fullname="Moodle UFPS" \
            --shortname="$CONTAINER" \
            --adminuser="$ADMINUSER" \
            --adminpass="$ADMINPASS" \
            --adminemail="$ADMINEMAIL"

        echo "✅ Moodle instalado en $CONTAINER"
    else
        echo "✅ $CONTAINER ya está instalado."
    fi

    echo "🌐 Asegurando configuración HTTP plano y cookies para $CONTAINER..."
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=lang --set=es_co
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=timezone --set=America/Bogota
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=locale --set=es_CO.UTF-8
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=localetext --set=utf-8
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=maxbytes --set=0
    
    # Desactivar bandera de cookies seguras en la BD de Moodle
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=cookiesecure --set=0

    podman exec "$CONTAINER" bash -c "
        CONFIG=\"/var/www/html/config.php\"
        if [ -f \"\$CONFIG\" ]; then
            sed -i \"s|\(\$CFG->wwwroot[[:space:]]*=\).*|\1 'http://$WWWROOT';|\" \"\$CONFIG\"
            sed -i '/\$CFG->sslproxy/d' \"\$CONFIG\"
        fi
    "

    echo "🔧 Ajustando permisos..."
    podman exec "$CONTAINER" bash -c '
        MOODLE_DIR="/var/www/html"
        MOODLEDATA="/var/www/moodledata"
        CONFIG="$MOODLE_DIR/config.php"
        if [ -f "$CONFIG" ]; then
            chmod 644 "$CONFIG"
            chown www-data:www-data "$CONFIG"
        fi
        chown -R www-data:www-data "$MOODLE_DIR" "$MOODLEDATA"
        find "$MOODLE_DIR" -type d -exec chmod 755 {} \;
        find "$MOODLE_DIR" -type f -exec chmod 644 {} \;
        find "$MOODLEDATA" -type d -exec chmod 770 {} \;
        find "$MOODLEDATA" -type f -exec chmod 660 {} \;
    '
}


# Ejecutar en ambas instancias
instalar_moodle_si_falta moodle_old "nplad2.ufps.edu.co" "$OLD_DBNAME" "$OLD_DBUSER" "$OLD_DBPASS" "$OLD_ADMINUSER" "$OLD_ADMINPASS" "$OLD_ADMINEMAIL"
instalar_moodle_si_falta moodle_new "nplad.ufps.edu.co" "$NEW_DBNAME" "$NEW_DBUSER" "$NEW_DBPASS" "$NEW_ADMINUSER" "$NEW_ADMINPASS" "$NEW_ADMINEMAIL"

# -------------------------------
# Instalar plugin VPL (si existe ZIP en ./plugins/)
# -------------------------------
instalar_vpl_si_falta() {
    CONTAINER=$1
    PLUGIN_ZIP=$(ls "$SCRIPT_DIR/plugins"/mod_vpl*.zip 2>/dev/null | head -n 1)

    if [[ -z "$PLUGIN_ZIP" ]]; then
        echo "⚠️ No se encontró el ZIP del plugin VPL en $SCRIPT_DIR/plugins/"
        return
    fi

    echo "📦 Instalando VPL en $CONTAINER..."
    podman cp "$PLUGIN_ZIP" "$CONTAINER":/tmp/mod_vpl.zip

    podman exec "$CONTAINER" bash -c '
        set -e
        cd /tmp
        unzip -o mod_vpl.zip -d /var/www/html/mod/
        chown -R www-data:www-data /var/www/html/mod/vpl
        rm -f mod_vpl.zip
    '

    # Ejecutar upgrade para que Moodle registre el plugin
    podman exec "$CONTAINER" php admin/cli/upgrade.php --non-interactive --allow-unstable

    echo "✅ VPL instalado en $CONTAINER"
}

# Ejecutar instalación de VPL en ambas instancias
instalar_vpl_si_falta moodle_old
instalar_vpl_si_falta moodle_new

# Final
echo ""
echo "✅ Servicios iniciados correctamente."
echo "📘 Moodle OLD: http://nplad2.ufps.edu.co"
echo "📘 Moodle NEW: http://nplad.ufps.edu.co"
echo "🛠️ PhpMyAdmin: http://nplad.ufps.edu.co/phpmyadmin/"
