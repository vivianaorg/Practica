-- ==============================================================================
-- SCRIPT DE POBLADO DE DATOS DE PRUEBA: ESTUDIANTE Y DOCENTE EN ORACLE
-- Proyecto: Divisist 2.0 / UFPS - Practica Profesional
-- Modulos: Informacion_academica.php (Estudiante) y Docente.php (Docente)
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. TABLA MATRICULA.materiasMatriculadasPensum (Para rol Estudiante)
-- ------------------------------------------------------------------------------
-- Limpiar registros de prueba anteriores para el estudiante 1151234
DELETE FROM MATRICULA.materiasMatriculadasPensum 
WHERE COD_CARRERA = '115' AND COD_ALUMNO = '1234' AND CODMAT = '5304';

INSERT INTO MATRICULA.materiasMatriculadasPensum (
    COD_CARRERA, COD_ALUMNO, CODCARMAT, CODMAT, CODMATMAT, NOMBREMAT,
    GRUPO, CREDITOS, SEMESTRE, DEF, ID_MICRO, CONTENIDO, BIBLIOGRAFIA,
    EMAIL, SECCIONAL, RUBRICA,
    NOTA1, EST_NOTA1, NOTA2, EST_NOTA2, NOTA3, EST_NOTA3,
    EXA, EST_EXA, HAB, EST_HAB, OPC, EST_OPC
) VALUES (
    '115', '1234', '115', '5304', '5304', 'ANALISIS Y DISENO DE SISTEMAS',
    'A', 3, 6, 3.8, 101, 
    'Unidad 1: Modelado de Requisitos' || CHR(10) || 'Unidad 2: Diagramas UML y Patrones' || CHR(10) || 'Unidad 3: Arquitectura Hexagonal y Limpia',
    'Sommerville, Software Engineering 10th Ed.' || CHR(10) || 'Pressman, Ingenieria de Software 7ma Ed.',
    'profesor.sistemas@ufps.edu.co', '01', 1,
    4.0, 'V',  -- Primer Previo: 4.0 Validada ('V')
    3.5, 'V',  -- Segundo Previo: 3.5 Validada ('V')
    0.0, 'S',  -- Tercer Previo: Sin evaluar ('S')
    0.0, 'S',  -- Examen Final: Pendiente ('S')
    0.0, 'S',  -- Habilitacion
    0.0, 'S'   -- Opcional
);

-- ------------------------------------------------------------------------------
-- 2. TABLAS DEL ESQUEMA SIA (Para rol Docente)
-- ------------------------------------------------------------------------------
-- A. Asignacion de grupo en SIA.D2_GRUPO_CARGADO
DELETE FROM SIA.D2_GRUPO_CARGADO 
WHERE COD_PROFESOR = '04608' AND COD_CARRERA = '115' AND COD_MATERIA = '5304' AND GRUPO = 'A';

INSERT INTO SIA.D2_GRUPO_CARGADO (
    COD_PROFESOR, CODIGO, COD_CARRERA, COD_MATERIA, GRUPO,
    NOMBRE, ROL_PROFESOR, NUM_ALUM_MATRICULADOS,
    SECCIONAL, NOMSECCIONAL, TIPO_CARRERA, UNICANOTA
) VALUES (
    '04608', '1155304-A', '115', '5304', 'A',
    'ANALISIS Y DISENO DE SISTEMAS', 'PRINCIPAL', 25,
    '01', 'CUCUTA', 'P', 'N'
);

-- B. Titularidad del grupo en SIA.GRUPOC
DELETE FROM SIA.GRUPOC 
WHERE COD_PROFESOR = '04608' AND COD_CARRERA = '115' AND COD_MATERIA = '5304' AND GRUPO = 'A';

INSERT INTO SIA.GRUPOC (COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL, COD_PROFESOR)
VALUES ('115', '5304', 'A', '01', '04608');

-- C. Tipos de Inclusion (Cortes) en SIA.TIPO_INCLUSION
MERGE INTO SIA.TIPO_INCLUSION t
USING (SELECT '1P' AS TIPO, '1P' AS TEXTO, 'PRIMER PREVIO' AS DESCRIPCION FROM DUAL) src
ON (t.TIPO = src.TIPO)
WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION);

MERGE INTO SIA.TIPO_INCLUSION t
USING (SELECT '2P' AS TIPO, '2P' AS TEXTO, 'SEGUNDO PREVIO' AS DESCRIPCION FROM DUAL) src
ON (t.TIPO = src.TIPO)
WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION);

MERGE INTO SIA.TIPO_INCLUSION t
USING (SELECT '3P' AS TIPO, '3P' AS TEXTO, 'TERCER PREVIO' AS DESCRIPCION FROM DUAL) src
ON (t.TIPO = src.TIPO)
WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION);

