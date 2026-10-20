<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Materia_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!isset($this->database2)) {
            $this->load->library('database2');
        }
    }

    private function _build_cands_est($codigo) {
        $cands = array(
            "'" . strtolower(trim($codigo)) . "'",
            "'" . strtoupper(trim($codigo)) . "'",
            "'" . trim($codigo) . "'"
        );
        return $cands;
    }

    private function _add_moodle_cands(&$cands, $codigo) {
        try {
            $CI =& get_instance();
            if (!isset($CI->Moodle_model)) {
                $CI->load->model('Moodle_model');
            }
            if (isset($CI->Moodle_model)) {
                $res = $CI->Moodle_model->obtener_cursos_estudiante($codigo);
                if (!empty($res['exito']) && isset($res['datos']['estudiante'])) {
                    $e = $res['datos']['estudiante'];
                    if (!empty($e['username'])) { $cands[] = "'" . strtolower(trim($e['username'])) . "'"; }
                    if (!empty($e['idnumber'])) { $cands[] = "'" . strtolower(trim($e['idnumber'])) . "'"; }
                    if (!empty($e['codigo']))   { $cands[] = "'" . strtolower(trim($e['codigo'])) . "'"; }
                }
                return $res;
            }
        } catch (Exception $ex) {
            log_message('error', 'Materia_model _add_moodle_cands: ' . $ex->getMessage());
        }
        return null;
    }

    private function _subnotas_map($candsEst) {
        $subnotasMap = array();
        if (!isset($this->database2)) {
            return $subnotasMap;
        }
        $subnotas = array();
        $inEst = implode(',', array_unique($candsEst));
        $sqlSub = "SELECT COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO, ROUND(SUM(SUBNOTA), 2) AS NOTA
                   FROM SUBNOTAS
                   WHERE LOWER(TRIM(COD_ESTUDIANTE)) IN ({$inEst})
                   GROUP BY COD_MATERIA, GRUPO, SEMESTRE, TIPO_PREVIO";
        $this->database2->get_obj_array($sqlSub, $subnotas);
        if (empty($subnotas)) {
            return $subnotasMap;
        }
        foreach ($subnotas as $row) {
            $codMatTrim = strtolower(trim($row->COD_MATERIA));
            $grpTrim    = strtoupper(trim($row->GRUPO));
            $corte      = trim($row->TIPO_PREVIO);
            $notaVal    = floatval($row->NOTA);

            $codMatBase = $codMatTrim;
            if (preg_match('/^\d{3}(.+)$/', $codMatTrim, $m) && strlen($codMatTrim) > 3) {
                $codMatBase = $m[1];
            }

            $clavesMap = array(
                $codMatTrim . '_' . $grpTrim,
                $codMatTrim . '_-',
                $codMatTrim,
                $codMatBase . '_' . $grpTrim,
                $codMatBase . '_-',
                $codMatBase,
            );
            if (strpos($codMatTrim, 'curso_') === 0) {
                $idNum = substr($codMatTrim, 6);
                $clavesMap[] = $idNum . '_' . $grpTrim;
                $clavesMap[] = $idNum . '_-';
                $clavesMap[] = $idNum;
            }

            foreach ($clavesMap as $mKey) {
                if (!isset($subnotasMap[$mKey])) {
                    $subnotasMap[$mKey] = array(
                        'COD_MATERIA'   => $row->COD_MATERIA,
                        'GRUPO'         => $row->GRUPO,
                        'SEMESTRE'      => isset($row->SEMESTRE) ? $row->SEMESTRE : '',
                        'TIENE_RUBRICA' => true,
                        'NOTA1'         => 0.0, 'EST_NOTA1' => 'S',
                        'NOTA2'         => 0.0, 'EST_NOTA2' => 'S',
                        'NOTA3'         => 0.0, 'EST_NOTA3' => 'S',
                        'EXA'           => 0.0, 'EST_EXA'   => 'S',
                    );
                }
                if (in_array($corte, array('1', '1P', 'NOTA1'))) {
                    $subnotasMap[$mKey]['NOTA1'] = $notaVal;
                    $subnotasMap[$mKey]['EST_NOTA1'] = 'V';
                } elseif (in_array($corte, array('2', '2P', 'NOTA2'))) {
                    $subnotasMap[$mKey]['NOTA2'] = $notaVal;
                    $subnotasMap[$mKey]['EST_NOTA2'] = 'V';
                } elseif (in_array($corte, array('3', '3P', 'NOTA3'))) {
                    $subnotasMap[$mKey]['NOTA3'] = $notaVal;
                    $subnotasMap[$mKey]['EST_NOTA3'] = 'V';
                } elseif (in_array($corte, array('FINAL', 'EX', 'EXA', '4'))) {
                    $subnotasMap[$mKey]['EXA'] = $notaVal;
                    $subnotasMap[$mKey]['EST_EXA'] = 'V';
                }
            }
        }
        return $subnotasMap;
    }

    private function _buscar_subnotas($subnotasMap, $codMat, $codCarrera, $grupo) {
        if (empty($subnotasMap)) {
            return null;
        }
        $codMatLow  = strtolower(trim($codMat));
        $grupoUp    = strtoupper(trim($grupo));
        $carreraLow = strtolower(trim($codCarrera));

        $codMatBase = $codMatLow;
        if (preg_match('/^\d{3}(.+)$/', $codMatLow, $m) && strlen($codMatLow) > 3) {
            $codMatBase = $m[1];
        }

        $candidatos = array(
            $codMatLow . '_' . $grupoUp,
            $codMatLow . '_-',
            $codMatLow,
            $codMatBase . '_' . $grupoUp,
            $codMatBase . '_-',
            $codMatBase,
            $carreraLow . $codMatLow . '_' . $grupoUp,
            $carreraLow . $codMatLow . '_-',
            $carreraLow . $codMatLow,
            $carreraLow . $codMatBase . '_' . $grupoUp,
            $carreraLow . $codMatBase . '_-',
            $carreraLow . $codMatBase,
        );

        foreach ($candidatos as $cand) {
            if (isset($subnotasMap[$cand])) {
                return $subnotasMap[$cand];
            }
        }

        foreach ($subnotasMap as $sVal) {
            $matOracle = strtolower(trim($sVal['COD_MATERIA']));
            $matBase   = $matOracle;
            if (preg_match('/^\d{3}(.+)$/', $matOracle, $m2) && strlen($matOracle) > 3) {
                $matBase = $m2[1];
            }
            if ($matBase === $codMatLow || $matBase === $codMatBase ||
                $matOracle === $carreraLow . $codMatLow ||
                $matOracle === $carreraLow . $codMatBase ||
                stripos($matBase, $codMatBase) !== false ||
                stripos($codMatBase, $matBase) !== false) {
                return $sVal;
            }
        }

        return null;
    }

    private function _normalizar_nota($n) {
        if ($n > 5.0 && $n <= 100.0) {
            return round(($n / 100.0) * 5.0, 1);
        }
        return round($n, 2);
    }

    private function _overlay_subnotas_en_oracle(&$output, $subnotasMap) {
        if (empty($subnotasMap) || empty($output) || !isset($output['CODCARMAT'])) {
            return;
        }
        $numFilas = count($output['CODCARMAT']);
        for ($i = 0; $i < $numFilas; $i++) {
            $oraCarrera = isset($output['CODCARMAT'][$i]) ? trim($output['CODCARMAT'][$i]) : '';
            $oraCodMat  = isset($output['CODMATMAT'][$i]) ? trim($output['CODMATMAT'][$i]) : '';
            $oraGrupo   = isset($output['GRUPO'][$i]) ? trim($output['GRUPO'][$i]) : 'A';

            $encontrado = $this->_buscar_subnotas($subnotasMap, $oraCodMat, $oraCarrera, $oraGrupo);
            if ($encontrado === null) {
                continue;
            }

            if ($encontrado['EST_NOTA1'] === 'V' && floatval($encontrado['NOTA1']) > 0) {
                $output['NOTA1'][$i]    = $this->_normalizar_nota(floatval($encontrado['NOTA1']));
                $output['EST_NOTA1'][$i] = 'V';
            }
            if ($encontrado['EST_NOTA2'] === 'V' && floatval($encontrado['NOTA2']) > 0) {
                $output['NOTA2'][$i]    = $this->_normalizar_nota(floatval($encontrado['NOTA2']));
                $output['EST_NOTA2'][$i] = 'V';
            }
            if ($encontrado['EST_NOTA3'] === 'V' && floatval($encontrado['NOTA3']) > 0) {
                $output['NOTA3'][$i]    = $this->_normalizar_nota(floatval($encontrado['NOTA3']));
                $output['EST_NOTA3'][$i] = 'V';
            }
            if ($encontrado['EST_EXA'] === 'V' && floatval($encontrado['EXA']) > 0) {
                $output['EXA'][$i]    = $this->_normalizar_nota(floatval($encontrado['EXA']));
                $output['EST_EXA'][$i] = 'V';
            }

            if (!isset($output['RUBRICA'][$i]) || !$output['RUBRICA'][$i]) {
                $output['RUBRICA'][$i] = 1;
            }
        }
    }

    public function materiasMatriculadasPensum($codigo) {
        $nrows  = 0;
        $output = array();

        $sql = "SELECT * FROM matricula.materiasMatriculadasPensum
                WHERE COD_ALUMNO=SUBSTR('{$codigo}','4','4')
                  AND COD_CARRERA=SUBSTR('{$codigo}','1','3')";

        if (isset($this->database2)) {
            $this->database2->get_sql($sql, $nrows, $output);
        }

        $candsEst = $this->_build_cands_est($codigo);

        $resMoodle   = $this->_add_moodle_cands($candsEst, $codigo);
        $cursosMoodle = array();
        if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['cursos']) && is_array($resMoodle['datos']['cursos'])) {
            $cursosMoodle = $resMoodle['datos']['cursos'];
        }
        if (!empty($resMoodle['exito']) && isset($resMoodle['datos']['estudiante']) && isset($this->session)) {
            $e = $resMoodle['datos']['estudiante'];
            $sessEst = $this->session->userdata('estudiante_divisist');
            if ($sessEst) {
                if (!empty($e['nombres']))   { $sessEst->NOMBRES   = $e['nombres']; }
                if (!empty($e['apellidos'])) { $sessEst->APELLIDOS = $e['apellidos']; }
                $this->session->set_userdata('estudiante_divisist', $sessEst);
            }
        }

        $subnotasMap = $this->_subnotas_map($candsEst);

        if (!empty($output) && isset($output['CODCARMAT']) && !empty($output['CODCARMAT'])) {
            $this->_overlay_subnotas_en_oracle($output, $subnotasMap);
            return $output;
        }

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
                'CLASES'       => array(),
            );

            $semestreNum = 1;
            foreach ($cursosMoodle as $c) {
                $fullname  = trim($c['fullname']);
                $shortname = trim($c['shortname']);

                $codCarrera = strlen($codigo) >= 3 ? substr($codigo, 0, 3) : '115';

                $grupo = 'A';
                if (preg_match('/\bB\b/i', $fullname) || preg_match('/[_-]?B$/i', $shortname)) {
                    $grupo = 'B';
                } elseif (preg_match('/\bC\b/i', $fullname) || preg_match('/[_-]?C$/i', $shortname)) {
                    $grupo = 'C';
                }

                $shortnameBase = $shortname;
                if (strpos($shortname, '-') !== false) {
                    $snPartes      = explode('-', $shortname, 2);
                    $shortnameBase = trim($snPartes[0]);
                }

                $snCarrera  = $codCarrera;
                $codMatPuro = $shortnameBase;
                if (preg_match('/^\d{3}(.+)$/', $shortnameBase, $snM) && strlen($shortnameBase) > 3) {
                    $snCarrera  = substr($shortnameBase, 0, 3);
                    $codMatPuro = $snM[1];
                }

                $encontrado = $this->_buscar_subnotas($subnotasMap, $codMatPuro, $snCarrera, $grupo);
                if ($encontrado === null) {
                    $encontrado = $this->_buscar_subnotas($subnotasMap, $shortnameBase, $snCarrera, $grupo);
                }
                if ($encontrado === null) {
                    $encontrado = $this->_buscar_subnotas($subnotasMap, (string)$c['id'], '', $grupo);
                }

                $nota1 = 0.0; $estNota1 = 'S';
                $nota2 = 0.0; $estNota2 = 'S';
                $nota3 = 0.0; $estNota3 = 'S';
                $exa   = 0.0; $estExa   = 'S';
                $def   = 0.0;

                if ($encontrado !== null && !empty($encontrado['TIENE_RUBRICA'])) {
                    if ($encontrado['EST_NOTA1'] === 'V') { $nota1 = $this->_normalizar_nota(floatval($encontrado['NOTA1'])); $estNota1 = 'V'; }
                    if ($encontrado['EST_NOTA2'] === 'V') { $nota2 = $this->_normalizar_nota(floatval($encontrado['NOTA2'])); $estNota2 = 'V'; }
                    if ($encontrado['EST_NOTA3'] === 'V') { $nota3 = $this->_normalizar_nota(floatval($encontrado['NOTA3'])); $estNota3 = 'V'; }
                    if ($encontrado['EST_EXA']   === 'V') { $exa   = $this->_normalizar_nota(floatval($encontrado['EXA']));   $estExa   = 'V'; }

                    $acumulado = 0.0; $pesoEvaluado = 0.0;
                    if ($estNota1 === 'V') { $acumulado += $nota1 * 0.233; $pesoEvaluado += 23.3; }
                    if ($estNota2 === 'V') { $acumulado += $nota2 * 0.233; $pesoEvaluado += 23.3; }
                    if ($estNota3 === 'V') { $acumulado += $nota3 * 0.234; $pesoEvaluado += 23.4; }
                    if ($estExa   === 'V') { $acumulado += $exa   * 0.300; $pesoEvaluado += 30.0; }
                    $def = ($pesoEvaluado > 0) ? round($acumulado, 1) : 0.0;
                } else {
                    if (!empty($c['tiene_nota1'])) { $nota1 = $this->_normalizar_nota(floatval($c['nota1'])); $estNota1 = 'V'; }
                    if (!empty($c['tiene_nota2'])) { $nota2 = $this->_normalizar_nota(floatval($c['nota2'])); $estNota2 = 'V'; }
                    if (!empty($c['tiene_nota3'])) { $nota3 = $this->_normalizar_nota(floatval($c['nota3'])); $estNota3 = 'V'; }
                    if (!empty($c['tiene_exa']))   { $exa   = $this->_normalizar_nota(floatval($c['exa']));   $estExa   = 'V'; }

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

                $output['CODCARMAT'][]    = $snCarrera;
                $output['CODMAT'][]       = $codMatPuro;
                $output['CODMATMAT'][]    = $codMatPuro;
                $output['NOMBREMAT'][]    = strtoupper($fullname);
                $output['GRUPO'][]        = $grupo;
                $output['CREDITOS'][]     = 3;
                $output['SEMESTRE'][]     = $semestreNum;
                $output['DEF'][]          = $def;
                $output['ID_MICRO'][]     = (int)$c['id'];
                $output['CONTENIDO'][]    = $contenido;
                $output['BIBLIOGRAFIA'][] = 'Recursos y material bibliografico disponibles en Moodle.';
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
                    'URL_REUNION' => array('http://localhost:10000/course/view.php?id=' . $c['id']),
                );
            }
            return $output;
        }

        if (!empty($subnotasMap)) {
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
                'CLASES'       => array(),
            );

            $seen = array();
            foreach ($subnotasMap as $m) {
                $codMat = $m['COD_MATERIA'];
                $grp    = $m['GRUPO'];
                $key    = strtolower($codMat) . '|' . strtolower($grp);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $codCarrera = strlen($codigo) >= 3 ? substr($codigo, 0, 3) : '115';
                $codMatLow  = strtolower(trim($codMat));
                $codMatBase = $codMatLow;
                $snCarrera  = $codCarrera;
                if (preg_match('/^\d{3}(.+)$/', $codMatLow, $mm) && strlen($codMatLow) > 3) {
                    $snCarrera  = substr($codMatLow, 0, 3);
                    $codMatBase = $mm[1];
                }

                $def = 0.0; $cnt = 0;
                if ($m['EST_NOTA1'] === 'V') { $def += floatval($m['NOTA1']) * 0.233; $cnt++; }
                if ($m['EST_NOTA2'] === 'V') { $def += floatval($m['NOTA2']) * 0.233; $cnt++; }
                if ($m['EST_NOTA3'] === 'V') { $def += floatval($m['NOTA3']) * 0.234; $cnt++; }
                if ($m['EST_EXA']   === 'V') { $def += floatval($m['EXA'])   * 0.300; $cnt++; }
                if ($cnt > 0) {
                    $def = round($def, 1);
                }

                $output['CODCARMAT'][]    = $snCarrera;
                $output['CODMAT'][]       = strtoupper($codMatBase);
                $output['CODMATMAT'][]    = strtoupper($codMatBase);
                $output['NOMBREMAT'][]    = 'MATERIA ' . strtoupper($codMatBase);
                $output['GRUPO'][]        = $grp;
                $output['CREDITOS'][]     = 3;
                $output['SEMESTRE'][]     = 6;
                $output['DEF'][]          = $def;
                $output['ID_MICRO'][]     = 101;
                $output['CONTENIDO'][]    = 'Contenido disponible en la plataforma.';
                $output['BIBLIOGRAFIA'][] = 'Material disponible en la plataforma.';
                $output['EMAIL'][]        = 'profesor@ufps.edu.co';
                $output['SECCIONAL'][]    = '01';
                $output['RUBRICA'][]      = 1;
                $output['NOTA1'][]        = $m['NOTA1'];
                $output['EST_NOTA1'][]    = $m['EST_NOTA1'];
                $output['NOTA2'][]        = $m['NOTA2'];
                $output['EST_NOTA2'][]    = $m['EST_NOTA2'];
                $output['NOTA3'][]        = $m['NOTA3'];
                $output['EST_NOTA3'][]    = $m['EST_NOTA3'];
                $output['EXA'][]          = $m['EXA'];
                $output['EST_EXA'][]      = $m['EST_EXA'];
                $output['HAB'][]          = 0.0;
                $output['EST_HAB'][]      = 'S';
                $output['OPC'][]          = 0.0;
                $output['EST_OPC'][]      = 'S';
                $output['CLASES'][]       = array(
                    'ID'          => array(1),
                    'FECHA_INI'   => array('2026-02-15 08:00'),
                    'URL_REUNION' => array('https://meet.google.com/'),
                );
            }
            return $output;
        }

        return $output;
    }

    public function codigo_proyecto($cod_carrera, $cod_alumno) {
        $obj = null;
        $sql = "SELECT p.cod_materia codigo_proyecto "
             . "FROM sia.alumno a, sia.pensum p "
             . "WHERE a.cod_carrera = p.cod_carrera "
             . "AND a.pensum = p.tipo_pensum "
             . "AND a.cod_carrera = '{$cod_carrera}' "
             . "AND a.cod_alumno = '{$cod_alumno}'";

        if (isset($this->database2)) {
            $this->database2->get_sql_object($sql, $obj);
        }
        return $obj;
    }
}
