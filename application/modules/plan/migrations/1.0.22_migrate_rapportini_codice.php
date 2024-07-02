<?php

/**
 * Migrazione del campo "rapportini_codice" da incrementale base a incrementale annuale
 */

echo_log('debug', "INIZIO MIGRATION rapportini_codice");

try {
    echo_log('debug', "Aggiorno le sedi prendendo il country_id dall'anagrafica principale");

    $this->apilib->clearCache();
    $rapportini = $this->apilib->search('rapportini', ["(rapportini_codice IS NOT NULL AND rapportini_codice <> '' AND rapportini_codice NOT LIKE '%-%')"]);

    $t_rapportini = count($rapportini);
    $c_rapportini = 0;
    if (!empty($rapportini)) {
        foreach ($rapportini as $rapportino) {
            $this->db
                ->where('rapportini_id', $rapportino['rapportini_id'])
                ->update('rapportini', ['rapportini_codice' => $rapportino['rapportini_codice'] . '-2023']);

            echo_log('debug', "aggiornato codice rapportino {$rapportino['rapportini_codice']}");
            
            progress(++$c_rapportini, $t_rapportini);
        }
        $this->apilib->clearCache();
    }
} catch (Exception $e) {
    echo_log('error', "ERRORE MIGRATION rapportini_codice: ". $e->getMessage());
}

echo_log('debug', "FINE MIGRATION rapportini_codice");
