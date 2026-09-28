#!/bin/bash
# encender_moodle.sh
# Ubicación sugerida: /home/plad/encender_moodle.sh
# Propósito: reparación mínima y arranque rápido de contenedores críticos (mariadb, moodle_old).
# No genera logs en disco (salida por stdout/stderr solamente).

# Evitar errores por variables no definidas
set -u
# No usamos -e para que el script intente seguir aunque un paso falle.

LOCK_DIR="$HOME/.local/share/containers/storage/locks"
QUAR_DIR="$HOME/locks-quarantine-$(date +%Y%m%d%H%M%S)"
CRITICAL_CONTAINERS=( "mariadb" "moodle_old" )

echo "=== encender_moodle.sh: inicio $(date '+%F %T') ==="

# 1) Parar servicios user de podman (suavemente) para evitar concurrencia
echo "-> Deteniendo podman.service / podman.socket (user)..."
systemctl --user stop podman.service podman.socket 2>/dev/null || true
sleep 1

# 2) Mover archivos de locks a cuarentena (si existe el directorio)
if [ -d "$LOCK_DIR" ]; then
  mkdir -p "$QUAR_DIR"
  shopt -s nullglob
  moved=0
  for f in "$LOCK_DIR"/*; do
    mv -n "$f" "$QUAR_DIR/" 2>/dev/null || true
    moved=1
  done
  shopt -u nullglob
  if [ "$moved" -eq 1 ]; then
    echo "-> Locks movidos a cuarentena: $QUAR_DIR"
  else
    # Si no había archivos, removemos la carpeta creada vacía
    rmdir --ignore-fail-on-non-empty "$QUAR_DIR" 2>/dev/null || true
    echo "-> No se encontraron archivos de lock en $LOCK_DIR"
  fi
else
  echo "-> $LOCK_DIR no existe, se omite cuarentena."
fi

# 3) Comandos de reparación de Podman (no destructivos)
echo "-> Ejecutando podman system renumber ..."
podman system renumber 2>/dev/null || echo "   (renumber falló o no aplicable)"

echo "-> Ejecutando podman system migrate ..."
podman system migrate 2>/dev/null || echo "   (migrate falló o no aplicable)"

# 4) Reiniciar socket para que el motor acepte conexiones
echo "-> Iniciando podman.socket (user)..."
systemctl --user start podman.socket 2>/dev/null || true
sleep 2

# 5) Intentar arrancar contenedores críticos si existen y no están up
for cname in "${CRITICAL_CONTAINERS[@]}"; do
  exists="$(podman ps -a --filter "name=^${cname}$" --format '{{.Names}}' 2>/dev/null || true)"
  if [ -z "$exists" ]; then
    echo "-> Contenedor '$cname' NO existe. Omitiendo."
    continue
  fi

  status="$(podman ps -a --filter "name=^${cname}$" --format '{{.Status}}' 2>/dev/null || true)"
  if echo "$status" | grep -qi '^Up' ; then
    echo "-> '$cname' ya está UP ($status)."
  else
    echo "-> Iniciando '$cname' (estado previo: ${status:-<no-data>}) ..."
    podman start "$cname" >/dev/null 2>&1 || echo "   ERROR: no se pudo iniciar $cname"
    # esperar un poco para estabilizar
    sleep 3
    # comprobar estado
    newstatus="$(podman ps --filter "name=^${cname}$" --format '{{.Status}}' 2>/dev/null || true)"
    echo "   Nuevo estado de '$cname': ${newstatus:-<no-data>}"
  fi
done

echo "=== encender_moodle.sh: fin $(date '+%F %T') ==="
