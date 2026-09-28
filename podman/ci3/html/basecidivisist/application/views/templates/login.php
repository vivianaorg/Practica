<!DOCTYPE html>
<html>
    <head>
        <!--hostname: <?php echo "http://{$_SERVER['HTTP_HOST']}/"; ?>-->
        <?php if (isset($page_tittle)): ?>
            <title><?php echo $page_tittle; ?></title>
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
    <body class="login-page">
        <div class="login-box2<?php echo (isset($box_medium) ? "-medium" : "") ?>">
            <div class="login-logo no-margin">
                <a href="">NOMBRE <strong>APLICACION <span class="fa fa-spin">.</span></strong></a>
                <!--<img class="img-responsive" src="<?php echo base_url("assets/img/ufps/logo_divisist2.png"); ?>"/>-->
            </div><!-- /.login-logo -->
            <div class="login-box-body">
                <div id="template_alerts">
                    <?php foreach ($_warning as $_msj): ?>
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <i class="fa fa-exclamation-triangle"></i>&nbsp;
                            <?php echo $_msj; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($_success as $_msj): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <i class="fa fa-check-circle"></i>&nbsp;
                            <?php echo $_msj; ?>
                        </div>
                    <?php endforeach; ?> 
                    <?php foreach ($_error as $_msj): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <i class="fa fa-times-circle"></i>&nbsp;
                            <?php echo $_msj; ?>
                        </div>
                    <?php endforeach; ?> 
                    <?php foreach ($_info as $_msj): ?>
                        <div class="alert alert-info alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <i class="fa fa-info-circle"></i>&nbsp;
                            <?php echo $_msj; ?>
                        </div>
                    <?php endforeach; ?>                     
                </div>
                <?php foreach ($_content as $_view): ?>
                    <?php include $_view; ?>
                <?php endforeach; ?>
            </div><!-- /.login-box -->
        </div>
        <?php echo $_js; ?>                
    </body>
</html>