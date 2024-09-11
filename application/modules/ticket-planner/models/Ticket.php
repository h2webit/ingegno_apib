<?php
class Ticket extends CI_Model
{
    public $ticket_planner_settings;

    public function __construct()
    {
        parent::__construct();

        $this->ticket_planner_settings = $this->apilib->searchFirst('ticket_planner_settings');
        $this->base_endpoint = $this->ticket_planner_settings['ticket_planner_settings_endpoint'] ?? null;
    }

    public function get_balance($project_id)
    {

        $billable_hours = my_api($this->base_endpoint . 'rest/v1/search/billable_hours', $this->ticket_planner_settings['ticket_planner_settings_auth_bearer'], ['maxdepth' => 1, 'where[billable_hours_project_id]' => $project_id]);

        if (!empty($billable_hours['data'])) {

            $balance = 0;
            foreach ($billable_hours['data'] as $billable) {
                $balance += $billable['billable_hours_hours'];
            }
            return $balance;
        } else {
            return 0;
        }
    }

    public function apiRequest($entity, $method = 'search', $post_data = [], $value_id = null, $use_api = true)
    {
        if (!$this->base_endpoint)
            return false;

        $ch = curl_init();

        if (in_array($method, ['search', 'view', 'count'])) {
            $post_data['maxdepth'] = 2;
        }

        $url = 'rest/v1/' . $method . '/' . $entity . '/' . $value_id;
        if (!$use_api) {
            $url = "{$entity}/{$value_id}";
        }

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.2 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $this->ticket_planner_settings['ticket_planner_settings_auth_bearer']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $this->base_endpoint . $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $data = curl_exec($ch);
        
        curl_close($ch);

        return json_decode($data, true);
    }
}