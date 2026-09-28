#!/bin/bash
set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT_PW='A2$kLp9#Zm8@qX1vTrC'

# ==============================================================================
# FUNCIÓN DE REPARACIÓN DE ACCESO
# ==============================================================================
reparar_acceso() {
    local CONTAINER=$1
    local DB_NAME=$2
    local ADMIN_PASS=$3

    echo -e "\n${YELLOW}▶️ REPARANDO ACCESO Y LOGIN PARA: ${CONTAINER} (BD: ${DB_NAME})${NC}"

    # 1. Eliminar URL alternativa de redirección
    echo "   [1/6] 1️⃣  Eliminando redirección externa (alternateloginurl)..."
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=alternateloginurl --set=""

    # 2. Desactivar login forzoso
    echo "   [2/6] 2️⃣  Desactivando forcelogin..."
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=forcelogin --set="0"

    # 3. Forzar al usuario admin a autenticación manual en la BD
    echo "   [3/6] 3️⃣  Asegurando autenticación manual para usuario admin..."
    podman exec -i mariadb mysql -u root -p"$ROOT_PW" "$DB_NAME" -e "UPDATE mdl_user SET auth='manual' WHERE username='admin';"

    # 4. Establecer autenticación global a 'manual'
    echo "   [4/6] 4️⃣  Estableciendo método de autenticación a manual..."
    podman exec "$CONTAINER" php admin/cli/cfg.php --name=auth --set="manual"

    # 5. Resetear contraseña del administrador
    echo "   [5/6] 5️⃣  Reseteando contraseña del administrador..."
    podman exec "$CONTAINER" php admin/cli/reset_password.php --username=admin --password="$ADMIN_PASS"

    # 6. Purgar cachés para aplicar cambios inmediatamente
    echo "   [6/6] 6️⃣  Purgando cachés del sistema..."
    podman exec "$CONTAINER" php admin/cli/purge_caches.php

    echo -e "${GREEN}✅ Acceso a ${CONTAINER} reparado exitosamente.${NC}"
}

# ==============================================================================
# EJECUCIÓN EN AMBOS CONTENEDORES
# ==============================================================================

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}   🔧 REPARANDO LOGIN DIRECTO EN SERVIDOR DE PRUEBAS${NC}"
echo -e "${BLUE}======================================================${NC}"

# 1. Reparar moodle_old
reparar_acceso "moodle_old" "2025_2" "uFpS_OldAdm#2025"

# 2. Reparar moodle_new
reparar_acceso "moodle_new" "moodle_new" "uFpS_NewAdm#2025"

echo -e "\n${BLUE}======================================================${NC}"
echo -e "${GREEN}🎉 ¡AMBOS CONTENEDORES TIENEN EL ACCESO DIRECTO RESTABLECIDO!${NC}"
echo -e "${BLUE}======================================================${NC}"