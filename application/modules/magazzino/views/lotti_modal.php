<!-- Modal per lotti -->
    <div id="lotti_modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Selezionare il lotto/matricola da cui prelevare i prodotti</h4>
                </div>
                <!--<div class="modal-body">-->
                <div class="modal-body">
                    <div class="callout callout-danger" style="display: none;" id="lotti_modal_error"></div>
                    <table class="table table-responsive table-condensed table-striped table-bordered " id="lotti_table">
                        <thead>
                            <tr>
                                <?php if ($settings['magazzino_settings_show_lotto'] == 1): ?>
                                    <th>Lotti/matricola</th>
                                <?php endif;?>
                                <?php if ($settings['magazzino_settings_show_scadenza'] == 1): ?>
                                    <th>Data Scadenza</th>
                                <?php endif;?>
                                <?php if ($settings['magazzino_settings_show_marchio'] == 1): ?>
                                    <th>Marchio</th>
                                <?php endif;?>
                                <?php if ($settings['magazzino_settings_show_fornitore'] == 1): ?>
                                    <th>Fornitore</th>
                                <?php endif;?>
                                <th>Quantità</th>
                                
                                <th>Q.tà Impegnate</th>

                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <!--</div>-->
            </div>
        </div>
    </div>
    <script>
        function reinitDataTableLotti() {
            if ($.fn.DataTable.isDataTable('#lotti_table')) {
                $('#lotti_table').DataTable().destroy();
            }
            $('#lotti_table').DataTable({
                "paging": true,
                "searching": true,
                "info": true,
                "lengthChange": true
            });
        }
        </script>