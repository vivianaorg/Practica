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
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <a href="<?php echo site_url('dashboard/cursos_moodle'); ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: 500; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                            <i class="fa fa-arrow-left text-muted"></i> Volver a cursos
                        </a>
                        <a href="<?php echo site_url('dashboard/actividades_moodle/' . $course_id); ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: 500; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                            <i class="fa fa-list text-primary"></i> Actividades
                        </a>
                        <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $course_id . '/' . $tipo_previo); ?>" class="btn btn-warning btn-sm" style="border-radius: 4px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                            <i class="fa fa-calculator"></i> Calificar Corte
                        </a>
                        <a href="<?php echo site_url('dashboard/calificaciones/' . $course_id); ?>" class="btn btn-success btn-sm" style="border-radius: 4px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                            <i class="fa fa-graduation-cap"></i> Calificaciones
                        </a>
                    </div>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="tab_resumen">
                    <h4>
                        <i class="fa fa-book text-primary"></i> <?php echo htmlspecialchars(isset($curso_nombre) ? $curso_nombre : 'Curso'); ?>
                        <?php $etiquetaCurso = $cod_materia . (!empty($grupo) && $grupo !== '-' ? '-' . $grupo : '') . (!empty($semestre) ? ' (' . $semestre . ')' : ''); ?>
                        <small class="text-muted"><?php echo htmlspecialchars($etiquetaCurso); ?></small>
                    </h4>
                    <p class="text-muted">Estado general de las rubricas asociadas a cada corte evaluativo:</p>

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
                                        <div class="box-tools pull-right">
                                            <span class="badge <?php echo $badgeClase; ?>" style="font-size: 11px; padding: 4px 8px;">
                                                <?php echo $totalPorcentajeCorte; ?>% / 100%
                                            </span>
                                        </div>
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
                                                        <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $course_id . '/' . $claveCorte); ?>" class="btn btn-default btn-xs">
                                                            <i class="fa fa-eye"></i> Ver Calificaciones
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-primary btn-xs" onclick="irAConfigurarCorte('<?php echo $claveCorte; ?>')">
                                                            <i class="fa fa-pencil"></i> Modificar Rubrica
                                                        </button>
                                                        <?php if ($estaCompleto): ?>
                                                            <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $course_id . '/' . $claveCorte); ?>" class="btn btn-success btn-xs">
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
                            <div class="form-group">
                                <label>Corte a Editar / Configurar:</label>
                                <select id="select_corte" class="form-control">
                                    <option value="1" <?php echo ($tipo_previo == '1') ? 'selected' : ''; ?>>Primer Previo</option>
                                    <option value="2" <?php echo ($tipo_previo == '2') ? 'selected' : ''; ?>>Segundo Previo</option>
                                    <option value="3" <?php echo ($tipo_previo == '3') ? 'selected' : ''; ?>>Tercer Previo</option>
                                    <option value="FINAL" <?php echo ($tipo_previo == 'FINAL') ? 'selected' : ''; ?>>Examen Final</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label>Ponderacion Actual (Total: <span id="total_porcentaje_txt">0%</span> / 100%):</label>
                            <div class="progress progress-sm active" style="margin-top: 5px;">
                                <div id="barra_progreso" class="progress-bar progress-bar-yellow" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div id="alerta_porcentaje" class="text-sm text-yellow">
                                <i class="fa fa-info-circle"></i> La suma de porcentajes debe ser exactamente 100%.
                            </div>
                        </div>
                    </div>

                    <hr style="margin-top: 5px; margin-bottom: 15px;">

                    <div id="mensaje_ajax" style="display: none;"></div>

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
                        </table>
                    </div>

                    <div style="margin-top: 20px; padding: 14px 18px; background-color: #fcfcfc; border: 1px solid #e3e7eb; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <!-- Acciones para agregar actividades -->
                            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <button type="button" class="btn btn-primary" id="btn_agregar_fila" <?php echo ($corte_calificado ? 'disabled' : ''); ?> style="font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                                    <i class="fa fa-plus-circle"></i> Agregar Actividad de Moodle
                                </button>
                                <button type="button" class="btn btn-info" id="btn_agregar_manual" <?php echo ($corte_calificado ? 'disabled' : ''); ?> style="font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                                    <i class="fa fa-pencil-square-o"></i> Agregar Actividad Manual
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
                <button type="button" class="btn btn-primary btn-sm" id="modal_app_btn_confirmar">
                    <i class="fa fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var actividadesDisponibles = <?php echo json_encode($actividades); ?>;
