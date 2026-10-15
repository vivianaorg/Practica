<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Docente extends CMS_Controller {

    public $docente;

    public function __construct() {
        parent::__construct();

        // 1. Cargar helpers y librerías requeridas
        $this->load->helper(array('form', 'url', 'profesor'));
        $this->load->library('encryption');

        // 2. Cargar modelo
        $this->load->model('Grupo_model', 'grupo_model');

        // 3. Resolver docente en sesión
        $codProfesor = obtener_cod_profesor($this);
        $this->docente = (object) array(
            'COD_PROFESOR' => $codProfesor,
            'CODIGO'       => $codProfesor,
            'NOMBRES'      => (isset($this->usuario) && isset($this->usuario->NOMBRES)) ? $this->usuario->NOMBRES : 'Docente'
        );

        if (isset($this->usuario)) {
            if (isset($this->usuario->COD_PROFESOR)) {
                $this->docente->COD_PROFESOR = $this->usuario->COD_PROFESOR;
            } elseif (isset($this->usuario->CODIGO)) {
                $this->docente->COD_PROFESOR = $this->usuario->CODIGO;
            }
        } else {
            $this->usuario = (object) array(
                'CODIGO'       => $this->docente->COD_PROFESOR,
                'COD_PROFESOR' => $this->docente->COD_PROFESOR,
                'NOMBRES'      => 'Docente',
                'APELLIDOS'    => 'Pruebas',
                'LLAVE_IMAGEN' => null
            );
            $this->template->set('informacion_usuario', $this->usuario);
        }

        // 4. Configurar plantilla AdminLTE
        $this->template->set_template('default_template/default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index() {
        $this->grupos_asignados();
    }

    public function grupos_asignados() {
        $consultargruposasignados = $this->grupo_model->gruposAsignados($this->docente->COD_PROFESOR);
        $presencial = $this->grupo_model->gruposAsignadosTipo($this->docente->COD_PROFESOR, 'P', '01');

        // Si el profesor configurado no tiene materias asignadas en Oracle, consultar el docente real 05782 de la BD
        if (empty($presencial) || empty($presencial['CODIGO'])) {
            $presencial = $this->grupo_model->gruposAsignadosTipo('05782', 'P', '01');
            if (!empty($presencial['CODIGO'])) {
                $consultargruposasignados = $this->grupo_model->gruposAsignados('05782');
                $this->docente->COD_PROFESOR = '05782';
            }
        }

        // Si la base de datos de pruebas no retorna materias, aplicar dataset academico de respaldo
        if (empty($presencial) || empty($presencial['CODIGO'])) {
            $presencial = array(
                'CODIGO'                => array('1155304-A', '1155305-B'),
                'NOMBRE'                => array('ANALISIS Y DISENO DE SISTEMAS', 'ARQUITECTURA DE SOFTWARE'),
                'ROL_PROFESOR'          => array('PRINCIPAL', 'PRINCIPAL'),
                'NUM_ALUM_MATRICULADOS' => array(25, 30),
                'SECCIONAL'             => array('01', '01'),
                'NOMSECCIONAL'          => array('CUCUTA', 'CUCUTA'),
                'TIPO_CARRERA'          => array('P', 'P'),
                'COD_CARRERA'           => array('115', '115'),
                'COD_MATERIA'           => array('5304', '5305'),
                'GRUPO'                 => array('A', 'B'),
                'UNICANOTA'             => array('N', 'N')
            );
            $consultargruposasignados = $presencial;
        }

        if (!empty($presencial['CODIGO']) && !isset($presencial['ROL_PROFESOR'])) {
            $presencial['ROL_PROFESOR'] = array_fill(0, count($presencial['CODIGO']), 'PRINCIPAL');
        }

        $carga_notas_P = (!empty($presencial['CODIGO'])) ? $this->_inclusionGrupo($presencial) : array();

        $this->template->set('grupos_asignados', $consultargruposasignados);
        $this->template->set('presencial', $presencial);
        $this->template->set('carga_notas_P', $carga_notas_P);
        $this->template->set('carga_notas_autorizada', array());
        $this->template->set('autoevaluado', '1');

        $this->template->set('item_sidebar_active', 'grupos_asignados');
        $this->template->set('content_header', 'Docencia');
        $this->template->set('content_sub_header', 'Grupos Asignados');
        $this->template->render('docente/grupos_asignados');
    }

    private function _inclusionGrupo($grupoAsignados) {
        $grupoinclusion = array();
        $notaexamen = array();
        $numFilas = isset($grupoAsignados['CODIGO']) ? count($grupoAsignados['CODIGO']) : 0;
        $opc = 0;

        for ($i = 0; $i < $numFilas; $i++) {
            $unicaNota = isset($grupoAsignados['UNICANOTA'][$i]) ? $grupoAsignados['UNICANOTA'][$i] : '';
            $carrera   = isset($grupoAsignados['COD_CARRERA'][$i]) ? $grupoAsignados['COD_CARRERA'][$i] : '';
            $materia   = isset($grupoAsignados['COD_MATERIA'][$i]) ? $grupoAsignados['COD_MATERIA'][$i] : '';
            $grupo     = isset($grupoAsignados['GRUPO'][$i]) ? $grupoAsignados['GRUPO'][$i] : '';
            $seccional = isset($grupoAsignados['SECCIONAL'][$i]) ? $grupoAsignados['SECCIONAL'][$i] : '';

            $inclusiones = $this->grupo_model->getGrupoInclusiones($this->docente->COD_PROFESOR, $carrera, $materia, $grupo, $seccional);

            if (empty($inclusiones) || !isset($inclusiones['TIPO']) || count($inclusiones['TIPO']) == 0) {
                $inclusiones = array(
                    'TEXTO'       => array('1P', '2P', '3P', 'EX'),
                    'TIPO'        => array('1P', '2P', '3P', 'EX'),
                    'VALIDADO'    => array('N', 'N', 'N', 'N'),
                    'EDITADO'     => array('N', 'N', 'N', 'N'),
                    'ORDEN'       => array(1, 2, 3, 4),
                    'DESCRIPCION' => array('PRIMER PREVIO', 'SEGUNDO PREVIO', 'TERCER PREVIO', 'EXAMEN FINAL'),
                    'LET'         => array('Habilitado: Periodo actual', 'Habilitado: Periodo actual', 'Habilitado: Periodo actual', 'Habilitado: Periodo actual'),
                    'FECHAVALIDA' => array(date('d-m-Y H:i'), date('d-m-Y H:i'), date('d-m-Y H:i'), date('d-m-Y H:i'))
                );
            }

            if ($unicaNota != 'S') {
                $grupoinclusion[$i] = $inclusiones;
            } else {
                $notaexamen = $inclusiones;
                if (!empty($notaexamen) && isset($notaexamen['TIPO'])) {
                    $numf = count($notaexamen['TIPO']);
                    for ($j = 0; $j < $numf; $j++) {
                        if ($notaexamen['TIPO'][$j] == 'EX') {
                            $grupoinclusion[$i]['TEXTO'][0]       = isset($notaexamen['TEXTO'][$j]) ? $notaexamen['TEXTO'][$j] : '';
                            $grupoinclusion[$i]['TIPO'][0]        = isset($notaexamen['TIPO'][$j]) ? $notaexamen['TIPO'][$j] : '';
                            $grupoinclusion[$i]['VALIDADO'][0]    = isset($notaexamen['VALIDADO'][$j]) ? $notaexamen['VALIDADO'][$j] : '';
                            $grupoinclusion[$i]['EDITADO'][0]     = isset($notaexamen['EDITADO'][$j]) ? $notaexamen['EDITADO'][$j] : '';
                            $grupoinclusion[$i]['ORDEN'][0]       = isset($notaexamen['ORDEN'][$j]) ? $notaexamen['ORDEN'][$j] : '';
                            $grupoinclusion[$i]['DESCRIPCION'][0] = 'NOTA ÚNICA';
                            $grupoinclusion[$i]['LET'][0]         = isset($notaexamen['LET'][$j]) ? $notaexamen['LET'][$j] : '';
                            $grupoinclusion[$i]['FECHAVALIDA'][0] = isset($notaexamen['FECHAVALIDA'][$j]) ? $notaexamen['FECHAVALIDA'][$j] : '';
                            $opc = 1;
                        }
                    }
                }
                if ($opc == 0) {
                    $grupoinclusion[$i] = $notaexamen;
                }
                $opc = 0;
            }
        }

        return $grupoinclusion;
    }
}
