# UNIVERSIDAD FRANCISCO DE PAULA SANTANDER
## FACULTAD DE INGENIERÍA
### PROGRAMA DE INGENIERÍA DE SISTEMAS

---

# INFORME DE PRÁCTICA PROFESIONAL EMPRESARIAL
### INTEGRACIÓN DE LA PLATAFORMA MOODLE (NPLAD) CON EL SISTEMA DE INFORMACIÓN ACADÉMICA DIVISIST PARA LA GESTIÓN DE RÚBRICAS EVALUATIVAS Y SINCRONIZACIÓN AUTOMATIZADA DE SUBNOTAS

---

| **DATOS GENERALES DE LA PRÁCTICA** | |
| :--- | :--- |
| **Estudiante:** | Viviana Katherine Ortiz Guerrero |
| **Código Estudiantil:** | 115XXXX *(Completar con código institucional)* |
| **Programa Académico:** | Ingeniería de Sistemas |
| **Semestre:** | X Semestre |
| **Empresa / Organización:** | Universidad Francisco de Paula Santander (UFPS) - División de Sistemas / PLAD |
| **Jefe Inmediato / Supervisor:** | Ing. [Nombre del Jefe Inmediato / Supervisor] |
| **Docente de Prácticas:** | MSc. I.S Carmen Janeth Parada / MSc. I.S José Martin Calixto Cely |
| **Periodo de Prácticas:** | [Fecha de Inicio] – [Fecha de Finalización] |
| **Lugar y Fecha:** | San José de Cúcuta, Norte de Santander, 2026 |

---

## LOGOS INSTITUCIONALES REQUERIDOS EN PORTADA
*(Al diagramar en Word, ubicar en la cabecera los logotipos oficiales)*:
* **Logo Universidad Francisco de Paula Santander (UFPS)**: Archivo institucional disponible en `c:\Users\VIVIANA ORTIZ\Documents\U VIVI\Practica\Logo ufps.png`.
* **Logo Plataforma NPLad / Moodle**: Archivo disponible en `c:\Users\VIVIANA ORTIZ\Documents\U VIVI\Practica\Logo plad.png` y `Logo moodle.png`.
* **Logo Programa de Ingeniería de Sistemas**: Emblema oficial de la Facultad de Ingeniería UFPS.

---

# ÍNDICE GENERAL

1. **Introducción**
   - 1.1 Antecedentes y Contexto de la Integración
   - 1.2 Importancia de la Articulación con el Sector Productivo e Institucional
   - 1.3 Objetivos (General y Específicos)
   - 1.4 Alcance del Proyecto
2. **Contextualización de la Entidad**
   - 2.1 Razón Social y Actividad Económica
   - 2.2 Dirección y Ubicación Geográfica
   - 2.3 Objeto Social
   - 2.4 Misión Institucional
   - 2.5 Visión Institucional
   - 2.6 La División de Sistemas y la Plataforma de Acompañamiento Docente (NPLad)
3. **Listas Adicionales**
   - 3.1 Lista de Tablas
   - 3.2 Lista de Figuras y Diagramas
   - 3.3 Glosario de Términos Técnicos
4. **Desarrollo de la Práctica**
   - 4.1 Conocimientos Teóricos Aplicados a la Función Diaria
   - 4.2 Informe Detallado de Actividades y Subactividades Realizadas
     - Formato UFPS - Actividad 1: Infraestructura y Entornos de Virtualización (Podman, Linux, Moodle 5.0)
     - Formato UFPS - Actividad 2: Arquitectura y API de Integración Desacoplada HTTP / CLI Divisist ↔ Moodle
     - Formato UFPS - Actividad 3: Persistencia y Diseño del Modelo Relacional de Rúbricas en Oracle
     - Formato UFPS - Actividad 4: Módulo de Extracción y Consulta de Calificaciones en Moodle
     - Formato UFPS - Actividad 5: Interfaz Docente para la Configuración Dinámica de Rúbricas por Corte
     - Formato UFPS - Actividad 6: Algoritmo de Cálculo de Nota Sugerida, Subnotas y Evaluación Docente
   - 4.3 Impactos Percibidos por el Estudiante (Personal, Académico y Laboral)
   - 4.4 Limitaciones y Dificultades Presentadas
   - 4.5 Diagrama de Gantt (Control de Avance en la Línea de Tiempo)
   - 4.6 Adaptación al Entorno Organizacional
   - 4.7 Tolerancia al Trabajo Bajo Presión
   - 4.8 Capacidad para Asumir Nuevas Responsabilidades
5. **Conclusiones**
6. **Bibliografía y Referencias (Normas APA Vigentes)**
7. **Observaciones y Anexos**
8. **Firmas de Aprobación del Tutor y Docente de Práctica**

---

# 1. INTRODUCCIÓN

### 1.1 Antecedentes y Contexto de la Integración
En la Universidad Francisco de Paula Santander (UFPS), la gestión académica se encuentra articulada principalmente a través de dos plataformas informáticas de misión crítica: el Sistema de Información Académica Institucional (**Divisist**), desarrollado en el framework CodeIgniter 3 bajo PHP 5.6 con motor de base de datos relacional Oracle (SIA/UDOCENTE), y la Plataforma de Acompañamiento Docente (**NPLad**), sustentada sobre el Entorno Virtual de Aprendizaje (EVA) de código abierto **Moodle 5.0**, desplegada en contenedores Podman con PHP moderno y motor MariaDB.

Históricamente, los docentes de la universidad han utilizado Moodle para la administración de sus cursos, recepción de entregas, cuestionarios automatizados y foros evaluativos; sin embargo, al momento de consolidar las notas parciales de cada corte evaluativo (Primer Previo, Segundo Previo, Tercer Previo y Examen Final) en Divisist, se veían obligados a realizar un cálculo manual de ponderaciones en hojas de cálculo externas o transcribir una a una las calificaciones obtenidas en el aula virtual. Este proceso no solo representaba una carga operativa significativa y propensa a errores humanos de digitación, sino que impedía a los estudiantes conocer la trazabilidad exacta de los porcentajes y subnotas que originaron su nota definitiva.

