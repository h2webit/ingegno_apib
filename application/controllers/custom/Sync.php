<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sync extends MY_Controller
{
    private $apib_db;
    function __construct ()
    {
        parent::__construct();
        
        if (!$this->auth->check()) {
            die("Unauthorized");
        }
        
        $this->apib_db_connect();
        
        set_log_scope('sync-pagamenti');
    }
    
    private function apib_db_connect() {
        //Mi connetto al db postgres
        $db['crm_postgres']['hostname'] = ($_SERVER['SERVER_NAME'] == 'apib.ingegnosuite.it') ? 'localhost' : "crm.apibinfermieribologna.com"; // Cambiare per testare in linea
        $db['crm_postgres']['database'] = 'mastercrm_apib';
        $db['crm_postgres']['username'] = 'mastercrm_apib';
        $db['crm_postgres']['password'] = 'Djf93MN@VZZF215';
        
        $db['crm_postgres']['dbdriver'] = 'postgre';
        $db['crm_postgres']['dbprefix'] = '';
        $db['crm_postgres']['pconnect'] = false;
        $db['crm_postgres']['db_debug'] = true;
        $db['crm_postgres']['cache_on'] = false;
        $db['crm_postgres']['cachedir'] = '';
        $db['crm_postgres']['char_set'] = 'utf8';
        $db['crm_postgres']['dbcollat'] = 'utf8_general_ci';
        $db['crm_postgres']['swap_pre'] = '';
        $db['crm_postgres']['autoinit'] = true;
        $db['crm_postgres']['stricton'] = false;
        
        $this->apib_db = $this->load->database($db['crm_postgres'], true);
    }
    
    public function import_pagamenti()
    {
        $associati = $this->db
            ->where("documenti_contabilita_settings_company_codice_fiscale IS NOT NULL AND documenti_contabilita_settings_company_codice_fiscale <> ''")
            ->get('documenti_contabilita_settings')->result_array();
        
        $associati_cf = array_key_value_map($associati, 'documenti_contabilita_settings_company_codice_fiscale', 'documenti_contabilita_settings_id');
        
        $pagamenti_id_importati = array_key_map($this->db->get('pagamenti')->result_array(), 'pagamenti_id_esterno');
        $pagamenti_id_importati[] = '-1';
        
        $pagamenti = $this->apib_db
            ->where_not_in('pagamenti_id', $pagamenti_id_importati)
            ->where('pagamenti_approvato', 't')
            ->where('pagamenti_anno >', '2022')
            ->where('pagamenti_acconto > 0', null, false)
            ->get('pagamenti')->result_array();
        
        if (empty($pagamenti)) {
            echo 'nessun pagamento da importare';
            return false;
        }
        
        $t = count($pagamenti);
        $c = 0;
        foreach ($pagamenti as $pagamento) {
            //debug($pagamento,true);
            $associato_old = $this->apib_db->where('associati_id', $pagamento['pagamenti_associato'])->get('associati')->row_array();
            
            $pagamento['pagamenti_id_esterno'] = $pagamento['pagamenti_id'];
            
            unset($pagamento['pagamenti_data_creazione']);
            unset($pagamento['pagamenti_data_modifica']);
            
            $pagamento['pagamenti_pagato'] = ($pagamento['pagamenti_pagato'] == 't') ? DB_BOOL_TRUE : DB_BOOL_FALSE;
            $pagamento['pagamenti_approvato'] = ($pagamento['pagamenti_approvato'] == 't') ? DB_BOOL_TRUE : DB_BOOL_FALSE;
            
            if (!empty($associati_cf[$associato_old['associati_cf']])) {
                $pagamento['pagamenti_associato'] = $associati_cf[$associato_old['associati_cf']];
            } else {
                $pagamento['pagamenti_associato'] = null;
            }
            
            try {
                $this->apilib->create('pagamenti', $pagamento);
                
                progress(++$c, $t);
            } catch (Exception $e) {
                echo "errore inserimento pagamento";
                my_log('error', "errore inserimento pagamento: {$e->getMessage()}");
            }
        }
    }

    public function migrate_dati() {
        $this->import_associati();
    }

    public function import_associati() {
        $associati = $this->apib_db->join('utenti', 'utenti_id = associati_utente', 'LEFT')->where('associati_deleted <> ', 't')->get('associati')->result_array();
        //debug($associati,true);
        $t = count($associati);
        $c = 0;
        foreach ($associati as $associato) {
            
            progress(++$c, $t, 'import associati vs dipendenti');
            if ($associato['associati_email'] == '') {
                continue;
            }
            if ($associato['associati_foto']) {
                $folder = explode('/', $associato['associati_foto']);
                $folder = "{$folder[0]}/{$folder[1]}/{$folder[2]}";
                @mkdir(FCPATH . 'uploads/' . $folder, DIR_WRITE_MODE, true);
                copy("https://crm.apibinfermieribologna.com/uploads/{$associato['associati_foto']}", FCPATH."uploads/{$associato['associati_foto']}");
            }
            $dipendente = [
                'dipendenti_id' => $associato['associati_id'],
                'dipendenti_user_id' => $associato['associati_utente'],
                'dipendenti_tipologia' => 3, //Empolyee
                
                'dipendenti_posizione' => 2, //P.iva
                'dipendenti_azienda' => 1, //Apib
                
                'dipendenti_nome' => $associato['associati_nome'],
                'dipendenti_cognome' => $associato['associati_cognome'] ?? 'senza cognome',
                'dipendenti_codice_fiscale' => $associato['associati_cf'],
                'dipendenti_cellulare' => $associato['associati_cellulare'],
                'dipendenti_foto' => $associato['associati_foto']??null,
                'dipendenti_email' => $associato['associati_email'],
                'dipendenti_password' => $associato['utenti_password'],
                'dipendenti_data_nascita' => $associato['associati_data_nascita'],
                'dipendenti_luogo_nascita' => $associato['associati_luogo_nascita'],
                
                'dipendenti_consenti_straordinari' => DB_BOOL_FALSE,
                'dipendenti_automobile_personale' => $associato['associati_automobile'],
                'dipendenti_costo_chilometrico' => $associato['associati_costo_km'],
                
                'dipendenti_data_inizio' => $associato['associati_inizio_rapporto'],
                'dipendenti_data_fine' => $associato['associati_fine_rapporto'],
                
                'dipendenti_note_aggiuntive' => "{$associato['associati_note_1']}\n{$associato['associati_note_2']}\n{$associato['associati_note_3']}",
                'dipendenti_indirizzo' => $associato['associati_indirizzo_residenza'],
                'dipendenti_citta' => $associato['associati_citta_residenza'],
                'dipendenti_dichiara_reparto' => DB_BOOL_FALSE,
                'dipendenti_reperibilita' => DB_BOOL_TRUE,
                'dipendenti_attivo' => ($associato['associati_non_attivo'] == 't' || $associato['associati_deleted'] == 't') ? DB_BOOL_FALSE : DB_BOOL_TRUE,
                'dipendenti_ignora_orari_lavoro' => DB_BOOL_TRUE,
                'dipendenti_ignora_pausa' => DB_BOOL_TRUE,
                'dipendenti_timbra_da_rapportino' => DB_BOOL_TRUE,
                
                'dipendenti_send_credential_by_email' => DB_BOOL_FALSE,
                
                'dipendenti_presenza_automatica' => DB_BOOL_FALSE,
                'dipendenti_mostra_in_riepilogo' => DB_BOOL_TRUE,
                'dipendenti_consenti_timbratura_senza_turno' => DB_BOOL_FALSE,
                
                'dipendenti_iban' => $associato['associati_iban'],
                'dipendenti_creation_date' => $associato['associati_data_creazione'],
                'dipendenti_modified_date' => $associato['associati_data_modifica'],
                
            ];
            //Tutti i campi che non esistono mappati su $dipendente ma che esistono su $associato, li salvo sul json dipendenti_altri_dati
            foreach ($associato as $key => $value) {
                $key = str_replace('associati_', 'dipendenti_', $key);
                if (!array_key_exists($key, $dipendente)) {
                    $dipendente['dipendenti_dati_apib'][$key] = $value;
                }

            }
            //$dipendente['dipendenti_dati_apib'] = json_encode($dipendente['dipendenti_dati_apib']);

            //debug($dipendente,true);

            try {
                $dipendente_exists = $this->db->get_where('dipendenti', ['dipendenti_id' => $dipendente['dipendenti_id']])->row_array();
                $_POST = $dipendente;
                if ($dipendente_exists) {
                    $dipendente_creato = $this->apilib->edit('dipendenti', $dipendente['dipendenti_id'], $dipendente);
                } else {
                    $dipendente_creato = $this->apilib->create('dipendenti', $dipendente);
                    //Una volta creato il dipendente, aggiorno il campo password con la password criptata (altrimenti le apilib fanno un md5 dell'md5...)
                   
                }

                // debug($associato);
                // debug($dipendente_creato, true);
                
                $this->db->where('users_id', $dipendente_creato['dipendenti_user_id'])->update('users', ['users_password' => $associato['utenti_password']]);
                $this->db->where('dipendenti_id', $dipendente_creato['dipendenti_id'])->update('dipendenti', ['dipendenti_password' => $associato['utenti_password']]);
                $_POST = [];

                
            } catch (Exception $e) {
                
                my_log('error', "errore inserimento associato: {$e->getMessage()}");
                debug($associato);
                debug($e->getMessage(),true);
            }
        }
    }
}
