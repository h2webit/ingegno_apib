<?php


class Backup extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->settings = $this->db->get('settings')->row_array();
    }

    function generatePassword()
    {
        if (!$this->auth->is_admin()) {
            die("Hacking attempt detected. Communication in progress to the system administrator.");
        }

        // Check if password exists or create it
        $check_password = $this->db->query("SELECT * FROM backups_settings LIMIT 1")->row_array();

        if (!empty($check_password['backups_settings_password'])) {
            echo "Oh no! Something wrong!";
        } else {
            $new_pass = time();
            $this->db->insert('backups_settings', array('backups_settings_password' => $new_pass));
            echo "Password: " . $new_pass;
        }
    }


    function download_dump($system_password)
    {
        if (!$system_password || empty($system_password)) {
            die("Hacking attempt detected. Communication in progress to the system administrator.");
        } else {
            $check_password = $this->db->query("SELECT * FROM backups_settings WHERE backups_settings_password = '$system_password' LIMIT 1")->num_rows();
            if ($check_password < 1) {
                die("Your password is wrong. Please, contact your system Administrator.");
            }
        }

        $DBUSER = $this->db->username;
        $DBPASSWD = $this->db->password;
        $DATABASE = $this->db->database;
        $DBHOST = $this->db->hostname;

        $filename = "backup-db-" . date("d-m-Y") . ".sql.gz";
        $mime = "application/x-gzip";

        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $cmd = "mysqldump -h $DBHOST -u $DBUSER --password=$DBPASSWD $DATABASE | gzip --best";
        passthru($cmd);

        $this->apilib->create('backups_downloads', array('backups_downloads_user' => $this->auth->get('users_id'), 'backups_downloads_type' => 'Database'));
    }

    function download_uploads($system_password)
    {
        if (!$system_password || empty($system_password)) {
            die("Hacking attempt detected. Communication in progress to the system administrator.");
        } else {
            $check_password = $this->db->query("SELECT * FROM backups_settings WHERE backups_settings_password = '$system_password' LIMIT 1")->num_rows();
            if ($check_password < 1) {
                die("Your password is wrong. Please, contact your system Administrator.");
            }
        }

        $filename = "backup-uploads-" . date("d-m-Y") . ".tar";

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachement; filename=" . $filename);
        passthru("tar -cz uploads/");
        $this->apilib->create('backups_downloads', array('backups_downloads_user' => $this->auth->get('users_id'), 'backups_downloads_type' => 'Files'));
    }

    function getFolderSize($dir = FCPATH)
    {
        passthru("cd $dir && du -hs");
    }
}
