#!/bin/bash

# ==============================================================================
# SCRIPT DE IMPORTACIÓN Y ACTUALIZACIÓN DE MOODLE (PROD -> TEST)
# Versión Final Ajustada a Nombres de Variables Reales
# ==============================================================================

# 1. CONFIGURACIÓN DE RUTAS ABSOLUTAS
BASE_DIR="/home/plad/podman2026_1/plad_new"
DUMP_PATH="$BASE_DIR/soporte/moodle_new_backup.sql"

# Rutas exactas a tus archivos compartidos
ENV_MOODLE="$BASE_DIR/moodle_old/.env"
ENV_MARIADB="$BASE_DIR/mariadb/.env"

# URLs para el reemplazo masivo
OLD_URL="http://nplad-old.ufps.edu.co:36511"
NEW_URL="https://nplad2.ufps.edu.co"

# Colores para la salida
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' 

echo -e "${GREEN}>>> Iniciando proceso de automatización desde /soporte...${NC}"

# 2. EXTRACCIÓN DE CREDENCIALES (Ajustado a tus archivos reales)
# Buscamos MOODLE_DB_NAME en lugar de MYSQL_DATABASE
DB_NAME=$(grep "MOODLE_DB_NAME" "$ENV_MOODLE" | cut -d '=' -f2 | tr -d '\r' | xargs)
ROOT_PASS=$(grep "MYSQL_ROOT_PASSWORD" "$ENV_MARIADB" | cut -d '=' -f2 | tr -d '\r' | xargs)

# Verificación de extracción
if [[ -z "$DB_NAME" || -z "$ROOT_PASS" ]]; then
    echo -e "${RED}[ERROR] No se pudieron extraer las variables.${NC}"
    echo -e "Verifica que existan: $ENV_MOODLE y $ENV_MARIADB"
    exit 1
fi

# 3. VERIFICACIÓN DEL DUMP
if [ ! -f "$DUMP_PATH" ]; then
    echo -e "${RED}[ERROR] No se encontró el archivo SQL en $DUMP_PATH${NC}"
    exit 1
fi

# 4. PASO 1: IMPORTACIÓN A MARIADB
echo -e "\n${GREEN}1. Importando base de datos a '$DB_NAME'...${NC}"
# Se usa comillas simples en el password para proteger caracteres como $
cat "$DUMP_PATH" | podman exec -i mariadb mysql -u root -p'A2$kLp9#Zm8@qX1vTrC' "$DB_NAME"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}[OK] Importación exitosa.${NC}"
else
    echo -e "${RED}[ERROR] Falló la importación.${NC}"
    exit 1
fi

# 5. PASO 2: REEMPLAZO DE URLS EN MOODLE
echo -e "\n${GREEN}2. Reemplazando URLs ($OLD_URL -> $NEW_URL)...${NC}"
podman exec -u www-data moodle_old php admin/tool/replace/cli/replace.php \
    --search="$OLD_URL" \
    --replace="$NEW_URL" \
    --non-interactive

# 6. PASO 3: LIMPIEZA DE CACHÉ
echo -e "\n${GREEN}3. Purgando cachés de Moodle...${NC}"
podman exec -u www-data moodle_old php admin/cli/purge_caches.php

# 7. VERIFICACIÓN FINAL Y ESTADÍSTICAS
echo -e "\n${GREEN}>>> REALIZANDO VERIFICACIONES FINALES:${NC}"

TOTAL_CURSOS=$(podman exec mariadb mysql -u root -p'A2$kLp9#Zm8@qX1vTrC' "$DB_NAME" -N -s -e "SELECT count(*) FROM mdl_course;")
TAMANO=$(podman exec mariadb mysql -u root -p'A2$kLp9#Zm8@qX1vTrC' "$DB_NAME" -N -s -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE table_schema = '$DB_NAME';")

echo -e "--------------------------------------------------"
echo -e "Cursos importados detectados: ${GREEN}$TOTAL_CURSOS${NC}"
echo -e "Tamaño de la base de datos: ${GREEN}$TAMANO MB${NC}"
echo -e "--------------------------------------------------"
echo -e "${GREEN}¡Sincronización finalizada correctamente!${NC}"