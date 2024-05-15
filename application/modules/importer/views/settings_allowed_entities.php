<?php
// Get entities list
$entities = $this->db->order_by('entity_name')->where('entity_type', 1)->get('entity')->result_array();

$module_settings = $this->apilib->searchFirst('importer_settings');

$allowed_entities = (!empty($module_settings['importer_settings_allowed_entities'])) ? json_decode($module_settings['importer_settings_allowed_entities'], true) : [];
?>
<p style="font-weight:bold">
    <?php e('Select the entities you want to make available in exports. Use the checkbox only to add/remove entities.'); ?>
</p>
<div class="row">
    <form method="post" class="formAjax" action="<?php echo base_url();?>importer/export/save_allowed_entities">
        <?php add_csrf(); ?>
        <?php foreach($entities as $entity): ?>
            <div class="col-md-3">
                <input type="checkbox" name="allowed_entities[]" value="<?php echo $entity['entity_name']; ?>" <?php echo (in_array($entity['entity_name'], $allowed_entities)) ? 'checked="checked"' : '' ?> />
                <?php echo $entity['entity_name']; ?><br />
            </div>
        <?php endforeach; ?>
        
        <div class="col-md-12">
            <hr/>
            <button type="submit" class="btn btn-primary"><?php e('Save'); ?></button>
        </div>
    </form>
</div>