<?php
class Field extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }
    
    public function create_index($field_name) {
        $this->load->model('entities');
        $field = $this->datab->get_field_by_name($field_name);
        $this->entities->createIndex($field['fields_id']);
        
        e_json(['status' => 11, 'txt' => "Index on '$field_name' created!"]);
    }
}
