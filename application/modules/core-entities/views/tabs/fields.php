<?php
$fieldCustomActions = unserialize(CUSTOM_ACTIONS_FIELDS);

$entityCustomActions = [];
if (!empty($dati['entity']['entity_action_fields'])) {
    $entityCustomActions = json_decode($dati['entity']['entity_action_fields'], true) ?: [];
}

$query = base64_encode("SELECT * FROM {$dati['entity']['entity_name']} LIMIT 50");
?>

<style>
    button.remove-relation {
        color: #5867dd;
        background: transparent;
        box-shadow: 0px 0px 0px transparent;
        border: 0px solid transparent;
        text-shadow: 0px 0px 0px transparent;
    }

    button.remove-relation:hover {
        color: #2739c1;
        background: transparent;
        box-shadow: 0px 0px 0px transparent;
        border: 0px solid transparent;
        text-shadow: 0px 0px 0px transparent;
    }

    button.remove-relation:active {
        outline: none;
        border: none;
    }

    button.remove-relation:focus {
        outline: 0;
    }

    .d-none {
        display: none;
    }
    
    .checkbox {
        padding-left: 20px;
    }
    
    .checkbox label {
        display: inline-block;
        vertical-align: middle;
        position: relative;
        padding-left: 5px;
    }
    
    .checkbox label::before {
        content: '';
        display: inline-block;
        position: absolute;
        width: 17px;
        height: 17px;
        left: 0;
        margin-left: -20px;
        border: 1px solid #cccccc;
        border-radius: 3px;
        background-color: #fff;
        -webkit-transition: border 0.15s ease-in-out, color 0.15s ease-in-out;
        -o-transition: border 0.15s ease-in-out, color 0.15s ease-in-out;
        transition: border 0.15s ease-in-out, color 0.15s ease-in-out;
    }
    
    .checkbox label::after {
        display: inline-block;
        position: absolute;
        width: 16px;
        height: 16px;
        left: 0;
        top: 0;
        margin-left: -20px;
        padding-left: 3px;
        padding-top: 1px;
        font-size: 11px;
        color: #555555;
    }
    
    .checkbox input[type='checkbox'] {
        opacity: 0;
        z-index: 1;
    }

    input[type='checkbox']:focus {
        outline: 0;
    }
    
    .checkbox input[type='checkbox']:focus + label::before {
        /*outline: thin dotted;*/
        /*outline: 5px auto -webkit-focus-ring-color;*/
        /*outline-offset: -2px;*/
    }
    
    .checkbox input[type='checkbox']:checked + label::after {
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        content: "\f00c";
    }
    
    .checkbox input[type='checkbox']:disabled + label {
        opacity: 0.65;
    }
    
    .checkbox input[type='checkbox']:disabled + label::before {
        background-color: #eeeeee;
        cursor: not-allowed;
    }
    
    .checkbox.checkbox-circle label::before {
        border-radius: 50%;
    }
    
    .checkbox.checkbox-inline {
        margin-top: 0;
    }
    
    .checkbox-primary input[type='checkbox']:checked + label::before {
        background-color: #428bca;
        border-color: #428bca;
    }
    
    .checkbox-primary input[type='checkbox']:checked + label::after {
        color: #fff;
    }
    
    .checkbox-danger input[type='checkbox']:checked + label::before {
        background-color: #d9534f;
        border-color: #d9534f;
    }
    
    .checkbox-danger input[type='checkbox']:checked + label::after {
        color: #fff;
    }
    
    .checkbox-info input[type='checkbox']:checked + label::before {
        background-color: #5bc0de;
        border-color: #5bc0de;
    }
    
    .checkbox-info input[type='checkbox']:checked + label::after {
        color: #fff;
    }
    
    .checkbox-warning input[type='checkbox']:checked + label::before {
        background-color: #f0ad4e;
        border-color: #f0ad4e;
    }
    
    .checkbox-warning input[type='checkbox']:checked + label::after {
        color: #fff;
    }
    
    .checkbox-success input[type='checkbox']:checked + label::before {
        background-color: #5cb85c;
        border-color: #5cb85c;
    }
    
    .checkbox-success input[type='checkbox']:checked + label::after {
        color: #fff;
    }
