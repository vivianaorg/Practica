<p class="text-center">
    <strong>
        UNIVERSIDAD FRANCISCO DE PAULA SANTANDER
        <br/>
        AVISO DE PRIVACIDAD
    </strong>
</p>
<div class="text-justify no-margin" style="height: 190px; overflow: auto;background-color: #f6f6f6;padding: 10px;" > 
    <p>
        La <b>UNIVERSIDAD FRANCISCO DE PAULA SANTANDER</b>, con domicilio en la ciudad de Cúcuta, Colombia, actúa y es Responsable del Tratamiento de los datos personales.
        <br/>
        <br/>
        - Dirección de Oficinas: Avenida Gran Colombia # 12E- 96 Colsag. 
        <br/>
        - Correo Electrónico: habeasdata@ufps.edu.co 
        <br/>
        - Teléfono: 5776655, Ext. 393
        <br/>
        <br/>
        Sus datos personales serán incluidos en una base de datos y serán utilizados de manera directa o a través de terceros designados, entre otras, y de forma meramente enunciativa, para las siguientes finalidades directas e indirectas relacionadas con el objeto y propósitos de la Universidad:
    </p>
    <ul style="text-align: justify;">
        <li>
            Lograr de manera eficiente la comunicación y procedimientos relacionados con nuestros servicios, y demás actividades afines con las funciones propias de la Universidad como institución de educación superior, como por ejemplo alianzas, estudios, contenidos, así como las demás instituciones que tengan una relación directa o indirecta, y para facilitarle el acceso general a la información de estos y provisión de nuestros servicios.
        </li>
        <li>
            Informar sobre nuevos servicios y a su vez los cambios realizados a servicios antiguos que estén relacionados con los ofrecidos por la Universidad.
        </li>
        <li>
            Dar cumplimiento a obligaciones contraídas con nuestros estudiantes, profesores, contratistas, contratantes, clientes, proveedores, y empleados.
        </li>
        <li>
            Evaluar la calidad del servicio, y realizar estudios internos sobre hábitos de consumo de los servicios y productos ofrecidos por la Universidad.
        </li>
    </ul>

    Se le informa a los Titulares de información que pueden consultar el Manual Interno de Políticas y Procedimientos de Datos Personales de la <b>UNIVERSIDAD FRANCISCO DE PAULA SANTANDER</b>, que contiene las políticas para el Tratamiento de la información recogida, así como los procedimientos de consulta y reclamación que le permitirán hacer efectivos sus derechos al acceso, consulta, rectificación, actualización y supresión de los datos, en el siguiente
    <a href ="http://www.ufps.edu.co/ufpsnuevo/modulos/contenido/view_content.php?item=44">enlace</a>.
</div>
<hr/>
<?php if ($mayorEdad): ?>
    <?php echo validation_errors('<div class="alert alert-danger alert-dismissable">', '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>'); ?>
    <?php
    echo form_open('', [
        'id' => 'form_hd_mayor',
        'name' => 'form_hd_mayor',
        'role' => 'form',
        'onsubmit' => "$('#aceptar1').attr('disabled','true');"
        . "$('#aceptar1').html($('#aceptar1_alt').html());"
            ], ['REGISTRAR' => 1]);
    ?>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="CONFIRMACION" value="1" onclick="aceptar1.disabled = !this.checked;"> 
            "Consiento y autorizo de manera previa, expresa e inequívoca que mis datos personales sean tratados conforme a lo previsto en el presente documento".
        </label>
    </div>
    <div class="row">
        <div class="form-group">
            <div class="col-md-12">
                <hr>
                <a class="btn btn-flat btn-primary pull-right" href="<?php echo site_url(); ?>" style="margin-left:10px;">
                    <i class="fa fa-times-circle"></i> Cancelar 
                </a>
                <?php
                echo
                form_button([
                    'content' => '<i class="fa fa-check-circle"></i> Acepto, ir a Divisist',
                    'type' => 'submit',
                    'class' => 'btn btn-primary btn-flat pull-right',
                    'id' => 'aceptar1'
                        ], '', 'disabled');
                ?>       
                <label id="aceptar1_alt" style="display: none;">
                    <i class='fa fa-spinner fa-spin'></i> Procesando
                </label>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>
