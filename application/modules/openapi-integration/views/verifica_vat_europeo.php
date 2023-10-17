<?php
$value_id = $this->input->get('customer_id') ?? $value_id;

if (empty($value_id)) {
    echo '<div class="alert alert-danger">'.t("ID Cliente non dichiarato").'</div>';
    return;
}

$customer = $this->apilib->view('customers', $value_id);
?>

<form action="<?php echo base_url('openapi-integration/main/verifica_eu_vat'); ?>" method="post" class="formAjax" id="verifica_eu_vat">
    <?php add_csrf(); ?>

    <div class="callout callout-info" style="margin-bottom: 0!important; background-color: #3c8dbc!important; border-left: 5px solid #eee; border-left-width: 5px; border-left-style: solid; border-left-color: #357ca5!important;">
        <h4><i class="fa fa-info"></i> Informazioni:</h4>
        <p style="margin-bottom:20px">Con questo servizio puoi verificare l'anagrafica di un nuovo cliente o fornitore, comodamente cercando la p.iva. Conferma i dati prima di procedere:</p>
        <p>NB: Sia la Partita IVA che la Nazione, non potranno essere modificati, in quanto vengono popolati direttamente dall'anagrafica del cliente</p>
    </div>

    <hr/>

    <div class="form-group">
        <label class="control-label" for="js_cerca_piva_input">Partita iva:</label>
        <input type="text" id="js_cerca_piva_input" name="piva_cf" class="form-control" readonly value="<?php echo (!empty($customer['customers_vat_number']) ? $customer['customers_vat_number'] : null); ?>">
    </div>

    <div class="form-group">
        <label class="control-label" for="js_cerca_nazione_input">Nazione:</label>
        <input type="text" class="form-control" value="<?php echo (!empty($customer['countries_name']) ? $customer['countries_name'] : null); ?>" disabled>
        <input type="hidden" name="nazione" value="<?php echo (!empty($customer['countries_iso']) ? $customer['countries_iso'] : null); ?>">
    </div>

    <div class="form-group">
        <div id="msg_verifica_eu_vat" class="alert alert-danger hide"></div>
    </div>

    <div class="form-actions" style="text-align: center">
        <button type="submit" id="js_cerca_piva" class="btn btn-primary">Verifica € 0,08</button>
    </div>
</form>
