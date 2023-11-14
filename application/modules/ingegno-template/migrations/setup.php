<?php

echo_log('info', '[INGEGNO TPL MODULE] Starting module install');

// Check settings installed
if (!$this->datab->module_installed('settings-manager')) {
    echo_log('error', 'Settings manager required to install this module. Failed.');
    return;
}

if (file_exists(APPPATH . 'modules/ingegno-template/uploads/tpl_ingegno.zip')) {
    $this->load->model('settings-manager/templates_model');
    
    echo_log('info', '[INGEGNO TPL MODULE] Checking row on settings_template folder');
    // Get Template if exist or create
    $setting_template = $this->db->where('settings_template_folder', 'ingegno')->get('settings_template')->row_array();
    
    if (empty($setting_template)) {
        echo_log('info', '[INGEGNO TPL MODULE] Row not present, creating now...');
        
        $template['settings_template_name'] = 'Ingegno';
        $template['settings_template_folder'] = 'ingegno';
        
        $this->db->insert('settings_template', $template);
        $id = $this->db->insert_id();
    } else {
        echo_log('info', '[INGEGNO TPL MODULE] Row found, getting id');
        $id = $setting_template['settings_template_id'];
    }

    // Extract
    echo_log('info', '[INGEGNO TPL MODULE] Extracting zip');
    try {
        $this->templates_model->extract(APPPATH . 'modules/ingegno-template/uploads/tpl_ingegno.zip');

        @copy(APPPATH . 'modules/ingegno-template/assets/images/ingegno_suite.png', FCPATH . 'uploads/ingegno_suite.png');
    
        echo_log('info', '[INGEGNO TPL MODULE] Updating settings_template db field');
        
        $this->db->update('settings', ['settings_template' => $id, 'settings_topbar_logo' => 'ingegno_suite.png']);
        
        $this->mycache->clearCache();
    } catch (Exception $e) {
        echo_log('error', 'Unable to extract template. ' . $e->getMessage());
    }
} else {
    echo_log('error', 'Template zip does not exists.');
}
