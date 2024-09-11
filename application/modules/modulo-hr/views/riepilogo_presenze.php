<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jsuites/jsuites.css'); ?>
<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jexcel/jexcel.css'); ?>

<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jsuites/jsuites.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jexcel/index.js'); ?>
<!-- Per salvataggio tabella !-->
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/FileSaver.min.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/xlsx.full.min.js'); ?>

<?php


$this->load->model('modulo-hr/timbrature');
$impostazioni = $this->apilib->searchFirst('impostazioni_hr');
$tipologie_richieste = $this->apilib->search('richieste_tipologia');

// Stampa numeri interi senza decimali altrimenti arrondati a 2
if (!function_exists('formatHoursXls')) {
    function formatHoursXls($number)
    {
        // Controlla se il numero ha decimali
        if (floor($number) == $number) {
            // Se non ha decimali, formattalo con 0 decimali
            return number_format($number, 0);
        } else {
            // Se ha decimali, formattalo con 2 decimali
            return number_format($number, 2);
        }
    }
}

function calculateOreGiornaliere($presenza_giornaliera, $current_date, $giorno)
{
    $CI = &get_instance();
    /*$ore_giornaliere = 0;
    $inizio_calendar = new DateTime($presenza_giornaliera['presenze_data_inizio_calendar']);
    $fine_calendar = new DateTime($presenza_giornaliera['presenze_data_fine_calendar']);

    // Estrai solo la data (ignorando l'orario)
    $inizio_data = $inizio_calendar->format('Y-m-d');
    $fine_data = $fine_calendar->format('Y-m-d');

    // Verifica se la data di fine è il giorno successivo rispetto a quella di inizio
    $un_giorno = new DateInterval('P1D'); // Un intervallo di 1 giorno
    $fine_data_successiva = $inizio_calendar->add($un_giorno)->format('Y-m-d');


    $anomalia = false;
    $straordinari = false;
    $pausa = $presenza_giornaliera['presenze_pausa_value'] ?? 0;
    //calcolare la pausa corretta

    //ricade in due giorni diversi, quindi, se sono nella data di entrata, vado a calcolare fino alle 24.00
    if (($fine_data === $fine_data_successiva) and !empty($presenza_giornaliera['presenze_ora_fine'])) {
        //ricade in due giorni diversi, quindi, se sono nella data di entrata, vado a calcolare fino alle 24.00
        if (date('Y-m-d', strtotime($presenza_giornaliera['presenze_data_inizio'])) == $current_date) {
            $ora_inizio = new DateTime($presenza_giornaliera['presenze_ora_inizio']);
            $ora_fine = new DateTime('24:00');
            $differenza = $ora_inizio->diff($ora_fine);

        } elseif (date('Y-m-d', strtotime($presenza_giornaliera['presenze_data_fine'])) == $current_date) {
            $ora_fine = new DateTime($presenza_giornaliera['presenze_ora_fine']);
            $ora_inizio = new DateTime('00:00');
            $differenza = $ora_inizio->diff($ora_fine);
        }

        $ore_giornaliere += round(($differenza->i / 60) + $differenza->h, 2);
    } else {
        $ore_giornaliere += number_format($presenza_giornaliera['presenze_ore_totali'] + $presenza_giornaliera['presenze_straordinario'] - $pausa, 2);
    }

    $pausa = $CI->timbrature->calcolaOrePausaPranzo($inizio_calendar->format('Ymd'), $presenza_giornaliera['presenze_dipendente']);
    return $ore_giornaliere - $pausa;*/
    
    return $CI->timbrature->calcolaOreTotali($presenza_giornaliera['presenze_data_inizio_calendar'], $presenza_giornaliera['presenze_dipendente']);
    
}

function checkAnomaliaOStraordinari($turni, $ore_giornaliere, $assenza, $giorno)
{ //il giorno SOLO per debug in caso di problemi
    $anomalia = false;
    $straordinari = false;
    $ferie = false;
    $permesso = false;
    $malattia = false;
    $smart_working = false;
    $trasferta = false;

    $totale_ore_turno = 0;

    foreach ($turni as $turno) {
        $pausa = 0;
        //$totale_ore += $turno
        $ora_inizio = new DateTime($turno['turni_di_lavoro_ora_inizio']);
        $ora_fine = new DateTime($turno['turni_di_lavoro_ora_fine']);
        if (isset($turno['orari_di_lavoro_ore_pausa_id'])) {
            $pausa = $turno['orari_di_lavoro_ore_pausa_value'];
        }

        $differenza = $ora_inizio->diff($ora_fine);
        $totale_ore_turno += round((($differenza->i / 60) + $differenza->h) - $pausa, 2);
    }

    /* 07/02 tolta questa parte per colori richieste <--> presenze
    if($totale_ore_turno > $ore_giornaliere){*/
    $hours = 0;

    if (!empty($assenza)) {
        $data_ora_inizio = str_ireplace(' 00:00:00', '', $assenza['richieste_dal']) . ' ' . $assenza['richieste_ora_inizio'];
        $data_ora_fine = str_ireplace(' 00:00:00', '', $assenza['richieste_al']) . ' ' . $assenza['richieste_ora_fine'];
        $inizio = new DateTime($data_ora_inizio);
        $fine = new DateTime($data_ora_fine);
        $diff_date = $fine->diff($inizio);

        if ($assenza['richieste_tipologia'] == 1) {
            // per ora gestisco solo i permessi, se sono in malattia o ferie non dovrei nemmeno aver timbrato.
            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);
            $permesso = true;
        } elseif ($assenza['richieste_tipologia'] == 2) {
            $ferie = true;
        } elseif ($assenza['richieste_tipologia'] == 3) {
            $malattia = true;
        } elseif ($assenza['richieste_tipologia'] == 4) {
            $smart_working = true;
        } elseif ($assenza['richieste_tipologia'] == 5) {
            $trasferta = true;
        } else {
            $hours = '24'; //così sicuramente non ho meno ore giornaliere
        }
    }
    if (($totale_ore_turno > ($ore_giornaliere + $hours)) && ($ferie == false || $malattia == false || $smart_working == false || $permesso == false || $trasferta == false)) {
        $anomalia = true;
    }



    /* 07/02 tolta questa parte per colori richieste <--> presenze
         } elseif($totale_ore_turno < $ore_giornaliere) {
        $straordinari = true;
    } */
    if ($totale_ore_turno < $ore_giornaliere) {
        $straordinari = true;
    }

    return [
        'anomalia' => $anomalia,
        'straordinari' => $straordinari,
        'permesso' => $permesso,
        'ferie' => $ferie,
        'malattia' => $malattia,
        'smart_working' => $smart_working,
        'trasferta' => $trasferta
    ];
}


