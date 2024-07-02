<?php
$this->load->model('core');
$core_modules = [
    'contabilita',
    'products_manager',
];
foreach ($core_modules as $module) {
    if ($this->datab->module_installed($module)) {
        //$this->core->updateModule($module);
    } else {
        $this->core->installModule($module);
    }
}