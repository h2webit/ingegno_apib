<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


/*
 *
 * TODO: Move this controller to the new Builder module!
 *
 */
class Utilities extends MY_Controller
{

    function __construct()
    {



        parent::__construct();


        // Super admin protection
        if (!$this->auth->is_admin() || $_SERVER['REMOTE_ADDR'] != '62.196.41.184') {
            die("Oh no! Only super-admin can use this module.");
        }
        $this->load->model('builder-toolbar/utils', 'utils');

    }


    public function reprocess_records()
    {
        $post = $this->input->post();
        $entity = $this->datab->get_entity($post['entita']);
        $data_inizio = $post['data_inizio'] ?? false;
        $data_fine = $post['data_fine' ?? false];
        $azione = $post['azione'];
        $entity_name = $entity['entity_name'];
        $action_fields = json_decode($entity['entity_action_fields'], true);
        $campo_data = $action_fields['create_time'] ?? false;

        $campo_data_modifica = $action_fields['update_time'] ?? false;

        if (!$campo_data && ($data_inizio || $data_fine)) {
            die("Errore: l'entità selezionata non ha un campo data (create_time)");
        }



        if ($this->datab->module_installed('long-operations')) {
            $this->load->model('long-operations/longoperations', 'long_operations');
            $impostazioni_modulo = $this->apilib->searchFirst('long_operations_settings');
            if ($impostazioni_modulo['long_operations_settings_enabled'] == DB_BOOL_TRUE) {
                $use_long_operations_system = true;
            } else {
                $use_long_operations_system = false;
            }

        } else {
            $use_long_operations_system = false;
        }
        if ($data_inizio) {
            $this->db->where($campo_data . ' >=', $data_inizio);
        }
        if ($data_fine) {
            $this->db->where($campo_data . ' <=', $data_fine);
        }
        $records = $this->db->get($entity_name)->result_array();
        $c = count($records);
        $i = 0;
        foreach ($records as $record) {

            progress(++$i, $c);
            //debug($record);
            $record['entity_name'] = $entity_name;
            $record['action'] = $azione;

            //Modifico almeno un campo, ovvero la data modifica
            if ($campo_data_modifica) {
                $record[$campo_data_modifica] = date('Y-m-d H:i:s');
            }

            if ($use_long_operations_system) {
                $this->long_operations->longOperation('reprocess_record', $record, 'builder-toolbar/utils');


            } else {
                $chiavi = array_change_key_case($record);

                $record = $this->utils->reprocess_record($record);

                //prendo solo le chiavi che mi interessano
                $record = array_intersect_key($record, $chiavi);

                //debug($record,true);
            }
        }
    }
}