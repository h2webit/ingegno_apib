<?php
global $totalone_euro, $totale_sedi, $righe_stampate, $dati_grezzi;
//debug(costo_orario(11, '2017-05-08 07:00:00', 1, 'f', 449),true);


foreach ($reports as $report) {

    //Verifico se sto stampando un affiancamento o meno (come passato dalla vista associato_mese) e se la categoria è uguale (sempre a quella passata dalla view)
    if (
        $report['report_orari_affiancamento'] != $affiancamento
        || $report['report_orari_costo_differenziato'] != $costo_differenziato
        || $report['sedi_operative_orari_categoria'] != $categoria['sedi_operative_orari_categoria_id']
    ) {
        continue;
    } elseif (empty($costo_orario) && empty($tariffa_piena)) {
        //debug($associato,true);
        $costo_orario = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't', $associato['associati_id']);
        $tariffa_piena = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't');


        if ($associato['associati_id'] == 705) {
            //debug($tariffa_piena,true);
        }
        if (empty($costo_orario) && empty($tariffa_piena)) { //Vuol dire che non ho nessun report da stampare, quindi ritorno senza stampare niente.
            //            debug($sede_id);
            //            debug($report['report_orari_inizio']);
            //            debug($report['report_orari_affiancamento']);
            //            debug($associato['associati_id']);
            //            debug(costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $associato['associati_id']),true);
            return;
        }
    }
}
if ($associato['associati_id'] == 464) {
    //exit;
}
if (empty($costo_orario) && empty($tariffa_piena)) { //Vuol dire che non ho nessun report da stampare, quindi ritorno senza stampare niente.
    return;
}
?>
<!--<tr>
    <td colspan="6">
        <?php echo $categoria['sedi_operative_orari_categoria_value']; ?>
        <?php
        if ($affiancamento == 't') {
            echo ' - Affiancamenti';
        } elseif ($costo_differenziato == 't') {
            echo ' - Costo differenziato';
        }
        ?>
        
    </td>
</tr>-->
<?php $righe_stampate++; ?>
<tr>
    <?php
    //Calcolo il totale ore
    $totale_ore = $totale_euro = 0;
    foreach ($reports as $report) {

        //Verifico se sto stampando un affiancamento o meno (come passato dalla vista associato_mese) e se la categoria è uguale (sempre a quella passata dalla view)
        if (
            $report['report_orari_affiancamento'] != $affiancamento
            || $report['report_orari_costo_differenziato'] != $costo_differenziato
            || $report['sedi_operative_orari_categoria'] != $categoria['sedi_operative_orari_categoria_id']
        ) {
            continue;
        } elseif (empty($costo_orario) && empty($tariffa_piena)) {
            //$costo_orario = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't', $associato['associati_id']);
            $tariffa_piena = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't');
            $costo_orario = $tariffa_piena;
        }

        $totale_ore += differenza_in_ore_float($report['report_orari_inizio'], $report['report_orari_fine']);

        //debug($totale_ore);

        $totale_euro += $report['tariffa_totale'];
    }

    @$dati_grezzi['totali_ore_sede'][$report['sedi_operative_reparto']][$categoria['sedi_operative_orari_categoria_value']]['ore'] += $totale_ore;
    @$dati_grezzi['totali_ore_sede'][$report['sedi_operative_reparto']][$categoria['sedi_operative_orari_categoria_value']]['euro'] += $totale_euro;
    @$dati_grezzi['totali_ore_sede'][$report['sedi_operative_reparto']][$categoria['sedi_operative_orari_categoria_value']]['tariffa'] = $tariffa_piena;

    $totalone_euro += round($totale_euro, 2);

    @$totale_sedi[$sede_id] += round($totale_euro, 2);

    ?>
    <td class="tg-031e"><?php echo $associato['associati_nome']; ?> <?php echo $associato['associati_cognome']; ?></td>
    <td class="tg-031e" style="white-space: nowrap;">
        <?php echo mese_testuale($report['report_orari_inizio']); ?> (<?php echo $categoria['sedi_operative_orari_categoria_value']; ?><?php echo ($affiancamento == 't') ? ' - Affiancamenti' : ''; ?>)
    </td>
    <td class="tg-0ord"><?php echo $totale_ore; ?></td>
    <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
        <td class="tg-0ord"><?php echo $tariffa_piena; ?></td>
        <td class="tg-0ord"><?php echo round($totale_euro, 2); ?></td>
    <?php endif; ?>
    <td class="tg-yw4l" style="white-space: nowrap;">
        <nobr><?php echo $sede['clienti_ragione_sociale']; ?> - <?php echo $sede['sedi_operative_reparto']; ?></nobr>
    </td>
    <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
        <td class="tg-lqy6"></td>
    <?php endif; ?>
</tr>