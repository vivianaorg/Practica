<div class="box box-primary">
    <div class="box-body">

        <?php if (!$exito): ?>
            <div class="alert alert-danger">
                Error al consultar Moodle: <?php echo htmlspecialchars(isset($mensaje_moodle) ? $mensaje_moodle : 'Error desconocido'); ?>
            </div>
        <?php elseif (empty($cursos)): ?>
            <p>No hay cursos registrados en Moodle todavía.</p>
        <?php else: ?>

            <div class="row">
                <?php foreach ($cursos as $curso): ?>
                    <div class="col-md-4">
                        <div class="small-box bg-aqua">
                            <div class="inner">
                                <h4><?php echo htmlspecialchars($curso['nombre']); ?></h4>
                                <p><?php echo htmlspecialchars($curso['codigo']); ?></p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-book"></i>
                            </div>
                            <a href="<?php echo site_url('dashboard/actividades_moodle/' . $curso['id']); ?>" class="small-box-footer">
                                Ver actividades <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>