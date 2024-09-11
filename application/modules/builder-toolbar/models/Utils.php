<?php

class Utils extends CI_Model
{

    public $scope = 'CRM';
    public $turni_dipendenti = [];
    public $orari_di_lavoro_ore_pausa = [];
    public $pause_support_table = [];


    public function __construct()
    {
        $this->impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
        $this->pause_support_table = array_key_map_data($this->apilib->search('presenze_pausa'), 'presenze_pausa_id');

        $_orari_di_lavoro_ore_pausa = $this->apilib->search('orari_di_lavoro_ore_pausa');
        foreach ($_orari_di_lavoro_ore_pausa as $turno) {
            $this->orari_di_lavoro_ore_pausa[$turno['orari_di_lavoro_ore_pausa_id']] = $turno;

        }
        parent::__construct();
    }

    public function scope($tipo)
    {
        $this->scope = $tipo;
    }

    public function reprocess_record($record)
    {
        $entity_name = $record['entity_name'];
        $id = $record[$entity_name.'_id'];
        $action = $record['action'];
        unset($record['entity_name']);
        unset($record[$entity_name.'_id']);
        unset($record['action']);
        switch ($action) {
            //TODO....
            // case 'save':
            //     $this->save($entity_name, $id, $record);
            //     break;
            // case 'edit':
            //     $this->edit($entity_name, $id, $record);
            //     break;
            // case 'insert':
            //     $this->insert($entity_name, $record);
            //     break;
            // case 'delete':
            //     $this->delete($entity_name, $id);
            //     break;
            default:
                $old_post = $_POST;
                $_POST = $record;
                $record = $this->apilib->edit($entity_name, $id, $record);
                $_POST = $old_post;
                break;
        }
        
        return $record;
    }
}