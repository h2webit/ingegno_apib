<?php
class Sondaggi extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With

        $this->load->library('form_validation');
    }

    public function save()
    {
        $sondaggio = $this->input->post('sondaggio_id');
        $risposte = $this->input->post('risposta');
        $sondaggio_compilazione_id = $this->input->post('compilazione_id');
        $timestamp = time();
        $user_id = $this->auth->get('users_id') ?? $this->input->post('user_id');

        //Se non ho compilazione_id in post devo creare il record di compilazione del sondaggio
        if (empty($sondaggio_compilazione_id)) {
            //Ottengo id della compilazione che sto per creare per usarlo come campo preview
            $ultimo_codice_compilazione = $this->db->query("SELECT MAX(sondaggi_compilazioni_id) as m FROM sondaggi_compilazioni")->row();
            if ($ultimo_codice_compilazione->m) {
                $codice_succesivo = $ultimo_codice_compilazione->m + 1;
            } else {
                $codice_succesivo = 1;
            }

            try {
                $sondaggio_compilazione = $this->apilib->create('sondaggi_compilazioni', [
                    'sondaggi_compilazioni_sondaggio_id' => $sondaggio,
                    'sondaggi_compilazioni_user_id' => $user_id,
                    'sondaggi_compilazioni_codice' => $timestamp,
                    'sondaggi_compilazioni_preview' => $codice_succesivo,
                    'sondaggi_compilazioni_data_ora_inizio' => date("Y-m-d H:i:s"),
                ]);
                $sondaggio_compilazione_id = $sondaggio_compilazione['sondaggi_compilazioni_id'];
                
            } catch (Exception $e) {
                die(json_encode(['status' => 0, 'txt' => 'Errore durante la creazione della sessione del questionario.']));
            }
        }

        unset($risposte['ci_csrf_token']);

        foreach ($risposte as $domanda_id => $risposta) {
            $domanda = $this->apilib->view('sondaggi_domande', $domanda_id);

            //Se non ho una risposta per questa domanda torno errore
            if ($domanda['sondaggi_domande_obbligatorio'] == DB_BOOL_TRUE) {
                if (empty($risposte[$domanda_id])) {
                    die(json_encode(['status' => 0, 'txt' => "La domanda {$domanda['sondaggi_domande_domanda']} è obbligatoria", 'compilazione_id' => $sondaggio_compilazione_id]));
                }
                if (is_array($risposta) && count(array_unique($risposta)) === 1) {
                    die(json_encode(['status' => 0, 'txt' => "La domanda {$domanda['sondaggi_domande_domanda']} è obbligatoria", 'compilazione_id' => $sondaggio_compilazione_id]));
                }
            }

            $db_data = [
                'sondaggi_risposte_utenti_utente_id' => $user_id,
                'sondaggi_risposte_utenti_domanda_id' => $domanda_id,
                'sondaggi_risposte_utenti_compilazione_sondaggio' => $sondaggio_compilazione_id,
            ];

            //Se esiste già un record per questa risposta e questo sondaggio vuol dire che sto modificando la risposta data precedentemente
            /*$risposta_esistente = $this->apilib->searchFirst('sondaggi_risposte_utenti', [
                'sondaggi_risposte_utenti_domanda_id' => $domanda_id,
                //'sondaggi_risposte_utenti_utente_id' => $user_id, //vedere se rimuovendo questo riga posso gestire comunque tutto quanto creazione e modifica
                'sondaggi_risposte_utenti_compilazione_sondaggio' => $sondaggio_compilazione_id,
            ]);*/
            $risposta_esistente = $this->db->get_where('sondaggi_risposte_utenti', ['sondaggi_risposte_utenti_domanda_id' => $domanda_id, 'sondaggi_risposte_utenti_compilazione_sondaggio' => $sondaggio_compilazione_id])->row_array();

            if (is_array($risposta)) { // questa è una checkbox
                $unique_values = array_unique(array_filter(array_values($risposta)));
                $risposta_diversa = $risposta_esistente && (array_diff($unique_values, json_decode($risposta_esistente['sondaggi_risposte_utenti_risposta_valore'], true)) || array_diff(json_decode($risposta_esistente['sondaggi_risposte_utenti_risposta_valore'], true), $unique_values));
                $db_data['sondaggi_risposte_utenti_risposta_valore'] = json_encode($unique_values);
            } else {
                if (in_array($domanda['sondaggi_domande_tipologia_value'], ['Risposta singola', 'Risposta singola - radio'])) {
                    $db_data['sondaggi_risposte_utenti_risposta_id'] = $risposta;
                    $risposta_diversa = $risposta_esistente && $risposta != $risposta_esistente['sondaggi_risposte_utenti_risposta_id'];
                } else {
                    $db_data['sondaggi_risposte_utenti_risposta_valore'] = $risposta;
                    $risposta_diversa = $risposta_esistente && $risposta != $risposta_esistente['sondaggi_risposte_utenti_risposta_valore'];
                }
            }

            $db_data['sondaggi_risposte_utenti_timestamp'] = $timestamp;


            if (!empty($risposta_esistente)) {
                // DA MICHAEL - PER VERIFICARE LA DIFFERENZA TRA JSON IN ARRIVO E QUELLA GIà SALVATA, DEVI FARE IL JSON_DECODE TRUE DEL JSON SALVATO SU DB E FARE ARRAY_DIFF TRA DB_DATA E RISPOSTA_ESISTENTE, SE ARRAY TORNA VUOTO, ALLORA è TUTTO UGUALE, PER INFO VEDI https://www.php.net/manual/en/function.array-diff.php
                try {
                    // DA MICHAEL - SE VUOI CHE RIMANGA IL VECCHIO UTENTE, O SOVRASCRIVI USER ID PRENDENDO DA $risposta_esistente VEDI NEL CASO SE FARE COMUNQUE QUESTO O NO
                    //if ($risposta_esistente['user_id'] !== $user_id) {
                    //   $db_data['user_id'] = $risposta_esistente['user_id']
                    //}
                    if ($risposta_diversa) {
                        $this->apilib->edit('sondaggi_risposte_utenti', $risposta_esistente['sondaggi_risposte_utenti_id'], $db_data);
                    }
                } catch (Exception $e) {
                    die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                }
            } else {
                //Creazione record risposta
                if (!empty($risposte[$domanda_id])) {
                    try {
                        //$this->apilib->create('sondaggi_risposte_utenti', $db_data);
                        $this->db->insert('sondaggi_risposte_utenti', $db_data);
                    } catch (Exception $e) {
                        die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                    }
                }
                /*//Creazione record risposta
                try {
                    $this->apilib->create('sondaggi_risposte_utenti', $db_data);
                } catch (Exception $e) {
                    die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                } */
            }
        }

        //Salvataggio risposte OK, aggiungo data fine alla compilazione
        try {
            $this->apilib->edit('sondaggi_compilazioni', $sondaggio_compilazione_id, [
                'sondaggi_compilazioni_data_ora_fine' => date("Y-m-d H:i:s"),
            ]);
        } catch (Exception $e) {
            log_message('error', 'Errore in creazione sessione questionario: '.$e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Errore durante la creazione della sessione del questionario.']));
        }

        //Se sono da app devo tornarmi almeno l'id del sondaggio appena creato
       $sondaggio_da_app = $this->input->post('sondaggio_da_app');
        if(isset($sondaggio_da_app) && !empty($sondaggio_da_app)) {
            die(json_encode(['status' => 1, 'txt' => 'Checklist salvata', 'compilazione_id' => $sondaggio_compilazione_id]));
        }

        die(json_encode(['status' => 1, 'txt' => 'Risposte salvate']));
    }


    /**
     * ! Cerco sondaggio legato all'appuntamento selezionato
     */
    public function getSondaggio()
    {
        $sondaggio_id = $this->input->post('sondaggio_id');
        if(empty($sondaggio_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Sondaggio non riconosciuto. Contattare l\'assitenza']));
        }

        //$sondaggio = $this->apilib->searchFirst('sondaggi', ['sondaggi_tipologia' => $sondaggio_id]);
        $sondaggio = $this->apilib->view('sondaggi', $sondaggio_id);
        //dump($sondaggio);

        if (!empty($sondaggio)) {

            $domande_sondaggio = $this->apilib->search('sondaggi_domande', [
                'sondaggi_domande_sondaggio_id' => $sondaggio['sondaggi_id'],
            ]);

            $steps = $this->apilib->search("sondaggi_step", ['sondaggi_step_sondaggio_id' => $sondaggio['sondaggi_id']], null, 0, 'sondaggi_step_ordine', 'ASC');

            $sections = [];

            if (!empty($steps)) {
                $domande_step = [];

                foreach ($steps as $index => $step) {
                    $domande_step = $this->apilib->search('sondaggi_domande', [
                        'sondaggi_domande_sondaggio_id' => $sondaggio['sondaggi_id'],
                        'sondaggi_domande_step' => $step['sondaggi_step_id']
                    ], null, 0, 'sondaggi_domande_ordine', 'ASC');
                
                    // Se sono in una domanda che prevede la scelta di risposte, devo prendermi le possibili risposte
                    //& mi permette di operare direttamente sul dato invece che sulla sua copia come avviene normalmente
                    foreach ($domande_step as &$domanda) {
                        $domanda['risposte'] = [];
                        if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox") {
                            $risposte = $this->apilib->search('sondaggi_domande_risposte', [
                                'sondaggi_domande_risposte_domanda_id' => $domanda['sondaggi_domande_id'],
                            ]);
                            // Popolo array di possibili risposte
                            if (!empty($risposte)) {
                                $domanda['risposte'] = $risposte;
                            }
                        }
                    }
                    unset($domanda); // Rimuovi il riferimento
                
                    $sections[$index]['step'] = $step['sondaggi_step_name'];
                    $sections[$index]['step_info'] = $step;
                    $sections[$index]['step_n'] = $index;
                    $sections[$index]['step_finale'] = $index + 1 == count($steps) ? true : false;
                    $sections[$index]['domande'] = $domande_step;
                    $sections[$index]['sondaggio_id'] = $sondaggio['sondaggi_id'];
                }
            }
            die(json_encode(['status' => 1, 'data' => $sections]));

        } else {
            die(json_encode(['status' => 0, 'txt' => 'Nessun sondaggio associato al tipo della commessa scelta']));
        }
    }
}