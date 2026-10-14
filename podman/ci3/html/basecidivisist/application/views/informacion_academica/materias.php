<?php
$carrera = isset($carrera) ? $carrera : '';
?>
<div class="box box-primary">
    <div class="box-header with-border">
        <i class="fa fa-file-text"></i>
        <h2 class="box-title">Materias Matriculadas</h2>
    </div>
    <div class="box-body table-responsive no-padding">
        <?php if (isset($materiasmatriculadas['CODCARMAT']) && count($materiasmatriculadas['CODCARMAT'])) : ?>
            <table class='table table-hover table-striped table-bordered table-condensed'>
                <tbody>
                    <tr>
                        <th width='8%' class="text-center">Materia</th>
                        <th width='25%'>Nombre</th>
                        <th width='10%' class='td_center'>Ver Clases</th>
                        <th width='10%' class='td_center'>Asistencia</th>
                        <th width='10%' class='td_center'>Matriculada</th>
                        <th class='td_center'>Créditos</th>
                        <th class='td_center'>Semestre</th>
                        <th class='td_center'>1P</th>
                        <th class='td_center'>2P</th>
                        <th class='td_center'>3P</th>
                        <th class='td_center'>EX</th>
                        <th class='td_center'>HA</th>
                        <th class='td_center'>OP</th>
                        <th class='td_center'>DEF</th>
                    </tr>
                    <?php $numFilas = count($materiasmatriculadas['CODCARMAT']); ?>
                    <?php $count_def = 0; ?>
                    <?php $count_creditos = 0; ?>
                    <?php for ($i = 0; $i < $numFilas; $i++) : ?>
                        <?php $es_proyecto_grado = isset($codigo_proyecto) && ($materiasmatriculadas['CODMAT'][$i] == $codigo_proyecto); ?>
                        <?php $count_notas = 0; ?>
                        <?php $creditos = isset($materiasmatriculadas['CREDITOS'][$i]) && $materiasmatriculadas['CREDITOS'][$i] ? $materiasmatriculadas['CREDITOS'][$i] : 0; ?>
                        <?php $definitiva = isset($materiasmatriculadas['DEF'][$i]) ? floatval($materiasmatriculadas['DEF'][$i]) : 0; ?>
                        <tr>
                            <td class="text-center">
                                <?php if (isset($materiasmatriculadas['ID_MICRO'][$i])) : ?>
                                    <button type="button" class="btn btn-xs btn-default" data-toggle="modal" data-target="#myModalMicrocurriculo<?php echo ((isset($materiasmatriculadas['CODCARMAT'][$i])) ? $carrera . $materiasmatriculadas['CODMAT'][$i] : '-'); ?>">
                                        <i class="fa fa-book"></i>&nbsp; <?php echo ((isset($materiasmatriculadas['CODCARMAT'][$i])) ? $carrera . $materiasmatriculadas['CODMAT'][$i] : '-'); ?>
                                    </button>
                                    <div class="modal modal-info fade" id="myModalMicrocurriculo<?php echo ((isset($materiasmatriculadas['CODCARMAT'][$i])) ? $carrera . $materiasmatriculadas['CODMAT'][$i] : '-'); ?>" tabindex="-1" role="dialog" aria-labelledby="Actualización datos de contacto">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span></button>
                                                    <h4 class="modal-title" id="myModalLabel">
                                                        <i class="fa fa-book"></i> <?php echo ((isset($materiasmatriculadas['NOMBREMAT'][$i])) ? ('MICROCURRICULO ' . $materiasmatriculadas['NOMBREMAT'][$i]) : '-'); ?>
                                                    </h4>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="text-justify">
                                                        <?php if (isset($materiasmatriculadas['CONTENIDO'][$i])) : ?>
                                                            <div class="margin-bottom">
                                                                <b>Contenido</b>
                                                            </div>
                                                            <p>
                                                                <?php echo nl2br($materiasmatriculadas['CONTENIDO'][$i]); ?>
                                                            </p>
                                                            <hr>
                                                        <?php endif; ?>
                                                        <?php if (isset($materiasmatriculadas['BIBLIOGRAFIA'][$i])) : ?>
                                                            <div class="margin-bottom">
                                                                <b>Bibliografía</b>
                                                            </div>
                                                            <p>
                                                                <?php echo nl2br($materiasmatriculadas['BIBLIOGRAFIA'][$i]); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline pull-right" data-dismiss="modal">Cerrar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <button type="button" class="btn btn-xs btn-default disabled" data-toggle="tooltip" data-placement="top" title="Microcurriculo no cargado">
                                        <i class="fa fa-book"></i>&nbsp; <?php echo ((isset($materiasmatriculadas['CODCARMAT'][$i])) ? $carrera . $materiasmatriculadas['CODMAT'][$i] : '-'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><?php echo ((isset($materiasmatriculadas['NOMBREMAT'][$i])) ? $materiasmatriculadas['NOMBREMAT'][$i] : '-'); ?><br><?php echo ((isset($materiasmatriculadas['EMAIL'][$i])) ? $materiasmatriculadas['EMAIL'][$i] : '-'); ?></td>
                            <td class='td_center'>
                                <button type="button" class="btn btn-primary btn-xs" title="Mas información" data-toggle="modal" data-target="#myModal<?php echo $materiasmatriculadas['CODCARMAT'][$i] . $materiasmatriculadas['CODMATMAT'][$i]; ?>"> <i class="fa fa-search"></i>
                                </button>

                                <div class="modal modal-primary fade" id="myModal<?php echo $materiasmatriculadas['CODCARMAT'][$i] . $materiasmatriculadas['CODMATMAT'][$i]; ?>" tabindex="-1" role="dialog" aria-labelledby="Información de la Materia">
                                    <div class="modal-dialog modal-dm" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title">
                                                    <i class='fa fa-list'> </i> Materia: <?php echo $materiasmatriculadas['CODCARMAT'][$i] . $materiasmatriculadas['CODMATMAT'][$i] . $materiasmatriculadas['GRUPO'][$i] . " " . $materiasmatriculadas['NOMBREMAT'][$i]; ?>
                                                </h4>
                                            </div>
                                            <div class="modal-body text-justify" style="font-size: 14px;">
                                                <div class="row">
                                                    <table class='table table-hover table-striped table-bordered table-condensed'>
                                                        <tbody>
                                                            <tr>
                                                                <th style="color:#333;"><b>Fecha Inicio</b></th>
                                                                <th style="color: #333;"><b>URL de la Reunión</b></th>
                                                            </tr>
                                                            <?php $numC = isset($materiasmatriculadas['CLASES'][$i]['ID']) ? count($materiasmatriculadas['CLASES'][$i]['ID']) : 0; ?>
                                                            <?php for ($j = 0; $j < $numC; $j++) : ?>
                                                                <tr>
                                                                    <td style="color:#333;"><?php echo ((isset($materiasmatriculadas['CLASES'][$i]['FECHA_INI'][$j])) ? $materiasmatriculadas['CLASES'][$i]['FECHA_INI'][$j] : '-'); ?></td>
                                                                    <td style="color:#333;"><a href="<?php echo ((isset($materiasmatriculadas['CLASES'][$i]['URL_REUNION'][$j])) ? $materiasmatriculadas['CLASES'][$i]['URL_REUNION'][$j] : 'JavaScript:void(0);'); ?>" target="_blank"><?php echo ((isset($materiasmatriculadas['CLASES'][$i]['URL_REUNION'][$j])) ? $materiasmatriculadas['CLASES'][$i]['URL_REUNION'][$j] : '-'); ?></a></td>
                                                                </tr>
                                                            <?php endfor; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">
                                                    <i class="fa fa-close" aria-hidden="true"></i> Cerrar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align:center">
                                <button type="button" class="btn btn-primary btn-xs" onclick="asistencia(<?php echo "'".($materiasmatriculadas['CODCARMAT'][$i] . $materiasmatriculadas['CODMATMAT'][$i] .$materiasmatriculadas['GRUPO'][$i])."'"; ?>)" title="Registrar Asistencia"> <i class="fa fa-edit"></i></button>
                            </td>
                            <td class='td_center'><?php echo ((isset($materiasmatriculadas['CODMATMAT'][$i])) ? $materiasmatriculadas['CODCARMAT'][$i] . $materiasmatriculadas['CODMATMAT'][$i] . '-' . $materiasmatriculadas['GRUPO'][$i] : '-'); ?></td>
                            <td class='td_center'><?php echo ($creditos ? $creditos : '-'); ?></td>
                            <td class='td_center'><?php echo ((isset($materiasmatriculadas['SEMESTRE'][$i])) ? intval($materiasmatriculadas['SEMESTRE'][$i]) : '-'); ?></td>
                            <?php if ($es_proyecto_grado) : ?>
                                <td colspan="7" class='td_center'>
                                    <a tabindex="0" class="badge bg-gray-gradient" data-toggle="popover" data-trigger="focus" title="Proyecto de Grado" data-content="<p class = 'text-justify'>La materia <b>Proyecto de grado</b> no tiene nota y por lo tanto no afecta el promedio ponderado.</p>" data-placement="top" data-html="true">
                                        <i class="fa fa-question-circle"></i>
                                    </a>
                                </td>
                            <?php else : ?>
                                <?php $notanull = 0; ?>
                                <?php for ($j = 0; $j < 6; $j++) : $tmp_nota = ''; ?>
                                    <?php if (isset($tiponotamateria[$i]["UNICANOTA"][$j]) && $tiponotamateria[$i]["UNICANOTA"][$j] == "N") : ?>
                                        <td class='td_center'>
                                            <?php if (isset($tiponotamateria[$i]["EST"][$j]) && $tiponotamateria[$i]["EST"][$j] == "S") : ?>
                                                <?php $notanull++; ?>
                                                <span class='td_default_color'>-</span>
                                            <?php elseif (isset($tiponotamateria[$i]["EST"][$j]) && in_array($tiponotamateria[$i]["EST"][$j], array("E", "V"))) : ?>
                                                <?php 
                                                    $tipoCol = isset($tiponotamateria[$i]["TIPO"][$j]) ? $tiponotamateria[$i]["TIPO"][$j] : '';
                                                    $tmp_nota = isset($materiasmatriculadas[$tipoCol][$i]) ? number_format(floatval($materiasmatriculadas[$tipoCol][$i]), 1) : '';
                                                ?>
                                                <?php if ($j == 4 && ($tmp_nota == "0.0" || $tmp_nota === '')) : ?>
                                                    <span>-</span>
                                                <?php elseif ($tmp_nota !== '') : ?>
                                                    <span class='badge bg-<?php echo floatval($tmp_nota) >= 3 ? (floatval($tmp_nota) >= 4 ? "green" : "yellow") : "red" ?>'>
                                                        <?php echo $tmp_nota; ?>
                                                        <?php $count_notas++; ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span>-</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (isset($materiasmatriculadas['RUBRICA'][$i]) && $materiasmatriculadas['RUBRICA'][$i] && (isset($tmp_nota) && $tmp_nota !== '') && (isset($secundaria) && $secundaria)) : ?>
                                                <a tabindex="0" class="badge bg-gray-gradient" title="Descripción detallada de las notas de esta materia." onclick="rubrica('<?php echo isset($materiasmatriculadas['CODCARMAT'][$i]) ? $materiasmatriculadas['CODCARMAT'][$i] : ''; ?>','<?php echo isset($materiasmatriculadas['CODMATMAT'][$i]) ? $materiasmatriculadas['CODMATMAT'][$i] : ''; ?>','<?php echo isset($materiasmatriculadas['GRUPO'][$i]) ? $materiasmatriculadas['GRUPO'][$i] : ''; ?>','<?php echo isset($materiasmatriculadas['SECCIONAL'][$i]) ? $materiasmatriculadas['SECCIONAL'][$i] : ''; ?>','<?php echo isset($tiponotamateria[$i]['TIPO'][$j]) ? $tiponotamateria[$i]['TIPO'][$j] : ''; ?>','<?php echo $tmp_nota; ?>')">
                                                    <i class="fa fa-info"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    <?php elseif ($j == 3 && $count_notas > 2) : ?>
                                        <?php 
                                        $nota_examen_requerida3 = number_format(((3 - $definitiva) / 0.3), 1); ?>
                                        <?php if ($nota_examen_requerida3 >= 0) : ?>
                                            <?php $tmp_msj = "<p class='text-justify'>La nota mínima necesaria en el examen para aprobar la materia es {$nota_examen_requerida3} </p>"; ?>
                                        <?php else : ?>
                                            <?php $tmp_msj = "<p class='text-justify'>Cuenta con la nota mínima requerida para aprobar la materia.</p>"; ?>
                                        <?php endif; ?>

                                        <td class='td_center'>
                                            <a tabindex="0" class="badge bg-gray-gradient" data-toggle="popover" data-trigger="focus" title="Puntaje requerido" data-content="<?php echo $tmp_msj; ?>" data-placement="top" data-html="true">
                                                <i class="fa fa-question-circle"></i>
                                            </a>
                                        </td>
                                    <?php elseif ($j == 3) : ?>
                                        <?php 
                                        $tmp_msj = "<p class='text-justify'>Cuando primer, segundo y tercer previo se encuentren cargados,"
                                            . " encontrará una estimación de la nota requerida en el examen final"
                                            . " para aprobar la materia.</p>";
                                        ?>
                                        <td class='td_center'>
                                            <a tabindex="0" class="badge bg-gray-gradient" data-toggle="popover" data-trigger="focus" title="Puntaje requerido" data-content="<?php echo $tmp_msj; ?>" data-placement="top" data-html="true">
                                                <i class="fa fa-question-circle"></i>
                                            </a>
                                        </td>
                                    <?php else : ?>
                                        <td class='td_center'><span>-</span></td>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($notanull == 4) : ?>
                                    <td class='td_center'><span class='td_default_color'>-</span></td>
                                <?php else : ?>
                                    <td class='td_center'>
                                        <span class='badge bg-<?php echo $definitiva >= 3 ? ($definitiva >= 4 ? "green" : "yellow") : "red" ?>'>
                                            <?php echo number_format($definitiva, 1); ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tr>
                        <?php if (!$es_proyecto_grado) : ?>
                            <?php $count_creditos += $creditos; ?>
                            <?php $count_def += ($creditos * $definitiva); ?>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($count_creditos) : ?>
                        <tr>
                            <td colspan=12 class='text-right'>
                                Promedio ponderado del semestre actual (<?php echo $count_creditos; ?> créditos matriculados)
                                <a tabindex="0" class="badge bg-gray-gradient" data-toggle="popover" data-trigger="focus" title="Ponderado parcial" 
                                data-content="<p class = 'text-justify'>Este promedio se calcula con las notas de las materias matriculadas en el semestre actual.<br>La nota no sera definitiva hasta tanto no haya terminado el semestre.</p>" 
                                data-placement="top" 
                                data-html="true">
                                    <i class="fa fa-question-circle"></i>
                                </a>
                            </td>
                            <?php if (isset($fecha_semtre) && $fecha_semtre):?>
                                <?php $ponderado_parcial = ($count_def / $count_creditos);?>
                                <td class='td_center'>
                                    <span class="badge bg-<?php echo $ponderado_parcial >= 3 ? ($ponderado_parcial >= 4 ? "green" : "yellow") : "red" ?>">
                                        <?php echo number_format($ponderado_parcial, 2); ?>
                                    </span>
                                </td>
                            <?php else: ?>
                                <?php $ponderado_parcial = '-- --';?>
                                <td class='td_center'>
                                    <span class="badge bg-secondary">
                                        <?php echo ($ponderado_parcial); ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($creditos_cursados)) : ?>
                        <tr>
                            <td colspan=12 class='text-right'>
                                Promedio ponderado acumulado (<?php echo $creditos_cursados; ?> créditos cursados en semestres anteriores)
                                <a tabindex="0" class="badge bg-gray-gradient" data-toggle="popover" data-trigger="focus" title="Ponderado actual" 
                                data-content="<p class = 'text-justify'>Este promedio se calcula con las notas de materias vistas en semestres anteriores.</p>" 
                                data-placement="top" 
                                data-html="true">
                                    <i class="fa fa-question-circle"></i>
                                </a>
                            </td>
                            <td class='td_center'>
                                <span class="badge bg-<?php echo $promedio_actual >= 3 ? ($promedio_actual >= 4 ? "green" : "yellow") : "red" ?>">
                                    <?php echo $promedio_actual; ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($count_creditos) : ?>
                            <tr>
                                <td colspan=12 class='text-right'>
                                    Promedio ponderado acumulado a la fecha (<?php echo $creditos_cursados + $count_creditos; ?> créditos en total)
                                    <a tabindex="0" class="badge bg-gray-gradient" data-toggle="popover" data-trigger="focus" title="Ponderado total"
                                    data-content="<p class = 'text-justify'>Este promedio se calcula con las notas del semestre actual y las notas de semestres anteriores.
                                                    <br>La nota no sera definitiva hasta tanto no haya terminado el semestre.</p>"
                                    data-placement="top"
                                    data-html="true">
                                        <i class="fa fa-question-circle"></i>
                                    </a>
                                </td>
                                <td class='td_center'>
                                    <?php if (isset($fecha_semtre) && $fecha_semtre):?>
                                        <?php $promedio_total = ($promedio_actual * $creditos_cursados + $ponderado_parcial * $count_creditos) / ($count_creditos + $creditos_cursados) ?>
                                        <span class="badge bg-<?php echo $promedio_total >= 3 ? ($promedio_total >= 4 ? "green" : "yellow") : "red" ?>">
                                            <?php echo number_format($promedio_total, 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php $promedio_total = '-- -- ' ?>
                                        <span class="badge bg-secondary">
                                            <?php echo ($promedio_total); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p style="padding: 20px;">Aún no cuenta con materias matriculadas.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detalle de Rúbrica y Subnotas para Estudiante -->
<div class="modal fade" id="modalRubricaEstudiante" tabindex="-1" role="dialog" aria-labelledby="modalRubricaTitulo">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalRubricaTitulo">
                    <i class="fa fa-pie-chart"></i> Desglose de Calificaciones - Rúbrica Evaluativa
                </h4>
            </div>
            <div class="modal-body">
                <div id="modalRubricaCargando" class="text-center" style="padding: 20px;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Consultando subnotas de Moodle en Divisist...</p>
                </div>
                <div id="modalRubricaContenido" style="display:none;">
                    <div class="callout callout-info" style="margin-bottom: 15px;">
                        <h4 id="rubricaMateriaTitulo" style="margin: 0 0 5px 0;">Materia</h4>
                        <p id="rubricaCorteDetalle" style="margin: 0; font-size: 13px;">Corte evaluativo</p>
                    </div>
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr class="active">
                                <th>#</th>
                                <th>Actividad en Moodle</th>
                                <th class="text-center">Nota Obtenida</th>
                                <th class="text-center">Escala Máx.</th>
                                <th class="text-center">Porcentaje (%)</th>
                                <th class="text-center">Subnota Ponderada</th>
                            </tr>
                        </thead>
                        <tbody id="rubricaTablaCuerpo">
                            <!-- Inserción dinámica vía JS -->
                        </tbody>
                        <tfoot>
                            <tr class="info" style="font-weight: bold;">
                                <td colspan="4" class="text-right">TOTAL PONDERADO ACUMULADO:</td>
                                <td class="text-center" id="rubricaTotalPorcentaje">100%</td>
                                <td class="text-center" id="rubricaTotalSubnota">0.0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.popover) {
        $('[data-toggle="popover"]').popover();
    }
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
});

