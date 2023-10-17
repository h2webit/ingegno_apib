

<?php $this->layout->addModuleJavascript('contabilita', 'sweetalert.js'); ?>

<form style="display: hidden" action="<?php echo base_url(); ?>contabilita/primanota/deletesottoconti" method="POST"
      id="form_bulk_delete_sottoconti">
    <?php add_csrf(); ?>

    <input type="hidden" id="bulk_sottoconti_ids" name="ids" value=""/>
    <input type="hidden" id="bulk_sottoconto_replace" name="sottoconto_replace" value=""/>
    
</form>

<script>
    $(document).ready(function () {
        $('.js-bulk-action').each(function (index) {
            var grid_container = $(this).closest('div[data-layout-box]');

            //aggiungo opzione download
            $(this).append('<option value="elimina_sottoconto">Elimina...</option>');
            
            $(this).on('change', function () {
                var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function () {
                    return $(this).val();
                }).get();

                if (chkbx_ids.length <= 0) {
                    $("input:checkbox.js_bulk_check:checked", grid_container).val('').trigger('change');
                    return false;
                }

                

                if ($(this).val() == 'elimina_sottoconto') {
                    
                    var sottoconto_replace = prompt('Inserisci il codice completo del sottoconto con cui uniformare i sottoconti selezionati (N = elimina solamente)');
                    if (!sottoconto_replace) {
                        alert("Azione annullata");
                        return false;
                    }
                    $('#bulk_sottoconto_replace').val(sottoconto_replace);
                    $('#bulk_sottoconti_ids').val(JSON.stringify(chkbx_ids));
                    $('#form_bulk_delete_sottoconti').submit();
                        
                    
                }
            });
        });

    });
</script>
