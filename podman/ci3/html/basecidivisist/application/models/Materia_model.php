<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materia_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    private function _limpiar_texto($str) {
        $str = strtolower(trim($str));
        $remplazos = array(
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n',
            'ä'=>'a', 'ë'=>'e', 'ï'=>'i', 'ö'=>'o', 'ü'=>'u'
        );
        $str = strtr($str, $remplazos);
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

        if (preg_match('/^(\d{3})(\d{3,})$/', $snBase, $mC)) {
            $carrera = $mC[1];
            $codMateria = $mC[2];
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

    private function _coincide_materia($subCodMateria, $curso) {
        $subClean = $this->_limpiar_texto($subCodMateria);
        if (empty($subClean)) {
            return false;
        }

        $cid = (string)$curso['id'];
        $shortname = isset($curso['shortname']) ? $curso['shortname'] : '';
        $fullname  = isset($curso['fullname']) ? $curso['fullname'] : '';
        $snClean   = $this->_limpiar_texto($shortname);
        $idClean   = $this->_limpiar_texto($cid);

        $directos = array($idClean, 'curso' . $idClean, $snClean);
        if (strpos($shortname, '-') !== false) {
            $p = explode('-', $shortname, 2);
            $directos[] = $this->_limpiar_texto($p[0]);
            if (preg_match('/^(\d{3})(\d{3,})$/', trim($p[0]), $mP)) {
                $directos[] = $this->_limpiar_texto($mP[2]);
            }
        } elseif (preg_match('/^(\d{3})(\d{3,})$/', $shortname, $mP)) {
            $directos[] = $this->_limpiar_texto($mP[2]);
        }
        if (!empty($curso['idnumber'])) {
            $directos[] = $this->_limpiar_texto($curso['idnumber']);
        }

        foreach (array_unique(array_filter($directos)) as $d) {
            if ($subClean === $d) {
                return true;
            }
        }

        $fnClean = $this->_limpiar_texto($fullname);
        $fnSinCurso = str_replace('curso', '', $fnClean);

        if ($subClean === $fnClean || $subClean === $fnSinCurso) {
            return true;
        }

        if (strlen($fnSinCurso) >= 4) {
            if (strpos($subClean, $fnSinCurso) !== false || strpos($fnSinCurso, $subClean) !== false) {
                return true;
            }
        }

        return false;
    }

    private function _normalizar_nota($n) {
        $f = floatval($n);
        if ($f > 5.0 && $f <= 100.0) {
            return round(($f / 100.0) * 5.0, 1);
        }
        return round($f, 2);
    }

    public function materiasMatriculadasPensum($codigo) {
        $nrows = 0;
        $output = array();

        $sql = "SELECT * FROM matricula.materiasMatriculadasPensum 
                WHERE COD_ALUMNO=SUBSTR('{$codigo}','4','4') 
                  AND COD_CARRERA=SUBSTR('{$codigo}','1','3')";

        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }

        $carreraEst = strlen($codigo) >= 3 ? substr($codigo, 0, 3) : '115';
        $cleanCod = strtolower(trim($codigo));

        $candsEst = array(
            "'{$cleanCod}'",
            "'" . strtoupper($cleanCod) . "'",
            "'{$codigo}'"
        );
        if ($cleanCod === '1152002' || $cleanCod === 'viviana') {
            $candsEst[] = "'1152002'";
            $candsEst[] = "'viviana'";
        } elseif ($cleanCod === '1152001' || $cleanCod === 'jorge' || $cleanCod === 'jorgeandresfs') {
            $candsEst[] = "'1152001'";
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

        if (empty($output) || !isset($output['CODCARMAT']) || empty($output['CODCARMAT'])) {
            if (!empty($cursosMoodle)) {
                $output = array(
                    'CODCARMAT'    => array(),
                    'CODMAT'       => array(),
                    'CODMATMAT'    => array(),
                    'NOMBREMAT'    => array(),
                    'GRUPO'        => array(),
                    'CREDITOS'     => array(),
                    'SEMESTRE'     => array(),
                    'DEF'          => array(),
                    'ID_MICRO'     => array(),
                    'CONTENIDO'    => array(),
                    'BIBLIOGRAFIA' => array(),
                    'EMAIL'        => array(),
                    'SECCIONAL'    => array(),
                    'RUBRICA'      => array(),
                    'NOTA1'        => array(),
                    'EST_NOTA1'    => array(),
                    'NOTA2'        => array(),
                    'EST_NOTA2'    => array(),
                    'NOTA3'        => array(),
                    'EST_NOTA3'    => array(),
                    'EXA'          => array(),
                    'EST_EXA'      => array(),
                    'HAB'          => array(),
                    'EST_HAB'      => array(),
                    'OPC'          => array(),
                    'EST_OPC'      => array(),
                    'CLASES'       => array()
                );

                $mapCortes = array(
                    '1' => '1', '1P' => '1', 'NOTA1' => '1',
                    '2' => '2', '2P' => '2', 'NOTA2' => '2',
                    '3' => '3', '3P' => '3', 'NOTA3' => '3',
                    'FINAL' => 'FINAL', 'EX' => 'FINAL', 'EXA' => 'FINAL', '4' => 'FINAL'
                );

                $semestreNum = 1;
                foreach ($cursosMoodle as $c) {
                    $fullname  = trim($c['fullname']);
                    $shortname = trim($c['shortname']);

                    $parsed = $this->_resolver_carrera_materia($shortname, $fullname, $carreraEst);
                    $codCarrera = $parsed['carrera'];
                    $codMateria = $parsed['cod_materia'];
                    $grupo      = $parsed['grupo'];

                    $subnotasPorFuente = array();
                    foreach ($todasSubnotas as $sub) {
                        $subMat = isset($sub->COD_MATERIA) ? trim($sub->COD_MATERIA) : '';
                        if ($this->_coincide_materia($subMat, $c)) {
                            $tipoRaw = isset($sub->TIPO_PREVIO) ? strtoupper(trim($sub->TIPO_PREVIO)) : '';
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

                    $tieneRubrica = false;
                    $nota1 = 0.0; $estNota1 = 'S';
                    $nota2 = 0.0; $estNota2 = 'S';
                    $nota3 = 0.0; $estNota3 = 'S';
                    $exa   = 0.0; $estExa   = 'S';

                    if ($cortesFinales['1']['tiene']) {
                        $nota1 = $cortesFinales['1']['nota'];
                        $estNota1 = 'V';
                        $tieneRubrica = true;
                    }
                    if ($cortesFinales['2']['tiene']) {
                        $nota2 = $cortesFinales['2']['nota'];
                        $estNota2 = 'V';
                        $tieneRubrica = true;
                    }
                    if ($cortesFinales['3']['tiene']) {
                        $nota3 = $cortesFinales['3']['nota'];
                        $estNota3 = 'V';
                        $tieneRubrica = true;
                    }
                    if ($cortesFinales['FINAL']['tiene']) {
                        $exa = $cortesFinales['FINAL']['nota'];
                        $estExa = 'V';
                        $tieneRubrica = true;
                    }

                    $def = 0.0;
                    if ($tieneRubrica) {
                        $acumulado = 0.0;
                        $peso = 0.0;
                        if ($estNota1 === 'V') { $acumulado += $nota1 * 0.233; $peso += 23.3; }
                        if ($estNota2 === 'V') { $acumulado += $nota2 * 0.233; $peso += 23.3; }
                        if ($estNota3 === 'V') { $acumulado += $nota3 * 0.234; $peso += 23.4; }
                        if ($estExa   === 'V') { $acumulado += $exa   * 0.300; $peso += 30.0; }
                        $def = ($peso > 0) ? round($acumulado, 1) : 0.0;
                    } else {
                        if (!empty($c['tiene_nota1'])) { $nota1 = $this->_normalizar_nota($c['nota1']); $estNota1 = 'V'; }
                        if (!empty($c['tiene_nota2'])) { $nota2 = $this->_normalizar_nota($c['nota2']); $estNota2 = 'V'; }
                        if (!empty($c['tiene_nota3'])) { $nota3 = $this->_normalizar_nota($c['nota3']); $estNota3 = 'V'; }
                        if (!empty($c['tiene_exa']))   { $exa   = $this->_normalizar_nota($c['exa']);   $estExa   = 'V'; }

                        $sum = 0.0; $cnt = 0;
                        if ($estNota1 === 'V') { $sum += $nota1; $cnt++; }
                        if ($estNota2 === 'V') { $sum += $nota2; $cnt++; }
                        if ($estNota3 === 'V') { $sum += $nota3; $cnt++; }
                        if ($estExa   === 'V') { $sum += $exa;   $cnt++; }
                        if ($cnt > 0) {
                            $def = round($sum / $cnt, 1);
                        } elseif (isset($c['def']) && floatval($c['def']) > 0) {
                            $def = $this->_normalizar_nota(floatval($c['def']));
                        }
                    }

                    $profEmail = !empty($c['profesor_email']) ? $c['profesor_email'] : 'docente@ufps.edu.co';
                    $contenido = !empty($c['summary']) ? $c['summary'] : "Curso virtual Moodle: {$fullname}.";

                    $output['CODCARMAT'][]    = $codCarrera;
                    $output['CODMAT'][]       = $codMateria;
                    $output['CODMATMAT'][]    = $codMateria;
                    $output['NOMBREMAT'][]    = strtoupper($fullname);
                    $output['GRUPO'][]        = $grupo;
                    $output['CREDITOS'][]     = 3;
                    $output['SEMESTRE'][]     = $semestreNum;
                    $output['DEF'][]          = $def;
                    $output['ID_MICRO'][]     = (int)$c['id'];
                    $output['CONTENIDO'][]    = $contenido;
                    $output['BIBLIOGRAFIA'][] = 'Recursos y material bibliográfico disponibles en Moodle.';
                    $output['EMAIL'][]        = $profEmail;
                    $output['SECCIONAL'][]    = '01';
                    $output['RUBRICA'][]      = 1;
                    $output['NOTA1'][]        = $nota1;
                    $output['EST_NOTA1'][]    = $estNota1;
                    $output['NOTA2'][]        = $nota2;
                    $output['EST_NOTA2'][]    = $estNota2;
                    $output['NOTA3'][]        = $nota3;
                    $output['EST_NOTA3'][]    = $estNota3;
                    $output['EXA'][]          = $exa;
                    $output['EST_EXA'][]      = $estExa;
                    $output['HAB'][]          = 0.0;
                    $output['EST_HAB'][]      = 'S';
                    $output['OPC'][]          = 0.0;
                    $output['EST_OPC'][]      = 'S';
                    $output['CLASES'][]       = array(
                        'ID'          => array((int)$c['id']),
                        'FECHA_INI'   => array('Aula Virtual Activa'),
                        'URL_REUNION' => array('http://localhost:10000/course/view.php?id=' . $c['id'])
                    );
                }
            }
        }

        return $output;
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
