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
    
    public function editRichiesteDisponibilita()
    {
        $data = $this->input->post();
        $gruppi_fascie = $this->input->post('fascie');
        $sede = $this->input->post('sede');
        $giorno = $this->input->post('giorno');
        
        //debug($this->input->post(),true);
        $old_data = $this->db
            ->where("richieste_disponibilita_giorno::date = '$giorno'::date", null, false)
            ->where("richieste_disponibilita_turno_assegnato IS NULL", null, false)
            ->where("richieste_disponibilita_sede_operativa", $sede)->get('richieste_disponibilita')->result_array();
        
        $this->db
            ->where("richieste_disponibilita_giorno::date = '$giorno'::date", null, false)
            ->where("richieste_disponibilita_turno_assegnato IS NULL", null, false)
            ->where("richieste_disponibilita_sede_operativa", $sede)->delete('richieste_disponibilita');
        
        $this->apilib->clearCache();
        
        $dati_sede = $this->apilib->view('sedi_operative', $sede);
        $utente = $this->apilib->view('utenti', 22);
        foreach ($gruppi_fascie as $fascie) {
            if (!empty($fascie)) {
                foreach ($fascie as $fascia) {
                    
                    if (stripos($fascia, '**')) { //Se è costo differenziato/studente
                        $fascia = str_ireplace('*', '', $fascia);
                        $affiancamento = 'f';
                        $studente = 't';
                    } else if (stripos($fascia, '*')) { //Se è affiancamento
                        $fascia = str_ireplace('*', '', $fascia);
                        $affiancamento = 't';
                        $studente = 'f';
                    } else {
                        $affiancamento = 'f';
                        $studente = 'f';
                    }
                    
                    $this->apilib->create('richieste_disponibilita', [
                        'richieste_disponibilita_sede_operativa' => $sede,
                        'richieste_disponibilita_giorno' => $giorno,
                        'richieste_disponibilita_fascia' => $fascia,
                        'richieste_disponibilita_turno_assegnato' => null,
                        'richieste_disponibilita_affiancamento' => $affiancamento,
                        'richieste_disponibilita_studente' => $studente,
                    ]);
                    
                    if (!in_array($this->auth->get('utenti_tipo'), [7, 8])) {
                        
                        foreach ($old_data as $richiesta) {
                            //Se avevo già fatto questa richiesta, non notifico
                            if ($richiesta['richieste_disponibilita_fascia'] == $fascia) {
                                continue 2;
                            }
                        }
                        
                        $dati_sede = $this->apilib->view('sedi_operative', $sede);
                        $notification = [
                            'notifications_user_id' => 22,
                            'notifications_type' => NOTIFICATION_TYPE_WARNING,
                            'notifications_link' => "main/layout/55/{$sede}",
                            'notifications_message' => "Nuova richiesta disponibilità per la sede {$dati_sede['sedi_operative_reparto']}"
                        ];
                        
                        $this->db->insert('notifications', $notification);
                        
                        
                        $dati_fascia = $this->apilib->view('sedi_operative_orari', $fascia);
                        //E mando anche una mail
                        $data['richieste_disponibilita_giorno'] = $giorno;
                        $this->mail_model->send($utente['utenti_email'], 'nuova_richiesta_disponibilita', 'it', array_merge($data, $dati_sede, $dati_fascia));
                    }
                }
            }
        }
        
        //A questo punto faccio un controllo per capire se sia stata rimossa una richiesta
        $new_data = $this->db
            ->where("richieste_disponibilita_giorno::date = '$giorno'::date", null, false)
            ->where("richieste_disponibilita_turno_assegnato IS NULL", null, false)
            ->where("richieste_disponibilita_sede_operativa", $sede)->get('richieste_disponibilita')->result_array();
        
        foreach ($old_data as $key => $old) {
            foreach ($new_data as $key1 => $new) {
                if ($new['richieste_disponibilita_fascia'] == $old['richieste_disponibilita_fascia']) {
                    unset($old_data[$key]);
                    break;
                }
            }
        }
        
        if (!empty($old_data)) {
            $dati_fascia = $this->apilib->view('sedi_operative_orari', $old_data[0]['richieste_disponibilita_fascia']);
            $data['richieste_disponibilita_giorno'] = $giorno;
            $notification = [
                'notifications_user_id' => 22,
                'notifications_type' => NOTIFICATION_TYPE_WARNING,
                'notifications_link' => "main/layout/55/{$sede}",
                'notifications_message' => "Richiesta disponibilità rimossa per la sede {$dati_sede['sedi_operative_reparto']}"
            ];
            
            $this->db->insert('notifications', $notification);
            $this->mail_model->send($utente['utenti_email'], 'richiesta_disponibilita_eliminata', 'it', array_merge($data, $dati_sede, $dati_fascia));
        }
        
        
        echo json_encode(['status' => 0]);
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
    
    public function stampaSchedaCompensiCliente($cliente_id = null)
    {
        if ($cliente_id == null) {
            if ($this->auth->get('utenti_tipo') == 15) {
                debug('TODO....', true);
                $cliente_id = $this->apilib->searchFirst('clienti', ['clienti_utente_amministrativo' => $this->auth->get('utenti_id')])['clienti_id'];
            } else {
                show_404();
                exit;
            }
        }
        
        $regenerate = (bool) $this->input->get('_regen');
        
        //Prevedo di poter passare sia un id che l'array coi dati direttamente
        //$data['cartella'] = (is_numeric($cartella))?$this->apilib->view('cartelle_cliniche', $cartella):$cartella;
        
        $mese_start = ($this->input->get('m') == 'tutti') ? 1 : $this->input->get('m');
        $mese_end = ($this->input->get('m') == 'tutti') ? date('m') : $this->input->get('m');
        $anno = $this->input->get('Y');
        
        $physicalDir = "./uploads/schede_compensi";
        $filename = "{$anno}_{$this->input->get('m')}_cliente_schedacompensi_{$cliente_id}";
        $pdfFile = "{$physicalDir}/{$filename}.pdf";
        
        // Vedo se ho già il file generato
        if (!file_exists($pdfFile) or is_development() or $regenerate) {
            $this->load->library('parser');
            $contents = [];
            for ($mese = $mese_start; $mese <= $mese_end; $mese++) {
                $mese = str_pad($mese, 2, '0', STR_PAD_LEFT);
                
                $data = $this->getSchedaCompensiMensileCliente($cliente_id, $mese, $anno);

                $data['mese'] = mese_testuale($mese);
                $data['anno'] = $anno;
                $data['mese_numero'] = $mese;
                $contents[] = $this->parser->parse("custom/pdf/scheda_compensi/cliente_mese", $data, true);
            }
            $content = $this->parser->parse("custom/pdf/scheda_compensi_cliente", compact('contents'), true);
            $html = $this->input->get('html');
            if ($html) {
                die($content);
            } else {
                // Create a temporary file with the view html
                if (!is_dir($physicalDir)) {
                    mkdir($physicalDir, 0755, true);
                }
                $tmpHtml = "{$physicalDir}/{$filename}.html";
                file_put_contents($tmpHtml, $content, LOCK_EX);
                
                // Exec the command
                $options = "-T '20mm' -B '20mm' -O landscape";
                
                exec("wkhtmltopdf {$options} --viewport-size 1024 {$tmpHtml} {$pdfFile}", $output);
                
                //debug($output,true);
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
    
    private function getSchedaCompensiMensileCliente($cliente_id, $mese, $anno)
    {
        $this->load->helper('custom/apib');
        
        $data = [];
        
        $days = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
        $data_fine = "$anno-$mese-$days";
        $data_inizio = "$anno-$mese-01";
        
        $_rapportini_sedi = $this->apilib->search('rapportini', [
            "rapportini_commessa IN (SELECT projects_id FROM projects WHERE projects_customer_id = '$cliente_id' AND (projects_deleted = '0' OR projects_deleted IS NULL OR projects_deleted = ''))",
            // "rapportini_commessa IN (SELECT projects_id FROM projects WHERE projects_customer_id = '$cliente_id' AND (projects_deleted = '0' OR projects_deleted IS NULL) AND (projects_nascosta <> '1' OR projects_nascosta IS NULL))",  // 20200630 - Michael E. - Rimetto il where senza il filtro projects_nascosta in quanto un cliente può aver fatto report di sedi operative successivamente disattivate
            "DATE(rapportini_data) <= '$data_fine' AND DATE(rapportini_data) >= '$data_inizio'",
            'rapportini_commessa IS NOT NULL',
            'rapportini_accessi IS NULL',
            "rapportini_id IN (SELECT rapportini_id FROM rel_rapportini_users)",
        ]);
        
        //debug($_rapportini_sedi,true);
        
        $_report_accessi_sedi = $this->apilib->search('rapportini', [
            "rapportini_commessa IN (SELECT projects_id FROM projects WHERE projects_customer_id = '$cliente_id' AND (projects_deleted = '0' OR projects_deleted IS NULL OR projects_deleted = ''))",
            // "rapportini_commessa IN (SELECT projects_id FROM projects WHERE projects_customer_id = '$cliente_id' AND (projects_deleted = '0' OR projects_deleted IS NULL) AND (projects_nascosta <> '1' OR projects_nascosta IS NULL))",  // 20200630 - Michael E. - Rimetto il where senza il filtro projects_nascosta in quanto un cliente può aver fatto report di sedi operative successivamente disattivate
            "DATE(rapportini_data) <= '$data_fine' AND DATE(rapportini_data) >= '$data_inizio'",
            'rapportini_commessa IS NOT NULL',
            'rapportini_accessi IS NOT NULL',
        
        ]);
        
        $data['rapportini_sedi_accessi'] = $data['rapportini_sedi'] = $data['report_prestazioni_domiciliari'] = [];
        foreach ($_report_accessi_sedi as $report) {
            $report['tariffa'] = calcola_tariffa_accesso_sede($report['rapportini_commessa'], $report['rapportini_fine'], $report['rapportini_affiancamento'] == '1', $report['rapportini_costo_differenziato'] == '1');
            
            $report['tariffa_totale'] = $report['tariffa'] * $report['rapportini_accessi'];
            $data['rapportini_sedi_accessi'][$report['rapportini_commessa']][$report['rapportini_associato']][] = $report;
        }

        //debug($data['rapportini_sedi'],true);
        foreach ($_rapportini_sedi as $report) {
            $associato_user_id = array_key_first($report['rapportini_operatori']);
            
            $associato = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $associato_user_id]);
            
            $associato_id = $associato['dipendenti_id'];
            
            //debug($report, true);
            if ($report['rapportini_festivo'] == '1') {
                $report['projects_orari_categoria'] = 3;
            }
            
            $report['rapportini_operatori'] = $associato;
            $report['rapportini_associato'] = $associato_id;
            
            //recupero le informazioni che mi mancano
            //debug($report,true);
            $report['tariffa_totale'] = calcola_tariffa_totale_oraria_sede($report, false);
            //debug($report['tariffa_totale'],true);
            
            if (empty($data['rapportini_sedi'][$report['rapportini_commessa']])) {
                $data['rapportini_sedi'][$report['rapportini_commessa']] = [];
            }
            
            $data['rapportini_sedi'][$report['rapportini_commessa']][$report['rapportini_associato']][] = $report;
        }
        
        $data['cliente'] = $this->apilib->view('customers', $cliente_id);
        //$data['rimborso_spese'] = (int)($this->db->query("SELECT SUM(rimborsi_km_costo_viaggio) as s FROM rimborsi_km WHERE rimborsi_km_utente = '{$data['associato']['associati_utente']}' AND rimborsi_km_data <= '$data_fine'::date AND rimborsi_km_data >= '$data_inizio'::date")->row()->s);
        //dump($data);
        return $data;
    }
}
