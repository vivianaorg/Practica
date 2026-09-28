#!/bin/bash

# ==============================================================================
# SCRIPT DE TRANSFERENCIA DE MOODLEDATA (SIN SUDO)
# ==============================================================================

# 1. CONFIGURACIÓN
# Carpeta donde subiste los archivos por FileZilla
ORIGEN_LOCAL="/home/plad/plad_new/soporte/"
CONTAINER="moodle_old"
# Ruta interna del contenedor que apunta al volumen /data/plad_old/moodledata
DESTINO_INTERNO="/var/www/moodledata"

# Colores para la consola
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}>>> Iniciando automatización de transferencia de datos...${NC}"

# 2. VERIFICACIÓN DE ORIGEN
if [ ! -d "$ORIGEN_LOCAL" ]; then
    echo -e "${RED}[ERROR] No se encuentra la carpeta de origen: $ORIGEN_LOCAL${NC}"
    exit 1
fi

# 3. CREAR CARPETA TEMPORAL DENTRO DEL CONTENEDOR
echo -e "${YELLOW}1. Creando espacio temporal en el contenedor...${NC}"
podman exec -u 0 $CONTAINER mkdir -p /tmp/moodledata_transfer

# 4. COPIAR DESDE EL HOST AL CONTENEDOR
echo -e "${YELLOW}2. Transfiriendo archivos al contenedor (esto puede tardar)...${NC}"
podman cp "$ORIGEN_LOCAL/." "$CONTAINER":/tmp/moodledata_transfer/

# 5. MOVER AL VOLUMEN Y AJUSTAR PERMISOS
echo -e "${YELLOW}3. Moviendo archivos al volumen y corrigiendo permisos...${NC}"
podman exec -u 0 $CONTAINER bash -c "
    # Mover contenido al destino final del volumen
    cp -rp /tmp/moodledata_transfer/* $DESTINO_INTERNO/
    
    # Ajuste de propietario al usuario web (ID 33 es www-data)
    chown -R www-data:www-data $DESTINO_INTERNO
    
    # Ajuste de permisos para carpetas y archivos
    find $DESTINO_INTERNO -type d -exec chmod 770 {} \;
    find $DESTINO_INTERNO -type f -exec chmod 660 {} \;
    
    # Limpiar rastro temporal
    rm -rf /tmp/moodledata_transfer
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ ¡Transferencia completada con éxito!${NC}"
    echo -e "${GREEN}Los archivos ya están en /data/plad_old/moodledata con los permisos correctos.${NC}"
else
    echo -e "${RED}[ERROR] Hubo un fallo en la transferencia interna.${NC}"
fi