$giorni_map = ['D', 'L', 'M', 'M', 'G', 'V', 'S'];

$giorni_mese = date('t');
$dati = [];

// Dati filtri impostati
$filters = $this->session->userdata(SESS_WHERE_DATA);
$where_dipendenti = 'dipendenti_attivo = ' . DB_BOOL_TRUE;


$join_dipendenti = DB_BOOL_FALSE;

if (!empty($filters['filter-presenze'])) {
    $filtri = $filters['filter-presenze'];

    $presenze_dipendente_field_id = $this->datab->get_field_by_name('presenze_dipendente')['fields_id'];
    $presenze_data_field_id = $this->datab->get_field_by_name('presenze_data_inizio')['fields_id'];
    $dipendenti_azienda_field_id = $this->datab->get_field_by_name('dipendenti_azienda')['fields_id'];
    $dipendenti_reparto_field_id = $this->datab->get_field_by_name('presenze_reparto')['fields_id'];
    $dipendenti_cliente_field_id = $this->datab->get_field_by_name('presenze_cliente')['fields_id'];

    if (!empty($filtri[$presenze_dipendente_field_id]['value']) && $filtri[$presenze_dipendente_field_id]['value'] !== '-1') {
        $filtro_dipendente_id = $filtri[$presenze_dipendente_field_id]['value'];

        $dipendente = $this->db->where('dipendenti_id', $filtri[$presenze_dipendente_field_id]['value'])->get('dipendenti')->row_array();

        $this->db->where('presenze_dipendente', $filtri[$presenze_dipendente_field_id]['value']);
    }

    if (!empty($filtri[$presenze_data_field_id]['value'])) {
        $presenze_data_ex = explode(' - ', $filtri[$presenze_data_field_id]['value']);
        $presenze_data = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('Y-m');

        $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = '{$presenze_data}'", null, false);
        $anno = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('Y');
        $mese = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('m');
        $giorni_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
    } else {
        $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
        $anno = date('Y');
        $mese = date('m');
    }

    // Filtro azienda
    if (!empty($filtri[$dipendenti_azienda_field_id]['value']) && $filtri[$dipendenti_azienda_field_id]['value'] !== '-1') {
        $this->db->join('dipendenti', 'presenze_dipendente = dipendenti_id', "left");
        $this->db->where('dipendenti_azienda', $filtri[$dipendenti_azienda_field_id]['value']);
        $where_dipendenti .= ' AND dipendenti_azienda = ' . $filtri[$dipendenti_azienda_field_id]['value'];

        $join_dipendenti = DB_BOOL_TRUE;
    }

    // Filtro reparto
    if (!empty($filtri[$dipendenti_reparto_field_id]['value']) && $filtri[$dipendenti_reparto_field_id]['value'] !== '-1') {
        $this->db->where('presenze_reparto', $filtri[$dipendenti_reparto_field_id]['value']);
        $where_dipendenti .= " AND rel_reparto_dipendenti.reparti_id = {$filtri[$dipendenti_reparto_field_id]['value']}";
    }

    // Filtro cliente
    if (!empty($filtri[$dipendenti_cliente_field_id]['value']) && $filtri[$dipendenti_cliente_field_id]['value'] !== '-1') {
        $this->db->where('presenze_cliente', $filtri[$dipendenti_cliente_field_id]['value']);
        $where_dipendenti .= " AND dipendenti.dipendenti_id IN (SELECT presenze_dipendente FROM presenze WHERE presenze_dipendente IS NOT NULL AND presenze_cliente = {$filtri[$dipendenti_cliente_field_id]['value']})";
    }

} else {
    $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
    $anno = date('Y');
    $mese = date('m');
}


//Se ho dei parametri prendo solo questi dipendenti e solo se non ne ho uno scelto nei filtri
$firstLetter = $this->input->get('firstLetter') ?? 'a';

// Se ho meno di 50 dipendenti e niente in get, li mostro tutti
$count_dipendenti = $this->db->query('SELECT COUNT(*) AS c FROM dipendenti')->row()->c;
if ($count_dipendenti < 50 && empty($this->input->get('lastLetter'))) {
    $lastLetter = 'z';
} else {
    $lastLetter = $this->input->get('lastLetter') ?? 'e';
}
if (!empty($firstLetter) && !empty($lastLetter) && (!isset($dipendente))) {
    if ($join_dipendenti == DB_BOOL_FALSE) {
        $this->db->join('dipendenti', 'presenze_dipendente = dipendenti_id', "left");
    }
    $this->db->where("LOWER(SUBSTRING(dipendenti_cognome, 1, 1)) BETWEEN '{$firstLetter}' AND '{$lastLetter}'", null, false);
}

$this->db->join("presenze_pausa", "presenze_pausa = presenze_pausa_id", "left");
$this->db->join('banca_ore', 'banca_ore_creato_da_presenza = presenze_id AND banca_ore_movimento = "1"', 'left');
$this->db->join('richieste', 'richieste_id = presenze_richiesta', 'left');
// Se ho in get una tipologia di richiesta devo prendere solo le presenze legate a richieste di questo tipo
$tipoRichiesta = $this->input->get('tipoRichiesta') ?? null;
if (!empty($tipoRichiesta)) {
    $this->db->where('richieste_tipologia', $tipoRichiesta);
}
$_presenze = $this->db->get('presenze')->result_array();

