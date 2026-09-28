<nav class="navbar navbar-static-top" role="navigation">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
    </a>
    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
            <?php // $count_unread = count($unread_notifications); ?>
            <?php $count_unread = 0; ?>
            <!--Notifications: style can be found in dropdown.less--> 
            <li class="dropdown notifications-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-bell-o <?php echo $count_unread ? 'hvr-buzz-permanent' : ''; ?>"></i>
                    <span class="label <?php echo $count_unread ? "label-warning" : "label-default"; ?>">
                        <?php echo $count_unread; ?>
                    </span>
                </a>
                <ul class="dropdown-menu">
                    <li class="header"><?php echo ($count_unread ? ($count_unread > 1 ? "Tienes {$count_unread} notificaciones nuevas" : "Tienes una nueva notificación") : "No tienes notificaciones nuevas"); ?></li>
                    <?php if ($count_unread): ?>
                        <li>
                            <ul class="menu">
                                <?php foreach ($unread_notifications as $key => $value): ?>
                                    <li>
                                        <a href="<?php echo base_url('notification'); ?>">
                                            <i class="fa fa-info-circle text-aqua"></i> <?php echo $value->EMISOR; ?>
                                        </a>
                                    </li>     
                                <?php endforeach; ?>                                                  
                            </ul>
                        </li>
                    <?php else: ?>
                        <div class="clearfix text-center text-muted">
                            <i class="fa fa-5x fa-check-circle-o"></i>                            
                        </div>
                    <?php endif; ?>                    
                    <li class="footer">
                        <a href="<?php echo base_url('notification'); ?>">
                            <span class="label label-default">Ver todas</span>
                        </a>
                    </li>
                </ul>
            </li>          

            <!-- User Account: style can be found in dropdown.less -->
            <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <?php if (isset($informacion_usuario->LLAVE_IMAGEN)): ?>
                        <img src="<?php echo is_readable("public/imagenes/{$informacion_usuario->LLAVE_IMAGEN}.JPEG") ? base_url("public/imagenes/{$informacion_usuario->LLAVE_IMAGEN}.JPEG") : base_url("public/imagenes/foto_default.JPEG"); ?>" class="user-image" alt="User Image"/>
                    <?php else: ?>
                        <img src="<?php echo base_url("public/imagenes/foto_default.JPEG"); ?>" class="user-image" alt="User Image" />
                    <?php endif; ?>
                    <span class="hidden-xs">
                        <?php
                        if (!empty(trim($informacion_usuario->NOMBRES))) {
                            echo ucwords($informacion_usuario->NOMBRES);
                        } elseif (isset($informacion_usuario->NOMBRE_COMPLETO)) {
                            $var = explode(" ", $informacion_usuario->NOMBRE_COMPLETO);
                            echo ucwords($var[2] . " " . $var[3]);
                        }
                        ?>
                    </span>
                </a>
                <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header">
                        <?php if (isset($informacion_usuario->LLAVE_IMAGEN)): ?>
                            <img src="<?php echo is_readable("public/imagenes/{$informacion_usuario->LLAVE_IMAGEN}.JPEG") ? base_url("public/imagenes/{$informacion_usuario->LLAVE_IMAGEN}.JPEG") : base_url("public/imagenes/foto_default.JPEG"); ?>" class="img-circle" alt="User Image" />
                        <?php else: ?>
                            <img src="<?php echo base_url("public/imagenes/foto_default.JPEG"); ?>" class="img-circle" alt="User Image" />
                        <?php endif; ?>
                        <p>
                            <?php
                            if (!empty(trim($informacion_usuario->NOMBRES))) {
                                echo ucwords($informacion_usuario->NOMBRES);
                            } elseif (isset($informacion_usuario->NOMBRE_COMPLETO)) {
                                $var = explode(" ", $informacion_usuario->NOMBRE_COMPLETO);
                                echo ucwords($var[2] . " " . $var[3]);
                            }
                            ?>
                            <small>Alumno UFPS</small>
                        </p>
                    </li>
                    <!-- Menu Body -->
                    <!--                    <li class="user-body">
                                            <div class="col-xs-4 text-center">
                                                <a href="#">Item de Menu</a>
                                            </div>                        
                                        </li>-->
                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <div class="pull-right">
                            <a href="<?php echo site_url('sesion/logout'); ?>" class="btn btn-default btn-flat"><i class="fa fa-power-off"></i> Cerrar Sesión</a>
                        </div>
                    </li>
                </ul>
            </li>
            <!-- Control Sidebar Toggle Button -->
            <li>
                <a href="<?php echo site_url('sesion/logout'); ?>"><i class="fa fa-power-off"></i></a>
            </li>
        </ul>
    </div>
</nav>