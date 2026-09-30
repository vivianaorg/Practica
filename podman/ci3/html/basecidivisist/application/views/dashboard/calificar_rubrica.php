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
            <div class="col-md-7">
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
            <div class="col-md-5 text-right">
                <a href="<?php echo site_url('dashboard/cursos_moodle'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Volver a Cursos
                </a>
                <a href="<?php echo site_url('dashboard/actividades_moodle/' . $course_id); ?>" class="btn btn-default btn-sm" style="margin-left: 3px;">
                    <i class="fa fa-list"></i> Actividades
                </a>
                <a href="<?php echo site_url('dashboard/configurar_rubrica/' . $course_id . '/' . $tipo_previo . '#tab_configurar'); ?>" class="btn btn-info btn-sm" style="margin-left: 3px;">
                    <i class="fa fa-sliders"></i> Ver / Configurar Rúbrica
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
                <div class="col-md-8">
                    <label>Estado de la Rúbrica para <?php echo htmlspecialchars($nombreCorteActual); ?>:</label>
                    <div>
                        <?php if ($rubrica_valida): ?>
                            <span class="label label-success" style="font-size: 13px; padding: 5px 10px;">
                                <i class="fa fa-check-circle"></i> Configurada al 100% (<?php echo count($rubrica); ?> actividades)
                            </span>
                            <div style="margin-top: 8px;">
                                <?php foreach ($rubrica as $itemR): ?>
                                    <span class="badge bg-gray" style="margin-right: 5px; font-weight: normal;">
                                        <?php echo htmlspecialchars($itemR->NOMBRE_ACTIVIDAD); ?>: <strong><?php echo (float)$itemR->PORCENTAJE; ?>%</strong>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="label label-warning" style="font-size: 13px; padding: 5px 10px;">
                                <i class="fa fa-exclamation-triangle"></i> Incompleta (Suma actual: <?php echo (float)$suma_porcentajes; ?>% / 100%)
                            </span>
                            <span style="margin-left: 10px; font-size: 12px; color: #777;">
                                Es necesario configurar la rúbrica al 100% antes de calcular las notas definitivas.
                            </span>
                        <?php endif; ?>
                    </div>
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

            <div id="mensaje_guardado" style="display: none;"></div>

            <div class="box box-solid box-default" style="border: 1px solid #d2d6de;">
                <div class="box-header with-border" style="background-color: #f4f5f7;">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_aceptar_todas">
                                <i class="fa fa-check-square-o"></i> Aceptar Todas las Sugerencias
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="btn_restablecer_todas" style="margin-left: 5px;">
                                <i class="fa fa-undo"></i> Restablecer Sugerencias
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-success btn-sm" id="btn_guardar_calificaciones" style="font-weight: bold;">
                                <i class="fa fa-save"></i> Guardar Calificaciones
                            </button>
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
                                        <span class="label <?php echo $badgeColor; ?>" style="font-size: 13px; padding: 4px 8px;">
                                            <?php echo number_format($sug, 2); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <button type="button" 
                                                class="btn btn-default btn-xs btn-aceptar-ind" 
                                                data-index="<?php echo $idx; ?>" 
                                                <?php echo ($estado === 'ACEPTADA') ? 'disabled' : ''; ?>
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
                        <i class="fa fa-info-circle"></i> Puedes ajustar manualmente la nota definitiva de cualquier estudiante antes de guardar.
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="modal_desglose" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal_titulo" style="font-weight: 600;">
                    <i class="fa fa-pie-chart"></i> Desglose de Rúbrica del Estudiante
                </h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-8">
                        <h4 id="modal_estudiante_nombre" style="margin-top: 0; font-weight: 600; color: #222;">-</h4>
                        <div class="text-muted">
                            <strong>Código:</strong> <span id="modal_estudiante_codigo">-</span> &nbsp;|&nbsp;
                            <strong>Corte:</strong> <?php echo htmlspecialchars($nombreCorteActual); ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div style="font-size: 11px; text-transform: uppercase; color: #777;">Nota Sugerida Total</div>
                        <div id="modal_nota_total" style="font-size: 26px; font-weight: bold; color: #00a65a;">0.00</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-gray-light">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Actividad Moodle</th>
                                <th style="width: 140px; text-align: center;">Nota Original</th>
                                <th style="width: 150px; text-align: center;">Nota Base (0.0-5.0)</th>
                                <th style="width: 110px; text-align: center;">Peso (%)</th>
                                <th style="width: 130px; text-align: center;">Subnota</th>
                            </tr>
                        </thead>
                        <tbody id="modal_tbody_desglose">
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-light" style="font-weight: bold;">
                                <td colspan="4" class="text-right">TOTAL PONDERADO:</td>
                                <td style="text-align: center;" id="modal_total_peso">100%</td>
                                <td style="text-align: center;" id="modal_total_subnota">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
