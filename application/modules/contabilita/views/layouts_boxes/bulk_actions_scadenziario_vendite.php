
    <?php



    $modelli = $this->db
        ->join('prime_note', 'prime_note_id = prime_note_modelli_prima_nota', 'LEFT')
        ->join('prime_note_causali', 'prime_note_causali_id = prime_note_causale', 'LEFT')
        ->join('prime_note_tipo', 'prime_note_tipo_id = prime_note_causali_tipo', 'LEFT')
        //->where('prime_note_tipo_value', 'Vendite')
        ->get('prime_note_modelli')

        ->result_array();
    $conti = $this->apilib->search('conti_correnti', ['conti_correnti_tipologia' => 1]);
    ?>

    <form style="display: none" method="POST" id="form_registra_in_prima_nota" action="<?php echo base_url('contabilita/primanota/genera_prime_note_da_pagamenti'); ?>">
        <?php add_csrf(); ?>
        <input type="hidden" name="ids" />
        <input type="hidden" name="modello" />
        <input type="hidden" name="conto" />
        <input type="hidden" name="marca_saldate" />
        
    </form>

    

    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.4.37/dist/sweetalert2.all.min.js'></script>

    

    <script>
        
        $(function () {
            var grid_container = $('table.js_scadenziario_entrate');
            var layout_box_container = grid_container.closest('div[data-layout-box]');

            var $js_bulk_action = $('.js-bulk-action', layout_box_container);

            $js_bulk_action.append('<option value="registra_prime_note"><?php e('Registra in prima nota...'); ?></option>');
            
            $js_bulk_action.on('change', function (e) {
                

                var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function () {
                    return $(this).val();
                }).get();

                if (chkbx_ids.length > 0) {
                    

                    if ($(this).val() == 'registra_prime_note') {
                        const modelli_obj = <?php echo (!empty($modelli) ? json_encode($modelli) : '{}'); ?>;
                        

                        let modelli_arr = [];

                        if (!$.isEmptyObject(modelli_obj)) {
                            modelli_arr = modelli_obj.map(function (_item) {
                                return _item.prime_note_modelli_nome;
                            });
                        }

                        modelli_arr.unshift('---');


                        const conti_obj = <?php echo (!empty($conti) ? json_encode($conti) : '{}'); ?>;
                                                let conti_arr = [];

                        if (!$.isEmptyObject(conti_obj)) {
                            conti_arr = conti_obj.map(function (_item) {
                                return _item.conti_correnti_nome_istituto;
                            });
                        }

                        conti_arr.unshift('---');


                        // CHECK DATA SCADENZA
                        var marca_saldate = prompt("Una volta registrate in prima nota, vuoi marcare le scadenze di pagamento come saldate? (S=sì, N=no)");

                        if (marca_saldate.length > 0) {
                            if (marca_saldate == 'S') {
                                marca_saldate = 1;
                                
                                const myalert_conto = Swal.fire({
                            title: 'Seleziona il conto su cui avviene l\'incasso...',
                            input: 'select',
                            inputOptions: conti_arr,
                            inputPlaceholder: 'Seleziona un conto...',
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
                                const choosen_conto = conti_arr[choosen.value];
                                const myalert = Swal.fire({
                            title: 'Seleziona il modello da utilizzare...',
                            input: 'select',
                            inputOptions: modelli_arr,
                            inputPlaceholder: 'Seleziona un modello...',
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
                            console.log(choosen);
                            if (choosen.isConfirmed) {
                                const choosen_modello = modelli_arr[choosen.value];
                                $('#form_registra_in_prima_nota [name="ids"]').val(chkbx_ids.join(','));
                                $('#form_registra_in_prima_nota [name="modello"]').val(choosen_modello);
                                $('#form_registra_in_prima_nota [name="conto"]').val(choosen_conto);
                                $('#form_registra_in_prima_nota [name="marca_saldate"]').val(marca_saldate);
                                $('#form_registra_in_prima_nota').submit();
                                
                            }
                           
                        });
                            }
                           
                        });

                            } else if (marca_saldate == 'N') {
                                marca_saldate = 0;
                                const myalert = Swal.fire({
                            title: 'Seleziona il modello da utilizzare...',
                            input: 'select',
                            inputOptions: modelli_arr,
                            inputPlaceholder: 'Seleziona un modello...',
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
                            console.log(choosen);
                            if (choosen.isConfirmed) {
                                const choosen_modello = modelli_arr[choosen.value];
                                $('#form_registra_in_prima_nota [name="ids"]').val(chkbx_ids.join(','));
                                $('#form_registra_in_prima_nota [name="modello"]').val(choosen_modello);
                                $('#form_registra_in_prima_nota [name="conto"]').val('');
                                $('#form_registra_in_prima_nota [name="marca_saldate"]').val(marca_saldate);
                                $('#form_registra_in_prima_nota').submit();
                                
                            }
                           
                        });
                            } else {
                                alert("Risposta non riconosciuta. Azione annullata.");
                                return false;
                            }
                        } else {
                            alert("Azione annullata.");
                            return false;
                        }

                        
                            
                        
                        
                    }
                }
            });
        });
    </script>
