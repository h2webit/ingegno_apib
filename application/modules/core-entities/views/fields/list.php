<?php
/*
 * Entity & Entity Fields Constants
 */

define('HTML_DRAW_TYPES', serialize([
    'text' => [
        'input_text' => ['label' => 'Input Text', 'sql_type' => 'VARCHAR'],
        'input_numeric' => ['label' => 'Input Text (numeric)', 'sql_type' => 'DOUBLE'],
        'input_money' => ['label' => 'Input Text (money)', 'sql_type' => 'DOUBLE'],
        'input_password' => ['label' => 'Password', 'sql_type' => 'VARCHAR'],
        'textarea' => ['label' => 'Textarea', 'sql_type' => 'LONGTEXT'],
        'wysiwyg' => ['label' => 'WYSIWYG', 'sql_type' => 'LONGTEXT'],
    ],
    'select' => [
        'select' => ['label' => 'Select', 'sql_type' => 'INTEGER'],
        'select_ajax' => ['label' => 'Select Ajax', 'sql_type' => 'INTEGER'],
        'multiselect' => ['label' => 'Multiselect', 'sql_type' => 'INTEGER'],
    ],
    'files' => [
        'upload' => ['label' => 'Upload', 'sql_type' => 'VARCHAR'],
        'upload_image' => ['label' => 'Upload Image', 'sql_type' => 'VARCHAR'],
        'multi_upload' => ['label' => 'Multi-Upload', 'sql_type' => 'JSON'],
        'multi_upload_no_preview' => ['label' => 'Multi-Upload (No preview)', 'sql_type' => 'JSON'],
    ],
    'dates' => [
        'date' => ['label' => 'Date', 'sql_type' => 'DATETIME'],
        'time' => ['label' => 'Time picker', 'sql_type' => 'VARCHAR'],
        'date_time' => ['label' => 'Date-time picker', 'sql_type' => 'DATETIME'],
        'date_range' => ['label' => 'Date-range picker', 'sql_type' => 'DATETIME'],
    ],
//    'maps' => [
//        'map' => ['label' => 'Map', 'sql_type' => ''],
//        'polygon' => ['label' => 'Polygon', 'sql_type' => ''],
//        'polygon_multi' => ['label' => 'Multi Polygon', 'sql_type' => 'MULTIPOLYGON'],
//    ],
    'others' => [
        'todo' => ['label' => 'ToDo', 'sql_type' => 'LONGTEXT'],
        'multiple_values' => ['label' => 'Multiple Values (TAGS)', 'sql_type' => 'LONGTEXT'],
        'multiple_key_values' => ['label' => 'Multiple Key Values', 'sql_type' => 'LONGTEXT'],
        'input_hidden' => ['label' => 'Input Hidden', 'sql_type' => 'INTEGER'],
        'checkbox' => ['label' => 'Checkbox', 'sql_type' => 'BOOLEAN'],
        'radio' => ['label' => 'Radio', 'sql_type' => 'BOOLEAN'],
        'color' => ['label' => 'Color', 'sql_type' => 'VARCHAR'],
        'color_palette' => ['label' => 'Color Palette', 'sql_type' => 'VARCHAR'],
        'stars' => ['label' => 'Stelline', 'sql_type' => 'INTEGER'],
        'int_range_base' => ['label' => 'Range', 'sql_type' => 'INTEGER'],
        'int_range_slider' => ['label' => 'Range Slider', 'sql_type' => 'INTEGER'],
    ],
]));

define('HTML_DRAW_TYPES_ICONS', serialize([
    'todo' => 'fa-tasks',
    'multiple_values' => 'fa-list-ul',
    'multiple_key_values' => 'fa-th-list',
    'input_text' => 'fa-text-width',
    'input_password' => 'fa-key',
    'textarea' => 'fa-italic',
    'wysiwyg' => 'fa-file-code-o',
    'select' => 'fa-list-ol',
    'select_ajax' => 'fa-spinner',
    'multiselect' => 'fa-th-list',
    'upload' => 'fa-upload',
    'upload_image' => 'fa-picture-o',
    'multi_upload' => 'fa-cloud-upload',
    'date' => 'fa-calendar-o',
    'time' => 'fa-times-circle-o',
    'date_time' => 'fa-calendar-times-o',
    'date_range' => 'fa-arrows-h',
    'map' => 'fa-map',
    'polygon' => 'fa-map-signs',
    'polygon_multi' => 'fa-street-view',
    'input_hidden' => 'fa-eye-slash',
    'checkbox' => 'fa-check-square',
    'radio' => 'fa-check-circle',
    'color' => 'fa-thumb-tack',
    'color_palette' => 'fa-thumb-tack',
    'stars' => 'fa-star-half-o',
    'int_range_base' => 'fa-sort-numeric-desc',
    'int_range_slider' => 'fa-sort-numeric-asc',
]));

