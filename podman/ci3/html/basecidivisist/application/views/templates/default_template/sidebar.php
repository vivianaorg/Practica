<aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <?php if (isset($informacion_usuario->LLAVE_IMAGEN)): ?>
                    <img src="<?php echo is_readable("public/imagenes/{$informacion_usuario->LLAVE_IMAGEN}.JPEG") ? base_url("public/imagenes/{$informacion_usuario->LLAVE_IMAGEN}.JPEG") : base_url("public/imagenes/foto_default.JPEG"); ?>" class="img-circle" alt="User Image" />
                <?php else: ?>
                    <img src="<?php echo base_url("public/imagenes/foto_default.JPEG"); ?>" class="img-circle" alt="User Image" />
                <?php endif; ?>
            </div>
            <div class="pull-left info">
                <p>
                    <?php
                    if (!empty(trim($informacion_usuario->NOMBRES))) {
                        echo ucwords($informacion_usuario->NOMBRES);
                    } elseif (isset($informacion_usuario->NOMBRE_COMPLETO)) {
                        $var = explode(" ", $informacion_usuario->NOMBRE_COMPLETO);
                        echo ucwords($var[2] . " " . $var[3]);
                    }
                    ?>
                </p>
                <!--<a href="#"><i class="fa fa-circle text-success"></i> Online</a>-->
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="header">NAVEGACION PRINCIPAL</li>
            <li class="<?php echo (in_array($item_sidebar_active, array("dashboard", "nevegacion2", "nevegacion3", "nevegacion4", "actividades_moodle"))) ? "active" : ""; ?> treeview">
                <a href="#">
                    <i class="fa fa-info-circle"></i> <span>Grupo de enlaces</span> <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="hvr-icon-back2 <?php echo ($item_sidebar_active == "dashboard") ? "active" : ""; ?>">
                        <a class="no-padding-left" href="<?php echo site_url('dashboard'); ?>"> Dashboard </a>
                    </li>
                    <li class="hvr-icon-back2 <?php echo ($item_sidebar_active == "nevegacion2") ? "active" : ""; ?>">
                        <a href="<?php echo site_url('dashboard/item2'); ?>"> Item navegación 2 </a>
                    </li>
                    <li class="hvr-icon-back2 <?php echo ($item_sidebar_active == "nevegacion3") ? "active" : ""; ?>">
                        <a href="<?php echo site_url('dashboard/item3'); ?>"> Item navegación 3</a>
                    </li>
                    <li class="hvr-icon-back2 <?php echo ($item_sidebar_active == "nevegacion4") ? "active" : ""; ?>">
                        <a href="<?php echo site_url('dashboard/item4'); ?>"> Item navegación 4 <small class="label pull-right bg-green">nuevo!</small> </a>
                    </li>
                    <li class="hvr-icon-back2 <?php echo ($item_sidebar_active == "actividades_moodle") ? "active" : ""; ?>">
                        <a href="<?php echo site_url('moodle'); ?>"> Cursos Moodle <small class="label pull-right bg-blue">NPLad</small> </a>
                    </li>
                </ul>
            </li>            
            <li class="<?php echo (in_array($item_sidebar_active, array("notification"))) ? "active" : ""; ?>">
                <a href="<?php echo site_url('notification'); ?>">
                    <i class="fa fa-bell"></i><span>Notificaciones</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- /.sidebar -->
</aside>


