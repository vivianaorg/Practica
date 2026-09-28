#!/bin/bash
set -e

CONTAINER_NAME="moodle"
CONFIG_NAME="apache-ssl.conf"
CONTAINER_CONFIG_PATH="/etc/apache2/sites-available/apache-ssl.conf"
CERT_PATH="/certs/certificado.crt"
KEY_PATH="/certs/certificado.key"

echo "🔍 Verificando que el contenedor '$CONTAINER_NAME' esté activo…"
if ! podman container inspect "$CONTAINER_NAME" &> /dev/null; then
  echo "❌ Error: El contenedor '$CONTAINER_NAME' no está en ejecución."
  exit 1
fi

echo "📥 Copiando '$CONFIG_NAME' al contenedor…"
podman cp "$CONFIG_NAME" "$CONTAINER_NAME:$CONTAINER_CONFIG_PATH"

echo "✏️ Ajustando rutas de certificados en el archivo dentro del contenedor…"
podman exec "$CONTAINER_NAME" sed -i "s|SSLCertificateFile .*|SSLCertificateFile $CERT_PATH|" "$CONTAINER_CONFIG_PATH"
podman exec "$CONTAINER_NAME" sed -i "s|SSLCertificateKeyFile .*|SSLCertificateKeyFile $KEY_PATH|" "$CONTAINER_CONFIG_PATH"

echo "🔍 Verificando que los certificados existan dentro del contenedor…"
podman exec "$CONTAINER_NAME" test -s "$CERT_PATH" || { echo "❌ Error: El archivo '$CERT_PATH' no existe o está vacío dentro del contenedor."; exit 1; }
podman exec "$CONTAINER_NAME" test -s "$KEY_PATH" || { echo "❌ Error: El archivo '$KEY_PATH' no existe o está vacío dentro del contenedor."; exit 1; }

echo "🛠️ Activando módulos y el nuevo sitio SSL…"
podman exec "$CONTAINER_NAME" a2enmod ssl || true
podman exec "$CONTAINER_NAME" a2enmod socache_shmcb || true
podman exec "$CONTAINER_NAME" a2ensite apache-ssl.conf || true

echo "🔍 Verificando configuración de Apache antes del reinicio…"
podman exec "$CONTAINER_NAME" apachectl configtest || { echo "❌ Error en la configuración de Apache."; exit 1; }

echo "🔄 Reiniciando Apache dentro del contenedor…"
podman exec "$CONTAINER_NAME" apachectl restart

echo "✅ Configuración SSL aplicada correctamente."
echo "🌐 Moodle debería estar disponible en: https://localhost:36513"
