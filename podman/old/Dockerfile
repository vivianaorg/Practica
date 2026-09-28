FROM almalinux:8

# Utilidades base
RUN dnf install -y epel-release \
    && dnf install -y dnf-plugins-core \
    && dnf config-manager --set-enabled powertools \
    && dnf install -y wget tar gcc make gcc-c++ git curl \
    && dnf clean all

# Repositorio Remi (PHP 5.6)
RUN dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm \
    && dnf clean all

# Instalar PHP 5.6 SCL desde Remi
RUN dnf install -y \
        php56 \
        php56-php-cli \
        php56-php-common \
        php56-php-mysqlnd \
        php56-php-pgsql \
        php56-php-gd \
        php56-php-mbstring \
        php56-php-xml \
        php56-php-curl \
        php56-php-zip \
        php56-php-soap \
        php56-php-opcache \
        php56-php-json \
        php56-php-pdo \
        php56-php-pear \
        php56-php-devel \
        php56-php-process \
        libaio \
    && dnf clean all

# Symlinks de PHP
RUN ln -s /opt/remi/php56/root/usr/bin/php /usr/bin/php \
    && ln -s /opt/remi/php56/root/usr/bin/phpize /usr/bin/phpize \
    && ln -s /opt/remi/php56/root/usr/bin/php-config /usr/bin/php-config \
    && ln -s /opt/remi/php56/root/usr/bin/pear /usr/bin/pear \
    && ln -s /opt/remi/php56/root/usr/bin/pecl /usr/bin/pecl

ENV PATH="/opt/remi/php56/root/usr/bin:${PATH}" \
    ORACLE_HOME=/usr/lib/oracle/11.2/client64 \
    LD_LIBRARY_PATH=/usr/lib/oracle/11.2/client64/lib \
    TNS_ADMIN=/usr/lib/oracle/11.2/client64/lib/network/admin

# Oracle Instant Client 11.2
RUN mkdir -p /opt/oracle/rpm \
    && cd /opt/oracle/rpm \
    && wget -q https://download.oracle.com/otn/linux/instantclient/11204/oracle-instantclient11.2-basic-11.2.0.4.0-1.x86_64.rpm \
    && wget -q https://download.oracle.com/otn/linux/instantclient/11204/oracle-instantclient11.2-devel-11.2.0.4.0-1.x86_64.rpm \
    && rpm -ivh oracle-instantclient11.2-basic-11.2.0.4.0-1.x86_64.rpm \
    && rpm -ivh oracle-instantclient11.2-devel-11.2.0.4.0-1.x86_64.rpm \
    && rm -rf /opt/oracle/rpm

# tnsnames.ora
RUN mkdir -p $TNS_ADMIN \
    && printf 'orcl =\n  (DESCRIPTION =\n    (ADDRESS_LIST =\n      (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.13.37)(PORT = 1521))\n    )\n    (CONNECT_DATA =\n      (SID = orcl)\n    )\n  )\n' > $TNS_ADMIN/tnsnames.ora

# OCI8 via PECL (no interactivo)
RUN printf "instantclient,/usr/lib/oracle/11.2/client64/lib\n" | pecl install oci8-2.0.12

# PDO_OCI desde fuente PHP 5.6.40
RUN cd /tmp \
    && wget -q https://www.php.net/distributions/php-5.6.40.tar.gz \
    && tar -xzf php-5.6.40.tar.gz \
    && cd /tmp/php-5.6.40/ext/pdo_oci \
    && phpize \
    && ./configure --with-pdo-oci=instantclient,/usr/lib/oracle/11.2/client64/lib,11.2 \
    && make \
    && make install \
    && rm -rf /tmp/php-5.6.40 /tmp/php-5.6.40.tar.gz

# Apache httpd
RUN dnf install -y httpd mod_ssl \
    && dnf clean all

# Configuracion PHP: habilitar OCI8 y PDO_OCI
RUN mkdir -p /opt/remi/php56/root/etc/php.d \
    && echo "extension=oci8.so" > /opt/remi/php56/root/etc/php.d/50-oci8.ini \
    && echo "extension=pdo_oci.so" > /opt/remi/php56/root/etc/php.d/50-pdo_oci.ini

# Configuracion Apache (mod_rewrite y DocumentRoot)
COPY httpd.conf /etc/httpd/conf/httpd.conf

# CodeIgniter 3.1.13
RUN mkdir -p /var/www/html \
    && cd /var/www/html \
    && curl -sL https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz -o ci3.tar.gz \
    && tar -xzf ci3.tar.gz \
    && mv CodeIgniter-3.1.13/* . \
    && rm -rf CodeIgniter-3.1.13 ci3.tar.gz

# Configuracion de BD para CI3
COPY ci3-databases.php /var/www/html/application/config/database.php
COPY .htaccess /var/www/html/.htaccess

RUN chown -R apache:apache /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 644 /etc/httpd/conf/httpd.conf

EXPOSE 80

CMD ["/usr/sbin/httpd", "-DFOREGROUND"]
