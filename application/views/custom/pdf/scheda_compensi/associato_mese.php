<div class="row">
    <div class="col-sm-6 text ">
        <p style="font-size: 2em;">NOMINATIVO: <?php echo $associato['associati_nome']; ?> <?php echo $associato['associati_cognome']; ?>
            ANNO <?php echo $this->input->get('Y'); ?> - MESE <?php echo $mese_testuale; ?><br>
            Data di entrata <?php echo $associato['associati_inizio_rapporto']; ?><br>
            <?php if ($associato['associati_fine_rapporto']): ?>Data DI FINE LAVORO <?php echo $associato['associati_fine_rapporto']; ?><?php endif;?>
        </p>
    </div>

    <div class="col-sm-1">
        <?php if ($scheda_pagata): ?>
            <?php //debug($pagamento,true);
?>
            <img src="<?php echo base_url('images/pagato.png'); ?>" />
            <?php echo date('d/m/Y', strtotime(substr($pagamento['pagamenti_data_evasione'] ?: $pagamento['pagamenti_data_modifica'], 0, 10))); ?>
        <?php endif;?>
    </div>

    <div class="right col-sm-5">
        <p>
            Automobile: <?php echo $associato['associati_automobile']; ?>-TARGA: <?php echo $associato['associati_targa']; ?>
        </p><br />
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <?php if ($associato["associati_note_$i"]): ?><?php echo $associato["associati_note_$i"]; ?><?php endif;?><br />
        <?php endfor;?>

    </div>
</div>
<div class="col-sm-12 text">
    <?php $is_associato = (in_array($this->auth->get('utenti_tipo'), ['9', '17'])) && !empty($this->auth->get('associati_id')) ? true : false;?>
    <table class="tg">
        <tr>
            <th class="tg-7nj3">PERIODO MESE LAVORATO</th>
            <th class="tg-7nj3">ORE TOT O NUM. PRESTAZIONI</th>
            <th class="tg-pbc0">
                <div class="red">IMPORTO UNITARIO A PRESTAZIONE</div>
            </th>
            <th class="tg-7nj3">TOTALE</th>
            <th class="tg-slju" <?php echo $is_associato ? 'colspan="2"' : null; ?>>CLIENTE</th>
            <?php if (!$is_associato): ?>
                <th class="tg-slju">tariffa piena</th>
            <?php endif;?>
        </tr>
        <tr>
            <td class="tg-031e" colspan="6"><strong>Ore sedi operative</strong></td>
        </tr>
        <?php $categorie = $this->db->where(['sedi_operative_orari_categoria_id <>' => '6'])->get('sedi_operative_orari_categoria')->result_array();?>
        <?php
global $totalone_euro;
$totalone_euro = 0;
?>
        <?php foreach ($report_orari_sedi as $sede_id => $reports): ?>
            <?php $sede = $this->apilib->view('sedi_operative', $sede_id);?>
            <?php //debug ($reports,true) ;
?>
            <?php foreach ($categorie as $categoria): ?>

                <?php // Non affiancamento
?>
                <?php
$this->load->view('pdf/scheda_compensi/riga_report_associato', [
    'sede' => $sede,
    'affiancamento' => 'f',
    'costo_differenziato' => 'f',
    'categoria' => $categoria,
    'totalone_euro' => $totalone_euro,
    'reports' => $reports,
    'sede_id' => $sede_id,
    'associato' => $associato,
]);?>

                <?php // Affiancamenti
?>
                <?php $this->load->view('pdf/scheda_compensi/riga_report_associato', [
    'sede' => $sede,
    'affiancamento' => 't',
    'costo_differenziato' => 'f',
    'categoria' => $categoria,
    'totalone_euro' => $totalone_euro,
    'reports' => $reports,
    'sede_id' => $sede_id,
    'associato' => $associato,
]);?>

                <?php // Costi differenziati
?>
                <?php
