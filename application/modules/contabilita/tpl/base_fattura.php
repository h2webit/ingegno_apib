<?php

//error_reporting(0);  //*********************** <!---------------- DISATTIVARE IN CASO DI DEBUG ------------------->

if (empty($this->input->get("documento_id")) && empty($documento_id)) {
    die("Documento non valido o non piu disponibile");
}
$id = (isset($documento_id)) ? $documento_id : $this->input->get("documento_id");

$documento = $this->apilib->view('documenti_contabilita', $id);

$segno = '';
if ($documento['documenti_contabilita_tipo'] == 4) { //Se è una nota di credito
    $segno = '-';
}

unset($documento['documenti_contabilita_file']);

//debug($documento,true);

$articoli = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $id], null, 0, 'documenti_contabilita_articoli_position');

$iva = [];
foreach ($articoli as $articolo) {
    if (!empty($articolo['iva_descrizione'])) {
        $iva[] = ['valore' => $segno . $articolo['iva_valore'], 'natura' => $articolo['iva_codice'], 'descrizione' => $articolo['iva_descrizione']];
    }
}

$destinatario = json_decode($documento['documenti_contabilita_destinatario'], true);

$scadenze = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $id]);

$settings = $this->apilib->view('documenti_contabilita_settings', $documento['documenti_contabilita_azienda']);

