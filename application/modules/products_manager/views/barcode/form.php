<?php
$product = $this->apilib->view('fw_products', $value_id);
$fw_setting = $this->apilib->searchFirst('fw_products_settings');

$generator = new Picqer\Barcode\BarcodeGeneratorPNG();

$type = $generator::TYPE_EAN_13;

$barcodes = json_decode($product['fw_products_barcode'], true);

if (!is_array($barcodes)) {
    $barcodes = [$barcodes];
}

$barcodes = array_filter($barcodes);

if (!empty($barcodes)):
?>

<style>
    .btn-view {
        color: #000;
        text-align: center !important;
        background-color: #f5f5f5 !important;
    }
    
    .btn-active {
        color: #fff !important;
        background-color: #337ab7 !important;
        border-color: #337ab7 !important;
    }
    
    .font-bold {
        font-weight: bold;
    }
    
    .label-container {
        border: 2px solid black;
        padding: 5px;
        margin: 5px;
        border-radius: 2px;
        background-color: #f5f5f5;
        margin-left: 30%;
        margin-right: 30%;
    }
</style>

<div class="js_barcode_grid text-center">
    <h3 class="text-info font-bold"><?php e("Click on a barcode to print its label"); ?></h3>
    <hr>
    
    <div class='list-group'>
        <?php foreach ($barcodes as $barcode) : ?>
            <button type="button" class="list-group-item btn-view" data-barcode="<?php echo $barcode; ?>" data-barcode_b64="<?php echo base64_encode($barcode); ?>" data-barcode_img="data:image/png;base64,<?php echo base64_encode($generator->getBarcode($barcode, $type)); ?>">
                <?php echo $barcode; ?>
            </button>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="callout callout-info"><?php e('No barcode provided.') ?></div>
