<?php
$customer = $this->apilib->view('customers', $value_id);

$customer['customers_contacts'] = $this->apilib->search('customers_contacts', ['customers_contacts_customer_id' => $value_id]);

$offset = 0;

$page = $this->input->get('p') ?? 1;
if (!empty($page) && ctype_digit($page)) {
    if ($page > 0) {
        $offset = 25 * $page;
    }
}

$emails_customers = [$customer['customers_email']];
foreach ($customer['customers_contacts'] as $contact) {
    $emails_customers[] = $contact['customers_contacts_email'];
}
$emails_customers = array_unique($emails_customers);

$account = $this->apilib->searchFirst('mailbox_accounts', ['mailbox_accounts_user' => $this->auth->get('id')]);

$where_in = [
    "mailbox_emails_from" => $emails_customers
];

$where_sent = [
    "(
        mailbox_emails_id IN (SELECT mailbox_emails_to_mail_id FROM mailbox_emails_to WHERE mailbox_emails_to_address IN ('" . implode("','", $emails_customers) . "'))

        OR 

        mailbox_emails_id IN (SELECT mailbox_emails_cc_mail_id FROM mailbox_emails_cc WHERE mailbox_emails_cc_address IN ('" . implode("','", $emails_customers) . "'))

        OR 

        mailbox_emails_id IN (SELECT mailbox_emails_bcc_mail_id FROM mailbox_emails_bcc WHERE mailbox_emails_bcc_address IN ('" . implode("','", $emails_customers) . "'))
    )",
];

/*
(
    ('{get folder}' = 'inbox' OR '{get folder}' = '')
    AND 
    (
        mailbox_emails_from = (SELECT customers_email FROM customers WHERE customers_id = {value_id}) 
        OR 
        mailbox_emails_from IN (SELECT customers_contacts_email FROM customers_contacts WHERE customers_contacts_customer_id = {value_id})
    )
    AND
    (
        mailbox_accounts_user = '{session_login users_id}'
        OR
        1 IN (SELECT mailbox_accounts_full_access FROM mailbox_accounts WHERE mailbox_accounts_user = '{session_login users_id}')
    )
) OR 
(
    '{get folder}' = 'sent'
    AND 
    (
        (
            mailbox_emails_id IN (SELECT mailbox_emails_to_mail_id FROM mailbox_emails_to WHERE mailbox_emails_to_address IN (SELECT customers_email FROM customers WHERE customers_id = {value_id}) OR mailbox_emails_to_address IN (SELECT customers_contacts_email FROM customers_contacts WHERE customers_contacts_customer_id = {value_id})) 

            OR 

            mailbox_emails_id IN (SELECT mailbox_emails_cc_mail_id FROM mailbox_emails_cc WHERE mailbox_emails_cc_address IN (SELECT customers_email FROM customers WHERE customers_id = {value_id}) OR mailbox_emails_cc_address IN (SELECT customers_contacts_email FROM customers_contacts WHERE customers_contacts_customer_id = {value_id})) 
            
            OR 

            mailbox_emails_id IN (SELECT mailbox_emails_bcc_mail_id FROM mailbox_emails_bcc WHERE mailbox_emails_bcc_address IN (SELECT customers_email FROM customers WHERE customers_id = {value_id}) OR mailbox_emails_bcc_address IN (SELECT customers_contacts_email FROM customers_contacts WHERE customers_contacts_customer_id = {value_id})) 
            
        )
    )
    AND
    (
        mailbox_accounts_user = '{session_login users_id}'
        OR
        1 IN (SELECT mailbox_accounts_full_access FROM mailbox_accounts WHERE mailbox_accounts_user = '{session_login users_id}')
    )
)

*/








if ($account && $account['mailbox_accounts_full_access']) {
} else {
    // $where_in['mailbox_accounts_user'] = $this->auth->get('id');
    // $where_sent['mailbox_accounts_user'] = $this->auth->get('id');
}

$emails_in = $this->apilib->search('mailbox_emails', $where_in, 200, $offset, 'mailbox_emails_date', 'DESC');
$emails_sent = $this->apilib->search('mailbox_emails', $where_sent, 200, $offset, 'mailbox_emails_date', 'DESC');

$emails = array_merge($emails_sent, $emails_in);

//debug($emails_sent, true);
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/iCheck/1.0.3/icheck.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/iCheck/1.0.3/skins/flat/blue.min.css" />