$this->load->view('pdf/scheda_compensi/riga_report_associato', [
    'sede' => $sede,
    'affiancamento' => 'f',
    'costo_differenziato' => 't',
    'categoria' => $categoria,
    'totalone_euro' => $totalone_euro,
    'reports' => $reports,
    'sede_id' => $sede_id,
    'associato' => $associato,
]);?>
            <?php endforeach;?>

        <?php endforeach;?>
        <tr>
            <td class="tg-031e" colspan="6"><strong>Accessi sedi operative</strong></td>

        </tr>
        <?php foreach ($report_accessi_sedi as $sede_id => $reports): ?>
            <?php
//$costo_accesso = costo_accesso($sede_id, $reports[0]['report_orari_inizio'], $reports[0]['report_orari_affiancamento'] == 't');
$sede = $this->apilib->view('sedi_operative', $sede_id);

//debug($sede,true);
?>
            <tr>
                <?php
//Calcolo il totale ore
$totale_accessi = $totale_euro = 0;
foreach ($reports as $report) {
    $totale_accessi += $report['report_orari_accessi'];
    $totale_euro += $report['tariffa_totale'];
}
$totalone_euro += round($totale_euro * $associato['associati_percentuale_sedi'] / 100, 2);
?>
                <td class="tg-031e"><?php echo mese_testuale($report['report_orari_inizio']); ?></td>
                <td class="tg-0ord"><?php echo $totale_accessi; ?></td>
                <td class="tg-0ord"><?php echo round($report['tariffa'] * $associato['associati_percentuale_sedi'] / 100, 2); ?></td>
                <td class="tg-0ord"><?php echo $totale_euro * $associato['associati_percentuale_sedi'] / 100; ?></td>

                <td class="tg-yw4l" <?php echo $is_associato ? 'colspan="2"' : null; ?>><?php echo $sede['clienti_ragione_sociale']; ?> - <?php echo $sede['sedi_operative_reparto']; ?></td>
                <?php if (!$is_associato): ?>
                <td class="tg-lqy6"><?php echo $report['tariffa']; ?></td>
                <?php endif;?>
            </tr>
        <?php endforeach;?>
        <tr>
            <td class="tg-031e" colspan="6"><strong>Domiciliari (ore)</strong></td>

        </tr>
        <?php foreach ($report_prestazioni_domiciliari as $domiciliare_id => $reports): ?>
            <?php
//$costo_accesso = costo_accesso($sede_id, $reports[0]['report_orari_inizio'], $reports[0]['report_orari_affiancamento'] == 't');
$domiciliare = $this->apilib->view('domiciliari', $domiciliare_id);
if (empty($domiciliare)) {
    continue;
}

//Calcolo il totale ore
$totale_prestazioni = $totale_euro = 0;
foreach ($reports as $report) {

    //debug(differenza_in_ore_float($report['report_orari_inizio'], $report['report_orari_fine']), true);
    //$totale_ore += differenza_in_ore($report['report_orari_inizio'], $report['report_orari_fine']);
    if (empty($report['report_orari_prestazioni'])) {
        $ore = differenza_in_ore_float($report['report_orari_inizio'], $report['report_orari_fine']);
        $totale_prestazioni += $ore;
        $totale_euro += $report['tariffa_totale'];
    } else {
        // $totale_prestazioni += count($report['prestazioni']);
        // foreach ($report['prestazioni'] as $prestazione) {
        //     $totale_euro += $prestazione['listino_prezzi_prezzo'];
        // }
    }
}

$totalone_euro += $totale_euro * $associato['associati_percentuale_domiciliari'] / 100;
?>
            <?php if ($totale_prestazioni): ?>
                <tr>

                    <td class="tg-031e"><?php echo mese_testuale($report['report_orari_inizio']); ?></td>
                    <td class="tg-0ord"><?php echo $totale_prestazioni; ?></td>
                    <td class="tg-0ord"></td>
                    <td class="tg-0ord"><?php echo number_format($totale_euro * $associato['associati_percentuale_domiciliari'] / 100, 2); ?></td>

                    <td class="tg-yw4l" <?php echo $is_associato ? 'colspan="2"' : null; ?>><?php echo $domiciliare['domiciliari_nome']; ?> <?php echo $domiciliare['domiciliari_cognome']; ?></td>
                    <?php if (!$is_associato): ?>
                    <td class="tg-lqy6"><?php echo $totale_euro; ?></td>
                    <?php endif;?>
                </tr>
            <?php endif;?>
        <?php endforeach;?>
        <tr>
            <td class="tg-031e" colspan="6"><strong>Domiciliari (prestazioni)</strong></td>

        </tr>
        <?php foreach ($report_prestazioni_domiciliari as $domiciliare_id => $reports): ?>
            <?php
