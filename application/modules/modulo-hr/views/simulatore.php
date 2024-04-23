<div class="pad no-print">
    <div class="callout callout-info" style="margin-bottom: 0!important; background-color: #3c8dbc!important; border-left: 5px solid #eee; border-left-width: 5px; border-left-style: solid; border-left-color: #357ca5!important;">
        <h4><i class="fa fa-info"></i> Informazioni:</h4>
        <p>In questa pagina viene mostrata la simulazione dei ratei sulla base della maturazione prevista e dei saldi registrati per il dipendente.</p>
        <p>In grigio vengono mostrati gli incrementi dei mesi precedenti ad oggi, in blu gli incrementi previsti a partire da questo mese sulla base delle maturazioni attualmente in vigore.</p>
    </div>
</div>

<?php
$anno = date('Y');
$settings = $this->apilib->searchFirst('ratei_ferie_permessi_settings');
$tipologie = array('permessi', 'rol', 'ferie', 'banca_ore');

foreach ($tipologie as $tipologia) {
    $key = "ratei_ferie_permessi_settings_" . $tipologia;
    $visualizzazione_key = $tipologia . "_visualizzazione";

    if (isset($settings[$key]) && $settings[$key] == 1) {
        ${$visualizzazione_key} = 'h';
    } else {
        ${$visualizzazione_key} = 'gg';
    }
}

