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
                            <div style="background: rgba(0,0,0,0.15); padding: 8px 6px; text-align: center; font-size: 11px; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 4px;">
                                <a href="<?php echo site_url('moodle/actividades/' . $curso['id']); ?>" class="btn btn-xs" style="color: #fff; background: rgba(255,255,255,0.18); font-weight: 500;">
                                    <i class="fa fa-list"></i> Actividades
                                </a>
                                <a href="<?php echo site_url('calificaciones/configurar_rubrica/' . $curso['id']); ?>" class="btn btn-xs" style="color: #fff; background: rgba(255,255,255,0.18); font-weight: 500;">
                                    <i class="fa fa-sliders"></i> Rúbrica
                                </a>
                                <a href="<?php echo site_url('calificaciones/calificar_rubrica/' . $curso['id']); ?>" class="btn btn-xs" style="color: #fff; background: rgba(255,255,255,0.18); font-weight: 500;">
                                    <i class="fa fa-calculator"></i> Calificar
                                </a>
                                <a href="<?php echo site_url('calificaciones/index/' . $curso['id']); ?>" class="btn btn-xs" style="color: #fff; background: rgba(0,0,0,0.25); font-weight: 600;">
                                    <i class="fa fa-graduation-cap"></i> Calificaciones
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>