//$costo_accesso = costo_accesso($sede_id, $reports[0]['report_orari_inizio'], $reports[0]['report_orari_affiancamento'] == 't');
$domiciliare = $this->apilib->view('domiciliari', $domiciliare_id);
if (empty($domiciliare)) {
    continue;
}
//debug($sede,true);
?>
            <tr>
                <?php
//Calcolo il totale ore
$totale_prestazioni = $totale_euro = 0;
foreach ($reports as $report) {

    //debug(differenza_in_ore_float($report['report_orari_inizio'], $report['report_orari_fine']), true);
    //$totale_ore += differenza_in_ore($report['report_orari_inizio'], $report['report_orari_fine']);
    if (empty($report['report_orari_prestazioni'])) {
        // $ore = differenza_in_ore_float($report['report_orari_inizio'], $report['report_orari_fine']);
        // $totale_prestazioni += $ore;
        // $totale_euro += $report['tariffa_totale'];
    } else {
        $totale_prestazioni += count($report['prestazioni']);
        foreach ($report['prestazioni'] as $prestazione) {
            $totale_euro += $prestazione['listino_prezzi_prezzo'];
        }
    }
}
$totalone_euro += $totale_euro * $associato['associati_percentuale_domiciliari'] / 100;
?>
                <td class="tg-031e"><?php echo mese_testuale($report['report_orari_inizio']); ?></td>
                <td class="tg-0ord"><?php echo $totale_prestazioni; ?></td>
                <td class="tg-0ord"></td>
                <td class="tg-0ord"><?php echo number_format($totale_euro * $associato['associati_percentuale_domiciliari'] / 100, 2); ?></td>

                <td class="tg-yw4l" <?php echo $is_associato ? 'colspan="2"' : null; ?>><?php echo $domiciliare['domiciliari_nome']; ?> <?php echo $domiciliare['domiciliari_cognome']; ?></td>
                <?php if (!$is_associato): ?>
                    <td class="tg-lqy6"><?php echo $totale_euro; ?></td>
                <?php endif;?>
            </tr>
        <?php endforeach;?>

        <tr>
            <td colspan="6"><strong>Variazioni</strong></td>

        </tr>
        <?php

$totale_bonus = $totale_variazioni = $variazioni_pie_di_lista = 0;?>
        <?php foreach ($variazioni as $variazione): ?>
            <tr>
                <?php
if ($variazione['compensi_variazioni_pie_di_lista'] != 't') {
    $totale_variazioni += $variazione['compensi_variazioni_importo'];
} else {
    $variazioni_pie_di_lista += $variazione['compensi_variazioni_importo'];
}

if ($variazione['compensi_variazioni_tipo_value'] == 'Bonus') {
    $totale_bonus += $variazione['compensi_variazioni_importo'];
}

//die('test:'.$totalone_euro);
?>
                <td class="tg-031e"><?php echo substr($variazione['compensi_variazioni_data'], 0, 10); ?></td>
                <td class="tg-0ord" colspan="2"><?php echo $variazione['compensi_variazioni_tipo_value']; ?> - <?php echo $variazione['compensi_variazioni_variazione']; ?></td>

                <td class="tg-0ord"><?php echo $variazione['compensi_variazioni_importo']; ?></td>

                <td class="tg-yw4l" <?php echo $is_associato ? 'colspan="2"' : null; ?>></td>
                <?php if (!$is_associato): ?>
                    <td class="tg-lqy6"></td>
                <?php endif;?>
            </tr>
        <?php endforeach;?>

        <tr>
            <td class="tg-yw4l" colspan="4"></td>
            <td class="tg-yw4l">ACCONTO</td>
            <td class="tg-yw4l">
                <?php
