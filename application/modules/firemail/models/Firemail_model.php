
<?php
class Firemail_model extends CI_Model
{
    public function downloadEmails($account_id = 0, $date_interval = 0, $limit = 0, $from_contact = 0)
    {
        set_time_limit(0);

        echo "Starting download.... \r\n";

        $accounts = $this->apilib->search('mailbox_accounts', ["mailbox_accounts_enabled = '" . DB_BOOL_TRUE . "'", "mailbox_accounts_downloading = '" . DB_BOOL_FALSE . "'"]);
        if ($account_id) {
            echo "Searching account id: $account_id";
        }
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
            if ($account_id != 0) {
                if ($account['mailbox_accounts_id'] != $account_id) {
                    continue;
                }
                echo "Found: $account_id";
            }

            // Lock account to prevent other cron download
            $this->db->query("UPDATE mailbox_accounts SET mailbox_accounts_downloading = '" . DB_BOOL_TRUE . "' WHERE mailbox_accounts_id  = '{$account['mailbox_accounts_id']}'");

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

                // Get all Message
                if (!empty($sync_folder)) {

                    if (in_array($mailbox_name, $sync_folder)) {
                        echo "<br>Searching emails in folder $mailbox_name...\r\n";
                    } else {
                        echo "<br>Salto la casella $mailbox_name \r\n";
                        continue;
                    }
                } else {
                    echo "<br>Searching emails in folder $mailbox_name...\r\n";
                }

                // Check if it is a new account to fetch all email
                //$total_message = $this->db->query("SELECT COUNT(mailbox_emails_id) AS total FROM mailbox_emails WHERE mailbox_emails_account_id = '{$account['mailbox_accounts_id']}'")->row()->total;
                // $last_message = $this->db->query("SELECT * FROM mailbox_emails WHERE mailbox_emails_folder = '$mailbox_name' AND  mailbox_emails_account_id = '{$account['mailbox_accounts_id']}' ORDER BY mailbox_emails_id DESC LIMIT 1");

                $last_message = $this->db->where('mailbox_emails_folder', $mailbox_name)->where('mailbox_emails_account_id', $account['mailbox_accounts_id'])->order_by('mailbox_emails_id', 'DESC')->limit(1)->get('mailbox_emails');
                if ($from_contact != 0) {
                    $customer_contact = $this->apilib->view('customers_contacts', $from_contact);
                    if (!empty($customer_contact)) {
                        $messages = $mailbox->getMessages(
                            new Ddeboer\Imap\Search\Email\From($customer_contact['customers_contacts_email']),
                            \SORTDATE,
                            // Sort criteria
                            true // Descending order
                        );
                    } else {
                        log_message('ERROR', 'Webmail Import CUSTOMER CONTACT cron error: ' . $t->getMessage());
                        continue;
                    }
                }
                if (($date_interval === 'all' || $last_message->num_rows() < 1) && empty($account['mailbox_accounts_download_from'])) {
                    try {
                        $messages = $mailbox->getMessages();
                        // Altri comandi da eseguire dopo aver ottenuto i messaggi
                    } catch (Exception $e) {
                        // Gestisci l'eccezione qui
                        echo 'Si è verificato un errore: ' . $e->getMessage();
                        continue;
                    }

                    // Continua a eseguire il codice qui, anche se si è verificato un errore


                    $check_exists_message = false;
                } else if ($last_message->num_rows() < 1 && !empty($account['mailbox_accounts_download_from'])) {
                    echo "Searching from: " . $account['mailbox_accounts_download_from'] . "\r\n";

                    $date = new DateTime($account['mailbox_accounts_download_from']);
                    $since = DateTimeImmutable::createFromMutable($date);
                    $messages = $mailbox->getMessages(
                        new Ddeboer\Imap\Search\Date\Since($since),
                        \SORTDATE,
                        // Sort criteria
                        true // Descending order
                    );
                    $check_exists_message = false;
                } else {
                    $last_message = $last_message->row_array();

                    $check_exists_message = true;

                    // Limit from get value or from last downloaded message
                    if ($date_interval != 0) {
                        echo "Searching without limits...\r\n";
                        $today = new DateTimeImmutable();
                        $since = $today->sub(new DateInterval($date_interval)); // P30D for 30 days
                    } else {
                        echo "Searching from: " . $last_message['mailbox_emails_date'] . "\r\n";

                        $date = new DateTime($last_message['mailbox_emails_date']);
                        $since = DateTimeImmutable::createFromMutable($date);
                    }

                    $messages = $mailbox->getMessages(
                        new Ddeboer\Imap\Search\Date\Since($since),
                        \SORTDATE,
                        // Sort criteria
                        true // Descending order
                    );
                }
                $count_messages = 0;
                $count_skipped = 0;
                $total = count($messages);
                foreach ($messages as $message) {
                    if ($limit != 0 && $count_messages > $limit) {
                        echo "Break for limit $limit";
                        break;
                    }
                    //debug($message);

                    $my_to = array();
                    $my_cc = array();
                    $my_bcc = array();

                    // Check blacklist
                    if ($message == null) {
                        continue;
                    }
                    $message_from = $message->getFrom();
                    if ($message_from == null) {
                        continue;
                    }
                    $from = $message_from->getAddress();
                    if (empty($from)) {
                        continue;
                    }
                    // Check if domain is in blacklist
                    $parts = explode('@', $from);
                    $domain = $parts[1];

                    $check = $this->db->query("
                    SELECT mailboxes_blacklist_domains_domain
                    FROM mailboxes_blacklist_domains
                    WHERE mailboxes_blacklist_domains_domain = '{$domain}'
                    ")->num_rows();

                    if ($check > 0) {
                        echo "$domain in blacklist... skipped...\r\n";
                        continue;
                    }

                    if ($account['mailbox_accounts_only_known_contacts'] == DB_BOOL_TRUE) {

                        $check = $this->db->query("
                        SELECT customers_domains_domain
                        FROM customers_domains
                        WHERE customers_domains_domain = '{$domain}'
                        ")->num_rows();

                        if ($check == 0) {
                            echo "$domain non recognized... skipped...\r\n";
                            continue;
                        }
                    }


                    // Check if from is in blacklist
                    $check = $this->db->query("SELECT * FROM mailboxes_blacklist WHERE mailboxes_blacklist_address = '$from'")->num_rows();
                    if ($check > 0) {
                        echo "$from address in blacklist... skipped...\r\n";
                        continue;
                    }

                    try {
                        if (!empty($message->getDate())) {
                            $email['mailbox_emails_date'] = (dateTime::createFromImmutable($message->getDate()))->format('Y-m-d H:i');
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
                        if (empty($email['mailbox_emails_message'])) {
                            $email['mailbox_emails_message'] = $message->getBodyText();
                            $email['mailbox_emails_message'] = str_replace("\n", "<br>", $email['mailbox_emails_message']);
                        }
                        //$email['mailbox_emails_has_attachments'] = $message->hasAttachments();
                        $email['mailbox_emails_has_attachments'] = DB_BOOL_FALSE;


                        $email['mailbox_emails_external_id'] = md5($message_from->getAddress() . $message_from->getName() . $message->getSubject() . $message->getBodyHtml() . $email['mailbox_emails_date']);
                        $email['mailbox_emails_folder'] = $mailbox_name;

                        $email['mailbox_emails_account_id'] = $account['mailbox_accounts_id'];

                        // Extra
                        $email['mailbox_emails_is_seen'] = $message->isSeen();
                        $email['mailbox_emails_is_draft'] = $message->isDraft();
                        $email['mailbox_emails_is_deleted'] = $message->isDeleted();
                        $email['mailbox_emails_is_answered'] = $message->isAnswered();

                        // If my database is empty
                        if ($check_exists_message == true) {
                            $check = $this->db->query("SELECT mailbox_emails_external_id FROM mailbox_emails WHERE mailbox_emails_account_id = '{$email['mailbox_emails_account_id']}' AND mailbox_emails_external_id = '{$email['mailbox_emails_external_id']}'")->num_rows();

                            if ($check > 0) {
                                $count_skipped++;
                                continue;
                            }
                        }


                        // Attachments  TODO
                        if ($message->hasAttachments() && $account['mailbox_accounts_download_attachments'] == DB_BOOL_TRUE) {
                            $attachments = $message->getAttachments();
                            $email['mailbox_emails_has_attachments'] = DB_BOOL_TRUE;

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
                                    'file_name'        => $attachment->getFilename(),
                                    'file_type'        => '',
                                    'file_path'        => $file_path,
                                    'full_path'        => '',
                                    'raw_name'        => '',
                                    'orig_name'        => $attachment->getFilename(),
                                    'client_name'        => '',
                                    'file_ext'        => '',
                                    'file_size'        => $file_size,
                                    'is_image'        => '',
                                    'image_width'        => '',
                                    'image_height'        => '',
                                    'image_type'        => '',
                                    'image_size_str'    => '',
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

                $this->db->query("UPDATE mailbox_accounts SET mailbox_accounts_downloading = '" . DB_BOOL_FALSE . "' WHERE mailbox_accounts_id  = '{$account['mailbox_accounts_id']}'");

                echo "Imported $count_messages messages and $count_skipped skipped\r\n\r\n";
            }
        }
    }
    public function testConnections($data)
    {
        $port = ($data['port']) ? $data['port'] : '993';
        $encryption = $data['encryption'];
        $protocol = ($data['validate'] == DB_BOOL_TRUE) ? "/imap/$encryption/validate-cert" : "/imap/ssl/novalidate-cert";
        $server = new Ddeboer\Imap\Server(
            $data['server'], // required
            $port,
            $protocol
        );

        try {
            $server->authenticate($data['email'], $data['password']);
            $result = [
                'success' => true,
                'message' => 'Credenziali valide'
            ];

            e_json($result);
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Credenziali non valide'
            ];

            e_json($result);
        }
    }
}
