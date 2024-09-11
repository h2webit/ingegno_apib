<?php
$this->entities_list = $this->db->order_by('entity_name', 'ASC')->get('entity')->result_array();
$this->modules_list = $this->db->order_by('modules_name')->get('modules')->result_array();
?>

<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css'/>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js'></script>

<style>
    .dataTables_wrapper {
        position: relative;
        z-index: 1;
    }
</style>

<div class='row' style="margin-bottom: 10px;">
    <div class='col-md-12'>
        <a href="<?php echo base_url('main/layout/new-entity'); ?>" class='btn btn-primary' disabled><i class='fas fa-plus fa-fw'></i> <?php e("Create a new Entity"); ?></a>

        <div class='pull-right'>
            <a href="<?php echo base_url('main/layout/entities-query'); ?>" class='btn btn-warning'><i class='fas fa-terminal fa-fw'></i> <?php e('Run Query'); ?></a>
            <a href='?system=1' class='btn btn-danger'><i class="fas fa-shield-alt fa-fw"></i> <?php e("Show system entities"); ?></a>
        </div>
    </div>
</div>

<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js'></script>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css'/>

<div class='row'>
    <div class='col-md-12'>
        <div class='box box-primary'>
            <div class='box-header with-border'>
                <h3 class='box-title'><?php e("Entities List"); ?></h3>
            </div>


            <div class='box-body'>
                <div class='alert alert-info'>
                    <h4><i class='icon fa fa-info'></i> <?php e("What is an entity?"); ?></h4>

                    <?php e('An entity is the representation of a table in a database'); ?>
                </div>

                <hr>

                <table id="entities" class='table table-bordered table-condensed table-striped table-hover nowrap' style="width: 100%">
                    <thead>
                        <tr>
                            <th><?php e("Name"); ?></th>
                            <th data-searchable='false'><?php e("Modules"); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php foreach ($this->entities_list as $e) : ?>
                            <?php
                            if ($e['entity_type'] == ENTITY_TYPE_SYSTEM && $this->input->get('system') !== 1) continue;

                            $associated_modules = explode(',', $e['entity_module']);
                            $query = base64_encode("SELECT * FROM {$e['entity_name']}");
                            ?>
                            <tr>
                                <td><a href='<?php echo base_url("main/layout/entity-fields/{$e['entity_id']}") ?>' class='btn-link'><?php echo $e['entity_name'] ?></a></td>
                                <td>
                                    <?php if ($e['entity_type'] != ENTITY_TYPE_SYSTEM) : ?>
                                        <select class="form-control input-sm module_switch" name="entity[entity_module]" multiple data-entity="<?php echo $e['entity_id']; ?>">
                                            <option></option>
                                            <?php foreach ($this->modules_list as $module) : ?>
                                                <option value="<?php echo $module['modules_identifier']; ?>" <?php if ($e['entity_module'] && in_array($module['modules_identifier'], $associated_modules)) : ?> selected="selected" <?php endif; ?>><?php echo $module['modules_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class='btn-group'>
                                        <button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                            Action <span class='caret'></span>
                                        </button>

                                        <ul class='dropdown-menu'>
                                            <li><a href='<?php echo base_url('main/layout/entities-query/?query=' . $query) ?>' target="_blank"><i class='fas fa-eye fa-fw'></i> <?php e("View all records"); ?></a></li>
                                            <li><a href='<?php echo base_url("datab/fix_entity_sequences/{$e['entity_id']}") ?>'><i class='fa fa-cogs fa-fw'></i> <?php e("Fix Sequence"); ?></a></li>
                                            <li><a href='javascript: void(0);' class='btn-link' onclick="return askConfirmOnEmptyEntity('<?php echo $e['entity_name']; ?>');"><i class='fas fa-fire fa-fw'></i> <?php e("Empty table"); ?></a></li>
                                            <li role='separator' class='divider'></li>
                                            <li><a href='javascript: void(0);' class='btn-link' onclick="return askConfirmOnDeleteEntity(<?php echo "'{$e['entity_id']}', '{$e['entity_name']}'"; ?>);"><i class='fas fa-trash fa-fw'></i> <?php e("Delete"); ?></a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function askConfirmOnDeleteEntity(id, name) {
        if (!confirm("Eliminando l'entità " + name + ' verranno eliminati anche tutte le sue referenze. Sei sicuro?')) {
            return false;
        }

        $.ajax({
            url: base_url + 'core-entities/core_entities/delete_entity',
            dataType: 'json',
            type: 'post',
            data: {
                [token_name]: token_hash,
                entity_id: id,
                entity_name: name
            },
            success: function (response) {
                if (response.status == '1') {
                    location.reload();
                    
                    return false;
                }
    
                $.toast({
                    heading: (response.status == '0' ? 'Error' : 'Success'),
                    text: response.txt,
                    icon: (response.status == '0' ? 'error' : 'success'),
                    loader: true,
                    position: 'top-right',
                    loaderBg: (response.status == '0' ? '#dd4b39' : '#00a65a')
                });
            },
            error: function (status, request, error) {
                $.toast({
                    heading: 'Error',
                    text: error,
                    icon: 'error',
                    loader: true,
                    position: 'top-right',
                    loaderBg: '#dd4b39'
                });
            }
        });
    }

    function askConfirmOnEmptyEntity(name) {
        if (!confirm("Svuotando l'entità " + name + ' verranno eliminati tutti i dati ad essa correlati. Sei sicuro?')) {
            return false;
        }

        // $.ajax(base_url + 'datab/truncate/' + json.id, {
        //     dataType: 'json',
        //     success: function () {
        //         window.location.reload();
        //     }
        // });

        return true;
    }

    $(function () {
        $('#entities').DataTable({
            scrollX: false,
            scrollY: false,
            saveState: true,
            lengthMenu: [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, 'All']
            ],
            pageLength: 10,
            order: [
                [0, 'asc']
            ],
            drawCallback: function (dt) {
                $('.module_switch').select2();
            }
        });

        $('.module_switch').on('change', function () {
            const entity_id = $(this).data('entity');
            const selected_modules = $(this).val();

            $.ajax({
                url: base_url + 'core-entities/core_entities/set_module/entity/' + entity_id,
                type: 'post',
                dataType: 'json',
                async: false,
                data: {
                    [token_name]: token_hash,
                    modules: selected_modules
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
                },
                error: function (status, request, error) {
                    $.toast({
                        heading: 'Error',
                        text: error,
                        icon: 'error',
                        loader: true,
                        position: 'top-right',
                        loaderBg: '#dd4b39'
                    });
                }
            });
        });
    });
</script>
