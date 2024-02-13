<?php
$tipologie = $this->db->get('documenti_contabilita_tipologie_fatturazione')->result_array();
$tipi_doc = $this->db->get('documenti_contabilita_tipo')->result_array();
$pdf_templates = $this->db->get('documenti_contabilita_template_pdf')->result_array();
?>

<form style="display: hidden" action="<?php echo base_url(); ?>contabilita/documenti/downloadZip" method="POST" id="form_export_zip">
    <input type="hidden" id="fatture_ids" name="ids" value=""/>
    <?php add_csrf(); ?>
</form>

<form style="display: hidden" action="<?php echo base_url(); ?>contabilita/documenti/print_all" method="POST" id="form_stampa_massiva_tpl">
    <input type="hidden" id="stampa_massiva_tpl_fatture_ids" name="ids" value="" />
    <input type="hidden" id="stampa_massiva_tpl_template_pdf" name="tpl" value="" />
    
    <?php add_csrf(); ?>
</form>

<?php $this->layout->addModuleJavascript('contabilita', 'sweetalert.js'); ?>

<form style="display: hidden" action="<?php echo base_url(); ?>contabilita/documenti/bulk_clone" method="POST"
      id="form_bulk_clone">
    <?php add_csrf(); ?>
    
    <input type="hidden" id="bulk_fatture_ids" name="ids" value=""/>
    <input type="hidden" id="bulk_data_emissione" name="data_emissione" value=""/>
    <input type="hidden" id="bulk_periodo_competenza" name="periodo_competenza" value=""/>
    <input type="hidden" id="bulk_data_scadenza" name="data_scadenza" value=""/>
    <input type="hidden" id="bulk_tipologia" name="tipologia" value=""/>
    <input type="hidden" id="bulk_tipo_doc" name="tipo_documento" value=""/>
</form>

<script>
    $(document).ready(function () {
        $('.js-bulk-action').each(function (index) {
            var grid_container = $(this).closest('div[data-layout-box]');
            
            //aggiungo opzione download
            $(this).append('<option value="download_zip">Download zip</option>');
            $(this).append('<option value="bulk_clone">Duplica / Trasforma</option>');
            $(this).append('<option value="stampa_accorpata_template">Stampa accorpata (con template)</option>');
            
            $(this).on('change', function () {
                var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function () {
                    return $(this).val();
                }).get();
                
                if (chkbx_ids.length <= 0) {
                    $("input:checkbox.js_bulk_check:checked", grid_container).val('').trigger('change');
                    return false;
                }
                
                if ($(this).val() == 'stampa_accorpata_template') {
                    const pdf_templates_obj = <?php e_json($pdf_templates); ?>;
                    
                    let pdf_templates_arr = {};
                    
                    pdf_templates_obj.forEach(pdf_template => {
                        pdf_templates_arr[pdf_template.documenti_contabilita_template_pdf_id] = pdf_template.documenti_contabilita_template_pdf_nome;
                    });
                    
                    Swal.fire({
                        title: 'Scelta template di stampa',
                        input: 'select',
                        inputOptions: pdf_templates_arr,
                        inputPlaceholder: '- Seleziona il template da stampare -',
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
                            $('#stampa_massiva_tpl_template_pdf', $('#form_stampa_massiva_tpl')).val(choosen.value);
                            $('#stampa_massiva_tpl_fatture_ids', $('#form_stampa_massiva_tpl')).val(JSON.stringify(chkbx_ids));
                            
                            $('#form_stampa_massiva_tpl').submit();
                        }
                    });
                }
                
                if ($(this).val() == 'download_zip') {
                    $('#fatture_ids').val(JSON.stringify(chkbx_ids));
                    if (chkbx_ids.length > 0) {
                        $('#form_export_zip').submit();
                    }
                }
                
                if ($(this).val() == 'bulk_clone') {
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
                    var data_scadenza = prompt("Inserisci la data scadenza (in formato gg/mm/aaaa)\nLascia vuoto per calcolo automatico da template pagamento");
                    
                    var _data_scadenza_obj = null;
                    if (data_scadenza) {
                        // alert("Azione annullata");
                        // return false;
                        
                        _data_scadenza_obj = moment(data_scadenza, 'DD/MM/YYYY', true);
                        
                        if (!_data_scadenza_obj._isValid) {
                            alert("Il formato della data scadenza non è corretto. Utilizzare il formato: gg/mm/aaaa");
                            return false;
                        }
                    }
                    
                    // CHECK periodo di competenza
                    var periodo_competenza = prompt("Inserisci il periodo di competenza\n\nSe valorizzato verrà aggiunto nella descrizione delle righe articolo, se lasciato vuoto, la descrizione resterà invariata.");
                    
                    /** SEZIONE TIPOLOGIA (TDXX) */
                    const tipologie_obj = <?php (!empty($tipologie)) ? e_json($tipologie) : '{}'; ?>;
                    
                    let tipologie_arr = [];
                    
                    if (!$.isEmptyObject(tipologie_obj)) {
                        tipologie_arr = tipologie_obj.map(function (_item) {
                            return _item.documenti_contabilita_tipologie_fatturazione_codice + " " + _item.documenti_contabilita_tipologie_fatturazione_descrizione;
                        });
                        tipologie_arr.unshift('---');
                    }
                    
                    /** SEZIONE TIPO DOCUMENTO */
                    const tipi_doc_obj = <?php e_json($tipi_doc); ?>;
                    
                    let tipi_doc_arr = {};
                    
                    tipi_doc_obj.forEach(tipo_doc => {
                        tipi_doc_arr[tipo_doc.documenti_contabilita_tipo_id] = tipo_doc.documenti_contabilita_tipo_value
                    });
                    
                    var swal_tipo_fatt = Swal.fire({
                        title: 'Seleziona una tipologia...',
                        input: 'select',
                        inputOptions: tipologie_arr,
                        inputPlaceholder: 'Seleziona una tipologia...',
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
                        if (choosen.isConfirmed && choosen.value !== '0') {
                            $('#bulk_tipologia').val(choosen.value);
                        }
                        
                        Swal.fire({
                            title: 'Seleziona il tipo documento...',
                            input: 'select',
                            inputOptions: tipi_doc_arr,
                            inputPlaceholder: 'Seleziona il tipo documento...',
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
                                $('#bulk_tipo_doc', $('#form_bulk_clone')).val(choosen.value);
                                $('#bulk_data_emissione', $('#form_bulk_clone')).val(_data_emissione_obj.format('YYYY-MM-DD'));
                                $('#bulk_data_scadenza', $('#form_bulk_clone')).val(_data_scadenza_obj ? _data_scadenza_obj.format('YYYY-MM-DD') : '');
                                $('#bulk_periodo_competenza', $('#form_bulk_clone')).val(periodo_competenza);
                                $('#bulk_fatture_ids', $('#form_bulk_clone')).val(JSON.stringify(chkbx_ids));
                                
                                $('#form_bulk_clone').submit();
                            }
                        });
                    });
                }
            });
        });
        
    });
</script>
