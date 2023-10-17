<?php
$this->load->model('core');
$core_modules = [
    'customers',
    'currencies',
    'payments-subscriptions',
    'todo-list',
    'scadenzario',
    'openapi-integration'
];
foreach ($core_modules as $module) {
    if ($this->datab->module_installed($module)) {
        //$this->core->updateModule($module);
    } else {
        $this->core->installModule($module);
    }
}

// Set contabilita full false
$this->db->query("UPDATE documenti_contabilita_general_settings SET documenti_contabilita_general_settings_contabilita_full = '" . DB_BOOL_FALSE . "'");