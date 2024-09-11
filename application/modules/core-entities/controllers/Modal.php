<?php
    class Modal extends MY_Controller {
        public function __construct() {
            parent::__construct();
        }
        
        public function support($field_id) {
            $view_data = [];
            
            $this->load->module_view('core-entities/views/modals', 'fields_support_table', $view_data);
        }
    }
