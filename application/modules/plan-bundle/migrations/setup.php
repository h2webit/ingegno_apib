<?php
$this->load->model('core');
$core_modules = [
    'customers',
    'currencies',
    'contabilita',
    'projects',
    'products_manager',
    'magazzino',
    'planner-squadre',
    'rapportini',
    'modulo-hr',
    'plan'
];
foreach ($core_modules as $module) {
    if ($this->datab->module_installed($module)) {
        //$this->core->updateModule($module);
    } else {
        $this->core->installModule($module);
    }
}