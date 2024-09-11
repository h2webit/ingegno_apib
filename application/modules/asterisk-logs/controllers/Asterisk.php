<?php

class Asterisk extends MX_Controller
{
    public $settings;

    public function __construct()
    {
        parent::__construct();
    }

    // Generate rubrica contacts from Customer entitiy
    public function generate_rubrica_from_customers()
    {
        if (!$this->datab->module_installed('customers')) {
            die('Customers module not found');
        }

        $customers = $this->db->query("SELECT * FROM customers")->result_array();

        foreach ($customers as $data) {
            $contact['rubrica_nome'] = ($data['customers_company']) ? $data['customers_company'] : $data['customers_name'];
            $contact['rubrica_cognome'] = $data['customers_last_name'];
            $contact['rubrica_cliente'] = $data['customers_id'];


            // Check if rubrica contacts exist with phone number
            if ($data['customers_phone']) {
                $check_telephone = $this->db->where('rubrica_recapito_telefonico', $data['customers_phone'])->get('rubrica');
                $contact['rubrica_recapito_telefonico'] = $data['customers_phone'];
                
                // If exists, update
                if ($check_telephone->num_rows() > 0) {
                    foreach ($check_telephone->result_array() as $rubrica) {
                        $this->apilib->edit('rubrica', $rubrica['rubrica_id'], $contact);
                    }
                } else {
                    // If doesn't exist, create
                    $this->apilib->create('rubrica', $contact);
                }
            }
            
            // Check if rubrica contacts exist with mobile number
            if ($data['customers_mobile']) {
                $check_telephone = $this->db->where('rubrica_recapito_telefonico', $data['customers_mobile'])->get('rubrica');
                
                $contact['rubrica_recapito_telefonico'] = $data['customers_mobile'];

                // If exists, update
                if ($check_telephone->num_rows() > 0) {
                    foreach ($check_telephone->result_array() as $rubrica) {
                        $output = $this->apilib->edit('rubrica', $rubrica['rubrica_id'], $contact);
                    }
                } else {
                    // If doesn't exist, create
                    $this->apilib->create('rubrica', $contact);
                }
            }
        }
    }

    // Generate rubrica contacts from Customer entitiy
    public function generate_rubrica_from_customers_contacts()
    {
        if (!$this->datab->module_installed('customers')) {
            die('Customers module not found');
        }

        $customers = $this->db->query("SELECT * FROM customers_contacts")->result_array();

        foreach ($customers as $data) {
            $contact['rubrica_nome'] = ($data['customers_contacts_name']);
            $contact['rubrica_cognome'] = $data['customers_contacts_last_name'];
            $contact['rubrica_cliente'] = $data['customers_contacts_customer_id'];
            $contact['rubrica_cliente_contatto'] = $data['customers_contacts_id'];


            // Check if rubrica contacts exist with phone number
            if ($data['customers_contacts_phone']) {
                $check_telephone = $this->db->where('rubrica_recapito_telefonico', $data['customers_contacts_phone'])->get('rubrica');
                $contact['rubrica_recapito_telefonico'] = $data['customers_contacts_phone'];
                
                // If exists, update
                if ($check_telephone->num_rows() > 0) {
                    foreach ($check_telephone->result_array() as $rubrica) {
                        $this->apilib->edit('rubrica', $rubrica['rubrica_id'], $contact);
                    }
                } else {
                    // If doesn't exist, create
                    $this->apilib->create('rubrica', $contact);
                }
            }
            
            // Check if rubrica contacts exist with mobile number
            if ($data['customers_contacts_mobile_number']) {
                $check_telephone = $this->db->where('rubrica_recapito_telefonico', $data['customers_contacts_mobile_number'])->get('rubrica');
                
                $contact['rubrica_recapito_telefonico'] = $data['customers_contacts_mobile_number'];

                // If exists, update
                if ($check_telephone->num_rows() > 0) {
                    foreach ($check_telephone->result_array() as $rubrica) {
                        $output = $this->apilib->edit('rubrica', $rubrica['rubrica_id'], $contact);
                    }
                } else {
                    // If doesn't exist, create
                    $this->apilib->create('rubrica', $contact);
                }
            }
        }
    }