$valuta = array(
    'AED' => '&#1583;.&#1573;',
    // ?
    'AFN' => '&#65;&#102;',
    'ALL' => '&#76;&#101;&#107;',
    'AMD' => '',
    'ANG' => '&#402;',
    'AOA' => '&#75;&#122;',
    // ?
    'ARS' => '&#36;',
    'AUD' => '&#36;',
    'AWG' => '&#402;',
    'AZN' => '&#1084;&#1072;&#1085;',
    'BAM' => '&#75;&#77;',
    'BBD' => '&#36;',
    'BDT' => '&#2547;',
    // ?
    'BGN' => '&#1083;&#1074;',
    'BHD' => '.&#1583;.&#1576;',
    // ?
    'BIF' => '&#70;&#66;&#117;',
    // ?
    'BMD' => '&#36;',
    'BND' => '&#36;',
    'BOB' => '&#36;&#98;',
    'BRL' => '&#82;&#36;',
    'BSD' => '&#36;',
    'BTN' => '&#78;&#117;&#46;',
    // ?
    'BWP' => '&#80;',
    'BYR' => '&#112;&#46;',
    'BZD' => '&#66;&#90;&#36;',
    'CAD' => '&#36;',
    'CDF' => '&#70;&#67;',
    'CHF' => '&#67;&#72;&#70;',
    'CLF' => '',
    // ?
    'CLP' => '&#36;',
    'CNY' => '&#165;',
    'COP' => '&#36;',
    'CRC' => '&#8353;',
    'CUP' => '&#8396;',
    'CVE' => '&#36;',
    // ?
    'CZK' => '&#75;&#269;',
    'DJF' => '&#70;&#100;&#106;',
    // ?
    'DKK' => '&#107;&#114;',
    'DOP' => '&#82;&#68;&#36;',
    'DZD' => '&#1583;&#1580;',
    // ?
    'EGP' => '&#163;',
    'ETB' => '&#66;&#114;',
    'EUR' => '&#8364;',
    'FJD' => '&#36;',
    'FKP' => '&#163;',
    'GBP' => '&#163;',
    'GEL' => '&#4314;',
    // ?
    'GHS' => '&#162;',
    'GIP' => '&#163;',
    'GMD' => '&#68;',
    // ?
    'GNF' => '&#70;&#71;',
    // ?
    'GTQ' => '&#81;',
    'GYD' => '&#36;',
    'HKD' => '&#36;',
    'HNL' => '&#76;',
    'HRK' => '&#107;&#110;',
    'HTG' => '&#71;',
    // ?
    'HUF' => '&#70;&#116;',
    'IDR' => '&#82;&#112;',
    'ILS' => '&#8362;',
    'INR' => '&#8377;',
    'IQD' => '&#1593;.&#1583;',
    // ?
    'IRR' => '&#65020;',
    'ISK' => '&#107;&#114;',
    'JEP' => '&#163;',
    'JMD' => '&#74;&#36;',
    'JOD' => '&#74;&#68;',
    // ?
    'JPY' => '&#165;',
    'KES' => '&#75;&#83;&#104;',
    // ?
    'KGS' => '&#1083;&#1074;',
    'KHR' => '&#6107;',
    'KMF' => '&#67;&#70;',
    // ?
    'KPW' => '&#8361;',
    'KRW' => '&#8361;',
    'KWD' => '&#1583;.&#1603;',
    // ?
    'KYD' => '&#36;',
    'KZT' => '&#1083;&#1074;',
    'LAK' => '&#8365;',
    'LBP' => '&#163;',
    'LKR' => '&#8360;',
    'LRD' => '&#36;',
    'LSL' => '&#76;',
    // ?
    'LTL' => '&#76;&#116;',
    'LVL' => '&#76;&#115;',
    'LYD' => '&#1604;.&#1583;',
    // ?
    'MAD' => '&#1583;.&#1605;.',
    //?
    'MDL' => '&#76;',
    'MGA' => '&#65;&#114;',
    // ?
    'MKD' => '&#1076;&#1077;&#1085;',
    'MMK' => '&#75;',
    'MNT' => '&#8366;',
    'MOP' => '&#77;&#79;&#80;&#36;',
    // ?
    'MRO' => '&#85;&#77;',
    // ?
    'MUR' => '&#8360;',
    // ?
    'MVR' => '.&#1923;',
    // ?
    'MWK' => '&#77;&#75;',
    'MXN' => '&#36;',
    'MYR' => '&#82;&#77;',
    'MZN' => '&#77;&#84;',
    'NAD' => '&#36;',
    'NGN' => '&#8358;',
    'NIO' => '&#67;&#36;',
    'NOK' => '&#107;&#114;',
    'NPR' => '&#8360;',
    'NZD' => '&#36;',
    'OMR' => '&#65020;',
    'PAB' => '&#66;&#47;&#46;',
    'PEN' => '&#83;&#47;&#46;',
    'PGK' => '&#75;',
    // ?
    'PHP' => '&#8369;',
    'PKR' => '&#8360;',
    'PLN' => '&#122;&#322;',
    'PYG' => '&#71;&#115;',
    'QAR' => '&#65020;',
    'RON' => '&#108;&#101;&#105;',
    'RSD' => '&#1044;&#1080;&#1085;&#46;',
    'RUB' => '&#1088;&#1091;&#1073;',
    'RWF' => '&#1585;.&#1587;',
    'SAR' => '&#65020;',
    'SBD' => '&#36;',
    'SCR' => '&#8360;',
    'SDG' => '&#163;',
    // ?
    'SEK' => '&#107;&#114;',
    'SGD' => '&#36;',
    'SHP' => '&#163;',
    'SLL' => '&#76;&#101;',
    // ?
    'SOS' => '&#83;',
    'SRD' => '&#36;',
    'STD' => '&#68;&#98;',
    // ?
    'SVC' => '&#36;',
    'SYP' => '&#163;',
    'SZL' => '&#76;',
    // ?
    'THB' => '&#3647;',
    'TJS' => '&#84;&#74;&#83;',
    // ? TJS (guess)
    'TMT' => '&#109;',
    'TND' => '&#1583;.&#1578;',
    'TOP' => '&#84;&#36;',
    'TRY' => '&#8356;',
    // New Turkey Lira (old symbol used)
    'TTD' => '&#36;',
    'TWD' => '&#78;&#84;&#36;',
    'TZS' => '',
    'UAH' => '&#8372;',
    'UGX' => '&#85;&#83;&#104;',
    'USD' => '&#36;',
    'UYU' => '&#36;&#85;',
    'UZS' => '&#1083;&#1074;',
    'VEF' => '&#66;&#115;',
    'VND' => '&#8363;',
    'VUV' => '&#86;&#84;',
    'WST' => '&#87;&#83;&#36;',
    'XAF' => '&#70;&#67;&#70;&#65;',
    'XCD' => '&#36;',
    'XDR' => '',
    'XOF' => '',
    'XPF' => '&#70;',
    'YER' => '&#65020;',
    'ZAR' => '&#82;',
    'ZMK' => '&#90;&#75;',
    // ?
    'ZWL' => '&#90;&#36;',
);

$gruppi_articoli = [];

$max_items = 8;
for ($i = 1; $i <= ceil(count($articoli) / $max_items); $i++) {
    $gruppi_articoli[$i] = array_slice($articoli, $max_items * ($i - 1), $max_items, true);
}