$presenze = [];
foreach ($_presenze as $_presenza) {
    $Ymd = substr($_presenza['presenze_data_inizio'], 0, 10);
    $presenze[$_presenza['presenze_dipendente']][$Ymd][] = $_presenza;
}
if (isset($filtro_dipendente_id)) {
    $where_dipendenti .= ' AND dipendenti.dipendenti_id= ' . $filtro_dipendente_id;
}

$this->db->select('dipendenti.*');
$this->db->from('dipendenti');
if (!empty($filters['filter-presenze'])) {
    if (!empty($filtri[$dipendenti_reparto_field_id]['value']) && $filtri[$dipendenti_reparto_field_id]['value'] !== '-1') {
        $this->db->join('rel_reparto_dipendenti', 'dipendenti.dipendenti_id = rel_reparto_dipendenti.dipendenti_id', 'left');
    }
}

//Se ho dei parametri prendo solo questi dipendenti e solo se non ne ho uno scelto nei filtri
if (!empty($firstLetter) && !empty($lastLetter) && (!isset($dipendente))) {
    $this->db->where("LOWER(SUBSTRING(dipendenti_cognome, 1, 1)) BETWEEN '{$firstLetter}' AND '{$lastLetter}'", null, false);
    // Solo i dipendenti con data inizio contratto non impostata o minore dei filtri
    $this->db->where("(dipendenti_data_inizio IS NULL OR DATE_FORMAT(dipendenti_data_inizio, '%Y-%m') <= '$anno-$mese')", null, false);
    // Solo i dipendenti con data fine contratto non impostata o maggiore dei filtri
    $this->db->where("(dipendenti_data_fine IS NULL OR DATE_FORMAT(dipendenti_data_fine, '%Y-%m') >= '$anno-$mese')", null, false);
}

$this->db->where($where_dipendenti);
$this->db->order_by('dipendenti_cognome', 'ASC');
$query = $this->db->get();
$dipendenti = $query->result_array();

/*
debug($dipendenti);*/
$data = $data_xls_presenze = [];
$row = 0;
$autoincrementalKey = 0; // Inizializza la variabile per l'autoincremento
// Funzione di callback per il filtro
function filterByDay($dayToSearch)
{
    return function ($presenza) use ($dayToSearch) {
        $dataInizio = substr($presenza['presenze_data_inizio'], 8, 2); // Estrai solo il giorno
        $dataFine = substr($presenza['presenze_data_fine'], 8, 2); // Estrai solo il giorno

        //return ((intval($dataInizio) === intval($dayToSearch) && !empty($dataFine)) || (intval($dataFine) === intval($dayToSearch) && !empty($dataFine)));
        return ((intval($dataInizio) === intval($dayToSearch) && !empty ($dataFine)));
    };
}


// Crea un nuovo array con le date delle festività nel formato 'yyyy-mm-dd'
$festivita = $this->apilib->search('festivita');

$dateFestivita = [];
if (!empty($festivita)) {
    foreach ($festivita as $festivita_item) {
        $dateFestivita[] = dateFormat($festivita_item['festivita_data'], 'Y-m-d');
    }
}


// Definire l'intervallo di date
$data_inizio = "$anno-$mese-01";
$data_fine = "$anno-$mese-$giorni_mese";

// Eseguire la query per ottenere tutte le assenze nel periodo
$this->db->select('*');
$this->db->from('richieste');
$this->db->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') <= ", $data_fine);
$this->db->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') >= ", $data_inizio);
$this->db->where('richieste_stato', '2');
//Se ho il parametro per il tipo richiesta prendo solo quelle
$tipoRichiesta = $this->input->get('tipoRichiesta') ?? null;
if (!empty($tipoRichiesta)) {
    $this->db->where('richieste_tipologia', $tipoRichiesta);
}
$query_assenze = $this->db->get();
$assenze = $query_assenze->result_array();

$assenzeOrganizzate = [];
foreach ($assenze as $assenza) {
    // Supponendo che `richieste_dal` e `richieste_al` definiscano l'intervallo dell'assenza
    $inizio = new DateTime($assenza['richieste_dal']);
    $fine = new DateTime($assenza['richieste_al']);

    // Itera tutte le date nell'intervallo di assenza
    for ($_data = $inizio; $_data <= $fine; $_data->modify('+1 day')) {
        $giornoChiave = $_data->format('Y-m-d');
        $assenzeOrganizzate[$assenza['richieste_user_id']][$giornoChiave] = $assenza;
    }
}

// Esegui la query per ottenere tutti i turni nel periodo per i dipendenti
$turniQuery = $this->db
    ->select('*')
    ->from('turni_di_lavoro')
    ->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', 'LEFT')
    ->where('turni_di_lavoro_data_inizio <=', $data_fine)
    ->where('turni_di_lavoro_data_fine >=', $data_inizio)
    ->or_where('turni_di_lavoro_data_fine', null)
    ->get()
    ->result_array();

// Organizza i turni in un array per un accesso rapido
$turniOrganizzati = [];
foreach ($turniQuery as $turno) {
    $turniOrganizzati[$turno['turni_di_lavoro_dipendente']][$turno['turni_di_lavoro_giorno']][] = $turno;
}