var rubricaGuardada = <?php echo json_encode($rubrica_actual); ?>;
var courseId = <?php echo (int)$course_id; ?>;
var codProfesor = "<?php echo htmlspecialchars($cod_profesor); ?>";
var codMateria = "<?php echo htmlspecialchars($cod_materia); ?>";
var grupo = "<?php echo htmlspecialchars($grupo); ?>";
var semestre = "<?php echo htmlspecialchars($semestre); ?>";
var corteCalificado = <?php echo ($corte_calificado ? 'true' : 'false'); ?>;

document.addEventListener("DOMContentLoaded", function() {

    var selectCorte = document.getElementById("select_corte");
    if (selectCorte) {
        selectCorte.addEventListener("change", function() {
            var urlBase = "<?php echo site_url('dashboard/configurar_rubrica/' . $course_id); ?>";
            window.location.href = urlBase + "/" + this.value + "#tab_configurar";
        });
    }

    if (window.location.hash === "#tab_configurar") {
        var tabLink = document.querySelector('a[href="#tab_configurar"]');
        if (tabLink) {
            tabLink.click();
        }
    }

    var btnAgregar = document.getElementById("btn_agregar_fila");
    if (btnAgregar && !corteCalificado) {
        btnAgregar.addEventListener("click", function() {
            agregarFilaRubrica(null, 0, false, "");
        });
    }

    var btnAgregarManual = document.getElementById("btn_agregar_manual");
    if (btnAgregarManual && !corteCalificado) {
        btnAgregarManual.addEventListener("click", function() {
            agregarFilaRubrica(0, 0, true, "");
        });
    }

    var btnGuardar = document.getElementById("btn_guardar_rubrica");
    if (btnGuardar && !corteCalificado) {
        btnGuardar.addEventListener("click", guardarRubrica);
    }

    var btnEliminarActual = document.getElementById("btn_eliminar_rubrica_actual");
    if (btnEliminarActual && !corteCalificado) {
        btnEliminarActual.addEventListener("click", function() {
            var corteActual = document.getElementById("select_corte").value;
            var selectEl = document.getElementById("select_corte");
            var nombreTxt = selectEl.options[selectEl.selectedIndex].text;
            confirmarEliminarRubrica(corteActual, nombreTxt);
        });
    }

    if (rubricaGuardada && rubricaGuardada.length > 0) {
        for (var i = 0; i < rubricaGuardada.length; i++) {
            var r = rubricaGuardada[i];
            var esMan = (parseInt(r.ID_ACTIVIDAD_MOODLE, 10) === 0 || (r.TIPO_ACTIVIDAD && String(r.TIPO_ACTIVIDAD).toLowerCase() === "manual"));
            if (esMan) {
                agregarFilaRubrica(0, r.PORCENTAJE, true, r.NOMBRE_ACTIVIDAD);
            } else {
                agregarFilaRubrica(r.ID_ACTIVIDAD_MOODLE, r.PORCENTAJE, false, "");
            }
        }
    } else {
        agregarFilaRubrica(null, 0, false, "");
    }

    actualizarOpcionesDisponibles();
    recalcularTotales();
});

