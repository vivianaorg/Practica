// ============================================================
// configurar_rubrica.js
// Gestión de Rúbricas Evaluativas - CIDIVISIST
// Extraído de configurar_rubrica.php para mejor organización
// Requiere: window.RUBRICA_CONFIG (inyectado por la vista PHP)
// ============================================================

var actividadesDisponibles = window.RUBRICA_CONFIG.actividadesDisponibles;
var rubricaGuardada = window.RUBRICA_CONFIG.rubricaGuardada;
var courseId = window.RUBRICA_CONFIG.courseId;
var codProfesor = window.RUBRICA_CONFIG.codProfesor;
var codMateria = window.RUBRICA_CONFIG.codMateria;
var grupo = window.RUBRICA_CONFIG.grupo;
var semestre = window.RUBRICA_CONFIG.semestre;
var corteCalificado = window.RUBRICA_CONFIG.corteCalificado;
var cursosConRubricasData = [];

const MODAL_THEMES = {
    bgColors: {
        danger: "#dd4b39",
        warning: "#f39c12",
        success: "#00a65a",
        info: "#3c8dbc"
    },
    icons: {
        danger: "fa fa-times-circle",
        warning: "fa fa-exclamation-triangle",
        success: "fa fa-check-circle",
        info: "fa fa-info-circle"
    }
};

function esActividadManual(id, tipo) {
    return parseInt(id, 10) === 0 || String(tipo).toLowerCase() === 'manual';
}

function obtenerFormDataCurso(corte) {
    var formData = new FormData();
    formData.append("course_id", courseId);
    formData.append("cod_profesor", codProfesor);
    formData.append("cod_materia", codMateria);
    formData.append("grupo", grupo);
    formData.append("semestre", semestre);
    formData.append("tipo_previo", corte);
    return formData;
}