### 1.2 Importancia de la Articulación con el Sector Productivo e Institucional
La realización de la práctica profesional en la División de Sistemas de la UFPS materializa la articulación directa entre la formación académica impartida en el Programa de Ingeniería de Sistemas y los desafíos reales de la industria del software y la gestión de tecnologías universitarias. Enfrentar sistemas legacy en producción, lidiar con interoperabilidad heterogénea entre diferentes versiones de lenguajes (PHP 5.6 frente a PHP moderno), gestionar restricciones estrictas de privilegios en bases de datos institucionales (Oracle OCI8) y aplicar arquitectura de software limpia orientada a la seguridad y escalabilidad, permite cerrar la brecha entre la teoría de aula y el ejercicio profesional de la ingeniería.

### 1.3 Objetivos

#### Objetivo General
Diseñar, desarrollar e implementar un módulo integral de interoperabilidad entre la plataforma de aulas virtuales Moodle (NPLad) y el Sistema de Información Académica Divisist, que permita la configuración dinámica de rúbricas evaluativas por corte y la sincronización y cálculo automatizado de notas sugeridas y subnotas ponderadas para los docentes de la Universidad Francisco de Paula Santander.

#### Objetivos Específicos
1. Configurar, orquestar y administrar los entornos de desarrollo y pruebas en contenedores Podman para Divisist y Moodle 5.0, asegurando la conectividad de red y el soporte a las rutinas de migración y respaldos semestrales.
2. Diseñar e implementar una arquitectura de comunicación desacoplada y segura basada en peticiones HTTP autenticadas y scripts de interfaz de línea de comandos (CLI) de Moodle, preservando la integridad referencial y de contextos del LMS.
3. Modelar e implementar el esquema relacional de persistencia en Oracle para las tablas `CONFIG_RUBRICA` y `SUBNOTAS`, solventando limitaciones de permisos y garantizando la trazabilidad histórica de calificaciones.
4. Desarrollar las interfaces de usuario interactivas y reactivas en Divisist para que los docentes puedan estructurar sus rúbricas con validación en tiempo real de la sumatoria del 100% y consultar de forma global el estado de sus asignaturas.
5. Programar el algoritmo de normalización de notas y cálculo de notas sugeridas, dotando al docente de herramientas de aceptación masiva, edición puntual y despliegue del desglose detallado de actividades para consulta de los estudiantes.

### 1.4 Alcance del Proyecto
El proyecto abarca desde el levantamiento de infraestructura y desarrollo de APIs en Moodle, hasta la programación completa del modelo, controlador y vistas en Divisist para el rol docente. Incluye la configuración por cortes evaluativos, la importación de notas en tiempo real vía cURL, la normalización a la escala oficial de 0.0 a 5.0, el almacenamiento del desglose por actividad en Oracle y la persistencia de estados de evaluación (`SUGERIDA`, `ACEPTADA`, `MODIFICADA`). Queda delimitado para operar tanto en el entorno de desarrollo local con Podman como en la red corporativa de producción de la universidad mediante configuración parametrizada.

---

# 2. CONTEXTUALIZACIÓN DE LA ENTIDAD

### 2.1 Razón Social y Actividad Económica
* **Razón Social:** Universidad Francisco de Paula Santander (UFPS)
* **NIT:** 890500622-6
* **Naturaleza Jurídica:** Ente universitario autónomo con personería jurídica, autonomía académica, administrativa y financiera, de carácter público y del orden departamental/nacional.
* **Actividad Económica:** Educación Superior Universitaria de Pregrado y Posgrado (Código CIIU 8544).

### 2.2 Dirección y Ubicación Geográfica
* **Dirección:** Avenida Gran Colombia No. 12E-96, Barrio Colsag.
* **Ciudad / Departamento:** San José de Cúcuta, Norte de Santander, Colombia.
* **Sede de la Práctica:** División de Sistemas / Unidad de Informática - Campus Central UFPS.

### 2.3 Objeto Social
La Universidad Francisco de Paula Santander tiene por objeto la prestación del servicio público de la educación superior mediante la formación integral de profesionales con sentido crítico, ético y humanístico; el fomento y difusión de la investigación científica, tecnológica y artística; y la extensión universitaria que propicie la transformación económica, social y cultural de la región nororiental y del país.

### 2.4 Misión Institucional
La Universidad Francisco de Paula Santander es una institución pública de educación superior de carácter oficial, con vocación regional, que tiene como misión fundamental la formación integral de profesionales idóneos, éticos y comprometidos con el desarrollo social, cultural, científico, tecnológico y sostenible; mediante procesos académicos de calidad soportados en la docencia, la investigación y la extensión universitaria, para responder a las dinámicas del contexto global.

### 2.5 Visión Institucional
La Universidad Francisco de Paula Santander será reconocida nacional e internacionalmente por su excelencia académica, su capacidad de innovación, su impacto en la investigación aplicada y su liderazgo en la transformación social y productiva de la región fronteriza colombo-venezolana, apoyada en el uso intensivo de tecnologías de la información, altos estándares de calidad y pertinencia social.

### 2.6 La División de Sistemas y la Plataforma NPLad
La División de Sistemas de la UFPS es la dependencia responsable de liderar la transformación digital, administrar la infraestructura de redes, telecomunicaciones y servidores, y mantener los sistemas de información institucionales. Entre ellos se destacan:
* **Divisist:** Plataforma transaccional de registro académico, matrículas, notas, carga docente y control estudiantil.
* **NPLad (Nueva Plataforma de Acompañamiento Docente):** Campus virtual basado en Moodle 5.0, orientado a dinamizar las asignaturas mediante aulas virtuales, mediación de contenidos y evaluación formativa.

---