function escapeHtml(text) {
    if (!text) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function irAConfigurarCorte(corte) {
    var selectCorte = document.getElementById("select_corte");
    if (selectCorte && selectCorte.value === String(corte)) {
        var tabLink = document.querySelector('a[href="#tab_configurar"]');
        if (tabLink) tabLink.click();
    } else {
        var urlBase = "<?php echo site_url('dashboard/configurar_rubrica/' . $course_id); ?>";
        window.location.href = urlBase + "/" + corte + "#tab_configurar";
    }
}

function agregarFilaRubrica(actividadSeleccionada, porcentaje, esManual, nombreManual) {
    var tbody = document.getElementById("tbody_rubrica");
    if (!tbody) return;

    var index = tbody.rows.length + 1;
    var tr = document.createElement("tr");

    var disAttr = corteCalificado ? ' disabled' : '';
    esManual = (esManual === true || String(actividadSeleccionada) === "0");

    var actividadHtml = "";
    var tipoBadgeHtml = "";

    if (esManual) {
        tr.setAttribute("data-manual", "1");
        actividadHtml = 
            '<div class="input-group">' +
                '<span class="input-group-addon" style="background-color: #f39c12; color: #fff; border-color: #e08e0b;"><i class="fa fa-pencil"></i></span>' +
                '<input type="text" class="form-control input-nombre-manual" placeholder="Nombre de la actividad manual (ej. Exposición, Taller en clase, Quiz)..." value="' + (nombreManual ? escapeHtml(nombreManual) : "") + '" oninput="recalcularTotales()"' + disAttr + ' required>' +
            '</div>';
        tipoBadgeHtml = '<span class="label label-warning badge-tipo"><i class="fa fa-pencil"></i> manual</span>';
    } else {
        tr.setAttribute("data-manual", "0");
        var selectHtml = '<select class="form-control select-actividad" required onchange="onActividadChange(this)"' + disAttr + '>';
        selectHtml += '<option value="">-- Seleccione una actividad de Moodle --</option>';

        for (var i = 0; i < actividadesDisponibles.length; i++) {
            var act = actividadesDisponibles[i];
            var selected = (actividadSeleccionada && String(act.id) === String(actividadSeleccionada)) ? 'selected' : '';
            selectHtml += '<option value="' + act.id + '" data-tipo="' + act.tipo + '" data-nombre="' + act.nombre.replace(/"/g, '&quot;') + '" ' + selected + '>';
            selectHtml += act.nombre + ' (' + act.tipo + ')';
            selectHtml += '</option>';
        }
        selectHtml += '</select>';
        actividadHtml = selectHtml;

        var tipoActividad = "";
        if (actividadSeleccionada) {
            for (var k = 0; k < actividadesDisponibles.length; k++) {
                if (String(actividadesDisponibles[k].id) === String(actividadSeleccionada)) {
                    tipoActividad = actividadesDisponibles[k].tipo;
                    break;
                }
            }
        }
        tipoBadgeHtml = '<span class="label label-info badge-tipo">' + (tipoActividad || '-') + '</span>';
    }

    tr.innerHTML = 
        '<td style="text-align: center; vertical-align: middle;" class="row-num">' + index + '</td>' +
        '<td>' + actividadHtml + '</td>' +
        '<td style="text-align: center; vertical-align: middle;">' + tipoBadgeHtml + '</td>' +
        '<td>' +
            '<div class="input-group">' +
                '<input type="number" class="form-control input-porcentaje text-right" min="1" max="100" step="0.5" value="' + (porcentaje > 0 ? porcentaje : '') + '" placeholder="0" oninput="recalcularTotales()" required' + disAttr + '>' +
                '<span class="input-group-addon">%</span>' +
            '</div>' +
        '</td>' +
        '<td style="text-align: center; vertical-align: middle;">' +
            '<button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaRubrica(this)"' + disAttr + '>' +
                '<i class="fa fa-trash"></i>' +
            '</button>' +
        '</td>';

    tbody.appendChild(tr);

    actualizarOpcionesDisponibles();
    recalcularTotales();
}

function eliminarFilaRubrica(btn) {
    var tr = btn.closest("tr");
    tr.parentNode.removeChild(tr);
    renumerarFilas();
    actualizarOpcionesDisponibles();
    recalcularTotales();
}

function renumerarFilas() {
    var rows = document.querySelectorAll("#tbody_rubrica tr");
    for (var i = 0; i < rows.length; i++) {
        var numCell = rows[i].querySelector(".row-num");
        if (numCell) numCell.textContent = (i + 1);
    }
}

function onActividadChange(selectElem) {
    var opt = selectElem.options[selectElem.selectedIndex];
    var tipo = opt ? opt.getAttribute("data-tipo") : "";
    var tr = selectElem.closest("tr");
    var badge = tr.querySelector(".badge-tipo");
    if (badge) {
        badge.textContent = tipo || "-";
    }
    actualizarOpcionesDisponibles();
    recalcularTotales();
}

function actualizarOpcionesDisponibles() {
    var selects = document.querySelectorAll(".select-actividad");
    var seleccionados = [];

    for (var i = 0; i < selects.length; i++) {
        var val = selects[i].value;
        if (val) {
            seleccionados.push(val);
        }
    }

    for (var j = 0; j < selects.length; j++) {
        var currentSelect = selects[j];
        var currentVal = currentSelect.value;
        var options = currentSelect.options;

        for (var k = 0; k < options.length; k++) {
            var opt = options[k];
            if (!opt.value) continue;

            if (seleccionados.indexOf(opt.value) !== -1 && opt.value !== currentVal) {
                opt.disabled = true;
            } else {
                opt.disabled = false;
            }
        }
    }
}

function recalcularTotales() {
    var rows = document.querySelectorAll("#tbody_rubrica tr");
    var total = 0;
    var completas = 0;

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var inputPct = r.querySelector(".input-porcentaje");
        var val = inputPct ? parseFloat(inputPct.value) : 0;
        if (!isNaN(val) && val > 0) {
            total += val;
        }

        var isMan = (r.getAttribute("data-manual") === "1");
        if (isMan) {
            var inputNom = r.querySelector(".input-nombre-manual");
            if (inputNom && inputNom.value.trim().length > 0 && !isNaN(val) && val > 0) {
                completas++;
            }
        } else {
            var selAct = r.querySelector(".select-actividad");
            if (selAct && selAct.value && !isNaN(val) && val > 0) {
                completas++;
            }
        }
    }

    total = Math.round(total * 100) / 100;

    var txtTotal = document.getElementById("total_porcentaje_txt");
    var barra = document.getElementById("barra_progreso");
    var alerta = document.getElementById("alerta_porcentaje");
    var btnGuardar = document.getElementById("btn_guardar_rubrica");

    if (txtTotal) txtTotal.textContent = total + "%";

    if (barra) {
        barra.style.width = Math.min(total, 100) + "%";
        barra.className = "progress-bar";

        if (total === 100) {
            barra.classList.add("progress-bar-success");
        } else if (total > 100) {
            barra.classList.add("progress-bar-danger");
        } else {
            barra.classList.add("progress-bar-yellow");
        }
    }

    if (alerta && btnGuardar) {
        if (corteCalificado) {
            alerta.className = "text-sm text-yellow";
            alerta.innerHTML = '<i class="fa fa-lock"></i> Rúbrica calificada y cerrada. Modo solo lectura.';
            btnGuardar.disabled = true;
        } else if (total === 100 && completas === rows.length && rows.length > 0) {
            alerta.className = "text-sm text-green";
            alerta.innerHTML = '<i class="fa fa-check-circle"></i> Ponderacion completa (100%). Lista para guardar.';
            btnGuardar.disabled = false;
        } else if (total > 100) {
            var exceso = Math.round((total - 100) * 100) / 100;
            alerta.className = "text-sm text-red";
            alerta.innerHTML = '<i class="fa fa-times-circle"></i> El total supera el 100% por ' + exceso + '%. Debe corregir los valores.';
            btnGuardar.disabled = true;
        } else {
            var falta = Math.round((100 - total) * 100) / 100;
            alerta.className = "text-sm text-yellow";
            alerta.innerHTML = '<i class="fa fa-info-circle"></i> Faltan ' + falta + '% para completar el 100%.';
            btnGuardar.disabled = true;
        }
    }
}

function guardarRubrica() {
    if (corteCalificado) return;
    var rows = document.querySelectorAll("#tbody_rubrica tr");
    var items = [];

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var inputPct = r.querySelector(".input-porcentaje");
        if (!inputPct) continue;
        var pct = parseFloat(inputPct.value);

        var isMan = (r.getAttribute("data-manual") === "1");
        if (isMan) {
            var inputNom = r.querySelector(".input-nombre-manual");
            var nomManual = inputNom ? inputNom.value.trim() : "";
            if (!nomManual || isNaN(pct) || pct <= 0) {
                mostrarAlertaApp("Todas las actividades manuales deben tener un nombre y un porcentaje mayor a 0.", "warning", "Datos Incompletos");
                return;
            }
            items.push({
                id_actividad_moodle: 0,
                nombre_actividad: nomManual,
                tipo_actividad: "manual",
                porcentaje: pct
            });
        } else {
            var selectAct = r.querySelector(".select-actividad");
            if (!selectAct) continue;
            var opt = selectAct.options[selectAct.selectedIndex];
            var idAct = selectAct.value;

            if (!idAct || isNaN(pct) || pct <= 0) {
                mostrarAlertaApp("Todas las actividades de Moodle deben estar seleccionadas y tener un porcentaje mayor a 0.", "warning", "Datos Incompletos");
                return;
            }

            items.push({
                id_actividad_moodle: idAct,
                nombre_actividad: opt.getAttribute("data-nombre"),
                tipo_actividad: opt.getAttribute("data-tipo"),
                porcentaje: pct
            });
        }
    }

    if (items.length === 0) {
        mostrarAlertaApp("Debe agregar al menos una actividad a la rúbrica.", "warning", "Datos Incompletos");
        return;
    }

    var corte = document.getElementById("select_corte").value;
    var btnGuardar = document.getElementById("btn_guardar_rubrica");
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';

    var formData = new FormData();
    formData.append("course_id", courseId);
    formData.append("cod_profesor", codProfesor);
    formData.append("cod_materia", codMateria);
    formData.append("grupo", grupo);
    formData.append("semestre", semestre);
    formData.append("tipo_previo", corte);
    formData.append("items", JSON.stringify(items));

    fetch("<?php echo site_url('dashboard/guardar_rubrica_ajax'); ?>", {
        method: "POST",
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var msgDiv = document.getElementById("mensaje_ajax");
        msgDiv.style.display = "block";

        if (data.exito) {
            msgDiv.className = "alert alert-success";
            msgDiv.innerHTML = '<i class="fa fa-check"></i> ' + data.mensaje;
            mostrarAlertaApp(data.mensaje, "success", "Rúbrica Guardada", function() {
                window.location.reload();
            });
        } else {
            msgDiv.className = "alert alert-danger";
            msgDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.mensaje;
            mostrarAlertaApp(data.mensaje, "danger", "Error al Guardar");
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fa fa-save"></i> Guardar Rubrica';
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
    })
    .catch(function(err) {
        var msgDiv = document.getElementById("mensaje_ajax");
        msgDiv.style.display = "block";
        msgDiv.className = "alert alert-danger";
        msgDiv.innerHTML = '<i class="fa fa-times"></i> Error de comunicacion con el servidor.';
        mostrarAlertaApp("Error de comunicación con el servidor.", "danger", "Error");

        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fa fa-save"></i> Guardar Rubrica';
    });
}

function confirmarEliminarRubrica(corte, nombre) {
    var nom = nombre || ("Corte " + corte);
    mostrarConfirmacionApp(
        "¿Está seguro de que desea eliminar la rúbrica de <strong>" + nom + "</strong>?<br><br><span class='text-muted'>Esta acción borrará la configuración de actividades y no se puede deshacer.</span>",
        function() {
            ejecutarEliminarRubrica(corte);
        },
        {
            tipo: "danger",
            titulo: "Eliminar Rúbrica",
            btnTexto: "Eliminar Rúbrica",
            btnClase: "btn-danger",
            icono: "fa-trash"
        }
    );
}

function ejecutarEliminarRubrica(corte) {
    var formData = new FormData();
    formData.append("course_id", courseId);
    formData.append("cod_profesor", codProfesor);
    formData.append("cod_materia", codMateria);
    formData.append("grupo", grupo);
    formData.append("semestre", semestre);
    formData.append("tipo_previo", corte);

    fetch("<?php echo site_url('dashboard/eliminar_rubrica_ajax'); ?>", {
        method: "POST",
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.exito) {
            mostrarAlertaApp(data.mensaje, "success", "Rúbrica Eliminada", function() {
                window.location.reload();
            });
        } else {
            mostrarAlertaApp(data.mensaje, "danger", "Error al Eliminar");
        }
    })
    .catch(function(err) {
        mostrarAlertaApp("Error de comunicación al intentar eliminar la rúbrica.", "danger", "Error");
    });
}

