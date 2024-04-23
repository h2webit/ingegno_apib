<?php
$filters = $this->session->userdata(SESS_WHERE_DATA);

$rimborsi_km_dipendente_field_id = $this->datab->get_field_by_name('rimborsi_km_dipendente')['fields_id'];
$rimborsi_km_data_field_id = $this->datab->get_field_by_name('rimborsi_km_data')['fields_id'];

if (empty($filters['filtra_anno_grafici']['361']['value']) || empty($filters['filtra_anno_grafici']['4398']['value'])) {
    die("Errore: Per generare una stampa, filtrare almeno un dipendente");
}

$dipendenti_where = null;

if (!empty($filters['filtra_anno_grafici']) && $filters['filtra_anno_grafici'][$rimborsi_km_dipendente_field_id]['value'] !== '-1') {
    $dipendenti_where =  $filters['filtra_anno_grafici'][$rimborsi_km_dipendente_field_id]['value'] ? "AND dipendenti_id = '{$filters['filtra_anno_grafici'][$rimborsi_km_dipendente_field_id]['value']}'" : null;
}

$dipendenti = $this->apilib->search('dipendenti', ["dipendenti_automobile_personale <> '' AND dipendenti_costo_chilometrico <> '' $dipendenti_where"]);

$mesi = [
    1 => 'gennaio',
    2 => 'febbraio',
    3 => 'marzo',
    4 => 'aprile',
    5 => 'maggio',
    6 => 'giugno',
    7 => 'luglio',
    8 => 'agosto',
    9 => 'settembre',
    10 => 'ottobre',
    11 => 'novembre',
    12 => 'dicembre',
];

$date = date('01/01/Y').' - '.date('31/12/Y');
$anno = date('Y');
if (!empty($filters['filtra_anno_grafici'])) {
    $date = $filters['filtra_anno_grafici'][$rimborsi_km_data_field_id]['value'] ?? null;
	//debug($date,true);
}

$date_ex = explode(' - ', $date);

$date_start = $date_ex[0];
$date_end = $date_ex[1];

$date_start_ex = explode('/', $date_start);
$date_start_month = $date_start_ex[1];
$date_start_year = $date_start_ex[2];

$anno = $date_start_year;

$date_end_ex = explode('/', $date_end);
$date_end_month = $date_end_ex[1];
$date_end_year = $date_end_ex[2];


foreach ($mesi as $mNum => $mName) {
    if ($mNum < $date_start_month || $mNum > $date_end_month) {
        unset($mesi[$mNum]);
    }
}

$fullTotalEur = $fullTotalKm = [];

?>
<style>
table {
    margin-top: 5px;
    margin-bottom: 40px;
}

table * {
    font-size: 10px;
}
</style>

<p style="margin-bottom: 20px">
    Periodo di riferimento: <strong><?php echo $date ?></strong><br />
    <?php foreach($dipendenti as $dipendente): ?>
    Automobile <strong><?php echo $dipendente['dipendenti_nome'].' ', ($dipendente['dipendenti_cognome'] ?? null); ?></strong>: <?php echo $dipendente['dipendenti_automobile_personale'] ?>, costo km € <strong><?php echo number_format($dipendente['dipendenti_costo_chilometrico'], 4, '.', ',') ?></strong><br />
    <?php endforeach; ?>
</p>

<?php foreach($mesi as $mNum => $mNome): ?>

<?php
            $rimborsi = $this->db
            ->join('dipendenti', 'dipendenti_id = rimborsi_km_dipendente')
            ->join('customers', 'customers_id = rimborsi_km_cliente', 'left')
            ->where("dipendenti_automobile_personale <> '' AND dipendenti_costo_chilometrico <> '' $dipendenti_where")
            ->where("EXTRACT(YEAR FROM rimborsi_km_data) = '{$anno}'")
            ->where("EXTRACT(MONTH FROM rimborsi_km_data) = '{$mNum}'")
            ->order_by('rimborsi_km_data')->get('rimborsi_km')
            ->result();

        if (empty($rimborsi)) continue;
        ?>

