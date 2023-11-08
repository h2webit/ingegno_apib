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
            ->get('pagamenti')->result_array();
        
        if (empty($pagamenti)) {
            echo 'nessun pagamento da importare';
            return false;
        }
        
        $t = count($pagamenti);
        $c = 0;
        foreach ($pagamenti as $pagamento) {
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
}