<?php endif; ?>
    
    <div class="text-center js_barcode_container hide" data-type="<?php echo $type; ?>" data-value="" data-url="products_manager/barcode/print/">
        <div class="label-container">
            <input type="hidden" name="product_name" value="<?php echo base64_encode(character_limiter($product['fw_products_name'], 15)); ?>">
            <strong class="product_name"><?php echo character_limiter($product['fw_products_name'], 15); ?></strong>
            
            <img class="barcode_img" />
            <br/>
    
            <small class="barcode_label"></small>
        </div>
    
        <hr>
        
        <div class="row">
            <div class="col-md-12 ">
                <?php e("Measurement of the print area") ?>:
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Width') ?>:</label>
                    <input class="form_control" type="text" name="w" size="3" <?php echo (!empty($fw_setting['fw_products_settings_label_default_width'])) ? 'value="'.$fw_setting['fw_products_settings_label_default_width'].'"' : ''; ?> /> mm
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Height'); ?>:</label>
                    <input class="form_control" type="text" name="h" size="3" <?php echo (!empty($fw_setting['fw_products_settings_label_default_height'])) ? 'value="'.$fw_setting['fw_products_settings_label_default_height'].'"' : ''; ?> /> mm
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Left margin'); ?>:</label>
                    <input class="form_control" type="text" name="left" size="3" <?php echo (!empty($fw_setting['fw_products_settings_label_default_left_margin'])) ? 'value="'.$fw_setting['fw_products_settings_label_default_left_margin'].'"' : ''; ?> /> mm
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Top margin'); ?>:</label>
                    <input class="form_control" type="text" name="top" size="3" <?php echo (!empty($fw_setting['fw_products_settings_label_default_top_margin'])) ? 'value="'.$fw_setting['fw_products_settings_label_default_top_margin'].'"' : ''; ?> /> mm
                </div>
            </div>
            
            <div class="col-sm-12">
                <div class="form-group">
                    <label><?php e('Notes') ?>:</label>
                    <input type="text" class="form-control" name="notes" id="notes">
                </div>
            </div>
            
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php e("Show Product Name"); ?></label>
    
                    <div class="radio">
                        <label class="radio-inline">
                            <input type="radio" name="show_product_name" id="show_product_name_true" value="1" checked>
                            <?php e('Yes') ?>
                        </label>
                        
                        <label class="radio-inline">
                            <input type="radio" name="show_product_name" id="show_product_name_false" value="0">
                            No
                        </label>
                    </div>
                </div>
            </div>
    
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php e("Show EAN"); ?></label>
            
                    <div class="radio">
                        <label class="radio-inline">
                            <input type="radio" name="show_ean" id="show_ean_true" value="1" checked>
                            <?php e('Yes') ?>
                        </label>
                        
                        <label class="radio-inline">
                            <input type="radio" name="show_ean" id="show_ean_false" value="0">
                            No
                        </label>
                    </div>
                </div>
            </div>
    
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="save_settings">
                        <input type="checkbox" name="save_settings" id="save_settings">
                        <?php e("Save your print settings for the future"); ?>
                    </label>
                </div>
            </div>
            
            <button class="btn bg-navy btn-lg btn-print" type="button"><?php e('Print'); ?> <i class="fas fa-print"></i></button>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('.btn-view').on('click', function () {
            var barcode_ct = $('.js_barcode_container');
            
            barcode_ct.find('img.barcode_img').prop('src', '');
            barcode_ct.find('small.barcode_label').text('');
            barcode_ct.prop('data-ean', '');
            
            var btn = $(this);
    
            $('.btn-view').removeClass('btn-active');
            
            btn.addClass('btn-active')
            
            var barcode = btn.data('barcode');
            var barcode_b64 = btn.data('barcode_b64');
            var barcode_img = btn.data('barcode_img');
            
            
            barcode_ct.find('img.barcode_img').prop('src', barcode_img);
            barcode_ct.find('small.barcode_label').text(barcode);
            barcode_ct.attr('data-ean', barcode_b64);
            
            barcode_ct.removeClass('hide');
            
            $('#save_settings').on('change', function() {
                var checkbox = $(this);
                var container = checkbox.closest('.js_barcode_container');
                
                if (checkbox.is(':checked')) {
                    var w = $('[name="w"]', container).val();
                    var h = $('[name="h"]', container).val();
    
                    var left = $('[name="left"]', container).val();
                    var top = $('[name="top"]', container).val();
                    
                    $.ajax({
                        url: base_url + 'products_manager/barcode/save_settings',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            [token_name]: token_hash,
                            fw_products_settings_label_default_width: w,
                            fw_products_settings_label_default_height: h,
                            fw_products_settings_label_default_left_margin: left,
                            fw_products_settings_label_default_top_margin: top,
                        },
                        success: function(res) {
                            handleSuccess(res);
                        },
                        error: function (status, request, error) {
                            handleSuccess(error);
                        }
                    });
                }
            });
            
            $('.btn-print').on('click', function () {
                var container = $(this).closest('.js_barcode_container');
                
                var url = base_url + container.data('url');
                
                var w = $('[name="w"]', container).val();
                var h = $('[name="h"]', container).val();
                
                var left = $('[name="left"]', container).val();
                var top = $('[name="top"]', container).val();
                
                var type = container.data('type');
                
                var ean = container.attr('data-ean');
                var show_ean = $('[name="show_ean"]:checked').val();
                
                var product_name = $('[name="product_name"]').val();
                var show_product_name = $('[name="show_product_name"]:checked').val();
                
                var notes = $('[name="notes"]').val();
                
                var _params = {
                    ean: ean,
                    w: w,
                    h: h,
                    left: left,
                    top: top,
                    product_name: product_name,
                    show_ean: show_ean,
                    show_product_name: show_product_name,
                    notes: notes
                };
                
                if (notes.length > 0) {
                    _params['notes'] = btoa(notes);
                }
                
                var params = new URLSearchParams(_params).toString();
                
                var url = url + type + '/?' + params;
                
                window.open(url, '_blank');
            });
        });
    });
</script>
