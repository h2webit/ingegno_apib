<?php


class Main extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('asterisk-logs/voipcall');
    }
    
    // -------------------------------------------- CRON ---------------------
    public function send_voip_call($force_contact_id=null)
    {
        if($force_contact_id){
            $this->voipcall->voip_call($force_contact_id);
        }else{
            $this->voipcall->voip_call();
        }               
    }
    public function delete_contacts()
    {
        $calls = $this->apilib->search("telemarketing_voip");
        foreach ($calls as $call):
            $this->apilib->delete('telemarketing_voip', $call['telemarketing_voip_id']);
        endforeach;
        
        $righe_ids = $this->input->post('chkbx_ids');
        foreach($righe_ids as $riga_ids){
            $lead = $this->apilib->searchFirst("leads", ['leads_id' => $riga_ids]);
            if ($lead['leads_phone'] == '' || empty($lead['leads_phone'])) {
                continue;
            } 
            $call_insert['telemarketing_voip_name'] = $lead['leads_title'];
            $call_insert['telemarketing_voip_last_name'] = '';
            $call_insert['telemarketing_voip_number'] = $lead['leads_phone'];

            $response = $this->apilib->create('telemarketing_voip',$call_insert);
        }
        die(json_encode(['status' => 3, 'txt' => 'ok, contatti creati con successo'])); 
    }
    /*public function send_voip_call($force_contact_id=null)
    {
        $calls = $this->apilib->search("telemarketing_voip_calls", ['telemarketing_voip_calls_called' => DB_BOOL_FALSE], 3);
        $exten = '21';
        
        if (empty($calls)) {
        echo "No calls to do";
        } else {
        
        
        
        echo "Chiamata in corso per: <br />";
        echo "<br />";
        
        foreach ($calls as $call):
        
        
            $text = $call['telemarketing_voip_campaign_text'];
        
        
            echo $call['telemarketing_voip_calls_contact_name']." ".$call['telemarketing_voip_calls_contact_number']."<br />";
        
        
        
            // Start call...
            $data = array(
                    'caller' => '21',
                    'numbertocall' => $call['telemarketing_voip_calls_contact_number'],
                    'exten' => $exten,
                    'direction' => 'outgoing',
                    'text' => "Buongiorno {$call['telemarketing_voip_calls_contact_name']}! $text"
                );
        
                $url = 'http://idra.h24hosting.com:81/moltiboxpbx/autodial.php';
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
         
        // Update record
            $this->apilib->edit('telemarketing_voip_calls', $call['telemarketing_voip_calls_id'], ['telemarketing_voip_calls_called' => DB_BOOL_TRUE]);
        
        endforeach;                        
        }                
    }*/
}
