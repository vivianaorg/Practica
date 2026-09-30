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
                <li class="pull-right">
                    <a href="<?php echo site_url('dashboard/cursos_moodle'); ?>" class="text-muted" style="padding: 10px 15px;">
                        <i class="fa fa-arrow-left"></i> Volver a cursos
                    </a>
                </li>
                <li class="pull-right">
                    <a href="<?php echo site_url('dashboard/actividades_moodle/' . $course_id); ?>" class="text-muted" style="padding: 10px 15px;">
                        <i class="fa fa-list"></i> Actividades
                    </a>
                </li>
                <li class="pull-right">
                    <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $course_id . '/' . $tipo_previo); ?>" class="text-green" style="padding: 10px 15px; font-weight: bold;">
                        <i class="fa fa-calculator"></i> Calificar Corte
                    </a>
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

                    <div class="row" style="margin-top: 15px;">
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
                        ?>
                            <div class="col-md-6 col-sm-12">
                                <div class="box box-solid <?php echo $estaCompleto ? 'box-success' : ($tieneItems ? 'box-warning' : 'box-default'); ?>">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">
                                            <i class="fa fa-calendar-check-o"></i> <?php echo htmlspecialchars($nombreCorte); ?>
                                        </h3>
                                        <div class="box-tools pull-right">
                                            <span class="badge <?php echo $estaCompleto ? 'bg-green' : ($tieneItems ? 'bg-yellow' : 'bg-gray'); ?>">
                                                <?php echo $totalPorcentajeCorte; ?>% / 100%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="box-body no-padding">
                                        <?php if (empty($itemsCorte)): ?>
                                            <div style="padding: 20px; text-align: center;" class="text-muted">
                                                <i class="fa fa-folder-open-o fa-2x"></i>
                                                <p style="margin-top: 5px;">No se han configurado actividades para este corte.</p>
                                                <button type="button" class="btn btn-default btn-sm" onclick="irAConfigurarCorte('<?php echo $claveCorte; ?>')">
                                                    <i class="fa fa-plus"></i> Configurar ahora
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <table class="table table-striped table-condensed">
                                                <thead>
                                                    <tr>
                                                        <th>Actividad</th>
                                                        <th style="width: 90px; text-align: center;">Tipo</th>
                                                        <th style="width: 90px; text-align: right;">Peso</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($itemsCorte as $it): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="fa fa-check-square-o text-green"></i> 
                                                                <?php echo htmlspecialchars($it->NOMBRE_ACTIVIDAD); ?>
                                                            </td>
                                                            <td style="text-align: center;">
                                                                <span class="label label-info"><?php echo htmlspecialchars($it->TIPO_ACTIVIDAD ? $it->TIPO_ACTIVIDAD : 'moodle'); ?></span>
                                                            </td>
                                                            <td style="text-align: right; font-weight: bold;">
                                                                <?php echo htmlspecialchars($it->PORCENTAJE); ?>%
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="bg-gray-light">
                                                        <th colspan="2" style="text-align: right;">Total Asignado:</th>
                                                        <th style="text-align: right; font-weight: bold; color: <?php echo $estaCompleto ? 'green' : 'orange'; ?>;">
                                                            <?php echo $totalPorcentajeCorte; ?>%
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            <div style="padding: 8px; text-align: right; background: #fafafa; border-top: 1px solid #f4f4f4;">
                                                <button type="button" class="btn btn-primary btn-xs" onclick="irAConfigurarCorte('<?php echo $claveCorte; ?>')">
                                                    <i class="fa fa-pencil"></i> Modificar Rubrica
                                                </button>
                                                <?php if ($estaCompleto): ?>
                                                    <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $course_id . '/' . $claveCorte); ?>" class="btn btn-success btn-xs" style="margin-left: 5px;">
                                                        <i class="fa fa-calculator"></i> Calificar Corte
                                                    </a>
                                                <?php endif; ?>
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
                        <div class="alert alert-danger">
                            Error al consultar Moodle: <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>
                        </div>
                    <?php else: ?>

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
                                        <th>Actividad de Moodle</th>
                                        <th style="width: 140px; text-align: center;">Tipo</th>
                                        <th style="width: 160px; text-align: center;">Porcentaje (%)</th>
                                        <th style="width: 60px; text-align: center;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_rubrica">
                                </tbody>
                            </table>
                        </div>

                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-default" id="btn_agregar_fila">
                                    <i class="fa fa-plus"></i> Agregar Actividad
                                </button>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-success" id="btn_guardar_rubrica" disabled style="font-weight: 600;">
                                    <i class="fa fa-save"></i> Guardar Rúbrica
                                </button>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
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
    if (btnAgregar) {
        btnAgregar.addEventListener("click", function() {
            agregarFilaRubrica(null, 0);
        });
    }

    var btnGuardar = document.getElementById("btn_guardar_rubrica");
    if (btnGuardar) {
        btnGuardar.addEventListener("click", guardarRubrica);
    }

    if (rubricaGuardada && rubricaGuardada.length > 0) {
        for (var i = 0; i < rubricaGuardada.length; i++) {
            var r = rubricaGuardada[i];
            agregarFilaRubrica(r.ID_ACTIVIDAD_MOODLE, r.PORCENTAJE);
        }
    } else {
        agregarFilaRubrica(null, 0);
    }

    actualizarOpcionesDisponibles();
    recalcularTotales();
});

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

