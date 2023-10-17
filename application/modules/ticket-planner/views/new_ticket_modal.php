<style>
    .text-danger {
        background-color: transparent !important;
    }
</style>

<script src="<?php echo base_url('script/js/multiupload.js'); ?>"></script>

<div class="modal fade" id="new_ticket_modal" tabindex="-1" role="dialog" aria-labelledby="NewTicketModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="NewTicketModal">Nuovo ticket per il progetto <b>
                        <?php echo $project['projects_name'] ?>
                    </b></h4>
            </div>

            <?php
            $categories = $this->ticket->apiRequest('tickets_category')['data'];
            $priorities = $this->ticket->apiRequest('tickets_priority')['data'];
            ?>

            <form id="form_222" role="form" class="formAjax" method="post"
                action="<?php echo base_url('ticket-planner/main/createTicket'); ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php add_csrf(); ?>

                    <input type="hidden" name="tickets_customer_id"
                        value="<?php echo $project['projects_customer_id'] ?>">
                    <input type="hidden" name="tickets_project_id" value="<?php echo $project['projects_id'] ?>">

                    <div class="form-body" data-select2-id="33">

                        <div class="row">

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="control-label">Oggetto <span class="text-danger">*</span></label>
                                    <input type="text" name="tickets_subject" class="form-control">
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="control-label">Categoria <span class="text-danger">*</span></label>

                                    <select class="form-control select2_standard" name="tickets_category">
                                        <option value="" selected> --- </option>
                                        <?php
                                        foreach ($categories as $category) {
                                            if ($saldo < 0 && $category['tickets_category_id'] != 8 && !$this->auth->is_admin()) {
                                                echo '<option value="' . $category['tickets_category_id'] . '" disabled="disabled">' . $category['tickets_category_value'] . '</option>';
                                            } else {
                                                echo '<option value="' . $category['tickets_category_id'] . '" >' . $category['tickets_category_value'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="control-label">Messaggio <span class="text-danger">*</span></label>

                                    <textarea name="tickets_message" class="form-control"
                                        id="js_tickets_message"></textarea>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="control-label">Priorità <span class="text-danger">*</span></label>

                                    <select class="form-control select2_standard" name="tickets_priority">
                                        <?php
                                        foreach ($priorities as $priority) {
                                            echo '<option value="' . $priority['tickets_priority_id'] . '">' . $priority['tickets_priority_value'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class='col-lg-12'>
                                <div class='form-group'>
                                    <label class='form-label-title'>Allegati</label>
                                    <div class='dropzone_div field_1121 fileinput fileinput-new'
                                        data-provides='fileinput'>
                                        <input type='hidden' name='attachments' />
                                        <div class='js_dropzone dropzone upload-drop-zone' data-preview='1'
                                            data-fieldid='' data-formid='222' data-unique=''
                                            data-fieldname='attachments' data-maxuploadsize='100'
                                            data-fieldtype='LONGTEXT' data-value=''
                                            data-url='<?php echo base_url('ticket-planner/db_ajax/dropzone'); ?>'
                                            style="min-height:100px!important">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <?php if ($saldo < 0): ?>
                        <div class="alert alert-warning" style="/*! padding-top: 10px; */">
                            <h4 style="text-shadow: 0 1px black;padding-top: 0px;margin-top: 0px;margin-bottom: 0px;">
                                Pacchetto ore esaurito, possono essere aperti solo ticket di "bug" che poi verranno
                                verificati e valutati</h4>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer clearfix">
                    <button type="submit" class="btn btn-primary pull-right btn-save">Salva</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    $('#new_ticket_modal').on('shown.bs.modal', function () {
        var tinymce_config = {
            selector: 'textarea#js_tickets_message',
            height: 300,
            resize: true,
            autosave_ask_before_unload: false,
            powerpaste_allow_local_images: true,
            paste_data_images: true,
            relative_urls: false,
            remove_script_host: false,
            //plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists wordcount textpattern noneditable charmap quickbars emoticons',
            menubar: '',
            toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
            toolbar_sticky: false,
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        };

        if (tinymce.get('textarea#js_tickets_message')) {
            tinymce.remove();
        }

        tinymce.init(tinymce_config);
    }).on('hide.bs.modal', function () {
        if (tinymce.get('textarea#js_tickets_message')) {
            tinymce.remove();
        }
    });
</script>