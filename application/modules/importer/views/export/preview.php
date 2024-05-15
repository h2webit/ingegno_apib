<section class="content-header">
    <h4 class="page-title"><?php e('Export'); ?> <small>/ <?php echo $dati['template']['exporter_templates_name'] ?></small></h4>
    
    <ol class="breadcrumb">
        <li>Home</li>
        <li>Importer</li>
        <li>Export</li>
        <li class="active">Preview</li>
    </ol>
</section>

<style>
    th, td {
        white-space: nowrap;
    }
</style>

<?php
$custom_labels = [];
if (!empty($dati['template']['exporter_templates_fields_labels'])) {
    $custom_labels = json_decode($dati['template']['exporter_templates_fields_labels'], true);
}
?>

<section class="content" style="padding-bottom: 10rem">
    <div class="row">
        <div class="col-sm-12 clearfix">
            <div class="pull-left">
                <a href="<?php echo base_url('main/layout/exporter-templates'); ?>" class="btn bg-maroon"><i class="fas fa-chevron-left fa-fw"></i> <?php echo t('Back to exports list'); ?></a>
                <a href="<?php echo base_url('importer/export/edit/' . $dati['template']['exporter_templates_id']); ?>" class="btn bg-purple"><i class="fas fa-edit fa-fw"></i> <?php echo t('Edit columns'); ?></a>
            </div>
            
            <div class="pull-right">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-file-export fa-fw"></i>
                        <?php e('Export as'); ?> <span class="caret"></span>
                    </button>
                    
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo base_url("export/download_excel/{$dati['template']['exporter_templates_grid_id']}?filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="fas fa-file-excel fa-fw"></i> Excel (.xls)</a></li>
                        <li><a href="<?php echo base_url("export/download_csv/{$dati['template']['exporter_templates_grid_id']}?filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="fas fa-file-csv fa-fw"></i> CSV</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="<?php echo base_url("export/download_pdf/{$dati['template']['exporter_templates_grid_id']}?show_line_number=1&orientation=landscape&filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="fas fa-file-pdf fa-fw fa-rotate-270"></i> <?php e('PDF Horizontal'); ?></a></li>
                        <li><a href="<?php echo base_url("export/download_pdf/{$dati['template']['exporter_templates_grid_id']}?show_line_number=1&filename=" . urlencode($dati['template']['exporter_templates_name'])) ?>"><i class="far fa-file-pdf fa-fw"></i> <?php e('PDF Vertical'); ?></a></li>
                    </ul>
                    
                    <?php if($this->datab->can_access_layout($this->layout->getLayoutByIdentifier('exporter-templates-admin'))): ?>
                        <a href="<?php echo base_url('importer/export/new_template/' . $dati['template']['exporter_templates_id']); ?>" class="btn btn-warning"><i class="fas fa-pencil-ruler fa-fw"></i> <?php echo t('Advanced edit'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row" style="margin-top: 25px;">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title"><?php e('Filters'); ?></h3></div>
                
                <div class="box-body">
                    <?php echo $dati['form_filters'] ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row" style="margin-top: 25px;">
        <div class="col-sm-12">
            <div class="box box-primary with-border">
                <div class="box-body table-responsive">
                    <table class="table table-condensed table-bordered table-striped js_datatable">
                        <thead>
                            <tr>
                                <?php foreach($dati['grid']['grids_fields'] as $column): ?>
                                    <th>
                                        <a href="javascript:void(0);" class="js_edit_custom_label_btn text-warning" data-toggle="tooltip" data-placement="top" title="<?php e('Click to change the column name'); ?>"><i class="fas fa-pencil-alt fa-fw"></i></a>
                                        
                                        <span class="edit_label" style="display:none">
                                            <a href="javascript:void(0);" class="js_save_custom_label_btn text-success" data-toggle="tooltip" data-placement="top" title="<?php e('Click to save the column name'); ?>"><i class="fas fa-save fa-fw"></i></a>
                                            <input type="text" class="js_custom_label_input" name="<?php echo $column['grids_fields_id']; ?>" placeholder="<?php echo $column['grids_fields_column_name']; ?>">
                                        </span>
                                        
                                        <span class="column_label"><?php echo $column['grids_fields_column_name']; ?></span>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php
                            foreach ($dati['grid']['grids_data'] as $dato) {
                                echo '<tr>';
                                foreach ($dati['grid']['grids_fields'] as $field) {
                                    echo "<td>{$this->datab->build_grid_cell($field, $dato)}</td>";
                                }
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <div class="callout callout-info"><h4><i class="fas fa-info fa-fw"></i> Infobox</h4>
                        <p><?php e('In this section, in addition to a preview of the data, it is possible to change the names of the columns by clicking on the pencil next to the column name and entering the new name in the input box that appears and then clicking on the <b>Save</b> button > next to it.<br/>If you leave it blank, the name will <b>NOT</b> be changed'); ?></p>
                    </div>
                    
                    <p class="text-danger" style="font-size: 16px;"><i class="fas fa-info fa-fw"></i> <?php e('The table shows a maximum preview of %s records to avoid overloading the system. <b>Final exports</b> will have <b>all</b> complete data', true, ['100']); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(function() {
        $('table.js_datatable').DataTable({
            ordering: false,
            // "aoColumnDefs": [{
            //     "aTargets": [2,3],
            //     "defaultContent": "",
            // }]
        });
    
        var template_id = '<?php echo $dati['template']['exporter_templates_id']; ?>';
    
        var js_edit_custom_label_btn = $('.js_edit_custom_label_btn');
    
        js_edit_custom_label_btn.on('click', function() {
            var edit_btn = $(this);
            var th_ct = edit_btn.closest('th')
    
            var span_edit_label = $('span.edit_label', th_ct);
            // var span_custom_label = $('span.custom_label', th_ct);
            var span_column_label = $('span.column_label', th_ct);
            var js_custom_label_input = $('.js_custom_label_input', th_ct);
    
            var js_save_custom_label_btn = $('.js_save_custom_label_btn', th_ct);
    
            // if (span_custom_label.text().trim() !== '') {
            //     js_custom_label_input.val(span_custom_label.text().trim());
            // }
    
            edit_btn.hide();
            span_column_label.hide();
            // span_custom_label.hide().text('');
            span_edit_label.show();
    
            js_save_custom_label_btn.on('click', function() {
                var save_btn = $(this);
    
                var new_field_name = js_custom_label_input.val().trim();
    
                $.ajax({
                    url: base_url + 'importer/export/save_field_label',
                    type: 'post',
                    dataType: 'json',
                    async: true,
                    data: {
                        [token_name]: token_hash,
                        template_id: template_id,
                        grid_id: '<?php echo $dati['template']['exporter_templates_grid_id'] ?>',
                        campo: {
                            id: js_custom_label_input.attr('name'),
                            value: new_field_name,
                        }
                    },
                    success: function(res) {
                        if (res.status == '0') {
                            alert(res.txt);
                            return;
                        }
    
                        if (new_field_name) {
                            span_column_label.text(new_field_name);
                            span_edit_label.hide();
                            edit_btn.show();
                        } else {
                            span_edit_label.hide();
                            edit_btn.show();
                        }
                        
                        span_column_label.show();
                    },
                    error: function(xhr, status, error) {
                        var err = eval("(" + xhr.responseText + ")");
                        alert(err.Message);
                    }
                })
            });
        });
    });
</script>