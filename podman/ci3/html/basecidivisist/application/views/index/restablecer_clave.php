<?php echo validation_errors('<div class="alert alert-danger alert-dismissable">', '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>'); ?>
<h4>
    <i class="fa fa-lock"></i>
    Recuperar su contraseña
</h4>
<p class="text-justify">
    Para restablecer su clave de usuario complete el siguiente formulario con uno de los correos electrónicos asociados a su código y el texto de la imagen mostrada.
    Al oprimir el botón "Enviar enlace" se enviará un correo con un enlace de recuperación.
</p>    
<?php
echo
form_open('', [
    'id' => 'form_forgot_pass',
    'name' => 'form_forgot_pass',
    'role' => 'form',
    'onsubmit' => "$('#boton_enviar').attr('disabled','true');"
    . "$('#boton_enviar').html($('#enviar_alt_id').html());"
        ], ['register' => 1]);
?>
<div class="row">
    <div class="form-group col-md-12 <?php echo (form_error('CODIGO') ? 'has-error has-feedback' : ''); ?>">
        <?php echo form_label("Código *", 'CODIGO', ['class' => 'control-label']); ?>            
        <?php
        echo
        form_input([
            'id' => 'CODIGO',
            'name' => 'CODIGO',
            'maxlength' => 7,
            'placeholder' => "Escriba su código",
            'class' => 'form-control',
                ], set_value('CODIGO'), 'required');
        ?>
        <br>
    </div>
    <div class="form-group col-md-12 <?php echo (form_error('EMAIL') ? 'has-error has-feedback' : ''); ?>">
        <?php echo form_label("Correo electrónico *", 'EMAIL', ['class' => 'control-label']); ?>            
        <?php
        echo
        form_input([
            'id' => 'EMAIL',
            'name' => 'EMAIL',
            'maxlength' => 100,
            'placeholder' => "Escriba su correo electrónico",
            'class' => 'form-control',
                ], set_value('EMAIL'), 'required');
        ?>
        <br>
    </div>
</div>
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
<div class="row">
    <div class="form-group">
        <div class="col-md-12">
            <hr>
            <a class="btn btn-flat btn-danger pull-left" style="" href="<?php site_url(); ?>" data-toggle="tooltip" data-placement="bottom" title="Recargar página">
                <i class="fa fa-refresh"></i>
            </a>
            <a class="btn btn-flat btn-danger pull-right" href="<?php echo site_url(); ?>" style="margin-left:10px;">
                <i class="fa fa-times-circle"></i> Cancelar 
            </a>
            <?php
            echo
            form_button([
                'content' => '<i class="fa fa-paper-plane"></i> Enviar enlace',
                'type' => 'submit',
                'class' => 'btn btn-danger btn-flat pull-right',
                'id' => 'boton_enviar'
            ]);
            ?>       
            <label id="enviar_alt_id" style="display: none;"><i class='fa fa-spinner fa-spin'></i> Enviando...</label>
        </div>
    </div>
</div>
<?php echo form_close(); ?>            