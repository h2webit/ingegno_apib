<?php


class Main extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('openapi-integration/openapi');
    }

    // ------------------- VISURE E BILANCI --------------------------------

    public function acquista_bilancio()
    {
        $piva = $this->input->post("piva");
        $servizio_id = $this->input->post("servizio_id");
        $azienda_id = $this->input->post("azienda_id");
        if ($piva && $servizio_id && $azienda_id) {
            $output = $this->openapi->richiesta_visure_bilanci($piva, $servizio_id,$azienda_id);
            echo $output;
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Inserire la P.IVA, l\'azienda ed il servizio richiesto'));
        }
    }

    // ------------------- BUSINESS INFORMATION -----------------------------

    // Cerca info dettagliate dati extra per una impresa
    public function cerca_impresa_advanced()
    {
        $piva_cf = $this->input->post("piva_cf");

        if ($piva_cf) {
            $output = $this->openapi->cerca_impresa_advanced($piva_cf);

            echo $output;
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Inserire la P.IVA'));
        }
    }

    // Ricerca tabulati imprese
    public function ricerca_imprese()
    {
        $ricerca = $this->input->post("data");
        $servizio_id = $this->input->post("servizio_id");

        if ($ricerca && $servizio_id) {
            $output = $this->openapi->ricerca_tabulati_imprese($ricerca, $servizio_id);
            echo $output;
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Ricerca o servizio mancante.'));
        }
    }

    // Cerca azienda data la partita iva
    public function cerca_cliente_piva()
    {
        $piva_cf = $this->input->post("piva_cf");

        $servizio_id = "";

        if ($piva_cf) {
            $output = $this->openapi->cerca_cliente_piva($piva_cf);

            echo $output;
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Inserire la P.IVA o il Codice fiscale'));
        }
    }

    // Creazione anagrafica dopo ricerca impresa
    public function crea_anagrafica()
    {
        $data = $this->input->post('data');

        $cliente = [
            'customers_zip_code' => $data['cap'],
            'customers_city' => $data['comune'],
            'customers_sdi' => $data['codice_destinatario'],
            'customers_last_name' => '',
            'customers_description' => 'Anagrafica creata da ricerca',
            'customers_address' => $data['indirizzo'],
            'customers_province' => $data['provincia'],
            'customers_company' => $data['denominazione'],
            'customers_type' => '1', // Customer
            'customers_vat_number' => $data['piva'],
            'customers_cf' => $data['cf'],
            'customers_group' => 2,
            'customers_country_id' => 105, // italia
        ];

        //verifico se esiste già qualche cliente:
        $esistente = $this->apilib->searchFirst('customers', ['customers_vat_number' => $data['piva'], 'customers_type' => '1']);
        if (!empty($esistente)) {
            echo json_encode(array('success' => false, 'message' => "cliente già esistente"));
            exit;
        }
        $output = $this->apilib->create('customers', $cliente, false);

        if (is_integer($output)) {
            echo json_encode(array('success' => true, 'message' => base_url('main/layout/customer-detail/' . $output)));
        } else {
            echo json_encode(array('success' => false, 'message' => $output));
        }
    }

    // Aggiorna anagrafica con dati advanced
    public function aggiorna_anagrafica($customer_id)
    {
        $data = $this->input->post('data');
        $full = $this->input->post('full_data');

        if ($full == 1) {
            // Anagrafica base
            $cliente = [
                'customers_zip_code' => $data['cap'],
                'customers_city' => $data['comune'],
                'customers_sdi' => $data['codice_destinatario'],
                'customers_address' => $data['indirizzo'],
                'customers_province' => $data['provincia'],
                'customers_company' => $data['denominazione'],
                //'customers_type' => '1', // Customer
                'customers_group' => 2,
                'customers_cf' => $data['cf'],
                'customers_pec' => $data['dettaglio']['pec'],
            ];

            $this->apilib->edit('customers', $customer_id, $cliente);
        }
        // Creazione o aggiornamento dati extra
        $extra = [
            'customers_dati_extra_bilanci' => json_encode($data['dettaglio']['bilanci']),
            'customers_dati_extra_cciaa' => $data['dettaglio']['cciaa'],
            'customers_dati_extra_codice_ateco' => $data['dettaglio']['codice_ateco'],
            'customers_dati_extra_descrizione_ateco' => $data['dettaglio']['descrizione_ateco'],
            'customers_dati_extra_data_inizio_attivita' => $data['dettaglio']['data_inizio_attivita'],
            'customers_dati_extra_rea' => $data['dettaglio']['rea'],
            'customers_dati_extra_openapi_id' => $data['id'],
        ];
        $check = $this->db->query("SELECT * FROM customers_dati_extra WHERE customers_dati_extra_customer_id = '{$customer_id}'")->row_array();
        if (!empty($check['customers_dati_extra_id'])) {
            $output = $this->apilib->edit('customers_dati_extra', $check['customers_dati_extra_id'], $extra);
        } else {
            $extra['customers_dati_extra_customer_id'] = $customer_id;
            $output = $this->apilib->create('customers_dati_extra', $extra);
        }


        echo json_encode(array('success' => true, 'message' => ''));
    }


    // ------------------------ SERVIZI POSTALI -------------------------

    // Metodo che aggiorna gli stati delle letter einviate
    public function aggiorna_stati_lettere() // TODO CRON
    {
        $output = $this->openapi->get_lettere('ordinarie');
        debug($output);
    }

    public function spedisci_lettera($lettera_id)
    {
        if (empty($lettera_id) || !intval($lettera_id)) {
            die('Request error, id missed');
        }

        $data = $this->apilib->view('openapi_lettere', $lettera_id);

        // Check balance
        if ($this->openapi->check_balance($data['openapi_lettere_costo']) !== true) {
            echo json_encode(array("success" => false, "message"=>"Non hai credito a sufficienza per questo acquisto."));
            return false;
        }

        switch ($data['openapi_lettere_tipo']) {
            // Posta ordinaria
            case 3:
                $output = $this->openapi->request_invio_lettere($data, "ordinarie");
                break;

            // Raccomandata A/R
            case 2:
                $output = $this->openapi->request_invio_lettere($data, "raccomandate");
                break;
            
            // Posta prioritaria
            case 4:
                $output = $this->openapi->request_invio_lettere($data, "prioritarie");
                break;

            default:
                 echo "Type ".$data['openapi_lettere_tipo']." not found";
                 return false;
            break;
        }

        
        // Check output, addebito e cambio stato
        $json_output = json_decode($output, true);
        if ($json_output['success'] !== false) {

            // Cambio stato
            $update['openapi_lettere_stato'] = 2; // Stato richiesta inviata.
            $update['openapi_lettere_openapi_id'] = $json_output['data'][0]['id'];
            $update['openapi_lettere_output_log'] = $output;

            $this->db->where('openapi_lettere_id', $lettera_id)->update('openapi_lettere', $update); // Volutamente non passa per Apilib per non triggerare events

            // Addebito
            $this->openapi->addebito($data['openapi_lettere_costo'], $data['openapi_lettere_tipologie_nome'], $lettera_id);

            echo $output;
        } else {
            echo $output;
        }
    }
    // acquista visiura
    public function acquista_visura()
    {
        $piva = $this->input->post("piva");
        $servizio_id = $this->input->post("servizio_id");
        $societa = $this->input->post("societa");
        $azienda_id = $this->input->post("azienda_id");
        if ($piva && $servizio_id) {
            $output = $this->openapi->richiesta_visure($piva, $servizio_id, $societa, $azienda_id);
            echo $output;
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Inserire la P.IVA ed il servizio richiesto'));
        }
    }
    
    // Scarica visura   
    public function scarica_visura_singola()
    {   
        $query = $this->db->query("SELECT *  FROM openapi_ricerche_bilanci WHERE openapi_ricerche_bilanci_hash_visura = '0' AND openapi_ricerche_bilanci_id_documento !='0' AND openapi_ricerche_bilanci_documento_link = '0'")->result_array();
        foreach ($query as $visura) {
            try {
                $query_servizi = $this->db->query("SELECT *  FROM openapi_servizi WHERE openapi_servizi_id = ".$visura['openapi_ricerche_bilanci_tipologia']."")->result_array();
                $output = $this->openapi->scarico_documento_visura($visura['openapi_ricerche_bilanci_id_documento'],$query_servizi[0]['openapi_servizi_endpoint_production']);
                $json_output = json_decode($output, true);
                print_r($output);
                if($json_output['success']=='true'){
                    $array_update = [
                        'openapi_ricerche_bilanci_documento_link' => $json_output['file']
                        ];        
                    $this->apilib->edit('openapi_ricerche_bilanci', $visura['openapi_ricerche_bilanci_id'], $array_update);
                }
            }
            catch (Exception $e) {
                echo "errore";
            }
        }
    }
    public function download_bilancio()
    {   
        $query = $this->db->query("SELECT *  FROM openapi_ricerche_bilanci WHERE openapi_ricerche_bilanci_hash_visura != '0' AND openapi_ricerche_bilanci_id_documento !='0' AND openapi_ricerche_bilanci_documento_link = '0'")->result_array();
        foreach ($query as $bilancio) {
            try {
                $query_servizi = $this->db->query("SELECT *  FROM openapi_servizi WHERE openapi_servizi_hash = '".$bilancio['openapi_ricerche_bilanci_hash_visura']."'")->result_array();
                $output = $this->openapi->scarico_documento_bilancio($bilancio['openapi_ricerche_bilanci_id_documento'],$query_servizi[0]['openapi_servizi_endpoint_production']);
                $json_output = json_decode($output, true);
                if($json_output['success']=='true'){
                    $array_update = [
                        'openapi_ricerche_bilanci_documento_link' => $json_output['file']
                        ];        
                    $this->apilib->edit('openapi_ricerche_bilanci', $bilancio['openapi_ricerche_bilanci_id'], $array_update);
                }
            }
            catch (Exception $e) {
                echo "errore";
            }
        }
    }

    /**
     * @param $documento_id
     * @return string
     *
     */
    public function invia_xml($documento_id) {
        log_message("debug",  "invio documento #$documento_id a openapi");
        
        // Verifica se il modulo 'contabilita' è installato, altrimenti termina con un messaggio di errore.
        if (!$this->datab->module_installed('contabilita')) {
            die(e_json(['status' => 3, 'txt' => "Modulo INGEGNO Office non risulta installato."]));
        }
        
        // Recupera le informazioni del documento dalla tabella 'documenti_contabilita' usando l'ID fornito.
        $documento = $this->db->get_where("documenti_contabilita", ['documenti_contabilita_id' => $documento_id])->row_array();
        
        // Se il documento non è stato trovato, termina con un messaggio di errore.
        if (empty($documento)) {
            die(e_json(['status' => 3, 'txt' => "Documento non trovato."]));
        }
        
        // Recupera le informazioni dell'azienda dal database.
        $azienda = $this->db->get_where('documenti_contabilita_settings', ['documenti_contabilita_settings_id' => $documento['documenti_contabilita_azienda']])->row_array();
        
        // Verifica che le informazioni dell'azienda siano complete, altrimenti termina con un messaggio di errore.
        if (empty($azienda) || empty($azienda['documenti_contabilita_settings_company_vat_number']) || empty($azienda['documenti_contabilita_settings_company_name']) || empty($azienda['documenti_contabilita_settings_smtp_mail_from'])) {
            log_message("error",  "Modulo Ingegno Office non configurato correttamente");
            die(e_json(['status' => 3, 'txt' => "Modulo Ingegno Office non configurato correttamente"]));
        }
        
        // Verifica se l'invio SDI OpenApi è attivo per l'azienda, altrimenti termina con un messaggio di errore.
        if (empty($azienda['documenti_contabilita_settings_invio_sdi_openapi_attivo']) || $azienda['documenti_contabilita_settings_invio_sdi_openapi_attivo'] !== DB_BOOL_TRUE) {
            log_message("error",  "Invio SDI OpenApi non attivo");
            die(e_json(['status' => 3, 'txt' => "Invio SDI OpenApi non attivo"]));
        }
        
        // Verifica se il file XML del documento esiste, altrimenti termina con un messaggio di errore.
        if (empty($documento['documenti_contabilita_file_xml']) || !file_exists(FCPATH . "uploads/{$documento['documenti_contabilita_file_xml']}")) {
            log_message("error", "File XML non presente a sistema!");
            die(e_json(['status' => 3, 'txt' => "File XML non presente a sistema!"]));
        }
        
        // Legge il contenuto del file XML dal percorso specificato.
        $xml = file_get_contents(FCPATH . "uploads/{$documento['documenti_contabilita_file_xml']}");
        
        // Invia il contenuto XML tramite l'API OpenApi e ottiene la risposta.
        $response = $this->openapi->invia_xml($xml, $azienda);
        
        // Se la risposta va in errore, gestire ulteriormente l'azione.
        if (!$response['success'] || empty($response['data']['uuid'])) {
            // Se la risposta indica errore, termina con un messaggio di errore.
            log_message("error", "Errore invio xml SDI OPENAPI: " . $response['message']);
            
            $this->apilib->edit('documenti_contabilita', $documento_id, ['documenti_contabilita_stato_invio_sdi' => '4']);
            
            die(e_json(['status' => 3, 'txt' => "Si è verificato un problema durante l'invio del documento: ". $response['message']]));
        }
        
        $oa_invoice = $this->openapi->get_invoice($response['data']['uuid'], $azienda);
        
        $oa_invoice_xml = $oa_invoice['data']['sdi_file_name'];
        
        // Salvo il UUID sul documento
        $this->apilib->edit('documenti_contabilita', $documento_id, [
            'documenti_contabilita_uuid_openapi' => $response['data']['uuid'],
            'documenti_contabilita_stato_invio_sdi' => '5',
            'documenti_contabilita_nome_file_xml' => $oa_invoice_xml,
        ]);
        
        // Se tutto è andato a buon fine, restituisci un messaggio di successo.
        e_json(['status' => 4, 'txt' => "Documento inviato correttamente al sistema SDI OPENAPI"]);
    }
    
    /**
     * @param $uuid
     * @return void
     */
    public function get_invoice($documento_id = null) {
        if (!$documento_id) die(e_json(['status' => 3, 'txt' => 'ID documento non fornito']));
        
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        
        if (empty($documento)) {
            die(e_json(['status' => 3, 'txt' => 'Documento non trovato.']));
        }
        
        if (empty($documento['documenti_contabilita_uuid_openapi'])) {
            die(e_json(['status' => 3, 'txt' => 'Questo documento non ha un ID OpenAPI']));
        }

        $res = $this->openapi->get_invoice($documento['documenti_contabilita_uuid_openapi']);
        
        debug($res, true);
    }
    
    public function get_invoice_notifications($documento_id = null, $html = false) {
        if (!$documento_id) {
            if ($html) {
                throw new ApiException('ID documento non fornito');
            }
            e_json(['status' => 0, 'txt' => 'ID documento non fornito']);
            return false;
        }
        
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        
        if (empty($documento)) {
            if ($html) {
                throw new ApiException('Documento non trovato.');
            }
            
            e_json(['status' => 0, 'txt' => 'Documento non trovato.']);
            return false;
        }
        
        if (empty($documento['documenti_contabilita_uuid_openapi'])) {
            if ($html) {
                throw new ApiException('Questo documento non ha un ID OpenAPI');
            }
            
            e_json(['status' => 0, 'txt' => 'Questo documento non ha un ID OpenAPI']);
            return false;
        }
        
        $type = 'json';
        if (!empty($this->input->get('xml')) && $this->input->get('xml') == '1') {
            $type = 'xml';
        }
        
        $azienda = $this->db->get_where('documenti_contabilita_settings', ['documenti_contabilita_settings_id' => $documento['documenti_contabilita_azienda']])->row_array();
        
        $res = $this->openapi->get_invoice_notifications($documento['documenti_contabilita_uuid_openapi'], $azienda, $type);
        
        if ($html) {
            echo '<pre>' . var_export( ($type == 'json' ? json_decode($res, true) : $res) , true) . '</pre>';
        } else {
            e_json(['status' => 1, 'txt' => $res]);
        }
        return true;
    }

    public function verifica_eu_vat()
    {
        $piva = $this->security->xss_clean($this->input->post("piva_cf"));
        $nazione = $this->security->xss_clean($this->input->post("nazione"));

        if (empty($piva)) die(e_json(['status' => 3, 'txt' => "Partita iva non impostata sull'anagrafica cliente"]));
        if (empty($nazione)) die(e_json(['status' => 3, 'txt' => "Nazione non impostata sull'anagrafica cliente"]));

        $res = $this->openapi->verifica_eu_vat($piva, $nazione);

        if (empty($res) || (!empty($res) && $res['success'] == false) || empty($res['data'])) {
            die(e_json(['status' => 3, 'txt' => "ANOMALIA DA OPENAPI: " . $res['message']]));
        }

        $res_txt = "<h4>Dati ricevuti</h4>" . PHP_EOL;
        if ($res['data']['valid'] == DB_BOOL_TRUE) {
            $res_txt .= 'La partita iva risulta <b>valida <i class="fas fa-tick fa-fw text-success"></i></b>' . PHP_EOL;
        } else {
            $res_txt .= 'La partita iva risulta <b>non valida <i class="fas fa-times fa-fw text-danger"></i></b>' . PHP_EOL;
        }

        if ($res['data']['format_valid'] == DB_BOOL_TRUE) {
            $res_txt .= 'Il formato della partita iva risulta <b>valido <i class="fas fa-tick fa-fw text-success"></i></b>' . PHP_EOL;
        } else {
            $res_txt .= 'Il formato della iva risulta <b>non valido <i class="fas fa-times fa-fw text-danger"></i></b>' . PHP_EOL;
        }

        if (!empty($res['data']['company_name'])) {
            $res_txt .= "Ragione Sociale: <b>{$res['data']['company_name']}</b>".PHP_EOL;
        }

        if (!empty($res['data']['company_address'])) {
            $res_txt .= "Indirizzo: <b>{$res['data']['company_address']}</b>".PHP_EOL;
        }

        die(e_json(['status' => 6, 'txt' => nl2br($res_txt)]));
    }
}
