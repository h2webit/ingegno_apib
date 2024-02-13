<section class="content-header">
    <h4 class="page-title"><?php e('Export'); ?> <small><?php e('Export your template'); ?></small></h4>
    
    <ol class="breadcrumb">
        <li>Home</li>
        <li>Importer</li>
        <li>Export</li>
        <li class="active"><?php e('Edit'); ?></li>
    </ol>
</section>

<style>
    .ms-container {
        width: 100% !important;
    }

    table.table tbody tr td,
    table.table thead tr th,
    table.table thead {
        border-left: 1px solid #f4f4f4;
        border-right: 1px solid #f4f4f4;
    }

    .custom-header {
        text-align: center;
        padding: 3px;
        color: #fff;
    }
    
    .custom-header.src-header {
        background-color: darkgreen;
    }

    .custom-header.dst-header {
        background-color: darkblue;
    }

    .custom-header.actions-header {
        background-color: darkred;
    }
    
    .custom-footer {
        text-align: center;
        padding: 3px;
        background: #000;
        color: #fff;
    }
</style>

<section class="content">
    <div class="row">
        <div class="col-sm-12 clearfix">
            <div class="pull-left">
                <a href="<?php echo base_url('main/layout/exporter-templates-admin'); ?>" class="btn bg-maroon"><i class="fas fa-chevron-left fa-fw"></i> <?php echo t('Back to templates list'); ?></a>
                <a href="<?php echo base_url('importer/export/preview/' . $dati['template']['exporter_templates_id']); ?>" class="btn btn-info"><i class="fas fa-eye fa-fw"></i> <?php e('Detail') ?></a>
                <a href="<?php echo base_url('importer/export/new_template/' . $dati['template']['exporter_templates_id']); ?>" class="btn btn-warning"><i class="fas fa-pencil-ruler fa-fw"></i> <?php echo t('Edit template'); ?></a>
            </div>
            
            <div class="pull-right">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-export fa-fw"></i>
                    <?php e('Export as'); ?> <span class="caret"></span>
                </button>
                
                <ul class="dropdown-menu">
                    <li><a href="<?php echo base_url("export/download_excel/{$dati['template']['exporter_templates_grid_id']}?filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="fas fa-file-excel fa-fw"></i> Excel (.xls)</a></li>
                    <li><a href="<?php echo base_url("export/download_csv/{$dati['template']['exporter_templates_grid_id']}?filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="fas fa-file-csv fa-fw"></i> CSV</a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="<?php echo base_url("export/download_pdf/{$dati['template']['exporter_templates_grid_id']}?orientation=landscape&filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="fas fa-file-pdf fa-fw fa-rotate-270"></i> PDF Horizontal</a></li>
                    <li><a href="<?php echo base_url("export/download_pdf/{$dati['template']['exporter_templates_grid_id']}?filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="far fa-file-pdf fa-fw"></i> PDF Vertical</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-sm-12">
            <div class="callout callout-info"><h4><i class="fas fa-info fa-fw"></i> Infobox</h4>
                <p><?php e('To select the columns you want to export, just choose the ones you are interested in in the <b>"AVAILABLE COLUMNS"</b> section on the right and click on <b>"Add selected"</b>.<br/>If you prefer export them all together, use the <b>"Add all"</b> button. To remove columns from the export list, follow the same procedure in the <b>"COLUMNS CHOSEN FOR EXPORT" section</b>'); ?></p>
            </div>
        </div>
        
        <div class="col-sm-5">
            <div class='custom-header src-header'><?php e('AVAILABLE COLUMNS') ?></div>
            
            <select id="export_fields" class="form-control" size="8" multiple="multiple">
                <?php
                $all_fields = array_map("unserialize", array_unique(array_map("serialize", $dati['all_fields'])));
                
                foreach($all_fields as $field) {
                    echo "<option value='{$field['fields_id']}'>". ucfirst($field['entity_name']) . ' - ' . $field['fields_draw_label']."</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="col-sm-2">
            <div class='custom-header actions-header'><?php e('ACTIONS'); ?></div>
            
            <button type="button" id="js_right_Selected_1" class="btn btn-block"><?php e('Add selected'); ?> <i class="glyphicon glyphicon-chevron-right"></i></button>
            <button type="button" id="js_left_Selected_1" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i> <?php e('Remove selected'); ?></button>
<!--            <hr style="margin: 0; margin-top: 5px; margin-bottom: 5px; padding:0; border: 1px solid black"/>-->
<!--            <button type="button" id="js_right_All_1" class="btn btn-block">Aggiungi tutto <i class="glyphicon glyphicon-forward"></i></button>-->
<!--            <button type="button" id="js_left_All_1" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i> Rimuovi tutto</button>-->
        </div>
        
        <div class="col-sm-5">
            <div class='custom-header dst-header'><?php e('COLUMNS SELECTED FOR EXPORT'); ?></div>
            <select name="export_fields[]" id="export_fields_to" class="form-control" size="8" multiple="multiple">
                <?php
                
                foreach ($dati['selected_fields'] as $field) {
                    $label = '';
                    if (!empty($field['entity_name']) && !empty($field['fields_draw_label'])) {
                        $label = ucfirst($field['entity_name']) . ' - ' . $field['fields_draw_label'];
                        
                        if (trim($field['grids_fields_column_name']) !== trim($field['fields_draw_label'])) {
                            $label .= " <small>({$field['grids_fields_column_name']})</small>";
                        }
                    } else {
                        $label = $field['grids_fields_column_name'];
                    }
                    
                    echo "<option value='{$field['grids_fields_id']}'>" . $label . "</option>";
                }
                ?>
            </select>
            
            <div class="row">
                <div class="col-sm-6">
                    <button type="button" id="js_move_up_1" class="btn btn-block"><i class="glyphicon glyphicon-arrow-up"></i></button>
                </div>
                <div class="col-sm-6">
                    <button type="button" id="js_move_down_1" class="btn btn-block col-sm-6"><i class="glyphicon glyphicon-arrow-down"></i></button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-sm-12">
            <div class="box box-info width-border">
                <div class="box-header with-border clearfix">
                    <h3 class="box-title pull-left">Preview</h3>
                    <button type="button" class="btn btn-sm bg-navy pull-right" id="refresh_preview"><i class="fas fa-sync-alt fa-fw"></i> <?php e('Refresh'); ?></button>
                </div>
                
                <div class="box-body">
                    <div class="alert alert-danger hide" id="msg_form_export_fields_map"></div>
                    
                    <div id="table_preview_loader" class="text-center" style="display: block;font-size: 5rem;text-transform: uppercase;font-weight: 600;"><?php e('Loading table...') ?> <i class="fas fa-solid fa-sync-alt fa-spin fa-fw"></i></div>
                    
                    <div class="table-responsive" id="preview"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->layout->addModuleJavascript('importer', 'js/multiselect.min.js'); ?>

<script>
    var table_preview_loader = $('#table_preview_loader');
    
    var refresh_preview = function () {
        var template_id = '<?php echo $dati['template']['exporter_templates_id']; ?>';
        var $select = $('#export_fields_to');
        var $preview = $('div#preview');
        var msg_alert = $('#msg_form_export_fields_map');
        
        table_preview_loader.show();
        
        var selected_fields =  $select.find('option').map(function(i, el) {
            return $(el).val();
        }).get();
        
        // console.log(selected_fields)
        
        $preview.html('');
        msg_alert.html('').addClass('hide');
        
        if (selected_fields) {
            $.ajax({
                url: base_url + 'importer/export/preview_table',
                type: 'post',
                dataType: 'json',
                data: {
                    [token_name]: token_hash,
                    tpl_id: template_id,
                    fields: selected_fields,
                },
                async: true,
                complete: function() {
                    table_preview_loader.hide();
                },
                success: function (res) {
                    if (res.status == 0) {
                        msg_alert.html(res.txt).removeClass('hide');
                    }
                    
                    $preview.html(res.data);
                },
                error: function (status, request, error) {
                    console.error(error);
                    msg_alert.html("Errore caricamento tabella").removeClass('hide');
                }
            });
        }
    }
    
    var save_fields_order = function () {
        var grid_id = '<?php echo $dati['template']['exporter_templates_grid_id']; ?>';
        
        var $select = $('#export_fields_to');
        
        var selected_fields =  $select.find('option').map(function(i, el) {
            return $(el).val();
        }).get();
        
        $.ajax({
            url: base_url + 'importer/export/reorder_fields',
            type: 'post',
            dataType: 'json',
            data: {
                [token_name]: token_hash,
                grid_id: grid_id,
                fields: selected_fields
            },
            success: function(res) {
                if (res.status == '0') {
                    alert(res.txt);
                    return false;
                }
                
                refresh_preview();
            }
        })
    }
    
    var grid_id = '<?php echo $dati['template']['exporter_templates_grid_id']; ?>';
    
    $(function() {
        $("#export_fields").multiselect({
            search: {
                left: '<input type="text" style="width: 100%; margin: 5px 0; padding-left: 5px;" placeholder="Ricerca..." />',
                right: '<input type="text" style="width: 100%; margin: 5px 0; padding-left: 5px;" placeholder="Ricerca..." />',
            },
            fireSearch: function(value) {
                return value.length > 2;
            },
            keepRenderingSort: true,
            
            right: '#js_multiselect_to_1',
            // rightAll: '#js_right_All_1',
            rightSelected: '#js_right_Selected_1',
            leftSelected: '#js_left_Selected_1',
            // leftAll: '#js_left_All_1',
            moveUp: '#js_move_up_1',
            moveDown: '#js_move_down_1',
            
            sort: {
                // left: function(a, b) {
                //     return a.value > b.value ? -1 : 1;
                // },
                right: function(a, b) {
                    return -1;
                }
            },
            
            afterMoveToLeft: function($left, $right, $options) {
                table_preview_loader.show();
                
                var selected_fields =  $options.map(function(i, el) {
                    return $(el).val();
                }).get();
                
                $.ajax({
                    url: base_url + 'importer/export/remove_fields/' + grid_id,
                    type: 'post',
                    dataType: 'json',
                    data: {
                        [token_name]: token_hash,
                        fields: selected_fields
                    },
                    success: function(res) {
                        if (res.status == '0') {
                            alert(res.txt);
                            return false;
                        }
                        
                        refresh_preview();
                    }
                })
            },
            afterMoveToRight: function($left, $right, $options) {
                table_preview_loader.show();
                
                var selected_fields =  $options.map(function(i, el) {
                    return $(el).val();
                }).get();
                
                $.ajax({
                    url: base_url + 'importer/export/add_fields/' + grid_id,
                    type: 'post',
                    dataType: 'json',
                    data: {
                        [token_name]: token_hash,
                        fields: selected_fields
                    },
                    success: function(res) {
                        if (res.status == '0') {
                            alert(res.txt);
                            return false;
                        }
                        
                        if (res.data.grid_field_map) {
                            var right_column = $('select#export_fields_to');
                            $.each(res.data.grid_field_map, function(field_id, grid_field_id) {
                                $('option[value="' + field_id + '"]', right_column).val(grid_field_id).appendTo('#export_fields_to');
                            });
                        }

                        refresh_preview();
                    }
                })
            },
            afterMoveUp: function($options) {
                table_preview_loader.show();

                save_fields_order();
            },
            afterMoveDown: function($options) {
                table_preview_loader.show();
                
                save_fields_order();
            },
        }).trigger('afterMoveToLeft')

        $('button#refresh_preview').on('click', function() {
            table_preview_loader.show();
            refresh_preview();
        }).trigger('click');
    });
</script>