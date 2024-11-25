<?php
echo_flush("Rimuovo il template 'Generico'\n");

// verifico se nella cartella APPPATH > modules > contabilita > templates esiste il file 4_Generico.tpl. se esiste, lo rimuovo
$dir = APPPATH . 'modules/contabilita/templates/';

if (file_exists($dir . '4_Generico.tpl')) {
    unlink($dir . '4_Generico.tpl');
}
