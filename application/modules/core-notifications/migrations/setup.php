<?php
$this->load->model('core');
$this->mycache->clearCache();

// my_log('debug', "SETUP Table exists notifications: " . ($this->db->table_exists('notifications') ? 'yes' : 'no'), 'update');
// my_log('debug', "SETUP Entity exists notifications: " . ($this->db->get_where('entity', ['entity_name' => 'notifications'])->num_rows() ? 'yes' : 'no'), 'update');

//Se esiste la tabella, ma non è un entità, molto probabilmnete il modulo è corrotto. Droppo e ricreo...
if ($this->db->table_exists('notifications') && $this->db->get_where('entity', ['entity_name' => 'notifications'])->num_rows() == 0 ) {
    if (!empty($already_in) && $already_in == true) {
        debug('ATTENZIONE: installazione modulo core notification fallita e continua a fallire. Blocco ricorsione... controllare migration 1.0.6!', true);
    } else {
        $already_in = true;
        $this->db->query("DROP TABLE IF EXISTS notifications;");
        $this->mycache->clearCache();
        $this->core->installModule('core-notifications');
    }
} elseif (!$this->db->table_exists('notifications')) {
    if (!empty($already_in) && $already_in == true) {
        debug('ATTENZIONE: installazione modulo core notification fallita e continua a fallire. Blocco ricorsione... controllare migration 1.0.6!', true);
    } else {
    $already_in = true;
    $this->mycache->clearCache();
    $this->core->installModule('core-notifications');
}
}