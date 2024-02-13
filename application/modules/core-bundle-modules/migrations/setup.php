<?php
$this->load->model('core');
$core_modules = [
    'builder-toolbar',
    'user-extender',
    'profile',
    'settings-manager',
    'module-manager',
    'backup',
    'mailer',
    'core-file-manager',
    'upgrade-new-notification-core',
    'support-table-manager',
    'core-modules-permissions',
    'infobox'
];
foreach ($core_modules as $module) {
    if ($this->datab->module_installed($module)) {
        //$this->core->updateModule($module);
    } else {
        $this->core->installModule($module);
    }
}