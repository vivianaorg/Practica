<script>
var ESTUDIANTES_DATA = <?php echo json_encode(isset($estudiantes) ? $estudiantes : array()); ?>;
var COD_PROFESOR     = "<?php echo isset($cod_profesor) ? $cod_profesor : ''; ?>";
var COD_MATERIA      = "<?php echo isset($cod_materia) ? $cod_materia : ''; ?>";
var GRUPO            = "<?php echo isset($grupo) ? $grupo : ''; ?>";
var SEMESTRE         = "<?php echo isset($semestre) ? $semestre : ''; ?>";
var TIPO_PREVIO      = "<?php echo isset($tipo_previo) ? $tipo_previo : ''; ?>";
var CORTE_CALIFICADO = <?php echo ($corte_calificado ? 'true' : 'false'); ?>;
var URL_GUARDAR_AJAX = "<?php echo site_url('calificaciones/guardar_calificaciones_corte_ajax'); ?>";
var URL_CAMBIO_CORTE = "<?php echo site_url('calificaciones/calificar_rubrica/' . $course_id); ?>";

document.addEventListener("DOMContentLoaded", function() {
    var selectCorte = document.getElementById("select_corte_evaluar");
    if (selectCorte) {
        selectCorte.addEventListener("change", function() {
            window.location.href = URL_CAMBIO_CORTE + "/" + this.value;
        });
    }

    if (!CORTE_CALIFICADO) {
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
    }

    var btnsDesglose = document.querySelectorAll(".btn-desglose");
    for (var k = 0; k < btnsDesglose.length; k++) {
        btnsDesglose[k].addEventListener("click", function() {
            var idx = parseInt(this.getAttribute("data-index"), 10);
            abrirModalDesglose(idx);
        });
    }
});

function aceptarSugerenciaIndividual(idx) {
    if (CORTE_CALIFICADO || !ESTUDIANTES_DATA[idx]) return;
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
    if (CORTE_CALIFICADO || !ESTUDIANTES_DATA[idx]) return;
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
    if (CORTE_CALIFICADO) return;
    for (var i = 0; i < ESTUDIANTES_DATA.length; i++) {
        aceptarSugerenciaIndividual(i);
    }
}

