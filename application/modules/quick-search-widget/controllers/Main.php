<?php
class Main extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    public function search()
    {
        $search_string = $this->input->post("search");

        // Get searchable entities
        $entities = $this->db->query("SELECT * FROM quick_search_entities LEFT JOIN entity ON quick_search_entities_entity_name = entity_name ORDER BY quick_search_entities_order_entity ASC")->result_array();

        $results = [];

        foreach ($entities as $entity) {

            // Check if entity has name, prevent errors because i can install module with "keep data" 
            if (empty($entity['entity_name'])) {
                continue;
            }

            // Check if only for admin or skip
            if ($entity['quick_search_entities_check_users_group'] == DB_BOOL_TRUE && $this->auth->is_admin() == false) {
                // Get related users group
                $check_user_group = $this->db->get_where('rel_quick_search_users_type', ['quick_search_entities_id' => $entity['quick_search_entities_id'], 'users_type_id' => $this->auth->get('users_type')])->num_rows();

                // Skip this entity
                if ($check_user_group < 1) {
                    continue;
                }
            }

            // Layout detail
            if (!empty($entity['quick_search_entities_override_layout_detail'])) {
                $layout_detail_link = base_url($entity['quick_search_entities_override_layout_detail']);
            } else {
                $layout_detail_link = $this->datab->get_detail_layout_link($entity['entity_id']);
            }

            // Reset fields
            $fields = [];

            // Search fields
            if (empty($entity['quick_search_entities_searchable_fields'])) {
                $fields = $this->crmentity->getEntityPreviewFields($entity['entity_id']);
            } else {
                $overwrite_searchable_fields = explode(',', $entity['quick_search_entities_searchable_fields']);

                foreach ($overwrite_searchable_fields as $key => $field) {
                    $fields[$key] = ['fields_name' => $field];
                }
            }

            if (count($fields) == 0) {
                continue;
            }

            // Costruire dinamicamente la clausola WHERE
            $whereClauses = [];

            // Add id
            $fields[] = array('fields_name' => $entity['entity_name'] . '_id');
            $fields = array_reverse($fields);

            foreach ($fields as $field) {
                if ($field['fields_name'] == $entity['entity_name'] . '_id') {
                    $whereClauses[] = $field['fields_name'] . " = ?";
                } else {
                    $whereClauses[] = $field['fields_name'] . " LIKE ?";
                }
            }

            // Select / Preview fields
            if (empty($entity['quick_search_entities_preview_fields'])) {
                $preview_fields = $this->crmentity->getEntityPreviewFields($entity['entity_id']);
                $select_fields = implode(',', array_column($preview_fields, 'fields_name'));
            } else {
                $select_fields = $entity['quick_search_entities_preview_fields'];
            }

            // Add id if not exists
            if (strpos($select_fields, $entity['entity_name'] . '_id') === false) {
                $select_fields = $entity['entity_name'] . '_id,' . $select_fields;
            }

            // $select_fields = implode(',', array_column($fields, 'fields_name'));
            $whereClause = implode(' OR ', $whereClauses);

            // Add check soft delete
            if (!empty($entity['entity_action_fields'])) {
                $entityCustomActions = empty($entity['entity_action_fields']) ? [] : json_decode($entity['entity_action_fields'], true);
                if (array_key_exists('soft_delete_flag', $entityCustomActions) && !empty($entityCustomActions['soft_delete_flag'])) {
                    $whereClause = "($whereClause) AND ({$entityCustomActions['soft_delete_flag']} = " . DB_BOOL_FALSE . ")";
                }
            }

            // Costruire la query
            $orderby = '';
            if (!empty($entity['quick_search_entities_order_by'])) {
                $orderby = "ORDER BY {$entity['quick_search_entities_order_by']}";
            }
            if (!empty($entity['quick_search_entities_append_where'])) {
                $query = "SELECT $select_fields FROM {$entity['entity_name']} {$entity['quick_search_entities_append_join']} WHERE {$entity['quick_search_entities_append_where']} AND ($whereClause) $orderby";
            } else {
                $query = "SELECT $select_fields FROM {$entity['entity_name']} {$entity['quick_search_entities_append_join']} WHERE $whereClause $orderby";
            }
            // Preparare la dichiarazione per prevenire l'iniezione SQL
            $stmt = $this->db->conn_id->prepare($query);

            if (!$stmt) {
                log_message('error', 'Error in query search of quick search widget: ' . $query);
                continue;
            }

            // Creare dinamicamente i termini di ricerca in base ai campi specifici
            $searchTerms = array_fill(0, count($fields) - 1, "%$search_string%"); // Escludere l'ID dall'array dei termini di ricerca
            array_unshift($searchTerms, $search_string); // Aggiungere $search_string all'inizio per l'ID

            // Preparare i tipi di dati per il bind (assumendo che tutti i campi siano stringhe)
            $types = str_repeat('s', count($fields));

            // Eseguire il binding dei parametri dinamicamente
            $stmt->bind_param($types, ...$searchTerms);

            // Eseguire la query
            $stmt->execute();

            $result = $stmt->get_result();

            $result_data = $result->fetch_all(MYSQLI_ASSOC);



            // Prepare results
            $results[$entity['entity_name']]['total'] = $result->num_rows;
            $results[$entity['entity_name']]['layout_detail_link'] = $layout_detail_link;
            $results[$entity['entity_name']]['layout_detail_modal'] = $entity['quick_search_entities_layout_detail_modal'];
            $results[$entity['entity_name']]['layout_detail_in_modal_params'] = $entity['quick_search_entities_layout_detail_in_modal_params'];
            $results[$entity['entity_name']]['entity_name'] = $entity['entity_name'];
            $results[$entity['entity_name']]['preview_print'] = $entity['quick_search_entities_preview_print'];
            $results[$entity['entity_name']]['data'] = $result_data;
        }

        $dati['related_entities'] = $this->layout->getRelatedEntities();
        //debug($dati['related_entities']);
        $dati['layout_id'] = null;
        $dati['value_id'] = null;
        
        // Processare i risultati come necessario
        $pagina = $this->load->view('quick-search-widget/results', ['results' => $results], true);


        e_json(['pagina' => $pagina, 'dati' => $dati]);
    }



    public function save_entities()
    {

        $data = $this->input->post();

        if (!empty($data['quick_entities'])) {

            foreach ($data['quick_entities'] as $entity_name) {

                $entities_name_in_post[] = $entity_name;

                // Check if exist
                if ($this->db->query("SELECT * FROM quick_search_entities WHERE quick_search_entities_entity_name = '$entity_name'")->num_rows() > 0) {
                    continue;
                }

                $entity = $this->db->get_where('entity', ['entity_name' => $entity_name])->row_array();

                $insert_data['quick_search_entities_entity_name'] = $entity_name;

                // Search default form id
                $form = $this->crmentity->getDefaultForm($entity['entity_id']);
                if (!empty($form)) {
                    $insert_data['quick_search_entities_form_id'] = $form['forms_id'];
                }

                $this->db->insert('quick_search_entities', $insert_data);
            }

            // $this->db->query("DELETE FROM quick_search_entities WHERE quick_search_entities_entity_name NOT IN (" . implode(',', $entities_name_in_post) . ")");

            // Aggiunge apici singoli attorno ad ogni elemento dell'array
            $quoted_entities = array_map(function ($item) {
                return "'" . $item . "'";
            }, $entities_name_in_post);

            // Crea una stringa dalla lista di elementi, ora con gli apici
            $entities_names_string = implode(',', $quoted_entities);

            // Costruisce la query con i valori correttamente racchiusi tra apici
            $query = "DELETE FROM quick_search_entities WHERE quick_search_entities_entity_name NOT IN ($entities_names_string)";

            // Esegue la query
            $this->db->query($query);
        }

        echo json_encode([
            'status' => 11,
            'txt' => 'Saved!'
        ]);
    }
}
?>