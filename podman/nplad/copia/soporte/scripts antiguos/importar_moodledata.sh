#!/bin/bash
# ========================================================================
# Script para importación masiva de moodledata en entornos Rootless
# ========================================================================

# Configuraciones de rutas basadas en tu estructura actual
TAR_SOURCE="/data/moodledata_old.tar.gz"
TARGET_DIR="/data/plad_old/moodledata"
CONTAINER_NAME="moodle_old"

echo "⏳ Iniciando proceso de importación automatizada..."

# 1. Limpieza de seguridad usando privilegios de contenedor
if [ -d "$TARGET_DIR" ]; then
    echo "🧹 Limpiando directorio de destino..."
    podman unshare rm -rf "$TARGET_DIR"/*
fi

# Asegurar que el directorio base existe
mkdir -p "$TARGET_DIR"

# 2. Extracción con podman unshare para preservar mapeo de IDs
echo "📦 Extrayendo archivos (esto puede tardar por los 130GB)..."
podman unshare tar -xzf "$TAR_SOURCE" -C "$TARGET_DIR" --strip-components=3

# 3. Ajuste de permisos preventivo en el Host
echo "🔑 Ajustando permisos de sistema de archivos..."
podman unshare chmod -R 777 "$TARGET_DIR"

# 4. Verificación de contenido
if [ -d "$TARGET_DIR/filedir" ]; then
    echo "✅ Extracción exitosa. Estructura de Moodle detectada."
else
    echo "❌ Error: La estructura de archivos no parece correcta. Revisa el --strip-components."
    exit 1
fi

# 5. Sincronización de permisos con el usuario interno del contenedor (www-data)
# Solo si el contenedor ya está corriendo
if podman ps --format "{{.Names}}" | grep -q "$CONTAINER_NAME"; then
    echo "🔄 Sincronizando dueño de archivos con www-data..."
    podman exec -u 0 -it "$CONTAINER_NAME" chown -R www-data:www-data /var/www/moodledata
    
    echo "🧹 Purgando cachés de Moodle..."
    podman exec -u www-data -it "$CONTAINER_NAME" php /var/www/html/admin/cli/purge_caches.php
fi

echo "✨ ¡Proceso completado exitosamente!"