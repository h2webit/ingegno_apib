<?php $this->layout->addModuleStylesheet('importer', 'plugins/codemirror/codemirror.min.css'); ?>
<?php $this->layout->addModuleStylesheet('importer', 'plugins/codemirror/material.css'); ?>
<?php $this->layout->addModuleStylesheet('importer', 'plugins/codemirror/fold/foldgutter.css'); ?>
<?php $this->layout->addModuleStylesheet('importer', 'plugins/codemirror/fullscreen.css'); ?>

<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/codemirror.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/sql.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/sql-hint.min.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/fold/foldcode.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/fold/foldgutter.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/fold/brace-fold.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/fullscreen.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/codemirror/autorefresh.js'); ?>

<?php $this->layout->addModuleStylesheet('importer', 'css/jquery_builder_custom.css'); ?>

<?php $this->layout->addModuleStylesheet('importer', 'plugins/querybuilder/query-builder.default.min.css'); ?>
<?php //$this->layout->addModuleJavascript('importer', 'plugins/querybuilder/query-builder.min.js'); ?>
<?php $this->layout->addModuleJavascript('importer', 'plugins/querybuilder/query-builder.standalone.min.js'); ?>

<?php $this->layout->addModuleJavascript('importer', 'plugins/sql-parser.js'); ?>

<style>
    .where_builder > div {
        width:100%;
    }
</style>

<section class="content-header">
    <h4 class="page-title"><?php e('Export'); ?> <small><?php e('Export your template'); ?></small></h4>
    
    <ol class="breadcrumb">
        <li>Home</li>
        <li>Importer</li>
        <li>Export</li>
        <li class="active"><?php e('New Template'); ?></li>
    </ol>
</section>