function confirmarDesbloquearCorte(corte, nombre) {
    var nom = nombre || ("Corte " + corte);
    mostrarConfirmacionApp(
        "¿Está seguro de que desea desbloquear el corte <strong>" + nom + "</strong>?<br><br>Se anularán las calificaciones guardadas de este corte y la rúbrica volverá a quedar habilitada para modificar actividades o eliminarla.",
        function() {
            ejecutarDesbloquearCorte(corte);
        },
        {
            tipo: "warning",
            titulo: "Desbloquear Corte",
            btnTexto: "Desbloquear Corte",
            btnClase: "btn-warning",
            icono: "fa-unlock"
        }
    );
}

function ejecutarDesbloquearCorte(corte) {
    var formData = new FormData();
    formData.append("course_id", courseId);
    formData.append("cod_profesor", codProfesor);
    formData.append("cod_materia", codMateria);
    formData.append("grupo", grupo);
    formData.append("semestre", semestre);
    formData.append("tipo_previo", corte);

    fetch("<?php echo site_url('dashboard/desbloquear_corte_ajax'); ?>", {
        method: "POST",
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.exito) {
            mostrarAlertaApp(data.mensaje, "success", "Corte Desbloqueado", function() {
                window.location.reload();
            });
        } else {
            mostrarAlertaApp(data.mensaje, "danger", "Error al Desbloquear");
        }
    })
    .catch(function(err) {
        mostrarAlertaApp("Error de comunicación al intentar desbloquear el corte.", "danger", "Error");
    });
}

