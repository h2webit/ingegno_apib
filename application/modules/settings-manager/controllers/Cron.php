<?php
class Cron extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * AutoUpdate client. Invoked from Cron Cli (events) every 30 minutes. 
     */
    public function autoUpdatePatches($recursive_to_last = false)
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

        $this->load->model('core');
        $last_version = $this->core->updatePatches($repository_url, $channel, $recursive_to_last);
        echo_log("debug", "Updated to: " . $last_version);
        echo_log("debug", "Update client finish...");
    }


    /**
     * AutoUpdate client. Invoked from Cron Cli (events) every 30 minutes. But works only at 1 - 4 am
     */
    public function autoUpdateClient($update_patches = false)
    {
        echo_log('info', 'Start audo-update...');

        // Check work time execution
        $hour = date('H');
        if ($hour < 1 || $hour > 4) {
            echo_log('info', 'Stop audo-update, this is not a good time to execute.');
            return false;
        }

        // Check if invoked from cli...
        if (!is_cli()) {
            echo_log('error', "AutoUpdate failed... this cron works only via cli");
            return false;
        }

        // First check
        $settings = $this->apilib->searchFirst('settings');
        $repository_url = $settings['settings_auto_update_repository'];
        $channel = $settings['settings_auto_update_channel'];

        if ($settings['settings_update_in_progress'] == DB_BOOL_TRUE) {
            echo_log('error', "I can not update client, already update in progress");
            return false;
        }

        if ($settings['settings_auto_update_client'] != DB_BOOL_TRUE) {
            echo_log('error', "I can not update client, auto update is disabled");
            return false;
        }

        if (empty($settings['settings_auto_update_repository'])) {
            echo_log('error', "I can not update client, auto update repository is not defined");
            return false;
        }

        if (empty($settings['settings_auto_update_repository'])) {
            echo_log('error', "I can not update client, auto update repository is not defined");
            return false;
        }

        $this->load->model('core');

        // Check version
        if ($this->core->checkUpdate($repository_url, $channel) == false) {
            echo_log('error', "This version is already updated.");
            return false;
        }

        // Temp directory
        $tempDir = FCPATH . "tmp";
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }


        // Filename new dump
        $dump_filename = "backup-db-" . date("d-m-Y") . ".sql.gz";
        // Clear old backup
        delete_older_files($tempDir, 5, 'backup-db');


        // Start Dump
        if (generate_dump($tempDir, $dump_filename) != true) {
            echo_log('error', "Dump failed... update stopped. Check logs.");
            return false;
        } else {
            echo_log('info', "Dump generated.");
        }

        // Start Backup files
        echo_log('info', "Start backup files...");

        $path = FCPATH;
        echo_log('error', "Zip path: " . $path);
        $exclude_dirs = array(
            "uploads",
            "tmp",
            ".git",
            "logs"
        );
        $destination = $tempDir . "/last_backup.zip";
        ci_zip_folder(FCPATH, $destination, $exclude_dirs);

        // Check exists zip file and size if already 100mb
        echo_log("debug", "File backup zip created: " . $destination);
        echo_log("debug", "File zip size: " . filesize($destination));
        if (!file_exists($destination) || filesize($destination) < 100000000) {
            echo_log('error', "Zip backup failed... update failed.");
            return false;
        }


        $this->core->updateClient($repository_url, 0, $channel, $update_patches);

        echo_log("debug", "AutoUpdate finish...");
    }

    public function disableMaintenance () {
        if (date('H') == 2 && is_maintenance()) {
            $settings = $this->db->get('settings')->row();
            $this->db->where('settings_id', $settings->settings_id)->update('settings', array('settings_maintenance_mode' => '0'));
        }
    }

}