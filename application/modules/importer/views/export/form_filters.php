<?php
$grid = $this->datab->get_grid($template['exporter_templates_grid_id'])['grids'];

$entity = $this->crmentity->getEntityFullData($grid['grids_entity_id']);

$fields = $this->crmentity->getVisibleFields($grid['entity_name'], 2);

// debug($fields, true);

$sess_data = $this->session->userdata(SESS_WHERE_DATA) ?: [];

if (empty($sess_data[$grid['grids_filter_session_key']]) && !empty($template['exporter_templates_filters'])) {
    $sess_data[$grid['grids_filter_session_key']] = json_decode($template['exporter_templates_filters'], true);
    
    $this->session->set_userdata(SESS_WHERE_DATA, $sess_data);
}

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
            <p><i class="fas fa-info-circle fa-fw text-info"></i> <?php e('Click <b>Add condition</b> button to start building filters'); ?></p>
        </div>
        
        <div class="row js_filter_columns_label <?php echo (empty($sess_data[$grid['grids_filter_session_key']])) ? 'hide' : ''; ?>">
            <div class="col-sm-4"><b><?php e('Field'); ?></b></div>
            <div class="col-sm-3"><b><?php e('Operator'); ?></b></div>
            <div class="col-sm-4"><b><?php e('Value'); ?></b></div>
        </div>
        
        <div class="js_filter_form_row row hide">
            <input type="hidden" class="js_reverse" data-name="reverse" value="<?php echo DB_BOOL_FALSE ?>">
            
            <div class="col-sm-4">
                <select class="form-control js_filter_fields js_select2" data-name="field_id">
                    <option value=""></option>
                    
                    <?php foreach ($fields as $field) : ?>
                        <?php
                        $source_ref = '';
                        if (stripos(strtoupper($field['fields_type']), 'INT') !== false && !empty($field['fields_ref'])) {
                            $source_ref = $field['fields_ref'];
                        }
                        ?>
                    
                        <option value="<?php echo $field['fields_id'] ?>" data-type="<?php echo strtoupper($field['fields_type']) ?>" data-source-ref="<?php echo $source_ref; ?>">
                            <?php echo ucfirst($field['entity_name']) . ' - ' . $field['fields_draw_label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-3">
                <select class="form-control js_operator" data-name="operator">
                    <option value=""></option>
                    <?php foreach (unserialize(OPERATORS) as $operator_key => $operator_data) : ?>
                        <option value="<?php echo $operator_key; ?>"><?php echo (!empty($operator_data['label'])) ? t($operator_data['label']) : $operator_data['html']; ?></option>
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
                    <input type="hidden" class="js_reverse" name="conditions[<?php echo $k; ?>][reverse]" value="<?php echo $condition['reverse'] ?? DB_BOOL_FALSE ?>">
                    <div class="col-xs-4">
                        <select class="form-control js_filter_fields js_select2" name="conditions[<?php echo $k; ?>][field_id]">
                            <option></option>
                            <?php foreach ($fields as $field) : ?>
                                <?php
                                $source_ref = '';
                                if (stripos(strtoupper($field['fields_type']), 'INT') !== false && !empty($field['fields_ref'])) {
                                    $source_ref = $field['fields_ref'];
                                }
                                ?>
                                
                                <option value="<?php echo $field['fields_id'] ?>" <?php if ($condition['field_id'] == $field['fields_id']) echo "selected"; ?> data-type="<?php echo strtoupper($field['fields_type']) ?>" data-source-ref="<?php echo $source_ref; ?>">
                                    <?php echo ucfirst($field['entity_name']) . ' - ' . $field['fields_draw_label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-3">
                        <select class="form-control js_operator" name="conditions[<?php echo $k; ?>][operator]">
                            <option></option>
                            <?php foreach (unserialize(OPERATORS) as $operator_key => $operator_data) : ?>
                                <option value="<?php echo $operator_key; ?>" <?php if ($condition['operator'] == $operator_key) echo "selected"; ?>><?php echo (!empty($operator_data['label'])) ? t($operator_data['label']) : $operator_data['html']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <?php if(!is_array($condition['value'])): ?>
                        <input type="text" class="form-control js_filter_value" name="conditions[<?php echo $k; ?>][value]" placeholder="<?php e('Matching value'); ?>" value="<?php echo $condition['value']; ?>" />
                        <?php else: ?>
                        <input type="hidden" class="js_filter_value" value="<?php echo implode(',', $condition['value']); ?>" />
                        <select class="form-control js_filter_value select2me" name="conditions[<?php echo $k; ?>][value][]" multiple></select>
                        <?php endif; ?>
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
                <?php if (!empty($sess_data[$grid['grids_filter_session_key']])) : ?><a href="<?php echo base_url('importer/export/clear_filters/' . $template['exporter_templates_id']); ?>" class="btn btn-default js_link_ajax"><i class="fas fa-broom fa-fw"></i> <?php e('Clear filters'); ?></a><?php endif; ?>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter fa-fw"></i> <?php echo empty($filters) ? t('Apply filters') : t('Update filters'); ?></button>
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
                $('.js_filter_columns_label').addClass('hide');
            }
        });
        
        $(document).on('click', 'button.js_filter_form_add_row', function() {
            $('.js_filter_placeholder').addClass('hide');
            $('.js_filter_columns_label').removeClass('hide');
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
                    $.each($('.js_operator', _row).find('option'), function() {
                        var _this = $(this);
                        var _val = _this.val();
                        
                        if (_val !== 'eq' && _val !== 'neq') {
                            _this.attr('disabled', 'disabled').prop('disabled', true);
                        } else {
                            _this.removeAttr('disabled').prop('disabled', false);
                        }
                    });
                } else {
                    var drp_obj = field_value.parent().data('daterangepicker');
                    
                    if (drp_obj) {
                        drp_obj.remove();
                        field_value.parent().removeClass('js_form_daterangepicker')
                    }
                    
                    $.each($('.js_operator', _row).find('option'), function() {
                        var _this = $(this);
                        var _val = _this.val();
                        
                        _this.removeAttr('disabled').prop('disabled', false);
                    });
                    
                    var attr_name = $('input.js_filter_value', _row).attr('name');
                    var current_value = $('input.js_filter_value', _row).val();
                    if (_type.toUpperCase().includes('INT') && option.data('source-ref')) {
                        if ($('.js_operator', _row).val() !== 'in' && $('.js_operator', _row).val() !== 'notin') {
                            $('.js_operator', _row).val('in').trigger('change');
                        }
                        
                        $.each($('.js_operator', _row).find('option'), function() {
                            var _this = $(this);
                            var _val = _this.val();
                            
                            if (_val !== 'in' && _val !== 'notin') {
                                _this.attr('disabled', 'disabled').prop('disabled', true);
                            } else {
                                _this.removeAttr('disabled').prop('disabled', false);
                            }
                        });
                        
                        $('input.js_filter_value:not([type="hidden"])', _row).replaceWith('<select class="form-control js_filter_value select2me" name="' + attr_name + '[]" multiple></select>');
                        
                        request(base_url + 'importer/export/get_support_values/' + option.data('source-ref')).then(function(res) {
                            var _select = $('select.js_filter_value', _row);
                            
                            if (!res.status == 1 || !res.data) {
                                _select.html('').trigger('change')
                                return;
                            }
                            
                            current_value = current_value.split(',');
                            
                            $.each(res.data, function(k, v) {
                                var selected_value = (current_value && current_value.includes(k)) ? 'selected' : '';
                                _select.append('<option value="' + k + '" '+selected_value +'>' + v + '</option>');
                            });
                        });
                    } else {
                        $('.js_operator', _row).trigger('change');
                        
                        if ($('select.js_filter_value', _row)) {
                            $('select.js_filter_value', _row).select2('destroy')
                        }
                        
                        
                        $('select.js_filter_value', _row).replaceWith('<input type="text" class="form-control js_filter_value" name="'+attr_name+'" placeholder="<?php e('Matching value'); ?>" />');
                    }
                }
                
                initComponents();
            }
        }).trigger('change')
        
        $(document).on('change', '.js_operator:visible', function() {
            var _selected = $(this).find(':selected').val();
            var _row = $(this).closest('.js_filter_form_row:visible');
            
            var _reverse = $('.js_reverse', _row);
            
            if (_selected == 'eq' || _selected == 'in' || _selected == 'like') {
                _reverse.val('0');
            } else if (_selected == 'neq' || _selected == 'notin' || _selected == 'notlike') {
                _reverse.val('1');
            }
        })
        
        $('.js_filter_fields:visible').trigger('change');
        
        $('select.js_select2:visible').select2();
    });
</script>