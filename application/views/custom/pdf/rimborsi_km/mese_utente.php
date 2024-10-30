<?php $totalekm = 0;

//debug($utente,true);
?>
<div style="clear: both;    display: block;    border :1px solid transparent;    page-break-after: always;">
    <div class="row">
        <div class="col-sm-9 text ">
            <p>
                SPETT.LE STUDIO INFERMIERISTICO BOSCO DANIELA E ASSOCIATI ( A.P.I.B.)<br />
                Via Paolo Fabbri, 1/2 40138 Bologna<br />
                <strong>Tel. 051-272603 FAX 051/9911961 Cell.335-383498 </strong><br />
                E-mail:info@apibinfermieribologna.com
            </p>
            <p>
                NOTA RIEPILOGATIVA RELATIVE ALLE SPESE DI TRASFERTA
            </p>
        </div>
        <div class="col-sm-3 text ">
            <?php echo mese_testuale((int) $mese); ?> <?php echo (int) $anno; ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6 left">
            <?php //debug($utente, true); 
            ?>
            Il/La sottoscritto/a: <strong><?php echo $utente['utenti_nome']; ?> <?php echo $utente['utenti_cognome']; ?></strong><br />
            codice fiscale: <strong><?php echo $utente['associati'][0]['associati_cf']; ?></strong><br />
            in via: <strong><?php echo $utente['associati'][0]['associati_indirizzo_residenza']; ?></strong><br />
            automobile: <strong><?php echo $utente['associati'][0]['associati_automobile']; ?></strong>
        </div>
        <div class="col-sm-6 right">
            luogo di nascita: <strong><?php echo $utente['associati'][0]['associati_luogo_nascita']; ?></strong><br />
            data di nascita: <strong><?php echo substr($utente['associati'][0]['associati_data_nascita'], 0, 10); ?></strong><br />
            residente e domiciliato/a: <strong><?php echo $utente['associati'][0]['associati_citta_residenza']; ?></strong><br />
            targa: <strong><?php echo $utente['associati'][0]['associati_targa']; ?></strong>
        </div>
    </div>
    <div class="row" style="margin-top:20px;">
        <div class="col-sm-12 text center">
            <table class="tg">
                <thead>
                    <tr>
                        <th class="tg-7nj3">DATA</th>
                        <th class="tg-7nj3">PERCORSI EFFETTUATI ANDATA E RITORNO</th>

                        <th class="tg-7nj3">KM</th>

                    </tr>
                </thead>
                <tbody>
                    <?php for ($d = 1; $d <= cal_days_in_month(CAL_GREGORIAN, $mese, $anno); $d++) : ?>
                        <?php $in = false; ?>
                        <?php $d_pad = ($d < 10) ? "0$d" : $d;
                        $totalekm_giorno = 0; ?>
                        <tr>
                            <td class="center"><?php echo $d_pad; ?></td>
                            <td>
                                <?php foreach ($rimborsi as $key => $rimborso) : ?>
                                    <?php //debug($rimborso, true); 
                                    ?>
                                    <?php if ($key === 'pie') {
                                        continue;
                                    } ?>
                                    <?php //debug($rimborso,true); debug($anno."-$mese-$d_pad"); 
                                    ?>
                                    <?php if (substr($rimborso['rimborsi_km_data'], 0, 10) == $anno . "-$mese-$d_pad") : ?>
                                        <?php //debug($rimborso,true); 
                                        ?>
                                        <?php $totalekm += $rimborso['rimborsi_km_km'];
                                        $totalekm_giorno += $rimborso['rimborsi_km_km']; ?>
                                        <?php if ($key > 0) : ?><br /><?php endif; ?>
                                        <?php if (!empty($rimborso['sedi_operative_reparto'])) : ?>
                                            <?php echo $rimborso['sedi_operative_reparto']; ?> - <?php echo $rimborso['sedi_operative_indirizzo']; ?> (<?php echo $rimborso['sedi_operative_provincia']; ?>)
                                        <?php elseif (!empty($rimborso['rimborsi_km_domiciliare'])) : ?>
                                            <?php echo $rimborso['domiciliari_nome'] . ' ' . $rimborso['domiciliari_cognome']; ?> - <?php echo $rimborso['domiciliari_domicilio_indirizzo']; ?> (<?php echo $rimborso['domiciliari_domicilio_provincia']; ?>)
                                        <?php else : ?>
                                            <?php echo $rimborso['rimborsi_km_luogo']; ?>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td class="center"><?php echo $totalekm_giorno; ?></td>
                        </tr>
                    <?php endfor; ?>

                    <tr>
                        <td class="no-border"></td>
                        <td class="right no-border">Totale Km</td>
                        <td class="center"><?php echo $totalekm; ?></td>

                    </tr>
                    <tr>
                        <td class="no-border"></td>
                        <td class="right no-border">Costo Km</td>
                        <td class="center"><?php echo $utente['associati'][0]['associati_costo_km']; ?></td>

                    </tr>
                    <tr>
                        <td class="no-border"></td>
                        <td class="right no-border">TOTALE</td>
                        <td class="center"><?php echo number_format($utente['associati'][0]['associati_costo_km'] * $totalekm); ?></td>

                    </tr>

                </tbody>
            </table>


        </div>
    </div>

    <?php
    //Raggruppo i pie di lista per tipo così da sommare tutto...
    $pie = [];
    //debug($rimborsi, true);

    if (!empty($rimborsi['pie'])) {
        foreach ($rimborsi['pie'] as $rimborso) {

            //debug($rimborso,true);
            if (array_key_exists($rimborso['compensi_variazioni_tipo'], $pie)) {
                $pie[$rimborso['compensi_variazioni_tipo']]['count']++;
                $pie[$rimborso['compensi_variazioni_tipo']]['importo'] += $rimborso['compensi_variazioni_importo'];
            } else {
                $pie[$rimborso['compensi_variazioni_tipo']] = [
                    'label' => $rimborso['compensi_variazioni_tipo_value'],
                    'count' => 1,
                    'importo' => $rimborso['compensi_variazioni_importo'],
                ];
            }
        }
    }
    ?>

    <div class="row" style="margin-top:20px;">
        <div class="col-sm-12 text ">
            <table style="border:1px solid black; font-size: 1.2em; ">
                <tbody>
                    <tr>
                        <td class="no-border" width="50%">
                            DISTINTA PER RIMBORSO SPESE PIE' DI LISTA
                        </td>
                        <td class="no-border left" colspan="2">
                            <!--SI&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NO-->
                        </td>

                    </tr>
                    <?php $i = 1;
                    $totale_pie = 0;

                    //debug($pie, true);

                    foreach ($pie as $tipo => $dati) : ?>
                        <tr>
                            <td class="no-border" colspan="2" width="80%">
                                <?php echo $i++; ?>. <?php echo $dati['label']; ?><?php if ($dati['count'] > 1) : ?> x <?php echo $dati['count']; ?><?php endif; ?>
                            </td>
                            <td class="" width="20%">
                                <?php echo $dati['importo'];
                                $totale_pie += $dati['importo']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr>
                        <td class="no-border right" colspan="2" width="80%">
                            TOTALE
                        </td>
                        <td class="" width="20%">
                            <?php echo $totale_pie; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>



    <div class="row" style="margin-top: 40px;">
        <div class="col-sm-12 text">
            <table style="border:1px solid black; font-size: 1.2em; ">
                <tbody>

                    <tr>
                        <td class="no-border" width="80%">
                            Totale documenti allegati N°
                        </td>
                        <td class="" width="20%">

                        </td>
                        <td class="" width="20%">

                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="no-border">&nbsp</td>
                    </tr>
                    <tr>
                        <td class="no-border" width="80%">
                            Totale da rimborsare
                        </td>
                        <td class="" width="20%">EURO</td>
                        <td class="" width="20%">
                            <?php echo number_format($totale_pie + ($utente['associati'][0]['associati_costo_km'] * $totalekm), 2, '.', ','); ?>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
    <div class="row" style="width:1000px;margin-top: 40px;">
        <div class="col-sm-6 center">
            DATA __/__/____
        </div>
        <div class="col-sm-6 center">
            Firma _____________________
        </div>
    </div>
</div>