$this->load->model('entities');
foreach ($dipendenti as $dipendente) {
    $totale_ore_dipendente = $totale_ore_previste_dipendente = 0;

    $data[$autoincrementalKey] = [
        '1' => "<a target='_blank' style='width:100%; color: #3c8dbc; text-decoration: none; display: block;' href='" . base_url("main/layout/dettaglio-dipendente/" . $dipendente['dipendenti_id']) . "'>" . $dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1) . "</a>",
    ];
    $totale_ore_dipendente_da_rapportini = 0;
    for ($giorno = 1; $giorno <= $giorni_mese; $giorno++) {
        $day = $giorno < 10 ? '0' . $giorno : $giorno;
        $current_date = $anno . '-' . $mese . '-' . $day;

        

        //Verifico se esiste l'entità rapportini
        if ($this->entities->entity_exists('rapportini')) {
            $ore_giorno = $this->db->query("SELECT
                SUM(
                    TIMESTAMPDIFF(MINUTE, 
                            STR_TO_DATE(CONCAT(rapportini_data, ' ', rapportini_ora_inizio), '%Y-%m-%d %H:%i'),
                            STR_TO_DATE(CONCAT(rapportini_data, ' ', rapportini_ora_fine), '%Y-%m-%d %H:%i')
                        ) / 60 
                    
                ) AS totale_ore_lavoro
            FROM
                rapportini
            WHERE
                    rapportini_da_validare = '0'
                    AND
                rapportini_id IN (SELECT rapportini_id FROM rel_rapportini_users WHERE users_id = '{$dipendente['dipendenti_user_id']}')  -- Sostituisci con l'ID del dipendente
                AND rapportini_data = '$current_date'
                


    ")->row()->totale_ore_lavoro;
            $totale_ore_dipendente_da_rapportini += $ore_giorno;
        }
        $giorno_della_settimana = date('N', strtotime($current_date));
        $giorno_lavorativo = !empty($turniOrganizzati[$dipendente['dipendenti_id']][$giorno_della_settimana]);
        if (!in_array($current_date, $dateFestivita)) {
            $totale_ore_previste_dipendente += $this->timbrature->calcolaOreGiornalierePreviste($current_date, $dipendente['dipendenti_id']);
        }
        

        if ($giorno_della_settimana == 7) {
            //debug($giorno_lavorativo);
        }
        // if ($current_date == '2024-03-16') {
        //     debug($turniOrganizzati[$dipendente['dipendenti_id']][$current_date],true);
        // }
        // $assenza = $this->db
        //     ->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') <= '{$current_date}'", null, false)
        //     ->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') >= '{$current_date}'", null, false)
        //     ->where('richieste_user_id', $dipendente['dipendenti_id'])
        //     ->where('richieste_stato', '2')
        //     ->get('richieste')->row_array();

        $assenza = $assenzeOrganizzate[$dipendente['dipendenti_id']][$current_date] ?? null;
            
        
        if (!empty($presenze[$dipendente['dipendenti_id']][$current_date])) {
            // $pres = $presenze[$dipendente['dipendenti_id']];

            // $filteredPresenze = array_filter($pres, filterByDay($day));
            $filteredPresenze = $presenze[$dipendente['dipendenti_id']][$current_date];
            //qua vedere eventuali anomalie
            // $this->db->select('*');
            // $this->db->from('turni_di_lavoro');
            // $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', 'LEFT');


            // $this->db->where('turni_di_lavoro_data_inizio <=', $current_date);
            // $this->db->where('turni_di_lavoro_dipendente', $dipendente['dipendenti_id']);

            // //se oggi vedo anche l'inizio
            // if ($current_date == date('Y-m-d')) {
            //     $this->db->where('turni_di_lavoro_ora_inizio <', date('H:i'));

            // }
            // $this->db->where('(turni_di_lavoro_data_fine >= ' . $this->db->escape($current_date) . ' OR turni_di_lavoro_data_fine IS NULL)');
            // $this->db->where('turni_di_lavoro_giorno', date('N', strtotime($current_date)));
            // $turni = $this->db->get()->result_array();
            $turni = $turniOrganizzati[$dipendente['dipendenti_id']][$giorno_della_settimana] ?? [];

            $pause = 0;
            if (!empty($turni)) {
                foreach ($turni as $turno) {
                    //debug($turno,true);
                    $pause += $turno['orari_di_lavoro_ore_pausa_value'];
                }
            }

            $Ymd = $anno . $mese . $day;
            $anomalia = false;
            $straordinari = false;

            //dump($filteredPresenze);

            if (!empty($filteredPresenze)) {
                $ore_giornaliere = 0;
                $anomalia_rilevata = false;
                $straordinari_rilevati = false;
                $permesso_rilevato = false;
                $ferie_rilevate = false;
                $malattia_rilevata = false;

                foreach ($filteredPresenze as $presenza_giornaliera) {
                    //$ore_giornaliere += calculateOreGiornaliere($presenza_giornaliera, $current_date, $giorno);  
                    // if ($Ymd == '20240527') {
                    //     debug($presenza_giornaliera);
                    // }
                    
                    $ore_giornaliere += str_replace(',','.', $presenza_giornaliera['presenze_ore_totali']);
                    
                    $anomalia = $presenza_giornaliera['presenze_anomalia'];

                    //vedo se il totale combacia
                    if (!empty($turni)) {
                        //$anomalie_o_straordinari = checkAnomaliaOStraordinari($turni, $ore_giornaliere, $assenza, $giorno);
                        $anomalia = $presenza_giornaliera['presenze_anomalia'];
                        $straordinari = $presenza_giornaliera['presenze_straordinario'] || $presenza_giornaliera['banca_ore_id'];
                        if (!empty($presenza_giornaliera['presenze_richiesta'])) {
                            //$assenza = $this->db->where('richieste_id', $presenza_giornaliera['presenze_richiesta'])->get('richieste')->row_array();
                            $assenza = $presenza_giornaliera;

                            if (!empty($assenza)) {
                                if ($assenza['richieste_tipologia'] == 1) {
                                    //Permesso
                                    $permesso_rilevato = true;
                                } elseif ($assenza['richieste_tipologia'] == 2) {
                                    //Ferie
                                    $ferie_rilevate = true;
                                } elseif ($assenza['richieste_tipologia'] == 3) {
                                    //Malattia
                                    $malattia_rilevata = true;
                                }
                            }
                        }
                        // Se c'è un'anomalia nella giornata, imposta la variabile di controllo
                        if ($anomalia == true) {
                            $anomalia_rilevata = true;
                        }
                        if ($straordinari == true) {
                            $straordinari_rilevati = true;
                        }
                    } else {
                        // Se è legata ad una richiesta la recupero
                        if (!empty($presenza_giornaliera['presenze_richiesta'])) {
                            //$assenza = $this->db->where('richieste_id', $presenza_giornaliera['presenze_richiesta'])->get('richieste')->row_array();
                            $assenza = $presenza_giornaliera;
                            if (!empty($assenza)) {
                                $result = checkAnomaliaOStraordinari($turni, $ore_giornaliere, $assenza, $giorno);

                                if ($assenza['richieste_tipologia'] == 1) {
                                    //Permesso
                                    $permesso_rilevato = true;
                                } elseif ($assenza['richieste_tipologia'] == 2) {
                                    //Ferie
                                    $ferie_rilevate = true;
                                } elseif ($assenza['richieste_tipologia'] == 3) {
                                    //Malattia
                                    $malattia_rilevata = true;
                                }

                                if ($result['smart_working'] == true) {
                                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Smart working - " . formatHoursXls($ore_giornaliere) . "</a>";
                                }
                                if ($result['trasferta'] == true) {
                                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Trasferta - " . formatHoursXls($ore_giornaliere) . "</a>";
                                }
                            }

                        }
                        if ($anomalia == true) {
                            $anomalia_rilevata = true;
                        }
                    }
                    //debug($presenza_giornaliera);

                }


                
                $pause = $this->timbrature->calcolaOrePausaPranzo($Ymd, $dipendente['dipendenti_id']);
                

                $ore_giornaliere -= $pause;
                

                if ($anomalia_rilevata == true && !($ferie_rilevate || $permesso_rilevato || $malattia_rilevata)) {
                    //$data[$autoincrementalKey][$giorno+1] = "Anomalia - ".formatHoursXls($ore_giornaliere);
                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Anomalia - " . formatHoursXls($ore_giornaliere) . "</a>";
                } elseif ($straordinari_rilevati == true) {
                    //$data[$autoincrementalKey][$giorno+1] = "Straordinari - ".formatHoursXls($ore_giornaliere);
                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Straordinari - " . formatHoursXls($ore_giornaliere) . "</a>";
                } elseif ($permesso_rilevato == true) {
                    //$data[$autoincrementalKey][$giorno+1] = "Straordinari - ".formatHoursXls($ore_giornaliere);
                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Permesso - " . formatHoursXls($ore_giornaliere) . "</a>";
                } elseif ($ferie_rilevate == true) {
                    if ($ore_giornaliere == 0 || !$giorno_lavorativo) {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Ferie - n/d</a>";
                    } else {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Ferie - " . formatHoursXls($ore_giornaliere) . "</a>";
                    }

                } elseif ($malattia_rilevata == true) {

                    //$data[$autoincrementalKey][$giorno+1] = "Straordinari - ".formatHoursXls($ore_giornaliere);
                    if ($ore_giornaliere == 0 || !$giorno_lavorativo) {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Malattia - n/d</a>";
                    } else {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Malattia - " . formatHoursXls($ore_giornaliere) . "</a>";
                    }

                } else {
                    //$data[$autoincrementalKey][$giorno+1] = formatHoursXls($ore_giornaliere);
                    if (empty($ore_giornaliere)) {
                        //$data[$autoincrementalKey][$giorno+1] = '';
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'></a>";
                    } else {
                        // dettaglio 
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>" . formatHoursXls($ore_giornaliere) . "</a>";

                        // Controllo se la presenza è legata a richiesta di smart working o trasferta
                        foreach ($filteredPresenze as $presenza_giornaliera) {
                            // Se è legata ad una richiesta la recupero
                            if (!empty($presenza_giornaliera['presenze_richiesta'])) {
                                //$assenza = $this->db->where('richieste_id', $presenza_giornaliera['presenze_richiesta'])->get('richieste')->row_array();
                                $assenza = $presenza_giornaliera;

                                if (!empty($assenza)) {
                                    $result = checkAnomaliaOStraordinari($turni, $ore_giornaliere, $assenza, $giorno);

                                    /* $colore = "rgb(249 115 22)";
                                    foreach ($tipologie_richieste as $tipologia) {
                                        if ($tipologia['richieste_tipologia_value'] === 'Trasferta' && $result['trasferta'] == true) {
                                            $colore = $tipologia['richieste_tipologia_colore'] ?? 'rgb(249 115 22)';
                                            break;
                                        }
                                        if ($tipologia['richieste_tipologia_value'] === 'Smart working' && $result['smart_working'] == true) {
                                            $colore = $tipologia['richieste_tipologia_colore'] ?? 'rgb(249 115 22)';
                                            break;
                                        }
                                    } */

                                    if ($result['smart_working'] == true) {
                                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Smart working - " . number_format($ore_giornaliere, 2) . "</a>";
                                    }
                                    if ($result['trasferta'] == true) {
                                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Trasferta - " . number_format($ore_giornaliere, 2) . "</a>";
                                    }
                                }
                            }
                        }
                    }
                }
                $totale_ore_dipendente += $ore_giornaliere;
            } else {

                // Converte date per non segnare anomalia nel futuro
                $data_corrente = date('m-d');
                $data_richiesta = sprintf('%02d-%02d', $mese, $giorno);
                $data_corrente_converted = date('Y-m-d', strtotime(date('Y') . "-$data_corrente"));
                $data_richiesta_converted = date('Y-m-d', strtotime("$anno-$data_richiesta"));

                if (!empty($turni) && $data_corrente_converted > $data_richiesta_converted) {

                    $anomalie_o_straordinari = checkAnomaliaOStraordinari($turni, 0, $assenza, $giorno);
                    $anomalia = $anomalie_o_straordinari['anomalia'];

                    if ($anomalia == true && !($anomalie_o_straordinari['ferie'] || $anomalie_o_straordinari['permesso'] || $anomalie_o_straordinari['permesso'] || $anomalie_o_straordinari['smart_working'])) {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Anomalia - </a>";
                    } elseif ($anomalie_o_straordinari['ferie'] == true) {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white;  text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Ferie - </a>";
                    } elseif ($anomalie_o_straordinari['permesso'] == true) {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white;  text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Permesso - </a>";
                    } elseif ($anomalie_o_straordinari['malattia'] == true) {
                        if ($ore_giornaliere == 0 || !$giorno_lavorativo) {
                            $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white;  text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Malattia - n/d</a>";
                        } else {
                            $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white;  text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Malattia - </a>";
                        }

                    } elseif ($anomalie_o_straordinari['smart_working'] == true) {

                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white;  text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Smart working - </a>";
                    } elseif ($anomalie_o_straordinari['trasferta'] == true) {
                        $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white;  text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Trasferta - </a>";
                    }/*else{
                       $data[$autoincrementalKey][$giorno+1] = "<a class='js_open_modal' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/".$dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=".$dipendente['dipendenti_id']."&presenze_data_inizio=".$current_date."'></a>";
                   }*/
                } else {


                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'></a>";


                }
                //Creazione presenza
                //$data[$autoincrementalKey][$giorno+1] = "<a class='js_open_modal' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/modal_form/form-presenze?_size=large&presenze_dipendente=".$dipendente['dipendenti_id']) . "&presenze_data_inizio=".$current_date."'></a>";
            }
        } else {
            
            if ($giorno_lavorativo && $current_date < date('Y-m-d')) {
                if(!empty($tipoRichiesta)) {
                    // Giornata con turno ma senza presenza, dovuto al filtro tipoRichiesta
                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'></a>";
                } else {
                    // Anomalia effettiva (giornata con turno ma senza presenza)
                    $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: black; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'>Anomalia - </a>";
                }
            } else {
                //$data[$autoincrementalKey][$giorno+1] = '';
                $data[$autoincrementalKey][$giorno + 1] = "<a class='js_open_modal js_cella_presenza' style='width:100%; height: 13px; color: white; text-decoration: none; display: block;' href='" . base_url("get_ajax/layout_modal/riepilogo_presenze_side/" . $dipendente['dipendenti_id']) . "?_mode=side_view&presenze_dipendente=" . $dipendente['dipendenti_id'] . "&presenze_data_inizio=" . $current_date . "'></a>";
            }
        }
    }
    $data[$autoincrementalKey][] = number_format($totale_ore_previste_dipendente, 2);
    $data[$autoincrementalKey][] = number_format($totale_ore_dipendente, 2);
    if ($this->entities->entity_exists('rapportini')) {
        $data[$autoincrementalKey][] = number_format($totale_ore_dipendente_da_rapportini, 2);
    }

    $autoincrementalKey++;
}

