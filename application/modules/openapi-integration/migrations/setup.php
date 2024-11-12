<?php
echo_flush('Setting up OpenAPI Integration module...<br/>');

$base_settings = [
    'openapi_settings_production_key' => '6523cb0cfc6cd2655d304501',
    'openapi_settings_sandbox_key' => '646ddcaefe018c1be6218a92',
    'openapi_settings_mode' => '1',
    'openapi_settings_saldo' => '3',
    'openapi_settings_enable_crons' => '0',
    'openapi_settings_skip_business_registry_check' => '0',
];

$this->apilib->create('openapi_settings', $base_settings);



echo_flush('OpenAPI Integration module setup completed.<br/>');
