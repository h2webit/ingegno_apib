<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title>Document</title>
    
    <link rel="stylesheet" href="<?php echo base_url("template/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css"); ?>" />
    
    <style>
        .justify-text {
            text-align: justify;
            text-align-last: justify;
        }
        .justify-text::after {
            content: '';
            display: inline-block;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container-fluid" style="font-size: 1.5rem">
        <?php foreach($rows as $row): ?>
        
        <div class="row" style="margin-bottom: 20px; page-break-inside: avoid; border-bottom: 1px dotted grey; padding-bottom: 10px;">
            <div class="col-sm-3">
                <b><?php echo $row['_azienda']['documenti_contabilita_settings_company_name']; ?></b><br />
                <?php echo $row['_azienda']['documenti_contabilita_settings_company_address'] ?><br/>
                <?php echo strtoupper($row['_azienda']['documenti_contabilita_settings_company_city'] ?: '') ?><br/>
                <?php echo $row['_azienda']['documenti_contabilita_settings_company_vat_number'] ?: ''; ?>
            </div>
            <div class="col-sm-9">
                <div class="row">
                    <div class="col-sm-6">
                        <br/>
                        <?php echo strtoupper($row['_azienda']['documenti_contabilita_settings_company_province'] ?: ''); ?>
                    </div>
                    <div class="col-sm-3">
                        <b>Scadenza</b><br/>
                        <?php echo dateFormat($row['documenti_contabilita_scadenze_scadenza'], 'd M Y'); ?>
                    </div>
                    <div class="col-sm-3">
                        <b>Importo effetto</b><br/>
                        <?php echo number_format($row['documenti_contabilita_scadenze_ammontare'], 2, ',', '.'); ?> €
                    </div>
                    <div class="col-sm-9 justify-text">
                        <i>A saldo della ns. fattura n°:</i> <?php echo $row['documenti_contabilita_numero'], (!empty($row['documenti_contabilita_serie']) ? '/' . $row['documenti_contabilita_serie'] : ''); ?>
                        <i>del</i> <?php echo dateFormat($row['documenti_contabilita_data_emissione'], 'd.m.Y'); ?> <i>dell'importo di</i>
                    </div>
                    <div class="col-sm-3">
                        <?php echo number_format($row['documenti_contabilita_totale'], 2, ',', '.'); ?> €
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="col-sm-2">
                    Incasso tramite:<br/>
                    <?php
                    if (empty($row['_bank_account']['customers_bank_accounts_abi']) && empty($row['_bank_account']['customers_bank_accounts_cab']) && empty($row['_bank_account']['customers_bank_accounts_iban'])) {
                        echo '<b class="text-danger">n.d.</b>';
                    } else {
                        if (!empty($row['_bank_account']['customers_bank_accounts_abi']) && !empty($row['_bank_account']['customers_bank_accounts_cab'])) {
                            echo ($row['_bank_account']['customers_bank_accounts_abi'] ?? ''), ' - ', ($row['_bank_account']['customers_bank_accounts_cab'] ?? '');
                        } else {
                            $iban_data = $this->ribaabicbi->extractIbanData($row['_bank_account']['customers_bank_accounts_iban']);
                            
                            echo $iban_data['abi'], ' - ', $iban_data['cab'];
                        }
                    }
                    
                    ?><br/>
                    <small><?php echo $row['_bank_account']['customers_bank_accounts_bank_name'] ?? '' ?></small><br/>
                </div>
                
                <div class="col-sm-6">
                    Spett.le<br/>
                    <?php
                    echo $row['_customer']['customers_full_name'] . "<br/>",
                    $row['_customer']['customers_address'] . "<br/>",
                    $row['_customer']['customers_zip_code'], '  ', $row['_customer']['customers_city'] . "<br/>",
                    $row['_customer']['customers_vat_number'];
                    ?>
                </div>
                <div class="col-sm-4">
                    <br/>
                    <?php
                    echo str_ireplace('.', '-', $row['_customer']['customers_codice_sottoconto']) . "<br/>",
                        "<br/>",
                    $row['_customer']['customers_province'];
                    ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