# 3. LISTAS ADICIONALES

### 3.1 Lista de Tablas
* **Tabla 1:** Relación de Asignaturas y Conocimientos Teóricos Aplicados.
* **Tabla 2:** Formato de Registro de Actividad 1 - Infraestructura de Virtualización y Entornos Podman.
* **Tabla 3:** Formato de Registro de Actividad 2 - Arquitectura de Comunicación Desacoplada HTTP/CLI.
* **Tabla 4:** Formato de Registro de Actividad 3 - Persistencia y Modelo Relacional en Oracle.
* **Tabla 5:** Formato de Registro de Actividad 4 - API de Calificaciones Moodle 5.0.
* **Tabla 6:** Formato de Registro de Actividad 5 - Interfaz Docente y Configuración de Rúbricas.
* **Tabla 7:** Formato de Registro de Actividad 6 - Algoritmo de Cálculo y Evaluación de Subnotas.
* **Tabla 8:** Cronograma y Control de Avance de Actividades (Diagrama de Gantt).
* **Tabla 9:** Asunción de Nuevas Responsabilidades y Nivel de Compromiso.

### 3.2 Lista de Figuras y Diagramas
* **Figura 1:** Arquitectura de Interoperabilidad de Tres Niveles (Divisist ↔ Moodle ↔ Oracle).
* **Figura 2:** Diagrama Entidad-Relación de las tablas `CONFIG_RUBRICA` y `SUBNOTAS`.
* **Figura 3:** Flujo del Algoritmo Matemático de Normalización y Ponderación de Subnotas.
* **Figura 4:** Captura de Pantalla - Pestaña de Resumen General de Rúbricas por Curso.
* **Figura 5:** Captura de Pantalla - Panel de Configuración de Rúbrica con Validación del 100%.
* **Figura 6:** Captura de Pantalla - Planilla Docente de Calificación y Modal de Desglose de Subnotas.

### 3.3 Glosario de Términos Técnicos
* **API (Application Programming Interface):** Conjunto de definiciones y protocolos que se utiliza para diseñar e integrar el software de las aplicaciones.
* **cURL:** Herramienta y biblioteca de línea de comandos para transferir datos con sintaxis URL mediante peticiones HTTP/HTTPS.
* **CodeIgniter 3 (CI3):** Framework de desarrollo web en PHP bajo el patrón Modelo-Vista-Controlador (MVC).
* **Moodle:** Modular Object-Oriented Dynamic Learning Environment; plataforma LMS para la gestión del aprendizaje.
* **OCI8:** Extensión de PHP que permite acceder a motores de bases de datos relacionales Oracle Database.
* **Podman:** Motor de contenedores de código abierto sin demonio (*daemonless*) para desarrollo, gestión y ejecución de contenedores OCI en Linux.
* **Rúbrica Evaluativa:** Instrumento o regla de ponderación que establece las actividades y porcentajes que componen una nota de corte.
* **Subnota:** Calificación parcial obtenida por un estudiante en una actividad específica, proporcional a su ponderación dentro del corte.
* **Timing Attack:** Ataque criptográfico de canal lateral en el que un atacante deduce secretos midiendo el tiempo de respuesta del servidor (mitigado mediante `hash_equals()`).

---

# 4. DESARROLLO DE LA PRÁCTICA

## 4.1 Conocimientos Teóricos Aplicados a la Función Diaria

Durante el desarrollo de la práctica se requirió la articulación interdisciplinaria de múltiples áreas del conocimiento de la carrera de Ingeniería de Sistemas:

| Área / Asignatura del Plan de Estudios | Conocimientos Teóricos Implementados en la Práctica | Aplicación Práctica en la Función Diaria |
| :--- | :--- | :--- |
| **Ingeniería de Software / Arquitectura de Software** | Patrón Modelo-Vista-Controlador (MVC), diseño modular, bajo acoplamiento y alta cohesión, desacoplamiento de interfaces. | Separación de responsabilidades en Divisist entre `Moodle_model.php`, `Subnotas_model.php`, `Dashboard.php` y vistas modulares. |
| **Bases de Datos I y II** | Modelado Entidad-Relación, normalización relacional, integridad transaccional, índices B-Tree, DDL/DML SQL para Oracle Database y MariaDB. | Creación del esquema de base de datos (`CONFIG_RUBRICA`, `SUBNOTAS`), optimización de consultas indexadas y generación incremental de IDs (`NVL(MAX(ID), 0) + 1`). |
| **Programación Web / Computación en Internet** | Programación orientada a objetos en PHP, JavaScript asíncrono (AJAX/Fetch API), manipulación del DOM, diseño responsivo con CSS/AdminLTE. | Construcción de controladores PHP 5.6, validación en tiempo real de sumatoria porcentual con barra reactiva y consumo asíncrono de endpoints sin recargar página. |
| **Redes de Computadores y Sistemas Distribuidos** | Protocolo HTTP/HTTPS, métodos GET/POST, códigos de estado HTTP, consumo de servicios web mediante cURL, resolución DNS y redes virtuales. | Interconexión entre los contenedores de Divisist y Moodle a través de peticiones HTTP parametrizadas, gestión de timeouts y control de certificados SSL. |
| **Seguridad de la Información** | Autenticación basada en tokens, prevención de ataques de temporización (*Timing Attacks*), saneamiento de parámetros y principio de mínimo privilegio. | Implementación de `hash_equals()` en puentes HTTP de Moodle, escape de argumentos con `escapeshellarg()` y token criptográfico en cabeceras. |
| **Sistemas Operativos y DevOps** | Administración de sistemas operativos GNU/Linux, contenedores OCI (Podman/Docker), shell scripting Bash, gestión de permisos y demonios del sistema. | Creación y administración de contenedores en Fedora/AlmaLinux, scripts de rescate (`podman-fix.sh`, `encender_moodle.sh`), ejecución de rutinas CLI con PHP. |

---

## 4.2 Informe Detallado de Actividades Realizadas

