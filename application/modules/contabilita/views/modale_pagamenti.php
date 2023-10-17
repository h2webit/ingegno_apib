<style>
    #js_product_table>tbody>tr>td,
    #js_product_table>tbody>tr>th,
    #js_product_table>tfoot>tr>td,
    #js_product_table>tfoot>tr>th,
    #js_product_table>thead>tr>td {
        vertical-align: top;
    }

    .modal-body .row {
        margin-left: 0px !important;
        margin-right: 0px !important;
    }

    button {
        outline: none;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
    }

    .button_selected {
        opacity: 0.6;
    }

    .table_prodotti td {
        vertical-align: top;
    }

    .totali label {
        display: block;
        font-weight: normal;
        text-align: left;
    }

    .totali label span {
        font-weight: bold;
        float: right;
    }

    label {
        font-size: 0.8em;
    }

    .rcr-adjust {
        width: 40%;
        display: inline;
    }

    .rcr_label label {
        width: 100%;
    }

    .margin-bottom-5 {
        margin-bottom: 5px;
    }

    .margin-left-20 {
        margin-left: 20px;
    }
</style>

<?php



$serie_documento = $this->apilib->search('documenti_contabilita_serie');
$conti_correnti = $this->apilib->search('conti_correnti');
$documento_id = $this->input->get('documenti_contabilita_contabilita_id');
$documenti_tipo = $this->apilib->search('documenti_contabilita_tipo');

$documento_id = $value_id;

if ($documento_id) {

    $documento = $this->apilib->view('documenti_contabilita', $documento_id);
    $documento['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);

}

$metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');

?>


<form class="formAjax" id="new_fattura" action="<?php echo base_url('contabilita/documenti/edit_scadenze'); ?>">
    <?php add_csrf(); ?>
    <?php if ($documento_id) : ?>
        <input name="documento_id" type="hidden" value="<?php echo $documento_id; ?>" />
    <?php endif; ?>

    <input type="hidden" name="documenti_contabilita_totale" value="<?php echo $documento['documenti_contabilita_totale']; ?>" />


    <div class="row">

        <div class="col-md-12 scadenze_box" style="background-color:#eeeeee;">
            <div class="row">
                <div class="col-md-12">
                    <h4>Scadenza pagamento</h4>
                </div>
            </div>

            <div class="row js_rows_scadenze">
                <?php if ($documento_id) : ?>
                    <?php foreach ($documento['scadenze'] as $key => $scadenza) : ?>
                        <div class="row row_scadenza">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input class="js_documenti_contabilita_scadenze_id" type="hidden" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_id]" data-name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_id]" value="<?php echo $scadenza['documenti_contabilita_scadenze_id']; ?>" />
                                    <label>Ammontare</label> <input type="text" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_ammontare]" class="form-control documenti_contabilita_scadenze_ammontare js_decimal" placeholder="Ammontare" value="<?php echo $scadenza['documenti_contabilita_scadenze_ammontare']; ?>" data-name="documenti_contabilita_scadenze_ammontare" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_scadenza]" class="form-control" placeholder="Scadenza" value="<?php echo date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_scadenza'])); ?>" data-name="documenti_contabilita_scadenze_scadenza" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fas fa-calendar-alt-alt"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Saldato con</label>
                                    <select name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_saldato_con]" class="js_scadenze_select2 select2" data-name="documenti_contabilita_scadenze_saldato_con" style="display:block;width:100%">
                                        <?php foreach ($metodi_pagamento as $metodo_pagamento) : ?>
                                            <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']; ?>" <?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']) : ?> selected="selected" <?php endif; ?>>
                                                <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <script>
                                        //$('.js_documenti_contabilita_scadenze_saldato_con').val('<?php echo $scadenza['documenti_contabilita_scadenze_saldato_con']; ?>').trigger('change.select2');
                                    </script>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Saldato su</label>
                                    <select name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_saldato_su]" class="js_scadenze_select2 select2" data-name="documenti_contabilita_scadenze_saldato_su" style="display:block;width:100%">
                                        <option <?php if (empty($scadenza['documenti_contabilita_scadenze_saldato_su'])) : ?> selected<?php endif; ?>>---</option>
                                        <?php foreach ($conti_correnti as $conto_corrente) : ?>
                                            <option value="<?php echo $conto_corrente['conti_correnti_id']; ?>" <?php if ($scadenza['documenti_contabilita_scadenze_saldato_su'] == $conto_corrente['conti_correnti_id']) : ?> selected="selected" <?php endif; ?>>
                                                <?php echo ucfirst($conto_corrente['conti_correnti_nome_istituto']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <script>
                                        //$('.js_table_select2<?php echo $key; ?>').val('<?php echo $scadenza['documenti_contabilita_scadenze_saldato_su']; ?>').trigger('change.select2');
                                    </script>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Data saldo</label>
                                    <div class="input-group js_form_datepicker date  field_68">
                                        <input type="text" class="form-control" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_data_saldo]" data-name="documenti_contabilita_scadenze_data_saldo" value="<?php echo ($scadenza['documenti_contabilita_scadenze_data_saldo']) ? date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_data_saldo'])) : ''; ?>">

                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none;"><i class="fas fa-calendar-alt-alt"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div id="msg_new_fattura" class="alert alert-danger hide"></div>
                </div>
            </div>
        </div>
    </div>


    <div class="form-actions fluid">
        <div class="col-md-offset-8 col-md-4">
            <div class="pull-right">

                <button type="submit" class="btn btn-primary">Salva
                </button>
            </div>
        </div>

    </div>
    </div>
