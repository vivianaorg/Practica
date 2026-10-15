<!-- CONSULTA DE GRUPOS DE ASIGNATURAS ASIGNADAS AL DOCENTE -->
<?php if (isset($grupos_asignados) && isset($grupos_asignados['CODIGO']) && count($grupos_asignados['CODIGO']) > 0): ?>

    <?php if (isset($presencial) && isset($presencial['CODIGO']) && count($presencial['CODIGO']) != 0): ?>
        <div class="box box-primary">
            <div class="box-header with-border">
                <i class="fa fa-check-square"></i>
                <h2 class="box-title">Grupos Asignados - Presencial</h2>
            </div>
            
            <div class="box-body table-responsive no-padding">
                <table class='table table-hover table-striped table-bordered table-condensed'>
                    <tbody>
                    <tr>
                        <th width='10%'>Código</th>
                        <th width='40%'>Nombre</th>
                        <th class='td_center'>Principal</th>
                        <th class='td_center'>Nro. Alumnos</th>
                        <th class='td_center'>Seccional</th>
                        <th class='td_center'>Cargar Notas</th>
                    </tr>

                    <?php $numFilas = count($presencial['CODIGO']);?>
                    <?php for ($i = 0; $i < $numFilas; $i++): ?>
                        <tr>
                            <td><?php echo((isset($presencial['CODIGO'][$i])) ? $presencial['CODIGO'][$i] : '-'); ?></td>
                            <td><?php echo((isset($presencial['NOMBRE'][$i])) ? $presencial['NOMBRE'][$i] : '-'); ?></td>
                            <td class='td_center'><?php echo((isset($presencial['ROL_PROFESOR'][$i])) ? (($presencial['ROL_PROFESOR'][$i] == 'PRINCIPAL')?'X':'') : '-'); ?></td>
                            <td class='td_center'><?php echo((isset($presencial['NUM_ALUM_MATRICULADOS'][$i])) ? $presencial['NUM_ALUM_MATRICULADOS'][$i] : '-'); ?></td>
                            <td class='td_center'><?php echo((isset($presencial['NOMSECCIONAL'][$i])) ? $presencial['NOMSECCIONAL'][$i] : '-'); ?></td>
                            <?php $numNotas = (isset($carga_notas_P[$i]['TIPO']) && is_array($carga_notas_P[$i]['TIPO'])) ? count($carga_notas_P[$i]['TIPO']) : 0; ?>
                            <?php if (($numNotas > 0) && (isset($presencial['ROL_PROFESOR'][$i]) && $presencial['ROL_PROFESOR'][$i] == 'PRINCIPAL')): ?>
                                <td class='td_center no-padding'>
                                    <table class="table table-bordered table-condensed no-margin">
                                        <tbody>
                                        <tr>
                                            <?php for ($j = 0; $j < $numNotas; $j++): ?>
                                                <?php
                                                $validado = isset($carga_notas_P[$i]['VALIDADO'][$j]) ? $carga_notas_P[$i]['VALIDADO'][$j] : '';
                                                switch ($validado):
                                                    case 'S' :
                                                        ?>
                                                        <th class='td_center'>
                                                            <button id="entregada<?php echo $i; ?>"
                                                                    class="btn btn-default btn-xs" type="button">
                                                                <span class="visible-xs">
                                                                    <i class="fa fa-list-alt"></i> Informe Generado
                                                                </span>
                                                                <span class="hidden-xs">
                                                                    <i class="fa fa-list-alt"></i> Informe Generado
                                                                </span>
                                                            </button>
                                                        </th>
                                                        <?php
                                                        break;
                                                    default :
                                                        $orden = isset($carga_notas_P[$i]['ORDEN'][$j]) ? (int)$carga_notas_P[$i]['ORDEN'][$j] : 0;
                                                        ?>
                                                        <?php if ($orden >= 3): ?>
                                                            <?php if (isset($autoevaluado) && $autoevaluado == 1): ?>
                                                                <?php
                                                                $llaveData = (isset($presencial['TIPO_CARRERA'][$i]) ? $presencial['TIPO_CARRERA'][$i] : '') . "_" .
                                                                    (isset($presencial['NUM_ALUM_MATRICULADOS'][$i]) ? $presencial['NUM_ALUM_MATRICULADOS'][$i] : '') . "_" .
                                                                    (isset($presencial['SECCIONAL'][$i]) ? $presencial['SECCIONAL'][$i] : '') . "_" .
                                                                    (isset($presencial['CODIGO'][$i]) ? $presencial['CODIGO'][$i] : '') . "_" .
                                                                    (isset($presencial['NOMSECCIONAL'][$i]) ? $presencial['NOMSECCIONAL'][$i] : '') . "_" .
                                                                    (isset($presencial['NOMBRE'][$i]) ? $presencial['NOMBRE'][$i] : '') . "_" .
                                                                    (isset($carga_notas_P[$i]['TIPO'][$j]) ? $carga_notas_P[$i]['TIPO'][$j] : '') . "_" .
                                                                    (isset($carga_notas_P[$i]['DESCRIPCION'][$j]) ? $carga_notas_P[$i]['DESCRIPCION'][$j] : '') . "_" .
                                                                    (isset($carga_notas_P[$i]['LET'][$j]) ? $carga_notas_P[$i]['LET'][$j] : '');
                                                                echo form_open('', [
                                                                    'id' => ('formulario_registro' . $j),
                                                                    'name' => ('formulario_registro' . $j),
                                                                    'role' => 'form'], ['registrar_notas' => 1, 'llave' => (isset($this->encryption) && is_object($this->encryption) ? @$this->encryption->encrypt($llaveData) ?: base64_encode($llaveData) : base64_encode($llaveData))]);
                                                                ?>
                                                                <th class='td_center'>
                                                                    <button id="listado<?php echo $i; ?>"
                                                                            class="btn btn-primary btn-xs" type="submit"
                                                                            data-toggle="tooltip" data-placement="top"
                                                                            data-original-title="<?php echo((isset($carga_notas_P[$i]['LET'][$j])) ? $carga_notas_P[$i]['LET'][$j] : '-'); ?>">
                                                                        <span class="visible-xs">
                                                                            <i class="fa fa-check-square"></i> <?php echo((isset($carga_notas_P[$i]['TEXTO'][$j])) ? $carga_notas_P[$i]['TEXTO'][$j] : '-'); ?>
                                                                        </span>
                                                                        <span class="hidden-xs">
                                                                            <i class="fa fa-check-square"></i> <?php echo((isset($carga_notas_P[$i]['TEXTO'][$j])) ? $carga_notas_P[$i]['TEXTO'][$j] : '-'); ?>
                                                                        </span>
                                                                    </button>
                                                                </th>
                                                                <?php echo form_close(); ?>
                                                            <?php else: ?>
                                                                <?php
                                                                echo form_open('autoevaluacion/evaluar', [
                                                                    'id' => ('formulario_autoevaluacion' . $j),
                                                                    'name' => ('formulario_autoevaluacion' . $j),
                                                                    'role' => 'form']);
                                                                ?>
                                                                <th class='td_center'>
                                                                    <button id="listado<?php echo $i; ?>"
                                                                            class="btn btn-warning btn-xs" type="submit">
                                                                        <span class="visible-xs">
                                                                            <i class="fa fa-pencil-square"></i> EVA.
                                                                        </span>
                                                                        <span class="hidden-xs">
                                                                            <i class="fa fa-pencil-square"></i> AUTOEVALUACIÓN
                                                                        </span>
                                                                    </button>
                                                                </th>
                                                                <?php echo form_close(); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php
                                                            $llaveData = (isset($presencial['TIPO_CARRERA'][$i]) ? $presencial['TIPO_CARRERA'][$i] : '') . "_" .
                                                                (isset($presencial['NUM_ALUM_MATRICULADOS'][$i]) ? $presencial['NUM_ALUM_MATRICULADOS'][$i] : '') . "_" .
                                                                (isset($presencial['SECCIONAL'][$i]) ? $presencial['SECCIONAL'][$i] : '') . "_" .
                                                                (isset($presencial['CODIGO'][$i]) ? $presencial['CODIGO'][$i] : '') . "_" .
                                                                (isset($presencial['NOMSECCIONAL'][$i]) ? $presencial['NOMSECCIONAL'][$i] : '') . "_" .
                                                                (isset($presencial['NOMBRE'][$i]) ? $presencial['NOMBRE'][$i] : '') . "_" .
                                                                (isset($carga_notas_P[$i]['TIPO'][$j]) ? $carga_notas_P[$i]['TIPO'][$j] : '') . "_" .
                                                                (isset($carga_notas_P[$i]['DESCRIPCION'][$j]) ? $carga_notas_P[$i]['DESCRIPCION'][$j] : '') . "_" .
                                                                (isset($carga_notas_P[$i]['LET'][$j]) ? $carga_notas_P[$i]['LET'][$j] : '');
                                                            echo form_open('', [
                                                                'id' => ('formulario_registro' . $j),
                                                                'name' => ('formulario_registro' . $j),
                                                                'role' => 'form'], ['registrar_notas' => 1, 'llave' => (isset($this->encryption) && is_object($this->encryption) ? @$this->encryption->encrypt($llaveData) ?: base64_encode($llaveData) : base64_encode($llaveData))]);
                                                            ?>
                                                            <th class='td_center'>
                                                                <button id="listado<?php echo $i; ?>"
                                                                        class="btn btn-primary btn-xs" type="submit"
                                                                        data-toggle="tooltip" data-placement="top"
                                                                        data-original-title="<?php echo((isset($carga_notas_P[$i]['LET'][$j])) ? $carga_notas_P[$i]['LET'][$j] : '-'); ?>">
                                                                    <span class="visible-xs">
                                                                        <i class="fa fa-check-square"></i> <?php echo((isset($carga_notas_P[$i]['TEXTO'][$j])) ? $carga_notas_P[$i]['TEXTO'][$j] : '-'); ?>
                                                                    </span>
                                                                    <span class="hidden-xs">
                                                                        <i class="fa fa-check-square"></i> <?php echo((isset($carga_notas_P[$i]['TEXTO'][$j])) ? $carga_notas_P[$i]['TEXTO'][$j] : '-'); ?>
                                                                    </span>
                                                                </button>
                                                            </th>
                                                            <?php echo form_close(); ?>
                                                        <?php endif; ?>
                                                <?php endswitch; ?>
                                            <?php endfor; ?>

                                            <?php $numNotasAuto = (isset($carga_notas_autorizada[$i]['TIPO']) && is_array($carga_notas_autorizada[$i]['TIPO'])) ? count($carga_notas_autorizada[$i]['TIPO']) : 0; ?>
                                            <?php for ($j = 0; $j < $numNotasAuto; $j++): ?>
                                                <?php if (isset($carga_notas_autorizada[$i]['NUM_ALUM_MATRICULADOS'][$j]) && $carga_notas_autorizada[$i]['NUM_ALUM_MATRICULADOS'][$j] > 0): ?>
                                                    <?php
                                                    $llaveAuto = (isset($presencial['TIPO_CARRERA'][$i]) ? $presencial['TIPO_CARRERA'][$i] : '') . "_" .
                                                        (isset($presencial['NUM_ALUM_MATRICULADOS'][$i]) ? $presencial['NUM_ALUM_MATRICULADOS'][$i] : '') . "_" .
                                                        (isset($presencial['SECCIONAL'][$i]) ? $presencial['SECCIONAL'][$i] : '') . "_" .
                                                        (isset($presencial['CODIGO'][$i]) ? $presencial['CODIGO'][$i] : '') . "_" .
                                                        (isset($presencial['NOMSECCIONAL'][$i]) ? $presencial['NOMSECCIONAL'][$i] : '') . "_" .
                                                        (isset($presencial['NOMBRE'][$i]) ? $presencial['NOMBRE'][$i] : '') . "_" .
                                                        (isset($carga_notas_autorizada[$i]['TIPO'][$j]) ? $carga_notas_autorizada[$i]['TIPO'][$j] : '') . "_" .
                                                        (isset($carga_notas_autorizada[$i]['DESCRIPCION'][$j]) ? $carga_notas_autorizada[$i]['DESCRIPCION'][$j] : '') . "_" .
                                                        (isset($carga_notas_autorizada[$i]['LET'][$j]) ? $carga_notas_autorizada[$i]['LET'][$j] : '');
                                                    echo form_open('', [
                                                        'id' => ('formulario_autorizado' . $j),
                                                        'name' => ('formulario_autorizado' . $j),
                                                        'role' => 'form'], ['registrar_notas_autorizado' => 1, 'llave' => (isset($this->encryption) && is_object($this->encryption) ? @$this->encryption->encrypt($llaveAuto) ?: base64_encode($llaveAuto) : base64_encode($llaveAuto))]);
                                                    ?>
                                                    <th class='td_center'>
                                                        <button id="listado<?php echo $i; ?>"
                                                                class="btn btn-primary btn-xs" type="submit">
                                                            <span class="visible-xs">
                                                                <i class="fa fa-check-square"></i> <?php echo((isset($carga_notas_autorizada[$i]['TEXTO'][$j])) ? $carga_notas_autorizada[$i]['TEXTO'][$j] : '-'); ?>
                                                            </span>
                                                            <span class="hidden-xs">
                                                                <i class="fa fa-check-square"></i> <?php echo((isset($carga_notas_autorizada[$i]['TEXTO'][$j])) ? $carga_notas_autorizada[$i]['TEXTO'][$j] : '-'); ?>
                                                            </span>
                                                        </button>
                                                    </th>
                                                    <?php echo form_close(); ?>
                                                <?php endif; ?>
                                            <?php endfor; ?>

                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            <?php else: ?>
                                <td class='td_center'>-</td>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="box box-warning">
            <div class="box-body">
                <p class="text-muted" style="margin: 10px;">No cuenta con grupos presenciales asignados en este periodo.</p>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-info-circle"></i> Grupos Asignados</h3>
        </div>
        <div class="box-body">
            <p style="padding: 15px;">No se encontraron asignaturas ni grupos asignados para el docente.</p>
        </div>
    </div>
<?php endif; ?>

<script>
$(document).ready(function() {
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
});
</script>
