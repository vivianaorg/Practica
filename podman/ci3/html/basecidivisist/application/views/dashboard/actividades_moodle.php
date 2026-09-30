<div class="box box-primary">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-6">
                <h3 class="box-title" style="font-weight: 600;">
                    <i class="fa fa-list text-primary"></i> <?php echo htmlspecialchars(isset($curso_nombre) ? $curso_nombre : 'Curso'); ?>
                </h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?php echo site_url('dashboard/cursos_moodle'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Volver a Cursos
                </a>
                <a href="<?php echo site_url('dashboard/configurar_rubrica/' . $course_id); ?>" class="btn btn-info btn-sm" style="margin-left: 5px;">
                    <i class="fa fa-sliders"></i> Rúbrica
                </a>
                <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $course_id); ?>" class="btn btn-success btn-sm" style="margin-left: 5px; font-weight: bold;">
                    <i class="fa fa-calculator"></i> Calificar
                </a>
            </div>
        </div>
    </div>
    <div class="box-body">

        <?php if (!$exito): ?>
            <div class="alert alert-danger">
                Error al consultar Moodle: <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>
            </div>
        <?php else: ?>

            <?php if (empty($actividades)): ?>
                <p>Este curso no tiene actividades registradas.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-gray-light">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Nombre</th>
                                <th style="width: 150px; text-align: center;">Tipo</th>
                                <th style="width: 120px; text-align: center;">Sección</th>
                                <th style="width: 100px; text-align: center;">Visible</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $idx => $actividad): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo ($idx + 1); ?></td>
                                    <td>
                                        <i class="fa fa-circle-o text-blue" style="font-size: 11px;"></i> 
                                        <strong><?php echo htmlspecialchars($actividad['nombre']); ?></strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="label label-info">
                                            <?php echo htmlspecialchars($actividad['tipo']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($actividad['seccion']); ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($actividad['visible']): ?>
                                            <span class="label label-success">Sí</span>
                                        <?php else: ?>
                                            <span class="label label-default">No</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>