<h4 style="margin:20px 0 5px"><?php echo ucfirst($mNome) . ' ' . $anno; ?></h4>
<table style="width:100%">
    <thead>
        <tr>
            <th width="60">Data</th>
            <th width="80">Automobile</th>
            <th width="110">Cliente</th>
            <th width="160">Motivo</th>
            <th width="100">Luogo</th>
            <th width="50">Km</th>
            <th width="50" class="text-right">Costo</th>
        </tr>
    </thead>
    <tbody>
        <?php $totKm = $totEu = []; ?>
        <?php foreach($rimborsi as $rkm): ?>
        <?php
            @$totKm[$rkm->dipendenti_id] += $rkm->rimborsi_km_km;
            @$totEu[$rkm->dipendenti_id] += $rkm->rimborsi_km_costo_viaggio;
        ?>
        <tr>
            <td><?php echo dateFormat($rkm->rimborsi_km_data) ?></td>
            <td><?php echo $rkm->dipendenti_automobile_personale ?></td>
            <td><?php echo $rkm->customers_company ? character_limiter($rkm->customers_company, 20) : $rkm->rimborsi_km_clienti; ?></td>
            <td><?php echo ucfirst($rkm->rimborsi_km_motivo) ?></td>
            <td><?php echo ucfirst($rkm->rimborsi_km_luogo).' - '.ucfirst($rkm->rimborsi_km_luogo_arrivo) ?></td>
            <td><?php echo $rkm->rimborsi_km_km ?> km</td>
            <td style="text-align: right"><?php echo number_format($rkm->rimborsi_km_costo_viaggio, 2, '.', ' ') ?> €</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">
                <?php foreach ($dipendenti as $dipendente): ?>
                <?php
                    @$fullTotalKm[$dipendente['dipendenti_id']] += isset($totKm[$dipendente['dipendenti_id']])? $totKm[$dipendente['dipendenti_id']]: 0;
                    @$fullTotalEur[$dipendente['dipendenti_id']] += isset($totEu[$dipendente['dipendenti_id']])? $totEu[$dipendente['dipendenti_id']]: 0;
                ?>
                <strong><?php echo $dipendente['dipendenti_nome'].' ', ($dipendente['dipendenti_cognome'] ?? null) ?></strong> -
                TOT Km: <strong><?php echo isset($totKm[$dipendente['dipendenti_id']])? number_format($totKm[$dipendente['dipendenti_id']], 2, '.', ' ') : 0; ?> km</strong> -
                TOT Rimborso: <strong><?php echo isset($totEu[$dipendente['dipendenti_id']])? number_format($totEu[$dipendente['dipendenti_id']], 2, '.', ' '): 0; ?> €</strong>
                <br />
                <?php endforeach; ?>
            </td>
        </tr>
    </tfoot>
</table>

<?php endforeach; ?>

<hr>
<h4 style="margin:20px 0 5px">Totali</h4>
<?php $grandTotalKm = $grandTotalEur = 0; ?>
<?php foreach ($dipendenti as $dipendente): ?>
<?php
   $grandTotalKm  += isset($fullTotalKm[$dipendente['dipendenti_id']]) ?  $fullTotalKm[$dipendente['dipendenti_id']] :  0;
   $grandTotalEur += isset($fullTotalEur[$dipendente['dipendenti_id']]) ? $fullTotalEur[$dipendente['dipendenti_id']] : 0;

?>
<strong><?php echo $dipendente['dipendenti_nome'].' ', ($dipendente['dipendenti_cognome'] ?? null) ?></strong> -
TOT km: <strong><?php echo isset($fullTotalKm[$dipendente['dipendenti_id']]) ? $fullTotalKm[$dipendente['dipendenti_id']] : 0; ?> km</strong> -
TOT Rimborso: <strong><?php echo isset($fullTotalEur[$dipendente['dipendenti_id']]) ? number_format($fullTotalEur[$dipendente['dipendenti_id']], 2, '.', ' ') : 0; ?> €</strong>
<br />
<?php endforeach; ?>

<br />

<strong>Totale</strong> -
TOT km: <strong><?php echo $grandTotalKm; ?> km</strong> -
TOT rimborso: <strong><?php echo number_format($grandTotalEur, 2, '.', ' '); ?> €</strong>