var _modalAppCallbackConfirmar = null;
var _modalAppCallbackCerrar = null;

function mostrarAlertaApp(mensaje, tipo, titulo, onCerrar) {
    tipo = tipo || "info";
    titulo = titulo || (tipo === "danger" ? "Error" : (tipo === "warning" ? "Atención" : (tipo === "success" ? "Operación Exitosa" : "Información")));
    _modalAppCallbackCerrar = onCerrar || null;

    var header = document.getElementById("modal_app_dialog_header");
    var titleTxt = document.getElementById("modal_app_dialog_title_txt");
    var icon = document.getElementById("modal_app_dialog_icon");
    var body = document.getElementById("modal_app_dialog_body");
    var btnCancelar = document.getElementById("modal_app_btn_cancelar");
    var btnConfirmar = document.getElementById("modal_app_btn_confirmar");

    var bgColors = {
        danger: "#dd4b39",
        warning: "#f39c12",
        success: "#00a65a",
        info: "#3c8dbc"
    };
    var icons = {
        danger: "fa fa-times-circle",
        warning: "fa fa-exclamation-triangle",
        success: "fa fa-check-circle",
        info: "fa fa-info-circle"
    };

    if (header) header.style.backgroundColor = bgColors[tipo] || "#3c8dbc";
    if (titleTxt) titleTxt.innerText = titulo;
    if (icon) icon.className = icons[tipo] || "fa fa-info-circle";
    if (body) body.innerHTML = mensaje;

    if (btnCancelar) btnCancelar.style.display = "none";
    if (btnConfirmar) {
        btnConfirmar.className = "btn btn-primary btn-sm";
        btnConfirmar.innerHTML = '<i class="fa fa-check"></i> Aceptar';
        btnConfirmar.onclick = function() {
            cerrarModalApp();
            if (typeof _modalAppCallbackCerrar === "function") {
                _modalAppCallbackCerrar();
                _modalAppCallbackCerrar = null;
            }
        };
    }

    abrirModalApp();
}

