<?php

class Voipcall extends CI_Model
{
    public $api_token = "";
    public $api_url = "";

    public function __construct()
    {
        parent::__construct();
        
        $this->settings = $this->apilib->searchFirst('switchboard_settings');
        //$this->api_url = $this->settings['nethesis_settings_api_url'];
        //@TODO in base al centralino attivo, prendere i dati per fare le chiamate. Per ora faccio con asterisk
    }


    
    // ************************************************************************* API METHODS ***************************************************************************************
    public function voip_call($force_contact_id = null)
    {
        $settings = $this->db->get('asterisk_settings')->row_array();

        if ($force_contact_id != null){
            $calls = $this->apilib->search("telemarketing_voip_calls", ['telemarketing_voip_calls_id' => $force_contact_id]);
        } else{
            $calls = $this->apilib->search("telemarketing_voip_calls", ['telemarketing_voip_calls_called' => DB_BOOL_FALSE], 3);
        }
        $exten = $settings['asterisk_settings_voip_call_caller'];
            
        if (empty($calls)) {
            echo "No calls to do";
        } else {

            $this->makeCalls($calls, $exten);        
        } 
            
    }
    private function makeCalls($calls, $exten) {
        $settings = $this->db->get('asterisk_settings')->row_array();

        foreach ($calls as $call) {
            $text = $call['telemarketing_voip_campaign_text'];
            $data = array(
                'caller' => $settings['asterisk_settings_voip_call_caller'],
                'numbertocall' => $call['telemarketing_voip_calls_contact_number'],
                'exten' => $exten,
                'direction' => 'outgoing',
                'text' => "Buongiorno {$call['telemarketing_voip_calls_contact_name']}! $text"
            );
            $url = $settings['asterisk_settings_voip_call_url'];
            $ch = curl_init($url);
            $postString = http_build_query($data, '', '&');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            curl_close($ch);
            sleep(3);
            $this->apilib->edit('telemarketing_voip_calls', $call['telemarketing_voip_calls_id'], ['telemarketing_voip_calls_called' => DB_BOOL_TRUE]);
        }
    }

}
