<div class="modal fade" id="modal_desglose" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal_titulo" style="font-weight: 600;">
                    <i class="fa fa-pie-chart"></i> Desglose de Rúbrica del Estudiante
                </h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-8">
                        <h4 id="modal_estudiante_nombre" style="margin-top: 0; font-weight: 600; color: #222;">-</h4>
                        <div class="text-muted">
                            <strong>Código:</strong> <span id="modal_estudiante_codigo">-</span> &nbsp;|&nbsp;
                            <strong>Corte:</strong> <?php echo htmlspecialchars(isset($nombreCorteActual) ? $nombreCorteActual : 'Corte'); ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div style="font-size: 11px; text-transform: uppercase; color: #777;">Nota Sugerida Total</div>
                        <div id="modal_nota_total" style="font-size: 26px; font-weight: bold; color: #00a65a;">0.00</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-gray-light">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Actividad</th>
                                <th style="width: 140px; text-align: center;">Nota Original</th>
                                <th style="width: 150px; text-align: center;">Nota Base (0.0-5.0)</th>
                                <th style="width: 110px; text-align: center;">Peso (%)</th>
                                <th style="width: 130px; text-align: center;">Subnota</th>
                            </tr>
                        </thead>
                        <tbody id="modal_tbody_desglose">
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-light" style="font-weight: bold;">
                                <td colspan="4" class="text-right">TOTAL PONDERADO:</td>
                                <td style="text-align: center;" id="modal_total_peso">100%</td>
                                <td style="text-align: center;" id="modal_total_subnota">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
