<?php
/**
 * Componente reutilizable: Barra de navegación del curso (Opción A - Segmentada / Unificada)
 * 
 * Parámetros esperados:
 * @var int|string $course_id ID del curso en Moodle
 * @var string     $activo    Sección activa: 'actividades' | 'rubrica' | 'calificar' | 'calificaciones'
 * @var string     $tipo_previo (opcional) Corte evaluativo actual ('1', '2', '3', 'FINAL')
 */

$seccionActiva = isset($activo) ? $activo : '';
$corteParam    = (isset($tipo_previo) && !empty($tipo_previo)) ? '/' . $tipo_previo : '';

$urlCursos         = site_url('moodle');
$urlActividades    = site_url('moodle/actividades/' . $course_id);
$urlRubrica        = site_url('calificaciones/configurar_rubrica/' . $course_id . $corteParam);
$urlCalificar      = site_url('calificaciones/calificar_rubrica/' . $course_id . $corteParam);
$urlCalificaciones = !empty($course_id) ? site_url('calificaciones/index/' . $course_id) : site_url('calificaciones');
?>

<div class="nav-curso-toolbar" style="display: inline-flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 6px;">
    <!-- Botón Volver -->
    <a href="<?php echo $urlCursos; ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: 500; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-right: 2px;">
        <i class="fa fa-arrow-left text-muted"></i> Volver a Cursos
    </a>

    <!-- Grupo de Navegación del Curso -->
    <div class="btn-group btn-group-sm" role="group" aria-label="Navegación del Curso" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <a href="<?php echo $urlActividades; ?>" 
           class="btn btn-sm <?php echo ($seccionActiva === 'actividades') ? 'btn-primary active' : 'btn-default'; ?>"
           style="<?php echo ($seccionActiva === 'actividades') ? 'font-weight: 600;' : 'color: #444;'; ?>"
           title="Ver actividades registradas en Moodle">
            <i class="fa fa-list"></i> Actividades
        </a>

        <a href="<?php echo $urlRubrica; ?>" 
           class="btn btn-sm <?php echo ($seccionActiva === 'rubrica') ? 'btn-primary active' : 'btn-default'; ?>"
           style="<?php echo ($seccionActiva === 'rubrica') ? 'font-weight: 600;' : 'color: #444;'; ?>"
           title="Configurar ponderación y rúbrica">
            <i class="fa fa-sliders"></i> Rúbrica
        </a>

        <a href="<?php echo $urlCalificar; ?>" 
           class="btn btn-sm <?php echo ($seccionActiva === 'calificar') ? 'btn-primary active' : 'btn-default'; ?>"
           style="<?php echo ($seccionActiva === 'calificar') ? 'font-weight: 600;' : 'color: #444;'; ?>"
           title="Calcular notas a partir de Moodle">
            <i class="fa fa-calculator"></i> Calificar
        </a>

        <a href="<?php echo $urlCalificaciones; ?>" 
           class="btn btn-sm <?php echo ($seccionActiva === 'calificaciones') ? 'btn-primary active' : 'btn-default'; ?>"
           style="<?php echo ($seccionActiva === 'calificaciones') ? 'font-weight: 600;' : 'color: #444;'; ?>"
           title="Planilla general de calificaciones">
            <i class="fa fa-graduation-cap"></i> Calificaciones
        </a>
    </div>
</div>
