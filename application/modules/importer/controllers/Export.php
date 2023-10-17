<?php
    class Export extends MY_Controller
    {
        public function __construct()
        {
            parent::__construct();
            if ($this->auth->guest()) {
                redirect('access');
            }
        }

        public function start()
        {
            $dati['current_page'] = 'module_' . MODULE_NAME;
            $dati['entities'] = $this->db->order_by('entity_name')->get('entity')->result_array();
            $dati['importer_mappings'] = $this->db->query("SELECT * FROM importer_mappings LEFT JOIN entity ON entity.entity_id = importer_mappings.importer_mappings_entity_id WHERE importer_mappings_is_export = '1'")->result_array();
            $this->stampa('export/export_1', $dati);
        }

        public function map()
        {
            $dati['current_page'] = 'module_' . MODULE_NAME;
            $dati['export_data'] = $this->session->userdata(SESS_EXPORT_DATA);

            if (empty($dati['export_data'])) {
                redirect(base_url('importer/export/start'));
            }

            $dati['entity'] = $this->db->get_where('entity', array('entity_id' => $dati['export_data']['entity_id']))->result_array();

            $dati['fields'] = $this->db->get_where('fields', array('fields_entity_id' => $dati['export_data']['entity_id']))->result_array();

            //Per ogni field_ref, estraggo la mappatura della relativa entità
            foreach ($dati['fields'] as $key => $field) {
                //Se questo campo ha una relazione esterna con l'entità field_ref
                //debug($field,true);
                if ($field['fields_ref']) {
                    $dati['fields'][$key][$field['fields_ref']] = $this->datab->get_entity_by_name($field['fields_ref']);
                }
            }

            $this->stampa('export/export_2', $dati);
        }

        private function stampa($view_file = null, $data = null)
        {
            $this->template['page'] = $this->load->view($view_file, array('dati' => $data), true);

            $this->template['head'] = $this->load->view('layout/head', array(), true);
            $this->template['header'] = $this->load->view('layout/header', array(), true);
            $this->template['sidebar'] = $this->load->view('layout/sidebar', array(), true);
            $this->template['footer'] = $this->load->view('layout/footer', null, true);
            $this->template['foot'] = $this->load->view('layout/foot', null, true);
    
            /*
             * Module-related assets extensions
             */
            $this->template['head'] .= $this->load->view('layout/module_css', array(), true);
            $this->template['footer'] .= $this->load->view('layout/module_js', null, true);

            //Build template
            $page = $this->load->view('layout/main', $this->template, true);
            $page = $this->layout->replaceTemplateHooks($page, null);
            $this->output->append_output($page);
        }
    }
