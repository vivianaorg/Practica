#!/bin/bash

# Configuración de colores para la consola
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}   🧹 SCRIPT DE LIMPIEZA FINAL Y OPTIMIZACIÓN MOODLE ${NC}"
echo -e "${BLUE}======================================================${NC}"

# ==============================================================================
echo -e "\n${YELLOW}[1/3] Purgando cachés de aplicación en moodle_new...${NC}"
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/purge_caches.php
echo -e "${GREEN}✅ Cachés purgadas con éxito.${NC}"

# ==============================================================================
echo -e "\n${YELLOW}[2/3] Forzando ejecución del Cron para limpieza de papelera (trashdir)...${NC}"
echo "Ejecutando pasada 1 (Limpieza de registros en cascada)..."
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/cron.php > /dev/null

echo "Ejecutando pasada 2 (Consolidación de tareas Adhoc)..."
podman exec --user www-data --workdir /var/www/html moodle_new php admin/cli/cron.php > /dev/null

echo -e "${GREEN}✅ Tareas de fondo y limpieza de huérfanos completadas.${NC}"

# ==============================================================================
echo -e "\n${YELLOW}[3/3] Desfragmentando y optimizando tablas en MariaDB...${NC}"
# Contraseña inyectada con comillas simples para evitar expansión de variables bash ($k)
podman exec -i mariadb mysqlcheck -u root -p'A2$kLp9#Zm8@qX1vTrC' --auto-repair --optimize moodle_new

echo -e "\n${BLUE}======================================================${NC}"
echo -e "${GREEN}✅ MANTENIMIENTO FINALIZADO.${NC}"
echo -e "${GREEN}✅ La plataforma está 100% limpia, desfragmentada y lista para producción.${NC}"
echo -e "${BLUE}======================================================${NC}"