<?php
    class Export_utils_model extends CI_Model {
        public function get_grid_available_fields($grid) {
            $dati = [];
            
            $fields = $this->db
                ->join('fields_draw', 'fields_id=fields_draw_fields_id', 'left')
                ->join('entity', 'fields_entity_id=entity_id', 'left')
                ->order_by('fields_draw_label', 'ASC')
                ->get_where('fields', array('fields_entity_id' => $grid['grids_entity_id']))->result_array();
            
            $dati[$grid['entity_name']] = $fields;
            
            $sub_entities = array();
            foreach ($fields as $field) {
                if ($field['fields_ref'] && !in_array($field['fields_ref'], $sub_entities)) {
                    $sub_entities[] = $field['fields_ref'];
                }
            }
            
            if ($sub_entities) {
                foreach ($sub_entities as $sub_entity) {
                    $dati[$sub_entity] = $this->db->where_in('entity_name', $sub_entity)
                        ->join('fields_draw', 'fields_id=fields_draw_fields_id', 'left')
                        ->order_by('fields_draw_label', 'ASC')
                        ->get_where('fields LEFT JOIN entity ON (fields_entity_id=entity_id)')->result_array();
                }
            }
            
            return $dati;
        }
    }