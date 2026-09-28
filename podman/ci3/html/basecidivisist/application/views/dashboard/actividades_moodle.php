<div class="box box-primary">
    <div class="box-body">

        <?php if (!$exito): ?>
            <div class="alert alert-danger">
                Error al consultar Moodle: <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>
            </div>
        <?php else: ?>

            <h4>Curso: <?php echo htmlspecialchars(isset($curso_nombre) ? $curso_nombre : ''); ?></h4>

            <?php if (empty($actividades)): ?>
                <p>Este curso no tiene actividades registradas.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Sección</th>
                            <th>Visible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $actividad): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($actividad['nombre']); ?></td>
                                <td>
                                    <span class="label label-info">
                                        <?php echo htmlspecialchars($actividad['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($actividad['seccion']); ?></td>
                                <td>
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
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>