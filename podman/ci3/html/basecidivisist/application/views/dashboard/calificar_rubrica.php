<?php
$nombresPrevios = array(
    '1'     => 'Primer Previo',
    '2'     => 'Segundo Previo',
    '3'     => 'Tercer Previo',
    'FINAL' => 'Examen Final'
);
$nombreCorteActual = isset($nombresPrevios[$tipo_previo]) ? $nombresPrevios[$tipo_previo] : 'Corte ' . $tipo_previo;
?>

<div class="box box-danger">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-6">
                <h3 class="box-title" style="font-weight: 600;">
                    <i class="fa fa-calculator text-red"></i> <?php echo htmlspecialchars($curso_nombre); ?>
                </h3>
                <div class="text-muted" style="margin-top: 4px;">
                    <strong>Materia:</strong> <?php echo htmlspecialchars($cod_materia); ?> &nbsp;|&nbsp;
                    <?php if (!empty($grupo) && $grupo !== '-'): ?>
                        <strong>Grupo:</strong> <?php echo htmlspecialchars($grupo); ?> &nbsp;|&nbsp;
                    <?php endif; ?>
                    <strong>Semestre:</strong> <?php echo htmlspecialchars($semestre); ?> &nbsp;|&nbsp;
                    <strong>Profesor:</strong> <?php echo htmlspecialchars($cod_profesor); ?>
                </div>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?php echo site_url('dashboard/cursos_moodle'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Volver a Cursos
                </a>
                <a href="<?php echo site_url('dashboard/actividades_moodle/' . $course_id); ?>" class="btn btn-default btn-sm" style="margin-left: 3px;">
                    <i class="fa fa-list"></i> Actividades
                </a>
                <a href="<?php echo site_url('dashboard/configurar_rubrica/' . $course_id . '/' . $tipo_previo . '#tab_configurar'); ?>" class="btn btn-info btn-sm" style="margin-left: 3px;">
                    <i class="fa fa-sliders"></i> Rúbrica
                </a>
                <a href="<?php echo site_url('dashboard/calificaciones/' . $course_id); ?>" class="btn btn-success btn-sm" style="margin-left: 3px; font-weight: bold;">
                    <i class="fa fa-graduation-cap"></i> Calificaciones
                </a>
            </div>
        </div>
    </div>

    <div class="box-body">

        <div class="well well-sm" style="background-color: #f9fafc; margin-bottom: 20px;">
            <div class="row">
                <div class="col-md-4">
                    <label><i class="fa fa-calendar-check-o"></i> Seleccionar Corte a Evaluar:</label>
                    <select id="select_corte_evaluar" class="form-control">
                        <option value="1" <?php echo ($tipo_previo == '1') ? 'selected' : ''; ?>>Primer Previo</option>
                        <option value="2" <?php echo ($tipo_previo == '2') ? 'selected' : ''; ?>>Segundo Previo</option>
                        <option value="3" <?php echo ($tipo_previo == '3') ? 'selected' : ''; ?>>Tercer Previo</option>
                        <option value="FINAL" <?php echo ($tipo_previo == 'FINAL') ? 'selected' : ''; ?>>Examen Final</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (!$exito_moodle): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> Error al consultar calificaciones desde Moodle: 
                <?php echo htmlspecialchars($mensaje_moodle); ?>
            </div>
        <?php elseif (!$rubrica_valida): ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-warning"></i> Rúbrica no disponible o incompleta para <?php echo htmlspecialchars($nombreCorteActual); ?></h4>
                <p>Para poder calcular las subnotas ponderadas a partir de Moodle, primero debes definir las actividades y porcentajes para este corte sumando exactamente 100%.</p>
                <p style="margin-top: 15px;">
                    <a href="<?php echo site_url('dashboard/configurar_rubrica/' . $course_id . '/' . $tipo_previo . '#tab_configurar'); ?>" class="btn btn-warning">
                        <i class="fa fa-sliders"></i> Configurar Rúbrica Ahora
                    </a>
                </p>
            </div>
        <?php elseif (empty($estudiantes)): ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> No se encontraron estudiantes matriculados en este curso de Moodle.
            </div>
        <?php else: ?>

            <?php if ($corte_calificado): ?>
                <div class="alert alert-info" style="margin-bottom: 15px;">
                    <i class="fa fa-lock"></i> <strong>Corte Calificado:</strong> Las calificaciones de este corte ya fueron registradas y consolidadas. La planilla se encuentra en modo solo lectura.
                </div>
            <?php endif; ?>

            <div id="mensaje_guardado" style="display: none;"></div>

            <div class="box box-solid box-default" style="border: 1px solid #d2d6de;">
                <div class="box-header with-border" style="background-color: #f4f5f7;">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_aceptar_todas" <?php echo ($corte_calificado ? 'disabled' : ''); ?>>
                                <i class="fa fa-check-square-o"></i> Aceptar Todas las Sugerencias
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="btn_restablecer_todas" style="margin-left: 5px;" <?php echo ($corte_calificado ? 'disabled' : ''); ?>>
                                <i class="fa fa-undo"></i> Restablecer Sugerencias
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <?php if ($corte_calificado): ?>
                                <button type="button" class="btn btn-warning btn-sm" onclick="confirmarDesbloquearCorteCalificar()" style="margin-right: 5px;">
                                    <i class="fa fa-unlock"></i> Desbloquear Calificaciones
                                </button>
                                <button type="button" class="btn btn-default btn-sm" id="btn_guardar_calificaciones" disabled style="font-weight: bold;">
                                    <i class="fa fa-lock"></i> Calificaciones Guardadas
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success btn-sm" id="btn_guardar_calificaciones" style="font-weight: bold;">
                                    <i class="fa fa-save"></i> Guardar Calificaciones
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="box-body no-padding table-responsive">
                    <table class="table table-bordered table-hover" id="tabla_estudiantes_corte">
                        <thead>
                            <tr class="bg-gray-light">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th style="width: 110px;">Código</th>
                                <th>Estudiante</th>
                                <th style="width: 120px; text-align: center;">Desglose</th>
                                <th style="width: 140px; text-align: center;">Nota Sugerida (Moodle)</th>
                                <th style="width: 140px; text-align: center;">Acción</th>
                                <th style="width: 130px; text-align: center;">Nota Definitiva</th>
                                <th style="width: 120px; text-align: center;">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_estudiantes">
                            <?php foreach ($estudiantes as $idx => $est): ?>
                                <?php
                                $sug = (float)$est['nota_sugerida'];
                                $def = (float)$est['nota_definitiva'];
                                $badgeColor = ($sug >= 3.0) ? 'label-success' : 'label-danger';
                                $estado = isset($est['estado']) ? $est['estado'] : 'SUGERIDA';
                                $estadoClass = 'label-warning';
                                if ($estado === 'ACEPTADA') {
                                    $estadoClass = 'label-success';
                                } elseif ($estado === 'MODIFICADA') {
                                    $estadoClass = 'label-primary';
                                }
                                ?>
                                <tr data-index="<?php echo $idx; ?>" data-codigo="<?php echo htmlspecialchars($est['codigo']); ?>">
                                    <td style="text-align: center; vertical-align: middle;"><?php echo ($idx + 1); ?></td>
                                    <td style="vertical-align: middle; font-weight: 600;">
                                        <?php echo htmlspecialchars($est['codigo']); ?>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <div style="font-weight: 600; color: #333;">
                                            <?php echo htmlspecialchars($est['nombre_completo']); ?>
                                        </div>
                                        <?php if (!empty($est['email'])): ?>
                                            <small class="text-muted"><i class="fa fa-envelope-o"></i> <?php echo htmlspecialchars($est['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <button type="button" class="btn btn-info btn-xs btn-desglose" data-index="<?php echo $idx; ?>">
                                            <i class="fa fa-list-ul"></i> Ver Rúbrica
                                        </button>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <span class="label <?php echo $badgeColor; ?> badge-sug" data-index="<?php echo $idx; ?>" style="font-size: 13px; padding: 4px 8px;">
                                            <?php echo number_format($sug, 2); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <button type="button" 
                                                class="btn btn-default btn-xs btn-aceptar-ind" 
                                                data-index="<?php echo $idx; ?>" 
                                                <?php echo ($corte_calificado || $estado === 'ACEPTADA') ? 'disabled' : ''; ?>
                                                title="Copiar sugerida a definitiva">
                                            <i class="fa fa-check text-green"></i> Aceptar
                                        </button>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="number" 
                                                class="form-control input-sm input-nota text-center" 
                                                data-index="<?php echo $idx; ?>"
                                                min="0.0" 
                                                max="5.0" 
                                                step="0.1" 
                                                value="<?php echo number_format($def, 2, '.', ''); ?>" 
                                                <?php echo ($corte_calificado ? 'readonly disabled' : ''); ?>
                                                style="font-weight: bold; font-size: 13px; max-width: 90px; margin: 0 auto;">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <span class="label <?php echo $estadoClass; ?> badge-estado" data-index="<?php echo $idx; ?>" style="font-size: 11px;">
                                            <?php echo htmlspecialchars($estado); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="box-footer clearfix" style="background-color: #f9fafc;">
                    <div class="text-muted">
                        <?php if ($corte_calificado): ?>
                            <i class="fa fa-lock"></i> Este corte evaluativo está cerrado. Las notas definitivas no pueden modificarse.
                        <?php else: ?>
                            <i class="fa fa-info-circle"></i> Puedes ajustar manualmente la nota definitiva de cualquier estudiante antes de guardar.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/calificar_rubrica_modal_desglose.php'; ?>
<?php include __DIR__ . '/calificar_rubrica_modal_dialog.php'; ?>
<?php include __DIR__ . '/calificar_rubrica_js.php'; ?>