---

### ACTIVIDAD 1
*(Formato Oficial UFPS de Seguimiento de Prácticas)*

| **Universidad Francisco de Paula Santander** <br> *Acreditada en Alta Calidad* | **INFORME Nro. 01** <br> PÁGINA: 1 de 6 |
| :--- | :--- |

| ID | 1 | ACTIVIDAD | Administración de Infraestructura de Virtualización y Entorno de Pruebas | Total Horas | 30 | % AVANCE | 100% |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ID** | **1.1** | **SUBACTIVIDAD** | Despliegue de contenedores Podman (Moodle 5.0, MariaDB, Nginx, PHP-CI3) y soporte a migración semestral | **Total Horas** | **30** | **% AVANCE** | **100%** |
| **ESTRATEGIA DE DESARROLLO** | 1. Configuración de la máquina virtual Linux con motor Podman sin privilegios de root (*rootless*). <br> 2. Orquestación del ecosistema Moodle 5.0 compuesto por `moodle_new`, `mariadb`, `nginx-proxy`, `phpmyadmin` y `vpl`. <br> 3. Construcción del contenedor `ci3-dev` para Divisist con Apache, PHP 5.6 y extensión `oci8.so` para soporte de Oracle. <br> 4. Análisis y ejecución de scripts de soporte de migración semestral (`inyeccion_maestra.sh`, `fix_moodle_old.sh`, `configurar_auth_institucional.sh`) para el reemplazo seguro de URLs institucionales y volcado de bases de datos. |
| **RECURSOS UTILIZADOS** | • Hardware: Máquina Virtual Linux (Fedora / AlmaLinux). <br> • Software: Podman, Podman-Compose, Bash, Nginx, Apache HTTP Server, MariaDB 10.x, PHP 5.6 y PHP 8.x. <br> • Repositorios y scripts de soporte institucional. |
| **RESULTADOS OBTENIDOS** | • Entorno local de desarrollo y pruebas 100% funcional y aislado. <br> • Interconexión bidireccional mediante red virtual de Podman (`fedorapracticante_default`) entre Divisist y Moodle. <br> • Dominio y aplicación de procedimientos técnicos para la clonación, respaldo y migración de cursos de Moodle. |
| **OBSERVACIONES** | Se superaron retos iniciales asociados al bloqueo de procesos de Apache (`httpd.pid`) y directivas de memoria y subida de archivos en `php.ini` y `clamd.conf`. |

---

### ACTIVIDAD 2
*(Formato Oficial UFPS de Seguimiento de Prácticas)*

| **Universidad Francisco de Paula Santander** <br> *Acreditada en Alta Calidad* | **INFORME Nro. 01** <br> PÁGINA: 2 de 6 |
| :--- | :--- |

| ID | 2 | ACTIVIDAD | Diseño y Construcción de la Arquitectura de Integración Desacoplada Divisist ↔ Moodle | Total Horas | 35 | % AVANCE | 100% |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ID** | **2.1** | **SUBACTIVIDAD** | Implementación de Puentes HTTP Seguros, Scripts CLI y Modelo de Conexión cURL | **Total Horas** | **35** | **% AVANCE** | **100%** |
| **ESTRATEGIA DE DESARROLLO** | 1. Descarte de la conexión directa a base de datos MariaDB para no vulnerar el árbol de contextos (`mdl_context`), permisos y libros de calificaciones de Moodle. <br> 2. Diseño del patrón en dos capas en Moodle: <br> &nbsp;&nbsp;&nbsp;&nbsp;a) Capa de Puentes Web (`/var/www/html/`) con validación de tokens mediante `hash_equals()` y saneamiento con `escapeshellarg()`. <br> &nbsp;&nbsp;&nbsp;&nbsp;b) Capa de Scripts CLI (`admin/cli/`) que ejecutan internamente la API nativa `$DB` y `\core\cron::setup_user()`. <br> 3. Centralización de parámetros de conexión en Divisist (`application/config/moodle.php`) permitiendo conmutar entre URLs de desarrollo (`http://moodle_new`) y producción (`https://nplad.ufps.edu.co`). <br> 4. Desarrollo de `Moodle_model.php` en CodeIgniter 3 con compatibilidad estricta con PHP 5.6 (evitando operadores incompatibles) para consumir cursos y módulos vía cURL. |
| **RECURSOS UTILIZADOS** | • Lenguajes: PHP 5.6 (Divisist), PHP 8.2 (Moodle), Shell Scripting. <br> • Librerías: cURL, JSON Parser. <br> • Herramientas: Git, GitHub, Terminal Podman. |
| **RESULTADOS OBTENIDOS** | • Comunicación HTTP exitosa y segura entre contenedores independientes. <br> • Métodos operativos para listar cursos del docente, buscar códigos académicos equivalentes (ej. `1155304-A`) y listar actividades evaluativas. <br> • Documentación técnica consolidada en `CONEXION_MOODLE_DIVISIST.md`. |
| **OBSERVACIONES** | El diseño desacoplado garantiza que cualquier actualización o cambio de versión en Moodle no afectará el funcionamiento interno del núcleo de Divisist. |

---

### ACTIVIDAD 3
*(Formato Oficial UFPS de Seguimiento de Prácticas)*

| **Universidad Francisco de Paula Santander** <br> *Acreditada en Alta Calidad* | **INFORME Nro. 01** <br> PÁGINA: 3 de 6 |
| :--- | :--- |

