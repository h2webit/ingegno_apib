<?php

/*$this->db->query(
    "INSERT INTO `users_type` (`users_type_value`) VALUES('Admin');"
);*/
$this->apilib->clearCache();
$tipologia = $this->db->query("SELECT DISTINCT users_type_value,users_type_id FROM users_type")->result_array();
$dashobard = $this->apilib->searchFirst('layouts', ['layouts_dashboardable' => 1]);
foreach ($tipologia as $tipologia_user) {
    if ($tipologia_user) {
        $user_type = $tipologia_user['users_type_value'];
        if ($user_type) {
            $this->db->insert('users', [
                'users_first_name' => $tipologia_user['users_type_value'],
                'users_last_name' => 'Ingegno',
                'users_email' => strtolower(str_replace(' ', '', $tipologia_user['users_type_value']))."@ingegnosuite.it",
                'users_password' => 'IngegnoSuite',
                'users_active' => DB_BOOL_TRUE,
                'users_deleted' => DB_BOOL_FALSE,
                'users_type' => $tipologia_user['users_type_id'],
                'users_temporary_password' => DB_BOOL_FALSE,
                'users_default_dashboard' => $dashobard['layouts_id']
            ]);
            $this->apilib->clearCache();
            $permissions = $this->db->get_where('permissions', array('permissions_group' => $user_type));
            if ($permissions->num_rows() == 0) {
                //non c'è in permission, quindi vado a creare la riga
                $dati_db['permissions_admin'] = 0;
                $dati_db['permissions_group'] = $user_type;
                $dati_db['permissions_user_id'] = null;

                //prima il campo users_id vuoto così da inizializzarlo
                $this->db->insert('permissions', $dati_db);
                //per ogni utente che ha quel permesso, lo vado ad impostare
                $users = $this->db->query("SELECT users_id FROM users WHERE users_type = ".$tipologia_user['users_type_id']."")->result_array();
                foreach ($users as $user) {
                    if ($user['users_id']) {
                        $dati_db['permissions_user_id'] = $user['users_id'];
                        $this->db->insert('permissions', $dati_db);
                        //ora imposto i permessi su 0 a tutto, a parte la dashboard iniziale
                        $dati_permissions['unallowed_layouts_user'] = $user['users_id'];
                        $layouts = $this->db->query("SELECT layouts_id FROM layouts")->result_array();
                        foreach ($layouts as $layout) {
                            //escludo il primo dashboard layout
                            if ($layout['layouts_id'] != $dashobard['layouts_id']) {
                                $dati_permissions['unallowed_layouts_layout'] = $layout['layouts_id'];
                                $this->db->insert('unallowed_layouts', $dati_permissions);
                            }
                        }
                    }
                }
            }
        }
    }
}
$this->apilib->clearCache();
