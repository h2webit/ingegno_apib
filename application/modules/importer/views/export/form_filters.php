<?php
$grid = $this->datab->get_grid($template['exporter_templates_grid_id'])['grids'];

$entity = $this->crmentity->getEntityFullData($grid['grids_entity_id']);

$fields = $this->crmentity->getVisibleFields($grid['entity_name'], 2);

$sess_data = $this->session->userdata(SESS_WHERE_DATA) ?: [];

// Recupera info filtri e indicizza i dati per field_id
$_sess_where_data = array_get($sess_data, $grid['grids_filter_session_key'], []);
$where_data = array_combine(array_key_map($_sess_where_data, 'field_id'), $_sess_where_data);

?>

<style>
    .js_filter_form_row {
        margin-bottom: 10px !important;
    }
</style>

<form id="form_filters" role="form" method="post" action="<?php echo base_url("importer/export/save_filters/{$template['exporter_templates_id']}"); ?>" class="formAjax js_filter_form">
    <?php add_csrf(); ?>
    
    <p><?php e('Add conditions to filter the table'); ?></p>
    
    <div class="js_filter_form_rows_container" style="border: 1px dashed darkgrey; padding: 10px 10px 0 10px; margin: 0 0 10px 0;">
        <div class="js_filter_placeholder <?php echo (!empty($sess_data[$grid['grids_filter_session_key']])) ? 'hide' : ''; ?>">
            <p><i class="fas fa-info-circle fa-fw text-info"></i> <?php e('Click "<b>Add condition</b>" button to start building filters'); ?></p>
        </div>
        
        <div class="js_filter_form_row row hide">
            <div class="col-sm-4">
                <select class="form-control js_filter_fields js_select2" data-name="field_id">
                    <option></option>
                    
                    <?php foreach ($fields as $field) : ?>
                        <option value="<?php echo $field['fields_id'] ?>" data-type="<?php echo $field['fields_type'] ?>">
                            <?php echo ucfirst($field['entity_name']) . ' - ' . $field['fields_draw_label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-3">
                <select class="form-control" data-name="operator">
                    <option></option>
                    <?php foreach (unserialize(OPERATORS) as $operator_key => $operator_data) : ?>
                        <option value="<?php echo $operator_key; ?>"><?php echo $operator_data['html']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-sm-4">
                <input type="text" class="form-control js_filter_value" data-name="value" placeholder="<?php e('Matching value'); ?>" />
            </div>

            <div class="col-sm-1">
                <button type="button" class="btn btn-danger js_remove_row"><i class="fas fa-trash-alt fa-fw"></i></button>
            </div>
        </div>
        
        <?php if (isset($sess_data[$grid['grids_filter_session_key']])) : ?>
            <?php foreach ($sess_data[$grid['grids_filter_session_key']] as $k => $condition) : ?>
                <div class="js_filter_form_row row">
                    <div class="col-xs-4">
                        <select class="form-control js_filter_fields js_select2" name="conditions[<?php echo $k; ?>][field_id]">
                            <option></option>
                            <?php foreach ($fields as $field) : ?>
                                <option value="<?php echo $field['fields_id'] ?>" <?php if ($condition['field_id'] == $field['fields_id']) echo "selected"; ?> data-type="<?php echo $field['fields_type'] ?>">
                                    <?php echo ucfirst($field['entity_name']) . ' - ' . $field['fields_draw_label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-3">
                        <select class="form-control" name="conditions[<?php echo $k; ?>][operator]">
                            <option></option>
                            <?php foreach (unserialize(OPERATORS) as $operator_key => $operator_data) : ?>
                                <option value="<?php echo $operator_key; ?>" <?php if ($condition['operator'] == $operator_key) echo "selected"; ?>><?php echo $operator_data['html']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <input type="text" class="form-control js_filter_value" name="conditions[<?php echo $k; ?>][value]" placeholder="<?php e('Matching value'); ?>" value="<?php echo $condition['value']; ?>" />
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-danger js_remove_row"><i class="fas fa-trash-alt fa-fw"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="row" style="margin-top: 10px;">
        <div class="col-sm-12">
            <div class="alert alert-danger hide" style="width: 100%;" id="msg_form_filters"></div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12 clearfix">
            <div class="pull-left">
                <button type="button" class="btn btn-success btn-sm js_filter_form_add_row">
                    <i class="fas fa-plus fa-fw"></i> <?php e('Add condition'); ?>
                </button>
            </div>
            
            <div class="pull-right">
                <?php if (!empty($sess_data[$grid['grids_filter_session_key']])) : ?><a href="<?php echo base_url('importer/export/clear_filters/' . $template['exporter_templates_id']); ?>" class="btn btn-danger js_link_ajax"><i class="fas fa-broom fa-fw"></i> <?php e('Clear filters'); ?></a><?php endif; ?>
                <button type="submit" class="btn btn-info"><i class="fas fa-filter fa-fw"></i> <?php echo empty($filters) ? t('Apply filters') : t('Update filters'); ?></button>
            </div>
        </div>
    </div>
</form>

<script>
    $(function() {
        $(document).on('click', 'button.js_remove_row', function() {
            var this_btn = $(this);
            var row_ct = this_btn.closest('.js_filter_form_row');
            
            row_ct.remove();
            
            if ($('.js_filter_form_row:not(.hide)').length === 0) {
                $('.js_filter_placeholder').removeClass('hide');
            }
        });
        
        $(document).on('click', 'button.js_filter_form_add_row', function() {
            $('.js_filter_placeholder').addClass('hide');
            $('select.js_select2:visible').select2();
        });
        
        $(document).on('change', '.js_filter_fields:visible', function() {
            var option = $(this).find(':selected');
            var _row = $(this).closest('.js_filter_form_row:visible');
            
            var _type = option.data('type');
            
            if (_type) {
                var field_value = $('.js_filter_value:visible', _row);
                
                if (_type.toUpperCase() === 'DATETIME') {
                    field_value.parent().addClass('js_form_daterangepicker');
                } else {
                    var drp_obj = field_value.parent().data('daterangepicker');
                    
                    if (drp_obj) {
                        drp_obj.remove();
                        field_value.parent().removeClass('js_form_daterangepicker')
                    }
                }
                
                initComponents();
            }
        })
        
        $('.js_filter_fields:visible').trigger('change');
        
        $('select.js_select2:visible').select2();
    });
</script>