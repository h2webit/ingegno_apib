<?php

class Nethesis extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        //$settings = $this->db->get('nethesis_settings')->row_array();

        $this->load->model('asterisk-logs/nethesisapi');
    }


    public function get_history_calls()
    {
        $map_direction = array('in' => 'incoming', 'out' => 'outgoing', 'internal' => 'internal');

        $asterisk_users = $this->db->query("SELECT * FROM asterisk_users_number")->result_array();
        // Set the timezone to GMT
        date_default_timezone_set('GMT');



        foreach ($asterisk_users as $user) {
            $calls = $this->nethesisapi->get_historycall($user['internal_number'], date('Ymd'), '20451025');

            //$calls = $this->nethesisapi->get_historycall($user['internal_number'], "20181025", "20451025");

            // Save calls
            if (!empty($calls['rows'])) {

                foreach ($calls['rows'] as $row) {
                    //debug($row);
                    $call['asterisk_log_calls_calldate'] = date('Y-m-d H:i:s', $row['time']); //convertire in datetime
                    //$call['asterisk_log_calls_calldate'] = date('Y-m-d H:i:s', strtotime('@'.$row['time'])); //convertire in datetime
                    $call['asterisk_log_calls_callee_name'] = $row['cnam'];
                    /*$call['asterisk_log_calls_callee_num'] = $row['callee_num'];
                    $call['asterisk_log_calls_dest_name'] = $row['dest_name'];
                    $call['asterisk_log_calls_dest_num'] = $row['dest_num'];*/
                    $call['asterisk_log_calls_call_direction'] = $map_direction[$row['type']];
                    $call['asterisk_log_calls_clid'] = $row['clid'];
                    $call['asterisk_log_calls_src'] = $row['src'];
                    $call['asterisk_log_calls_dst'] = $row['dst'];
                    $call['asterisk_log_calls_dcontext'] = $row['dcontext'];
                    $call['asterisk_log_calls_channel'] = $row['channel'];
                    $call['asterisk_log_calls_dstchannel'] = $row['dstchannel'];
                    // $call['asterisk_log_calls_lastapp'] = $row['lastapp'];
                    // $call['asterisk_log_calls_lastdata'] = $row['lastdata'];
                    $call['asterisk_log_calls_duration'] = $row['duration'];
                    // $call['asterisk_log_calls_billsec'] = $row['billsec'];
                    $call['asterisk_log_calls_disposition'] = $row['disposition'];
                    // $call['asterisk_log_calls_amaflags'] = $row['amaflags'];
                    // $call['asterisk_log_calls_accountcode'] = $row['accountcode'];
                    $call['asterisk_log_calls_uniqueid'] = $row['uniqueid'];
                    $call['asterisk_log_calls_userfield'] = $row['userfield'];
                    $call['asterisk_log_calls_switchboard'] = 2;
                    /*if($call['asterisk_log_calls_dst'] != '3459208784'){
                        continue;

                    }*/
                    /*debug($row);
                    debug($call);*/
                    // debug($call);
                    $call_esistente = $this->apilib->searchFirst('asterisk_log_calls', ['asterisk_log_calls_calldate' => $call['asterisk_log_calls_calldate'], 'asterisk_log_calls_uniqueid' =>  $call['asterisk_log_calls_uniqueid']]);
                    if (!$call_esistente) {
                        $this->apilib->create('asterisk_log_calls', $call);
                    } else {
                        continue;
                    }
                }
            }
        }
    }
}
