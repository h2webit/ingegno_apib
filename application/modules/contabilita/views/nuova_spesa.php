<style>
    #js_product_table>tbody>tr>td,
    #js_product_table>tbody>tr>th,
    #js_product_table>tfoot>tr>td,
    #js_product_table>tfoot>tr>th,
    #js_product_table>thead>tr>td {
        vertical-align: top !important;
    }

    div.upload-drop-zone {
        min-height: 200px !important;
        border-width: 3px !important;
        background: url('<?php echo base_url(); ?>images/drop_zone.png');
        background-repeat: no-repeat;
        background-position: 50% 50%;
        background-size: 300px auto;
        border-style: dashed !important;
        border-color: #3c8dbc;
        line-height: 20px;
        text-align: left;
    }

    .dropzone .dz-preview .dz-details img,
    .dropzone-previews .dz-preview .dz-details img {
        background-color: #ffffff;
    }

    .dz-message {
        display: none !important;
    }

    .row {
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

    .elenco_articoli {
        display: none;
    }

    .pref_checkboxes {
        display: none;
    }

    .glyphicon.glyphicon-cloud-upload {
        font-size: 75px;
    }
</style>

<?php

$dati['id'] = null;
$dati['fattura'] = null;
$dati['fatture_cliente'] = null;
$dati['serie'] = null;
$dati['fatture_numero'] = null;
$dati['fatture_serie'] = null;
$dati['fatture_scadenza_pagamento'] = null;
$dati['fatture_pagato'] = null;
$dati['prodotti'] = null;
$dati['fatture_note'] = null;

/*
 * Install constants
 */
define('MODULE_NAME', 'fatture');

/** Entità **/
defined('ENTITY_SETTINGS') or define('ENTITY_SETTINGS', 'settings');
defined('FATTURE_E_CUSTOMERS') or define('FATTURE_E_CUSTOMERS', 'clienti');

/** Parametri **/
defined('FATTURAZIONE_METODI_PAGAMENTO') or define('FATTURAZIONE_METODI_PAGAMENTO', serialize(array('Bonifico', 'Paypal', 'Contanti', 'Sepa RID', 'RIBA')));

defined('FATTURAZIONE_URI_STAMPA') or define('FATTURAZIONE_URI_STAMPA', null);

$settings = $this->apilib->search('documenti_contabilita_settings');

$conti_correnti = $this->apilib->search('conti_correnti');

$impostazioni = $this->apilib->searchFirst('documenti_contabilita_settings');

$spese_anni_ammortamento = $this->apilib->search('spese_anni_ammortamento');
$centri_di_costo = $this->apilib->search('centri_di_costo_ricavo');
$spese_categoria = $this->apilib->search('spese_categorie');
$valute = $this->apilib->search('valute', [], null, 0, 'valute_codice');
$documento_id = $this->input->get('documenti_contabilita_id');

$tipologie_fatturazione = $this->apilib->search('documenti_contabilita_tipologie_fatturazione');

$spesa_id = (empty($value_id)) ? false : $value_id;

// Per eventuale get

$ragione_sociale = "";
$data_emissione = "";
$data_saldo = "";
$data_scadenza = $data_saldo;
$imponibile = "";
$ammontare = $imponibile;

if ($spesa_id) {
    $spesa = $this->apilib->view('spese', $spesa_id);

    if (empty($spesa)) {
        echo "Spesa non trovata. Riprovare o contattare l'assistenza.";
        return false;
    }
    $spesa['articoli'] = $this->apilib->search('spese_articoli', ['spese_articoli_spesa' => $spesa_id]);
    $spesa['scadenze'] = $this->apilib->search('spese_scadenze', ['spese_scadenze_spesa' => $spesa_id]);
    //debug($spesa);
    $spesa['spese_fornitore'] = json_decode($spesa['spese_fornitore'], true);
    $spesa['allegati'] = $this->apilib->search('spese_allegati', ['spese_allegati_spesa' => $spesa_id]);
} elseif ($documento_id) {
    $documento = $this->apilib->view('documenti_contabilita', $documento_id);

    $documento['documenti_contabilita_fornitore'] = json_decode($documento['documenti_contabilita_destinatario'], true);

    $spesa = [];
    $spesa['articoli'] = [];
    foreach ($documento as $field => $value) {
        $field = str_ireplace('documenti_contabilita_', 'spese_', $field);
        $spesa[$field] = $value;
    }

    $spesa['spese_documento_id'] = $documento_id;

    $documento['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ["documenti_contabilita_articoli_documento" => $documento_id]);
    foreach ($documento['articoli'] as $articolo) {
        $_articolo = [];
        foreach ($articolo as $field => $value) {
            $field = str_ireplace('documenti_contabilita_articoli_', 'spese_articoli_', $field);
            $_articolo[$field] = $value;
        }
        $spesa['articoli'][] = $_articolo;
    }
    $spesa['allegati'] = [];
} elseif ($flussi_cassa_ids_str = $this->input->get('flussi_cassa_ids')) {
    $flussi_cassa_ids = explode(',', $flussi_cassa_ids_str);

    $flussi_cassa = $this->apilib->search('flussi_cassa', ['flussi_cassa_id' => $flussi_cassa_ids]);

    $spesa['spese_data_emissione'] = date('Y-m-d');

    $spesa['spese_imponibile'] = 0;
    foreach ($flussi_cassa as $flusso_cassa) {
        $spesa['spese_imponibile'] += $flusso_cassa['flussi_cassa_importo'];
        //debug($flusso_cassa,true);
        $spesa['scadenze'][] = [
            'spese_scadenze_saldato_da' => $flusso_cassa['flussi_cassa_risorsa'],
            'spese_scadenze_saldato_con' => $flusso_cassa['flussi_cassa_metodo'],
            'spese_scadenze_data_saldo' => $flusso_cassa['flussi_cassa_data'],
            'spese_scadenze_scadenza' => $flusso_cassa['flussi_cassa_data'],
            'spese_scadenze_ammontare' => $flusso_cassa['flussi_cassa_importo'],
            'spese_scadenze_saldata' => DB_BOOL_TRUE,
            'spese_scadenze_note' => $flusso_cassa['flussi_cassa_descrizione'],
            '_flussi_cassa_id' => $flusso_cassa['flussi_cassa_id'],
        ];
    }
    
} else {
    $spesa = [];
    $spesa['allegati'] = [];

    $ragione_sociale = $this->input->get('ragione_sociale');
    $data_emissione = ($this->input->get('data_emissione')) ? $this->input->get('data_emissione') : date('d/m/Y');
    $data_saldo = ($this->input->get('data_emissione')) ? $this->input->get('data_emissione') : date('d/m/Y');
    $data_scadenza = $data_saldo;
    $imponibile = $this->input->get('imponibile');
    $ammontare = $imponibile;
}

$metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');

$full_enabled = $this->apilib->searchFirst('documenti_contabilita_general_settings', ['documenti_contabilita_general_settings_contabilita_full' => DB_BOOL_TRUE]);

$nazioni = $this->apilib->search('countries', [], null, 0, 'countries_name', 'ASC');

?>

<form class="formAjax" id="new_spesa" action="<?php echo base_url('contabilita/spese/create_spesa'); ?>">
    <?php add_csrf();?>
    <?php if ($spesa_id): ?>
        <input name="spesa_id" type="hidden" value="<?php echo $spesa_id; ?>" />
    <?php endif;?>
    <input name="spese_totale" type="hidden" value="<?php if ($spesa_id && $spesa['spese_totale']): ?><?php echo ($spesa['spese_totale']); ?><?php endif;?>" />
    
    <?php if (!empty($flussi_cassa_ids_str)) : ?>
        <input name="_flussi_cassa_ids" type="hidden" value="<?php echo $flussi_cassa_ids_str; ?>" />
    <?php endif; ?>
    
            <input type="hidden" name="spese_utente_id" value="<?php echo ($spesa_id && $spesa['spese_utente_id']) ? $spesa['spese_utente_id'] : $this->session->userdata('session_login')[LOGIN_ENTITY . '_id']; ?>" />


    <div class="row">
        <div class="col-md-4 bg-gray" style="background-color:#eeeeee;">
            <div class="row">
                <div class="col-md-12">
                    <h4>Dati del <span class="js_dest_type">fornitore</span></h4>
                </div>
            </div>

            <input type="hidden" name="dest_entity_name" value="customers" />
            <input id="js_dest_id" type="hidden" name="dest_id" value="<?php if ($spesa_id && $spesa['spese_customer_id']): ?><?php echo ($spesa['spese_customer_id']); ?><?php endif;?>" />

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input id="search_cliente" type="text" name="ragione_sociale" class="form-control js_dest_ragione_sociale" placeholder="Ragione sociale" value="<?php echo (!empty($spesa['spese_fornitore']["ragione_sociale"])) ? $spesa['spese_fornitore']["ragione_sociale"] : $ragione_sociale; ?>" autocomplete="off" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="indirizzo" class="form-control js_dest_indirizzo" placeholder="Indirizzo" value="<?php if (!empty($spesa['spese_fornitore']["indirizzo"])): ?><?php echo $spesa['spese_fornitore']["indirizzo"]; ?><?php endif;?>" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="citta" class="form-control js_dest_citta" placeholder="Città" value="<?php if (!empty($spesa['spese_fornitore']["citta"])): ?><?php echo $spesa['spese_fornitore']["citta"]; ?><?php endif;?>" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" name="cap" class="form-control js_dest_cap" placeholder="CAP" value="<?php if (!empty($spesa['spese_fornitore']["cap"])): ?><?php echo $spesa['spese_fornitore']["cap"]; ?><?php endif;?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div clasS="form-group">
                        <input type="text" name="provincia" class="form-control js_dest_provincia" placeholder="Provincia" value="<?php if (!empty($spesa['spese_fornitore']["provincia"])): ?><?php echo $spesa['spese_fornitore']["provincia"]; ?><?php endif;?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <?php if (!empty($nazioni)): ?>
                            <select name="nazione" id="nazione" class="form-control select2_standard js_dest_nazione">
                                <?php foreach ($nazioni as $nazione): ?>
                                    <?php
$selected = '';

if (
    (!empty($spesa['spese_fornitore']) && !empty($spesa['spese_fornitore']["nazione"]) && $spesa['spese_fornitore']["nazione"] === $nazione['countries_iso'])
    || (empty($spesa['spese_fornitore']) && $nazione['countries_iso'] == 'IT')
) {
    $selected = 'selected';
}
?>

                                    <option value="<?php echo $nazione['countries_iso']; ?>" <?php echo $selected ?>><?php echo $nazione['countries_name']; ?></option>
                                <?php endforeach;?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="nazione" maxlength="2" minlength="2" class="form-control js_dest_nazione" placeholder="Nazione" value="<?php if (!empty($spesa['spese_fornitore']) && !empty($spesa['spese_fornitore']["nazione"]) && (strlen($spesa['spese_fornitore']["nazione"]) < 3)): ?><?php echo $spesa['spese_fornitore']["nazione"]; ?><?php else: ?><?php echo "IT"; ?><?php endif;?>" />
                        <?php endif;?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="partita_iva" class="form-control js_dest_partita_iva" placeholder="P.IVA" value="<?php if (!empty($spesa['spese_fornitore']["partita_iva"])): ?><?php echo $spesa['spese_fornitore']["partita_iva"]; ?><?php endif;?>" />
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="codice_fiscale" class="form-control js_dest_codice_fiscale" placeholder="Codice fiscale" value="<?php if (!empty($spesa['spese_fornitore']["codice_fiscale"])): ?><?php echo $spesa['spese_fornitore']["codice_fiscale"]; ?><?php endif;?>" />
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label id="js_label_rubrica">Salva in rubrica</label> <input type="checkbox" class="minimal" name="save_dest" value="true" />

                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-8">
            <div class="row bg-light-blue" style="background-color:#e0eaf0;padding-bottom:10px;">
                <div class="col-md-12">
                    <h4>Dati documento di spesa</h4>
                </div>
                <div class="row">

                            <div class="col-md-3">
                        <div class="form-group">
                            <label>Azienda: </label>
                            <select name="spese_azienda" class="form-control spese_azienda" required>
                                <?php foreach ($settings as $setting) : ?>
                                    <option value="<?php echo $setting['documenti_contabilita_settings_id']; ?>" <?php if ((!empty($spesa)) && (!empty($spesa['spese_azienda']) && $spesa['spese_azienda'] == $setting['documenti_contabilita_settings_id'])) : ?> selected="selected" <?php endif; ?>><?php echo $setting['documenti_contabilita_settings_company_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Numero doc.: </label>
                            <input type="text" name="spese_numero" class="form-control" placeholder="Numero documento" value="<?php $key = 'spese_numero';
if (!empty($spesa[$key])): ?><?php echo $spesa[$key]; ?><?php endif;?>" />
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Data emissione: </label>
                            <div class="input-group js_form_datepicker date ">
                                <input type="text" name="spese_data_emissione" class="form-control" placeholder="Data emissione" value="<?php $key = 'spese_data_emissione';
echo (!empty($spesa[$key])) ? date('d/m/Y', strtotime($spesa[$key])) : $data_emissione;
?>" data-name="spese_data_emissione" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fas fa-calendar-alt-alt"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label style="min-width:80px">Categoria: </label>
                        <select name="spese_categoria" class="select2 form-control">
                            <option value="">Seleziona...</option>
                            <?php foreach ($spese_categoria as $categoria): ?>
                                <option value="<?php echo $categoria['spese_categorie_id']; ?>" <?php if (($categoria['spese_categorie_id'] == '1' && empty($spesa_id)) || (!empty($spesa['spese_categoria']) && $spesa['spese_categoria'] == $categoria['spese_categorie_id'])): ?> selected="selected" <?php endif;?>><?php echo $categoria['spese_categorie_nome']; ?></option>
                            <?php endforeach;?>

                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label style="min-width:80px">Valuta: </label><select name="spese_valuta" class="select2 form-control">
                            <?php foreach ($valute as $key => $valuta): ?>
                                <option data-id="<?php echo $valuta['valute_id']; ?>" value="<?php echo $valuta['valute_codice']; ?>" <?php if (($valuta['valute_id'] == $impostazioni['documenti_contabilita_settings_valuta_base'] && empty($documento_id)) || (!empty($spesa['spese_valuta']) && strtoupper($spesa['spese_valuta']) == strtoupper($valuta['valute_codice']))): ?> selected="selected" <?php endif;?>><?php echo $valuta['valute_nome']; ?> - <?php echo $valuta['valute_simbolo']; ?></option>
                            <?php endforeach;?>
                            
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label style="min-width:80px">Centro di costo: </label>
                        <select name="spese_centro_di_costo" class="select2 form-control">
                            <?php foreach ($centri_di_costo as $centro): ?>
                                <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>" <?php if (($centro['centri_di_costo_ricavo_id'] == '1' && empty($spesa_id)) || (!empty($spesa['spese_centro_di_costo']) && $spesa['spese_centro_di_costo'] == $centro['centri_di_costo_ricavo_id'])): ?> selected="selected" <?php endif;?>><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                            <?php endforeach;?>

                        </select>
                    </div>

                    <div class="col-md-3">
                        <label style="min-width:80px">Tipologia: </label>
                        <select name="spese_tipologia_fatturazione" class="select2 form-control">
                            <option></option>
                            <?php foreach ($tipologie_fatturazione as $tipologia): ?>
                                <option value="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_id']; ?>" <?php if (($tipologia['documenti_contabilita_tipologie_fatturazione_id'] == '1' && empty($spesa_id)) || (!empty($spesa['spese_tipologia_fatturazione']) && $spesa['spese_tipologia_fatturazione'] == $tipologia['documenti_contabilita_tipologie_fatturazione_id'])): ?> selected="selected" <?php endif;?>><?php echo "{$tipologia['documenti_contabilita_tipologie_fatturazione_codice']} {$tipologia['documenti_contabilita_tipologie_fatturazione_descrizione']}"; ?></option>
                            <?php endforeach;?>

                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label style="min-width:80px">Tipo fattura rev.:</label>
                        <select name="spese_tipologia_autofattura" class="select2 form-control">
                            <option <?php if (empty($spesa_id) || empty($spesa['spese_tipologia_autofattura'])) : ?>selected<?php endif; ?>></option>
                            <?php foreach ($tipologie_fatturazione as $tipologia): ?>
                                <?php if (in_array($tipologia['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19'])): ?>
                                    <option value="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_id']; ?>" <?php if ((!empty($spesa['spese_tipologia_autofattura']) && $spesa['spese_tipologia_autofattura'] == $tipologia['documenti_contabilita_tipologie_fatturazione_id'])): ?> selected="selected" <?php endif;?>><?php echo "{$tipologia['documenti_contabilita_tipologie_fatturazione_codice']} {$tipologia['documenti_contabilita_tipologie_fatturazione_descrizione']}"; ?></option>
                                <?php endif;?>
                            <?php endforeach;?>

                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label style="min-width:80px">Note interne: </label>
                                <input type="text" name="spese_note" class="form-control" placeholder="Note" value="<?php $key = 'spese_note';
                                if (!empty($spesa[$key])): ?><?php echo $spesa[$key]; ?><?php endif; ?>" />
                            </div>
                        </div>
                    </div>


                </div>

            </div>
            <?php if ($full_enabled):
    $modelli = $this->db
        ->join('prime_note', 'prime_note_id = prime_note_modelli_prima_nota', 'LEFT')
        ->join('prime_note_causali', 'prime_note_causali_id = prime_note_causale', 'LEFT')
        ->join('prime_note_tipo', 'prime_note_tipo_id = prime_note_causali_tipo', 'LEFT')
        ->where('prime_note_tipo_value', 'Acquisti')
        ->get('prime_note_modelli')

        ->result_array();
    //debug($modelli,true);
    ?>

										            <div class="row bg-light-blue" style="background-color:#e0eaf0;padding-bottom:10px;">
										                <div class="col-md-12">
										                    <h4>Dettagli documento di spesa</h4>
										                </div>
										                <div class="row">
										                <div class="col-md-2">
	                                                        <?php if ($full_enabled): ?>
										                        <label style="min-width:80px">Modello prima nota: </label>
										                        <select name="spese_modello_prima_nota" class="select2 form-control">
										                            <option value=""></option>
										                            <?php foreach ($modelli as $modello): ?>
										                                <option value="<?php echo $modello['prime_note_modelli_id']; ?>" <?php if ((!empty($spesa['spese_modello_prima_nota']) && $spesa['spese_modello_prima_nota'] == $modello['prime_note_modelli_id'])): ?> selected="selected" <?php endif;?>><?php echo $modello['prime_note_modelli_nome']; ?></option>
                            <?php endforeach;?>

                        </select>
                        <?php endif;?>
                    </div>

                </div>




            </div>

<?php endif;?>



        </div>

        <div class="col-md-8" style="margin-top: 0.5%;">
            <div class="row  my_dropzone dropzone upload-drop-zone">

            </div>
        </div>

    </div>
    <div class="row">
        <div class="col-md-12">


            <hr />

            <div class="row">
                <div class="col-md-12" style="margin-bottom:20px; text-align:center">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary js_totali btn-expenses">Registra solo i totali
                        </button>
                        <button type="button" class="btn btn-default js_articoli btn-expenses">Registra i singoli
                            articoli
                        </button>

                    </div>
                </div>
            </div>


            <hr />


            <div class="row elenco_articoli">
                <div class="col-md-12">


                    <table id="js_product_table" class="table table-condensed table-striped table_prodotti">
                        <thead>
                            <tr>
                                <th width="50">Codice</th>
                                <th>Nome prodotto</th>
                                <th width="30">Quantità</th>
                                <th width="90">Prezzo</th>
                                <th width="90">Sconto %</th>
                                <th width="75">IVA</th>
                                <th width="100">Importo</th>
                                <th width="35"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hidden">
                                <td><input type="text" class="form-control input-sm js_spese_articoli_codice js_autocomplete_prodotto" data-name="spese_articoli_codice" tabindex="1" />
                                </td>
                                <td>
                                    <input type="text" class="form-control input-sm js_spese_articoli_name js_autocomplete_prodotto" data-id="1" data-name="spese_articoli_name" tabindex="2" />
                                    <small>Descrizione aggiuntiva:</small>
                                    <textarea class="form-control input-sm js_spese_articoli_descrizione" data-name="spese_articoli_descrizione" tabindex="8" style="width:100%;" row="2"></textarea>
                                </td>

                                <td><input type="text" class="form-control input-sm js_spese_articoli_quantita" data-name="spese_articoli_quantita" value="1" tabindex="3" /></td>
                                <td><input type="text" class="form-control input-sm text-right js_spese_articoli_prezzo" data-name="spese_articoli_prezzo" value="0.00" tabindex="4" /></td>
                                <td><input type="text" class="form-control input-sm text-right js_spese_articoli_sconto" data-name="spese_articoli_sconto" value="0" tabindex="5" /></td>
                                <td>
                                    <input type="text" class="form-control input-sm text-right js_spese_articoli_iva_perc" data-name="spese_articoli_iva_perc" value="22" tabindex="6" />
                                    <input type="hidden" class="form-control input-sm text-right js_spese_articoli_iva" data-name="spese_articoli_iva" value="0" />

                                    <input type="hidden" class="js_spese_articoli_prodotto_id" data-name="spese_articoli_prodotto_id" />
                                </td>

                                <td>
                                    <input type="text" class="form-control input-sm text-right js-importo" data-name="spese_articoli_importo_totale" value="0" tabindex="7" />
                                    <!--<p class="form-control-static text-right js-importo">0.00</p>-->
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn  btn-danger btn-xs js_remove_product">
                                        <span class="fas fa-times"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php if (isset($spesa['articoli']) && $spesa['articoli']): ?>
                                <?php foreach ($spesa['articoli'] as $k => $prodotto): ?>
                                    <!-- DA RIVEDEER POTREBBERO MANCARE DEI CAMPI QUANDO SI FARA L EDIT -->
                                    <tr>
                                        <td><input type="text" class="form-control input-sm" tabindex="1" name="products[<?php echo $k + 1; ?>][spese_articoli_codice]" value="<?php echo $prodotto['spese_articoli_codice']; ?>" />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control input-sm" tabindex="2" name="products[<?php echo $k + 1; ?>][spese_articoli_name]" value="<?php echo $prodotto['spese_articoli_name']; ?>" /> <br />
                                            <textarea class="form-control input-sm js_spese_articoli_descrizione" data-name="products[<?php echo $k + 1; ?>][spese_articoli_descrizione]" tabindex="8" style="width:100%;" row="2"><?php echo $prodotto['spese_articoli_descrizione']; ?></textarea>
                                        </td>
                                        <td><input type="text" class="form-control input-sm js_spese_articoli_quantita" tabindex="3" name="products[<?php echo $k + 1; ?>][spese_articoli_quantita]" value="<?php echo $prodotto['spese_articoli_quantita']; ?>" placeholder="1" /></td>
                                        <td><input type="text" class="form-control input-sm text-right js_spese_articoli_prezzo" tabindex="4" name="products[<?php echo $k + 1; ?>][spese_articoli_prezzo]" value="<?php echo $prodotto['spese_articoli_prezzo']; ?>" placeholder="0.00" /></td>
                                        <td><input type="text" class="form-control input-sm text-right js_spese_articoli_sconto" tabindex="5" name="products[<?php echo $k + 1; ?>][spese_articoli_sconto]" value="<?php echo $prodotto['spese_articoli_sconto']; ?>" placeholder="0" /></td>
                                        <td><input type="text" class="form-control input-sm text-right js_spese_articoli_iva_perc" tabindex="6" name="products[<?php echo $k + 1; ?>][spese_articoli_iva_perc]" value="<?php echo $prodotto['spese_articoli_iva_perc']; ?>" placeholder="22" />
                                            <input type="hidden" class="form-control input-sm text-right js_spese_articoli_iva" data-name="spese_articoli_iva" value="0" />

                                        </td>
                                        <td><input type="text" class="form-control input-sm text-right js-importo" placeholder="0" tabindex="7" />
                                            <!--<p class="form-control-static text-right js-importo">0.00</p>-->
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-xs js_remove_product">
                                                <span class="fas fa-times"></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach;?>

                            <?php endif;?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>
                                    <button id="js_add_product" type="button" class="btn btn-primary btn-sm">+ Aggiungi
                                        prodotto
                                    </button>
                                </td>
                                <td colspan="3"></td>
                                <td class="totali bg-yellow-active" colspan="3" style="background: #faf6ea">

                                    <label>Totale: <span class="js_totale">€ 0</span></label> <label>IVA: <span class="js_tot_iva">€ 0</span></label> <label>Totale fattura: <span class="js_tot_da_saldare">€ 0</span></label>

                                </td>
                            </tr>
                        </tfoot>
                    </table>


                </div>
                <hr />
            </div>
            <div class="pref_checkboxes">
                <div class="row">
                    <div class="col-md-12" style="margin-bottom:20px; text-align:center">
                        <fieldset>
                            <legend>Preferenze addizionali</legend>
                            <div class="checkbox-inline">
                                <input type="checkbox" id="product_census" name="censisci" value="1" checked />
                                <label for="scales">Censisci / Aggiorna prodotti</label>
                            </div>

                            <div class="checkbox-inline">
                                <input type="checkbox" id="generate_movement" name="movimenti" value="1" checked />
                                <label for="horns">Genera movimento magazzino</label>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
            <div class="row">

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Imponibile</label>
                        <div class="input-group date">
                            <div class="input-group-addon">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <?php //debug($spesa);
?>
                            <input type="text" name="spese_imponibile" class="form-control pull-right js_decimal" value="<?php $key = 'spese_imponibile';
echo (!empty($spesa[$key])) ? $spesa[$key] : $imponibile?>" placeholder="0,00">
                        </div>

                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Importo IVA</label>
                        <div class="input-group date">
                            <div class="input-group-addon">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <input type="text" name="spese_iva" class="form-control pull-right js_decimal" placeholder="0,00" value="<?php $key = 'spese_iva';
if (!empty($spesa[$key])): ?><?php echo $spesa[$key]; ?><?php endif;?>">
                        </div>

                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Ritenuta d'acconto</label>
                        <div class="input-group date">
                            <div class="input-group-addon">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <input type="text" name="spese_rit_acconto" class="form-control pull-right js_decimal" value="<?php $key = 'spese_rit_acconto';
if (!empty($spesa[$key])): ?><?php echo $spesa[$key]; ?><?php endif;?>" placeholder="0,00">
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Deducibilità tasse</label>
                        <div class="input-group date">
                            <div class="input-group-addon">
                                %
                            </div>
                            <input type="text" name="spese_deduc_tasse" class="form-control pull-right" placeholder="0" value="<?php $key = 'spese_deduc_tasse';
if (!empty($spesa[$key])): ?><?php echo $spesa[$key]; ?><?php endif;?>">
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Deducibilità IVA</label>
                        <div class="input-group date">
                            <div class="input-group-addon">
                                %
                            </div>
                            <input type="text" name="spese_deduc_iva" class="form-control pull-right" placeholder="0" value="<?php $key = 'spese_deduc_iva';
if (!empty($spesa[$key])): ?><?php echo $spesa[$key]; ?><?php endif;?>">
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Anni ammortamento</label>
                        <select class="select2 form-control" name="spese_anni_ammortamento" style="width:100%">
                            <option <?php if ((empty($spesa_id))): ?> selected <?php endif;?>></option>
                            <?php foreach ($spese_anni_ammortamento as $ammortamento): ?>
                                <option value="<?php echo $ammortamento['spese_anni_ammortamento_id']; ?>" <?php if ((!empty($spesa['spese_anni_ammortamento']) && $spesa['spese_anni_ammortamento'] == $ammortamento['spese_anni_ammortamento_id'])): ?> selected="selected" <?php endif;?>><?php echo $ammortamento['spese_anni_ammortamento_value']; ?></option>
                            <?php endforeach;?>
                        </select>

                    </div>
                </div>
            </div>

            <hr />

            <div class="row">

                <div class="col-md-8 col-offset-2 scadenze_box bg-light-blue" style="background-color:#eeeeee;margin:0 auto;float:none;">

                    <div class="row">
                        <div class="col-md-12">
                            <h4>Scadenze pagamento</h4>
                        </div>
                    </div>


                    <div class="row js_rows_scadenze">

                        <?php if (!empty($spesa['scadenze'])): ?>

                            <?php foreach ($spesa['scadenze'] as $key => $scadenza):  ?>




										                                <div class="row row_scadenza">
                                <input type="hidden" name="scadenze[<?php echo $key; ?>][spese_scadenze_id]"                                            value="<?php echo @$scadenza['spese_scadenze_id']; ?>" />
                                <?php if (!empty($scadenza['_flussi_cassa_id'])) : ?>
                                    <input type="hidden" name="scadenze[<?php echo $key; ?>][_flussi_cassa_id]" value="<?php echo $scadenza['_flussi_cassa_id']; ?>" />
                                    
                                <?php endif; ?>

										                                    <div class="col-md-2">
										                                        <div class="form-group">
										                                            <label>Ammontare</label> <input type="text" name="scadenze[<?php echo $key; ?>][spese_scadenze_ammontare]" class="form-control spese_scadenze_ammontare" placeholder="Ammontare" value="<?php echo $scadenza['spese_scadenze_ammontare']; ?>" data-name="spese_scadenze_ammontare" />
										                                        </div>
										                                    </div>
										                                    <div class="col-md-3">
										                                        <div class="form-group">
										                                            <label>Scadenza</label>
										                                            <div class="input-group js_form_datepicker date ">
										                                                <input type="text" name="scadenze[<?php echo $key; ?>][spese_scadenze_scadenza]" class="form-control js_spese_scadenze_scadenza js_form_datepicker" placeholder="Scadenza" value="<?php echo date('d/m/Y', strtotime($scadenza['spese_scadenze_scadenza'])); ?>" data-name="spese_scadenze_scadenza" />
										                                                <span class="input-group-btn">
										                                                    <button class="btn btn-default" type="button" style="display:none"><i class="fas fa-calendar-alt-alt"></i></button>
										                                                </span>
										                                            </div>
										                                        </div>
										                                    </div>
										                                    <div class="col-md-2">
										                                        <div class="form-group">
										                                            <label>Metodo</label> <select name="scadenze[<?php echo $key; ?>][spese_scadenze_saldato_con]" class="_select2 form-control _js_table_select2 _js_table_select2<?php echo $key; ?> js_spese_scadenze_saldato_con" data-name="spese_scadenze_saldato_con">

										                                                <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
										                                                    <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']; ?>" <?php if ($scadenza['spese_scadenze_saldato_con'] == $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']): ?> selected="selected" <?php endif;?>>
                                                        <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                                    </option>
                                                <?php endforeach;?>
                                            </select>

                                            
                                        </div>
                                    </div>

                                             <div class="col-md-2">
										                                        <div class="form-group">
										                                            <label>Saldato da</label> 
                                                                                    
                                                                                    <select name="scadenze[<?php echo $key; ?>][spese_scadenze_saldato_da]" class="select2 form-control js_spese_scadenze_saldato_da" data-name="spese_scadenze_saldato_da">
<option value="">---</option>
										                                                <?php foreach ($conti_correnti as $conto_corrente) : ?>
                                            <option value="<?php echo $conto_corrente['conti_correnti_id']; ?>" <?php if ($scadenza['spese_scadenze_saldato_da'] == $conto_corrente['conti_correnti_id']) : ?> selected="selected" <?php endif; ?>>
                                                <?php echo ucfirst($conto_corrente['conti_correnti_nome_istituto']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                            </select>

                                            
                                        </div>
                                    </div>                                                   

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Data saldo</label>
                                            <div class="input-group js_form_datepicker date  field_68">
                                                <input type="text" class="form-control spese_scadenze_data_saldo" id="empty_date" name="scadenze[<?php echo $key; ?>][spese_scadenze_data_saldo]" data-name="spese_scadenze_data_saldo" value="<?php echo ($scadenza['spese_scadenze_data_saldo']) ? date('d/m/Y', strtotime($scadenza['spese_scadenze_data_saldo'])) : ''; ?>">

                                                <span class="input-group-btn">
                                                    <button class="btn btn-default" type="button" style="display:none;"><i class="fas fa-calendar-alt-alt"></i></button>
                                                </span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;?>
                        <?php endif;?>

                        <div class="row row_scadenza">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Ammontare</label> <input type="text" name="scadenze[99][spese_scadenze_ammontare]" class="form-control spese_scadenze_ammontare" placeholder="Ammontare" value="<?php echo $ammontare; ?>" data-name="spese_scadenze_ammontare" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text" name="scadenze[99][spese_scadenze_scadenza]" class="form-control js_spese_scadenze_scadenza js_form_datepicker" placeholder="Scadenza" value="<?php echo ($data_scadenza) ? $data_scadenza : date('d/m/Y'); ?>" data-name="spese_scadenze_scadenza" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fas fa-calendar-alt-alt"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Metodo</label>
                                    <select name="scadenze[99][spese_scadenze_saldato_con]" class="select2 form-control js_table_select2 js_table_select2<?php echo $key; ?>" data-name="spese_scadenze_saldato_con">

                                        <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                            <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']; ?>" <?php if ($metodo_pagamento['documenti_contabilita_metodi_pagamento_codice'] == 'MP05'): //bonifico
    ?> selected="selected" <?php endif;?>>
                                                <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                            </option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
										                                        <div class="form-group">
										                                            <label>Saldato da</label> 
                                                                                    
                                                                                    <select name="scadenze[99][spese_scadenze_saldato_da]" class="select2 form-control js_spese_scadenze_saldato_da" data-name="spese_scadenze_saldato_da">
<option value="">---</option>
										                                                <?php foreach ($conti_correnti as $conto_corrente) : ?>
                                            <option value="<?php echo $conto_corrente['conti_correnti_id']; ?>" >
                                                <?php echo ucfirst($conto_corrente['conti_correnti_nome_istituto']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                            </select>

                                            
                                        </div>
                                    </div>  

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Data saldo</label>
                                    <div class="input-group js_form_datepicker date  field_68">
                                        <input type="text" class="form-control" name="scadenze[99][spese_scadenze_data_saldo]" id="empty_date" data-name="spese_scadenze_data_saldo" value="<?php echo $data_saldo; ?>">

                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fas fa-calendar-alt-alt"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button id="js_add_scadenza" class="btn btn-primary btn-sm hidden">+ Aggiungi scadenza
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div id="msg_new_spesa" class="alert alert-danger hide"></div>
                </div>
            </div>
        </div>
    </div>


    <div class="form-actions fluid">
        <div class="col-md-12">
            <div class="pull-left">
                <a href="<?php echo base_url('main/layout/elenco_spese'); ?>" class="btn btn-info"><i class="fas fa-arrow-left"></i> Elenco Spese</a>
            </div>
            <div class="pull-right">
                <button type="submit" class="btn btn-success">Salva spesa</button>
            </div>
            <div class="clearfix"></div>
        </div>

    </div>
    </div>
</form>


<script>
    var token = JSON.parse(atob('<?php echo base64_encode(json_encode(get_csrf())); ?>'));
    var token_name = token.name;
    var token_hash = token.hash;

    var mode = 'totali';
    $('.js_articoli').click(function() {
        $('.elenco_articoli').fadeIn();
        $('.pref_checkboxes').fadeIn();
        mode = 'articoli';
    });
    $('.js_totali').click(function() {
        $('.elenco_articoli').fadeOut();
        $('.pref_checkboxes').fadeOut();
        mode = 'totali';
    });

    $('.btn-expenses').click(function(e) {
        $('.btn-expenses').removeClass('btn-primary');
        $('.btn-expenses').addClass('btn-default');
        $(this).addClass('btn-primary');
        $(this).removeClass('btn-default');

        //calculateTotals();
    });
</script>

<script>
    /****************** AUTOCOMPLETE Destinatario *************************/
    function initAutocomplete(autocomplete_selector) {

        autocomplete_selector.autocomplete({
            source: function(request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "contabilita/documenti/autocomplete/fw_products",
                    dataType: "json",
                    data: {
                        search: request.term,
                        [token_name]: token_hash
                    },
                    /*search: function( event, ui ) {
                        loading(true);
                    },*/
                    success: function(data) {
                        var collection = [];
                        loading(false);

                        //                        console.log(autocomplete_selector.data("id"));
                        //                        if (data.count_total == 1) {
                        //                            popolaProdotto(data.results.data[0], autocomplete_selector.data("id"));
                        //                        } else {

                        $.each(data.results.data, function(i, p) {
                            //console.log(p);
                            collection.push({
                                "id": p.fw_products_id,
                                "label": p.fw_products_sku + ': ' + p.fw_products_name,
                                "value": p
                            });
                        });
                        //                        }

                        //console.log(collection);
                        response(collection);
                    }
                });
            },
            minLength: 3,
            select: function(event, ui) {
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                //console.log(ui.item.value);
                popolaProdotto(ui.item.value, autocomplete_selector.data("id"));
                return false;
            }
        });
    }

    function popolaProdotto(prodotto, rowid) {

        $("input[name='products[" + rowid + "][spese_articoli_codice]']").val(prodotto['fw_products_sku']);
        $("input[name='products[" + rowid + "][spese_articoli_name]']").val(prodotto['fw_products_name']);
        $("input[name='products[" + rowid + "][spese_articoli_descrizione]']").val(prodotto['fw_products_description']);
        $("input[name='products[" + rowid + "][spese_articoli_prezzo]']").val(prodotto['fw_products_sell_price']);
        $("input[name='products[" + rowid + "][spese_articoli_sconto]']").val(prodotto['fw_products_discount_percentage']);
        if (isNaN(parseInt(prodotto['fw_products_tax_value']))) {
            $("input[name='products[" + rowid + "][spese_articoli_iva]']").val('0');
        } else {
            $("input[name='products[" + rowid + "][spese_articoli_iva]']").val(parseInt(prodotto['fw_products_tax_value']));
        }
        
        $("input[name='products[" + rowid + "][spese_articoli_prodotto_id]']").val(prodotto['fw_products_id']);

        $("input[name='products[" + rowid + "][spese_articoli_quantita]']").val(1);

        calculateTotals();
    }


    $(document).ready(function() {

        <?php if ($spesa_id): ?>
            calculateTotals(<?php echo $spesa_id; ?>);
        <?php endif;?>

        /****************** AUTOCOMPLETE Destinatario *************************/
        $("#search_cliente").autocomplete({
            source: function(request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "contabilita/documenti/autocomplete/suppliers",
                    dataType: "json",
                    data: {
                        search: request.term,
                        [token_name]: token_hash
                    },
                    minLength: 0,
                    /*search: function( event, ui ) {
                        loading(true);
                    },*/
                    success: function(data) {
                        var collection = [];
                        loading(false);

                        //                        if (data.count_total == 1) {
                        //
                        //                            popolaCliente(data.results.data[0]);
                        //                        } else {

                        $.each(data.results.data, function(i, p) {
                            console.log(p);
                            collection.push({
                                "id": p.customers_id,
                                "label": p.customers_company,
                                "value": p

                            });
                        });
                        //                        }

                        //console.log(collection);
                        response(collection);
                    }
                });
            },
            minLength: 3,
            //            focus: function (event, ui) {
            //                return false;
            //            },
            select: function(event, ui) {
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                console.log(ui.item.value);
                popolaFornitore(ui.item.value);
                //drawProdotto(ui.item.value, true);
                return false;
            }
        });


        function popolaFornitore(fornitore) {
            //Cambio la label
            $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

            $('.js_dest_ragione_sociale').val(fornitore['customers_company']);
            $('.js_dest_indirizzo').val(fornitore['customers_address']);
            $('.js_dest_citta').val(fornitore['customers_city']);
            $('.js_dest_cap').val(fornitore['customers_zip_code']);
            $('.js_dest_provincia').val(fornitore['customers_province']);
            $('.js_dest_nazione').val(fornitore['countries_iso']).trigger('change');
            $('.js_dest_partita_iva').val(fornitore['customers_vat_number']);
            $('.js_dest_codice_fiscale').val(fornitore['customers_cf']);
            $('#js_dest_id').val(fornitore['customers_id']);
        }


        initAutocomplete($('.js_autocomplete_prodotto'));


        $('.js_select2').each(function() {
            var select = $(this);
            var placeholder = select.attr('data-placeholder');
            select.select2({
                placeholder: placeholder ? placeholder : '',
                allowClear: true
            });
        });


        $('[name="spese_data_emissione"]').on('change', function () {
            const spese_data_emissione = moment($(this).val(), 'DD/MM/YYYY');


            //$('.js_spese_scadenze_scadenza').datepicker('setDate', new Date(spese_data_emissione.year(), spese_data_emissione.month(), spese_data_emissione.date()));
            $('.js_spese_scadenze_scadenza').val(spese_data_emissione.format('DD/MM/YYYY'));

            //initComponents($('.js_rows_scadenze'), true);

        });
    });
