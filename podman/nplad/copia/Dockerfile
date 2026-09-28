# Usa como base la imagen ya configurada con supervisord y Moodle 5.0
FROM moodle-cron:5.0

LABEL maintainer="TuNombre <tuemail@ufps.edu.co>"

# Copiar configuración Apache personalizada (no SSL)
COPY ssl/apache.conf /etc/apache2/sites-available/apache.conf

# Activar módulos necesarios y configurar sitio Apache
RUN a2enmod headers && \
    a2dissite 000-default.conf && \
    a2ensite apache.conf && \
    apachectl configtest || exit 1

# Instalar ClamAV y su base de datos
RUN apt-get update && \
    apt-get install -y clamav clamav-daemon && \
    freshclam || true

# Configurar freshclam para ejecutarse en primer plano y actualizar 12 veces al día
RUN sed -i 's/^#Foreground .*$/Foreground true/' /etc/clamav/freshclam.conf && \
    sed -i 's/^Checks .*$/Checks 12/' /etc/clamav/freshclam.conf

# Añadir configuración de freshclam al supervisord
RUN echo "[program:freshclam]" > /etc/supervisor/conf.d/freshclam.conf && \
    echo "command=/usr/bin/freshclam -d --foreground=true" >> /etc/supervisor/conf.d/freshclam.conf && \
    echo "autostart=true" >> /etc/supervisor/conf.d/freshclam.conf && \
    echo "autorestart=true" >> /etc/supervisor/conf.d/freshclam.conf && \
    echo "stdout_logfile=/var/log/freshclam.log" >> /etc/supervisor/conf.d/freshclam.conf && \
    echo "stderr_logfile=/var/log/freshclam.err" >> /etc/supervisor/conf.d/freshclam.conf

# Exponer puerto HTTP
EXPOSE 80
