<?php $this->layout->addModuleJavascript('ticket-planner', 'js/multiupload.js'); ?>

<div class="modal fade" id="ticket<?php echo $tickets_id; ?>_attachments_modal" tabindex="-1" role="dialog" aria-labelledby="TicketAttachmentsModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="TicketAttachmentsModal">Carica allegati per il ticket <b>#<?php echo $tickets_id ?></b></h4>
            </div>

            <form id="form_222" role="form" class="formAjax" method="post" action="<?php echo base_url('ticket-planner/main/upload_attachments'); ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php add_csrf(); ?>

                    <?php if(!empty($tickets_attachments)): ?>
                    <strong>Allegati già presenti:</strong>
                    <br/>
                    <?php
                        $files = json_decode($tickets_attachments, true);

                        foreach ($files as $file) {
                            echo anchor($this->ticket->base_endpoint . "uploads/" . $file['path_local'], '<i class="fas fa-paperclip fa-fw"></i>' . $file['original_filename'], ['target' => '_blank']) . '<br/>';
                        }

                        echo '<hr/>';

                        endif;
                    ?>

                    <input type="hidden" name="tickets_id" value="<?php echo $tickets_id; ?>">

                    <div class="form-body" data-select2-id="33">
                        <div class="row">
                            <div class='col-lg-12'>
                                <div class='form-group'>
                                    <label class='form-label-title'>Trascina o clicca qui sotto per caricare gli allegati</label>
                                    <div class='dropzone_div field_1121 fileinput fileinput-new' data-provides='fileinput'>
                                        <input type='hidden' name='attachments' />
                                        <div class='js_dropzone_ticket_planner dropzone upload-drop-zone' data-preview='1' data-fieldid='' data-formid='222' data-unique='' data-fieldname='attachments' data-maxuploadsize='100' data-fieldtype='LONGTEXT' data-value='' data-url='<?php echo base_url('ticket-planner/db_ajax/dropzone'); ?>'></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer clearfix">
                    <button type="submit" class="btn btn-primary pull-right btn-save">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>
