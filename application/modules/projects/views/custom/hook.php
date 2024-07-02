<form style="display: hidden" method="POST" id="form_contabilita">
    <?php add_csrf(); ?>
</form>
<form  action="<?php echo base_url(); ?>contabilita/documenti/genera_ordini_fornitori" method="POST" id="form">
  <?php add_csrf(); ?>
    <input type="hidden" id="cl_var1" name="articoli_ids" value=""/>
    <input type="hidden" name="_from_commessa" value="1"/>
    <input type="hidden" id="id_commessa" name="id_commessa" value="<?php echo $value_id; ?>"/>
    
  <input type="hidden" id="cl_bulk_action" name="bulk_action" value=""/>
</form>
<script>
    var cur_layout = '<?php echo $this->layout->getCurrentLayoutIdentifier(); ?>';
    
    $(function() {
        var grid_container = $('table.js_fg_grid_documenti_contabilita_articoli');
        var layout_box_container = grid_container.closest('div[data-layout-box]');
    
        var $js_bulk_action = $('.js-bulk-action', layout_box_container);

    
        $js_bulk_action.append('<option value="ordiniFornitori">Genera ordini fornitori automatici</option>');
        $js_bulk_action.append('<option value="creaDoc">Genera documenti</option>');
        
        $js_bulk_action.on('change', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            var $selected_action = $(this).val();
    
            var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function () {
                return $(this).val();
            }).get();
            $('#cl_var1').val(JSON.stringify(chkbx_ids));

            var $ajax_url = '';
            var $ajax_data = '';
            
            var actionType = null;
            switch ($selected_action) {
                case 'ordiniFornitori':
                    actionType = 'contabilita';
                    
                    $ajax_url = base_url + 'contabilita/documenti/genera_ordini_fornitori';
                    
                    break;
                case 'creaDoc':
                    actionType = 'contabilita';
                    
                    $ajax_url = base_url + 'contabilita/documenti/buildArticoliData?type='+$selected_action;
                    
                    break;
            }
    
            if (chkbx_ids.length > 0 && actionType) {
                $.ajax({
                    url: $ajax_url,
                    type: 'post',
                    dataType: 'json',
                    async: false,
                    data: {
                        [token_name]: token_hash,
                        articoli_ids: chkbx_ids,
                        type: actionType,
                        commessa_id : $('#id_commessa').val(),
                    },
                    success: function (res) {
                        if (res.status == 1 && res.data) {
                            if (actionType == 'contabilita') {
                                var articoli = res.data;
                    
                                var $form_contabilita = $('#form_contabilita');
                                /*
                                if(res.fornitore !== 0 ){
                                    var form_action = '<?php echo base_url('main/layout/nuovo_documento?doc_type=Ordine+fornitore'); ?>&documenti_contabilita_clienti_id=' + res.fornitore;
                                } else {
                                    var form_action = '<?php echo base_url('main/layout/nuovo_documento?doc_type=Ordine+fornitore'); ?>';
                                }*/
                                var form_action = '<?php echo base_url('main/layout/nuovo_documento?doc_type=Ordine+fornitore&commessa='.$value_id); ?>';
                    
                                $form_contabilita.prop('action', form_action);
                    
                                $.each(articoli, function (index, articolo) {
                                    $.each(articolo, function (key, val) {
                                        $form_contabilita.append('<input type="hidden" name="articoli[' + index + '][' + key + ']" value="' + val + '" />');
                                    });
                                });
                    
                                $form_contabilita.submit();
                            }
                        } else {
                            alert(res.txt);
                            location.reload();
                        }
                    },
                    error: function (status, request, error) {
            
                    }
                });
            }
        })
    });
</script>
