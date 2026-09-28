#!/bin/bash
set -e

echo ">>> 🔍 Limpiando Nginx Proxy..."

# 1. Detener y eliminar contenedor nginx-proxy si existe
if podman ps -a --format "{{.Names}}" | grep -q "^nginx-proxy$"; then
    echo ">>> 🚫 Eliminando contenedor nginx-proxy..."
    podman rm -f nginx-proxy
else
    echo ">>> ✅ No hay contenedor nginx-proxy en ejecución."
fi

# 2. Eliminar imagen nginx:stable-alpine si está descargada
if podman images --format "{{.Repository}}:{{.Tag}}" | grep -q "^docker.io/library/nginx:stable-alpine$"; then
    echo ">>> 🗑️ Eliminando imagen nginx:stable-alpine..."
    podman rmi -f docker.io/library/nginx:stable-alpine
else
    echo ">>> ✅ No hay imagen nginx:stable-alpine que eliminar."
fi

# 3. (Opcional) Borrar logs antiguos de nginx-proxy en el host
if [ -d "./logs" ]; then
    echo ">>> 🧹 Limpiando logs antiguos..."
    rm -rf ./logs/*
fi

echo ">>> ✅ Reset de Nginx completado."
echo "Ahora puedes volver a ejecutar ./start.sh para recrear el proxy limpio."
