<?php


class Firemail extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('firemail/firemail_model');

        require FCPATH . "application/modules/firemail/vendor/autoload.php";
    }


    // Predisporre un metodo da chiamare via cron CLI che cicla i mailbox account confiurati nel sistema
    // scaricare le loro mail dell'ultima giornata e importarle se messaggio già presente a db non reimportare
    // Per gli attachments come ultima cosa scaricarli solo se l'opzione per quell'account è attiva (aggiungere flag su mailboxaccounts)
    // Per testare usa michael@h2web.it

    // IMAP Library Documentation: https://github.com/ddeboer/imap

    // Pass $date_interval = 'all' to get all messages

    public function addToBlacklist($value_id)
    {
        if (!$value_id) {
            return false;
        }

        // Add to blacklist
        $mail = $this->apilib->view('mailbox_emails', $value_id);

        $this->apilib->create('mailboxes_blacklist', array('mailboxes_blacklist_address' => $mail['mailbox_emails_from']));

        // Delete emails
        //echo "Delete: " . $mail['mailbox_emails_from'];
        $this->db->query("DELETE FROM mailbox_emails WHERE mailbox_emails_from = '{$mail['mailbox_emails_from']}' ");
        echo json_encode(array('status' => 3, 'txt' => 'Delete: ' . $mail['mailbox_emails_from']));
    }
    public function downloadEmails($account_id = 0, $date_interval = 0, $limit = 0, $from_contact = 0)
    {
        $this->firemail_model->downloadEmails($account_id, $date_interval, $limit, $from_contact);
    }
    /* Da cancellare
    public function downloadEmailsFromContact($contact){
        
        $contact = 'norman@topgensport.it';
        $accounts = $this->apilib->search('mailbox_accounts', ["mailbox_accounts_enabled = '" . DB_BOOL_TRUE . "'", "mailbox_accounts_downloading = '" . DB_BOOL_FALSE . "'"]);

        foreach ($accounts as $account) {

            $sync_folder = [];
            if (!empty($account['mailbox_accounts_inbox_folder'])) {
                $mailbox_accounts_inbox_folder = $account['mailbox_accounts_inbox_folder'];
                $inbox_folders = explode(',', $mailbox_accounts_inbox_folder);
                $sync_folder = array_merge($sync_folder, $inbox_folders);
            }
            
            if (!empty($account['mailbox_accounts_sent_folder'])) {
                $mailbox_accounts_sent_folder = $account['mailbox_accounts_sent_folder'];
                $sent_folders = explode(',', $mailbox_accounts_sent_folder);
                $sync_folder = array_merge($sync_folder, $sent_folders);
            }

            // Lock account to prevent other cron download
            //$this->db->query("UPDATE mailbox_accounts SET mailbox_accounts_downloading = '" . DB_BOOL_TRUE . "' WHERE mailbox_accounts_id  = '{$account['mailbox_accounts_id']}'");

            // Connection
            $account['mailbox_accounts_port'] = ($account['mailbox_accounts_port']) ? $account['mailbox_accounts_port'] : '993';
            //$account['mailbox_accounts_protocol'] = ($account['mailbox_accounts_protocol']) ? $account['mailbox_accounts_protocol'] : '/imap/ssl/validate-cert'; // Alternative: /imap/ssl
            $encryption = $account['mailbox_accounts_encryption_value'];
            $protocol = ($account['mailbox_accounts_validate_certificate'] == DB_BOOL_TRUE) ? "/imap/$encryption/validate-cert" : "/imap/ssl/novalidate-cert";

            $server = new Ddeboer\Imap\Server(
                $account['mailbox_accounts_server'], // required
                $account['mailbox_accounts_port'],
                // defaults to '993'
                $protocol, // defaults to '/imap/ssl/validate-cert'
            );
            echo "-----------------------------------------------------------------------------------------------------------------\r\n";
            echo "\r\nConnecting to: " . $account['mailbox_accounts_email'] . "\r\n";
            try {
                $connection = $server->authenticate($account['mailbox_accounts_email'], $account['mailbox_accounts_password']);
                $this->db->where('mailbox_accounts_id', $account['mailbox_accounts_id'])->update('mailbox_accounts', ['mailbox_accounts_connection_log' => 'Success']);
            } catch (Exception $e) {
                echo "Connection failed: " . $account['mailbox_accounts_email'] . "\r\n";
                echo $e;
                echo "\r\n";
                $this->db->where('mailbox_accounts_id', $account['mailbox_accounts_id'])->update('mailbox_accounts', ['mailbox_accounts_connection_log' => $e]);
                continue;
            }
        }
        $mailboxes = $connection->getMailboxes();

        $emails = [];

        foreach ($mailboxes as $mailbox) {
            $mailbox_name = $mailbox->getEncodedName();
            try {
                $mailbox = $connection->getMailbox($mailbox_name);
            } catch (\Exception $e) {
                log_message('error', "Mailbox error {$e->getMessage()}");
                continue;
            }

            $from = new Ddeboer\Imap\Search\Email\From($contact);
            $messages = $mailbox->getMessages($from);
            // Ora puoi iterare attraverso le email trovate
            foreach ($messages as $message) {
            
                $message_from = $message->getFrom();
                if ($message_from == null) {
                    continue;
                }

                $from = $message_from->getAddress();
                if (empty($from)) {
                    continue;
                }
                //check if mailbox_accounts_only_known_contacts is true

                try {
                    if (!empty($message->getDate())) {
                        $email['mailbox_emails_date'] = (dateTime::createFromImmutable($message->getDate()))->format('d/m/Y H:i');
                    } else {
                        $email['mailbox_emails_date'] = '01/01/1970';
                    }


                    $email['mailbox_emails_from'] = $from;
                    $email['mailbox_emails_from_name'] = ($message_from->getName()) ?? null;
                    $email['mailbox_emails_subject'] = $message->getSubject();
                    $email['mailbox_emails_attachments'] = null;
                    $to = array();
                    foreach ($message->getTo() as $recipient) {
                        $to[] = $recipient->getAddress();
                    }
                    $email['mailbox_emails_recipients'] = json_encode($to);

                    $email['mailbox_emails_message'] = $message->getBodyHtml();
                    if(empty($email['mailbox_emails_message'])){
                        $email['mailbox_emails_message'] = $message->getBodyText();
                        $email['mailbox_emails_message'] = str_replace("\n", "<br>", $email['mailbox_emails_message']);

                    }
                    //$email['mailbox_emails_has_attachments'] = $message->hasAttachments();
                    if ($message->hasAttachments()) {
                        $email['mailbox_emails_has_attachments'] = 1;
                    } else {
                        $email['mailbox_emails_has_attachments'] = 0;
                    }
                    

                    $email['mailbox_emails_external_id'] = md5($message_from->getAddress() . $message_from->getName() . $message->getSubject() . $message->getBodyHtml() . $email['mailbox_emails_date']);
                    $email['mailbox_emails_folder'] = $mailbox_name;

                    $email['mailbox_emails_account_id'] = $account['mailbox_accounts_id'];

                    // Extra
                    $email['mailbox_emails_is_seen'] = $message->isSeen();
                    $email['mailbox_emails_is_draft'] = $message->isDraft();
                    $email['mailbox_emails_is_deleted'] = $message->isDeleted();
                    $email['mailbox_emails_is_answered'] = $message->isAnswered();


                    $check = $this->db->query("SELECT mailbox_emails_external_id FROM mailbox_emails WHERE mailbox_emails_account_id = '{$email['mailbox_emails_account_id']}' AND mailbox_emails_external_id = '{$email['mailbox_emails_external_id']}'")->num_rows();

                    if ($check > 0) {
                        continue;
                    }


                    // Attachments  TODO
                    if ($message->hasAttachments() && $account['mailbox_accounts_download_attachments'] == DB_BOOL_TRUE) {
                        $attachments = $message->getAttachments();

                        if (!is_dir(FCPATH . 'uploads/attachments')) {
                            mkdir(FCPATH . 'uploads/attachments', DIR_WRITE_MODE, true);
                        }

                        $arr_attachments = array();
                        foreach ($attachments as $attachment) {
                            // $attachment is instance of \Ddeboer\Imap\Message\Attachment
                            $file_name = md5(time()) . '-' . $attachment->getFilename();
                            $file_path = FCPATH . 'uploads/attachments/' . $file_name;
                            if (!file_exists($file_path)) {
                                file_put_contents($file_path, $attachment->getDecodedContent());
                            }
                            if (file_exists($file_path)) {
                                $file_size = filesize($file_path);

                            } else {
                                $file_size = 0;
                            }
                            $data_allegato = array(
                                'filename' => $attachment->getFilename(), //per retrocompatibilità delle mail
                                'file_name'		=> $attachment->getFilename(),
                                'file_type'		=> '',
                                'file_path'		=> $file_path,
                                'full_path'		=> '',
                                'raw_name'		=> '',
                                'orig_name'		=> $attachment->getFilename(),
                                'client_name'		=> '',
                                'file_ext'		=> '',
                                'file_size'		=> $file_size,
                                'is_image'		=> '',
                                'image_width'		=> '',
                                'image_height'		=> '',
                                'image_type'		=> '',
                                'image_size_str'	=> '',
                                'original_filename' => '',
                                'path_local' => 'attachments/' . $file_name

                            );
                            $arr_attachments[] = $data_allegato;
                            //$arr_attachments[] = array('filename' => $attachment->getFilename(), 'file_path' => $file_path);
                        }
                        $email['mailbox_emails_attachments'] = json_encode($arr_attachments);
                    }

                    // To debug email
                    // Insert
                    $mail = $this->apilib->create('mailbox_emails', $email);
                    echo "|";

                    // Insert to cc bcc
                    foreach ($message->getTo() as $to) {
                        $this->apilib->create('mailbox_emails_to', array('mailbox_emails_to_mail_id' => $mail['mailbox_emails_id'], 'mailbox_emails_to_address' => $to->getAddress(), 'mailbox_emails_to_name' => $to->getAddress()));
                    }

                    foreach ($message->getCc() as $cc) {
                        $this->apilib->create('mailbox_emails_cc', array('mailbox_emails_cc_mail_id' => $mail['mailbox_emails_id'], 'mailbox_emails_cc_address' => $cc->getAddress(), 'mailbox_emails_cc_name' => $cc->getAddress()));
                    }

                    foreach ($message->getBcc() as $bcc) {
                        $this->apilib->create('mailbox_emails_bcc', array('mailbox_emails_bcc_mail_id' => $mail['mailbox_emails_id'], 'mailbox_emails_bcc_address' => $bcc->getAddress(), 'mailbox_emails_bcc_name' => $bcc->getAddress()));
                    }

                    $count_messages++;
                    progress($count_messages, $total);
                } catch (\MailboxDoesNotExistException $e) {
                    log_message('ERROR', 'Webmail Import cron error: ' . $e->getMessage());
                    $email['mailbox_emails_date'] = '01/01/1970';
                } catch (\Exception $e) {
                    log_message('ERROR', 'Webmail Import cron error: ' . $e->getMessage());
                    $email['mailbox_emails_date'] = '01/01/1970';
                } catch (\Throwable $t) {
                    log_message('ERROR', 'Webmail Import cron error: ' . $t->getMessage());
                    $email['mailbox_emails_date'] = '01/01/1970';
                }
            }

        }

    }*/


    public function iframeMail($id)
    {
        $mail = $this->apilib->view('mailbox_emails', $id);
        echo (htmlspecialchars_decode($mail['mailbox_emails_message']));
    }
    public function trova_customers($valore = null)
    {

        if ($valore == null) {
            throw new Exception('Impossibile trovare il dominio del contatto');
            exit;
        }
        $result = null;

        $items = $this->apilib->searchFirst('customers_domains', ["customers_domains_domain LIKE '%{$valore}%'"]);
        if ($items) {
            $cliente = $this->apilib->searchFirst('customers', ['customers_id' => $items['customers_domains_customer']]);
            $result['customers_id'] = $cliente['customers_full_name'];
        } else {
            $parts = explode('.', $valore);
            $valore = $parts[0];
            $cliente = $this->apilib->searchFirst('customers', ["customers_full_name LIKE '%{$valore}%'"]);
            if ($cliente) {
                $result['customers_id'] = $cliente['customers_full_name'];
            }
        }
        echo json_encode($result);
    }
    public function addToBlacklistDomain($value_id)
    {
        if (!$value_id) {
            return false;
        }

        $mail = $this->apilib->view('mailbox_emails', $value_id);
        $parts = explode('@', $mail['mailbox_emails_from']);
        $domain = $parts[1];
        /*
        $mails = $this->db->query("
        SELECT * FROM mailbox_emails WHERE mailbox_emails_from LIKE '%{$domain}%'
        ")->result_array();
        */
        /*
        $mails = $this->db->query("
        SELECT *
        FROM mailbox_emails
        WHERE SUBSTRING(mailbox_emails.mailbox_emails_from, INSTR(mailbox_emails.mailbox_emails_from, '@') + 1) = '{$domain}'
        ")->result_array();*/
        $this->apilib->create('mailboxes_blacklist_domains', array('mailboxes_blacklist_domains_domain' => $domain));

        $this->db->query("DELETE FROM mailbox_emails WHERE SUBSTRING(mailbox_emails_from, INSTR(mailbox_emails_from, '@') + 1) = '{$domain}' ");
        /*
        $mails = $this->db->query("SELECT DISTINCT mailbox_emails_from FROM mailbox_emails WHERE mailbox_emails_from LIKE '%{$domain}%'")->result_array();
        foreach ($mails as $mail) {
        //echo "Metto in blacklist: ".$mail['mailbox_emails_from']."<br>";
        $this->apilib->create('mailboxes_blacklist', array('mailboxes_blacklist_address' => $mail['mailbox_emails_from']));
        $this->db->query("DELETE FROM mailbox_emails WHERE mailbox_emails_from = '{$mail['mailbox_emails_from']}' ");
        }*/
        echo json_encode(array('status' => 2));

    }
    public function testConnection()
    {
        $data['email'] = $this->input->post("email");
        $data['password'] = $this->input->post("password");
        $data['server'] = $this->input->post("server");
        $data['port'] = $this->input->post("port");
        $data['validate'] = $this->input->post("validate");
        $encrypt = $this->apilib->searchFirst('mailbox_accounts_encryption', ["mailbox_accounts_encryption_id = '" . $this->input->post("encryption") . "'"]);
        $data['encryption'] = $encrypt['mailbox_accounts_encryption_value'];

        $this->firemail_model->testConnections($data);

    }
    public function skipContact($value_id){
        $email = $this->apilib->searchFirst('mailbox_emails', ['mailbox_emails_id' => $value_id]);
        if(!empty($email)){
            $this->db->query("UPDATE mailbox_emails SET mailbox_emails_ignore_email = '" . DB_BOOL_TRUE . "' WHERE mailbox_emails_from  = '{$email['mailbox_emails_from']}'");
            echo json_encode(array('status' => 2));

        }
    }
}
