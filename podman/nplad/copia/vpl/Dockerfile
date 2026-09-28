# Dockerfile (usa imagen local cargada con podman load)
# Requiere previamente: podman load -i vpl-jail-debian-full.tar
FROM localhost/vpl-jail-debian-full:latest

LABEL maintainer="plad@ufps.edu.co"
LABEL description="VPL Jail System UFPS (Debian base) - UTF-8, utilidades y compatibilidad Podman"

# ---------------------------------------------------------------
# Entorno build/run
# ---------------------------------------------------------------
# NOTA IMPORTANTE: URLPATH debe terminar en '/' para que coincida con la configuración interna
ENV DEBIAN_FRONTEND=noninteractive \
    LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8 \
    TZ=America/Bogota \
    PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/sbin/vpl" \
    URLPATH=/ \
    VPL_JAIL_PORT=80 \
    VPL_JAIL_SECURE_PORT=0 \
    VPL_CERTIFICATES_DIR=/etc/vpl/ssl \
    VPL_JAIL_SSL_CERT_FILE=/etc/vpl/ssl/fullchain.pem \
    VPL_JAIL_SSL_KEY_FILE=/etc/vpl/ssl/privkey.pem

# ---------------------------------------------------------------
# Rutas internas necesarias (coinciden con volumes del compose)
# ---------------------------------------------------------------
RUN mkdir -p /var/log/vpl /tmp /etc/vpl /etc/vpl/ssl; \
    chmod -R 0777 /var/log/vpl /tmp || true; \
    chown -R root:root /var/log/vpl /tmp || true

# ---------------------------------------------------------------
# Variables por defecto (se sobrescriben desde compose/env)
# ---------------------------------------------------------------
# Se asegura que JAILPATH sea '/' para evitar conflictos con mounts externos
ENV JAILPATH=/ \
    LOGDIR=/var/log/vpl \
    TMPDIR=/tmp \
    FIREWALL=0 \
    CHROOT=0 \
    AUTOREMOVE=1 \
    MIN_PRISONER_UGID=10000 \
    MAX_PRISONER_UGID=12000 \
    MAXTIME=1800 \
    MAXMEMORY=512M \
    MAXPROCESSES=150 \
    VPLSERVERHOST=vpl.ufps.edu.co \
    VPLSERVERPORT=80 \
    LOGLEVEL=3

# ---------------------------------------------------------------
# Asegurar ejecutables del VPL en PATH si existen en la base
# ---------------------------------------------------------------
RUN if [ -f /usr/sbin/vpl/vpl-jail-system ]; then \
        chmod +x /usr/sbin/vpl/vpl-jail-system || true; \
        ln -sf /usr/sbin/vpl/vpl-jail-system /usr/local/bin/vpl-jail-system || true; \
    fi; \
    if [ -f /usr/sbin/vpl/vpl-jail-server ]; then \
        chmod +x /usr/sbin/vpl/vpl-jail-server || true; \
    fi

# ---------------------------------------------------------------
# Exponer puerto HTTP (el compose/NGINX hará el manejo de TLS)
# ---------------------------------------------------------------
EXPOSE 80

# ---------------------------------------------------------------
# CORRECCIONES AL ARCHIVO DE CONFIGURACIÓN INTERNO
# Sincronizamos el archivo .conf con las variables ENV
# ---------------------------------------------------------------

# 1. JAILPATH: Cambia /jail por /
RUN sed -i 's|^JAILPATH=/jail$|JAILPATH=/|g' /etc/vpl/vpl-jail-system.conf

# 2. URLPATH: Ajustado a la raíz (/) para compatibilidad con Nginx Proxy
# Usamos pipes | como separador para evitar conflicto con la barra de la URL
RUN sed -i 's|^URLPATH=.*$|URLPATH=/|g' /etc/vpl/vpl-jail-system.conf

# Desactivar restricción de IP para prueba definitiva
RUN sed -i 's/^TASK_ONLY_FROM=/# TASK_ONLY_FROM=/' /etc/vpl/vpl-jail-system.conf

# ===============================================================
# ENTRY / CMD
# ===============================================================
ENTRYPOINT ["/usr/bin/env", "bash", "-lc"]
CMD ["\
    echo '🚀 Iniciando VPL Jail System (UFPS)'; \
    mkdir -p ${LOGDIR} /tmp; \
    chmod -R 0777 ${LOGDIR} /tmp || true; \
    if [ -x /usr/sbin/vpl/vpl-jail-system ]; then \
        echo '→ lanzando /usr/sbin/vpl/vpl-jail-system start_foreground (exec)'; \
        exec /usr/sbin/vpl/vpl-jail-system start_foreground || { \
            echo '❌ fallo start_foreground — mostrando ldd para depuración:'; \
            ldd /usr/sbin/vpl/vpl-jail-system || true; \
            echo '--- /var/log/vpl ---'; ls -la /var/log/vpl || true; \
            sleep 2; exit 1; \
        }; \
    else \
        echo '❌ /usr/sbin/vpl/vpl-jail-system no encontrado'; exit 1; \
    fi \
"]

# ---------------------------------------------------------------
# Healthcheck
# ---------------------------------------------------------------
HEALTHCHECK --interval=20s --timeout=5s --start-period=30s --retries=3 \
    CMD bash -lc 'if command -v curl >/dev/null 2>&1; then curl -sSf http://127.0.0.1:80${URLPATH}ping.html >/dev/null || exit 1; else exit 0; fi'