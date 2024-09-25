<?php $tipi_doc = $this->db->get('documenti_contabilita_tipo')->result_array(); ?>

<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.4.37/dist/sweetalert2.all.min.js'></script>

<form style="display: hidden" method="POST" id="form_contabilita">
    <?php add_csrf(); ?>
</form>

<form action="<?php echo base_url(); ?>main/layout/nuovo_documento?" method="POST" id="genera_documento">
    <?php add_csrf(); ?>

    <input type="hidden" id="documenti_contabilita_articoli_ids_qtys" name="documenti_contabilita_articoli_ids_qtys" value="" />

    <input type="hidden" id="cl_bulk_action" name="bulk_action" value="" />
</form>

<form action="<?php echo base_url(); ?>main/layout/nuovo_movimento?" method="POST" id="genera_movimento">
    <?php add_csrf(); ?>

    <input type="hidden" id="mov_documenti_contabilita_articoli_ids_qtys" name="documenti_contabilita_articoli_ids_qtys"
        value="" />
    <input type="hidden" id="cl_bulk_action" name="bulk_action" value="" />
</form>

<script>
    $(function () {
    var grid_container = $('table.js_prodotti_in_ordine');
    var layout_box_container = grid_container.closest('div[data-layout-box]');

    var $js_bulk_action = $('.js-bulk-action', layout_box_container);

    if ($('option[value="genera_documento"]', $js_bulk_action).length > 0) {
        return;
    }
    $js_bulk_action.append('<option value="genera_documento">Genera documento</option>');
    $js_bulk_action.append('<option value="movimenta_articoli">Movimenta articoli</option>');

    $js_bulk_action.on('change', function () {
        var customers = [];
        var selectedData = {};

        $("input:checkbox.js_bulk_check:checked", grid_container).each(function () {
            var trow = $(this).closest('tr');
            var customer = $('span.js_customers', trow).data('customer');
            customers.push(customer);
            
            var id = $(this).val();
            var qty = $('input[name="qty"]', trow).val() || 1; // Default to 1 if qty is not found
            selectedData[id] = qty;
        });

        var uniqueCustomers = Array.from(new Set(customers));
        
        if ($(this).val() !== '' && uniqueCustomers.length >= 2) {
            alert("Hai selezionato righe ordini di clienti diversi. Seleziona solo gli ordini di stessi clienti!");

            $js_bulk_action.val('').trigger('change');
            return false;
        }

        if (Object.keys(selectedData).length <= 0) {
            $("input:checkbox.js_bulk_check:checked", grid_container).val('').trigger('change');
            return false;
        }

        if (Object.keys(selectedData).length > 0) {
            if ($(this).val() == 'genera_documento') {
                $('#documenti_contabilita_articoli_ids_qtys').val(JSON.stringify(selectedData));

                const tipi_doc_obj = <?php e_json($tipi_doc); ?>;

                        let tipi_doc_arr = {};

                        tipi_doc_obj.forEach(tipo_doc => {
                            tipi_doc_arr[tipo_doc.documenti_contabilita_tipo_id] = tipo_doc.documenti_contabilita_tipo_value
                        });

                        Swal.fire({
                            title: 'Seleziona il tipo documento da generare...',
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
                                $('#genera_documento').prop('action', $('#genera_documento').prop('action') + '&doc_type=' + tipi_doc_arr[choosen.value]);
                                $('#genera_documento').submit();
                            }
                        });
                    } else if ($(this).val() == 'movimenta_articoli') {
                        $('#mov_documenti_contabilita_articoli_ids_qtys').val(JSON.stringify(selectedData));
                        $('#genera_movimento').submit();
                    }
                }
            });
        });
</script>