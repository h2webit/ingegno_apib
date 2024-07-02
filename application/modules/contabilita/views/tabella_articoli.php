<table id="js_product_table" class="table table-condensed table-striped table_prodotti">
    <thead style="visibility:hidden;">
        <tr>
            <th width='7%'>Codice</th>
            <th>Nome prodotto</th>
            <?php if (!empty($campi_personalizzati[1])) : ?>
            <?php foreach ($campi_personalizzati[1] as $campo) : ?>
            <th>
                <?php //debug($campo); ?>
                <?php echo $campo['fields_draw_label']; ?></th>
            <?php endforeach; ?>
            <?php endif; ?>
            <th width="5%">U.M.</th>
            <th width="6%">Quantit&agrave;</th>
            <th width="7%">Prezzo</th>
            <?php if ($impostazioni['documenti_contabilita_settings_lotto']): ?>
            <th width="5%">Lotto</th>
            <?php endif; ?>
            <?php if ($impostazioni['documenti_contabilita_settings_periodo_comp']): ?>
                <th width="16%">Periodo comp.</th>
            <?php endif; ?>
            <?php if ($impostazioni['documenti_contabilita_settings_commessa']): ?>
                <th width="5%">Commessa</th>
            <?php endif; ?>
            <?php if ($impostazioni['documenti_contabilita_settings_scadenza']): ?>
            <th width="5%">Scad</th>
            <?php endif; ?>
            <th width="5%">Sc. %</th>
            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']) : ?>
            <th width="5%">Sc. 2</th>
            <?php endif; ?>
            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']) : ?>
            <th width="5%">Sc. 3</th>
            <?php endif; ?>
            <?php if (!empty($campo_centro_costo)) : ?>
            <th width="10%">Costo/ricavo</th>
            <?php endif; ?>
            <th width="7%">IVA</th>
            <th width="7%">Importo</th>
            <th width="5%"></th>
        </tr>
    </thead>
    <tbody>
        <tr class="hidden">
            <td colspan="<?php echo $colonne_count; ?>">
                <table class="table-condensed">
                    <thead __style="visibility: hidden">
                        <tr>
                            <th width='7%'>Codice</th>
                            <th>Nome prodotto <span class="js_modal_product_detail"></span></th>
                            <?php if (!empty($campi_personalizzati[1])) : ?>
                            <?php foreach ($campi_personalizzati[1] as $campo) : ?>
                            <th>
                                <?php //debug($campo); ?>
                                <?php echo $campo['fields_draw_label']; ?></th>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <th width="5%">U.M.</th>
                            <th width="6%">Quantit&agrave;</th>
                            <th width="7%">Prezzo</th>
                            <?php if ($impostazioni['documenti_contabilita_settings_lotto']): ?>
                            <th width="5%">Lotto</th>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_periodo_comp']): ?>
                                <th width="16%">Periodo comp.</th>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_commessa']): ?>
                                <th width="5%">Commessa</th>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_scadenza']): ?>
                            <th width="9%">Scadenza</th>
                            <?php endif; ?>
                            <th width="5%">Sc. %</th>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']) : ?>
                            <th width="5%">Sc. 2</th>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']) : ?>
                            <th width="5%">Sc. 3</th>
                            <?php endif; ?>
                            <?php if (!empty($campo_centro_costo)) : ?>
                            <th width="10%">Costo/ricavo</th>
                            <?php endif; ?>
                            <th width="7%">IVA</th>
                            <th width="7%">Importo</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="text" class="form-control input-sm js_documenti_contabilita_articoli_codice js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_codice" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_codice_asin js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_codice_asin" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_codice_ean js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_codice_ean" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_rif_riga_articolo" data-id="1" data-name="documenti_contabilita_articoli_rif_riga_articolo" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_rif_pagamento" data-id="1" data-name="documenti_contabilita_articoli_rif_pagamento" />

                                <input type="hidden" class="js_documenti_contabilita_articoli_attributi_sdi " data-id="1" data-name="documenti_contabilita_articoli_attributi_sdi" />
                                <input type="checkbox" class="_form-control js-riga_desc" data-id='1' data-name="documenti_contabilita_articoli_riga_desc" value="<?php echo DB_BOOL_TRUE; ?>" />

                                <small>Riga descrittiva</small>

                            </td>
                            <td>
                                <input type="text" class="form-control input-sm js_documenti_contabilita_articoli_name js_autocomplete_prodotto" data-id="1" data-name="documenti_contabilita_articoli_name" />
                                <small>Descrizione aggiuntiva:</small>
                                <textarea class="form-control input-sm js_documenti_contabilita_articoli_descrizione" data-name="documenti_contabilita_articoli_descrizione" style="width:100%;" row="2"></textarea>
                            </td>
                            <?php if (!empty($campi_personalizzati[1])) : ?>
                            <?php foreach ($campi_personalizzati[1] as $campo) : ?>
                            <td>
                                <?php echo $campo['html']; ?>
                            </td>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_unita_misura" data-name="documenti_contabilita_articoli_unita_misura" placeholder="(facoltativo)" />
                            </td>
                            <td><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_quantita" data-name="documenti_contabilita_articoli_quantita" value="1" /></td>
                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_prezzo js_decimal" data-name="documenti_contabilita_articoli_prezzo" value="0.00" />
                                <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_imponibile" data-name="documenti_contabilita_articoli_imponibile" value="0" />
                                <small style="text-align:center;display:block;">Imponibile<br />
                                    <span class="js_riga_imponibile">0.00</span>
                                </small>
                            </td>

                            <?php if ($impostazioni['documenti_contabilita_settings_lotto']) : ?>
                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_lotto" data-name="documenti_contabilita_articoli_lotto" value="" />
                            </td>
                            <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_periodo_comp']): ?>
                                <td>
                                    <div class="input-group input-group js_form_daterangepicker input-sm">
                                        <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_periodo_comp"
                                        data-name="documenti_contabilita_articoli_periodo_comp" value="" placeholder="es: 01/01/2024 - 31/03/2024"
                                        pattern="^(\d{2}/\d{2}/\d{4}) - (\d{2}/\d{2}/\d{4})$"
                                        />
                                        <span class="input-group-btn" tabindex="-1" >
                                            <button class="btn btn-default" type="button" tabindex="-1" style="display:none;"><i class="fas fa-calendar-alt"></i></button>
                                        </span>
                                    </div>
                                    
                                </td>
                            <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_commessa']): ?>
                                <td>
                                    <select data-name="documenti_contabilita_articoli_commessa" class="form-control input-sm js_documenti_contabilita_articoli_commessa"<?php if ($this->input->get('doc_type') == 'DDT Fornitore' && $this->input->get('lock_type')) : ?> disabled <?php endif; ?>>
                                        <option value="">---</option>
                                        <?php foreach ($commesse as $commessa): ?>
                                            <?php //debug($prodotto,true);  ?>
                                            <option value="<?php echo $commessa['projects_id']; ?>" <?php if (!empty($prodotto['documenti_contabilita_articoli_commessa']) && $commessa['projects_id'] == $prodotto['documenti_contabilita_articoli_commessa']): ?>
                                                    selected="selected" <?php endif; ?>>
                                                <?php echo $commessa['projects_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                </td>
                            <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_scadenza']): ?>
                            <td>

                                <div class="input-group js_form_datepicker date">
                                    <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_scadenza" data-name="documenti_contabilita_articoli_scadenza" />
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" style="display: none;">
                                            <i class="fa fa-calendar"></i>
                                        </button>
                                    </span>
                                </div>


                            </td>
                            <?php endif; ?>


                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto" data-name="documenti_contabilita_articoli_sconto" value="0" />
                            </td>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']) : ?>
                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto2" data-name="documenti_contabilita_articoli_sconto2" value="0" />
                            </td>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']) : ?>
                            <td>
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto3" data-name="documenti_contabilita_articoli_sconto3" value="0" />
                            </td>
                            <?php endif; ?>
                            <?php if ($campo_centro_costo) : ?>
                            <td>
                                <select class="form-control input-sm  js_documenti_contabilita_articoli_centro_costo" data-name="documenti_contabilita_articoli_centro_costo_ricavo">
                                    <option value="">---</option>
                                    <?php foreach ($centri_di_costo as $centro) : ?>
                                    <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>"><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php //debug($impostazioni); ?>
                                <select class="form-control input-sm js_documenti_contabilita_articoli_iva_id" data-name="documenti_contabilita_articoli_iva_id">
                                    <?php foreach ($elenco_iva as $iva) : ?>
                                    <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo (int) $iva['iva_valore']; ?>" <?php if ($iva['iva_id'] == $impostazioni['documenti_contabilita_settings_iva_default']) : ?> selected="selected" <?php endif; ?>><?php echo $iva['iva_label']; ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_iva" data-name="documenti_contabilita_articoli_iva" value="0" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_prodotto_id" data-name="documenti_contabilita_articoli_prodotto_id" />
                            </td>

                            <td class="js_column_importo">
                                <input type="text" class="form-control input-sm text-right js-importo js_decimal" data-name="documenti_contabilita_articoli_importo_totale" value="0" />

                                <input type="checkbox" class="_form-control js-applica_ritenute" data-name="documenti_contabilita_articoli_applica_ritenute" value="<?php echo DB_BOOL_TRUE; ?>" checked="checked" />
                                <small>Appl. ritenute</small>
                                <br /> <input type="checkbox" class="_form-control js-applica_sconto" data-name="documenti_contabilita_articoli_applica_sconto" value="<?php echo DB_BOOL_TRUE; ?>" checked="checked" />
                                <small>Appl. sconto</small>
                            </td>

                            <td class="text-center js_actions">
                                <button type="button" class="btn btn-warning btn-xs js_xml_attributes" data-attributes="<?php echo base64_encode(json_encode($xml_articoli_altri_dati_gestionale)); ?>">
                                    <span class="fas fa-cogs"></span>
                                </button>
                                <button type="button" class="btn  btn-danger btn-xs js_remove_product">
                                    <span class="fas fa-times"></span>
                                </button>
                            </td>
                        </tr>
                        <?php if (!empty($campi_personalizzati[2])) : ?>
                        <tr>
                            <?php foreach ($campi_personalizzati[2] as $campo) : ?>
                            <td>
                                <?php if ($campo) : ?>
                                <strong><?php echo $campo['fields_draw_label']; ?></strong><br />
                                <?php echo $campo['html']; ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </td>
        </tr>

        <?php if (isset($documento['articoli']) && $documento['articoli']) : ?>
        <?php foreach ($documento['articoli'] as $k => $prodotto) : ?>


        <!-- DA RIVEDEER POTREBBERO MANCARE DEI CAMPI QUANDO SI FARA L EDIT -->
        <tr>

            <td colspan="<?php echo $colonne_count; ?>">

                <table class="table-condensed">
                    <thead __style="visibility: hidden">
                        <tr>
                            <th width='7%'>Codice</th>
                            <th>Nome prodotto <span class="js_modal_product_detail"></span></th>
                            <?php if (!empty($campi_personalizzati[1])) : ?>
                            <?php foreach ($campi_personalizzati[1] as $campo) : ?>
                            <th>
                                <?php if ($campo) : ?>
                                <?php echo $campo['fields_draw_label']; ?>
                                <?php endif; ?>
                            </th>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <th width="5%">U.M.</th>
                            <th width="6%">Quantit&agrave;</th>
                            <th width="7%">Prezzo</th>

                            <?php if ($impostazioni['documenti_contabilita_settings_lotto']): ?>
                            <th width="5%">Lotto</th>
                            <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_periodo_comp']): ?>
                                <th width="16%">Periodo comp.</th>
                            <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_commessa']): ?>
                                <th width="5%">Commessa</th>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_scadenza']): ?>
                            <th width="8%">Scad</th>
                            <?php endif; ?>

                            <th width="5%">Sc. %</th>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']) : ?>
                            <th width="5%">Sc. 2</th>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']) : ?>
                            <th width="5%">Sc. 3</th>
                            <?php endif; ?>
                            <?php if (!empty($campo_centro_costo)) : ?>
                            <th width="10%">Costo/ricavo</th>
                            <?php endif; ?>
                            <th width="7%">IVA</th>
                            <th width="7%">Importo</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td width="100"><input type="text" class="form-control input-sm js_autocomplete_prodotto js_documenti_contabilita_articoli_codice" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_codice]" value="<?php echo $prodotto['documenti_contabilita_articoli_codice']; ?>" />
                                <input type="hidden" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_id]" value="<?php echo isset($prodotto['documenti_contabilita_articoli_id']) ? $prodotto['documenti_contabilita_articoli_id'] : ""; ?>" />
                                <input type="hidden" class="js_autocomplete_prodotto " data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_codice_asin]" value="<?php echo $prodotto['documenti_contabilita_articoli_codice_asin']; ?>" />
                                <input type="hidden" class="js_autocomplete_prodotto" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_codice_ean]" value="<?php echo $prodotto['documenti_contabilita_articoli_codice_ean']; ?>" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_rif_riga_articolo" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_rif_riga_articolo]" value="<?php echo isset($prodotto['documenti_contabilita_articoli_rif_riga_articolo']) ? $prodotto['documenti_contabilita_articoli_rif_riga_articolo'] : ""; ?>" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_rif_pagamento" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_rif_pagamento]" value="<?php echo isset($prodotto['documenti_contabilita_articoli_rif_pagamento']) ? $prodotto['documenti_contabilita_articoli_rif_pagamento'] : ""; ?>" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_attributi_sdi " data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_attributi_sdi]" value="<?php echo isset($prodotto['documenti_contabilita_articoli_attributi_sdi']) ? base64_encode($prodotto['documenti_contabilita_articoli_attributi_sdi']) : ""; ?>" />
                                <br />
                                <input type="checkbox" class="_form-control js-riga_desc" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_riga_desc]" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (isset($prodotto['documenti_contabilita_articoli_riga_desc']) && $prodotto['documenti_contabilita_articoli_riga_desc'] == DB_BOOL_TRUE) : ?> checked="checked" <?php endif; ?> />
                                <small>Riga descrittiva</small>
                            </td>
                            <td>
                                <input type="text" class="form-control input-sm js_autocomplete_prodotto" data-id="<?php echo $k + 1; ?>" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_name]" value="<?php echo str_replace('"', '&quot;', $prodotto['documenti_contabilita_articoli_name']); ?>" />
                                <small>Descrizione aggiuntiva:</small>
                                <textarea class="form-control input-sm js_documenti_contabilita_articoli_descrizione" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_descrizione]" style="width:100%;" row="2"><?php echo $prodotto['documenti_contabilita_articoli_descrizione']; ?></textarea>
                            </td>
                            <?php if (!empty($campi_personalizzati[1])) : ?>
                            <?php foreach ($campi_personalizzati[1] as $campo) : ?>
                            <td>
                                <?php //ricreare il campo html passando il value valorizzato di questo record. Attenzione a non usare il campo, ma direttamente il map_to...
                                                                        $campo['fields_name'] = "products[" . ($k + 1) . "][{$campo['fields_name']}]";

                                                                    $data = [
                                                                        'lang' => '',
                                                                        'field' => $campo,
                                                                        'value' => ($prodotto[$campo['campi_righe_articoli_map_to']]) ?? '',
                                                                        'label' => '', // '<label class="control-label">' . $field['fields_draw_label'] . '</label>',
                                                                        'placeholder' => '',
                                                                        'help' => '',
                                                                        'class' => 'input-sm',
                                                                        'attr' => '',
                                                                        'onclick' => '',
                                                                        'subform' => '',
                                                                    ];

                                                                    $campo['html'] = str_ireplace(
                                                                        'select2_standard',
                                                                        '',
                                                                        sprintf('<div class="form-group">%s</div>', $this->load->view("box/form_fields/{$campo['fields_draw_html_type']}", $data, true))
                                                                    );
                                                                    ?>
                                <?php echo $campo['html']; ?>
                            </td>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <td width="20">
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_unita_misura" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_unita_misura]" placeholder="(facoltativo)" value="<?php echo $prodotto['documenti_contabilita_articoli_unita_misura']; ?>" />
                            </td>
                            <td width="30"><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_quantita" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_quantita]" value="<?php echo $prodotto['documenti_contabilita_articoli_quantita']; ?>" placeholder="1" /></td>
                            <td width="90">
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_prezzo js_decimal" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_prezzo]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_prezzo'], 3, '.', ''); ?>" placeholder="0.00" />
                                <input type="hidden" class="js_documenti_contabilita_articoli_imponibile" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_imponibile]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_imponibile'], 3, '.', ''); ?>" placeholder="0.00" />
                                <small style="text-align:center;display:block;">
                                    Imponibile<br /><span class="js_riga_imponibile"><?php echo number_format($prodotto['documenti_contabilita_articoli_prezzo'] * $prodotto['documenti_contabilita_articoli_quantita'], 2, '.', ''); ?></span>
                                </small>
                            </td>

                            <?php if ($impostazioni['documenti_contabilita_settings_lotto']) : ?>
                            <td width="50">
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_lotto" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_lotto]" value="<?php echo $prodotto['documenti_contabilita_articoli_lotto']; ?>" placeholder="0" />
                            </td>
                            <?php endif; ?>

                                <?php if ($impostazioni['documenti_contabilita_settings_periodo_comp']): ?>
                                    <td width="50">
                                        <div class="input-group input-group js_form_daterangepicker input-sm">
                                        <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_periodo_comp"
                                            name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_periodo_comp]"
                                            value="<?php echo ($prodotto['documenti_contabilita_articoli_periodo_comp']??''); ?>"
                                            placeholder="es.:01/01/2024 - 31/03/2024" pattern="^(\d{2}/\d{2}/\d{4}) - (\d{2}/\d{2}/\d{4})$" />
                                        <span class="input-group-btn" tabindex="-1" >
                                            <button class="btn btn-default" type="button" tabindex="-1" style="display:none;"><i class="fas fa-calendar-alt"></i></button>
                                        </span>
                                    </div>
                                        
                                    </td>
                                <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_commessa']): ?>
                                <td width="50">
                                    <select class="form-control input-sm  js_documenti_contabilita_articoli_commessa" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_commessa]" <?php if ($this->input->get('doc_type') == 'DDT Fornitore' && $this->input->get('lock_type')): ?> disabled <?php endif; ?>>
                                        <option value="">---</option>
                                        <?php foreach ($commesse as $commessa): ?>
                                            <?php //debug($prodotto,true);  ?>
                                            <option value="<?php echo $commessa['projects_id']; ?>" <?php if (!empty($prodotto['documenti_contabilita_articoli_commessa']) && $commessa['projects_id'] == $prodotto['documenti_contabilita_articoli_commessa']): ?>
                                                    selected="selected" <?php endif; ?>>
                                                <?php echo $commessa['projects_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    
                                </td>
                            <?php endif; ?>

                            <?php if ($impostazioni['documenti_contabilita_settings_scadenza']): ?>
                            <td width="80">



                                <div class="input-group js_form_datepicker date">
                                    <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_scadenza" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_scadenza]" value="<?php echo (!empty($prodotto['documenti_contabilita_articoli_scadenza'])) ? date('d/m/Y', strtotime($prodotto['documenti_contabilita_articoli_scadenza'])) : ''; ?>" />
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" style="display: none;">
                                            <i class="fa fa-calendar"></i>
                                        </button>
                                    </span>
                                </div>


                            </td>
                            <?php endif; ?>


                            <td width="70"><input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_sconto]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_sconto'], 2, '.', ''); ?>" placeholder="0" /></td>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto2']) : ?>
                            <td width="50">
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto2" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_sconto2]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_sconto2'], 2, '.', ''); ?>" placeholder="0" />
                            </td>
                            <?php endif; ?>
                            <?php if ($impostazioni['documenti_contabilita_settings_sconto3']) : ?>
                            <td width="50">
                                <input type="text" class="form-control input-sm text-right js_documenti_contabilita_articoli_sconto3" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_sconto3]" value="<?php echo number_format((float) $prodotto['documenti_contabilita_articoli_sconto3'], 2, '.', ''); ?>" placeholder="0" />
                            </td>
                            <?php endif; ?>


                            <?php if ($campo_centro_costo) : ?>
                            <td width="50">
                                <select class="form-control input-sm  js_documenti_contabilita_articoli_centro_costo_ricavo" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_centro_costo_ricavo]">
                                    <option value="">---</option>
                                    <?php foreach ($centri_di_costo as $centro) : ?>
                                    <?php //debug($prodotto,true); ?>
                                    <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>" <?php if (!empty($prodotto['documenti_contabilita_articoli_centro_costo_ricavo']) && $centro['centri_di_costo_ricavo_id'] == $prodotto['documenti_contabilita_articoli_centro_costo_ricavo']) : ?> selected="selected" <?php endif; ?>><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <?php endif; ?>
                            <td width="75">
                                <select class="form-control input-sm js_documenti_contabilita_articoli_iva_id" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_iva_id]">
                                    <?php foreach ($elenco_iva as $iva) : ?>
                                    <option value="<?php echo $iva['iva_id']; ?>" data-perc="<?php echo (int) $iva['iva_valore']; ?>" <?php if ($iva['iva_id'] == $prodotto['documenti_contabilita_articoli_iva_id']) : ?> selected="selected" <?php endif; ?>><?php echo $iva['iva_label']; ?></option>
                                    <?php endforeach; ?>
                                </select> <input type="hidden" class="form-control input-sm text-right js_documenti_contabilita_articoli_iva" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_iva]" value="0" /> <input type="hidden" class="js_documenti_contabilita_articoli_prodotto_id" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_prodotto_id]" value="<?php echo $prodotto['documenti_contabilita_articoli_prodotto_id']; ?>" />
                            </td>
                            <td width="90" class="js_column_importo">
                                <input type="text" class="form-control input-sm text-right js-importo js_decimal" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_importo_totale]" placeholder="0" /> <input type="checkbox" class="_form-control js-applica_ritenute" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_applica_ritenute]" value="<?php echo DB_BOOL_TRUE; ?>" <?php if ($prodotto['documenti_contabilita_articoli_applica_ritenute'] == DB_BOOL_TRUE) : ?> checked="checked" <?php endif; ?> />
                                <small>Appl. ritenute</small>
                                <br /> <input type="checkbox" class="_form-control js-applica_sconto" name="products[<?php echo $k + 1; ?>][documenti_contabilita_articoli_applica_sconto]" value="<?php echo DB_BOOL_TRUE; ?>" <?php if ($prodotto['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE) : ?> checked="checked" <?php endif; ?> />
                                <small>Appl. sconto</small>
                            </td>
                            <td width="35" class="text-center js_actions">
                                <button type="button" class="btn btn-warning btn-xs js_xml_attributes" data-attributes="<?php echo base64_encode(json_encode($xml_articoli_altri_dati_gestionale)); ?>">
                                    <span class="fas fa-cogs"></span>
                                </button>
                                <button type="button" class="btn btn-danger btn-xs js_remove_product">
                                    <span class="fas fa-times"></span>
                                </button>
                            </td>
                        </tr>
                        <?php if (!empty($campi_personalizzati[2])) : ?>
                        <tr>
                            <?php foreach ($campi_personalizzati[2] as $campo) : ?>
                            <td>
                                <?php if ($campo) : ?>
                                <?php

                                                                        //ricreare il campo html passando il value valorizzato di questo record. Attenzione a non usare il campo, ma direttamente il map_to...
                                                                        $campo['fields_name'] = "products[" . ($k + 1) . "][{$campo['fields_name']}]";
                                                                            $campo['forms_fields_dependent_on'] = '';

                                                                            $data = [
                                                                                'lang' => '',
                                                                                'field' => $campo,
                                                                                'value' => $prodotto[$campo['campi_righe_articoli_map_to']],
                                                                                'label' => '', // '<label class="control-label">' . $field['fields_draw_label'] . '</label>',
                                                                                'placeholder' => '',
                                                                                'help' => '',
                                                                                'class' => 'input-sm',
                                                                                'attr' => '',
                                                                                'onclick' => '',
                                                                                'subform' => '',
                                                                            ];

                                                                            $campo['html'] = str_ireplace(
                                                                                'select2_standard',
                                                                                '',
                                                                                sprintf('<div class="form-group">%s</div>', $this->load->view("box/form_fields/{$campo['fields_draw_html_type']}", $data, true))
                                                                            );
                                                                            ?>
                                <strong><?php echo $campo['fields_draw_label']; ?></strong><br />
                                <?php echo $campo['html']; ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <?php endforeach; ?>

        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td>
                <button id="js_add_product" type="button" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Aggiungi prodotto
                </button>
            </td>
            <td colspan="<?php echo(count($campi_personalizzati)); ?>"></td>
            <td class="totali" colspan="7" style="background: #faf6ea;background: #f3cf66;">

                <label>Imponibile: <span class="js_competenze">€ 0</span></label>

                <label class="competenze_scontate">Imponibile scontato: <span class="js_competenze_scontate">€ 0</span></label>

                <label>
                    Sconto percentuale: <input type="text" name="documenti_contabilita_sconto_percentuale" class="js_sconto_totale sconto_totale pull-right" value="<?php if (!empty($documento['documenti_contabilita_sconto_percentuale'])) : ?><?php echo number_format((float) $documento['documenti_contabilita_sconto_percentuale'], 2, '.', ''); ?><?php else : ?>0<?php endif; ?>" />
                </label>

                <label>
                    <input class="js_sconto_su_imponibile" type="checkbox" value="<?php echo DB_BOOL_TRUE; ?>" name="documenti_contabilita_sconto_su_imponibile" <?php if (empty($documento) || (!empty($documento['documenti_contabilita_sconto_su_imponibile']) && $documento['documenti_contabilita_sconto_su_imponibile'] == DB_BOOL_TRUE)) : ?> checked="checked" <?php endif; ?> /> Applica sconto su imponibile
                </label>

                <label class="js_rivalsa"></label>

                <label class="js_competenze_rivalsa"></label>

                <label class="js_cassa_professionisti"></label>
                <label class="js_imponibile"></label>

                <label class="js_ritenuta_acconto"></label>

                <label class="js_tot_iva">IVA: <span class="___js_tot_iva">€ 0</span></label>

                <label class="js_split_payment"></label>

                <label class="js_tot_fattura_container" style="display:none;">Totale fattura: <span class="js_tot_fattura">€ 0</span></label>

                <label>Totale da saldare: <span class="js_tot_da_saldare">€ 0</span></label>

            </td>
        </tr>
    </tfoot>
</table>

<script>
var current_row_lotto;
$(function() {
    // Make the table rows draggable
    $("#js_product_table > tbody").sortable({
        handle: ".drag-handle",
        stop: function(event, ui) {
            // Renumber the position input fields
            $("#js_product_table > tbody > tr").each(function(i) {
                $(this).find(".position-input").val(i);
                var campo_esempio = $('.js_documenti_contabilita_articoli_codice', $(this));
                var data_id = campo_esempio.data('id');

                $('.position-input', $(this)).remove();
                campo_esempio.parent().append("<input type='hidden' name='products[" + data_id + "][documenti_contabilita_articoli_position]' class='position-input' value='" + i + "'>");
            });
        }
    });

    // michael - 15/05/2023 devo scatenare questo update tramite tabella_articoli.php perchè altrimenti quando aggiungi i prodotti, a meno di scatenare il drag, saranno tutti su chiave products[1]
    // ho preso l'esempio da questa soluzione stackoverflow: https://stackoverflow.com/a/19344119
    $('#js_product_table > tbody').on('sortupdate', function() {
        $('#js_product_table > tbody > tr').each(function(i) {
            $(this).find('.position-input').val(i);
            var campo_esempio = $('.js_documenti_contabilita_articoli_codice', $(this));
            var data_id = campo_esempio.data('id');

            $('.position-input', $(this)).remove();
            campo_esempio.parent().append("<input type='hidden' name='products[" + data_id + "][documenti_contabilita_articoli_position]' class='position-input' value='" + i + "'>");
        });
    });

    // Add a drag handle to each row
    $("#js_product_table table > tbody > tr > td.js_actions").prepend("<td class='drag-handle btn btn-primary btn-xs' style='color: #ffffff;'><i class=\"fas fa-arrows-alt-v\"></i></td>");

    // Add a hidden input field for the position of each row
    $("#js_product_table > tbody > tr").each(function(i) {
        var campo_esempio = $('.js_documenti_contabilita_articoli_codice', $(this));
        var data_id = campo_esempio.data('id');

        $('.position-input', $(this)).remove();
        campo_esempio.parent().append("<input type='hidden' name='products[" + data_id + "][documenti_contabilita_articoli_position]' class='position-input' value='" + i + "'>");
    });



    /** 
     * 
     * GESTIONE LOTTI E SCADENZE
     *   --- 12/09/2023 ---
     * Author: Matteo Puppis
     * 
     */

    <?php if ($this->datab->module_installed('magazzino')) : ?>
    <?php $magazzino_settings = $this->apilib->searchFirst('magazzino_settings'); ?>
    <?php endif; ?>

    $('#js_product_table').on('click', '.js_documenti_contabilita_articoli_lotto', function() {


        var prodotto_id = $('.js_documenti_contabilita_articoli_prodotto_id', $(this).closest('tr')).val();

        if (prodotto_id) {
            getLotti(prodotto_id, $(this).closest('tr'));
        }



    });

    function getLotti(prodotto_id, row_lotto = null) {

        current_row_lotto = row_lotto;

        $.ajax({
            url: base_url + "magazzino/movimenti/getlotti/" + prodotto_id,
            method: "get",

            success: function(data) {
                var my = JSON.parse(data);
                //Sottraggo le quantità già selezionate:

                if (my.status == 1) {
                    my.data.forEach((item, index) => {
                        var lotto_codice = item.movimenti_articoli_lotto;
                        var riga_lotto = $('.js_documenti_contabilita_articoli_lotto').filter(function() {
                            return this.value == lotto_codice;
                        }).parents('tr');

                        var quantita_gia_scalate = $('.js_documenti_contabilita_articoli_quantita', riga_lotto).val() - $('.js_documenti_contabilita_articoli_quantita', current_row_lotto).val();

                        if (typeof quantita_gia_scalate !== 'undefined' && !isNaN(quantita_gia_scalate)) {

                            //Sembra più un bug che una cosa intelligente questa... forse la cosa corretta sarebbe far sì così, ma escludendo la riga attuale!
                            my.data[index].movimenti_articoli_quantita = item.movimenti_articoli_quantita - quantita_gia_scalate;
                        }

                    });
                    if (false && my.data.length == 1) {
                        movimento = my.data[0];
                        quantita = movimento.movimenti_articoli_quantita;
                        $('.js_documenti_contabilita_articoli_lotto', row_lotto).val(movimento.movimenti_articoli_lotto);
                        $('.js_documenti_contabilita_articoli_scadenza', row_lotto).val(movimento.movimenti_articoli_data_scadenza);
                        if (parseInt(quantita) >= parseInt($('.js_documenti_contabilita_articoli_quantita', row_lotto).val())) { //Se ci sono abbastanza articoli siamo a posto con questa riga
                            // console.log(quantita,$('.js_documenti_contabilita_articoli_quantita', row_lotto).val());
                            // alert(1);
                        } else { //Altrimenti devo scorporare perchè non ho abbastanza articoli in questo lotto
                            // console.log(quantita);
                            // console.log($('.js_documenti_contabilita_articoli_quantita', row_lotto).val());
                            // alert(2);
                            var differenza = $('.js_documenti_contabilita_articoli_quantita', row_lotto).val() - quantita;
                            $('.js_documenti_contabilita_articoli_quantita', row_lotto).val(quantita);
                            console.log('TODO: duplicare la riga con la differenza ' + differenza);
                        }
                    } else {
                        $('#lotti_modal').modal('show');
                        //console.log(my.data);
                        $("#lotti_table tbody").html('');

                        $.each(my.data, function(i, item) {
                            var _data_scadenza = item.movimenti_articoli_data_scadenza;
                            if (!item.movimenti_articoli_lotto) {
                                item.movimenti_articoli_lotto = 'n/a';
                            }
                            if (_data_scadenza != null) {
                                var data_scadenza = _data_scadenza.substr(0, 10);
                            } else {
                                var data_scadenza = '';
                            }
                            if (item.movimenti_articoli_quantita > 0) {
                                // console.log(row_lotto);
                                // alert(JSON.stringify(row_lotto));
                                var button = "<button type='button' class='btn btn-success btn-sm btn_lotto' data-row='" + JSON.stringify(row_lotto) + "' data-lotto_codice='" + item.movimenti_articoli_lotto + "' data-lotto_scadenza='" + data_scadenza + "' data-lotto_quantita='" + item.movimenti_articoli_quantita + "'><i class='fa fa-plus'></i> Seleziona</button>";
                            } else {
                                var button = '';
                            }
                            var append_tr = '<tr>';

                            <?php if ($impostazioni['documenti_contabilita_settings_lotto'] == 1): ?>
                            append_tr += "<td>" + (item.movimenti_articoli_lotto == null ? '' : item.movimenti_articoli_lotto) + "</td>";
                            <?php endif; ?>

                            

                            <?php if ($impostazioni['documenti_contabilita_settings_scadenza'] == 1): ?>
                            append_tr += "<td>" + data_scadenza + "</td>";
                            <?php endif; ?>
                            <?php if (!empty($magazzino_settings) && $magazzino_settings['magazzino_settings_show_marchio'] == 1): ?>

                            append_tr += "<td>" + (item.fw_products_brand_value == null ? '' : item.fw_products_brand_value) + "</td>";
                            <?php endif; ?>
                            <?php if (!empty($magazzino_settings) && $magazzino_settings['magazzino_settings_show_fornitore'] == 1): ?>
                            append_tr += "<td>" + (item.customers_company == null ? '' : item.customers_company) + "</td>";
                            <?php endif; ?>

                            append_tr += "<td>" + item.movimenti_articoli_quantita + "</td><td>" + button + "</td></tr>";

                            //$("#lotti_table tbody").append("<tr><td>" + item.movimenti_articoli_lotto + "</td><td>" + data_scadenza + "</td><td>" + item.movimenti_articoli_quantita + "</td><td>" + button + "</td></tr>");
                            $("#lotti_table tbody").append(append_tr);

                        });
                    }

                } else {
                    //alert(my.error);
                }
            }
        });
    }

    $('#lotti_table').on('click', '.btn_lotto', function() {

        var riga = current_row_lotto; //$(this).data('row');
        var lotto = $(this).data('lotto_codice');
        var scadenza = $(this).data('lotto_scadenza');
        var quantita = $(this).data('lotto_quantita');

        $('.js_documenti_contabilita_articoli_lotto', riga).val(lotto);
        $('.js_documenti_contabilita_articoli_scadenza', riga).val(scadenza);
        if (quantita >= $('.js_documenti_contabilita_articoli_quantita', riga).val()) { //Se ci sono abbastanza articoli siamo a posto con questa riga

        } else { //Altrimenti devo scorporare perchè non ho abbastanza articoli in questo lotto ***ATTENZIONE: non funziona benissimo sta cosa... va fatta funzionare meglio...
            var differenza = $('.js_documenti_contabilita_articoli_quantita', riga).val() - quantita;

            var change_quantita = confirm('Attenzione! La quantità presente in questo lotto (' + quantita + ') è inferiore alla quantità richiesta (' + $('.js_documenti_contabilita_articoli_quantita', riga).val() + '). Vuoi modificare la quantità di questa riga facendola corrispondere a quella presente nel lotto?');

            $('.js_documenti_contabilita_articoli_quantita', riga).val(quantita);

            // $('#js_add_product').trigger('click');
            // var codice_articolo = $('.js_documenti_contabilita_articoli_codice', riga).val();
            // var nuova_riga = riga.next();

            // $('.js_documenti_contabilita_articoli_quantita', nuova_riga).val(differenza).trigger('change');
            // $('.js_documenti_contabilita_articoli_codice', nuova_riga).val(codice_articolo).trigger('keydown');



        }

        $('#lotti_modal').modal('hide');
        $('.modal-backdrop').remove();

    });

    /** FINE GESTIONE LOTTI E SCADENZE **/



});
</script>