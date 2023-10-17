
<?php


class Openapi extends CI_Model
{
    public $openapi_settings;
    private $api_key;

    public function __construct()
    {
        parent::__construct();

        $this->openapi_settings = $this->apilib->searchFirst('openapi_settings');

        if ($this->openapi_settings['openapi_settings_mode'] == 1) {
            $this->api_key = $this->openapi_settings['openapi_settings_production_key'];
        } else {
            $this->api_key = $this->openapi_settings['openapi_settings_sandbox_key'];
        }

        if (empty($this->api_key)) {
            return "Missing OpenAPI Configuration";
        }
    }

    // +++++++++++++++++++++++++++++++++++++ CACHE +++++++++++++++++++++++++++++++

    public function save_to_cache($servizio_id, $url, $post, $output)
    {
        // Salvo solo in caso di produzione
        if ($this->openapi_settings['openapi_settings_mode'] != 1) {
            return true;
        }

        // Se esiste un altro record identico evito duplicati
        $check = $this->db->where('openapi_cache_url', $url)->where('openapi_cache_output', $output)->get('openapi_cache');
        if ($check->num_rows() > 0) {
            return false;
        }

        $data['openapi_cache_url'] = $url;
        $data['openapi_cache_servizio'] = $servizio_id;
        $data['openapi_cache_post'] = $post;
        $data['openapi_cache_output'] = $output;

        $this->apilib->create('openapi_cache', $data);
    }


    // +++++++++++++++++++++++++++++++++++++ BALANCE SALDO +++++++++++++++++++++++++++++++

    public function get_balance()
    {
        return number_format($this->openapi_settings['openapi_settings_saldo'], 2, ",", ".");
    }

    public function check_balance($importo)
    {
        if ($this->openapi_settings['openapi_settings_saldo'] >= $importo) {
            return true;
        } else {
            return false;
        }
    }

    public function addebito($importo, $servizio = null, $servizio_id = null)
    {

        // Se modalita diversa da production non esegue l'addebito
        if ($this->openapi_settings['openapi_settings_mode'] != 1) {
            return true;
        }

        if ($this->openapi_settings['openapi_settings_saldo'] >= $importo) {
            // Saldo su settings
            $nuovo_saldo = $this->openapi_settings['openapi_settings_saldo'] - $importo;
            $this->apilib->edit('openapi_settings', $this->openapi_settings['openapi_settings_id'], ['openapi_settings_saldo' => $nuovo_saldo]);

            // Storico movimenti
            $movimento = array('openapi_movimenti_credito_tipo' => 1, 'openapi_movimenti_credito_importo' => $importo, 'openapi_movimenti_credito_servizio' => $servizio, 'openapi_movimenti_credito_servizio_id' => $servizio_id);
            $this->apilib->create('openapi_movimenti_credito', $movimento);
            return true;
        } else {
            return false;
        }
    }

    public function accredito($importo)
    {
        $nuovo_saldo = $this->openapi_settings['openapi_settings_saldo'] + $importo;
        $this->apilib->edit('openapi_settings', $this->openapi_settings['openapi_settings_id'], ['openapi_settings_saldo' => $nuovo_saldo]);
    }




    // --------------------------------- BUSINESS INFORMATION CERCA IMPRESE --------------------------------

    // Ricerca tabulati aziende
    public function ricerca_tabulati_imprese($ricerca, $id_servizio)
    {
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();
        // Check balance
        if ($servizio['openapi_servizi_costo_chiamata'] > 0 && $this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
            return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
        }

        // Check attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo'));
        }

        $ricerca = json_decode($ricerca, true);
        $parametri['provincia'] = $ricerca['openapi_ricerce_imprese_provincia'];
        $parametri['codice_ateco'] = $ricerca['openapi_ricerce_imprese_codice_ateco'];
        $parametri['fatturato_min'] = $ricerca['openapi_ricerce_imprese_fatturato_min'];
        $parametri['fatturato_max'] = $ricerca['openapi_ricerce_imprese_fatturato_max'];
        $parametri['dipendenti_min'] = $ricerca['openapi_ricerce_imprese_dipendenti_min'];
        $parametri['dipendenti_max'] = $ricerca['openapi_ricerce_imprese_dipendenti_max'];
        $parametri['limit'] = $ricerca['openapi_ricerce_imprese_limite_risultati'];

