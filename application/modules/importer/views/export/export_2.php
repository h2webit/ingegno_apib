<script src="https://cdnjs.cloudflare.com/ajax/libs/multiselect/2.2.9/js/multiselect.min.js"></script>

<section class="content-header">
    <h4 class="page-title"><?php e('Exporter'); ?> <small><?php e('Mapping'); ?></small></h4>
    <ol class="breadcrumb">
        <li><a href="#"><i clachoosess="fa fa-dashboard"></i> <?php e('Home'); ?></a></li>
        <li class="active"><?php e('Exporter'); ?></li>
    </ol>
</section>
<section class="content">
    <div class="row">

        <form class="formAjax" id="export_map_form" action="<?php echo base_url('importer/db_ajax/export_2'); ?>">
            <?php add_csrf(); ?>

            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <i class="fa fa-upload"></i>
                        <h4 class="box-title"><?php e('Select an entity to export'); ?> </h4>
                    </div>

                    <div class="box-body ">
                        <div class="row">
                            <div class="col-sm-5">
                                <select id="multiselect" class="form-control" size="8" multiple="multiple">
                                    <?php foreach ($dati['fields'] as $field_key => $field): ?>
                                    <option data-ref="<?php echo $field['fields_ref']; ?>" data-key="<?php echo $field_key; ?>" value="<?php echo $field['fields_name']; ?>"><?php echo $field['fields_name']; ?></option>
                                    <?php if (!empty($field['fields_ref']) && !empty($field[$field['fields_ref']]['fields'])): ?>
                                    <optgroup label="Ref. <?php echo $field['fields_ref']; ?>">
                                        <?php foreach ($field[$field['fields_ref']]['fields'] as $ref_field_key => $ref_field): ?>
                                        <option data-ref="<?php echo $ref_field['fields_ref']; ?>" data-key="<?php echo $ref_field_key; ?>" value="<?php echo $ref_field['fields_name']; ?>"><?php echo $ref_field['fields_name']; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-sm-2">
                                <button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
                                <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
                                <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
                                <button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
                            </div>

                            <div class="col-sm-5">
                                <select name="to[]" id="multiselect_to" class="form-control" size="8" multiple="multiple"></select>

                                <!-- Da capire perchè non funzionano i due bottoni (sono roba nativa del plugin, perciò boh...) -->
                                <!-- <div class="row" style="margin-top: 10px;">
                                    <div class="col-sm-6">
                                        <button type="button" id="multiselect_move_up" class="btn btn-block"><i class="glyphicon glyphicon-arrow-up"></i></button>
                                    </div>
                                    <div class="col-sm-6">
                                        <button type="button" id="multiselect_move_down" class="btn btn-block col-sm-6"><i class="glyphicon glyphicon-arrow-down"></i></button>
                                    </div>
                                </div> -->
                            </div>
                        </div>

                        <div class="form-group">
                            <div id="msg_export_map_form" class="alert alert-danger hide"></div>
                        </div>

                        <div class="form-group">
                            <div id="js_export_test_result" class="alert hide"></div>
                        </div>
                    </div>

                    <div class="box-footer">

                        <div class="form-group">
                            <input type="checkbox" name="save_mapping" value="1" />
                            <label>Save this mapping for the future exports</label>
                            <input type="text" name="exporter_mappings_name" value="" size="30" placeholder="Choose a mapping name" />
                        </div>
                        <div class="form-actions fluid clearfix">
                            <a href="<?php echo base_url('importer'); ?>" class="btn btn-danger">Cancel</a>
                            <div class="pull-right">
                                <button type="submit" class="btn btn-primary">Continue</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
$(function() {
    $('#multiselect').multiselect();
});
</script>