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

            if ($unicaNota != 'S') {
                $grupoinclusion[$i] = $this->grupo_model->getGrupoInclusiones($this->docente->COD_PROFESOR, $carrera, $materia, $grupo, $seccional);
            } else {
                $notaexamen = $this->grupo_model->getGrupoInclusiones($this->docente->COD_PROFESOR, $carrera, $materia, $grupo, $seccional);
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