| ID | 3 | ACTIVIDAD | Persistencia de Datos y Modelado Relacional de Rúbricas y Subnotas | Total Horas | 30 | % AVANCE | 100% |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ID** | **3.1** | **SUBACTIVIDAD** | Creación de tablas en Oracle Database y programación del modelo Subnotas_model.php | **Total Horas** | **30** | **% AVANCE** | **100%** |
| **ESTRATEGIA DE DESARROLLO** | 1. Diseño y formulación de script SQL (`application/sql/crear_tablas_rubricas_subnotas.sql`) definiendo las entidades `CONFIG_RUBRICA` (reglas por corte) y `SUBNOTAS` (calificaciones históricas individuales). <br> 2. Creación de índices especializados (`IDX_RUBRICA_BUSQUEDA`, `IDX_SUBNOTAS_ESTUDIANTE`, `IDX_SUBNOTAS_GRUPO`) para optimizar tiempos de respuesta. <br> 3. Detección y resolución del error de privilegios Oracle `ORA-01031` en el usuario institucional `UDOCENTE`, reemplazando el uso de `SEQUENCE` por la generación incremental segura `SELECT NVL(MAX(ID), 0) + 1`. <br> 4. Construcción de métodos transaccionales en `Subnotas_model.php` para guardar, consultar, limpiar rúbricas y consultar resúmenes ponderados. |
| **RECURSOS UTILIZADOS** | • Motor de Base de Datos: Oracle Database (SIA Institucional). <br> • Drivers: OCI8 (`Database2` en CodeIgniter 3). <br> • Lenguaje: SQL / PL-SQL y PHP 5.6. |
| **RESULTADOS OBTENIDOS** | • Esquema de base de datos desplegado y validado en Oracle. <br> • Persistencia íntegra de la configuración docente de previos y exámenes. <br> • Documento de bitácora `DIA_1_ARQUITECTURA_PERSISTENCIA.md` generado y código versionado en GitHub. |
| **OBSERVACIONES** | La solución de cálculo incremental de IDs aseguró la autonomía técnica del desarrollo sin requerir intervención directa ni escalamiento de privilegios por parte del DBA. |

---

### ACTIVIDAD 4
*(Formato Oficial UFPS de Seguimiento de Prácticas)*

| **Universidad Francisco de Paula Santander** <br> *Acreditada en Alta Calidad* | **INFORME Nro. 01** <br> PÁGINA: 4 de 6 |
| :--- | :--- |

| ID | 4 | ACTIVIDAD | Desarrollo de la API de Extracción Automatizada de Calificaciones desde Moodle | Total Horas | 25 | % AVANCE | 100% |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ID** | **4.1** | **SUBACTIVIDAD** | Programación de scripts CLI/HTTP para extracción masiva de notas por curso | **Total Horas** | **25** | **% AVANCE** | **100%** |
| **ESTRATEGIA DE DESARROLLO** | 1. Creación del script CLI `cli_obtener_calificaciones.php` en Moodle, consumiendo la API `$DB` para realizar un cruce relacional entre `{grade_items}`, `{grade_grades}` y `{user}`. <br> 2. Agrupación estructurada de notas por estudiante, asociando el código institucional (`idnumber` o `username`), nombre completo, nota obtenida y escala máxima. <br> 3. Creación del puente web seguro `obtener_calificaciones.php` con validación estricta de token. <br> 4. Incorporación del método `obtener_calificaciones($courseId)` en `Moodle_model.php` de Divisist para deserializar la respuesta JSON. |
| **RECURSOS UTILIZADOS** | • Entorno Moodle 5.0 (PHP CLI nativo). <br> • Modelo `Moodle_model.php` en Divisist. <br> • JSON y protocolo HTTP/REST. |
| **RESULTADOS OBTENIDOS** | • Extracción automatizada y en tiempo real de todas las calificaciones registradas en el libro de calificaciones de Moodle. <br> • Eliminación de la dependencia de exportar e importar archivos planos Excel o CSV. <br> • Documento de bitácora `DIA_2_API_CALIFICACIONES_MOODLE.md` registrado. |
| **OBSERVACIONES** | Se validó que el script soporte estudiantes con entregas pendientes o sin calificar, asignándoles por defecto nota 0.0 de forma controlada sin interrumpir el proceso. |

---

### ACTIVIDAD 5
*(Formato Oficial UFPS de Seguimiento de Prácticas)*

| **Universidad Francisco de Paula Santander** <br> *Acreditada en Alta Calidad* | **INFORME Nro. 01** <br> PÁGINA: 5 de 6 |
| :--- | :--- |

| ID | 5 | ACTIVIDAD | Implementación de la Interfaz de Usuario y Configuración Dinámica de Rúbricas | Total Horas | 35 | % AVANCE | 100% |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ID** | **5.1** | **SUBACTIVIDAD** | Desarrollo del panel de doble pestaña en AdminLTE con validación de 100% en tiempo real | **Total Horas** | **35** | **% AVANCE** | **100%** |
| **ESTRATEGIA DE DESARROLLO** | 1. Implementación de una vista moderna basada en pestañas (`nav-tabs-custom`): <br> &nbsp;&nbsp;&nbsp;&nbsp;• Pestaña 1 ("Resumen General"): Muestra tarjetas independientes para cada corte (1°, 2°, 3° Previo y Examen Final), badges de estado (*Configurada al 100%*, *Incompleta*), barra de ponderación acumulada y accesos directos. <br> &nbsp;&nbsp;&nbsp;&nbsp;• Pestaña 2 ("Configurar / Editar"): Permite agregar y remover actividades de Moodle mediante filas dinámicas. <br> 2. Programación en JavaScript para bloqueo y exclusión de opciones duplicadas en los selectores. <br> 3. Implementación de una barra de progreso reactiva: color amarillo si suma <100%, rojo si >100%, y verde si es exactamente 100% (habilitando el botón de guardado). <br> 4. Guardado asíncrono con `fetch` hacia el controlador `Dashboard->guardar_rubrica_ajax()`. |
| **RECURSOS UTILIZADOS** | • Frontend: HTML5, CSS3, JavaScript (ES5/ES6), Bootstrap 3 / AdminLTE. <br> • Backend: CodeIgniter 3 (`Dashboard.php`, `Subnotas_model.php`). |
| **RESULTADOS OBTENIDOS** | • Interfaz docente intuitiva, libre de errores y estéticamente homologada con el diseño corporativo de Divisist. <br> • Imposibilidad matemática de registrar rúbricas que no sumen el 100%. <br> • Bitácora `DIA_3_INTERFAZ_DOCENTE_RUBRICAS.md` documentada. |
| **OBSERVACIONES** | La separación en pestañas resolvió la necesidad de permitir al docente auditar de un vistazo la planeación semestral de todas sus materias. |

