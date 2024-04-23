<?php
$woocommerce = $this->apilib->searchFirst('woocommerce');

if (empty($woocommerce['woocommerce_consumer_key']) || empty($woocommerce['woocommerce_consumer_secret']) || empty($woocommerce['woocommerce_endpoint'])) {
    echo '<div class="alert alert-danger"><h3>ATTENZIONE: IMPOSTAZIONI WOOCOMMERCE NON CONFIGURATE</h3></div>';
    return;
}
?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#woocommerce_import_single" data-toggle="tab">Singolo</a></li>
                <li><a href="#woocommerce_import_bulk" data-toggle="tab">Bulk</a></li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane active" id="woocommerce_import_single">
                    <div class="input-group">
                        <input type="text" class="form-control" id="product_id" placeholder="1234567890" name="product_id">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary btn_import_single"><?php e('Import this product'); ?></button>
                        </span>
                    </div>
                    <hr/>
                    
                    <h3><?php e('Help - Where is the Product Id?'); ?></h3>
                    <p><?php e('You can find the <b>Product ID</b> by going on your WordPress admin area, <b>click on Products</b>, and you\'ll see list of all products.<br />If you hover with mouse (without click) on one product listed, you\'ll see a voice "<code>ID: *number*</code>"... This is your Product ID'); ?></p>
                </div>
                
                <div class="tab-pane" id="woocommerce_import_bulk">
                    <div class="form-bulk-import form-inline">
                        <div class="form-group">
                            <label for="da_pag">Da pag.</label>
                            <input type="number" min="1" max="100" required class="form-control" id="da_pag" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="a_pag">A pag.</label>
                            <input type="number" min="1" max="100" required class="form-control" id="a_pag" value="5">
                        </div>
                        
                        <div class="form-group">
                            <label for="n_per_pag">N. per pag.</label>
                            <input type="number" min="1" max="100" required class="form-control" id="n_per_pag" value="20">
                        </div>
                        
                        <button type="submit" class="btn btn-info btn-bulk-import">Importa</button>
                    </div>
                    
                    <hr/>
                    
                    <h3>Guida</h3>
                    <p>
                        Si può personalizzare l'import di bulk scegliendo:<br/>
                        - Pagina di inizio (<kbd>Da pag.</kbd>; Min 1 - Max 100)<br/>
                        - Pagina di fine (<kbd>A pag.</kbd>; Min 1 - Max 100)<br/>
                        - Numero di prodotti per pagina da importare (<kbd>N. per pag.</kbd>; Min 1 - Max 100)
                        <br/><br/>
                        Come funziona?<br/><br/>
                        Configurando i tre parametri, si potranno importare più o meno prodotti.<br/>
                        Da questo dipenderà anche la lentezza di importazione, il minimo selezionabile, per tutti e 3 i parametri è il valore <kbd>1</kbd>, mentre il massimo è <kbd>100</kbd>.<br/>
                        La configurazione migliore è più pagine e meno prodotti per pagina (ad esempio come i valori predefiniti, in questo modo l'importatore avrà meno consumo di risorse.<br/>
                        Naturalmente se i prodotti saranno minori del numero per pagina e si è selezionato più pagine di quante ne esistano effettivamente, l'importer intelligentemente lo capirà e non farà richieste a vuoto.<br/>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.btn_import_single').on('click', function (e) {
            var product_id = $('#product_id').val();
            var all_ok = true;

            if (product_id.length == 0 || !$.isNumeric(product_id)) {
                alert("Product ID not provided or is not numeric");
                all_ok = false;
                e.preventDefault();
                e.stopPropagation();
            }

            if (all_ok) {
                var this_btn = $(this);

                this_btn.prop('disabled', true);
                $('.btn_import_all').prop('disabled', true);

                if ($('.js_import_alert').length > 0) {
                    $('.js_import_alert').remove();
                }

                window.open(base_url + 'products_manager/woocommerce/importproducts/?product_id=' + product_id, '_blank');

                // $.ajax({
                //     url: ,
                //     success: function(data) {
                //         $('.alert_zone').after().append('<div class="callout callout-info js_import_alert">Import from Woocommerce started</div>');
                //         $('.log_output').text(data).show();
                //         this_btn.prop('disabled', false);
                //         $('.btn_import_all').prop('disabled', false);
                //     },
                //     error: function() {
                //         this_btn.prop('disabled', false);
                //         $('.btn_import_all').prop('disabled', false);
                //     }
                // });
            }
        });

        $('.btn-bulk-import').on('click', function () {
            var this_btn = $(this);

            var form_bulk_import = $('.form-bulk-import');
            
            var da_pag = $('#da_pag', form_bulk_import).val();
            var a_pag = $('#a_pag', form_bulk_import).val();
            var n_per_pag = $('#n_per_pag', form_bulk_import).val();
            
            if (!da_pag || !a_pag || !n_per_pag) {
                return false;
            }
            
            if (a_pag < da_pag) {
                a_pag = da_pag;
            }

            window.open(base_url + 'products_manager/woocommerce/importproducts/' + a_pag + '/' + da_pag + '/' + n_per_pag, '_blank');
        });
    });
</script>
