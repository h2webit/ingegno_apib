<?php
$projects_settings = $this->apilib->searchFirst('projects_settings');
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

    .form_control{
        border: 1px solid #000;
    }
</style>

<div class="js_barcode_grid text-center">

    <div class="text-center js_barcode_container"  data-url="projects/etichetta/print/">

        <div class="row">
            <div class="col-md-12 ">
                <?php e("Measurement of the print area") ?>:
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Width') ?>:</label>
                    <input class="form_control" type="text" name="w" size="3" <?php echo (!empty($projects_settings['projects_settings_label_default_width'])) ? 'value="' . $projects_settings['projects_settings_label_default_width'] . '"' : ''; ?> /> mm
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Height'); ?>:</label>
                    <input class="form_control" type="text" name="h" size="3" <?php echo (!empty($projects_settings['projects_settings_label_default_height'])) ? 'value="' . $projects_settings['projects_settings_label_default_height'] . '"' : ''; ?> /> mm
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Left margin'); ?>:</label>
                    <input class="form_control" type="text" name="left" size="3" <?php echo (!empty($projects_settings['projects_settings_label_default_left_margin'])) ? 'value="' . $projects_settings['projects_settings_label_default_left_margin'] . '"' : ''; ?> /> mm
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label><?php e('Top margin'); ?>:</label>
                    <input class="form_control" type="text" name="top" size="3" <?php echo (!empty($projects_settings['projects_settings_label_default_top_margin'])) ? 'value="' . $projects_settings['projects_settings_label_default_top_margin'] . '"' : ''; ?> /> mm
                </div>
            </div>

            <button class="btn bg-navy btn-lg btn-print" type="button"><?php e('Print'); ?> <i class="fas fa-print"></i></button>
        </div>
    </div>
</div>

<script>
    $(function() {


        $('.btn-print').on('click', function() {
            var w = $('[name="w"]').val();
            var h = $('[name="h"]').val();

            var left = $('[name="left"]').val();
            var top = $('[name="top"]').val();

            var _params = {
                qrcode: <?php echo $value_id; ?>,
                w: w,
                h: h,
                left: left,
                top: top
            };
            var params = new URLSearchParams(_params).toString();

            var url = base_url + 'projects/main/print/?' + params;

            window.open(url, '_blank');
        });
    });
</script>