<?php //debug($cartella,true);    
?>
<!DOCTYPE HTML>
<html>

<head>

    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="<?php echo base_url_template('template/crm-v2/assets/global/plugins/bootstrap/css/bootstrap.min.css'); ?>">
    <link href='css/bootstrap.css' rel='stylesheet' type='text/css'>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <style>
        html,
        body,
        p,
        * {
            font-family: 'Open Sans', sans-serif;
            font-size: 13px;
        }

        table {
            width: 100%;
        }

        table thead tr th {
            /*background: #e4e4e4*/

        }

        .dl-horizontal dt {
            overflow: visible;
            text-overflow: none;
            width: auto !important;
        }

        .dl-horizontal dd {
            margin-left: 300px;
        }

        .tg {
            border-collapse: collapse;
            border-spacing: 0;
            margin: 0px auto;
        }

        .tg td {
            font-family: 'Open Sans', sans-serif;

            padding: 10px 5px;
            border-style: solid;
            border-width: 1px;
            overflow: hidden;
            word-break: normal;
        }

        .tg th {
            font-family: 'Open Sans', sans-serif;

            font-weight: normal;
            padding: 6px 3px;
            border-style: solid;
            border-width: 1px;
            overflow: hidden;
            word-break: normal;
        }

        .tg .tg-yw4l {
            vertical-align: top
        }

        .tg {
            border-collapse: collapse;
            border-spacing: 0;
            border-color: black;
            background-color: #e4e4e4;
        }

        .tg td {
            font-family: 'Open Sans', sans-serif;

            padding: 6px 3px;
            border-style: solid;
            border-width: 1px;
            overflow: hidden;
            word-break: normal;
            background-color: #e4e4e4;
        }

        .tg th {
            font-family: 'Open Sans', sans-serif;

            font-weight: normal;
            padding: 6px 3px;
            border-style: solid;
            border-width: 1px;
            overflow: hidden;
            word-break: normal;
            background-color: #e4e4e4;
        }

        .tg .tg-msp3 {
            background-color: #ffce93;
            color: #000000;
            text-align: center;
            vertical-align: top;
        }

        .tg .tg-0ord {
            text-align: right;
            background-color: #e4e4e4;
        }

        .tg .tg-wqtr {
            background-color: #ffffc7;
            color: #000000;
            text-align: center;
            vertical-align: top;
        }

        .tg .tg-lqy6 {
            text-align: right;
            vertical-align: top;
            background-color: #e4e4e4;
        }

        .tg .tg-7nj3 {
            background-color: #ffce93;
            color: #000000;
            text-align: center;
        }

        .tg .tg-pbc0 {
            background-color: #ffce93;
            color: #000000;
            text-align: center;
        }

        .tg .tg-slju {
            background-color: #ffce93;
            color: #000000;
            text-align: center;
            vertical-align: top;
        }

        .tg .tg-yw4l {
            vertical-align: top;
            background-color: #e4e4e4;

        }

        .tg .tg-xr8r {
            background-color: #ffffc7;
            text-align: right;
            vertical-align: top;
        }

        .tg .tg-kjho {
            background-color: #ffffc7;
            vertical-align: top;
        }

        .tg .tg-mejs {
            color: #000000;
            vertical-align: top;
            background-color: #e4e4e4;
        }

        .tg .tg-fl7z {
            color: #000000;
            text-align: right;
            vertical-align: top;
        }

        .red {
            color: red;
        }

        .right {
            text-align: right;

        }

        h2.new_page {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="row">
        <div class="col-sm-6 text ">
            <p style="font-size: 2em;">NOMINATIVO: <?php echo $associato['associati_nome']; ?> <?php echo $associato['associati_cognome']; ?>
                ANNO <?php echo $anno; ?><br>
                Data di entrata <?php echo $associato['associati_inizio_rapporto']; ?><br>
                <?php if ($associato['associati_fine_rapporto']) : ?>Data DI FINE LAVORO <?php echo $associato['associati_fine_rapporto']; ?><?php endif; ?>
            </p>
        </div>

        <div class="right col-sm-6">
            <p>
                Automobile: <?php echo $associato['associati_automobile']; ?>-TARGA: <?php echo $associato['associati_targa']; ?>
            </p><br />
            <?php for ($i = 1; $i <= 3; $i++) : ?>
                <?php if ($associato["associati_note_$i"]) : ?><?php echo $associato["associati_note_$i"]; ?><?php endif; ?><br />
            <?php endfor; ?>

        </div>
    </div>
    <div class="col-sm-12 text">
        <table class="tg">
            <thead>
                <tr>
                    <th>Anno</th>
                    <th>Mese</th>
                    <th>Acconto</th>
                    <th>Rimborsi km</th>
                    <th>Piè di lista</th>
                    <th>Variazioni</th>
                    <th>Importo</th>
                </tr>
            </thead>
            <tbody>
                <?php $totali = []; ?>
                <?php foreach ($pagamenti as $pagamento) :
                    if (!array_filter($pagamento)) {
                        continue;
                    }
                    @$totali['pagamenti_acconto'] += $pagamento['pagamenti_acconto'];
                    @$totali['pagamenti_rimborsi_km'] += $pagamento['pagamenti_rimborsi_km'];
                    @$totali['pagamenti_pie_di_lista'] += $pagamento['pagamenti_pie_di_lista'];
                    @$totali['pagamenti_totale_variazioni'] += $pagamento['pagamenti_totale_variazioni'];
                    @$totali['pagamenti_importo_totale'] += $pagamento['pagamenti_importo_totale'];

                ?>
                    <tr>
                        <td><?php echo $pagamento['pagamenti_anno']; ?></td>
                        <td><?php echo mese_testuale($pagamento['pagamenti_mese']); ?></td>
                        <td><?php echo $pagamento['pagamenti_acconto']; ?></td>
                        <td><?php echo $pagamento['pagamenti_rimborsi_km']; ?></td>
                        <td><?php echo $pagamento['pagamenti_pie_di_lista']; ?></td>
                        <td><?php echo $pagamento['pagamenti_totale_variazioni']; ?></td>
                        <td><?php echo $pagamento['pagamenti_importo_totale']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7"></td>
                </tr>
                <tr style="font-weight: bold;">
                    <td colspan="2" style="text-align: center;">TOTALI</td>

                    <td><?php echo @$totali['pagamenti_acconto']; ?></td>
                    <td><?php echo @$totali['pagamenti_rimborsi_km']; ?></td>
                    <td><?php echo @$totali['pagamenti_pie_di_lista']; ?></td>
                    <td><?php echo @$totali['pagamenti_totale_variazioni']; ?></td>
                    <td><?php echo @$totali['pagamenti_importo_totale']; ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>