MERGE INTO SIA.TIPO_INCLUSION t
USING (SELECT 'EX' AS TIPO, 'EX' AS TEXTO, 'EXAMEN FINAL' AS DESCRIPCION FROM DUAL) src
ON (t.TIPO = src.TIPO)
WHEN NOT MATCHED THEN INSERT (TIPO, TEXTO, DESCRIPCION) VALUES (src.TIPO, src.TEXTO, src.DESCRIPCION);

-- D. Calendario y Fechas Habilitadas en SIA.GRUPO_INCLUSION
DELETE FROM SIA.GRUPO_INCLUSION 
WHERE COD_CARRERA = '115' AND COD_MATERIA = '5304' AND GRUPO = 'A' AND SECCIONAL = '01';

-- 1P Habilitado para digitacion
INSERT INTO SIA.GRUPO_INCLUSION (
    TIPO, COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL,
    ORDEN, VALIDADO, EDITADO, FECHA_INI, FECHA_FIN, FECHA_VALIDA
) VALUES (
    '1P', '115', '5304', 'A', '01',
    1, 'N', 'N', SYSDATE - 5, SYSDATE + 10, SYSDATE + 10
);

-- 2P Habilitado para digitacion
INSERT INTO SIA.GRUPO_INCLUSION (
    TIPO, COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL,
    ORDEN, VALIDADO, EDITADO, FECHA_INI, FECHA_FIN, FECHA_VALIDA
) VALUES (
    '2P', '115', '5304', 'A', '01',
    2, 'N', 'N', SYSDATE - 2, SYSDATE + 15, SYSDATE + 15
);

-- 3P Validado / Cerrado (Informe Generado)
INSERT INTO SIA.GRUPO_INCLUSION (
    TIPO, COD_CARRERA, COD_MATERIA, GRUPO, SECCIONAL,
    ORDEN, VALIDADO, EDITADO, FECHA_INI, FECHA_FIN, FECHA_VALIDA
) VALUES (
    '3P', '115', '5304', 'A', '01',
    3, 'S', 'N', SYSDATE - 25, SYSDATE - 5, SYSDATE - 5
);

-- ------------------------------------------------------------------------------
-- 3. TABLAS DE RÚBRICAS Y SUBNOTAS (Esquema UDOCENTE)
-- ------------------------------------------------------------------------------
-- Limpieza de pruebas previas
DELETE FROM CONFIG_RUBRICA 
WHERE COD_PROFESOR = '04608' AND COD_MATERIA = '1155304' AND GRUPO = 'A' AND SEMESTRE = '2026-1';

DELETE FROM SUBNOTAS 
WHERE COD_PROFESOR = '04608' AND COD_MATERIA = '1155304' AND GRUPO = 'A' AND SEMESTRE = '2026-1';

-- Rúbrica del Primer Previo (Suma 100%)
INSERT INTO CONFIG_RUBRICA (
    ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
    ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
) VALUES (
    (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
    '04608', '1155304', 'A', '2026-1', '1',
    101, 'Cuestionario Tema 1: Requerimientos', 'quiz', 40.0, SYSDATE
);

INSERT INTO CONFIG_RUBRICA (
    ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO,
    ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, TIPO_ACTIVIDAD, PORCENTAJE, FECHA_CREACION
) VALUES (
    (SELECT NVL(MAX(ID), 0) + 1 FROM CONFIG_RUBRICA),
    '04608', '1155304', 'A', '2026-1', '1',
    102, 'Taller Grupal: Diagramas de Casos de Uso', 'assign', 60.0, SYSDATE
);

-- Subnotas individuales del Estudiante 1151234
INSERT INTO SUBNOTAS (
    ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
    ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
) VALUES (
    (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
    '04608', '1155304', 'A', '2026-1', '1151234', '1',
    101, 'Cuestionario Tema 1: Requerimientos', 4.5, 5.0, 40.0, 1.80, 'ACEPTADA', SYSDATE
);

INSERT INTO SUBNOTAS (
    ID, COD_PROFESOR, COD_MATERIA, GRUPO, SEMESTRE, COD_ESTUDIANTE, TIPO_PREVIO,
    ID_ACTIVIDAD_MOODLE, NOMBRE_ACTIVIDAD, NOTA_ORIGINAL, NOTA_MAXIMA, PORCENTAJE, SUBNOTA, ESTADO, FECHA_REGISTRO
) VALUES (
    (SELECT NVL(MAX(ID), 0) + 1 FROM SUBNOTAS),
    '04608', '1155304', 'A', '2026-1', '1151234', '1',
    102, 'Taller Grupal: Diagramas de Casos de Uso', 3.7, 5.0, 60.0, 2.22, 'ACEPTADA', SYSDATE
);

COMMIT;