define('SQL_TYPES', serialize([
    'VARCHAR',
    'INTEGER',
    'DOUBLE',
    'BOOLEAN',
    'LONGTEXT',
    'DATERANGE',
    'DATETIME',
    'GEOGRAPHY',
    'POLYGON',
    'MULTIPOLYGON',
    'JSON',
]));

define('CUSTOM_ACTIONS_FIELDS', serialize([
    'create_time' => [
        'name' => 'Creation Timestamp',
        'icon' => 'fa fa-calendar-plus',
        'available_for' => ['DATETIME'],
    ],
    'update_time' => [
        'name' => 'Last Edited Timestamp',
        'icon' => 'fa fa-edit',
        'available_for' => ['DATETIME'],
    ],
    'soft_delete_flag' => [
        'name' => 'Flag soft delete',
        'icon' => 'fa fa-trash',
        'tooltip' => 'If you want that your records will be never deleted for this entity, check this, and Open Builder will manage it as a soft-delete flag.',
        'available_for' => ['BOOL', 'BOOLEAN'],
    ],
    'add_foreign_key' => [
        'name' => 'Add Foreign key',
        'icon' => 'fa fa-caret-square-down',
        'tooltip' => 'Set this field as a foreign key for a specific entity',
        'available_for' => ['_referenced_by'],
    ],
    'order_by_asc' => [
        'name' => 'Default order by',
        'icon' => 'fa fa-sort-alpha-down',
        'tooltip' => 'Set this field as the default order by (ASC)',
    ],
    'order_by_desc' => [
        'name' => 'Default order by',
        'icon' => 'fa fa-sort-alpha-up',
        'tooltip' => 'Set this field as the default order by (DESC)',
    ],
]));

/*
 * Validation
 */
define('VALIDATION_TYPES', serialize(
                array(
                    // Semplici
                        'valid_email' => 'valid_email',
                        'valid_emails' => 'valid_emails',
                        'integer' => 'integer',
                        'numeric' => 'numeric',
                        'is_natural' => 'is_natural',
                        'is_natural_no_zero' => 'is_natural_no_zero',
                        'alpha' => 'alpha',
                        'alpha_numeric' => 'alpha_numeric',
                        'alpha_dash' => 'alpha_dash',
                    // Con parametri
                        'decimal' => 'decimal',
                        'is_unique' => 'is_unique',
                        'min_length' => 'min_length',
                        'max_length' => 'max_length',
                        'exact_length' => 'exact_length',
                        'greater_than' => 'greater_than',
                        'less_than' => 'less_than',
                    // Custom
                        'date_after' => 'date_after',
                        'date_before' => 'date_before',
                )
        )
);

/*
 * Forms
 */
define('FORM_LAYOUTS', serialize(
                array(
                        'vertical' => 'Vertical',
                        'vertical2col' => 'Vertical 2 columns',
                        'horizontal' => 'Horizontal',
                        'filter' => 'Filter',
                        'filter_select' => 'Filter distinct values',
                )
        )
);

define('FORM_FIELD_DATA_TYPES', serialize(
                array(
                        'session' => 'Session',
                        'static_value' => 'Static value',
                        'variable' => 'Variable',
                        'function' => 'Function',
                )
        )
);

$entity_id = $value_id;
$field_id = $this->uri->segment(5) ?? null;
$entity = $this->db->where('entity_id', $entity_id)->get('entity')->row_array();

$this->entities_list = $this->db->order_by('entity_name', 'ASC')->get('entity')->result_array();

if (empty($entity)) {
    echo '<div class="alert alert-danger">Entity not found!</div>';
    return;
}

$dati['entity'] = $this->db->get_where('entity', array('entity_id' => $entity_id))->row_array();
$dati['fields'] = $this->db->from('fields')->join('fields_draw', 'fields_id=fields_draw_fields_id', 'left')->join('entity', 'fields_ref=entity_name', 'left')->where('fields_entity_id', $entity_id)->get()->result_array();
$dati['forms'] = $this->db->where('forms_entity_id', $entity_id)->order_by('forms_name')->get('forms')->result_array();
$dati['grids'] = $this->db->where('grids_entity_id', $entity_id)->order_by('grids_name')->get('grids')->result_array();
$dati['maps'] = $this->db->where('maps_entity_id', $entity_id)->order_by('maps_name')->get('maps')->result_array();
$dati['calendars'] = $this->db->where('calendars_entity_id', $entity_id)->order_by('calendars_name')->get('calendars')->result_array();
$dati['action'] = 'add';

// Get entity Events
$dati['fi_events'] = $this->db
        ->join('post_process', 'post_process_id = fi_events_post_process_id', 'LEFT')
        ->where('post_process_entity_id', $entity_id)
        ->order_by('fi_events_when', 'ASC')
        ->get('fi_events')->result_array();

$event_id = $dati['fi_events']['fi_events_id'] ?? null;

if ($event_id) {
    $dati['event'] = $this->db->get_where('fi_events', ['fi_events_id' => $event_id])->row_array();
}

$this->load->model('fi_events');
$dati['fi_events'] = array_map(function ($ev) {
    $CI = get_instance();
    return $CI->fi_events->additionalData($ev);
}, $dati['fi_events']);

