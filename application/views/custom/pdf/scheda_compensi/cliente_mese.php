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
        <?php echo $cliente['clienti_ragione_sociale']; ?>
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
            <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
                <th class="tg-pbc0">

                    <div class="red">TARIFFA</div>
                </th>

                <th class="tg-7nj3">TOTALE</th>
            <?php endif; ?>
            <th class="tg-slju">CLIENTE</th>
            <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
                <th class="tg-slju">Tot.da fatturare</th>
            <?php endif; ?>
        </tr>
        <tr>
            <td class="tg-031e" colspan="7"><strong>Ore sedi operative</strong></td>
        </tr>
        <?php $categorie = $this->db->where(['sedi_operative_orari_categoria_id <>' => '6'])->get('sedi_operative_orari_categoria')->result_array(); ?>
        <?php
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

        <?php foreach ($report_orari_sedi as $sede_id => $reports_associato) : ?>
            <?php $sede = $this->apilib->view('sedi_operative', $sede_id); ?>
            <?php foreach ($reports_associato as $associato_id => $reports) : ?>
                <?php //if (705 == $associato_id) {debug($reports,true);} 
                ?>
                <?php $associato = $this->apilib->view('associati', $associato_id); ?>
                <?php //debug($associato_id,true); 
                ?>
                <?php foreach ($categorie as $categoria) : ?>



                    <?php // Non affiancamento 
                    ?>
                    <?php $this->load->view('pdf/scheda_compensi/riga_report_cliente', [
                        'sede' => $sede,
                        'affiancamento' => 'f',
                        'costo_differenziato' => 'f',
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
                        'affiancamento' => 't',
                        'costo_differenziato' => 'f',
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
                        'affiancamento' => 'f',
                        'costo_differenziato' => 't',
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
            <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
                <tr>
                    <td><strong>Totale <?php echo $sede['sedi_operative_reparto']; ?></strong></td>
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
        <?php //debug($report_accessi_sedi, true); 
        ?>
        <?php foreach ($report_accessi_sedi as $sede_id => $reports_associato) : ?>
            <?php $sede = $this->apilib->view('sedi_operative', $sede_id); ?>

            <?php foreach ($reports_associato as $associato_id => $reports) : $righe_stampate++; ?>
                <?php $associato = $this->apilib->view('associati', $associato_id); ?>
                <tr>
                    <?php
                    //Calcolo il totale ore
                    $totale_accessi = $totale_euro = 0;
                    foreach ($reports as $report) {
                        if (!array_key_exists('report_orari_accessi', $report)) {
                            debug($report, true);
                        } else {
                        }
                        $totale_accessi += $report['report_orari_accessi'];
                        $totale_euro += $report['tariffa_totale'];
                    }
                    $totalone_euro += round($totale_euro, 2); //round($totale_euro * $associato['associati_percentuale_sedi'] / 100,2);
                    ?>
                    <td class="tg-031e"><?php echo $associato['associati_nome']; ?> <?php echo $associato['associati_cognome']; ?></td>
                    <td class="tg-031e"><?php echo mese_testuale($report['report_orari_inizio']); ?></td>
                    <td class="tg-0ord"><?php echo $totale_accessi; ?></td>

                    <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
                        <td class="tg-0ord"><?php echo round($report['tariffa'], 2); // * $associato['associati_percentuale_sedi'] / 100, 2); 
                                            ?></td>
                        <td class="tg-0ord"><?php echo $totale_euro; // * $associato['associati_percentuale_sedi'] / 100; 
                                            ?></td>
                    <?php endif; ?>

                    <td class="tg-yw4l"><?php echo $sede['clienti_ragione_sociale']; ?> - <?php echo $sede['sedi_operative_reparto']; ?></td>

                    <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
                        <td class="tg-lqy6"><?php echo (int) $report['tariffa']; ?></td>
                    <?php endif; ?>
                </tr>
                <?php if ($righe_stampate >= 15) : ?>
                    <?php $this->load->view('pdf/scheda_compensi/interruzione_di_pagina');
                    $righe_stampate = 0; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>


        <?php if ($this->auth->get('utenti_tipo') != 15) : ?>
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
        "allegati_per_fattura_cliente" => $cliente['clienti_id'],
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