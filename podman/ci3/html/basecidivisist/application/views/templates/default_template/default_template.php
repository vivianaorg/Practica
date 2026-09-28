<!DOCTYPE html>
<html>
    <head>
        <?php if (isset($page_tittle)): ?>
            <title><?php echo $page_tittle; ?></title>
        <?php elseif (isset($content_header)): ?>
            <title><?php echo $content_header . " Divisist 2.0"; ?></title>
        <?php endif; ?>
        <meta charset="UTF-8">
        <meta name="application-name" content="Divisist2.0" lang="es">
        <meta name="Author" content="Henry Alexander Peñaranda Mora" lang="es">             
        <?php if (isset($page_keywords)): ?>
            <meta name="keywords" content="<?php echo $page_keywords; ?>"/>
        <?php endif; ?>
        <?php if (isset($page_description)): ?>
            <meta name="description" content="<?php echo $page_keywords; ?>"/>
        <?php endif; ?>
        <link href='<?php echo base_url("assets/img/ufps/favicon.ico"); ?>' rel='Shortcut icon'>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        <?php echo $_css; ?>       
    </head>
    <body class="skin-red-light sidebar-mini <?php echo isset($hide_sidebar) ? 'sidebar-collapse' : ''; ?>">
        <div class="wrapper">
            <header class="main-header">
                <!-- Logo -->
                <a href="#" class="logo">
                    <!-- mini logo for sidebar mini 50x50 pixels -->
                    <span class="logo-mini"><b>DS</b>2.0</span>
                    <!-- logo for regular state and mobile devices -->
                    <span class="logo-lg">DIVISIST <strong>2.0</strong></span>
                </a>
                <!-- Header Navbar: style can be found in header.less -->
                <?php include APPPATH . "views/templates/default_template/header_navbar.php"; ?>        
            </header>
            <!-- Left side column. contains the logo and sidebar -->
            <?php include APPPATH . "views/templates/default_template/sidebar.php"; ?>
            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">                
                <section class="content-header"> 
                    <!-- Alertas  -->
                    <div id="template_alerts">
                        <?php foreach ($_warning as $_msj): ?>
                            <div class="alert alert-warning alert-dismissible text-justify" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <i class="fa fa-exclamation-triangle"></i>&nbsp;
                                <?php echo $_msj; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($_success as $_msj): ?>
                            <div class="alert alert-success alert-dismissible text-justify" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <i class="fa fa-check-circle"></i>&nbsp;
                                <?php echo $_msj; ?>
                            </div>
                        <?php endforeach; ?> 
                        <?php foreach ($_error as $_msj): ?>
                            <div class="alert alert-danger alert-dismissible text-justify" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <i class="fa fa-times-circle"></i>&nbsp;
                                <?php echo $_msj; ?>
                            </div>
                        <?php endforeach; ?> 
                        <?php foreach ($_info as $_msj): ?>
                            <div class="alert alert-info alert-dismissible text-justify" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <i class="fa fa-info-circle"></i>&nbsp;
                                <?php echo $_msj; ?>
                            </div>
                        <?php endforeach; ?>                     
                    </div>
                    <?php if (isset($content_header)): ?>
                        <!-- Content Header (Page header) -->  
                        <h1>
                            <?php echo ((isset($content_header)) ? $content_header : ''); ?>
                            <small><?php echo ((isset($content_sub_header)) ? $content_sub_header : ''); ?></small>
                            <span class="text-muted pull-right" style="font-size: 10px;"><?php echo $fecha_actual; ?></span>
                        </h1>  
                    <?php endif; ?>
                </section>
                <!-- Main content -->
                <section class="content">                    
                    <?php foreach ($_content as $_view): ?>
                        <?php include $_view; ?>
                    <?php endforeach; ?>
                </section><!-- /.content -->
            </div><!-- /.content-wrapper -->
            <footer class="main-footer">
                <div class="pull-right hidden-xs">
                    Version <b>2.0</b>
                </div>
                Copyright &copy; 2016 - División de Sistemas UFPS. Todos los derechos reservados.
            </footer>   
        </div><!-- ./wrapper -->
        <?php echo $_js; ?> 
        <?php if (ENVIRONMENT == 'development'): ?>
            <div id="codeigniter_profiler" style="clear:both;background-color:#fff;padding:10px;border-top: 2px solid black">
                <h4><i class="fa fa-tachometer"></i> Monitor de consultas SQL</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-striped">
                        <?php $limite = count($this->session->queries); ?>
                        <?php $exec_time = 0; ?>
                        <?php for ($i = 0; $i < $limite; $i++): ?>
                            <tr>
                                <td class="info">
                                    <?php echo $this->session->query_time[$i]; ?>
                                    <?php $exec_time += $this->session->query_time[$i]; ?>
                                </td>
                                <td>
                                    <?php
                                    $highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');
                                    $val = highlight_code($this->session->queries[$i]);
                                    foreach ($highlight as $bold) {
                                        $val = str_replace($bold, '<strong>' . $bold . '</strong>', $val);
                                    }
                                    ?>
                                    <?php echo $val; ?>
                                </td>                        
                            </tr>
                        <?php endfor; ?>
                        <tr>
                            <td class="success">
                                <?php echo $exec_time; ?>
                            </td>
                            <td>
                                TIEMPO DE EJECUCIÓN TOTAL (<?php echo $limite; ?> Consultas ejecutadas)
                            </td>
                        </tr>    
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </body>    
</html>
