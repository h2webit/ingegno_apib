
<style>
    #js_product_table > tbody > tr > td,
    #js_product_table > tbody > tr > th,
    #js_product_table > tfoot > tr > td,
    #js_product_table > tfoot > tr > th,
    #js_product_table > thead > tr > td {
        vertical-align: top;
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

    .rcr-adjust{
        width: 40%;
        display: inline;
    }

    .rcr_label label{
        width: 100%;
    }

    .margin-bottom-5{
        margin-bottom: 5px;
    }

    .margin-left-20{
        margin-left: 20px;
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
defined('ENTITY_SETTINGS') OR define('ENTITY_SETTINGS', 'settings');
defined('FATTURE_E_CUSTOMERS') OR define('FATTURE_E_CUSTOMERS', 'clienti');

/** Parametri **/
defined('FATTURAZIONE_METODI_PAGAMENTO') OR define('FATTURAZIONE_METODI_PAGAMENTO', serialize(array('Bonifico', 'Paypal', 'Contanti', 'Sepa RID', 'RIBA')));

defined('FATTURAZIONE_URI_STAMPA') OR define('FATTURAZIONE_URI_STAMPA', null);

$elenco_iva = $this->apilib->search('iva', [], null, 0, 'iva_order');
$serie_documento = $this->apilib->search('documenti_contabilita_serie');
$conti_correnti = $this->apilib->search('conti_correnti');
$documento_id = ($value_id)?:$this->input->get('documenti_contabilita_id');
$documenti_tipo = $this->apilib->search('documenti_contabilita_tipo');
$centri_di_costo = $this->apilib->search('centri_di_costo_ricavo');
if ($documento_id) {

    $documento = $this->apilib->view('documenti_contabilita', $documento_id);
    //debug($documento,true);
    $documento['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);
    $documento['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
    $documento['documenti_contabilita_destinatario'] = json_decode($documento['documenti_contabilita_destinatario'], true);
    $documento['entity_destinatario'] = ($documento['documenti_contabilita_fornitori_id'])?'fw_providers':'clienti';
    //debug($documento);
}
?>


<form class="formAjax" id="new_fattura" action="<?php echo base_url('custom/documents/create_document'); ?>">

    <?php if ($documento_id): ?>
        <input name="documento_id" type="hidden" value="<?php echo $documento_id; ?>"/>
    <?php endif; ?>

    <input type="hidden" name="documenti_contabilita_totale" value="<?php echo ($documento_id && $documento['documenti_contabilita_totale'])?$documento['documenti_contabilita_totale']:''; ?>" />
    <input type="hidden" name="documenti_contabilita_iva" value="<?php echo ($documento_id && $documento['documenti_contabilita_iva'])?$documento['documenti_contabilita_iva']:''; ?>" />

    <input type="hidden" name="documenti_contabilita_competenze" value="<?php echo ($documento_id && $documento['documenti_contabilita_competenze'])?$documento['documenti_contabilita_competenze']:''; ?>" />

    <input type="hidden" name="documenti_contabilita_rivalsa_inps_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_rivalsa_inps_valore'])?$documento['documenti_contabilita_rivalsa_inps_valore']:''; ?>" />
    <input type="hidden" name="documenti_contabilita_competenze_lordo_rivalsa" value="<?php echo ($documento_id && $documento['documenti_contabilita_competenze_lordo_rivalsa'])?$documento['documenti_contabilita_competenze_lordo_rivalsa']:''; ?>" />
    <?php //debug($documento); ?>
    <input type="hidden" name="documenti_contabilita_cassa_professionisti_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_cassa_professionisti_valore'])?$documento['documenti_contabilita_cassa_professionisti_valore']:''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile'])?$documento['documenti_contabilita_imponibile']:''; ?>" />
    <input type="hidden" name="documenti_contabilita_ritenuta_acconto_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_ritenuta_acconto_valore'])?$documento['documenti_contabilita_ritenuta_acconto_valore']:''; ?>" />
    <input type="hidden" name="documenti_contabilita_ritenuta_acconto_imponibile_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'])?$documento['documenti_contabilita_ritenuta_acconto_imponibile_valore']:''; ?>" />

    <input type="hidden" name="documenti_contabilita_iva_json" value="<?php echo ($documento_id && $documento['documenti_contabilita_iva_json'])?$documento['documenti_contabilita_iva_json']:''; ?>" />


    <div class="row">
        <div class="col-md-12" style="margin-bottom:20px;">
            <label>Tipo di documento:</label>
            <div class="btn-group">
                <?php foreach ($documenti_tipo as $tipo): ?>
                    <button type="button" class="btn <?php if ($documento_id && ($documento_id && $documento['documenti_contabilita_tipo'] == $tipo['documenti_contabilita_tipo_id'])): ?>btn-primary<?php else: ?>btn-default<?php endif; ?> js_btn_tipo" data-tipo="<?php echo $tipo['documenti_contabilita_tipo_id']; ?>"><?php echo $tipo['documenti_contabilita_tipo_value']; ?></button>
                <?php endforeach; ?>


                <input type="hidden" name="documenti_contabilita_tipo" class="js_documenti_contabilita_tipo" value="<?php if ($documento_id && $documento['documenti_contabilita_tipo']): ?><?php echo $documento['documenti_contabilita_tipo']; ?><?php endif; ?>"/>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4" style="background-color:#eeeeee;">
            <h4>Dati del <span class="js_dest_type"><?php if ($documento_id && $documento['documenti_contabilita_fornitori_id']): ?>fornitore<?php else : ?>cliente<?php endif; ?></span></h4>

            <input type="hidden" name="dest_entity_name" value="<?php if ($documento_id && $documento['documenti_contabilita_fornitori_id']): ?>fw_providers<?php else : ?>clienti<?php endif; ?>"/>
            <input id="js_dest_id" type="hidden" name="dest_id" value="<?php if ($documento_id && $documento['documenti_contabilita_clienti_id']): ?><?php echo ($documento['documenti_contabilita_clienti_id']?:$documento['documenti_contabilita_fornitori_id']); ?><?php endif; ?>"/>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input id="search_cliente" type="text" name="ragione_sociale"
                               class="form-control js_dest_ragione_sociale" placeholder="Ragione sociale" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["ragione_sociale"]; ?><?php endif; ?>"
                               autocomplete="off" />
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="indirizzo" class="form-control js_dest_indirizzo"
                               placeholder="Indirizzo"
                               value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["indirizzo"]; ?><?php endif; ?>"/>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="citta" class="form-control js_dest_citta" placeholder="Città"
                               value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["citta"]; ?><?php endif; ?>"/>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="nazione" class="form-control js_dest_nazione" placeholder="Nazione"
                               value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["nazione"]; ?><?php else: ?><?php echo "Italia"; ?><?php endif; ?>"/>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <input type="text" name="cap" class="form-control js_dest_cap" placeholder="CAP" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["cap"]; ?><?php endif; ?>"/>
                    </div>
                </div>
                <div class="col-md-6">
                    <div clasS="form-group">
                        <input type="text" name="provincia" class="form-control js_dest_provincia" placeholder="Provincia" maxlength="2"
                               value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["provincia"]; ?><?php endif; ?>"/>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="partita_iva" class="form-control js_dest_partita_iva"
                               placeholder="P.IVA" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["partita_iva"]; ?><?php endif; ?>"/>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="codice_fiscale" class="form-control js_dest_codice_fiscale"
                               placeholder="Codice fiscale" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']["codice_fiscale"]; ?><?php endif; ?>"/>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label id="js_label_rubrica">Salva in rubrica</label>
                        <input type="checkbox" class="minimal" name="save_dest" value="true"/>

                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-8">
            <div class="row" style="background-color:#e0eaf0;">
                <div class="col-md-12">
                    <h4>Dati <span class="js_doc_type">documento</span></h4>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Numero: </label>
                            <input type="text" name="documenti_contabilita_numero" class="form-control documenti_contabilita_numero" placeholder="Numero documento" value="<?php if (!empty($documento['documenti_contabilita_numero'])) : ?><?php echo $documento['documenti_contabilita_numero']; ?><?php endif; ?>"/>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Serie: </label><br />
                        <div class="btn-group">
                            <?php foreach ($serie_documento as $serie): ?>
                                <button type="button" class="btn js_btn_serie btn-info <?php if (!empty($documento['documenti_contabilita_serie']) && $documento['documenti_contabilita_serie'] == $serie['documenti_contabilita_serie_value']) : ?>button_selected<?php endif; ?>"
                                        data-serie="<?php echo $serie['documenti_contabilita_serie_value']; ?>">
                                    /<?php echo $serie['documenti_contabilita_serie_value']; ?></button>
                            <?php endforeach; ?>
                            <input type="hidden" class="js_documenti_contabilita_serie" name="documenti_contabilita_serie" value="<?php if (!empty($documento['documenti_contabilita_serie'])) : ?><?php echo $documento['documenti_contabilita_serie']; ?><?php endif; ?>"/>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Data emissione: </label>
                            <?php //debug($documento); ?>
                            <div class="input-group js_form_datepicker date ">
                                <input type="text"
                                       name="documenti_contabilita_data_emissione"
                                       class="form-control"
                                       placeholder="Data emissione" value="<?php if (!empty($documento['documenti_contabilita_data_emissione'])) : ?><?php echo date('d/m/Y', strtotime($documento['documenti_contabilita_data_emissione'])); ?><?php else : ?><?php echo date('d/m/Y'); ?><?php endif; ?>"
                                       data-name="documenti_contabilita_data_emissione"/>
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label style="min-width:80px" >Valuta: </label>
                        <select name="documenti_contabilita_valuta"
                                class="select2 form-control">
                            <?php foreach(VALUTE as $valuta => $simbolo): ?>
                                <option value="<?php echo $valuta; ?>"<?php if (($valuta == 'EUR' && empty($documento_id)) || (!empty($documento['documenti_contabilita_valuta']) && $documento['documenti_contabilita_valuta'] == $valuta)) : ?> selected="selected"<?php endif; ?>><?php echo $valuta; ?></option>
                            <?php endforeach; ?>

                        </select>
                    </div>
                    <div class="col-md-3">
                        <label style="min-width:80px" >Centro di costo: </label>
                        <select name="documenti_contabilita_centro_di_ricavo" class="select2 form-control">
                            <?php foreach($centri_di_costo as $centro): ?>
                                <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>"<?php if (($centro['centri_di_costo_ricavo_id'] == '1' && empty($documento_id)) || (!empty($documento['documenti_contabilita_centro_di_ricavo']) && $documento['documenti_contabilita_centro_di_ricavo'] == $centro['centri_di_costo_ricavo_id'])) : ?> selected="selected"<?php endif; ?>><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                            <?php endforeach; ?>

                        </select>
                    </div>
                </div>

            </div>

            <div class="row" style="background-color:#e0e8d1;">
                <div class="row">
                    <div class="col-md-12">
                        <h4>Informazioni pagamento</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <select name="documenti_contabilita_metodo_pagamento" class="select2 form-control">
                                <option value="">Metodo di pagamento</option>
                                <option value="contanti"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'contanti') : ?> selected="selected"<?php endif; ?>>Contanti</option>
                                <option value="bonifico bancario"<?php if (empty($documento['documenti_contabilita_metodo_pagamento']) || (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'bonifico bancario')) : ?> selected="selected"<?php endif; ?>>Bonifico bancario</option>
                                <option value="assegno"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'assegno') : ?> selected="selected"<?php endif; ?>>Assegno</option>
                                <option value="riba"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'riba') : ?> selected="selected"<?php endif; ?>>RiBA</option>
                                <option value="sepa_rid"<?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == 'sepa_rid') : ?> selected="selected"<?php endif; ?>>SEPA RID</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <select name="documenti_contabilita_conto_corrente" class="select2 form-control">
                                <option value="">Scegli conto corrente....</option>

                                <?php foreach ($conti_correnti as $key => $conto): ?>
                                    <option value="<?php echo $conto['conti_correnti_id']; ?>" <?php if ((empty($documento_id) && $key == 0) || (!empty($documento['documenti_contabilita_conto_corrente']) && $documento['documenti_contabilita_conto_corrente'] == $conto['conti_correnti_id'])) : ?> selected="selected"<?php endif; ?>><?php echo $conto['conti_correnti_nome_istituto']; ?></option>

                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <span>
                            <label>Accetta pagamento paypal</label> <input type="checkbox" class="minimal"
                                                                           name="documenti_contabilita_accetta_paypal" value="<?php echo DB_BOOL_TRUE; ?>"
                                    <?php if (!empty($documento['documenti_contabilita_accetta_paypal']) && $documento['documenti_contabilita_accetta_paypal'] == DB_BOOL_TRUE) : ?> checked="checked"<?php endif; ?>/>
                            </span>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <span class="margin-left-20">
                            <label>Applica modalità Split Payment</label>
                            <input type="checkbox" class="minimal"
                                   name="documenti_contabilita_split_payment" value="<?php echo DB_BOOL_TRUE; ?>"
                                <?php if (!empty($documento['documenti_contabilita_split_payment']) && $documento['documenti_contabilita_split_payment'] == DB_BOOL_TRUE) : ?> checked="checked"<?php endif; ?> />
                            </span>
                    </div>

                </div>

            </div>

            <div class="row" style="background-color:#e0eaf0;">
                <div class="col-md-12">
                    <h4>Rivalsa, Cassa INPS e Ritenuta d’acconto</h4>
                </div>
                <div class="row rcr_label">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Rivalsa INPS: </label>
                            <input type="text" class="form-control rcr-adjust" name="documenti_contabilita_rivalsa_inps_perc" value="<?php if (!empty($documento['documenti_contabilita_rivalsa_inps_perc'])) : ?><?php echo $documento['documenti_contabilita_rivalsa_inps_perc']; ?><?php else: ?>0<?php endif; ?>" /> %
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cassa professionisti: </label>
                            <input type="text" class="form-control rcr-adjust" name="documenti_contabilita_cassa_professionisti_perc" value="<?php if (!empty($documento['documenti_contabilita_cassa_professionisti_perc'])) : ?><?php echo $documento['documenti_contabilita_cassa_professionisti_perc']; ?><?php else: ?>0<?php endif; ?>"/> %
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Ritenuta d'acconto: </label>
                            <input type="text" class="form-control rcr-adjust" name="documenti_contabilita_ritenuta_acconto_perc" value="<?php if (!empty($documento['documenti_contabilita_ritenuta_acconto_perc'])) : ?><?php echo $documento['documenti_contabilita_ritenuta_acconto_perc']; ?><?php else: ?>0<?php endif; ?>"/> %
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>% sull'imponibile: </label>
                            <input type="text" class="form-control rcr-adjust" name="documenti_contabilita_ritenuta_acconto_perc_imponibile" value="<?php if (!empty($documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'])) : ?><?php echo $documento['documenti_contabilita_ritenuta_acconto_perc_imponibile']; ?><?php else: ?>100<?php endif; ?>"/>
                        </div>
                    </div>
                </div>
            </div>



        </div>

    </div>
    <div class="row">
        <div class="col-md-12">


            <hr/>


            <div class="row">
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
                            <td><input type="text" class="form-control input-sm js_documenti_contabilita_articoli_codice js_autocomplete_prodotto" data-name="documenti_contabilita_articoli_codice" tabindex="1"/>
                            </td>
                            <td>
                                <input type="text" class="form-control input-sm js_documenti_contabilita_articoli_name js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_name" tabindex="2"/>
                                <small>Descrizione aggiuntiva:</small>
                                <textarea class="form-control input-sm js_documenti_contabilita_articoli_descrizione" data-name="documenti_contabilita_articoli_descrizione" tabindex="8"
                                          style="width:100%;" row="2"></textarea>
                            </td>

                            <td><input type="text" class="form-control input-sm js_documenti_contabilita_articoli_quantita" data-name="documenti_contabilita_articoli_quantita" value="1" tabindex="3"/></td>
                            <td><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_prezzo" data-name="documenti_contabilita_articoli_prezzo" value="0.00" tabindex="4"/></td>
                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto" data-name="documenti_contabilita_articoli_sconto" value="0" tabindex="5"/>
                            </td>
                            <td>

                                <select class="form-control input-sm text-right js_documenti_contabilita_articoli_iva_id" data-name="documenti_contabilita_articoli_iva_id">
                                    <?php foreach($elenco_iva as $iva): ?>
                                        <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo $iva['iva_valore']; ?>"><?php echo $iva['iva_label']; ?></option>
                                    <?php endforeach; ?>
                                </select>


                                <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_iva" data-name="documenti_contabilita_articoli_iva" value="0" />

                                <input type="hidden" class="js_documenti_contabilita_articoli_prodotto_id" data-name="documenti_contabilita_articoli_prodotto_id"/>
                            </td>

                            <td>
                                <input type="text" class="form-control input-sm text-right js-importo" data-name="documenti_contabilita_articoli_importo_totale" value="0" tabindex="7"/>
                                <input type="checkbox" class="_form-control js-applica_ritenute" data-name="documenti_contabilita_articoli_applica_ritenute" value="<?php echo DB_BOOL_TRUE; ?>" checked="checked" />
                                <small>Appl. ritenute</small>
                            </td>

                            <td class="text-center">
                                <button type="button"
                                        class="btn  btn-danger btn-xs js_remove_product">
                                    <span class="fa fa-remove"></span>
                                </button>
                            </td>
                        </tr>
                        <?php if (isset($documento['articoli']) && $documento['articoli']): ?>
                            <?php foreach ($documento['articoli'] as $k => $prodotto): ?>

                                <?php //debug($prodotto); ?>

                                <!-- DA RIVEDEER POTREBBERO MANCARE DEI CAMPI QUANDO SI FARA L EDIT -->
                                <tr>
                                    <td><input type="text" class="form-control input-sm" tabindex="1"
                                               name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_codice]"
                                               value="<?php echo $prodotto['documenti_contabilita_articoli_codice']; ?>"/>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-sm" tabindex="2"
                                               name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_name]"
                                               value="<?php echo $prodotto['documenti_contabilita_articoli_name']; ?>"/>
                                        <small>Descrizione aggiuntiva:</small>
                                        <textarea class="form-control input-sm js_documenti_contabilita_articoli_descrizione" name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_descrizione]" tabindex="8"
                                                  style="width:100%;" row="2"><?php echo $prodotto['documenti_contabilita_articoli_descrizione']; ?></textarea>
                                    </td>
                                    <td><input type="text" class="form-control input-sm js_documenti_contabilita_articoli_quantita" tabindex="3"
                                               name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_quantita]"
                                               value="<?php echo $prodotto['documenti_contabilita_articoli_quantita']; ?>"
                                               placeholder="1"/></td>
                                    <td><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_prezzo" tabindex="4"
                                               name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_prezzo]"
                                               value="<?php echo $prodotto['documenti_contabilita_articoli_prezzo']; ?>"
                                               placeholder="0.00"/></td>
                                    <td><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto" tabindex="5"
                                               name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_sconto]"
                                               value="<?php echo $prodotto['documenti_contabilita_articoli_sconto']; ?>"
                                               placeholder="0"/></td>
                                    <td>
                                        <select class="form-control input-sm text-right js_documenti_contabilita_articoli_iva_id" name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_iva_id]">
                                            <?php foreach($elenco_iva as $iva): ?>
                                                <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo $iva['iva_valore']; ?>"<?php if ($iva['iva_id'] == $prodotto['documenti_contabilita_articoli_iva_id']) : ?> selected="selected"<?php endif; ?>><?php echo $iva['iva_label']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_iva" name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_iva]" value="0" />
                                        <input type="hidden" class="js_documenti_contabilita_articoli_prodotto_id" name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_prodotto_id]" value="<?php echo $prodotto['documenti_contabilita_articoli_prodotto_id']; ?>"/>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-sm text-right js-importo" name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_importo_totale]" placeholder="0" tabindex="7"/>
                                        <input type="checkbox" class="_form-control js-applica_ritenute" name="products[<?php echo $k+1; ?>][documenti_contabilita_articoli_applica_ritenute]" value="<?php echo DB_BOOL_TRUE; ?>"<?php if ($prodotto['documenti_contabilita_articoli_applica_ritenute'] == DB_BOOL_TRUE) : ?> checked="checked"<?php endif; ?> />
                                        <small>Appl. ritenute</small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-xs js_remove_product">
                                            <span class="fa fa-remove"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>
                                <button id="js_add_product" type="button" class="btn btn-primary btn-sm">+ Aggiungi
                                    prodotto
                                </button>
                            </td>
                            <td colspan="3"></td>
                            <td class="totali" colspan="3" style="background: #faf6ea">

                                <label>Competenze: <span class="js_competenze">€ 0</span></label>

                                <label class="js_rivalsa"></label>
                                <label class="js_competenze_rivalsa"></label>

                                <label class="js_cassa_professionisti"></label>
                                <label class="js_imponibile"></label>

                                <label class="js_ritenuta_acconto"></label>

                                <label class="js_tot_iva">IVA: <span class="___js_tot_iva">€ 0</span></label>

                                <label class="js_split_payment"></label>

                                <label>Totale da saldare: <span class="js_tot_da_saldare">€ 0</span></label>

                            </td>
                        </tr>
                        </tfoot>
                    </table>


                </div>
            </div>

            <hr/>
            <div class="row margin-bottom-5 col-md-12">
                <div class="form-group">
                    <label>
                        <input type="checkbox" class="minimal js_fattura_accompagnatoria_checkbox" name="documenti_contabilita_fattura_accompagnatoria" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_fattura_accompagnatoria']) && $documento['documenti_contabilita_fattura_accompagnatoria'] == DB_BOOL_TRUE) : ?> checked="checked"<?php endif; ?>>
                        Fattura accompagnatoria
                    </label>
                </div>
            </div>
            <div class="row js_fattura_accompagnatoria_row hide">
                <div class="col-md-1">
                    <div class="form-group">
                        <label>N. Colli: </label>
                        <input type="text" class="form-control" placeholder="1" name="documenti_contabilita_n_colli" value="<?php echo (!empty($documento['documenti_contabilita_n_colli']))?$documento['documenti_contabilita_n_colli']:''; ?>" />
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>Peso: </label>
                        <input type="text" class="form-control" placeholder="0 kg" name="documenti_contabilita_peso" value="<?php echo (!empty($documento['documenti_contabilita_peso']))?$documento['documenti_contabilita_peso']:''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Luogo di destinazione: </label>
                        <input type="text" class="form-control" placeholder="Luogo di destinazione" name="documenti_contabilita_luogo_destinazione" value="<?php echo (!empty($documento['documenti_contabilita_luogo_destinazione']))?$documento['documenti_contabilita_luogo_destinazione']:''; ?>" />
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Trasporto a cura di: </label>
                        <input type="text" class="form-control" placeholder="Azienda di trasporti" name="documenti_contabilita_trasporto_a_cura_di" value="<?php echo (!empty($documento['documenti_contabilita_trasporto_a_cura_di']))?$documento['documenti_contabilita_trasporto_a_cura_di']:''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Causale di trasporto: </label>
                        <textarea class="form-control" placeholder="Causale trasporto" rows="3" name="documenti_contabilita_causale_trasporto" ><?php echo (!empty($documento['documenti_contabilita_causale_trasporto']))?$documento['documenti_contabilita_causale_trasporto']:''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Annotazioni: </label>
                        <textarea class="form-control" placeholder="Annotazioni" rows="3" name="documenti_contabilita_annotazioni_trasporto" ><?php echo (!empty($documento['documenti_contabilita_annotazioni_trasporto']))?$documento['documenti_contabilita_annotazioni_trasporto']:''; ?></textarea>
                    </div>
                </div>
            </div>

            <hr/>

            <div class="row">
                <div class="col-md-5">
                    <textarea name="documenti_contabilita_note" rows="10" class="form-control"
                              placeholder="Inserisci delle note [opzionali]"><?php if ($documento_id) : ?><?php echo $documento['documenti_contabilita_note_interne']; ?><?php endif; ?></textarea>
                </div>
                <div class="col-md-7 scadenze_box" style="background-color:#eeeeee;">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Scadenza pagamento</h4>
                        </div>
                    </div>

                    <div class="row js_rows_scadenze">
                        <?php if ($documento_id) : ?>
                            <?php foreach ($documento['scadenze'] as $key => $scadenza) : ?>
                                <div class="row row_scadenza">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Ammontare</label> <input type="text"
                                                                            name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_ammontare]"
                                                                            class="form-control documenti_contabilita_scadenze_ammontare"
                                                                            placeholder="Ammontare" value="<?php echo $scadenza['documenti_contabilita_scadenze_ammontare']; ?>"
                                                                            data-name="documenti_contabilita_scadenze_ammontare"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Scadenza</label>
                                            <div class="input-group js_form_datepicker date ">
                                                <input type="text"
                                                       name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_scadenza]"
                                                       class="form-control"
                                                       placeholder="Scadenza" value="<?php echo date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_scadenza'])); ?>"
                                                       data-name="documenti_contabilita_scadenze_scadenza"/>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-default" type="button" style="display:none"><i
                                                                class="fa fa-calendar"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Saldato con</label>
                                            <select
                                                    name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_saldato_con]"
                                                    class="select2 js_table_select2 js_table_select2<?php echo $key; ?>"
                                                    data-name="documenti_contabilita_scadenze_saldato_con"

                                            >
                                                <option value="">Non ancora saldato</option>
                                                <option value="Contanti" <?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Contanti'): ?> selectefd="selected"<?php endif; ?>>Contanti</option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Bonifico bancario'): ?> selectefd="selected"<?php endif; ?>>Bonifico bancario</option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Assegno'): ?> selectefd="selected"<?php endif; ?>>Assegno</option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'RiBA'): ?> selectefd="selected"<?php endif; ?>>RiBA</option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Sepa RID'): ?> selectefd="selected"<?php endif; ?>>Sepa RID</option>
                                            </select>

                                            <script>$('.js_table_select2<?php echo $key; ?>').val('<?php echo $scadenza['documenti_contabilita_scadenze_saldato_con']; ?>').trigger('change.select2');</script>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Data saldo</label>
                                            <div class="input-group js_form_datepicker date  field_68">
                                                <input type="text" class="form-control documenti_contabilita_scadenze_data_saldo"
                                                       name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_data_saldo]"
                                                       data-name="documenti_contabilita_scadenze_data_saldo"
                                                       value="<?php echo ($scadenza['documenti_contabilita_scadenze_data_saldo'])?date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_data_saldo'])):''; ?>"
                                                >

                                                <span class="input-group-btn">
                                                    <button class="btn btn-default" type="button" style="display:none;"><i
                                                                class="fa fa-calendar"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="row row_scadenza">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ammontare</label> <input type="text"
                                                                    name="scadenze[0][documenti_contabilita_scadenze_ammontare]"
                                                                    class="form-control documenti_contabilita_scadenze_ammontare"
                                                                    placeholder="Ammontare" value=""
                                                                    data-name="documenti_contabilita_scadenze_ammontare"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text"
                                               name="scadenze[0][documenti_contabilita_scadenze_scadenza]"
                                               class="form-control"
                                               placeholder="Scadenza" value="<?php echo date('d/m/Y'); ?>"
                                               data-name="documenti_contabilita_scadenze_scadenza"/>
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i
                                                        class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Saldato con</label>
                                    <select
                                            name="scadenze[0][documenti_contabilita_scadenze_saldato_con]"
                                            class="select2 js_table_select2"
                                            data-name="documenti_contabilita_scadenze_saldato_con">
                                        <option value="">Non ancora saldato</option>
                                        <option>Contanti</option>
                                        <option>Bonifico bancario</option>
                                        <option>Assegno</option>
                                        <option>RiBA</option>
                                        <option>Sepa RID</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Data saldo</label>
                                    <div class="input-group js_form_datepicker date  field_68">
                                        <input type="text" class="form-control"
                                               name="scadenze[0][documenti_contabilita_scadenze_data_saldo]"
                                               data-name="documenti_contabilita_scadenze_data_saldo">

                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i
                                                        class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <?php /*
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button style="display:none;" id="js_add_scadenza" class="btn btn-primary btn-sm">+ Aggiungi scadenza</button>
                        </div>
                    </div> */ ?>
                </div>
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
                <a href="<?php echo base_url('fatture'); ?>" class="btn default">Annulla</a>
                <button type="submit" class="btn btn-primary">Salva
                </button>
            </div>
        </div>

    </div>
    </div>
