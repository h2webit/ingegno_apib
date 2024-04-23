<?php
$today = date('Y-m-d H:i:s', mktime(0, 0, 0));
$actual_time = date('H:i');

//$dipendenti = $this->apilib->search('dipendenti');

/**
 * ! Dipendenti presenti (data inizio oggi e data fine non impostata)
 */
//$dipendenti_presenti = $this->apilib->search('presenze', ['presenze_data_inizio' => $today, 'presenze_data_fine IS NULL OR presenze_data_fine = ""']);
$dipendenti_presenti = $this->db->query("SELECT * FROM presenze WHERE presenze_data_inizio = '{$today}' AND (presenze_data_fine IS NULL OR presenze_data_fine = '')")->result_array();
if (!empty($dipendenti_presenti)) {
    $presenti = count($dipendenti_presenti);
} else {
    $presenti = 0;
}


/**
 * ! Dipendenti reperibili oggi
 */
//$dipendenti_reperibili = $this->apilib->search('reperibilita', ["reperibilita_data = '{$today}'"]);
$dipendenti_reperibili = $this->db->query("SELECT * FROM reperibilita WHERE reperibilita_data = '{$today}'")->result_array();
if (!empty($dipendenti_reperibili)) {
    $reperibili = count($dipendenti_reperibili);
} else {
    $reperibili = 0;
}


/**
 * ! Dipendenti in malattia oggi (inzio <= oggi e fine non impostata o >= oggi) e stato approvato
 */
//$dipendenti_malattia = $this->apilib->search('richieste', ["richieste_tipologia = 3 AND richieste_stato = 2 AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '')"]);
$dipendenti_malattia = $this->db->query("SELECT * FROM richieste WHERE richieste_tipologia = '3' AND richieste_stato = '2' AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '')")->result_array();
if (!empty($dipendenti_malattia)) {
    $malattia = count($dipendenti_malattia);
} else {
    $malattia = 0;
}


/**
 * ! Dipendenti in permesso oggi (inzio <= oggi e fine non impostata o >= oggi e ora inizio <= all'ora attuale e ora fine > ora attuale) e stato approvato
 */
//$dipendenti_permesso = $this->apilib->search('richieste', ["richieste_tipologia = 1 AND richieste_stato = 2 AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '') AND richieste_ora_inizio <= '{$actual_time}' AND (richieste_ora_fine > '{$actual_time}' OR richieste_ora_fine IS NULL OR richieste_ora_fine = '')"]);
$dipendenti_permesso = $this->db->query("SELECT * FROM richieste WHERE richieste_tipologia = '1' AND richieste_stato = '2' AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '') AND richieste_ora_inizio <= '{$actual_time}' AND (richieste_ora_fine > '{$actual_time}' OR richieste_ora_fine IS NULL OR richieste_ora_fine = '')")->result_array();
if (!empty($dipendenti_permesso)) {
    $permesso = count($dipendenti_permesso);
} else {
    $permesso = 0;
}


/**
 * ! Dipendenti in ferie oggi (inzio <= oggi e fine non impostata o >= oggi) e stato approvato
 */
//$dipendenti_ferie = $this->apilib->search('richieste', ["richieste_tipologia = 2 AND richieste_stato = 2 AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '')"]);
$dipendenti_ferie = $this->db->query("SELECT * FROM richieste WHERE richieste_tipologia = '2' AND richieste_stato = '2' AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '')")->result_array();
if (!empty($dipendenti_ferie)) {
    $ferie = count($dipendenti_ferie);
} else {
    $ferie = 0;
}


/**
 * ! Dipendenti in smart working oggi e stato approvato
 */
