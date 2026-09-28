#!/bin/bash

# Colores para la salida
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}========================================================${NC}"
echo -e "${BLUE}   Configurador Masivo de Capacidad de Subida Moodle    ${NC}"
echo -e "${BLUE}========================================================${NC}"

# 1. Solicitar el valor al usuario
echo -n -e "${YELLOW}Ingrese el nuevo límite de subida (ejemplo: 4096M o 5G): ${NC}"
read NUEVO_VALOR

if [[ -z "$NUEVO_VALOR" ]]; then
    echo -e "${RED}Error: No ingresó ningún valor. Abortando.${NC}"
    exit 1
fi

CONTENEDORES=("moodle_new" "moodle_old")

for CTN in "${CONTENEDORES[@]}"; do
    echo -e "\n${GREEN}>>> Procesando contenedor: $CTN...${NC}"

    # --- CAPA 1: PHP (Archivo z-performance.ini identificado) ---
    PHP_INI="/usr/local/etc/php/conf.d/z-performance.ini"
    
    echo -e "   [PHP] Ajustando $PHP_INI..."
    podman exec -u root $CTN bash -c "
        if [ -f $PHP_INI ]; then
            sed -i 's/upload_max_filesize = .*/upload_max_filesize = $NUEVO_VALOR/' $PHP_INI
            sed -i 's/post_max_size = .*/post_max_size = $NUEVO_VALOR/' $PHP_INI
            sed -i 's/memory_limit = .*/memory_limit = $NUEVO_VALOR/' $PHP_INI
            echo '      ✅ PHP: Valores actualizados.'
        else
            echo '      ⚠️ PHP: No se encontró z-performance.ini, omitiendo.'
        fi
        apache2ctl graceful
    "

    # --- CAPA 2: Antivirus ClamAV ---
    CLAM_CONF="/etc/clamav/clamd.conf"
    
    echo -e "   [Antivirus] Ajustando límites de escaneo..."
    podman exec -u root $CTN bash -c "
        if [ -f $CLAM_CONF ]; then
            sed -i 's/^MaxFileSize .*/MaxFileSize $NUEVO_VALOR/' $CLAM_CONF
            sed -i 's/^MaxScanSize .*/MaxScanSize $NUEVO_VALOR/' $CLAM_CONF
            sed -i 's/^StreamMaxLength .*/StreamMaxLength $NUEVO_VALOR/' $CLAM_CONF
            echo '      ✅ ClamAV: Límites de seguridad actualizados.'
            # Reiniciar servicio de ClamAV si existe
            /etc/init.d/clamav-daemon restart > /dev/null 2>&1 || service clamav-daemon restart > /dev/null 2>&1
        else
            echo '      ⚠️ ClamAV: No se encontró clamd.conf, omitiendo.'
        fi
    "
done

echo -e "\n${BLUE}========================================================${NC}"
echo -e "${GREEN}¡PROCESO FINALIZADO CON ÉXITO!${NC}"
echo -e "${YELLOW}RECUERDA LOS PASOS MANUALES FINALES:${NC}"
echo -e "1. Ajustar 'client_max_body_size' en el Nginx del Host (AlmaLinux)."
echo -e "2. Ajustar 'client_max_body_size' en el archivo moodle.conf del contenedor nginx-proxy."
echo -e "3. Entrar a Moodle > Administración > Seguridad > Políticas del sitio y cambiar 'maxbytes' a $NUEVO_VALOR."
echo -e "${BLUE}========================================================${NC}"