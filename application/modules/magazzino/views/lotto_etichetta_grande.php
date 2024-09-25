<?php
$barcodes = json_decode($articolo['fw_products_barcode'], true);

$array = array_values($barcodes);
$barcode = array_shift($array);

?>

<style>
@page {
    size: auto;
    margin: 0;
}
body {
    font-family: Arial, sans-serif;
    padding: 0;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    text-transform: uppercase;
    margin: 0 5mm;
    
}
.label-container {
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-sizing: border-box;
}
.product-code, .lot-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1em;
    margin: 5mm 0;
}
.product-name {
    font-size: 1em;
    font-weight: bold;
    text-align: center;
    margin: 10mm 0;
}
.additional-info {
    display: flex;
    justify-content: space-between;
    font-size: 1em;
    margin-top: 5mm;
}
.barcode-img {
    height: 60px;
    width: auto;
    margin-left: 1em;
}
@media print {
    body {
        /*font-size: 12pt;*/
    }
    .label-container {
        height: auto;
        min-height: 0;
    }
}
</style>

<body onload="loadHandler()">
    <div class="label-container">
        <div class="product-code">
            <span><?php echo $articolo['movimenti_articoli_codice'] ?></span>
            <svg class="barcode-img product-barcode"
                 jsbarcode-height="50"
                 jsbarcode-format="ean13"
                 jsbarcode-value="<?php echo $barcode ?>"
                 jsbarcode-textmargin="0"
                 jsbarcode-fontoptions="bold"></svg>
        </div>
        <div class="product-name">
            <?php echo $articolo['movimenti_articoli_name'] ?>
        </div>
        <div class="lot-info">
            <span>Lotto: <?php echo $articolo['movimenti_articoli_lotto'] ?></span>
            <svg class="barcode-img lotto-barcode"
                 jsbarcode-height="50"
                 jsbarcode-value="<?php echo $articolo['movimenti_articoli_lotto'] ?>"
                 jsbarcode-textmargin="0"
                 jsbarcode-fontoptions="bold"></svg>
        </div>
        <div class="additional-info">
            <span>del: <?php echo (!empty($articolo['movimenti_articoli_data_scadenza'])) ? dateFormat($articolo['movimenti_articoli_data_scadenza'], 'd/m/Y') : '-' ?></span>
            <span><?php echo $qta_um ?? '-' ?></span>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode(".barcode-img").init();

        function loadHandler() {
            window.print();
        }
    </script>
</body>