function agregarFilaRubrica(actividadSeleccionada, porcentaje) {
    var tbody = document.getElementById("tbody_rubrica");
    if (!tbody) return;

    var index = tbody.rows.length + 1;
    var tr = document.createElement("tr");

    var selectHtml = '<select class="form-control select-actividad" required onchange="onActividadChange(this)">';
    selectHtml += '<option value="">-- Seleccione una actividad --</option>';

    for (var i = 0; i < actividadesDisponibles.length; i++) {
        var act = actividadesDisponibles[i];
        var selected = (actividadSeleccionada && String(act.id) === String(actividadSeleccionada)) ? 'selected' : '';
        selectHtml += '<option value="' + act.id + '" data-tipo="' + act.tipo + '" data-nombre="' + act.nombre.replace(/"/g, '&quot;') + '" ' + selected + '>';
        selectHtml += act.nombre + ' (' + act.tipo + ')';
        selectHtml += '</option>';
    }
    selectHtml += '</select>';

    var tipoActividad = "";
    if (actividadSeleccionada) {
        for (var k = 0; k < actividadesDisponibles.length; k++) {
            if (String(actividadesDisponibles[k].id) === String(actividadSeleccionada)) {
                tipoActividad = actividadesDisponibles[k].tipo;
                break;
            }
        }
    }

    tr.innerHTML = 
        '<td style="text-align: center; vertical-align: middle;" class="row-num">' + index + '</td>' +
        '<td>' + selectHtml + '</td>' +
        '<td style="text-align: center; vertical-align: middle;"><span class="label label-info badge-tipo">' + (tipoActividad || '-') + '</span></td>' +
        '<td>' +
            '<div class="input-group">' +
                '<input type="number" class="form-control input-porcentaje text-right" min="1" max="100" step="0.5" value="' + (porcentaje > 0 ? porcentaje : '') + '" placeholder="0" oninput="recalcularTotales()" required>' +
                '<span class="input-group-addon">%</span>' +
            '</div>' +
        '</td>' +
        '<td style="text-align: center; vertical-align: middle;">' +
            '<button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaRubrica(this)">' +
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
    var inputs = document.querySelectorAll(".input-porcentaje");
    var selects = document.querySelectorAll(".select-actividad");
    var total = 0;
    var completas = 0;

    for (var i = 0; i < inputs.length; i++) {
        var val = parseFloat(inputs[i].value);
        if (!isNaN(val) && val > 0) {
            total += val;
        }
        if (selects[i] && selects[i].value && !isNaN(val) && val > 0) {
            completas++;
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
        if (total === 100 && completas === inputs.length && inputs.length > 0) {
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
    var rows = document.querySelectorAll("#tbody_rubrica tr");
    var items = [];

    for (var i = 0; i < rows.length; i++) {
        var select = rows[i].querySelector(".select-actividad");
        var input = rows[i].querySelector(".input-porcentaje");

        if (!select || !input) continue;

        var opt = select.options[select.selectedIndex];
        var idAct = select.value;
        var pct = parseFloat(input.value);

        if (!idAct || isNaN(pct) || pct <= 0) {
            alert("Todas las filas deben tener una actividad seleccionada y un porcentaje mayor a 0.");
            return;
        }

        items.push({
            id_actividad_moodle: idAct,
            nombre_actividad: opt.getAttribute("data-nombre"),
            tipo_actividad: opt.getAttribute("data-tipo"),
            porcentaje: pct
        });
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
            setTimeout(function() {
                window.location.reload();
            }, 1200);
        } else {
            msgDiv.className = "alert alert-danger";
            msgDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.mensaje;
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

        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fa fa-save"></i> Guardar Rubrica';
    });
}
</script>
