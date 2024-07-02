<?php
$settings = $this->apilib->searchFirst('settings');
$logo = base_url('uploads/' . $settings['settings_company_logo']);

$project = $this->apilib->view('projects',$qrcode);
$project_code = $project['projects_code'];
?>

<style>
    @page {
        margin: 0;
        padding: 0;
        size: <?php echo "{$w}mm {$h}mm landscape" ?>;
    }
    
    * {
        margin: 0;
        padding: 0;
    }

    .barcode_ct {
        padding: 0 !important;

        width:<?php echo $w; ?>mm;
        height:<?php echo $h; ?>mm;
        margin-top:<?php echo $top; ?>mm;
        margin-left:<?php echo $left; ?>mm;
    
        text-align: center;
    }
    
    .barcode_ct > img {
        width: 80%;
    }
    
    .barcode_ct > .label {
        padding: 0 !important;
        margin: 0 !important;
        font-size: 11px;
        font-family: Arial, sans-serif;
        text-align: center !important;
        width:<?php echo $w; ?>mm;
        display: inline-block;
    }
</style>

<div class="barcode_ct">
    <span class="label"><img style="width:70%;" src="<?php echo $logo ?>"></span>
    <span class="label">#: <?php echo $project['projects_code']; ?></span>
    <span class="label">Commessa: <?php echo $project['projects_name']; ?></span>
    <span class="label">Cliente: <?php echo $project['customers_full_name']; ?></span>

</div>



<script>
   window.print();
</script>