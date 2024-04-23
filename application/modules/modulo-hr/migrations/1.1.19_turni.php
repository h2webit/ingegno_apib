<?php
$this->mycache->clearCache();
$orari = $this->db->query("SELECT * FROM orari_di_lavoro")->result_array();
foreach($orari as $orario){
    try {
        $this->apilib->create('turni_di_lavoro', [
            'turni_di_lavoro_dipendente' => $orario['orari_di_lavoro_dipendente'],
            'turni_di_lavoro_giorno' => $orario['orari_di_lavoro_giorno'],
            'turni_di_lavoro_ora_inizio' => $orario['orari_di_lavoro_ora_inizio'],
            'turni_di_lavoro_ora_fine' => $orario['orari_di_lavoro_ora_fine'],
            'turni_di_lavoro_pausa' => $orario['orari_di_lavoro_ore_pausa'],
            'turni_di_lavoro_data_inizio' => date('Y-m-d')
        ]);
    } catch (Exception $e) {
        log_message('error', "Errore durante migration 1.1.19 modulo HR. ".$e->getMessage());
        // se non va in automatico, fare un cron con questo codice e lanciarlo, poi cancellare.
        throw new Exception('Impossibile creare i turni per il dipendente. Riga attuale di orario_lavori: '.$orario['orari_di_lavoro_id']);
        exit;
    }
}
/*$dipendenti = $this->db->query("SELECT * FROM dipendenti")->result_array();
foreach($dipendenti as $dipendente){
    try {
        $this->apilib->edit('dipendenti', $dipendente['dipendenti_id'], [
            'dipendenti_lavoro_a_turni' => 0
        ]);
    } catch (Exception $e) {
        throw new Exception('Impossibile modificare il dipendente: '.$dipendente['dipendenti_id']);
        exit;
    }
}*/