<section class="content">
    <div class="row">
        <div class="col-md-3">
            <a href="compose.html" class="btn btn-primary btn-block margin-bottom">Compose</a>

            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Folders</h3>

                    <div class="box-tools">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <ul class="nav nav-pills nav-stacked">
                        <li class="active"><a href="#"><i class="fa fa-inbox"></i> Inbox<span class="label label-primary pull-right">12</span></a></li>
                        <li><a href="#"><i class="fa fa-envelope-o"></i> Sent</a></li>
                        <li><a href="#"><i class="fa fa-file-text-o"></i> Drafts</a></li>
                        <li><a href="#"><i class="fa fa-filter"></i> Junk <span class="label label-warning pull-right">65</span></a>
                        </li>
                        <li><a href="#"><i class="fa fa-trash-o"></i> Trash</a></li>
                    </ul>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Labels</h3>

                    <div class="box-tools">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>

                <div class="box-body no-padding">
                    <ul class="nav nav-pills nav-stacked">
                        <li><a href="#"><i class="fa fa-circle-o text-red"></i> Important</a></li>
                        <li><a href="#"><i class="fa fa-circle-o text-yellow"></i> Promotions</a></li>
                        <li><a href="#"><i class="fa fa-circle-o text-light-blue"></i> Social</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Inbox</h3>

                    <div class="box-tools pull-right">
                        <div class="has-feedback">
                            <input type="text" class="form-control input-sm" placeholder="Search Mail">
                            <span class="glyphicon glyphicon-search form-control-feedback"></span>
                        </div>
                    </div>
                </div>

                <div class="box-body no-padding">
                    <div class="mailbox-controls">
                        <button type="button" class="btn btn-default btn-sm checkbox-toggle"><i class="fa fa-square-o"></i></button>

                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm"><i class="far fa-trash-alt"></i></button>
                            <button type="button" class="btn btn-default btn-sm"><i class="fas fa-reply"></i></button>
                        </div>

                        <button type="button" class="btn btn-default btn-sm"><i class="fas fa-sync-alt"></i></button>

                        <div class="pull-right">
                            1-50/200
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm"><i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm"><i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mailbox-messages">
                        <table class="table table-striped ">
                            <tbody>
                                <?php foreach ($emails as $email) : ?>
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td class="mailbox-star"><a href="#"><i class="far fa-star text-yellow"></i></a></td>
                                        <td class="mailbox-name"><?php echo anchor("mailto:{$email['mailbox_emails_from']}", $email['mailbox_emails_from_name']); ?></td>
                                        <td class="mailbox-subject"><?php echo $email['mailbox_emails_subject'] ?></td>
                                        <td class="mailbox-attachment"><?php echo $email['mailbox_emails_has_attachments'] ? '<i class="fas fa-paperclip"></i>' : null; ?></td>
                                        <td class="mailbox-date"><?php echo dateFormat($email['mailbox_emails_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="box-footer no-padding">
                    <div class="mailbox-controls">
                        <button type="button" class="btn btn-default btn-sm checkbox-toggle"><i class="fa fa-square-o"></i></button>

                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm"><i class="far fa-trash-alt"></i></button>
                            <button type="button" class="btn btn-default btn-sm"><i class="fas fa-reply"></i></button>
                        </div>

                        <button type="button" class="btn btn-default btn-sm"><i class="fas fa-sync-alt"></i></button>

                        <div class="pull-right">
                            1-50/200
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm"><i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm"><i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(function() {
        //Enable iCheck plugin for checkboxes
        //iCheck for checkbox and radio inputs
        $('.mailbox-messages input[type="checkbox"]').iCheck({
            checkboxClass: 'icheckbox_flat-blue',
            radioClass: 'iradio_flat-blue'
        });

        //Enable check and uncheck all functionality
        $(".checkbox-toggle").click(function() {
            var clicks = $(this).data('clicks');
            if (clicks) {
                //Uncheck all checkboxes
                $(".mailbox-messages input[type='checkbox']").iCheck("uncheck");
                $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
            } else {
                //Check all checkboxes
                $(".mailbox-messages input[type='checkbox']").iCheck("check");
                $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
            }
            $(this).data("clicks", !clicks);
        });

        //Handle starring for glyphicon and font awesome
        $(".mailbox-star").click(function(e) {
            e.preventDefault();
            //detect type
            var $this = $(this).find("a > i");
            var glyph = $this.hasClass("glyphicon");
            var fa = $this.hasClass("fa");

            //Switch states
            if (glyph) {
                $this.toggleClass("glyphicon-star");
                $this.toggleClass("glyphicon-star-empty");
            }

            if (fa) {
                $this.toggleClass("fa-star");
                $this.toggleClass("fa-star-o");
            }
        });
    });
</script>