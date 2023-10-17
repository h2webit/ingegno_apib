<section class="content-header">
    <h4 class="page-title"><?php e('Importer'); ?> <small><?php e('import from a CSV file'); ?></small></h4>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> <?php e('Home'); ?></a></li>
        <li class="active"><?php e('Importer'); ?></li>
    </ol>
</section>

<section class="content">

    <div class="row">
        <div class="col-md-12">
            <span data-toggle="tooltip" title="" data-original-title="Show stocks">
                <a class="btn btn-primary js_open_modal" href="<?php echo base_url(); ?>get_ajax/layout_modal/importer-mappings">
                    <i class="fas fa-cogs"></i> Mappings
                </a>
            </span>
        </div>
        <br />&nbsp;
    </div>

    <div class="row">
        <div class="col-md-12">
            <form class="formAjax" id="import_form" action="<?php echo base_url('importer/db_ajax/import_1'); ?>" method="post" enctype="multipart/form-data">
                <?php add_csrf(); ?>
                <div class="col-md-12">
                    <div class=" box box-primary">
                        <div class="box-header">
                            <i class="fa fa-upload"></i>
                            <h4 class="box-title"><?php e('Select an entity to import'); ?> </h4>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3"><?php e('Use CSV mapping'); ?></label>
                            <div class="col-md-5">
                                <select class="form-control js_importer_mappings_id" name="importer_mappings_id">
                                    <option></option>
                                    
                                    <?php foreach ($dati['importer_mappings'] as $mapping) : ?>
                                        <option data-entity_name="<?php echo $mapping['entity_name'] ?>" value="<?php echo $mapping['importer_mappings_id'] ?>"><?php echo $mapping['importer_mappings_name'] ?> (<?php echo $mapping['entity_name'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="clearfix"></div>
                        </div>

                        <div class="box-body ">
                            <div class="form-group">
                                <label class="control-label col-md-3"><?php e('Entity to import'); ?></label>
                                <div class="col-md-5">
                                    <select class="form-control js_entity_id" name="entity_id">
                                        <option></option>
                                        <?php foreach ($dati['entities'] as $entity) : ?>
                                            <option value="<?php echo $entity['entity_id'] ?>" <?php echo (!empty($dati['entity_id']) && $dati['entity_id'] == $entity['entity_id']) ? 'selected' : '' ?>><?php echo $entity['entity_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="clearfix"></div>
                            </div>



                            <div class="form-group">
                                <label class="control-label col-md-3">CSV file</label>
                                <div class="col-md-5">
                                    <input type="file" name="csv_file" class="form-control" />
                                </div>
                                <div class="clearfix"></div>
                            </div>

                            <div class="form-group">
                                <a href="#" class="js_toggle_option">Advanced option -></a>
                            </div>

                            <div class="js_advanced_option hide">
                                <div class="form-group">
                                    <label class="control-label col-md-3"><?php e('Field separator'); ?></label>
                                    <div class="col-md-5">
                                        <input type="text" class="input-xsmall form-control" name="field_separator" value=";" />
                                    </div>
                                    <div class="clearfix"></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-3"><?php e('Multiple values separator'); ?></label>
                                    <div class="col-md-5">
                                        <input type="text" class="input-xsmall form-control" name="multiple_values_separator" value="," />
                                    </div>
                                    <div class="clearfix"></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-3"><?php e('Use Apilib?'); ?></label>
                                    <div class="col-md-5">
                                        <label class="radio-inline">
                                            <input type="radio" name="use_apilib" value="0" checked="checked" /> <?php e('No'); ?>
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="use_apilib" value="1" /> <?php e('Yes'); ?>
                                        </label>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-3"><?php e('Action on data present?'); ?></label>
                                    <div class="col-md-9">
                                        <label class="radio-inline">
                                            <input type="radio" name="action_on_data_present" value="1" /> <?php e('Delete data before import'); ?>
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="action_on_data_present" value="2" /> <?php e('Update data (will ask for unique key)'); ?>
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="action_on_data_present" value="4" checked /> <?php e('Update data (will ask for unique key), insert if key not present'); ?>
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="action_on_data_present" value="3" /> <?php e('Insert'); ?>
                                        </label>

                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="controls">
                                    <div id="msg_import_form" class="alert alert-danger hide"></div>
                                </div>
                            </div>

                            <div class="form-actions fluid">
                                <div class="col-md-offset-8 col-md-4">
                                    <div class="pull-right">
                                        <button type="submit" class="btn btn-primary "><?php e('Continue'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
