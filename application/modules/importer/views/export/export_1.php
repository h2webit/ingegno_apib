<section class="content-header">
    <h4 class="page-title"><?php e('Export'); ?> <small><?php e('Export to a CSV file'); ?></small></h4>

    <ol class="breadcrumb">
        <li>Home</li>
        <li>Importer</li>
        <li class="active">Export</li>
    </ol>
</section>

<section class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header">
                    <i class="fa fa-download"></i>
                    <h4 class="box-title">
                        <?php e('Select an entity to export'); ?>
                    </h4>

                    <a class="btn btn-primary js_open_modal pull-right" href="<?php echo base_url('get_ajax/layout_modal/importer-mappings?export=1'); ?>">
                        <i class="fas fa-cogs"></i> Mappings
                    </a>
                </div>

                <div class="box-body">
                    <form class="formAjax" id="export_form" action="<?php echo base_url('importer/db_ajax/export_1'); ?>">
                        <?php add_csrf(); ?>

                        <div class="form-group row">
                            <label class="control-label col-sm-3"><?php e('Use CSV mapping'); ?></label>
                            <div class="col-sm-5">
                                <select class="form-control js_importer_mappings_id" name="importer_mappings_id">
                                    <option></option>
                                    <?php foreach ($dati['importer_mappings'] as $e) : ?>
                                    <option data-entity_name="<?php echo $e['entity_name'] ?>" value="<?php echo $e['importer_mappings_id'] ?>"><?php echo $e['importer_mappings_name'] ?> (<?php echo $e['entity_name'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="clearfix"></div>
                        </div>

                        <div class="form-group row">
                            <label class="control-label col-sm-3"><?php e('Entity to export'); ?></label>

                            <div class="col-sm-5">
                                <select class="form-control js_entity_id" name="entity_id">
                                    <option></option>
                                    <?php foreach ($dati['entities'] as $e) : ?>
                                    <option value="<?php echo $e['entity_id'] ?>"><?php echo $e['entity_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="clearfix"></div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-12">
                                <a href="#" class="js_toggle_option">Advanced option -></a>
                            </div>
                        </div>

                        <div class="js_advanced_option hide">
                            <div class="form-group row">
                                <label class="control-label col-sm-3"><?php e('Field separator'); ?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="input-xsmall form-control" name="field_separator" value=";" />
                                </div>
                                <div class="clearfix"></div>
                            </div>

                            <div class="form-group row">
                                <label class="control-label col-sm-3"><?php e('Multiple values separator'); ?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="input-xsmall form-control" name="multiple_values_separator" value="," />
                                </div>
                                <div class="clearfix"></div>
                            </div>

                            <div class="form-group row">
                                <label class="control-label col-sm-3"><?php e('Use Apilib?'); ?></label>
                                <div class="col-sm-5">
                                    <label class="radio-inline">
                                        <input type="radio" name="use_apilib" value="0" checked="checked" /> <?php e('No'); ?>
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="use_apilib" value="1" /> <?php e('Yes'); ?>
                                    </label>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <dov class="col-sm-12">
                                <div id="msg_export_form" class="alert alert-danger hide"></div>
                            </dov>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-offset-8 col-sm-4">
                                <div class="pull-right">
                                    <button type="submit" class="btn btn-primary "><?php e('Continue'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('.js_importer_mappings_id').on('change', function() {
        var entita = $(this).find(':selected').data('entity_name');

        $('.js_entity_id option').map(function() {
            if ($(this).text() == entita) return this;
        }).attr('selected', 'selected');
    });

    $('.js_toggle_option').on('click', function() {
        $('.js_advanced_option').toggleClass('hide');
    });
});
</script>