#!/bin/bash
set -e

cd "$(dirname "$0")"

echo ">>> Eliminando contenedor previo (si existe)..."
podman rm -f nginx-proxy 2>/dev/null || true

echo ">>> Levantando Nginx Reverse Proxy..."
podman-compose up -d
