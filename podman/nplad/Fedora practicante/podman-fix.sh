#!/bin/bash
# ================================================================
# Podman Auto-Heal & Light Backup + Restart Critical Containers
# User: plad
# Path: /home/plad/docker/podman-fix.sh
#
# Detecta errores "acquiring lock ... file exists", los corrige,
# crea un backup ligero (solo metadatos: libpod, overlay-containers, locks),
# mueve los locks a cuarentena y, si procede, reinicia los contenedores críticos.
#
# Log: /home/plad/docker/podman-fix.log
# Backups: /home/plad/docker/podman-backup/podman-locks-YYYYMMDDHHMM.tgz
# Locks cuarentena: /home/plad/docker/locks-quarantine-YYYYMMDDHHMM/
# Limpieza: borra backups > 7 días
# Ejecutado periódicamente por systemd user timer (cada 10 min).
# ================================================================

set -euo pipefail

LOG_DIR="/home/plad/docker"
LOG_FILE="$LOG_DIR/podman-fix.log"
BACKUP_DIR="$LOG_DIR/podman-backup"
STORAGE_DIR="/home/plad/.local/share/containers/storage"
# Subdirectorios esenciales para un backup ligero (relativos a STORAGE_DIR)
ESSENTIALS=( "libpod" "overlay-containers" "locks" )

# Contenedores críticos a asegurar (ajusta si tus nombres son distintos)
CRITICAL_CONTAINERS=( "mariadb" "moodle_old" )

DATE="$(date '+%F %T')"
TS="$(date +%Y%m%d%H%M)"
QUARANTINE_DIR="$LOG_DIR/locks-quarantine-$TS"

mkdir -p "$LOG_DIR" "$BACKUP_DIR"

echo "[$DATE] --- Ejecución automática del monitor Podman ---" >> "$LOG_FILE"

# Ejecutar podman ps y capturar salida (no hacer fallar todo si lanza error)
OUT="$(podman ps -a 2>&1 || true)"

# Función: iniciar contenedor si existe y no está "Up"
start_if_needed() {
    local cname="$1"
    local now="$(date '+%F %T')"
    local status
    status="$(podman ps -a --filter "name=^${cname}$" --format '{{.Status}}' 2>/dev/null || true)"
    if [ -z "$status" ]; then
        echo "[$now] Contenedor '$cname' no encontrado (no intentar start)." >> "$LOG_FILE"
        return 0
    fi
    if echo "$status" | grep -qi '^Up' ; then
        echo "[$now] Contenedor '$cname' ya está en estado: $status" >> "$LOG_FILE"
        return 0
    fi
    echo "[$now] Intentando iniciar contenedor '$cname' (estado actual: $status)..." >> "$LOG_FILE"
    if podman start "$cname" >> "$LOG_FILE" 2>&1; then
        echo "[$now] Contenedor '$cname' iniciado correctamente." >> "$LOG_FILE"
        sleep 3
        newstatus="$(podman ps --filter "name=^${cname}$" --format '{{.Status}}' 2>/dev/null || true)"
        echo "[$now] Nuevo estado '$cname': ${newstatus:-<no-data>}" >> "$LOG_FILE"
    else
        echo "[$now] ERROR: no se pudo iniciar '$cname' (ver logs arriba)." >> "$LOG_FILE"
    fi
    return 0
}

