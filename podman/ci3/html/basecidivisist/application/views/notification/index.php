<div class="box box-primary">
    <div class="box-header with-border">
        <h2 class="box-title"><i class="fa fa-bell-o"></i> Notificaciones</h2>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table id="table_notifications" class="display table table-striped table-bordered table-hover table-condensed" style="width: 100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>De</th>
                        <th style="max-width: 60%;">Mensaje</th>
                        <th>Estado</th>
                        <th>Lectura</th>
                        <th>Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($notifications): ?>
                        <?php foreach ($notifications as $key => $value): ?>
                            <tr class="<?php echo $value->ESTADO == '0' ? 'info' : ''; ?>">
                                <td><?php echo $value->ROWNUM; ?></td>
                                <td><?php echo $value->EMISOR; ?></td>
                                <td class="text-justify"><?php echo $value->MENSAJE; ?></td>
                                <td><?php echo $value->DESC_ESTADO; ?></td>
                                <td><?php echo $value->FECHA_LECTURA; ?></td>
                                <td><?php echo $value->FECHA_CREACION; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>                    
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (!$notifications): ?>
    <div class="box box-info">
        <div class="box-header">
            <h2 class="box-title">
                <i class="fa fa-info-circle"></i> Aún no cuenta con notificaciones                
            </h2>
        </div>
        <div class="box-body">
            <p class="text-justify">
                Tu bandeja de notificaciones está vacia.
            </p>
        </div>
    </div>
<?php endif; ?>