if ($field_id !== null) {
    $dati['field'] = $this->db->from('fields')->join('fields_draw', 'fields_id=fields_draw_fields_id', 'left')->where('fields_entity_id', $entity_id)->where('fields_id', $field_id)->get()->row_array();

    // Check if this fields used inside an events
    $dati['events_count'] = $this->db->query("SELECT COUNT(*) AS count FROM fi_events WHERE fi_events_actiondata LIKE '%{$dati['field']['fields_name']}%'")->row()->count;
}

// Check soft delete and suggestion
$dati['joined_entities'] = [];
if (empty($dati['entity']['entity_action_fields']) || empty(json_decode($dati['entity']['entity_action_fields'], true)['soft_delete_flag'])) {
    $joined_entities = $this->db->query("SELECT * FROM fields LEFT JOIN entity ON entity.entity_id = fields.fields_entity_id WHERE fields_ref = '{$dati['entity']['entity_name']}'")->result_array();
    $dati['joined_entities'] = count($joined_entities);

    $joined_list = [];
    foreach ($joined_entities as $join) {
        $joined_list[$join['entity_name']][] = $join['fields_name'];
    }

    $dati['joined_entites_list'] = $joined_list;
}

?>

<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css'/>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js'></script>

<div class="col-sm-6 col-sm-offset-3">
    <?php if (!empty($dati['joined_entities']) && is_array($dati['joined_entities']) && count($dati['joined_entities']) > 3): ?>
        <div class='callout callout-warning'>
            This entity is joined by <?php echo implode(', ', $dati['joined_entities']); ?> other entities. It is recommended to use
            the "soft-delete" function to enable the recycle bin. To avoid losing data irretrievably due to human error.
        </div>
    <?php endif; ?>
</div>

<div class='nav-tabs-custom'>
    <ul class='nav nav-tabs pull-right'>
        <li><a href='#maps_list' data-toggle='tab'>Maps</a></li>
        <li><a href='#calendars_list' data-toggle='tab'>Calendars</a></li>
        <li><a href='#grids_list' data-toggle='tab'>Grids</a></li>
        <li><a href='#forms_list' data-toggle='tab'>Forms</a></li>
        <li><a href='#events_list' data-toggle='tab'>Events</a></li>
        <?php if (!empty($dati['joined_entites_list'])): ?>
            <li><a href='#joined_entities' data-toggle='tab'>Joined</a></li>
        <?php endif; ?>
        <li class='active'><a href='#fields_list' data-toggle='tab'>Fields</a></li>

        <li class='pull-left header'>Field list for entity <strong><?php echo $dati['entity']['entity_name'] ?></strong></li>
    </ul>

    <div class='tab-content'>
        <div class='tab-pane active' id='fields_list'>
            <?php
            switch ($dati['action']) {
                case 'draw':
//                    $this->load->view('pages/box/entity_fields/fields_list', array('dati' => $dati));
//                    $this->load->view('pages/box/entity_fields/draw', array('dati' => $dati));
                    break;

                default:
                    $this->load->module_view('core-entities/views/tabs', 'fields', ['dati' => $dati]);
//                    $this->load->module_view('core-entities/views/fields', 'add_edit', ['dati' => $dati]);
                    break;
            }
            ?>
        </div>
        <?php if (!empty($dati['joined_entites_list'])): ?>
            <div class='tab-pane' id='joined_entities'>

            </div>
        <?php endif; ?>

        <div class='tab-pane' id='events_list'>
            <?php $this->load->module_view('core-entities/views/tabs', 'events', ['dati' => $dati]); ?>
        </div>
        <div class='tab-pane' id='forms_list'>
            <?php $this->load->module_view('core-entities/views/tabs', 'forms', ['dati' => $dati]); ?>

        </div>
        <div class='tab-pane' id='grids_list'>
            <?php $this->load->module_view('core-entities/views/tabs', 'grids', ['dati' => $dati]); ?>

        </div>
        <div class='tab-pane' id='maps_list'>
            <?php $this->load->module_view('core-entities/views/tabs', 'maps', ['dati' => $dati]); ?>

        </div>
        <div class='tab-pane' id='calendars_list'>
            <?php $this->load->module_view('core-entities/views/tabs', 'calendars', ['dati' => $dati]); ?>

        </div>
    </div>
</div>

<div class='box box-danger d-none'>
    <div class='box-header with-border'>
        <h3 class='box-title'><?php e("Add fields to entity:"); ?> <strong><?php echo $dati['entity']['entity_name'] ?></strong></h3>
    </div>
</div>

<?php
$this->load->module_view('core-entities/views/fields', 'add_edit', ['dati' => $dati]);

$this->load->module_view('core-entities/views/modals', 'fields_draw', ['dati' => $dati]);
$this->load->module_view('core-entities/views/modals', 'fields_validations', ['dati' => $dati]);
?>

<div style="margin-bottom: 50px;"></div>
