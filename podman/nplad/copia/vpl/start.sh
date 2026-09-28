#!/usr/bin/env bash
set -euo pipefail

# ==========================================================
# start.sh (ajustado para usar podman-compose (Python) v1.5.0)
# - Guarda toda la salida en $HERE/start.log (stdout+stderr)
# - Fuerza uso de podman-compose (Python)
# - Maneja rootless socket (XDG_RUNTIME_DIR / podman.socket)
# - Carga imagen desde .tar o build local sin pull
# ==========================================================

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOGFILE="$HERE/start.log"
CERT_DIR="$(realpath "$HERE/../nginx-proxy/ssl" 2>/dev/null || true)"
COMPOSE_FILE="$HERE/podman-compose.yml"

BASE_TAR="$HERE/vpl-jail-debian-full.tar"
TARGET_IMAGE="localhost/vpl-jail-system:latest"
ALT_LOADED_IMAGE="localhost/vpl-jail-debian-full:latest"
LOGS_DIR="$HERE/logs"
# --- JAIL_DIR ELIMINADO ---
DOCKERFILE="$HERE/Dockerfile"

# --- Redirect all output (stdout+stderr) to logfile while still showing on console ---
# Use a process substitution with tee so user sees output and it is appended to log.
exec > >(tee -a "$LOGFILE") 2>&1

START_TS=$(date +%s)
echo "=================================================================="
echo " START - $(date '+%F %T')"
echo " Script: $HERE/$(basename "$0")"
echo " Log: $LOGFILE"
echo "=================================================================="
echo

# ----------------------------------------------------------
# Helper: on exit write footer (duration + rc)
# ----------------------------------------------------------
_on_exit() {
    RC=$?
    END_TS=$(date +%s)
    DURATION=$((END_TS-START_TS))
    echo
    echo "=================================================================="
    echo " END   - $(date '+%F %T')"
    echo " Return code: $RC"
    echo " Duration: ${DURATION}s"
    echo "=================================================================="
    # preserve original exit code
    exit $RC
}
trap _on_exit EXIT

# ----------------------------------------------------------
# Preparar entorno runtime para rootless Podman
# ----------------------------------------------------------
export XDG_RUNTIME_DIR="${XDG_RUNTIME_DIR:-/run/user/$(id -u)}"
PODMAN_ROOTLESS_SOCK="$XDG_RUNTIME_DIR/podman/podman.sock"

if [[ -S "$PODMAN_ROOTLESS_SOCK" ]]; then
    echo "✅ Socket rootless detectado: $PODMAN_ROOTLESS_SOCK"
else
    echo "⚠️ Socket rootless no detectado aún: $PODMAN_ROOTLESS_SOCK"
fi

# ----------------------------------------------------------
# Directorios (SECCIÓN SIMPLIFICADA)
# ----------------------------------------------------------
mkdir -p "${CERT_DIR:-/dev/null}" "$LOGS_DIR"
chmod 0777 "$LOGS_DIR" || true
# (Todas las referencias a JAIL_DIR han sido eliminadas)

if command -v chcon >/dev/null 2>&1; then
    chcon -R -t container_file_t "$LOGS_DIR" "${CERT_DIR:-/dev/null}" 2>/dev/null || true
fi

echo "✅ Carpetas listas: $LOGS_DIR"
echo

# ----------------------------------------------------------
# detectar podman y podman-compose (python)
# ----------------------------------------------------------
PODMAN_BIN="$(command -v podman || true)"
PODMAN_COMPOSE_BIN="$(command -v podman-compose || true)"

if [[ -z "$PODMAN_BIN" ]]; then
    echo "❌ podman no está instalado o no está en PATH. Instálalo antes de continuar."
    exit 1
fi
echo "✅ Podman detectado en: $PODMAN_BIN"

if [[ -z "$PODMAN_COMPOSE_BIN" ]]; then
    echo "❌ podman-compose (Python) no encontrado en PATH. Instálalo (v1.5.0) y vuelve a intentarlo."
    exit 1
fi
echo "✅ Usaremos podman-compose (Python) en: $PODMAN_COMPOSE_BIN"
echo

# ----------------------------------------------------------
# asegurar conexión a daemon/socket (rootless)
# ----------------------------------------------------------
echo ">>> Verificando conexión con Podman (podman info)..."
if ! "$PODMAN_BIN" info >/dev/null 2>&1; then
    echo "⚠️ podman info falló: intentando arrancar podman.socket (user systemd) si está disponible..."
    if command -v systemctl >/dev/null 2>&1; then
        if systemctl --user status podman.socket &>/dev/null; then
            systemctl --user start podman.socket || true
            sleep 1
        elif sudo systemctl status podman.socket &>/dev/null 2>&1; then
            sudo systemctl start podman.socket || true
            sleep 1
        fi
    fi
fi

if ! "$PODMAN_BIN" info >/dev/null 2>&1; then
    echo
    echo "❌ No se pudo conectar al socket de Podman. Asegúrate de 'systemctl --user start podman.socket' o de que XDG_RUNTIME_DIR sea correcto."
    exit 1
fi
echo "✅ Podman conectado correctamente."
echo