</script>


<script>
    var totale = 0;
    var totale_iva = 0;
    var totale_no_iva = 0;

    var ritenuta = 0;

    function reverseRowCalculate(tr) {
        //Calcolo gli importi basandomi sul totale...
        var qty = parseFloat($('.js_spese_articoli_quantita', tr).val());
        var sconto = parseFloat($('.js_spese_articoli_sconto', tr).val());
        var iva = parseFloat($('.js_spese_articoli_iva_perc', tr).val());

        if (isNaN(qty)) {
            qty = 0;
        }
        if (isNaN(sconto)) {
            sconto = 0;
        }
        if (isNaN(iva)) {
            iva = 0;
        }

        var importo_ivato = parseFloat($('.js-importo', tr).val());

        //Applico lo sconto al rovescio
        var importo = parseFloat(importo_ivato / ((100 + iva) / 100));
        var importo_ricalcolato = parseFloat(importo_ivato - ((importo_ivato / 100) * sconto));


        //console.log(importo);

        $('.js-importo', tr).val(importo_ricalcolato.toFixed(2));
        $('.js_spese_articoli_prezzo', tr).val(importo.toFixed(2));
        //
        calculateTotals();
    }

    function calculateTotals(spesa_id) {
        //debugger
        ritenuta = 0;
        totale = 0;
        totale_iva = 0;
        totale_iva_divisa = {};
        totale_no_iva = 0;
        if (mode == 'articoli') {
            
            $('#js_product_table tbody tr:not(.hidden)').each(function() {
                var qty = parseFloat($('.js_spese_articoli_quantita', $(this)).val());
                var prezzo = parseFloat($('.js_spese_articoli_prezzo', $(this)).val());
                var sconto = parseFloat($('.js_spese_articoli_sconto', $(this)).val());
                var iva = parseFloat($('.js_spese_articoli_iva_perc', $(this)).val());

                if (isNaN(qty)) {
                    qty = 0;
                }
                if (isNaN(prezzo)) {
                    prezzo = 0;
                }
                if (isNaN(sconto)) {
                    sconto = 0;
                }
                if (isNaN(iva)) {
                    iva = 0;
                }
                //            console.log(qty);
                //            console.log(prezzo);
                //            console.log(sconto);
                //            console.log(iva);
                var totale_riga = prezzo * qty;
                var totale_riga_scontato = (totale_riga / 100) * (100 - sconto);
                var totale_riga_scontato_ivato = parseFloat((totale_riga_scontato / 100) * (100 + iva));
                totale_no_iva += totale_riga_scontato;
                if (isNaN(totale_iva_divisa[iva])) {
                    totale_iva_divisa[iva] = parseFloat((totale_riga_scontato / 100) * iva);
                } else {
                    totale_iva_divisa[iva] += parseFloat((totale_riga_scontato / 100) * iva);
                }

                totale_iva += parseFloat((totale_riga_scontato / 100) * iva);
                totale += totale_riga_scontato_ivato;

                $('.js-importo', $(this)).val(totale_riga_scontato_ivato.toFixed(2));
                $('.js_spese_articoli_iva', $(this)).val(parseFloat((totale_riga_scontato / 100) * iva).toFixed(2));


            });
            ritenuta = parseFloat($('[name="spese_rit_acconto"]').val());
            if (isNaN(ritenuta)) {
                ritenuta = 0;
            }
            totale -= ritenuta;
            $('[name="spese_imponibile"]').val(totale_no_iva.toFixed(2));
        } else { //solo totali
            var imponibile = parseFloat($('[name="spese_imponibile"]').val());
            totale_iva = parseFloat($('[name="spese_iva"]').val());
            ritenuta = parseFloat($('[name="spese_rit_acconto"]').val());
            var spese_deduc_tasse = parseFloat($('[name="spese_deduc_tasse"]').val()).toFixed(2);
            var spese_deduc_iva = parseFloat($('[name="spese_deduc_iva"]').val()).toFixed(2);

            if (isNaN(imponibile)) {
                imponibile = 0;
            }
            if (isNaN(totale_iva)) {
                totale_iva = 0;
            }
            if (isNaN(ritenuta)) {
                ritenuta = 0;
            }

            totale += parseFloat((imponibile + totale_iva - ritenuta));
            totale_no_iva = parseFloat((imponibile - ritenuta));


        }

        //console.log(totale_no_iva);

        $('.js_totale').html('€ ' + totale_no_iva.toFixed(2));

        $(".js_tot_iva:not(:first)").remove();
        $(".js_tot_iva:first").hide();
        for (var i in totale_iva_divisa) {

            //console.log(totale_iva_divisa);

            $(".js_tot_iva:last").clone().insertAfter(".js_tot_iva:last").show();
            $('.js_tot_iva:last').html(`IVA (` + i + `%): <span>€ ` + totale_iva_divisa[i].toFixed(2) + `</span>`); //'€ '+totale_iva.toFixed(2));
        }

        $('.js_tot_da_saldare').html('€ ' + totale.toFixed(2));

        //alert(totale + ritenuta);

        $('[name="spese_totale"]').val(totale + ritenuta);
        $('[name="spese_iva"]').val(totale_iva);




        //$('.spese_scadenze_ammontare').val(totale.toFixed(2));

        if (isNaN(spesa_id)) {
            $('.spese_scadenze_ammontare').val(totale);
            $('.spese_scadenze_ammontare:first').trigger('change');
        } else {
            // Todo da rivedere in quanto in fase di modifica mi trovo sempre la riga vuota che invece si potrebbe togliere
            //$('.spese_scadenze_ammontare:last').closest('.row_scadenza').remove();
            $('.spese_scadenze_ammontare:last').trigger('change');
        }

    }

    function increment_scadenza() {
        var counter_scad = $('.row_scadenza').length;
        var rows_scadenze = $('.js_rows_scadenze');
        // Fix per clonare select inizializzata
        if ($('.js_table_select2').filter(':first').data('select2')) {
            $('.js_table_select2').filter(':first').select2('destroy');
        } else {

        }

        var newScadRow = $('.row_scadenza').filter(':first').clone();
        $('.spese_scadenze_data_saldo', newScadRow).val('');
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


        // $('.js_form_datepicker input', newScadRow).datepicker({
        //     todayBtn: 'linked',
        //     format: 'dd/mm/yyyy',
        //     todayHighlight: true,
        //     weekStart: 1,
        //     language: 'it'
        // });

        /* Line manipulation end */
        counter_scad++;
        newScadRow.appendTo(rows_scadenze);
    }

    $(document).ready(function() {
        var table = $('#js_product_table');
        var body = $('tbody', table);
        var rows = $('tr', body);
        var increment = $('#js_add_product', table);

        var rows_scadenze = $('.js_rows_scadenze');
        //var increment_scadenza = $('#js_add_scadenza');

        var firstRow = rows.filter(':first');
        var counter = rows.length;

        table.on('change', '.js_spese_articoli_quantita, .js_spese_articoli_prezzo, .js_spese_articoli_sconto, .js_spese_articoli_iva_perc', function() {
            calculateTotals();
        });

        $('[name="spese_imponibile"],[name="spese_iva"],[name="spese_rit_acconto"]').on('change', function() {
            calculateTotals();
        });


        table.on('change', '.js-importo', function() {

            reverseRowCalculate($(this).closest('tr'));
        });

        // Aggiungi prodotto
        increment.on('click', function() {
            var newRow = firstRow.clone();

            /* Line manipulation begin */
            newRow.removeClass('hidden');
            $('input, select, textarea', newRow).each(function() {
                var control = $(this);
                var name = control.attr('data-name');
                if (name) {
                    control.attr('name', 'products[' + counter + '][' + name + ']').removeAttr('data-name');
                }
                //control.val("");
            });

            $('.js_table_select2', newRow).select2({
                placeholder: "Seleziona prodotto",
                allowClear: true
            });
            $('.js_autocomplete_prodotto', newRow).data('id', counter);
            initAutocomplete($('.js_autocomplete_prodotto', newRow));

            /* Line manipulation end */

            counter++;
            newRow.appendTo(body);
        });


        table.on('click', '.js_remove_product', function() {
            $(this).parents('tr').remove();
            calculateTotals();
        });
        $('#offerproducttable .js_remove_product').on('click', function() {
            $(this).parents('tr').remove();
        });


        //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
        rows_scadenze.on('change', '.spese_scadenze_ammontare', function() {
            //Se la somma degli ammontare è minore del totale procedo
            var totale_scadenze = 0;
            $('.spese_scadenze_ammontare').each(function() {
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
                    $('.spese_scadenze_ammontare', next_row).val((totale - totale_scadenze).toFixed(2));
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

            // if (ritenuta > 0) { //Se c'è una ritenuta, l'ultima scadenza (che è la ritenuta stessa) la forzo con metodo F24
            //     var ultima_scadenza = $('.row_scadenza').last();
            //     var prima_scadenza = $('.row_scadenza:visible').first();
            //     $('.js_spese_scadenze_saldato_con', ultima_scadenza).val('quietanza erario');
            //     var dataSaldoPrimaScadenza = $('.spese_scadenze_data_saldo', prima_scadenza).val();
            //     if (dataSaldoPrimaScadenza) {
            //         var dataPrecedente = moment(dataSaldoPrimaScadenza, 'DD/MM/YYYY');
            //     } else {
            //         var dataPrecedente = moment($('.js_spese_scadenze_scadenza', prima_scadenza).val(), 'DD/MM/YYYY');
            //     }

            //     var scadenzaF24 = moment(dataPrecedente).add(1, 'M').format('16/MM/YYYY');
            //     $('.js_spese_scadenze_scadenza', ultima_scadenza).val(scadenzaF24);
            // }

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

<link rel="stylesheet" type="text/css" href="<?php echo base_url_template('script/global/plugins/dropzone/css/dropzone.css'); ?>" />
<script src="<?php echo base_url_template('script/global/plugins/dropzone/dropzone.js'); ?>"></script>
<script>
    Dropzone.autoDiscover = false;
    $(document).ready(function() {

        var myDropzone = new Dropzone(document.querySelector('.my_dropzone'), {
            url: "<?php echo base_url('contabilita/spese/addFile'); ?>",
            autoProcessQueue: false,
            parallelUploads: 1,
            addRemoveLinks: true,
            clickable: true,
            params: {
                [token_name]: token_hash
            },
            complete: function(file) {
                file._downloadLink = Dropzone.createElement("<center><a class=\"\" target=\"_blank\" href=\"" + file.url + "\" >Download</a></center>");
                file.previewElement.appendChild(file._downloadLink);
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    //alert('tst');
                    $('#new_spesa').trigger('submit');
                    loading(false);
                } else {
                    loading(true);
                    $('#new_spesa [type="submit"]').trigger('click');
                }
            },
            success: function(file, response) {
                file._downloadLink = Dropzone.createElement("<center><a class=\"\" target=\"_blank\" href=\"" + file.url + "\" >Download</a></center>");
                file.previewElement.appendChild(file._downloadLink);
                return file.previewElement.classList.add("dz-success");
            },
            removedfile: function(file) {
                x = confirm('Do you want to delete?');
                //console.log(file);
                if (!x) {
                    return false;
                } else {
                    if (file.id) {
                        $.ajax(base_url + 'contabilita/spese/removeFile/' + file.id, {
                            //dataType: 'json',
                            success: function() {
                                file.previewElement.remove();
                                return true;
                            }
                        });
                    } else {
                        file.previewElement.remove();
                        return true;
                    }

                }
            }

        });
        $('#new_spesa [type="submit"]').click(function(e) {

            loading(true);
            e.preventDefault();
            e.stopPropagation();
            if (myDropzone.files.length > 0) {
                myDropzone.processQueue();
            } else {
                $('#new_spesa').trigger('submit');
            }

        });

        $('.my_dropzone *').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.my_dropzone').trigger('click');
        });


        <?php if ($documento_id): ?>
            $('.js_articoli').trigger('click');
        <?php endif;?>
        <?php if (!empty($spesa['allegati'])) : ?>
            <?php foreach ($spesa['allegati'] as $key => $allegato): ?>

                <?php //debug($allegato,true);
    ?>

                // Create the mock file:
                var mockFile = {
                    name: "Allegato <?php echo $key + 1; ?>",
                    size: 1000000,
                    id: <?php echo $allegato['spese_allegati_id']; ?>,
                    url: "<?php echo base_url_uploads('uploads/' . $allegato['spese_allegati_file']); ?>"
                };

                // Call the default addedfile event handler
                myDropzone.emit("addedfile", mockFile);
                myDropzone.emit("success", mockFile);
                // And optionally show the thumbnail of the file:
                myDropzone.emit("thumbnail", mockFile, "<?php echo base_url('images/docs_icon.svg'); ?>");

            <?php endforeach;?>
            <?php endif; ?>
    });
</script>
