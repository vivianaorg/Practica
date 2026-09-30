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
                            <div style="background: rgba(0,0,0,0.15); padding: 7px 10px; text-align: center; font-size: 11px;">
                                <a href="<?php echo site_url('dashboard/actividades_moodle/' . $curso['id']); ?>" style="color: #fff; margin-right: 8px;">
                                    Actividades <i class="fa fa-list"></i>
                                </a>
                                <a href="<?php echo site_url('dashboard/configurar_rubrica/' . $curso['id']); ?>" style="color: #fff; margin-right: 8px;">
                                    Rúbrica <i class="fa fa-sliders"></i>
                                </a>
                                <a href="<?php echo site_url('dashboard/calificar_rubrica/' . $curso['id']); ?>" style="color: #fff; margin-right: 8px;">
                                    Calificar <i class="fa fa-calculator"></i>
                                </a>
                                <a href="<?php echo site_url('dashboard/calificaciones/' . $curso['id']); ?>" style="color: #fff; font-weight: bold; background: rgba(0,0,0,0.25); padding: 3px 6px; border-radius: 3px;">
                                    Calificaciones <i class="fa fa-graduation-cap"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>