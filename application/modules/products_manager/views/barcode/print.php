<?php
$generator = new Picqer\Barcode\BarcodeGeneratorPNG();
$barcode_img = $generator->getBarcode($ean, $type);
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
        margin-top:<?php echo $top ?? "0"; ?>mm;
        margin-left:<?php echo $left ?? "0"; ?>mm;
        margin-right:<?php echo $right ?? "0"; ?>mm;
        margin-bottom:<?php echo $bottom ?? "0"; ?>mm;


        text-align: center;
        float:left;

        font-size:<?php echo $font_size_ct ?? '1.2rem'; ?>;
    }

    .barcode_ct > img {
        width: 100%;
    }

    .barcode_ct > .label {
        padding: 0 !important;
        margin: 0 !important;
        font-size: <?php echo $font_size_label ?? '12px'; ?>;
        font-family: Arial, sans-serif;
        text-align: center !important;
    }
</style>

<div class="barcode_ct">
    <span class="label"><?php echo ($show_product_name) ? $product_name.'<br/>' : null ?></span>
    <img src="data:image/png;base64,<?php echo base64_encode($barcode_img); ?>" />
    <span class="label"><?php echo ($show_ean) ? '<br/>'. $ean : null ?></span>
    <span class="label"><?php echo (!empty($notes)) ? '<br/>'.$notes : null ?></span>

    <?php if(false && isset($show_divider) && $show_divider == true): ?>
        <hr style="margin-top: 5px; margin-bottom: 5px;"/>
    <?php endif; ?>
</div>

<?php if(!isset($show_print_dialog) || $show_print_dialog == true): ?>
    <script>
        window.print();
    </script>
<?php endif; ?>
