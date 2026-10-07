<div class="row">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#tab_resumen" data-toggle="tab">
                        <i class="fa fa-eye"></i> Resumen de Rubricas del Curso
                    </a>
                </li>
                <li>
                    <a href="#tab_configurar" data-toggle="tab">
                        <i class="fa fa-sliders"></i> Configurar / Editar Rubrica
                    </a>
                </li>
                <li class="pull-right" style="padding: 6px 10px;">
                    <?php $this->load->view('dashboard/nav_curso_botones', array(
                        'course_id'   => $course_id,
                        'activo'      => 'rubrica',
                        'tipo_previo' => isset($tipo_previo) ? $tipo_previo : ''
                    )); ?>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="tab_resumen">
                    <h4>
                        <i class="fa fa-book text-primary"></i> <?php echo htmlspecialchars(isset($curso_nombre) ? $curso_nombre : 'Curso'); ?>
                        <?php $etiquetaCurso = $cod_materia . (!empty($grupo) && $grupo !== '-' ? '-' . $grupo : '') . (!empty($semestre) ? ' (' . $semestre . ')' : ''); ?>
                        <small class="text-muted"><?php echo htmlspecialchars($etiquetaCurso); ?></small>
                    </h4>

                    <div class="row" style="margin-top: 15px; display: flex; flex-wrap: wrap;">
                        <?php
                        $cortesNombres = array(
                            '1'     => 'Primer Previo',
                            '2'     => 'Segundo Previo',
                            '3'     => 'Tercer Previo',
                            'FINAL' => 'Examen Final'
                        );

                        foreach ($cortesNombres as $claveCorte => $nombreCorte):
                            $itemsCorte = isset($todas_rubricas[$claveCorte]) ? $todas_rubricas[$claveCorte] : array();
                            $totalPorcentajeCorte = 0;
                            foreach ($itemsCorte as $it) {
                                $totalPorcentajeCorte += (float)$it->PORCENTAJE;
                            }
                            $estaCompleto = (abs($totalPorcentajeCorte - 100) < 0.01);
                            $tieneItems = !empty($itemsCorte);
                            $corteEstaCalificado = isset($cortes_calificados[$claveCorte]) && $cortes_calificados[$claveCorte];

                            $boxClase = 'box-default';
                            $boxStyle = '';
                            $badgeClase = 'bg-gray';
                            if ($corteEstaCalificado) {
                                $boxClase = 'box-default';
                                $boxStyle = 'border: 1px solid #d2d6de; background-color: #f8f9fa; opacity: 0.88;';
                                $badgeClase = 'bg-gray';
                            } elseif ($estaCompleto) {
                                $boxClase = 'box-success';
                                $badgeClase = 'bg-green';
                            } elseif ($tieneItems) {
                                $boxClase = 'box-warning';
                                $badgeClase = 'bg-yellow';
                            }
                        ?>
                            <div class="col-md-6 col-sm-12" style="display: flex; margin-bottom: 20px;">
                                <div class="box box-solid <?php echo $boxClase; ?>" style="width: 100%; display: flex; flex-direction: column; margin-bottom: 0; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); <?php echo $boxStyle; ?>">
                                    <div class="box-header with-border" style="<?php echo $corteEstaCalificado ? 'background-color: #ededed; color: #555;' : ''; ?>">
                                        <h3 class="box-title" style="font-weight: 600;">
                                            <i class="fa <?php echo $corteEstaCalificado ? 'fa-lock text-muted' : 'fa-calendar-check-o'; ?>" style="margin-right: 5px;"></i> 
                                            <?php echo htmlspecialchars($nombreCorte); ?>
                                            <?php if ($corteEstaCalificado): ?>
                                                <small style="color: #777; font-weight: 500;">(Calificado)</small>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                    <div class="box-body no-padding" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                        <?php if (empty($itemsCorte)): ?>
                                            <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 35px 20px;" class="text-muted">
                                                <i class="fa fa-folder-open-o fa-2x" style="opacity: 0.6; margin-bottom: 8px;"></i>
                                                <p style="margin-bottom: 12px;">No se han configurado actividades para este corte.</p>
                                                <?php if (!$corteEstaCalificado): ?>
                                                    <button type="button" class="btn btn-default btn-sm" style="box-shadow: 0 1px 2px rgba(0,0,0,0.08);" onclick="irAConfigurarCorte('<?php echo $claveCorte; ?>')">
                                                        <i class="fa fa-plus"></i> Configurar ahora
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="flex: 1;">
                                                <table class="table table-striped table-condensed" style="margin-bottom: 0;">
                                                    <thead>
                                                        <tr>
                                                            <th>Actividad</th>
                                                            <th style="width: 100px; text-align: right;">Peso</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($itemsCorte as $it): ?>
                                                            <?php $esItemManual = ((int)$it->ID_ACTIVIDAD_MOODLE === 0 || strtolower($it->TIPO_ACTIVIDAD) === 'manual'); ?>
                                                            <tr>
                                                                <td style="vertical-align: middle;">
                                                                    <i class="fa <?php echo $esItemManual ? 'fa-pencil text-yellow' : 'fa-check-square-o text-green'; ?>"></i> 
                                                                    <?php echo htmlspecialchars($it->NOMBRE_ACTIVIDAD); ?>
                                                                </td>
                                                                <td style="text-align: right; font-weight: bold; vertical-align: middle;">
                                                                    <?php echo htmlspecialchars($it->PORCENTAJE); ?>%
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="bg-gray-light">
                                                            <th style="text-align: right;">Total Asignado:</th>
                                                            <th style="text-align: right; font-weight: bold; color: <?php echo $corteEstaCalificado ? '#777' : ($estaCompleto ? 'green' : 'orange'); ?>;">
                                                                <?php echo $totalPorcentajeCorte; ?>%
                                                            </th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <div style="padding: 10px 12px; background: <?php echo $corteEstaCalificado ? '#f0f0f0' : '#fafafa'; ?>; border-top: 1px solid <?php echo $corteEstaCalificado ? '#e4e4e4' : '#f0f0f0'; ?>; display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <?php if ($corteEstaCalificado): ?>
                                                        <button type="button" class="btn btn-danger btn-xs" onclick="confirmarEliminarRubrica('<?php echo $claveCorte; ?>', '<?php echo htmlspecialchars($nombreCorte); ?>')">
                                                            <i class="fa fa-trash"></i> Eliminar
                                                        </button>
                                                    <?php elseif (!empty($rubricasAgrupadas[$claveCorte])): ?>
                                                        <button type="button" class="btn btn-danger btn-xs" onclick="confirmarEliminarRubrica('<?php echo $claveCorte; ?>', '<?php echo htmlspecialchars($nombreCorte); ?>')">
                                                            <i class="fa fa-trash"></i> Eliminar
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 5px;">
                                                    <?php if ($corteEstaCalificado): ?>
                                                        <span class="label label-default" style="font-size: 11px; padding: 4px 7px;">
                                                            <i class="fa fa-lock"></i> Inhabilitada
                                                        </span>
                                                        <button type="button" class="btn btn-primary btn-xs" onclick="confirmarDesbloquearCorte('<?php echo $claveCorte; ?>', '<?php echo htmlspecialchars($nombreCorte); ?>')">
                                                            <i class="fa fa-unlock"></i> Desbloquear
                                                        </button>
                                                        <a href="<?php echo site_url('calificaciones/calificar_rubrica/' . $course_id . '/' . $claveCorte); ?>" class="btn btn-default btn-xs">
                                                            <i class="fa fa-eye"></i> Ver Calificaciones
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-primary btn-xs" onclick="irAConfigurarCorte('<?php echo $claveCorte; ?>')">
                                                            <i class="fa fa-pencil"></i> Modificar Rubrica
                                                        </button>
                                                        <?php if ($estaCompleto): ?>
                                                            <a href="<?php echo site_url('calificaciones/calificar_rubrica/' . $course_id . '/' . $claveCorte); ?>" class="btn btn-success btn-xs">
                                                                <i class="fa fa-calculator"></i> Calificar Corte
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tab-pane" id="tab_configurar">

                    <?php if (!$exito): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i> No fue posible sincronizar con Moodle: <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>. Sin embargo, puede configurar actividades manuales.
                        </div>
                    <?php endif; ?>

                    <?php if ($corte_calificado): ?>
                        <div class="alert alert-warning" style="margin-bottom: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div class="row" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                                <div class="col-md-8 col-sm-7" style="margin-bottom: 5px;">
                                    <i class="fa fa-lock" style="font-size: 16px; margin-right: 5px;"></i> <strong>Rúbrica Inhabilitada:</strong> Este corte ya fue calificado y cerrado. Para modificar o eliminar esta rúbrica, primero debes desbloquear el corte.
                                </div>
                                <div class="col-md-4 col-sm-5 text-right" style="margin-bottom: 5px;">
                                    <button type="button" class="btn btn-primary btn-sm" style="font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.2);" onclick="confirmarDesbloquearCorte('<?php echo $tipo_previo; ?>', '<?php echo htmlspecialchars($nombreCorteActual); ?>')">
                                        <i class="fa fa-unlock"></i> Desbloquear Corte
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" style="margin-left: 6px; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.2);" onclick="confirmarEliminarRubrica('<?php echo $tipo_previo; ?>', '<?php echo htmlspecialchars($nombreCorteActual); ?>')">
                                        <i class="fa fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group" style="margin-bottom: 5px;">
                                <label><i class="fa fa-calendar-check-o text-primary"></i> Corte a Editar / Configurar:</label>
                                <select id="select_corte" class="form-control">
                                    <option value="1" <?php echo ($tipo_previo == '1') ? 'selected' : ''; ?>>Primer Previo</option>
                                    <option value="2" <?php echo ($tipo_previo == '2') ? 'selected' : ''; ?>>Segundo Previo</option>
                                    <option value="3" <?php echo ($tipo_previo == '3') ? 'selected' : ''; ?>>Tercer Previo</option>
                                    <option value="FINAL" <?php echo ($tipo_previo == 'FINAL') ? 'selected' : ''; ?>>Examen Final</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="mensaje_ajax" style="display: none; margin-top: 15px;"></div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabla_rubrica">
                            <thead>
                                <tr class="bg-gray-light">
                                    <th style="width: 50px; text-align: center;">#</th>
                                    <th>Actividad</th>
                                    <th style="width: 140px; text-align: center;">Tipo</th>
                                    <th style="width: 160px; text-align: center;">Porcentaje (%)</th>
                                    <th style="width: 60px; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody_rubrica">
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-light">
                                    <th colspan="3" style="text-align: right; vertical-align: middle;">Total Ponderado:</th>
                                    <th style="text-align: right; vertical-align: middle; font-size: 14px; font-weight: bold;" id="tfoot_total_porcentaje">0%</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div style="margin-top: 20px; padding: 14px 18px; background-color: #fcfcfc; border: 1px solid #e3e7eb; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <button type="button" class="btn btn-primary" id="btn_agregar_fila" <?php echo ($corte_calificado ? 'disabled' : ''); ?> style="font-weight: 600; box-shadow: 0 1px 2px #caf0f8;">
                                    <i class="fa fa-plus-circle"></i> Agregar Actividad de Moodle
                                </button>
                                <button type="button" class="btn btn-info" id="btn_agregar_manual" <?php echo ($corte_calificado ? 'disabled' : ''); ?> style="font-weight: 600; box-shadow: 0 1px 2px #c0d6df;">
                                    <i class="fa fa-pencil-square-o"></i> Agregar Actividad Manual
                                </button>
                                <button type="button" class="btn btn-default" id="btn_abrir_modal_reutilizar" <?php echo ($corte_calificado ? 'disabled' : ''); ?> style="font-weight: 600; background-color: #f4f6f8; color: #3b5998; border: 1px solid #d0d7de; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                                    <i class="fa fa-clone"></i> Reutilizar Rúbrica
                                </button>
                            </div>

                            <!-- Acciones generales de la rúbrica -->
                            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <?php if (!$corte_calificado && !empty($rubrica_actual)): ?>
                                    <button type="button" class="btn btn-danger" id="btn_eliminar_rubrica_actual" style="font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                                        <i class="fa fa-trash"></i> Eliminar Rúbrica
                                    </button>
                                <?php endif; ?>
                                <?php if ($corte_calificado): ?>
                                    <button type="button" class="btn btn-default" id="btn_guardar_rubrica" disabled style="font-weight: 600;">
                                        <i class="fa fa-lock"></i> Rúbrica Bloqueada
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-success" id="btn_guardar_rubrica" disabled style="font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                                        <i class="fa fa-save"></i> Guardar Rúbrica
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_reutilizar_rubrica" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 520px; margin-top: 8%;">
        <div class="modal-content" style="border-radius: 6px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); border: none; overflow: hidden;">
            <div class="modal-header" style="padding: 12px 18px; color: #334e68; background-color: #f0f4f8; border-bottom: 1px solid #d9e2ec;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #627d98; opacity: 0.85;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" style="font-size: 15px; font-weight: 600; color: #243b53;">
                    <i class="fa fa-clone text-muted" style="margin-right: 6px;"></i> Reutilizar Rúbrica de otro Curso o Corte
                </h4>
            </div>
            <div class="modal-body" style="padding: 20px; font-size: 13px; color: #334e68; line-height: 1.5;">
                <p class="text-muted" style="margin-bottom: 15px;">
                    Seleccione un curso y corte previamente configurado para importar sus actividades y porcentajes a este corte.
                </p>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="select_curso_origen" style="font-weight: 600; font-size: 12px; color: #486581;">
                        <i class="fa fa-book text-muted"></i> Curso Origen:
                    </label>
                    <select id="select_curso_origen" class="form-control" style="border-radius: 4px;" <?php echo ($corte_calificado ? 'disabled' : ''); ?>>
                        <option value="">-- Cargando cursos con rúbricas... --</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="select_corte_origen" style="font-weight: 600; font-size: 12px; color: #486581;">
                        <i class="fa fa-calendar-check-o text-muted"></i> Corte Evaluativo Origen:
                    </label>
                    <select id="select_corte_origen" class="form-control" style="border-radius: 4px;" disabled>
                        <option value="">-- Seleccione curso primero --</option>
                    </select>
                </div>
                <div id="alerta_importacion_feedback" style="display: none; margin-top: 10px; margin-bottom: 0;" class="alert"></div>
            </div>
            <div class="modal-footer" style="padding: 12px 18px; background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_aplicar_rubrica_importada" disabled style="font-weight: 600; padding: 6px 14px;">
                    <i class="fa fa-arrow-circle-down"></i> Aplicar a este corte
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_app_dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 480px; margin-top: 10%;">
        <div class="modal-content" style="border-radius: 6px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); border: none; overflow: hidden;">
            <div class="modal-header" id="modal_app_dialog_header" style="padding: 12px 16px; color: #fff; background-color: #3c8dbc;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.85;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal_app_dialog_title" style="font-size: 16px; font-weight: 600;">
                    <i id="modal_app_dialog_icon" class="fa fa-info-circle"></i> <span id="modal_app_dialog_title_txt">Mensaje</span>
                </h4>
            </div>
            <div class="modal-body" id="modal_app_dialog_body" style="padding: 20px; font-size: 14px; color: #333; line-height: 1.5;">
            </div>
            <div class="modal-footer" id="modal_app_dialog_footer" style="padding: 10px 16px; background-color: #f9f9f9; border-top: 1px solid #eee;">
                <button type="button" class="btn btn-default btn-sm" id="modal_app_btn_cancelar" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="modal_app_btn_confirmar" data-dismiss="modal">
                    <i class="fa fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuración inyectada desde PHP para el módulo JS externo
    window.RUBRICA_CONFIG = {
        actividadesDisponibles: <?php echo json_encode($actividades); ?>,
        rubricaGuardada: <?php echo json_encode($rubrica_actual); ?>,
        courseId: <?php echo (int)$course_id; ?>,
        codProfesor: "<?php echo htmlspecialchars($cod_profesor); ?>",
        codMateria: "<?php echo htmlspecialchars($cod_materia); ?>",
        grupo: "<?php echo htmlspecialchars($grupo); ?>",
        semestre: "<?php echo htmlspecialchars($semestre); ?>",
        corteCalificado: <?php echo ($corte_calificado ? 'true' : 'false'); ?>,
        urls: {
            listarCursos: "<?php echo site_url('calificaciones/listar_cursos_rubricas_ajax'); ?>",
            obtenerItems: "<?php echo site_url('calificaciones/obtener_items_rubrica_ajax'); ?>",
            guardar: "<?php echo site_url('calificaciones/guardar_rubrica_ajax'); ?>",
            eliminar: "<?php echo site_url('calificaciones/eliminar_rubrica_ajax'); ?>",
            desbloquear: "<?php echo site_url('calificaciones/desbloquear_corte_ajax'); ?>",
            irAConfigurar: "<?php echo site_url('calificaciones/configurar_rubrica/' . $course_id); ?>"
        }
    };
</script>
<script src="<?php echo base_url('assets/js/configurar_rubrica.js'); ?>"></script>