var ESTUDIANTES_DATA = <?php echo json_encode(isset($estudiantes) ? $estudiantes : array()); ?>;
var COD_PROFESOR     = "<?php echo isset($cod_profesor) ? $cod_profesor : ''; ?>";
var COD_MATERIA      = "<?php echo isset($cod_materia) ? $cod_materia : ''; ?>";
var GRUPO            = "<?php echo isset($grupo) ? $grupo : ''; ?>";
var SEMESTRE         = "<?php echo isset($semestre) ? $semestre : ''; ?>";
var TIPO_PREVIO      = "<?php echo isset($tipo_previo) ? $tipo_previo : ''; ?>";
var URL_GUARDAR_AJAX = "<?php echo site_url('dashboard/guardar_calificaciones_corte_ajax'); ?>";
var URL_CAMBIO_CORTE = "<?php echo site_url('dashboard/calificar_rubrica/' . $course_id); ?>";

document.addEventListener("DOMContentLoaded", function() {
    var selectCorte = document.getElementById("select_corte_evaluar");
    if (selectCorte) {
        selectCorte.addEventListener("change", function() {
            window.location.href = URL_CAMBIO_CORTE + "/" + this.value;
        });
    }

    var btnsAceptarInd = document.querySelectorAll(".btn-aceptar-ind");
    for (var i = 0; i < btnsAceptarInd.length; i++) {
        btnsAceptarInd[i].addEventListener("click", function() {
            var idx = parseInt(this.getAttribute("data-index"), 10);
            aceptarSugerenciaIndividual(idx);
        });
    }

    var inputsNota = document.querySelectorAll(".input-nota");
    for (var j = 0; j < inputsNota.length; j++) {
        inputsNota[j].addEventListener("input", function() {
            var idx = parseInt(this.getAttribute("data-index"), 10);
            evaluarCambioNota(idx, this.value);
        });
    }

    var btnsDesglose = document.querySelectorAll(".btn-desglose");
    for (var k = 0; k < btnsDesglose.length; k++) {
        btnsDesglose[k].addEventListener("click", function() {
            var idx = parseInt(this.getAttribute("data-index"), 10);
            abrirModalDesglose(idx);
        });
    }

    var btnAceptarTodas = document.getElementById("btn_aceptar_todas");
    if (btnAceptarTodas) {
        btnAceptarTodas.addEventListener("click", function() {
            aceptarTodasSugerencias();
        });
    }

    var btnRestablecerTodas = document.getElementById("btn_restablecer_todas");
    if (btnRestablecerTodas) {
        btnRestablecerTodas.addEventListener("click", function() {
            restablecerTodasSugerencias();
        });
    }

    var btnGuardar = document.getElementById("btn_guardar_calificaciones");
    if (btnGuardar) btnGuardar.addEventListener("click", guardarCalificaciones);
});

function aceptarSugerenciaIndividual(idx) {
    if (!ESTUDIANTES_DATA[idx]) return;
    var est = ESTUDIANTES_DATA[idx];
    var input = document.querySelector('.input-nota[data-index="' + idx + '"]');
    var badge = document.querySelector('.badge-estado[data-index="' + idx + '"]');
    var btn = document.querySelector('.btn-aceptar-ind[data-index="' + idx + '"]');

    if (input) {
        input.value = parseFloat(est.nota_sugerida).toFixed(2);
    }

    est.nota_definitiva = parseFloat(est.nota_sugerida);
    est.estado = "ACEPTADA";

    if (badge) {
        badge.className = "label label-success badge-estado";
        badge.innerText = "ACEPTADA";
    }

    if (btn) {
        btn.disabled = true;
    }
}

function evaluarCambioNota(idx, valorStr) {
    if (!ESTUDIANTES_DATA[idx]) return;
    var est = ESTUDIANTES_DATA[idx];
    var badge = document.querySelector('.badge-estado[data-index="' + idx + '"]');
    var btn = document.querySelector('.btn-aceptar-ind[data-index="' + idx + '"]');

    var val = parseFloat(valorStr);
    if (isNaN(val)) val = 0.0;
    if (val < 0) val = 0.0;
    if (val > 5) val = 5.0;

    est.nota_definitiva = val;

    var sug = parseFloat(est.nota_sugerida);
    if (Math.abs(val - sug) < 0.01) {
        est.estado = "ACEPTADA";
        if (badge) {
            badge.className = "label label-success badge-estado";
            badge.innerText = "ACEPTADA";
        }
        if (btn) {
            btn.disabled = true;
        }
    } else {
        est.estado = "MODIFICADA";
        if (badge) {
            badge.className = "label label-primary badge-estado";
            badge.innerText = "MODIFICADA";
        }
        if (btn) {
            btn.disabled = false;
        }
    }
}

function aceptarTodasSugerencias() {
    for (var i = 0; i < ESTUDIANTES_DATA.length; i++) {
        aceptarSugerenciaIndividual(i);
    }
}

