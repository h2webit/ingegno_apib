<?php
class Db_ajax extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('ticket-planner/ticket');

        $this->ticket_planner_settings = $this->apilib->searchFirst('ticket_planner_settings');
        $this->base_endpoint = $this->ticket_planner_settings['ticket_planner_settings_endpoint'] ?? null;
    }

    public function change_value($entity_name = null, $id = null, $field_name = null, $new_value = null)
    {
        $this->ticket->apiRequest($entity_name, 'edit', [$field_name => $new_value], $id);

        if ($this->input->is_ajax_request()) {
            echo json_encode(array('status' => 2));
        } else {
            redirect(base_url());
        }
    }

    public function accept_quote($ticket_id, $type)
    {

        // Check
        if (empty($type) || empty($ticket_id)) {
            e_json(['status' => 0, 'txt' => 'Ticket non trovato oppure errore temporaneo. Riprovare o contattare il supporto telefonicamente.']);
            return false;
        }

        // Send to server
        $request = my_api($this->base_endpoint . 'rest/v1/edit/tickets/' . $ticket_id, $this->ticket_planner_settings['ticket_planner_settings_auth_bearer'], ['tickets_estimated_type_confirm' => $type]);

        e_json(['status' => 1, 'txt' => 'Richiesta inviata']);
    }

    public function new_chat_message($ticket_id = null)
    {
        $message = $this->input->post('message');

        if (empty($message) || empty($ticket_id))
            die(e_json(['status' => 0, 'txt' => "Anomalia invio messaggio ticket."]));

        $response = $this->ticket->apiRequest('tickets_messages', 'create', [
            'tickets_messages_member' => 99999,
            // 20230707 - michael - faccio questo per avere poi la possibilità di capire che i messaggi dei ticket arrivano dal planner.. quindi riconoscere che a rispondere è il cliente
            'tickets_messages_ticket' => $ticket_id,
            'tickets_messages_text' => $message,
            
            // 20240618 - michael - aggiungo il nome dell'utente che ha inviato il messaggio
            'tickets_messages_original_user' => "{$this->auth->get('users_first_name')} {$this->auth->get('users_last_name')}"
        ]);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(json_encode(array('status' => 0, 'txt' => 'Invio messaggio ticket fallito')));
        }

        e_json(['status' => 1, 'txt' => $response['data'][0]]);
    }

    public function dropzone()
    {
        $file = $_FILES['file'];

        $b64 = base64_encode(file_get_contents($file['tmp_name']));

        $file_data = [
            'file' => $b64,
            'mime' => $file['type'],
            'name' => $file['name'],
            'size' => $file['size'],
        ];

        e_json(['status' => 1, 'file' => $file_data]);
    }

    public function get_ticket_messages($ticket_id)
    {
        // $response = $this->ticket->apiRequest('tickets_messages', 'search', [
        //     'where[tickets_messages_ticket]' => $ticket_id,
        //     'orderby' => 'tickets_messages_creation_date desc'
        // ]);
        
        $response = $this->ticket->apiRequest('custom/ticket_planner/get_ticket_messages', '', [], $ticket_id, false);

        if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
            die(json_encode(array('status' => 0, 'txt' => 'Ottenimento messaggi ticket fallito.')));
        }

        $messages = $response['data'];

        $html = '';
        foreach ($messages as $message) {
            //$user = ($message['tickets_messages_member'] == $this->ticket->ticket_planner_settings['ticket_planner_settings_customer_id']) ? 'Cliente' : 'Assistenza';
            $user = ($message['tickets_messages_member'] == 99999) ? 'Cliente' : 'Assistenza';

            $html .= '<li style="border-bottom: 1px solid #eee;">';
            $html .= '  <strong class="user">' . $user . '</strong>';
            $html .= '  <span class="direct-chat-timestamp pull-right datetime">' . dateTimeFormat($message['tickets_messages_creation_date'], 'd/m/Y H:i') . '</span> ';
            $html .= '  <div class="text">';
            $html .= '    <p>' . $message['tickets_messages_text'] . '</p>';
            $html .= '  </div> ';
            $html .= '</li>';
        }

        e_json(['status' => 1, 'txt' => $html]);
    }
}
