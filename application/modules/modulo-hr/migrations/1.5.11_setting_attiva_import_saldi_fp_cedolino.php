<?php
$this->mycache->clearCache();

$this->db->query("UPDATE impostazioni_hr SET impostazioni_hr_attiva_import_saldi_fp_da_cedolino = 0;");

$this->mycache->clearCache();