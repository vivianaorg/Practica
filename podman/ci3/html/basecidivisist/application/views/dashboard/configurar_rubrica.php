<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-sliders"></i> <?php echo htmlspecialchars(isset($curso_nombre) ? $curso_nombre : 'Curso'); ?>
        </h3>
        <div class="box-tools pull-right">
            <a href="<?php echo site_url('dashboard/cursos_moodle'); ?>" class="btn btn-default btn-sm">
                <i class="fa fa-arrow-left"></i> Volver a cursos
            </a>
        </div>
    </div>

    <div class="box-body">

        <?php if (!$exito): ?>
            <div class="alert alert-danger">
                Error al consultar Moodle: <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>
            </div>
        <?php else: ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Seleccionar Corte / Previo:</label>
                        <select id="select_corte" class="form-control">
                            <option value="1" <?php echo ($tipo_previo == '1') ? 'selected' : ''; ?>>Primer Previo</option>
                            <option value="2" <?php echo ($tipo_previo == '2') ? 'selected' : ''; ?>>Segundo Previo</option>
                            <option value="3" <?php echo ($tipo_previo == '3') ? 'selected' : ''; ?>>Tercer Previo</option>
                            <option value="FINAL" <?php echo ($tipo_previo == 'FINAL') ? 'selected' : ''; ?>>Examen Final</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-8">
                    <label>Progreso de Ponderación (Total: <span id="total_porcentaje_txt">0%</span> / 100%):</label>
                    <div class="progress progress-sm active" style="margin-top: 5px;">
                        <div id="barra_progreso" class="progress-bar progress-bar-yellow" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="alerta_porcentaje" class="text-sm text-yellow">
                        <i class="fa fa-info-circle"></i> La suma de porcentajes debe ser exactamente 100%.
                    </div>
                </div>
            </div>

            <hr style="margin-top: 10px; margin-bottom: 20px;">

            <div id="mensaje_ajax" style="display: none;"></div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabla_rubrica">
                    <thead>
                        <tr class="bg-gray-light">
                            <th style="width: 50px; text-align: center;">#</th>
                            <th>Actividad de Moodle</th>
                            <th style="width: 150px; text-align: center;">Tipo</th>
                            <th style="width: 180px; text-align: center;">Porcentaje (%)</th>
                            <th style="width: 80px; text-align: center;">Accion</th>
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
                    <button type="button" class="btn btn-success btn-lg" id="btn_guardar_rubrica" disabled>
                        <i class="fa fa-save"></i> Guardar Rubrica
                    </button>
                </div>
            </div>

        <?php endif; ?>

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
            window.location.href = urlBase + "/" + this.value;
        });
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
        } else {
            msgDiv.className = "alert alert-danger";
            msgDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.mensaje;
        }

        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fa fa-save"></i> Guardar Rubrica';
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