    public function autodial_link($number_to_call, $caller = null)
    {
        $settings = $this->db->get('switchboard_settings')->row_array();

        if ($caller == null) {
            $user_id = $this->auth->get(LOGIN_ENTITY . '_id');
            $interno = $this->db->query("SELECT * FROM interni WHERE interni_user = '$user_id'")->row()->interni_interno;
            $caller = $interno;
        }
        $link = str_replace("{caller}", $caller, $settings['switchboard_settings_autodial_link']);
        $link = str_replace("{number_to_call}", $number_to_call, $link);

        redirect($link);
    }

    public function genera_ics()
    {
        $events = $this->apilib->search('asterisk_calendar');
        // $output = $this->load->view('asterisk-logs/ics', ['events' => $events], true);
        // echo $output; //str_replace(["\r\n", "\r", "\n", PHP_EOL], "\r\n", $output);
        // exit;

        $str_events = '';
        date_default_timezone_set('Europe/Rome');
        // Build the ics file
        $ical = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN{events}
END:VCALENDAR';
        foreach ($events as $event) {
            $dtstart = (new DateTime($event['asterisk_calendar_date_start'], new DateTimeZone('Europe/Rome')))->format('Ymd\THis');
            $dtend = (new DateTime($event['asterisk_calendar_date_end'], new DateTimeZone('Europe/Rome')))->format('Ymd\THis');
            $dtstamp = (new DateTime("now", new DateTimeZone('Europe/Rome')))->format('Ymd\THis');
            
            $str_events .= '
BEGIN:VEVENT
DTSTAMP:' . $dtstamp . '
DTSTART:' . $dtstart . '
DTEND:' . $dtend . '
UID:' . md5($event['asterisk_calendar_title']) . '
DESCRIPTION:' . addslashes($event['asterisk_calendar_description']) . '
SUMMARY:' . addslashes($event['asterisk_calendar_title']) . '
END:VEVENT';
        }
        $ical = str_replace('{events}', $str_events, $ical);
        //set correct content-type-header

        $ical = strtr($ical, array(
            "\r\n" => "\r\n",
            "\r" => "\r\n",
            "\n" => "\r\n",
        ));

        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=asterisk-calendar.ics');
        echo $ical;
    }

    public function genera_interni_conf()
    {
        $interni = $this->apilib->search('interni');

        $base_file = file_get_contents(APPPATH . '/modules/asterisk-logs/assets/interni.conf');
        $base_interno = file_get_contents(APPPATH . '/modules/asterisk-logs/assets/interno.conf');
        $interni_string = '';
        foreach ($interni as $interno) {
            $new_data = array();
            foreach ($interno as $key => $val) {
                $new_data['{' . $key . '}'] = $val;
            }
            $interni_string .= PHP_EOL . strtr($base_interno, $new_data);
        }
        $file = strtr($base_file, ['{interni}' => $interni_string]);

        file_put_contents('./uploads/interni.conf', $file);
        $this->load->helper('download');
        force_download('interni.conf', $file);
    }

    public function check_chiamata_in_entrata()
    {
        $chiamata = $this->apilib->searchFirst('log_chiamate_in_entrata', [
            'log_chiamate_in_entrata_intercettata' => DB_BOOL_FALSE,
            //"log_chiamate_in_entrata_creation_date > now() - INTERVAL '20 seconds' "
            "log_chiamate_in_entrata_creation_date > DATE_SUB(NOW(), INTERVAL 20 SECOND)"
        ], 0, 'log_chiamate_in_entrata_id', 'DESC');

        if ($chiamata) {
            //Cerco nei clienti
            $search = $this->searchNumero($chiamata['log_chiamate_in_entrata_numero']);

            $chiamata = array_merge($chiamata, $search);
        }

        e_json($chiamata);
    }
    public function add_chiamata_in_entrata($numero)
    {
        $chiamata = $this->apilib->create('log_chiamate_in_entrata', [
            'log_chiamate_in_entrata_intercettata' => DB_BOOL_FALSE,
            'log_chiamate_in_entrata_numero' => $numero,
            //'log_chiamate_in_entrata_data_ora' => date('Y-m-d h:i:s')
        ]);

        //e_json($chiamata);
        echo $this->searchNumero($numero, true);
    }
    private function searchNumero($numero, $only_name = false)
    {
        if ($rubrica = $this->apilib->searchFirst('rubrica', ['rubrica_recapito_telefonico' => $numero])) {
            if ($only_name) {
                $nome = "{$rubrica['rubrica_nome']} {$rubrica['rubrica_cognome']}";
                return $nome;
            } else {
                return ['rubrica' => $rubrica];
            }
        } else {
            if ($only_name) {
                return '';
            } else {
                return [];
            }
        }
    }
}