<?php elseif ($enlacesVigentes): ?>
    <?php if (!$anulacionPrevia): ?>
        <p class="text-justify">
            Se ha enviado un correo a <?php echo $enlacesVigentes->EMAIL; ?> con un enlace para completar el proceso para la
            autorización del tratamiento de los datos personales. <b>No se le permitira el ingreso a este portal hasta que
                su responsable legal autorice el manejo de sus datos personales.</b> En el caso que un error no le permita acceder al enlace
            mencionado anteriormente, podra <b>ANULAR</b> el mismo y llenar de nuevo el formulario de autorización haciendo uso del
            siguiente formulario. Solo se podra anular la información enviada <b>UNA VEZ.</b>
        </p> 
        <?php echo validation_errors('<div class="alert alert-danger alert-dismissable">', '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>'); ?>
        <?php
        echo form_open('', [
            'id' => 'form_hd_anular',
            'name' => 'form_hd_anular',
            'role' => 'form',
            'onsubmit' => "$('#anular').attr('disabled','true');"
            . "$('#anular').html($('#anular_alt').html());"
                ], ['ANULAR' => 1]);
        ?>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="CONFIRMACION" value="1" onclick="anular.disabled = !this.checked;"> 
                "Deseo anular la informacion suministrada previamente". 
            </label>
        </div>
        <div class="row">
            <div class="form-group">
                <div class="col-md-12">
                    <hr>
                    <a class="btn btn-flat btn-primary pull-right" href="<?php echo site_url(); ?>" style="margin-left:10px;">
                        <i class="fa fa-times-circle"></i> Cancelar 
                    </a>
                    <?php
                    echo
                    form_button([
                        'content' => '<i class="fa fa-check-circle"></i> Acepto, ir a Divisist',
                        'type' => 'submit',
                        'class' => 'btn btn-primary btn-flat pull-right',
                        'id' => 'anular'
                            ], '', 'disabled');
                    ?>       
                    <label id="anular_alt" style="display: none;">
                        <i class='fa fa-spinner fa-spin'></i> Procesando
                    </label>
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
    <?php else: ?>
        <p class="text-justify">
            Se ha enviado un correo a <?php echo $enlacesVigentes->EMAIL; ?> con un enlace para completar el proceso para la
            autorización del tratamiento de los datos personales. <b>No se le permitira el ingreso a este portal hasta que 
                su responsable legal autorice el manejo de sus datos personales.</b> Ya ha solicitado la anulación de la informacón 
            relacionada con su responsable legal ingresada previamente, no le sera posible realizar esta operación de nuevo. Si 
            existe alguna inconsistencia comuniquenosla a <b>consulta.estudiante@ufps.edu.co</b>.
        </p>                            
    <?php endif; ?>
<?php else: ?>
    <p class="text-justify">
        En su condición de menor de edad, no es posible que autorice que sus datos personales sean 
        tratados conforme a lo  previsto en este documento, por esta razón es necesario que introduzca
        los datos solicitados de su representante legal o acudiente en el siguiente formulario.
        <b>Se enviara un enlace de verificación al correo electronico de su representante legal para completar 
            este proceso de autorización de manejo de datos personales.</b>
    </p>
    <?php echo validation_errors('<div class="alert alert-danger alert-dismissable">', '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>'); ?>
    <?php
    echo form_open('', [
        'id' => 'form_hd_mayor',
        'name' => 'form_hd_mayor',
        'role' => 'form',
        'onsubmit' => "$('#aceptar2').attr('disabled','true');"
        . "$('#aceptar2').html($('#aceptar2_alt').html());"
            ], ['TITULAR' => 1]);
    ?>    
    <div class="row">
        <div class="form-group">
            <div class="col-md-12 <?php echo (form_error('CEDULA_ACUDIENTE') ? 'has-error has-feedback' : ''); ?>">
                <?php echo form_label("Cédula del Acudiente *", 'CEDULA_ACUDIENTE', ['class' => 'control-label']); ?>            
                <?php
                echo
                form_input([
                    'id' => 'CEDULA_ACUDIENTE',
                    'name' => 'CEDULA_ACUDIENTE',
                    'maxlength' => 12,
                    'placeholder' => "Escriba su la cédula de su acudiente",
                    'class' => 'form-control',
                        ], set_value('CEDULA_ACUDIENTE'), 'required');
                ?>
                <br>
            </div>
            <div class="col-md-12 <?php echo (form_error('NOMBRE_ACUDIENTE') ? 'has-error has-feedback' : ''); ?>">
                <?php echo form_label("Nombre del acudiente *", 'NOMBRE_ACUDIENTE', ['class' => 'control-label']); ?>            
                <?php
                echo
                form_input([
                    'id' => 'NOMBRE_ACUDIENTE',
                    'name' => 'NOMBRE_ACUDIENTE',
                    'maxlength' => 100,
                    'placeholder' => "Escriba el nombre de su acudiente",
                    'class' => 'form-control',
                        ], set_value('NOMBRE_ACUDIENTE'), 'required');
                ?>
                <br>
            </div>
            <div class="col-md-12 <?php echo (form_error('CORREO_ACUDIENTE') ? 'has-error has-feedback' : ''); ?>">
                <?php echo form_label("Correo electrónico del acudiente *", 'CORREO_ACUDIENTE', ['class' => 'control-label']); ?>            
                <?php
                echo
                form_input([
                    'id' => 'CORREO_ACUDIENTE',
                    'name' => 'CORREO_ACUDIENTE',
                    'maxlength' => 100,
                    'placeholder' => "Escriba el correo electrónico de su acudiente",
                    'class' => 'form-control',
                        ], set_value('CORREO_ACUDIENTE'), 'required');
                ?>
                <br>
            </div>
        </div>
        <div class="form-group">            
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="CONFIRMACION" value="1" onclick="aceptar2.disabled = !this.checked;"> 
                        "Certifico que la información contenida en esta solicitud es veraz y ha sido diligenciada a mi entero conocimiento"
                    </label>
                </div>
                <hr>
                <a class="btn btn-flat btn-danger pull-right" href="<?php echo site_url(); ?>" style="margin-left:10px;">
                    <i class="fa fa-times-circle"></i> Cancelar 
                </a>
                <?php
                echo
                form_button([
                    'content' => '<i class="fa fa-check-circle"></i> Acepto, ir a Divisist',
                    'type' => 'submit',
                    'class' => 'btn btn-danger btn-flat pull-right',
                    'id' => 'aceptar2'
                        ], '', 'disabled');
                ?>       
                <label id="aceptar2_alt" style="display: none;">
                    <i class='fa fa-spinner fa-spin'></i> Procesando
                </label>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>                   
<?php endif; ?>