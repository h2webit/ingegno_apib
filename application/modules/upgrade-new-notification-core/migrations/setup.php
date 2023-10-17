<?php



$this->load->model('core');

if ($this->datab->module_installed('core-notifications')) {
    //nothing...
} else {
    // Drop native notifications table
    $this->db->query("DROP TABLE IF EXISTS notifications;");

    $this->core->installModule('core-notifications');
}
