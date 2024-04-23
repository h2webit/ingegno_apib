<?php

class Documenti extends MY_Controller
{
    public function __construct()
    {

        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With
    }

    /**
     * 
     * ! Imposta il documento come letto, solo la prima volta
     * 
     */
    public function readDocument()
    {
        $post = $this->input->post();
        $doc_id = $post['doc_id'];
        $user_id = $post['user_id'];

        if (empty($doc_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Documento non riconosciuto']));
        }
        if (empty($user_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente non riconosciuto']));
        }

        //Se ho già comunicato la lettura non faccio nulla
        $alreadyRead = $this->apilib->search('documenti_letture', [
            'documenti_letture_documento_id' => $doc_id,
            'documenti_letture_user_id' => $user_id,
        ]);

        if(empty($alreadyRead)) {
            try {
                $this->apilib->create('documenti_letture', [
                    'documenti_letture_documento_id' => $doc_id,
                    'documenti_letture_user_id' => $user_id,
                ]);
            } catch (Exception $e) {
                log_message('error', "Errore durante lettura del documento {$doc_id} del dip. {$dipendente_id}: ".$e->getMessage());
                die(json_encode(['status' => 0, 'txt' => 'Errore durante la conferma lettura del documento']));
            }
        }
    }

}