<?php

echo_log('info', '[INGEGNO TPL MODULE] Starting module install<br/>');

// Check settings installed
if (!$this->datab->module_installed('settings-manager')) {
    echo_log('error', '[INGEGNO TPL MODULE] Settings manager required to install this module. Failed.<br/>');
    return;
}

if (file_exists(APPPATH . 'modules/ingegno-template/uploads/tpl_ingegno.zip')) {
    $this->load->model('settings-manager/templates_model');
    
    echo_log('info', '[INGEGNO TPL MODULE] Checking row on settings_template folder<br/>');
    // Get Template if exist or create
    $setting_template = $this->db->where('settings_template_folder', 'ingegno')->get('settings_template')->row_array();
    
    if (empty($setting_template)) {
        echo_log('info', '[INGEGNO TPL MODULE] Row not present, creating now...<br/>');
        
        $template['settings_template_name'] = 'Ingegno';
        $template['settings_template_folder'] = 'ingegno';
        
        $this->db->insert('settings_template', $template);
        $id = $this->db->insert_id();
    } else {
        echo_log('info', '[INGEGNO TPL MODULE] Row found, getting id<br/>');
        $id = $setting_template['settings_template_id'];
    }

    // Extract
    echo_log('info', '[INGEGNO TPL MODULE] Extracting zip<br/>');
    try {
        $this->templates_model->extract(APPPATH . 'modules/ingegno-template/uploads/tpl_ingegno.zip');
        echo_log('info', '[INGEGNO TPL MODULE] Zip extracted<br/>');
        
        echo_log('info', '[INGEGNO TPL MODULE] Copying Ingegno logo<br/>');
        @copy(APPPATH . 'modules/ingegno-template/assets/images/ingegno_suite.png', FCPATH . 'uploads/ingegno_suite.png');
        
        echo_log('info', '[INGEGNO TPL MODULE] Updating settings_template db field<br/>');
        
        $this->db->update('settings', ['settings_template' => $id, 'settings_topbar_logo' => 'ingegno_suite.png']);
        
        $this->mycache->clearCache();
    } catch (Exception $e) {
        echo_log('error', '[INGEGNO TPL MODULE] Unable to extract template. ' . $e->getMessage() . '<br/>');
    }
} else {
    echo_log('error', '[INGEGNO TPL MODULE] Template zip does not exists.<br/>');
}