//$dipendenti_ferie = $this->apilib->search('richieste', ["richieste_tipologia = 2 AND richieste_stato = 2 AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '')"]);
$dipendenti_smart_working = $this->db->query("SELECT * FROM richieste WHERE richieste_tipologia = '4' AND richieste_stato = '2' AND richieste_dal <= '{$today}' AND richieste_al >= '{$today}'")->result_array();
if (!empty($dipendenti_smart_working)) {
    $smart_working = count($dipendenti_smart_working);
} else {
    $smart_working = 0;
}


/**
 * ! Dipendenti in missione oggi  stato approvato
 */
//$dipendenti_ferie = $this->apilib->search('richieste', ["richieste_tipologia = 2 AND richieste_stato = 2 AND richieste_dal <= '{$today}' AND (richieste_al >= '{$today}' OR richieste_al IS NULL OR richieste_al = '')"]);
$dipendenti_trasferta = $this->db->query("SELECT * FROM richieste WHERE richieste_tipologia = '5' AND richieste_stato = '2' AND richieste_dal <= '{$today}' AND richieste_al >= '{$today}'")->result_array();
if (!empty($dipendenti_trasferta)) {
    $trasferta = count($dipendenti_trasferta);
} else {
    $trasferta = 0;
}


/**
 * ! Dipendenti assenti oggi (somma di chi è in malattia, permesso e farie nel momento in cui visulizzo la pagina)
 */
$assenti = $malattia + $permesso + $ferie;


/**
 * ! Dipendenti usciti oggi (inzio = oggi fine oggi e senza timbratura di oggi senza data di fine --> altrimenti potrei aver timbrato il turno pomeridiano ed essere presente)
 */
