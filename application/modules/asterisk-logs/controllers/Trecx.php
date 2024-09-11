<?php

class Trecx extends MX_Controller
{
    private $trecx_settings;
    private $folder;
    private $link;
    private $user;
    private $password;

    public function __construct()
    {
        parent::__construct();
        require FCPATH . "application/modules/asterisk-logs/vendor/autoload.php";
        $this->trecx_settings = $this->apilib->searchFirst('3cx_settings');

        if (!empty($this->trecx_settings)) {
            $this->folder = $this->trecx_settings['3cx_settings_sftp_folder'];
            $this->password = $this->trecx_settings['3cx_settings_sftp_password'];
            $this->link = $this->trecx_settings['3cx_settings_sftp_link'];
            $this->user = $this->trecx_settings['3cx_settings_sftp_user'];
             
            if (empty($this->folder) || empty($this->password) || empty($this->link) || empty($this->user)) {
                echo "One or more 3cx configuration parameters are missing";
                //throw new Exception("One or more 3cx configuration parameters are missing");
            }
        } else{
            return "Missing 3cx Configuration";
        }
    }

    public function downloadFile(){

        //verifico se ci sono chiamate senza registrazione, altrimenti salto tutto
        $recordings = $this->db->query("SELECT * FROM asterisk_log_calls
        WHERE asterisk_log_calls_recording IS NULL 
        AND asterisk_log_calls_disposition != 'NO ANSWER'
        AND DATE(asterisk_log_calls_creation_date) = CURDATE()")->result_array();

        if(!empty($recordings)){
            $sftp = new phpseclib\Net\SFTP($this->link);

            if (!$sftp->login($this->user, $this->password)) {
                echo "Login failed";
                throw new Exception('Login failed');
            }

            // Spostarsi nella cartella desiderata
            if (!$sftp->chdir($this->folder)) {
                echo "Unable to find directory";
                throw new Exception('Unable to find directory');
            }

            // Percorso della cartella di destinazione
            $destination_folder = FCPATH . "uploads/asterisk_recordings";
            if (!file_exists($destination_folder)) {
                mkdir($destination_folder, 0777, true);
            }
            $interni = $sftp->nlist();
            foreach($recordings as $record){
                    //definisco la data, il numero
                    $date = new DateTime($record['asterisk_log_calls_calldate']);
                    $data_record_calldate = $date->format("Ymd");
                    $numero = $record['asterisk_log_calls_src'];
                    foreach ($interni as $interno) {
                        if (is_numeric($interno)) {
                            if (!$sftp->chdir($this->folder . $interno)) {
                                echo "Unable to find directory";
                                throw new Exception('Unable to find directory');
                            }
                            $files = $sftp->nlist();

                            foreach ($files as $file) {
                                //echo $file."<br><br>";
                                // Rimuovo gli spazi dal nome del file
                                $clean_file_name = str_replace(' ', '', $file);
                                //dump($clean_file_name);
                                if (strpos($clean_file_name, $numero) !== false  && strpos($clean_file_name, $data_record_calldate) !== false) {

                                    //ho trvato stessa data e numero, ora vedo se l'ora ricade tra creation e calldate
                                    $var = preg_split("#{$data_record_calldate}#", $clean_file_name);
                                    $data_file = $date->format("Y-m-d");
                                    $data_ora_file = $var[1];
                                    $ore =  substr($data_ora_file, 0, 2);
                                    $minuti =  substr($data_ora_file, 2, 2);
                                    $secondi =  substr($data_ora_file, 4, 2);
                                    $data_formatted = $data_file." ".$ore.":".$minuti.":".$secondi;
                                    $data_formatted = strtotime($data_formatted) + 60*60; //perchè 3cx mette l'ora non con fuso orario italiano.
                                    //$data_formatted = strtotime($data_formatted) + 60*60 + 60*60; //perchè 3cx mette l'ora non con fuso orario italiano.
                                    $data_formatted = date('Y-m-d H:i:s', $data_formatted);
                                    if($record['asterisk_log_calls_calldate'] <= $data_formatted AND $record['asterisk_log_calls_creation_date'] >= $data_formatted){
                                        //salvo il file
                                        $content = $sftp->get($file);
                                        //$nome_file = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $clean_file_name);
                                        $nome_file = $record['asterisk_log_calls_id'] . '.wav';

                                        $saved = file_put_contents($destination_folder . "/" . $nome_file, $content);
                                        $this->db->insert('asterisk_recordings', array(
                                            'asterisk_recordings_filename' => $nome_file,
                                            'asterisk_recordings_file' => 'asterisk_recordings/' . $nome_file
                                        ));
                                        $asterisk_recordings_id = $this->db->insert_id();
                                        $data = array('asterisk_log_calls_recording' => $asterisk_recordings_id);
                                        $this->db->where('asterisk_log_calls_id', $record['asterisk_log_calls_id']);
                                        $this->db->update('asterisk_log_calls', $data);
                                        echo "<br>trovata corrispondenza del file".$nome_file. " con l'id ".$record['asterisk_log_calls_id']."<br>";
                                    }
                                } else {

                                    //no corrispondenza
                                }
                            }
                        }
                    }
            }
        }
    }
}