$data_xls_presenze = $data;


?>
<style>
.btn-filter-presenze-active {
    color: #ffffff;
    background-color: #086fa3;
}

.xls_container {
    width: 100%;
    overflow-x: scroll;
}

.jexcel tbody tr:nth-child(even) {
    background-color: rgba(0, 0, 0, .05);
}

.filtri_cartellino_ore {
    margin-top: 16px;
}

.legenda_container {
    float: right;
    display: flex;
    justify-content: flex-start;
    align-items: baseline;
    gap: 20px;
}

.legenda_item {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    gap: 4px;
}

.legenda_square {
    width: 16px;
    height: 16px;
    border-radius: 2px;
}

.legenda_square.anomalia {
    background-color: rgb(253 224 71);
}

.legenda_square.straordinari {
    background-color: rgb(8 111 163);
}


.legenda_square.ass_ing {
    background-color: rgb(124 45 18);
}

.legenda_square.infortunio {
    background-color: rgb(15 23 42);

}

.legenda_square.l104 {
    background-color: rgb(234 179 8);
}

.legenda_square.festivita {
    background-color: rgb(239 68 68);
}

.jexcel>tbody>tr>td {
    padding: 1px;
    font-size: 11px;
    width: 30px;
}

.jexcel_overflow>tbody>tr>td {

    font-size: 12px !important;
}

