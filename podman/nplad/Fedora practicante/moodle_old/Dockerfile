# Usa como base la imagen ya configurada con supervisord y Moodle 5.0
FROM moodle-cron:5.0

LABEL maintainer="TuNombre <tuemail@ufps.edu.co>"

# Copiar configuración Apache personalizada (no SSL)
COPY apache.conf /etc/apache2/sites-available/apache.conf

# 1. Capa Apache: Activar módulos, configurar sitio e inyectar Timeout y LimitRequestBody
RUN a2enmod headers && \
    a2dissite 000-default.conf && \
    a2ensite apache.conf && \
    echo "Timeout 3600" >> /etc/apache2/apache2.conf && \
    echo "LimitRequestBody 0" >> /etc/apache2/apache2.conf && \
    apachectl configtest || exit 1

# 2. Capa PHP: Crear archivo uploads.ini para forzar límites a 2GB y tiempos a 3600s
RUN echo "upload_max_filesize = 4096M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 4096M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 3600" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time = 3600" >> /usr/local/etc/php/conf.d/uploads.ini

# Exponer puerto HTTP
EXPOSE 80