<section class="content">
    <div class="row">
        <div class="col-sm-12 clearfix">
            <div class="pull-left">
                <a href="<?php echo base_url('main/layout/exporter-templates-admin'); ?>" class="btn bg-maroon"><i class="fas fa-chevron-left fa-fw"></i> <?php e('Back to templates list'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="row" style="margin-bottom: 10rem;">
        <div class="col-sm-8 col-sm-offset-2">
            <div class="box box-primary">
                <div class="box-header">
                    <i class="fa fa-download"></i>
                    
                    <h4 class="box-title">
                        <?php e( (!empty($dati['exporter_tpl']) ? (!empty($this->input->get('clone')) ? 'Clone' : 'Edit') : 'Create') . ' your template'); ?>
                    </h4>
                </div>
                
                <div class="box-body">
                    <form action="<?php echo base_url('importer/export/save_template'); ?>" class="formAjax" id="new_template" method="post">
                        <?php add_csrf(); ?>
                        
                        <input type="hidden" name="exporter_templates_id" value="">
                        <input type="hidden" name="exporter_templates_created_by" value="<?php echo $this->auth->get('users_id') ?>">
                        
                        <?php if(!empty($this->input->get('clone')) && $this->input->get('clone') == '1'): ?>
                            <input type="hidden" name="_clone" value="1">
                            <?php if(!empty($dati['template']['exporter_templates_grid_id'])): ?>
                                <input type="hidden" name="grid_id" value="<?php echo $dati['template']['exporter_templates_grid_id']; ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="tpl_name"><?php e('Name'); ?></label>
                                <input type="text" class="form-control" name="exporter_templates_name" id="tpl_name" oninput="updateSlug(this, document.getElementById('tpl_key'))" />
                            </div>
                            
                            <div class="col-sm-4">
                                <label for="tpl_key"><?php e('Key'); ?></label>
                                <input type="text" class="form-control" name="exporter_templates_key" id="tpl_key" oninput="updateSlug(this)" />
                            </div>
                            
                            <?php if($this->auth->is_admin()): ?>
                                <div class="col-sm-4">
                                    <label for="tpl_module"><?php e('Module'); ?></label>
                                    <select type="text" class="select2_standard" name="exporter_templates_module" id="tpl_module">
                                        <option value="">---</option>
                                        <?php foreach($dati['modules'] as $module): ?>
                                            <option value="<?php echo $module['modules_id'] ?>"><?php echo $module['modules_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(empty($dati['template'])): ?>
                            <div class="form-group row">
                                <div class="col-sm-4">
                                    <label for="tpl_choose_type"><?php e('Where do you want to start from?'); ?></label>
                                    <div id="tpl_choose_type">
                                        <label for="tpl_from_grid">
                                            <input type="radio" name="tpl_type" id="tpl_from_grid" value="grid" checked>
                                            <?php e('Existing table'); ?>
                                        </label>
                                        &nbsp;&nbsp;
                                        <label for="tpl_new_template">
                                            <input type="radio" name="tpl_type" id="tpl_new_template" value="new_template">
                                            <?php e('New Template'); ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="js_from_grid">
                                    <div class="col-sm-8">
                                        <label for="tpl_select_grid"><?php e('Select table'); ?></label>
                                        <select class="form-control select2_standard" id="tpl_select_grid" name="grid_id">
                                            <option value=""> --- </option>
                                            <?php foreach($dati['grids'] as $grid): ?>
                                                <option value="<?php echo $grid['grids_id']; ?>" data-current_where="<?php echo base64_encode($grid['grids_where']); ?>"><?php echo "(#{$grid['grids_id']}) {$grid['grids_name']}"; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group row js_new_template" style="<?php echo empty($dati['template']) ? 'display:none;' : '' ?>">
                            <div class="col-sm-12">
                                <hr style="margin: 0 0 15px 0; padding: 0">
                            </div>
                            
                            <div class="col-sm-2">
                                <label for="tpl_full_query">Full query</label>
                                <div id="tpl_full_query">
                                    <label for="tpl_full_query_yes">
                                        <input type="radio" name="exporter_templates_full_query" id="tpl_full_query_yes" value="1">
                                        <?php e('Yes'); ?>
                                    </label>
                                    &nbsp;&nbsp;
                                    <label for="tpl_full_query_no">
                                        <input type="radio" name="exporter_templates_full_query" id="tpl_full_query_no" value="0" checked>
                                        <?php e('No'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-5">
                                <label for="tpl_entity"><?php e('Entity') ?></label>
                                <select name="exporter_templates_entity" id="tpl_entity" class="select2_standard">
                                    <option value="">---</option>
                                    <?php foreach($dati['entities'] as $entity): ?>
                                        <option value="<?php echo $entity['entity_name'] ?>"><?php e($entity['entity_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-sm-12 js_show_full_query" style="display:none" data-full_query="0">
                                <label for="tpl_where" class="where_condition_label" style="display:none"><?php e('Where conditions') ?></label>
                                <div class="where_builder"></div>
                                <textarea name="exporter_templates_where" id="tpl_where" style="display:none"></textarea>
                            </div>
                            
                            <div class="js_show_full_query" style="display:none" data-full_query="0">
                                <div class="col-sm-4">
                                    <label for="tpl_limit"><?php e('Limit'); ?></label>
                                    <input type="number" class="form-control" name="exporter_templates_limit" id="tpl_limit">
                                </div>
                                
                                <div class="col-sm-4">
                                    <label for="tpl_order_by">Order By</label>
                                    <input type="text" class="form-control" name="exporter_templates_order_by" id="tpl_order_by">
                                </div>
                                
                                <div class="col-sm-4">
                                    <label for="tpl_order_dir">Order Dir</label>
                                    <select class="select2_standard" name="exporter_templates_order_dir" id="tpl_order_dir">
                                        <option value="ASC">ASC</option>
                                        <option value="DESC">DESC</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-sm-12 js_show_full_query" style="display:none" data-full_query="1">
                                <label for="tpl_query">Query</label>
                                <textarea class="form-control" name="exporter_templates_query" id="tpl_query"></textarea>
                                <span class="help-block">You can use also placeholders like {where}, {limit}, {orderby}</span>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <label for="tpl_additional_where"><?php e('Additional Where'); ?></label>
                                <textarea class="form-control" name="exporter_templates_additional_where" id="tpl_additional_where"></textarea>
                                
                                <p class="help-block js_from_grid"><i class="fas fa-info-circle fa-fw"></i> <?php e('If selected table has a custom where, it will be placed also in the above editor and content will be overwritten to newly created table'); ?></p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="alert alert-danger hide" id="msg_new_template"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg pull-right"><i class="fas fa-save fa-fw"></i> <?php e('Save') ?></button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6">
        
        </div>
    </div>
</section>

<script>
    const slugify = (string, separator = "_") => {
        /**
         * @param {string} "fÃ²o bAr -3"
         * @param {string} [separator] Separator (default: "_")
         * @return {string} "foo_bar_3"
         */
        const normalizedString = string
            .toLowerCase() // Convert to lowercase
            .normalize("NFD") // Remove accents
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-zA-Z0-9-_ ]/g, "") // Remove non-alphanumeric characters, spaces, - and _
            .replace(/\s+/g, separator) // Spaces become separator
            .replace(/-+/g, separator); // - becomes separator
        
        // Remove separator at the beginning and end of the string
        return normalizedString.replace(new RegExp(`^${separator}+|${separator}+$`, "g"), "");
    };
    
    function updateSlug(inputElement, outputElement = null) {
        var slug = slugify(inputElement.value);
        
        if (outputElement) {
            outputElement.value = slug;
        } else {
            inputElement.value = slug;
        }
    }
    
    var initWhereBuilder = function (entity_id, selector, where) {
        $('.where_condition_label').hide();
        
        $.ajax({
            url: base_url + "importer/export/where_builder/" + entity_id,
            dataType: 'json',
            cache: false,
            success: function (data) {
                selector.queryBuilder('destroy');
                
                selector.queryBuilder({
                    filters: data
                });
                
                if (where) {
                    selector.queryBuilder('setRulesFromSQL', where);
                }
                
                selector.queryBuilder().on('rulesChanged.queryBuilder afterDeleteRule.queryBuilder', function () {
                    try {
                        var objSQL = selector.queryBuilder('getSQL', false, true);
                        
                        if (objSQL != null) {
                            $('#tpl_where').val(objSQL.sql);
                        } else {
                            $('#tpl_where').val('');
                        }
                    } catch (e) { }
                });
                
                $('.where_condition_label').show();
            }
        });
    };
    
    var template = <?php echo (!empty($dati['template'])) ? json_encode($dati['template']) : '[]'; ?>;
    
    $(function() {
        const editorFullQuery = CodeMirror.fromTextArea(document.getElementById('tpl_query'), {
            mode: 'sql',
            theme: 'material',
            lineNumbers: true,
            foldGutter: true,
            placeholder: 'SELECT * FROM users',
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            extraKeys: {
                'F11': function (cm) {
                    cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                },
                'Esc': function (cm) {
                    if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
                }
            },
            autoRefresh: true
        });
        
        const editorAdditionalWhere = CodeMirror.fromTextArea(document.getElementById('tpl_additional_where'), {
            mode: 'sql',
            theme: 'material',
            lineNumbers: true,
            foldGutter: true,
            placeholder: 'SELECT * FROM users',
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            extraKeys: {
                'F11': function (cm) {
                    cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                },
                'Esc': function (cm) {
                    if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
                }
            },
            autoRefresh: true
        });
        
        $('[name="tpl_type"]').on('click', function() {
            var selected_val = $(this).val();
            var js_new_template = $('.js_new_template');
            var js_from_grid = $('.js_from_grid');
            var tpl_select_grid = $('select#tpl_select_grid');
            
            console.log(selected_val);
            
            if (selected_val === 'grid') {
                js_new_template.hide();
                js_from_grid.show();
            } else {
                js_new_template.show();
                js_from_grid.hide();
                tpl_select_grid.val('').trigger('change');
            }
        });
        
        $('select#tpl_select_grid').on('change', function() {
            editorAdditionalWhere.setValue('');
            
            var selected_grid = $(this).find('option:selected');
            var grid_where = selected_grid.data('current_where');
            
            if (grid_where) {
                grid_where = atob(selected_grid.data('current_where'));
                
                editorAdditionalWhere.setValue(grid_where);
            }
            
        });
        
        $('[name="tpl_type"][value="grid"]').trigger('click');
        
        
        $('[name="exporter_templates_full_query"]').on('click', function() {
            $('.js_show_full_query').hide();
            
            var _selected = $(this).val();
            
            $('.where_builder').queryBuilder('destroy')
            if (_selected === 0) {
                $('.js_show_full_query[data-full_query="0"] :input').val("");
            } else {
                $('#tpl_order_by').html('');
                // $('#tpl_entity').val('').trigger('change');
            }
            
            $('.js_show_full_query[data-full_query="'+$(this).val()+'"]').show();
            
            $('#tpl_entity').trigger('change')
        });
        
        $('[name="exporter_templates_full_query"]:checked').trigger('click');
        
        $('#tpl_entity').on('change', function() {
            var selected_entity = $(this).val();
            
            if (selected_entity && $('[name="exporter_templates_full_query"]:checked').val() === '0') {
                initWhereBuilder(selected_entity, $('.where_builder'), false);
            } else {
                $('.where_condition_label').hide();
                $('.where_builder').queryBuilder('destroy');
            }
        }).trigger('change');
        
        if (template) {
            $.each(template, function(field, value) {
                if (value !== '' && value !== null) {
                    if (field === 'exporter_templates_full_query') {
                        $('[name="exporter_templates_full_query"][value="'+value+'"]').trigger('click');
                    } else if (field === 'exporter_templates_query') {
                        editorFullQuery.setValue(value);
                        editorFullQuery.refresh();
                    } else if (field === 'exporter_templates_additional_where') {
                        editorAdditionalWhere.setValue(value);
                        editorAdditionalWhere.refresh();
                    } else {
                        $('[name="'+field+'"]').val(value).trigger('change');
                    }
                }
            })
            
            if (template.exporter_templates_full_query == '0') {
                initWhereBuilder(template['exporter_templates_entity'], $('.where_builder'), template['exporter_templates_where']);
            }
        }
    })
</script>