# ----------------------------------------------------------
# Cargar imagen desde TAR o construir
# ----------------------------------------------------------
echo ">>> Verificando imagen target: $TARGET_IMAGE"
if ! "$PODMAN_BIN" image exists "$TARGET_IMAGE"; then
    if [[ -f "$BASE_TAR" ]]; then
        echo "📦 Cargando imagen desde TAR: $BASE_TAR"
        LOAD_OUT="$("$PODMAN_BIN" load -i "$BASE_TAR" 2>&1 || true)"
        echo "$LOAD_OUT"
        LOADED_NAME="$(printf "%s\n" "$LOAD_OUT" | sed -n 's/^Loaded image: //p' | tail -n1 || true)"
        if [[ -z "$LOADED_NAME" ]]; then
            RECENT="$("$PODMAN_BIN" images --format '{{.Repository}}:{{.Tag}} {{.CreatedAt}}' | grep -Ei 'jail|vpl' | head -n1 | awk '{print $1}' || true)"
            LOADED_NAME="$RECENT"
        fi
        if [[ -n "$LOADED_NAME" ]]; then
            echo "🔖 Etiquetando $LOADED_NAME -> $TARGET_IMAGE"
            "$PODMAN_BIN" tag "$LOADED_NAME" "$TARGET_IMAGE" || true
            "$PODMAN_BIN" tag "$LOADED_NAME" "$ALT_LOADED_IMAGE" >/dev/null 2>&1 || true
        fi
    else
        if [[ -f "$DOCKERFILE" ]]; then
            echo "🔧 Construyendo imagen local desde Dockerfile (sin pull remoto)..."
            "$PODMAN_BIN" build --pull=false -t "$TARGET_IMAGE" "$HERE"
        else
            echo "❌ No existe $BASE_TAR ni Dockerfile. No puedo obtener la imagen."
            exit 1
        fi
    fi
else
    echo "✅ Imagen $TARGET_IMAGE ya presente."
fi
echo

# ----------------------------------------------------------
# Limpieza previa
# ----------------------------------------------------------
# --- BLOQUE DE VALIDACIÓN DE CONF_FILE ELIMINADO ---

echo "🧹 Apagando stack previo (si existe)..."
set +e
"$PODMAN_COMPOSE_BIN" -f "$COMPOSE_FILE" down --remove-orphans >/dev/null 2>&1 || true
"$PODMAN_BIN" rm -f vpl-service >/dev/null 2>&1 || true
set -e
echo "✅ Limpieza completada."
echo

# ----------------------------------------------------------
# Levantar servicio con podman-compose (Python)
# ----------------------------------------------------------
echo "🚀 Levantando servicio con: $PODMAN_COMPOSE_BIN -f $COMPOSE_FILE up -d --build"
set +e
"$PODMAN_COMPOSE_BIN" -f "$COMPOSE_FILE" up -d --build
RC=$?
set -e

if [[ $RC -ne 0 ]]; then
    echo "❌ Falló el arranque del compose (exit code $RC). Diagnóstico básico:"
    echo "-> Podman version:"
    "$PODMAN_BIN" --version || true
    echo "-> Podman info (intento):"
    "$PODMAN_BIN" info || true
    echo
    echo "-> Validar YAML / config del compose (podman-compose):"
    "$PODMAN_COMPOSE_BIN" -f "$COMPOSE_FILE" config || true
    echo
    echo "-> Últimos logs (si existe contenedor):"
    "$PODMAN_BIN" logs --tail 200 vpl-service || true
    exit $RC
fi

# ==========================================================
# --- ¡BLOQUE AJUSTADO! ---
# Esperar que el contenedor esté 'healthy' en lugar de 'running'
# ==========================================================
echo
echo "⏳ Esperando a que el contenedor esté 'healthy' (puede tardar ~30s)..."

# Bucle de espera (hasta 15 intentos, 3s c/u = 45s max)
for i in {1..15}; do
    # Obtenemos el estado actual
    STATUS_INFO="$("$PODMAN_BIN" ps --filter "name=vpl-service" --format '{{.Status}}' 2>/dev/null || true)"

    if echo "$STATUS_INFO" | grep -q '(healthy)'; then
        echo "✅ Contenedor 'vpl-service' en estado 'healthy'."
        break # Éxito, salimos del bucle
    
    elif echo "$STATUS_INFO" | grep -q '(unhealthy)'; then
        echo "❌ El contenedor ha fallado el healthcheck."
        "$PODMAN_BIN" logs --tail 100 vpl-service || true
        exit 1 # Falla, salimos del script
    
    elif ! echo "$STATUS_INFO" | grep -q 'Up'; then
        echo "❌ El contenedor se ha detenido inesperadamente."
        "$PODMAN_BIN" logs --tail 100 vpl-service || true
        exit 1 # Falla, salimos del script
    fi
    
    echo "⌛ Intento $i/15... (estado actual: ${STATUS_INFO:-starting})"
    sleep 3
done

# Comprobación final después del bucle
if ! "$PODMAN_BIN" ps --filter "name=vpl-service" --format '{{.Status}}' | grep -q '(healthy)'; then
    echo "❌ El contenedor no alcanzó 'healthy' a tiempo. Últimos logs:"
    "$PODMAN_BIN" logs --tail 200 vpl-service || true
    exit 1
fi

# Ya no necesitamos el 'podman exec curl' porque el estado 'healthy' lo confirma
echo
echo "🔍 Comprobación final (versión):"
"$PODMAN_BIN" exec vpl-service sh -c "/usr/sbin/vpl/vpl-jail-system status 2>/dev/null || true"

echo
echo "✅ VPL Jail System desplegado."
echo "🌐 URL de prueba: http://localhost:8092/vplkeyUFPS2025"