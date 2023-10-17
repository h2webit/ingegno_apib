<?php
// Set baseurl default
$this->db->query("UPDATE settings SET settings_auto_update_repository = 'https://admin.openbuilder.net/'");

// Force Stable Channel
$this->db->query("UPDATE settings SET settings_auto_update_channel = '4'");