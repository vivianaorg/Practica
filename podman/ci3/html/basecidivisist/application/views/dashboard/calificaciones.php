<div class="box box-primary">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-6">
                <h3 class="box-title" style="font-weight: 600;">
                    <i class="fa fa-graduation-cap text-primary"></i> <?php echo htmlspecialchars($curso_nombre); ?>
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
                <?php $this->load->view('dashboard/nav_curso_botones', array('course_id' => $course_id, 'activo' => 'calificaciones')); ?>
            </div>
        </div>
    </div>

    <div class="box-body">

        <?php if (!$exito_moodle): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i> Error al consultar Moodle: 
                <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>
            </div>
        <?php elseif (empty($planilla)): ?>
            <div class="callout callout-info">
                <h4><i class="fa fa-info-circle"></i> Sin estudiantes</h4>
                <p>No se encontraron estudiantes matriculados en este curso.</p>
            </div>
        <?php else: ?>

            <div class="row" style="margin-bottom: 12px;">
                <div class="col-md-6">
                    <div class="input-group input-group-sm" style="max-width: 350px;">
                        <input type="text" id="buscar_estudiante" class="form-control" placeholder="Buscar por código o nombre...">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default"><i class="fa fa-search"></i></button>
                        </span>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="tabla_calificaciones">
                    <thead>
                        <tr class="bg-gray-light">
                            <th style="width: 35px; text-align: center;">#</th>
                            <th style="width: 95px; text-align: center;">Código</th>
                            <th>Estudiante</th>
                            <th style="width: 110px; text-align: center;">1er Previo</th>
                            <th style="width: 110px; text-align: center;">2do Previo</th>
                            <th style="width: 110px; text-align: center;">3er Previo</th>
                            <th style="width: 110px; text-align: center;">Examen Final</th>
                            <th style="width: 100px; text-align: center;">Definitiva</th>
                            <th style="width: 85px; text-align: center;">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planilla as $idx => $fila): ?>
                            <?php
                            $c1Txt = ($fila['corte_1'] !== null) ? number_format($fila['corte_1'], 2) : '<span class="text-muted">-</span>';
                            $c2Txt = ($fila['corte_2'] !== null) ? number_format($fila['corte_2'], 2) : '<span class="text-muted">-</span>';
                            $c3Txt = ($fila['corte_3'] !== null) ? number_format($fila['corte_3'], 2) : '<span class="text-muted">-</span>';
                            $cfTxt = ($fila['corte_final'] !== null) ? number_format($fila['corte_final'], 2) : '<span class="text-muted">-</span>';

                            $defTxt = '<span class="text-muted">-</span>';
                            $badgeDef = 'label-default';
                            if ($fila['definitiva'] !== null) {
                                $defTxt = number_format($fila['definitiva'], 2);
                                $badgeDef = ($fila['definitiva'] >= 3.0) ? 'label-success' : 'label-danger';
                            }
                            ?>
                            <tr class="fila-estudiante">
                                <td style="text-align: center; vertical-align: middle;"><?php echo ($idx + 1); ?></td>
                                <td style="text-align: center; vertical-align: middle; font-weight: 600;" class="col-codigo">
                                    <?php echo htmlspecialchars($fila['codigo']); ?>
                                </td>
                                <td style="vertical-align: middle;" class="col-nombre">
                                    <strong><?php echo htmlspecialchars($fila['nombre_completo']); ?></strong>
                                    <?php if (!empty($fila['email'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($fila['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; vertical-align: middle; font-weight: 600;">
                                    <?php echo $c1Txt; ?>
                                </td>
                                <td style="text-align: center; vertical-align: middle; font-weight: 600;">
                                    <?php echo $c2Txt; ?>
                                </td>
                                <td style="text-align: center; vertical-align: middle; font-weight: 600;">
                                    <?php echo $c3Txt; ?>
                                </td>
                                <td style="text-align: center; vertical-align: middle; font-weight: 600;">
                                    <?php echo $cfTxt; ?>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <span class="label <?php echo $badgeDef; ?>" style="font-size: 13px; padding: 4px 8px;">
                                        <?php echo $defTxt; ?>
                                    </span>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <button type="button" class="btn btn-default btn-xs btn-detalle-est" data-index="<?php echo $idx; ?>" title="Ver desglose de subnotas">
                                        <i class="fa fa-search text-primary"></i> Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="modal_detalle_estudiante" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" style="font-weight: 600;">
                    <i class="fa fa-graduation-cap"></i> Desglose Detallado de Calificaciones
                </h4>
            </div>
            <div class="modal-body">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-8">
                        <h4 id="det_estudiante_nombre" style="margin-top: 0; font-weight: 600; color: #222;">-</h4>
                        <div class="text-muted">
                            <strong>Código:</strong> <span id="det_estudiante_codigo">-</span> &nbsp;|&nbsp;
                            <strong>Curso:</strong> <?php echo htmlspecialchars($curso_nombre); ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div style="font-size: 11px; text-transform: uppercase; color: #777;">Definitiva Acumulada</div>
                        <div id="det_estudiante_definitiva" style="font-size: 26px; font-weight: bold; color: #00a65a;">-</div>
                    </div>
                </div>

                <div class="nav-tabs-custom" style="box-shadow: none; border: 1px solid #e0e0e0;">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab_det_1" data-toggle="tab">1er Previo</a></li>
                        <li><a href="#tab_det_2" data-toggle="tab">2do Previo</a></li>
                        <li><a href="#tab_det_3" data-toggle="tab">3er Previo</a></li>
                        <li><a href="#tab_det_final" data-toggle="tab">Examen Final</a></li>
                    </ul>
                    <div class="tab-content" style="padding: 15px;">
                        <div class="tab-pane active" id="tab_det_1">
                            <div id="contenedor_corte_1"></div>
                        </div>
                        <div class="tab-pane" id="tab_det_2">
                            <div id="contenedor_corte_2"></div>
                        </div>
                        <div class="tab-pane" id="tab_det_3">
                            <div id="contenedor_corte_3"></div>
                        </div>
                        <div class="tab-pane" id="tab_det_final">
                            <div id="contenedor_corte_final"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
var PLANILLA_DATA = <?php echo json_encode(isset($planilla) ? $planilla : array()); ?>;

document.addEventListener("DOMContentLoaded", function() {
    var inputBuscar = document.getElementById("buscar_estudiante");
    if (inputBuscar) {
        inputBuscar.addEventListener("keyup", function() {
            var termino = this.value.toLowerCase().trim();
            var filas = document.querySelectorAll(".fila-estudiante");
            for (var i = 0; i < filas.length; i++) {
                var cod = filas[i].querySelector(".col-codigo").textContent.toLowerCase();
                var nom = filas[i].querySelector(".col-nombre").textContent.toLowerCase();
                if (cod.indexOf(termino) !== -1 || nom.indexOf(termino) !== -1) {
                    filas[i].style.display = "";
                } else {
                    filas[i].style.display = "none";
                }
            }
        });
    }

    var btnsDetalle = document.querySelectorAll(".btn-detalle-est");
    for (var j = 0; j < btnsDetalle.length; j++) {
        btnsDetalle[j].addEventListener("click", function() {
            var idx = parseInt(this.getAttribute("data-index"), 10);
            abrirDetalleEstudiante(idx);
        });
    }
});

function abrirDetalleEstudiante(idx) {
    if (!PLANILLA_DATA[idx]) return;
    var est = PLANILLA_DATA[idx];

    var elNombre = document.getElementById("det_estudiante_nombre");
    var elCodigo = document.getElementById("det_estudiante_codigo");
    var elDef    = document.getElementById("det_estudiante_definitiva");

    if (elNombre) elNombre.innerText = est.nombre_completo;
    if (elCodigo) elCodigo.innerText = est.codigo;
    if (elDef) {
        if (est.definitiva !== null) {
            var val = parseFloat(est.definitiva).toFixed(2);
            elDef.innerText = val;
            elDef.style.color = (parseFloat(val) >= 3.0) ? "#00a65a" : "#dd4b39";
        } else {
            elDef.innerText = "-";
            elDef.style.color = "#777";
        }
    }

    renderizarTablaCorte("contenedor_corte_1", est.desglose["1"] || [], est.corte_1);
    renderizarTablaCorte("contenedor_corte_2", est.desglose["2"] || [], est.corte_2);
    renderizarTablaCorte("contenedor_corte_3", est.desglose["3"] || [], est.corte_3);
    renderizarTablaCorte("contenedor_corte_final", est.desglose["FINAL"] || [], est.corte_final);

    $("#modal_detalle_estudiante").modal("show");
}

function renderizarTablaCorte(contenedorId, subnotas, notaCorte) {
    var cont = document.getElementById(contenedorId);
    if (!cont) return;

    if (!subnotas || subnotas.length === 0) {
        cont.innerHTML = '<div class="alert alert-warning" style="margin-bottom: 0;">' +
            '<i class="fa fa-info-circle"></i> No hay calificaciones registradas para este corte evaluativo.' +
            '</div>';
        return;
    }

    var html = '<div class="table-responsive">' +
        '<table class="table table-bordered table-striped">' +
        '<thead>' +
        '<tr class="bg-gray-light">' +
        '<th style="width: 35px; text-align: center;">#</th>' +
        '<th>Actividad Moodle</th>' +
        '<th style="width: 130px; text-align: center;">Nota Original</th>' +
        '<th style="width: 90px; text-align: center;">Peso (%)</th>' +
        '<th style="width: 110px; text-align: center;">Subnota</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>';

    var sumaSub = 0;
    var sumaPct = 0;

    for (var i = 0; i < subnotas.length; i++) {
        var s = subnotas[i];
        var orig = parseFloat(s.NOTA_ORIGINAL).toFixed(2) + " / " + parseFloat(s.NOTA_MAXIMA).toFixed(2);
        var pct = parseFloat(s.PORCENTAJE).toFixed(1);
        var sub = parseFloat(s.SUBNOTA).toFixed(2);

        sumaSub += parseFloat(s.SUBNOTA);
        sumaPct += parseFloat(s.PORCENTAJE);

        html += '<tr>' +
            '<td style="text-align: center;">' + (i + 1) + '</td>' +
            '<td><strong>' + s.NOMBRE_ACTIVIDAD + '</strong></td>' +
            '<td style="text-align: center;">' + orig + '</td>' +
            '<td style="text-align: center;"><span class="badge bg-blue">' + pct + '%</span></td>' +
            '<td style="text-align: center; font-weight: bold; color: #0073b7;">' + sub + '</td>' +
            '</tr>';
    }

    var notaFinalCorte = (notaCorte !== null) ? parseFloat(notaCorte).toFixed(2) : sumaSub.toFixed(2);

    html += '</tbody>' +
        '<tfoot>' +
        '<tr class="bg-gray-light" style="font-weight: bold;">' +
        '<td colspan="3" class="text-right">TOTAL PONDERADO:</td>' +
        '<td style="text-align: center;">' + sumaPct.toFixed(1) + '%</td>' +
        '<td style="text-align: center; color: #00a65a; font-size: 14px;">' + notaFinalCorte + '</td>' +
        '</tr>' +
        '</tfoot>' +
        '</table>' +
        '</div>';

    cont.innerHTML = html;
}
</script>
