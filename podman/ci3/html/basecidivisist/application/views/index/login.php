<p class="login-box-msg">Ingresa tus datos para iniciar sesión</p>
<?php echo form_open('', ['class' => '', 'id' => 'form_login', 'role' => 'form'], ['login' => 1]); ?>
<div class="form-group <?php echo (form_error('usuario') ? 'has-error has-feedback' : 'has-feedback'); ?>">
    <input type="text" class="form-control" placeholder="Código" name="usuario" maxlength="7" value="<?php echo $this->input->post('usuario'); ?>" required="required"/>
    <span class="glyphicon glyphicon-user form-control-feedback"></span>
    <?php if (form_error("usuario")): ?>
        <?php echo form_error("usuario", "<span class='text-danger'>", "</span>"); ?>
    <?php endif; ?>
</div>
<div class="form-group <?php echo (form_error('password') ? 'has-error has-feedback' : 'has-feedback'); ?>">
    <input type="password" class="form-control" placeholder="Contraseña" name="password" maxlength="16" required="required"/>
    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
    <?php if (form_error("password")): ?>
        <?php echo form_error("password", "<span class='text-danger'>", "</span>"); ?>
    <?php endif; ?>
</div>
<?php if (isset($cap)): ?>
    <div class="row">
        <div class="form-group col-md-12">
            <?php echo $cap['image']; ?>        
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12 <?php echo (form_error('TEXTO_CAP') ? 'has-error has-feedback' : ''); ?>">
            <?php
            echo
            form_input([
                'id' => 'TEXTO_CAP',
                'name' => 'TEXTO_CAP',
                'maxlength' => 3,
                'placeholder' => "Escriba los caracteres de la imagen",
                'class' => 'form-control',
                    ], '', 'required');
            ?>
        </div>
    </div>
    <?php if (form_error("TEXTO_CAP")): ?>
        <?php echo form_error("TEXTO_CAP", "<span class='text-danger'>", "</span>"); ?>
    <?php endif; ?>
<?php endif; ?>
<div class="row">
    <br>
    <div class="col-xs-12 center-block">
        <button type="submit" class="btn btn-danger btn-block btn-flat">Iniciar Sesión</button>
    </div><!-- /.col -->
    <div class="col-xs-12 center-block" style="margin-top: 20px;">
        <a class="text-danger" href="<?php echo base_url('index/restablecer_clave'); ?>">¿Olvidaste tu clave?</a>
        <!--<button type="button" class="cd-btn btn btn-flat btn-danger pull-right"><i class="fa fa-newspaper-o"></i></button>-->
        <?php if (isset($noticias) && $noticias): ?>
            <a href="#" class="cd-btn text-danger pull-right"><i class="fa fa-newspaper-o hvr-buzz-out"></i> Noticias</a>
        <?php endif; ?>
    </div>
    <div class="col-xs-12 center-block" style="margin-top: 20px;"></div>
</div>
<?php echo form_close(); ?>

<!--About Section-->
<section id="">
    <!-- all for your comfort blocks -->
    <div class="row login-links">
        <div class="col-md-3 col-sm-3 col-xs-3">
            <a href="http://www.divisist.ufps.edu.co/docentes/index.php" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Docente">
                <div class="text-center">
                    <span aria-hidden="true" class="text-danger icon-user icon hvr-grow fa-2x"></span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-3 col-xs-3">
            <a class=" " href="http://www.divisist.ufps.edu.co/egresado/index.php" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Egresado">
                <div class="text-center">
                    <span aria-hidden="true" class="text-danger icon-graduation icon hvr-grow fa-2x"></span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-3 col-xs-3">
            <a href="http://www.divisist.ufps.edu.co/dirplan/" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Jefe de Plan">
                <div class="text-center">
                    <span aria-hidden="true" class="text-danger icon-user-following icon hvr-grow fa-2x"></span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-3 col-xs-3">
            <a href="http://www.divisist.ufps.edu.co/vacacionales/index.php" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Vacacionales">
                <div class="text-center">
                    <span aria-hidden="true" class="text-danger icon-book-open icon hvr-grow fa-2x"></span>
                </div>
            </a>
        </div>
    </div>
</section>
<!--End of About Section-->
<?php if (isset($noticias) && $noticias): ?>
    <div class="cd-panel from-left">
        <header class="cd-panel-header">
            <h1><i class="fa fa-newspaper-o"></i> Noticias Académicas</h1>
            <a href="#0" class="cd-panel-close">Close</a>
        </header>
        <div class="cd-panel-container">
            <div class="cd-panel-content">
                <?php if (isset($noticias['TITULO']) && count($noticias['TITULO'])): ?>
                    <?php
                    $numFilas = count($noticias['TITULO']);
                    for ($i = 0; $i < $numFilas; $i++):
                        ?>
                        <p style="border-bottom: 1px solid #DD4B39; color: #DD4B39; font-size: 14px; font-weight: bold;"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $noticias['TITULO'][$i]; ?></p>
                        <p style="color: #DD4B39; font-size: 10px;"><i class="fa fa-calendar"></i> Publicado, <?php echo $noticias['FECHA_CREACION'][$i]; ?> - <?php echo $noticias['OBSERVACION'][$i]; ?></p>
                        <p style="font-size: 14px; text-align: justify"><?php echo $noticias['CONTENIDO'][$i]; ?></p>
                        <br>
                    <?php endfor; ?> 
                <?php endif; ?>
            </div> <!-- cd-panel-content -->
        </div> <!-- cd-panel-container -->
    </div> <!-- cd-panel -->
<?php endif; ?>

