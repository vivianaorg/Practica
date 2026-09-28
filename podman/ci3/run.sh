#!/bin/bash
# CI3 Podman Development Environment
# Equivalent to Vagrant VirtualBox synced folders

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONTAINER_NAME="ci3-dev"
IMAGE_NAME="ci3-app"
HOST_HTTP_PORT="${HOST_HTTP_PORT:-9091}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}____________________________________________________${NC}"
echo -e "${GREEN} CI3 Development Environment - Podman${NC}"
echo -e "${GREEN}____________________________________________________${NC}"

# Check if podman is installed
if ! command -v podman &> /dev/null; then
    echo -e "${RED}Error: podman not installed${NC}"
    exit 1
fi

# Stop and remove existing container
echo -e "${YELLOW}Stopping existing container...${NC}"
podman stop $CONTAINER_NAME 2>/dev/null || true
podman rm $CONTAINER_NAME 2>/dev/null || true

# Set SELinux contexts
#echo -e "${YELLOW}Setting SELinux contexts...${NC}"
#chcon -R -t httpd_sys_rw_content_t "$PROJECT_DIR/html" 2>/dev/null || true
#chcon -R -t httpd_sys_rw_content_t "$PROJECT_DIR/google_credentials" 2>/dev/null || true
#chcon -R -t httpd_sys_rw_content_t "$PROJECT_DIR/data" 2>/dev/null || true

# Build image if not exists
if ! podman image exists $IMAGE_NAME; then
    echo -e "${YELLOW}Building container image...${NC}"
    podman build -t $IMAGE_NAME -f Containerfile "$PROJECT_DIR"
fi

# Run container with volume mounts (like VirtualBox synced folders)
echo -e "${YELLOW}Starting container with volume mounts...${NC}"
podman run -d \
    --name $CONTAINER_NAME \
    --network fedorapracticante_default \
    -p $HOST_HTTP_PORT:80 \
    -p 10001:10001 \
    -v "$PROJECT_DIR/html:/var/www/html:Z" \
    -v "$PROJECT_DIR/google_credentials:/var/www/google_credentials:Z" \
    -v "$PROJECT_DIR/data/vhost.conf:/etc/httpd/conf.d/vhost.conf:Z" \
    $IMAGE_NAME

# Wait for container to start
sleep 3

# Check if container is running
if podman ps --format '{{.Names}}' | grep -q $CONTAINER_NAME; then
    echo -e "${GREEN}____________________________________________________${NC}"
    echo -e "${GREEN} Container started successfully!${NC}"
    echo -e "${GREEN}____________________________________________________${NC}"
    echo ""
    echo -e "${YELLOW}Access URLs:${NC}"
    echo -e "  http://localhost:$HOST_HTTP_PORT (Apache default)"
    echo -e "  http://localhost:10001 (CI3 project)"
    echo ""
    echo -e "${YELLOW}Container commands:${NC}"
    echo -e "  Shell:        podman exec -it $CONTAINER_NAME bash"
    echo -e "  Logs:         podman logs -f $CONTAINER_NAME"
    echo -e "  Apache logs:  podman exec $CONTAINER_NAME tail -f /var/log/httpd/error_log"
    echo -e "  Stop:         podman stop $CONTAINER_NAME"
    echo -e "  Restart:      podman restart $CONTAINER_NAME"
    echo -e "  Override HTTP port: HOST_HTTP_PORT=9092 ./run.sh"
    echo ""
    echo -e "${YELLOW}Volume mounts:${NC}"
    echo -e "  ./html                 -> /var/www/html"
    echo -e "  ./google_credentials   -> /var/www/google_credentials"
    echo -e "  ./data/vhost.conf      -> /etc/httpd/conf.d/vhost.conf"
    echo ""
else
    echo -e "${RED}Error: Container failed to start${NC}"
    podman logs $CONTAINER_NAME
    exit 1
fi