td.js_last_clicked {
    border: solid 3px #086fa3 !important;
}

.cursor_pointer {
    cursor: pointer;
}
</style>

<div class="container-fluid riepilogo_presenze">
    <div class="row">
        <div class="col-sm-12">
            <button type="button" id="stampa_riepilogo" class="btn bg-teal btn-sm"><i class="fas fa-print fa-fw"></i> Stampa riepilogo</button>
            
            <div class="legenda_container">
                <div class="text-uppercase legenda_item">
                    <strong>Anomalia</strong> <span class="legenda_square anomalia"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>
                        <?php if ($impostazioni['impostazioni_hr_banca_ore']): ?>
                        Straord./Banca ore
                        <?php else: ?>
                        Straordinari
                        <?php endif; ?>
                    </strong>
                    <span class="legenda_square straordinari"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>Festività</strong> <span class="legenda_square festivita"></span>
                </div>
                <?php
                foreach ($tipologie_richieste as $tipologia):
                    $colore = $tipologia['richieste_tipologia_colore'] ?? 'rgb(249 115 22)';
                    $tipologia['richieste_tipologia_value'];
                ?>
                <div class="text-uppercase legenda_item cursor_pointer" onclick="setRichiestaFilter(<?php echo $tipologia['richieste_tipologia_id']; ?>)">
                    <strong style="<?php echo (!empty($tipoRichiesta) && $tipoRichiesta == $tipologia['richieste_tipologia_id']) ? 'color: '.$colore.';' : ((!empty($tipoRichiesta) && $tipoRichiesta != $tipologia['richieste_tipologia_id']) ? 'color: #94a3b8;' : ''); ?>"><?php echo $tipologia['richieste_tipologia_value']; ?></strong>
                    <span class="legenda_square" style="background-color:<?php echo ((!empty($tipoRichiesta) && $tipoRichiesta != $tipologia['richieste_tipologia_id']) ? '#94a3b8;' : $colore); ?>"></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <br><br>

    <div class="row">
        <div class="col-sm-12">
            <div class="xls_container">
                <div id="spreadsheet_presenze_riepilogo"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="pull-right">
                <span>Filtra dipendenti per</span>
                <button class="btn btn-sm <?php echo ($firstLetter == 'a' && $lastLetter == 'e') ? 'btn-filter-presenze-active' : 'btn-info'; ?>" onclick="setPagination('a', 'e')"><strong>A - E</strong></button>
                <button class="btn btn-sm <?php echo ($firstLetter == 'f' && $lastLetter == 'j') ? 'btn-filter-presenze-active' : 'btn-info'; ?>" onclick="setPagination('f', 'j')"><strong>F - J</strong></button>
                <button class="btn btn-sm <?php echo ($firstLetter == 'k' && $lastLetter == 'o') ? 'btn-filter-presenze-active' : 'btn-info'; ?>" onclick="setPagination('k', 'o')"><strong>K - O</strong></button>
                <button class="btn btn-sm <?php echo ($firstLetter == 'p' && $lastLetter == 't') ? 'btn-filter-presenze-active' : 'btn-info'; ?>" onclick="setPagination('p', 't')"><strong>P - T</strong></button>
                <button class="btn btn-sm <?php echo ($firstLetter == 'u' && $lastLetter == 'z') ? 'btn-filter-presenze-active' : 'btn-info'; ?>" onclick="setPagination('u', 'z')"><strong>U - Z</strong></button>
                <button class="btn btn-sm <?php echo ($firstLetter == 'a' && $lastLetter == 'z') ? 'btn-filter-presenze-active' : 'btn-info'; ?>" onclick="setPagination('a', 'z')"><strong>TUTTI</strong></button>
            </div>
        </div>
    </div>

