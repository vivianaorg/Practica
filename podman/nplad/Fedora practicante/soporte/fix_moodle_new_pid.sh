#!/bin/bash

# ============================================================
# SCRIPT DE RESCATE PARA MOODLE (Limpieza de PIDs y Reinicio)
# ============================================================

CONTAINER_NAME="moodle_new"

echo "🔍 Iniciando rescate del contenedor $CONTAINER_NAME..."

# 1. Limpiar archivos de bloqueo internos (.pid)
echo "🧹 Eliminando archivos de bloqueo (.pid) internos..."
podman exec -it $CONTAINER_NAME rm -f /var/run/apache2/apache2.pid
podman exec -it $CONTAINER_NAME rm -f /var/run/apache2/httpd.pid

# 2. Matar procesos huérfanos de Apache que hayan quedado colgados
echo "💀 Terminando procesos huérfanos de Apache..."
podman exec -it $CONTAINER_NAME pkill -9 apache2 2>/dev/null
podman exec -it $CONTAINER_NAME pkill -9 httpd 2>/dev/null

# 3. Reiniciar el gestor de procesos (Supervisord)
echo "🚀 Reiniciando servicio Apache vía Supervisor..."
podman exec -it $CONTAINER_NAME supervisorctl restart apache2

echo "------------------------------------------------------------"
echo "✅ Operación completada."
echo "⏳ Esperando 5 segundos para verificar estado..."
sleep 5

# 4. Mostrar estado final
podman ps --filter "name=$CONTAINER_NAME"
echo "------------------------------------------------------------"