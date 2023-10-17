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
                'notifications_title' => "Aggiornamento fatturazione",
                'notifications_message' => "E' stato aggiornato il modulo per la fatturazione. Da oggi è possibile ordinare le righe articolo cliccando sull'apposita icona e trascinando la riga nella posizione corretta. <br /><br />Se avete uno o più template di stampa personalizzati e l'ordinamento non venisse mantenuto in fase di stampa, vi chiediamo gentilmente di aprire un ticket in merito che verrà risolto nel giro di pochi minuti adeguando il template al nuovo sistema di ordinamento.<br /><br />Cordiali saluti,<br />lo staff.",
                //'notifications_link' => ""
            )
        );
    }

}