function restablecerTodasSugerencias() {
    if (CORTE_CALIFICADO) return;
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

function escapeHtml(text) {
    if (!text) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function actualizarCabeceraModalDesglose(est) {
    var elNotaTotal = document.getElementById("modal_nota_total");
    if (elNotaTotal) {
        var notaSug = parseFloat(est.nota_sugerida).toFixed(2);
        elNotaTotal.innerText = notaSug;
        elNotaTotal.style.color = (parseFloat(notaSug) >= 3.0) ? "#00a65a" : "#dd4b39";
    }
}

function actualizarFilaEstudianteUI(idx) {
    var est = ESTUDIANTES_DATA[idx];
    if (!est) return;

    var badgeSug = document.querySelector('.badge-sug[data-index="' + idx + '"]');
    var inputNota = document.querySelector('.input-nota[data-index="' + idx + '"]');
    var badgeEstado = document.querySelector('.badge-estado[data-index="' + idx + '"]');
    var btnAceptar = document.querySelector('.btn-aceptar-ind[data-index="' + idx + '"]');

    if (badgeSug) {
        badgeSug.innerText = parseFloat(est.nota_sugerida).toFixed(2);
        badgeSug.className = "label badge-sug " + (parseFloat(est.nota_sugerida) >= 3.0 ? "bg-green" : "bg-red");
    }

    if (inputNota) {
        inputNota.value = parseFloat(est.nota_definitiva).toFixed(2);
    }

    if (badgeEstado) {
        if (est.estado === "ACEPTADA") {
            badgeEstado.className = "label label-success badge-estado";
            badgeEstado.innerText = "ACEPTADA";
        } else if (est.estado === "MODIFICADA") {
            badgeEstado.className = "label label-primary badge-estado";
            badgeEstado.innerText = "MODIFICADA";
        } else {
            badgeEstado.className = "label label-warning badge-estado";
            badgeEstado.innerText = "SUGERIDA";
        }
    }

    if (btnAceptar) {
        btnAceptar.disabled = (est.estado === "ACEPTADA");
    }
}

function onCambioNotaManual(inputElem) {
    var estIdx = parseInt(inputElem.getAttribute("data-est-idx"), 10);
    var itemIdx = parseInt(inputElem.getAttribute("data-item-idx"), 10);

    if (!ESTUDIANTES_DATA[estIdx]) return;
    var est = ESTUDIANTES_DATA[estIdx];
    if (!est.desglose || !est.desglose[itemIdx]) return;
    var d = est.desglose[itemIdx];

    var val = parseFloat(inputElem.value);
    if (isNaN(val)) val = 0.0;
    if (val < 0) val = 0.0;
    if (val > 5) val = 5.0;

    d.nota_original = val;
    d.nota_maxima = 5.0;
    d.nota_normalizada = val;
    d.presento = (val > 0);
    d.subnota = Math.round((val * (parseFloat(d.porcentaje) / 100)) * 100) / 100;

    var tr = inputElem.closest("tr");
    if (tr) {
        var cellSub = tr.querySelector(".cell-subnota");
        if (cellSub) cellSub.innerText = d.subnota.toFixed(2);
    }

    var nuevoSug = 0;
    for (var k = 0; k < est.desglose.length; k++) {
        nuevoSug += (parseFloat(est.desglose[k].subnota) || 0);
    }
    nuevoSug = Math.min(5.0, Math.max(0.0, Math.round(nuevoSug * 100) / 100));
    est.nota_sugerida = nuevoSug;

    var totalSubnotaEl = document.getElementById("modal_total_subnota");
    if (totalSubnotaEl) {
        totalSubnotaEl.innerText = nuevoSug.toFixed(2);
    }

    actualizarCabeceraModalDesglose(est);

    if (est.estado !== "MODIFICADA") {
        est.nota_definitiva = nuevoSug;
        est.estado = "ACEPTADA";
    }

    actualizarFilaEstudianteUI(estIdx);
}

function abrirModalDesglose(idx) {
    if (!ESTUDIANTES_DATA[idx]) return;
    var est = ESTUDIANTES_DATA[idx];

    var elNombre = document.getElementById("modal_estudiante_nombre");
    var elCodigo = document.getElementById("modal_estudiante_codigo");
    var tbody = document.getElementById("modal_tbody_desglose");
    var totalSubnotaEl = document.getElementById("modal_total_subnota");

    if (elNombre) elNombre.innerText = est.nombre_completo;
    if (elCodigo) elCodigo.innerText = est.codigo;
    actualizarCabeceraModalDesglose(est);

    if (tbody) {
        tbody.innerHTML = "";
        var desglose = est.desglose || [];
        var sumaSub = 0;

        for (var i = 0; i < desglose.length; i++) {
            var d = desglose[i];
            var tr = document.createElement("tr");

            var sub = parseFloat(d.subnota) || 0;
            sumaSub += sub;

            var actNombreHtml = '<strong>' + escapeHtml(d.nombre_actividad) + '</strong>';
            if (d.es_manual) {
                actNombreHtml += ' <span class="label label-warning" style="font-size: 10px; margin-left: 5px;"><i class="fa fa-pencil"></i> Manual</span>';
            }

            var colNotaOrig = "";
            var colNotaBase = "";

            if (d.es_manual && !CORTE_CALIFICADO) {
                colNotaOrig = '<span class="text-muted"><i class="fa fa-pencil"></i> Directa</span>';
                colNotaBase = 
                    '<input type="number" min="0" max="5" step="0.1" class="form-control input-sm input-manual-cal" ' +
                    'data-est-idx="' + idx + '" data-item-idx="' + i + '" ' +
                    'value="' + parseFloat(d.nota_normalizada || 0).toFixed(2) + '" ' +
                    'oninput="onCambioNotaManual(this)" ' +
                    'style="width: 85px; margin: 0 auto; text-align: center; font-weight: bold; border-color: #f39c12;">';
            } else {
                var notaOrigTxt = parseFloat(d.nota_original).toFixed(2) + " / " + parseFloat(d.nota_maxima).toFixed(2);
                if (!d.presento) {
                    notaOrigTxt = '<span class="text-muted"><i class="fa fa-minus-circle"></i> Sin calificar</span>';
                }
                colNotaOrig = notaOrigTxt;
                colNotaBase = parseFloat(d.nota_normalizada).toFixed(2);
            }

            tr.innerHTML = 
                '<td style="text-align: center; vertical-align: middle;">' + (i + 1) + '</td>' +
                '<td style="vertical-align: middle;">' + actNombreHtml + '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' + colNotaOrig + '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' + colNotaBase + '</td>' +
                '<td style="text-align: center; vertical-align: middle;"><span class="badge bg-blue">' + parseFloat(d.porcentaje).toFixed(1) + '%</span></td>' +
                '<td style="text-align: center; vertical-align: middle; font-weight: bold; color: #0073b7;" class="cell-subnota">' + sub.toFixed(2) + '</td>';

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
    if (CORTE_CALIFICADO) return;

    var advertencia = "<strong>ATENCIÓN:</strong> Al guardar las calificaciones de este corte, la rúbrica y las notas quedarán registradas definitivamente y pasarán a modo <strong>INHABILITADO</strong>, impidiendo futuras modificaciones.<br><br>¿Está seguro de que desea confirmar y guardar las calificaciones?";

    mostrarConfirmacionApp(
        advertencia,
        function() {
            ejecutarGuardarCalificaciones();
        },
        {
            tipo: "warning",
            titulo: "Confirmar Calificaciones",
            btnTexto: "Confirmar y Guardar",
            btnClase: "btn-success",
            icono: "fa-save"
        }
    );
}

function ejecutarGuardarCalificaciones() {
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
            estado:          (est.estado === "MODIFICADA") ? "MODIFICADA" : "ACEPTADA",
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
                mostrarAlertaApp(data.mensaje, "success", "Calificaciones Guardadas", function() {
                    window.location.reload();
                });
            } else {
                msgDiv.className = "alert alert-danger";
                msgDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.mensaje;
                mostrarAlertaApp(data.mensaje, "danger", "Error al Guardar");
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
        mostrarAlertaApp("Error de comunicación con el servidor: " + err, "danger", "Error");
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Guardar Calificaciones'; }
    });
}

function confirmarDesbloquearCorteCalificar() {
    mostrarConfirmacionApp(
        "¿Está seguro de que desea desbloquear este corte?<br><br>Se anularán las calificaciones guardadas de este corte y volverá a quedar habilitado para recalificar o modificar la rúbrica.",
        function() {
            ejecutarDesbloquearCorteCalificar();
        },
        {
            tipo: "warning",
            titulo: "Desbloquear Calificaciones",
            btnTexto: "Desbloquear Corte",
            btnClase: "btn-warning",
            icono: "fa-unlock"
        }
    );
}

function ejecutarDesbloquearCorteCalificar() {
    var formData = new FormData();
    formData.append("course_id", <?php echo (int)$course_id; ?>);
    formData.append("cod_profesor", COD_PROFESOR);
    formData.append("cod_materia", COD_MATERIA);
    formData.append("grupo", GRUPO);
    formData.append("semestre", SEMESTRE);
    formData.append("tipo_previo", TIPO_PREVIO);

    fetch("<?php echo site_url('calificaciones/desbloquear_corte_ajax'); ?>", {
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
