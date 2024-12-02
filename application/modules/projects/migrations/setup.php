<?php
$this->load->model('core');

//auto update ultima patch stable
$recursive_to_last = true;
$last_version = $this->core->updatePatches(null, 4, $recursive_to_last);


$core_modules = [
    'planner',
    'billable-hours'
];


foreach ($core_modules as $module) {
    if ($this->datab->module_installed($module)) {
        $this->core->updateModule($module);
    } else {
        $this->core->installModule($module);
    }
}
