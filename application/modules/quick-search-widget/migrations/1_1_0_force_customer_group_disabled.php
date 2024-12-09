<?php

// Set check user group to every record
$this->db->query("UPDATE quick_search_entities SET quick_search_entities_check_users_group = 1");

$records = $this->db->get('quick_search_entities')->result_array();

$user_type_id = $this->db->get_where('users_type', ['users_type_value' => 'Customer'])->row()->users_type_id;

if (!empty($user_type_id)) {
    foreach ($records as $record) {
        $this->db->insert('rel_quick_search_users_type', [
            'quick_search_entities_id' => $record['quick_search_entities_id'],
            'users_type_id' => $user_type_id
        ]);
    }
}
