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

//TODO: Creo magazzino di default
$this->apilib->create('magazzini', [
    'magazzini_titolo' => 'Principale',
    'magazzini_colore' => '3c40c6',
    'magazzini_default' => 1,
    'magazzini_azienda' => 1
]);