<?php
class Api extends MY_Controller {
    public function __construct() {
        parent::__construct();
        
        /**
         * @todo - Quando vedi questo todo, ricordati di aggiungere (se non c'è già) la seguente riga di codice nel file "application/config/config_custom.php", in quanto va escluso questo controller dal CSRF.
         *
         * $config['csrf_exclude_uris'][] = 'contabilita/api/.*?';
         */
        
        $this->load->model('contabilita/docs');
        
        $token_data = $this->db->get_where('api_manager_tokens', ['api_manager_tokens_token' => '' . $this->getBearerToken()]);
        
        if ($token_data->num_rows() == 0) {
            e_json(['status' => 0, 'error' => "Invalid bearer '" . $this->getBearerToken() . "'.", 'data' => null]);
            die();
        } else {
            $token = $token_data->row();
            $this->token_id = $token->api_manager_tokens_id;
            $this->token = $token->api_manager_tokens_token;
            
            //Aggiorno i conteggi
            $this->db->where('api_manager_tokens_id', $this->token_id)->update('api_manager_tokens', [
                'api_manager_tokens_last_use_date' => date('Y-m-d H:m:s'),
                'api_manager_tokens_requests' => (int) ($token->api_manager_tokens_requests) + 1,
            ]);
        }
        
        set_log_scope('contabilita-api');
    }
    
    public function create()
    {
        $post = $this->input->post();
        
        // CONTROLLI GENERICI
        if (empty($post)) {
            e_json(['status' => 0, 'error' => 'Nessun dato inviato', 'data' => null]);
            return;
        }
        
        if (empty($post['destinatario'])) {
            e_json(['status' => 0, 'error' => 'Dati destinatario mancanti', 'data' => null]);
            return;
        }
        
        $keys_dati_dest = array_keys([
            "codice" => "",
            "ragione_sociale" => "",
            "indirizzo" => "",
            "citta" => "",
            "nazione" => "",
            "cap" => "",
            "provincia" => "",
            "partita_iva" => "",
            "codice_fiscale" => "",
            "codice_sdi" => "",
            "pec" => ""
        ]);
        
        foreach ($keys_dati_dest as $key) {
            if (!isset($post['destinatario'][$key])) {
                e_json(['status' => 0, 'error' => "Campo {$key} in dati destinatario non presente", 'data' => null]);
                return;
            }
        }
        
        if (empty($post['cliente_id']) || !is_numeric($post['cliente_id'])) {
            e_json(['status' => 0, 'error' => 'Cliente non valido', 'data' => null]);
            return;
        }
        
        if (empty($post['articoli']) && empty($post['articoli_data'])) {
            e_json(['status' => 0, 'error' => 'Dati articoli mancanti', 'data' => null]);
            return;
        }
        
        $dati_doc = [
            'documenti_contabilita_destinatario' => json_encode($post['destinatario']),
            'cliente_id' => array_get($post, 'cliente_id'),
            'tipo_documento' => array_get($post, 'tipo_documento', 1),
            'tipo_destinatario' => array_get($post, 'tipo_destinatario', 1),
            'stato' => array_get($post, 'stato', 1),
            'serie' => array_get($post, 'serie', 1),
            'valuta' => array_get($post, 'valuta', 'EUR'),
            'agente' => array_get($post, 'agente', null),
        ];
        
        if (!empty($post['scadenza'])) {
            $dati_doc['scadenza'] = $post['scadenza'];
        } elseif (!empty($post['scadenze'])) {
            $dati_doc['scadenze'] = $post['scadenze'];
        } else {
            if (!empty($post['data'])) {
                $dati_doc['data'] = $post['data'];
                
            }
        }
        
        if (!empty($post['articoli'])) {
            $dati_doc['articoli'] = $post['articoli'];
        } else if (!empty($post['articoli_data'])) {
            $dati_doc['articoli_data'] = $post['articoli_data'];
        }
        
        if (!empty($post['totale'])) {
            $dati_doc['totale'] = $post['totale'];
        }
        
        // allo stesso modo dei dati precedenti, passo anche i campi: saldato, saldato_con e data_saldo
        if (!empty($post['saldato'])) {
            $dati_doc['saldato'] = $post['saldato'];
        }
        
        if (!empty($post['saldato_con'])) {
            $dati_doc['saldato_con'] = $post['saldato_con'];
        }
        
        if (!empty($post['data_saldo'])) {
            $dati_doc['data_saldo'] = $post['data_saldo'];
        }
        
        // debug($dati_doc, true);
        
        $this->load->model('contabilita/docs');
        
        try {
            $doc_id = $this->docs->doc_express_save($dati_doc);
            
            $documento_salvato = $this->apilib->view('documenti_contabilita', $doc_id);
            
            unset($documento_salvato['documenti_contabilita_template_pdf_html']);
            
            $righe_articolo = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $doc_id]);
            
            $documento_salvato['righe_articolo'] = $righe_articolo;
            
            $pdf = null;
            
            if (
                (!empty($post['get_pdf']) && $post['get_pdf'] == '1')
                && file_exists(FCPATH . 'uploads/' . $documento_salvato['documenti_contabilita_file_pdf'])
            ) {
                $pdf_content = file_get_contents(FCPATH . 'uploads/' . $documento_salvato['documenti_contabilita_file_pdf']);
                
                $pdf = base64_encode($pdf_content);
            }
            
            e_json(['status' => 1, 'error' => null, 'data' => $documento_salvato, 'pdf' => $pdf]);
        } catch (\Exception $e) {
            my_log('error', "Errore salvataggio documento: " . $e->getMessage());
            e_json(['status' => 0, 'error' => "Errore durante la crezione del documento. Contattare l'assistenza!", 'data' => null]);
        }
    }
    
    private function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
}