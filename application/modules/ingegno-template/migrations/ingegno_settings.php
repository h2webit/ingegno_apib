<?php

/**
 * Logic Scheme:
 * 1. Verifica del file JSON:
 *
 * 2. Recupero delle impostazioni dal database:
 *    - Ottiene tutte le impostazioni esistenti
 *    - Estrae le chiavi delle impostazioni dal database
 *
 * 3. Confronto tra file e database:
 *    - Identifica le nuove impostazioni (presenti nel file ma non nel db)
 *    - Identifica le impostazioni obsolete (presenti nel db ma non nel file)
 *
 * 4. Aggiornamento del database:
 *    - Se non ci sono differenze, termina l'esecuzione
 *    - Per ogni impostazione nel file:
 *      a. Se è nuova, la inserisce nel database
 *      b. Se esiste già, controlla se il modulo è cambiato e aggiorna se necessario
 *    - Rimuove le impostazioni obsolete dal database
 // */

echo_log('info', '[INGEGNO SETTINGS] Inizio aggiornamento tabella ingegno_settings<br/>');

// Definisce il percorso del file JSON
$settings_json_path = APPPATH . 'modules/ingegno-template/assets/settings.json';

// Verifica se il file esiste
if (!file_exists($settings_json_path)) {
    echo_log('info', '[INGEGNO SETTINGS] File non presente<br/>');
    return;
}

// Legge il contenuto del file
$json_content = file_get_contents($settings_json_path);
if ($json_content === FALSE) {
    echo_log('info', '[INGEGNO SETTINGS] Impossibile leggere il file<br/>');
    return;
}

// Decodifica il contenuto JSON
$file_settings = json_decode($json_content, true);

// Verifica se il file è vuoto o non contiene dati validi
if (empty($file_settings)) {
    echo_log('info', '[INGEGNO SETTINGS] File vuoto<br/>');
    return;
}

// Ottiene le impostazioni esistenti dal database
$db_settings = $this->db->where("(ingegno_settings_module IS NOT NULL AND ingegno_settings_module <> '')", null, false)->get('ingegno_settings')->result_array();
$db_setting_keys = array_column($db_settings, 'ingegno_settings_key');

// Estrae le chiavi delle impostazioni dal file JSON
$file_setting_keys = array_column($file_settings, 'ingegno_settings_key');

// Determina le impostazioni mancanti nel database e nel file
$new_settings = array_diff($file_setting_keys, $db_setting_keys);
$obsolete_settings = array_diff($db_setting_keys, $file_setting_keys);

// Se non ci sono modifiche da apportare, termina l'esecuzione
if (empty($new_settings) && empty($obsolete_settings)) {
    echo_log('info', '[INGEGNO SETTINGS] Nessuna impostazione mancante<br/>');
    return;
}

// Flag per tenere traccia delle modifiche effettuate
$changes_made = false;

// Elabora ogni impostazione dal file JSON
foreach ($file_settings as $file_setting) {
    $setting_key = $file_setting['ingegno_settings_key'];
    if (in_array($setting_key, $new_settings)) {
        // Inserisce le nuove impostazioni nel database
        $this->db->insert('ingegno_settings', [
            'ingegno_settings_key' => $setting_key,
            'ingegno_settings_value' => $file_setting['ingegno_settings_default_value'],
            'ingegno_settings_default_value' => $file_setting['ingegno_settings_default_value'],
            'ingegno_settings_module' => $file_setting['ingegno_settings_module'],
        ]);
        
        echo_log('info', '[INGEGNO SETTINGS] Impostazione ' . $setting_key . ' inserita<br/>');
        $changes_made = true;
    } else {
        // Aggiorna il modulo se è cambiato
        $db_index = array_search($setting_key, $db_setting_keys);
        if ( $db_index !== false && ($db_settings[$db_index]['ingegno_settings_module'] != $file_setting['ingegno_settings_module']) ) {
            $this->db->where('ingegno_settings_key', $setting_key)->update('ingegno_settings', ['ingegno_settings_module' => $file_setting['ingegno_settings_module']]);
            echo_log('info', '[INGEGNO SETTINGS] Impostazione ' . $setting_key . ' aggiornata<br/>');
            $changes_made = true;
        }
    }
}

// Rimuove le impostazioni obsolete
foreach ($obsolete_settings as $obsolete_key) {
    $this->db->where('ingegno_settings_key', $obsolete_key)->delete('ingegno_settings');
    echo_log('info', '[INGEGNO SETTINGS] Impostazione ' . $obsolete_key . ' eliminata<br/>');
    $changes_made = true;
}

// Se non sono state apportate modifiche, registra un messaggio
if (!$changes_made) {
    echo_log('info', '[INGEGNO SETTINGS] Nessuna modifica necessaria<br/>');
}
