<?php
class Planner extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function aggiornaAppuntamento($id, $day, $riga = null)
    {
        $this->apilib->edit('appuntamenti', $id, [
            //'appuntamenti_riga' => $riga,
            'appuntamenti_giorno' => $day
        ]);
    }
    
    public function aggiungiMezzoAppuntamento($id, $mezzo_id)
    {
        $rel = $this->db->where('appuntamenti_id', $id)->where('automezzi_id', $mezzo_id)->get('rel_appuntamenti_automezzi')->row();
        
        if (empty($rel)) {
            $this->apilib->create('rel_appuntamenti_automezzi', [
                'appuntamenti_id' => $id,
                'automezzi_id' => $mezzo_id,
            ]);
        }
        
        $this->apilib->clearCache();
    }
    
    public function aggiungiPersonaAppuntamento($id, $persona_id)
    {
        $rel = $this->db->where('appuntamenti_id', $id)->where('users_id', $persona_id)->get('rel_appuntamenti_persone')->row();
        
        if (empty($rel)) {
            $this->apilib->create('rel_appuntamenti_persone', [
                'appuntamenti_id' => $id,
                'users_id' => $persona_id,
            ]);
        }
        $this->apilib->clearCache();
    }
    
    public function rimuoviMezzoAppuntamento($id, $mezzo_id)
    {
        $rel = $this->db->where('appuntamenti_id', $id)->where('automezzi_id', $mezzo_id)->get('rel_appuntamenti_automezzi')->row();
        
        if (!empty($rel)) {
            $this->apilib->delete('rel_appuntamenti_automezzi', $rel->rel_appuntamenti_automezzi_id);
        }
        
        $this->apilib->clearCache();
    }
    
    public function rimuoviPersonaAppuntamento($id, $persona_id)
    {
        //debug($this->db->where('appuntamenti_id', $id)->where('users_id', $persona_id)->get('appuntamenti_persone')->result_array());
        // $this->db->where('appuntamenti_id', $id)->where('users_id', $persona_id)->delete('rel_appuntamenti_persone');
        
        $rel = $this->db->where('appuntamenti_id', $id)->where('users_id', $persona_id)->get('rel_appuntamenti_persone')->row();
        
        if (!empty($rel)) {
            $this->apilib->delete('rel_appuntamenti_persone', $rel->rel_appuntamenti_persone_id);
        }
        
        $this->apilib->clearCache();
    }
    
    public function salvaRigaCalendarioLavoriSquadre()
    {
        $post = $this->input->post();
        
        //Ciclare i giorni ma escludere quelli passati (va mantenuto lo storico!) e quindi fare solo update sui futuri
        // debug($post, true);
        
        /*
        ciclo i giorni as giorno
            se giorno >= oggi
                appuntamenti where riga and data = giorno
                    se vuoto
                        creo appuntamneto con cliente null e utenti nell'array users
                    else
                        aggiorno tutti con users (che sia vuoto o meno)
        */
        if (!empty($post['riga'])) {
            $giorni = $post['giorni'];
            $persone = [];
            if (!empty($post['users'])) {
                $persone = $post['users'];
            }
            
            $automezzi = [];
            if (!empty($post['automezzi'])) {
                $automezzi = $post['automezzi'];
            }
            // ciclo i giorni
            foreach ($giorni as $giorno) {
                // se giorno è maggiore o uguale ad oggi..
                if ($giorno >= date('Y-m-d')) {
                    // ..verifico se esistono in db appuntamenti where data = oggi, riga = quella che mi arriva
                    $appuntamenti = $this->apilib->search('appuntamenti', [
                        'appuntamenti_riga' => $post['riga'],
                        "DATE(appuntamenti_giorno) = DATE('{$giorno}')"
                    ]);
                    // dump($appuntamenti);
                    if (empty($appuntamenti)) { // se non ce ne sono...
                        try {
                            // ..li creo
                            $this->apilib->create('appuntamenti', [
                                'appuntamenti_riga' => $post['riga'],
                                'appuntamenti_giorno' => $giorno,
                                'appuntamenti_persone' => $persone,
                                'appuntamenti_automezzi' => $automezzi
                            ]);
                        } catch (Exception $e) {
                            die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                        }
                    } else { // altrimenti..
                        // li ciclo..
                        foreach ($appuntamenti as $appuntamento) {
                            try {
                                // ..e li modifico.
                                $this->apilib->edit('appuntamenti', $appuntamento['appuntamenti_id'], [
                                    'appuntamenti_persone' => $persone,
                                    'appuntamenti_automezzi' => $automezzi
                                ]);
                            } catch (Exception $e) {
                                die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                            }
                        }
                    }
                }
            }
        }
        
        die(json_encode(['status' => 1, 'txt' => 'Salvataggio effettuato']));
        // dd($post);
    }
    
    public function duplicaAppuntamento($appuntamento_id)
    {
        $appuntamento = $this->db->get_where('appuntamenti', ['appuntamenti_id' => $appuntamento_id])->row_array();
        $tecnici = $this->db->get_where('rel_appuntamenti_persone', ['appuntamenti_id' => $appuntamento_id])->result_array();
        $mezzi = $this->db->get_where('rel_appuntamenti_automezzi', ['appuntamenti_id' => $appuntamento_id])->result_array();
        
        $appuntamento_new = $appuntamento;
        unset($appuntamento_new['appuntamenti_id']);
        unset($appuntamento_new['appuntamenti_creation_date']);
        unset($appuntamento_new['appuntamenti_modified_date']);
        unset($appuntamento_new['appuntamenti_note']);
        
        $giorno = DateTime::createFromFormat('Y-m-d', substr($appuntamento['appuntamenti_giorno'], 0, 10));
        
        if ($giorno->format('w') == 6) { //Se è sabato
            $giorno_new = $giorno->modify('+2 days')->format('Y-m-d');
        } else {
            $giorno_new = $giorno->modify('+1 day')->format('Y-m-d');
        }
        
        $appuntamento_new['appuntamenti_giorno'] = $giorno_new;
        $users = [];
        foreach ($tecnici as $t) {
            $users[] = $t['users_id'];
        }
        $automezzi = [];
        foreach ($mezzi as $m) {
            $automezzi[] = $m['automezzi_id'];
        }
        $appuntamento_new['appuntamenti_persone'] = $users;
        $appuntamento_new['appuntamenti_automezzi'] = $automezzi;
        
        $this->apilib->create('appuntamenti', $appuntamento_new);
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    public function interrompiPianificazione($pianifcazione)
    {
        
        //Modifico data fine pianificazione con oggi
        $this->db->where('pianificazione_lavori_id', $pianifcazione)->update('pianificazione_lavori', [
            'pianificazione_lavori_modified_date' => date('Y-m-d H:i:s'),
            'pianificazione_lavori_data_fine' => date('Y-m-d'),
        ]);
        //$this->mycache->clearEntityCache('presenze');
        
        //Gli appuntamenti futuri sono da cancellare
        $this->db->query("DELETE FROM appuntamenti WHERE appuntamenti_pianificazione IS NOT NULL AND appuntamenti_pianificazione <> '' AND appuntamenti_pianificazione = '{$pianifcazione}' AND DATE(appuntamenti_giorno) > DATE(NOW())");
        
        //Gli appuntamenti passati sono da impostare senza pianificazione
        $appuntamenti_passati = $this->db->query("SELECT * FROM appuntamenti WHERE appuntamenti_pianificazione IS NOT NULL AND appuntamenti_pianificazione <> '' AND appuntamenti_pianificazione = '{$pianifcazione}' AND DATE(appuntamenti_giorno) <= DATE(NOW())")->result_array();
        
        if (!empty($appuntamenti_passati)) {
            foreach ($appuntamenti_passati as $appuntamento) {
                try {
                    /* $this->apilib->edit('appuntamenti', $appuntamento['appuntamenti_id'], [
                        'appuntamenti_pianificazione' => NULL,
                    ]); */
                    $this->db->where('appuntamenti_id', $appuntamento['appuntamenti_id'])->update('appuntamenti', [
                        'appuntamenti_pianificazione' => NULL,
                    ]);
                } catch (Exception $e) {
                    log_message('Errore durante la cancellazione degli appuntamenti dopo cancellata pianificazione: ', $e->getMessage());
                }
            }
        }
        
        $this->apilib->clearCache();
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * ! Sposta appuntamento di una giornata in avanti o indietro
     */
    public function spostaGiornoAppuntamento($appuntamento_id, $giorno)
    {
        if (!empty($appuntamento_id) && !empty($giorno)) {
            $this->apilib->edit('appuntamenti', $appuntamento_id, [
                'appuntamenti_giorno' => $giorno
            ]);
            
            redirect($_SERVER['HTTP_REFERER']);
        }
        
    }
}