---

### ACTIVIDAD 6
*(Formato Oficial UFPS de Seguimiento de Prácticas)*

| **Universidad Francisco de Paula Santander** <br> *Acreditada en Alta Calidad* | **INFORME Nro. 01** <br> PÁGINA: 6 de 6 |
| :--- | :--- |

| ID | 6 | ACTIVIDAD | Algoritmo de Cálculo de Nota Sugerida, Subnotas y Módulo de Evaluación Docente | Total Horas | 35 | % AVANCE | 100% |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ID** | **6.1** | **SUBACTIVIDAD** | Formulación matemática, normalización de notas a escala 0.0-5.0 y persistencia en Oracle | **Total Horas** | **35** | **% AVANCE** | **100%** |
| **ESTRATEGIA DE DESARROLLO** | 1. Implementación del algoritmo matemático de normalización para actividades evaluadas en diferentes escalas (10, 100 o 5 puntos): <br> &nbsp;&nbsp;&nbsp;&nbsp;$$\text{NotaNormalizada}_i = (\text{NotaOriginal}_i / \text{NotaMaxima}_i) \times 5.0$$ <br> 2. Ponderación de subnotas: $\text{Subnota}_i = \text{round}(\text{NotaNormalizada}_i \times (P_i / 100), 2)$ y sumatoria de Nota Sugerida. <br> 3. Algoritmo de reescalado proporcional para mantener la coherencia del desglose si el docente modifica manualmente la nota sugerida. <br> 4. Desarrollo de la vista `calificar_rubrica.php` con KPIs del curso (Aprobados, Reprobados, Promedio), botón masivo *"Aceptar Todas las Sugerencias"*, edición manual con detección de cambios y persistencia de estados (`SUGERIDA`, `ACEPTADA`, `MODIFICADA`). <br> 5. Implementación del botón modal *"Ver Rúbrica"* que detalla cada actividad evaluada, su nota base y su aporte a la nota definitiva. <br> 6. Reglas de negocio avanzadas: bloqueo de configuración y calificación si el corte ya fue cerrado, con advertencias y opción de desbloqueo controlado. |
| **RECURSOS UTILIZADOS** | • Backend: PHP 5.6 (Controlador `Dashboard.php` y Modelo `Subnotas_model.php`). <br> • Base de Datos: Oracle Database (Tabla `SUBNOTAS`). <br> • Frontend: JavaScript, jQuery, Modales Bootstrap, Badges dinámicos. |
| **RESULTADOS OBTENIDOS** | • Módulo completo y funcional de calificación docente. <br> • Cálculo instantáneo y matemáticamente exacto de notas a partir de Moodle. <br> • Registro de trazabilidad y desglose accesible para docentes y estudiantes. <br> • Documento de bitácora `DIA_4_CALCULO_NOTA_SUGERIDA.md` y commits finales consolidados en GitHub. |
| **OBSERVACIONES** | El sistema provee flexibilidad al docente, permitiéndole acoger la nota sugerida o realizar ajustes cualitativos justificados. |

---

## 4.3 Impactos Percibidos por el Estudiante

### Impacto Personal
La experiencia práctica ha fortalecido la autoconfianza profesional, la capacidad de pensamiento crítico y la perseverancia frente a errores complejos de arquitectura y compatibilidad. Asimismo, demandó una alta disciplina en la gestión autónoma del tiempo, la organización de código limpio y el desarrollo de habilidades de resiliencia ante contingencias técnicas imprevistas.

### Impacto Académico
Permitió articular de manera práctica y tangible conceptos teóricos que previamente se estudiaban de forma aislada en asignaturas como Ingeniería de Software, Bases de Datos, Redes y Sistemas Operativos. Comprender cómo interactúan las restricciones de un motor institucional como Oracle frente a sistemas modernos basados en microservicios o contenedores enriqueció notablemente el criterio de diseño de software.

### Impacto Laboral
Adquisición de competencias de alto valor demandadas en el mercado laboral de tecnología: administración de entornos virtualizados con Podman en Linux, refactorización y mantenimiento de software legacy institucional, desarrollo seguro de APIs desacopladas y trabajo colaborativo bajo control de versiones con Git/GitHub siguiendo buenas prácticas de integración continua.

---

## 4.4 Limitaciones y Dificultades Presentadas

Durante el transcurso de las actividades se presentaron y superaron los siguientes retos técnicos:
1. **Restricción de Privilegios en Oracle (`ORA-01031: insufficient privileges`):** El usuario asignado `UDOCENTE` no contaba con permisos de DBA para la creación de secuencias (`CREATE SEQUENCE`). Se solventó implementando en el modelo una rutina de generación incremental segura de claves primarias:
   ```sql
   SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM CONFIG_RUBRICA;
   ```
2. **Disparidad de Versiones entre PHP 5.6 (Divisist) y PHP 8.x (Moodle 5.0):** El código desarrollado en Divisist debía cumplir rigurosamente con los estándares y sintaxis de PHP 5.6 (uso exclusivo de `array()`, ausencia de operador `??`, compatibilidad con OCI8 legacy), exigiendo revisiones de sintaxis previas a cada despliegue.
3. **Bloqueos de Ejecución y Redes en Podman:** Conflictos esporádicos generados por archivos huérfanos de bloqueo en Apache (`httpd.pid`) y configuraciones de cortafuegos en la máquina virtual Linux, superados mediante la creación de scripts de soporte y automatización de red.
4. **Variabilidad en las Escalas Evaluativas de Moodle:** Las actividades creadas por distintos docentes podían calificarse indistintamente sobre 5.0, 10.0 o 100 puntos. Se diseñó una función matemática universal de normalización para estandarizar todas las notas a la escala oficial institucional de 0.0 a 5.0.

