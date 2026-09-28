<?php echo validation_errors('<div class="alert alert-danger alert-dismissable">', '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>'); ?>
<h4>
    <i class="fa fa-lock"></i>
    Restaurar la clave de su cuenta
</h4>

<div class="row">
    <div class="col-md-12">
        <div class="password-container">
            <?php echo form_open('', ['id' => 'form_cambiar_clave', 'name' => 'form_cambiar_clave', 'role' => 'form'], ['register' => 1]); ?>
            <input id="level" type="hidden" name="level" value="0" />
            <p class="text-justify">
                Por favor ingrese la nueva contraseña para su cuenta. Recuerde que una contraseña segura debe contener entre 8 y
                16 caracteres, no incluye palabras o nombres comunes, combina letras en mayúsculas, minúsculas, números y simbolos.
            </p>    
            <div class="row">
                <div class="form-group col-md-12 <?php echo (form_error('PASSWORD') ? 'has-error has-feedback' : ''); ?>">
                    <?php echo form_label("Nueva clave *", 'PASSWORD', ['class' => 'control-label']); ?>            
                    <?php
                    echo
                    form_input([
                        'id' => 'PASSWORD',
                        'name' => 'PASSWORD',
                        'type' => 'PASSWORD',
                        'maxlength' => 16,
                        'placeholder' => "Escriba la nueva clave",
                        'class' => 'form-control strong-password',
                            ], set_value('PASSWORD'), 'required');
                    ?>
                    <br>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12 <?php echo (form_error('PASSWORD2') ? 'has-error has-feedback' : ''); ?>">
                    <?php echo form_label("Confirmación nueva clave *", 'PASSWORD2', ['class' => 'control-label']); ?>            
                    <?php
                    echo
                    form_input([
                        'id' => 'PASSWORD2',
                        'name' => 'PASSWORD2',
                        'type' => 'PASSWORD',
                        'maxlength' => 16,
                        'placeholder' => "Confirme su nueva clave",
                        'class' => 'form-control strong-password',
                            ], set_value('PASSWORD2'), 'required');
                    ?>
                    <br>
                    <div id="diffpasswords" class="label label-danger"></div>
                </div>
            </div>                        
            <div class="row">
                <div class="form-group col-md-12">
                    <div class="indicador_clave">                        
                        <div class='progress progress-xs active'>
                            <div class='progress-bar progress-bar-striped progress-bar-info' style='width: 0%'></div>
                        </div>
                        <p class='text-muted'>Nivel de Seguridad</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group">
                    <div class="col-md-12">
                        <hr>
                        <a class="btn btn-danger btn-flat pull-right" href="<?php echo site_url(); ?>" style="margin-left:10px;">
                            <i class="fa fa-times-circle"></i> Cancelar
                        </a>
                        <?php
                        echo
                        form_button([
                            'content' => '<i class="fa fa-floppy-o"></i></span> Guardar Cambios',
                            'type' => 'submit',
                            'class' => 'btn btn-danger btn-flat pull-right submit-button locked'
                        ]);
                        ?>                          
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

