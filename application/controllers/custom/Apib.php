<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Apib extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        //Check logged in
        if ($this->auth->guest()) { //Guest
            //Do your stuff...
            throw new AssertionError("Guest not allowed");
        } elseif ($this->auth->check()) { //Logged in
            //Do your stuff...
        } else {
            //Do your stuff...
            throw new AssertionError("Undetected authorization type");
        }
    }

    public function editSediProfessionisti($notity_check = 0)
    {
        //debug($this->input->post(), true);
        $fascie = $this->input->post('appuntamenti_fascie');
        $sede = $this->input->post('appuntamenti_impianto');
        $associato = $this->input->post('dipendenti_id');
        $giorno = $this->input->post('appuntamenti_giorno');
        $user_id = $this->apilib->view('dipendenti', $associato)['dipendenti_user_id'];
        //Rimuovo tutte le assegnazioni in quel giorno di quella sede per quell'associato (null se turno scoperto)
        // $turni_assegnati = $this->db->where([
        //     'DATE(appuntamenti_giorno)' => $giorno,
        //     "appuntamenti_id IN (SELECT appuntamenti_id FROM rel_appuntamenti_persone WHERE users_id = '$user_id)",
        //     'appuntamenti_impianto' => $sede,
        // ])->get('appuntamenti')->result_array();

        // foreach ($turni_assegnati as $turno) {
        //     //Commentato per task 5130 (punto 6): vuole che se rimuovo un turno, anche la relativa richiesta sparisca e non ricompaia... essendoci drop cascade dovrebbe funzionare...
        //     //             $this->db->where([
        //     //                'richieste_disponibilita_turno_assegnato' => $turno['sedi_professionisti_id'],
        //     //            ])->update('richieste_disponibilita', ['richieste_disponibilita_turno_assegnato' => null]);
        // }

        $this->db->where([
            'DATE(appuntamenti_giorno)' => $giorno,
            "appuntamenti_id IN (SELECT appuntamenti_id FROM rel_appuntamenti_persone WHERE users_id = '$user_id)",
            'appuntamenti_impianto' => $sede,
        ])->delete('appuntamenti');

        if (!empty($fascie)) {
            foreach ($fascie as $fascia) {
                if (stripos($fascia, '**')) { //Se è studente
                    $fascia = str_ireplace('*', '', $fascia);
                    $affiancamento = DB_BOOL_FALSE;
                    $studente = DB_BOOL_TRUE;
                } else if (stripos($fascia, '*')) { //Se è affiancamento
                    $fascia = str_ireplace('*', '', $fascia);
                    $affiancamento = DB_BOOL_TRUE;
                    $studente =     DB_BOOL_FALSE;
                } else {
                    $affiancamento = DB_BOOL_FALSE;
                    $studente =    DB_BOOL_FALSE;
                }
                $appuntamento_id = $this->apilib->create('appuntamenti', [
                    'appuntamenti_giorno' => $giorno,
                    'appuntamenti_persone' => [$user_id],
                    'appuntamenti_impianto' => $sede,
                    'appuntamenti_fascia_oraria' => $fascia,
                    'appuntamenti_affiancamento' => $affiancamento,
                    'appuntamenti_studente' => $studente,
                ], false);

                //Verifico se in questo giorno per questa fascia, era richiesta una disponibilità da parte della sede.
                //Se sì allora associo quest'id alla richiesta (così quando e se lo rimuoverò, la richiesta tornerà a comparire.
                //Viceversa, se questo turno rimane, la richiesta sparirà (essendo una richiesta ormai "evasa")
                //TODO: da capire coem gestire le richieste di disponibilità
                // if ($associato) {
                //     $richiesta = $this->db->where([
                //         'richieste_disponibilita_giorno' => $giorno,
                //         'richieste_disponibilita_fascia' => $fascia,
                //         'richieste_disponibilita_sede_operativa' => $sede,
                //         'richieste_disponibilita_turno_assegnato' => null,
                //         'richieste_disponibilita_affiancamento' => $affiancamento,
                //         'richieste_disponibilita_studente' => $studente,
                //     ])->get('richieste_disponibilita');

                //     if ($richiesta->num_rows() > 0) {
                //         $this->db->where('richieste_disponibilita_id', $richiesta->row()->richieste_disponibilita_id)->update('richieste_disponibilita', [
                //             'richieste_disponibilita_turno_assegnato' => $id_sedi_professionisti
                //         ]);
                //     }
                // }
            }
        }

        if ($notity_check) {
            
            $sede_data = $this->apilib->view('projects', $sede);
                $allarme = [
                    'allarmi_utente' => $user_id,
                    'allarmi_tipo' => ALLARMI_CAMBIO_TURNO,
                    'allarmi_titolo' => 'Turno modificato',
                    'allarmi_testo' => "Il giorno <strong>{$giorno}</strong> è stato modificato il tuo turno presso la sede <strong>{$sede_data['projects_name']}</strong>.",
                    'allarmi_data' => json_encode($this->input->post()),
                ];

                $this->apilib->create('allarmi', $allarme);
            
        }

        echo json_encode(['status' => 0]);
    }

    public function stampaCalendarioSede($sede_id)
    {

        $regenerate = (bool) $this->input->get('_regen');
        $data['associato'] = $this->input->get('associati_id');

        $data['sede'] = $this->apilib->view('projects', $sede_id);

        //Prendo i turni assegnati, ordinati decrescentemente (così per primo ho l'ultimo assegnato e capisco se devo rigenerare il file)
        $data['calendario'] = $this->apilib->search('appuntamenti', [
            'appuntamenti_impianto' => $sede_id
            //TODO: filtro data????
        ], null, 0, 'appuntamenti_id', 'DESC');


        // Vedo se ho già il file generato
        $physicalDir = "./uploads/calendari";
        $filename = 'calendariosede_' . $data['sede']['projects_id'] . '-' . @$data['calendario'][0]['appuntamenti_id'];
        $pdfFile = "{$physicalDir}/{$filename}.pdf";

        if (!file_exists($pdfFile) or is_development() or $regenerate) {
            $this->load->library('parser');

            $data['year'] = ($this->input->get('Y')) ? $this->input->get('Y') : date('Y');
            $data['month'] = ($this->input->get('m')) ? $this->input->get('m') : date('m');

            $contents = $this->parser->parse("custom/pdf/calendario_sede", $data, true);

            $html = $this->input->get('html');
            if ($html) {
                die($contents);
            } else {
                // Create a temporary file with the view html
                if (!is_dir($physicalDir)) {
                    mkdir($physicalDir, 0755, true);
                }
                $tmpHtml = "{$physicalDir}/{$filename}.html";
                file_put_contents($tmpHtml, $contents, LOCK_EX);
                //die('test');
                // Exec the command
                $options = "-T '10mm' -B '10mm' -O landscape";
                // die("wkhtmltopdf {$options} --viewport-size 1024 {$tmpHtml} {$pdfFile}");
                exec("wkhtmltopdf {$options} --viewport-size 1024 {$tmpHtml} {$pdfFile}");
                //debug("wkhtmltopdf {$options} --viewport-size 1024 {$tmpHtml} {$pdfFile}",true);
            }
        }
        // Send the file
        $fp = fopen($pdfFile, 'rb');
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdfFile));
        fpassthru($fp);

        // Remove the temp files
        //        @unlink($tmpHtml);
        //        if ($isH2Web) {
        //            @unlink($pdfFile);
        //        }
    }

}