</form>




<script>

    $( ".js_fattura_accompagnatoria_checkbox" ).change(function() {

        if ($(this).is(':checked')) {
            $( ".js_fattura_accompagnatoria_row" ).removeClass('hide');
        } else {
            $( ".js_fattura_accompagnatoria_row" ).addClass('hide');
        }


//        if (!$( ".js_fattura_accompagnatoria_row" ).hasClass('hide')) {
//            $( ".js_fattura_accompagnatoria_row" ).addClass('hide');
//        } else {
//            $( ".js_fattura_accompagnatoria_row" ).removeClass('hide');
//        }
    });

    $( ".js_fattura_accompagnatoria_checkbox" ).trigger('change');

</script>

<script>

    /****************** AUTOCOMPLETE Destinatario *************************/
    function initAutocomplete(autocomplete_selector) {

        autocomplete_selector.autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "custom/documents/autocomplete/fw_products",
                    dataType: "json",
                    data: {
                        search: request.term
                    },
                    /*search: function( event, ui ) {
                        loading(true);
                    },*/
                    success: function (data) {
                        var collection = [];
                        loading(false);

//                        console.log(autocomplete_selector.data("id"));
//                        if (data.count_total == 1) {
//                            popolaProdotto(data.results.data[0], autocomplete_selector.data("id"));
//                        } else {

                        $.each(data.results.data, function (i, p) {
                            //console.log(p);
                            collection.push({"id": p.fw_products_id, "label": p.fw_products_sku+': '+p.fw_products_name, "value": p});
                        });
//                        }

                        //console.log(collection);
                        response(collection);
                    }
                });
            },
            minLength: 3,
            select: function (event, ui) {
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

        $("input[name='products["+rowid+"][documenti_contabilita_articoli_codice]']").val(prodotto['fw_products_sku']);
        $("input[name='products["+rowid+"][documenti_contabilita_articoli_name]']").val(prodotto['fw_products_name']);
        $("input[name='products["+rowid+"][documenti_contabilita_articoli_descrizione]']").val(prodotto['fw_products_description']);
        $("input[name='products["+rowid+"][documenti_contabilita_articoli_prezzo]']").val(prodotto['fw_products_sell_price']);
        $("input[name='products["+rowid+"][documenti_contabilita_articoli_sconto]']").val(prodotto['fw_products_discount_percentage']);
        if (isNaN(parseInt(prodotto['fw_products_tax_value']))) {
            $("input[name='products["+rowid+"][documenti_contabilita_articoli_iva]']").val('0');
        } else {
            $("input[name='products["+rowid+"][documenti_contabilita_articoli_iva]']").val(parseInt(prodotto['fw_products_tax_value']));
        }

        $("input[name='products["+rowid+"][documenti_contabilita_articoli_prodotti_id]']").val(prodotto['fw_products_id']);

        $("input[name='products["+rowid+"][documenti_contabilita_articoli_quantita]']").val(1);

        calculateTotals();
    }


    $(document).ready(function () {

        <?php if ($documento_id) : ?>
        calculateTotals(<?php echo $documento_id; ?>);
        <?php endif; ?>

        /****************** AUTOCOMPLETE Destinatario *************************/
        $("#search_cliente").autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: 'post',
                    url: base_url + "custom/documents/autocomplete/"+$('[name="dest_entity_name"]').val(),
                    dataType: "json",
                    data: {
                        search: request.term
                    },
                    minLength: 0,
                    /*search: function( event, ui ) {
                        loading(true);
                    },*/
                    success: function (data) {
                        var collection = [];
                        loading(false);

//                        if (data.count_total == 1) {
//
//                            popolaCliente(data.results.data[0]);
//                        } else {

                        $.each(data.results.data, function (i, p) {
                            //console.log(p);
                            if ($('[name="dest_entity_name"]').val() == 'clienti') {
                                collection.push({"id": p.clienti_id, "label": p.clienti_ragione_sociale, "value": p});
                            } else {
                                collection.push({"id": p.clienti_id, "label": p.fw_providers_name, "value": p});
                            }
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
            select: function (event, ui) {
                // fix per disabilitare la ricerca con il tab
                if (event.keyCode === 9)
                    return false;

                console.log(ui.item.value);
                if ($('[name="dest_entity_name"]').val() == 'clienti') {
                    popolaCliente(ui.item.value);
                } else {
                    popolaFornitore(ui.item.value);
                }

                //drawProdotto(ui.item.value, true);
                return false;
            }
        });



        function popolaCliente(cliente) {
            //Cambio la label
            $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

            $('.js_dest_ragione_sociale').val(cliente['clienti_ragione_sociale']);
            $('.js_dest_indirizzo').val(cliente['clienti_indirizzo']);
            $('.js_dest_citta').val(cliente['clienti_citta']);
            $('.js_dest_nazione').val(cliente['clienti_nazione']);
            $('.js_dest_cap').val(cliente['clienti_cap']);
            $('.js_dest_provincia').val(cliente['clienti_provincia']);
            $('.js_dest_partita_iva').val(cliente['clienti_partita_iva']);
            $('.js_dest_codice_fiscale').val(cliente['clienti_codice_fiscale']);
            $('#js_dest_id').val(cliente['clienti_id']);
        }
        function popolaFornitore(fornitore) {
            //Cambio la label
            $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

            $('.js_dest_ragione_sociale').val(fornitore['fw_providers_name']);
            $('.js_dest_indirizzo').val(fornitore['fw_providers_address']);
            $('.js_dest_citta').val(fornitore['fw_providers_city']);
            $('.js_dest_nazione').val(fornitore['fw_providers_city']);
            $('.js_dest_cap').val(fornitore['fw_providers_cap']);
            $('.js_dest_provincia').val(fornitore['fw_providers_province']);
            $('.js_dest_partita_iva').val(fornitore['fw_providers_vat_number']);
            $('.js_dest_codice_fiscale').val(fornitore['fw_providers_phone']);
            $('#js_dest_id').val(fornitore['fw_providers_id']);
        }


        initAutocomplete($('.js_autocomplete_prodotto'));




        $('.js_select2').each(function () {
            var select = $(this);
            var placeholder = select.attr('data-placeholder');
            select.select2({
                placeholder: placeholder ? placeholder : '',
                allowClear: true
            });
        });
    });
</script>


<script>

    $('.js_btn_tipo').click(function (e) {
        var tipo = $(this).data('tipo');
        //Cambio eventuali label
        console.log(tipo);
        switch (tipo) {
            case 1:
            case 2:
            case 6:
            case 3:
            case 4:
            case 7:
                $('.js_dest_type').html('cliente');
                $('.scadenze_box').show();
                $('[name="dest_entity_name"]').val('clienti');
                break;
            case 5:
                //Nascondo blocco scadenze
                $('.js_dest_type').html('fornitore');
                $('.scadenze_box').hide();
                $('[name="dest_entity_name"]').val('fw_providers');
                break;
            default:
                break;
        }

        $('.js_btn_tipo').removeClass('btn-primary');
        $('.js_btn_tipo').addClass('btn-default');
        $(this).addClass('btn-primary');
        $(this).removeClass('btn-default');
        $('.js_documenti_contabilita_tipo').val(tipo);

    });
    function getNumeroAjax(tipo, serie) {
        $.ajax({
            method: 'get',
            url: base_url + "custom/documents/numeroSucessivo/"+$('.js_documenti_contabilita_tipo').val()+'/'+$('.js_documenti_contabilita_serie').val(),
            success: function (numero) {
                $('[name="documenti_contabilita_numero"]').val(numero);
            }
        });
    }
    function getNumeroDocumento() {
        var is_modifica = !isNaN($('[name="documento_id"]').val());
        var tipo = $('.js_btn_tipo.btn-primary').data('tipo');
        var serie = $('.js_btn_serie.button_selected').data('serie');
        if (is_modifica) {
            if (tipo == '<?php echo (empty($documento['documenti_contabilita_tipo']))?'XXX':$documento['documenti_contabilita_tipo']; ?>' && serie == '<?php echo (empty($documento['documenti_contabilita_serie']))?'XXX':$documento['documenti_contabilita_serie']; ?>') {
                $('[name="documenti_contabilita_numero"]').val(<?php echo (!empty($documento['documenti_contabilita_numero']))?$documento['documenti_contabilita_numero']:''; ?>);
            } else {
                getNumeroAjax(tipo, serie);
            }
        } else {
            getNumeroAjax(tipo, serie);
        }
    }

    $('.js_btn_serie').click(function (e) {
        if ($(this).hasClass('button_selected')) {
            $('.js_btn_serie').removeClass('button_selected');
            $('.js_documenti_contabilita_serie').val('');
        } else {
            $('.js_btn_serie').removeClass('button_selected');
            $(this).addClass('button_selected');

            $('.js_documenti_contabilita_serie').val($(this).data('serie'));
        }
        getNumeroDocumento();
    });
    $('.js_btn_tipo').click(function (e) {
        //$('.js_btn_serie').first().trigger('click');
        getNumeroDocumento();

    });

    <?php if (empty($documento['documenti_contabilita_numero'])) : ?>
    $('.js_btn_tipo').first().trigger('click');
    //$('.js_btn_serie').first().trigger('click');
    <?php endif ;?>


    var totale = 0;
    var totale_iva = 0;
    var competenze = 0;
    var competenze_no_ritenute = 0;
    var iva_perc_max = 0;
    var rivalsa_inps_percentuale = 0;
    var rivalsa_inps_valore = 0;

    var competenze_con_rivalsa = 0;

    var cassa_professionisti_perc = 0;
    var cassa_professionisti_valore = 0;

    var imponibile = 0;

    var ritenuta_acconto_perc = 0;
    var ritenuta_acconto_perc_sull_imponibile = 0;

    function reverseRowCalculate(tr) {
        //Calcolo gli importi basandomi sul totale...
        var qty = parseFloat($('.js_documenti_contabilita_articoli_quantita', tr).val());
        var sconto = parseFloat($('.js_documenti_contabilita_articoli_sconto', tr).val());
        var iva = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', tr).data('perc'));

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
        var importo = parseFloat(importo_ivato / ((100+iva)/100));
        var importo_ricalcolato = parseFloat(importo_ivato - ((importo_ivato / 100) * sconto));


        //console.log(importo);

        $('.js-importo', tr).val(importo_ricalcolato.toFixed(2));
        $('.js_documenti_contabilita_articoli_prezzo', tr).val(importo.toFixed(2));
//        
        calculateTotals();
    }
    function calculateTotals(documento_id) {
        totale = 0;
        totale_iva = 0;
        totale_iva_divisa = {};
        totale_imponibile_divisa = {};
        competenze = 0;
        competenze_no_ritenute = 0;

        $('#js_product_table tbody tr:not(.hidden)').each(function () {
            var qty = parseFloat($('.js_documenti_contabilita_articoli_quantita', $(this)).val());
            var prezzo = parseFloat($('.js_documenti_contabilita_articoli_prezzo', $(this)).val());
            var sconto = parseFloat($('.js_documenti_contabilita_articoli_sconto', $(this)).val());
            var iva = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', $(this)).data('perc'));
            var appl_ritenute = $('.js-applica_ritenute', $(this)).is(':checked');

            console.log(appl_ritenute);

            iva_perc_max = Math.max(iva_perc_max, iva);

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
            var totale_riga = prezzo*qty;
            var totale_riga_scontato = (totale_riga / 100) * (100-sconto);
            var totale_riga_scontato_ivato = parseFloat((totale_riga_scontato / 100) * (100+iva));
            competenze += totale_riga_scontato;

            if (!appl_ritenute) {
                competenze_no_ritenute += totale_riga_scontato;
            }

            if (isNaN(totale_iva_divisa[iva])) {
                totale_iva_divisa[iva] = parseFloat((totale_riga_scontato / 100) * iva);
                totale_imponibile_divisa[iva] = totale_riga_scontato;
            } else {
                totale_iva_divisa[iva] += parseFloat((totale_riga_scontato / 100) * iva);
                totale_imponibile_divisa[iva] += totale_riga_scontato;
            }

            totale_iva += parseFloat((totale_riga_scontato / 100) * iva);
            totale += totale_riga_scontato_ivato;

            $('.js-importo', $(this)).val(totale_riga_scontato_ivato.toFixed(2));
            $('.js_documenti_contabilita_articoli_iva', $(this)).val(parseFloat((totale_riga_scontato / 100) * iva).toFixed(2));

        });

        rivalsa_inps_percentuale = parseFloat($('[name="documenti_contabilita_rivalsa_inps_perc"]').val());
        rivalsa_inps_valore = parseFloat(((competenze-competenze_no_ritenute)/100)*rivalsa_inps_percentuale);

        competenze_con_rivalsa = competenze+rivalsa_inps_valore;

        cassa_professionisti_perc = parseFloat($('[name="documenti_contabilita_cassa_professionisti_perc"]').val());
        cassa_professionisti_valore = parseFloat(((competenze_con_rivalsa-competenze_no_ritenute)/100)*cassa_professionisti_perc);

        imponibile = competenze_con_rivalsa+cassa_professionisti_valore;

        var applica_split_payment = $('[name="documenti_contabilita_split_payment"]').is(':checked');

        var totale_imponibili_iva_diverse_da_max = 0;
        var totale_iva_diverse_da_max = 0;
        for (var _iva in totale_iva_divisa) {
            if (_iva != iva_perc_max) {
                if (_iva != 0) {
                    totale_imponibili_iva_diverse_da_max += parseFloat((totale_iva_divisa[_iva]/_iva)*100);
                } else {
                    totale_imponibili_iva_diverse_da_max += totale_imponibile_divisa[_iva];//L'errore è qua. Devo aggiungere tutto l'imponibile in quanto l'iva è 0. Però nn ce l'ho in nessun array 

                }
                totale_iva_diverse_da_max += parseFloat(totale_iva_divisa[_iva]);
            }
        }
        //Aggiungo alla iva massima, ciò che manca tenendo conto delle modifiche ai totali dovute a rivalsa e cassa
//        console.log(imponibile);
//        console.log(totale_imponibili_iva_diverse_da_max);
//        console.log(iva_perc_max);
        totale_iva_divisa[iva_perc_max] = parseFloat(((imponibile-totale_imponibili_iva_diverse_da_max)/100)*iva_perc_max);

        //Valuto le ritenute
        ritenuta_acconto_perc = parseFloat($('[name="documenti_contabilita_ritenuta_acconto_perc"]').val());
        ritenuta_acconto_perc_sull_imponibile = parseFloat($('[name="documenti_contabilita_ritenuta_acconto_perc_imponibile"]').val());
        ritenuta_acconto_valore_sull_imponibile = ((competenze_con_rivalsa-competenze_no_ritenute)/100)*ritenuta_acconto_perc_sull_imponibile;
        totale_ritenuta = (ritenuta_acconto_valore_sull_imponibile/100)*ritenuta_acconto_perc;

        //console.log(totale_iva_divisa);
        totale = imponibile+totale_iva_diverse_da_max+totale_iva_divisa[iva_perc_max]-totale_ritenuta;


        $('[name="documenti_contabilita_rivalsa_inps_valore"]').val(rivalsa_inps_valore);
        $('[name="documenti_contabilita_competenze_lordo_rivalsa"]').val(competenze_con_rivalsa);
        if (rivalsa_inps_percentuale && rivalsa_inps_valore > 0) {
            $('.js_rivalsa').html('Rivalsa INPS '+rivalsa_inps_percentuale+'% <span>€ '+rivalsa_inps_valore.toFixed(2)+'</span>').show();
            $('.js_competenze_rivalsa').html('Competenze (al lordo della rivalsa)<span>€ '+competenze_con_rivalsa.toFixed(2)+'</span>').show();
        } else {
            $('.js_rivalsa').hide();
            $('.js_competenze_rivalsa').hide();
        }

        $('[name="documenti_contabilita_cassa_professionisti_valore"]').val(cassa_professionisti_valore);
        $('[name="documenti_contabilita_imponibile"]').val(imponibile.toFixed(2));

        if (cassa_professionisti_perc && cassa_professionisti_valore > 0) {
            $('.js_cassa_professionisti').html('Cassa professionisti '+cassa_professionisti_perc+'% <span>€ '+cassa_professionisti_valore.toFixed(2)+'</span>').show();
            $('.js_imponibile').html('Imponibile <span>€ '+imponibile+'</span>').show();
        } else {
            $('.js_cassa_professionisti').hide();
            $('.js_imponibile').hide();
        }


        $('[name="documenti_contabilita_ritenuta_acconto_valore"]').val(totale_ritenuta);
        $('[name="documenti_contabilita_ritenuta_acconto_imponibile_valore"]').val(ritenuta_acconto_valore_sull_imponibile);
        if (ritenuta_acconto_perc > 0 && ritenuta_acconto_perc_sull_imponibile > 0 && totale_ritenuta > 0) {
            $('.js_ritenuta_acconto').html('Ritenuta d\'acconto -'+ritenuta_acconto_perc+'% di &euro; '+ritenuta_acconto_valore_sull_imponibile.toFixed(2)+'<span>€ '+totale_ritenuta.toFixed(2)+'</span>').show();
        } else {
            $('.js_ritenuta_acconto').hide();
        }

        $('[name="documenti_contabilita_competenze"]').val(competenze);
        $('.js_competenze').html('€ '+competenze.toFixed(2));

        $(".js_tot_iva:not(:first)").remove();
        $(".js_tot_iva:first").hide();


        $('[name="documenti_contabilita_iva_json"]').val(JSON.stringify(totale_iva_divisa));

        for (var i in totale_iva_divisa) {

            //console.log(totale_iva_divisa);

            $(".js_tot_iva:last").clone().insertAfter(".js_tot_iva:last").show();
            $('.js_tot_iva:last').html(`IVA (`+i+`%): <span>€ `+totale_iva_divisa[i].toFixed(2)+`</span>`);//'€ '+totale_iva.toFixed(2));
        }

        if (applica_split_payment) {
            $('.js_split_payment').html('Iva non dovuta (split payment) <span>€ -'+(totale_iva_diverse_da_max+totale_iva_divisa[iva_perc_max]).toFixed(2)+'</span>').show();
            totale-=(totale_iva_diverse_da_max+totale_iva_divisa[iva_perc_max]);
        } else {
            $('.js_split_payment').hide();
        }

        $('.js_tot_da_saldare').html('€ '+totale.toFixed(2));

        $('[name="documenti_contabilita_totale"]').val(totale.toFixed(2));
        $('[name="documenti_contabilita_iva"]').val(totale_iva.toFixed(2));

        if (isNaN(documento_id)) {
            $('.documenti_contabilita_scadenze_ammontare').val(totale.toFixed(2));
            $('.documenti_contabilita_scadenze_ammontare:first').trigger('change');
        } else {
            $('.documenti_contabilita_scadenze_ammontare:last').closest('.row_scadenza').remove();
//            $('.documenti_contabilita_scadenze_ammontare:last').trigger('change');
        }

    }

    function increment_scadenza() {
        var counter_scad = $('.row_scadenza').length;
        var rows_scadenze = $('.js_rows_scadenze');
        // Fix per clonare select inizializzata
        $('.js_table_select2').filter(':first').select2('destroy');

        var newScadRow = $('.row_scadenza').filter(':first').clone();
        $('.documenti_contabilita_scadenze_data_saldo',newScadRow).val('');
        // Fix per clonare select inizializzata
        $('.js_table_select2').filter(':first').select2();

        /* Line manipulation begin */
        //newScadRow.removeClass('hidden');
        $('input, select, textarea', newScadRow).each(function () {
            var control = $(this);
            var name = control.attr('data-name');
            control.attr('name', 'scadenze[' + counter_scad + '][' + name + ']').removeAttr('data-name');
        });

        $('.js_table_select2', newScadRow).select2({
            //placeholder: "Seleziona prodotto",
            allowClear: true
        });

        $('.js_form_datepicker input', newScadRow).datepicker({todayBtn: 'linked', format: 'dd/mm/yyyy', todayHighlight: true, weekStart: 1, language: 'it'});

        /* Line manipulation end */
        counter_scad++;
        newScadRow.appendTo(rows_scadenze);
    }

    $(document).ready(function () {
        var table = $('#js_product_table');
        var body = $('tbody', table);
        var rows = $('tr', body);
        var increment = $('#js_add_product', table);

        var rows_scadenze = $('.js_rows_scadenze');
        //var increment_scadenza = $('#js_add_scadenza');


        var firstRow = rows.filter(':first');
        var counter = rows.length;

        $('#new_fattura').on('change', '[name="documenti_contabilita_split_payment"], [name="documenti_contabilita_rivalsa_inps_perc"],[name="documenti_contabilita_cassa_professionisti_perc"],[name="documenti_contabilita_ritenuta_acconto_perc"],[name="documenti_contabilita_ritenuta_acconto_perc_imponibile"]', function () {
            calculateTotals();
        });

        table.on('change', '.js-applica_ritenute, .js_documenti_contabilita_articoli_quantita, .js_documenti_contabilita_articoli_prezzo, .js_documenti_contabilita_articoli_sconto, .js_documenti_contabilita_articoli_iva_id',
            function () {
                calculateTotals();
            });

        table.on('change', '.js-importo', function () {

            reverseRowCalculate($(this).closest('tr'));
        });

        // Aggiungi prodotto
        increment.on('click', function () {
            var newRow = firstRow.clone();

            /* Line manipulation begin */
            newRow.removeClass('hidden');
            $('input, select, textarea', newRow).each(function () {
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


        table.on('click', '.js_remove_product', function () {
            $(this).parents('tr').remove();
            calculateTotals();
        });
        $('#offerproducttable .js_remove_product').on('click', function () {
            $(this).parents('tr').remove();
        });



        //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
        rows_scadenze.on('change', '.documenti_contabilita_scadenze_ammontare', function () {
            //Se la somma degli ammontare è minore del totale procedo
            var totale_scadenze = 0;
            $('.documenti_contabilita_scadenze_ammontare').each(function () {
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
                    $('.documenti_contabilita_scadenze_ammontare', next_row).val((totale-totale_scadenze).toFixed(2));
                }
            } else {
                if (next_row_exists) {
                    console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                    $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                    $(this).trigger('change');
                } else {
                    console.log('Non esiste scadenza successiva. Tutto a posto ma nel dubbio forzo questa = alla differenza tra totale e totale scadenze');
                    $(this).val((totale-(totale_scadenze-$(this).val())).toFixed(2));

                }
            }

        });

        if (rows.length < 2) {
            increment.click();

        }
    });
</script>


<script>
    $(document).ready(function () {
        $('#js_dtable').dataTable({
            aoColumns: [null, null, null, null, null, null, null, {bSortable: false}],
            aaSorting: [[0, 'desc']]
        });
        $('#js_dtable_wrapper .dataTables_filter input').addClass("form-control input-small"); // modify table search input
        $('#js_dtable_wrapper .dataTables_length select').addClass("form-control input-xsmall"); // modify table per page dropdown
    });
</script>
<!-- END Module Related Javascript -->