function asistencia(materia) {
    alert('Módulo de registro de asistencia: ' + materia);
}

function rubrica(codcarmat, codmatmat, grupo, seccional, tipo, tmp_nota) {
    $('#modalRubricaEstudiante').modal('show');
    $('#modalRubricaCargando').show();
    $('#modalRubricaContenido').hide();

    var mapaNombres = {
        'NOTA1': 'Primer Previo (1P)',
        '1P': 'Primer Previo (1P)',
        'NOTA2': 'Segundo Previo (2P)',
        '2P': 'Segundo Previo (2P)',
        'NOTA3': 'Tercer Previo (3P)',
        '3P': 'Tercer Previo (3P)',
        'EXA': 'Examen Final (EX)',
        'EX': 'Examen Final (EX)'
    };

    var nombreCorte = mapaNombres[tipo] || tipo;
    $('#rubricaMateriaTitulo').text('Materia: ' + codcarmat + codmatmat + ' - Grupo ' + grupo);
    $('#rubricaCorteDetalle').html('Evaluación: <b>' + nombreCorte + '</b> | Nota Registrada: <span class="badge bg-blue">' + tmp_nota + '</span>');

    $.ajax({
        url: '<?php echo site_url("informacion_academica/obtener_subnotas_rubrica"); ?>',
        type: 'POST',
        data: {
            cod_materia: codmatmat,
            grupo: grupo,
            tipo_previo: tipo
        },
        dataType: 'json',
        success: function(resp) {
            $('#modalRubricaCargando').hide();
            $('#modalRubricaContenido').show();

            var tbody = $('#rubricaTablaCuerpo');
            tbody.empty();

            if (resp && resp.exito && resp.subnotas && resp.subnotas.length > 0) {
                var sumaPorc = 0;
                var sumaSubnota = 0;

                $.each(resp.subnotas, function(idx, item) {
                    var porc = parseFloat(item.PORCENTAJE || 0);
                    var sub = parseFloat(item.SUBNOTA || 0);
                    sumaPorc += porc;
                    sumaSubnota += sub;

                    var fila = '<tr>' +
                        '<td>' + (idx + 1) + '</td>' +
                        '<td><i class="fa fa-check-square-o text-green"></i> ' + (item.NOMBRE_ACTIVIDAD || '-') + '</td>' +
                        '<td class="text-center">' + parseFloat(item.NOTA_ORIGINAL || 0).toFixed(2) + '</td>' +
                        '<td class="text-center">' + parseFloat(item.NOTA_MAXIMA || 5).toFixed(1) + '</td>' +
                        '<td class="text-center">' + porc.toFixed(1) + '%</td>' +
                        '<td class="text-center font-weight-bold"><b>' + sub.toFixed(2) + '</b></td>' +
                        '</tr>';
                    tbody.append(fila);
                });

                $('#rubricaTotalPorcentaje').text(sumaPorc.toFixed(1) + '%');
                $('#rubricaTotalSubnota').text(sumaSubnota.toFixed(2));
            } else {
                tbody.append('<tr><td colspan="6" class="text-center text-muted" style="padding:15px;">No se encontraron actividades de rúbrica registradas para este corte.</td></tr>');
                $('#rubricaTotalPorcentaje').text('-');
                $('#rubricaTotalSubnota').text(tmp_nota);
            }
        },
        error: function() {
            $('#modalRubricaCargando').hide();
            $('#modalRubricaContenido').show();
            $('#rubricaTablaCuerpo').html('<tr><td colspan="6" class="text-center text-danger" style="padding:15px;">Error al consultar el desglose de subnotas. Intente nuevamente.</td></tr>');
        }
    });
}
</script>