---

## 4.5 Diagrama de Gantt (Control de Avance)

A continuación se presenta el cronograma y seguimiento del avance de las actividades planificadas y ejecutadas a la fecha:

| ID | Actividad / Subactividad | Sem 1 | Sem 2 | Sem 3 | Sem 4 | Sem 5 | Sem 6 | % Avance Real |
| :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **1** | **Infraestructura y Virtualización Podman** | **X** | **X** | | | | | **100%** |
| 1.1 | Despliegue de contenedores Moodle 5.0 y Divisist | X | | | | | | 100% |
| 1.2 | Soporte a scripts de migración semestral y URLs | | X | | | | | 100% |
| **2** | **Arquitectura de Conexión Desacoplada** | | **X** | **X** | | | | **100%** |
| 2.1 | Diseño de puentes HTTP y seguridad con tokens | | X | | | | | 100% |
| 2.2 | Creación de `Moodle_model.php` y llamadas cURL | | | X | | | | 100% |
| **3** | **Persistencia y Modelo Relacional Oracle** | | | **X** | **X** | | | **100%** |
| 3.1 | DDL tablas `CONFIG_RUBRICA` y `SUBNOTAS` | | | X | | | | 100% |
| 3.2 | Desarrollo de `Subnotas_model.php` e IDs seguros | | | | X | | | 100% |
| **4** | **API de Extracción de Notas en Moodle** | | | | **X** | **X** | | **100%** |
| 4.1 | Script CLI con `$DB` (`grade_items`, `grade_grades`)| | | | X | | | 100% |
| 4.2 | Puente HTTP y deserialización en Divisist | | | | | X | | 100% |
| **5** | **Interfaz Docente y Rúbricas por Corte** | | | | | **X** | **X** | **100%** |
| 5.1 | Panel de doble pestaña y exclusión de duplicados | | | | | X | | 100% |
| 5.2 | Barra reactiva de progreso (100%) y AJAX | | | | | | X | 100% |
| **6** | **Cálculo de Nota Sugerida y Evaluación** | | | | | | **X** | **100%** |
| 6.1 | Algoritmo de normalización y reescalado | | | | | | X | 100% |
| 6.2 | Vista interactiva `calificar_rubrica.php` y modales | | | | | | X | 100% |

---

## 4.6 Adaptación al Entorno Organizacional

Durante el inicio de la práctica en la División de Sistemas, fue indispensable ajustar los siguientes patrones de comportamiento y hábitos de trabajo:
* **Alineación a Estándares y Convenciones Institucionales:** Adaptarse a la arquitectura preexistente de Divisist, comprendiendo que en un sistema de producción masivo no es viable realizar reescrituras completas, sino integraciones limpias, no intrusivas y que respeten las convenciones de nomenclatura histórica.
* **Cultura de Documentación Rigurosa:** Transitar del desarrollo empírico a un registro metodológico y diario de bitácoras técnicas (`DIA_1_...`, `DIA_2_...`), asegurando que cualquier ingeniero del equipo de desarrollo pueda replicar, mantener o auditar los módulos implementados.
* **Seguridad y Respeto a Entornos de Producción:** Interiorizar la estricta separación entre los entornos de pruebas locales y los servidores productivos institucionales, asumiendo protocolos rigurosos antes de ejecutar migraciones o cambios en bases de datos.

---

## 4.7 Tolerancia al Trabajo Bajo Presión

Durante el transcurso de las labores, se presentaron situaciones de alta exigencia donde los tiempos de entrega coincidían con la resolución de incidencias técnicas imprevistas, tales como el fallo de privilegios en Oracle (`ORA-01031`) y bloqueos en la comunicación de red entre contenedores. 

Las estrategias implementadas para mitigar la presión y asegurar el éxito del proyecto incluyeron:
1. **Descomposición del Problema (Divide y Vencerás):** Aislar cada falla en componentes mínimos verificables mediante pruebas unitarias por consola (`curl`, `php -l`, consultas SQL independientes) antes de diagnosticar la interfaz completa.
2. **Priorización Basada en Riesgo:** Concentrar los esfuerzos inicialmente en la estabilidad de la capa de datos y la seguridad criptográfica de la API, permitiendo que la capa de presentación se desarrollara sobre cimientos sólidos.
3. **Gestión Emocional y Comunicación Asertiva:** Mantener una comunicación fluida y transparente con el tutor empresarial y los docentes, reportando oportunamente los cuellos de botella encontrados y proponiendo soluciones técnicas viables.

---

## 4.8 Capacidad para Asumir Nuevas Responsabilidades

A medida que el proyecto avanzó, se asignaron y asumieron responsabilidades que trascendieron el desarrollo de software convencional:

| Nuevas Responsabilidades Asumidas | Forma en que Fueron Asumidas | Nivel de Interés, Compromiso y Eficiencia |
| :--- | :--- | :--- |
| **Soporte y Automatización de la Migración Semestral de Moodle** | Se estudiaron, analizaron y ejecutaron los scripts de migración institucional (`inyeccion_maestra.sh`, respaldos de MariaDB), asumiendo la responsabilidad de asegurar la continuidad de datos académicos de semestres anteriores. | **Alto Nivel de Interés y Compromiso:** Se asumió con rigurosidad técnica, ejecutando pruebas previas en máquinas locales y verificando la integridad de las rutas web `wwwroot`. |
| **Diseño de Mecanismos de Seguridad y Prevención de Vulnerabilidades** | Implementación proactiva de validación con `hash_equals()` contra ataques de tiempo y saneamiento de llamadas al sistema operativo mediante `escapeshellarg()`. | **Alta Eficiencia:** Se aplicaron estándares internacionales de OWASP sin que fueran exigidos como requerimiento funcional inicial, elevando la calidad del entregable. |
| **Diseño de Experiencia de Usuario (UX) para Docentes** | Planteamiento y diseño de la interfaz basada en pestañas para que el docente no solo califique, sino que tenga un tablero de control del avance de sus rúbricas semestrales. | **Alto Compromiso:** Proactividad demostrada al diseñar una solución que reduce los clics del usuario y previene activamente el error humano. |

