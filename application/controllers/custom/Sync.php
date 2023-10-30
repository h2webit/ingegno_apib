<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sync extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        //Check logged in
        if ($this->auth->guest()) { //Guest
            //Do your stuff...
        } elseif ($this->auth->check()) { //Logged in
            //Do your stuff...
        } else {
            //Do your stuff...
            throw new AssertionError("Undetected authorization type");
        }
    }

    // Call this method by {your_url}/custom/foo/bar
    public function import_pagamenti()
    {
        //Mi connetto al db postgres
        $db['crm_postgres']['hostname'] = "crm.apibinfermieribologna.com"; // Cambiare per testare in linea
        $db['crm_postgres']['database'] = 'mastercrm_apib';
        $db['crm_postgres']['username'] = 'mastercrm_apib';
        $db['crm_postgres']['password'] = 'Djf93MN@VZZF215';

        $db['crm_postgres']['dbdriver'] = 'postgre';
        $db['crm_postgres']['dbprefix'] = '';
        $db['crm_postgres']['pconnect'] = false;
        $db['crm_postgres']['db_debug'] = TRUE;
        $db['crm_postgres']['cache_on'] = FALSE;
        $db['crm_postgres']['cachedir'] = '';
        $db['crm_postgres']['char_set'] = 'utf8';
        $db['crm_postgres']['dbcollat'] = 'utf8_general_ci';
        $db['crm_postgres']['swap_pre'] = '';
        $db['crm_postgres']['autoinit'] = TRUE;
        $db['crm_postgres']['stricton'] = FALSE;

        $crm_postgres = $this->load->database($db['crm_postgres'], true);
        $pagamenti_id_importati = array_key_map($this->db->get('pagamenti')->result_array(),'pagamenti_id_esterno');
        $pagamenti_id_importati[] = '-1';

        $pagamenti = $crm_postgres->where_not_in('pagamenti_id', $pagamenti_id_importati)->where('pagamenti_anno >', '2022')->get('pagamenti')->result_array();

        $count = count($pagamenti);
        $c = 0;

        foreach ($pagamenti as $pagamento) {
            $c++;
            progress($c,$count);
            $pagamento['pagamenti_id_esterno'] = $pagamento['pagamenti_id'];
            unset($pagamento['pagamenti_data_creazione']);
            unset($pagamento['pagamenti_data_modifica']);
            $pagamento['pagamenti_pagato'] = ($pagamento['pagamenti_pagato']=='t')?DB_BOOL_TRUE:DB_BOOL_FALSE;
            //TODO
            //$associato = $this->apilib->searchFirst('documenti_contabilita_settings', [....]);
            $pagamento['pagamenti_associato'] = null;
            try {
                $this->apilib->create('pagamenti', $pagamento);
            } catch (Exception $e) {
               debug($pagamento,true);
            }
            
        }
    }
}
