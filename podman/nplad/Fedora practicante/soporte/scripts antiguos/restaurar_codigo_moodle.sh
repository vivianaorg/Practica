#!/bin/bash

# ==============================================================================
# SCRIPT DE RESTAURACIÓN DE CÓDIGO FUENTE (PROD -> TEST)
# Ubicación: /home/plad/plad_new/soporte/restaurar_codigo_moodle.sh
# ==============================================================================

# 1. CONFIGURACIÓN DE RUTAS
# Obtiene la ruta absoluta de la carpeta donde reside este script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# El archivo .tar.gz debe estar en la misma carpeta que el script
SOURCE_TAR="$SCRIPT_DIR/codigo_fuente_moodle_old.tar.gz"
CONTAINER="moodle_old"
HTML_PATH="/var/www/html"

# Colores para la terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}>>> Iniciando restauración en $CONTAINER desde $SCRIPT_DIR...${NC}"

# 2. VERIFICACIÓN DE ARCHIVO
if [ ! -f "$SOURCE_TAR" ]; then
    echo -e "${RED}[ERROR] No se encuentra el archivo:$NC"
    echo -e "        $SOURCE_TAR"
    exit 1
fi

# 3. RESPALDAR CONFIG.PHP ACTUAL (EL DE PRUEBAS)
# Se guarda temporalmente en la carpeta 'soporte'
echo -e "${BLUE}1. Respaldando config.php de pruebas en carpeta soporte...${NC}"
podman exec $CONTAINER cat $HTML_PATH/config.php > "$SCRIPT_DIR/config.php.bak"
if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] No se pudo extraer el config.php del contenedor.${NC}"
    exit 1
fi

# 4. LIMPIAR DIRECTORIO Y COPIAR CÓDIGO
echo -e "${BLUE}2. Transfiriendo y extrayendo código de producción...${NC}"
# Copiamos el tarball desde la ruta absoluta al contenedor
podman cp "$SOURCE_TAR" "$CONTAINER":/tmp/moodle_code.tar.gz

# Limpieza y extracción interna
podman exec -u 0 $CONTAINER bash -c "
    rm -rf $HTML_PATH/*
    tar -xzf /tmp/moodle_code.tar.gz -C $HTML_PATH/ --strip-components=1
    rm /tmp/moodle_code.tar.gz
"

# 5. RESTAURAR EL CONFIG.PHP DE PRUEBAS
echo -e "${BLUE}3. Restaurando config.php con las credenciales de pruebas...${NC}"
podman cp "$SCRIPT_DIR/config.php.bak" "$CONTAINER":"$HTML_PATH/config.php"
rm "$SCRIPT_DIR/config.php.bak"

# 6. AJUSTE DE PERMISOS (Basado en tu configuración de start.sh)
echo -e "${BLUE}4. Sincronizando permisos de archivos...${NC}"
podman exec -u 0 $CONTAINER bash -c "
    chown -R www-data:www-data $HTML_PATH
    find $HTML_PATH -type d -exec chmod 755 {} \;
    find $HTML_PATH -type f -exec chmod 644 {} \;
"

# 7. LIMPIEZA DE CACHÉ
echo -e "${BLUE}5. Purgando cachés de Moodle...${NC}"
podman exec -u www-data $CONTAINER php $HTML_PATH/admin/cli/purge_caches.php

echo -e "\n${GREEN}==================================================${NC}"
echo -e "${GREEN}   ¡CÓDIGO RESTAURADO CORRECTAMENTE!${NC}"
echo -e "${GREEN}   Origen: $SOURCE_TAR${NC}"
echo -e "${GREEN}==================================================${NC}"