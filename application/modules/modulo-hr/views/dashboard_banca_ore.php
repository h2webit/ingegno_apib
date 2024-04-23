<?php
$dipendente_id = $this->auth->get('dipendenti_id');

$block = DB_BOOL_FALSE;

if(empty($dipendente_id)) {
    $block = DB_BOOL_TRUE;
}

$settings = $this->apilib->searchFirst('impostazioni_hr');
$giorno_sblocco = (int) min($settings['impostazioni_hr_giorno_sblocco_banca_ore'] ?? 0, 31);

$giorno = intval(date('d'));

$AND = '';
$txt = '';
$data_aggiornamento = date('d/m/Y');

if(empty($giorno_sblocco) || $giorno_sblocco == 0) {
    $txt = 'Banca ore real time';
} else if($giorno < $giorno_sblocco) {
    // Devo tornare la banca ore di due mesi fa
    // Es. sblocco = 10, oggi è 5 agosto, devo tornare la banca ore fine a fine giugno
    $AND = " AND DATE_FORMAT(banca_ore_data, '%Y-%m') <= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), '%Y-%m')";
    $txt = 'Banca ore fino a due mesi fa';
    $data_aggiornamento = (new DateTime('last day of -2 months'))->format('d/m/Y');
} else {
    // Devo tornare la banca ore di due mesi fa
    // Es. sblocco = 10, oggi è 16 agosto, devo tornare la banca ore fino a fine luglio
    $AND = " AND DATE_FORMAT(banca_ore_data, '%Y-%m') <= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
    $txt = 'Banca ore ore fino al mese precedente';
    $data_aggiornamento = (new DateTime('last day of last months'))->format('d/m/Y');
}

$banca_ore = $this->db->query("
    SELECT *
    FROM banca_ore
    JOIN banca_ore_movimento ON banca_ore.banca_ore_movimento = banca_ore_movimento.banca_ore_movimento_id
    WHERE banca_ore_dipendente = '{$dipendente_id}'
    {$AND}
    ORDER BY banca_ore_data DESC
")->result_array();

//Calcolo saldo
$saldo = 0;

if(!empty($banca_ore)) {
    foreach ($banca_ore as $movimento) {
        switch ($movimento['banca_ore_movimento']) {
            case "1": // Aggiunto
                $saldo += floatval($movimento['banca_ore_hours']);
                break;
            case "2": // Cancellata
                $saldo += 0;
                break;
            case "3": // Pagata
            case "4": // Utilizzata
                $saldo -= floatval($movimento['banca_ore_hours']);
                break;
            default:
                $saldo += 0;
                break;
        }
    }
}

if($block == DB_BOOL_FALSE) : 
?>

<div class="row">
    <div class="col-xs-12 col-md-4">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>Banca ore</h3>
                <h4>Attualmente hai a disposizione <strong><?php echo number_format($saldo, 2, ',', '.'); ?></strong> ore.</h4>
                <h4>Saldo aggiornato al <strong><?php echo $data_aggiornamento; ?></strong></h4>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-md-8">
        <table class="table table-striped table-bordered table-hover table-condensed js_datatable">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Movimento</th>
                    <th>Ore</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($banca_ore as $movimento) :
                        $segno = $movimento['banca_ore_movimento'] == 4 ? '-' : '';
                ?>
                <tr class="<?php echo $movimento['banca_ore_movimento'] == 4 ? 'text-red' : ''; ?>">
                    <td><?php echo dateFormat($movimento['banca_ore_data'], 'd/m/Y'); ?></td>
                    <td><?php echo $movimento['banca_ore_movimento_value']; ?></td>
                    <td><?php echo $segno.' '.number_format($movimento['banca_ore_hours'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php else : ?>
<div class="row">
    <div class="col-sm-12">
        <h3>Non è stato rilevato un dipendente associato all'utente, non è possibile visualizzare la banca ore.</h3>
    </div>
</div>
<?php endif; ?>