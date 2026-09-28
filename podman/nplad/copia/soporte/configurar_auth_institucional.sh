#!/bin/bash
set -e

# --- CONFIGURACIÓN ---
OLD_CONTAINER="moodle_old"
NEW_CONTAINER="moodle_new"
DB_CONTAINER="mariadb"
TEMP_DIR="./temp_migration"

if [ -f "./moodle_old/.env" ]; then ENV_PATH="."; elif [ -f "../moodle_old/.env" ]; then ENV_PATH=".."; else
    echo "❌ Error: Ejecuta desde la carpeta 'soporte'." && exit 1
fi

BLUE='\033[0;34m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${BLUE}💉 TRASPLANTANDO CONFIGURACIÓN LDAP Y DIVISIST${NC}"
mkdir -p "$TEMP_DIR"

# 1. Obtener credenciales
OLD_DB=$(grep MOODLE_DB_NAME "$ENV_PATH/moodle_old/.env" | cut -d'=' -f2 | tr -d '\r')
NEW_DB=$(grep MOODLE_DB_NAME "$ENV_PATH/moodle_new/.env" | cut -d'=' -f2 | tr -d '\r')
ROOT_PW=$(grep MYSQL_ROOT_PASSWORD "$ENV_PATH/mariadb/.env" | cut -d'=' -f2 | tr -d '\r')

# 2. Tablas que contienen LDAP, Divisist y Filtros
TABLES="mdl_config mdl_config_plugins mdl_filter_active mdl_filter_config"

echo "   📦 Exportando tablas de configuración..."
podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" "$OLD_DB" $TABLES > "$TEMP_DIR/auth_configs.sql"

echo "   📥 Importando en $NEW_DB..."
podman exec -i "$DB_CONTAINER" mysql -u root -p"$ROOT_PW" "$NEW_DB" < "$TEMP_DIR/auth_configs.sql"

# 3. CORRECCIÓN DE URL (Indispensable para que no redirija a producción)
echo -e "${YELLOW}⚠️  Restaurando URL local para evitar redirección externa...${NC}"
NEW_URL="https://nplad.ufps.edu.co"
SQL_FIX="UPDATE mdl_config SET value='$NEW_URL' WHERE name='wwwroot';"
podman exec -i "$DB_CONTAINER" mysql -u root -p"$ROOT_PW" "$NEW_DB" -e "$SQL_FIX"

# 4. Finalizar
podman exec "$NEW_CONTAINER" php admin/cli/upgrade.php --non-interactive --allow-unstable
podman exec "$NEW_CONTAINER" php admin/cli/purge_caches.php

rm -rf "$TEMP_DIR"
echo -e "${GREEN}🎉 Configuración institucional (LDAP/Divisist) aplicada con éxito.${NC}"