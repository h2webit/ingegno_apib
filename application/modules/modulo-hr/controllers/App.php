<?php

class App extends MY_Controller
{
    public function __construct()
    {

        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With

        $this->load->model('timbrature');
        $this->timbrature->scope('APP');
    }

    /**
     * Timbra entrata
     */
    public function timbraEntrata()
    {
        $post = $this->input->post();

        $dipendente_id = @$post['dipendente_id'];
        $ora_entrata = @$post['ora_entrata'];
        $reparto = @$post['reparto_id'];
        $latitude = @$post['latitude'];
        $longitude = @$post['longitude'];
        $scope = @$post['scope'];
        $data_entata = @$post['data_entrata'];

        $entrata = $this->timbrature->timbraEntrata($dipendente_id, $ora_entrata, $data_entata, $scope, $reparto, null, null, $latitude, $longitude);
        /* @TODO: fare un json con elenco entrate e uscite, attenzione a tutti i punti dove è usato */
        die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'entrata' => $entrata]));
    }

    /**
     * Timbra uscita
     */
    public function timbraUscita()
    {
        $post = $this->input->post();

        $dipendente_id = @$post['dipendente_id'];
        $ora_uscita = @$post['ora_uscita'];
        $data_uscita = @$post['data_uscita'] ?? null;
        $scope = @$post['scope'];

        $uscita = $this->timbrature->timbraUscita($dipendente_id, $ora_uscita, $data_uscita, null, $scope);

        die(json_encode(['status' => 1, 'txt' => 'Uscita salvata con successo', 'uscita' => $uscita]));
    }

    /**
     * Crea o cancella reperibilita del dipendente per la giornata in corso
     * ! 05/08/2022 - Rimossa la parte di switch tra creazione e cancellazione
     * !Si crea e basta, cancellazione solo se solo nell'arco di un'ora dalla creazione
     * ! Se trova reperibilità già dichiarata per oggi torna errore
     */
    public function dichiaraReperibilita()
    {
        $post = $this->input->post();
        $dipendente_id = $post['dipendente_id'];
        $selected_date = $post['data_selezionata'];

        if (empty($dipendente_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente non riconosciuto']));
        }
        if (empty($selected_date)) {
            die(json_encode(['status' => 0, 'txt' => 'Data della reperibilità non comunicata']));
        }

        //Cerco reperibilita del dipendente per oggi, se c'è la cancello altrimenti la creo
        $data = date('Y-m-d', strtotime($selected_date));
        $reperibilita = $this->apilib->searchFirst('reperibilita', ['reperibilita_dipendente' => $dipendente_id, 'reperibilita_data' => $data]);

        if (!empty($reperibilita)) {
            die(json_encode(['status' => 0, 'txt' => 'Hai già dichiarato la reperibilità per la data selezionata']));
        } else {
            try {
                $reperibilita = $this->apilib->create('reperibilita', [
                    'reperibilita_dipendente' => $dipendente_id,
                    'reperibilita_data' => $data,
                ]);
                die(json_encode(['status' => 1, 'txt' => 'Reperibilità creata con successo', 'data' => $reperibilita]));
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                die(json_encode(['status' => 0, 'txt' => 'Errore durante il salvataggio della reperibilità']));
            }
        }
    }

    /**
     * Cancella reperibilità
     */
    public function cancellaReperibilita()
    {
        $post = $this->input->post();
        $reperibilita_id = $post['reperibilita'];

        if (empty($reperibilita_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Reperibilità non riconosciuta']));
        }

        $reperibilita = $this->apilib->view('reperibilita', $reperibilita_id);
        if (empty($reperibilita)) {
            die(json_encode(['status' => 0, 'txt' => 'Reperibilità non riconosciuta']));
        }

        $creation_date = new DateTime($reperibilita['reperibilita_creation_date']);
        $data = new DateTime();

        //Se sono entro un'ora dalla creazione possono cancellare la repoeribilità
        $diff_giorni = $data->diff($creation_date)->d;
        $diff_ore = $data->diff($creation_date)->h;
        $diff_minuti = $data->diff($creation_date)->i;

        if ($diff_giorni == 0 && $diff_ore == 0 && $diff_minuti <= 59) {
            try {
                $reperibilita = $this->apilib->delete('reperibilita', $reperibilita_id);

                die(json_encode(['status' => 1, 'txt' => 'Reperibilità revocata con successo', 'data' => $reperibilita]));
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                die(json_encode(['status' => 0, 'txt' => 'Errore durante la revoca della reperibilità']));
            }
        } else {
            die(json_encode(['status' => 0, 'txt' => "La disponibilità può essere revocata al massimo entro un ora dalla creazione"]));
        }
    }

    /**
     * Timbratura con NFC - dismesso visto che si timbra con il campo personalizzabile e non più con l'id reparto
     */
    /*public function timbraNfc()
    {
        $post = $this->input->post();

        $dipendente_id = @$post['dipendente_id'];
        $reparto_id = @$post['reparto_id'];

        if(empty($dipendente_id) || empty($reparto_id)) {
            log_message('error', "Impossibile timbrare, utenteo repart o mancante");
            die(json_encode(['status' => 0, 'txt' => 'Utente o reparto non riconosciuti']));
            exit;
        }
        //controllo che il dipendente sia associato a quel reparto
        $reparto_detail = $this->apilib->searchFirst('rel_reparto_dipendenti', [
            'reparti_id' => $reparto_id, 
            'dipendenti_id' => $dipendente_id
        ]);
                
        if(empty($reparto_detail)) {
            log_message('error', "Impossibile timbrare, utente non associato al reparto");
            die(json_encode(['status' => 0, 'txt' => 'Utente non associato al reparto']));
            exit;
        }

        //verifico che l'utente non sia già presente, in quel caso deve fare l'uscita
        $presenza_ordierna = $this->apilib->searchFirst('presenze', [
            'presenze_dipendente' => $dipendente_id, 
            'presenze_data_inizio' => date('Y-m-d'), 
            'presenze_data_fine IS NULL or presenze_data_fine = ""'
        ]);
        
        if(empty($presenza_ordierna)) {
            $entrata = $this->timbrature->timbraEntrata($dipendente_id, null, null, "NFC", $reparto_id);
            die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'tipo' => 'entrata', 'data' => $entrata]));
        } else {
            $uscita = $this->timbrature->timbraUscita($dipendente_id, null, null, null, "NFC");
            die(json_encode(['status' => 1, 'txt' => 'Uscita registrata correttamente.', 'tipo' => 'uscita', 'data' => $uscita]));
        }
    }*/



    /**
     * ! Timbra con NFC
     * * Usato campo personalizzabile e non più id reparto
     */
    public function timbraNfc()
    {
        $post = $this->input->post();

        $dipendente_id = @$post['dipendente_id'];
        $reparto_code = @$post['reparto_id']; //in realtà è il campo tag sul reparto, per compatibilità lasciamo la variabile reparto_id
        $cliente = @$post['cliente'] ?? null;
        $commessa = @$post['commessa'] ?? null;

        if (empty($dipendente_id) || empty($reparto_code)) {
            log_message('error', "Impossibile timbrare, utente o reparto mancante");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, utente o reparto non riconosciuti']));
            exit;
        }

        $reparto = $this->apilib->searchFirst('reparti', [
            'reparti_tag_nfc_code' => $reparto_code,
        ]);

        if (empty($reparto)) {
            log_message('error', "Impossibile timbrare, nessun reparto associato al tag scansionato");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, nessun reparto associato al tag scansionato']));
            exit;
        }

        //controllo che il dipendente sia associato a quel reparto
        $reparto_detail = $this->apilib->searchFirst('rel_reparto_dipendenti', [
            'reparti_id' => $reparto['reparti_id'],
            'dipendenti_id' => $dipendente_id
        ]);

        if (empty($reparto_detail)) {
            log_message('error', "Impossibile timbrare, utente non associato al reparto");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, utente non associato al reparto']));
            exit;
        }

        //verifico che l'utente non sia già presente, in quel caso deve fare l'uscita
        $presenza_ordierna = $this->apilib->searchFirst('presenze', [
            'presenze_dipendente' => $dipendente_id,
            'presenze_data_inizio' => date('Y-m-d'),
            'presenze_data_fine IS NULL or presenze_data_fine = ""'
        ]);

        if (empty($presenza_ordierna)) {
            $entrata = $this->timbrature->timbraEntrata($dipendente_id, null, null, "NFC", $reparto['reparti_id'], $cliente, $commessa);
            die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'tipo' => 'entrata', 'data' => $entrata]));
        } else {
            $uscita = $this->timbrature->timbraUscita($dipendente_id, null, null, null, "NFC");
            die(json_encode(['status' => 1, 'txt' => 'Uscita registrata correttamente.', 'tipo' => 'uscita', 'data' => $uscita]));
        }
    }

    public function timbraGPS()
    {
        $post = $this->input->post();

        $dipendente_id = @$post['dipendente_id'];
        $orario = @$post['orario'];
        $reparto_code = @$post['reparto_id']; //in realtà è il campo tag sul reparto, per compatibilità lasciamo la variabile reparto_id
        $cliente = @$post['cliente'] ?? null;
        $commessa = @$post['commessa'] ?? null;
        $latitude = @$post['latitude'];
        $longitude = @$post['longitude'];


        if (empty($dipendente_id) || empty($reparto_code)) {
            log_message('error', "Impossibile timbrare, utente o reparto mancante");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, utente o reparto non riconosciuti']));
            exit;
        }

        if (empty($cliente) || empty($commessa)) {
            log_message('error', "Impossibile timbrare con GPS per dipendente #{$dipendente_id}, cliente o commessa mancanti");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, cliente e/o commessa non riconosciuti']));
            exit;
        }

        $reparto = $this->apilib->view('reparti', $reparto_code);

        if (empty($reparto)) {
            log_message('error', "Impossibile timbrare, reparto non riconosciuto");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, reparto non riconosciuto']));
            exit;
        }

        //Se ho dipendenti associati al reparto e non sono tra quelli devo bloccare
        $rel_reparto_dipendenti = $this->apilib->search('rel_reparto_dipendenti', [
            'reparti_id' => $reparto['reparti_id'],
        ]);
        $found = in_array($dipendente_id, array_column($rel_reparto_dipendenti, 'dipendenti_id'));

        if (!empty($rel_reparto_dipendenti) && !$found) {
            log_message('error', "Impossibile timbrare, dipendente #{$dipendente_id} non associato al reparto #{$reparto['reparti_id']}");
            die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, dipendente non associato al reparto']));
            exit;
        }

        //verifico che il dipendente non sia già presente, in quel caso deve fare l'uscita
        $presenza_ordierna = $this->apilib->searchFirst('presenze', [
            'presenze_dipendente' => $dipendente_id,
            'presenze_data_inizio' => date('Y-m-d'),
            'presenze_data_fine IS NULL or presenze_data_fine = ""'
        ]);

        if (empty($presenza_ordierna)) {
            $entrata = $this->timbrature->timbraEntrata($dipendente_id, $orario, null, "GPS", $reparto['reparti_id'], $cliente, $commessa, $latitude, $longitude);
            die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'tipo' => 'entrata', 'data' => $entrata]));
        } else {
            $uscita = $this->timbrature->timbraUscita($dipendente_id, null, null, null, "GPS");
            die(json_encode(['status' => 1, 'txt' => 'Uscita registrata correttamente.', 'tipo' => 'uscita', 'data' => $uscita]));
        }
    }

    /**
     * ! Cambio password dipendente
     */
    public function cambiaPassword()
    {
        $post = $this->input->post();

        $dipendente_id = @$post['dipendente_id'];
        $psw_corrente = @$post['current'];
        $psw_nuova = @$post['new'];
        $psw_nuova_conferma = @$post['confirm'];

        if (empty($dipendente_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente non riconosciuto']));
        }
        if (empty($psw_corrente) || empty($psw_nuova) || empty($psw_nuova_conferma)) {
            die(json_encode(['status' => 0, 'txt' => 'Devi compilare tutte e tre le password']));
        }

        // Cerco dipendente con password fornita
        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
        if (empty($dipendente)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente non riconosciuto']));
        }

        // Controllo le password
        if ($dipendente['dipendenti_password'] != md5($psw_corrente)) {
            die(json_encode(['status' => 0, 'txt' => 'La password corrente che è stata fornita non coincide con quella salvata nel sistema.']));
        }
        if ($psw_nuova != $psw_nuova_conferma) {
            die(json_encode(['status' => 0, 'txt' => 'La nuova password non coincide con quella richiesta per la conferma.']));
        }
        if ($dipendente['dipendenti_password'] === md5($psw_nuova)) {
            die(json_encode(['status' => 0, 'txt' => 'La nuova password deve essere diversa da quella corrente.']));
        }

        try {
            //Cambio password dipendente
            $updated_dipendente = $this->apilib->edit('dipendenti', $dipendente['dipendenti_id'], [
                "dipendenti_password" => $psw_nuova
            ]);
            // Cambio password utente
            $this->apilib->edit('users', $dipendente['dipendenti_user_id'], [
                "users_password" => $psw_nuova
            ], false);

            die(json_encode(['status' => 1, 'txt' => 'Modifica password effettuata con successo', 'data' => $updated_dipendente]));
        } catch (Exception $e) {
            log_message('error', "Errore durante la modifica password dipendente #{$dipendente_id} e utente da app: " . $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Si è verificato un errore durante il salvataggio della nuova password.']));
        }
    }

    /**
     * ! Imposta giustificativo presenza
     */
    public function impostaGiustificativo()
    {
        $post = $this->input->post();

        $presenza_id = @$post['presenza_id'];
        $giustificativo = @$post['giustificativo'];

        if (empty($presenza_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Presenza non riconosciuta']));
        }
        if (empty($giustificativo)) {
            die(json_encode(['status' => 0, 'txt' => 'Il campo giustificativo non può essere vuoto']));
        }

        try {
            $this->db->query("UPDATE presenze SET presenze_note = '$giustificativo' WHERE presenze_id = '$presenza_id'");
            $updated_presenza = $this->db->where('presenze_id', $presenza_id)->get('presenze')->row_array();

            die(json_encode(['status' => 1, 'txt' => 'Giustificativo salvato con successo', 'presenza' => $updated_presenza]));
        } catch (Exception $e) {
            log_message('error', "Errore durante salvataggio giustivaie presenza #{$presenza_id}: " . $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Si è verificato un errore durante il salvataggio del giustificativo.']));
        }
    }

    /**
     * ! Torna la banca ore del dipendente in base al confronto con settings modulo
     */
    public function bancaOre()
    {
        $post = $this->input->post();
        $dipendente_id = @$post['dipendente_id'];

        if (empty($dipendente_id)) {
            die(json_encode(['status' => 1, 'txt' => 'Dipendente non riconosciuto']));
        }

        $settings = $this->apilib->searchFirst('impostazioni_hr');

        $giorno_sblocco = (int) min($settings['impostazioni_hr_giorno_sblocco_banca_ore'] ?? 0, 31);
        $giorno = intval(date('d'));

        $AND = '';
        $txt = '';
        $data_aggiornamento = date('d/m/Y');

        if (empty($giorno_sblocco) || $giorno_sblocco == 0) {
            $txt = 'Banca ore real time';
        } else if ($giorno < $giorno_sblocco) {
            // Devo tornare la banca ore di due mesi fa
            // Es. sblocco = 10, oggi è 5 agosto, devo tornare la banca ore fine a fine giugno
            $AND = " AND DATE_FORMAT(banca_ore_data, '%Y-%m') <= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), '%Y-%m')";
            $txt = 'Banca ore fino a due mesi fa';
            $data_aggiornamento = (new DateTime('last day of -2 months'))->format('d/m/Y');
        } else {
            // Devo tornare la banca ore di due mesi fa
            // Es. sblocco = 10, oggi è 16 agosto, devo tornare la banca ore fino a fine luglio
            $AND = " AND DATE_FORMAT(banca_ore_data, '%Y-%m') <= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
            $txt = 'Banca ore ore fino al mese precedente';
            $data_aggiornamento = (new DateTime('last day of last months'))->format('d/m/Y');
        }

        try {
            $banca_ore = $this->db->query("
                SELECT *
                FROM banca_ore
                JOIN banca_ore_movimento ON banca_ore.banca_ore_movimento = banca_ore_movimento.banca_ore_movimento_id
                WHERE banca_ore_dipendente = '{$dipendente_id}'
                AND banca_ore_movimento_id <> '2'
                {$AND}
                ORDER BY banca_ore_data DESC
            ")->result_array();

            //Calcolo saldo
            $saldo = 0;

            if (!empty($banca_ore)) {

                foreach ($banca_ore as $movimento) {
                    switch ($movimento['banca_ore_movimento']) {
                        case "1": // Aggiunto
                            $saldo += floatval($movimento['banca_ore_hours']);
                            break;
                        case "2": // Cancellata
                            $saldo += 0;
                            break;
                        case "3": // Pagata
                        case "4": // Utilizzata
                            $saldo -= floatval($movimento['banca_ore_hours']);
                            break;
                        default:
                            $saldo += 0;
                            break;
                    }
                }
            }

            die(json_encode(['status' => 0, 'txt' => $txt, 'data' => $banca_ore, 'saldo' => number_format($saldo, 2), 'data_aggiornamento' => $data_aggiornamento]));
        } catch (Exception $e) {
            log_message('error', 'Errore recuperando dati banca ore da app: ' . $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Si è verificato un errore durante la richiesta della banca ore']));
        }
    }



    public function testTimbraentrata($dipendente_id, $ora_entrata, $reparto)
    {





        $entrata = $this->timbrature->timbraEntrata($dipendente_id, $ora_entrata, null, 'TEST', $reparto, null, null, null, null);
        /* @TODO: fare un json con elenco entrate e uscite, attenzione a tutti i punti dove è usato */
        die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'entrata' => $entrata]));
    }
    public function testTimbrauscita($dipendente_id, $ora_uscita)
    {

        $uscita = $this->timbrature->timbraUscita($dipendente_id, $ora_uscita, null, null, 'TEST');
        /* @TODO: fare un json con elenco entrate e uscite, attenzione a tutti i punti dove è usato */
        die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'uscita' => $uscita]));
    }
}