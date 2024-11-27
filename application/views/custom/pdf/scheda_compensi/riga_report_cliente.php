<?php
global $totalone_euro, $totale_sedi, $righe_stampate, $dati_grezzi;
//debug(costo_orario(11, '2017-05-08 07:00:00', 1, '0', 449),true);

foreach ($reports as $report) {
    $report['rapportini_inizio'] = dateFormat($report['rapportini_data'], 'Y-m-d') . ' ' . dateTimeFormat($report['rapportini_ora_inizio'], 'H:i:00');
    $report['rapportini_fine'] = dateFormat($report['rapportini_data'], 'Y-m-d') . ' ' . dateTimeFormat($report['rapportini_ora_fine'], 'H:i:00');
    
    //Verifico se sto stampando un affiancamento o meno (come passato dalla vista associato_mese) e se la categoria è uguale (sempre a quella passata dalla view)
    if (
        $report['rapportini_affiancamento'] != $affiancamento
        || $report['rapportini_costo_differenziato'] != $costo_differenziato
        || $report['projects_orari_categoria'] != $categoria['sedi_operative_orari_categoria_id']
    ) {
        continue;
    } elseif (empty($costo_orario) && empty($tariffa_piena)) {
        //debug($associato,true);
        $costo_orario = costo_orario($sede_id, $report['rapportini_inizio'], $report['projects_orari_categoria'], $report['rapportini_affiancamento'] == '1', $report['rapportini_costo_differenziato'] == '1', $associato['dipendenti_id'] ?? null);
        $tariffa_piena = costo_orario($sede_id, $report['rapportini_inizio'], $report['projects_orari_categoria'], $report['rapportini_affiancamento'] == '1', $report['rapportini_costo_differenziato'] == '1');
        
        if (empty($costo_orario) && empty($tariffa_piena)) { //Vuol dire che non ho nessun report da stampare, quindi ritorno senza stampare niente.
            //            debug($sede_id);
            //            debug($report['rapportini_inizio']);
            //            debug($report['rapportini_affiancamento']);
            //            debug($associato['dipendenti_id']);
            //            debug(costo_orario($sede_id, $report['rapportini_inizio'], $report['projects_orari_categoria'], $report['rapportini_affiancamento'] == '1', $associato['dipendenti_id']),true);
            return;
        }
    }
}
if (empty($costo_orario) && empty($tariffa_piena)) { //Vuol dire che non ho nessun report da stampare, quindi ritorno senza stampare niente.
    return;
}
?>
<!--<tr>
    <td colspan="6">
        <?php echo $categoria['sedi_operative_orari_categoria_value']; ?>
        <?php
        if ($affiancamento == '1') {
            echo ' - Affiancamenti';
        } elseif ($costo_differenziato == '1') {
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
        $report['rapportini_inizio'] = dateFormat($report['rapportini_data'], 'Y-m-d') . ' ' . dateTimeFormat($report['rapportini_ora_inizio'], 'H:i:00');
        $report['rapportini_fine'] = dateFormat($report['rapportini_data'], 'Y-m-d') . ' ' . dateTimeFormat($report['rapportini_ora_fine'], 'H:i:00');

        //Verifico se sto stampando un affiancamento o meno (come passato dalla vista associato_mese) e se la categoria è uguale (sempre a quella passata dalla view)
        if (
            $report['rapportini_affiancamento'] != $affiancamento
            || $report['rapportini_costo_differenziato'] != $costo_differenziato
            || $report['projects_orari_categoria'] != $categoria['sedi_operative_orari_categoria_id']
        ) {
            continue;
        } elseif (empty($costo_orario) && empty($tariffa_piena)) {
            //$costo_orario = costo_orario($sede_id, $report['rapportini_inizio'], $report['projects_orari_categoria'], $report['rapportini_affiancamento'] == '1', $report['rapportini_costo_differenziato'] == '1', $associato['dipendenti_id']);
            $tariffa_piena = costo_orario($sede_id, $report['rapportini_inizio'], $report['projects_orari_categoria'], $report['rapportini_affiancamento'] == '1', $report['rapportini_costo_differenziato'] == '1');
            $costo_orario = $tariffa_piena;
        }

        $totale_ore += differenza_in_ore_float($report['rapportini_inizio'], $report['rapportini_fine']);

        //debug($totale_ore);

        $totale_euro += $report['tariffa_totale'];
    }

    @$dati_grezzi['totali_ore_sede'][$report['customers_shipping_address_name']][$categoria['sedi_operative_orari_categoria_value']]['ore'] += $totale_ore;
    @$dati_grezzi['totali_ore_sede'][$report['customers_shipping_address_name']][$categoria['sedi_operative_orari_categoria_value']]['euro'] += $totale_euro;
    @$dati_grezzi['totali_ore_sede'][$report['customers_shipping_address_name']][$categoria['sedi_operative_orari_categoria_value']]['tariffa'] = $tariffa_piena;

    $totalone_euro += round($totale_euro, 2);

    @$totale_sedi[$sede_id] += round($totale_euro, 2);
    ?>
    <td class="tg-031e"><?php echo $associato['dipendenti_nome']; ?> <?php echo $associato['dipendenti_cognome']; ?></td>
    <td class="tg-031e" style="white-space: nowrap;">
        <?php echo mese_testuale($report['rapportini_inizio']); ?> (<?php echo $categoria['sedi_operative_orari_categoria_value']; ?><?php echo ($affiancamento == '1') ? ' - Affiancamenti' : ''; ?>)
    </td>
    <td class="tg-0ord"><?php echo $totale_ore; ?></td>
    <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
        <td class="tg-0ord"><?php echo $tariffa_piena; ?></td>
        <td class="tg-0ord"><?php echo round($totale_euro, 2); ?></td>
    <?php endif; ?>
    <td class="tg-yw4l" style="white-space: nowrap;">
        <nobr><?php echo $sede['customers_full_name']; ?> - <?php echo $sede['customers_shipping_address_name']; ?></nobr>
    </td>
    <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
        <td class="tg-lqy6"></td>
    <?php endif; ?>
</tr>