</div>

<script>
var baseURL = '<?php echo base_url("main/layout/presenze-recap"); ?>';
//Foo alert
//alert(1);
function setRichiestaFilter(tipoRichiestaId) {
    var url = new URL(window.location.href);

    // Controlla se il parametro tipoRichiesta è già presente nell'URL
    if (url.searchParams.has('tipoRichiesta')) {
        // Se il valore del parametro tipoRichiesta è uguale a tipoRichiestaId, rimuovilo dall'URL e reindirizza
        if (url.searchParams.get('tipoRichiesta') == tipoRichiestaId) {
            url.searchParams.delete('tipoRichiesta');
        } else {
            // Se il valore del parametro tipoRichiesta è diverso da tipoRichiestaId, aggiornalo nell'URL e reindirizza
            url.searchParams.set('tipoRichiesta', tipoRichiestaId);
        }
    } else {
        // Se il parametro tipoRichiesta non è presente nell'URL, aggiungilo e reindirizza
        url.searchParams.append('tipoRichiesta', tipoRichiestaId);
    }

    window.location.replace(url.toString());
}


function setPagination(firstLetter, lastLetter) {
    var url = new URL(window.location.href);
    var params = new URLSearchParams(url.search);

    // Rimuovi eventuali parametri esistenti 'firstLetter' e 'lastLetter'
    params.delete('firstLetter');
    params.delete('lastLetter');

    // Aggiungi i nuovi parametri 'firstLetter' e 'lastLetter', se presenti
    if (firstLetter) {
        params.append('firstLetter', firstLetter);
    }
    if (lastLetter) {
        params.append('lastLetter', lastLetter);
    }

    url.search = params.toString();
    window.location.replace(url.toString());
}

