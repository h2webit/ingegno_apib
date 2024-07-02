<!DOCTYPE html>
<html>

<head>
    <title><?php echo strtoupper($document['billing_documents_type_value']); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>template/adminlte/bower_components/bootstrap/dist/css/bootstrap.css?v=1" />
    <?php $this->layout->addModuleStylesheet('firecrm', 'css/billing_pdf.css'); ?>
</head>

<body class="a4_paper">
    <div id="page_1>" class="new_page">
        <div class="big_table">
            <div class="container">
                <div class="row small-margin">
                    <div class="col-md-3">
                        <?php if (!empty($settings['settings_company_logo'])) : ?>
                            <img src="<?php echo $settings['settings_company_logo']; ?>" alt="" class="m_logo" />
                        <?php else : ?>
                            <div class="alt_logo"><?php echo $settings['settings_company_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 fattura">
                        <div class="blu1">
                            <h1><?php echo strtoupper($document['billing_documents_type_value']); ?></h1>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4 mittente1">
                            <h5 class="mb-3"><strong class="mittente"><?php e('Sender'); ?>:</strong></h5>

                            <div class="company-details">
                                <div><strong class="blue"><?php echo $settings['settings_company_name']; ?></strong></div>
                                <div><?php echo $settings['settings_company_vat_number'] ? 'VAT N. ' . $settings['settings_company_vat_number'] : ''; ?></div>
                                <div><?php echo $settings['settings_company_address']; ?></div>
                                <div><?php echo $settings['settings_company_city']; ?><?php echo $settings['settings_company_province'] ? '(' . $settings['settings_company_province'] . ')' : ''; ?></div>
                                <div><?php echo $settings['settings_company_country']; ?></div>
                            </div>
                        </div>

                        <div class="col-sm-4 destinatario">
                            <h5 class="mb-3"><strong class="mittente"><?php e('Recipient'); ?>:</strong></h5>
                            <div><strong class="blue"><?php echo $customer['customers_company']; ?></strong></div>
                            <div><?php echo $customer['customers_address']; ?></div>
                            <div><?php echo $customer['customers_zip_code']; ?><?php echo $customer['customers_city']; ?><?php echo $customer['customers_country'] ? '(' . $customer['customers_country'] . ')' : ''; ?></div>
                            <div><?php echo $customer['customers_state']; ?></div>
                        </div>

                        <div class="col-sm-4">
                            <div class="document">
                                <strong><?php e('Document') ?>: </strong><br>
                                <strong class="blue">nr. <?php echo $document['billing_documents_number']; ?>
                                    del <?php echo dateFormat($document['billing_documents_date']); ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive-sm table_articoli">
                        <table class="table table-cleared">
                            <?php foreach ($items as $item) : ?>
                                <tr class="table_title">
                                    <th><?php e('Product'); ?></th>
                                    <th class="right"><?php e('Price'); ?></th>
                                    <th class="center"><?php e('Quantity'); ?></th>
                                    <th class="right">
                                        <?php e('VAT'); ?> <?php echo number_format((float)$item['taxes_value'], 2, '.', ''); ?>
                                        %
                                    </th>
                                    <th class="right"><?php e('Subtotal'); ?></th>
                                </tr>

                                <tr class="t_rows">
                                    <td class="left strong"><?php echo $item['billing_items_name']; ?>
                                        <br>
                                        <small><?php echo $item['billing_items_description']; ?></small>
                                    </td>
                                    <td class="right"><?php echo $document['currencies_symbol']; ?> <?php echo number_format((float)$item['billing_items_unit_price'], 2, '.', ''); ?></td>
                                    <td class="center"><?php echo $item['billing_items_qty']; ?></td>
                                    <td class="center"></td>
                                    <td class="right"><?php echo $document['currencies_symbol']; ?> <?php echo number_format((float)$item['billing_items_subtotal'], 2, '.', ''); ?></td>
                                </tr>
                            <?php endforeach; ?>


                            <tr>
                                <td></td>
                                <td></td>

                                <td></td>

                                <td colspan="3" class="right"><?php e('Taxes'); ?></td>
                                <td class="right"><?php echo $document['currencies_symbol']; ?> <?php echo number_format((float)$document['billing_documents_taxes'], 2, '.', ''); ?></td>
                            </tr>

                            <tr>
                                <td></td>
                                <td></td>

                                <td></td>
                                <td colspan="3" class="right"><?php e('Subtotal'); ?></td>
                                <td class="right"><?php echo $document['currencies_symbol']; ?> <?php echo number_format((float)$document['billing_documents_subtotal'], 2, '.', ''); ?></td>
                            </tr>

                            <tr class="blu">
                                <td></td>
                                <td class="left"></td>

                                <td class="center"></td>

                                <td class="right document-type" colspan="3">
                                    <strong>

                                    </strong>
                                </td>
                                <td class="right" class="document-total">
                                    <strong><?php echo $document['currencies_symbol']; ?><?php echo number_format((float)$document['billing_documents_total'], 2, '.', ''); ?></strong>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="row ptp-50">
                        <div class="col-sm-6 payment_method">
                            <strong><?php e('PAYMENT METHOD'); ?></strong>

                            <div class="payments">
                                <div class="bank">
                                    <div class="banktext">
                                        <strong><?php echo ucfirst($document['billing_documents_payments_method_value']); ?></strong><br>


                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <strong><?php e('DUE DATE'); ?></strong>

                            <div class="payments">
                                <div class="bank">
                                    <div class="banktext">
                                        <strong>Due
                                            date <?php echo dateFormat($document['billing_documents_due_date']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br><br>
                    <div class="Thanks">
                        <strong><?php e('THANK YOU FOR YOUR BUSINESS!'); ?></strong>
                    </div>

                    <br><br>

                    <div class="services">
                        <?php echo $document['billing_documents_note']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>