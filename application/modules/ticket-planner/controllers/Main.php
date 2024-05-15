<?php
class Main extends MY_Controller
{
    private $attachments = [];
    public function __construct()
    {
        parent::__construct();

        $this->load->model('ticket-planner/ticket');
        $this->load->library('form_validation');
    }

    public function get_ticket_html($ticket_id = null)
    {
        $response = $this->ticket->apiRequest('tickets', 'view', [], $ticket_id);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(json_encode(array('status' => 0, 'txt' => 'Fetching ticket categories failed')));
        }

        $ticket = $response['data'];

        $page = $this->load->module_view("ticket-planner/views", 'ticket_detail', $ticket, true);

        echo $page;
    }

    public function get_customer_projects()
    {
        $settings = $this->apilib->searchFirst('ticket_planner_settings');
        $response = $this->ticket->apiRequest('projects', 'search', ['where[projects_customer_id]' => $settings['ticket_planner_settings_customer_id'], 'where[projects_status]' => '2']);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(json_encode(array('status' => 0, 'txt' => 'Fetching ticket categories failed')));
        }

        $projects = $response['data'];

        if (!empty($projects) && is_array($projects)) {
            echo json_encode(array('status' => 1, 'data' => $projects));
        } else {
            echo json_encode(array('status' => 0, 'txt' => 'Fetching customer projects failed'));
        }
    }

    public function get_ticket_categories()
    {
        $response = $this->ticket->apiRequest('tickets_category', 'search');

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(json_encode(array('status' => 0, 'txt' => 'Fetching ticket categories failed')));
        }

        $categories = $response['data'];

        if (!empty($categories) && is_array($categories)) {
            echo json_encode(array('status' => 1, 'data' => $categories));
        } else {
            echo json_encode(array('status' => 0, 'txt' => 'Fetching ticket categories failed'));
        }
    }

    public function createFastTicket()
    {
        $settings = $this->apilib->searchFirst('ticket_planner_settings');

        $data = $this->input->post();
        $data['tickets_status'] = '1';
        $data['tickets_priority'] = '1';
        $data['tickets_customer_id'] = $settings['ticket_planner_settings_customer_id'];

        // Check balance ore
        $balance = $this->ticket->get_balance($data['tickets_project_id']);
        if ($balance < 0 && $data['tickets_category'] != 8) {
            die(e_json(['status' => 3, 'txt' => 'Saldo ore esaurito, in questo momento puoi aprire solamente ticket di Anomalie/Bug']));
        }

        // Add Extra data to message
        $url = $data['current_url'];
        unset($data['current_url']);
        $extra_data = "<br /><br /><strong>Extra details: </strong><br />";
        $extra_data .= "User: " . $this->auth->get('users_first_name') . " " . $this->auth->get('users_last_name') . "<br />";
        $extra_data .= "URL: " . $url . "<br />";
        $extra_data .= "Client version: " . VERSION;
        $data['tickets_message'] = $data['tickets_message'] . " " . $extra_data;

        $data['tickets_web_screenshot'] = array_filter($data['tickets_web_screenshot']);

        $this->form_validation->set_rules(
            'tickets_subject',
            'Oggetto',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        $this->form_validation->set_rules(
            'tickets_message',
            'Messaggio',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        $this->form_validation->set_rules(
            'tickets_category',
            'Categoria',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        if (!$this->form_validation->run()) {
            die(e_json(['status' => 3, 'txt' => strip_tags(validation_errors())]));
        }

        $response = $this->ticket->apiRequest('tickets', 'create', $data);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(e_json(['status' => 3, 'txt' => $response['message']]));
        }

        echo json_encode(array('status' => 4, 'txt' => 'Ticket creato con successo!'));
    }


    public function createTicket()
    {
        $data = $this->input->post();

        $data['tickets_status'] = '1';

        $this->form_validation->set_rules(
            'tickets_subject',
            'Oggetto',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        $this->form_validation->set_rules(
            'tickets_message',
            'Messaggio',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        $this->form_validation->set_rules(
            'tickets_priority',
            'Priorità',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        $this->form_validation->set_rules(
            'tickets_category',
            'Categoria',
            'required|trim',
            array('required' => 'Il campo <b>%s</b> è richiesto')
        );

        if (!$this->form_validation->run()) {
            die(e_json(['status' => 3, 'txt' => strip_tags(validation_errors())]));
        }

        if (empty($data['attachments'])) {
            unset($data['attachments']);
        }
        
        // Add Extra data to message
        $extra_data = "<br /><br /><strong>Extra details: </strong><br />";
        $extra_data .= "User: " . $this->auth->get('users_first_name') . " " . $this->auth->get('users_last_name') . "<br />";
        $extra_data .= "Client version: " . VERSION;
        $data['tickets_message'] .= " " . $extra_data;

        $response = $this->ticket->apiRequest('tickets', 'create', $data);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(e_json(['status' => 3, 'txt' => strip_tags($response['message'])]));
        }

        e_json(['status' => 4, 'txt' => 'Ticket creato con successo!']);
    }

    public function upload_attachments()
    {
        $data = $this->security->xss_clean($this->input->post());

        if (empty($data['attachments']))
            die(e_json(['status' => 3, 'txt' => 'Nessun allegato caricato']));

        $response = $this->ticket->apiRequest('tickets', 'edit', ['attachments' => $data['attachments']], $data['tickets_id']);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(e_json(['status' => 3, 'txt' => strip_tags($response['message'])]));
        }

        e_json(['status' => 4, 'txt' => 'Allegati caricati con successo!']);
    }
}