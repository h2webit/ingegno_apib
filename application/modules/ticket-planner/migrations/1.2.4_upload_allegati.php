<?php
if ($this->datab->module_installed('core-notifications')) {
    $this->load->model('core-notifications/clientnotifications');
    $admin_users = $this->apilib->search('users', ['users_type_value' => 'Admin']);
    foreach ($admin_users as $user) {
        $user_id = $user['users_id'];
        $this->clientnotifications->create(
            array(
                'notifications_type' => 5,
                'notifications_user_id' => $user_id,
                'notifications_title' => "Aggiornamento Assistenza CRM",
                'notifications_message' => "Siamo lieti di annunciare l'aggiornamento dell'assistenza CRM, che include una nuova funzionalità molto attesa. Ora è possibile caricare gli allegati nei ticket anche dopo l'apertura di essi.<br /><br />Cordiali saluti,<br />lo staff.",
            )
        );
    }
    
}
