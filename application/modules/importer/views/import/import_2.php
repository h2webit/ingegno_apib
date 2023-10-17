<section class="content-header">
    <h4 class="page-title"><?php e('Importer'); ?> <small><?php e('Mapping'); ?></small></h4>
    <ol class="breadcrumb">
        <li><a href="#"><i clachoosess="fa fa-dashboard"></i> <?php e('Home'); ?></a></li>
        <li class="active"><?php e('Importer'); ?></li>
    </ol>
</section>
<section class="content">
    <div class="row">

        <form class="formAjax" id="import_map_form" action="<?php echo base_url('importer/db_ajax/import_2'); ?>" method="post">
            <?php add_csrf(); ?>
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <i class="fa fa-upload"></i>
                        <h4 class="box-title"><?php e('Select an entity to import'); ?> </h4>
                    </div>

                    <div class="box-body ">
                        <div class="form-group" style="overflow:auto">

                            <table id="mapping-table" class="table table-striped table-condensed table-bordered table-responsive-scrollable">
                                <thead>
                                    <tr>
                                        <?php foreach ($dati['csv_head'] as $k => $field) : ?>
                                            <?php if ($field) : ?>
                                                <th class="text-center">
                                                    <?php echo $field; ?><?php if ($dati['import_data']['action_on_data_present'] == 2 || $dati['import_data']['action_on_data_present'] == 4) : ?> (<input style="" type="radio" name="unique_key" value="<?php echo $k; ?>" /> <?php e('Unique Key') ?>)<?php endif; ?>
                                                    <br />
                                                    <select class="js-select-field" name="csv_fields[<?php echo $k; ?>]">
                                                        <option data-ref=""></option>
                                                        <option data-ref="" data-key="<?php echo $k; ?>" value="<?php echo $dati['entity'][0]['entity_name']; ?>_id"><?php echo $dati['entity'][0]['entity_name']; ?>_id</option>
                                                        <?php foreach ($dati['fields'] as $e_field) : ?>
                                                            <option data-ref="<?php echo $e_field['fields_ref']; ?>" data-key="<?php echo $k; ?>" value="<?php echo $e_field['fields_name']; ?>"><?php echo $e_field['fields_name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </th>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dati['csv_body'] as $row) : ?>
                                        <?php if (!empty($row)) : ?>
                                            <tr>
                                                <?php foreach ($row as $field) : ?>
                                                    <td><?php echo $field; ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="clearfix"></div>
                        </div>


                        <div class="form-group">
                            <div id="msg_import_map_form" class="alert alert-danger hide"></div>
                        </div>

                        <div class="form-group">
                            <div id="js_import_test_result" class="alert hide"></div>
                        </div>
                    </div>

                    <div class="box-footer">

                        <div class="form-group">
                            <input type="checkbox" name="save_mapping" value="1" />
                            <label>Save this mapping for the future imports</label>
                            <input type="text" name="importer_mappings_name" value="" size="30" placeholder="Choose a mapping name" />
                        </div>
                        <div class="form-actions fluid clearfix">
                            <a href="<?php echo base_url('importer'); ?>" class="btn btn-danger">Cancel</a>
                            <div class="pull-right">
                                <button type="button" class="btn bg-navy js_test_import">Test</button>
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
    $(document).ready(function() {
        $('.js-select-field').on('change', function() {
            $(this).siblings('select').remove();
            var current_select = $(this);
            var entity_name = $(this).find(":selected").attr('data-ref');
            var key = $(this).find(":selected").attr('data-key');
            if (entity_name != '') {
                //Fare ajax per chiedere su quale campo chiave mappare
                $.ajax(base_url + 'importer/db_ajax/get_fields_by_entity_name/' + entity_name, {
                    dataType: 'json',
                    success: function(fields) {

                        var new_select = $('<select class="js-select-field" />').attr('name', "ref_fields[" + key + "]");
                        $('<option/>').val(entity_name + "_id").text(entity_name + "_id").appendTo(new_select);

                        $.each(fields, function(i, field) {
                            $('<option/>').val(field.fields_name).text(field.fields_name).appendTo(new_select);
                        });

                        current_select.parent().append(new_select);
                    }
                });

            } else {
                //TODO: rimuovo eventuale select precedentemente appesa
            }
        });
    });
</script>
