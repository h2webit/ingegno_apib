<?php
class Main extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Force UpdateDB 
     * @return void
     */

    public function UpdateDb()
    {

        // Security check
        if (!$this->datab->is_admin() && !is_cli()) {
            echo_log("error", "Cannot access without admin or cli...");
            return false;
        }

        echo_log('info', 'Start update database without backup...');
        $this->load->model('core');
        $this->core->update();
    }

    /**
     * Update client patches Invoked manually
     */
    public function UpdatePatches()
    {

        // Security check
        if (!$this->datab->is_admin() && !is_cli()) {
            echo_log("error", "Cannot access without admin or cli...");
            return false;
        }
        echo_log('info', 'Start update without backup...');

        // First check
        $settings = $this->apilib->searchFirst('settings');
        $repository_url = $settings['settings_auto_update_repository'];
        $channel = $settings['settings_auto_update_channel'];

        if (empty($settings['settings_auto_update_repository'])) {
            echo_log('error', "I can not update client, auto update repository is not defined");
            return false;
        }

        if (empty($settings['settings_auto_update_repository'])) {
            echo_log('error', "I can not update client, auto update repository is not defined");
            return false;
        }

        $this->load->model('core');
        $last_version = $this->core->updatePatches($repository_url, $channel);
        echo_log("debug", "Updated to: " . $last_version);
        echo_log("debug", "Update client patches finish...");
    }

    /**
     * AutoUpdate client. Invoked manually
     */
    public function UpdateClientNoBackup()
    {

        // Security check
        if (!$this->datab->is_admin() && !is_cli()) {
            echo_log("error", "Cannot access without admin or cli...");
            return false;
        }
        echo_log('info', 'Start audo-update without backup...');

        // First check
        $settings = $this->apilib->searchFirst('settings');
        $repository_url = $settings['settings_auto_update_repository'];
        $channel = $settings['settings_auto_update_channel'];

        if (empty($settings['settings_auto_update_repository'])) {
            echo_log('error', "I can not update client, auto update repository is not defined");
            return false;
        }

        if (empty($settings['settings_auto_update_repository'])) {
            echo_log('error', "I can not update client, auto update repository is not defined");
            return false;
        }

        $this->load->model('core');
        $this->core->updateClient($repository_url, 0, $channel);

        echo_log("debug", "AutoUpdate finish...");
    }

}