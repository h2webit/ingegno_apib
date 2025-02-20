<?php
    class Ajax extends MY_controller {
        public function __construct() {
            parent::__construct();
            
            if (!$this->auth->check()) {
                if ($this->input->is_ajax_request()) {
                    e_json(['status' => 0, 'txt' => t('You are not logged in')]);
                } else {
                    show_404();
                }
                exit;
            }
        }
        
        public function get_user_type_accessible_layouts($user_type_id) {
            $user_can_access = $this->apilib->searchFirst('users', ['users_type' => $user_type_id]);
            
            if (empty($user_can_access)) {
                e_json(['status' => 0, 'txt' => t('No user found for this type')]);
                return;
            }
            
            // Ottieni tutti i layout dashboardable
            $query = "
                SELECT l.layouts_id, l.layouts_title
                FROM layouts l
                WHERE l.layouts_dashboardable = 1
                ORDER BY l.layouts_title ASC
            ";
            
            $result = $this->db->query($query);
            $all_layouts = $result->result_array();
            
            // Ottieni i layout non consentiti per questo utente
            $unallowed_query = "
                SELECT unallowed_layouts_layout
                FROM unallowed_layouts
                WHERE unallowed_layouts_user = ?
            ";
            
            $unallowed_result = $this->db->query($unallowed_query, [$user_can_access['users_id']]);
            $unallowed_layouts = $unallowed_result->result_array();
            
            // Crea un array di ID di layout non consentiti
            $unallowed_ids = array_column($unallowed_layouts, 'unallowed_layouts_layout');
            
            // Filtra i layout accessibili
            $accessible_layouts = array_filter($all_layouts, function($layout) use ($unallowed_ids) {
                return !in_array($layout['layouts_id'], $unallowed_ids);
            });
            
            if (empty($accessible_layouts)) {
                e_json(['status' => 0, 'txt' => t('No accessible layouts found for this user type')]);
                return;
            }
            
            e_json(['status' => 1, 'txt' => t('Layouts accessible to this user type'), 'layouts' => array_values($accessible_layouts)]);
        }
    }