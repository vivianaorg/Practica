#!/bin/bash

# --- CONFIGURACIÓN ---
DB_NAME="2025_2"
DB_CONTAINER="mariadb"
ROOT_PW='A2$kLp9#Zm8@qX1vTrC'
OUTPUT_FILE="configuracion_moodle.sql"

# 1. EL "CEREBRO" DE MOODLE (Datos Completos)
# Agregamos 'mdl_external_*' para que el AJAX y la carga de la interfaz funcionen correctamente.
CORE_DATA="mdl_config mdl_config_plugins mdl_modules mdl_context mdl_role mdl_role_capabilities mdl_course_categories mdl_repository mdl_repository_instances mdl_block_instances mdl_filter_active mdl_filter_config mdl_course_format_options mdl_external_functions mdl_external_services mdl_external_services_functions"

# 2. PLUGINS DEL PDF (Datos Completos)
PLUGINS_PDF=("attendance" "choicegroup" "customcert" "scheduler" "vpl" "theme_moove" "auth_ldap" "format_buttons")

BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🔍 Analizando tablas de plugins en Producción...${NC}"
PLUGIN_TABLES_DATA=""
for plugin in "${PLUGINS_PDF[@]}"; do
    TABLES=$(podman exec "$DB_CONTAINER" mysql -u root -p"$ROOT_PW" "$DB_NAME" -N -e "SHOW TABLES LIKE 'mdl_${plugin}%';")
    PLUGIN_TABLES_DATA="$PLUGIN_TABLES_DATA $TABLES"
done

# --- INICIO DE EXPORTACIÓN ---

# FASE 1: Cerebro y Plugins (Estructura + Datos)
echo -e "${GREEN}📦 Fase 1: Exportando Cerebro del Sistema y Plugins...${NC}"
podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" "$DB_NAME" $CORE_DATA $PLUGIN_TABLES_DATA > "$OUTPUT_FILE"

# FASE 2: Identidad y Portada (Filtrado con integridad)
echo -e "${GREEN}👤 Fase 2: Exportando Usuarios Vitales y Portada...${NC}"

# Usuarios Admin (2) e Invitado (1)
podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" --where="id IN (1,2) OR username='admin'" "$DB_NAME" mdl_user >> "$OUTPUT_FILE"

# Permisos: Solo exportamos los roles asignados al Admin (evita basura de producción)
podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" --where="userid IN (1,2)" "$DB_NAME" mdl_role_assignments >> "$OUTPUT_FILE"

# Portada: Curso ID 1 y sus SECCIONES (Vital para que no de error al cargar el home)
podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" --where="id=1" "$DB_NAME" mdl_course >> "$OUTPUT_FILE"
podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" --where="course=1" "$DB_NAME" mdl_course_sections >> "$OUTPUT_FILE"

# FASE 3: Todo lo demás (Estructura vacía)
echo -e "${YELLOW}📦 Fase 3: Generando estructura vacía para el resto...${NC}"
ALL_TABLES=$(podman exec "$DB_CONTAINER" mysql -u root -p"$ROOT_PW" "$DB_NAME" -N -e "SHOW TABLES;")
STRUCT_ONLY_TABLES=""
for table in $ALL_TABLES; do
    # Excluir TODO lo que ya exportamos con datos arriba
    if [[ ! " $CORE_DATA $PLUGIN_TABLES_DATA mdl_user mdl_role_assignments mdl_course mdl_course_sections " =~ " $table " ]]; then
        STRUCT_ONLY_TABLES="$STRUCT_ONLY_TABLES $table"
    fi
done

podman exec "$DB_CONTAINER" mysqldump -u root -p"$ROOT_PW" --no-data "$DB_NAME" $STRUCT_ONLY_TABLES >> "$OUTPUT_FILE"

echo -e "${GREEN}✅ SQL generado exitosamente.${NC}"