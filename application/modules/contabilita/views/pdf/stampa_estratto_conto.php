<?php
$settings = $this->apilib->searchFirst('settings');
$where_azienda = [];

if (!empty($this->input->get('azienda'))) {
    $where_azienda['documenti_contabilita_settings_id'] = $this->input->get('azienda');
}

$azienda = $this->apilib->searchFirst('documenti_contabilita_settings', $where_azienda);
//$customer = $this->apilib->view('customers', $customer_id);

$where = [
    "documenti_contabilita_scadenze_documento IN (SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_customer_id = '{$customer_id}')",
    'documenti_contabilita_tipo IN (1,4,11,12)'
];
if ($this->input->get('solo_insoluti')) {
    $where[] = "(documenti_contabilita_scadenze_saldata = 0 OR documenti_contabilita_scadenze_saldata IS NULL)";
    $where[] = "(documenti_contabilita_scadenze_scadenza < CURRENT_TIMESTAMP)";
}

if (!empty($this->input->get('azienda'))) {
    $where[] = "(documenti_contabilita_azienda = '{$this->input->get('azienda')}')";
}

$scadenze = $this->apilib->search('documenti_contabilita_scadenze', $where, null, 0, 'documenti_contabilita_scadenze_scadenza');


$titolo = "Estratto conto ".($this->input->get('solo_insoluti')?'insoluti ':'')."del " . date('d/m/Y') . " - {$customer['customers_full_name']}";
?>

<style>
    .prima_nota_odd {
        /*background-color: #FF7ffc;*/
        background-color: #eeeeee;
    }

    .prima_nota_odd .table,
    .prima_nota_table_container_even .table {
        /*background-color: #FFAffA;
        background-color: #80b4d3;*/
        background-color: #9ac6e0;
    }



    .js_prime_note tbody tr td {
        padding: 2px;
        border-left: 1px dotted #CCC;
    }

    .js_prime_note tbody tr td:last-child {

        border-right: 1px dotted #CCC;
    }

    .intestazione {
        padding-top: 20px;
        padding-bottom: 30px;
    }

    .intestazione_estratto_conto {
        padding: 10px;
        border-radius: 3px;
        margin-bottom: 20px;
    }

    .intestazione_estratto_conto h3 {
        margin: 0;
        font-size: 22px;
    }

    .js_prime_note thead tr {
        font-size: 14px;
    }

    .js_prime_note tbody tr {
        font-size: 12px;
    }

    .js_prime_note tfoot tr {
        font-size: 14px;
    }
</style>

<!-- CDN Stylesheets -->
<link rel="stylesheet"
    href="<?php echo base_url("template/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css"); ?>" />

<div>

    <div class="page">

        <div class="container-fluid">

            <div class="row intestazione">
                <div class="col-sm-2">
                    <img src="<?php echo base_url('uploads/' . $azienda['documenti_contabilita_settings_company_logo']); ?>"
                        class="img-responsive" style="max-height: 100px;">
                </div>
                <div class="col-sm-10 text-right">
                    <strong>
                        <?php echo $azienda['documenti_contabilita_settings_company_name']; ?>
                    </strong> <br />
                    <?php echo $azienda['documenti_contabilita_settings_company_address'] ?> -
                    <?php echo $azienda['documenti_contabilita_settings_company_city'] ? $azienda['documenti_contabilita_settings_company_city'] : '/' ?>
                    <?php echo $azienda['documenti_contabilita_settings_company_zipcode'] ? $azienda['documenti_contabilita_settings_company_zipcode'] : ''; ?>
                    <?php echo $azienda['documenti_contabilita_settings_company_province'] ? '(' . $azienda['documenti_contabilita_settings_company_province'] . ')' : ''; ?><br />
                    <?php echo t('C.F.'), ': ', $azienda['documenti_contabilita_settings_company_codice_fiscale'] ? $azienda['documenti_contabilita_settings_company_codice_fiscale'] : '/'; ?>
                    -
                    <?php echo t('P.IVA'), ': ', $azienda['documenti_contabilita_settings_company_vat_number'] ? $azienda['documenti_contabilita_settings_company_vat_number'] : '/'; ?>
                </div>
            </div>
            <?php if (!empty($titolo)): ?>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="intestazione_estratto_conto bg-success text-uppercase">
                            <h3 class="text-center">
                                <?php echo $titolo; ?>
                            </h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            //debug($dati, true);
            $progressivo = $i = 0;

            ?>

            <table class="table table-bordered table-condensed js_prime_note">
                <thead>
                    <tr>
                        <th>Nr fattura</th>
                        <th>Data fattura</th>
                        <th>Data scadenza</th>


                        <th>Imponibile</th>
                        <th>Iva</th>
                        <th>Importo totale</th>
                        <th>Progressivo insoluto</th>

                    </tr>
                </thead>
                <tbody>


                    <?php foreach ($scadenze as $scadenza): ?>

                        <?php
                        $i++;
                        if (in_array($scadenza['documenti_contabilita_tipo'], [4, 12])) {
                            $scadenza['documenti_contabilita_scadenze_ammontare'] = -$scadenza['documenti_contabilita_scadenze_ammontare'];
                            $scadenza['documenti_contabilita_imponibile'] = -$scadenza['documenti_contabilita_imponibile'];
                            $scadenza['documenti_contabilita_iva'] = -$scadenza['documenti_contabilita_iva'];
                        }
                        if (!$scadenza['documenti_contabilita_scadenze_saldata']) {

                            $progressivo += $scadenza['documenti_contabilita_scadenze_ammontare'];
                        }


                        ?>
                        <tr
                            class="js_tr_prima_nota <?php echo (is_odd($i)) ? 'prima_nota_odd' : 'prima_nota_even'; ?> <?php if (!$scadenza['documenti_contabilita_scadenze_saldata']): ?>text-danger<?php else: ?>text-success<?php endif; ?>">
                            <td>
                                <?php echo ($scadenza['documenti_contabilita_numero']); ?>
                                <?php if ($scadenza['documenti_contabilita_serie']): ?>/
                                    <?php echo ($scadenza['documenti_contabilita_serie']); ?>
                                <?php endif; ?>
                                <?php if (in_array($scadenza['documenti_contabilita_tipo'], [4, 12])): ?> *
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo dateFormat($scadenza['documenti_contabilita_data_emissione']); ?>
                            </td>
                            <td>
                                <?php echo dateFormat($scadenza['documenti_contabilita_scadenze_scadenza']); ?>
                            </td>
                            <td>
                                <?php e_money($scadenza['documenti_contabilita_imponibile']); ?>
                            </td>
                            <td>
                                <?php e_money($scadenza['documenti_contabilita_iva']); ?>
                            </td>
                            <td>
                                <?php e_money($scadenza['documenti_contabilita_scadenze_ammontare']); ?>
                            </td>

                            <td>
                                <?php e_money($progressivo); ?>
                            </td>


                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5"></th>
                        <th class="text-left text-uppercase">Totali:</th>

                        <th class="text-left">
                            <?php e_money($progressivo); ?>
                        </th>

                    </tr>
                </tfoot>
            </table>


        </div>
        * Nota di credito
    </div>
</div>