</style>

<div>
    <?php if (empty($dati['fields'])): ?>
        <h3 class="text-center">No fields for the <strong><?php echo $dati['entity']['entity_name'] ?></strong> entity</h3>
    <?php else: ?>
        <div class="clearfix">
            <a href='<?php echo base_url('main/layout/entities') ?>' class='btn btn-sm btn-default pull-left'>
                <i class='fas fa-chevron-left fa-fw'></i> <?php e('Go back to entities'); ?>
            </a>

            <a href='<?php echo base_url('main/layout/entities-query?query=' . $query) ?>' target='_blank' class='btn btn-sm btn-success pull-right'>
                <i class='fas fa-eye fa-fw'></i> <?php e('View all records'); ?>
            </a>
        </div>

        <hr>

        <table class="table table-bordered table-condensed table-hover table-striped">
            <thead>
                <tr>
                    <th>Name (<a href="javascript: void(0);" onclick="togglePrefix($(this))"><span class='d-none'>show</span><span>hide</span> prefix</a>)</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Options</th>
                    <th>Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="color: #5867dd; font-weight:400">
                        <i class="far fa-copy" style="cursor:pointer;" data-toggle="tooltip" title="Copy to clipboard" onclick="copyToClipboard($('#<?php echo $dati['entity']['entity_name'] ?>_id').data('field_name'));"></i>&nbsp;
                        <span class="d-none" id="<?php echo $dati['entity']['entity_name'] ?>_id" data-field_name="<?php echo $dati['entity']['entity_name'] ?>_id"></span>
                        <span class="js-entity_name_prefix"><?php echo $dati['entity']['entity_name'] ?>_</span>id
                    </td>
                    <td colspan="5">
                        INTEGER
                    </td>
                </tr>
                <?php foreach ($dati['fields'] as $f): ?>
                    <?php

                    $actionsAssigned = array_keys($entityCustomActions, $f['fields_name']);
                    $on_delete_cascade = (in_array('add_foreign_key', $actionsAssigned) && ($f['fields_required'] == FIELD_REQUIRED || $this->db->dbdriver == 'postgre'));
                    $on_delete_set_null = (in_array('add_foreign_key', $actionsAssigned) && ($f['fields_required'] != FIELD_REQUIRED && $this->db->dbdriver != 'postgre'));

                    $tr_class = '';

                    if ($on_delete_cascade) {
                        $tr_class = 'danger';
                    } elseif ($on_delete_set_null) {
                        $tr_class = 'warning';
                    }

                    ?>
                    <tr class="<?php echo $tr_class; ?>">
                        <td>
                            <i class="far fa-copy" style="cursor:pointer;" data-toggle="tooltip" title="Copy to clipboard" onclick="copyToClipboard($('#field_id_<?php echo $f['fields_id']; ?>').data('field_name'));"></i>&nbsp;

                            <a class="btn-link" id="field_id_<?php echo $f['fields_id']; ?>" href="<?php echo base_url("main/layout/entity-fields/{$f['fields_entity_id']}/{$f['fields_id']}"); ?>" data-field_name="<?php echo $f['fields_name']; ?>">
                                <span class=" js-entity_name_prefix"><?php echo $dati['entity']['entity_name']; ?>_</span><?php echo substr($f['fields_name'], strlen($dati['entity']['entity_name']) + 1); ?>
                            </a>
                        </td>

                        <td>
                            <?php echo strtoupper($f['fields_type']); ?><?php if ($f['fields_size']): ?>(<?php echo $f['fields_size']; ?>)<?php endif; ?>
                            (<?php echo $f['fields_draw_html_type'] ?>)
                            <?php if ($f['fields_multilingual'] == DB_BOOL_TRUE): ?>&nbsp; <i class="icon-flag" style="color:#CC0615" data-toggle="tooltip" title="Multilanguage"></i><?php endif; ?>
                        </td>
                        <td>
                            <div class='btn-group'>
                                <button type='button' class='btn btn-sm btn-link dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                    <?php if ($f['fields_required'] == FIELD_SOFT_REQUIRED): ?>
                                        <strong class="text-dark">Soft required</strong>
                                    <?php elseif ($f['fields_required'] == FIELD_REQUIRED): ?>
                                        <strong class="text-danger">Required</strong>
                                    <?php else: ?>
                                        Not required
                                    <?php endif; ?>

                                    <span class='caret'></span>
                                </button>
                                <ul class='dropdown-menu'>
                                    <li>
                                        <a href='javascript:void(0);' class="dropdown-item js-required <?php if ($f['fields_required'] == FIELD_NOT_REQUIRED): ?> bg-success text-white<?php endif; ?>" data-field_id="<?php echo $f['fields_id']; ?>" data-field_idenfier_value='<?php echo FIELD_NOT_REQUIRED; ?>'>
                                            <i class='flaticon2-edit text-white'></i>&nbsp;Not required
                                        </a>
                                    </li>
                                    <li>
                                        <a href='javascript:void(0);' class="dropdown-item js-required <?php if ($f['fields_required'] == FIELD_SOFT_REQUIRED): ?> bg-success text-white<?php endif; ?>" data-field_id="<?php echo $f['fields_id']; ?>" data-field_idenfier_value='<?php echo FIELD_SOFT_REQUIRED; ?>'>
                                            <i class='flaticon2-check-mark'></i>&nbsp;Soft required
                                        </a>
                                    </li>
                                    <li>
                                        <a href='javascript:void(0);' class="dropdown-item js-required <?php if ($f['fields_required'] == FIELD_REQUIRED): ?> bg-success text-white<?php endif; ?>" data-field_id="<?php echo $f['fields_id']; ?>" data-field_idenfier_value='<?php echo FIELD_REQUIRED; ?>'>
                                            <i class='flaticon2-check-mark text-dark'></i>&nbsp;Required
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>

                        <td>
                            <div class='checkbox checkbox-inline'>
                                <input class='field_identifier' data-field_id="<?php echo $f['fields_id']; ?>" data-field_identifier='fields_visible' type='checkbox' <?php if ($f['fields_visible'] == DB_BOOL_TRUE) echo 'checked="checked"'; ?>>
                                <label>Visible</label>
                            </div>
                            <div class='checkbox checkbox-inline'>
                                <input class='field_identifier' data-field_id="<?php echo $f['fields_id']; ?>" data-field_identifier='fields_preview' type='checkbox' <?php if ($f['fields_preview'] == DB_BOOL_TRUE) echo 'checked="checked"'; ?>>
                                <label>Preview</label>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($f['fields_ref']) {
                                if (in_array($f['fields_draw_html_type'], ['multiselect'])) {
                                    $badge_class = 'label-default';
                                    $type = 'relation';
                                } elseif ($f['entity_type'] == '2') { // is a support table entity
                                    $badge_class = 'label-info';
                                    $type = 'support table';
                                } else {
                                    $badge_class = 'label-primary';
                                    $type = 'entity';
                                }

                                echo anchor(sprintf('main/layout/entity-fields/%s', $f['entity_id']), $f['fields_ref'], ['data-toggle' => 'tooltip', 'title' => "Open {$type}", 'class' => 'bold label ' . $badge_class]);

                                if ($f['fields_draw_html_type'] == 'multiselect') {
                                    echo '&nbsp;<button class="remove-relation" data-toggle="tooltip" data-placement="top" title="Remove relation" data-field_ref="' . $f['fields_ref'] . '" data-field_id="' . $f['fields_id'] . '"><i class="fas fa-times"></i></button>';
                                }
                            } elseif (strtolower($f['fields_type']) == 'integer') {
                                if ($f['fields_draw_html_type'] == 'multiselect') {
                                    $this->load->module_view('core-entities/views/modals', 'create_relation_table', [
                                        'field_id' => $f['fields_id'],
                                        'entity_id' => $f['fields_entity_id'],
                                        'module' => $dati['entity']['entity_module'],
                                    ]);
                                } else {
                                    $this->load->module_view('core-entities/views/modals', 'create_support', [
                                        'field_id' => $f['fields_id'],
                                        'proposed_name' => $f['fields_name'],
                                    ]);
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <div class='btn-group'>
                                <button type='button' class='btn btn-default btn-sm dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                    More <span class='caret'></span>
                                </button>

                                <ul class='dropdown-menu dropdown-menu-right'>
                                    <?php
                                    foreach ($fieldCustomActions as $key => $attr) {
                                        $found = false;
                                        if (!empty($attr['available_for'])) {
                                            foreach ($attr['available_for'] as $available_for_attr) {
                                                if ($available_for_attr == '_referenced_by' && !empty($f['fields_ref'])) {
                                                    $found = true;
                                                } else {
                                                    if (strtolower($available_for_attr) == strtolower($f['fields_type'])) {
                                                        $found = true;
                                                    }
                                                }
                                            }
                                        }

                                        if (!$found) continue;
                                        
                                        $isAssigned = in_array($key, $actionsAssigned);

                                        $url = base_url("core-entities/core_entities/set_field_custom_action/{$f['fields_id']}/?action_key={$key}&add=" . ($isAssigned ? '0' : '1'));
                                        $icon = "<i class='{$attr['icon']}'></i>&nbsp;";
                                        $title = $icon . $attr['name'];

                                        if ($key == 'add_foreign_key') {
                                            if ($f['fields_required'] == FIELD_REQUIRED || $this->db->dbdriver == 'postgre') {
                                                $title .= ' (On delete cascade)';
                                            } else {
                                                $title .= ' (On delete set null)';
                                            }
                                        }

                                        $classes = ['js_link_ajax'];

                                        if ($isAssigned) {
                                            $classes[] = 'bg-success text-white';
                                        }

                                        $attributes = [
                                            'class' => implode(' ', $classes),
                                            'onclick' => "return confirm('" . t(($isAssigned ? 'Disattivare' : 'Attivare') . " l'opzione?") . "')",
                                        ];

                                        if (!empty($attr['tooltip'])) {
                                            $attributes['data-original-title'] = $attr['tooltip'];
                                        }

                                        $anchor = anchor($url, $title, $attributes);

                                        echo "<li>{$anchor}</li>\n";
                                    }
                                    
                                    ?>
                                    <li><a href='javascript: void(0);' class='btn-link' onclick="return openFieldModal('field_draw', '<?php echo $f['fields_id']; ?>', 'fields_draw_fields_id');" disabled><i class='fas fa-terminal fa-fw'></i> Field Draw</a></li>
                                    <li><a href='javascript: void(0);' class='btn-link' onclick="return openFieldModal('field_validation', '<?php echo $f['fields_id']; ?>', 'fields_validation_fields_id');" disabled><i class='fas fa-check-double fa-fw'></i> Field Validation</a></li>
                                    <li role='separator' class='divider'></li>
                                    <li><a href='javascript: void(0);' class='btn-link text-red' onclick="return askConfirmOnDeleteField('<?php echo $f['fields_id']; ?>');"><i class='fas fa-trash fa-fw'></i> Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    function askConfirmOnDeleteField(field_id) {
        if (!field_id || !confirm("Data of this field will be destroyed and cannot be restored. Are you sure?")) return false;
    
        $.ajax({
            url: base_url + 'core-entities/core_entities/delete_field/' + field_id,
            type: 'post',
            dataType: 'json',
            data: {
                [token_name]: token_hash,
            },
            success: function (response) {
                if (response.status == '1') {
                    window.location.href = response.url;
                    
                    return true;
                }
            
                $.toast({
                    heading: (response.status == '0' ? 'Error' : 'Success'),
                    text: response.txt,
                    icon: (response.status == '0' ? 'error' : 'success'),
                    loader: true,
                    position: 'top-right',
                    loaderBg: (response.status == '0' ? '#dd4b39' : '#00a65a')
                });
            }
        });
    }
    
    function togglePrefix(container) {
        $('.js-entity_name_prefix').toggleClass('d-none');
        $('span', container).toggleClass('d-none');
    }

    $(function() {
        $('.js-required').on('click', function () {
            var field_id = $(this).data('field_id');
            var field_identifier = 'fields_required';
            var field_identifier_value = $(this).data('field_idenfier_value');
            
            $.ajax({
                url: base_url + "core-entities/core_entities/update_field_identifier",
                type: "post",
                dataType: "json",
                data: {
                    [token_name]: token_hash,
                    field_id: field_id,
                    field_identifier: field_identifier,
                    field_identifier_value: field_identifier_value,
                },
                success: function(response) {
                    if (response.status == '1') {
                        location.reload();
                        return true;
                    }
    
                    $.toast({
                        heading: (response.status == '0' ? 'Error' : 'Success'),
                        text: response.txt,
                        icon: (response.status == '0' ? 'error' : 'success'),
                        loader: true,
                        position: 'top-right',
                        loaderBg: (response.status == '0' ? '#dd4b39' : '#00a65a')
                    });
                }
            });
        });

        $('.field_identifier').on('click', function () {
            var field_id = $(this).data('field_id');
            var field_identifier = $(this).data('field_identifier');
            var field_identifier_value = $(this).is(':checked') ? '1' : '0';
    
            $.ajax({
                url: base_url + 'core-entities/core_entities/update_field_identifier',
                type: 'post',
                dataType: 'json',
                data: {
                    [token_name]: token_hash,
                    field_id: field_id,
                    field_identifier: field_identifier,
                    field_identifier_value: field_identifier_value,
                },
                success: function (response) {
                    $.toast({
                        heading: (response.status == '0' ? 'Error' : 'Success'),
                        text: response.txt,
                        icon: (response.status == '0' ? 'error' : 'success'),
                        loader: true,
                        position: 'top-right',
                        loaderBg: (response.status == '0' ? '#dd4b39' : '#00a65a')
                    });
                }
            });
        });

        $('.remove-relation').on('click', function (e) {
            e.preventDefault();

            var this_btn = $(this);
            var delete_relation = false;
            var field_ref = this_btn.data('field_ref');
            var field_id = this_btn.data('field_id');

            if (!confirm('Are you sure?\nTHIS ACTION CANNOT BE REVERTED')) {
                return false;
            }

            $.ajax({
                url: base_url + 'datab/remove_relation',
                async: false,
                data: {
                    field_ref: field_ref,
                    field_id: field_id,
                    delete_relation: delete_relation
                },
                type: 'post',
                dataType: 'json',
                success: function (response) {
                    if (response.status == 0) {
                        //Alert e refresh
                        alert(response.txt);

                        if (response.hasOwnProperty('timeout')) {
                            setTimeout(function () {
                                window.location.reload();
                            }, response.timeout);
                        } else {
                            window.location.reload();
                        }
                        return false;
                    } else {
                        window.location.reload();
                    }
                }
            });
        });

        $('.js-add_support_value').trigger('click');
    })

    function submitSupportTable(modalId) {
        var modal = $('#' + modalId);
        var form = $('form', modal);
        form.submit();
        modal.modal('hide');
    }

    function submitRelation(modalId) { // Michael E. - 2021-03-11 - Sarebbe da unificare con la funzione subito sopra
        var modal = $('#' + modalId);
        var form = $('form', modal);
        form.submit();
        modal.modal('hide');
    }

    function insertSupportValue(modalId) {
        var clone = $('#' + modalId + ' .js_support_table_record').filter(':first').clone();
        var count = $('#' + modalId + ' .js_support_table_record').length;
        $('input', clone).each(function () {
            $(this).val('');
            var name = $(this).attr('data-name');
            $(this).attr('name', 'support_table[' + count + '][' + name + ']').removeAttr('data-name');
        });
        clone.removeClass('d-none').insertAfter($('#' + modalId + ' .js_support_table_record').filter(':last'));
        count++;
    }


    function removeSupportValue(modalId, id) {
        var container = $('#' + modalId + ' .js_old_values');
        $('.js_old_value_' + id, container).fadeOut('fast', function () {
            remove();
        });
    }

    function copyToClipboard(val) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(val).select();
        document.execCommand("copy");
        $temp.remove();
    }
</script>
