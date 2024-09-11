<?php
$this->mycache->clearCache();
$this->db->query("
    UPDATE mailbox_emails 
    SET mailbox_emails_ignore_email = '" . DB_BOOL_FALSE. "' 
    WHERE 
    mailbox_emails_ignore_email IS  NULL");
$this->mycache->clearCache();
