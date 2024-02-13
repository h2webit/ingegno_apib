<?php if($this->module->moduleExists('contabilita')): ?>
<form style="display: none" method="POST" id="form_crea_fattura">
    <?php add_csrf(); ?>
</form>
    
<form style="display: none" method="POST" id="form_crea_fattura_contab">
    <?php add_csrf(); ?>
</form>
    
<form style="display: none" method="POST" class="formAjax" action="<?php echo base_url('contabilita/documenti/genera_fatture_da_pagamenti_cliente'); ?>" id="form_crea_fattura_cliente">
    <?php add_csrf(); ?>
    
    <input type="hidden" name="pagamenti_ids" value=""/>
    <input type="hidden" name="periodo_competenza" value=""/>
</form>
    
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.4.37/dist/sweetalert2.all.min.js'></script>
    
<?php $serie = $this->db->get('documenti_contabilita_serie')->result_array(); ?>
    
<script>
    $(function() {
        var grid_container = $('table.js_payments_table');
        var layout_box_container = grid_container.closest('div[data-layout-box]');

        var $js_bulk_action = $('.js-bulk-action', layout_box_container);

        $js_bulk_action.append('<option value="crea_fattura"><?php e('Genera fattura'); ?></option>');
        $js_bulk_action.append('<option value="genera_fatture_distinte"><?php e('Genera fatture distinte'); ?></option>');
        $js_bulk_action.append('<option value="genera_fatture_cliente"><?php e('Genera fatture per cliente'); ?></option>');

        $js_bulk_action.on('change', function(e) {
            // e.preventDefault();
            // e.stopImmediatePropagation();

            var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function() {
                return $(this).val();
            }).get();

            if (chkbx_ids.length > 0) {
                // if ($(this).val() === 'genera_fatture_cliente') {
                //     // CHECK DATA SCADENZA
                //     var periodo_competenza = prompt("Inserisci il periodo di competenza\n\nSe valorizzato verrà aggiunto nella descrizione delle righe articolo, se lasciato vuoto, la descrizione resterà invariata.");
                //
                //     if (periodo_competenza.length > 0) {
                //         $('[name="periodo_competenza"]', $('#form_crea_fattura_cliente')).val(periodo_competenza);
                //     }
                //
                //     $('[name="pagamenti_ids"]', $('#form_crea_fattura_cliente')).val(JSON.stringify(chkbx_ids));
                //
                //     $('#form_crea_fattura_cliente').submit();
                // }

                if ($(this).val() == 'crea_fattura') {
                    $.ajax({
                        url: base_url + 'payments-subscriptions/ajax/buildData',
                        type: 'post',
                        dataType: 'json',
                        async: false,
                        data: {
                            [token_name]: token_hash,
                            payments_ids: chkbx_ids,
                        },
                        success: function (res) {
                            if (res.status == 1 && res.data) {
                                var articoli = res.data;

                                var $form_crea_preventivo = $('#form_crea_fattura_contab');

                                var form_action = '<?php echo base_url('main/layout/nuovo_documento?doc_type=Fattura'); ?>&documenti_contabilita_clienti_id='+res.cliente;

                                $form_crea_preventivo.prop('action', form_action);

                                $.each(articoli, function(index, articolo){
                                    $.each(articolo, function(key, val) {
                                        console.log('<input type="hidden" name="articoli['+index+']['+key+']" value="'+val+'" />');
                                        $form_crea_preventivo.append('<input type="hidden" name="articoli['+index+']['+key+']" value="'+val+'" />');
                                    });
                                });

                                $form_crea_preventivo.submit();
                            } else {
                                alert(res.txt);
                            }
                        },
                        error: function() {

                        }
                    });
                }
                
                if ($(this).val() == 'genera_fatture_distinte' || $(this).val() === 'genera_fatture_cliente') {
                    const serie_obj = <?php echo (!empty($serie) ? json_encode($serie) : '{}'); ?>;

                    let serie_arr = [];

                    if (!$.isEmptyObject(serie_obj)) {
                        serie_arr = serie_obj.map(function(_item) {
                            return _item.documenti_contabilita_serie_value;
                        });
                    }

                    serie_arr.unshift('---');

                    // CHECK DATA EMISSIONE
                    var data_emissione = prompt("Inserisci la data emissione (in formato gg/mm/aaaa)", moment().format('DD/MM/YYYY'));

                    if (!data_emissione) {
                        alert("Azione annullata");
                        return false;
                    }

                    var _data_emissione_obj = moment(data_emissione, 'DD/MM/YYYY', true);
                    if (!_data_emissione_obj._isValid) {
                        alert("Il formato della data emissione non è corretto. Utilizzare il formato: gg/mm/aaaa");
                        return false;
                    }

                    // CHECK DATA SCADENZA
                    var data_scadenza = prompt("Inserisci la data scadenza (in formato gg/mm/aaaa)\n\nSe lasciato vuoto, verrà usata la data emissione");

                    if (data_scadenza.length > 0) {
                        var _data_scadenza_obj = moment(data_scadenza, 'DD/MM/YYYY', true);

                        if (!_data_scadenza_obj._isValid) {
                            alert("Il formato della data scadenza non è corretto. Utilizzare il formato: gg/mm/aaaa");
                            return false;
                        }

                        data_scadenza = _data_scadenza_obj.format('YYYY-MM-DD');
                    }

                    data_emissione = _data_emissione_obj.format('YYYY-MM-DD');

                    // CHECK periodo di competenza
                    var periodo_competenza = prompt("Inserisci il periodo di competenza\n\nSe valorizzato verrà aggiunto nella descrizione delle righe articolo, se lasciato vuoto, la descrizione resterà invariata.");

                    if (periodo_competenza.length > 0) {
                        //$('[name="periodo_competenza"]', $('#form_crea_fattura_cliente')).val(periodo_competenza);
                    }

                    const myalert = Swal.fire({
                        title: 'Seleziona una serie...',
                        input: 'select',
                        inputOptions: serie_arr,
                        inputPlaceholder: 'Seleziona una serie...',
                        showCancelButton: true,
                        showLoaderOnConfirm: true,
                        inputValidator: (value) => {
                            return new Promise((resolve) => {
                                if (value) {
                                    resolve()
                                } else {
                                    resolve('Devi selezionare un valore')
                                }
                            })
                        }
                    }).then(choosen => {
                        if (choosen.isConfirmed) {
                            const choosen_serie = serie_arr[choosen.value];

                            var url_genera_fatture = base_url + 'contabilita/documenti/genera_fatture_da_pagamenti'
                            
                            if ($(this).val() === 'genera_fatture_cliente') {
                                url_genera_fatture = base_url + 'contabilita/documenti/genera_fatture_da_pagamenti_cliente';
                            }
                            
                            $.ajax({
                                url: url_genera_fatture,
                                type: 'post',
                                dataType: 'json',
                                async: false,
                                data: {
                                    [token_name]: token_hash,
                                    ids: chkbx_ids,
                                    serie: choosen_serie,
                                    data_emissione: data_emissione,
                                    data_scadenza: data_scadenza,
                                    periodo_competenza: periodo_competenza
                                },
                                success: function(res) {
                                    alert(res.txt);

                                    if (res.status == '0') {
                                        return false;
                                    }

                                    location.reload();
                                },
                                error: function(status, request, error) {}
                            });
                        }
                    });
                }
            }
        });
    });
</script>
<?php endif; ?>
