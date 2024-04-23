<?php

class Qrcode extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With
        
        $this->load->model('timbrature');
    }
    public function inquadraQr() {
        $post = $this->input->post();
        $dipendente_id = @$post['dipendente_id'];
        $qr_value = @$post['value'];

        if (empty($dipendente_id) && empty($qr_value)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente o codice QR non riconosciuto']));
        }

        $this->scan($dipendente_id, $qr_value);
    }

    public function scan($dipendente_id, $value_id){
        //todo: da capire come gestire questo controllo per bloccare trimbratura da camera
        if(empty($dipendente_id)){
            $this->auth->logout();
            dump('dipendente non loggato');
            exit;
            redirect();
        }
        $reparto = '';
        $dipendente = $dipendente_id;

        //verifico che il qrcode fosse uno degli ultimi 3 
        $ultimo_qrcode = $this->apilib->searchFirst('qrcode', [
            'qrcode_valore' => $value_id,
        ]);

        $settings = $this->apilib->searchFirst('impostazioni_hr', [
            'impostazioni_hr_qrcode_generico' => $value_id
        ]);

        //vuoto o sto usando field tag_nfc
        if(empty($ultimo_qrcode) && empty($settings)) {
            $reparto_tag = $this->apilib->searchFirst('reparti', [
                'reparti_tag_nfc_code' => $value_id,
            ]);
            
            if(empty($reparto_tag)) {
                log_message('error', "Impossibile timbrare, campo tag su repart non riconosciuto");
                die(json_encode(['status' => 0, 'txt' => 'Impossibile timbrare, campo tag del reparto non riconosciuto']));
                exit;
            }
            $reparto =  $reparto_tag['reparti_id'];
        } else {
            if(empty($settings)){

                $reparto =  $ultimo_qrcode['qrcode_reparto'];
            }
        }

        //controllo che il dipendente sia associato a quel reparto
        if(!empty($reparto)){
            $reparto_detail = $this->apilib->searchFirst('rel_reparto_dipendenti', [
                'reparti_id' => $reparto, 
                'dipendenti_id' => $dipendente
            ]);
    
            //controllo che il reparto di cui ho letto il tag NON abbia dipendenti
            if(!empty($reparto_detail)){
                $reparto_tag_detail = $this->db->query("SELECT * FROM reparti WHERE reparti_id = '$reparto' AND reparti_id NOT IN (SELECT reparti_id FROM rel_reparto_dipendenti)")->row();      
    
            } else {
                $reparto_tag_detail = '';
            }
            
            if(empty($reparto_detail) && empty($reparto_tag_detail)){
                log_message('error', "Impossibile timbrare, utente non associato al reparto");
                die(json_encode(['status' => 0, 'txt' => 'Utente non associato al reparto']));
                exit;
            }
        }
        

        if (!empty($ultimo_qrcode)) {
            if($ultimo_qrcode['qrcode_active'] != DB_BOOL_TRUE){
                log_message('error', "Errore, il qrcode non è più valido, si prega di rifare la scansione.");
                die(json_encode(['status' => 0, 'txt' => 'Errore, il qrcode non è più valido, si prega di rifare la scansione.']));
            }

            //verifico che l'utente non sia già presente, in quel caso deve fare l'uscita
            $presenze_odierna = $this->apilib->searchFirst('presenze', [
                'presenze_dipendente' => $dipendente, 
                'presenze_data_inizio' => date('Y-m-d'), 
                'presenze_data_fine IS NULL or presenze_data_fine = ""'
            ]);

            $utenti_attuali = $ultimo_qrcode['qrcode_users']+1;

            if (!empty($presenze_odierna)) {
                //se uscita, non serve ricrearlo, deve solo timbrare l'uscita.
                //edit, anche se uscita, viene invalidato
                if(!$ultimo_qrcode['qrcode_first_edit'] || $ultimo_qrcode['qrcode_first_edit'] == NULL){
                    $array_edit = [
                            'qrcode_first_edit' => date('Y-m-d H:i:s'),
                            'qrcode_users' => $utenti_attuali,
                    ];
                } else {
                    $array_edit = [
                        'qrcode_users' => $utenti_attuali,
                    ];
                }

                $uscita = $this->timbrature->timbraUscita($dipendente,null,null,null,"QRCODE");

                try {
                    $this->apilib->create('qrcode_users_detail', [
                        'qrcode_users_detail_dipendente_id' => $dipendente,
                        'qrcode_users_detail_tipologia' => 2,
                        'qrcode_users_detail_qrcode_id' => $ultimo_qrcode['qrcode_id'],
                    ]);
                } catch (Exception $e) {
                    log_message('error', "Impossibile creare la riga di accesso: Error: {$e->getMessage()}");
                    exit;
                }
                //aggiungo il numero di utilizzo al qrcode
                $this->apilib->edit('qrcode', $ultimo_qrcode['qrcode_id'],$array_edit);
                //rigenero qrcode
                $this->generate(1,$reparto);
                die(json_encode(['status' => 1, 'txt' => 'Uscita registrata correttamente.', 'tipo' => 'uscita', 'data' => $uscita]));
            } else {
                $entrata = $this->timbrature->timbraEntrata($dipendente,null,null,"QRCODE",$reparto);
                $id_entrata = $entrata['presenze_id'];

                //prendere ultima riga inserita e registrarla come utilizzo del qrcode
                //aggiorno il qrcode settando utente +1
                if(!$ultimo_qrcode['qrcode_first_edit'] || $ultimo_qrcode['qrcode_first_edit'] == NULL){
                    $array_edit = [
                            'qrcode_first_edit' => date('Y-m-d H:i:s'),
                            'qrcode_users' => $utenti_attuali,
                            'qrcode_presenza_id' => $id_entrata
                    ];
                } else {
                    $array_edit = [
                        'qrcode_users' => $utenti_attuali,
                        'qrcode_presenza_id' => $id_entrata
                    ];
                }

                try {
                    $this->apilib->edit('qrcode', $ultimo_qrcode['qrcode_id'],$array_edit);
                } catch (Exception $e) {
                    log_message('error', "Impossibile modificare il qrcode attuale: Error: {$e->getMessage()}");
                    throw new Exception('Impossibile modificare il qrcode attuale');
                    exit;
                }

                //inserisco in qrcode_users_detail per tenere traccia dei login
                try {
                    $this->apilib->create('qrcode_users_detail', [
                        'qrcode_users_detail_dipendente_id' => $dipendente,
                        'qrcode_users_detail_tipologia' => 1,
                        'qrcode_users_detail_qrcode_id' => $ultimo_qrcode['qrcode_id']
                    ]);
                } catch (Exception $e) {
                    log_message('error', "Impossibile creare la riga di dettaglio del qrcode: Error: {$e->getMessage()}");
                    throw new Exception('Impossibile creare la riga di dettaglio del qrcode');
                    exit;
                }
                
                //rigenero qrcode
                $this->generate(1,$reparto);
                //stampo risultato
                die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'tipo' => 'entrata', 'data' => $entrata]));
            }
        } else if (!empty($reparto_tag) || !empty($settings)) {
            //verifico che l'utente non sia già presente, in quel caso deve fare l'uscita
            $presenze_odierna = $this->apilib->searchFirst('presenze', [
                'presenze_dipendente' => $dipendente, 
                'presenze_data_inizio' => date('Y-m-d'), 
                'presenze_data_fine IS NULL or presenze_data_fine = ""'
            ]);
            if (!empty($presenze_odierna)) {
                //deve solo timbrare l'uscita.
                $uscita = $this->timbrature->timbraUscita($dipendente,null,null,null,"QRCODE_PRINT");
                die(json_encode(['status' => 1, 'txt' => 'Uscita registrata correttamente.', 'tipo' => 'uscita', 'data' => $uscita]));
            } else {
                //timbro entrata
                if(isset($reparto)){
                    $entrata = $this->timbrature->timbraEntrata($dipendente,null,null,"QRCODE_PRINT",$reparto);
                } else {
                    $entrata = $this->timbrature->timbraEntrata($dipendente,null,null,"QRCODE_PRINT");

                }
                die(json_encode(['status' => 1, 'txt' => 'Entrata salvata con successo', 'tipo' => 'entrata', 'data' => $entrata]));
            }

        } else {
            //Campo tag qr non corretto (stampa) o ultimo qr letto non valido (tablet)
            throw new Exception('Errore, il qrcode non esiste, contattare l\assistenza.');
            exit;
        } 
    }
    
    public function generate($forza_update = 0, $reparto = null, $pwa = false)
    {
        /* C'era un cron ma disattivato, altrimenti rigenerava quello prinicipale ogni volta */
        $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
        $time_refresh = $impostazioni_modulo['impostazioni_hr_time_refresh'];
        if($this->input->get('reparto')){
            $reparto = $this->input->get('reparto');
        }
        /* PRIMA VERIFICO SE E' QUELLO GENERICO */
        $settings = $this->apilib->searchFirst('impostazioni_hr', [
            'impostazioni_hr_qrcode_generico' => $this->input->get('reparto')
        ]);

        if(!empty($settings)){
            //controllo non esista già
            $unique=FALSE;
            while(!$unique)
            {
                $nuovo_qrcode =  $this->generateRandomString(12);

                if($nuovo_qrcode != $settings['impostazioni_hr_qrcode_generico']){
                    $unique=TRUE;
                }
            }
            log_message('error', "Modifico il qrcode principale, è stato rigenerato");
            $this->apilib->edit('impostazioni_hr', $settings['impostazioni_hr_id'],['impostazioni_hr_qrcode_generico' => $nuovo_qrcode ]);
            if($pwa == true){
                die(json_encode(['status' => 1, 'txt' => 'Pagina ricaricata']));

            }

        } else {
            $ultimo_qrcode = $this->apilib->searchFirst('qrcode', [
                'qrcode_active' => DB_BOOL_TRUE,
                'qrcode_reparto' => $reparto,
            ]);
            //prima installazione
            if (empty($ultimo_qrcode)) {
                //prendo tutti i reparti, li ciclo e creo il qrcode
                $reparti = $this->apilib->search('reparti');
                foreach($reparti as $reparto){
                    $nuovo_qrcode =  $this->generateRandomString(5);
                    $this->createQrcode($nuovo_qrcode,$reparto['reparti_id']);
                }
            }
            else{
                //controllo se ci sono utenti che hanno fatto login e sono passati i time_refresh
                if($ultimo_qrcode['qrcode_users']>0 || $forza_update == 1){
                    /*$datetime1 = new DateTime();
                    $datetime2 = new DateTime($ultimo_qrcode['qrcode_first_edit']);
                    $interval = $datetime1->diff($datetime2);
                    $elapsed = $interval->i; */
                    //if($elapsed > $time_refresh || $forza_update == 1){
                        //disattivo vecchio qrcode
                        try {
                            $this->apilib->edit('qrcode', $ultimo_qrcode['qrcode_id'], [
                                'qrcode_active' => DB_BOOL_FALSE,
                            ]);
                        } catch (Exception $e) {
                            log_message('error', "Impossibile modificare il qrcode attuale: Error: {$e->getMessage()}");
                            throw new Exception('Impossibile modificare il qrcode attuale');
                            exit;
                        }
                        //controllo non esista già
                        $unique=FALSE;
                        while(!$unique)
                        {
                            $nuovo_qrcode =  $this->generateRandomString(5);
                            $ultimo_qrcode = $this->apilib->searchFirst('qrcode', [
                                'qrcode_valore' => $nuovo_qrcode,
                            ]);
                            if(!$ultimo_qrcode){
                                $unique=TRUE;
                            }
                        }
                        $this->createQrcode($nuovo_qrcode,$reparto);
                        //creo il nuovo qrcode
                        //Se decommentato crea correttamente qr code e apre/chiude presenza ma da errore su app perchè mi aspetto la risposta formattata come in scan
                        //die(json_encode(['status' => 1, 'txt' => 'creato con successo']));
                        
                }
            }
        }
        /*echo json_encode([
            'success' => 'true',
        ]);*/
    }
    public function generateRandomString($length = 5){
            $randomString = substr(str_shuffle('0123456789'),1,$length);
            return $randomString;
    }
    public function createQrcode($nuovo_qrcode,$reparto){
        try {
            $this->apilib->create('qrcode', [
                'qrcode_valore' => $nuovo_qrcode,
                'qrcode_users' => 0,
                'qrcode_active' => DB_BOOL_TRUE,
                'qrcode_reparto' => $reparto,
            ]);
        } catch (Exception $e) {
            log_message('error', "Impossibile creare il nuovo qrcode: Error: {$e->getMessage()}");
            throw new Exception('Impossibile creare il nuovo qrcode');
            exit;
        }
    }
    public function scansiona_badge($badge,$reparto = null){
        $dipendente = $this->db->query("SELECT dipendenti_id FROM dipendenti WHERE dipendenti_badge= '$badge' OR dipendenti_badge_app= '$badge'")->row();
        if(empty($dipendente)){
            die(json_encode(['status' => 0, 'txt' => 'Errore, utente non trovato']));
        }
        $dipendente = $dipendente->dipendenti_id;
        //todo: da capire come gestire questo controllo per bloccare trimbratura da camera
        if(empty($dipendente)){
            $this->auth->logout();
            dump('dipendente non loggato');
            exit;
            redirect();
        }
        if(!empty($reparto)){
            //verifico che sia associato al reparto
            $reparto_detail = $this->apilib->searchFirst('rel_reparto_dipendenti', [
                'reparti_id' => $reparto, 
                'dipendenti_id' => $dipendente
            ]);
            if(empty($reparto_detail)){
                //vedo se ho scansionato quello principale
                $settings = $this->apilib->searchFirst('impostazioni_hr', [
                    'impostazioni_hr_qrcode_generico' => $reparto
                ]);
                if(empty($settings)){
                    log_message('error', "Impossibile timbrare, utente non associato al reparto");
                    die(json_encode(['status' => 0, 'txt' => 'Utente non associato al reparto']));
                    exit;
                }

            }
        }
        
        //verifico che l'utente non sia già presente, in quel caso deve fare l'uscita
        $presenze_odierna = $this->apilib->searchFirst('presenze', [
            'presenze_dipendente' => $dipendente, 
            'presenze_data_inizio' => date('Y-m-d'), 
            'presenze_data_fine IS NULL or presenze_data_fine = ""'
        ]);
        if(empty($presenze_odierna)){
            //verifico che l'utente non sia già presente, in quel caso deve fare l'uscita. Tolgo la data perchè potrebbero essere presenze passate (nel caso della notte)
            $presenze_odierna = $this->apilib->searchFirst('presenze', [
                'presenze_dipendente' => $dipendente, 
                'presenze_data_fine IS NULL or presenze_data_fine = ""'
            ]);
        }
        if (!empty($presenze_odierna)) {

            $uscita = $this->timbrature->timbraUscita($dipendente,null,null,$presenze_odierna['presenze_id'],"BADGE");
            $this->db->query("UPDATE dipendenti SET dipendenti_badge_app = null WHERE dipendenti_id= '$dipendente'");
            die(json_encode(['status' => 1, 'txt' => 'Arrivederci ', 'tipo' => 'uscita', 'data' => $uscita]));
        }
        else {
            $entrata = $this->timbrature->timbraEntrata($dipendente,null,null,"BADGE",$reparto);
            $this->db->query("UPDATE dipendenti SET dipendenti_badge_app = null WHERE dipendenti_id= '$dipendente'");
            die(json_encode(['status' => 1, 'txt' => 'Benvenuto ', 'tipo' => 'entrata', 'data' => $entrata]));
        }
    }
    public function checkValidita($qrcode){
        $ultimo_qrcode = array();
        $ultimo_qrcode = $this->apilib->searchFirst('qrcode', [
            'qrcode_valore' => $qrcode,
        ]);
        if(empty($ultimo_qrcode)){
            //vedo se ho scansionato quello principale
            $settings = $this->apilib->searchFirst('impostazioni_hr', [
                'impostazioni_hr_qrcode_generico' => $qrcode
            ]);
            if(!empty($settings)){
                // Restituisci una risposta JSON
                $ultimo_qrcode = [
                    'qrcode_active' => 1
                ];
            }

        }
        echo json_encode([
            'data' => $ultimo_qrcode,
        ]);
    }
    public function stampaBadge($dipendente){
        $dettagli_dipendente = $this->apilib->searchFirst('dipendenti', [
            'dipendenti_id' => $dipendente
        ]);
        $this->load->view("modulo-hr/pdf/badge", ['badge' => $dettagli_dipendente['dipendenti_badge'],'nome_dipendente' => $dettagli_dipendente['dipendenti_nome']." ".$dettagli_dipendente['dipendenti_cognome']]);
    }

    public function impostaPausa(){
        $dipendente_id = $this->input->post('dipendente_id');
        $presenza_id = $this->input->post('presenza_id');
        $pausa = $this->input->post('pausa');

        if(empty($dipendente_id)) {
            die(json_encode(['status' => 0, 'txt' => "Dipendente non riconosciuto, contattare l'assistenza"]));
        }
        if(empty($presenza_id)) {
            die(json_encode(['status' => 0, 'txt' => "Presenza non riconosciuta, contattare l'assistenza"]));
        }
        if(empty($pausa)) {
            die(json_encode(['status' => 0, 'txt' => "Pausa non riconosciuta, contattare l'assistenza"]));
        }

        $presenza = $this->apilib->view('presenze', $presenza_id);
        if(empty($presenza)) {
            die(json_encode(['status' => 0, 'txt' => "Presenza non riconosciuta, contattare l'assistenza"]));
        }

        try {
            $uscita = $this->apilib->edit('presenze', $presenza_id, [
                'presenze_pausa' => $pausa
            ]);
            die(json_encode(['status' => 1, 'txt' => 'Arrivederci ', 'tipo' => 'uscita', 'data' => $uscita]));
        } catch (Exception $e) {
            log_message('error', "Uscita con badge e selezione pausa su presenza n° {$presenza_id} fallita.  Error: " . $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => "Presenza non riconosciuta, contattare l'assistenza"]));
        }
    }
    public function impostaSettings(){
        $visualizzazione = $this->input->post('visualizzazione');
        if(empty($visualizzazione)) {
            die(json_encode(['status' => 0, 'txt' => "Errore generico, contattare l'assistenza"]));
        }
       
        $this->apilib->edit('impostazioni_hr', 1, [
            'impostazioni_hr_visualizzazione_pwa' => $visualizzazione
        ]);
        $this->mycache->clearCache();

        die(json_encode(['status' => 1, 'txt' => 'Visualizzazione modifica con successo']));
    }
    public function genera_elenco_barcode()
    {
        for ($i = 1; $i <= $this->input->post('quantita'); $i++) {
            //controllo non esista già
            $unique=FALSE;
            while(!$unique)
            {
                $nuovo_qrcode =  $this->generateRandomString(5);
                $esiste = $this->apilib->searchFirst('qrcode_elenco', [
                    'qrcode_elenco_qrcode' => $nuovo_qrcode,
                ]);
                if(!$esiste){
                    $unique=TRUE;
                }
            }
            try {
                $this->apilib->create('qrcode_elenco', [
                    'qrcode_elenco_qrcode' => $nuovo_qrcode
                ]);
            } catch (Exception $e) {
                log_message('error', "Impossibile creare il nuovo qrcode: Error: {$e->getMessage()}");
                throw new Exception('Impossibile creare il nuovo qrcode');
                exit;
            }
        }
        echo json_encode(array('status' => 4, 'txt' => 'Qrcode generati correttamente.'));

    }
    public function generateimage($badge = null, $show_info = false, $show_png = false, $dipendente = null){
        if(!empty($badge) && $badge != 'null') {
            $badge = $this->apilib->searchFirst('qrcode_elenco', [
                'qrcode_elenco_id' => $badge
            ]);
            $badge = $badge['qrcode_elenco_qrcode'];
        } elseif(!empty($dipendente)){

            $dipendente_detail = $this->apilib->searchFirst('dipendenti', [
                'dipendenti_id' => $dipendente
            ]);

            if(!empty($dipendente_detail) && !empty($dipendente_detail['dipendenti_badge'])){
                $badge = $dipendente_detail['dipendenti_badge'];
                $nome_dipendente = $dipendente_detail['dipendenti_nome'];

            } else {
                die(json_encode(['status' => 0, 'txt' => 'Errore, dipendente o badge non riconosciuti.']));
            }

        } else {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente o badge non riconosciuto']));
        }


        $view_content = $this->load->view("modulo-hr/pdf/badge_accessi", [
            'badge' => $badge,
            'info' => $show_info,
            'nome_dipendente' => isset($nome_dipendente) ? $nome_dipendente : ''
        ], true);
               
        $image = $this->layout->generate_image($view_content, 'portrait', '', [], false, true, ['quality' => 10]);
        //messo qualità 10 per evitare che pesi troppo l'immagine.
        if ($image) {
            if ($show_png == 'true') {
                ?>
<img src="<?php echo base_url('uploads/image/' . $image); ?>" alt="Immagine">
<?php
            } else {
                $imageData = file_get_contents(base_url('uploads/image/' . $image));
                if ($imageData !== false) {
                    $base64Image = base64_encode($imageData);
                    if ($base64Image !== false) {
                        echo $base64Image;
                        return $base64Image;
                    } else {
                        // Errore nella codifica del base64 dell'immagine
                        log_message('error', "Errore nella codifica del base64 del qrcode: Error: {$e->getMessage()}");
                        throw new Exception('Errore nella codifica del base64 base64 del qrcode');
                        exit;
                    }
                } else {
                    log_message('error', "Impossibile stampare il base64 del qrcode: Error: {$e->getMessage()}");
                    throw new Exception('Impossibile stampare il base64 del qrcode');
                    exit;
                }
            }
        }
        
    }
    public function LastQrcode($reparto){
        $response = [];
        if($reparto != 0){
            $ultimo_qrcode = $this->apilib->searchFirst('qrcode', [
                'qrcode_active' => DB_BOOL_TRUE,
                'qrcode_reparto' => $reparto,
            ]);
            $qrcode = $ultimo_qrcode['qrcode_valore'];
            $response['nome'] = $ultimo_qrcode['reparti_nome'];
            $response['valore'] = $ultimo_qrcode['qrcode_valore'];

        }
        else {
            $ultimo_qrcode = $this->apilib->searchFirst('impostazioni_hr');
            $response['valore'] = $ultimo_qrcode['impostazioni_hr_qrcode_generico'];
        }
        echo json_encode([
            'success' => 'true',
            'data' => $response,
        ]);
    }
    public function GeneralSettings(){
        $response['view'] = 1;
        $settings = $this->apilib->searchFirst('impostazioni_hr');

        if(!empty($settings)){
            if(!empty($settings['impostazioni_hr_visualizzazione_pwa'])) {
                $response['view'] = $settings['impostazioni_hr_visualizzazione_pwa'];
            } 
            if(!empty($settings['impostazioni_hr_codice_pwa'])) {
                $response['code'] = $settings['impostazioni_hr_codice_pwa'];
            } 
        }

        echo json_encode([
            'success' => 'true',
            'data' => $response,
        ]);
    }
    public function pwa(){
        $this->load->view("modulo-hr/pwa/index");

    }
    public function timbratore(){
        $this->load->view("modulo-hr/pwa/pwa");

    }
    public function LoadLogo(){
        $settings = $this->apilib->searchFirst('settings');
        echo json_encode([
            'success' => 'true',
            'data' => $settings,
        ]);

    } 
    public function VersionHr(){
        $version = $this->db->where('modules_identifier','modulo-hr')->order_by('modules_name')->get('modules')->row_array();
        //$version = $this->apilib->searchFirst('modules',['modules_identifier' => 'modulo-hr']);
        echo json_encode([
            'success' => 'true',
            'data' => $version['modules_version_code'],

        ]);
    }
    
}