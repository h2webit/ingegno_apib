<?php

$this->db->query("
ALTER TABLE `flussi_cassa`
CHANGE `flussi_cassa_risorsa` `flussi_cassa_risorsa` int(11) NULL;
    ");
