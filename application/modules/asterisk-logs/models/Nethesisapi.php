<?php

class Nethesisapi extends CI_Model
{
    public $api_token = "";
    public $api_url = "";

    public function __construct()
    {
        parent::__construct();

        $this->settings = $this->apilib->searchFirst('nethesis_settings');
        $this->api_url = $this->settings['nethesis_settings_api_url'];
    }


    
    // ************************************************************************* API METHODS ***************************************************************************************
    public function api_login()
    {
        $url = $this->api_url."/authentication/login";
        $data['username'] = $this->settings['nethesis_settings_username'];
        $data['password'] = $this->settings['nethesis_settings_password'];
        if(!empty($data['username']) && !empty($data['password'])){
            $data_json = json_encode($data);
            $exp_headers = $this->sendLoginCurl($url, $data_json, []);
            // Search www-authenticate token
            foreach ($exp_headers as $key => $value) {
                if (strpos($value, "www-authenticate") !== false) {
                    $_token = explode(" ", $value);
                    if (!empty($_token[2])) {
                        $token = $_token[2];
                        $shatoken = hash_hmac('sha1', $data['username'].":".$data['password'].":".$token, $data['password'], false);
                        $this->api_token = $data['username'].":".$shatoken;
                    }
                }
            }
        } else {
            echo "Login failed";
            throw new Exception('Login failed');
        }
       
    }

    public function get_historycall($extension, $from, $to)
    {
        $this->api_login();
        //$url = $this->api_url."/historycall/interval/extension/$extension/$from/$to";
        $url = $this->api_url."/histcallswitch/interval/$from/$to";

        //$params['direction'] = 'in';
        //$params['limit'] = 100;
        //$params['offset'] = 0;
        $params['sort'] = 'time';
        $url = $url.'?'.http_build_query($params);
        /*d($this->api_token);
        debug($url);*/
        $output = $this->sendGetCurl($url);

        return json_decode($output, true);
    }
    

    // Login curl with header answer
    private function sendLoginCurl($url, $post_json=null, $headers=[])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_json,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                "Content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Then, after your curl_exec call:
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $exp_headers = explode("\r\n", $header);

        curl_close($curl);

        if ($err) {
            log_message('error', $err);
            return $err;
        } else {
            return $exp_headers;
        }
    }


    // Standard post curl
    private function sendPostCurl($url, $post_json=null, $headers=[])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_json,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                "Content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            log_message('error', $err);
            return $err;
        } else {
            return $response;
        }
    }


    // Standard get Curl
    private function sendGetCurl($url)
    {

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: {$this->api_token}",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            log_message('error', $err);
            return false;
        } else {
            return $response;
        }
    }
}