$ratei_tot = $this->db->query("
SELECT
    rfpa.ratei_ferie_permessi_dipendente,
    rfpa.ratei_ferie_permessi_mese,
    SUM(rfpa.ratei_ferie_permessi_saldo_ferie) AS tot_ferie,
    SUM(rfpa.ratei_ferie_permessi_saldo_rol) AS tot_rol,
    SUM(rfpa.ratei_ferie_permessi_saldo_permessi) AS tot_permessi,
    SUM(rfpa.ratei_ferie_permessi_saldo_banca_ore) AS tot_banca_ore
FROM
    ratei_ferie_permessi rfpa
JOIN
    ratei_ferie_permessi_anno rfpa_anno ON rfpa_anno.ratei_ferie_permessi_anno_id = rfpa.ratei_ferie_permessi_anno
WHERE
    rfpa.ratei_ferie_permessi_dipendente = '{$value_id}' AND rfpa_anno.ratei_ferie_permessi_anno_value = YEAR(CURDATE()) - 1 AND rfpa.ratei_ferie_permessi_mese = 12
")->row_array();

//saldi anno precedente
if (empty($ratei_tot)) {
    $ratei_tot['tot_ferie'] = 0;
    $ratei_tot['tot_rol'] = 0;
    $ratei_tot['tot_banca_ore'] = 0;
    $ratei_tot['tot_permessi'] = 0;
}

$ratei = $this->db->query("
SELECT
    rf.*,
    rfa.*
FROM
    ratei_ferie_permessi rf
LEFT JOIN
    ratei_ferie_permessi_anno rfa ON rf.ratei_ferie_permessi_anno = rfa.ratei_ferie_permessi_anno_id
WHERE
    rf.ratei_ferie_permessi_dipendente = '{$value_id}' AND rfa.ratei_ferie_permessi_anno_value = YEAR(CURDATE())
")->result_array();

// maturazioni previste
$maturazioni = $this->db->query("
    SELECT
        maturazioni.*
    FROM
        maturazioni
    WHERE
        maturazioni_dipendente = '{$value_id}' AND 
        (DATE(maturazioni_data_inizio) <= CURDATE() AND (DATE(maturazioni_data_fine) >= CURDATE() OR maturazioni_data_fine IS NULL))
")->result_array();

// Creare un array associativo dei mesi con valori predefiniti a 0
$monthData = [];

$monthName = [
    1 => 'Gennaio',
    2 => 'Febbraio',
    3 => 'Marzo',
    4 => 'Aprile',
    5 => 'Maggio',
    6 => 'Giugno',
    7 => 'Luglio',
    8 => 'Agosto',
    9 => 'Settembre',
    10 => 'Ottobre',
    11 => 'Novembre',
    12 => 'Dicembre',
];


for ($month = 1; $month <= 12; $month++) {
    $monthData[$month] = [
        'mese' => $monthName[$month],
        'ratei_ferie_permessi_saldo_ferie' => 0,
        'ratei_ferie_permessi_saldo_rol' => 0,
        'ratei_ferie_permessi_saldo_permessi' => 0,
        'ratei_ferie_permessi_saldo_banca_ore' => 0,
    ];
}
$mesi_popolati = array();

if(!empty($ratei)) {
    // Popolare l'array dei mesi con i dati effettivi
    foreach ($ratei as $rateo) {
        $mesi_popolati[] = $rateo['ratei_ferie_permessi_mese'];
        $month = $rateo['ratei_ferie_permessi_mese'];
        // Se l'elemento esiste, aggiorna i valori
        $monthData[$month]['ratei_ferie_permessi_saldo_ferie'] += (float)$rateo['ratei_ferie_permessi_saldo_ferie'];
        $monthData[$month]['ratei_ferie_permessi_saldo_rol'] += (float)$rateo['ratei_ferie_permessi_saldo_rol'];
        $monthData[$month]['ratei_ferie_permessi_saldo_permessi'] += (float)$rateo['ratei_ferie_permessi_saldo_permessi'];
        $monthData[$month]['ratei_ferie_permessi_saldo_banca_ore'] += (float)$rateo['ratei_ferie_permessi_saldo_banca_ore'];
        
    }
}
foreach ($monthData as $month => $data) {
    if (!in_array($month, $mesi_popolati)) {
        $tipologie_maturazioni = [1 => 'ferie', 2 => 'permessi', 3 => 'rol', 4 => 'banca_ore'];
        $mese_format = str_pad($month, 2, '0', STR_PAD_LEFT); // Aggiungi zero davanti se il mese è inferiore a 10

        $maturazioni = $this->db->query("
        SELECT
            maturazioni.*
        FROM
            maturazioni
        WHERE
            maturazioni_dipendente = '{$value_id}' AND 
            DATE_FORMAT(maturazioni_data_inizio, '%Y-%m') <= '{$anno}-{$mese_format}' AND 
            (DATE_FORMAT(maturazioni_data_fine, '%Y-%m') >= '{$anno}-{$mese_format}' OR maturazioni_data_fine IS NULL)
    ")->result_array();
        foreach ($tipologie_maturazioni as $tipologia => $maturazione_nome) {
            $key = array_search($tipologia, array_column($maturazioni, 'maturazioni_tipologia'));
            $incremento = ($key !== false) ? $maturazioni[$key]['maturazioni_inc_mensile'] : 0;
            if($month == 1 AND $incremento == 0){
                //qua vuol dire che sono a gennaio, ho incremento 0 quindi devo stampare lo stesso valore del tot anno precedente
                $incremento_mese_precedente = $ratei_tot["tot_$maturazione_nome"];
            } else {
                $incremento_mese_precedente = isset($monthData[$month - 1]["ratei_ferie_permessi_saldo_$maturazione_nome"]) ? $monthData[$month - 1]["ratei_ferie_permessi_saldo_$maturazione_nome"] : 0;
            }

            $monthData[$month]["ratei_ferie_permessi_saldo_$maturazione_nome"] = (float) $incremento_mese_precedente + $incremento;
        }

        $monthData[$month]['totale'] = array_sum(array_column($monthData[$month], 'ratei_ferie_permessi_saldo_'));
    }
}

?>

<style>
.sim_badge {
    width: 95px;
    display: flex;
    justify-content: center;
    padding: 4px 12px;
    color: #3b82f6;
    background-color: #bfdbfe;
    border-radius: 4px;
    font-weight: bold;
}

.sim_badge_passato {
    width: 95px;
    display: flex;
    justify-content: center;
    padding: 4px 12px;
    color: #3b82f6;
    background-color: #e9e9e9;
    border-radius: 4px;
    font-weight: bold;
}
</style>

<section>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Ferie</th>
                    <th>ROL</th>
                    <th>Permessi</th>
                    <th>Banca ore</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Residuo <?php echo date('Y'); ?></th>
                    <td><span class="sim_badge_passato"><?php echo (float)number_format($ratei_tot['tot_ferie'], 2, '.', ',');?> <?php echo $ferie_visualizzazione; ?></span></td>
                    <td><span class="sim_badge_passato"><?php echo (float)number_format($ratei_tot['tot_rol'], 2, '.', ',');?> <?php echo $rol_visualizzazione; ?></span></td>
                    <td><span class="sim_badge_passato"><?php echo (float)number_format($ratei_tot['tot_permessi'], 2, '.', ',');?> <?php echo $permessi_visualizzazione; ?></span></td>
                    <td><span class="sim_badge_passato"><?php echo (float)number_format($ratei_tot['tot_banca_ore'], 2, '.', ',');?> <?php echo $banca_ore_visualizzazione; ?></span></td>
                </tr>
                <?php 
                if(!empty($monthData)) :
                    $mese_corrente = date('n');
                    foreach ($monthData as $el) :
                        // Ottenere la chiave numerica del mese dalla lista
                        $mese_dati = array_search($el['mese'], $monthName);
                        // Verificare se il mese è passato rispetto ad ora
                        if ($mese_dati < $mese_corrente) {
                            $classe = 'sim_badge_passato';
                        } else {
                            $classe = 'sim_badge';

                        }
                ?>
                <tr>
                    <th><?php echo $el['mese'];?></th>
                    <td><span class="<?php echo $classe; ?>"><?php echo $el['ratei_ferie_permessi_saldo_ferie'];?> <?php echo $ferie_visualizzazione; ?></span></td>
                    <td><span class="<?php echo $classe; ?>"><?php echo $el['ratei_ferie_permessi_saldo_rol'];?> <?php echo $rol_visualizzazione; ?></span></td>
                    <td><span class="<?php echo $classe; ?>"><?php echo $el['ratei_ferie_permessi_saldo_permessi'];?> <?php echo $permessi_visualizzazione; ?></span></td>
                    <td><span class="<?php echo $classe; ?>"><?php echo $el['ratei_ferie_permessi_saldo_banca_ore'];?> <?php echo $banca_ore_visualizzazione; ?></span></td>
                </tr>
                <?php
                    endforeach;
                endif;    
                ?>
                <!-- <tr>
                    <th>Gennaio</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Febbraio</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Marzo</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Aprile</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Maggio</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Giugno</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Luglio</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Agosto</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Settembre</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Ottobre</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Novembre</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Dicembre</th>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr> -->
            </tbody>
        </table>
    </div>
</section>