$totale = $totalone_euro - ($rimborso_spese + $variazioni_pie_di_lista);

$totale = ($totale + $totale_variazioni) - $totale_bonus;
//debug($totale);
echo number_format((float) $totale, 2, ',', '.');
?>
            </td>
        </tr>
        <tr>
            <td class="tg-yw4l" colspan="4"></td>
            <td class="tg-yw4l">RIMBORSI KM</td>
            <td class="tg-yw4l"><?php echo number_format((float) $rimborso_spese, 2, ',', '.'); ?></td>
        </tr>
        <tr>
            <td class="tg-yw4l" colspan="4"></td>
            <td class="tg-yw4l">RIMB. PIE' DI LISTA</td>
            <td class="tg-yw4l"><?php echo $variazioni_pie_di_lista; ?></td>
        </tr>
        <?php if ($totale_bonus > 0): ?>
            <tr>
                <td class="tg-yw4l" colspan="4"></td>
                <td class="tg-yw4l">BONUS</td>
                <td class="tg-yw4l"><?php echo $totale_bonus; ?></td>
            </tr>
        <?php endif;?>
        <tr>
            <td class="tg-yw4l" colspan="4"></td>
            <td class="tg-yw4l">TOTALE VARIAZIONI (escl. piè di lista)</td>
            <td class="tg-yw4l"><?php echo $totale_variazioni - $totale_bonus; ?></td>
        </tr>
        <tr>
            <td class="tg-yw4l" colspan="4"></td>
            <td class="tg-yw4l"><strong>IMPORTO BONIFICO</strong></td>
            <td class="tg-yw4l"><strong><?php echo number_format((float) $totalone_euro + $totale_variazioni, 2, ',', '.'); ?></strong></td>
        </tr>



    </table>

    <?php

//debug($mese_numero,true);
$note_per_scheda = $this->apilib->search('allegati_per_scheda_compensi', [
    "allegati_per_scheda_compensi_associato" => $associato['associati_id'],
    'allegati_per_scheda_compensi_mese' => $mese,
    'allegati_per_scheda_compensi_anno' => $anno,
]);
//debug($note_per_fattura);
foreach ($note_per_scheda as $nota) {
    ?>
        <br />
        <p class="note_per_fattura"><?php echo $nota['allegati_per_scheda_compensi_note']; ?></p>
    <?php
}
?>

</div>

<?php
//Una volta generato, se ho un record in pagamenti_cache, vado a popolare i valori che mi interessano
$pagamenti_cache = $this->db->get_where('pagamenti_cache', [
    'pagamenti_cache_associato' => $associato['associati_id'],
    'pagamenti_cache_mese' => $mese,
    'pagamenti_cache_anno' => $anno,
])->row_array();

//print_r($pagamenti_cache);

if (!empty($pagamenti_cache)) {

    $this->db->where('pagamenti_cache_id', $pagamenti_cache['pagamenti_cache_id'])->update('pagamenti_cache', [
        'pagamenti_cache_acconto' => number_format($totalone_euro + $totale_variazioni, 2, '.', ''),
        'pagamenti_cache_rimborsi_km' => number_format($rimborso_spese, 2, '.', ''),
        'pagamenti_cache_totale_variazioni' => number_format($totale_variazioni, 2, '.', ''),
        'pagamenti_cache_importo_totale' => number_format($totalone_euro - ($rimborso_spese + $variazioni_pie_di_lista), 2, '.', ''),
        'pagamenti_cache_pie_di_lista' => number_format($variazioni_pie_di_lista, 2, '.', ''),
    ]);

    // $pagamenti_cache = $this->db->get_where('pagamenti_cache', [
    //     'pagamenti_cache_associato' => $associato['associati_id'],
    //     'pagamenti_cache_mese' => $mese,
    //     'pagamenti_cache_anno' => $anno
    // ])->row_array();

    //debug($pagamenti_cache,true);

}
?>