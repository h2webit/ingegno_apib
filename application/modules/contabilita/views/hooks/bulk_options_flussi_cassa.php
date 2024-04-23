<?php $this->layout->addModuleJavascript('contabilita', 'sweetalert.js'); ?>
   
    
<script>
    $(function() {
        var grid_container = $('table.js_elenco_flussi_cassa');
        var layout_box_container = grid_container.closest('div[data-layout-box]');

        var $js_bulk_action = $('.js-bulk-action', layout_box_container);

        $js_bulk_action.append('<option value="genera_spesa"><?php e('Genera spesa'); ?></option>');
        

        $js_bulk_action.on('change', function(e) {
            //e.preventDefault();
            //e.stopImmediatePropagation();

            var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function() {
                return $(this).val();
            }).get();

            if (chkbx_ids.length > 0) {
                if ($(this).val() === 'genera_spesa') {
                    
                    window.location = '<?php echo base_url("main/layout/nuova_spesa/?flussi_cassa_ids="); ?>'+chkbx_ids.join(',');
                } 

                
            }
        });
    });
</script>

