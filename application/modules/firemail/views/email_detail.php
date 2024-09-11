<?php

$email_detail = $this->apilib->view('mailbox_emails', $value_id);
if (!empty($email_detail['mailbox_emails_recipients'])) {
    $recipients = json_decode($email_detail['mailbox_emails_recipients']);
}
$corrispondenza = false;
//verifico se ha un contatto associato
$esistente = $this->db->query("SELECT * FROM customers_contacts WHERE customers_contacts_email = '{$email_detail['mailbox_emails_from']}'")->row_array();
if($esistente != 0){
    $cliente = $this->db->query("SELECT * FROM customers WHERE customers_id = '{$esistente['customers_contacts_customer_id']}'")->row_array();
    if(!empty($cliente)){
        $corrispondenza = true;
        $contatto = $esistente;
    }     
} 
if(!$corrispondenza){
    $partiEmail = explode("@", $email_detail['mailbox_emails_from']);
    if (count($partiEmail) == 2) {
        $customer = $this->apilib->searchFirst('customers_domains', [
            'customers_domains_domain' => $partiEmail[1],
        ]);
    }
}             
//$email_text = strip_tags($email_detail['mailbox_emails_message'], '<b><p><a><strong><i><br><ul><li>');
?>


<div class="row">
    <div class="col-lg-12">
        <div>
            <!-- /.box-header -->
            <div class="box-body no-padding">
                <div class="mailbox-read-info">
                    <h3><?php echo $email_detail['mailbox_emails_subject']; ?></h3>
                    <h5>From: <?php echo $email_detail['mailbox_emails_from']; ?>
                        <?php if(isset($contatto)):
                        ?>
                        <a class="js-action_button btn btn-grid-action-s js_open_modal" href="<?php echo base_url('get_ajax/layout_modal/customer-contact-detail/'.$contatto['customers_contacts_id'].'?_mode=side_view'); ?>">
                            <?php 
                            echo "(".$contatto['customers_contacts_name'] . " " .$contatto['customers_contacts_last_name'] . ")";  
                            ?> 
                        </a>          
                        <?php
                        else:
                            ?>
                            <a class="js-action_button btn btn-grid-action-s js_open_modal" href="<?php echo base_url("/get_ajax/modal_form/customers-contatti-orfani?customers_contacts_customer_id=" . (isset($customer['customers_id']) ? $customer['customers_id'] : '') . "&customers_contacts_email=" . $email_detail['mailbox_emails_from']); ?>">
                                <button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" data-container="body" title="" data-original-title="Create contact">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </a>

                            <?php
                        endif; ?>
                        <span class="mailbox-read-time pull-right"><?php echo dateFormat($email_detail['mailbox_emails_date']); ?></span>
                    </h5>
                    <?php
                    if (!empty($recipients)):
                        ?>
                        <h5>To: <?php foreach ($recipients as $recipient) {
                                echo $recipient . '; ';
                            }
                        echo "</h5>";    
                    endif;    
                        ?>
                        <?php
                    if (!empty($email_detail['mailbox_emails_attachments'])):
                        $attachments = @json_decode($email_detail['mailbox_emails_attachments'], true);
                        ?>
                        <h5><?php e("Allegati"); ?> 
                        <?php 
                        foreach ($attachments as $attachment):

                            $pos = strpos($attachment['file_path'], "attachments/");
                            if ($pos !== false) {
                                // Ottieni la parte del percorso dopo "attachments/"
                                $file = substr($attachment['file_path'], $pos + strlen("attachments/"));
                            } else {
                                $file = "#";
                            }
                            ?>
                            <a target="_blank" title="<?php echo $attachment['filename']; ?>" href="<?php echo base_url("uploads/attachments/".$file); ?>"><i class="fa fa-paperclip" aria-hidden="true"><?php echo $attachment['filename']; ?></i></a>
                            <br>
                            <?php

                        endforeach;
                        echo "</h5>";    
                    endif;    
                        ?>

                </div>
                <!-- /.mailbox-read-info -->
                <div class="mailbox-controls with-border text-center">
                    <div class="btn-group">
                        <!-- associa a cliente -->  
                        <a style="margin-right: 10px;" class="js-action_button btn btn-grid-action-s bg-red js_confirm_button js_link_ajax" data-confirm-text="are you sure to add this sender to blacklist and delete all his emails?" title="Contatto in blacklist" href="<?php echo base_url("firemail/firemail/addToBlacklist/".$email_detail['mailbox_emails_id']); ?>">
                            <span class="fa fa-ban"></span>
                        </a>       
                        <a class="js-action_button btn btn-grid-action-s bg-red js_confirm_button js_link_ajax" data-confirm-text="are you sure to add this domain to blacklist and delete all his emails?" title="Dominio in blacklist" href="<?php echo base_url("firemail/firemail/addToBlacklistDomain/".$email_detail['mailbox_emails_id']); ?>">
                            <span class="fa fa-lock"></span>
                        </a>
                    </div>
                </div>
                <!-- /.mailbox-controls -->
                <div class="mailbox-read-message">
                    <iframe style="width:100%;border:none;min-height:650px" src="<?php echo base_url("firemail/firemail/iframeMail/{$email_detail['mailbox_emails_id']}"); ?>" _srcdoc="<?php echo $email_detail['mailbox_emails_message']; ?>" frameborder="0" cellspacing="0"></iframe>

                    <?php //echo htmlspecialchars_decode($email_detail['mailbox_emails_message']); 
                    ?>

                </div>
                <!-- /.mailbox-read-message -->
            </div>
            <!-- /.box-footer -->
        </div>
    </div>
</div>