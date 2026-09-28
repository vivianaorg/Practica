#!/bin/bash
set -e

# --- CONFIGURACIÓN DE CONTENEDORES ---
OLD_CONTAINER="moodle_old"
NEW_CONTAINER="moodle_new"
TEMP_DIR="./temp_migration"

# Colores para la consola
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🚀 INICIANDO SINCRONIZACIÓN INTEGRAL (CÓDIGO + DATOS ESENCIALES)${NC}"
mkdir -p "$TEMP_DIR"

# ==============================================================================
# 1. SINCRONIZACIÓN DE CÓDIGO (PLUGINS)
# ==============================================================================
echo -e "\n${YELLOW}📦 Paso 1: Copiando código de plugins entre contenedores...${NC}"
PLUGIN_TYPES=(
    "mod" "blocks" "theme" "local" "auth" "enrol" "filter" 
    "course/format" "question/type" "question/behaviour" 
    "mod/customcert/element" "mod/vpl/jail" "report" "availability/condition"
)

for type in "${PLUGIN_TYPES[@]}"; do
    # Verificamos dentro del contenedor para evitar errores de ruta
    if podman exec "$OLD_CONTAINER" [ -d "/var/www/html/$type" ]; then
        echo -e "   🔄 Sincronizando código: $type..."
        rm -rf "$TEMP_DIR/transfer"
        
        # Copiamos del viejo al host y luego al nuevo
        podman cp "$OLD_CONTAINER:/var/www/html/$type" "$TEMP_DIR/transfer"
        podman exec "$NEW_CONTAINER" mkdir -p "/var/www/html/$type"
        podman cp "$TEMP_DIR/transfer/." "$NEW_CONTAINER:/var/www/html/$type/"
    fi
done

# ==============================================================================
# 2. SINCRONIZACIÓN QUIRÚRGICA DE MOODLEDATA (MODO SEGURO)
# ==============================================================================
echo -e "\n${YELLOW}📂 Paso 2: Sincronizando carpetas de datos esenciales...${NC}"

# Carpetas que SÍ contienen recursos del sistema (no de alumnos)
CARPETAS_A_COPIAR=("customcert" "lang")

for carpeta in "${CARPETAS_A_COPIAR[@]}"; do
    echo "   🔍 Verificando $carpeta en moodle_old..."
    # Usamos podman exec para verificar existencia SIN tocar permisos del host
    if podman exec "$OLD_CONTAINER" [ -d "/var/www/moodledata/$carpeta" ]; then
        echo "   ✅ Copiando recursos: $carpeta..."
        
        rm -rf "$TEMP_DIR/$carpeta"
        # 1. Extraer del contenedor viejo a temporal
        podman cp "$OLD_CONTAINER:/var/www/moodledata/$carpeta" "$TEMP_DIR/$carpeta"
        # 2. Inyectar en el contenedor nuevo
        podman cp "$TEMP_DIR/$carpeta" "$NEW_CONTAINER:/var/www/moodledata/"
    else
        echo "   ℹ️ Carpeta $carpeta no encontrada o vacía. Se omite."
    fi
done

# Carpetas que deben existir (vacías) para evitar errores de plugins
CARPETAS_A_CREAR=("vpl" "filedir" "cache" "localcache" "sessions" "temp" "trashdir")

for carpeta in "${CARPETAS_A_CREAR[@]}"; do
    echo "   📁 Asegurando carpeta estructural: $carpeta"
    # Creamos la carpeta DESDE ADENTRO del contenedor (evita Error Permiso Denegado)
    podman exec "$NEW_CONTAINER" mkdir -p "/var/www/moodledata/$carpeta"
done

# ==============================================================================
# 3. AJUSTE DE PERMISOS (CÓDIGO Y DATOS)
# ==============================================================================
echo -e "\n${YELLOW}🔧 Paso 3: Ajustando permisos...${NC}"
# Permisos del código
podman exec "$NEW_CONTAINER" chown -R www-data:www-data /var/www/html
# Permisos de moodledata
podman exec "$NEW_CONTAINER" chown -R www-data:www-data /var/www/moodledata

# ==============================================================================
# 4. ACTIVACIÓN DE PLUGINS (CLI UPGRADE)
# ==============================================================================
echo -e "\n${YELLOW}⚙️ Paso 4: Registrando plugins en la base de datos...${NC}"
# Esto elimina el botón de "Para instalar" y lo pone en "Instalado"
podman exec "$NEW_CONTAINER" php admin/cli/upgrade.php --non-interactive --allow-unstable

echo -e "🧹 Limpiando caché de Moodle..."
podman exec "$NEW_CONTAINER" php admin/cli/purge_caches.php

# Limpieza de temporales del host
rm -rf "$TEMP_DIR"

echo -e "\n${GREEN}🎉 ¡PROCESO FINALIZADO CON ÉXITO!${NC}"
echo -e "Revisa tu Moodle en: ${BLUE}https://test-nplad.ufps.edu.co/admin/plugins.php${NC}"