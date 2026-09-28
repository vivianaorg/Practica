#!/bin/bash
set -e

echo "🛑 Deteniendo y eliminando contenedores de Moodle..."
for container in moodle_old moodle_new phpmyadmin mariadb; do
    if podman ps -a --format '{{.Names}}' | grep -q "^${container}$"; then
        podman stop $container || true
        podman rm $container || true
        echo "✅ Contenedor $container eliminado."
    else
        echo "ℹ️ Contenedor $container no existe, se omite."
    fi
done

echo ""
echo "🧹 Eliminando imágenes locales y de Docker Hub..."
podman rmi -f localhost/moodle_old:5.0 || true
podman rmi -f localhost/moodle_new:5.0 || true
podman rmi -f localhost/moodle-cron:5.0 || true
podman rmi -f localhost/mariadb:10.11 || true
podman rmi -f localhost/phpmyadmin:5.2.2 || true
podman rmi -f localhost/moodle_cron:moodle5 || true 
podman rmi docker.io/library/mariadb:10.11 || true
podman rmi docker.io/phpmyadmin/phpmyadmin:latest || true
echo "✅ Imágenes locales eliminadas."

echo ""
echo "📂 Verificando volúmenes de Podman..."
M_VOLUME_PREFIX="moodle"
VOLUMES=$(podman volume ls --format "{{.Name}}" | grep "$M_VOLUME_PREFIX" || true)
if [[ -n "$VOLUMES" ]]; then
    for vol in $VOLUMES; do
        podman volume rm -f $vol || true
        echo "✅ Volumen $vol eliminado."
    done
else
    echo "ℹ️ No hay volúmenes de Moodle para eliminar."
fi

echo ""
echo "🔥 Forzando eliminación de bloqueos en volúmenes..."
podman unshare rm -rf /tmp/mysql* || true

echo ""
echo "🧹 Eliminando la carpeta local ./data (el paso más crítico)..."
DATA_DIR="../data"
if [[ -d "$DATA_DIR" ]]; then
    echo "    → Eliminando todo el contenido de $DATA_DIR usando podman unshare..."
    # Se usa podman unshare para gestionar los permisos de los espacios de nombres en rootless Podman
    podman unshare rm -rf "$DATA_DIR"
    
    if [[ ! -d "$DATA_DIR" ]]; then
        echo "✅ Carpeta $DATA_DIR eliminada por completo."
    else
        echo "❌ No se pudo eliminar $DATA_DIR. Intenta manualmente: podman unshare rm -rf $DATA_DIR"
    fi
else
    echo "ℹ️ Carpeta $DATA_DIR no existe, se omite."
fi

echo ""
echo "🌐 Limpiando redes residuales..."
podman network rm plad_new_default || true
podman network rm fedorapracticante_default || true
podman network prune -f

echo "🧹 Limpieza profunda de Podman (Storage y Cache)..."
podman system prune -f

echo ""
echo "✨ Reset de Moodle completado. Ahora puede ejecutar su start.sh principal para una instalación limpia."
