<style>
    .highlight-green {
        /*background-color: #d4edda;*/
    }
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

$anno = $this->input->get('anno');
$mese = $this->input->get('mese');
$all = $this->input->get('all');
$primo_del_mese = date('Y-m-d', strtotime('first day of ' . $anno . '-' . $mese));
$ultimo_del_mese = date('Y-m-d', strtotime('last day of ' . $anno . '-' . $mese));

if ($all) {
    $data_da = $this->input->get('data_da');
    $data_a = $this->input->get('data_a');
} else {
    $data_da = $primo_del_mese;
    $data_a = $ultimo_del_mese;
}


$cliente = $this->input->get('cliente');

$payments = $this->apilib->search('payments',[
    'payments_customer' => $cliente,
    'payments_date >=' => $data_da,
    'payments_date <=' => $data_a,
    'payments_canceled <> 1'
]);
$totale = array_sum(array_column($payments,'payments_amount'));

?>


<form id="form_multiple_payments" class="formAjax" action="<?php echo base_url('payments-subscriptions/ajax/save_payments'); ?>">
    <?php add_csrf(); ?>
    

    <input type="hidden" name="totale" value="<?php echo $totale; ?>" />
    <input type="hidden" name="customer" value="<?php echo $cliente; ?>" />


    <div class="row">

        <div class="col-md-12 scadenze_box" style="background-color:#eeeeee;">
            <div class="row">
                <div class="col-md-12">
                    <h4>Pagamenti</h4>
                </div>
            </div>

            <div class="row js_rows_scadenze">
                
                    <?php foreach ($payments as $key => $payment) : ?>
                        <?php
                            $payment_date = date('Y-m-d', strtotime($payment['payments_date']));
                            $highlight_class = ($payment_date >= $primo_del_mese && $payment_date <= $ultimo_del_mese) ? 'highlight-green' : '';
                            ?>
                        <div class="row row_scadenza <?php echo $highlight_class; ?>">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input class="js_payments_id" type="hidden" name="payments[<?php echo $key; ?>][payments_id]" data-name="payments[<?php echo $key; ?>][payments_id]" value="<?php echo $payment['payments_id']; ?>" />

                                    <label>Ammontare</label> <input type="text" name="payments[<?php echo $key; ?>][payments_amount]" class="form-control payments_amount js_decimal" placeholder="Ammontare" value="<?php echo $payment['payments_amount']; ?>" data-name="payments_amount" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text" name="payments[<?php echo $key; ?>][payments_date]" class="form-control" placeholder="Scadenza" value="<?php echo date('d/m/Y', strtotime($payment['payments_date'])); ?>" data-name="payments_date" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fas fa-calendar-alt-alt"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    
                                    <label>Pagato/incassato</label> <input type="checkbox" name="payments[<?php echo $key; ?>][payments_paid]"
                                        class="payments_paid " 
                                        value="1" data-name="payments_paid" <?php if ($payment['payments_paid']) : ?> checked<?php endif; ?> /> 
                                            
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    
                                    <label>Fatturato</label> <input type="checkbox" name="payments[<?php echo $key; ?>][payments_invoice_sent]"
                                        class="payments_invoice_sent " value="1" data-name="payments_invoice_sent" <?php if ($payment['payments_invoice_sent']): ?>
                                            checked<?php endif; ?> />
                            
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    
                                    <label>Approvato</label> <input type="checkbox" name="payments[<?php echo $key; ?>][payments_approved]"
                                        class="payments_approved " value="1" data-name="payments_approved" <?php if ($payment['payments_approved']): ?>
                                            checked<?php endif; ?> />
                            
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div id="msg_form_multiple_payments" class="alert alert-danger hide"></div>
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
   


    function increment_scadenza() {
        var counter_scad = $('.row_scadenza').length;
        var rows_scadenze = $('.js_rows_scadenze');
        
        var newScadRow = $('.row_scadenza').filter(':first').clone();
        $('input, select, textarea', newScadRow).each(function() {
            var control = $(this);
            var name = control.attr('data-name');
            control.attr('name', 'payments[' + counter_scad + '][' + name + ']').removeAttr('data-name');
        });

       

        $('.js_form_datepicker input', newScadRow).datepicker({
            todayBtn: 'linked',
            format: 'dd/mm/yyyy',
            todayHighlight: true,
            weekStart: 1,
            language: 'it'
        });

        $('.js_payments_id', newScadRow).remove();

        /* Line manipulation end */
        counter_scad++;
        newScadRow.appendTo(rows_scadenze);
    }

    $(document).ready(function() {
        
        var totale = <?php echo $totale; ?>;
        var rows_scadenze = $('.js_rows_scadenze');
        //var increment_scadenza = $('#js_add_scadenza');

        //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
        rows_scadenze.on('change', '.payments_amount', function() {
            //Se la somma degli ammontare è minore del totale procedo
            var totale_scadenze = 0;
            $('.payments_amount').each(function() {
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
                    $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                    $(this).trigger('change');
                } else {
                    increment_scadenza();
                    next_row = $(this).closest('.row_scadenza').next('.row_scadenza');
                    $('.payments_amount', next_row).val((totale - totale_scadenze).toFixed(2));
                }
            } else {
                if (next_row_exists) {
                    $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                    $(this).trigger('change');
                } else {
                    $(this).val((totale - (totale_scadenze - $(this).val())).toFixed(2));

                }
            }

        });

        
    });
</script>

