<?php
// data extraction section

$customers = $this->apilib->search('customers');
$taxes = $this->apilib->search('taxes');
$sale_agents = $this->apilib->search('users');
$paymethods = $this->apilib->search('billing_documents_payments_method');
$doc_types = $this->apilib->search('billing_documents_type');
$templates = $this->apilib->search('billing_documents_templates');

$edit = false;
$billing_document = [];
$billing_items = [];

if (!empty($value_id)) {
    $billing_document = $this->apilib->searchFirst('billing_documents', ['billing_documents_id' => $value_id]);

    if (!empty($billing_document)) {
        $edit = true;

        $billing_items = $this->apilib->search('billing_items', ['billing_items_document' => $value_id]);
    }
}

?>
<?php $this->layout->addModuleStylesheet('projects', 'css/billing_document.css'); ?>
<form id="billing_form" action="<?php echo base_url('projects/billing/saveData'); ?>" method="post" class="formAjax">
    <?php add_csrf(); ?>
    <?php if ($edit) : ?>
        <input type="hidden" name="billing_documents_id" value="<?php echo $value_id ?>">
    <?php endif; ?>

    <div class="box box-info">
        <div class="box-header">
            <h2 class="box-title">Generic Informations</h2>
        </div>
        <div class="box-body">
            <div class="row clearfix">
                <div class="col-md-6">
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="billing_customer">Customer</label>
                            <select class="form-control" name="billing_documents_customer" id="billing_customer">
                                <?php
                                if (!empty($customers)) {
                                    foreach ($customers as $customer) {
                                        $customer_name = null;


                                        if (!empty($customer['customers_company'])) {
                                            $customer_name = $customer['customers_company'];
                                        } else {
                                            $customer_name = $customer['customers_name'] . ' ' . $customer['customers_last_name'];
                                        }

                                        $selected_customer = (($edit && !empty($billing_document['billing_documents_customer'])) && $billing_document['billing_documents_customer'] === $customer['customers_id']) ? 'selected="selected"' : '';

                                        echo "<option value='{$customer['customers_id']}' {$selected_customer}>{$customer_name}</option>\n";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <div class="col-sm-6">
                            <label for="billing_doc_date">Date</label>
                            <input type="text" class="form-control js_form_datepicker" id="billing_doc_date" name="billing_documents_date" value="<?php echo (!empty($billing_document['billing_documents_date'])) ? $billing_document['billing_documents_date'] : date('d/m/Y'); ?>">
                        </div>

                        <div class="col-sm-6">
                            <label for="billing_doc_due_date">Due Date</label>
                            <input type="text" class="form-control js_form_datepicker" id="billing_doc_due_date" name="billing_documents_due_date" value="<?php echo (!empty($billing_document['billing_documents_date'])) ? $billing_document['billing_documents_date'] : date('d/m/Y', strtotime(date('d/m/Y') . '+2 months')) ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label for="billing_doc_number">Invoice Number</label>
                            <input type="text" class="form-control" id="billing_doc_number" name="billing_documents_number" value="<?php echo (!empty($billing_document['billing_documents_number'])) ? $billing_document['billing_documents_number'] : ""; ?>">
                        </div>

                        <div class="col-sm-4">
                            <label for="billing_doc_type">Type</label>

                            <select class="form-control" id="billing_doc_type" name="billing_documents_type">
                                <?php
                                foreach ($doc_types as $type) {
                                    echo "<option value='{$type['billing_documents_type_id']}'>{$type['billing_documents_type_value']}</option>\n";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-sm-4">
                            <label for="billing_doc_paymethod">Payment Method</label>
                            <select class="form-control" id="billing_doc_paymethod" name="billing_documents_payments_method">
                                <?php
                                foreach ($paymethods as $paymethod) {
                                    echo "<option value='{$paymethod['billing_documents_payments_method_id']}'>{$paymethod['billing_documents_payments_method_value']}</option>\n";
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <div class="form-group row">

                        <div class="col-sm-4">
                            <label for="billing_doc_sale_agent">Sale Agent</label>

                            <select class="form-control" id="billing_doc_sale_agent" name="billing_documents_sale_agent">
                                <?php
                                foreach ($sale_agents as $agent) {
                                    echo "<option value='{$agent['users_id']}'>{$agent['users_first_name']} - {$agent['users_last_name']}</option>\n";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-sm-4">
                            <label for="billing_doc_type">PDF Template</label>

                            <select class="form-control" id="billing_doc_tpl" name="billing_documents_template">
                                <?php
                                foreach ($templates as $template) {
                                    echo "<option value='{$template['billing_documents_templates_id']}'>{$template['billing_documents_templates_name']}</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-12">
                            <label for="billing_doc_notes">Notes</label>
                            <textarea type="text" class="form-control" id="billing_doc_notes" name="billing_documents_note"><?php echo (!empty($billing_document['billing_documents_note'])) ? $billing_document['billing_documents_note'] : "" ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header">
            <h2 class="box-title">Billing Items</h2>
        </div>

        <div class="box-body">
            <div class="row clearfix">
                <div class="col-md-12">
                    <table class="table table-bordered table-condensed table-striped table-bordered" id="billing_items">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Tax</th>
                                <th>Subtotal</th>
                                <th class="text-center">
                                    <button type="button" name="add" class="btn btn-success btn-xs" id="js_add_row"><i class="glyphicon glyphicon-plus"></i> Add</button>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr class="hidden">
                                <td><input type="text" class="form-control item_name w100" data-name="billing_items_name" tabindex="1" placeholder="Name" /></td>
                                <td><textarea class="form-control item_description w100" data-name="billing_items_description" cols="30" rows="10" tabindex="2" placeholder="Description"></textarea></td>
                                <td><input type="number" min="0" step="0.01" class="form-control text-right item_qty w100" value="0.00" data-name="billing_items_qty" tabindex="3" placeholder="Quantity" /></td>
                                <td><input type="number" min="0" step="0.01" class="form-control text-right item_unit_price w100" value="0.00" data-name="billing_items_unit_price" tabindex="4" placeholder="Unit Price" /></td>

                                <td>
                                    <select class="form-control item_tax" data-name="billing_items_tax" tabindex="5">
                                        <?php foreach ($taxes as $tax) : ?>
                                            <option value="<?php echo $tax['taxes_id']; ?>" data-perc="<?php echo $tax['taxes_value'] ?>"><?php echo $tax['taxes_value'] ?> %</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td><input type="number" min="0" step="0.01" class="form-control text-right item_subtotal w100" data-name="billing_items_subtotal" tabindex="6" /></td>

                                <td class="text-center">
                                    <button type="button" class="btn  btn-danger js_delete_row">
                                        <span class="fa fa-times"></span>
                                    </button>

                                    <?php if ($edit && !empty($value_id)) : ?>
                                        <input type="hidden" data-name="billing_items_document" value="<?php echo $value_id ?>">
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if (!empty($billing_items)) : foreach ($billing_items as $key => $billing_item) : ?>
                                    <tr>
                                        <td><input type="text" class="form-control item_name w100" name="items[<?php echo $key; ?>][billing_items_name]" tabindex="1" placeholder="Name" value="<?php echo $billing_item['billing_items_name']; ?>" /></td>
                                        <td><textarea class="form-control item_description w100" name="items[<?php echo $key; ?>][billing_items_description]" cols="30" rows="10" tabindex="2" placeholder="Description"><?php echo $billing_item['billing_items_description']; ?></textarea></td>
                                        <td><input type="number" min="0" step="0.01" class="form-control text-right item_qty w100" name="items[<?php echo $key; ?>][billing_items_qty]" value="<?php echo $billing_item['billing_items_qty']; ?>" tabindex="3" placeholder="Quantity" /></td>
                                        <td><input type="number" min="0" step="0.01" class="form-control text-right item_unit_price w100" name="items[<?php echo $key; ?>][billing_items_unit_price]" value="<?php echo $billing_item['billing_items_unit_price']; ?>" tabindex="4" placeholder="Unit Price" /></td>

                                        <td>
                                            <select class="form-control item_tax" data-name="billing_items_tax" name="items[<?php echo $key; ?>][billing_items_tax]" tabindex="5">
                                                <?php foreach ($taxes as $tax) : ?>
                                                    <option value="<?php echo $tax['taxes_id']; ?>" data-perc="<?php echo $tax['taxes_value'] ?>" <?php echo (!empty($billing_item['billing_items_tax']) && $billing_item['billing_items_tax'] == $tax['taxes_id']) ? 'selected="selected"' : ''; ?>><?php echo $tax['taxes_value'] ?> %</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>

                                        <td><input type="number" min="0" step="0.01" class="form-control text-right item_subtotal w100" name="items[<?php echo $key; ?>][billing_items_subtotal]" value="<?php echo $billing_item['billing_items_subtotal']; ?>" tabindex="6" /></td>

                                        <td class="text-center">
                                            <button type="button" class="btn  btn-danger js_delete_row">
                                                <span class="fa fa-times"></span>
                                            </button>

                                            <input type="hidden" name="items[<?php echo $key; ?>][billing_items_id]" value="<?php echo $billing_item['billing_items_id'] ?>">
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row clearfix mt-20">
                <div class="pull-right col-md-4">
                    <table class="table table-bordered" id="tab_logic_total">
                        <tbody>
                            <tr>
                                <th class="text-center">Subotal</th>
                                <td class="text-center"><input type="number" name='billing_documents_subtotal' placeholder='0.00' value="<?php echo (!empty($billing_document['billing_documents_subtotal'])) ? $billing_document['billing_documents_subtotal'] : 0; ?>" class="form-control" id="subtotal" readonly /></td>
                            </tr>
                            <tr>
                                <th class="text-center">Taxes</th>
                                <td class="text-center"><input type="number" name='billing_documents_taxes' id="taxes" placeholder='0.00' value="<?php echo (!empty($billing_document['billing_documents_taxes'])) ? $billing_document['billing_documents_subtotal'] : 0; ?>" class="form-control" readonly /></td>
                            </tr>
                            <tr>
                                <th class="text-center">Total</th>
                                <td class="text-center"><input type="number" name='billing_documents_total' id="total" placeholder='0.00' value="<?php echo (!empty($billing_document['billing_documents_total'])) ? $billing_document['billing_documents_subtotal'] : 0; ?>" class="form-control" readonly /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div id="msg_billing_form">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <button type="submit" class="btn btn-success btn-lg pull-right"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
</form>

<script>
    'use strict';
    var token = JSON.parse(atob($('body').data('csrf')));
    var token_name = token.name;
    var token_hash = token.hash;

    function calculateTotals() {
        var total = <?php echo (!empty($billing_document['billing_documents_subtotal'])) ? $billing_document['billing_documents_subtotal'] : 0; ?>;
        var vat = <?php echo (!empty($billing_document['billing_documents_taxes'])) ? $billing_document['billing_documents_taxes'] : 0; ?>;
        var total_no_vat = <?php echo (!empty($billing_document['billing_documents_total'])) ? $billing_document['billing_documents_total'] : 0; ?>;

        $('#billing_items tbody tr:not(.hidden)').each(function() {
            var qty = parseFloat($('.item_qty', $(this)).val());
            var price = parseFloat($('.item_unit_price', $(this)).val());
            var vat_val = parseInt($('.item_tax option:selected', $(this)).data('perc'));

            if (isNaN(qty) || typeof qty == 'undefined') {
                qty = 0;
            }

            if (isNaN(price) || typeof price == 'undefined') {
                price = 0;
            }

            if (isNaN(vat_val) || typeof vat_val == 'undefined') {
                vat_val = 0;
            }

            var totale_riga = price * qty;

            total_no_vat += totale_riga;
            if (vat_val > 0) {
                vat += (totale_riga * vat_val) / 100;
            }
        });

        total += (total_no_vat + vat);


        $('#subtotal').val(total_no_vat.toFixed(2));
        $('#taxes').val(vat.toFixed(2));
        $('#total').val(total.toFixed(2));
    }

    $('#billing_items').on('keyup change', '.item_qty, .item_unit_price, .item_tax, .item_subtotal', function() {
        var tr = $(this).closest('tr');

        var subtotal_calc = 0;
        var quantity = parseFloat($('.item_qty', tr).val());
        var unit_price = parseFloat($('.item_unit_price', tr).val());
        var tax = parseInt($('.item_tax option:selected', tr).data('perc'));
        var subtotal = $('.item_subtotal', tr);

        subtotal_calc = parseFloat(quantity * unit_price);

        if (typeof subtotal_calc != 'undefined' && subtotal_calc != null && $.isNumeric(subtotal_calc)) {
            subtotal.val(subtotal_calc.toFixed(2));
        }

        calculateTotals();
    });

    $(document).ready(function() {

    });

    $(document).ready(function() {
        $('select').select2();

        const table = $('#billing_items');
        const body = $('tbody', table);
        const rows = $('tr', body);
        const increment = $('#js_add_row', table);
        const firstRow = rows.filter(':first');
        let counter = rows.length;

        increment.on('click', function() {
            const newRow = firstRow.clone();

            newRow.removeClass('hidden');

            $('input, select, textarea', newRow).each(function() {
                const control = $(this);
                const name = control.attr('data-name');

                if (name) {
                    control.attr('name', 'items[' + counter + '][' + name + ']').removeAttr('data-name');
                }
            });

            counter++;
            newRow.appendTo(body);

            calculateTotals();
        });

        table.on('click', '.js_delete_row', function() {
            $(this).parents('tr').remove();
            $('#billing_items .item_qty').trigger('change');
        });

        <?php if (!$edit || ($edit && empty($billing_items))) : ?>
            setTimeout(function() {
                $('#js_add_row').trigger('click');
            }, 10);
        <?php endif; ?>

        /*$('#billing_customer').on('change', function() {
            var customer_id = $(this).val();

            $.ajax({
                url: base_url + 'firecrm/billing/getCustomerAddresses',
                type: "POST",
                data: {
                    'customer_id': customer_id,
                    [token_name]: token_hash
                },
                async: false,
                success: function(response, textStatus, jqXHR) {
                    console.log(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        });*/
    });
</script>