function restablecerTodasSugerencias() {
    for (var i = 0; i < ESTUDIANTES_DATA.length; i++) {
        var est = ESTUDIANTES_DATA[i];
        var input = document.querySelector('.input-nota[data-index="' + i + '"]');
        var badge = document.querySelector('.badge-estado[data-index="' + i + '"]');
        var btn = document.querySelector('.btn-aceptar-ind[data-index="' + i + '"]');

        est.nota_definitiva = parseFloat(est.nota_sugerida);
        est.estado = "SUGERIDA";

        if (input) {
            input.value = parseFloat(est.nota_sugerida).toFixed(2);
        }
        if (badge) {
            badge.className = "label label-warning badge-estado";
            badge.innerText = "SUGERIDA";
        }
        if (btn) {
            btn.disabled = false;
        }
    }
}

function abrirModalDesglose(idx) {
    if (!ESTUDIANTES_DATA[idx]) return;
    var est = ESTUDIANTES_DATA[idx];

    var elNombre = document.getElementById("modal_estudiante_nombre");
    var elCodigo = document.getElementById("modal_estudiante_codigo");
    var elNotaTotal = document.getElementById("modal_nota_total");
    var tbody = document.getElementById("modal_tbody_desglose");
    var totalSubnotaEl = document.getElementById("modal_total_subnota");

    if (elNombre) elNombre.innerText = est.nombre_completo;
    if (elCodigo) elCodigo.innerText = est.codigo;
    if (elNotaTotal) {
        var notaSug = parseFloat(est.nota_sugerida).toFixed(2);
        elNotaTotal.innerText = notaSug;
        elNotaTotal.style.color = (parseFloat(notaSug) >= 3.0) ? "#00a65a" : "#dd4b39";
    }

    if (tbody) {
        tbody.innerHTML = "";
        var desglose = est.desglose || [];
        var sumaSub = 0;

        for (var i = 0; i < desglose.length; i++) {
            var d = desglose[i];
            var tr = document.createElement("tr");

            var notaOrigTxt = parseFloat(d.nota_original).toFixed(2) + " / " + parseFloat(d.nota_maxima).toFixed(2);
            if (!d.presento) {
                notaOrigTxt = '<span class="text-muted"><i class="fa fa-minus-circle"></i> Sin calificar</span>';
            }

            var sub = parseFloat(d.subnota) || 0;
            sumaSub += sub;

            tr.innerHTML = 
                '<td style="text-align: center;">' + (i + 1) + '</td>' +
                '<td><strong>' + d.nombre_actividad + '</strong></td>' +
                '<td style="text-align: center;">' + notaOrigTxt + '</td>' +
                '<td style="text-align: center;">' + parseFloat(d.nota_normalizada).toFixed(2) + '</td>' +
                '<td style="text-align: center;"><span class="badge bg-blue">' + parseFloat(d.porcentaje).toFixed(1) + '%</span></td>' +
                '<td style="text-align: center; font-weight: bold; color: #0073b7;">' + sub.toFixed(2) + '</td>';

            tbody.appendChild(tr);
        }

        if (totalSubnotaEl) {
            totalSubnotaEl.innerText = sumaSub.toFixed(2);
        }
    }

    if (typeof $ !== "undefined" && $("#modal_desglose").modal) {
        $("#modal_desglose").modal("show");
    } else {
        var m = document.getElementById("modal_desglose");
        if (m) {
            m.style.display = "block";
            m.className = "modal fade in";
        }
    }
}

function guardarCalificaciones() {
    var btn = document.getElementById("btn_guardar_calificaciones");
    var msgDiv = document.getElementById("mensaje_guardado");

    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...'; }

    var payload = [];
    for (var i = 0; i < ESTUDIANTES_DATA.length; i++) {
        var est = ESTUDIANTES_DATA[i];
        payload.push({
            cod_estudiante:  est.codigo,
            nota_definitiva: parseFloat(est.nota_definitiva) || 0.0,
            nota_sugerida:   parseFloat(est.nota_sugerida) || 0.0,
            estado:          est.estado || "SUGERIDA",
            desglose:        est.desglose || []
        });
    }

    var formData = new FormData();
    formData.append("cod_profesor", COD_PROFESOR);
    formData.append("cod_materia", COD_MATERIA);
    formData.append("grupo", GRUPO);
    formData.append("semestre", SEMESTRE);
    formData.append("tipo_previo", TIPO_PREVIO);
    formData.append("calificaciones", JSON.stringify(payload));

    fetch(URL_GUARDAR_AJAX, {
        method: "POST",
        body: formData
    })
    .then(function(res) {
        return res.json();
    })
    .then(function(data) {
        if (msgDiv) {
            msgDiv.style.display = "block";
            if (data.exito) {
                msgDiv.className = "alert alert-success";
                msgDiv.innerHTML = '<i class="fa fa-check"></i> ' + data.mensaje;
            } else {
                msgDiv.className = "alert alert-danger";
                msgDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.mensaje;
            }
        }

        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Guardar Calificaciones'; }
        window.scrollTo({ top: 0, behavior: "smooth" });
    })
    .catch(function(err) {
        if (msgDiv) {
            msgDiv.style.display = "block";
            msgDiv.className = "alert alert-danger";
            msgDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error de comunicacion con el servidor: ' + err;
        }
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Guardar Calificaciones'; }
    });
}
</script>
