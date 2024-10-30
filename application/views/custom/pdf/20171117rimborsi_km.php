<!DOCTYPE HTML>
<html>
    <head>

        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="<?php echo base_url_template('template/crm-v2/assets/global/plugins/bootstrap/css/bootstrap.min.css'); ?>">
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
        <style>
            html, body, p, * {
                font-family: 'Open Sans', sans-serif;
            }
        </style>
    </head>
    <body>
<?php
//$agentiTotal = [
//    5 => 'Manuel Aiello',
//    2 => 'Matteo Puppis',
//];

$associati = $this->apilib->search('associati', [
    "associati_automobile <> '' AND associati_automobile IS NOT NULL",
    "associati_costo_km <> '0' AND associati_costo_km IS NOT NULL",
]);

$anno = date('Y');
$mesi = [
    1 => 'gennaio',
    2 => 'febbraio',
    3 => 'marzo',
    4 => 'aprile',
    5 => 'maggio',
    6 => 'giugno',
    7 => 'luglio',
    8 => 'agosto',
    9 => 'settembre',
    10 => 'ottobre',
    11 => 'novembre',
    12 => 'dicembre',
];

$fullTotalEur = $fullTotalKm = [];

?>
<style>
    
    table {
        margin-top:0px;
        margin-bottom:0px
    }
    
    table * {
        font-size: 10px;
    }
    
    * {
        padding: 1px;
       
    }
    
    h2.new_page {page-break-before:always;}
</style>


<p style="margin-bottom: 20px">
    Anno di riferimento: <strong><?php echo $anno ?></strong><br/>
    <?php foreach ($associati as $associato) : ?>
    Automobile <strong><?php echo $associato['associati_nome']; ?> <?php echo $associato['associati_cognome']; ?></strong>: <?php echo $associato['associati_automobile']; ?>, costo km € <strong><?php echo $associato['associati_costo_km']; ?></strong><br/>
    <?php endforeach; ?>
</p>

<?php foreach($mesi as $mNum => $mNome): ?>
    <h2 class="new_page"></h2>
    <?php
    $rimborsi = $this->db
        ->join('associati', 'associati_utente = rimborsi_km_utente')
        ->join('sedi_operative', 'sedi_operative_id = rimborsi_km_sede_operativa', 'left')
        ->where("DATE_PART('year', rimborsi_km_data) = '{$anno}'")
    	->where("DATE_PART('month', rimborsi_km_data) = '{$mNum}'")
        ->order_by('rimborsi_km_data')->get('rimborsi_km')
        ->result();
    ?>

	<h4 style=""><?php echo ucfirst($mNome) . ' ' . $anno; ?></h4>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th width="60">Data</th>
                <th width="100">Utente</th>
                <th width="280">Cliente</th>
                <th width="150">Luogo</th>
                <th width="50">Km</th>
                <th width="50">Costo</th>
            </tr>
        </thead>
        <tbody>
            <?php $totKm = $totEu = []; ?>
            <?php foreach($rimborsi as $rkm): ?>
            	<?php
				@$totKm[$rkm->rimborsi_km_utente] += $rkm->rimborsi_km_km;
				@$totEu[$rkm->rimborsi_km_utente] += $rkm->rimborsi_km_costo_viaggio;
            	?>
                <tr>
                    <td><?php echo dateFormat($rkm->rimborsi_km_data) ?></td>
                    <td><?php echo $rkm->associati_nome . ' ' . $rkm->associati_cognome ?></td>
                    <td><?php echo $rkm->sedi_operative_reparto ?></td>
                    <td><?php echo ucfirst($rkm->sedi_operative_citta) ?></td>
                    <td><?php echo $rkm->rimborsi_km_km ?> km</td>
                    <td><?php echo $rkm->rimborsi_km_costo_viaggio ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
        	<tr>
                <td colspan="6">
                    <?php foreach ($associati as $associato): $id = $associato['associati_utente']; ?>
                    	<?php
                        @$fullTotalKm[$rkm->rimborsi_km_utente] += isset($totKm[$id])? $totKm[$id]: 0;
                        @$fullTotalEur[$rkm->rimborsi_km_utente] += isset($totEu[$id])? $totEu[$id]: 0;
                    	?>
                    	<strong><?php echo $associato['associati_nome'] ?> <?php echo $associato['associati_cognome'] ?></strong> - 
                    	tot. km: <strong><?php echo isset($totKm[$id])? $totKm[$id]: 0; ?> km</strong> -
                    	tot. rimborso: <strong><?php echo isset($totEu[$id])? $totEu[$id]: 0; ?> €</strong>
                    	<br/>
                    <?php endforeach; ?>
                </td>
            </tr>
        </tfoot>
    </table>

<?php endforeach; ?>

<hr>
<h4 style="margin:20px 0 5px">Totali</h4>
<?php $grandTotalKm = $grandTotalEur = 0; ?>
<?php foreach ($associati as $associato): $id = $associato['associati_utente']; ?>
	<?php
    $grandTotalKm  += isset($fullTotalKm[$id])?  $fullTotalKm[$id]:  0;
	$grandTotalEur += isset($fullTotalEur[$id])? $fullTotalEur[$id]: 0;
	?>
    <strong><?php echo $associato['associati_nome'] ?> <?php echo $associato['associati_cognome'] ?></strong> - 
    tot. km: <strong><?php echo isset($fullTotalKm[$id])? $fullTotalKm[$id]: 0; ?> km</strong> -
    tot. rimborso: <strong><?php echo isset($fullTotalEur[$id])? $fullTotalEur[$id]: 0; ?> €</strong>
	<br/>
<?php endforeach; ?>
<br/>
<strong>Totale</strong> - 
tot. km: <strong><?php echo $grandTotalKm; ?> km</strong> -
tot. rimborso: <strong><?php echo $grandTotalEur; ?> €</strong>
</body>
</html>