if (!empty($this->input->get('debug'))) {
    switch ($this->input->get('debug')) {
        case '1':
            debug($documento, true);
            break;
        case '2':
            debug($articoli, true);
            break;
        case '3':
            debug($settings, true);
            break;
        case '4':
            debug($old_settings, true);
            break;
        case '5':
            debug($scadenze, true);
            break;
    }
}

$_metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');
$metodi_pagamento = array_key_value_map($_metodi_pagamento, 'documenti_contabilita_metodi_pagamento_id', 'documenti_contabilita_metodi_pagamento_valore');

$this->load->model('contabilita/docs');

$documenti_tree = $this->docs->getDocumentiPadri($id);

//    debug($documenti_tree, true);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Documento</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css"
        href="<?php echo base_url(); ?>template/adminlte/bower_components/bootstrap/dist/css/bootstrap.css?v=1" />
    <style>
        .a4_paper {
            width: 100%;
            height: 29.7cm;
            display: block;
            margin: 0 auto;
            margin-bottom: 0.5cm;
        }

        .t_rows {
            color: #000;
            border-bottom: 1px solid #000;
            border-color: #000;
            height: 50px
        }

        tr.t_rows1 {
            color: #000;
            border-bottom: 0;
            border-color: #000;
            height: 50px
        }

        div.big_table {
            align-content: center;
            margin: 50px;
            margin-left: 0;
            margin-right: 0;
            font-size: 16px
        }

        .blu1 {
            color: #fff;
            text-align: center
        }

        .blu {
            background-color: #4b8ffc;
            color: #fff;
            height: 50px
        }

        div.card-body {
            margin-top: 50px
        }

        tr.table_title {
            color: #000;
            border-bottom: 5px solid #4b8ffc;
        }

        div.payment_method {
            float: left
        }

        div.Thanks {
            float: left;
            color: #4b8ffc;
            font-size: 20px
        }

        div.services {
            float: left;
            margin-bottom: 10px;
        }

        .mittente {
            font-size: 16px
        }

        .center {
            text-align: center;
        }

        .right {
            width: 140px;
            text-align: right;
        }

        .right1 {
            width: 190px;
            text-align: left;
            margin: 5px;
            padding: 10px;
        }

        .h1 {
            margin-top: 0px;
            margin-bottom: 0px
        }

        .small_margin {
            margin-left: 10px;
            margin-right: 10px;
            margin-top: 0px
        }

        .fattura {
            text-align: center;
            background-color: #4b8ffc;
            padding: 2px;
            /*height: 70px;*/
            width: 300px;
            float: right;
            margin-top: 20px;
            margin-right: 1.5%;
        }

        .mittente1 {
            text-align: left;
        }

        .destinatario {
            text-align: left;
            align-content: center
        }

        .document {
            margin-left: 25px;
        }

        .payments {
            margin-left: 3px;
            margin-right: 3px
        }

        .bank {
            text-align: left;
            background-color: #4b8ffc;
            padding: 15px;
            border-right: 5px solid #fff;
            border-color: #fff;
            color: #fff;
        }

        .payments2 {
            text-align: left;
            background-color: #4b8ffc;
            padding: 15px;
            border-left: 5px solid #fff;
            border-right: 5px solid #fff;
            border-color: #fff;
            color: #fff;
        }

        .PayPal {
            text-align: left;
            background-color: #fff;
            padding: 15px;
            border-left: 5px solid #fff;
            border-color: #fff
        }

        ​.paypal_img {
            width: 312px;
            margin-top: 10px
        }

        .footer_left {
            text-align: left
        }

        .footer_center {
            text-align: center
        }

        .footer_right {
            text-align: right
        }

        .m_logo {
            max-width: 400px;
            height: auto;
            align-content: center;
            text-align: center;
            vertical-align: middle;
            float: left;
        }

        th {
            border-top: none !important;
        }

        .table_articoli {
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .row {
            margin-top: -20px !important;
        }

        .container {
            width: 97%;
        }

        .paginator {
            text-align: center;
            margin-top: 40px;
        }

        .services {
            width: 100%;
        }

        .informativa {
            width: 100% !important;
        }

        .table {
            /*font-size: 16px;*/
        }

        .banktext {
            width: 100%;
        }

        .alt_logo {
            font-weight: 200;
            font-size: xx-large;
        }

        .new_page {
            display: block;
            page-break-after: always !important;
        }
    </style>
</head>

<body class="a4_paper">
    <?php foreach ($gruppi_articoli as $pagina => $gruppo): ?>
        <div id="page_<?php echo $pagina; ?>" class="new_page">
            <div class="big_table">
                <div class="container">
                    <div class="row small-margin">
                        <div class="col-md-3">
                            <?php if (!empty($settings['documenti_contabilita_settings_company_logo'])): ?>
                                <img src="<?php echo base_url('uploads/' . $settings['documenti_contabilita_settings_company_logo']); ?>"
                                    alt="" class="m_logo img-responsive" />
                            <?php else: ?>
                                <div class="alt_logo">
                                    <?php echo $settings['documenti_contabilita_settings_company_name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 fattura" style="background-color: #4b8ffc;">
                            <div class="blu1">
                                <h1 style="margin-top: 10px;">
                                    <?php echo strtoupper($documento['documenti_contabilita_tipo_value']); ?>
                                </h1>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-4 mittente1">
                                <h5 class="mb-3"><strong class="mittente">Mittente: </strong></h5>
                                <div class="" style="float: left;">
                                    <div>
                                        <strong style="color: #4b8ffc;">
                                            <?php echo $settings['documenti_contabilita_settings_company_name']; ?>
                                        </strong>
                                    </div>
                                    <div>P.IVA
                                        <?php echo $settings['documenti_contabilita_settings_company_vat_number']; ?>
                                    </div>
                                    <div>CF
                                        <?php echo $settings['documenti_contabilita_settings_company_codice_fiscale']; ?>
                                    </div>
                                    <div>Codice SDI
                                        <?php echo $settings['documenti_contabilita_settings_codice_sdi']; ?>
                                    </div>
                                    <div>
                                        <?php echo $settings['documenti_contabilita_settings_company_address']; ?>
                                    </div>
                                    <div>
                                        <?php echo $settings['documenti_contabilita_settings_company_zipcode']; ?>
                                        <?php echo $settings['documenti_contabilita_settings_company_city']; ?>
                                        (
                                        <?php echo $settings['documenti_contabilita_settings_company_province']; ?>)
                                    </div>
                                    <div>
                                        <?php echo $settings['documenti_contabilita_settings_company_country']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4 destinatario">
                                <h5 class="mb-3"><strong class="mittente">Destinatario:</strong></h5>
                                <div>
                                    <strong style="color: #4b8ffc">
                                        <?php echo $destinatario['ragione_sociale']; ?>
                                    </strong>
                                </div>
                                <div>
                                    <?php echo $destinatario['indirizzo']; ?>
                                </div>
                                <div>
                                    <?php echo $destinatario['cap']; ?>
                                    <?php echo $destinatario['citta']; ?>
                                    (
                                    <?php echo $destinatario['provincia']; ?>)
                                </div>
                                <div>P.IVA
                                    <?php echo $destinatario['partita_iva']; ?>
                                </div>
                                <div>CF
                                    <?php echo $destinatario['codice_fiscale']; ?>
                                </div>
                                <div>Codice SDI
                                    <?php echo $destinatario['codice_sdi']; ?>
                                </div>
                                <div>
                                    <?php echo $destinatario['nazione']; ?>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="document">
                                    <!--   <div class="col-sm-6" style=" text-align: left;">
                                            <div style="">
                                            <strong>INVOICE NO:</strong>
                                           </div>
                                           <div>#543210</div>
                                           </div> -->
                                    <!--                        <div class="col-sm-12 document">-->
                                    <strong>Documento: </strong><br><strong style="color: #4b8ffc">nr.
                                        <?php echo $documento['documenti_contabilita_numero'] . "/" . date('Y', strtotime($documento['documenti_contabilita_data_emissione'])) ?>
                                        del
                                        <?php echo date('d-m-Y', strtotime($documento['documenti_contabilita_data_emissione'])); ?>
                                    </strong>
                                    <!--                        </div>-->
                                </div>
                            </div>


                        </div>
                        <div class="row mb-12">
                            <div class="col-sm-12">
                                <?php
                                if ($documento['documenti_contabilita_oggetto']) {
                                    echo "<h3 class='mb-3'><strong>Oggetto: " . $documento['documenti_contabilita_oggetto'] . "</strong></h3>";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="table-responsive-sm table_articoli">

                            <table class="table table-cleared">
                                <?php
                                $iva_perc_before = "";
                                $iva_totale = array();
                                $imponibile_totale = array();
                                $totale_imponibile = 0;
                                ?>
                                <tr class="table_title">
                                    <th>Codice</th>
                                    <th>Prodotto</th>
                                    <th>U.m.</th>
                                    <th class="right">Prezzo</th>
                                    <th class="center">Quantità</th>
                                    <th class="right">% IVA</th>
                                    <th class="right">IVA</th>
                                    <th class="right">Importo</th>
                                </tr>
                                <?php foreach ($gruppo as $articolo):
                                    if (!empty($articolo['documenti_contabilita_articoli_name'])): ?>
                                        <?php if ($articolo['documenti_contabilita_articoli_iva_perc'] != $iva_perc_before or !$iva_perc_before): ?>

                                            <?php $iva_perc_before = number_format((float) $articolo['documenti_contabilita_articoli_iva_perc'], 2, '.', ''); ?>
                                        <?php endif; ?>
                                        <?php $iva_totale[$articolo['documenti_contabilita_articoli_iva_perc']] = $iva_totale[$articolo['documenti_contabilita_articoli_iva_perc']] + $articolo['documenti_contabilita_articoli_iva']; ?>
                                        <?php $imponibile_totale[$articolo['documenti_contabilita_articoli_iva_perc']] += $articolo['documenti_contabilita_articoli_prezzo'] * $articolo['documenti_contabilita_articoli_quantita']; ?>

                                        <tr class="t_rows">
                                            <td class="left">
                                                <?php echo $articolo['documenti_contabilita_articoli_codice']; ?>
                                            </td>
                                            <td class="left strong">
                                                <?php echo $articolo['documenti_contabilita_articoli_name']; ?>
                                                <br>
                                                <small>
                                                    <?php echo $articolo['documenti_contabilita_articoli_descrizione']; ?>
                                                </small>
                                            </td>
                                            <td class="left">
                                                <?php echo $articolo['documenti_contabilita_articoli_unita_misura']; ?>
                                            </td>
                                            <td class="right">
                                                <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                <?php echo number_format((float) $articolo['documenti_contabilita_articoli_prezzo'], 2, '.', ''); ?>
                                                <?php echo ($articolo['documenti_contabilita_articoli_sconto'] != 0.00) ? "<br /><small>Sconto " . number_format((float) $articolo['documenti_contabilita_articoli_sconto'], 2, '.', '') . '% </small>' : ''; ?>
                                            </td>

                                            <td class="center">
                                                <?php echo $articolo['documenti_contabilita_articoli_quantita']; ?>
                                            </td>
                                            <td class="right">%
                                                <?php echo $articolo['documenti_contabilita_articoli_iva_perc']; ?>
                                            </td>
                                            <td class="right">
                                                <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                <?php echo $segno . number_format((float) $articolo['documenti_contabilita_articoli_iva'], 2, '.', ''); ?>
                                            </td>
                                            <td class="right">
                                                <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                <?php echo $segno . number_format((float) $articolo['documenti_contabilita_articoli_importo_totale'], 2, '.', ''); ?>
                                            </td>
                                        </tr>
                                    <?php
                                    endif;
                                endforeach;
                                ?>

                                <?php if ($documento['documenti_contabilita_importo_bollo'] > 0): ?>
                                    <tr>
                                        <td></td>
                                        <td></td>

                                        <td></td>

                                        <td colspan="3" class="right">Bollo su quietanza</td>
                                        <td class="right">
                                            <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                            <?php echo $segno . number_format((float) $documento['documenti_contabilita_importo_bollo'], 2, '.', ''); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($documento['documenti_contabilita_rivalsa_inps_valore'] > 0): ?>
                                    <tr>
                                        <td></td>
                                        <td></td>

                                        <td></td>

                                        <td colspan="3" class="right">Rivalsa
                                            INPS
                                            <?php echo number_format((float) $documento['documenti_contabilita_rivalsa_inps_perc'], 2, '.', ''); ?>%
                                        </td>
                                        <td class="right">
                                            <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                            <?php echo $segno . number_format((float) $documento['documenti_contabilita_rivalsa_inps_valore'], 2, '.', ''); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($documento['documenti_contabilita_competenze_lordo_rivalsa'] && $documento['documenti_contabilita_rivalsa_inps_perc'] > 0): ?>
                                    <tr>
                                        <td></td>
                                        <td></td>

                                        <td></td>

                                        <td colspan="3" class="right">Competenze (al lordo della rivalsa)</td>
                                        <td class="right">
                                            <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                            <?php echo $segno . number_format((float) $documento['documenti_contabilita_competenze_lordo_rivalsa'], 2, '.', ''); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($documento['documenti_contabilita_cassa_professionisti_valore'] > 0): ?>
                                    <tr>
                                        <td></td>
                                        <td></td>

                                        <td></td>

                                        <td colspan="3" class="right">Cassa
                                            professionisti
                                            <?php echo number_format((float) $documento['documenti_contabilita_cassa_professionisti_perc'], 2, '.', ''); ?>
                                        </td>
                                        <td class="right">
                                            <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                            <?php echo $segno . number_format((float) $documento['documenti_contabilita_cassa_professionisti_valore'], 2, '.', ''); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php
                                if (!empty($iva_totale)):
                                    echo "<tr>";
                                    echo "<td colspan='8'><h3>Dati di riepilogo</h3></td>";
                                    echo "</tr>";
                                    ?>
                                    <tr class="table_title">
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th class="right">% Iva</th>
                                        <th class="right">Imponibile</th>
                                        <th class="right">Imposta</th>
                                    </tr>
                                    <?php
                                    $totale_imponibile_fix = $totale_iva_fix = 0;
                                    foreach (json_decode($documento['documenti_contabilita_imponibile_iva_json'],true) as $key => $arr_iva):
                                        if ($arr_iva[1] == 0) {
                                            continue;
                                        } else {
                                            $totale_imponibile_fix += $arr_iva[1];
                                                    $totale_iva_fix += (float)number_format($arr_iva[1] / 100 * $arr_iva[0], 2, '.', '');
                                        }
                                        ?>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td class="right" colspan="3">%
                                                <?php echo $arr_iva[0]; ?>
                                            </td>
                                            <td class="right" colspan="1">
                                                <?php echo $valuta[$documento['documenti_contabilita_valuta']] . number_format($arr_iva[1], 2, '.', ''); ?>
                                            </td>
                                            <td class="right">
                                                <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                <?php echo number_format($arr_iva[1]/100* $arr_iva[0], 2, '.', ''); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    endforeach;
                                    ?>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th class="right">Totale:</th>
                                        <th class="right">
                                            <?php echo $valuta[$documento['documenti_contabilita_valuta']] . $segno . number_format((float) $totale_imponibile_fix, 2, '.', ''); ?>
                                        </th>
                                        <th class="right">
                                            <?php echo $valuta[$documento['documenti_contabilita_valuta']] . number_format((float) $totale_iva_fix, 2, '.', ''); ?>
                                        </th>
                                    </tr>
                                    <?php
                                endif;
                                ?>

                                <?php if ($pagina == count($gruppi_articoli)): ?>

                                    <tr class="blu">
                                        <td></td>
                                        <td class="left"></td>

                                        <td class="center"></td>

                                        <td class="right" colspan="4" style="padding: 10px;">
                                            <strong style="margin-bottom: 1px;">
                                                <?php if ($documento['documenti_contabilita_tipo'] != 4): ?>
                                                    Totale fattura
                                                <?php else: ?>
                                                    Nota di credito
                                                <?php endif; ?>
                                            </strong>
                                        </td>
                                        <td class="right" style="font-size:1.4em;">
                                            <strong>
                                                <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                <?php echo $segno . number_format((float) $documento['documenti_contabilita_totale'] + (float) $documento['documenti_contabilita_ritenuta_acconto_valore'], 2, '.', ''); ?>
                                            </strong>
                                        </td>
                                    </tr>

                                    <?php if ($documento['documenti_contabilita_ritenuta_acconto_valore'] > 0): ?>
                                        <tr class="blu">
                                            <td></td>
                                            <td class="left"></td>

                                            <td class="center"></td>

                                            <td class="right" colspan="4" style="padding: 10px;">
                                                <strong style="margin-bottom: 1px;">
                                                    Ritenuta d'acconto
                                                    <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta'])): ?>
                                                        <?php echo "( causale: " . $documento['documenti_contabilita_causale_pagamento_ritenuta'] . " )"; ?>
                                                    <?php endif; ?>
                                                    <?php echo number_format((float) $documento['documenti_contabilita_ritenuta_acconto_perc']); ?>
                                                    %
                                                </strong>
                                            </td>
                                            <td class="right" style="font-size:1.4em;">
                                                <strong>
                                                    <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                    <?php echo $segno . number_format((float) $documento['documenti_contabilita_ritenuta_acconto_valore'], 2, '.', ''); ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr class="blu">
                                            <td></td>
                                            <td class="left"></td>

                                            <td class="center"></td>

                                            <td class="right" colspan="4" style="padding: 10px;">
                                                <strong style="margin-bottom: 1px;">
                                                    Netto a pagare
                                                </strong>
                                            </td>
                                            <td class="right" style="font-size:1.4em;">
                                                <strong>
                                                    <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                    <?php echo $segno . number_format((float) $documento['documenti_contabilita_totale'], 2, '.', ''); ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($documento['documenti_contabilita_importo_bollo'] > 0): ?>
                                    <tr class="">
                                        <td colspan="6">Esente art. Esente art. 10 D.P.R. 633/72 e succ.mod.</td>
                                    </tr>
                                    <tr class="">
                                        <td colspan="6">Es. art. 15 Escluso IVA art. 15</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($iva) || $documento['documenti_contabilita_split_payment'] == DB_BOOL_TRUE): ?>
                                    <tr class="">
                                        <td colspan="6">Esenzioni iva:</td>
                                    </tr>

                                    <?php $iva_stampate = [] ?>

                                    <?php foreach ($iva as $i): ?>
                                        <?php
                                        if (in_array($i, $iva_stampate)) {
                                            continue;
                                        } else {
                                            $iva_stampate[] = $i;
                                        }
                                        ?>
                                        <tr class="">
                                            <td colspan="6">
                                                <?php echo number_format((float) $i['valore'], 2, '.', ''); ?>% -
                                                <?php echo $i['descrizione']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if ($documento['documenti_contabilita_importo_bollo'] > 0): ?>
                                        <tr class="">
                                            <td colspan="6">Esente art. Esente art. 10 D.P.R. 633/72 e succ.mod.</td>
                                        </tr>
                                        <tr class="">
                                            <td colspan="6">Es. art. 15 Escluso IVA art. 15</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($documento['documenti_contabilita_split_payment'] == DB_BOOL_TRUE): ?>
                                        <tr>
                                            <td colspan="6"><strong>Questa fattura applica lo "Split Payment", pertanto l'IVA dovrà
                                                    essere versata dal cliente ai sensi dell'art. 17-ter, DPR n. 633/72.</strong>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </table>


                        </div>

                        <?php if ($documento['documenti_contabilita_fattura_accompagnatoria'] == DB_BOOL_TRUE): ?>
                            <div class="row">
                                <div class="payment_method col-md-12">
                                    <strong>Fattura ACCOMPAGNATORIA</strong>
                                </div>
                            </div>
                            <br />
                            <div class="row">
                                <div class="col-md-3">
                                    <span>Colli: <strong>
                                            <?php echo (!empty($documento['documenti_contabilita_n_colli'])) ? $documento['documenti_contabilita_n_colli'] : '-'; ?>
                                        </strong></span>
                                </div>
                                <div class="col-md-3">
                                    <span>Peso: <strong>
                                            <?php echo (!empty($documento['documenti_contabilita_peso'])) ? $documento['documenti_contabilita_peso'] : '-'; ?>
                                        </strong></span>
                                </div>
                                <div class="col-md-6">
                                    <span>Causale trasporto: <strong>
                                            <?php echo (!empty($documento['documenti_contabilita_causale_trasporto'])) ? $documento['documenti_contabilita_causale_trasporto'] : '-'; ?>
                                        </strong></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <span>Trasporto a cura di: <strong>
                                            <?php echo (!empty($documento['documenti_contabilita_trasporto_a_cura_di'])) ? $documento['documenti_contabilita_trasporto_a_cura_di'] : '-'; ?>
                                        </strong></span>
                                </div>
                                <div class="col-md-3">
                                    <span>Destinazione: <strong>
                                            <?php echo (!empty($documento['documenti_contabilita_luogo_destinazione'])) ? $documento['documenti_contabilita_luogo_destinazione'] : '-'; ?>
                                        </strong></span>
                                </div>
                                <div class="col-md-6">
                                    <span>Annot. trasporto: <strong>
                                            <?php echo (!empty($documento['documenti_contabilita_annotazioni_trasporto'])) ? $documento['documenti_contabilita_annotazioni_trasporto'] : '-'; ?>
                                        </strong></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <br>
                        <div class="payment_method">
                            <strong>MODALITA' DI PAGAMENTO</strong>
                        </div>
                        <br> <br>
                        <div class="row payments">
                            <div class="col-md-5 bank">
                                <div class="banktext">
                                    <strong>
                                        <?php echo ucfirst($documento['documenti_contabilita_metodo_pagamento']); ?>
                                    </strong><br>

                                    <?php if ($documento['documenti_contabilita_conto_corrente']): ?>
                                        <strong>Banca </strong>
                                        <?php echo $documento['conti_correnti_nome_istituto']; ?> <br />
                                        <strong>IBAN</strong>
                                        <?php echo $documento['conti_correnti_iban']; ?><br />
                                        <strong>SWIFT</strong>
                                        <?php echo $documento['conti_correnti_swift']; ?><br />
                                        <strong>Intestazione</strong>
                                        <?php echo $documento['conti_correnti_intestatario']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($documento['documenti_contabilita_tipo'] != 5 && $documento['documenti_contabilita_tipo'] != 4): ?>
                                <div class="col-md-7 payments2">
                                    <strong>Scadenze di pagamento</strong><br>
                                    <table class="table table-cleared">

                                        <?php foreach ($scadenze as $scadenza): ?>
                                            <?php $data_saldo = strtotime($scadenza['documenti_contabilita_scadenze_data_saldo']);
                                            $data_scadenza = strtotime($scadenza['documenti_contabilita_scadenze_scadenza']);
                                            $data_saldo = strtotime($scadenza['documenti_contabilita_scadenze_data_saldo']); ?>
                                            <?php if ($scadenza['documenti_contabilita_scadenze_data_saldo']): ?>

                                                <tr>
                                                    <td>
                                                        <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                        <?php echo number_format((float) $scadenza['documenti_contabilita_scadenze_ammontare'], 2, '.', ''); ?>
                                                        saldati in data
                                                        <?php echo date('d-m-Y', $data_saldo); ?>
                                                        <?php echo ($scadenza['documenti_contabilita_scadenze_saldato_con']) ? "con " . $metodi_pagamento[$scadenza['documenti_contabilita_scadenze_saldato_con']] : ""; ?>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $valuta[$documento['documenti_contabilita_valuta']]; ?>
                                                        <?php echo number_format((float) $scadenza['documenti_contabilita_scadenze_ammontare'], 2, '.', ''); ?>
                                                        da saldare entro il
                                                        <?php echo date('d-m-Y', $data_scadenza); ?>
                                                        <?php echo ($scadenza['documenti_contabilita_scadenze_saldato_con']) ? "con " . $metodi_pagamento[$scadenza['documenti_contabilita_scadenze_saldato_con']] : ""; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </table>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!------------- PAYPAL DISATTIVATO PER ORA ------------>
                        <div class="row" style="display:none">
                            <div class="col-md-3 PayPal">
                                <strong style="color: #4b8ffc;">Paga con PayPal</strong> <img
                                    src="../../../metodi-di-pagamento.png" alt="" class="paypal_img" />
                            </div>
                        </div>

                        <br>
                        <br><br>
                        <div class="services">
                            <?php echo $documento['documenti_contabilita_note_interne']; ?>
                        </div>
                        <hr size="1" width="1066px" color="black">
                        <div class="informativa">
                            <?php if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE && in_array($documento['documenti_contabilita_tipo'], [1, 4, 11, 12])): ?>
                                <p>
                                    Documento privo di valenza fiscale ai sensi dell'art. 21 Dpr 633/72.
                                    L'originale è disponibile all'indirizzo telematico da Lei fornito oppure nella Sua area
                                    riservata dell’Agenzia delle Entrate.
                                </p>
                            <?php endif; ?>

                            <p>
                                Ai sensi del D.lgs. 196/2003 Vi informiamo che i Vs. dati saranno utilizzati esclusivamente
                                per i fini
                                connessi ai rapporti commerciali tra di noi in essere. Vi preghiamo di controllare i Vs.
                                dati
                                anagrafici, la P.IVA e il Cod. Fiscale. Non ci riteniamo responsabili di eventuali errori.
                            </p>
                        </div>
                        <div class="row">

                            <div class="col-md-12 paginator">
                                Pagina
                                <?php echo $pagina; ?>/
                                <?php echo count($gruppi_articoli); ?>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</body>

</html>