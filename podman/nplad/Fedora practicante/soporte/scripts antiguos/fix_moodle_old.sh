#!/bin/bash
CONTAINER="moodle_old"

echo "🔧 Iniciando reparación segura de acceso para el contenedor: $CONTAINER"

# 1. Eliminar URL alternativa (Usando CLI nativo)
echo "1️⃣  Eliminando configuración 'alternateloginurl'..."
podman exec $CONTAINER php admin/cli/cfg.php --name=alternateloginurl --set=""

# 2. Desactivar login forzoso
echo "2️⃣  Desactivando login forzoso..."
podman exec $CONTAINER php admin/cli/cfg.php --name=forcelogin --set="0"

# 3. Forzar al usuario admin a ser 'manual' (Única inyección SQL estrictamente necesaria)
echo "3️⃣  Asegurando que el admin use autenticación manual..."
podman exec -i mariadb mysql -u root -p'A2$kLp9#Zm8@qX1vTrC' 2025_2 -e "UPDATE mdl_user SET auth='manual' WHERE username='admin';"

# 4. Establecer autenticación global conservando la estructura de plugins
# Nota: Dejamos manual activo, pero no destruimos la configuración del plugin LDAP
echo "4️⃣  Estableciendo autenticación a 'manual'..."
podman exec $CONTAINER php admin/cli/cfg.php --name=auth --set="manual"

# 5. Resetear contraseña
echo "5️⃣  Reseteando contraseña de admin..."
podman exec $CONTAINER php admin/cli/reset_password.php --username=admin --password="uFpS_OldAdm#2025"

# 6. Purgar cachés para asegurar la sincronización total
echo "6️⃣  Purgando cachés del sistema..."
podman exec $CONTAINER php admin/cli/purge_caches.php

echo "✅ Reparación completada con éxito."