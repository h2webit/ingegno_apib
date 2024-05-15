<?php

class Docs extends CI_Model
{
    public function generateXmlFilename($prefisso, $documento_id)
    {
        $algoritmo = $this->incrementalHash();
        $xmlfilename = $prefisso . "_" . $algoritmo . ".xml";
        for ($i = 1; $i < 100; $i++) {
            if ($this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_file_xml = '$xmlfilename'")->row_array()) {
                usleep(100000);
                $algoritmo = $this->incrementalHash();
                $xmlfilename = $prefisso . "_" . $algoritmo . ".xml";
            } else {
                break;
            }
        }
        //Se arrivato qua il file esiste ancora c'è proprio qualcosa che non va!
        if ($this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_file_xml = '$xmlfilename'")->row_array()) {
            log_message('debug', "Generati 100 random già esistenti per il documento id '{$documento_id}'! Ultimo generato: '$algoritmo'.");
            $algoritmo = '00000';
            $xmlfilename = $prefisso . "_" . $algoritmo . ".xml";
        }
        // Aggiorno il documento indicando il nome del file xml per fare match piu facilmente con le notifiche di scarto
        //$this->apilib->edit("documenti_contabilita", $documento_id, ["documenti_contabilita_nome_file_xml" => $xmlfilename]); // Sostituito perche dava problemi veniva svuotato dopo
        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_nome_file_xml = '$xmlfilename' WHERE documenti_contabilita_id = '$documento_id'");
        $this->mycache->clearCacheTags(['documenti_contabilita']);
        return $xmlfilename;
    }
    
    private function incrementalHash($len = 5)
    {
        $charset = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"; //abcdefghijklmnopqrstuvwxyz"; //Disabilitato perchè non chiaro nella documentazione se sia case insensitive o meno
        $base = strlen($charset);
        $result = '';
        
        $now = explode(' ', microtime())[1];
        while ($now >= $base) {
            $i = $now % $base;
            $result = $charset[$i] . $result;
            $now /= $base;
        }
        return substr($result, -5);
    }
    
    public function getMappature()
    {
        $mappature_data = $this->apilib->search('documenti_contabilita_mappature');
        return array_key_value_map($mappature_data, 'documenti_contabilita_mappature_key_value', 'documenti_contabilita_mappature_value');
    }
    public function getMappatureAutocomplete()
    {
        $mappature_data = $this->apilib->search('documenti_contabilita_mappature');
        return array_key_value_map($mappature_data, 'documenti_contabilita_mappature_key_value', 'documenti_contabilita_mappature_autocomplete');
    }
    public function getDocumentiPadriOld($id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $id);
        
        $return = [];
        $elaborated_ids = [];
        
        while ($documento['documenti_contabilita_rif_documento_id'] && $documento['documenti_contabilita_rif_documento_id'] != $id && !in_array($documento['documenti_contabilita_rif_documento_id'], $elaborated_ids)) {
            $elaborated_ids[] = $documento['documenti_contabilita_rif_documento_id'];
            $return[] = $this->apilib->view('documenti_contabilita', $documento['documenti_contabilita_rif_documento_id']);
            $documento = $this->apilib->view('documenti_contabilita', $documento['documenti_contabilita_rif_documento_id']);
        }
        
