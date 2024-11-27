<style>
    table {
        page-break-after: auto;
        page-break-inside: auto
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto
    }

    td {
        page-break-inside: avoid;
        page-break-after: auto
    }

    thead {
        /* display: table-row-group; */
    }

    tfoot {
        /* display: table-footer-group */
    }
    
    .right {
        float: right;
    }
</style>
<div class="col-md-4" style="font-size:0.8em;">
    <strong>A.P.I.B. S.R.L. S.T.P.</strong><br />
    Via Paolo Fabbri 1/2 - 40138 Bologna<br />
    Tel. 051/272603 / Cell. 335383498 / Fax. 051/9911961<br />
    <br />
    C.F. e P.I. 04120871209<br />
    Email - info@apibstp.com
    <br />
    Referente:
</div>

<div class="right">
    <p>
        Spett.le<br />
        <?php echo $cliente['customers_full_name']; ?>
    </p>

    <p>
        <strong style="font-size:2em;"><?php echo $mese; ?></strong>
    </p>

</div>
<div class="col-sm-12 text">
    <table class="tg">
        <tr>
            <th class="tg-7nj3">NOMINATIVO</th>
            <th class="tg-7nj3">PERIODO MESE LAVORATO</th>
            <th class="tg-7nj3">ORE TOT O NUM. PRESTAZIONI</th>
            <?php if ($this->auth->get('users_type') != 15) : ?>
                <th class="tg-pbc0">

                    <div class="red">TARIFFA</div>
                </th>

                <th class="tg-7nj3">TOTALE</th>
            <?php endif; ?>
            <th class="tg-slju">CLIENTE</th>
            <?php if ($this->auth->get('users_type') != 15) : ?>
                <th class="tg-slju">Tot.da fatturare</th>
            <?php endif; ?>
        </tr>
        <tr>
            <td class="tg-031e" colspan="7"><strong>Ore sedi operative</strong></td>
        </tr>
        <?php
        $categorie = $this->db->where(['sedi_operative_orari_categoria_id <>' => '6'])->get('sedi_operative_orari_categoria')->result_array();
        global $totalone_euro, $totale_sedi, $righe_stampate, $dati_grezzi;

        $totalone_euro = 0;
        $totale_sedi = [];
        $righe_stampate = 0;





        ?>
        <!--<div>
            TOTALI


            Totale ore (notte/festivi) / tariffa / importo.
            Totale ore (mattina ) / tariffa / importo.
            Totale ore (pomeriggio)/ tariffa / importo.
        </div>-->

        <?php foreach ($rapportini_sedi as $sede_id => $reports_associato) : ?>
            <?php $sede = $this->apilib->view('customers_shipping_address', $sede_id); ?>
            <?php foreach ($reports_associato as $associato_id => $reports) : ?>
                <?php //if (705 == $associato_id) {debug($reports,true);} 
                ?>
                <?php $associato = $this->apilib->view('dipendenti', $associato_id); ?>
                <?php foreach ($categorie as $categoria) : ?>



                    <?php // Non affiancamento 
                    ?>
                    <?php $this->load->view('pdf/scheda_compensi/riga_report_cliente', [
                        'sede' => $sede,
                        'affiancamento' => '0',
                        'costo_differenziato' => '0',
                        'categoria' => $categoria,
                        'totalone_euro' => $totalone_euro,
                        'reports' => $reports, 'sede_id' => $sede_id,
                        'associato' => $associato
                    ]); ?>



                    <?php if ($righe_stampate >= 15) : ?>
                        <?php $this->load->view('pdf/scheda_compensi/interruzione_di_pagina');
                        $righe_stampate = 0; ?>
                    <?php endif; ?>

                    <?php // Affiancamenti 
                    ?>
                    <?php $this->load->view('pdf/scheda_compensi/riga_report_cliente', [
                        'sede' => $sede,
                        'affiancamento' => '1',
                        'costo_differenziato' => '0',
                        'categoria' => $categoria,
                        'totalone_euro' => $totalone_euro,
                        'reports' => $reports,
                        'sede_id' => $sede_id,
                        'associato' => $associato
                    ]); ?>

                    <?php if ($righe_stampate >= 15) : ?>
                        <?php $this->load->view('pdf/scheda_compensi/interruzione_di_pagina');
                        $righe_stampate = 0; ?>
                    <?php endif; ?>

                    <?php // Costi differenziati 
                    ?>
                    <?php $this->load->view('pdf/scheda_compensi/riga_report_cliente', [
                        'sede' => $sede,
                        'affiancamento' => '0',
                        'costo_differenziato' => '1',
                        'categoria' => $categoria,
                        'totalone_euro' => $totalone_euro,
                        'reports' => $reports,
                        'sede_id' => $sede_id,
                        'associato' => $associato
                    ]); ?>


                    <?php if ($righe_stampate >= 15) : ?>
                        <?php $this->load->view('pdf/scheda_compensi/interruzione_di_pagina');
                        $righe_stampate = 0; ?>
                    <?php endif; ?>




                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if ($this->auth->get('users_type') != 15) : ?>
                <tr>
                    <td><strong>Totale <?php echo $sede['customers_shipping_address_name']; ?></strong></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><strong><?php echo (!empty($totale_sedi[$sede_id])) ? $totale_sedi[$sede_id] : 0; ?></strong></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td class="tg-031e" colspan="7"><strong>Accessi sedi operative</strong></td>

        </tr>
        <?php //debug($rapportini_sedi, true); 
        ?>
        <?php foreach ($rapportini_sedi_accessi as $sede_id => $reports_associato) : ?>
            <?php $sede = $this->apilib->view('customers_shipping_address', $sede_id); ?>

            <?php foreach ($reports_associato as $associato_id => $reports) : $righe_stampate++; ?>
                <?php $associato = $this->apilib->view('dipendenti', $associato_id); ?>
                <tr>
                    <?php
                    //Calcolo il totale ore
                    $totale_accessi = $totale_euro = 0;
                    foreach ($reports as $report) {
                        if (!array_key_exists('rapportini_accessi', $report)) {
                            debug($report, true);
                        } else {
                        }
                        $totale_accessi += $report['rapportini_accessi'];
                        $totale_euro += $report['tariffa_totale'];
                        
                        $report['rapportini_inizio'] = dateFormat($report['rapportini_data'], 'Y-m-d') . ' ' . dateTimeFormat($report['rapportini_ora_inizio'], 'H:i:00');
                        $report['rapportini_fine'] = dateFormat($report['rapportini_data'], 'Y-m-d') . ' ' . dateTimeFormat($report['rapportini_ora_fine'], 'H:i:00');
                    }
                    $totalone_euro += round($totale_euro, 2); //round($totale_euro * $associato['dipendenti_percentuale_sedi'] / 100,2);
                    ?>
                    <td class="tg-031e"><?php echo $associato['dipendenti_nome']; ?> <?php echo $associato['dipendenti_cognome']; ?></td>
                    <td class="tg-031e"><?php echo mese_testuale($report['rapportini_inizio']); ?></td>
                    <td class="tg-0ord"><?php echo $totale_accessi; ?></td>

                    <?php if ($this->auth->get('users_type') != 15) : ?>
                        <td class="tg-0ord"><?php echo round($report['tariffa'], 2); // * $associato['dipendenti_percentuale_sedi'] / 100, 2); 
                                            ?></td>
                        <td class="tg-0ord"><?php echo $totale_euro; // * $associato['dipendenti_percentuale_sedi'] / 100; 
                                            ?></td>
                    <?php endif; ?>

                    <td class="tg-yw4l"><?php echo $sede['customers_full_name']; ?> - <?php echo $sede['customers_shipping_address_name']; ?></td>

                    <?php if ($this->auth->get('users_type') != 15) : ?>
                        <td class="tg-lqy6"><?php echo (int) $report['tariffa']; ?></td>
                    <?php endif; ?>
                </tr>
                <?php if ($righe_stampate >= 15) : ?>
                    <?php $this->load->view('pdf/scheda_compensi/interruzione_di_pagina');
                    $righe_stampate = 0; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>


        <?php if ($this->auth->get('users_type') != 15) : ?>
            <tr>
                <td class="tg-yw4l" colspan="5"></td>
                <td class="tg-yw4l">TOTALE</td>
                <td class="tg-yw4l"><?php echo $totalone_euro; ?></td>
            </tr>


            <tr>
                <td class="tg-yw4l" colspan="5"></td>
                <td class="tg-yw4l"><strong>IMPORTO BONIFICO</strong></td>
                <td class="tg-yw4l"><strong><?php echo $totalone_euro; ?></strong></td>
            </tr>
        <?php endif; ?>


    </table>
    <br /><br />

    <table style="width: 50%;">
        <thead>
            <tr>
                <th>Sede</th>
                <th>Categoria</th>
                <th>Ore</th>
                <th>Tariffa</th>
                <th>Totale euro</th>

            </tr>
        </thead>
        <tbody>
            <?php foreach (@(array) $dati_grezzi['totali_ore_sede'] as $sede => $_totali) : ?>
                <?php foreach ($_totali as  $categoria => $_totali_sede) : ?>


                    <tr>
                        <td><?php echo $sede; ?></td>
                        <td><?php echo $categoria; ?></td>
                        <td><?php echo $_totali_sede['ore']; ?></td>
                        <td><?php echo $_totali_sede['tariffa']; ?></td>
                        <td><?php echo $_totali_sede['euro']; ?></td>
                    </tr>

                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    //debug($mese_numero,true);
    $note_per_fattura = $this->apilib->search('allegati_per_fattura', [
        "allegati_per_fattura_cliente" => $cliente['customers_id'],
        'allegati_per_fattura_mese' => $mese_numero,
        'allegati_per_fattura_anno' => $anno
    ]);
    //debug($note_per_fattura);
    foreach ($note_per_fattura as $nota) {
    ?>
        <br />
        <p class="note_per_fattura"><?php echo $nota['allegati_per_fattura_note']; ?></p>
    <?php
    }
    ?>
</div>
