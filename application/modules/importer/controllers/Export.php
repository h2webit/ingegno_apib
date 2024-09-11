<?php
    class Export extends MY_Controller
    {
        private $module_settings;
        public function __construct()
        {
            parent::__construct();
            if ($this->auth->guest()) {
                redirect('access');
            }
            
            $this->load->model('importer/export_utils_model', 'export_utils');
            
            $module_settings = $this->apilib->searchFirst('importer_settings');
            
            $this->module_settings = $module_settings;
            
            $exporter_allowed_user_types = array_keys($module_settings['importer_settings_exporter_allowed_user_types'] ?? []);
            
            $current_user_type = $this->auth->get('users_type') ?? null;
            
            if ($module_settings['importer_settings_enable_exporter'] != DB_BOOL_TRUE || (!empty($exporter_allowed_user_types) && !in_array($current_user_type, $exporter_allowed_user_types))) {
                show_404();
            }
        }

        public function preview($tpl_id = null) {
            if (!$tpl_id) {
                show_404();
                return false;
            }
            
            $view_data = [];
            
            $template = $this->apilib->view('exporter_templates', $tpl_id);
            
            // debug($this->session->userdata());
            
            $grid = $this->datab->get_grid($template['exporter_templates_grid_id']);
            $grid['grids_data'] = $this->datab->get_grid_data($grid, null, [], 100);
            
            $view_data['grid'] = $grid;
            $view_data['form_filters'] = $this->load->view('export/form_filters', ['template' => $template], true);
            $view_data['template'] = $template;
            
            $this->stampa('export/preview', $view_data);
        }
        
        public function edit($template_id = null) {
            if (!$template_id) {
                show_404();
                return false;
            }
            
            $view_data = [];
            
            $template = $this->apilib->searchFirst('exporter_templates', ['exporter_templates_id' => $template_id]);
            
            if (empty($template)) {
                echo 'Template non trovato';
                return;
            }
            
            $view_data['template'] = $template;
            
            $grid = $this->datab->get_grid($template['exporter_templates_grid_id']);
            
            $all_fields = $this->crmentity->getVisibleFields($grid['grids']['entity_name'], 2);
            $selected_fields = $grid['grids_fields'];
            
            // remove fields from "all_fields" that are already in "selected_fields"
            foreach ($all_fields as $key => $field) {
                foreach ($selected_fields as $selected_field) {
                    if ($field['fields_id'] == $selected_field['fields_id']) {
                        unset($all_fields[$key]);
                    }
                }
            }
            
            $view_data['all_fields'] = $all_fields;
            $view_data['selected_fields'] = $selected_fields;
            
            $this->stampa('export/edit', $view_data);
        }
        
        public function new_template($tpl_id = null)
        {
            // Initialize an empty array to store view data.
            $view_data = [];
            
            // Set the current page based on the MODULE_NAME.
            $view_data['current_page'] = 'module_' . MODULE_NAME;
            
            // Retrieve all entities using the crmentity->getAllEntities() method.
            if (!empty($this->module_settings['importer_settings_allowed_entities'])) {
                $view_data['entities'] = json_decode($this->module_settings['importer_settings_allowed_entities']);
            } else {
                $view_data['entities'] = array_key_map($this->crmentity->getAllEntities(), 'entity_name');
            }
            
            // Retrieve modules from the database and order them by modules_name in ascending order.
            $view_data['modules'] = $this->db->order_by('modules_name', 'ASC')->get('modules')->result_array();
            
            // Retrieve grids from the database that are not associated with any exporter templates and order them by grids_name in ascending order.
            $grids = $this->db->where("grids_id NOT IN (SELECT exporter_templates_grid_id FROM exporter_templates)", null, false)->order_by('grids_name', 'ASC')->get('grids')->result_array();
            
            // Store the retrieved grids in the view data array.
            $view_data['grids'] = $grids;
            
            // Initialize an empty array to store template data.
            $template = [];
            
            // Check if $tpl_id is provided and if so, retrieve the corresponding exporter template using the apilib->view() method.
            if (!empty($tpl_id)) {
                $template = $this->apilib->view('exporter_templates', $tpl_id);
                
                // If the 'clone' parameter is present in the input, remove the exporter_templates_id from the template data.
                if (!empty($this->input->get('clone'))) {
                    unset($template['exporter_templates_id']);
                }
            }
            
            // Store the template data in the view data array.
            $view_data['template'] = $template;
            
            // Render the 'export/new_template' view and pass the view data for rendering.
            $this->stampa('export/new_template', $view_data);
        }
        
        public function save_template() {
            // Get POST data.
            $post = $this->input->post();
            
            $is_clone = false;
            if (!empty($post['_clone']) && $post['_clone'] == '1') {
                $is_clone = true;
                unset($post['_clone']);
            }
            
            $stay = false;
            if (!empty($post['_stay']) && $post['_stay'] == '1') {
                $stay = true;
                unset($post['_stay']);
            }
            
            // Check if POST data is empty.
            if (empty($post)) {
                e_json(['status' => 0, 'txt' => 'No data']);
                return false;
            }
            
            // Remove elements from POST data with keys starting with 'qb_'.
            foreach ($post as $key => $value) {
                if (substr($key, 0, 3) == 'qb_') {
                    unset($post[$key]);
                }
            }
            
            // Load the form_validation library.
            $this->load->library('form_validation');
            
            // Set validation rules for 'exporter_templates_name' and 'exporter_templates_key'.
            $this->form_validation->set_rules('exporter_templates_name', t('Name'), 'trim|required');
            $this->form_validation->set_rules('exporter_templates_key', 'Key', 'trim|required');
            
            // Check if form validation fails.
            if (!$this->form_validation->run()) {
                e_json(['status' => 0, 'txt' => validation_errors()]);
                return false;
            }
            
            // Define conditions to check for duplicate templates with the same key.
            $where_template_with_key = ['exporter_templates_key' => $post['exporter_templates_key']];
            if (!empty($post['exporter_templates_id'])) {
                $this->db->where("exporter_templates_id <> '{$post['exporter_templates_id']}'");
            }
            
            // Check for templates with the same key in the database.
            $template_with_key = $this->db->get_where('exporter_templates', $where_template_with_key)->row_array();
            
            // If a template with the same key exists, return an error.
            if (!empty($template_with_key)) {
                e_json(['status' => 0, 'txt' => t("Another export with the same key already exists")]);
                return false;
            }
            
            // Initialize a variable to control further validations.
            $run_validations = false;
            
            // Check if 'tpl_type' is present in POST data.
            if (!empty($post['tpl_type']) || $is_clone) {
                if ( (!empty($post['tpl_type']) && $post['tpl_type'] == 'grid') || $is_clone) {
                    // Check if 'grid_id' is present.
                    if (empty($post['grid_id'])) {
                        e_json(['status' => 0, 'txt' => t('You have selected to import an existing grid but have not selected one')]);
                        return false;
                    }
                    
                    // Retrieve grid information based on 'grid_id'.
                    $grid = $this->db->get_where('grids', ['grids_id' => $post['grid_id']])->row_array();
                    
                    // If the grid does not exist, return an error.
                    if (empty($grid)) {
                        e_json(['status' => 0, 'txt' => t('Selected grid does not exist')]);
                        return false;
                    }
                    
                    // Clone the selected grid.
                    $new_grid = $this->datab->clone_element('grid', $post['grid_id'], false);
                    
                    // Check for errors during grid cloning.
                    if (!is_numeric($new_grid) && !is_array($new_grid)) {
                        e_json(['status' => 0, 'txt' => t('An error occurred while cloning the grid: ' . $new_grid)]);
                        return false;
                    }
                    
                    // Extract relevant grid information.
                    $new_grid = $new_grid['grids'];
                    $entity = $this->datab->get_entity($new_grid['grids_entity_id']);
                    
                    // Lock the cloned grid and remove its actions.
                    $this->datab->lock_element('grid', $new_grid['grids_id']);
                    try {
                        $this->db->where('grids_actions_grids_id', $new_grid['grids_id'])->delete('grids_actions');
                    } catch (Exception $e) { }
                    
                    // Set relevant fields in POST data for the exporter template.
                    $post['exporter_templates_grid_id'] = $new_grid['grids_id'];
                    $post['exporter_templates_entity'] = $entity['entity_name'];
//                    $post['exporter_templates_additional_where'] = $new_grid['grids_where'] ?: null; // 2024-01-10 - michael - commentato perchè arriva già in post quando si seleziona una grid che ha già un where, con la possibilità quindi di modificarlo
                    $post['exporter_templates_where'] = $new_grid['grids_builder_where'] ?: null;
                    $post['exporter_templates_limit'] = $new_grid['grids_limit'] ?: null;
                    $post['exporter_templates_order_by'] = str_ireplace(['ASC', 'DESC'], '', $new_grid['grids_order_by']);
                    $post['exporter_templates_full_query'] = (!empty($new_grid['grids_custom_query'])) ? DB_BOOL_TRUE : DB_BOOL_FALSE;
                    $post['exporter_templates_query'] = $new_grid['grids_custom_query'];
                } else {
                    $run_validations = true;
                }
            } else {
                $run_validations = true;
            }
            
            // Remove 'tpl_type' and 'grid_id' from POST data.
            unset($post['tpl_type'], $post['grid_id']);
            
            // Additional form validations for exporter templates.
            if ($run_validations) {
                $this->form_validation->set_rules('exporter_templates_full_query', 'Full query', 'trim|required|in_list[0,1]');
                $this->form_validation->set_rules('exporter_templates_entity', t('Entity'), 'trim|required');
                
                // Validate 'exporter_templates_query' and 'exporter_templates_order_dir' if 'exporter_templates_full_query' is true.
                if ($post['exporter_templates_full_query'] == DB_BOOL_TRUE) {
                    $this->form_validation->set_rules('exporter_templates_query', 'Query', 'trim|required');
                } else {
                    $this->form_validation->set_rules('exporter_templates_order_by', 'Order By', 'trim');
                    $this->form_validation->set_rules('exporter_templates_order_dir', 'Order Dir', 'trim|in_list[ASC,DESC]');
                }
            }
            
            // Check if form validation fails again.
            if (!$this->form_validation->run()) {
                e_json(['status' => 0, 'txt' => validation_errors()]);
                return false;
            }
            
            // Set 'exporter_templates_limit' to null if it's empty or less than or equal to 0.
            if (empty($post['exporter_templates_limit']) || $post['exporter_templates_limit'] <= 0) {
                $post['exporter_templates_limit'] = null;
            }
            
            // Check if 'exporter_templates_full_query' is true and 'exporter_templates_query' is empty.
            if ($post['exporter_templates_full_query'] == DB_BOOL_TRUE && empty(trim($post['exporter_templates_query']))) {
                e_json(['status' => 0, 'txt' => t('No query provided in full query mode')]);
                return false;
            }
            
            try {
                // Get entity by name.
                $entity = $this->datab->get_entity_by_name($post['exporter_templates_entity']);
                
                // Define grid data based on exporter template information.
                $grid_data = [
                    'grids_entity_id' => $entity['entity_id'],
                    'grids_name' => "[EXPORT MODULE] {$post['exporter_templates_name']}",
                    'grids_where' => $post['exporter_templates_additional_where'] ?? null,
                    'grids_builder_where' => !empty($post['exporter_templates_where']) ? $post['exporter_templates_where'] : null,
                    'grids_limit' => $post['exporter_templates_limit'] ?: null,
                    'grids_order_by' => !empty($post['exporter_templates_order_by']) ? $post['exporter_templates_order_by'] . ' ' . $post['exporter_templates_order_dir'] : null,
                    'grids_group_by' => !empty($post['exporter_templates_group_by']) ? $post['exporter_templates_group_by'] : null,
                    'grids_custom_query' => $post['exporter_templates_full_query'] == DB_BOOL_TRUE ? $post['exporter_templates_query'] : null,
                    'grids_filter_session_key' => "export_tpl_filter_{$post['exporter_templates_key']}",
                    'grids_identifier' => "grid_{$post['exporter_templates_key']}",
                    
                    // STATIC VALUES
                    'grids_layout' => 'table',
                    'grids_actions_column' => 0,
                    'grids_datatable' => 0,
                    'grids_ajax' => 0,
                    'grids_searchable' => 0,
                    'grids_pagination' => 10,
                    'grids_inline_edit' => 0,
                    'grids_bulk_mode' => null,
                    'grids_depth' => 3,
                    'grids_design' => 2, // table slim
                ];
                
                // Check if an exporter template ID is provided.
                if (!empty($post['exporter_templates_id'])) {
                    // Update the exporter template.
                    $exporter_template = $this->apilib->edit('exporter_templates', $post['exporter_templates_id'], $post);
                    
                    // Update the associated grid.
                    if (!empty($exporter_template['exporter_templates_grid_id'])) {
                        $this->db->where('grids_id', $exporter_template['exporter_templates_grid_id'])->update('grids', $grid_data);
                    }
                    $this->mycache->clearCache();
                    $exporter_template_id = $exporter_template['exporter_templates_id'];
                } else {
                    unset($post['exporter_templates_id']);
                    
                    $post['exporter_templates_active'] = DB_BOOL_TRUE; // forzo che sia attivo di default
                    
                    // Create a new exporter template.
                    $exporter_template_id = $this->apilib->create('exporter_templates', $post, false);
                    $this->mycache->clearCache();
                    
                    // Update or insert the associated grid.
                    if (!empty($post['exporter_templates_grid_id'])) {
                        $grid_id = $post['exporter_templates_grid_id'];
                        $this->db->where('grids_id', $grid_id)->update('grids', $grid_data);
                    } else {
                        $this->db->insert('grids', $grid_data);
                        $grid_id = $this->db->insert_id();
                        
                        $this->datab->lock_element('grid', $grid_id);
                        
                        $entity_preview_fields = $this->crmentity->getEntityPreviewFields($post['exporter_templates_entity']);
                        
                        if (!empty($entity_preview_fields)) {
                            foreach ($entity_preview_fields as $preview_field) {
                                $grid_field_data = [
                                    'grids_fields_grids_id' => $grid_id,
                                    'grids_fields_fields_id' => $preview_field['fields_id'],
                                    'grids_fields_order' => 0,
                                    'grids_fields_replace_type' => 'field',
                                    'grids_fields_replace' => null,
                                    'grids_fields_column_name' => $preview_field['fields_draw_label'],
                                    'grids_fields_eval_cache_type' => null,
                                    'grids_fields_eval_cache_data' => null,
                                    'grids_fields_totalable' => 0,
                                    'grids_fields_with_actions' => 0,
                                    'grids_fields_switch_inline' => 0,
                                    'grids_fields_module_key' => null,
                                    'grids_fields_module' => null,
                                    'grids_fields_width' => null
                                ];
                                
                                try {
                                    $this->db->insert('grids_fields', $grid_field_data);
                                } catch (Exception $e) { }
                            }
                        }
                    }
                    
                    // Lock the grid element.
                    $this->datab->lock_element('grid', $grid_id);
                    $this->mycache->clearCache();
                    
                    // Insert grid information into layout boxes.
                    $layout_id = $this->layout->getLayoutByIdentifier('exporter-templates-grids');
                    $this->db->insert('layouts_boxes', [
                        'layouts_boxes_layout' => $layout_id,
                        'layouts_boxes_title' => $grid_data['grids_name'],
                        'layouts_boxes_content_type' => 'grid',
                        'layouts_boxes_content_ref' => $grid_id,
                        'layouts_boxes_position' => 1,
                        'layouts_boxes_titolable' => DB_BOOL_TRUE,
                        'layouts_boxes_collapsible' => DB_BOOL_TRUE,
                        'layouts_boxes_collapsed' => DB_BOOL_TRUE,
                        'layouts_boxes_show_container' => DB_BOOL_TRUE,
                        'layouts_boxes_row' => 1,
                        'layouts_boxes_cols' => 12,
                        'layouts_boxes_color' => 'box-default',
                    ]);
                    $this->mycache->clearCache();
                    
                    // Update the exporter template with the associated grid.
                    $this->db->where('exporter_templates_id', $exporter_template_id)->update('exporter_templates', ['exporter_templates_grid_id' => $grid_id]);
                }
                
                // Clear the cache and return success response.
                $this->mycache->clearCache();
                
                if ($stay) {
                    e_json(['status' => 11, 'txt' => t('Export saved')]);
                } else {
                    e_json(['status' => 10, 'txt' => 'Export saved', 'url' => base_url('importer/export/edit/' . $exporter_template_id)]);
                }
            } catch (Exception $e) {
                // Log error and return an error response.
                my_log('error', $e->getMessage());
                e_json(['status' => 0, 'txt' => t('Error saving template: ') . $e->getMessage()]);
            }
        }
        
        public function preview_table() {
            $post = $this->input->post();
            
            if (empty($post['tpl_id'])) {
                e_json(['status' => 0, 'txt' => 'No template']);
                return false;
            }
            
            $template = $this->db
                ->where('exporter_templates_id', $post['tpl_id'])
                ->get('exporter_templates')->row_array();
            
            $grid = $this->datab->get_grid($template['exporter_templates_grid_id']);
            
            $records = $this->datab->get_grid_data($grid, null, [], 10, 0, 'RAND() DESC');
            
            $grid_data = ['data' => $records, 'sub_grid_data' => []];
            
            $html_table = $this->load->view("pages/layouts/grids/table", array(
                'grid' => $grid,
                'sub_grid' => null,
                'grid_data' => $grid_data,
                'value_id' => null,
                'layout_data_detail' => null,
                'where' => false,
            ), true);
            
            e_json(['status' => 1, 'txt' => '', 'data' => $html_table]);
        }
        
        public function where_builder($entity)
        {
            $entity_id = $this->datab->get_entity_by_name($entity)['entity_id'];
            
            $entity_fields = $this->datab->get_entity_fields($entity_id);
            
            usort($entity_fields, function ($f1, $f2) {
                return $f1['fields_name'] <=> $f2['fields_name'];
            });
            
            $fields_for_query_builder = $this->_formatFieldForQueryBuilder($entity_fields);
            
            e_json($fields_for_query_builder);
        }
        
        private function _formatFieldForQueryBuilder($fields, $group = false, $prefix = '')
        {
            $data_fields = [];

            $fields_names = [];
            foreach ($fields as $field) {
                if (!in_array($field['fields_name'], $fields_names)) {
                    $fields_names[] = $field['fields_name'];
                    $query_builder_field_data = $this->_buildQueryBuilderFieldData($field, $prefix);
                    if ($group) {
                        $query_builder_field_data['optgroup'] = $group;
                    }
                    $data_fields[] = $query_builder_field_data;
                } else { }
            }
            
            return $data_fields;
        }
        
        private function _buildQueryBuilderFieldData($field, $prefix = '')
        {
            $data = [];
            
            $data['id'] = $prefix . $field['fields_name'];
            
            $data['label'] = "{$field['fields_draw_label']}";
            $validation = $operators = $plugin = $plugin_config = null;
            
            switch ($field['fields_draw_html_type']) {
                case 'input_text':
                    $type = 'string';
                    $operators = ['equal', 'in', 'is_null', 'is_not_null', 'contains', 'ends_with', 'begins_with', 'is_empty'];
                    break;
                case 'radio':
                    $type = 'integer';
                    $input = 'radio';
                    $operators = ['equal', 'is_null', 'is_not_null'];
                    $values = [DB_BOOL_TRUE, DB_BOOL_FALSE];
                    break;
                case 'date_time':
                case 'date':
                    $type = 'date';
                    $validation = ['format' => 'YYYY-MM-DD'];
                    $plugin = 'datepicker';
                    $plugin_config = [
                        'format' => 'yyyy-mm-dd',
                        'todayBtn' => 'linked',
                        'todayHighlight' => true,
                        'autoclose' => true,
                    ];
                    break;
                case 'select':
                    $type = 'string';
                    $input = null;
                    $values = null;
                    if ($field['fields_ref'] && $this->crmentity->entityExists($field['fields_ref'])) {
                        $source_entity = $this->datab->get_entity_by_name($field['fields_ref']);
                        $source_entity['fields'] = $this->datab->get_entity_fields($source_entity['entity_id']);
                        
                        if ($source_entity['entity_type'] == ENTITY_TYPE_SUPPORT_TABLE) {
                            $previews_fields = [['fields_name' => $source_entity['entity_name'] . '_value']];
                        } else {
                            //Trovo i campi in preview
                            $previews_fields = [];
                            foreach ($source_entity['fields'] as $sub_field) {
                                if ($sub_field['fields_preview']) {
                                    $previews_fields[] = $sub_field;
                                }
                            }
                        }
                        
                        //debug($source_entity,true);
                        $count = $this->db->count_all($source_entity['entity_name']);
                        if ($previews_fields && $count < 100 && $count > 0) {
                            $type = 'integer';
                            $input = 'select';
                            $operators = ['equal', 'is_null', 'in'];
                            
                            $values = array_map(function ($row) use ($source_entity, $previews_fields) {
                                return [
                                    'value' => $row[$source_entity['entity_name'] . '_id'],
                                    'label' => $row[$previews_fields[0]['fields_name']],
                                ];
                            }, $this->db->get($source_entity['entity_name'])->result_array());
                            //debug($values);
                            $values = array_merge(
                                [
                                    [
                                        'value' => '{value_id}',
                                        'label' => 'Current record (value_id)',
                                    ],
                                    [
                                        'value' => '',
                                        'label' => '---',
                                    ],
                                ],
                                $values
                            );
                        }
                    }
                    
                    break;
                default:
                    $type = 'string';
                    $operators = ['equal', 'in', 'is_null', 'is_not_null', 'contains', 'ends_with', 'begins_with', 'is_empty'];
                    $data['label'] = $data['label'] . ' (type ' . $field['fields_draw_html_type'] . '  beta)';
                    break;
            }
            
            if (!empty($operators)) {
                foreach ($operators as $ope) {
                    $operators[] = 'not_' . $ope;
                }
            }
            
            $data['type'] = $type;
            $data['operators'] = $operators;
            $data['values'] = $values ?? null;
            $data['input'] = $input ?? null;
            $data['validation'] = $validation;
            $data['plugin'] = $plugin;
            $data['plugin_config'] = $plugin_config;
            
            return $data;
        }
        
        public function add_fields($grid_id = null) {
            if (!$grid_id) {
                e_json(['status' => 0, 'txt' => t('Id not found')]);
                return false;
            }
            
            $post = $this->input->post();
            
            if (empty($post) || empty($post['fields'])) {
                e_json(['status' => 0, 'txt' => t('No column selected')]);
                return false;
            }
            
            try {
                $grid_fields_id = [];
                foreach ($post['fields'] as $field_id) {
                    $this->db
                        ->insert('grids_fields', [
                            'grids_fields_grids_id' => $grid_id,
                            'grids_fields_fields_id' => $field_id,
                            'grids_fields_order' => 99,
                        ]);
                    
                    $grid_fields_id[$field_id] = $this->db->insert_id();
                }
                
                e_json(['status' => 9, 'txt' => 'refresh_preview()', 'data' => ['grid_field_map' => $grid_fields_id]]);
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => t('Error occurred while saving fields to export')]);
            }
        }
        
        public function remove_fields($grid_id = null) {
            if (!$grid_id) {
                e_json(['status' => 0, 'txt' => t('Id not found')]);
                return false;
            }
            
            $post = $this->input->post();
            
            if (empty($post) || empty($post['fields'])) {
                e_json(['status' => 0, 'txt' => t('No column selected')]);
                return false;
            }
            
            try {
                $this->db
                    ->where('grids_fields_grids_id', $grid_id)
                    ->where_in('grids_fields_id', $post['fields'])
                    ->delete('grids_fields');
                
                e_json(['status' => 1, 'txt' => t('Fields removed')]);
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => t('Error occurred while removing fields')]);
            }
        }
        
        public function reorder_fields() {
            $post = $this->input->post();
            
            if (empty($post['fields'])) {
                e_json(['status' => 0, 'txt' => 'Nessun campo passato']);
                return false;
            }
            
            foreach ($post['fields'] as $index => $field_id) {
                $pos = $index + 1;
                
                $this->db
                    ->where('grids_fields_grids_id', $post['grid_id'])
                    ->where('grids_fields_id', $field_id)
                    ->update('grids_fields', ['grids_fields_order' => $pos]);
            }
            
            $this->mycache->clearCache();
            
            e_json(['status' => 1, 'txt' => "Ordine campi salvato"]);
        }
        
        public function save_filters($template_id = null) {
            if (!$template_id) {
                e_json(['status' => 0, 'txt' => t('Template ref not passed')]);
                return false;
            }
            
            $post = $this->input->post();
            
            if (empty($post) || empty($post['conditions'])) {
                $this->clear_filters($template_id, false);

                e_json(['status' => 5, 'txt' => t('No filter applied')]);

                return false;
            }
            
            // debug($post, true);
            
            $errors = [];
            $c_row = 1;
            foreach ($post['conditions'] as $index => $condition) {
                if (empty($condition['field_id']) && empty($condition['operator'])) {
                    unset($post['conditions'][$index]);
                    $c_row++;
                    continue;
                }
                
                if (empty($condition['field_id'])) {
                    $errors[] = t("Field on row %s is required", true, [$c_row]);
                }

                if (empty($condition['operator'])) {
                    $errors[] = t("Operator on row %s is required", true, [$c_row]);
                }

                if (empty($condition['value']) && !in_array($condition['operator'], ['empty', 'not_empty'])) {
                    $errors[] = t("Value on row %s is required", true, [$c_row]);
                }
                
                $c_row++;
            }

            if (!empty($errors)) {
                e_json(['status' => 0, 'txt' => implode('<br/>', $errors)]);

                return false;
            }
            
            $template = $this->apilib->view('exporter_templates', $template_id);
            
            if (empty($template)) {
                e_json(['status' => 0, 'txt' => t('Template not found')]);
                return false;
            }
            
            $grid = $this->datab->get_grid($template['exporter_templates_grid_id'])['grids'];
            
            ////////////////////////////////////////////////////////////////////
            $filterSessionKey = $grid['grids_filter_session_key'];
            
            $entity = $this->crmentity->getEntityFullData($grid['grids_entity_id']);
            
            $_visible_fields = $entity['visible_fields'];
            $visible_fields = [];
            foreach ($_visible_fields as $field) {
                $visible_fields[$field['fields_id']] = $field;
            }
            
//            debug($entity);
//            debug($visible_fields, true);
            
            // Processo le condizioni da salvare in input
            $conditions = [];
            $where_data = $this->session->userdata(SESS_WHERE_DATA);
            
            // Se invece ho fatto un submit normale, valuto le condizioni valide
            // da tenere in sessione
//            debug($post, true);
            foreach ($post['conditions'] as $index => $conditional) {
                if (!array_key_exists($conditional['field_id'], $visible_fields)) {
                    //TODO Wrong! Field id can be in another left joined table, so get the field information direct from the field_id to check his type...
                    //throw new Exception("Missing field '{$conditional['field_id']}' in entity '{$entity['entity']['entity_name']}'.");
                } elseif (array_key_exists('value', $conditional) && $conditional['value'] !== '') { //Se ho passato un valore devo verificarne la consistenza
                    //Check field consistency
                    
                    $error = false;
                    $error_text = '';
                    switch ($visible_fields[$conditional['field_id']]['fields_type']) {
                        case 'INT':
                        case 'TIMESTAMP WITHOUT TIME ZONE':
                        default:
                            break;
                    }
                    if ($error && !empty($error_text)) {
                        throw new Exception($error_text);
                    }
                }
                
                if (
                    !empty($conditional['operator']) &&
                    !empty($conditional['field_id']) &&
                    (array_key_exists('value', $conditional))
                ) {
                    if (!array_key_exists('value', $conditional)) {
                        $conditional['value'] = '';
                    }
                    // $conditions[$conditional['field_id']] = $conditional;
                    $conditions[$index] = $conditional;
                } else {
                    //unset($where_data[$filterSessionKey][$conditional['field_id']]);
                }
            }
            
            try {
                // salvo il json dei filtri sul campo exporter_templates_filters
                $this->apilib->edit('exporter_templates', $template_id, ['exporter_templates_filters' => json_encode($conditions)]);
            } catch (Exception $e) {
                log_message('error', 'Error occurred while saving filters for template #' . $template_id . ': ' . $e->getMessage());
                
                e_json(['status' => 0, 'txt' => t('Error occurred while saving filters')]);
                return false;
            }
            
//            debug($conditions, true);
            
            // Aggiorno i dati da sessione mettendo alla chiave corretta le
            // condizioni processate. Nel caso in cui siano vuote, queste verranno
            // rimosse con un array_filter
            
            $where_data[$filterSessionKey] = $conditions;
            
            $this->session->set_userdata(SESS_WHERE_DATA, array_filter($where_data));
            ////////////////////////////////////////////////////////////////////
            
            e_json(['status' => 2, 'txt' => t('Filters saved')]);
        }
        
        public function clear_filters($template_id = null, $return = true) {
            if (!$template_id) {
                e_json(['status' => 0, 'txt' => t('Template ref not passed')]);
                return false;
            }
            
            $template = $this->apilib->view('exporter_templates', $template_id);
            
            $this->apilib->edit('exporter_templates', $template_id, ['exporter_templates_filters' => null]);
            
            $grid = $this->datab->get_grid($template['exporter_templates_grid_id'])['grids'];
            
            if (!empty($_SESSION[SESS_WHERE_DATA][$grid['grids_filter_session_key']])) {
                unset($_SESSION[SESS_WHERE_DATA][$grid['grids_filter_session_key']]);
            }
            if ($return) {
                e_json(['status' => 2, 'txt' => t('Filters cleared')]);
            }
        }
        
        public function save_field_label() {
            $post = $this->input->post();
            
            if (empty($post['grid_id'])) {
                e_json(['status' => 0, 'txt' => "Id grid non passata"]);
                
                return false;
            }
            
            if (empty($post['campo'])) {
                e_json(['status' => 0, 'txt' => "Dati campo non passati"]);
                
                return false;
            }
            
            if (empty($post['campo']['id'])) {
                e_json(['status' => 0, 'txt' => "Id campo non passato"]);
                
                return false;
            }
            
            try {
                $this->db
                    ->where('grids_fields_id', $post['campo']['id'])
                    ->where('grids_fields_grids_id', $post['grid_id'])
                    ->update('grids_fields', [
                        'grids_fields_column_name' => $post['campo']['value'],
                    ]);
                
                $this->mycache->clearCache();
                
                e_json(['status' => 1, 'txt' => "Campo salvato"]);
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => "Errore salvataggio campo: " . $e->getMessage()]);
            }
        }
        
        public function get_support_values($entity, $source_field = null) {
            $additional_where_data = null;
            
            if (!empty($source_field)) {
                $field = $this->datab->get_field($source_field);
                
                if (!empty($field) && !empty($field['fields_select_where'])) {
                    $additional_where_data = '(' . $field['fields_select_where'] . ')';
                }
            }
            
            $data = $this->crmentity->getEntityPreview($entity, $additional_where_data);
            
            e_json(['status' => 1, 'txt' => '', 'data' => $data]);
            return;
        }
        
        public function save_allowed_entities() {
            $post = $this->input->post();
            
            $post_allowed_entities = $post['allowed_entities'] ?? [];
            
            $module_settings = $this->apilib->searchFirst('importer_settings');
            
            try {
                if (empty($module_settings)) {
                    $this->apilib->create('importer_settings', [
                        'importer_settings_enable_importer' => DB_BOOL_TRUE,
                        'importer_settings_enable_exporter' => DB_BOOL_TRUE,
                        'importer_settings_allowed_entities' => $post_allowed_entities ? json_encode($post_allowed_entities) : null
                    ]);
                } else {
                    $this->apilib->edit('importer_settings', $module_settings['importer_settings_id'], [
                        'importer_settings_allowed_entities' => $post_allowed_entities ? json_encode($post_allowed_entities) : null
                    ]);
                }
                
                e_json(['status' => 7, 'txt' => t('Allowed entities saved')]);
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => t('Error occurred while saving allowed entities:') . ' ' . $e->getMessage()]);
            }
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