function mostrarConfirmacionApp(mensaje, onConfirmar, opciones) {
    opciones = opciones || {};
    var tipo = opciones.tipo || "warning";
    var titulo = opciones.titulo || "Confirmar Acción";
    var btnTexto = opciones.btnTexto || "Confirmar";
    var btnClase = opciones.btnClase || "btn-primary";
    var iconoClase = opciones.icono || (tipo === "danger" ? "fa-trash" : (tipo === "warning" ? "fa-exclamation-triangle" : "fa-check"));

    _modalAppCallbackConfirmar = onConfirmar || null;
    _modalAppCallbackCerrar = null;

    var header = document.getElementById("modal_app_dialog_header");
    var titleTxt = document.getElementById("modal_app_dialog_title_txt");
    var icon = document.getElementById("modal_app_dialog_icon");
    var body = document.getElementById("modal_app_dialog_body");
    var btnCancelar = document.getElementById("modal_app_btn_cancelar");
    var btnConfirmar = document.getElementById("modal_app_btn_confirmar");

    var bgColors = {
        danger: "#dd4b39",
        warning: "#f39c12",
        success: "#00a65a",
        info: "#3c8dbc"
    };
    var icons = {
        danger: "fa fa-exclamation-circle",
        warning: "fa fa-exclamation-triangle",
        success: "fa fa-check-circle",
        info: "fa fa-info-circle"
    };

    if (header) header.style.backgroundColor = bgColors[tipo] || "#3c8dbc";
    if (titleTxt) titleTxt.innerText = titulo;
    if (icon) icon.className = "fa " + iconoClase;
    if (body) body.innerHTML = mensaje;

    if (btnCancelar) btnCancelar.style.display = "inline-block";
    if (btnConfirmar) {
        btnConfirmar.className = "btn btn-sm " + btnClase;
        btnConfirmar.innerHTML = '<i class="fa fa-check"></i> ' + btnTexto;
        btnConfirmar.onclick = function() {
            cerrarModalApp();
            if (typeof _modalAppCallbackConfirmar === "function") {
                _modalAppCallbackConfirmar();
                _modalAppCallbackConfirmar = null;
            }
        };
    }

    abrirModalApp();
}

function abrirModalApp() {
    if (typeof $ !== "undefined" && $("#modal_app_dialog").modal) {
        $("#modal_app_dialog").modal("show");
    } else {
        var m = document.getElementById("modal_app_dialog");
        if (m) {
            m.style.display = "block";
            m.className = "modal fade in";
        }
    }
}

function cerrarModalApp() {
    if (typeof $ !== "undefined" && $("#modal_app_dialog").modal) {
        $("#modal_app_dialog").modal("hide");
    } else {
        var m = document.getElementById("modal_app_dialog");
        if (m) {
            m.style.display = "none";
            m.className = "modal fade";
        }
    }
}
</script>
