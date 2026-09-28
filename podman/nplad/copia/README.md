Arquitectura con Podman, Nginx Proxy y Entorno Aislado (2025)
📌 1. Objetivo del Proyecto

El objetivo es implementar un entorno de pre-producción basado en contenedores Podman, que permita gestionar dos instancias independientes de Moodle (Nueva y Legado), bajo un modelo de microservicios con:

Proxy inverso Nginx como punto único de entrada.

Terminación SSL (certificados wildcard *.ufps.edu.co).

Bases de datos separadas en una misma instancia de MariaDB.

Automatización completa mediante script start.sh.

Integración de ClamAV (antivirus) y del plugin VPL.

Aislamiento y persistencia de datos en /data.

📁 2. Estructura del Proyecto
proyecto/
├── podman-compose.yml           # Orquestación principal
├── start.sh                     # Script de despliegue offline
├── imagenes/                    # Imágenes base .tar usadas por Podman
│   ├── mariadb-10.11.tar
│   ├── moodle_cron.tar
│   └── phpmyadmin-5.2.2.tar
│
├── mariadb/
│   └── .env                     # Credenciales root
│
├── moodle_new/                  # Moodle 5.0 oficial (entorno actual)
│   ├── .env
│   ├── Dockerfile
│   └── apache.conf
│
├── moodle_old/                  # Moodle heredado (consulta)
│   ├── .env
│   ├── Dockerfile
│   └── apache.conf
│
├── nginx-proxy/
│   ├── ssl/                     # Certificados Wildcard (*.ufps.edu.co)
│   └── conf.d/
│       └── moodle.conf          # Reglas de enrutamiento y host virtual
│   ├── podman-compose.yml
│   ├── reset_nginx.sh
│   └── start.sh
│
├── phpmyadmin/
│   └── .env
│
├── plugins/
│   └── mod_vpl*.zip             # Plugin VPL para Moodle
│
└── soporte/                     # Scripts operativos
    ├── backup_mariadb.sh
    ├── backup_moodlecode.sh
    ├── backup_moodledata.sh
    ├── reset_moodle.sh
    ├── restore_mariadb.sh
    └── restore_moodledata.sh

🏗️ 3. Servicios del Entorno
Servicio  Contenedor  Función
Nginx Proxy nginx-proxy Punto de entrada, terminación SSL, enrutamiento hacia Moodle.
Moodle Nuevo  moodle_new  Moodle 5.0, configuración actual.
Moodle Legacy moodle_old  Moodle heredado solo consulta.
MariaDB mariadb Base de datos centralizada para ambas instancias.
phpMyAdmin  phpmyadmin  Interfaz visual de administración de BD.

Todos los servicios están dentro de la red interna plad_new_default and solo Nginx expone puertos al exterior.

📦 4. Requerimientos del Host

Almalinux 10

Podman

Podman Compose (python)

Acceso rootless habilitado (podman.socket)

Certificados wildcard ubicados en nginx-proxy/ssl/

🚚 5. Estrategia de Despliegue (Offline)

El script start.sh no descarga nada de internet, sino que usa las imágenes .tar locales:

Imagen .tar Uso
mariadb-10.11.tar Servicio MariaDB
moodle_cron.tar Base para moodle_new y moodle_old
phpmyadmin-5.2.2.tar  Administración BD
💾 6. Persistencia de Datos en /data

El script crea automáticamente:

/data/
├── plad_actual/moodledata   # Moodle Nuevo
├── plad_old/moodledata      # Moodle Legado
└── mariadb_data             # MariaDB (compartido)


Cada uno es montado en su contenedor correspondiente.

⚙️ 7. Proceso Automatizado de Despliegue

Ejecutar:

./start.sh


Carga de imágenes desde imagenes/*.tar.

Creación de estructura de directorios en /data.

Levantamiento de contenedores usando:

podman-compose up -d --build


Creación automática de bases de datos y usuarios:

moodle_new → usuario moodle_new_user

moodle_old → usuario moodle_old_user

Instalación CLI de Moodle (sin navegador):

admin/cli/install.php --non-interactive


Configuración de SSL vía proxy:

Se agrega automáticamente:

$CFG->sslproxy = 1;


Integración de ClamAV (antivirus interno).

Instalación automática del plugin VPL:

admin/cli/upgrade.php


Ajuste de permisos finales para www-data.

🔐 8. Acceso al Sistema
8.1 Acceso Web
Servicio  URL Contenedor
Moodle Nuevo  https://test-nplad.ufps.edu.co
  moodle_new
Moodle Legacy https://test-plad-old.ufps.edu.co
  moodle_old

Solo Nginx expone puertos 80/443.

8.2 phpMyAdmin
http://IP_DEL_SERVIDOR:36513

🔑 9. Credenciales de Administración

MariaDB Root
user: root
pass: A2$kLp9#Zm8@qX1vTrC

Moodle Nuevo
BD: moodle_new
DB_USER: moodle_new_user
DB_PASS: pP7@zKd!9R$6vX
ADMIN_USER: admin
ADMIN_PASS: uFpS_NewAdm#2025

Moodle Legacy
BD: moodle_old
DB_USER: moodle_old_user
DB_PASS: hH4&!aLk7T$1sM
ADMIN_USER: admin
ADMIN_PASS: uFpS_OldAdm#2025

🧰 10. Scripts de Operación

Ubicados en /soporte/:

backup_mariadb.sh

backup_moodlecode.sh

backup_moodledata.sh

restore_mariadb.sh

restore_moodledata.sh

reset_moodle.sh

Todos automatizan operaciones críticas de mantenimiento.

📝 11. Consideraciones Finales

La arquitectura garantiza aislamiento total, alta seguridad TLS, y reproducibilidad.

Todo se ejecuta con rootless Podman, sin necesidad de Docker.

El despliegue es completamente automático y offline.

El sistema está preparado para integrar VPL cuando el entorno jail funcione correctamente.