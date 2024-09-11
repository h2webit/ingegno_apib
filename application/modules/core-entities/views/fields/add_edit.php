<?php
$html_types = unserialize(HTML_DRAW_TYPES);
$sql_types = unserialize(SQL_TYPES);

//debug($html_types);

?>

<div class="box box-danger box-field d-none" id='js-new_field_row'>
    <div class="box-header with-border">
        <div style="display:flex; justify-content: flex-start; gap: 10px; align-items: center">
            <h3 class='box-title'>
                <?php echo (!empty($dati['field'])) ? t('Edit field') : t('Add field'); ?>
            </h3>

            <div class='input-group' style='width: 30%'>
                <?php if (isset($dati['entity'])): ?>
                    <span class="input-group-addon js-entity_name_prefix"><?php echo $dati['entity']['entity_name'] ?>_</span>
                <?php endif; ?>

                <input type='text' class='form-control input-sm js_field_name' data-name="fields_name" <?php echo (!empty($dati['field'])) ? 'readonly' : ''; ?> value="<?php echo (!empty($dati['field'])) ? str_replace($dati['entity']['entity_name'] . '_', '', $dati['field']['fields_name']) : ''; ?>">
            </div>
        </div>

        <div class='box-tools pull-right' style="align-items: center">
            <?php if (!isset($dati['field'])): ?>
                <button type='button' class='btn btn-box-tool js_remove_line'><i class='fa fa-times'></i></button>
            <?php endif; ?>
        </div>
    </div>

    <div class="box-body">
        <input type="text" data-name="fields_type" class="js-fields_type" value="<?php echo (isset($dati['field'])) ? $dati['field']['fields_type'] : ''; ?>" />
        
        <div class="row">
            <div class="col-sm-3">
                <label class='control-label'>HTML Type <span class='text-danger'>*</span></label>
    
                <select class='form-control js-check-select-where js-html' data-name='fields_draw_html_type'>
                    <?php foreach ($html_types as $label => $options): ?>
                        <optgroup label="<?php echo strtoupper($label); ?>">
                            <?php foreach ($options as $val => $option): ?>
                                <option value="<?php echo $val; ?>" <?php echo (!empty($dati['field']['fields_draw_html_type']) && $dati['field']['fields_draw_html_type'] == $val) ? 'selected' : '' ?> data-sql_type="<?php echo $option['sql_type']; ?>">
                                    <?php echo $option['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class='col-sm-3 js-fields_ref' style='display:none;'>
                <label class='control-label'>Reference entity</label>
    
                <select class='form-control js-check-select-where js-ref' data-name='fields_ref'>
                    <option></option>
                    <?php foreach ($this->entities_list as $e): ?>
                        <option value="<?php echo $e['entity_name']; ?>" <?php echo (!empty($dati['field']['fields_ref']) && $dati['field']['fields_ref'] == $e['entity_name']) ? 'selected' : ''; ?>>
                            <?php echo $e['entity_name'] ?><?php if (in_array($e['entity_type'], [ENTITY_TYPE_SYSTEM])): ?>*<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
    
            <div class='col-sm-3 js-fields_source' style='display:none;'>
                <label class=' control-label'>Source field</label>
    
                <select class='form-control' data-name='fields_source'>
                    <option></option>
                    <?php foreach ($dati['fields'] as $field): ?>
                        <option value="<?php echo $field['fields_name']; ?>" <?php echo (!empty($dati['field']['fields_source']) && $dati['field']['fields_source'] == $field['fields_name']) ? 'selected' : ''; ?>>
                            <?php echo $field['fields_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-sm-2">
                <label for="" class="control-label">&nbsp;</label>
                
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <hr style="margin-top: 10px; margin-bottom: 10px;">
                
                <p class='js_advanced_settings' style='cursor:pointer'><?php e("Advanced"); ?> <i class='fas fa-plus fa-fw'></i></p>
            </div>
    
            <div class='field_advanced_settings' style='display:none'>
                <div class='col-sm-2'>
                    <label class=' control-label'>Default value</label>
                    <input type='text' data-name='fields_default' class='form-control text-right' value="<?php echo (isset($dati['field'])) ? $dati['field']['fields_default'] : ''; ?>" />
                </div>
                
                <div class='col-sm-2'>
                    <label class=' control-label'>Field size</label>
                    <input type='text' data-name='fields_size' class='form-control text-right js-field-size' value="<?php echo (isset($dati['field'])) ? $dati['field']['fields_size'] : ''; ?>"/>
                </div>
                
                <div class='col-sm-2 js-auto_join'>
                    <label>Automatic join</label><br/>
    
                    <div class='checkbox'>
                        <input type='checkbox' data-name='fields_ref_auto_left_join' value='<?php echo DB_BOOL_TRUE; ?>' <?php echo (!isset($dati['field']) || (isset($dati['field']) && $dati['field']['fields_ref_auto_left_join'] == DB_BOOL_TRUE)) ? 'checked' : ''; ?> />
                        <label>Left Join</label>
                    </div>
                    <div class='checkbox'>
                        <input type='checkbox' data-name='fields_ref_auto_right_join' value='<?php echo DB_BOOL_TRUE; ?>' <?php echo (isset($dati['field']) && $dati['field']['fields_ref_auto_right_join'] == DB_BOOL_TRUE) ? 'checked' : ''; ?> />
                        <label>Right Join</label>
                    </div>
                    
                    <?php if (empty($dati['field'])): // Se sto creando un campo ?>
                        <label class="control-label">&nbsp;</label><br/>
                        <input type="hidden" data-name="on_default_grid" value="0">
                        <input type="hidden" data-name="on_default_form" value="0">
                    <?php endif; ?>
                </div>
                
                <div class="col-sm-3 js-join_where" style="display:none;">
                    <label>Join where condition</label><br/>
                    <textarea class="form-control" data-name="fields_select_where"><?php echo isset($dati['field']['fields_select_where']) ? html_entity_decode($dati['field']['fields_select_where']) : ''; ?></textarea>
                </div>
        
                <div class="col-sm-3">
                    <label>Parameters</label><br/>
            
                    <?php if (empty($dati['field'])): ?>
                        <div class='checkbox'>
                            <input type='checkbox' data-name='on_default_grid' value='1' class='toggle' checked/>
                            <label>Add to default <strong>grid</strong></label>
                        </div>
                        <div class='checkbox'>
                            <input type='checkbox' data-name='on_default_form' value='1' class='toggle' checked/>
                            <label>Add to default <strong>form</strong></label>
                        </div>
                    <?php endif; ?>
    
                    <div class='checkbox'>
                        <input type='checkbox' data-name='fields_auto_increment' value='<?php echo DB_BOOL_TRUE; ?>' <?php echo (isset($dati['field']) && !empty($dati['field']['fields_auto_increment']) && $dati['field']['fields_auto_increment'] == DB_BOOL_TRUE) ? 'checked' : ''; ?> />
                        <label>Auto-increment</label>
                    </div>
    
                    <div class='checkbox'>
                        <input type='checkbox' data-name='fields_multilingual' value='<?php echo DB_BOOL_TRUE; ?>' <?php echo (isset($dati['field']) && !empty($dati['field']['fields_multilingual']) && $dati['field']['fields_multilingual'] == DB_BOOL_TRUE) ? 'checked' : ''; ?> />
                        <label>Multilanguage</label>
                    </div>
    
                    <div class='checkbox'>
                        <input type='checkbox' data-name='fields_xssclean' value='<?php echo DB_BOOL_TRUE; ?>' <?php echo (isset($dati['field']) && $dati['field']['fields_xssclean'] == DB_BOOL_TRUE) ? 'checked' : ''; ?> />
                        <label>Auto XSS Clean</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="<?php echo base_url('core-entities/core_entities/save_field') ?>" id="form_add_edit_field" class="form-horizontal formAjax">
    <?php add_csrf(); ?>
    
    <input type='hidden' value="<?php echo $dati['entity']['entity_id'] ?>" name='entity_id'/>

    <?php if (isset($dati['field'])): ?>
        <input type="hidden" value="<?php echo $dati['field']['fields_id'] ?>" name="fields_id"/>
    <?php endif; ?>

    <div class="js_fields_list_to_add"></div>

    <div class='row'>
        <div class='col-md-12'>
            <div id='msg_form_add_edit_field' class='alert alert-danger hide'></div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?php if(!empty($dati['field'])): ?>
            <a href="<?php echo base_url("main/layout/entity-fields/{$dati['entity']['entity_id']}"); ?>" class='btn btn-danger'><i class='fas fa-times fa-fw'></i> <?php e('Cancel'); ?></a>
            <?php endif; ?>
        </div>
        
        <div class="col-sm-6 text-center">
            <?php if (!isset($dati['field'])): ?>
                <button type='button' onclick='addNewField();' class='btn btn-success'><i class='fas fa-plus fa-fw'></i> <?php e('Add Field'); ?></button>
            <?php endif; ?>
        </div>

        <div class="col-sm-3">
            <button type='submit' class='btn btn-primary pull-right'><i class='fas fa-save fa-fw'></i> <?php e('Save'); ?></button>
        </div>
    </div>
</form>

<script>
    function addNewField() {
        console.log("creating new row");
        
        const new_field_row = $('#js-new_field_row');
        const form = $('#form_add_edit_field');
        const line = new_field_row.clone().removeAttr('id').removeClass('d-none').addClass('js-field_data');

        const fields_container = $('.js_fields_list_to_add');
        $('input, select, textarea', line).each(function () {
            const name = $(this).attr('data-name');
            if (name) {
                //console.log(table.children('tbody').children('tr'));
                const count = $('.js-field_data').length;
                $(this).attr('name', 'fields[' + count + '][' + name + ']').removeAttr('data-name');
            }
        });

        fields_container.append(line);
    }

    function entityNamePrepend() {
        var entity = $('option:selected', $('#js_entity_id')).text();
        $('.js_entity_prepend').text(entity.replace(/ /gi, '') + '_');
    }

    $('#js_field_table').on('change', '.js-check-select-where', function () {
        var container = $(this).parents('.subtable').filter(':first');

        var htmlType = $('.js-html', container).val();
        var fieldRef = $('.js-ref', container).val();

        if ($.inArray(htmlType, ['select', 'select_ajax', 'multiselect']) >= 0 && fieldRef) {
            $('.select-where-container', container).show();
        } else {
            $('.select-where-container', container).hide();
        }
    });

    $(function () {
        addNewField();
        entityNamePrepend();

        $('#js_entity_id').on('change', entityNamePrepend);
        
        const form_container = $('#form_add_edit_field');
        
        form_container.on('click', '.js_remove_line', function () {
            $(this).closest('.js-field_data').remove();
        });
        
        form_container.on('change', '.js-html', function() {
            var sql_type = $(this).find(':selected').data('sql_type');
            
            $('.js-fields_type', form_container).val(sql_type)
        })

        form_container.on('change', '.js-fields_type', function () {
            const selected = $(this).val();
            
            if (selected !== 'VARCHAR') {
                $('.js-field-size', $(this).closest('.js-field_data')).val('');
            }

            if (selected === 'INTEGER') {
                $('.js-fields_ref', $(this).closest('.box-field')).show();
                $('.js-ref', $(this).closest('.box-field')).trigger('change');
            } else {
                $('.js-ref', $(this).closest('.box-field')).val('').trigger('change');
                $('.js-fields_ref,.js-fields_source', $(this).closest('.box-field')).hide();
            }
        });

        form_container.on('change', '.js-ref', function () {
            const line = $(this).closest('.js-field_data');
            
            if ($(this).val() !== '') {
                $('.js-fields_source,.js-auto_join,.js-join_where', line).show();

            } else {
                $('.js-fields_source,.js-auto_join,.js-join_where', line).hide();
            }
        });

        form_container.on('click', '.js_advanced_settings', function () {
            $(this).find('i').toggleClass('fa-plus fa-minus');
            
            const line = $(this).closest('.js-field_data');
            
            $('.field_advanced_settings', line).toggle();
        });
    
        $('.js-fields_type').trigger('change');
    });

    function cleanText(string) {
        /**
         * @param {string} "fòo bAr -3"
         * @return {string} "foo_bar_3"
         * @author Michael E.
         */
        var str = string;
        var clean_str;
    
        clean_str = str.replace(new RegExp(/\s/g), '_');
        clean_str = clean_str.replace(new RegExp(/[àáâãäå]/g), 'a');
        clean_str = clean_str.replace(new RegExp(/æ/g), 'ae');
        clean_str = clean_str.replace(new RegExp(/ç/g), 'c');
        clean_str = clean_str.replace(new RegExp(/[èéêë]/g), 'e');
        clean_str = clean_str.replace(new RegExp(/[ìíîï]/g), 'i');
        clean_str = clean_str.replace(new RegExp(/ñ/g), 'n');
        clean_str = clean_str.replace(new RegExp(/[òóôõö]/g), 'o');
        clean_str = clean_str.replace(new RegExp(/œ/g), 'oe');
        clean_str = clean_str.replace(new RegExp(/[ùúûü]/g), 'u');
        clean_str = clean_str.replace(new RegExp(/[ýÿ]/g), 'y');
        clean_str = clean_str.replace(new RegExp(/\W/g), '');
        clean_str = clean_str.replace(/[^a-zA-Z 0-9_]+/g, '').split(' ').join('_').toString().toLowerCase();
    
        return clean_str;
    }
    
    $(document).on('keyup', '.js_field_name', function() {
        var value = $(this).val();
    
        $(this).val(cleanText(value));
    });
</script>