        return $return;
    }
    
    public function getDocumentiPadri($id, $depth = 2)
    {
        $documento = $this->db->get_where("rel_doc_contabilita_rif_documenti", ['rel_doc_contabilita_rif_documenti_padre' => $id])->result_array();
        
        // @todo
    }
    
    public function get_content_fattura_elettronica($id, $reverse = false)
    {
        $dati['fattura'] = $this->apilib->view('documenti_contabilita', $id);
        //$dati['fattura']['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $id]);
        $dati['fattura']['articoli'] = $this->getArticoliFromDocumento($id);
        $dati['fattura']['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $id]);
        foreach ($dati['fattura']['scadenze'] as $key => $scadenza) {
            if ($scadenza['documenti_contabilita_scadenze_ammontare'] == '0.00') {
                unset($dati['fattura']['scadenze'][$key]);
            }
        }
        
        if (!$reverse) {
            $pagina = $this->load->module_view("contabilita/views", 'xml_fattura_elettronica', ['dati' => $dati], true);
            // Utilizza un'espressione regolare più generale per catturare tutti i tipi di spazi bianchi
            // Rimuove gli spazi bianchi tra il tag e il suo contenuto
            $pagina = preg_replace('/>\s+/', '>', $pagina);
            $pagina = preg_replace('/\s+</', '<', $pagina);
            
            // Rimuove gli spazi bianchi tra i tag XML
            $pagina = preg_replace('/>\s+</', '><', $pagina);
            
            //debug($pagina,true);
        } else {
            $pagina = $this->load->module_view("contabilita/views", 'xml_fattura_elettronica_reverse', ['dati' => $dati], true);
        }
        
        $pagina = str_ireplace(['–', '’'], ['-', "'"], $pagina);
        
        // Formattazione XML per renderlo pulito solo se DOM è caricato come estensione
        if (extension_loaded('dom')) {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($pagina);
            return $dom->saveXML();
        } else {
            return $pagina;
        }
    }
    
    public function generate_beautify_name($documento)
    {
        return $documento['documenti_contabilita_tipo_value'] . "_" . $documento['documenti_contabilita_numero'] . $documento['documenti_contabilita_serie'] . "_" . substr(url_title($documento['documenti_contabilita_settings_company_name']), 0, 15) . "_" . date('d-m-Y', strtotime($documento['documenti_contabilita_data_emissione'])) . ".pdf";
    }
    
    public function salva_file_fisico($filename, $folder, $content)
    {
        $physicalDir = FCPATH . "uploads/" . $folder;
        
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }
        
        $tmpFile = "{$physicalDir}/{$filename}";
        file_put_contents($tmpFile, $content, LOCK_EX);
        if (file_exists($tmpFile)) {
            $file_path = str_replace(FCPATH . "uploads/", "", $tmpFile);
            
            return $file_path;
        } else {
            return false;
        }
    }
    
    public function generate_xml($documento, $xml_filename = null)
    {
        $documento_id = $documento['documenti_contabilita_id'];
        
        // PDF DI CORTESIA
        
        // Storicizzo un PDF di cortesia
        if ($documento['documenti_contabilita_template_pdf']) {
            $template = $this->apilib->view('documenti_contabilita_template_pdf', $documento['documenti_contabilita_template_pdf']);
            
            // Se caricato un file che contiene un html da priorità a quello
            if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
            } else {
                $content_html = $template['documenti_contabilita_template_pdf_html'];
            }
            
            $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
        } else {
            $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
        }
        // Storicizzo la copia di cortesia del PDF su file
        if (file_exists($pdfFile)) {
            $pdf_file_name = $this->docs->generate_beautify_name($documento);
            //debug($pdf_file_name);
            $content = file_get_contents($pdfFile, true);
            $folder = "modules_files/contabilita/pdf_cortesia";
            $filepath = $this->salva_file_fisico($pdf_file_name, $folder, $content);
            //debug($filepath);
            $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_pdf' => $filepath]);
            
            //debug($this->apilib->view("documenti_contabilita", $documento_id)['documenti_contabilita_file_pdf'],true);
            
            unlink($pdfFile);
        }
        
        
        
        // XML
        if (empty($xml_filename)) {
            $xml_filename = $documento['documenti_contabilita_nome_file_xml'];
        }
        
        if ($this->db->dbdriver != 'postgre') {
            $progressivo_invio = $this->db->query("SELECT MAX(CAST(documenti_contabilita_progressivo_invio AS integer)) as m FROM documenti_contabilita");
        } else {
            $progressivo_invio = $this->db->query("SELECT MAX(documenti_contabilita_progressivo_invio::int4) as m FROM documenti_contabilita");
        }
        
        if ($progressivo_invio->num_rows() == 0) {
            $progressivo_invio = 1;
        } else {
            $progressivo_invio = (int) ($progressivo_invio->row()->m) + 1;
        }
        
        $this->db->where('documenti_contabilita_id', $documento_id)->update('documenti_contabilita', ['documenti_contabilita_progressivo_invio' => $progressivo_invio]);
        
        $reverse = in_array($documento['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19']);
        $content_xml = $this->get_content_fattura_elettronica($documento_id, $reverse);
        
        
        // Storicizzo la copia di cortesia del XML su file non su db
        $folder = "modules_files/contabilita/xml_generati";
        $filepath = $this->salva_file_fisico($xml_filename, $folder, $content_xml);
        $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_xml' => $filepath]);
        
        
        
        
        // Deprecata la storicizzazione del pdf sul database, viene salvata sul file fisico nella cartella dedicata
        // if (file_exists($pdfFile)) {
        //     $contents = file_get_contents($pdfFile, true);
        //     $pdf_b64 = base64_encode($contents);
        //     $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_preview' => $pdf_b64]);
        // }
    }
    public function numero_sucessivo($azienda, $tipo, $serie, $data)
    {
        $data_emissione = DateTime::createFromFormat("d/m/Y", $data);
        $year = $data_emissione->format('Y');
        
        //20220916 - Le note di credito non devono più seguire una numerazione a sè, ma proseguire con la numerazione delle fatture (anche per i reverse)
        if (in_array($tipo, [1, 4, 11, 12])) { //1,4,11 e 12 vanno insieme
            $tipo_replace = '1,4,11,12';
            // } elseif (in_array($tipo, [8,10])) {//DDT cliente e fornitore li considero alla pari
            //     $tipo_replace = '8,10';
        } else {
            $tipo_replace = $tipo;
        }
        
        if ($serie) {
            $serie_where = " AND documenti_contabilita_serie = '$serie'";
        } else {
            $serie_where = " AND (documenti_contabilita_serie IS NULL OR documenti_contabilita_serie = '')";
        }
        $serie_where .= " AND documenti_contabilita_azienda = '$azienda'";
        
        //debug($serie_where, true);
        
        if ($this->db->dbdriver != 'postgre') {
            $next = $this->db->query("SELECT MAX(documenti_contabilita_numero) + 1 as numero FROM documenti_contabilita WHERE documenti_contabilita_tipo IN ($tipo_replace) $serie_where AND YEAR(documenti_contabilita_data_emissione) = $year")->row()->numero;
            //debug($this->db->last_query(), true);
        } else {
            $next = $this->db->query("SELECT MAX(documenti_contabilita_numero::int4)::int4 + 1 as numero FROM documenti_contabilita WHERE documenti_contabilita_tipo IN ($tipo_replace) $serie_where AND date_part('year', documenti_contabilita_data_emissione) = '$year'")->row()->numero;
        }
        
        return ($next) ?: 1;
    }
    
    // Metodo usato per automatismi e postprocess vari, non è per ora il metodo principale di creazione di un documento
    public function doc_express_save(array $data = [])
    {
        extract($data);
        $mappature = $this->docs->getMappature();
        extract($mappature);
        if (array_key_exists('documenti_contabilita_destinatario', $data)) {
            $destinario = $data['documenti_contabilita_destinatario'];
            
            $cliente = $this->apilib->view($entita_clienti, $cliente_id ?? $fornitore_id);
        } elseif (!empty($cliente_id) || !empty($fornitore_id)) {
            $cliente = $this->apilib->view($entita_clienti, $cliente_id ?? $fornitore_id);
            $customer['ragione_sociale'] = (!empty($cliente[$clienti_ragione_sociale])) ? $cliente[$clienti_ragione_sociale] : $cliente[$clienti_nome] . ' ' . $cliente[$clienti_cognome];
            $customer['indirizzo'] = $cliente[$clienti_indirizzo];
            $customer['citta'] = $cliente[$clienti_citta];
            $customer['provincia'] = $cliente[$clienti_provincia];
            
            $nazione_id = $cliente[$clienti_nazione];
            $nazioni = $this->db->query("SELECT * FROM countries WHERE countries_id = '$nazione_id'");
            if ($nazioni->num_rows() > 0) {
                $customer['nazione'] = $nazioni->row()->countries_iso;
            }
            
            $customer['cap'] = $cliente[$clienti_cap];
            $customer['pec'] = $cliente[$clienti_pec];
            $customer['partita_iva'] = $cliente[$clienti_partita_iva];
            $customer['codice_fiscale'] = $cliente[$clienti_codice_fiscale];
            $customer['codice_sdi'] = $cliente[$clienti_codice_sdi];
            
            $destinario = json_encode($customer);
        } else {
            // die(json_encode(['status' => 0, 'txt' => 'Id cliente o fornitore non trovato.']));
            throw new ApiException('Id cliente o fornitore non trovato.');
            exit;
        }
        
        /** MICHAEL, 24/01/2023
         * Ripristinata logica precedente al commit di Matteo del 15/01, in quanto questa logica è, secondo me, corretta.
         * Perchè qui già prende l'azienda nel caso venga passata.
         * In caso ci fossero casi in cui viene presa l'azienda sbagliata, allora è da sistemare all'origine, dove viene chiamato il metodo.
         */
        $settings_db = $this->apilib->searchFirst('documenti_contabilita_settings', [], 0, 'documenti_contabilita_settings_id', 'DESC');
        
        $azienda = array_get($data, 'azienda', $settings_db['documenti_contabilita_settings_id'] ?? null);
        
        $settings_db = $this->apilib->searchFirst('documenti_contabilita_settings', ['documenti_contabilita_settings_id' => $azienda]);
        
        if ($settings_db['documenti_contabilita_settings_serie_default']) {
            $serie_db = $this->apilib->view('documenti_contabilita_serie', $settings_db['documenti_contabilita_settings_serie_default']);
        } else {
            $serie_db = '';
        }
        
        $serie = array_get($data, 'serie', $serie_db['documenti_contabilita_serie_value'] ?? null);
        
        $totale = array_get($data, 'totale', 0);
        $costo_spedizione = array_get($data, 'costo_spedizione', false);
        $tipo_destinatario = array_get($data, 'tipo_destinatario', null);
        $tipo = array_get($data, 'tipo_documento', 1);
        
        $tpl_pdf = $this->db
            ->where("documenti_contabilita_template_pdf_tipo = '{$tipo}'")
            ->or_where("documenti_contabilita_template_pdf_default", DB_BOOL_TRUE)
            ->order_by("documenti_contabilita_template_pdf_tipo = '{$tipo}'", 'DESC')
            ->get("documenti_contabilita_template_pdf")->row_array();
        
        $tpl_pdf = array_get($data, 'template', $tpl_pdf['documenti_contabilita_template_pdf_id'] ?? null);
        
        $data_emissione = array_get($data, 'data_emissione', date('Y-m-d'));
        
        if (array_get($data, 'documenti_contabilita_numero')) {
            $numero_documento = array_get($data, 'documenti_contabilita_numero');
        } else {
            $numero_documento = $this->numero_sucessivo($azienda, $tipo, $serie, (DateTime::createFromFormat('Y-m-d', $data_emissione))->format('d/m/Y'));
        }
        
        $rif_doc = array_get($data, 'documenti_contabilita_rif_documento_id', null);
        
        $dati_documento = [
            'documenti_contabilita_numero' => $numero_documento,
            'documenti_contabilita_serie' => $serie,
            'documenti_contabilita_destinatario' => $destinario,
            'documenti_contabilita_customer_id' => $cliente_id ?? null,
            'documenti_contabilita_supplier_id' => $fornitore_id ?? null,
            'documenti_contabilita_data_emissione' => $data_emissione,
            //'documenti_contabilita_metodo_pagamento' => array_get($data, 'metodo_pagamento', 'carta di credito'),
            'documenti_contabilita_template_pagamento' => array_get($data, 'template_pagamento', ($cliente['customers_template_pagamento']) ?? null),
            'documenti_contabilita_tipo_destinatario' => $tipo_destinatario,
            'documenti_contabilita_azienda' => $azienda,
            'documenti_contabilita_utente_id' => array_get($data, 'utente', $this->auth->get('users_id')),
            'documenti_contabilita_template_pdf' => $tpl_pdf,
            'documenti_contabilita_stato' => array_get($data, 'stato', 1),
        ];
        
        if (!empty($cliente['customers_template_pagamento'])) {
            $tpl_pagamento = $this->db
                ->where('documenti_contabilita_tpl_pag_scadenze_id', $cliente['customers_template_pagamento'])
                ->get('documenti_contabilita_tpl_pag_scadenze')->row_array();
            
            if (!empty($tpl_pagamento['documenti_contabilita_tpl_pag_scadenze_banca_di_riferimento'])) {
                $data['conto_corrente'] = $tpl_pagamento['documenti_contabilita_tpl_pag_scadenze_banca_di_riferimento'];
            }
        }
        // Cerco se c'è un template di default uso quello altrimenti il primo che creato che si presume il generico
        if (empty($data['documenti_contabilita_template_pdf'])) {
            $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', ["documenti_contabilita_template_pdf_default" => DB_BOOL_TRUE], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
            if (empty($tpl_pdf_db)) {
                $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
            }
            $dati_documento['documenti_contabilita_template_pdf'] = $tpl_pdf;
        }
        
        $rif_docs = array_get($data, 'rif_documenti', []);
        
        $dati_documento['documenti_contabilita_oggetto'] = array_get($data, 'oggetto', null);
        $dati_documento['documenti_contabilita_rif_uso_interno'] = array_get($data, 'rif_uso_interno', null);
        $dati_documento['documenti_contabilita_rif_data'] = array_get($data, 'rif_data', null);
        $dati_documento['documenti_contabilita_note_interne'] = array_get($data, 'note_interne', null);
        $dati_documento['documenti_contabilita_tipo'] = $tipo;
        $dati_documento['documenti_contabilita_valuta'] = array_get($data, 'valuta', 'EUR');
        $dati_documento['documenti_contabilita_tasso_di_cambio'] = null;
        $dati_documento['documenti_contabilita_conto_corrente'] = array_get($data, 'conto_corrente', null);
        $dati_documento['documenti_contabilita_formato_elettronico'] = array_get($data, 'formato_elettronico', DB_BOOL_FALSE);
        $dati_documento['documenti_contabilita_extra_param'] = array_get($data, 'documenti_contabilita_extra_param', null);
        //        $dati_documento['documenti_contabilita_rif_documento_id'] = array_get($data, 'documenti_contabilita_rif_documento_id', null); // 08-06 - DEPRECATO, USARE rif_documenti
        $dati_documento['documenti_contabilita_da_sollecitare'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_tipologia_fatturazione'] = array_get($data, 'tipo_fatturazione', 1);
        $dati_documento['documenti_contabilita_rivalsa_inps_perc'] = null;
        $dati_documento['documenti_contabilita_stato_invio_sdi'] = array_get($data, 'stato_invio_sdi', 1);
        $dati_documento['documenti_contabilita_cassa_professionisti_perc'] = null;
        
        //Accompagnatoria/DDT
        $dati_documento['documenti_contabilita_fattura_accompagnatoria'] = array_get($data, 'dati_trasporto', DB_BOOL_FALSE);
        $dati_documento['documenti_contabilita_n_colli'] = array_get($data, 'n_colli', null);
        $dati_documento['documenti_contabilita_peso'] = array_get($data, 'peso', null);
        $dati_documento['documenti_contabilita_volume'] = array_get($data, 'volume', null);
        $dati_documento['documenti_contabilita_targhe'] = null;
        $dati_documento['documenti_contabilita_descrizione_colli'] = array_get($data, 'descrizione_colli', null);
        $dati_documento['documenti_contabilita_luogo_destinazione'] = array_get($data, 'luogo_destinazione', null);
        $dati_documento['documenti_contabilita_trasporto_a_cura_di'] = array_get($data, 'vettore', null);
        $dati_documento['documenti_contabilita_causale_trasporto'] = null;
        $dati_documento['documenti_contabilita_annotazioni_trasporto'] = null;
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc'] = array_get($data, 'ritenuta_acconto_perc', null);
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'] = array_get($data, 'ritenuta_acconto_perc_imponibile', null);
        $dati_documento['documenti_contabilita_porto'] = null;
        $dati_documento['documenti_contabilita_vettori_residenza_domicilio'] = null;
        $dati_documento['documenti_contabilita_data_ritiro_merce'] = array_get($data, 'data_ritiro_merce', null);
        $dati_documento['documenti_contabilita_rif_ddt'] = null;
        $dati_documento['documenti_contabilita_codice_esterno'] = array_get($data, 'codice_esterno', null);
        $dati_documento['documenti_contabilita_tracking_code'] = array_get($data, 'tracking_code', null);
        
        // Attributi avanzati Fattura Elettronica
        $dati_documento['documenti_contabilita_fe_attributi_avanzati'] = DB_BOOL_FALSE;
        
        /*$json = [];
        if (!empty($input['documenti_contabilita_fe_rif_n_linea'])) {
        $json['RiferimentoNumeroLinea'] = $input['documenti_contabilita_fe_rif_n_linea'];
        }
        if (!empty($input['documenti_contabilita_fe_id_documento'])) {
        $json['IdDocumento'] = $input['documenti_contabilita_fe_id_documento'];
        }*/
        
        $dati_documento['documenti_contabilita_fe_attributi_avanzati_json'] = '';
        $dati_documento['documenti_contabilita_fe_dati_contratto'] = '';
        $dati_documento['documenti_contabilita_fe_ordineacquisto'] = '';
        
        
        //Pagamento
        $dati_documento['documenti_contabilita_accetta_paypal'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_split_payment'] = DB_BOOL_FALSE;
        
        //Note generiche
        $dati_documento['documenti_contabilita_note_generiche'] = array_get($data, 'note_generiche', null);
        
        $dati_documento['documenti_contabilita_centro_di_ricavo'] = array_get($data, 'centro_di_costo', null);
        
        $iva = [];
        $competenze = 0;
        $imponibile = 0;
        $iva_tot = 0;
        
        $dati_documento['documenti_contabilita_sconto_percentuale'] = array_get($data, 'sconto_percentuale', (!empty($cliente['customers_sconto_abituale']) && $cliente['customers_sconto_abituale'] > 0) ? $cliente['customers_sconto_abituale'] : null);
        
        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                if (empty($articolo['documenti_contabilita_articoli_riga_desc']) || $articolo['documenti_contabilita_articoli_riga_desc'] != DB_BOOL_TRUE) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                    $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                    //debug($articolo);
                    
                    if (empty($articolo['documenti_contabilita_articoli_sconto'])) {
                        $articolo['documenti_contabilita_articoli_sconto'] = 0;
                    }
                    
                    $importo = ($prezzo_unit * $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int) $articolo['documenti_contabilita_articoli_sconto']);
                    if (!empty($articolo['documenti_contabilita_articoli_applica_sconto']) && $articolo['documenti_contabilita_articoli_applica_sconto'] && !empty($dati_documento['documenti_contabilita_sconto_percentuale'])) {
                        $importo = $importo / 100 * (100 - $dati_documento['documenti_contabilita_sconto_percentuale']);
                    }
                    
                    $iva_valore = ($importo * $iva_perc) / 100;
                    
                    if (array_key_exists($articolo['documenti_contabilita_articoli_iva_id'], $iva)) {
                        $iva[$articolo['documenti_contabilita_articoli_iva_id']][1] += $iva_valore;
                    } else {
                        $iva[$articolo['documenti_contabilita_articoli_iva_id']] = [$iva_perc, $iva_valore];
                    }
                    
                    $competenze += $importo;
                    $imponibile += $importo;
                    $iva_tot += $iva_valore;
                    $totale += ($importo + $iva_valore);
                }
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $articolo_dati) {
                $qty = $articolo_dati['qty'];
                
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);
                if (!empty($articolo_dati['prezzo_unit']) && is_numeric($articolo_dati['prezzo_unit'])) {
                    $prezzo_unit = $articolo_dati['prezzo_unit'];
                } else {
                    if (!empty($fornitore_id) && $campo_prezzo_fornitore && $articolo[$campo_prezzo_fornitore] != '0.00') {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                    } else {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                    }
                }
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);
                $importo = $prezzo_unit * $qty;
                
                
                
                $iva_valore = ($importo * $iva_perc) / 100;
                
                $iva[$articolo['iva_valore']][] = $iva_valore;
                
                $competenze += $importo;
                $imponibile += $importo;
                $iva_tot += $iva_valore;
                $totale += ($importo + $iva_valore);
            }
        }
        
        if ($costo_spedizione) {
            $imponibile += $costo_spedizione;
            $competenze += $costo_spedizione;
        }
        
        // debug($customer);
        // debug($dati_documento['documenti_contabilita_sconto_percentuale'],true);
        //$dati_documento['documenti_contabilita_imponibile_scontato'] = array_get($data, 'imponibile_scontato', 0);
        //Importi
        //debug($imponibile,true);
        $dati_documento['documenti_contabilita_imponibile'] = array_get($data, 'imponibile', $imponibile);
        if (array_get($data, 'importo_scontato')) {
            $iva_tot = $iva_tot - ($iva_tot * $dati_documento['documenti_contabilita_sconto_percentuale'] / 100);
            $sconto_imponibile = $dati_documento['documenti_contabilita_imponibile'] * $dati_documento['documenti_contabilita_sconto_percentuale'] / 100;
            $dati_documento['documenti_contabilita_imponibile_scontato'] = $dati_documento['documenti_contabilita_imponibile'] - $sconto_imponibile;
            $dati_documento['documenti_contabilita_imponibile_scontato'] = number_format($dati_documento['documenti_contabilita_imponibile_scontato'], 2, '.', '');
            $imponibile = $dati_documento['documenti_contabilita_imponibile'] - $sconto_imponibile;
            $dati_documento['documenti_contabilita_imponibile'] = number_format($competenze - ($competenze * $dati_documento['documenti_contabilita_sconto_percentuale'] / 100), 2, '.', '');
            $dati_documento['documenti_contabilita_imponibile_scontato'] = $dati_documento['documenti_contabilita_imponibile'];
        }
        //debug($dati_documento,true);
        $dati_documento['documenti_contabilita_iva'] = array_get($data, 'iva', $iva_tot);
        $dati_documento['documenti_contabilita_competenze'] = array_get($data, 'competenze', $competenze);
        $dati_documento['documenti_contabilita_iva_json'] = array_get($data, 'iva_json', json_encode($iva));
        $dati_documento['documenti_contabilita_imponibile_iva_json'] = array_get($data, 'imponibile_iva_json', json_encode([]));
        
        $dati_documento['documenti_contabilita_totale'] = array_get($data, 'documenti_contabilita_totale', number_format($totale, 2, '.', ''));
        
        $dati_documento['documenti_contabilita_rivalsa_inps_valore'] = 0;
        $dati_documento['documenti_contabilita_competenze_lordo_rivalsa'] = 0;
        $dati_documento['documenti_contabilita_cassa_professionisti_valore'] = 0;
        $dati_documento['documenti_contabilita_ritenuta_acconto_valore'] = array_get($data, 'ritenuta_acconto_valore', 0);
        $dati_documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'] = array_get($data, 'ritenuta_acconto_imponibile_valore', 0);
        $dati_documento['documenti_contabilita_tipo_ritenuta'] = array_get($data, 'tipo_ritenuta', null);
        $dati_documento['documenti_contabilita_importo_bollo'] = 0;
        $dati_documento['documenti_contabilita_applica_bollo'] = DB_BOOL_FALSE;
        //$dati_documento['documenti_contabilita_importo_scontato'] =  array_get($data, 'importo_scontato', null);
        
        $dati_documento['documenti_contabilita_causale_pagamento_ritenuta'] = array_get($data, 'causale_pagamento_ritenuta', null);
        $dati_documento['documenti_contabilita_stato_pagamenti'] = array_get($data, 'stato_pagamenti', 1);
        try {
            //debug($dati_documento,true);
            $documento = $this->apilib->create('documenti_contabilita', $dati_documento);
            
            $documento_id = $documento['documenti_contabilita_id'];
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }
        
        if (!empty($rif_docs)) {
            $this->associaDocumenti($documento_id, $rif_docs);
        }
        
        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                if (empty($articolo['documenti_contabilita_articoli_riga_desc']) || $articolo['documenti_contabilita_articoli_riga_desc'] != DB_BOOL_TRUE) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                    $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                    
                    if (empty($articolo['documenti_contabilita_articoli_sconto'])) {
                        $articolo['documenti_contabilita_articoli_sconto'] = 0;
                    }
                    
                    $importo = ($prezzo_unit * $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int) $articolo['documenti_contabilita_articoli_sconto']);
                    $iva_valore = ($importo * $iva_perc) / 100;
                    $iva[$articolo['documenti_contabilita_articoli_iva_id']][] = $iva_valore;
                    //trovo il valore dell'iva, se ce ne sono di più, prendo la prima
                    
                    if ($iva_perc == '0.00') {
                        //TODO: siamo sicuri di iva_codice???
                        
                        $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                    } else {
                        $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $iva_perc]);
                    }
                    if (empty($articolo['documenti_contabilita_articoli_iva_id'])) {
                        $articolo['documenti_contabilita_articoli_iva_id'] = $iva['iva_id'];
                    }
                    $articolo['documenti_contabilita_articoli_iva'] = $iva_valore;
                    // $competenze += $importo;
                    // $imponibile += $importo;
                    // $iva_tot += $iva_valore;
                    // $totale += ($importo + $iva_valore);
                    
                    $articolo['documenti_contabilita_articoli_applica_sconto'] = (!empty($articolo['documenti_contabilita_articoli_applica_sconto']) && $articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_FALSE) ? DB_BOOL_FALSE : DB_BOOL_TRUE;
                    
                    $articolo['documenti_contabilita_articoli_imponibile'] = $importo;
                    
                    //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                    $articolo['documenti_contabilita_articoli_importo_totale'] = ($importo + $iva_valore);
                    
                    
                }
                if (isset($articolo['documenti_contabilita_articoli_id'])) {
                    unset($articolo['documenti_contabilita_articoli_id']);
                }
                $articolo['documenti_contabilita_articoli_documento'] = $documento_id;
                try {
                    $this->apilib->create("documenti_contabilita_articoli", $articolo);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $articolo_dati) {
                $qty = $articolo_dati['qty'];
                unset($articolo_dati['qty']);
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);
                
                $doc_articolo = [];
                if (!empty($articolo_dati['documenti_contabilita_articoli_rif_riga_articolo'])) {
                    $doc_articolo = $this->db->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $articolo_dati['documenti_contabilita_articoli_rif_riga_articolo']])->row_array();
                }
                
                if (!empty($articolo_dati['prezzo_unit']) && is_numeric($articolo_dati['prezzo_unit'])) {
                    $prezzo_unit = $articolo_dati['prezzo_unit'];
                } else {
                    if (!empty($fornitore_id) && $campo_prezzo_fornitore) {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                    } else {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                    }
                }
                unset($articolo_dati['prezzo_unit']);
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);
                
                // if ($iva_perc == '0.00') {
                //TODO: VEDI 20 righe sopra...
                //     $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                // } else {
                $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $iva_perc]);
                
                // }
                
                $sconto = (!empty($articolo['documenti_contabilita_articoli_sconto']) ? $articolo['documenti_contabilita_articoli_sconto'] : 0);
                
                $importo = ($prezzo_unit * $qty / 100) * (100 - (int) $sconto);
                
                // $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;
                
                $prodotto = [
                    'documenti_contabilita_articoli_documento' => $documento_id,
                    'documenti_contabilita_articoli_iva_id' => $iva['iva_id'],
                    'documenti_contabilita_articoli_name' => $articolo[$campo_preview_prodotto],
                    'documenti_contabilita_articoli_quantita' => $qty,
                    'documenti_contabilita_articoli_prodotto_id' => $articolo_id,
                    'documenti_contabilita_articoli_iva' => $iva_valore,
                    'documenti_contabilita_articoli_imponibile' => $importo,
                    'documenti_contabilita_articoli_prezzo' => $prezzo_unit,
                    'documenti_contabilita_articoli_codice' => $articolo[$campo_codice_prodotto],
                    'documenti_contabilita_articoli_iva_perc' => $iva_perc,
                    'documenti_contabilita_articoli_descrizione' => $articolo[$campo_descrizione_prodotto],
                    'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_TRUE,
                    //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                    'documenti_contabilita_articoli_importo_totale' => ($importo + $iva_valore),
                ];
                
                // aggiungo campi personalizzati all'array $documento['articoli']
                if (!empty($campi_personalizzati[1]) && !empty($doc_articolo)) {
                    foreach ($campi_personalizzati[1] as $campo) {
                        if (array_key_exists($campo['campi_righe_articoli_map_to'], $doc_articolo) && !empty($doc_articolo[$campo['campi_righe_articoli_map_to']])) {
                            $prodotto[$campo['campi_righe_articoli_map_to']] = $doc_articolo[$campo['campi_righe_articoli_map_to']];
                        }
                    }
                }
                
                $prodotto = array_merge($prodotto, $articolo_dati);
                //debug($prodotto, true);
                
                // dump($prodotto);
                
                try {
                    $this->apilib->create('documenti_contabilita_articoli', $prodotto);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage() . ' | json articolo: ' . json_encode($prodotto));
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        }
        
        if ($costo_spedizione) {
            $this->apilib->create("documenti_contabilita_articoli", [
                'documenti_contabilita_articoli_documento' => $documento_id,
                'documenti_contabilita_articoli_iva_id' => $iva_default_id,
                'documenti_contabilita_articoli_name' => 'Shipping cost',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_prodotto_id' => null,
                'documenti_contabilita_articoli_iva' => 0,
                'documenti_contabilita_articoli_imponibile' => $costo_spedizione,
                'documenti_contabilita_articoli_prezzo' => $costo_spedizione,
                'documenti_contabilita_articoli_codice' => '',
                'documenti_contabilita_articoli_iva_perc' => $iva_default_valore,
                'documenti_contabilita_articoli_descrizione' => '',
                'documenti_contabilita_articoli_importo_totale' => $costo_spedizione,
            ]);
        }
        
        // Se mi arriva l'array con una sola scadenza, la forzo ad array multidimensionale, così entra nell'if successivo, ossia quello di "SCADENZE"
        if (!empty($data['scadenza'])) {
            $dati_scadenza = $data['scadenza'];
            $dati_scadenza['documenti_contabilita_scadenze_documento'] = $documento_id;
            unset($dati_scadenza['documenti_contabilita_scadenze_id']);
            
            $data['scadenze'] = [$dati_scadenza];
        }
        
        // Se mi arriva l'array di SCADENZE (plurale, quindi un array con più scadenze) le ciclo e le inserisco.
        if (!empty($data['scadenze'])) {
            foreach ($data['scadenze'] as $scadenza) {
                try {
                    $scadenza['documenti_contabilita_scadenze_documento'] = $documento_id;
                    
                    $this->apilib->create('documenti_contabilita_scadenze', $scadenza);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        } elseif (!empty($clienti_template_pagamento) && !empty($cliente[$clienti_template_pagamento])) { // se non ci sono scadenze manuali ma il cliente ha un template pagamento, allora sfrutto quello
            $template_pagamento = $cliente[$clienti_template_pagamento];
            
            //A questo punto verifico se ho un template di pagamento passato in ingresso (o forzato in base al cliente)
            //Mi costruisco un oggetto da riutilizzare per le scadenze automatiche
            //20230203 - MP - Perchè non WHERE il template è quello del cliente??? Vedi anche sotto l'if inutile...
            
            $template_scadenze = $this->apilib->search('documenti_contabilita_template_pagamenti', ['documenti_contabilita_template_pagamenti_id' => $template_pagamento]);
            
            foreach ($template_scadenze as $key => $tpl_scad) {
                //Riordino le sotto scadenze sul campo "giorni"
                usort($tpl_scad['documenti_contabilita_tpl_pag_scadenze'], function ($a, $b) {
                    return ($a['documenti_contabilita_tpl_pag_scadenze_giorni'] < $b['documenti_contabilita_tpl_pag_scadenze_giorni']) ? -1 : 1;
                });
                $template_scadenze[$key] = $tpl_scad;
            }
            
            foreach ($template_scadenze as $template_scadenza) {
                
                $residuo = $totale;
                $count_scadenze = count($template_scadenza['documenti_contabilita_tpl_pag_scadenze']);
                foreach ($template_scadenza['documenti_contabilita_tpl_pag_scadenze'] as $index => $tpl_pag_scadenza) {
                    // creo l'oggetto datetime partendo dalla data cadenza passata in post (se presente, altrimenti data emissione)
                    $dataScadenzaBaseObj = DateTime::createFromFormat('Y-m-d', (!empty($data['data_scadenza']) ? $data['data_scadenza'] : $data_emissione));
                    
                    if (!empty($tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_giorni'])) {
                        switch($tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_giorni']) {
                            // michael,matteo - 31/01/2024 - fatto questo fix in quanto c'erano casi in cui calcolava male il mese successivo. per info leggi qui: https://stackoverflow.com/questions/28351285/php-february-date-2015-01-31-1-month-2015-03-30-how-to-fix
                            case '30':
                            case '60':
                            case '90':
                            case '120':
                                for ( $i = 1; $i <= (int) ( $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_giorni'] / 30 ); $i++ ) {
                                    $dataScadenzaBaseObj->modify('last day of');
                                    $dataScadenzaBaseObj->modify('+1 day');
                                    $dataScadenzaBaseObj->modify('last day of');
                                }
                                break;
                            default:
                                $dataScadenzaBaseObj->modify("+{$tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_giorni']} day");
                                break;
                        }
                    }
                    switch ($tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_tipo_value']) {
                        case 'Fine mese':
                            // qui va la data fine mese (ossia se sono al 17/01, la data sarà 01/02...
                            // se c'è anche calcolo giorni (es 90), sarà 01/05)
                            $dataScadenzaBaseObj->modify('last day of');
                            break;
                        case 'Data Fattura':
                            $dataScadenzaBaseObj->modify('+' . $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_giorni'] . ' days');
                            break;
                        default:
                            // qui non modifico l'oggetto datetime
                            break;
                    }
                    
                    if (!empty($cliente['customers_pag_gg_spostamento']) && $cliente['customers_pag_gg_spostamento'] > 0) {
                        $dataScadenzaBaseObj->modify("+{$cliente['customers_pag_gg_spostamento']} days");
                    } elseif (!empty($cliente['customers_pag_giorno_fisso']) && $cliente['customers_pag_giorno_fisso'] > 0) {
                        $dataScadenzaBaseObj->setDate($dataScadenzaBaseObj->format('Y'), $dataScadenzaBaseObj->format('m'), $cliente['customers_pag_giorno_fisso']);
                    } else {
                    }
                    
                    $data_scadenza = $dataScadenzaBaseObj->format('Y-m-d H:i:s');
                    
                    /*
                    Questo è il codice js... la logica deve essere la stessa anche qua
                    if (percentuale == 100 && count(template_scadenza.documenti_contabilita_tpl_pag_scadenze) > 1) {
                        percentuale = 100/count(template_scadenza.documenti_contabilita_tpl_pag_scadenze);
                    }*/
                    if ($tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_percentuale'] == 100 && $count_scadenze > 1) {
                        $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_percentuale'] = 100 / $count_scadenze;
                    }

                    $ammontare = (($totale / 100) * $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_percentuale']);
                    //20230203 - MP - index non mi sembra definita... forse funziona ma volevo capire
                    if ($index + 1 >= $count_scadenze) {
                        $ammontare = $residuo;
                    } else {
                        $residuo -= $ammontare;
                    }
                    
                    $scadenza = [
                        'documenti_contabilita_scadenze_documento' => $documento_id,
                        'documenti_contabilita_scadenze_ammontare' => $ammontare,
                        'documenti_contabilita_scadenze_saldato_con' => $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_metodo'],
                        'documenti_contabilita_scadenze_saldata' => DB_BOOL_FALSE,
                        'documenti_contabilita_scadenze_utente_id' => $this->auth->get('users_id'),
                        'documenti_contabilita_scadenze_data_saldo' => null,
                        'documenti_contabilita_scadenze_scadenza' => $data_scadenza, //20230203 - MP - Questo è rischioso, visto per caso: diamo sempre nomi alle variabili a prova di "duplicato"... per fortuna sotto c'è un array che si chiama dati_scadenza che non centra niente con la "data" scadenza... per evitare darei nomi sicuri
                    ];
                    
                    try {
                        $this->apilib->create('documenti_contabilita_scadenze', $scadenza);
                    } catch (Exception $e) {
                        log_message('error', $e->getMessage());
                        throw new ApiException('Si è verificato un errore.');
                        exit;
                    }
                }
                
            }
        } else { // altrimenti creo la scadenza alla vecchia maniera...
            if (!empty($data['data_scadenza'])) {
                $data_scadenza = $data['data_scadenza'];
            } else {
                $data_scadenza = $data_emissione;
            }
            
            $dati_scadenza = [
                'documenti_contabilita_scadenze_documento' => $documento_id,
                'documenti_contabilita_scadenze_ammontare' => $dati_documento['documenti_contabilita_totale'],
                //'documenti_contabilita_scadenze_saldato_con' => $dati_documento['documenti_contabilita_metodo_pagamento'],
                'documenti_contabilita_scadenze_saldata' => array_get($data, 'saldato', DB_BOOL_FALSE),
                'documenti_contabilita_scadenze_utente_id' => $this->auth->get('users_id'),
                'documenti_contabilita_scadenze_data_saldo' => array_get($data, 'data_saldo', null),
                'documenti_contabilita_scadenze_scadenza' => $data_scadenza,
            ];
            
            try {
                
                $this->apilib->create('documenti_contabilita_scadenze', $dati_scadenza);
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                throw new ApiException('Si è verificato un errore.');
                exit;
            }
        }
        
        
        try {
            //20230203 - MP - Ho commentato questo che creava sempre un'altra scadenza...
            //$this->apilib->create('documenti_contabilita_scadenze', $dati_scadenza);
            
            if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $prefisso = 'IT' . $settings_db['documenti_contabilita_settings_company_vat_number'];
                if (!$documento['documenti_contabilita_nome_file_xml']) {
                    $xmlfilename = $this->generateXmlFilename($prefisso, $documento['documenti_contabilita_id']);
                } else {
                    $xmlfilename = $documento['documenti_contabilita_nome_file_xml'];
                }
                $this->docs->generate_xml($documento, $xmlfilename);
            } else {
                if ($dati_documento['documenti_contabilita_template_pdf']) {
                    $template = $this->apilib->view('documenti_contabilita_template_pdf', $dati_documento['documenti_contabilita_template_pdf']);
                    // Se caricato un file che contiene un html da priorità a quello
                    if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                        $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                    } else {
                        $content_html = $template['documenti_contabilita_template_pdf_html'];
                    }
                    
                    $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
                } else {
                    $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
                }
                // Storicizzo la copia di cortesia del PDF su file
                if (file_exists($pdfFile)) {
                    $pdf_file_name = $this->docs->generate_beautify_name($documento);
                    //debug($pdf_file_name,true);
                    $content = file_get_contents($pdfFile, true);
                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $filepath = $this->salva_file_fisico($pdf_file_name, $folder, $content);
                    $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_pdf' => $filepath]);
                }
                
                // Deprecato
                // if (file_exists($pdfFile)) {
                //     $contents = file_get_contents($pdfFile, true);
                //     $pdf_b64 = base64_encode($contents);
                //     $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_preview' => $pdf_b64]);
                // }
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }
        return $documento_id;
    }
    
    
    // Metodo specifico per l'import delle fatture di vendita
    public function doc_express_save_import(array $data = [])
    {
        extract($data);
        $mappature = $this->docs->getMappature();
        extract($mappature);
        if (array_key_exists('documenti_contabilita_destinatario', $data)) {
            $destinario = $data['documenti_contabilita_destinatario'];
            
        } elseif (!empty($cliente_id) || !empty($fornitore_id)) {
            $cliente = $this->apilib->view($entita_clienti, $cliente_id ?? $fornitore_id);
            $customer['ragione_sociale'] = (!empty($cliente[$clienti_ragione_sociale])) ? $cliente[$clienti_ragione_sociale] : $cliente[$clienti_nome] . ' ' . $cliente[$clienti_cognome];
            $customer['indirizzo'] = $cliente[$clienti_indirizzo];
            $customer['citta'] = $cliente[$clienti_citta];
            $customer['provincia'] = $cliente[$clienti_provincia];
            
            $nazione_id = $cliente[$clienti_nazione];
            $nazioni = $this->db->query("SELECT * FROM countries WHERE countries_id = '$nazione_id'");
            if ($nazioni->num_rows() > 0) {
                $customer['nazione'] = $nazioni->row()->countries_iso;
            }
            
            $customer['cap'] = $cliente[$clienti_cap];
            $customer['pec'] = $cliente[$clienti_pec];
            $customer['partita_iva'] = $cliente[$clienti_partita_iva];
            $customer['codice_fiscale'] = $cliente[$clienti_codice_fiscale];
            $customer['codice_sdi'] = $cliente[$clienti_codice_sdi];
            
            $destinario = json_encode($customer);
        } else {
            // die(json_encode(['status' => 0, 'txt' => 'Id cliente o fornitore non trovato.']));
            throw new ApiException('Id cliente o fornitore non trovato.');
            exit;
        }
        
        
        
        $azienda = array_get($data, 'azienda', $settings_db['documenti_contabilita_settings_id'] ?? null);
        
        $settings_db = $this->apilib->view('documenti_contabilita_settings', $azienda);
        
        if ($settings_db['documenti_contabilita_settings_serie_default']) {
            $serie_db = $this->apilib->view('documenti_contabilita_serie', $settings_db['documenti_contabilita_settings_serie_default']);
        } else {
            $serie_db = '';
        }
        
        if (array_get($data, 'documenti_contabilita_serie')) {
            $serie = array_get($data, 'documenti_contabilita_serie', null);
            $serie = str_replace(' ', '', $serie);
        } else {
            $serie = array_get($data, 'serie', $serie_db['documenti_contabilita_serie_value'] ?? null);
        }
        $totale = array_get($data, 'totale', 0);
        $costo_spedizione = array_get($data, 'costo_spedizione', false);
        $tipo_destinatario = array_get($data, 'tipo_destinatario', null);
        $tipo = array_get($data, 'tipo_documento', 1);
        
        $tpl_pdf = $this->db
            ->where("documenti_contabilita_template_pdf_tipo = '{$tipo}'")
            ->or_where("documenti_contabilita_template_pdf_default", DB_BOOL_TRUE)
            ->order_by("documenti_contabilita_template_pdf_tipo = '{$tipo}'", 'DESC')
            ->get("documenti_contabilita_template_pdf")->row_array();
        
        $tpl_pdf = array_get($data, 'template', $tpl_pdf['documenti_contabilita_template_pdf_id'] ?? null);
        
        $data_emissione = array_get($data, 'data_emissione', date('Y-m-d'));
        if (array_get($data, 'documenti_contabilita_data_emissione')) {
            $data_emissione = array_get($data, 'documenti_contabilita_data_emissione', date('Y-m-d'));
            
        }
        
        if (array_get($data, 'documenti_contabilita_numero')) {
            $numero_documento = array_get($data, 'documenti_contabilita_numero');
        } else {
            $numero_documento = $this->numero_sucessivo($azienda, $tipo, $serie, (DateTime::createFromFormat('Y-m-d', $data_emissione))->format('d/m/Y'));
        }
        
        $dati_documento = [
            'documenti_contabilita_numero' => $numero_documento,
            'documenti_contabilita_serie' => $serie,
            'documenti_contabilita_destinatario' => $destinario,
            'documenti_contabilita_customer_id' => $cliente_id,
            'documenti_contabilita_supplier_id' => $fornitore_id ?? null,
            'documenti_contabilita_data_emissione' => $data_emissione,
            //'documenti_contabilita_metodo_pagamento' => array_get($data, 'metodo_pagamento', 'carta di credito'),
            'documenti_contabilita_template_pagamento' => array_get($data, 'template_pagamento', null),
            'documenti_contabilita_tipo_destinatario' => $tipo_destinatario,
            'documenti_contabilita_azienda' => $azienda,
            'documenti_contabilita_utente_id' => array_get($data, 'utente', $this->auth->get('users_id')),
            'documenti_contabilita_template_pdf' => $tpl_pdf,
            'documenti_contabilita_stato' => array_get($data, 'stato', 1),
            'documenti_contabilita_codice_esterno' => array_get($data, 'codice_esterno', null),
        ];
        
        // Cerco se c'è un template di default uso quello altrimenti il primo che creato che si presume il generico
        if (empty($data['documenti_contabilita_template_pdf'])) {
            $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', ["documenti_contabilita_template_pdf_default" => DB_BOOL_TRUE], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
            if (empty($tpl_pdf_db)) {
                $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
            }
            $dati_documento['documenti_contabilita_template_pdf'] = $tpl_pdf;
        }
        
        $dati_documento['documenti_contabilita_oggetto'] = array_get($data, 'oggetto', null);
        $dati_documento['documenti_contabilita_note_interne'] = null;
        $dati_documento['documenti_contabilita_tipo'] = $tipo;
        $dati_documento['documenti_contabilita_valuta'] = array_get($data, 'valuta', 'EUR');
        $dati_documento['documenti_contabilita_tasso_di_cambio'] = null;
        $dati_documento['documenti_contabilita_conto_corrente'] = array_get($data, 'conto_corrente', null);
        $dati_documento['documenti_contabilita_formato_elettronico'] = array_get($data, 'formato_elettronico', DB_BOOL_FALSE);
        $dati_documento['documenti_contabilita_importata_da_xml'] = array_get($data, 'documenti_contabilita_importata_da_xml', DB_BOOL_FALSE);
        $dati_documento['documenti_contabilita_extra_param'] = array_get($data, 'documenti_contabilita_extra_param', null);
        //documenti_contabilita_extra_param
        $dati_documento['documenti_contabilita_rif_documento_id'] = array_get($data, 'documenti_contabilita_rif_documento_id', null); // 08-06 - DISATTIVATO IN QUANTO DIVENTA MULTIPLO
        $dati_documento['documenti_contabilita_da_sollecitare'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_tipologia_fatturazione'] = array_get($data, 'tipo_fatturazione', 1);
        $dati_documento['documenti_contabilita_rivalsa_inps_perc'] = null;
        $dati_documento['documenti_contabilita_stato_invio_sdi'] = array_get($data, 'stato_invio_sdi', 1);
        $dati_documento['documenti_contabilita_cassa_professionisti_perc'] = null;
        
        //Accompagnatoria/DDT
        $dati_documento['documenti_contabilita_fattura_accompagnatoria'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_n_colli'] = array_get($data, 'n_colli', null);
        $dati_documento['documenti_contabilita_peso'] = array_get($data, 'peso', null);
        $dati_documento['documenti_contabilita_volume'] = array_get($data, 'volume', null);
        $dati_documento['documenti_contabilita_targhe'] = null;
        $dati_documento['documenti_contabilita_descrizione_colli'] = array_get($data, 'descrizione_colli', null);
        $dati_documento['documenti_contabilita_luogo_destinazione'] = array_get($data, 'luogo_destinazione', null);
        $dati_documento['documenti_contabilita_trasporto_a_cura_di'] = array_get($data, 'vettore', null);
        $dati_documento['documenti_contabilita_causale_trasporto'] = null;
        $dati_documento['documenti_contabilita_annotazioni_trasporto'] = null;
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc'] = array_get($data, 'ritenuta_acconto_perc', null);
        $dati_documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'] = array_get($data, 'ritenuta_acconto_perc_imponibile', null);
        $dati_documento['documenti_contabilita_porto'] = null;
        $dati_documento['documenti_contabilita_vettori_residenza_domicilio'] = null;
        $dati_documento['documenti_contabilita_data_ritiro_merce'] = array_get($data, 'data_ritiro_merce', null);
        $dati_documento['documenti_contabilita_rif_ddt'] = null;
        $dati_documento['documenti_contabilita_codice_esterno'] = array_get($data, 'codice_esterno', null);
        $dati_documento['documenti_contabilita_tracking_code'] = array_get($data, 'tracking_code', null);
        
        // Attributi avanzati Fattura Elettronica
        $dati_documento['documenti_contabilita_fe_attributi_avanzati'] = DB_BOOL_FALSE;
        
        /*$json = [];
        if (!empty($input['documenti_contabilita_fe_rif_n_linea'])) {
        $json['RiferimentoNumeroLinea'] = $input['documenti_contabilita_fe_rif_n_linea'];
        }
        if (!empty($input['documenti_contabilita_fe_id_documento'])) {
        $json['IdDocumento'] = $input['documenti_contabilita_fe_id_documento'];
        }*/
        
        $dati_documento['documenti_contabilita_fe_attributi_avanzati_json'] = '';
        $dati_documento['documenti_contabilita_fe_dati_contratto'] = '';
        $dati_documento['documenti_contabilita_fe_ordineacquisto'] = '';
        
        //Pagamento
        $dati_documento['documenti_contabilita_accetta_paypal'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_split_payment'] = DB_BOOL_FALSE;
        
        $dati_documento['documenti_contabilita_centro_di_ricavo'] = array_get($data, 'centro_di_costo', null);
        
        $iva = [];
        $competenze = 0;
        $imponibile = 0;
        $imponibile_totale = [];
        $iva_tot = 0;
        //Natura
        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                if (empty($articolo['documenti_contabilita_articoli_riga_desc']) || $articolo['documenti_contabilita_articoli_riga_desc'] != DB_BOOL_TRUE) {
                    //
                    
                    /*
                    if (!empty($articolo['documenti_contabilita_articoli_iva_id'])) {
                    $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                    } else {
                    $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $articolo['documenti_contabilita_articoli_iva_perc']], null,'iva_order');
                    }
                    */
                    if (empty($articolo['documenti_contabilita_articoli_iva_id'])) {
                        $iva_prodotto = $this->apilib->searchFirst('iva', ['iva_valore' => $articolo['documenti_contabilita_articoli_iva_perc']], null, 'iva_order');
                    } else {
                        $iva_prodotto = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                    }
                    //
                    $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                    $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                    $importo = ($prezzo_unit * (int) $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int) $articolo['documenti_contabilita_articoli_sconto']);
                    $iva_valore = ($importo * $iva_perc) / 100;
                    
                    $iva[$iva_prodotto['iva_id']][] = $iva_valore;
                    $imponibile_totale[$iva_prodotto['iva_id']][] = $importo;
                    
                    
                    
                    $competenze += $importo;
                    $iva_tot += $iva_valore;
                    $imponibile += ($importo + $iva_valore); //MATTEO l'imponibile somma
                    
                }
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $articolo_dati) {
                $qty = $articolo_dati['qty'];
                
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);
                if (!empty($articolo_dati['prezzo_unit']) && is_numeric($articolo_dati['prezzo_unit'])) {
                    $prezzo_unit = $articolo_dati['prezzo_unit'];
                } else {
                    if (!empty($fornitore_id) && $campo_prezzo_fornitore && $articolo[$campo_prezzo_fornitore] != '0.00') {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                    } else {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                    }
                }
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);
                $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;
                
                $iva[$articolo['iva_valore']][] = $iva_valore;
                $imponibile_totale[$articolo['iva_valore']][] = $iva_valore;
                
                $competenze += $importo;
                $iva_tot += $iva_valore;
                $imponibile += ($importo + $iva_valore);
            }
        }
        //Formatto imponibile_totale
        foreach ($imponibile_totale as $key => $subarray) {
            $sum = array_sum($subarray);
            $imponibile_totale[$key] = [$sum];
            
        }
        
        $newArray = array();
        
        foreach ($imponibile_totale as $key => $value) {
            $id_iva = $this->apilib->searchFirst('iva', ['iva_id' => $key]);
            $newArray[(string) $key] = array((int) $id_iva['iva_valore'], $value[0]);
        }
        // Creazione dell'array associativo
        $assocArray = array();
        foreach ($newArray as $index => $value) {
            $assocArray[$index] = $value;
        }
        $data['imponibile_iva_json'] = json_encode($assocArray);
        
        //Formatto iva
        foreach ($iva as $key => $subarray) {
            $sum = array_sum($subarray);
            $iva[$key] = [$sum];
            
        }
        $newArray = array();
        
        foreach ($iva as $key => $value) {
            $id_iva = $this->apilib->searchFirst('iva', ['iva_id' => $key]);
            $newArray[(string) $key] = array((int) $id_iva['iva_valore'], $value[0]);
        }
        // Creazione dell'array associativo
        $assocArray = array();
        foreach ($newArray as $index => $value) {
            $assocArray[$index] = $value;
        }
        
        $data['iva_json'] = json_encode($assocArray);
        /*
        foreach ($iva as $key => $subarray) {
        $sum = array_sum($subarray);
        $iva[$key] = [$sum];
        if ($sum == 0) {
        unset($iva[$key]);
        }
        }*/
        
        if ($costo_spedizione) {
            $imponibile += $costo_spedizione;
            $competenze += $costo_spedizione;
        }
        $dati_documento['documenti_contabilita_sconto_percentuale'] = array_get($data, 'sconto_percentuale', null);
        //$dati_documento['documenti_contabilita_imponibile_scontato'] = array_get($data, 'imponibile_scontato', 0);
        //Importi
        $dati_documento['documenti_contabilita_imponibile'] = array_get($data, 'imponibile', $imponibile);
        if (array_get($data, 'importo_scontato')) {
            //se l'importo scontato
            //se l'importo scontato ed è impostato sconto su imponibile faccio come oggi
            if (array_get($data, 'documenti_contabilita_sconto_su_imponibile') == DB_BOOL_FALSE) {
                $dati_documento['documenti_contabilita_imponibile'] = array_get($data, 'documenti_contabilita_imponibile', $dati_documento['documenti_contabilita_imponibile']);
                $dati_documento['documenti_contabilita_imponibile_scontato'] = array_get($data, 'documenti_contabilita_imponibile_scontato', $dati_documento['documenti_contabilita_imponibile']);
            } else {
                $iva_tot = $iva_tot - ($iva_tot * $dati_documento['documenti_contabilita_sconto_percentuale'] / 100);
                $sconto_imponibile = $dati_documento['documenti_contabilita_imponibile'] * $dati_documento['documenti_contabilita_sconto_percentuale'] / 100;
                $dati_documento['documenti_contabilita_imponibile_scontato'] = $dati_documento['documenti_contabilita_imponibile'] - $sconto_imponibile;
                $dati_documento['documenti_contabilita_imponibile_scontato'] = number_format($dati_documento['documenti_contabilita_imponibile_scontato'], 2, '.', '');
                $imponibile = $dati_documento['documenti_contabilita_imponibile'] - $sconto_imponibile;
                $dati_documento['documenti_contabilita_imponibile'] = number_format($competenze - ($competenze * $dati_documento['documenti_contabilita_sconto_percentuale'] / 100), 2, '.', '');
                $dati_documento['documenti_contabilita_imponibile_scontato'] = $dati_documento['documenti_contabilita_imponibile'];
            }
            
        } else {
            
            $dati_documento['documenti_contabilita_imponibile'] = array_get($data, 'documenti_contabilita_imponibile', $dati_documento['documenti_contabilita_imponibile']);
            $dati_documento['documenti_contabilita_imponibile_scontato'] = array_get($data, 'documenti_contabilita_imponibile_scontato', $dati_documento['documenti_contabilita_imponibile']);
        }
        $dati_documento['documenti_contabilita_iva'] = array_get($data, 'iva', $iva_tot);
        $dati_documento['documenti_contabilita_competenze'] = array_get($data, 'competenze', $competenze);
        $dati_documento['documenti_contabilita_iva_json'] = array_get($data, 'iva_json', json_encode($iva));
        $dati_documento['documenti_contabilita_imponibile_iva_json'] = array_get($data, 'imponibile_iva_json', json_encode([]));
        $dati_documento['documenti_contabilita_totale'] = array_get($data, 'documenti_contabilita_totale', number_format($imponibile, 2, '.', ''));
        
        $dati_documento['documenti_contabilita_rivalsa_inps_valore'] = 0;
        $dati_documento['documenti_contabilita_competenze_lordo_rivalsa'] = 0;
        $dati_documento['documenti_contabilita_cassa_professionisti_valore'] = 0;
        $dati_documento['documenti_contabilita_ritenuta_acconto_valore'] = array_get($data, 'ritenuta_acconto_valore', 0);
        $dati_documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'] = array_get($data, 'ritenuta_acconto_imponibile_valore', 0);
        $dati_documento['documenti_contabilita_tipo_ritenuta'] = array_get($data, 'tipo_ritenuta', null);
        $dati_documento['documenti_contabilita_importo_bollo'] = 0;
        $dati_documento['documenti_contabilita_applica_bollo'] = DB_BOOL_FALSE;
        $dati_documento['documenti_contabilita_file_xml'] = array_get($data, 'documenti_contabilita_file_xml', null);
        $dati_documento['documenti_contabilita_nome_file_xml'] = array_get($data, 'documenti_contabilita_nome_file_xml', null);
        $dati_documento['documenti_contabilita_sconto_su_imponibile'] = array_get($data, 'documenti_contabilita_sconto_su_imponibile', 1);
        
        //$dati_documento['documenti_contabilita_importo_scontato'] =  array_get($data, 'importo_scontato', null);
        
        $dati_documento['documenti_contabilita_causale_pagamento_ritenuta'] = array_get($data, 'causale_pagamento_ritenuta', null);
        $dati_documento['documenti_contabilita_stato_pagamenti'] = array_get($data, 'stato_pagamenti', 1);
        try {
            $documento = $this->apilib->create('documenti_contabilita', $dati_documento);
            
            $documento_id = $documento['documenti_contabilita_id'];
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }
        
        if (!empty($articoli_data)) {
            foreach ($articoli_data as $articolo) {
                if (empty($articolo['documenti_contabilita_articoli_riga_desc']) || $articolo['documenti_contabilita_articoli_riga_desc'] != DB_BOOL_TRUE) {
                    $prezzo_unit = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_prezzo']);
                    $iva_perc = str_ireplace(',', '.', $articolo['documenti_contabilita_articoli_iva_perc']);
                    $importo = ($prezzo_unit * (int) $articolo['documenti_contabilita_articoli_quantita'] / 100) * (100 - (int) $articolo['documenti_contabilita_articoli_sconto']);
                    $iva_valore = ($importo * $iva_perc) / 100;
                    $iva[$articolo['documenti_contabilita_articoli_iva_perc']][] = $iva_valore;
                    //trovo il valore dell'iva, se ce ne sono di più, prendo la prima
                    if (empty($articolo['documenti_contabilita_articoli_iva_id'])) {
                        $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $iva_perc], null, 'iva_order');
                    } else {
                        $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                    }
                    
                    $articolo['documenti_contabilita_articoli_iva_id'] = $iva['iva_id'];
                    
                    
                    
                    
                    /*
                    if ($iva_perc == '0.00') {
                    //TODO: siamo sicuri di iva_codice???
                    $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                    } else {
                    $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $iva_perc]);
                    }
                    if (empty($articolo['documenti_contabilita_articoli_iva_id'])) {
                    $articolo['documenti_contabilita_articoli_iva_id'] = $iva['iva_id'];
                    }*/
                    $articolo['documenti_contabilita_articoli_iva'] = $iva_valore;
                    $competenze += $importo;
                    $iva_tot += $iva_valore;
                    $imponibile += ($importo + $iva_valore);
                    //vedo se è impostato lo sconto, lo flaggo, altrimenti no.
                    $articolo['documenti_contabilita_articoli_applica_sconto'] = isset($articolo['documenti_contabilita_articoli_applica_sconto']) && $articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_FALSE ? DB_BOOL_FALSE : DB_BOOL_TRUE;
                    //$articolo['documenti_contabilita_articoli_applica_sconto'] = DB_BOOL_TRUE;
                    $articolo['documenti_contabilita_articoli_applica_ritenute'] = isset($articolo['documenti_contabilita_articoli_applica_ritenute']) && $articolo['documenti_contabilita_articoli_applica_ritenute'] == DB_BOOL_FALSE ? DB_BOOL_FALSE : DB_BOOL_TRUE;
                    
                    
                    $articolo['documenti_contabilita_articoli_imponibile'] = $importo;
                    
                    //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                    $articolo['documenti_contabilita_articoli_importo_totale'] = ($importo + $iva_valore);
                    
                    
                }
                if (isset($articolo['documenti_contabilita_articoli_id'])) {
                    unset($articolo['documenti_contabilita_articoli_id']);
                }
                $articolo['documenti_contabilita_articoli_documento'] = $documento_id;
                try {
                    $this->apilib->create("documenti_contabilita_articoli", $articolo);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        } elseif (!empty($articoli)) {
            foreach ($articoli as $articolo_id => $articolo_dati) {
                $qty = $articolo_dati['qty'];
                unset($articolo_dati['qty']);
                $articolo = $this->apilib->searchFirst($entita_prodotti, [$campo_id_prodotto => $articolo_id]);
                
                if (!empty($articolo_dati['prezzo_unit']) && is_numeric($articolo_dati['prezzo_unit'])) {
                    $prezzo_unit = $articolo_dati['prezzo_unit'];
                } else {
                    if (!empty($fornitore_id) && $campo_prezzo_fornitore) {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_fornitore]);
                    } else {
                        $prezzo_unit = str_ireplace(',', '.', $articolo[$campo_prezzo_prodotto]);
                    }
                }
                unset($articolo_dati['prezzo_unit']);
                $iva_perc = str_ireplace(',', '.', $articolo['iva_valore']);
                
                // if ($iva_perc == '0.00') {
                //TODO: VEDI 20 righe sopra...
                //     $iva = $this->apilib->searchFirst('iva', ['iva_codice' => (string) $articolo['documenti_contabilita_articoli_iva_id']]);
                // } else {
                $iva = $this->apilib->searchFirst('iva', ['iva_valore' => $iva_perc]);
                
                // }
                
                $sconto = (!empty($articolo['documenti_contabilita_articoli_sconto']) ? $articolo['documenti_contabilita_articoli_sconto'] : 0);
                
                $importo = ($prezzo_unit * $qty / 100) * (100 - (int) $sconto);
                
                // $importo = $prezzo_unit * $qty;
                $iva_valore = ($importo * $iva_perc) / 100;
                
                $prodotto = [
                    'documenti_contabilita_articoli_documento' => $documento_id,
                    'documenti_contabilita_articoli_iva_id' => $iva['iva_id'],
                    'documenti_contabilita_articoli_name' => $articolo[$campo_preview_prodotto],
                    'documenti_contabilita_articoli_quantita' => $qty,
                    'documenti_contabilita_articoli_prodotto_id' => $articolo_id,
                    'documenti_contabilita_articoli_iva' => $iva_valore,
                    'documenti_contabilita_articoli_imponibile' => $importo,
                    'documenti_contabilita_articoli_prezzo' => $prezzo_unit,
                    'documenti_contabilita_articoli_codice' => $articolo[$campo_codice_prodotto],
                    'documenti_contabilita_articoli_iva_perc' => $iva_perc,
                    'documenti_contabilita_articoli_descrizione' => $articolo[$campo_descrizione_prodotto],
                    'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_TRUE,
                    //Attenzione che il totale non tiene conto di eventuali sconti al momento. Cambiare quando supportato lo sconto...
                    'documenti_contabilita_articoli_importo_totale' => ($importo + $iva_valore),
                ];
                $prodotto = array_merge($prodotto, $articolo_dati);
                //debug($prodotto, true);
                
                // dump($prodotto);
                
                try {
                    $this->apilib->create('documenti_contabilita_articoli', $prodotto);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage() . ' | json articolo: ' . json_encode($prodotto));
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        }
        
        if ($costo_spedizione) {
            $this->apilib->create("documenti_contabilita_articoli", [
                'documenti_contabilita_articoli_documento' => $documento_id,
                'documenti_contabilita_articoli_iva_id' => $iva_default_id,
                'documenti_contabilita_articoli_name' => 'Shipping cost',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_prodotto_id' => null,
                'documenti_contabilita_articoli_iva' => 0,
                'documenti_contabilita_articoli_imponibile' => $costo_spedizione,
                'documenti_contabilita_articoli_prezzo' => $costo_spedizione,
                'documenti_contabilita_articoli_codice' => '',
                'documenti_contabilita_articoli_iva_perc' => $iva_default_valore,
                'documenti_contabilita_articoli_descrizione' => '',
                'documenti_contabilita_articoli_importo_totale' => $costo_spedizione,
            ]);
        }
        
        if (!empty($data['scadenze'])) {
            foreach ($data['scadenze'] as $scadenza) {
                try {
                    $scadenza['documenti_contabilita_scadenze_documento'] = $documento_id;
                    
                    $this->apilib->create('documenti_contabilita_scadenze', $scadenza);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new ApiException('Si è verificato un errore.');
                    exit;
                }
            }
        } else if (!empty($clienti_template_pagamento) && !empty($cliente[$clienti_template_pagamento])) {
            //Mi costruisco un oggetto da riutilizzare per le scadenze automatiche
            
            
            //20230203 - MP - Perchè non WHERE il template è quello del cliente??? Vedi anche sotto l'if inutile...
            
            $template_scadenze = $this->apilib->search('documenti_contabilita_template_pagamenti');
            foreach ($template_scadenze as $key => $tpl_scad) {
                //Riordino le sotto scadenze sul campo "giorni"
                usort($tpl_scad['documenti_contabilita_tpl_pag_scadenze'], function ($a, $b) {
                    return ($a['documenti_contabilita_tpl_pag_scadenze_giorni'] < $b['documenti_contabilita_tpl_pag_scadenze_giorni']) ? -1 : 1;
                });
                $template_scadenze[$key] = $tpl_scad;
            }
            
            foreach ($template_scadenze as $template_scadenza) {
                if ($template_scadenza['documenti_contabilita_template_pagamenti_id'] == $cliente[$clienti_template_pagamento]) { //20230203 - MP - Questo è l'if inutile... bastava prendere solo il template del cliente
                    $residuo = $totale;
                    $count_scadenze = count($template_scadenza['documenti_contabilita_tpl_pag_scadenze']);
                    foreach ($template_scadenza['documenti_contabilita_tpl_pag_scadenze'] as $index => $tpl_pag_scadenza) {
                        switch ($tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_tipo_value']) {
                            case 'Fine mese':
                                // qui va la data fine mese (ossia se sono al 17/01, la data sarà 01/02...
                                // se c'è anche calcolo giorni (es 90), sarà 01/05)
                                $data_scadenza = (DateTime::createFromFormat('Y-m-d', (!empty($data['data_scadenza']) ? $data['data_scadenza'] : $data_emissione)))
                                    ->modify('+' . $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_giorni'] . ' days')
                                    ->modify('first day of next month')
                                    ->format('Y-m-d H:i:s');
                                break;
                            
                            case 'Data Fattura':
                            default:
                                // qui va data di oggi
                                $data_scadenza = (DateTime::createFromFormat('Y-m-d', (!empty($data['data_scadenza']) ? $data['data_scadenza'] : $data_emissione)))->format('Y-m-d H:i:s');
                                break;
                        }
                        
                        $ammontare = (($totale / 100) * $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_percentuale']);
                        //20230203 - MP - index non mi sembra definita... forse funziona ma volevo capire
                        if ($index + 1 >= $count_scadenze) {
                            $ammontare = $residuo;
                        } else {
                            $residuo -= $ammontare;
                        }
                        
                        $scadenza = [
                            'documenti_contabilita_scadenze_documento' => $documento_id,
                            'documenti_contabilita_scadenze_ammontare' => $ammontare,
                            'documenti_contabilita_scadenze_saldato_con' => $tpl_pag_scadenza['documenti_contabilita_tpl_pag_scadenze_metodo'],
                            'documenti_contabilita_scadenze_saldata' => DB_BOOL_FALSE,
                            'documenti_contabilita_scadenze_utente_id' => $this->auth->get('users_id'),
                            'documenti_contabilita_scadenze_data_saldo' => null,
                            'documenti_contabilita_scadenze_scadenza' => $data_scadenza, //20230203 - MP - Questo è rischioso, visto per caso: diamo sempre nomi alle variabili a prova di "duplicato"... per fortuna sotto c'è un array che si chiama dati_scadenza che non centra niente con la "data" scadenza... per evitare darei nomi sicuri
                        ];
                        
                        try {
                            $this->apilib->create('documenti_contabilita_scadenze', $scadenza);
                        } catch (Exception $e) {
                            log_message('error', $e->getMessage());
                            throw new ApiException('Si è verificato un errore.');
                            exit;
                        }
                    }
                }
            }
        } else {
            if (!empty($data['scadenza'])) {
                $dati_scadenza = $data['scadenza'];
                $dati_scadenza['documenti_contabilita_scadenze_documento'] = $documento_id;
                unset($dati_scadenza['documenti_contabilita_scadenze_id']);
            } else {
                if (!empty($data['data_scadenza'])) {
                    $data_scadenza = $data['data_scadenza'];
                } else {
                    $data_scadenza = $data_emissione;
                }
                
                $dati_scadenza = [
                    'documenti_contabilita_scadenze_documento' => $documento_id,
                    'documenti_contabilita_scadenze_ammontare' => $dati_documento['documenti_contabilita_totale'],
                    //'documenti_contabilita_scadenze_saldato_con' => $dati_documento['documenti_contabilita_metodo_pagamento'],
                    'documenti_contabilita_scadenze_saldata' => array_get($data, 'saldato', DB_BOOL_FALSE),
                    'documenti_contabilita_scadenze_utente_id' => $this->auth->get('users_id'),
                    'documenti_contabilita_scadenze_data_saldo' => array_get($data, 'data_saldo', null),
                    'documenti_contabilita_scadenze_scadenza' => $data_scadenza,
                ];
            }
            
            try {
                
                $this->apilib->create('documenti_contabilita_scadenze', $dati_scadenza);
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                throw new ApiException('Si è verificato un errore.');
                exit;
            }
        }
        
        try {
            //20230203 - MP - Ho commentato questo che creava sempre un'altra scadenza...
            //$this->apilib->create('documenti_contabilita_scadenze', $dati_scadenza);
            
            if (empty($dati_documento['documenti_contabilita_file_xml']) && $documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $prefisso = 'IT' . $settings_db['documenti_contabilita_settings_company_vat_number'];
                if (!$documento['documenti_contabilita_nome_file_xml']) {
                    $xmlfilename = $this->generateXmlFilename($prefisso, $documento['documenti_contabilita_id']);
                } else {
                    $xmlfilename = $documento['documenti_contabilita_nome_file_xml'];
                }
                $this->docs->generate_xml($documento, $xmlfilename);
            } else {
                if ($dati_documento['documenti_contabilita_template_pdf']) {
                    $template = $this->apilib->view('documenti_contabilita_template_pdf', $dati_documento['documenti_contabilita_template_pdf']);
                    // Se caricato un file che contiene un html da priorità a quello
                    if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                        $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                    } else {
                        $content_html = $template['documenti_contabilita_template_pdf_html'];
                    }
                    
                    $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
                } else {
                    $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
                }
                // Storicizzo la copia di cortesia del PDF su file
                if (file_exists($pdfFile)) {
                    $pdf_file_name = $this->docs->generate_beautify_name($documento);
                    //debug($pdf_file_name,true);
                    $content = file_get_contents($pdfFile, true);
                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $filepath = $this->salva_file_fisico($pdf_file_name, $folder, $content);
                    $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_pdf' => $filepath]);
                }
                
                // Deprecato
                // if (file_exists($pdfFile)) {
                //     $contents = file_get_contents($pdfFile, true);
                //     $pdf_b64 = base64_encode($contents);
                //     $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_preview' => $pdf_b64]);
                // }
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw new ApiException('Si è verificato un errore.');
            exit;
        }
        return $documento_id;
    }
    public function extractIbanData($iban)
    {
        // IT 94 P 02008 12310 000101714112
        $iban = str_ireplace(' ', '', $iban);
        return [
            'sigla' => substr($iban, 0, 2),
            'controllo' => substr($iban, 2, 2),
            'cin' => substr($iban, 4, 1),
            'abi' => substr($iban, 5, 5),
            'cab' => substr($iban, 10, 5),
            'cc' => substr($iban, 15),
        ];
    }
    public function formatDateFromGregorianFileName($filename, $format = 'd/m/Y')
    {
        // Estrai l'anno e il giorno giuliano usando regex
        $pattern = "/^.*\.(\d{4})(\d{3})\..*$/";
        preg_match($pattern, $filename, $matches);

        $anno = $matches[1];
        $giorno_giuliano = $matches[2];

        // Usa "Y z" con giorno giuliano 0-based
        $data = DateTime::createFromFormat('Y z', $anno . ' ' . ($giorno_giuliano - 1));

        return $data->format($format);
    }



    public function getArticoliFromDocumento($documento_id)
    {
        return $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id], null, 0, 'documenti_contabilita_articoli_position');
    }
    
    /**
     * @param int $documento_id
     * @param bool $return_data
     * @return array|string
     */
    public function getDocumentiCollegati(int $documento_id, bool $return_data = false)
    {
        // OTTENGO I DOCUMENTI COLLEGATI O AL PADRE O AL FIGLIO
        $documenti_collegati = $this->db
            ->where('rel_doc_contabilita_rif_documenti_padre', $documento_id)
            ->or_where('documenti_contabilita_id', $documento_id)
            ->get("rel_doc_contabilita_rif_documenti")->result_array();
        
        $html = '';
        $data = [];
        // LI CICLO
        foreach ($documenti_collegati as $documento_collegato) {
            // E NE OTTENGO I DETTAGLI
            if ($documento_collegato['rel_doc_contabilita_rif_documenti_padre'] == $documento_id) {
                $altro_documento = $this->db
                    ->join('documenti_contabilita_tipo', 'documenti_contabilita_tipo = documenti_contabilita_tipo_id', 'left')
                    ->where('documenti_contabilita_id', $documento_collegato['documenti_contabilita_id'])
                    ->get('documenti_contabilita')->row_array();
            } else {
                $altro_documento = $this->db
                    ->join('documenti_contabilita_tipo', 'documenti_contabilita_tipo = documenti_contabilita_tipo_id', 'left')
                    ->where('documenti_contabilita_id', $documento_collegato['rel_doc_contabilita_rif_documenti_padre'])
                    ->get('documenti_contabilita')->row_array();
            }
            
            if (!empty($altro_documento)) {
                $tipo = $altro_documento['documenti_contabilita_tipo_value'];
                $numero = $altro_documento['documenti_contabilita_numero'];
                $serie = (!empty($altro_documento['documenti_contabilita_serie'])) ? "/ {$altro_documento['documenti_contabilita_serie']}" : '';
                
                $html .= "{$tipo}: {$numero} {$serie}<br/>";
                $data[] = $altro_documento;
            }
        }
        
        // RITORNO POI I DATI IN BASE A SE VOGLIO L'HTML O L'ARRAY PURO
        return $return_data ? $data : $html;
    }
    
    public function associaDocumenti($documento_padre, $documenti_figli)
    {
        foreach ($documenti_figli as $documento_figlio) {
            // 08-05-2023 rif_doc diventa multplo
            // Imposto documento padre nella relazione
            $this->db->insert('rel_doc_contabilita_rif_documenti', [
                'documenti_contabilita_id' => $documento_figlio,
                'rel_doc_contabilita_rif_documenti_padre' => $documento_padre,
            ]);
        }
    }
    
    public function calcolaQuantitaEvasaDoc($documenti_contabilita_articoli_rif_riga_articolo)
    {
        $riga_articolo = $this->db
            ->join('documenti_contabilita', 'documenti_contabilita_articoli_documento = documenti_contabilita_id', 'LEFT')
            ->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $documenti_contabilita_articoli_rif_riga_articolo])
            ->row_array();
        //debug($riga_articolo,true);
        if (!$riga_articolo) {
            return 0;
        }
        $quantita_in_padri = $this->db->query("
            SELECT SUM(documenti_contabilita_articoli_quantita) AS s 
            FROM documenti_contabilita_articoli 
            LEFT JOIN documenti_contabilita ON (documenti_contabilita_id = documenti_contabilita_articoli_documento)
            WHERE 
                documenti_contabilita_articoli_rif_riga_articolo = '{$riga_articolo['documenti_contabilita_articoli_id']}'
                AND
                documenti_contabilita_tipo <> '{$riga_articolo['documenti_contabilita_tipo']}'
                AND documenti_contabilita_tipo NOT IN (4,11,12,6)
                ")->row()->s; //I documenti di tipo nota di credito, fattura reverse e nota di credito reverse non vanno a chiudere un ordine cui son legati. Nemmeno gli ordini fornitore!!!
        
        if ($this->datab->module_installed('magazzino')) {
            $this->load->model('magazzino/mov');
            $quantita_in_movimenti = $this->mov->calcolaQuantitaEvasa($riga_articolo['documenti_contabilita_articoli_id']);
        } else {
            $quantita_in_movimenti = 0;
        }
        
        //Aggiorno
        $this->apilib->edit('documenti_contabilita_articoli', $documenti_contabilita_articoli_rif_riga_articolo, [
            'documenti_contabilita_articoli_qty_evase_in_doc' => $quantita_in_padri,
            'documenti_contabilita_articoli_qty_movimentate' => $quantita_in_movimenti,
        ]);
        return $quantita_in_padri + $quantita_in_movimenti;
    }
    public function calcolaQuantitaEvase($documento_id)
    {
        $righe_articolo = $this->db
            ->join('documenti_contabilita', 'documenti_contabilita_id = documenti_contabilita_articoli_documento', 'LEFT')
            ->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id])
            ->result_array();
        
        foreach ($righe_articolo as $riga) {
            if (in_array($riga['documenti_contabilita_tipo'], [5, 1, 8])) { //Solo ordini di tipo ordine_cliente, fattura o ddt cliente evadono la merce dei padri... serve per evitare che un ordine a fornitore da ordini clienti vada ad evadere gli ordini cliente stessi
                //Se il documento è stato generato da un'ordine cliente (o più ordini cliente), marco quella riga come "fattura_evasa"...
                if ($riga['documenti_contabilita_articoli_rif_riga_articolo']) {
                    //Calcolo la quantità di merce evasa
                    $quantita_evase = $this->calcolaQuantitaEvasaDoc($riga['documenti_contabilita_articoli_rif_riga_articolo']);
                }
            }
            
        }
        
    }
    
    public function aggiornaStatoDocumento($documento_id, $old_documento_tipo = false)
    {
        //$documento = $this->apilib->view('documenti_contabilita', $documento_id);
        $documento = $this->db->get_where('documenti_contabilita', ['documenti_contabilita_id' => $documento_id])->row_array();
        $documento_tipo = $documento['documenti_contabilita_tipo'];
        
        
        // debug($documento_tipo);
        // debug($old_documento_tipo,true);
        
        if ($old_documento_tipo != false && $documento_tipo == $old_documento_tipo) {
            //Se ho ad esempio duplicato un documento dello stesso tipo, non devo aggiornare il padre... si tratta di una banale duplicazione e non di un evasione...
            return $documento['documenti_contabilita_stato'];
        }
        
        $righe_articolo = $this->db->get_where('documenti_contabilita_articoli', [
            'documenti_contabilita_articoli_documento' => $documento_id
        ])->result_array();
        
        $stato = 1; //Di default aperto
        foreach ($righe_articolo as $key => $riga) {
            if (empty($riga['documenti_contabilita_articoli_prodotto_id'])) {
                unset($righe_articolo[$key]);
            }
            
            //Calcolo la quantità di merce evasa
            $quantita_evase = $this->calcolaQuantitaEvasaDoc($riga['documenti_contabilita_articoli_id']);
            
            if ($quantita_evase >= $riga['documenti_contabilita_articoli_quantita']) {
                unset($righe_articolo[$key]);
            } elseif ($quantita_evase) {
                $stato = 2; //Parziale
            }
        }
        if (empty($righe_articolo)) {
            $stato = 3; //Chiuso
        }
        
        
        
        $this->apilib->edit('documenti_contabilita', $documento_id, ['documenti_contabilita_stato' => $stato]);
        return $stato;
    }
    public function getPdfFormatoCompatto($documento)
    {
        $basepath = FCPATH . '/uploads/';
        $view_content = file_get_contents($basepath . $documento['documenti_contabilita_file_xml']);
        // URL del file XSL
        $xslUrl = APPPATH . "modules/contabilita/assets/fattura-compatta.xsl";
        
        // XML da una variabile
        $xmlString = $view_content;
        
        // Carica il file XSL
        $xsl = new DOMDocument();
        $xsl->load($xslUrl);
        
        // Carica il file XML dalla variabile
        $xml = new DOMDocument();
        $xml->loadXML($xmlString);
        
        // Crea il trasformatore XSLT
        $processor = new XSLTProcessor();
        $processor->importStylesheet($xsl);
        
        // Esegui la trasformazione XSLT in HTML
        $html = $processor->transformToXML($xml);
        
        $pdfFile = $this->layout->generate_pdf($html, 'portrait', "", [], false, true, ["-T '5mm' -B '5mm' --disable-external-links"]);
        
        return $pdfFile;
    }
    
    public function getPdfTemplate($documento, $template)
    {
        if (!empty($template['documenti_contabilita_template_pdf_file_html'])) {
            $html = file_get_contents(FCPATH . 'uploads/' . $template['documenti_contabilita_template_pdf_file_html']);
        } elseif (!empty($template['documenti_contabilita_template_pdf_html'])) {
            $html = $template['documenti_contabilita_template_pdf_html'];
        }
        
        $pdfFile = $this->layout->generate_pdf($html, "portrait", "", ['documento_id' => $documento['documenti_contabilita_id']], 'contabilita', true);
        
        return $pdfFile;
    }
}