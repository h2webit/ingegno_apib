<?php
class Entities extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
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

        if (empty($post['query']))
            die(e_json(['status' => 0, 'txt' => "Query not given"]));

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
        } catch (\Exception $e) {
            die(e_json(['status' => 0, 'txt' => 'Query error', 'error' => $e->getMessage()]));
        }
    }

    public function update_field_identifier()
    {
        //debug($this->input->post(), true);

        $field_id = $this->input->post('field_id');
        $field_identifier = $this->input->post('field_identifier');
        $field_idenfier_value = $this->input->post('field_idenfier_value');

        try {
            $field = $this->db->where('fields_id', $field_id)->get('fields')->row_array();

            //Caso speciale required: devo anche modificare la struttura della tabella, quindi invoco entities
            if ($field_identifier == 'fields_required') {
                $this->load->model('entities');

                $entity = $this->entities->get_entity($field['fields_entity_id']);

                $field['fields_required'] = $field_idenfier_value;
                //Il model entities appende di suo il nome della tabella nel name, quindi va preliminarmente tolto
                if (strpos($field['fields_name'], "{$entity['entity_name']}_") === 0) {
                    $field['fields_name'] = substr($field['fields_name'], strlen("{$entity['entity_name']}_"));
                }

                $this->entities->addFields([
                    'fields_id' => $field['fields_id'],
                    'entity_id' => $field['fields_entity_id'],
                    'fields' => [
                        $field,
                    ]
                ]);
            } else { //Altrimenti faccio banale update su fields di quest'informazione
                $this->db->where('fields_id', $field_id)->update('fields', ["$field_identifier" => "$field_idenfier_value"]);
            }

            // Check presence in events
            $events_count = $this->db->query("SELECT COUNT(*) AS count FROM fi_events WHERE fi_events_actiondata LIKE '%{$field['fields_name']}%'")->row()->count;

            //debug($this->db->last_query());
            if ($events_count > 0) {
                echo json_encode(array('status' => 1, 'txt' => 'This operation was successful, but this field is present in ' . $events_count . ' events. Make sure your modification does not affect smooth operation'));
            } else {
                if ($field_identifier == 'fields_required') {
                    echo json_encode(array('status' => 2));
                } else {
                    echo json_encode(array('status' => 1));
                }
            }
        } catch (Exception $e) {
            echo json_encode(array('status' => 0, 'txt' => 'error ' . $e->getMessage()));
        }
    }

    public function delete_entity($entity_id)
    {

    }
}