        if ($ricerca['openapi_ricerce_imprese_coordinate']) {
            $coordinate = explode(';', $ricerca['openapi_ricerce_imprese_coordinate']);
            $parametri['lat'] = $coordinate[0];
            $parametri['lng'] = $coordinate[1];
            $parametri['radius'] = $ricerca['openapi_ricerce_imprese_raggio'];
        }
        $url = $this->getServizioUrl($servizio);
        $url = $url . "?" . http_build_query($parametri);

        $json_output = $this->sendGetCurl($url);
        // Addebito
        $output = json_decode($json_output, true);
        if(!empty($output)){
        //print_r($output);
            if ($output['success'] !== false) {
                $output_array = json_decode($json_output, true);

                if ($servizio['openapi_servizi_costo_chiamata'] > 0) {
                    // Calcolo costo in base ai risultati ottenuti
                    $risultati = count($output_array['data']);
                    $costo = $servizio['openapi_servizi_costo_chiamata'] * $risultati;
                    $this->addebito($costo, $servizio['openapi_servizi_nome']);
                }
                // Salvo i dati
                foreach ($output_array['data'] as $risultato) {
                    $new['openapi_ricerche_risultati_ricerca'] = $ricerca['openapi_ricerce_imprese_id'];
                    $new['openapi_ricerche_risultati_openapi_id'] = $risultato['id'];
                    $new['openapi_ricerche_risultati_ragione_sociale'] = $risultato['denominazione'];
                    $new['openapi_ricerche_risultati_comune'] = $risultato['comune'];
                    $new['openapi_ricerche_risultati_risposta'] = json_encode($risultato, true);
                    //verifico che non fosse già presente l'id di openapi, in quel caso aggiorno la riga.
                    $azienda = $this->db->query("SELECT * FROM openapi_ricerche_risultati WHERE openapi_ricerche_risultati_openapi_id = '".$risultato['id']."' AND openapi_ricerche_risultati_ricerca = '".$ricerca['openapi_ricerce_imprese_id']."'")->row_array();
                    if($azienda){
                        $risposta_attuale = json_decode($azienda['openapi_ricerche_risultati_risposta'], true);
                        //se ho fatto una ricerca gratuita, ed esiste già la key dettaglio nella risposta, NON devo aggiornare la risposta.
                        if(array_key_exists("dettaglio",$risposta_attuale) AND $id_servizio = '6'){
                            $this->apilib->edit('openapi_ricerche_risultati', $azienda['openapi_ricerche_risultati_id'], [
                                'openapi_ricerche_risultati_ricerca' => $ricerca['openapi_ricerce_imprese_id'],
                                'openapi_ricerche_risultati_openapi_id' => $risultato['id'],
                                'openapi_ricerche_risultati_ragione_sociale' => $risultato['denominazione'],
                                'openapi_ricerche_risultati_comune' => $risultato['comune'],
                            ]);
                        } else {
                            $this->apilib->edit('openapi_ricerche_risultati', $azienda['openapi_ricerche_risultati_id'], [
                                'openapi_ricerche_risultati_risposta' => $new['openapi_ricerche_risultati_risposta'],
                                'openapi_ricerche_risultati_ricerca' => $ricerca['openapi_ricerce_imprese_id'],
                                'openapi_ricerche_risultati_openapi_id' => $risultato['id'],
                                'openapi_ricerche_risultati_ragione_sociale' => $risultato['denominazione'],
                                'openapi_ricerche_risultati_comune' => $risultato['comune'],
                            ]);
                        }
                    }
                    else {
                        $this->apilib->create('openapi_ricerche_risultati', $new);
                    }
                }
            }
        }
        else {
            if(!isset($output['message'])){
                $output['message'] = 'Nessun risultato trovato';
            }
            return json_encode(array("success" => false, "message" => $output['message']));
        }
        // Save to cache
        return $json_output;    
    }

    // Cerca dati extra aziendali
    public function cerca_impresa_advanced($piva_cf)
    {
        $id_servizio = 3; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();

        // Check balance
        if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
            return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
        }

        // Check attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo'));
        }

        $url = $this->getServizioUrl($servizio);
        $url = $url . $piva_cf;
        $json_output = $this->sendGetCurl($url);

        // Addebito
        $output = json_decode($json_output, true);
        if ($output['success'] !== false) {
            $this->addebito($servizio['openapi_servizi_costo_chiamata'], $servizio['openapi_servizi_nome']);

            // Salvo il dato grezzo anche in caso di esito negativo
            $this->openapi->save_to_cache($id_servizio, $url, $piva_cf, $json_output);
        }

        // Save to cache
        return $json_output;
    }

    // Cerca cliente da piva o cf
    public function cerca_cliente_piva($piva_cf)
    {
        $id_servizio = 1; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();

        // Check balance
        if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
            return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
        }

        // Check attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo'));
        }

        $url = $this->getServizioUrl($servizio);
        $url = $url . $piva_cf;

        $json_output = $this->sendGetCurl($url);

        // Addebito
        $output = json_decode($json_output, true);
        if ($output['success'] !== false) {
            $this->addebito($servizio['openapi_servizi_costo_chiamata'], $servizio['openapi_servizi_nome']);

            // Salvo il dato grezzo anche in caso di esito negativo
            $this->openapi->save_to_cache($id_servizio, $url, $piva_cf, $json_output);
        }

        // Save to cache
        return $json_output;
    }



    // ------------------------------------- VISURE E BILANCI ---------------------------------------------------

    public function richiesta_visure_bilanci($piva, $id_servizio,$id_azienda)
    {
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();
        
        // Check balance
        if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
            return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
        }
        // Check attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo.'));
        }

        $url = $this->getServizioUrl($servizio);
        // prendo dati azienda
        $customer = $this->db->get_where('customers', "customers_id = $id_azienda")->row_array();
        if(strlen($customer['customers_province'])>2){
            $provincia = $this->trova_provincia($customer['customers_province']);
            if(!$provincia){
                return json_encode(array('success' => false, 'message' => 'Provincia non trovata, controllare e riprovare.'));
            }
        }
        else {
            $provincia = $customer['customers_province'];
        }

        $post['hash_visura'] = $servizio['openapi_servizi_hash'];
        //$post['json_visura'] = array("$1" => "RM", "$0" => "12485671007");
        if ($this->openapi_settings['openapi_settings_mode'] == 1) {
            $post['json_visura'] = array("$1" => $provincia, "$0" => $piva);
        }else{
            $post['json_visura'] = array("$1" => "RM", "$0" => "12485671007");
        }
        $output = $this->sendPostCurl($url, json_encode($post));

        $array_risposta = json_decode($output, true);
        // se tutto ok, addebito
        if ($array_risposta['success'] !== false) {

            if ($servizio['openapi_servizi_costo_chiamata'] > 0) {
                // Addebito
                $this->addebito($servizio['openapi_servizi_costo_chiamata'], $servizio['openapi_servizi_nome']);
            }

        }
        else{
            return json_encode(array('success' => false, 'message' => $array_risposta['message']));
        }
        //se ho cercato bilanci, trovo gli indici
        if($id_servizio=='4' OR $id_servizio=='16'){
            if(!$array_risposta['data']['ricerche'][0]['json_risultato']){
                return json_encode(array('success' => false, 'message' => 'ambiente di test, impossibile proseguire.'));
            }
            else {
                $elenco_bilanci = json_decode($array_risposta['data']['ricerche'][0]['json_risultato'], true);
                //da prove fatte l'ultimo bilancio è il primo nell'array
                $ultimo_bilancio = array_key_first($elenco_bilanci);
                $indice_bilancio = substr($ultimo_bilancio, -1);
                
                $ricerca_id = $array_risposta['data']['ricerche'][0]['id_ricerca'];
                $id = $array_risposta['data']['_id'];
                //ora faccio get richiesta id
                $json_output1 = $this->sendGetCurl($url.$id);
                $post_value['id_ricerca'] = $ricerca_id;
                $post_value['indice'] = $indice_bilancio;
                $url = $url.$array_risposta['data']['_id']."/ricerche";
                //ora faccio put richiesta id ricerche
                $output1 = $this->sendPutCurl($url, json_encode($post_value));
                $array_risposta1 = json_decode($output1, true);
                $id_documento = $array_risposta1['data']['_id'];
                // Salvo il dato grezzo
                $this->openapi->save_to_cache($id_servizio, $url, $piva, $json_output1);
            }
        } else{
            $id_documento = $array_risposta['data']['_id'];
        }
        //ora salvo il nome del file nel db per scaricarlo dopo.
        //la risposta serve effettivamente?
        if ($array_risposta['success'] !== false) {
            
            $new['openapi_ricerche_bilanci_tipologia'] = $id_servizio;
            $new['openapi_ricerche_bilanci_azienda'] = $id_azienda;
            $new['openapi_ricerche_bilanci_hash_visura'] = $array_risposta['data']['hash_visura'];
            $new['openapi_ricerche_bilanci_risposta'] = $array_risposta['data']['ricerche'][0]['json_risultato'];
            $new['openapi_ricerche_bilanci_id_documento'] = $id_documento;
            $new['openapi_ricerche_bilanci_documento_link'] = 0;
            $new['openapi_ricerche_bilanci_openapi_id_azienda'] = 0;
            $this->apilib->create('openapi_ricerche_bilanci', $new);
        }
        return $output;
    }


    public function richiesta_visure($piva, $id_servizio, $societa, $azienda_id)
    {
        $new['openapi_ricerche_bilanci_documento_link'] = 0;
        $new['openapi_ricerche_bilanci_openapi_id_azienda'] = '';
        //verifico se si tratta di società di capitale o persone
        if ($societa != '') {
            //$url = '';
            //ho la società, quindi non è impresa individuale, ricerco con lo strumento gratuito se è capitale o persone
            $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '6'")->row_array(); 
                // Check balance
            if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
                return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
            }
            $url = $this->getServizioUrl($servizio);
            $societa = str_replace(' ', '%20', $societa);
            $url = $url."?denominazione=".$societa;
            $post['cf_piva_id'] = $societa;
            $json_output = $this->sendGetCurl($url);
            $output = json_decode($json_output, true);
            if ($output['success'] === false) {
                return json_encode(array('success' => false, 'message' => $output['message']));
            }
            // se più valori, prendo quello con comune uguale al nostro
            foreach ($output['data'] as $risultato) {
                $customer = $this->db->get_where('customers', "customers_id = $azienda_id")->row_array();
                $customer['customers_city'] = 'Roma';
                if (strtolower($customer['customers_city'])  == strtolower($risultato['comune'])) {
                    $new['openapi_ricerche_bilanci_openapi_id_azienda'] = $risultato['id'];
                    $new['openapi_ricerche_bilanci_azienda'] = $azienda_id;
                    foreach ($risultato['chiamate_disponibili'] as $chiamate) {
                        if ($id_servizio == '11') {
                            // visura ordinaria
                            if (strpos($chiamate, 'ordinaria') !== false) {
                                $url = $chiamate;
                                if (strpos($url, 'persone') !== false) {
                                    //ordinaria di persone
                                    $new['openapi_ricerche_bilanci_tipologia'] = 14;
                                }else{
                                    //ordinaria di capitali
                                    $new['openapi_ricerche_bilanci_tipologia'] = 11;
                                }
                                break;
                            }
                        } else {
                            if (strpos($chiamate, 'storica') !== false) {
                                $url = $chiamate;
                                if (strpos($url, 'persone') !== false) {
                                    //storica di persone
                                    $new['openapi_ricerche_bilanci_tipologia'] = 13;
                                }else{
                                    //storica di capitali
                                    $new['openapi_ricerche_bilanci_tipologia'] = 12;
                                }
                                break;
                            }
                        }
                    }
                }
            }
            if ($new['openapi_ricerche_bilanci_openapi_id_azienda'] == '') {
                return json_encode(array('success' => false, 'message' => 'Nessuna visura trovata per questa azienda'));
            }
            else {
                $servizio_id =  $new['openapi_ricerche_bilanci_tipologia'];
                $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$servizio_id'")->row_array(); 
                if ($servizio['openapi_servizi_costo_chiamata'] > 0) {
                    // Addebito costo
                    $this->addebito($servizio['openapi_servizi_costo_chiamata'], $servizio['openapi_servizi_nome']);
                }
            }
            //$new['openapi_ricerche_visure_visura_tipologia'] = $id_servizio;
            $url = "https://".$url;
            
            $post['cf_piva_id'] = $new['openapi_ricerche_bilanci_openapi_id_azienda'];
            $json_output1 = $this->sendPostCurl($url, json_encode($post));
            $output_array1 = json_decode($json_output1, true);
            if ($output_array1['success'] !== false) {
                $new['openapi_ricerche_bilanci_id_documento'] = $output_array1['data']['id'];
                $output_array1 = json_decode($json_output1, true);
                $risposta_json=json_encode($output_array1['data']);
                $new['openapi_ricerche_bilanci_risposta'] = $risposta_json;
                $new['openapi_ricerche_bilanci_hash_visura'] = 0;
                $this->openapi->save_to_cache($id_servizio, $url, $piva, $json_output1);
                $this->apilib->create('openapi_ricerche_bilanci', $new);
            }
        } else {
            //libero professionista
            $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = $id_servizio")->row_array();
            // Check balance
            if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
                return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
            }
            $url = $this->getServizioUrl($servizio);
            $url_originale = $this->getServizioUrl($servizio);
            if ($id_servizio == 9) {
                //visura ordinaria
                $url = $url . "/ordinaria-impresa-individuale";
            } elseif ($id_servizio == 10) {
                //visura storica
                $url = $url . "/storica-impresa-individuale";
            }
            $post['cf_piva_id'] = $piva;
            $json_output = $this->sendPostCurl($url, json_encode($post));
            $output = json_decode($json_output, true);
            if ($output['success'] === false) {
                return json_encode(array('success' => false, 'message' => $output['message']));
            }
            else {
                if ($servizio['openapi_servizi_costo_chiamata'] > 0) {
                    // Calcolo costo in base ai risultati ottenuti
                    $risultati = count($output['data']);
                    $costo = $servizio['openapi_servizi_costo_chiamata'] * $risultati;
                    $this->addebito($costo, $servizio['openapi_servizi_nome']);
                }
            }
            $new['openapi_ricerche_bilanci_openapi_id_azienda'] = $output['data']['id'];
            $new['openapi_ricerche_bilanci_id_documento'] = $output['data']['id'];
            $customer = $this->db->get_where('customers', "customers_id = $azienda_id")->row_array();
            $new['openapi_ricerche_bilanci_azienda'] = $azienda_id;
            $new['openapi_ricerche_bilanci_tipologia'] = $id_servizio;
            $url = $url . "/" . $new['openapi_ricerche_bilanci_openapi_id_azienda'];
            $json_output1 = $this->sendGetCurl($url);
            $output_array1 = json_decode($json_output1, true);
            if ($output_array1['success'] !== false) {
                $new['openapi_ricerche_bilanci_id_documento'] = $output_array1['data']['id'];
                $output_array1 = json_decode($json_output1, true);
                $risposta_json=json_encode($output_array1['data']);
                $new['openapi_ricerche_bilanci_risposta'] = $risposta_json;
                $new['openapi_ricerche_bilanci_hash_visura'] = 0;
                $this->openapi->save_to_cache($id_servizio, $url, $piva, $json_output1);
                $this->apilib->create('openapi_ricerche_bilanci', $new);
            }
        }
        return $json_output1;
    }

    //-------------------------------------- SERVIZI POSTALI ----------------------------------------------------------

    // Metodo che aggiorna gli stati delle lettere inviate
    public function get_lettere($type)
    {
        $id_servizio = 2; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();

        // Check attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo.'));
        }

        // Definizione endopoint url
        $url = $this->getServizioUrl($servizio);
        $url = $url . $type . "/";

        return $this->sendGetCurl($url);
    }


    // Metodo che invia una lettera
    public function request_invio_lettere($data, $type)
    {
        $id_servizio = 2; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();

        // Check attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo.'));
        }


        // Definizione endopoint url
        $url = $this->getServizioUrl($servizio);
        $url = $url . $type . "/";


        // Destinatario e mittente
        $destinatari[] = json_decode($data['openapi_lettere_destinatario_json'], true);
        $mittente = json_decode($data['openapi_lettere_mittente_json'], true);

        // Opzioni
        $opzioni['fronteretro'] = "false";
        $opzioni['colori'] = "false";
        $opzioni['autoconfirm'] = "false";
        $opzioni['ricevuta'] = "false";

        // $opzioni['callback_url'] = null; // "example.com/status_updates",
        // $opzioni['callback_field'] = null; // "data

        // Only Raccomandata
        if ($type == "raccomandate") {
            $opzioni['ar'] = true;
            //$opzioni['ragione_sociale'] = $mittente['ragione_sociale']; // TODO: Manca documentazione in merito
        }

        $document_content = file_get_contents(FCPATH . "uploads/" . $data['openapi_lettere_documento']);
        //$document_content = file_get_contents("https://crm.h2web.it/uploads/" . $data['openapi_lettere_documento']); // Per test locale

        $documento = "data:application/pdf;base64," . base64_encode($document_content);

        // Post data
        $post['mittente'] = $mittente;
        $post['opzioni'] = $opzioni;
        $post['destinatari'] = $destinatari;
        $post['documento'] = $documento;
        $output = $this->sendPostCurl($url, json_encode($post));

        return $output;
    }

    public function verifica_eu_vat($piva, $nazione)
    {
        $id_servizio = 18; // TODO: Soluzione non definitiva

        $servizio = $this->db->where('openapi_servizi_id', $id_servizio)->get('openapi_servizi')->row_array();

        if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
            return json_encode(array("success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."));
        }

        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return json_encode(array('success' => false, 'message' => 'Servizio momentaneamente non attivo'));
        }

        $url = $this->getServizioUrl($servizio);

        $json_output = $this->sendGetCurl($url . $nazione . "/" . $piva);

        $output = json_decode($json_output, true);
        if ($output['success'] !== false) {
            $this->addebito($servizio['openapi_servizi_costo_chiamata'], $servizio['openapi_servizi_nome']);

            $this->openapi->save_to_cache($id_servizio, $url, $piva, $json_output);
        }

        return $output;
    }



    // ************************************ PRIVATE FUNCTIONS **********************************

    private function getServizioUrl($servizio)
    {
        if ($this->openapi_settings['openapi_settings_mode'] == 1) {
            $url = $servizio['openapi_servizi_endpoint_production'];
        } else {
            $url = $servizio['openapi_servizi_endpoint_sandbox'];
        }
        return $url;
    }

    private function saveLog($url, $post_json = null, $output = null, $error = null)
    {
        $log['openapi_calls_log_mode'] = $this->openapi_settings['openapi_settings_mode'];
        $log['openapi_calls_log_balance'] = $this->openapi_settings['openapi_settings_saldo'];

        $log['openapi_calls_log_endpoint'] = $url;
        $log['openapi_calls_log_post_data'] = $post_json;
        $log['openapi_calls_log_error'] = $error;
        $log['openapi_calls_log_output'] = $output;

        $this->apilib->create('openapi_calls_log', $log);
    }

    private function sendPostCurl($url, $post_json = null, $content_type = 'application/json')
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_json,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->api_key}",
                "Content-type: {$content_type}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // Log
        $this->saveLog($url, $post_json, $response, $err);

        if ($err) {
            log_message('error', $err);
            return false;
        } else {
            return $response;
        }
    }

    private function sendGetCurl($url, $custom_header = [])
    {
        $header = [
            "Authorization: Bearer {$this->api_key}",
        ];

        if (!empty($custom_header)) {
            $header = array_merge($header, $custom_header);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $header,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // Log
        $this->saveLog($url, null, $response, $err);

        if ($err) {
            log_message('error', $err);
            return false;
        } else {
            return $response;
        }
    }

    private function sendPutCurl($url, $post_json = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $post_json,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->api_key}",
                "Content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // Log
        $this->saveLog($url, $post_json, $response, $err);

        if ($err) {
            log_message('error', $err);
            return false;
        } else {
            return $response;
        }
    }

        // ************************************ SCARICO DOCUMENTI **********************************

    public function scarico_documento_visura($id_visura, $tipologia)
    {
        $url = $tipologia . "/" . $id_visura . "/allegati";
        $json_output = $this->sendGetCurl($url);
        $output = json_decode($json_output, true);
        if ($output['success'] != 'true') {
            json_encode(['status' => 0, 'success' => $output['success']]);
        } else {
            $file = base64_decode($output['data']['file']);
            $filename = $output['data']['nome'];
            if (!is_dir(FCPATH . "uploads/open_api/documenti/")) {
                mkdir(FCPATH . "uploads/open_api/documenti/", DIR_WRITE_MODE, true);
            }
            $path = FCPATH . 'uploads/open_api/documenti/' . $filename;
            file_put_contents($path, $file);
            return json_encode(array('status' => 1, 'success' => $output['success'], 'file' => $filename));
        }
    }
    public function scarico_documento_bilancio($id_file,$link)
    {   
        $link = str_replace("richiesta/","documento/",$link);
        $url = $link."".$id_file;
        $json_output = $this->sendGetCurl($url);
        $output = json_decode($json_output, true);
        if ($output['success'] != 'true') {
            return json_encode(['status' => 0, 'success' => $output['success']]);
        } else {
            $file = base64_decode($output['data']['file']);
            $filename = $output['data']['nome'];
            if (!is_dir(FCPATH . "uploads/open_api/documenti/")) {
                mkdir(FCPATH . "uploads/open_api/documenti/", DIR_WRITE_MODE, true);
            }
            $path = FCPATH . 'uploads/open_api/documenti/' . $filename;
            file_put_contents($path, $file);
            return json_encode(array('status' => 1, 'success' => $output['success'], 'file' => $filename));
        }
    }
    public function trova_provincia($provincia)
    {
        $province= array( 
            'AG' => 'Agrigento',
            'AL' => 'Alessandria',
            'AN' => 'Ancona',
            'AO' => 'Aosta',
            'AR' => 'Arezzo',
            'AP' => 'Ascoli Piceno',
            'AT' => 'Asti',
            'AV' => 'Avellino',
            'BA' => 'Bari',
            'BT' => 'Barletta-Andria-Trani',
            'BL' => 'Belluno',
            'BN' => 'Benevento',
            'BG' => 'Bergamo',
            'BI' => 'Biella',
            'BO' => 'Bologna',
            'BZ' => 'Bolzano',
            'BS' => 'Brescia',
            'BR' => 'Brindisi',
            'CA' => 'Cagliari',
            'CL' => 'Caltanissetta',
            'CB' => 'Campobasso',
            'CE' => 'Caserta',
            'CT' => 'Catania',
            'CZ' => 'Catanzaro',
            'CH' => 'Chieti',
            'CO' => 'Como',
            'CS' => 'Cosenza',
            'CR' => 'Cremona',
            'KR' => 'Crotone',
            'CN' => 'Cuneo',
            'EN' => 'Enna',
            'FM' => 'Fermo',
            'FE' => 'Ferrara',
            'FI' => 'Firenze',
            'FG' => 'Foggia',
            'FC' => 'Forlì-Cesena',
            'FR' => 'Frosinone',
            'GE' => 'Genova',
            'GO' => 'Gorizia',
            'GR' => 'Grosseto',
            'IM' => 'Imperia',
            'IS' => 'Isernia',
            'SP' => 'La Spezia',
            'AQ' => 'L\'Aquila',
            'LT' => 'Latina',
            'LE' => 'Lecce',
            'LC' => 'Lecco',
            'LI' => 'Livorno',
            'LO' => 'Lodi',
            'LU' => 'Lucca',
            'MC' => 'Macerata',
            'MN' => 'Mantova',
            'MS' => 'Massa-Carrara',
            'MT' => 'Matera',
            'ME' => 'Messina',
            'MI' => 'Milano',
            'MO' => 'Modena',
            'MB' => 'Monza e della Brianza',
            'NA' => 'Napoli',
            'NO' => 'Novara',
            'NU' => 'Nuoro',
            'OR' => 'Oristano',
            'PD' => 'Padova',
            'PA' => 'Palermo',
            'PR' => 'Parma',
            'PV' => 'Pavia',
            'PG' => 'Perugia',
            'PU' => 'Pesaro e Urbino',
            'PE' => 'Pescara',
            'PC' => 'Piacenza',
            'PI' => 'Pisa',
            'PT' => 'Pistoia',
            'PN' => 'Pordenone',
            'PZ' => 'Potenza',
            'PO' => 'Prato',
            'RG' => 'Ragusa',
            'RA' => 'Ravenna',
            'RC' => 'Reggio Calabria',
            'RE' => 'Reggio Emilia',
            'RI' => 'Rieti',
            'RN' => 'Rimini',
            'RM' => 'Roma',
            'RO' => 'Rovigo',
            'SA' => 'Salerno',
            'SS' => 'Sassari',
            'SV' => 'Savona',
            'SI' => 'Siena',
            'SR' => 'Siracusa',
            'SO' => 'Sondrio',
            'SU' => 'Sud Sardegna',
            'TA' => 'Taranto',
            'TE' => 'Teramo',
            'TR' => 'Terni',
            'TO' => 'Torino',
            'TP' => 'Trapani',
            'TN' => 'Trento',
            'TV' => 'Treviso',
            'TS' => 'Trieste',
            'UD' => 'Udine',
            'VA' => 'Varese',
            'VE' => 'Venezia',
            'VB' => 'Verbano-Cusio-Ossola',
            'VC' => 'Vercelli',
            'VR' => 'Verona',
            'VV' => 'Vibo Valentia',
            'VI' => 'Vicenza',
            'VT' => 'Viterbo',
        );
        $key = array_search ($provincia, $province);
        if ($key != '') {
            return $key;
        }
        else {
            return false;
        }
    }
    
    private function verificaBusinessRegistry ($url, $azienda) {
        $res = $this->sendGetCurl($url . "/business_registry_configurations/{$azienda['documenti_contabilita_settings_company_vat_number']}", ["Accept: application/json"]);
        $business_registry_config = json_decode($res, true);
        
        if ($business_registry_config['data'] == null) {
            $business_config = json_encode([
                'fiscal_id' => $azienda['documenti_contabilita_settings_company_vat_number'],
                'name' => $azienda['documenti_contabilita_settings_company_name'],
                'email' => $azienda['documenti_contabilita_settings_smtp_mail_from'],
                'apply_signature' => true, // @todo parametrizzare su riga servizio
                'apply_legal_storage' => true, // @todo parametrizzare su riga servizio
            ]);
            
            $res = $this->sendPostCurl($url . '/business_registry_configurations', $business_config);
            $business_registry = json_decode($res, true);
            if ($business_registry['success'] == false) {
                return $business_registry['message'];
            } else {
                $this->db->update('openapi_settings', ['openapi_settings_skip_business_registry_check' => DB_BOOL_TRUE]);
                return true;
            }
        } else if (!empty($business_registry_config['data']) && $business_registry_config['data']['active'] == false) {
            return "OPENAPI SDI: Business Registry disattivato.";
        } else {
            $this->db->update('openapi_settings', ['openapi_settings_skip_business_registry_check' => DB_BOOL_TRUE]);
            return true;
        }
    }

    public function invia_xml($xml, $azienda) {
        $id_servizio = 17; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();

        // Check balance
        if ($this->openapi->check_balance($servizio['openapi_servizi_costo_chiamata']) !== true) {
            return ["success" => false, "message" => "Non hai credito a sufficienza per questo acquisto."];
        }

        // Check servizio attivo
        if ($servizio['openapi_servizi_attivo'] == DB_BOOL_FALSE) {
            return ['success' => false, 'message' => 'Servizio non attivo'];
        }
        
        $url = $this->getServizioUrl($servizio);
        
        if (isset($this->openapi_settings['openapi_settings_skip_business_registry_check']) && $this->openapi_settings['openapi_settings_skip_business_registry_check'] !== DB_BOOL_TRUE) {
            $verifica_business_registry = $this->verificaBusinessRegistry($url, $azienda);
            
            if (!is_bool($verifica_business_registry)) {
                return ['success' => false, 'message' => $verifica_business_registry];
            }
        }
        
        $json_output = $this->sendPostCurl($url . '/invoices_signature_legal_storage', $xml, 'text/xml');
        
        $output = json_decode($json_output, true);
        
        // Addebito
        if ($output['success'] !== false) {
            $this->addebito($servizio['openapi_servizi_costo_chiamata'], $servizio['openapi_servizi_nome']);
        }

        // Salvo il dato grezzo anche in caso di esito negativo
        $this->save_to_cache($id_servizio, $url, $azienda['documenti_contabilita_settings_company_vat_number'], $json_output);

        return $output;
    }
    
    public function get_invoice($uuid, $azienda) {
        $id_servizio = 17; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();

        $url = $this->getServizioUrl($servizio);
        
        $json_output = $this->sendGetCurl($url . "/invoices/$uuid", ["Accept: application/json"]);
        
        // Salvo il dato grezzo anche in caso di esito negativo
        $this->save_to_cache($id_servizio, $url, $azienda['documenti_contabilita_settings_company_vat_number'], $json_output);
        
        $output = json_decode($json_output, true);
        
        return $output;
    }
    
    public function get_invoice_notifications($uuid, $azienda, $type = 'json') {
        // metto questo controllo così non si rischia di passare valori non accettabili dal servizio openapi
        if (!in_array($type, ['json', 'xml'])) {
            $type = 'json';
        }
        
        $id_servizio = 17; // TODO: Soluzione non definitiva
        $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$id_servizio'")->row_array();
        
        $url = $this->getServizioUrl($servizio);
        
        $output = $this->sendGetCurl($url . "/invoices_notifications/$uuid", ["Accept: application/{$type}"]);
        
        // Salvo il dato grezzo anche in caso di esito negativo
        $this->save_to_cache($id_servizio, $url, $azienda['documenti_contabilita_settings_company_vat_number'], $output);
        
        if ($type == 'json') {
            $output = json_decode($output, true);
        }
        
        return $output;
    }
}