# Detectar el error de locks
if echo "$OUT" | grep -q "acquiring lock .* file exists"; then
    echo "[$DATE] ERROR detectado: conflicto de locks." >> "$LOG_FILE"

    # 1) Detener servicios de usuario Podman (intentar suavemente)
    systemctl --user stop podman.service podman.socket 2>>"$LOG_FILE" || true
    sleep 1

    # 2) Backup ligero de metadatos (solo los subdirs existentes)
    BACKUP_FILE="$BACKUP_DIR/podman-locks-$TS.tgz"
    TO_BACKUP=()
    for d in "${ESSENTIALS[@]}"; do
        if [ -d "$STORAGE_DIR/$d" ]; then
            TO_BACKUP+=("$d")
        fi
    done

    if [ "${#TO_BACKUP[@]}" -gt 0 ]; then
        # Crear backup comprimido (solo metadatos importantes)
        tar czf "$BACKUP_FILE" -C "$STORAGE_DIR" "${TO_BACKUP[@]}" >> "$LOG_FILE" 2>&1 || echo "[$DATE] Aviso: tar devolvió error (no crítico)" >> "$LOG_FILE"
        echo "[$DATE] Backup ligero creado en $BACKUP_FILE (elementos: ${TO_BACKUP[*]})" >> "$LOG_FILE"
    else
        echo "[$DATE] No se encontraron subdirectorios esenciales en $STORAGE_DIR; omitiendo backup." >> "$LOG_FILE"
    fi

    # 3) Mover archivos de locks a cuarentena (si existen)
    if [ -d "$STORAGE_DIR/locks" ]; then
        # comprobar si hay archivos
        shopt -s nullglob
        files=( "$STORAGE_DIR/locks/"* )
        shopt -u nullglob
        if [ "${#files[@]}" -gt 0 ]; then
            mkdir -p "$QUARANTINE_DIR"
            echo "[$DATE] Moviendo ${#files[@]} archivos de locks a cuarentena: $QUARANTINE_DIR" >> "$LOG_FILE"
            # mover individualmente para evitar error si algunos no pueden moverse
            for f in "${files[@]}"; do
                mv "$f" "$QUARANTINE_DIR"/ 2>>"$LOG_FILE" || echo "[$DATE] Aviso: no se pudo mover $f" >> "$LOG_FILE"
            done
        else
            echo "[$DATE] Directorio locks existe pero está vacío." >> "$LOG_FILE"
        fi
    else
        echo "[$DATE] No existe $STORAGE_DIR/locks; nada que mover." >> "$LOG_FILE"
    fi

    # 4) Intentar reparar (renumber + migrate)
    echo "[$DATE] Ejecutando podman system renumber..." >> "$LOG_FILE"
    podman system renumber >> "$LOG_FILE" 2>&1 || echo "[$DATE] Falló renumber (ver log)" >> "$LOG_FILE"
    echo "[$DATE] Ejecutando podman system migrate..." >> "$LOG_FILE"
    podman system migrate >> "$LOG_FILE" 2>&1 || echo "[$DATE] Falló migrate (ver log)" >> "$LOG_FILE"

    # 5) Reiniciar socket (servicio)
    systemctl --user start podman.socket 2>>"$LOG_FILE" || true
    echo "[$DATE] Reparación completada; socket reiniciado." >> "$LOG_FILE"

    # 6) Intentar levantar contenedores críticos (si estaban detenidos)
    for c in "${CRITICAL_CONTAINERS[@]}"; do
        start_if_needed "$c" || true
    done

else
    echo "[$DATE] Sin errores de locks, todo OK." >> "$LOG_FILE"
fi

# Registrar estado de servicios y almacenamiento
systemctl --user status podman.socket --no-pager >> "$LOG_FILE" 2>&1 || true
df -h /home >> "$LOG_FILE" 2>&1 || true

# Volcar listado actual de contenedores para diagnóstico
echo "[$DATE] Lista de contenedores (podman ps -a):" >> "$LOG_FILE"
podman ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Image}}\t{{.Ports}}' >> "$LOG_FILE" 2>&1 || true

# Limpieza de backups antiguos (más de 7 días)
find "$BACKUP_DIR" -type f -name "podman-locks-*.tgz" -mtime +7 -print -delete >> "$LOG_FILE" 2>&1 || true
echo "[$DATE] Limpieza de backups > 7 días realizada." >> "$LOG_FILE"

echo "------------------------------------------------------------" >> "$LOG_FILE"