//$dipendenti_usciti = $this->apilib->search('presenze', ['presenze_data_inizio' => $today, 'presenze_data_fine' => $today]);
$dipendenti_usciti = $this->db->query("SELECT *
FROM presenze
WHERE 
    presenze_data_inizio = CURDATE() 
    AND presenze_data_fine = CURDATE()
    AND presenze_dipendente NOT IN (
        SELECT presenze_dipendente 
        FROM presenze 
        WHERE presenze_data_inizio = CURDATE() AND presenze_data_fine IS NULL
    )
GROUP BY presenze_dipendente")->result_array();

if(empty($dipendenti_usciti)) {
    $usciti = 0;
} else {
    $usciti = count($dipendenti_usciti);
}


/**
 * ! MANCATE TIMBRATURE
 */
$mancati = 0;

$anomalie = array();
$current_date_format = date('Y-m-d');

$this->db->select('t.*,d.dipendenti_nome,d.dipendenti_cognome');
$this->db->from('turni_di_lavoro t');
$this->db->join('dipendenti d', 't.turni_di_lavoro_dipendente = d.dipendenti_id');
$this->db->where('t.turni_di_lavoro_data_inizio <=', $current_date_format);
//se oggi vedo anche l'inizio
$this->db->where('d.dipendenti_attivo', 1);
$this->db->where('(d.dipendenti_presenza_automatica = 0 OR d.dipendenti_presenza_automatica IS NULL)');
$this->db->where('t.turni_di_lavoro_ora_inizio <', date('H:i'));
$this->db->where('(t.turni_di_lavoro_data_fine >= ' . $this->db->escape($current_date_format) . ' OR t.turni_di_lavoro_data_fine IS NULL)');
$this->db->where('t.turni_di_lavoro_giorno', date('N', strtotime($current_date_format)));
$turni = $this->db->get()->result_array();

//$subquery = "(SELECT COUNT(*) FROM presenze p WHERE p.presenze_dipendente = t.turni_di_lavoro_dipendente AND DATE(p.presenze_data_inizio) = '{$current_date_format}') = 0";
//debug($this->db->last_query());

$date_ita = date('d-m-Y');
if (!empty($turni)) {
    foreach ($turni as $turno) {
        $data_calendar_turno = $current_date_format . " " . $turno['turni_di_lavoro_ora_inizio'] . ":00";
        //vedo se ha timbrato correttamente
        $this->db->select('*');
        $this->db->from('presenze');
        $this->db->where('presenze_dipendente', $turno['turni_di_lavoro_dipendente']);
        $this->db->where('presenze_data_inizio', $current_date_format);
        $presenze = $this->db->get()->result_array();

        if (!empty($presenze)) {
            foreach ($presenze as $presenza) {
                if ($presenza['presenze_data_inizio_calendar'] <= $data_calendar_turno) {
                    //ok, ho timbrato prima, quindi vado avanti.
                    continue;
                } else {
                    $anomalie[] = [
                        "dipendente" => $turno['turni_di_lavoro_dipendente'],
                        "turno" => $turno,
                        "presenza" => $presenza,
                        "data" => $current_date_format
                    ];
                    //qua vuol dire che non ho timbrato!
                }
            }
        } else {
            $anomalie[] = [
                "dipendente" => $turno['turni_di_lavoro_dipendente'],
                "turno" => $turno,
                "presenza" => '',
                "data" => $current_date_format
            ];
        }
    }
}

$conteggio_anomalie = 0;
$anomalie_continue = false; // Variabile flag per il loop delle anomalie

foreach ($anomalie as $anomalia) {
    $data_calendar_turno = $anomalia['data'] . " " . $anomalia['turno']['turni_di_lavoro_ora_inizio'] . ":00";
    //verifico se aveva un permesso
    $this->db->select('*');
    $this->db->from('richieste');
    $this->db->where('richieste_user_id', $anomalia['dipendente']);
    $this->db->where('richieste_stato', 2);
    $richieste = $this->db->get()->result_array();
    if (!empty($richieste)) {
        foreach ($richieste as $richiesta) {
            // se la richiesta comprende oggi
            if (dateFormat($richiesta['richieste_data_ora_inizio_calendar'], 'Y-m-d H:i') <= date('Y-m-d H:i', strtotime($data_calendar_turno)) && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= date('Y-m-d H:i', strtotime($data_calendar_turno))) {
                $anomalie_continue = true; // Imposta il flag a true
                break; // Esci dal loop delle richieste
            } elseif (dateFormat($richiesta['richieste_data_ora_inizio_calendar'], 'Y-m-d') <= $data_calendar_turno && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
                $anomalie_continue = true; // Imposta il flag a true
                break; // Esci dal loop delle richieste
            } elseif ((strtotime($richiesta['richieste_data_ora_inizio_calendar']) == date('Y-m-d H:i', strtotime($data_calendar_turno)))) {
                $anomalie_continue = true; // Imposta il flag a true
                break; // Esci dal loop delle richieste
            }
        }
    }

    if ($anomalie_continue) {
        continue; // Se il flag è true, continua il loop delle anomalie
    }
    $conteggio_anomalie++;
}

?>

<style>
.presenza_card {
    /*height: 120px;*/
    height: 140px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-direction: column;
    background-color: #ffffff;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
    width: 100%;
    border-radius: 4px;
    padding: 16px;
    margin-bottom: 24px;
}

.presenza_card_recap {
    height: 140px;
    /*display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-direction: column;*/
    background-color: #ffffff;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
    width: 100%;
    border-radius: 4px;
    padding: 16px;
    margin-bottom: 24px;
}

.presenza_card_recap .presenza_info:first-child .presenza_label {
    margin-top: 0px;
}

.presenza_counter {
    font-size: 50px;
    font-weight: medium;
    text-align: center;
    color: #94a3b8;
}

.presenza_label {
    margin-top: 8px;
    color: #64748b;
    font-size: 18px;
    font-weight: medium;
    text-align: center;
    text-transform: capitalize;
    letter-spacing: 0.025em;
}

.container_dipendenti {
    overflow-x: scroll;
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

.avatar_dipendente {
    margin-right: 24px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.dipendente_riferimento {
    width: 100px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 6px;
    background-color: #94a3b8;
    color: #ffffff;
    padding: 3px 6px;
    font-size: 12px;
    text-align: center;
    border-radius: 4px;
}



.presenza_info {
    /*padding: 8px 0;*/
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    line-height: 1 !important;
}

.presenza_info .title {
    font-size: 14px;
}

.presenza_info .counter {
    font-size: 24px;
}

.presenza_info .presenti {
    color: #16a34a;
}

.presenza_info .reperibili {
    color: #0284c7;
}

.presenza_info .assenti {
    color: #d97706;
}
</style>



<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-xs-12">
            <div class="presenze_info presenza_card_recap">
                <div class="presenza_info">
                    <span class="presenza_label" style="float: left;">Presenti</span> <span style="float: right;" class="counter presenti"><?php echo $presenti; ?></span>
                </div>
                <div class="presenza_info">
                    <span class="presenza_label" style="float: left;">Reperibili</span> <span style="float: right;" class="counter reperibili"><?php echo $reperibili; ?></span>
                </div>
                <div class="presenza_info">
                    <span class="presenza_label" style="float: left;">Assenti</span> <span style="float: right;" class="counter assenti"><?php echo $assenti; ?></span>
                </div>
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/mancate_timbrature/"); ?>?_size=extra">

                    <div style="margin-top:5px;" class="presenza_info">

                        <span class="presenza_label" style="float: left;">Anomalie timbrature</span> <span style="float: right;" class="counter mancati"><?php echo $conteggio_anomalie; ?></span>

                    </div>
                </a>

            </div>
        </div>
        <div class="col-md-9 col-xs-12">
            <div class="row">
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/smart_working_giornalieri"); ?>?_size=extra">
                    <div class="col-xs-6 col-md-2">
                        <div class="presenza_card">
                            <div class="presenza_counter" style="color: #6366f1;">
                                <?php echo $smart_working; ?>
                            </div>
                            <div class="presenza_label">
                                smart working
                            </div>
                        </div>
                    </div>
                </a>
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/trasferte_giornaliere"); ?>?_size=extra">
                    <div class="col-xs-6 col-md-2">
                        <div class="presenza_card">
                            <div class="presenza_counter" style="color: #0ea5e9;">
                                <?php echo $trasferta; ?>
                            </div>
                            <div class="presenza_label">
                                Trasferta
                            </div>
                        </div>
                    </div>
                </a>
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/malattie_giornaliere"); ?>?_size=extra">
                    <div class="col-xs-6 col-md-2">
                        <div class="presenza_card">

                            <div class="presenza_counter" style="color: #dc2626;">
                                <?php echo $malattia; ?>
                            </div>
                            <div class="presenza_label">
                                malattia
                            </div>

                        </div>
                    </div>
                </a>
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/permessi_giornalieri"); ?>?_size=extra">
                    <div class="col-xs-6 col-md-2">
                        <div class="presenza_card">
                            <div class="presenza_counter" style="color: #ca8a04;">
                                <?php echo $permesso; ?>
                            </div>
                            <div class="presenza_label">
                                permesso
                            </div>
                        </div>
                    </div>
                </a>
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/ferie_giornaliere"); ?>?_size=extra">
                    <div class="col-xs-6 col-md-2">
                        <div class="presenza_card">
                            <div class="presenza_counter" style="color: #6c7178;">
                                <?php echo $ferie; ?>
                            </div>
                            <div class="presenza_label">
                                ferie
                            </div>
                        </div>
                    </div>
                </a>
                <a class='js_open_modal' title="Visualizza dettagli" href="<?php echo base_url("get_ajax/layout_modal/uscite_giornaliere"); ?>?_size=extra">
                    <div class="col-xs-6 col-md-2">
                        <div class="presenza_card">
                            <div class="presenza_counter" style="color: #0f172a;">
                                <?php echo $usciti; ?>
                            </div>
                            <div class="presenza_label">
                                Usciti
                            </div>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
</div>