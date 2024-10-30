<?php
global $totalone_euro;
//debug(costo_orario(11, '2017-05-08 07:00:00', 1, 'f', 449),true);
foreach ($reports as $report) {
    if ($affiancamento == 't' && $categoria['sedi_operative_orari_categoria_id'] == 2) {
        //        debug($affiancamento);
        //        debug($costo_differenziato);
        //        debug($categoria['sedi_operative_orari_categoria_id']);
        //        debug($report, true);
    }



    //Verifico se sto stampando un affiancamento o meno (come passato dalla vista associato_mese) e se la categoria è uguale (sempre a quella passata dalla view)
    if (
        $report['report_orari_affiancamento'] != $affiancamento
        || $report['report_orari_costo_differenziato'] != $costo_differenziato
        || $report['sedi_operative_orari_categoria'] != $categoria['sedi_operative_orari_categoria_id']
    ) {

        //        if ($report['report_orari_id'] == 12440 && $categoria['sedi_operative_orari_categoria_id'] == 3 && $affiancamento == 'f' && $costo_differenziato == 'f') {
        //            var_dump($report['report_orari_affiancamento'] != $affiancamento );
        //            var_dump($report['report_orari_costo_differenziato'] != $costo_differenziato );
        //            var_dump(($report['sedi_operative_orari_categoria'] != $categoria['sedi_operative_orari_categoria_id']));
        //            debug($categoria);
        //            debug($report,true);
        //        }

        continue;
    } elseif (empty($costo_orario) && empty($tariffa_piena)) {

        $costo_orario = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't', $associato['associati_id']);

        //debug($costo_orario);

        $tariffa_piena = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't');
        if (empty($costo_orario) && empty($tariffa_piena)) { //Vuol dire che non ho nessun report da stampare, quindi ritorno senza stampare niente.
            //            debug($sede_id);
            //            debug($report['report_orari_inizio']);
            //            debug($report['report_orari_affiancamento']);
            //            debug($associato['associati_id']);
            //            debug(costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $associato['associati_id']),true);
            //            debug($costo_orario);
            //            debug($tariffa_piena,true);
            //            return;    
        }
    }
}
if (empty($costo_orario) && empty($tariffa_piena) && !isset($costo_orario)) { //Vuol dire che non ho nessun report da stampare, quindi ritorno senza stampare niente.


    return;
}
//debug($costo_orario);
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
        $costo_orario = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't', $associato['associati_id']);
        $tariffa_piena = costo_orario($sede_id, $report['report_orari_inizio'], $report['sedi_operative_orari_categoria'], $report['report_orari_affiancamento'] == 't', $report['report_orari_costo_differenziato'] == 't');
    }

    $totale_ore += differenza_in_ore_float($report['report_orari_inizio'], $report['report_orari_fine']);

    //debug($totale_ore);

    $totale_euro += $report['tariffa_totale'];
}
//debug($totale_ore);
$totalone_euro += round($totale_euro, 2); //round($totale_euro * $associato['associati_percentuale_sedi'] / 100, 2);
//    debug($costo_orario);
//    debug($associato,true);
$is_associato = (in_array($this->auth->get('utenti_tipo'), ['9', '17'])) && !empty($this->auth->get('associati_id')) ? true : false;
?>
<?php if ($totale_ore) : ?>
    <tr>

        <td class="tg-031e">
            <?php echo mese_testuale($report['report_orari_inizio']); ?> (<?php echo $categoria['sedi_operative_orari_categoria_value']; ?><?php
                                                                                                                                            if ($affiancamento == 't') {
                                                                                                                                                echo ' - Affiancamenti';
                                                                                                                                            } elseif ($costo_differenziato == 't') {
                                                                                                                                                echo ' - Costo differenziato';
                                                                                                                                            }
                                                                                                                                            ?>)</td>
        <td class="tg-0ord"><?php echo $totale_ore; ?></td>

        <td class="tg-0ord"><?php echo round($costo_orario, 2); ?></td>
        <td class="tg-0ord"><?php echo round($totale_euro, 2); ?></td>

        <td class="tg-yw4l" <?php echo $is_associato ? 'colspan="2"' : null; ?>><?php echo $sede['clienti_ragione_sociale']; ?> - <?php echo $sede['sedi_operative_reparto']; ?></td>
        <?php if (!$is_associato) : ?>
            <td class="tg-lqy6"><?php echo $tariffa_piena; ?></td>
        <?php endif; ?>
    </tr>
<?php endif; ?>