</form>


<script>
    $(document).ready(function() {
        $('.js_scadenze_select2').each(function() {
            var select = $(this);
            var placeholder = select.attr('data-placeholder');
            $(this).select2({
                placeholder: placeholder ? placeholder : '',
                allowClear: true
            });
        });
    });


    function increment_scadenza() {
        var counter_scad = $('.row_scadenza').length;
        var rows_scadenze = $('.js_rows_scadenze');
        // Fix per clonare select inizializzata
        $('.js_table_select2').filter(':first').select2('destroy');

        var newScadRow = $('.row_scadenza').filter(':first').clone();

        // Fix per clonare select inizializzata
        $('.js_table_select2').filter(':first').select2();

        /* Line manipulation begin */
        //newScadRow.removeClass('hidden');
        $('input, select, textarea', newScadRow).each(function() {
            var control = $(this);
            var name = control.attr('data-name');
            control.attr('name', 'scadenze[' + counter_scad + '][' + name + ']').removeAttr('data-name');
        });

        $('.js_table_select2', newScadRow).select2({
            //placeholder: "Seleziona prodotto",
            allowClear: true
        });

        $('.js_form_datepicker input', newScadRow).datepicker({
            todayBtn: 'linked',
            format: 'dd/mm/yyyy',
            todayHighlight: true,
            weekStart: 1,
            language: 'it'
        });

        $('.js_documenti_contabilita_scadenze_id', newScadRow).remove();

        /* Line manipulation end */
        counter_scad++;
        newScadRow.appendTo(rows_scadenze);
    }

    $(document).ready(function() {
        var table = $('#js_product_table');
        var body = $('tbody', table);
        var rows = $('tr', body);
        var increment = $('#js_add_product', table);
        var totale = <?php echo $documento['documenti_contabilita_totale']; ?>;
        var rows_scadenze = $('.js_rows_scadenze');
        //var increment_scadenza = $('#js_add_scadenza');

        //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
        rows_scadenze.on('change', '.documenti_contabilita_scadenze_ammontare', function() {
            //Se la somma degli ammontare è minore del totale procedo
            var totale_scadenze = 0;
            $('.documenti_contabilita_scadenze_ammontare').each(function() {
                totale_scadenze += parseFloat($(this).val());
            });

            /*
             * La logica è questa:
             * 1. se le scadenza superano l'importo totale, metto a posto togliendo ricorsivamente la riga sucessiva finchè non entro nel caso 2
             * 2. se le scadenza non superano l'importo totale, tolgo tutte le righe sucessiva all'ultima modificata, ne creo una nuova e forzo importo corretto sull'ultima
             */
            next_row_exists = $(this).closest('.row_scadenza').next('.row_scadenza').length != 0;

            if (totale_scadenze < totale) {
                if (next_row_exists) {
                    console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                    $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                    $(this).trigger('change');
                } else {
                    console.log('Non esiste scadenza successiva. Creo...');
                    //$('#js_add_scadenza').trigger('click');
                    increment_scadenza();
                    next_row = $(this).closest('.row_scadenza').next('.row_scadenza');
                    $('.documenti_contabilita_scadenze_ammontare', next_row).val((totale - totale_scadenze).toFixed(2));
                }
            } else {
                if (next_row_exists) {
                    console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                    $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                    $(this).trigger('change');
                } else {
                    console.log('Non esiste scadenza successiva. Tutto a posto ma nel dubbio forzo questa = alla differenza tra totale e totale scadenze');
                    $(this).val((totale - (totale_scadenze - $(this).val())).toFixed(2));

                }
            }

        });

        if (rows.length < 2) {
            increment.click();

        }
    });
</script>


<script>
    $(document).ready(function() {
        $('#js_dtable').dataTable({
            aoColumns: [null, null, null, null, null, null, null, {
                bSortable: false
            }],
            aaSorting: [
                [0, 'desc']
            ]
        });
        $('#js_dtable_wrapper .dataTables_filter input').addClass("form-control input-small"); // modify table search input
        $('#js_dtable_wrapper .dataTables_length select').addClass("form-control input-xsmall"); // modify table per page dropdown
    });
</script>
<!-- END Module Related Javascript -->