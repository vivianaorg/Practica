<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materia_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    public function _limpiar_texto($str) {
        $str = trim((string)$str);
        $remplazos = array(
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n',
            'ä'=>'a', 'ë'=>'e', 'ï'=>'i', 'ö'=>'o', 'ü'=>'u',
            'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ñ'=>'n',
            'Ä'=>'a', 'Ë'=>'e', 'Ï'=>'i', 'Ö'=>'o', 'Ü'=>'u',
            "\xC3\x81" => 'a', "\xC3\x89" => 'e', "\xC3\x8D" => 'i', "\xC3\x93" => 'o', "\xC3\x9A" => 'u', "\xC3\x91" => 'n',
            "\xC3\xA1" => 'a', "\xC3\xA9" => 'e', "\xC3\xAD" => 'i', "\xC3\xB3" => 'o', "\xC3\xBA" => 'u', "\xC3\xB1" => 'n',
            "\xE1" => 'a', "\xE9" => 'e', "\xED" => 'i', "\xF3" => 'o', "\xFA" => 'u', "\xF1" => 'n',
            "\xC1" => 'a', "\xC9" => 'e', "\xCD" => 'i', "\xD3" => 'o', "\xDA" => 'u', "\xD1" => 'n'
        );
        $str = strtr($str, $remplazos);
        $str = strtolower($str);
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    private function _resolver_carrera_materia($shortname, $fullname, $carreraEstudiante) {
        $shortname = trim($shortname);
        $snBase = $shortname;
        $grupo = 'A';

        if (strpos($shortname, '-') !== false) {
            $partes = explode('-', $shortname, 2);
            $snBase = trim($partes[0]);
            $g = strtoupper(trim($partes[1]));
            if (!empty($g)) {
                $grupo = $g;
            }
        } elseif (preg_match('/\b([B-Z])\b/i', $fullname, $mG)) {
            $grupo = strtoupper($mG[1]);
        }

        $carrera = $carreraEstudiante;
        $codMateria = $snBase;

        if (!empty($carreraEstudiante) && strpos($snBase, $carreraEstudiante) === 0) {
            $resto = substr($snBase, strlen($carreraEstudiante));
            if ($resto !== '') {
                $codMateria = $resto;
            }
        }

        return array(
            'carrera'    => $carrera,
            'cod_materia'=> $codMateria,
            'grupo'      => $grupo,
            'sn_base'    => $snBase
        );
    }

    private function _obtener_todas_subnotas_estudiante($candsEst) {
        $subnotas = array();
        if (!isset($this->database2)) {
            return $subnotas;
        }
        $inEst = implode(',', array_unique($candsEst));
        $sql = "SELECT ID, COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, SUBNOTA, ESTADO 
                FROM SUBNOTAS 
                WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst}) 
                ORDER BY ID ASC";
        $this->database2->get_obj_array($sql, $subnotas);
        return $subnotas;
    }

    public function obtener_mapeo_canonical($valor) {
        $c = $this->_limpiar_texto($valor);
        if ($c === '1234' || $c === '1151234' || $c === 'biologia' || $c === 'cursobiologia' || $c === 'curso1234' || $c === 'curso1151234' || $c === '2') {
            return array(
                'curso_id'       => 2,
                'shortname'      => '1151234',
                'fullname'       => 'Biología',
                'fullname_upper' => 'BIOLOGIA',
                'nombre_limpio'  => 'biologia',
                'cod_materia'    => '1151234',
                'sia_carrera'    => '115',
                'sia_codmat'     => '1234',
                'sia_codmatmat'  => '1234',
                'sia_codigo'     => '1151234'
            );
        }
        if ($c === '5678' || $c === '1155678' || $c === 'matematicas' || $c === 'cursomatematicas' || $c === 'matematicas115' || $c === 'cursomatematicas115' || $c === 'curso5678' || $c === 'curso1155678' || $c === '3') {
            return array(
                'curso_id'       => 3,
                'shortname'      => '1155678',
                'fullname'       => 'Matemáticas',
                'fullname_upper' => 'MATEMATICAS',
                'nombre_limpio'  => 'matematicas',
                'cod_materia'    => '1155678',
                'sia_carrera'    => '115',
                'sia_codmat'     => '5678',
                'sia_codmatmat'  => '5678',
                'sia_codigo'     => '1155678'
            );
        }
        if ($c === '91011' || $c === '11591011' || $c === 'pruebapractica' || $c === 'cursopruebapractica' || $c === 'cursopruebaprtica' || $c === 'curso91011' || $c === 'curso11591011' || $c === 'pruebapractica115' || $c === 'prueba_practica' || $c === '4') {
            return array(
                'curso_id'       => 4,
                'shortname'      => '91011',
                'fullname'       => 'Curso prueba práctica',
                'fullname_upper' => 'CURSO PRUEBA PRACTICA',
                'nombre_limpio'  => 'pruebapractica',
                'cod_materia'    => '11591011',
                'sia_carrera'    => '115',
                'sia_codmat'     => '91011',
                'sia_codmatmat'  => '91011',
                'sia_codigo'     => '11591011'
            );
        }
        if ($c === 'p' || $c === '115p' || $c === 'cursop' || $c === 'prueba' || $c === 'cursoprueba' || $c === 'curso9' || $c === '5') {
            return array(
                'curso_id'       => 5,
                'shortname'      => 'p',
                'fullname'       => 'Prueba',
                'fullname_upper' => 'PRUEBA',
                'nombre_limpio'  => 'prueba',
                'cod_materia'    => '115p',
                'sia_carrera'    => '115',
                'sia_codmat'     => 'p',
                'sia_codmatmat'  => 'p',
                'sia_codigo'     => '115p'
            );
        }
        return null;
    }

    public function _coincide_materia($subCodMateria, $curso) {
        $canSub = $this->obtener_mapeo_canonical($subCodMateria);
        $cid = isset($curso['id']) ? (string)$curso['id'] : '';
        $shortname = isset($curso['shortname']) ? $curso['shortname'] : '';
        $fullname  = isset($curso['fullname']) ? $curso['fullname'] : '';
        $idnumber  = isset($curso['idnumber']) ? $curso['idnumber'] : '';

        $canCur = null;
        foreach (array($cid, $shortname, $fullname, $idnumber) as $cand) {
            if (!empty($cand)) {
                $canCur = $this->obtener_mapeo_canonical($cand);
                if ($canCur !== null) {
                    break;
                }
            }
        }

        if ($canSub !== null && $canCur !== null) {
            return ($canSub['cod_materia'] === $canCur['cod_materia']);
        }

        $subClean = $this->_limpiar_texto($subCodMateria);
        if (empty($subClean)) {
            return false;
        }

        if ($subClean === 'biologia111' || $subClean === 'cursobiologia111') {
            return false;
        }

        $snClean = $this->_limpiar_texto($shortname);
        $fnClean = $this->_limpiar_texto($fullname);
        $idClean = $this->_limpiar_texto($cid);

        $targets = array_unique(array_filter(array($snClean, $fnClean, $idClean, str_replace('curso', '', $fnClean))));
        foreach ($targets as $t) {
            if ($subClean === $t) {
                return true;
            }
        }
        return false;
    }

    private function _normalizar_nota($n) {
        $f = floatval($n);
        if ($f > 5.0 && $f <= 100.0) {
            return round(($f / 100.0) * 5.0, 2);
        }
        if ($f > 100.0) {
            return 5.0;
        }
        return round(min(5.0, max(0.0, $f)), 2);
    }

    private function _calcular_subnotas_materia($c, $todasSubnotas, $todasRubricas) {
        $mapCortes = array(
            '1' => '1', '1P' => '1', 'NOTA1' => '1', 'CORTE1' => '1', 'PRIMERPREVIO' => '1', 'PRIMERCORTE' => '1',
            '2' => '2', '2P' => '2', 'NOTA2' => '2', 'CORTE2' => '2', 'SEGUNDOPREVIO' => '2', 'SEGUNDOCORTE' => '2',
            '3' => '3', '3P' => '3', 'NOTA3' => '3', 'CORTE3' => '3', 'TERCERPREVIO' => '3', 'TERCERCORTE' => '3',
            'FINAL' => 'FINAL', 'EX' => 'FINAL', 'EXA' => 'FINAL', '4' => 'FINAL', 'EXAMEN' => 'FINAL', 'EXAMENFINAL' => 'FINAL', 'CORTEFINAL' => 'FINAL'
        );

        $subnotasPorFuente = array();
        foreach ($todasSubnotas as $sub) {
            $subMat = isset($sub->COD_MATERIA) ? trim($sub->COD_MATERIA) : '';
            if ($this->_coincide_materia($subMat, $c)) {
                $tipoRaw = isset($sub->TIPO_PREVIO) ? preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($sub->TIPO_PREVIO))) : '';
                $cN = isset($mapCortes[$tipoRaw]) ? $mapCortes[$tipoRaw] : $tipoRaw;
                $val = isset($sub->SUBNOTA) ? floatval($sub->SUBNOTA) : 0.0;
                $subId = isset($sub->ID) ? intval($sub->ID) : 0;

                if (in_array($cN, array('1', '2', '3', 'FINAL'))) {
                    if (!isset($subnotasPorFuente[$subMat])) {
                        $subnotasPorFuente[$subMat] = array(
                            '1' => array('suma' => 0.0, 'items' => 0, 'max_id' => 0),
                            '2' => array('suma' => 0.0, 'items' => 0, 'max_id' => 0),
                            '3' => array('suma' => 0.0, 'items' => 0, 'max_id' => 0),
                            'FINAL' => array('suma' => 0.0, 'items' => 0, 'max_id' => 0)
                        );
                    }
                    $subnotasPorFuente[$subMat][$cN]['suma'] += $val;
                    $subnotasPorFuente[$subMat][$cN]['items']++;
                    if ($subId > $subnotasPorFuente[$subMat][$cN]['max_id']) {
                        $subnotasPorFuente[$subMat][$cN]['max_id'] = $subId;
                    }
                }
            }
        }

        $cortesFinales = array(
            '1'     => array('nota' => 0.0, 'tiene' => false, 'max_id' => 0),
            '2'     => array('nota' => 0.0, 'tiene' => false, 'max_id' => 0),
            '3'     => array('nota' => 0.0, 'tiene' => false, 'max_id' => 0),
            'FINAL' => array('nota' => 0.0, 'tiene' => false, 'max_id' => 0)
        );

        foreach ($subnotasPorFuente as $fuenteMat => $datosCortes) {
            foreach (array('1', '2', '3', 'FINAL') as $cN) {
                if ($datosCortes[$cN]['items'] > 0) {
                    if (!$cortesFinales[$cN]['tiene'] || $datosCortes[$cN]['max_id'] > $cortesFinales[$cN]['max_id']) {
                        $cortesFinales[$cN]['nota']   = $this->_normalizar_nota($datosCortes[$cN]['suma']);
                        $cortesFinales[$cN]['tiene']  = true;
                        $cortesFinales[$cN]['max_id'] = $datosCortes[$cN]['max_id'];
                    }
                }
            }
        }

        $tieneConfigRubrica = false;
        foreach ($todasRubricas as $r) {
            $rMat = isset($r->COD_MATERIA) ? trim($r->COD_MATERIA) : '';
            if ($this->_coincide_materia($rMat, $c)) {
                $tieneConfigRubrica = true;
                break;
            }
        }

        $tieneSubnotas = false;
        foreach (array('1', '2', '3', 'FINAL') as $cN) {
            if ($cortesFinales[$cN]['tiene']) {
                $tieneSubnotas = true;
                break;
            }
        }

        $tieneRubrica = ($tieneConfigRubrica || $tieneSubnotas);

        $nota1 = 0.0; $estNota1 = 'S';
        $nota2 = 0.0; $estNota2 = 'S';
        $nota3 = 0.0; $estNota3 = 'S';
        $exa   = 0.0; $estExa   = 'S';

        if ($cortesFinales['1']['tiene']) { $nota1 = $cortesFinales['1']['nota']; $estNota1 = 'V'; }
        if ($cortesFinales['2']['tiene']) { $nota2 = $cortesFinales['2']['nota']; $estNota2 = 'V'; }
        if ($cortesFinales['3']['tiene']) { $nota3 = $cortesFinales['3']['nota']; $estNota3 = 'V'; }
        if ($cortesFinales['FINAL']['tiene']) { $exa = $cortesFinales['FINAL']['nota']; $estExa = 'V'; }

        if (!$tieneConfigRubrica && !$tieneSubnotas) {
            if (!empty($c['tiene_nota1'])) { $nota1 = $this->_normalizar_nota($c['nota1']); $estNota1 = 'V'; }
            if (!empty($c['tiene_nota2'])) { $nota2 = $this->_normalizar_nota($c['nota2']); $estNota2 = 'V'; }
            if (!empty($c['tiene_nota3'])) { $nota3 = $this->_normalizar_nota($c['nota3']); $estNota3 = 'V'; }
            if (!empty($c['tiene_exa']))   { $exa   = $this->_normalizar_nota($c['exa']);   $estExa   = 'V'; }
        }

        $def = 0.0;
        if ($tieneSubnotas) {
            $def = round((($nota1 + $nota2 + $nota3) / 3.0) * 0.70 + ($exa * 0.30), 2);
        } else {
            $sum = 0.0; $cnt = 0;
            if ($estNota1 === 'V') { $sum += $nota1; $cnt++; }
            if ($estNota2 === 'V') { $sum += $nota2; $cnt++; }
            if ($estNota3 === 'V') { $sum += $nota3; $cnt++; }
            if ($estExa   === 'V') { $sum += $exa;   $cnt++; }
            if ($cnt > 0) {
                $def = round($sum / $cnt, 2);
            } elseif (isset($c['def']) && floatval($c['def']) > 0) {
                $def = $this->_normalizar_nota(floatval($c['def']));
            }
        }
        $def = round(min(5.0, max(0.0, $def)), 2);

        return array(
            'tiene_rubrica' => $tieneRubrica,
            'nota1'         => $nota1,
            'est_nota1'     => $estNota1,
            'nota2'         => $nota2,
            'est_nota2'     => $estNota2,
            'nota3'         => $nota3,
            'est_nota3'     => $estNota3,
            'exa'           => $exa,
            'est_exa'       => $estExa,
            'def'           => $def
        );
    }

    public function persistir_materia_pensum($codigo, $codCarrera, $codAlumno, $codMateria, $nombreMateria, $grupo, $calc, $idMicro = 0, $email = 'docente@ufps.edu.co') {
        if (!isset($this->database2)) {
            return;
        }
        $conn = $this->database2->get_conn();
        if (!$conn) {
            return;
        }

        $rubVal = $calc['tiene_rubrica'] ? 1 : 0;
        $n1 = (float)$calc['nota1'];
        $e1 = $calc['est_nota1'];
        $n2 = (float)$calc['nota2'];
        $e2 = $calc['est_nota2'];
        $n3 = (float)$calc['nota3'];
        $e3 = $calc['est_nota3'];
        $ex = (float)$calc['exa'];
        $eEx = $calc['est_exa'];
        $def = (float)$calc['def'];

        $can = $this->obtener_mapeo_canonical($codMateria);
        if ($can === null) {
            $can = $this->obtener_mapeo_canonical($nombreMateria);
        }

        $nomEsc = str_replace("'", "''", $nombreMateria);
        $codEsc = str_replace("'", "''", $codMateria);
        $carrEsc = str_replace("'", "''", $codCarrera);
        $alumEsc = str_replace("'", "''", $codAlumno);
        $cleanCod = str_replace("'", "''", $codigo);

        if ($can !== null) {
            $siaCod = $can['sia_codmat'];
            $siaNom = $can['fullname_upper'];
            $sqlUpdate = "UPDATE MATRICULA.materiasMatriculadasPensum 
                          SET NOTA1 = {$n1}, EST_NOTA1 = '{$e1}',
                              NOTA2 = {$n2}, EST_NOTA2 = '{$e2}',
                              NOTA3 = {$n3}, EST_NOTA3 = '{$e3}',
                              EXA = {$ex}, EST_EXA = '{$eEx}',
                              DEF = {$def},
                              RUBRICA = {$rubVal}
                          WHERE (COD_ALUMNO = '{$alumEsc}' OR COD_ALUMNO = '{$cleanCod}')
                            AND (CODMAT IN ('{$siaCod}', '{$can['sia_codigo']}', '{$can['cod_materia']}', '{$codEsc}')
                                 OR CODMATMAT IN ('{$siaCod}', '{$can['sia_codigo']}', '{$can['cod_materia']}', '{$codEsc}')
                                 OR LOWER(TRIM(NOMBREMAT)) LIKE '%{$can['nombre_limpio']}%')";
        } else {
            $sqlUpdate = "UPDATE MATRICULA.materiasMatriculadasPensum 
                          SET NOTA1 = {$n1}, EST_NOTA1 = '{$e1}',
                              NOTA2 = {$n2}, EST_NOTA2 = '{$e2}',
                              NOTA3 = {$n3}, EST_NOTA3 = '{$e3}',
                              EXA = {$ex}, EST_EXA = '{$eEx}',
                              DEF = {$def},
                              RUBRICA = {$rubVal}
                          WHERE (COD_ALUMNO = '{$alumEsc}' OR COD_ALUMNO = '{$cleanCod}')
                            AND (CODMAT = '{$codEsc}' OR CODMATMAT = '{$codEsc}' OR LOWER(TRIM(NOMBREMAT)) = LOWER(TRIM('{$nomEsc}')))";
        }

        $stid = @oci_parse($conn, $sqlUpdate);
        $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
        $rowsAff = @oci_num_rows($stid);
        @oci_free_statement($stid);

        if (!$ok || $rowsAff === 0) {
            $insCodMat = ($can !== null) ? $can['sia_codmat'] : $codEsc;
            $insNomMat = ($can !== null) ? $can['fullname_upper'] : $nomEsc;
            $sqlIns = "INSERT INTO MATRICULA.materiasMatriculadasPensum (
                COD_CARRERA, COD_ALUMNO, CODCARMAT, CODMAT, CODMATMAT, NOMBREMAT,
                GRUPO, CREDITOS, SEMESTRE, DEF, ID_MICRO, CONTENIDO, BIBLIOGRAFIA,
                EMAIL, SECCIONAL, RUBRICA,
                NOTA1, EST_NOTA1, NOTA2, EST_NOTA2, NOTA3, EST_NOTA3,
                EXA, EST_EXA, HAB, EST_HAB, OPC, EST_OPC
            ) VALUES (
                '{$carrEsc}', '{$alumEsc}', '{$carrEsc}', '{$insCodMat}', '{$insCodMat}', '{$insNomMat}',
                '{$grupo}', 3, 1, {$def}, {$idMicro}, 'Contenido Moodle', 'Bibliografia Moodle',
                '{$email}', '01', {$rubVal},
                {$n1}, '{$e1}', {$n2}, '{$e2}', {$n3}, '{$e3}',
                {$ex}, '{$eEx}', 0.0, 'S', 0.0, 'S'
            )";
            $stidIns = @oci_parse($conn, $sqlIns);
            @oci_execute($stidIns, OCI_NO_AUTO_COMMIT);
            @oci_free_statement($stidIns);
        }
        @oci_commit($conn);
        @oci_close($conn);
    }

    public function materiasMatriculadasPensum($codigo) {
        $nrows = 0;
        $output = array();

        $carreraEst = strlen($codigo) >= 3 ? substr($codigo, 0, 3) : '115';
        $alumnoEst  = strlen($codigo) >= 7 ? substr($codigo, 3, 4) : $codigo;
        $cleanCod   = strtolower(trim($codigo));

        $sql = "SELECT * FROM matricula.materiasMatriculadasPensum 
                WHERE (COD_ALUMNO=SUBSTR('{$codigo}','4','4') OR COD_ALUMNO='{$codigo}' OR COD_ALUMNO='{$cleanCod}') 
                  AND (COD_CARRERA=SUBSTR('{$codigo}','1','3') OR COD_CARRERA='115')";

        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }

        $candsEst = array(
            "'{$cleanCod}'",
            "'" . strtoupper($cleanCod) . "'",
            "'{$codigo}'",
            "'{$alumnoEst}'"
        );
        if ($cleanCod === '1152002' || $cleanCod === 'viviana' || $cleanCod === '2002') {
            $candsEst[] = "'1152002'";
            $candsEst[] = "'2002'";
            $candsEst[] = "'viviana'";
        } elseif ($cleanCod === '1152001' || $cleanCod === 'jorge' || $cleanCod === 'jorgeandresfs' || $cleanCod === '2001') {
            $candsEst[] = "'1152001'";
            $candsEst[] = "'2001'";
            $candsEst[] = "'jorge'";
            $candsEst[] = "'jorgeandresfs'";
        }

        $cursosMoodle = array();
        try {
            $CI =& get_instance();
            if (!isset($CI->Moodle_model)) {
                $CI->load->model('Moodle_model');
            }
            if (isset($CI->Moodle_model)) {
                $resMoodle = $CI->Moodle_model->obtener_cursos_estudiante($codigo);
                if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['cursos']) && is_array($resMoodle['datos']['cursos'])) {
                    $cursosMoodle = $resMoodle['datos']['cursos'];
                }
                if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['estudiante'])) {
                    $e = $resMoodle['datos']['estudiante'];
                    if (!empty($e['username'])) { $candsEst[] = "'" . strtolower(trim($e['username'])) . "'"; }
                    if (!empty($e['idnumber'])) { $candsEst[] = "'" . strtolower(trim($e['idnumber'])) . "'"; }
                    if (!empty($e['codigo']))   { $candsEst[] = "'" . strtolower(trim($e['codigo'])) . "'"; }
                    if (isset($CI->session)) {
                        $sessEst = $CI->session->userdata('estudiante_divisist');
                        if ($sessEst) {
                            if (!empty($e['nombres']))   { $sessEst->NOMBRES   = $e['nombres']; }
                            if (!empty($e['apellidos'])) { $sessEst->APELLIDOS = $e['apellidos']; }
                            $CI->session->set_userdata('estudiante_divisist', $sessEst);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Materia_model Moodle exception: ' . $e->getMessage());
        }

        $todasSubnotas = $this->_obtener_todas_subnotas_estudiante($candsEst);

        $todasRubricas = array();
        if (isset($this->database2)) {
            $this->database2->get_obj_array("SELECT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO FROM CONFIG_RUBRICA", $todasRubricas);
        }

        $canonicalList = array(
            '1151234'  => array('id' => 2, 'shortname' => '1151234', 'fullname' => 'Biología',               'cod_materia' => '1151234', 'sia_codmat' => '1234',  'sia_nom' => 'BIOLOGIA'),
            '1155678'  => array('id' => 3, 'shortname' => '1155678', 'fullname' => 'Matemáticas',            'cod_materia' => '1155678', 'sia_codmat' => '5678',  'sia_nom' => 'MATEMATICAS'),
            '11591011' => array('id' => 4, 'shortname' => '91011',   'fullname' => 'Curso prueba práctica', 'cod_materia' => '11591011', 'sia_codmat' => '91011', 'sia_nom' => 'CURSO PRUEBA PRACTICA'),
            '115p'     => array('id' => 5, 'shortname' => 'p',       'fullname' => 'Prueba',                 'cod_materia' => '115p',     'sia_codmat' => 'p',     'sia_nom' => 'PRUEBA')
        );

        $keys = array(
            'CODCARMAT', 'CODMAT', 'CODMATMAT', 'NOMBREMAT', 'GRUPO', 'CREDITOS', 'SEMESTRE',
            'DEF', 'ID_MICRO', 'CONTENIDO', 'BIBLIOGRAFIA', 'EMAIL', 'SECCIONAL', 'RUBRICA',
            'TIENE_RUBRICA', 'NOTA1', 'EST_NOTA1', 'NOTA2', 'EST_NOTA2', 'NOTA3', 'EST_NOTA3',
            'EXA', 'EST_EXA', 'HAB', 'EST_HAB', 'OPC', 'EST_OPC', 'CLASES'
        );

        if (!isset($output['CODMAT']) || !is_array($output['CODMAT'])) {
            $output = array();
            foreach ($keys as $k) {
                $output[$k] = array();
            }
        } else {
            foreach ($keys as $k) {
                if (!isset($output[$k]) || !is_array($output[$k])) {
                    $output[$k] = array();
                }
            }
        }

        $total = count($output['CODMAT']);
        $matchedCanKeys = array();

        for ($i = 0; $i < $total; $i++) {
            $cMat = isset($output['CODMAT'][$i]) ? $output['CODMAT'][$i] : '';
            $nMat = isset($output['NOMBREMAT'][$i]) ? $output['NOMBREMAT'][$i] : '';
            $gr   = isset($output['GRUPO'][$i]) ? $output['GRUPO'][$i] : 'A';
            $idM  = isset($output['ID_MICRO'][$i]) ? (int)$output['ID_MICRO'][$i] : 0;
            $em   = isset($output['EMAIL'][$i]) ? $output['EMAIL'][$i] : 'docente@ufps.edu.co';

            $curMatch = array('id' => $idM, 'shortname' => $cMat, 'fullname' => $nMat);
            foreach ($cursosMoodle as $cm) {
                if ($this->_coincide_materia($cMat, $cm) || $this->_coincide_materia($nMat, $cm)) {
                    $curMatch = $cm;
                    break;
                }
            }

            $canObj = $this->obtener_mapeo_canonical($cMat);
            if ($canObj === null) {
                $canObj = $this->obtener_mapeo_canonical($nMat);
            }
            if ($canObj !== null) {
                $matchedCanKeys[$canObj['cod_materia']] = true;
                if (empty($idM) && !empty($canObj['curso_id'])) {
                    $idM = $canObj['curso_id'];
                    $output['ID_MICRO'][$i] = $idM;
                }
            }

            $calc = $this->_calcular_subnotas_materia($curMatch, $todasSubnotas, $todasRubricas);

            $output['TIENE_RUBRICA'][$i] = $calc['tiene_rubrica'] ? 1 : 0;
            $output['RUBRICA'][$i]       = $calc['tiene_rubrica'] ? 1 : 0;
            $output['NOTA1'][$i]         = $calc['nota1'];
            $output['EST_NOTA1'][$i]     = $calc['est_nota1'];
            $output['NOTA2'][$i]         = $calc['nota2'];
            $output['EST_NOTA2'][$i]     = $calc['est_nota2'];
            $output['NOTA3'][$i]         = $calc['nota3'];
            $output['EST_NOTA3'][$i]     = $calc['est_nota3'];
            $output['EXA'][$i]           = $calc['exa'];
            $output['EST_EXA'][$i]       = $calc['est_exa'];
            $output['DEF'][$i]           = $calc['def'];

            $this->persistir_materia_pensum($codigo, $carreraEst, $alumnoEst, $cMat, $nMat, $gr, $calc, $idM, $em);
        }

        foreach ($canonicalList as $canCod => $canDef) {
            if (isset($matchedCanKeys[$canCod])) {
                continue;
            }
            $curMatch = array('id' => $canDef['id'], 'shortname' => $canDef['shortname'], 'fullname' => $canDef['fullname']);
            foreach ($cursosMoodle as $cm) {
                if ($this->_coincide_materia($canCod, $cm)) {
                    $curMatch = $cm;
                    break;
                }
            }

            $calc = $this->_calcular_subnotas_materia($curMatch, $todasSubnotas, $todasRubricas);

            $output['CODCARMAT'][]     = $carreraEst;
            $output['CODMAT'][]        = $canDef['sia_codmat'];
            $output['CODMATMAT'][]     = $canDef['sia_codmat'];
            $output['NOMBREMAT'][]     = $canDef['sia_nom'];
            $output['GRUPO'][]         = 'A';
            $output['CREDITOS'][]      = 3;
            $output['SEMESTRE'][]      = 1;
            $output['DEF'][]           = $calc['def'];
            $output['ID_MICRO'][]      = $canDef['id'];
            $output['CONTENIDO'][]     = 'Contenido Moodle';
            $output['BIBLIOGRAFIA'][]  = 'Recursos y material bibliografico disponibles en Moodle.';
            $output['EMAIL'][]         = 'docente@ufps.edu.co';
            $output['SECCIONAL'][]     = '01';
            $output['RUBRICA'][]       = $calc['tiene_rubrica'] ? 1 : 0;
            $output['TIENE_RUBRICA'][] = $calc['tiene_rubrica'] ? 1 : 0;
            $output['NOTA1'][]         = $calc['nota1'];
            $output['EST_NOTA1'][]     = $calc['est_nota1'];
            $output['NOTA2'][]         = $calc['nota2'];
            $output['EST_NOTA2'][]     = $calc['est_nota2'];
            $output['NOTA3'][]         = $calc['nota3'];
            $output['EST_NOTA3'][]     = $calc['est_nota3'];
            $output['EXA'][]           = $calc['exa'];
            $output['EST_EXA'][]       = $calc['est_exa'];
            $output['HAB'][]           = 0.0;
            $output['EST_HAB'][]       = 'S';
            $output['OPC'][]           = 0.0;
            $output['EST_OPC'][]       = 'S';
            $output['CLASES'][]        = array(
                'ID'          => array((int)$canDef['id']),
                'FECHA_INI'   => array('Aula Virtual Activa'),
                'URL_REUNION' => array('http://localhost:10000/course/view.php?id=' . $canDef['id'])
            );

            $this->persistir_materia_pensum($codigo, $carreraEst, $alumnoEst, $canDef['sia_codmat'], $canDef['sia_nom'], 'A', $calc, $canDef['id'], 'docente@ufps.edu.co');
        }

        return $output;
    }
    }

    public function codigo_proyecto($cod_carrera, $cod_alumno) {
        $obj = null;
        $sql = "SELECT p.cod_materia codigo_proyecto 
                FROM sia.alumno a, sia.pensum p 
                WHERE a.cod_carrera = p.cod_carrera 
                  AND a.pensum = p.tipo_pensum 
                  AND a.cod_carrera = '{$cod_carrera}' 
                  AND a.cod_alumno = '{$cod_alumno}'";

        if (isset($this->database2)) {
            $this->database2->get_sql_object($sql, $obj);
        }
        return $obj;
    }
}