---

# 5. CONCLUSIONES

1. **Interoperabilidad Exitosa sin Afectación a la Integridad de Datos:** Se logró establecer un canal de comunicación robusto, desacoplado y seguro entre Divisist y Moodle 5.0 mediante el uso de HTTP/cURL y scripts CLI. Este enfoque impidió la corrupción del árbol de contextos relacionales de Moodle y garantizó la total independencia tecnológica entre ambos sistemas.
2. **Automatización Eficiente del Proceso Calificativo:** La implementación de la API de calificaciones y el algoritmo de cálculo de subnotas eliminó por completo la necesidad de transcripción manual de notas por parte de los docentes universitarios, mitigando errores humanos y reduciendo a segundos una labor administrativa que previamente tomaba horas.
3. **Transparencia y Trazabilidad para la Comunidad Académica:** El modelo de base de datos relacional implementado en Oracle (`CONFIG_RUBRICA` y `SUBNOTAS`) asegura por primera vez en la institución un registro histórico detallado de los porcentajes y notas que componen cada corte, permitiendo tanto al docente como al estudiante consultar el desglose exacto mediante la funcionalidad "Ver Rúbrica".
4. **Superación de Limitaciones de Entornos Heterogéneos:** Se demostró la viabilidad de modernizar e integrar sistemas legados institucionales (PHP 5.6 sobre CodeIgniter 3 y Oracle) con arquitecturas modernas de contenedores (Podman, Moodle 5.0, PHP 8.x) aplicando patrones de diseño desacoplados y soluciones algorítmicas autónomas frente a restricciones de privilegios en bases de datos.
5. **Recomendaciones de Continuidad:** Se recomienda, para fases posteriores del proyecto, extender la vista de consulta de subnotas detalladas directamente al módulo de estudiantes en Divisist, e incorporar notificaciones automatizadas por correo electrónico institucional cuando un corte haya sido calificado y sincronizado.

---

# 6. BIBLIOGRAFÍA (NORMAS APA VIGENTES)

* British Computer Society. (2021). *Code Quality: The Open Source Perspective*. Addison-Wesley.
* Ellis, H. J., & Morelli, R. A. (2020). Teaching open source software development through real-world projects. *ACM Transactions on Computing Education (TOCE)*, 20(3), 1-24. https://doi.org/10.1145/3394960
* Gamma, E., Helm, R., Johnson, R., & Vlissides, J. (1994). *Design Patterns: Elements of Reusable Object-Oriented Software*. Addison-Wesley Professional.
* Moodle HQ. (2024). *Moodle Developer Documentation: Gradebook API and CLI Scripts*. Moodle.org. https://docs.moodle.org/dev/
* Oracle Corporation. (2023). *Oracle Database SQL Language Reference: NVL Function and Sequences*. Oracle Help Center. https://docs.oracle.com/en/database/oracle/oracle-database/
* Red Hat. (2024). *Podman Documentation: Managing pods, containers, and container images*. Red Hat Enterprise Linux. https://podman.io/docs
* Sommerville, I. (2019). *Ingeniería del Software* (10.ª ed.). Pearson Educación.
* Universidad Francisco de Paula Santander. (2021). *Proyecto Educativo Institucional (PEI) y Políticas de Gestión Tecnológica*. UFPS. https://ww2.ufps.edu.co

---

# 7. OBSERVACIONES Y ANEXOS

### Observaciones
* El presente informe consolida los avances correspondientes a las primeras seis fases de trabajo técnico, abarcando desde la configuración de contenedores hasta la culminación del módulo de cálculo y evaluación docente.
* El código fuente de todo el proyecto se encuentra respaldado y versionado en el repositorio oficial de GitHub del proyecto (`vivianaorg/Practica`).

### Anexos
* **Anexo A:** Documento Técnico de Conexión (`CONEXION_MOODLE_DIVISIST.md`).
* **Anexo B:** Bitácora Día 1 - Persistencia y Esquema de Base de Datos (`DIA_1_ARQUITECTURA_PERSISTENCIA.md`).
* **Anexo C:** Bitácora Día 2 - API de Calificaciones en Moodle (`DIA_2_API_CALIFICACIONES_MOODLE.md`).
* **Anexo D:** Bitácora Día 3 - Interfaz de Rúbricas por Corte (`DIA_3_INTERFAZ_DOCENTE_RUBRICAS.md`).
* **Anexo E:** Bitácora Día 4 - Algoritmo de Cálculo y Evaluación (`DIA_4_CALCULO_NOTA_SUGERIDA.md`).
* **Anexo F:** Script de Creación DDL en Oracle (`application/sql/crear_tablas_rubricas_subnotas.sql`).

---

# 8. FIRMAS DE APROBACIÓN

<br><br><br>

___________________________________________________  
**Ing. [Nombre del Jefe Inmediato / Supervisor]**  
Supervisor de Práctica Empresarial  
Cargo: Ingeniero Líder / Coordinador de Desarrollo  
División de Sistemas - UFPS  
C.C.:  
Email:  
Fecha de aprobación (dd/mm/aaaa): _____ / _____ / _________  

<br><br><br>

___________________________________________________  
**MSc. I.S Carmen Janeth Parada / MSG. I.S José Martin Calixto Cely**  
Docente de Prácticas Profesionales  
Programa de Ingeniería de Sistemas  
Facultad de Ingeniería - UFPS  
C.C.:  
Email:  
Fecha de aprobación (dd/mm/aaaa): _____ / _____ / _________  