var data = <?php echo json_encode($data_xls_presenze); ?>;
var table3 = jspreadsheet(document.getElementById('spreadsheet_presenze_riepilogo'), {
    onload: function(el, instance) {
        //header background
        var x = 1 // column A
        $(instance.thead).find("tr td").css({
            'font-weight': 'bold',
            'font-size': '13px',
            'text-align': 'center'
        });
        $(instance.tbody).find("tr td").css({
            'font-size': '13px',
            'text-align': 'center'
        });
    },
    updateTable: function(instance, cell, col, row, val, label, cellName) {
        //console.log(`cell: ${cell}, col: ${col}, row: ${row}, val: ${val}, label: ${label}, cellName: ${cellName}`);
        //console.log(instance);

        //Coloro sfondo e testo per le ore lavorate
        if (col != '0' && col != '1') {
            cell.style.color = 'rgb(0 0 0)';
            cell.style.fontWeight = 'bold';
        }
        //Colore sfondo e testo per permesso
        if (cell.textContent.includes('Anomalia - ')) {
            cell.innerHTML = cell.innerHTML.replace('Anomalia - ', '');
            cell.style.color = 'rgb(253 224 71)';
            cell.style.background = 'rgb(253 224 71)';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('Straordinari - ')) {
            cell.innerHTML = cell.innerHTML.replace('Straordinari - ', '');
            cell.style.color = 'rgb(255 255 255)';
            cell.style.background = 'rgb(8 111 163)';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('Malattia - ')) {
            cell.innerHTML = cell.innerHTML.replace('Malattia - ', '');
            cell.style.color = 'rgb(255 255 255)';
            cell.style.background = 'rgb(249 115 22)';
            cell.style.background = '<?php echo $coloreSmartWorking = array_reduce($tipologie_richieste, function ($carry, $item) {
                    return $item['richieste_tipologia_value'] === 'Malattia' ? $item['richieste_tipologia_colore'] : $carry; }, 'rgb(249 115 22)'); ?>';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('Permesso - ')) {
            cell.innerHTML = cell.innerHTML.replace('Permesso - ', '');
            cell.style.color = 'rgb(255 255 255)';
            cell.style.background = 'rgb(249 115 22)';
            cell.style.background = '<?php echo $coloreSmartWorking = array_reduce($tipologie_richieste, function ($carry, $item) {
                    return $item['richieste_tipologia_value'] === 'Permesso' ? $item['richieste_tipologia_colore'] : $carry; }, 'rgb(249 115 22)'); ?>';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('Ferie - ')) {
            cell.innerHTML = cell.innerHTML.replace('Ferie - ', '');
            cell.style.color = 'rgb(255 255 255)';
            cell.style.background = 'rgb(249 115 22)';
            cell.style.background = '<?php echo $coloreSmartWorking = array_reduce($tipologie_richieste, function ($carry, $item) {
                    return $item['richieste_tipologia_value'] === 'Ferie' ? $item['richieste_tipologia_colore'] : $carry; }, 'rgb(249 115 22)'); ?>';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('Smart working - ')) {
            cell.innerHTML = cell.innerHTML.replace('Smart working - ', '');
            cell.style.color = 'rgb(255 255 255)';
            cell.style.background = '<?php echo $coloreSmartWorking = array_reduce($tipologie_richieste, function ($carry, $item) {
                    return $item['richieste_tipologia_value'] === 'Smart working' ? $item['richieste_tipologia_colore'] : $carry; }, 'rgb(249 115 22)'); ?>';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('Trasferta - ')) {
            cell.innerHTML = cell.innerHTML.replace('Trasferta - ', '');
            cell.style.color = 'rgb(255 255 255)';
            cell.style.background = '<?php echo $coloreSmartWorking = array_reduce($tipologie_richieste, function ($carry, $item) {
                    return $item['richieste_tipologia_value'] === 'Trasferta' ? $item['richieste_tipologia_colore'] : $carry; }, 'rgb(249 115 22)'); ?>';
            cell.style.fontWeight = 'bold';
        }
    },
    data: data,
    contextMenu: false,
    defaultColAlign: 'left',
    /*footers: [
         //json_encode($footer);
    ],*/
    columns: [{
            type: 'html',
            title: 'Dipendente',
            width: 50,
            readOnly: true,
        },
        <?php
            for ($giorno = 1; $giorno <= $giorni_mese; $giorno++):
                $giorno_completo = sprintf("%s-%02d-%02d", $anno, $mese, $giorno);
                $giorno_settimana = strftime("%w", strtotime($giorno_completo));
                $iniziale_giorno = substr($giorni_map[$giorno_settimana], 0, 1);

                //Se sono in una festività devo colorare la cella quindi uso lettera diversa
                if (!empty($festivita)) {
                    foreach ($festivita as $festivo) {
                        $data_festivo = dateFormat($festivo['festivita_data'], 'Y-m-d');
                        if ($giorno_completo == $data_festivo) {
                            $iniziale_giorno = "F";
                        }
                    }
                }

                $headers[] = $giorno . "($iniziale_giorno)"; //mi serve per l'export
                if ($iniziale_giorno === 'D' || $iniziale_giorno === 'F') {
                    $giorno_header = $giorno . "($iniziale_giorno)";
                } else {
                    $giorno_header = $giorno;
                }
                ?> {
            type: 'html',
            title: '<?php echo $giorno_header; ?>',
            width: 12,
            readOnly: true,
        },
        <?php endfor; ?> {
            type: 'numeric',
            title: 'Previste',
            width: 20,
            readOnly: true,
            align: 'center'
        }, {
            type: 'numeric',
            title: 'TOT',
            width: 20,
            readOnly: true,
            align: 'center'
        }, <?php if ($this->entities->entity_exists('rapportini')): ?>{
                type: 'numeric',
                title: 'Rapportini',
                width: 20,
                readOnly: true,
                align: 'center'
            },<?php endif; ?>

    ],
});

//hide row number column
table3.hideIndex();

$(document).ready(function() {
    // Seleziona tutte le <td> principali con la parola 'D' nel titolo
    var mainTdsDomenica = $('#spreadsheet_presenze_riepilogo td[title*="(D)"]');

    mainTdsDomenica.each(function() {
        var mainTdDomenica = $(this);
        var dataXDomenica = mainTdDomenica.data('x');
        var relatedTdsDomenica = $('#spreadsheet_presenze_riepilogo td[data-x="' + dataXDomenica + '"]');

        relatedTdsDomenica.css({
            color: 'white',
            background: 'rgb(239 68 68)'
        });
    });


    // Seleziona tutte le <td> principali con la parola 'F' nel titolo
    var mainTdsFestivo = $('#spreadsheet_presenze_riepilogo td[title*="(F)"]');

    mainTdsFestivo.each(function() {
        var mainTdFestivo = $(this);
        var dataXFestivo = mainTdFestivo.data('x');
        var relatedTdsFestivo = $('#spreadsheet_presenze_riepilogo td[data-x="' + dataXFestivo + '"]');

        relatedTdsFestivo.css({
            color: 'white',
            background: 'rgb(239 68 68)'
        });
    });

    $('body').on('click', '.js_cella_presenza', function(e) {
        $('.jexcel_overflow td').removeClass('js_last_clicked');

        $(this).parent().addClass('js_last_clicked');
    });
    
    $('#stampa_riepilogo').on('click', function() {
        html2canvas(document.querySelector("#spreadsheet_presenze_riepilogo")).then(canvas => {
            var base64Image = canvas.toDataURL();
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Stampa Immagine</title>
                        <style>
                            @page {
                                size: landscape
                            }
                            body {
                                margin: 0;
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                height: 100vh;
                            }
                            img {
                                max-width: 100%;
                                max-height: 100%;
                            }
                        </style>
                    </head>

                    <body>
                        <img src="${base64Image}" alt="Riepilogo presenze">
                    </body>

                    \x3Cscript>
                        window.matchMedia('print').addEventListener('change', function(event) {
                            if (!event.matches) {
                                window.close();
                            }
                        });
    
                        window.onload = function() {
                            window.print();
                        };
                        
                        window.onafterprint = window.close;
                    \x3C/script>
                </html>
            `);
            printWindow.document.close();
        });
    });
});
</script>