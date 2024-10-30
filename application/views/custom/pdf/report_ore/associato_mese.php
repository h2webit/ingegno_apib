<?php
$workingDays = [1, 2, 3, 4, 5, 6]; # date format = N (1 = Monday, for more information see )
$holidayDays = HOLIDAYS; /*[
    '12-25', //Natale
    '01-01', //Capodanno
    '01-06', //Epifania
    '04-25', //Liberazione
    '05-01', //Festa dei lavoratori
    '06-02', //Festa della repubblica
    '08-15', //Ferragosto
    '11-01', //Tutti i santi
    '12-08', //Immacolata
    '12-26', //Santo stefano
    '04-02', //Pasqua
]; # variable and fixed holidays*/
?>

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

<!-- <div style="clear: both;    display: block;    border :1px solid transparent;    page-break-after: always;"> -->
<div style="border :1px solid transparent;">
    <div class="col-sm-6 text ">
        <p>
            STUDIO INFERMIERISTICO BOSCO DANIELA E ASSOCIATI ( A.P.I.B.)<br />
            Via Paolo Fabbri, 1/2 40138 Bologna<br />
            <strong>Tel. 051-272603 FAX 051/9911961 Cell.335-383498 </strong><br />
            E-mail:info@apibinfermieribologna.com
        </p>
        <p>
            Scheda di <?php echo $associato['associati_nome']; ?> <?php echo $associato['associati_cognome']; ?>. Mese <?php echo $mese; ?>. Anno <?php echo $anno; ?>.
        </p>
    </div>

    <div class="col-sm-6 right">
        <p>
            Prestazioni libero professionali svolte presso la sede operativa di : <?php echo $sede['clienti_ragione_sociale']; ?> - <?php echo $sede['sedi_operative_reparto']; ?>
        </p>
    </div>
    <div class="col-sm-12 text">
        <table class="tg">
            <thead>
                <tr>
                    <th class="tg-7nj3">DATA</th>
                    <th class="tg-7nj3">PERMANENZA</th>

                    <th class="tg-7nj3">ORE M</th>
                    <th class="tg-pbc0">ORE P</th>
                    <th class="tg-7nj3">ORE N/F</th>

                    <th class="tg-slju">ORE M AFF</th>
                    <th class="tg-slju">ORE P AFF</th>
                    <th class="tg-slju">ORE N/F AFF</th>

                    <th class="tg-slju">ORE M COSTO DIFF.</th>
                    <th class="tg-slju">ORE P COSTO DIFF.</th>
                    <th class="tg-slju">ORE N/F COSTO DIFF.</th>

                    <th class="tg-slju">Accesso</th>
                    <th class="tg-slju">Note</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($d = 1; $d <= cal_days_in_month(CAL_GREGORIAN, $mese_numero, $anno); $d++) : ?>
                    <?php
                    if (!in_array(date('w', strtotime("$anno-$mese_numero-$d")), $workingDays) || in_array(date('m-d', strtotime("$anno-$mese_numero-$d")), $holidayDays)) {
                        $bad_days = ' festivita';
                    } else {
                        $bad_days = '';
                    }
                    ?>
                    <tr>
                        <td class="<?php echo $bad_days; ?>"><?php echo $d; ?></td>
                        <td>
                            <?php foreach (array_merge(@(array)$report_ore[$d]['reports'], @(array)$report_accessi[$d]['reports']) as $report) : ?>
                                <span><?php echo substr($report['report_orari_inizio'], 11, 5); ?> - <?php echo substr($report['report_orari_fine'], 11, 5); ?></span><br />
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo @$report_ore[$d][1]['NORMALE']; ?></td>
                        <td><?php echo @$report_ore[$d][2]['NORMALE']; ?></td>
                        <td><?php echo @$report_ore[$d][3]['NORMALE']; ?></td>

                        <td><?php echo @$report_ore[$d][1]['AFF']; ?></td>
                        <td><?php echo @$report_ore[$d][2]['AFF']; ?></td>
                        <td><?php echo @$report_ore[$d][3]['AFF']; ?></td>

                        <td><?php echo @$report_ore[$d][1]['DIFF']; ?></td>
                        <td><?php echo @$report_ore[$d][2]['DIFF']; ?></td>
                        <td><?php echo @$report_ore[$d][3]['DIFF']; ?></td>

                        <td><?php echo @$report_accessi[$d]; ?></td>
                        <td>
                            <?php foreach (array_merge(@(array)$report_ore[$d]['reports'], @(array)$report_accessi[$d]['reports']) as $report) : ?>
                                <span><?php echo $report['report_orari_note']; ?></span><br />
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endfor; ?>



            </tbody>
        </table>

        <div class="col-sm-6 left">
            <br /> <br />

            <table style="width:400px;">
                <tr>
                    <td>Totale ore M</td>
                    <td><strong><?php echo @number_format($totali[1]['NORMALE'], 2); ?></strong></td>

                    <td>Totale ore M Aff / Costo Diff.</td>
                    <td><strong><?php echo @number_format($totali[1]['AFF'], 2); ?> / <?php echo @number_format($totali[1]['DIFF'], 2); ?></strong></td>
                </tr>

                <tr>
                    <td>Totale ore P</td>
                    <td><strong><?php echo @number_format($totali[2]['NORMALE'], 2); ?></strong></td>

                    <td>Totale ore P Aff / Costo Diff.</td>
                    <td><strong><?php echo @number_format($totali[2]['AFF'], 2); ?> / <?php echo @number_format($totali[2]['DIFF'], 2); ?></strong></td>
                </tr>

                <tr>
                    <td>Totale ore N/F</td>
                    <td><strong><?php echo @number_format($totali[3]['NORMALE'], 2); ?></strong></td>

                    <td>Totale ore N/F Aff / Costo Diff.</td>
                    <td><strong><?php echo @number_format($totali[3]['AFF'], 2); ?> / <?php echo @number_format($totali[3]['DIFF'], 2); ?></strong></td>
                </tr>

                <tr>
                    <td colspan="4">
                        <hr style="margin:4px 0px;padding:0px;" />
                    </td>
                </tr>

                <tr>
                    <td>Totale:</td>
                    <td><strong><?php echo $totali_noaff; ?></strong></td>

                    <td>Totale Aff / Totale Costo Diff.:</td>
                    <td><strong><?php echo $totali_aff; ?></strong> / <strong><?php echo $totali_costo_diff; ?></strong></td>
                </tr>

                <tr>
                    <td>Totale accessi:</td>
                    <td colspan="3"><strong><?php echo @$totali_accessi; ?></strong></td>
                </tr>

            </table>
        </div>
        <div class="col-sm-6 right">
            <br /> <br /><br /> <br />
            Firma responsabile del servizio<br /><br /> <br />
            _____________________________________________
        </div>
    </div>

</div>