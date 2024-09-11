<?php

// Integrato il campo Template default nei template della contabilita. Questa migration setta il primo template (in teoria il generico base) come default
$settings = $this->db->query("SELECT * FROM magazzino_settings")->row_array();

if (count($settings) > 0) {
    $this->db->query("UPDATE magazzino_settings SET magazzino_settings_show_barcode = '".DB_BOOL_TRUE."'");
}