document.addEventListener("DOMContentLoaded", function() {

    var selectCorte = document.getElementById("select_corte");
    if (selectCorte) {
        selectCorte.addEventListener("change", function() {
            var urlBase = window.RUBRICA_CONFIG.urls.irAConfigurar;
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

    // Inicializar carga de cursos con rúbricas disponibles para reutilización
    cargarCursosConRubricas();

    var selectCursoOrigen = document.getElementById("select_curso_origen");
    var selectCorteOrigen = document.getElementById("select_corte_origen");
    var btnAplicarImportada = document.getElementById("btn_aplicar_rubrica_importada");

    var nombresCortes = {
        "1": "Primer Previo",
        "2": "Segundo Previo",
        "3": "Tercer Previo",
        "FINAL": "Examen Final"
    };

    if (selectCursoOrigen) {
        selectCursoOrigen.addEventListener("change", function() {
            var val = this.value;
            if (!val) {
                selectCorteOrigen.innerHTML = '<option value="">-- Seleccione curso primero --</option>';
                selectCorteOrigen.disabled = true;
                if (btnAplicarImportada) btnAplicarImportada.disabled = true;
                return;
            }

            var idx = this.options[this.selectedIndex].getAttribute("data-index");
            var cursoObj = cursosConRubricasData[idx];
            if (!cursoObj || !cursoObj.cortes || cursoObj.cortes.length === 0) {
                selectCorteOrigen.innerHTML = '<option value="">-- Sin cortes configurados --</option>';
                selectCorteOrigen.disabled = true;
                if (btnAplicarImportada) btnAplicarImportada.disabled = true;
                return;
            }

            var corteActual = document.getElementById("select_corte").value;
            var esMismoCurso = (cursoObj.cod_materia === codMateria && cursoObj.grupo === grupo && cursoObj.semestre === semestre);

            var corteHtml = '<option value="">-- Seleccionar corte evaluativo --</option>';
            var cortesValidos = 0;

            for (var k = 0; k < cursoObj.cortes.length; k++) {
                var cInfo = cursoObj.cortes[k];
                var cClave = cInfo.tipo_previo;
                var cNom = nombresCortes[cClave] || ("Corte " + cClave);
                var cItems = cInfo.total_items ? (" (" + cInfo.total_items + " act.)") : "";

                var esMismoCorteActual = (esMismoCurso && cClave === corteActual);
                if (esMismoCorteActual) {
                    corteHtml += '<option value="' + cClave + '" disabled>' + cNom + cItems + ' (Corte actual en edición)</option>';
                } else {
                    corteHtml += '<option value="' + cClave + '">' + cNom + cItems + '</option>';
                    cortesValidos++;
                }
            }

            selectCorteOrigen.innerHTML = corteHtml;
            selectCorteOrigen.disabled = (cortesValidos === 0 || corteCalificado);
            if (btnAplicarImportada) btnAplicarImportada.disabled = true;
        });
    }

    if (selectCorteOrigen) {
        selectCorteOrigen.addEventListener("change", function() {
            var corteVal = this.value;
            if (btnAplicarImportada) {
                btnAplicarImportada.disabled = (!corteVal || corteCalificado);
            }
        });
    }

    if (btnAplicarImportada && !corteCalificado) {
        btnAplicarImportada.addEventListener("click", function() {
            aplicarRubricaImportada();
        });
    }

    if (rubricaGuardada && rubricaGuardada.length > 0) {
        for (var i = 0; i < rubricaGuardada.length; i++) {
            var r = rubricaGuardada[i];
            var esMan = esActividadManual(r.ID_ACTIVIDAD_MOODLE, r.TIPO_ACTIVIDAD);
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

function normalizarTexto(texto) {
    if (!texto) return "";
    return texto
        .toString()
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

function cargarCursosConRubricas() {
    var selectCurso = document.getElementById("select_curso_origen");
    if (!selectCurso) return;

    var url = window.RUBRICA_CONFIG.urls.listarCursos;
    fetch(url)
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data || !data.exito || !data.cursos || data.cursos.length === 0) {
            selectCurso.innerHTML = '<option value="">-- No se encontraron cursos con rúbricas registradas --</option>';
            return;
        }

        cursosConRubricasData = data.cursos;
        var html = '<option value="">-- Seleccionar curso origen --</option>';

        for (var i = 0; i < data.cursos.length; i++) {
            var c = data.cursos[i];
            var esMismoCurso = (c.cod_materia === codMateria && c.grupo === grupo && c.semestre === semestre);
            var etiqueta = escapeHtml(c.etiqueta);
            if (esMismoCurso) {
                etiqueta += " (Este Curso)";
            }
            html += '<option value="' + escapeHtml(c.clave) + '" data-index="' + i + '">' + etiqueta + '</option>';
        }

        selectCurso.innerHTML = html;
        selectCurso.disabled = corteCalificado ? true : false;
    })
    .catch(function(err) {
        console.error("Error al cargar cursos con rúbricas:", err);
        selectCurso.innerHTML = '<option value="">-- Error al cargar cursos del docente --</option>';
    });
}

function aplicarRubricaImportada() {
    if (corteCalificado) return;

    var selectCurso = document.getElementById("select_curso_origen");
    var selectCorte = document.getElementById("select_corte_origen");
    if (!selectCurso || !selectCorte) return;

    var claveCurso = selectCurso.value;
    var corteOrigen = selectCorte.value;

    if (!claveCurso || !corteOrigen) {
        mostrarAlertaApp("Por favor seleccione tanto el curso como el corte evaluativo de origen.", "warning", "Datos Incompletos");
        return;
    }

    var idx = selectCurso.selectedIndex;
    var optCurso = selectCurso.options[idx];
    var cursoIdx = optCurso.getAttribute("data-index");
    var cursoObj = cursosConRubricasData[cursoIdx];
    if (!cursoObj) return;

    var corteActual = document.getElementById("select_corte").value;
    var esMismoCurso = (cursoObj.cod_materia === codMateria && cursoObj.grupo === grupo && cursoObj.semestre === semestre);
    if (esMismoCurso && corteOrigen === corteActual) {
        mostrarAlertaApp("No es necesario importar el mismo corte que ya está editando actualmente.", "info", "Mismo Corte");
        return;
    }

    var tbody = document.getElementById("tbody_rubrica");
    var filasActuales = tbody ? tbody.querySelectorAll("tr").length : 0;
    var tieneDatos = false;
    if (filasActuales > 0) {
        var inputsPct = tbody.querySelectorAll(".input-porcentaje");
        for (var p = 0; p < inputsPct.length; p++) {
            if (parseFloat(inputsPct[p].value) > 0) {
                tieneDatos = true;
                break;
            }
        }
    }

    var proceder = function() {
        ejecutarImportacionRubrica(cursoObj, corteOrigen);
    };

    if (tieneDatos) {
        var textoCorteOrigen = selectCorte.options[selectCorte.selectedIndex].text;
        mostrarConfirmacionApp(
            "¿Desea cargar la rúbrica de <strong>" + escapeHtml(optCurso.text) + " (" + escapeHtml(textoCorteOrigen) + ")</strong>?<br><br><span class='text-warning'><i class='fa fa-exclamation-triangle'></i> Se reemplazarán las actividades y porcentajes actuales de este corte en el formulario.</span><br><small class='text-muted'>Los cambios solo se guardarán en la base de datos cuando haga clic en 'Guardar Rúbrica'.</small>",
            proceder,
            {
                tipo: "warning",
                titulo: "Reemplazar Rúbrica Actual",
                btnTexto: "Sí, Cargar Rúbrica",
                btnClase: "btn-warning",
                icono: "fa-arrow-circle-down"
            }
        );
    } else {
        proceder();
    }
}

function ejecutarImportacionRubrica(cursoObj, corteOrigen) {
    var btnAplicar = document.getElementById("btn_aplicar_rubrica_importada");
    if (btnAplicar) {
        btnAplicar.disabled = true;
        btnAplicar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Cargando...';
    }

    var formData = new FormData();
    formData.append("cod_profesor", codProfesor);
    formData.append("cod_materia", cursoObj.cod_materia);
    formData.append("grupo", cursoObj.grupo);
    formData.append("semestre", cursoObj.semestre);
    formData.append("tipo_previo", corteOrigen);

    fetch(window.RUBRICA_CONFIG.urls.obtenerItems, {
        method: "POST",
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (btnAplicar) {
            btnAplicar.disabled = false;
            btnAplicar.innerHTML = '<i class="fa fa-arrow-circle-down"></i> Aplicar a este corte';
        }

        if (!data || !data.exito) {
            mostrarAlertaApp((data && data.mensaje) ? data.mensaje : "Error al consultar la rúbrica origen.", "danger", "Error");
            return;
        }

        var items = data.items || [];
        if (items.length === 0) {
            mostrarAlertaApp("La rúbrica seleccionada no contiene actividades registradas.", "warning", "Sin Actividades");
            return;
        }

        // Limpiar la tabla actual
        var tbody = document.getElementById("tbody_rubrica");
        if (tbody) tbody.innerHTML = "";

        var totalItems = items.length;
        var autoCoincidentes = 0;
        var manuales = 0;
        var pendientesManual = 0;
        var idsUsados = [];

        // Auto-matching: Normalizar nombre y buscar coincidencia exacta
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var esManual = esActividadManual(item.id_actividad_moodle, item.tipo_actividad);
            var pct = parseFloat(item.porcentaje);
            var nombreOrigen = item.nombre_actividad || "";

            if (esManual) {
                manuales++;
                agregarFilaRubrica(0, pct, true, nombreOrigen);
            } else {
                var normOrigen = normalizarTexto(nombreOrigen);
                var actEncontrada = null;

                for (var j = 0; j < actividadesDisponibles.length; j++) {
                    var actDisp = actividadesDisponibles[j];
                    var actDispIdStr = String(actDisp.id);
                    if (idsUsados.indexOf(actDispIdStr) === -1 && normalizarTexto(actDisp.nombre) === normOrigen) {
                        actEncontrada = actDisp;
                        idsUsados.push(actDispIdStr);
                        break;
                    }
                }

                if (actEncontrada) {
                    autoCoincidentes++;
                    agregarFilaRubrica(actEncontrada.id, pct, false, "");
                } else {
                    pendientesManual++;
                    // Si no coincide: precargar fila con porcentaje pero selector en blanco
                    agregarFilaRubrica(null, pct, false, "");
                }
            }
        }

        actualizarOpcionesDisponibles();
        recalcularTotales();

        // Mostrar notificación y feedback descriptivo
        var feedbackDiv = document.getElementById("alerta_importacion_feedback");
        if (feedbackDiv) {
            feedbackDiv.style.display = "block";
            if (pendientesManual > 0) {
                feedbackDiv.className = "alert alert-warning";
                feedbackDiv.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' +
                    'Rúbrica cargada: <strong>' + autoCoincidentes + '</strong> actividades vinculadas automáticamente y <strong>' + pendientesManual + '</strong> requieren selección manual de la actividad en Moodle.';
            } else {
                feedbackDiv.className = "alert alert-success";
                feedbackDiv.innerHTML = '<i class="fa fa-check-circle"></i> ' +
                    'Rúbrica cargada con éxito: las <strong>' + totalItems + '</strong> actividades fueron vinculadas al 100%.';
            }
        }

        var detalleMsg = "Se cargó la rúbrica con un total de <strong>" + totalItems + "</strong> actividad(es):<br><ul style='margin-top: 8px;'>";
        if (autoCoincidentes > 0) {
            detalleMsg += "<li><strong style='color: #00a65a;'>" + autoCoincidentes + "</strong> actividad(es) vinculada(s) automáticamente por nombre.</li>";
        }
        if (pendientesManual > 0) {
            detalleMsg += "<li><strong style='color: #e08e0b;'>" + pendientesManual + "</strong> actividad(es) con porcentaje cargado pero pendientes por seleccionar en el menú desplegable.</li>";
        }
        if (manuales > 0) {
            detalleMsg += "<li><strong style='color: #0073b7;'>" + manuales + "</strong> actividad(es) manual(es).</li>";
        }
        detalleMsg += "</ul>";
        detalleMsg += "<small class='text-muted'>Revise la tabla y haga clic en <strong>'Guardar Rúbrica'</strong> para almacenar los cambios en la base de datos.</small>";

        mostrarAlertaApp(detalleMsg, (pendientesManual > 0 ? "warning" : "success"), "Rúbrica Importada");
    })
    .catch(function(err) {
        console.error("Error al obtener ítems de la rúbrica:", err);
        if (btnAplicar) {
            btnAplicar.disabled = false;
            btnAplicar.innerHTML = '<i class="fa fa-arrow-circle-down"></i> Aplicar a este corte';
        }
        mostrarAlertaApp("Error de comunicación al intentar cargar la rúbrica: " + err.message, "danger", "Error");
    });
}

function irAConfigurarCorte(corte) {
    var selectCorte = document.getElementById("select_corte");
    if (selectCorte && selectCorte.value === String(corte)) {
        var tabLink = document.querySelector('a[href="#tab_configurar"]');
        if (tabLink) tabLink.click();
    } else {
        var urlBase = window.RUBRICA_CONFIG.urls.irAConfigurar;
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

    var tfootTotal = document.getElementById("tfoot_total_porcentaje");
    var btnGuardar = document.getElementById("btn_guardar_rubrica");
    var barraProgreso = document.getElementById("barra_progreso_porcentaje");
    var badgeProgreso = document.getElementById("badge_progreso_porcentaje");

    // Actualizar barra de progreso en tiempo real
    if (barraProgreso) {
        var pctAncho = Math.min(Math.max(total, 0), 100);
        barraProgreso.style.width = pctAncho + "%";
        barraProgreso.textContent = total + "%";

        if (total === 100) {
            barraProgreso.className = "progress-bar progress-bar-success";
            if (badgeProgreso) {
                badgeProgreso.className = "label label-success";
                badgeProgreso.textContent = "100% Completo";
            }
        } else if (total > 100) {
            barraProgreso.className = "progress-bar progress-bar-danger";
            if (badgeProgreso) {
                badgeProgreso.className = "label label-danger";
                badgeProgreso.textContent = total + "% (Excedido)";
            }
        } else {
            barraProgreso.className = "progress-bar progress-bar-yellow";
            if (badgeProgreso) {
                badgeProgreso.className = "label label-warning";
                badgeProgreso.textContent = total + "% (Incompleto)";
            }
        }
    }

    // Actualizar el total ponderado en el pie de la tabla
    if (tfootTotal) {
        tfootTotal.textContent = total + "%";
        if (total === 100) {
            tfootTotal.style.color = "#00a65a";
        } else if (total > 100) {
            tfootTotal.style.color = "#dd4b39";
        } else {
            tfootTotal.style.color = "#f39c12";
        }
    }

    // Habilitar / deshabilitar Guardar Rúbrica: corte abierto, suma exacta de 100% y datos completos
    var ponderacionCompleta = (total === 100 && completas === rows.length && rows.length > 0);
    if (btnGuardar) {
        btnGuardar.disabled = corteCalificado || !ponderacionCompleta;
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

    var formData = obtenerFormDataCurso(corte);
    formData.append("items", JSON.stringify(items));

    fetch(window.RUBRICA_CONFIG.urls.guardar, {
        method: "POST",
        body: formData
    })
    .then(function(res) {
        return res.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Respuesta no JSON del servidor:", text);
                throw new Error("Respuesta inválida del servidor: " + (text.substring(0, 100)));
            }
        });
    })
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
            btnGuardar.innerHTML = '<i class="fa fa-save"></i> Guardar Rúbrica';
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
    })
    .catch(function(err) {
        console.error("Error al guardar rúbrica:", err);
        var msgDiv = document.getElementById("mensaje_ajax");
        msgDiv.style.display = "block";
        msgDiv.className = "alert alert-danger";
        msgDiv.innerHTML = '<i class="fa fa-times"></i> Error al procesar la solicitud.';
        mostrarAlertaApp("Error al comunicarse con el servidor: " + err.message, "danger", "Error");

        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fa fa-save"></i> Guardar Rúbrica';
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
    var formData = obtenerFormDataCurso(corte);

    fetch(window.RUBRICA_CONFIG.urls.eliminar, {
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
    var formData = obtenerFormDataCurso(corte);

    fetch(window.RUBRICA_CONFIG.urls.desbloquear, {
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

    if (header) header.style.backgroundColor = MODAL_THEMES.bgColors[tipo] || "#3c8dbc";
    if (titleTxt) titleTxt.innerText = titulo;
    if (icon) icon.className = MODAL_THEMES.icons[tipo] || "fa fa-info-circle";
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

    if (header) header.style.backgroundColor = MODAL_THEMES.bgColors[tipo] || "#3c8dbc";
    if (titleTxt) titleTxt.innerText = titulo;
    if (icon) icon.className = (opciones.icono ? "fa " + iconoClase : (MODAL_THEMES.icons[tipo] || "fa fa-info-circle"));
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
