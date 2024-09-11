<?php

class Core_entities extends MY_Controller
{
    private $selected_db;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->selected_db = $this->db;
        
        $this->load->model("entities");
    }

    public function set_module($type, $id)
    {
        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['modules'])) {
            $post['modules'] = null;
        } else {
            if (is_array($post['modules'])) {
                $post['modules'] = implode(',', $post['modules']);
            }
        }

        try {
            switch (strtolower($type)) {
                case 'entity':
                    $this->db->where('entity_id', $id)->update('entity', ['entity_module' => $post['modules']]);
                    break;
                case 'relation':
                    $this->db->where('relations_id', $id)->update('relations', ['relations_module' => $post['modules']]);
                    break;
                default:
                    die(e_json(['status' => 0, 'txt' => t('Unrecognized type')]));
                    break;
            }

            die(e_json(['status' => 1, 'txt' => t('Module set')]));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => t('Error occurred while setting module to entity')]));
        }
    }

    public function run_query()
    {
        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['query'])) die(e_json(['status' => 0, 'txt' => "Query not given"]));

        try {
            $dbquery = $this->db->query($post['query']);

            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception($db_error['message']);
                return false;
            }

            $rows = $dbquery->result_array();

            $this->apilib->create('core_query_log', [
                'core_query_log_query' => $post['query'],
                'core_query_log_created_by' => $this->auth->get('users_id')
            ]);

            $columns = [];
            if (!empty($rows)) {
                $header = array_keys($rows[0]);

                $columns = array_map(function ($h) {
                    return ['data' => $h, 'title' => $h];
                }, $header);
            }

            die(e_json(['status' => 1, 'txt' => 'Here are the query results!', 'data' => ['rows' => $rows, 'columns' => $columns]]));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => 'Query error', 'error' => $e->getMessage()]));
        }
    }

    public function update_field_identifier()
    {
        $field_id = $this->input->post('field_id');
        $field_identifier = $this->input->post('field_identifier');
        $field_identifier_value = $this->input->post('field_identifier_value');

        if (empty($field_id) || empty($field_identifier) || (!is_numeric($field_identifier_value) && empty((string) $field_identifier_value))) die(e_json(['status' => 0, 'txt' => t("Invalid data")]));
        
        try {
            $field = $this->db->where('fields_id', $field_id)->get('fields')->row_array();

            //Caso speciale required: devo anche modificare la struttura della tabella, quindi invoco entities
            if ($field_identifier == 'fields_required') {
                $entity = $this->entities->get_entity($field['fields_entity_id']);

                $field['fields_required'] = $field_identifier_value;
                //Il model entities appende di suo il nome della tabella nel name, quindi va preliminarmente tolto
                if (strpos($field['fields_name'], "{$entity['entity_name']}_") === 0) {
                    $field['fields_name'] = substr($field['fields_name'], strlen("{$entity['entity_name']}_"));
                }

                $this->entities->addFields(['fields_id' => $field['fields_id'], 'entity_id' => $field['fields_entity_id'], 'fields' => [
                    $field,
                ]]);
            } else { //Altrimenti faccio banale update su fields di quest'informazione
                $this->db->where('fields_id', $field_id)->update('fields', ["$field_identifier" => "$field_identifier_value"]);
            }

            // Check presence in events
            $events_count = $this->db->query("SELECT COUNT(*) AS count FROM fi_events WHERE fi_events_actiondata LIKE '%{$field['fields_name']}%'")->row()->count;

            if ($events_count > 0) {
                echo json_encode(array('status' => 1, 'txt' => t('This operation was successful, but this field is present in %s events. Make sure your modification does not affect smooth operation', [$events_count])));
            } else {
                echo json_encode(array('status' => 1, 'txt' => t("Successfully updated")));
            }
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => $e->getMessage()]));
        }
    }

    public function save_field() {
        $post = $this->security->xss_clean($this->input->post());
        
        $db_entity = $this->selected_db->where('entity_id', $post['entity_id'])->get('entity')->row_array();
        
        if (empty($post['fields_id'])) {
            $unique_fields = [];
            
            foreach ($post['fields'] as $key => $field) {
                $db_field = $this->selected_db
                    ->where('LOWER(fields_name)', strtolower($db_entity['entity_name'] . '_' . $field['fields_name']))
                    ->get('fields')->row_array();

                if (in_array($field['fields_name'], $unique_fields) || !empty($db_field)) {
                    unset($post['fields'][$key]);
                } else {
                    $unique_fields[] = $field['fields_name'];
                }
            }
        }
    
        try {
            $this->entities->addFields($post);
            
            die(e_json(['status' => 4, 'txt' => t("Field(s) saved")]));
        } catch (ApiException $e) {
            die(e_json(['status' => 0, 'txt' => $e->getMessage()]));
        }
    }
    
    public function delete_field($field_id, $physical = true)
    {
        
        $this->selected_db->trans_start();
        
        $field = $this->db
            ->join('entity', 'fields.fields_entity_id = entity.entity_id', 'LEFT')
            ->where('fields_id', $field_id)
            ->get('fields')->row_array();
        
        
        $entity_name = $field['entity_name'];
        $field_name = $field['fields_name'];
        
        if (!$this->selected_db->table_exists($entity_name)) die(e_json(['status' => 0, 'txt' => t("Db table associated to this field does not exists")]));
        
        if (empty($field) || !$this->selected_db->field_exists($field_name, $entity_name)) die(e_json(['status' => 0, 'txt' => t('Field does not exists')]));
        
        // Se il field ha una action speciale, devo aggiornare il dato
        if (isset($field['entity_action_fields']) && $field['entity_action_fields']) {
            $actions = json_decode($field['entity_action_fields'], true);
            $actionsForThisField = array_keys($actions, $field_name);
            $jsonOrNull = $this->entities->processEntityCustomActionFields($field['entity_action_fields'], array_fill_keys($actionsForThisField, null));
            $this->selected_db->update('entity', ['entity_action_fields' => $jsonOrNull], ['entity_id' => $field['entity_id']]);
        }
        
        try {
            // Elimino dalla tabella
            $this->selected_db->delete('fields', array('fields_id' => $field_id));
    
            // Elimino da draw
            $this->selected_db->delete('fields_draw', array('fields_draw_fields_id' => $field_id));
    
            // Elimino da forms fields
            $this->selected_db->delete('forms_fields', array('forms_fields_fields_id' => $field_id));
    
            // Elimino da grids fields
            $this->selected_db->delete('grids_fields', array('grids_fields_fields_id' => $field_id));
    
            // Elimino da calendars fields
            $this->selected_db->delete('calendars_fields', array('calendars_fields_fields_id' => $field_id));
    
            // Elimino da maps fields
            $this->selected_db->delete('maps_fields', array('maps_fields_fields_id' => $field_id));
    
            // Elimino da charts fields
            $this->selected_db->delete('charts_elements', array('charts_elements_fields_id' => $field_id));
    
            // Elimino da fields validations
            $this->selected_db->delete('fields_validation', array('fields_validation_fields_id' => $field_id));
    
            // Elimino la colonna (per qualche motivo potrebbe fallire: magari esiste in fields, ma non esiste nella tabella). Metto quindi in un try/catch
            $this->dbforge->drop_column($entity_name, $field['fields_name']);
    
            $this->selected_db->trans_complete();
            
            die(e_json(['status' => 1, 'txt' => t("Field deleted successfully"), 'url' => base_url('main/layout/entity-fields/' . $field['fields_entity_id'])]));
        } catch (Exception $e) {
            $this->selected_db->trans_rollback();
            
            log_message("error", $e->getMessage());
            
            die(e_json(['status' => 0, 'txt' => t("Something gone wrong. Cannot delete the entity field")]));
        }
    }
    
    public function delete_entity()
    {
        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['entity_id']) || empty($post['entity_name'])) die(e_json(['status' => 0, 'txt' => t("Id and/or name not passed")]));
        
        try {
            $this->entities->delete_entity($post['entity_id'], $post['entity_name']);
            
            die(e_json(['status' => 1, 'txt' => t("Entity deleted")]));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => $e->getMessage()]));
        }
    }
}
