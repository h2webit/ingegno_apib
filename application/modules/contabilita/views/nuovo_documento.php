<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css' />
<script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js'></script>

<style>
#js_product_table>tbody>tr>td,
#js_product_table>tbody>tr>th,
#js_product_table>tfoot>tr>td,
#js_product_table>tfoot>tr>th,
#js_product_table>thead>tr>td {
    vertical-align: top !important;
}

/* New sub-table */
#js_product_table tr>td>table>tbody>tr>td,
#js_product_table tr>td>table>tbody>tr>th,
#js_product_table tr>td>table>tfoot>tr>td,
#js_product_table tr>td>table>tfoot>tr>th,
#js_product_table tr>td>table>thead>tr>td {
    vertical-align: top !important;
}


.row {
    margin-left: 0px !important;
    margin-right: 0px !important;
}

button {
    outline: none;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

.button_selected {
    /*opacity: 0.6;*/
    color: #fff;
    background-color: #00c0ef;
    border-color: #00acd6;
}

.button_selected:hover {
    color: #fff;
    background-color: #00acd6;
}

.button_selected:focus {
    color: #fff;
    background-color: #31b0d5;
    border-color: #1b6d85;
}

.table_prodotti td {
    vertical-align: top;
}

.totali label {
    display: block;
    font-weight: normal;
    text-align: left;
}

.totali label span {
    font-weight: bold;
    float: right;
}

label {
    font-size: 0.8em;
}

.rcr-adjust {
    /*width: 40%;*/
    width: 90%;
    display: inline;
}

.rcr_label label {
    width: 100%;
}

.margin-bottom-5 {
    margin-bottom: 5px;
}

.margin-left-20 {
    margin-left: 20px;
}

small,
.small {
    font-size: 75%;
}

.js_form_datepicker {
    width: 100% !important;
}

.causale-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-causale {
    font-size: 0.8em;
    margin-bottom: 3px
}

@media only screen and (max-width: 992px) {
    .mb-15 {
        margin-bottom: 15px;
    }

    .rcr-adjust {
        /*width: 90%;*/
    }

    .table-responsive {
        border: none;
    }

    /*Product table input field*/
    .js_documenti_contabilita_articoli_descrizione {
        min-width: 130px;
    }

    .js_documenti_contabilita_articoli_unita_misura,
    .js_documenti_contabilita_articoli_iva_id,
    .js_documenti_contabilita_articoli_prezzo,
    .js-importo {
        min-width: 85px;
    }

    .js_documenti_contabilita_articoli_sconto {
        min-width: 70px;
    }
}

@media only screen and (min-width: 992px) {
    .rcr-adjust {
        /*width: 60%;*/
        display: inline;
    }
}

/* New rules */
td.totali {
    font-size: 16px;
}

td.totali .sconto_totale {
    max-width: 50px;
    border: 1px solid #ccc;
}

/* Reset rules row, col and input-sm elements inside td */
table.table_prodotti tr td div.row,
table.table_prodotti tr td [class*='col-'],
table.table_prodotti tr td div.input-sm {
    margin: 0;
    padding: 0;
}

button.accordion {
    background-color: #b7d7ea;
    color: #444;
    cursor: pointer;
    padding: 18px;
    width: 100%;
    border: none;
    text-align: left;
    outline: none;
    font-size: 15px;
    transition: 0.4s;
}

div.panel_acc {
    background-color: #b7d7ea;
    display: none;
}

div.panel_acc.show {
    display: block !important;
}

#botton_back {
    display: inline-block;
    overflow: auto;
    white-space: nowrap;
    margin: 0px auto;
    float: right;
}

.help_text_custom_attributi {
    font-size: 0.8em;
    margin-bottom: 5px;
    margin-left: 5px;
    color: #64748b;
}

td.js_actions {
    min-width: 90px;
}

.drag-handle {
    cursor: pointer;
}
</style>
<?php
$this->load->model('contabilita/docs');
if ($this->datab->module_installed('magazzino')) {
    $this->load->model('magazzino/mov');
    
}
$general_settings = $this->apilib->searchFirst('documenti_contabilita_general_settings');

$field_azienda = $this->db->query("SELECT * FROM fields WHERE fields_name = 'documenti_contabilita_azienda'")->row()->fields_id;

$filtro_fatture = (array) @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];

$azienda_in_sessione = null;
if (!empty($filtro_fatture[$field_azienda]['value'])) {
    $azienda_in_sessione = $filtro_fatture[$field_azienda]['value'];
} elseif (!empty($filtro_fatture[0]['value']) && $filtro_fatture[0]['field_id'] == $field_azienda) {
    $azienda_in_sessione = $filtro_fatture[0]['value'];
}

$dati['id'] = null;
$dati['fattura'] = null;
$dati['fatture_cliente'] = null;
$dati['serie'] = null;
$dati['fatture_numero'] = null;
$dati['fatture_serie'] = null;
$dati['fatture_scadenza_pagamento'] = null;
$dati['fatture_pagato'] = null;
$dati['prodotti'] = null;
$dati['fatture_note'] = null;

/*
 * Install constants
 */
define('MODULE_NAME', 'fatture');

/** Entità **/
defined('ENTITY_SETTINGS') or define('ENTITY_SETTINGS', 'settings');
//defined('FATTURE_E_CUSTOMERS') or define('FATTURE_E_CUSTOMERS', 'clienti');

/** Parametri **/
defined('FATTURAZIONE_METODI_PAGAMENTO') or define('FATTURAZIONE_METODI_PAGAMENTO', serialize(array('Bonifico', 'Paypal', 'Contanti', 'Sepa RID', 'RIBA')));

defined('FATTURAZIONE_URI_STAMPA') or define('FATTURAZIONE_URI_STAMPA', null);

$elenco_iva = $this->apilib->search('iva', [], null, 0, 'iva_order');
$serie_documento = $this->apilib->search('documenti_contabilita_serie');
$conti_correnti = $this->apilib->search('conti_correnti');
$documento_id = ($value_id) ?: $this->input->get('documenti_contabilita_id');
$spesa_id = $this->input->get('spesa_id'); //Serve per l'autofattura reverse
$serie_get = $this->input->get('serie'); //Serve per l'autofattura reverse

$documenti_tipo = $this->apilib->search('documenti_contabilita_tipo');
$ordinamento_documenti_tipo = array_key_value_map($this->apilib->search('ordinamento_documenti_tipo', [], null, 0, 'ordinamento_documenti_tipo_order'), 'ordinamento_documenti_tipo_tipo', 'ordinamento_documenti_tipo_order');


usort($documenti_tipo, function ($t1, $t2) use ($ordinamento_documenti_tipo) {
    if (array_key_exists($t1['documenti_contabilita_tipo_id'], $ordinamento_documenti_tipo)) {
        if (array_key_exists($t2['documenti_contabilita_tipo_id'], $ordinamento_documenti_tipo)) {

            return $ordinamento_documenti_tipo[$t1['documenti_contabilita_tipo_id']] <=> $ordinamento_documenti_tipo[$t2['documenti_contabilita_tipo_id']];
        } else {

            return -1;
        }
    } else {

        if (array_key_exists($t2['documenti_contabilita_tipo_id'], $ordinamento_documenti_tipo)) {
            return 1;
        } else {
            return 0;
        }
    }
});

//debug($documenti_tipo,true);

$centri_di_costo = $this->apilib->search('centri_di_costo_ricavo');
$templates = $this->apilib->search('documenti_contabilita_template_pdf', [], null, 0, "(documenti_contabilita_template_pdf_default = '1')", 'DESC');
$fonts = $this->apilib->search('documenti_contabilita_font');
$tipi_ritenuta = $this->apilib->search('documenti_contabilita_tipo_ritenuta');
$tipi_cassa_pro = $this->apilib->search('documenti_contabilita_cassa_professionisti_tipo');
$valute = $this->apilib->search('valute', [], null, 0, 'valute_codice');
$clone = $this->input->get('clone');
$tipo_destinatario = $this->apilib->search('documenti_contabilita_tipo_destinatario');
$rifDocId = '';
$rif_documenti_ids = [];
$show_iva_advisor = false;

$_campi_personalizzati = $this->apilib->search('campi_righe_articoli', [], null, 0, 'campi_righe_articoli_pos');
$campi_personalizzati = [1 => [], 2 => []];
foreach ($_campi_personalizzati as $key => $campo) {
    if (!$campo['campi_righe_articoli_riga'] || $campo['campi_righe_articoli_riga'] > 2) {
        $campo['campi_righe_articoli_riga'] = 1;
    }
    $field = $this->datab->get_field_by_name($campo['campi_righe_articoli_campo'], true);
    if ($field['fields_ref']) {
        $field['support_data'] = $this->crmentity->getEntityPreview($field['fields_ref'], $field['fields_select_where'], null);
    } else {
        if (!empty($field['fields_additional_data'])) {
            $support_data = explode(',', $field['fields_additional_data']);
            $field['support_data'] = array_combine($support_data, $support_data);
        }
    }
    $campi_personalizzati[$campo['campi_righe_articoli_riga']][$campo['campi_righe_articoli_pos']] = array_merge($campo, $field);
    $field['data_name'] = $field['fields_name'];
    $field['forms_fields_dependent_on'] = '';

    $data = [
        'lang' => '',
        'field' => $field,
        'value' => '',
        'label' => '',
        // '<label class="control-label">' . $field['fields_draw_label'] . '</label>',
        'placeholder' => '',
        'help' => '',
        'class' => 'input-sm',
        'attr' => 'data-name="' . $field['data_name'] . '"',
        'onclick' => '',
        'subform' => '',
    ];
    $campi_personalizzati[$campo['campi_righe_articoli_riga']][$campo['campi_righe_articoli_pos']]['html'] = str_ireplace('select2_standard', '', sprintf('<div class="form-group">%s</div>', $this->load->view("box/form_fields/{$field['fields_draw_html_type']}", $data, true)));
}

$accorpamento_documenti = false;
$ddt_gia_fatturati = null;
$ddt_con_sconto = null;

if ($documento_id) {
    $documento = $this->apilib->view('documenti_contabilita', $documento_id);

    if ($documento['documenti_contabilita_data_emissione'] <= '2020-12-31 23:59:59') {
        $show_iva_advisor = true;
    }

    //debug($documento,true);
    //$documento['articoli'] = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);
    $documento['articoli'] = $this->docs->getArticoliFromDocumento($documento_id);
    
    if ($clone == DB_BOOL_TRUE && !empty($documento) && $general_settings['documenti_contabilita_general_settings_auto_rif_doc']) {
        $riga_desc_rif_doc = [
            'documenti_contabilita_articoli_id' => null,
            'documenti_contabilita_articoli_rif_riga_articolo' => null,
            'documenti_contabilita_articoli_riga_desc' => DB_BOOL_TRUE,
            'documenti_contabilita_articoli_codice' => null,
            'documenti_contabilita_articoli_name' => 'Rif. ' . $documento['documenti_contabilita_tipo_value'] . ' n. ' . $documento['documenti_contabilita_numero'] . '' . (!empty($documento['documenti_contabilita_serie']) ? '/' . $documento['documenti_contabilita_serie'] : ''),
            'documenti_contabilita_articoli_descrizione' => 'del ' . dateFormat($documento['documenti_contabilita_data_emissione']),
            'documenti_contabilita_articoli_prezzo' => 0,
            'documenti_contabilita_articoli_imponibile' => 0,
            'documenti_contabilita_articoli_iva' => '',
            'documenti_contabilita_articoli_prodotto_id' => '',
            'documenti_contabilita_articoli_codice_asin' => '',
            'documenti_contabilita_articoli_codice_ean' => '',
            'documenti_contabilita_articoli_unita_misura' => '',
            'documenti_contabilita_articoli_quantita' => 1,
            'documenti_contabilita_articoli_sconto' => '',
            'documenti_contabilita_articoli_applica_ritenute' => '',
            'documenti_contabilita_articoli_applica_sconto' => '',
            'documenti_contabilita_articoli_importo_totale' => '',
            'documenti_contabilita_articoli_iva_id' => 1,
            'documenti_contabilita_articoli_periodo_comp' => '',

        ];
        
        array_unshift($documento['articoli'] , $riga_desc_rif_doc);
    }
    
//    debug($documento['articoli'], true);
    
    $documento['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
    $documento['documenti_contabilita_destinatario'] = json_decode($documento['documenti_contabilita_destinatario'], true);
    $documento['entity_destinatario'] = ($documento['documenti_contabilita_supplier_id']) ? 'suppliers' : 'clienti';

    $documento['documenti_contabilita_fe_dati_contratto'] = json_decode($documento['documenti_contabilita_fe_dati_contratto'], true);
    $documento['documenti_contabilita_fe_ordineacquisto'] = json_decode($documento['documenti_contabilita_fe_ordineacquisto'], true);

    //debug(array_filter($documento['documenti_contabilita_fe_dati_contratto']));

    //L'associazione singola andra prima o poi dismessa... teniamo solo per retrocompatibilità
    $rifDoc = $documento['documenti_contabilita_rif_documento_id'];
    $rif_documenti = $documento['documenti_contabilita_rif_documenti'];

    if ($clone) {
        foreach ($documento['articoli'] as $key => $p) {
            $documento['articoli'][$key]['documenti_contabilita_articoli_rif_riga_articolo'] = $documento['articoli'][$key]['documenti_contabilita_articoli_id'];
            $documento['articoli'][$key]['documenti_contabilita_articoli_id'] = null;
        }
        if (!empty($rifDoc)) {
            if ($documento_id == $rifDoc) {
                $rifDocId = $rifDoc;
                //Questa è la nuova gestione multi-documenti
                $rif_documenti_ids = [$rifDoc];
            } else {
                $rifDocId = $documento_id;
                //Questa è la nuova gestione multi-documenti
                $rif_documenti_ids = [$documento_id];
            }
        } else {
            $rifDocId = $documento_id;
            //Questa è la nuova gestione multi-documenti
            $rif_documenti_ids = [$documento_id];
        }
        //debug($rif_documenti_ids,true);
    } else {
        if (empty($rif_documenti)) { //Prima o poi non entrerà più in questo if perchè questo campo sarà sempre valorizzato nel caso di documenti clonati o collegati
            if (!empty($rifDoc)) {
                $rifDocId = $rifDoc;
                //Questa è la nuova gestione multi-documenti
                $rif_documenti_ids = [$rifDoc];
            }
        } else {
            $rif_documenti_ids = $rif_documenti;
            //debug($rif_documenti_ids,true);
        }

    }
} elseif ($this->input->post('ddt_ids') || $this->input->get('ddt_id')) {
    $accorpamento_documenti = true;
    $ids = json_decode($this->input->post('ddt_ids'), true);
    $rif_documenti_ids = $ids;
    if ($this->input->post('bulk_action') == 'Genera fattura distinta') {
        $tipo = 'DDT';
        //Apro una tab per ogni ddt selezionato e gli passo il ddt
        foreach ($ids as $key => $id) {
            if ($key == 0) {
                //Il primo lo skippo perchè lo processerò qua...
                continue;
            } ?>
<script>
window.open('<?php echo base_url(); ?>main/layout/nuovo_documento/?ddt_id=<?php echo $id; ?>', '_blank');
</script>
<?php
        }
        //Una volta aperte le tab (una per ddt) continuo con questo, quindi tolgo gli altri da ids...
        $ids = [$ids[0]];
    } else {
        if (!$ids) { //Se non arrivano in post, sono delle tab, una per ddocumento... quindi lo prendo da get
            $ids = [$this->input->get('ddt_id')];
        } else {
            //Mi sono arrivati in post da una bulk action. Devo però capire se mi arrivano da un elenco ddt o da degli ordini
            if ($this->input->post('tipo_doc')) {
                $tipo = $this->input->post('tipo_doc');
            } else {
                $tipo = 'DDT';
            }
        }
    }

    if ($this->input->post('bulk_action') == 'Genera fattura accorpata' && $this->input->post('tipo_doc') == 'DDT') {
        // In questa sezione prendo i ddt che son già stati fatturati
        $ddt_relazionati = $this->db
            ->join('documenti_contabilita', "rel_doc_contabilita_rif_documenti.documenti_contabilita_id = documenti_contabilita.documenti_contabilita_id", "left")
            ->where('documenti_contabilita_tipo', 1)
            ->where("documenti_contabilita.documenti_contabilita_id IN (".implode(',', $ids).")", null, false)
            ->get('rel_doc_contabilita_rif_documenti')->result_array();
        
        if (!empty($ddt_relazionati)) {
            $ddt_gia_fatturati = array_filter(array_unique(array_map(function($doc) {
                return 'N° ' . $doc['documenti_contabilita_numero'] . (!empty($doc['documenti_contabilita_serie']) ? '/' . $doc['documenti_contabilita_serie'] : '');
            }, $ddt_relazionati)));
        } else {
            // non faccio nulla
        }

        /////////////////////////////////////////////////////////////////////////////////////

        // in questa sezione verifico quali ddt hanno uno sconto percentuale
        $ddt_con_sconto = $this->db
            ->where("(documenti_contabilita_id IN (".implode(',', $ids)."))", null, false)
            ->where("(documenti_contabilita_sconto_percentuale IS NOT NULL AND documenti_contabilita_sconto_percentuale <> '' AND documenti_contabilita_sconto_percentuale > 0)", null, false)
            ->get('documenti_contabilita')->result_array();

        if (!empty($ddt_con_sconto)) {
            $ddt_con_sconto = array_filter(array_unique(array_map(function($doc) {
                return 'N° ' . $doc['documenti_contabilita_numero'] . (!empty($doc['documenti_contabilita_serie']) ? '/' . $doc['documenti_contabilita_serie'] : '');
            }, $ddt_con_sconto)));
        } else {
            // non faccio nulla
        }
        $tipo = 'Fattura';
    }

    $clone = true;
    $documento_id = $ids[0];

    $documento = $this->apilib->view('documenti_contabilita', $documento_id);
    
    $tipi_doc = $this->db->get('documenti_contabilita_tipo')->result_array();
    
    $tipi_doc_map = array_column($tipi_doc, 'documenti_contabilita_tipo_id', 'documenti_contabilita_tipo_value');

    //Per retrocompatibilità
    if ($tipo == 'DDT' && empty($tipi_doc_map[$tipo])) {
        $tipo = 'DDT Cliente';
    }
    
    $documento['documenti_contabilita_tipo'] = $tipi_doc_map[$tipo];


    if ($this->input->post('bulk_action') == 'Genera fattura accorpata') {
        $documento['documenti_contabilita_formato_elettronico'] = DB_BOOL_TRUE;
        $documento['documenti_contabilita_tipologia_fatturazione'] = 1;
    }
    //Per prima cosa prendo il template default. 
    foreach ($templates as $template) {
        if ($template['documenti_contabilita_template_pdf_default'] == DB_BOOL_TRUE) {
            $documento['documenti_contabilita_template_pdf'] = $template['documenti_contabilita_template_pdf_id'];
            break;
        }
    }

    //A questo punto, cerco se c'è un template specifico per il tipo di documento...
    foreach ($templates as $template) {
        
        
        if ($template['documenti_contabilita_template_pdf_tipo'] == $documento['documenti_contabilita_tipo']) {
            
            $documento['documenti_contabilita_template_pdf'] = $template['documenti_contabilita_template_pdf_id'];
            break;
        }
    }

    $documenti_contabilita = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_id IN (' . implode(',', $ids) . ')']);
    
    $documento['articoli'] = [];
    foreach ($documenti_contabilita as $doc) {
        $articoli_documento = $this->docs->getArticoliFromDocumento($doc['documenti_contabilita_id']);
        
        foreach ($articoli_documento as $index => $articolo) {
            $articoli_documento[$index]['documenti_contabilita_articoli_rif_riga_articolo'] = $articolo['documenti_contabilita_articoli_id'];
            
            unset($articoli_documento[$index]['documenti_contabilita_articoli_id']);
            unset($articoli_documento[$index]['documenti_contabilita_articoli_creation_date']);
            unset($articoli_documento[$index]['documenti_contabilita_articoli_modified_date']);
        }
        if ($general_settings['documenti_contabilita_general_settings_auto_rif_doc']) {
            $riga_desc_rif_doc = [
                'documenti_contabilita_articoli_id' => null,
                'documenti_contabilita_articoli_rif_riga_articolo' => null,
                'documenti_contabilita_articoli_riga_desc' => DB_BOOL_TRUE,
                'documenti_contabilita_articoli_codice' => null,
                'documenti_contabilita_articoli_name' => 'Rif. ' . $doc['documenti_contabilita_tipo_value'] . ' n. ' . $doc['documenti_contabilita_numero'] . '' . (!empty($doc['documenti_contabilita_serie']) ? '/' . $doc['documenti_contabilita_serie'] : ''),
                'documenti_contabilita_articoli_descrizione' => 'del ' . dateFormat($doc['documenti_contabilita_data_emissione']),
                'documenti_contabilita_articoli_prezzo' => 0,
                'documenti_contabilita_articoli_imponibile' => 0,
                'documenti_contabilita_articoli_iva' => '',
                'documenti_contabilita_articoli_prodotto_id' => '',
                'documenti_contabilita_articoli_codice_asin' => '',
                'documenti_contabilita_articoli_codice_ean' => '',
                'documenti_contabilita_articoli_unita_misura' => '',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_sconto' => '',
                'documenti_contabilita_articoli_applica_ritenute' => '',
                'documenti_contabilita_articoli_applica_sconto' => '',
                'documenti_contabilita_articoli_importo_totale' => '',
                'documenti_contabilita_articoli_iva_id' => 1,
            ];
        
            array_unshift($articoli_documento, $riga_desc_rif_doc);
        }
        
        $documento['articoli'] = array_merge($documento['articoli'], $articoli_documento);
    }
    //debug($documento,true);
    $documento['scadenze'] = $this->apilib->search('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
    $documento['documenti_contabilita_destinatario'] = json_decode($documento['documenti_contabilita_destinatario'], true);
    $documento['entity_destinatario'] = ($documento['documenti_contabilita_supplier_id']) ? 'suppliers' : 'clienti';
    
    //debug($this->input->post('ddt_ids'));
} //Aggiungere qua il controllo se mi arrivano dei prodotti generici in post...
elseif ($this->input->post('articoli') && is_array($this->input->post('articoli'))) {
    $articoli_post = $this->input->post('articoli');

    // debug($articoli_post);
    foreach ($articoli_post as $index => $articolo) {
        if (empty(trim($articolo['nome'])) && empty($articolo['prezzo'])) {
            continue;
        }
        $prezzo = 0;
        if (!empty($articolo['prezzo']) && is_numeric($articolo['prezzo'])) {
            $prezzo = $articolo['prezzo'];
        }
        $nome_articolo = trim($articolo['nome']);

        $desc = '';
        if (!empty($articolo['descrizione'])) {
            if (!empty(trim($articolo['descrizione']))) {
                $desc = trim($articolo['descrizione']);
            }
        }

        $codice = '';
        if (!empty($articolo['codice'])) {
            $codice = trim($articolo['codice']);
        }
        
        $quantita = 1;
        if (!empty($articolo['quantita']) && is_numeric($articolo['quantita'])) {
            $quantita = $articolo['quantita'];
        }
        
        $documento['articoli'][$index] = [
            //            'documenti_contabilita_articoli_id' => null,
            
            'documenti_contabilita_articoli_codice' => $codice,
            'documenti_contabilita_articoli_name' => $nome_articolo,
            'documenti_contabilita_articoli_descrizione' => $desc,
            
            'documenti_contabilita_articoli_rif_riga_articolo' => $articolo['id_riga'] ?? null,
            'documenti_contabilita_articoli_riga_desc' => (string) (isset($articolo['riga_desc']) && $articolo['riga_desc']),
            
            'documenti_contabilita_articoli_prezzo' => $prezzo,
            'documenti_contabilita_articoli_quantita' => $quantita,
            'documenti_contabilita_articoli_imponibile' => 0,
            'documenti_contabilita_articoli_iva_id' => $articolo['iva_id'] ?? 1,
            'documenti_contabilita_articoli_iva' => 0,
            'documenti_contabilita_articoli_iva_perc' => $articolo['iva_perc'] ?? 0,
            'documenti_contabilita_articoli_importo_totale' => 0,
            
            'documenti_contabilita_articoli_prodotto_id' => null,
            'documenti_contabilita_articoli_codice_asin' => null,
            'documenti_contabilita_articoli_codice_ean' => null,
            'documenti_contabilita_articoli_unita_misura' => null,
            'documenti_contabilita_articoli_sconto' => 0,
            'documenti_contabilita_articoli_applica_ritenute' => DB_BOOL_TRUE,
            'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_TRUE,
        ];
        
        if (isset($articolo['rif_pagamento']) && !empty($articolo['rif_pagamento'])) {
            $documento['articoli'][$index]['documenti_contabilita_articoli_rif_pagamento'] = $articolo['rif_pagamento'];
        }
        
        // aggiungo campi personalizzati all'array $documento['articoli']
        if (!empty($campi_personalizzati[1])) {
            foreach ($campi_personalizzati[1] as $campo) {
                if (array_key_exists($campo['campi_righe_articoli_map_to'], $articolo) && !empty($articolo[$campo['campi_righe_articoli_map_to']])) {
                    $documento['articoli'][$index][$campo['campi_righe_articoli_map_to']] = $articolo[$campo['campi_righe_articoli_map_to']];
                }
            }
        }
    }
} elseif ($spesa_id) {
    //Sto generando un'autofattura partendo dalla spesa...
    $spesa = $this->apilib->view('spese', $spesa_id);
    $spesa_articoli = $this->apilib->search('spese_articoli', ['spese_articoli_spesa' => $spesa_id]);

    $clone = true;
    $documento['documenti_contabilita_tipo'] = 11;
    $documento['documenti_contabilita_tipologia_fatturazione'] = $spesa['spese_tipologia_autofattura'];

    //debug($documento,true);
    foreach ($templates as $template) {
        //Cerco di trovare un template di tipo reverse...
        //debug($template,true);
        if (stripos($template['documenti_contabilita_template_pdf_nome'], 'everse')) {
            //debug($template['documenti_contabilita_template_pdf_id'],true);
            $documento['documenti_contabilita_template_pdf'] = $template['documenti_contabilita_template_pdf_id'];
        }
    }

    //debug($documento,true);
    $documento['articoli'] = [];
    if ($spesa_articoli) {
        debug('TODO: Registrazioni singole in spesa non gestite!', true);
    } else {
        if ($this->input->get('doc_type') == 'Nota di credito Reverse') {
            $sign = -1;
        } else {
            $sign = 1;
        }
        //debug($spesa, true);

        $documento['articoli'][] = [
            'documenti_contabilita_articoli_name' => 'Beni e servizi',
            'documenti_contabilita_articoli_prezzo' => number_format($spesa['spese_imponibile'] * $sign, 2, '.', ''),
            'documenti_contabilita_articoli_iva' => $spesa['spese_iva'],
            'documenti_contabilita_articoli_iva_id' => '',
            'documenti_contabilita_articoli_iva_perc' => 22,
            'documenti_contabilita_articoli_importo_totale' => $spesa['spese_totale'] * $sign,
            'documenti_contabilita_articoli_imponibile' => $spesa['spese_imponibile'] * $sign,
            'documenti_contabilita_articoli_quantita' => 1,
            'documenti_contabilita_articoli_codice' => '',
            'documenti_contabilita_articoli_unita_misura' => '',
            'documenti_contabilita_articoli_sconto' => '0',
            'documenti_contabilita_articoli_prodotto_id' => null,
            'documenti_contabilita_articoli_applica_ritenute' => DB_BOOL_FALSE,
            'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_FALSE,
            'documenti_contabilita_articoli_id' => null,
            'documenti_contabilita_articoli_descrizione' => '',
            'documenti_contabilita_articoli_codice_asin' => '',
            'documenti_contabilita_articoli_codice_ean' => '',
            'documenti_contabilita_articoli_rif_riga_articolo' => '',
            'documenti_contabilita_articoli_riga_desc' => DB_BOOL_FALSE,
        ];
        //debug($documento['articoli'],true);
    }
    $documento['documenti_contabilita_destinatario'] = json_decode($spesa['spese_fornitore'], true);
    // $documento['documenti_contabilita_destinatario']['nazione'] = '';
    $documento['entity_destinatario'] = 'customers';
}
$azienda_get = $this->input->get('documenti_contabilita_settings') ?? null;
$settings = $this->apilib->search('documenti_contabilita_settings');
$impostazioni = $settings[0];

//Rimosse le mappature. Ora punta tutto a customers
$mappature = $this->docs->getMappature();
$mappature_autocomplete = $this->docs->getMappatureAutocomplete();

extract($mappature);

//$entita_prodotti = 'listino_prezzi'; // commentato in quanto $entita_prodotti è preso dalla variabile $mappature (quindi mappato in db)
$entita = $entita_prodotti;
$campo_codice = $campo_codice_prodotto;
$campo_unita_misura = (!empty($campo_unita_misura_prodotto)) ? $campo_unita_misura_prodotto : '';
$campo_preview = $campo_preview_prodotto;
$campo_prezzo = $campo_prezzo_prodotto;
$campo_prezzo_fornitore = (!empty($campo_prezzo_fornitore)) ? $campo_prezzo_fornitore : '';
$campo_quantita = (!empty($campo_quantita_prodotto)) ? $campo_quantita_prodotto : '';
$campo_iva = @$campo_iva_prodotto;
$campo_provvigione = @$campo_provvigione_prodotto;
$campo_ricarico = @$campo_ricarico_prodotto;
$campo_descrizione = $campo_descrizione_prodotto;
$campo_sconto = (!empty($campo_sconto_prodotto)) ? $campo_sconto_prodotto : '';
$campo_sconto2 = (!empty($campo_sconto2_prodotto)) ? $campo_sconto2_prodotto : '';
$campo_sconto3 = (!empty($campo_sconto3_prodotto)) ? $campo_sconto3_prodotto : '';
$campo_centro_costo = (!empty($campo_centro_costo_prodotto)) ? $campo_centro_costo_prodotto : '';

//debug($campo_centro_costo, true);

$campo_id = (empty($campo_id_prodotto)) ? $entita . '_id' : $campo_id_prodotto;

$articoli_ids = $this->input->get_post('articoli_ids');

if (!empty($articoli_ids)) {
    $articoli_ids = implode(',', $articoli_ids);
    $articoli = $this->apilib->search($entita, [$entita . '_id IN (' . $articoli_ids . ')']);

    if (!empty($articoli)) {
        foreach ($articoli as $key => $value) {

            $documento['articoli'][$key] = [
                'documenti_contabilita_articoli_codice' => $value[$campo_codice],
                'documenti_contabilita_articoli_name' => $value[$campo_preview],
                'documenti_contabilita_articoli_descrizione' => $value[$campo_descrizione],
                'documenti_contabilita_articoli_prezzo' => $value[$campo_prezzo],
                'documenti_contabilita_articoli_iva' => $value[$campo_iva],
                'documenti_contabilita_articoli_prodotto_id' => $value[$campo_id],
                'documenti_contabilita_articoli_codice_asin' => '',
                'documenti_contabilita_articoli_codice_ean' => '',
                'documenti_contabilita_articoli_unita_misura' => '',
                'documenti_contabilita_articoli_quantita' => 1,
                'documenti_contabilita_articoli_sconto' => '',
                'documenti_contabilita_articoli_applica_ritenute' => '',
                'documenti_contabilita_articoli_applica_sconto' => '',
                'documenti_contabilita_articoli_importo_totale' => '',
                'documenti_contabilita_articoli_iva_id' => 1,
            ];
        }
    }
}
if ($this->input->get('documenti_contabilita_clienti_id')) {
    $customer = $this->apilib->view($entita_clienti, $this->input->get('documenti_contabilita_clienti_id'));
    $documento['documenti_contabilita_customer_id'] = $this->input->get('documenti_contabilita_clienti_id');

    $nazione_cliente = $customer[$clienti_nazione];
    if (!empty($mappature_autocomplete['clienti_nazione']) && $mappature_autocomplete['clienti_nazione'] != $clienti_nazione) {
        $nazione_cliente = $customer[$mappature_autocomplete['clienti_nazione']];
    }

    $cliente = [
        'codice' => $customer[$clienti_codice],
        'ragione_sociale' => $customer[$clienti_ragione_sociale] ?? $customer[$clienti_nome] . ' ' . $customer[$clienti_cognome],
        'indirizzo' => $customer[$clienti_indirizzo],
        'citta' => $customer[$clienti_citta],
        'nazione' => $nazione_cliente,
        'cap' => $customer[$clienti_cap],
        'provincia' => $customer[$clienti_provincia],
        'partita_iva' => $customer[$clienti_partita_iva],
        'codice_fiscale' => $customer[$clienti_codice_fiscale],
        'codice_sdi' => $customer[$clienti_codice_sdi],
        'pec' => $customer[$clienti_pec],
    ];

    $documento['documenti_contabilita_destinatario'] = $cliente;
    $documento['entity_destinatario'] = $entita_clienti;
}

if ($documenti_contabilita_articoli_ids = $this->input->get_post('documenti_contabilita_articoli_ids')) {

    $cliente = false;
    //debug($documenti_contabilita_articoli_ids,true);
    foreach (json_decode($documenti_contabilita_articoli_ids) as $key => $documenti_contabilita_articoli_id) {
        $riga_articolo = $this->db
            ->join('documenti_contabilita', 'documenti_contabilita_id = documenti_contabilita_articoli_documento', 'LEFT')
            ->join('documenti_contabilita_tipo', 'documenti_contabilita_tipo_id = documenti_contabilita_tipo', 'LEFT')
            ->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $documenti_contabilita_articoli_id])->row_array();
        //$riga_articolo = $this->apilib->searchFirst('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $documenti_contabilita_articoli_id], 0, null, 'ASC', 3);
        if (!$riga_articolo) {
            continue;
        }
        if (!in_array($riga_articolo['documenti_contabilita_articoli_documento'], $rif_documenti_ids)) {
            $rif_documenti_ids[] = $riga_articolo['documenti_contabilita_articoli_documento'];
        }

        $qty_evasa = $riga_articolo['documenti_contabilita_articoli_qty_evase_in_doc'] + $riga_articolo['documenti_contabilita_articoli_qty_movimentate'];

        $rimanente = $riga_articolo['documenti_contabilita_articoli_quantita'] - $qty_evasa;

        unset($riga_articolo['documenti_contabilita_articoli_id']);
        unset($riga_articolo['documenti_contabilita_articoli_documento']);
        //Associo la riga alla riga originale...
        $riga_articolo['documenti_contabilita_articoli_rif_riga_articolo'] = $documenti_contabilita_articoli_id;

        // debug($riga_articolo,true);

        $tipo = $riga_articolo['documenti_contabilita_tipo_value'];
        $numero = $riga_articolo['documenti_contabilita_numero'] . (!empty($riga_articolo['documenti_contabilita_serie']) ? ' / ' . $riga_articolo['documenti_contabilita_serie'] : '');
        $data = dateFormat($riga_articolo['documenti_contabilita_data_emissione']);

        $desc = "{$tipo} N. {$numero} del {$data}" . ((!empty($riga_articolo['documenti_contabilita_oggetto'])) ? " | {$riga_articolo['documenti_contabilita_oggetto']}" : '');
        if (empty($riga_articolo['documenti_contabilita_articoli_descrizione'])) {
            $riga_articolo['documenti_contabilita_articoli_descrizione'] = $desc;
        } else {
            $riga_articolo['documenti_contabilita_articoli_descrizione'] .= "\n\n" . $desc;
        }

        if (!$cliente) {
            $articolo_full_data = $this->apilib->view('documenti_contabilita_articoli', $documenti_contabilita_articoli_id);
            $cliente_id = $articolo_full_data['documenti_contabilita_customer_id'];
            if ($cliente_id) {
                $customer = $this->apilib->view($entita_clienti, $cliente_id);
                $documento['documenti_contabilita_customer_id'] = $cliente_id;

                $nazione_cliente = $customer[$clienti_nazione];
                if (!empty($mappature_autocomplete['clienti_nazione']) && $mappature_autocomplete['clienti_nazione'] != $clienti_nazione) {
                    $nazione_cliente = $customer[$mappature_autocomplete['clienti_nazione']];
                }

                $cliente = [
                    'codice' => $customer[$clienti_codice],
                    'ragione_sociale' => $customer[$clienti_ragione_sociale] ?? $customer[$clienti_nome] . ' ' . $customer[$clienti_cognome],
                    'indirizzo' => $customer[$clienti_indirizzo],
                    'citta' => $customer[$clienti_citta],
                    'nazione' => $nazione_cliente,
                    'cap' => $customer[$clienti_cap],
                    'provincia' => $customer[$clienti_provincia],
                    'partita_iva' => $customer[$clienti_partita_iva],
                    'codice_fiscale' => $customer[$clienti_codice_fiscale],
                    'codice_sdi' => $customer[$clienti_codice_sdi],
                    'pec' => $customer[$clienti_pec],
                ];
                $documento['documenti_contabilita_destinatario'] = $cliente;
            } else {
                $documento['documenti_contabilita_destinatario'] = json_decode($articolo_full_data['documenti_contabilita_destinatario'], true);
            }




            $documento['entity_destinatario'] = $entita_clienti;
        }

        //debug($articolo,true);
        //Costruisco l'array da passare in modo che vengano precompilate le righe articolo
        //Passo pari pari tutti i dati, tranne la quantità, ricalcolando quella rimanente

        $riga_articolo['documenti_contabilita_articoli_quantita'] = $rimanente;




        $documento['articoli'][$key] = $riga_articolo;
    }
}


$metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');

$metodi_pagamento_map = [];

foreach ($metodi_pagamento as $metodo) {
    $metodi_pagamento_map[$metodo['documenti_contabilita_metodi_pagamento_valore']] = $metodo['documenti_contabilita_metodi_pagamento_id'];
}

$metodi_pagamento_map_reverse = array_flip($metodi_pagamento_map);

$tipologie_fatturazione = $this->apilib->search('documenti_contabilita_tipologie_fatturazione');
$ddts = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_tipo' => '8']); // @todo - 20190704 - Michael E. - Aggiungere poi il filtro documenti_contabilita_utente_id per filtrare solo i ddt dell'utente loggato

//Mi costruisco un oggetto da riutilizzare per le scadenze automatiche
$template_scadenze = $this->apilib->search('documenti_contabilita_template_pagamenti', [
    'documenti_contabilita_template_pagamenti_id IN (SELECT documenti_contabilita_tpl_pag_scadenze_tpl_id FROM documenti_contabilita_tpl_pag_scadenze)'
]);
foreach ($template_scadenze as $key => $tpl_scad) {
    //Riordino le sotto scadenze sul campo "giorni"
    usort($tpl_scad['documenti_contabilita_tpl_pag_scadenze'], function ($a, $b) {
        return ($a['documenti_contabilita_tpl_pag_scadenze_giorni'] < $b['documenti_contabilita_tpl_pag_scadenze_giorni']) ? -1 : 1;
    });
    $template_scadenze[$key] = $tpl_scad;
}

//Mi costruisco un oggetto da riutilizzare per i listini automatici
$listini = $this->apilib->search('listini');
//debug($campi_personalizzati,true);
//La prima riga detta legge... tutti gli altri campi vengono mostrati nella riga 2 e devono essere <= ai campi di riga 1
$colonne_count = 9 + count($campi_personalizzati[1]) + ($impostazioni['documenti_contabilita_settings_commessa'] ? 1 : 0) + ($impostazioni['documenti_contabilita_settings_lotto'] ? 1 : 0) + ($impostazioni['documenti_contabilita_settings_periodo_comp'] ? 1 : 0) + ($impostazioni['documenti_contabilita_settings_scadenza'] ? 1 : 0)+ ($impostazioni['documenti_contabilita_settings_sconto2'] ? 1 : 0) + ($impostazioni['documenti_contabilita_settings_sconto3'] ? 1 : 0) + ($campo_centro_costo ? 1 : 0);

for ($i = 1; $i <= $colonne_count; $i++) {
    if (!array_key_exists($i, $campi_personalizzati[2])) {
        //debug($campi_personalizzati[2]);
        $campi_personalizzati[2][$i] = false;
    }
}
ksort($campi_personalizzati[2]);

$nazioni = $this->apilib->search('countries', [], null, 0, 'countries_name', 'ASC');
if (!empty($documento)) {
    if (isset($documento['documenti_contabilita_destinatario'])) {
        if (!empty($documento['documenti_contabilita_destinatario']['nazione']) && ($documento['documenti_contabilita_destinatario']['nazione'] === 'Italia' || $documento['documenti_contabilita_destinatario']['nazione'] == '105')) {
            $documento['documenti_contabilita_destinatario']['nazione'] = 'IT';
        }
    }
}

$_dest_id = null;

if ((($this->input->get('documenti_contabilita_clienti_id') || $this->input->get('documenti_contabilita_customer_id')) || $documento_id) && $documento['documenti_contabilita_customer_id']) {
    $_dest_id = $documento['documenti_contabilita_supplier_id'] ?? $documento['documenti_contabilita_customer_id'];
} elseif (((!$this->input->get('documenti_contabilita_clienti_id') || !$this->input->get('documenti_contabilita_customer_id')) || empty($documento_id)) && !empty($articoli_ids)) {
    // qua entro se ho passato degli articoli ma non sono in modifica, quindi cerco se c'è un fornitore di default

    $articoli_ids = $this->input->get_post('articoli_ids');
    $articoli_ids_elenco = implode(',', $articoli_ids);
    $articoli = $this->apilib->search($entita, [$entita . '_id IN (' . $articoli_ids_elenco . ')']);

    if (!empty($articoli)) {
        foreach ($articoli as $key => $value) {
            if ($value['fw_products_supplier'] != '') {
                $_dest_id = $value['fw_products_supplier'];

                if (!empty($_dest_id)) {
                    $customer = $this->apilib->view($entita_clienti, $value['fw_products_supplier']);

                    if (!empty($customer)) {
                        $documento['documenti_contabilita_customer_id'] = $value['fw_products_supplier'];

                        $nazione_cliente = $customer[$clienti_nazione];
                        if (!empty($mappature_autocomplete['clienti_nazione']) && $mappature_autocomplete['clienti_nazione'] != $clienti_nazione) {
                            $nazione_cliente = $customer[$mappature_autocomplete['clienti_nazione']];
                        }

                        $cliente = [
                            'codice' => $customer[$clienti_codice],
                            'ragione_sociale' => $customer[$clienti_ragione_sociale] ?? $customer[$clienti_nome] . ' ' . $customer[$clienti_cognome],
                            'indirizzo' => $customer[$clienti_indirizzo],
                            'citta' => $customer[$clienti_citta],
                            'nazione' => $nazione_cliente,
                            'cap' => $customer[$clienti_cap],
                            'provincia' => $customer[$clienti_provincia],
                            'partita_iva' => $customer[$clienti_partita_iva],
                            'codice_fiscale' => $customer[$clienti_codice_fiscale],
                            'codice_sdi' => $customer[$clienti_codice_sdi],
                            'pec' => $customer[$clienti_pec],
                        ];

                        $documento['documenti_contabilita_destinatario'] = $cliente;
                        $documento['entity_destinatario'] = $entita_clienti;
                    }
                }
            }
        }
    }
}

//Se ho un customer id, precarico le sue commesse
if ($_dest_id && $this->db->table_exists('projects')) {
    //debug($_dest_id);
    $_commesse = $this->db
        ->where('projects_customer_id', $_dest_id)
        ->where_not_in('projects_status', [3,4,5])
        ->get('projects')->result_array();
    foreach ($_commesse as $commessa) {
        $commesse[$commessa['projects_id']] = $commessa;

    }
}
//se ci sono degli articoli, metto comunque l'elenco delle commesse eventualmente selezionate in quell'articolo (questo permette in edit o accorpamenti di non perdere mai il riferimento alla commessa)
if (!empty($documento['articoli'])) {
    foreach ($documento['articoli'] as $key => $value) {
        if (!empty($value['documenti_contabilita_articoli_commessa'])) {
            $commesse[$value['documenti_contabilita_articoli_commessa']] = $this->db
                ->where('projects_id', $value['documenti_contabilita_articoli_commessa'])
                ->get('projects')->row_array();
        }
    }
}


if (empty($general_settings['documenti_contabilita_general_settings_agenti'])) {
    $agenti_vendita = $this->apilib->search('users', ['users_type' => [1, 5]]); //Admin or Agent

} else {
    
    $agenti_vendita = $this->apilib->search('users', ['users_type' => array_keys($general_settings['documenti_contabilita_general_settings_agenti'])]); //Admin or Agent
}



$agente_vendita_corrente = ($documento_id && $documento['documenti_contabilita_agente']) ? $documento['documenti_contabilita_agente'] : $this->session->userdata('session_login')[LOGIN_ENTITY . '_id'];


$xml_articoli_altri_dati_gestionale = [
    'title' => '2.2.1.16 - Altri dati gestionale',
    'id' => 'AltriDatiGestionali',
    'help' => 'Blocco che consente di agli utenti di inserire, con riferimento ad una linea di dettaglio, informazioni utili ai fini amministrativi, gestionali etc.',
    'desc' => 'D. Lgs. N 504/95 art 24 ter Tabella A punto 4 bis; DPR 277/2000 - L\'INFORMAZIONE VIENE MEMORIZZATA SOLO QUANDO ASSUME RILEVANZA AI FINI IVA E AI FINI ACCISE NEL CASO IN CUI IL
                    CAMPO E\' VALORIZZATO CON SPECIFICHE CODIFICHE PRESCRITTE DA NORME TRIBUTARIE E RICHIAMATI DA PROVVEDIMENTI AGENZIA (ES. TIPOLOGIA DOCUMENTO "SCONTRINO" +
                    IDENTIFICATIVO DOCUMENTO "NUMERO SCONTRINO" + DATA DOCUMENTO "DATA SCONTRINO")',
    'type' => 'contenitore',
    'figli' => [
        [
            'type' => 'campo',
            'label' => '2.2.1.16.1 - Tipo Dato',
            'name' => 'TipoDato',
            'desc' => 'TEST POPUP',
            'help' => 'Codice che identifica la tipologia di informazione',
            'pattern' => '(\p{IsBasicLatin}{1,10})',
        ],
        [
            'name' => 'RiferimentoTesto',
            'label' => '2.2.1.16.2 - Riferimento testo',
            'help' => 'Elemento informativo in cui inserire un valore alfanumerico riferito alla tipologia di informazione di cui all\'elemento informativo 2.2.1.16.1',
            'pattern' => '\d{4,4}',
        ],
        [
            'name' => 'RiferimentoNumero',
            'label' => '2.2.1.16.3 - Riferimento numero',
            'help' => 'Elemento informativo in cui inserire un valore numerico riferito alla tipologia di informazione di cui all\'elemento informativo 2.2.1.16.1',
            'pattern' => '[\-]?[0-9]{1,11}\.[0-9]{2,8}',
            // 'figli' => [
            //     [
            //         'type' => 'campo',
            //         'name' =>'Primo figlio',
            //         'pattern' => '\d{4,4}',
            //     ],
            //     [
            //         'name' =>'RiferimentoTesto',
            //         'label' => 'Secondo figlio',
            //         'pattern' => '\d{4,4}',
            //         'figli' => [
            //             [
            //                 'type' => 'campo',
            //                 'name' =>'TipoDato',
            //                 'pattern' => '(\p{IsBasicLatin}{1,10})',
            //                 'figli' => [
            //                     [
            //                         'type' => 'campo',
            //                         'name' =>'TipoDato',
            //                         'pattern' => '(\p{IsBasicLatin}{1,10})',
            //                     ],
            //                     [
            //                         'name' =>'RiferimentoTesto',
            //                         'label' => 'Riferimento Testo',
            //                         'pattern' => '/^[0-9]+$/',
            //                     ],
            //                 ],
            //             ],
            //             [
            //                 'name' =>'RiferimentoTesto',
            //                 'label' => 'Riferimento Testo',
            //                 'pattern' => '/^[0-9]+$/',
            //             ],
            //         ],
            //     ],
            // ],
        ],
        [
            'name' => 'RiferimentoData',
            'label' => '2.2.1.16.4 - Riferimento data',
            'placeholder' => 'YYYY-MM-DD',
            'help' => 'Elemento informativo in cui inserire una data riferita alla tipologia di informazione di cui all\'elemento informativo 2.2.1.16.1',
            'pattern' => '\d{4,4}-\d{2,2}-\d{2,2}',
        ],

    ],
];

if ($documento_id && !$clone && !empty($documento['documenti_contabilita_json_editor_xml'])) {
    $json_editor_xml = $documento['documenti_contabilita_json_editor_xml'];
} else {
    $json_editor_xml = json_encode([]);
}

if ($documento_id && !$clone && !empty($documento['documenti_contabilita_impostazioni_stampa_json'])) {
    $json_stampa = json_decode($documento['documenti_contabilita_impostazioni_stampa_json'], true);
} else {
    $json_stampa = [
        'max_articoli_pagina' => '10',
        'font' => '',
        'font-size' => '14',
        'mostra_foto' => DB_BOOL_FALSE,
        'mostra_totali' => DB_BOOL_TRUE,
        'mostra_scadenze_pagamento' => DB_BOOL_TRUE,
        'mostra_totali_senza_iva' => DB_BOOL_FALSE,
        'mostra_prodotti_senza_importi' => DB_BOOL_FALSE,
    ];
}

?>

<!-- CHECK DOCUMENTO XML IMPORTATO -->
<?php if (!$clone && !empty($documento) && isset($documento['documenti_contabilita_importata_da_xml']) && $documento['documenti_contabilita_importata_da_xml'] == DB_BOOL_TRUE): ?>
<section class="content-header">
    <div class="callout callout-warning">
        <h4>Attenzione!</h4>
        <p>Il documento che stai modificando è un documento importato tramite la funzione di import XML massiva. Non è un documento generato da questo sistema di fatturazione.</p>
    </div>
</section>
<?php endif; ?>

<!-- CHECK DOCUMENTO CONSEGNATO ALLO SDI -->
<?php if (!$clone && !empty($documento) && !empty($documento['documenti_contabilita_stato_invio_sdi']) && in_array($documento['documenti_contabilita_stato_invio_sdi'], [2, 3, 5, 7, 8, 9, 10, 11, 12, 14, 15])): ?>
<section class="content-header">
    <div class="callout callout-danger">
        <h4>Attenzione!</h4>
        <p>Il documento che stai modificando è già stato inviato allo SDI.<br /><br />
            <br />
            Si consiglia di NON apportare alcuna modifica a questo documento.<br />
            Eventuali modifiche potrebbero causare un <u>disallineamento</u> tra le informazioni ricevute dallo SDI e quelle presenti nel gestionale.<br />
            <br />
            Qualora fosse necessaria una modifica su un documento già inviato allo SDI, ti invitiamo a rivolgerti al <u>tuo commercialista</u>.
        </p>
    </div>
</section>
<?php endif; ?>


<?php if ($show_iva_advisor): ?>
<section class="content-header">
    <div class="callout callout-warning">
        <h4>Nuove specifiche in vigore dal 01/01/2021</h4>

        <p>Il documento che stai modificando o duplicando è stato creato prima del 01/01/2021, data in cui sono entrate in vigore le nuove regole SDI per le classi iva. Verificare attentamente che le righe articolo riportino l'indicazione di iva corretta.</p>
    </div>
</section>
<?php endif; ?>

<div class="row">
    <?php if(!empty($ddt_gia_fatturati)): ?>
    <div class="col-sm-6">
        <section class="content-header">
            <div class="callout callout-warning">
                <h4 style="font-weight: 900">ATTENZIONE</h4>
                <p>I seguenti DDT risultano già fatturati:</p>
                <ul>
                    <li><?php echo implode('</li><li>', $ddt_gia_fatturati); ?></li>
                </ul>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <?php if(!empty($ddt_con_sconto)): ?>
    <div class="col-sm-6">
        <section class="content-header">
            <div class="callout callout-warning">
                <h4 style="font-weight: 900">ATTENZIONE</h4>
                <p>I seguenti DDT contengono uno sconto generale:</p>
                <ul>
                    <li><?php echo implode('</li><li>', $ddt_con_sconto); ?></li>
                </ul>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>

<div class="row mb-15" id="botton_back" style="margin-bottom:10px;">
    <div class="col-md-2 col-sm-12">
        <div>
            <?php if ($this->input->get('doc_type') == 'DDT Fornitore' && $this->input->get('lock_type')): ?>
            <a href="<?php echo base_url('main/layout/elenco_spese'); ?>" class="btn btn-success js_elenco_documenti"><i class="fa fa-arrow-left"></i> Elenco acquisti</a>
            <?php else: ?>
            <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-success js_elenco_documenti"><i class="fa fa-arrow-left"></i> Elenco Documenti</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<form class="formAjax" id="new_fattura" method="post" action="<?php echo base_url('contabilita/documenti/create_document'); ?>">
    <?php add_csrf(); ?>
    <?php if ($documento_id && !$clone): ?>
    <input name="documento_id" type="hidden" value="<?php echo $documento_id; ?>" />
    <?php endif; ?>

    <?php if ($spesa_id): ?>
    <input name="spesa_id" type="hidden" value="<?php echo $spesa_id; ?>" />
    <?php endif; ?>

    <?php if (!empty($this->input->get('payment_id'))): ?>
    <input name="payment_id" type="hidden" value="<?php echo $this->input->get('payment_id'); ?>" />
    <?php endif; ?>

    <input type="hidden" name="documenti_contabilita_totale" value="<?php echo ($documento_id && $documento['documenti_contabilita_totale']) ? number_format((float) $documento['documenti_contabilita_totale'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_iva" value="<?php echo ($documento_id && $documento['documenti_contabilita_iva']) ? number_format((float) $documento['documenti_contabilita_iva'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_competenze" value="<?php echo ($documento_id && $documento['documenti_contabilita_competenze']) ? number_format((float) $documento['documenti_contabilita_competenze'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_rivalsa_inps_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_rivalsa_inps_valore']) ? number_format((float) $documento['documenti_contabilita_rivalsa_inps_valore'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_competenze_lordo_rivalsa" value="<?php echo ($documento_id && $documento['documenti_contabilita_competenze_lordo_rivalsa']) ? number_format((float) $documento['documenti_contabilita_competenze_lordo_rivalsa'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_cassa_professionisti_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_cassa_professionisti_valore']) ? number_format((float) $documento['documenti_contabilita_cassa_professionisti_valore'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile']) ? number_format((float) $documento['documenti_contabilita_imponibile'], 3, ',', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile_scontato" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile_scontato']) ? number_format((float) $documento['documenti_contabilita_imponibile_scontato'], 3, ',', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_ritenuta_acconto_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_ritenuta_acconto_valore']) ? number_format((float) $documento['documenti_contabilita_ritenuta_acconto_valore'], 2, '.', '') : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_ritenuta_acconto_imponibile_valore" value="<?php echo ($documento_id && $documento['documenti_contabilita_ritenuta_acconto_imponibile_valore']) ? number_format((float) $documento['documenti_contabilita_ritenuta_acconto_imponibile_valore'], 2, '.', '') : ''; ?>" />

    <input type="hidden" name="documenti_contabilita_iva_json" value="<?php echo ($documento_id && $documento['documenti_contabilita_iva_json']) ? $documento['documenti_contabilita_iva_json'] : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_imponibile_iva_json" value="<?php echo ($documento_id && $documento['documenti_contabilita_imponibile_iva_json']) ? $documento['documenti_contabilita_imponibile_iva_json'] : ''; ?>" />
    <input type="hidden" name="documenti_contabilita_extra_param" value="<?php echo ($documento_id && $documento['documenti_contabilita_extra_param']) ? $documento['documenti_contabilita_extra_param'] : (!empty($this->input->get('extra_param')) ? $this->input->get('extra_param') : ''); ?>" />
    <input type="hidden" name="documenti_contabilita_luogo_destinazione_id" value="<?php echo ($documento_id && $documento['documenti_contabilita_luogo_destinazione_id']) ? $documento['documenti_contabilita_luogo_destinazione_id'] : ''; ?>" />
    <input type='hidden' name='documenti_contabilita_utente_id' value="<?php echo ($documento_id && $documento['documenti_contabilita_utente_id']) ? $documento['documenti_contabilita_utente_id'] : $this->session->userdata('session_login')[LOGIN_ENTITY . '_id']; ?>" />

    <?php
    if ($this->input->get('commessa')):
        $dettaglio_commessa = $this->apilib->searchFirst('projects', ['projects_id' => $this->input->get('commessa')]);
        ?>
    <div class="row mb-15">
        <div class="col-md-12 col-sm-12" style="margin-bottom:20px;">

            <div class="alert alert-info" role="alert">
                Stai associando questo documento alla commessa #<?php echo $this->input->get('commessa'); ?> (<?php echo $dettaglio_commessa['projects_name']; ?> - <?php echo $dettaglio_commessa['customers_name']; ?>)
            </div>

        </div>
    </div>
    <?php
    endif;
    ?>
    <input type="hidden" name="documenti_contabilita_commessa" value="<?php echo $this->input->get('commessa'); ?>" />

    <div class="row mb-15">
        <div class="col-md-12 col-sm-12" style="margin-bottom:20px;">
            <div class="btn-group">
                <?php foreach ($documenti_tipo as $tipo): ?>
                <?php 
                    //Lascio solo il tipo DDT fornitore se arrivo da documenti di acquisto 
                     if ($this->input->get('from') == 'spese' && $tipo['documenti_contabilita_tipo_id'] != 10) {
                            continue;
                     } elseif (!$this->input->get('from') == 'spese' && $tipo['documenti_contabilita_tipo_id'] == 10) {//Arrivo da vendite, quindi tolgo il DDT Fornitore
                            continue;
                     }
                    ?>
                <button style="font-size:11px;" type="button" class="btn <?php if (($documento_id && ($documento_id && $documento['documenti_contabilita_tipo'] == $tipo['documenti_contabilita_tipo_id'])) || $tipo['documenti_contabilita_tipo_value'] == $this->input->get('doc_type')): ?>btn-primary<?php else: ?>btn-default<?php endif; ?> js_btn_tipo" data-tipo="<?php echo $tipo['documenti_contabilita_tipo_id']; ?>"><?php echo $tipo['documenti_contabilita_tipo_value']; ?></button>
                <?php endforeach; ?>

                <input type="hidden" name="documenti_contabilita_tipo" class="js_documenti_contabilita_tipo" value="<?php if (($documento_id || $spesa_id) && $documento['documenti_contabilita_tipo']): ?><?php echo $documento['documenti_contabilita_tipo']; ?><?php else: ?><?php echo 1; ?><?php endif; ?>" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="row bg-gray mb-15 js_cliente_container">
                <div class="row">
                    <div class="col-md-12">
                        <h4>
                            Dati del destinatario

                            <?php if ($_dest_id): ?>
                            <a href="<?php echo base_url('main/layout/customer-detail/' . $_dest_id); ?>" target="_blank" class="btn btn-primary btn-xs pull-right" id="btn_dettaglio_customer">Vai all'anagrafica</a>
                            <?php else: ?>
                            <a style="display:none;" href="" target="_blank" class="btn btn-primary btn-xs pull-right" id="btn_dettaglio_customer">Vai all'anagrafica</a>
                            <?php endif; ?>
                        </h4>
                    </div>
                </div>

                <input type="hidden" name="dest_entity_name" value="<?php if ($documento_id && $documento['documenti_contabilita_supplier_id']): ?>fornitori<?php else: ?><?php echo $entita_clienti; ?><?php endif; ?>" />
                <input id="js_dest_id" type="hidden" name="dest_id" value="<?php echo $_dest_id; ?>" />

                <div class="row">
                    <div class="form-group">
                        <?php foreach ($tipo_destinatario as $tipo_dest): ?>
                        <div class="col-sm-4">
                            <label>
                                <input type="radio" name="documenti_contabilita_tipo_destinatario" class="js_tipo_destinatario" <?php if (!empty($documento['documenti_contabilita_tipo_destinatario']) && $documento['documenti_contabilita_tipo_destinatario'] == $tipo_dest['documenti_contabilita_tipo_destinatario_id']): ?> checked="checked" <?php endif; ?> value="<?php echo $tipo_dest['documenti_contabilita_tipo_destinatario_id']; ?>"> <?php echo $tipo_dest['documenti_contabilita_tipo_destinatario_value']; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class=" form-group">
                            <input type="text" name="codice" class="form-control js_dest_codice search_cliente" placeholder="Codice" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo @$documento['documenti_contabilita_destinatario']['codice']; ?><?php endif; ?>" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <input type="text" name="ragione_sociale" class="form-control js_dest_ragione_sociale search_cliente" placeholder="Ragione sociale" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['ragione_sociale']; ?><?php endif; ?>" autocomplete="off" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="indirizzo" class="form-control js_dest_indirizzo" placeholder="Indirizzo" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['indirizzo']; ?><?php endif; ?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="citta" class="form-control js_dest_citta" placeholder="Città" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['citta']; ?><?php endif; ?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <?php if (!empty($nazioni)): ?>
                            <select name="nazione" id="nazione" class="form-control select2_standard js_dest_nazione">
                                <?php foreach ($nazioni as $nazione): ?>
                                <?php
                                        $selected = '';
                                        if (
                                            (empty($documento['documenti_contabilita_destinatario']['nazione']) && $nazione['countries_iso'] == 'IT')
                                            ||
                                            (!empty($documento['documenti_contabilita_destinatario']['nazione']) && $documento['documenti_contabilita_destinatario']['nazione'] === $nazione['countries_iso'])
                                        ) {
                                            $selected = 'selected';
                                        }
                                        ?>

                                <option value="<?php echo $nazione['countries_iso']; ?>" <?php echo $selected ?>><?php echo $nazione['countries_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <input type="text" name="nazione" maxlength="2" minlength="2" class="form-control js_dest_nazione" placeholder="Nazione" value="<?php if (!empty($documento['documenti_contabilita_destinatario']) && (strlen($documento['documenti_contabilita_destinatario']['nazione']) < 3)): ?><?php echo $documento['documenti_contabilita_destinatario']['nazione']; ?><?php else: ?><?php echo 'IT'; ?><?php endif; ?>" />
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="text" name="cap" class="form-control js_dest_cap" placeholder="CAP" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['cap']; ?><?php endif; ?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div clasS="form-group">
                            <input type="text" name="provincia" class="form-control js_dest_provincia" placeholder="Provincia" maxlength="2" minlength="2" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['provincia']; ?><?php endif; ?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="partita_iva" class="form-control js_dest_partita_iva" placeholder="P.IVA" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['partita_iva']; ?><?php endif; ?>" />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="codice_fiscale" class="form-control js_dest_codice_fiscale" placeholder="Codice fiscale" value="<?php if (!empty($documento['documenti_contabilita_destinatario'])): ?><?php echo $documento['documenti_contabilita_destinatario']['codice_fiscale']; ?><?php endif; ?>" />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="codice_sdi" class="form-control js_dest_codice_sdi" placeholder="Codice destinatario (per privati 0000000)" value="<?php if (!empty($documento['documenti_contabilita_destinatario']['codice_sdi'])): ?><?php echo $documento['documenti_contabilita_destinatario']['codice_sdi']; ?><?php endif; ?>" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" name="pec" class="form-control js_dest_pec" placeholder="Indirizzo pec" value="<?php if (!empty($documento['documenti_contabilita_destinatario']['pec'])): ?><?php echo $documento['documenti_contabilita_destinatario']['pec']; ?><?php endif; ?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label id="js_label_rubrica">Salva in rubrica</label> <input type="checkbox" class="minimal" name="save_dest" value="true" />

                        </div>

                    </div>
                    <div class="col-md-6">

                        <div id="js_listino_applicato"></div>


                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="row mb-15" style="padding-bottom:10px;">
                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-12">
                        <h4>Dati <span class="js_doc_type">documento</span></h4>
                    </div>
                </div>

                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Azienda: </label>
                            <select name="documenti_contabilita_azienda" class="form-control documenti_contabilita_azienda">
                                <?php foreach ($settings as $setting): ?>
                                <?php
                                    $selected = '';
                                    
                                    if (!empty($documento_id)) {
                                        if (!empty($documento['documenti_contabilita_azienda']) && $documento['documenti_contabilita_azienda'] == $setting['documenti_contabilita_settings_id']) {
                                            $selected = 'selected="selected"';
                                        }
                                    } else {
                                        if ($azienda_in_sessione == $setting['documenti_contabilita_settings_id'] || (!empty($azienda_get) && $azienda_get == $setting['documenti_contabilita_settings_id'] )) {
                                            $selected = 'selected="selected"';
                                        }
                                    }
                                    ?>

                                <option value="<?php echo $setting['documenti_contabilita_settings_id']; ?>" <?php echo $selected; ?>><?php echo $setting['documenti_contabilita_settings_company_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Numero: </label> <input type="text" name="documenti_contabilita_numero" class="form-control documenti_contabilita_numero" placeholder="Numero documento" value="<?php if (!empty($documento['documenti_contabilita_numero']) && !$clone): ?><?php echo $documento['documenti_contabilita_numero']; ?><?php endif; ?>" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Data emissione: </label>
                            <?php //debug($documento);
                            ?>
                            <div class="input-group js_form_datepicker date ">
                                <input type="text" name="documenti_contabilita_data_emissione" class="form-control" placeholder="Data emissione" value="<?php if (!empty($documento['documenti_contabilita_data_emissione']) && !$clone): ?><?php echo date('d/m/Y', strtotime($documento['documenti_contabilita_data_emissione'])); ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif; ?>" data-name="documenti_contabilita_data_emissione" /> <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <?php //debug($documento['documenti_contabilita_valuta']);
                            ?>
                            <label style="min-width:80px">Valuta: </label> <select name="documenti_contabilita_valuta" class="select2 form-control documenti_contabilita_valuta">
                                <?php foreach ($valute as $key => $valuta): ?>
                                <option data-id="<?php echo $valuta['valute_id']; ?>" value="<?php echo $valuta['valute_codice']; ?>" <?php if (($valuta['valute_id'] == $impostazioni['documenti_contabilita_settings_valuta_base'] && empty($documento_id)) || (!empty($documento['documenti_contabilita_valuta']) && strtoupper($documento['documenti_contabilita_valuta']) == strtoupper($valuta['valute_codice']))): ?> selected="selected" <?php endif; ?>><?php echo $valuta['valute_nome']; ?> - <?php echo $valuta['valute_simbolo']; ?></option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-3">
                        <label style="min-width:80px">Tasso di cambio (<?php echo $impostazioni['valute_simbolo']; ?>): </label>
                        <input type="text" class="form-control documenti_contabilita_tasso_di_cambio" name="documenti_contabilita_tasso_di_cambio" value="<?php if (empty($documento_id) || empty($documento['documenti_contabilita_tasso_di_cambio'])): ?>1<?php else: ?><?php echo $documento['documenti_contabilita_tasso_di_cambio']; ?><?php endif; ?>">
                    </div>
                </div>

                <div class="row" style="background-color:#b7d7ea;">
                    <?php if ($serie_documento): ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Serie: </label><br />
                            <div class="btn-group">
                                <?php foreach ($serie_documento as $serie): ?>
                                <?php
                                        $selected = '';
                                        
                                        if (!empty($value_id) && !$clone) {
                                            if (!empty($documento['documenti_contabilita_serie']) && $documento['documenti_contabilita_serie'] == $serie['documenti_contabilita_serie_value']) {
                                                $selected = 'button_selected';
                                            }
                                        } else {
                                            if (
                                                ($serie_get && $serie_get == $serie['documenti_contabilita_serie_value'])
                                                || (!$serie_get && !empty($documento['documenti_contabilita_serie']) && $documento['documenti_contabilita_serie'] == $serie['documenti_contabilita_serie_value'])
                                                || (!$serie_get && empty($documento['documenti_contabilita_serie']) && $impostazioni['documenti_contabilita_settings_serie_default'] == $serie['documenti_contabilita_serie_id'])
                                            ) {
                                                $selected = 'button_selected';
                                            }
                                        }

                                        ?>
                                <button type="button" class="btn js_btn_serie btn-default <?php echo $selected; ?>" data-centro_costo_ricavo="<?php echo $serie['documenti_contabilita_serie_centro_di_ricavo']; ?>" data-serie="<?php echo $serie['documenti_contabilita_serie_value']; ?>">/<?php echo $serie['documenti_contabilita_serie_value']; ?></button>
                                <?php endforeach; ?>

                                <?php
                                    $serie_selezionata = '';
                                    if (!empty($documento['documenti_contabilita_serie'])) {
                                        $serie_selezionata = $documento['documenti_contabilita_serie'];
                                    } else {
                                        if (empty($value_id) && !empty($impostazioni['documenti_contabilita_serie_value'])) {
                                            $serie_selezionata = $impostazioni['documenti_contabilita_serie_value'];
                                        }
                                    }
                                    ?>

                                <input type="hidden" class="js_documenti_contabilita_serie" name="documenti_contabilita_serie" value="<?php echo $serie_selezionata; ?>" />
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>


                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Centro di ricavo: </label>
                            <select name="documenti_contabilita_centro_di_ricavo" class="select2 form-control">
                                <option value="">---</option>
                                <?php foreach ($centri_di_costo as $centro): ?>

                                <?php
                                    $centro_costo_ricavo_selected = '';
                                    
                                    if ( !empty($documento['documenti_contabilita_centro_di_ricavo']) && $documento['documenti_contabilita_centro_di_ricavo'] == $centro['centri_di_costo_ricavo_id'] ) {
                                        $centro_costo_ricavo_selected = 'selected="selected"';
                                    } else {
                                        if ( !empty($impostazioni['documenti_contabilita_settings_centro_costo_ricavo_default']) && $impostazioni['documenti_contabilita_settings_centro_costo_ricavo_default'] == $centro['centri_di_costo_ricavo_id'] ) {
                                            $centro_costo_ricavo_selected = 'selected="selected"';
                                        }
                                    }
                                ?>

                                <option value="<?php echo $centro['centri_di_costo_ricavo_id']; ?>" <?php echo $centro_costo_ricavo_selected; ?>><?php echo $centro['centri_di_costo_ricavo_nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="min-width:80px">Tipologia di fatturazione: </label>
                            <select name="documenti_contabilita_tipologia_fatturazione" class="select2 form-control js_tipologia_fatturazione">
                                <?php if (!empty($documento_id) && empty($documento['documenti_contabilita_tipologia_fatturazione'])): ?>
                                <option value=""></option> <?php endif; ?>
                                <?php foreach ($tipologie_fatturazione as $tipologia): ?>
                                <option data-tipologia_codice="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_codice']; ?>" data-tipologia_genitore="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_tipo_genitore']; ?>" data-tipologia_descrizione="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_descrizione']; ?>" value="<?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_id']; ?>" <?php if (($tipologia['documenti_contabilita_tipologie_fatturazione_id'] == '1' && empty($documento_id)) || (!empty($documento['documenti_contabilita_tipologia_fatturazione']) && $documento['documenti_contabilita_tipologia_fatturazione'] == $tipologia['documenti_contabilita_tipologie_fatturazione_id'])): ?> selected="selected" <?php endif; ?>><?php echo $tipologia['documenti_contabilita_tipologie_fatturazione_codice'], ' ', $tipologia['documenti_contabilita_tipologie_fatturazione_descrizione']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (!empty($agenti_vendita)): ?>
                    <div class='col-md-3'>
                        <div class='form-group'>
                            <label style='min-width:80px'>Agente vendita: </label>
                            <select name='documenti_contabilita_agente' class='select2_standard form-control js_agente_vendita'>
                                <?php foreach ($agenti_vendita as $agente): ?>
                                <option value="<?php echo $agente['users_id']; ?>" <?php echo ($agente['users_id'] == $agente_vendita_corrente) ? 'selected="selected"' : '' ?>><?php echo $agente['users_first_name'] . ' ' . ($agente['users_last_name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3" style="display:none;">
                        <div class="form-group">
                            <label style="min-width:80px;">Rif. Documento: </label>
                            <input type="text" class="form-control" name="documenti_contabilita_rif_documento_id" value="<?php echo $rifDocId; ?>">
                            <input type="text" class="form-control" name="documenti_contabilita_rif_documenti" value="<?php echo implode(',', $rif_documenti_ids); ?>">
                        </div>
                    </div>
                </div>
                <?php if ($this->module->moduleExists('magazzino')) : ?>
                <div class="row" style="background-color:#b7d7ea;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Magazzino: </label>

                            <select name='documenti_contabilita_magazzino' class='select2_standard form-control js_magazzino'>
                                <option value=''>---</option>
                                <?php foreach ($this->apilib->search('magazzini') as $magazzino): ?>
                                <option data-json_data="<?php echo base64_encode(json_encode($magazzino)); ?>" value="<?php echo $magazzino['magazzini_id']; ?>" <?php if ((!empty($documento['documenti_contabilita_magazzino']) && $documento['documenti_contabilita_magazzino'] == $magazzino['magazzini_id'])): ?> selected="selected" <?php endif; ?>>
                                    <?php echo ucfirst($magazzino['magazzini_titolo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row mb-15" style="background-color:#b7d7ea;">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="min-width:80px;">Oggetto del documento <small>(max 200 caratteri)</small></label>
                            <input type="text" maxlength="200" class="form-control" placeholder="In caso di fattura elettronica questo è il campo 2.1.1.11 <Causale>" name="documenti_contabilita_oggetto" value="<?php if (!empty($documento['documenti_contabilita_oggetto'])): ?><?php echo $documento['documenti_contabilita_oggetto']; ?><?php endif; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <span>
                                <label><strong>Rif. uso interno</strong></label><br />
                                <input type="text" class="form-control" placeholder="es.: ABC-12345" name="documenti_contabilita_rif_uso_interno" value="<?php if (!empty($documento['documenti_contabilita_rif_uso_interno'])): ?><?php echo $documento['documenti_contabilita_rif_uso_interno']; ?><?php endif; ?>">
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rif. data: </label>
                            <?php //debug($documento);
                            ?>
                            <div class="input-group js_form_datepicker date ">
                                <input type="text" name="documenti_contabilita_rif_data" class="form-control" placeholder="Rif. data" value="<?php if (!empty($documento['documenti_contabilita_rif_data']) && !$clone): ?><?php echo date('d/m/Y', strtotime($documento['documenti_contabilita_data_emissione'])); ?><?php endif; ?>" data-name="documenti_contabilita_rif_data" /> <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" style="display:none">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <span>
                                <label><strong>Formato elettronico</strong></label><br />
                                <input type="checkbox" class="minimal" name="documenti_contabilita_formato_elettronico" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_formato_elettronico']) && $documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                            </span>
                        </div>
                    </div>
                </div>


                <div class="row mb-15" style="background-color:#6bbf81 ">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Informazioni pagamento</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <!-- <select name="documenti_contabilita_metodo_pagamento" class="select2 form-control">
                                    <option value="">Metodo di pagamento</option>

                                    <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                        <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']; ?>" <?php if (!empty($documento['documenti_contabilita_metodo_pagamento']) && $documento['documenti_contabilita_metodo_pagamento'] == $metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']): ?> selected="selected" <?php endif; ?>>
                                            <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select> -->

                                <select name="documenti_contabilita_template_pagamento" class="select2 form-control">
                                    <!--<option value="">Metodo di pagamento</option>-->
                                    <?php foreach ($template_scadenze as $template_pagamento): ?>
                                    <!--<option value="<?php echo $template_pagamento['documenti_contabilita_template_pagamenti_id']; ?>" <?php if (!empty($documento['documenti_contabilita_template_pagamento']) && $documento['documenti_contabilita_template_pagamento'] == $template_pagamento['documenti_contabilita_template_pagamenti_id']): ?> selected="selected" <?php endif; ?>> -->
                                    <option value="<?php echo $template_pagamento['documenti_contabilita_template_pagamenti_id']; ?>" <?php if ((!empty($documento['documenti_contabilita_template_pagamento']) && $documento['documenti_contabilita_template_pagamento'] == $template_pagamento['documenti_contabilita_template_pagamenti_id']) || ($template_pagamento['documenti_contabilita_template_pagamenti_default'] == DB_BOOL_TRUE && empty($documento_id) && !$clone)): ?> selected="selected" <?php endif; ?>>
                                        <?php echo ucfirst($template_pagamento['documenti_contabilita_template_pagamenti_codice']); ?> - <?php echo ucfirst($template_pagamento['documenti_contabilita_template_pagamenti_nome']); ?>
                                    </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a target="_blank" href="<?php echo base_url('get_ajax/layout_modal/gestione_template_pagamenti?_size=extra'); ?>" class="btn btn-warning js_open_modal">
                                Gestisci <i class="fa fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <select name="documenti_contabilita_conto_corrente" class="select2 form-control">
                                    <option value="">Scegli conto corrente....</option>

                                    <?php foreach ($conti_correnti as $key => $conto): ?>
                                    <option value="<?php echo $conto['conti_correnti_id']; ?>" <?php if ((empty($documento_id) && $conto['conti_correnti_default'] == DB_BOOL_TRUE) || (!empty($documento['documenti_contabilita_conto_corrente']) && $documento['documenti_contabilita_conto_corrente'] == $conto['conti_correnti_id'])): ?> selected="selected" <?php endif; ?>><?php echo $conto['conti_correnti_nome_istituto']; ?></option>

                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12" style="margin-bottom: 20px;">
                            <textarea name='documenti_contabilita_note' rows='3' class='form-control' placeholder='Note pagamento [opzionali]'><?php if ($documento_id): ?><?php echo $documento['documenti_contabilita_note_interne']; ?><?php endif; ?></textarea>
                        </div>
                    </div>

                </div>

                <div class="row js_rivalsa_container" style="background-color:#edb92b;">
                    <div class="col-sm-12">
                        <button type="button" class="accordion" style="background-color: #edb92b; padding: 0px">
                            <h4>Impostazioni di stampa <i class="fas fa-plus container-plus-button" style="font-size: 15px;"></i></h4>
                        </button>

                        <div class="panel_acc" style="background-color: #edb92b;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Template Documento: </label>
                                            <select name="documenti_contabilita_template_pdf" class="select2 form-control js_template_pdf">
                                                <?php foreach ($templates as $template): ?>
                                                <?php
                                                    $categoria_template = '';
                                                    if (!empty($template['documenti_contabilita_template_pdf_tipo'])) {
                                                        $categoria_template = $this->apilib->searchFirst('documenti_contabilita_tipo', ['documenti_contabilita_tipo_id' => $template['documenti_contabilita_template_pdf_tipo']]);
                                                        $categoria_template = $categoria_template['documenti_contabilita_tipo_value'] . ' - ';
                                                    }
                                                    
                                                    $tpl_selezionato = '';
                                                    if ((!empty($documento_id) || !empty($spesa_id)) && (!empty($documento['documenti_contabilita_template_pdf']) && $documento['documenti_contabilita_template_pdf'] == $template['documenti_contabilita_template_pdf_id'])) {
                                                        $tpl_selezionato = 'selected="selected"';
                                                    } else {
                                                        if (!empty($this->input->get('template_contabilita')) && $this->input->get('template_contabilita') == $template['documenti_contabilita_template_pdf_id']) {
                                                            $tpl_selezionato = 'selected="selected"';
                                                        }
                                                    }
                                                    ?>
                                                <option data-tipo="<?php echo $template['documenti_contabilita_template_pdf_tipo']; ?>" value='<?php echo $template['documenti_contabilita_template_pdf_id']; ?>' <?php echo $tpl_selezionato; ?>><?php echo $categoria_template . $template['documenti_contabilita_template_pdf_nome']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Max n° articoli per pagina: </label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="json_stampa[max_articoli_pagina]" value="<?php if (!empty($json_stampa['max_articoli_pagina'])): ?><?php echo $json_stampa['max_articoli_pagina']; ?><?php else: ?>10<?php endif; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Font: </label> <select name="documenti_contabilita_font" class="select2 form-control js_template_pdf">
                                                <?php foreach ($fonts as $font): ?>
                                                <option data-tipo="<?php echo $font['documenti_contabilita_font_value']; ?>" value='<?php echo $font['documenti_contabilita_font_id']; ?>' <?php if ((!empty($documento_id) || !empty($spesa_id)) && (!empty($documento['documenti_contabilita_font']) && $documento['documenti_contabilita_font'] == $font['documenti_contabilita_font_id'])): ?> selected="selected" <?php endif; ?>><?php echo $font['documenti_contabilita_font_value']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Font Size:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="json_stampa[font-size]" value="<?php if (!empty($json_stampa['font-size'])): ?><?php echo $json_stampa['font-size']; ?><?php else: ?>14<?php endif; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Lingua template:</label>
                                            <?php $lingue = $this->apilib->search('languages'); ?>
                                            <select name="documenti_contabilita_lingua" class="select2 form-control js_template_pdf">
                                                <?php foreach ($lingue as $lingua) : ?>
                                                <option value='<?php echo $lingua['languages_id']; ?>' <?php echo ((($lingua['languages_id'] == 2) && empty($documento_id) && empty($spesa_id) && (empty($documento['documenti_contabilita_lingua']) || $documento['documenti_contabilita_lingua'] != $lingua['languages_id'])) ? "selected='selected'" : (((!empty($documento_id) || !empty($spesa_id)) && (!empty($documento['documenti_contabilita_lingua']) && $documento['documenti_contabilita_lingua'] == $lingua['languages_id'])) ? "selected='selected'" : "")); ?>><?php echo $lingua['languages_name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4 style="font-size: 16px;">Mostra in stampa</h4>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Foto prodotti:</label>
                                            <input type="checkbox" class="minimal" name="json_stampa[mostra_foto]" class="rcr-adjust" value="<?php echo $json_stampa['mostra_foto'] ?? DB_BOOL_FALSE; ?>" <?php if (!empty($json_stampa['mostra_foto']) && $json_stampa['mostra_foto'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Totali documento: </label>
                                            <input type="checkbox" class="minimal" name="json_stampa[mostra_totali]" class="rcr-adjust" value="<?php echo $json_stampa['mostra_totali'] ?? DB_BOOL_FALSE; ?>" <?php if (!empty($json_stampa['mostra_totali']) && $json_stampa['mostra_totali'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Totali senza IVA: </label>
                                            <input type="checkbox" class="minimal" name="json_stampa[mostra_totali_senza_iva]" class="rcr-adjust" value="<?php echo $json_stampa['mostra_totali_senza_iva'] ?? DB_BOOL_FALSE; ?>" <?php if (!empty($json_stampa['mostra_totali_senza_iva']) && $json_stampa['mostra_totali_senza_iva'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Scadenze pagamento: </label>
                                            <input type="checkbox" class="minimal" name="json_stampa[mostra_scadenze_pagamento]" class="rcr-adjust" value="<?php echo $json_stampa['mostra_scadenze_pagamento'] ?? DB_BOOL_FALSE; ?>" <?php if (!empty($json_stampa['mostra_scadenze_pagamento']) && $json_stampa['mostra_scadenze_pagamento'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label style="min-width:80px">Prodotti senza importi: </label>
                                            <input type="checkbox" class="minimal" name="json_stampa[mostra_prodotti_senza_importi]" class="rcr-adjust" value="<?php echo $json_stampa['mostra_prodotti_senza_importi'] ?? DB_BOOL_FALSE; ?>" <?php if (!empty($json_stampa['mostra_prodotti_senza_importi']) && $json_stampa['mostra_prodotti_senza_importi'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row js_rivalsa_container real_rivalsa" style="background-color:#b7d7ea;">
                    <div class="col-sm-12">
                        <button type="button" class="accordion" style="padding: 0px">
                            <h4>Rivalsa e altri dettagli <i class="fas fa-plus container-plus-button" style="font-size: 15px;"></i></h4>
                        </button>

                        <div class="panel_acc">
                            <div class="row rcr_label">
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>Rivalsa INPS: </label>
                                        <div class="input-group">
                                            <?php
                                        $rivalsa_inps_perc = 0;
                                        if (!empty($documento['documenti_contabilita_rivalsa_inps_perc'])) {
                                            $rivalsa_inps_perc = number_format((float) $documento['documenti_contabilita_rivalsa_inps_perc'], 2, '.', '');
                                        } else {
                                            if (!empty($this->input->get('rivalsa_inps_perc'))) {
                                                $rivalsa_inps_perc = number_format((float) $this->input->get('rivalsa_inps_perc'), 2, '.', '');
                                            } else {
                                                if (!empty($impostazioni['documenti_contabilita_settings_rivalsa_inps_perc'])) {
                                                    $rivalsa_inps_perc = number_format((float)$impostazioni['documenti_contabilita_settings_rivalsa_inps_perc'], 2, '.', '');
                                                }
                                            }
                                        }
                                        ?>
                                            <input type="text" class="form-control" name="documenti_contabilita_rivalsa_inps_perc" value="<?php echo $rivalsa_inps_perc ?>" />
                                            <span class="input-group-addon" id="basic-addon2">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>Ritenuta d'acconto: </label>
                                        <div class="input-group">
                                            <?php
                                        $ritenuta_acconto_perc = 0;
                                        if (!empty($documento['documenti_contabilita_ritenuta_acconto_perc'])) {
                                            $ritenuta_acconto_perc = number_format((float) $documento['documenti_contabilita_ritenuta_acconto_perc'], 2, '.', '');
                                        } else {
                                            if (!empty($this->input->get('ritenuta_acconto_perc'))) {
                                                $ritenuta_acconto_perc = number_format((float) $this->input->get('ritenuta_acconto_perc'), 2, '.', '');
                                            } else {
                                                if (!empty($impostazioni['documenti_contabilita_settings_ritenuta_acconto_perc'])) {
                                                    $ritenuta_acconto_perc = number_format((float)$impostazioni['documenti_contabilita_settings_ritenuta_acconto_perc'], 2, '.', '');
                                                }
                                            }
                                        }
                                        ?>
                                            <input type="text" class="form-control" name="documenti_contabilita_ritenuta_acconto_perc" value="<?php echo $ritenuta_acconto_perc ?>" />
                                            <span class="input-group-addon" id="basic-addon2">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>% sull'imponibile: </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="documenti_contabilita_ritenuta_acconto_perc_imponibile" value="<?php if (!empty($documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'])): ?><?php echo number_format((float) $documento['documenti_contabilita_ritenuta_acconto_perc_imponibile'], 2, '.', ''); ?><?php else: ?>100<?php endif; ?>" />
                                            <span class="input-group-addon" id="basic-addon2">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row rcr_label">
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>Cassa professionisti: </label>
                                        <div class="input-group">
                                            <?php
                                            $cassa_professionisti_perc = 0;
                                            if (!empty($documento['documenti_contabilita_cassa_professionisti_perc'])) {
                                                $cassa_professionisti_perc = number_format((float) $documento['documenti_contabilita_cassa_professionisti_perc'], 2, '.', '');
                                            } else {
                                                if (!empty($this->input->get('cassa_professionisti_perc'))) {
                                                    $cassa_professionisti_perc = number_format((float) $this->input->get('cassa_professionisti_perc'), 2, '.', '');
                                                } else {
                                                    if (!empty($impostazioni['documenti_contabilita_settings_cassa_professionisti_perc'])) {
                                                        $cassa_professionisti_perc = number_format((float)$impostazioni['documenti_contabilita_settings_cassa_professionisti_perc'], 2, '.', '');
                                                    }
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" name="documenti_contabilita_cassa_professionisti_perc" value="<?php echo $cassa_professionisti_perc ?>" />
                                            <span class="input-group-addon" id="basic-addon2">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8 col-sm-6">
                                    <div class="form-group">
                                        <label>Tipo: </label>
                                        <select name="documenti_contabilita_cassa_professionisti_tipo" class="form-control select2_standard" style="width: 100%;">
                                            <option value="">---</option>
                                            <?php foreach($tipi_cassa_pro as $tipo): ?>
                                            <?php
                                        $selected = '';
                                        
                                        if (!empty($documento['documenti_contabilita_cassa_professionisti_tipo']) && $documento['documenti_contabilita_cassa_professionisti_tipo'] == $tipo['documenti_contabilita_cassa_professionisti_tipo_id']) {
                                            $selected = 'selected="selected"';
                                        } else {
                                            if (!empty($this->input->get('cassa_professionisti_tipo')) && $this->input->get('cassa_professionisti_tipo') == $tipo['documenti_contabilita_cassa_professionisti_tipo_id']) {
                                                $selected = 'selected="selected"';
                                            } else {
                                                if (!empty($impostazioni['documenti_contabilita_settings_tipo_cassa_professionisti']) && $impostazioni['documenti_contabilita_settings_tipo_cassa_professionisti'] == $tipo['documenti_contabilita_cassa_professionisti_tipo_id']) {
                                                    $selected = 'selected="selected"';
                                                }
                                            }
                                        }
                                        ?>
                                            <option value="<?php echo $tipo['documenti_contabilita_cassa_professionisti_tipo_id'] ?>" <?php echo $selected; ?>><?php echo $tipo['documenti_contabilita_cassa_professionisti_tipo_codice'] . ' - ' . $tipo['documenti_contabilita_cassa_professionisti_tipo_value'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row rcr_label">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>2.1.1.6.2 - Importo bollo: </label>
                                        <div class="input-group">
                                            <?php
                                        $importo_bollo = 0;
                                        if (!empty($documento['documenti_contabilita_importo_bollo'])) {
                                            $importo_bollo = number_format((float) $documento['documenti_contabilita_importo_bollo'], 2, '.', '');
                                        } else {
                                            if (!empty($this->input->get('importo_bollo'))) {
                                                $importo_bollo = number_format((float) $this->input->get('importo_bollo'), 2, '.', '');
                                            } else {
                                                if (!empty($impostazioni['documenti_contabilita_settings_importo_bollo'])) {
                                                    $importo_bollo = number_format((float)$impostazioni['documenti_contabilita_settings_importo_bollo'], 2, '.', '');
                                                }
                                            }
                                        }
                                        ?>
                                            <input type="text" class="form-control" name="documenti_contabilita_importo_bollo" value="<?php echo $importo_bollo; ?>" />
                                            <span class="input-group-addon" id="basic-addon2">€</span>
                                        </div>
                                        <!--<span>
                                        <label><strong>Applica Bollo</strong>
                                            <input type="checkbox" class="minimal" name="documenti_contabilita_applica_bollo" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (empty($documento_id) || !empty($documento['documenti_contabilita_applica_bollo']) && $documento['documenti_contabilita_applica_bollo'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </label>
                                        <label><strong>Bollo virtuale</strong>
                                            <input type="checkbox" class="minimal" name="documenti_contabilita_bollo_virtuale" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (empty($documento_id) || !empty($documento['documenti_contabilita_bollo_virtuale']) && $documento['documenti_contabilita_bollo_virtuale'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?> />
                                        </label>
                                    </span>-->
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <div class="causale-container">
                                            <label>Causale Pag. Rit.: </label>
                                            <button type="button" class="btn btn-xs btn-info btn-causale" data-toggle="modal" data-target="#modal-default">
                                                Legenda
                                            </button>
                                        </div>
                                        <select name="documenti_contabilita_causale_pagamento_ritenuta" class="select2 form-control">
                                            <option value="" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == ''): ?>selected="selected" <?php endif; ?>></option>
                                            <option value="A" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'A'): ?>selected="selected" <?php endif; ?>>A</option>
                                            <option value="B" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'B'): ?>selected="selected" <?php endif; ?>>B</option>
                                            <option value="C" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'C'): ?>selected="selected" <?php endif; ?>>C</option>
                                            <option value="D" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'D'): ?>selected="selected" <?php endif; ?>>D</option>
                                            <option value="E" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'E'): ?>selected="selected" <?php endif; ?>>E</option>
                                            <option value="F" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'F'): ?>selected="selected" <?php endif; ?>>F</option>
                                            <option value="G" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'G'): ?>selected="selected" <?php endif; ?>>G</option>
                                            <option value="H" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'H'): ?>selected="selected" <?php endif; ?>>H</option>
                                            <option value="I" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'I'): ?>selected="selected" <?php endif; ?>>I</option>
                                            <option value="J" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'J'): ?>selected="selected" <?php endif; ?>>J</option>
                                            <option value="K" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'K'): ?>selected="selected" <?php endif; ?>>K</option>
                                            <option value="L" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'L'): ?>selected="selected" <?php endif; ?>>L</option>
                                            <option value="L1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'L1'): ?>selected="selected" <?php endif; ?>>L1</option>
                                            <option value="M" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'M'): ?>selected="selected" <?php endif; ?>>M</option>
                                            <option value="M1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'M1'): ?>selected="selected" <?php endif; ?>>M1</option>
                                            <option value="M2" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'M2'): ?>selected="selected" <?php endif; ?>>M2</option>
                                            <option value="N" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'N'): ?>selected="selected" <?php endif; ?>>N</option>
                                            <option value="O" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'O'): ?>selected="selected" <?php endif; ?>>O</option>
                                            <option value="O1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'O1'): ?>selected="selected" <?php endif; ?>>O1</option>
                                            <option value="P" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'P'): ?>selected="selected" <?php endif; ?>>P</option>
                                            <option value="Q" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'Q'): ?>selected="selected" <?php endif; ?>>Q</option>
                                            <option value="R" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'R'): ?>selected="selected" <?php endif; ?>>R</option>
                                            <option value="S" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'S'): ?>selected="selected" <?php endif; ?>>S</option>
                                            <option value="T" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'T'): ?>selected="selected" <?php endif; ?>>T</option>
                                            <option value="U" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'U'): ?>selected="selected" <?php endif; ?>>U</option>
                                            <option value="V" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'V'): ?>selected="selected" <?php endif; ?>>V</option>
                                            <option value="V1" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'V1'): ?>selected="selected" <?php endif; ?>>V1</option>
                                            <option value="V2" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'V2'): ?>selected="selected" <?php endif; ?>>V2</option>
                                            <option value="W" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'W'): ?>selected="selected" <?php endif; ?>>W</option>
                                            <option value="X" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'X'): ?>selected="selected" <?php endif; ?>>X</option>
                                            <option value="Y" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'Y'): ?>selected="selected" <?php endif; ?>>Y</option>
                                            <option value="Z" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'Z'): ?>selected="selected" <?php endif; ?>>Z</option>
                                            <option value="ZO" <?php if (!empty($documento['documenti_contabilita_causale_pagamento_ritenuta']) && $documento['documenti_contabilita_causale_pagamento_ritenuta'] == 'ZO'): ?>selected="selected" <?php endif; ?>>ZO</option>
                                        </select>
                                        <!-- todo da completare in base alle richieste -->
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Tipo ritenuta: </label>
                                        <select name="documenti_contabilita_tipo_ritenuta" class="select2 form-control">
                                            <?php foreach ($tipi_ritenuta as $key => $tipo_ritenuta): ?>
                                            <?php
                                        $tipo_ritenuta_selected = '';
                                        
                                        if (!empty($documento['documenti_contabilita_tipo_ritenuta']) && $documento['documenti_contabilita_tipo_ritenuta'] == $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_id']) {
                                            $tipo_ritenuta_selected = 'selected="selected"';
                                        } else {
                                            if (!empty($this->input->get('tipo_ritenuta')) && $this->input->get('tipo_ritenuta') == $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_id']) {
                                                $tipo_ritenuta_selected = 'selected="selected"';
                                            } else {
                                                if (!empty($impostazioni['documenti_contabilita_settings_tipo_ritenuta']) && $impostazioni['documenti_contabilita_settings_tipo_ritenuta'] == $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_id']) {
                                                    $tipo_ritenuta_selected = 'selected="selected"';
                                                }
                                            }
                                        }
                                        ?>
                                            <option value="<?php echo $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_id']; ?>" <?php echo $tipo_ritenuta_selected ?>><?php echo $tipo_ritenuta['documenti_contabilita_tipo_ritenuta_descrizione']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <!-- todo da completare in base alle richieste -->
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <span>
                                            <label>
                                                <strong>Applica Bollo</strong>
                                                <?php
                                            $applica_bollo = 'checked="checked"';
                                            if (
                                                (!empty($documento) && $documento['documenti_contabilita_applica_bollo'] == DB_BOOL_FALSE)
                                                || ($this->input->get('applica_bollo') == DB_BOOL_FALSE || $impostazioni['documenti_contabilita_settings_applica_bollo'] == DB_BOOL_FALSE)
                                            ) {
                                                $applica_bollo = '';
                                            }
                                            ?>
                                                <input type="checkbox" class="minimal" name="documenti_contabilita_applica_bollo" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php echo $applica_bollo ?> />
                                            </label>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <span>
                                            <label><strong>2.1.1.6.1 - Bollo virtuale</strong>
                                                <?php
                                            $bollo_virtuale = 'checked="checked"';
                                            if (
                                                (!empty($documento) && $documento['documenti_contabilita_bollo_virtuale'] == DB_BOOL_FALSE)
                                                || ($this->input->get('bollo_virtuale') == DB_BOOL_FALSE || $impostazioni['documenti_contabilita_settings_bollo_virtuale'] == DB_BOOL_FALSE)
                                            ) {
                                                $bollo_virtuale = '';
                                            }
                                            ?>
                                                <input type="checkbox" class="minimal" name="documenti_contabilita_bollo_virtuale" class="rcr-adjust" value="<?php echo DB_BOOL_TRUE; ?>" <?php echo $bollo_virtuale ?> />
                                            </label>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <span>
                                            <label>Applica Split Payment</label>
                                            <?php
                                        $split_payment = '';
                                        if (
                                            (!empty($documento['documenti_contabilita_split_payment']) && $documento['documenti_contabilita_split_payment'] == DB_BOOL_TRUE)
                                            || ($this->input->get('split_payment') == DB_BOOL_TRUE || $impostazioni['documenti_contabilita_settings_applica_split_payment'] == DB_BOOL_TRUE)
                                        ) {
                                            $split_payment = 'checked="checked"';
                                        }
                                        ?>
                                            <input type="checkbox" class="minimal" name="documenti_contabilita_split_payment" value="<?php echo DB_BOOL_TRUE; ?>" <?php echo $split_payment ?> />
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">


            <hr />


            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <?php $this->load->view('contabilita/tabella_articoli', [
                            'campi_personalizzati' => $campi_personalizzati,
                            'impostazioni' => $impostazioni,
                            'campo_centro_costo' => $campo_centro_costo,
                            'colonne_count' => $colonne_count,
                            'centri_di_costo' => $centri_di_costo,
                            'elenco_iva' => $elenco_iva,
                            'xml_articoli_altri_dati_gestionale' => $xml_articoli_altri_dati_gestionale,
                            'documento' => $documento ?? null,
                            'documento_id' => $documento_id ?? null,
                            'clone' => $clone ?? null,
                            'commesse' => $commesse ?? [],
                        ]);
                        ?>
                    </div>
                </div>
            </div>

            <hr />
            <div class="row margin-bottom-5 col-md-12">
                <div class="form-group">
                    <label> <input type="checkbox" class="minimal js_fattura_accompagnatoria_checkbox" name="documenti_contabilita_fattura_accompagnatoria" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_fattura_accompagnatoria']) && $documento['documenti_contabilita_fattura_accompagnatoria'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?>>
                        Dati trasporto </label>
                    <label> <input type="checkbox" class="minimal js_attr_avanzati_fe_checkbox" name="documenti_contabilita_fe_attributi_avanzati" value="<?php echo DB_BOOL_TRUE; ?>" <?php if (!empty($documento['documenti_contabilita_fe_attributi_avanzati']) && $documento['documenti_contabilita_fe_attributi_avanzati'] == DB_BOOL_TRUE): ?> checked="checked" <?php endif; ?>>
                        Attributi Avanzati Fattura Elettronica </label>
                    <?php
                        if ($this->datab->module_installed('magazzino')):
                        $settings_magazzino = $this->apilib->searchFirst('magazzino_settings');
                        $aggiungi_a_catalogo = $settings_magazzino['magazzino_settings_aggiungi_articoli_non_presenti'] ?? DB_BOOL_FALSE;
                    ?>
                    <label> <input type="checkbox" class="minimal js_fattura_add_articoli" name="documenti_contabilita_aggiungi_articoli" value="<?php echo DB_BOOL_TRUE; ?>" <?php if ($aggiungi_a_catalogo == DB_BOOL_TRUE) : ?> checked="checked" <?php endif; ?>>
                        Aggiungi articoli non presenti a magazzino </label>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row js_fattura_accompagnatoria_row hide">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>N. Colli: </label> <input type="text" class="form-control" placeholder="1" name="documenti_contabilita_n_colli" value="<?php echo (!empty($documento['documenti_contabilita_n_colli'])) ? number_format((float) $documento['documenti_contabilita_n_colli'], 0, ',', '') : ''; ?>" />
                    </div>
                </div>

                <div class='col-md-2'>
                    <div class='form-group'>
                        <label>Peso netto: </label> <input type='text' class='form-control' placeholder='0 kg' name='documenti_contabilita_peso_netto' value="<?php echo (!empty($documento['documenti_contabilita_peso_netto'])) ? number_format((float) $documento['documenti_contabilita_peso_netto'], 2, '.', '') : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Peso lordo: </label> <input type="text" class="form-control" placeholder="0 kg" name="documenti_contabilita_peso" value="<?php echo (!empty($documento['documenti_contabilita_peso'])) ? number_format((float) $documento['documenti_contabilita_peso'], 2, '.', '') : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Volume: </label> <input type="text" class="form-control" placeholder="0 m3" name="documenti_contabilita_volume" value="<?php echo (!empty($documento['documenti_contabilita_volume'])) ? number_format((float) $documento['documenti_contabilita_volume'], 2, '.', '') : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Porto: </label> <input type="text" class="form-control" placeholder="Porto" name="documenti_contabilita_porto" value="<?php echo (!empty($documento['documenti_contabilita_porto'])) ? $documento['documenti_contabilita_porto'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>Targhe: </label> <input type="text" class="form-control" placeholder="Targhe" name="documenti_contabilita_targhe" value="<?php echo (!empty($documento['documenti_contabilita_targhe'])) ? $documento['documenti_contabilita_targhe'] : ''; ?>" />
                    </div>
                </div>
            </div>

            <div class="row js_fattura_accompagnatoria_row hide">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Data Ritiro Merce: </label>
                        <div class="input-group js_form_datepicker date ">
                            <input type="text" name="documenti_contabilita_data_ritiro_merce" class="form-control" placeholder="Data Ritiro Merce" value="<?php if (!empty($documento['documenti_contabilita_data_ritiro_merce']) && !$clone): ?><?php echo $documento['documenti_contabilita_data_ritiro_merce']; ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif; ?>" data-name="documenti_contabilita_data_ritiro_merce" /> <span class="input-group-btn">
                                <button class="btn btn-default" type="button" style="display:none">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Corriere/Vettore: </label> <input type="text" class="form-control" placeholder="Azienda di trasporti" name="documenti_contabilita_trasporto_a_cura_di" value="<?php echo (!empty($documento['documenti_contabilita_trasporto_a_cura_di'])) ? $documento['documenti_contabilita_trasporto_a_cura_di'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Descrizione Colli: </label> <input type="text" class="form-control" placeholder="Desc. Colli" name="documenti_contabilita_descrizione_colli" value="<?php echo (!empty($documento['documenti_contabilita_descrizione_colli'])) ? $documento['documenti_contabilita_descrizione_colli'] : ''; ?>" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tracking code: </label>
                        <input type="text" class="form-control" placeholder="es.: ABC00012345" name="documenti_contabilita_tracking_code" value="<?php echo (!empty($documento['documenti_contabilita_tracking_code'])) ? $documento['documenti_contabilita_tracking_code'] : ''; ?>" />
                    </div>
                </div>
            </div>

            <div class="row js_fattura_accompagnatoria_row hide">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Luogo di destinazione: <a style="display:none;" class="js_choose_address js_open_modal">(mostra indirizzi cliente)</a></label>
                        <textarea class="form-control" placeholder="Luogo di Destinazione" rows="3" name="documenti_contabilita_luogo_destinazione"><?php echo (!empty($documento['documenti_contabilita_luogo_destinazione'])) ? $documento['documenti_contabilita_luogo_destinazione'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dati corriere/vettore: </label> <textarea class="form-control" placeholder="Annotazioni" rows="3" name="documenti_contabilita_vettori_residenza_domicilio"><?php echo (!empty($documento['documenti_contabilita_vettori_residenza_domicilio'])) ? $documento['documenti_contabilita_vettori_residenza_domicilio'] : ''; ?></textarea>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Causale di trasporto: </label> <textarea class="form-control" placeholder="Causale trasporto" rows="3" name="documenti_contabilita_causale_trasporto"><?php echo (!empty($documento['documenti_contabilita_causale_trasporto'])) ? $documento['documenti_contabilita_causale_trasporto'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Annotazioni: </label> <textarea class="form-control" placeholder="Annotazioni" rows="3" name="documenti_contabilita_annotazioni_trasporto"><?php echo (!empty($documento['documenti_contabilita_annotazioni_trasporto'])) ? $documento['documenti_contabilita_annotazioni_trasporto'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="row js_attributi_avanzati_fattura_elettronica hide">

                <!-- @todo 20190703 - Michael E. - in futuro questi campi saranno resi multicreazione, come per i prodotti -->
                <?php
                $documento_fe = (!empty($documento['documenti_contabilita_fe_attributi_avanzati_json'])) ? json_decode($documento['documenti_contabilita_fe_attributi_avanzati_json'], true) : '';
                ?>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>1.2.6 - Riferimento amministrazione</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[riferimento_amministrazione]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['riferimento_amministrazione'])) ? $documento['documenti_contabilita_fe_dati_contratto']['riferimento_amministrazione'] : ''; ?>" />
                    </div>
                </div>

            </div>
            <div class="row js_attributi_avanzati_fattura_elettronica hide">


                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.2.1 Ord. d'acquisto - Rif. N° Linea</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_ordineacquisto[riferimento_numero_linea]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['riferimento_numero_linea'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['riferimento_numero_linea'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.2.2 Ordine d'acquisto - Id Documento</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_ordineacquisto[id_documento]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['id_documento'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['id_documento'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.2.3 Ordine d'acquisto - Data (YYYY-MM-DD)</label>
                        <input type="text" class="form-control" placeholder="YYYY-MM-DD" name="documenti_contabilita_fe_ordineacquisto[data]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['data'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['data'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.2.4 Ordine d'acquisto - Num Item</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_ordineacquisto[num_item]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['num_item'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['num_item'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>2.1.2.5 Ordine d'acquisto - Codice Commessa Convenzione</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_ordineacquisto[codice_commessa_convenzione]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['codice_commessa_convenzione'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['codice_commessa_convenzione'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>2.1.2.6 Ordine d'acquisto - Codice CUP</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_ordineacquisto[codice_cup]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['codice_cup'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['codice_cup'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>2.1.2.7 Ordine d'acquisto - Codice CIG</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_ordineacquisto[codice_cig]" value="<?php echo (!empty($documento['documenti_contabilita_fe_ordineacquisto']['codice_cig'])) ? $documento['documenti_contabilita_fe_ordineacquisto']['codice_cig'] : ''; ?>" />
                    </div>
                </div>


            </div>
            <div class="row js_attributi_avanzati_fattura_elettronica hide">
                <!--
                <DatiContratto>
                    <RiferimentoNumeroLinea>
                    <IdDocumento></IdDocumento>
                    <Data></Data>
                    <NumItem></NumItem>
                    <CodiceCommessaConvenzione></CodiceCommessaConvenzione>
                    <CodiceCUP></CodiceCUP>
                    <CodiceCIG></CodiceCIG>
                </DatiContratto>
                -->

                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.3.1 Dati Contratto - Rif. N° Linea</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[riferimento_numero_linea]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['riferimento_numero_linea'])) ? $documento['documenti_contabilita_fe_dati_contratto']['riferimento_numero_linea'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.3.2 Dati Contratto - Id Documento</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[id_documento]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['id_documento'])) ? $documento['documenti_contabilita_fe_dati_contratto']['id_documento'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.3.3 Dati Contratto - Data (YYYY-MM-DD)</label>
                        <input type="text" class="form-control" placeholder="YYYY-MM-DD" name="documenti_contabilita_fe_dati_contratto[data]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['data'])) ? $documento['documenti_contabilita_fe_dati_contratto']['data'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>2.1.3.4 Dati Contratto - Num Item</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[num_item]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['num_item'])) ? $documento['documenti_contabilita_fe_dati_contratto']['num_item'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>2.1.3.5 Dati Contratto - Codice Commessa Convenzione</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[codice_commessa_convenzione]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['codice_commessa_convenzione'])) ? $documento['documenti_contabilita_fe_dati_contratto']['codice_commessa_convenzione'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>2.1.3.6 Dati Contratto - Codice CUP</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[codice_cup]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['codice_cup'])) ? $documento['documenti_contabilita_fe_dati_contratto']['codice_cup'] : ''; ?>" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>2.1.3.7 Dati Contratto - Codice CIG</label>
                        <input type="text" class="form-control" name="documenti_contabilita_fe_dati_contratto[codice_cig]" value="<?php echo (!empty($documento['documenti_contabilita_fe_dati_contratto']['codice_cig'])) ? $documento['documenti_contabilita_fe_dati_contratto']['codice_cig'] : ''; ?>" />
                    </div>
                </div>
            </div>
            <div class="row js_attributi_avanzati_fattura_elettronica hide">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Riferimento Documento DDT <small>(facoltativo)</small>:</label>
                        <select name="documenti_contabilita_rif_ddt" class="select2 form-control">
                            <option value=""></option>
                            <?php foreach ($ddts as $ddt): ?>
                            <option data-documento_id="<?php echo $ddt['documenti_contabilita_id']; ?>" value="<?php echo $ddt['documenti_contabilita_id']; ?>" <?php if ((!empty($documento['documenti_contabilita_rif_ddt']) && $documento['documenti_contabilita_rif_ddt'] == $ddt['documenti_contabilita_id'])): ?> selected="selected" <?php endif; ?>>
                                <?php echo 'DDT N° ', $ddt['documenti_contabilita_numero'], ' del ', date('d/m/Y', strtotime($ddt['documenti_contabilita_data_emissione'])), ' - ', json_decode($ddt['documenti_contabilita_destinatario'], true)['ragione_sociale']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <label>Editor attributi avanzato</label><br />
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalEditorXml">Editor parametri avanzati</button>
                        <div class="modal fade" id="modalEditorXml" tabindex="-1" role="dialog" aria-labelledby="modalEditorXmlLabel" aria-hidden="true" __data-elements="FatturaElettronica/FatturaElettronicaBody/DatiGenerali/DatiFattureCollegate">
                            <div class="modal-dialog modal-xl" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalEditorXmlLabel">Editor XML</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="jsEditorContainer" data-json_data="<?php echo base64_encode($json_editor_xml); ?>" data-fetchurl="<?php echo $this->layout->moduleAssets('contabilita', 'uploads/Schema_del_file_xml_FatturaPA_v1.2.2.xsd.xml'); ?>"></div>
                                    </div>
                                    <div class="modal-footer">

                                        <button type="button" class="btn btn-primary" data-dismiss="modal">Salva modifiche</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>


            <hr />


            <div class="row">
                <div class="col-md-5 mb-15">
                    <textarea name="documenti_contabilita_note_generiche" rows="10" class="form-control" placeholder="Note generiche [opzionali]"><?php if ($documento_id): ?><?php echo $documento['documenti_contabilita_note_generiche']; ?><?php endif; ?></textarea>
                </div>
                <div class="col-md-7 scadenze_box" style="background-color: #b7d7ea;">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Scadenza pagamento</h4>
                        </div>
                    </div>

                    <div class="row js_rows_scadenze">
                        <?php if ($documento_id && !$clone): ?>
                        <?php foreach ($documento['scadenze'] as $key => $scadenza): ?>
                        <div class="row row_scadenza">
                            <input type="hidden" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_template_json]" value="" class="documenti_contabilita_scadenze_template_json" data-name="documenti_contabilita_scadenze_template_json" />
                            <div class=" col-md-3">
                                <div class="form-group">
                                    <input class="js_documenti_contabilita_scadenze_id" type="hidden" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_id]" data-name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_id]" value="<?php echo $scadenza['documenti_contabilita_scadenze_id']; ?>" />
                                    <label>Ammontare</label> <input type="text" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_ammontare]" class="form-control documenti_contabilita_scadenze_ammontare js_decimal" placeholder="Ammontare" value="<?php echo number_format((float) $scadenza['documenti_contabilita_scadenze_ammontare'], 2, '.', ''); ?>" data-name="documenti_contabilita_scadenze_ammontare" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_scadenza]" class="form-control documenti_contabilita_scadenze_scadenza" placeholder="Scadenza" value="<?php echo date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_scadenza'])); ?>" data-name="documenti_contabilita_scadenze_scadenza" /> <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Metodo di pagamento</label>
                                    <select name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_saldato_con]" class="_select2 form-control _js_table_select2 _js_table_select2<?php echo $key; ?> documenti_contabilita_scadenze_saldato_con" data-name="documenti_contabilita_scadenze_saldato_con">

                                        <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                        <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']; ?>" <?php if (stripos($scadenza['documenti_contabilita_scadenze_saldato_con'], $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']) !== false): ?> selected="selected" <?php endif; ?>>
                                            <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <!--
                                                <option value="Contanti" <?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Contanti'): ?> selectefd="selected"<?php endif; ?>>
                                                    Contanti
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Bonifico bancario'): ?> selectefd="selected"<?php endif; ?>>
                                                    Bonifico bancario
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Assegno'): ?> selectefd="selected"<?php endif; ?>>
                                                    Assegno
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'RiBA'): ?> selectefd="selected"<?php endif; ?>>
                                                    RiBA
                                                </option>
                                                <option<?php if ($scadenza['documenti_contabilita_scadenze_saldato_con'] == 'Sepa RID'): ?> selectefd="selected"<?php endif; ?>>
                                                    Sepa RID
                                                </option>-->
                                    </select>

                                    <script>
                                    $('.js_table_select2<?php echo $key; ?>').val('<?php echo strtolower($scadenza['documenti_contabilita_scadenze_saldato_con']); ?>').trigger('change.select2');
                                    </script>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Data saldo</label>
                                    <div class="input-group js_form_datepicker date  field_68">
                                        <input type="text" class="form-control documenti_contabilita_scadenze_data_saldo" id="empty_date" name="scadenze[<?php echo $key; ?>][documenti_contabilita_scadenze_data_saldo]" data-name="documenti_contabilita_scadenze_data_saldo" value="<?php echo ($scadenza['documenti_contabilita_scadenze_data_saldo']) ? date('d/m/Y', strtotime($scadenza['documenti_contabilita_scadenze_data_saldo'])) : ''; ?>">

                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none;"><i class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else:
                            $key = -1; ?>
                        <?php endif; ?>
                        <div class="row row_scadenza">
                            <input type="hidden" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_template_json]" value="" class="documenti_contabilita_scadenze_template_json" data-name="documenti_contabilita_scadenze_template_json" />

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ammontare</label> <input type="text" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_ammontare]" class="form-control documenti_contabilita_scadenze_ammontare js_decimal" placeholder="Ammontare" value="" data-name="documenti_contabilita_scadenze_ammontare" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Scadenza</label>
                                    <div class="input-group js_form_datepicker date ">
                                        <input type="text" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_scadenza]" class="form-control documenti_contabilita_scadenze_scadenza" placeholder="Scadenza" value="<?php echo date('d/m/Y'); ?>" data-name="documenti_contabilita_scadenze_scadenza" /> <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Metodo di pagamento</label>


                                    <select name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_saldato_con]" class="select2 form-control js_table_select2 documenti_contabilita_scadenze_saldato_con" data-name="documenti_contabilita_scadenze_saldato_con">

                                        <?php foreach ($metodi_pagamento as $metodo_pagamento): ?>
                                        <option value="<?php echo $metodo_pagamento['documenti_contabilita_metodi_pagamento_id']; ?>" <?php if ($metodo_pagamento['documenti_contabilita_metodi_pagamento_codice'] == 'MP05'): //bonifico
                                                           ?> selected="selected" <?php endif; ?>>
                                            <?php echo ucfirst($metodo_pagamento['documenti_contabilita_metodi_pagamento_valore']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Data saldo</label>
                                    <div class="input-group js_form_datepicker date  field_68">
                                        <input type="text" class="form-control" name="scadenze[<?php echo $key + 1; ?>][documenti_contabilita_scadenze_data_saldo]" id="empty_date" data-name="documenti_contabilita_scadenze_data_saldo" value="">

                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" style="display:none"><i class="fa fa-calendar"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <?php /*
                <div class="row">
                <div class="col-md-12 text-center">
                <button style="display:none;" id="js_add_scadenza" class="btn btn-primary btn-sm">+ Aggiungi scadenza</button>
                </div>
                </div> */?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div id="msg_new_fattura" class="alert alert-danger hide"></div>
            </div>
        </div>
    </div>

    <div class="form-actions fluid">
        <div class="col-md-6">
            <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-success js_elenco_documenti"><i class="fa fa-arrow-left"></i> Elenco Documenti</a>
        </div>
        <div class="col-md-6">
            <div class="pull-right">
                <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-danger default">Annulla</a>
                <button type="submit" class="btn btn-success">Salva</button>
            </div>
        </div>
    </div>

    <!--<div class="form actions">
        <div class="row">
            <div class="col-sm-6 col-xs-12 mb-15">
                <div class="pull-left">
                    <a href="<?php echo base_url('main/layout/elenco_documenti'); ?>" class="btn btn-success"><i class="fa fa-arrow-left"></i> Elenco Documenti</a>
                </div>
            </div>
            <div class="col-sm-6 col-xs-12">
                <div class="pull-right">
                    <a href="<?php echo base_url(); ?>" class="btn btn-danger default">Annulla</a>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </div>
        </div>
    </div>-->

    </div>

</form>

<div class="modal fade" tabindex="-1" role="dialog" id="modal_xml_converted">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-12">
                            <form class="form_custom_input" method="POST" action="#">
                                <div class="modal_content_custom_input">

                                    <div class="title"></div>
                                    <div class="content_custom_input"></div>

                                </div>
                            </form>
                            <div class="modal_footer_custom_input">
                                <button class="btn btn-success pull-right js-pulsanteSalvaAttributi" onclick="saveAttributiAvanzati()">Salva attributi</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<!-- Modale for Causale Pag. rit.-->
<div class="modal fade" id="modal-default" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">Causale del pagamento</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Descrizione</th>
                            <th>Formato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">A</th>
                            <td>Prestazioni di lavoro autonomo rientranti nell’esercizio di arte o professione abituale.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">B</th>
                            <td>Utilizzazione economica, da parte dell’autore o dell’inventore, di opere dell’ingegno, di brevetti industriali e di processi, relativi a esperienze acquisite in campo industriale, commerciale o scientifico.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">C</th>
                            <td>Utili derivanti da contratti di associazione in partecipazione e da contratti di cointeressenza, quando l’apporto è costituito esclusivamente dalla prestazione di lavoro.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">D</th>
                            <td>Utili spettanti ai soci promotori e ai soci fondatori delle società di capitali.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">E</th>
                            <td>Levata di protesti cambiari da parte dei segretari comunali.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">G</th>
                            <td>Indennità corrisposte per la cessazione di attività sportiva professionale.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">H</th>
                            <td>Indennità corrisposte per la cessazione dei rapporti di agenzia delle persone fisiche e delle società di persone, con esclusione delle somme maturate entro il 31.12.2003, già imputate per competenza.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">I</th>
                            <td>Indennità corrisposte per la cessazione da funzioni notarili.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">J</th>
                            <td>Compensi corrisposti ai raccoglitori occasionali di tartufi non identificati ai fini dell’imposta sul valore.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">K</th>
                            <td>Assegni di servizio civile di cui all’art. 16 del D.lgs. n. 40 del 6 marzo 2017.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">L</th>
                            <td>Utilizzazione economica, da parte di soggetto diverso dall’autore o dall’inventore, di opere dell’ingegno, di brevetti industriali e di processi, formule e informazioni relative a esperienze acquisite.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">L1</th>
                            <td>Redditi derivanti dall’utilizzazione economica di opere dell’ingegno, di brevetti industriali e di processi, che sono percepiti da soggetti che abbiano acquistato a titolo oneroso i diritti alla loro utilizzazione.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">M</th>
                            <td>Prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">M1</th>
                            <td>Redditi derivanti dall’assunzione di obblighi di fare, di non fare o permettere.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">M2</th>
                            <td>Prestazioni di lavoro autonomo non esercitate abitualmente per le quali sussiste l’obbligo di iscrizione alla Gestione Separata ENPAPI.</td>
                            <td>V.202X</td>
                        </tr>
                        <tr>
                            <th scope="row">N</th>
                            <td>Indennità di trasferta, rimborso forfettario di spese, premi e compensi erogati: .. nell’esercizio diretto di attività sportive dilettantistiche.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">O</th>
                            <td>Prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere, per le quali non sussiste l’obbligo di iscrizione alla gestione separata (Circ. Inps 104/2001).</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">O1</th>
                            <td>Redditi derivanti dall’assunzione di obblighi di fare, di non fare o permettere, per le quali non sussiste l’obbligo di iscrizione alla gestione separata (Circ. INPS n. 104/2001).</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">P</th>
                            <td>Compensi corrisposti a soggetti non residenti privi di stabile organizzazione per l’uso o la concessione in uso di attrezzature industriali, commerciali o scientifiche che si trovano nel territorio dello Stato.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">Q</th>
                            <td>Provvigioni corrisposte ad agente o rappresentante di commercio monomandatario.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">R</th>
                            <td>Provvigioni corrisposte ad agente o rappresentante di commercio plurimandatario</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">S</th>
                            <td>Provvigioni corrisposte a commissionario.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">T</th>
                            <td>Provvigioni corrisposte a mediatore.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">U</th>
                            <td>Provvigioni corrisposte a procacciatore di affari.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">V</th>
                            <td>Provvigioni corrisposte a incaricato per le vendite a domicilio e provvigioni corrisposte a incaricato per la vendita porta a porta e per la vendita ambulante di giornali quotidiani e periodici (L. 25.02.1987, n. 67).</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">V1</th>
                            <td>Redditi derivanti da attività commerciali non esercitate abitualmente (ad esempio, provvigioni corrisposte per prestazioni occasionali ad agente o rappresentante di commercio, mediatore, procacciatore d’affari);.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">V2</th>
                            <td>Redditi derivanti dalle prestazioni non esercitate abitualmente rese dagli incaricati alla vendita diretta a domicilio.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">W</th>
                            <td>Corrispettivi erogati nel 2015 per prestazioni relative a contratti d’appalto cui si sono resi applicabili le disposizioni contenute nell’art. 25-ter D.P.R. 600/1973.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">X</th>
                            <td>Canoni corrisposti nel 2004 da società o enti residenti, ovvero da stabili organizzazioni di società estere di cui all’art. 26-quater, c. 1, lett. a) e b) D.P.R. 600/1973.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">Y</th>
                            <td>Canoni corrisposti dal 1.01.2005 al 26.07.2005 da soggetti di cui al punto precedente.</td>
                            <td>V.20XX</td>
                        </tr>
                        <tr>
                            <th scope="row">Z</th>
                            <td>Titolo diverso dai precedenti. (Non idoneo con l'operatività corrente)</td>
                            <td>V.&#8804;2020</td>
                        </tr>
                        <tr>
                            <th scope="row">ZO</th>
                            <td>Titolo diverso dai precedenti.</td>
                            <td>V.20XX</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<?php if ($this->datab->module_installed('magazzino')) : ?>
<?php $magazzino_settings = $this->apilib->searchFirst('magazzino_settings'); ?>
<?php $this->load->module_view('magazzino/views','lotti_modal', ['settings' => $magazzino_settings], false); ?>
<script>
var movimenta_per = <?php echo (!empty($magazzino_settings['magazzino_settings_movimenta_per'])) ? json_encode(array_keys($magazzino_settings['magazzino_settings_movimenta_per'])) : '[]'; ?>;
</script>
<?php else: ?>
<script>
var movimenta_per = [];
</script>
<?php endif; ?>

<?php $this->layout->addModuleJavascript('contabilita', 'nuovo_documento.js'); ?>
<?php $this->layout->addModuleJavascript('contabilita', 'xsd_to_form.js'); ?>
<?php $this->layout->addModuleJavascript('contabilita', 'editor_xml.js'); ?>
<?php $this->layout->addModuleStylesheet('contabilita', 'css/editor_xml.css'); ?>

<script>
var template_scadenze = <?php echo json_encode($template_scadenze); ?>;
var listini = <?php echo json_encode($listini); ?>;
var metodi_pagamenti_map = <?php echo json_encode($metodi_pagamento_map) ?>;
var metodi_pagamenti_map_reverse = <?php echo json_encode($metodi_pagamento_map_reverse) ?>;

$('form #js_product_table:not(textarea)').bind('keypress', function(e) {
    if (e.keyCode == 13) {
        const pressedElement = $(e.target);
        const closestTr = pressedElement.closest('tr');
        const nextTr = closestTr.next();
        nextTr.find('input').first().focus();

        e.preventDefault();
        return false;
    }
});
</script>

<script>
var token = JSON.parse(atob('<?php echo base64_encode(json_encode(get_csrf())); ?>'));
var token_name = token.name;
var token_hash = token.hash;

function popolaVettore(vettore) {
    $('input[name="documenti_contabilita_trasporto_a_cura_di"]').val(vettore['vettori_ragione_sociale']);
    $('textarea[name=documenti_contabilita_vettori_residenza_domicilio]').val(vettore['vettori_indirizzo'] + "\n" + vettore['vettori_citta'] + ' ' + vettore['vettori_cap']);
}

$(document).ready(function() {
    $('button', $('.js_rivalsa_container')).on('click', function() {
        $(this).find('.container-plus-button').toggleClass('fa-plus', 'fa-minus');
    })

    $("textarea[name='documenti_contabilita_vettori_residenza_domicilio'], input[name='documenti_contabilita_trasporto_a_cura_di']").autocomplete({
        source: function(request, response) {
            $.ajax({
                method: 'post',
                url: base_url + "contabilita/documenti/autocomplete/vettori",
                dataType: "json",
                data: {
                    search: request.term,
                    [token_name]: token_hash
                },
                minLength: 2,
                success: function(data) {
                    var collection = [];
                    loading(false);
                    $.each(data.results.data, function(i, p) {
                        collection.push({
                            "id": p.vettori_id,
                            "label": p.vettori_ragione_sociale + " - " + p.vettori_indirizzo,
                            "value": p.vettori_ragione_sociale + " - " + p.vettori_indirizzo,
                            "data": p
                        });
                    });
                    response(collection);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            if (event.keyCode === 9) return false;
            popolaVettore(ui.item.data);
            return false;
        }
    });


    $('.js_select2').each(function() {
        var select = $(this);
        var placeholder = select.attr('data-placeholder');
        select.select2({
            placeholder: placeholder ? placeholder : '',
            allowClear: true
        });
    });
});


$(".js_fattura_accompagnatoria_checkbox").change(function() {

    if ($(this).is(':checked')) {
        $(".js_fattura_accompagnatoria_row").removeClass('hide');
    } else {
        $(".js_fattura_accompagnatoria_row").addClass('hide');
    }


    //        if (!$( ".js_fattura_accompagnatoria_row" ).hasClass('hide')) {
    //            $( ".js_fattura_accompagnatoria_row" ).addClass('hide');
    //        } else {
    //            $( ".js_fattura_accompagnatoria_row" ).removeClass('hide');
    //        }
});

//Apro i dati trasporto
<?php if ($documento_id): ?>
var js_dati_trasporto_checked = <?php echo ($documento['documenti_contabilita_fattura_accompagnatoria'] == DB_BOOL_TRUE) ? 'true' : 'false'; ?>;

if (js_dati_trasporto_checked) {
    if (!$('.js_fattura_accompagnatoria_checkbox').is(':checked')) {
        $('.js_fattura_accompagnatoria_checkbox').trigger('click');
    } else if ($('.js_fattura_accompagnatoria_checkbox').is(':checked') && $('.js_fattura_accompagnatoria_row').hasClass('hide')) {
        $('.js_fattura_accompagnatoria_row').removeClass('hide');
    }
}
<?php endif; ?>

$('[name="documenti_contabilita_applica_bollo"]').on('change', function() {
    calculateTotals();
})

$(".js_attr_avanzati_fe_checkbox").change(function() {

    if ($(this).is(':checked')) {
        $(".js_attributi_avanzati_fattura_elettronica").removeClass('hide');
    } else {
        $(".js_attributi_avanzati_fattura_elettronica").addClass('hide');
        var inputs = $(".js_attributi_avanzati_fattura_elettronica :input");
        $.each(inputs, function() {
            $(this).val('');
        });
    }
});

$(".js_attr_avanzati_fe_checkbox").trigger('change');
</script>

<script>
var ricalcolaPrezzo = function(prezzo, prodotto) {
    //console.log('dentro funzione originale');
    return prezzo;
}

/****************** AUTOCOMPLETE Destinatario *************************/
var initAutocomplete = function(autocomplete_selector) {

    autocomplete_selector.autocomplete({
        source: function(request, response) {
            //conto i caratteri e metto alert se superano i 240
            var characterCount = request.term.length;
            if (characterCount > 249) {

                alert("Attenzione, stai superando il massimo dei caratteri consentiti, pari a 249 caratteri. ");
            }


            $.ajax({
                method: 'post',
                url: base_url + "contabilita/documenti/autocomplete/<?php echo $entita; ?>",
                dataType: "json",
                data: {
                    search: request.term,
                    [token_name]: token_hash
                },
                /*search: function( event, ui ) {
                    loading(true);
                },*/
                success: function(data) {
                    var collection = [];
                    loading(false);

                    //                        console.log(autocomplete_selector.data("id"));
                    //                        if (data.count_total == 1) {
                    //                            popolaProdotto(data.results.data[0], autocomplete_selector.data("id"));
                    //                        } else {

                    $.each(data.results.data, function(i, p) {
                        <?php if ($campo_codice && !empty($campo_fornitore_prodotto)): ?>

                        var label = <?php if ($campo_preview): ?>p.<?php echo $campo_codice; ?> + ' - ' + p.<?php echo $campo_preview; ?><?php else: ?> '*impostare campo preview*'
                        <?php endif; ?> + ' - ' + p.<?php echo $campo_fornitore_prodotto; ?> + ' - ' + p.<?php echo $campo_prezzo; ?>;

                        <?php elseif ($campo_codice): ?>

                        var label = <?php if ($campo_preview): ?>p.<?php echo $campo_codice; ?> + ' - ' + p.<?php echo $campo_preview; ?><?php else: ?> '*impostare campo preview*';
                        <?php endif; ?>
                        <?php else: ?>

                        var label = <?php if ($campo_preview): ?>p.<?php echo $campo_preview; ?><?php else: ?> '*impostare campo preview*';
                        <?php endif; ?>
                        <?php endif; ?>


                        <?php if ($campo_quantita): ?>
                        label += ' (qty: ' + p.<?php echo $campo_quantita; ?> + ')';
                        <?php endif; ?>
                        collection.push({
                            "id": p.<?php echo $campo_id; ?>,
                            "label": label,
                            "value": label,
                            "data": p
                        });

                    });
                    //                        }

                    //console.log(collection);
                    response(collection);
                }
            });
        },
        minLength: 2,
        response: function(event, ui) {
            if (ui.content.length == 1) {
                // $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', {
                //     item: {
                //         value: ui.content[0].value
                //     }
                // });
                //popolaProdotto(ui.content[0].value, autocomplete_selector.data("id"));
            }
        },
        select: function(event, ui) {
            // fix per disabilitare la ricerca con il tab
            if (event.keyCode === 9)
                return false;

            popolaProdotto(ui.item.data, autocomplete_selector.data("id"));

            return false;
        }
    }).on("keydown", function(event) {
        // Gestisci qui l'evento TAB
        if (event.keyCode === 9) {
            var menu = $(this).autocomplete("widget");
            if (menu.is(":visible")) {
                event.preventDefault(); // Previene il cambio di focus solo se il menu è visibile
                var items = menu.find("li");
                if (items.length > 0) {
                    selectedIndex = (selectedIndex + 1) % items.length;
                    items.removeClass("ui-state-focus"); // Rimuovi lo stato di focus dagli altri elementi
                    $(items[selectedIndex]).addClass("ui-state-focus"); // Applica lo stato di focus all'elemento corrente

                    // Opcional: aggiorna il valore dell'input con quello dell'elemento selezionato
                    // $(this).val(items.eq(selectedIndex).text());
                }
            }
        }

        if (event.keyCode === 13 && selectedIndex >= 0) {
            // ENTER è stato premuto e c'è un elemento selezionato
            $(this).autocomplete("close"); // Chiudi il menu di autocomplete
            var menu = $(this).autocomplete("widget");
            var item = menu.find("li").eq(selectedIndex).data("ui-autocomplete-item");
            console.log(item)
            if (item) {
                $(this).val(item.value); // Aggiorna il valore dell'input (se desiderato)
                // Simula la selezione dell'elemento come se l'utente avesse cliccato su di esso
                // Qui puoi chiamare `popolaProdotto` o qualsiasi altra logica necessaria
                console.log(item)
                popolaProdotto(item.data, autocomplete_selector.data("id"));
                return false;
            }
        }
    });
}

var popolaProdotto = function(prodotto, rowid) {
    //Add modal link to selected product
    const product_link_container = $("input[name='products[" + rowid + "][documenti_contabilita_articoli_name]']").closest('table').find('thead th span.js_modal_product_detail');
    const current_link = $("input[name='products[" + rowid + "][documenti_contabilita_articoli_name]']").closest('table').find('thead th span.js_modal_product_detail a.product_link_modal');
    const has_current_link = current_link.length === 0 ? false : true;
    const product_link_element = `<a href="${base_url}get_ajax/layout_modal/dettagli_prodotto_modale/${prodotto.fw_products_id}?_size=large" class="js_open_modal btn-xs btn-primary product_link_modal"><i class="fas fa-eye"></i></a>`;
    if (has_current_link) {
        product_link_container.empty();
    }
    product_link_container.append(product_link_element);


    console.log(prodotto['<?php echo $campo_preview; ?>']);
    var tipo_documento = $('.js_documenti_contabilita_tipo').val();

    var data = {
        "PA": <?php echo (int) ($this->auth->get('provvigione')); ?>,
        "RAW_DATA": prodotto
    };
    <?php if ($campo_codice): ?>
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_codice]']").val(prodotto['<?php echo $campo_codice; ?>']);

    <?php endif; ?>
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_codice_ean]']").val(prodotto['listino_prezzi_codice_ean_prodotto']);
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_codice_asin]']").val(prodotto['listino_prezzi_codice_asin_prodotto']);
    <?php if ($campo_unita_misura): ?>
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_unita_misura]']").val(prodotto['<?php echo $campo_unita_misura; ?>']);
    <?php endif; ?>

    <?php if ($campo_preview): ?>
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_name]']").val(prodotto['<?php echo $campo_preview; ?>']);

    <?php endif; ?>
    <?php if ($campo_descrizione): ?>
    $("textarea[name='products[" + rowid + "][documenti_contabilita_articoli_descrizione]']").html(prodotto['<?php echo $campo_descrizione; ?>']);
    <?php endif; ?>

    if (
        typeof cliente_raw_data !== 'undefined' &&
        typeof cliente_raw_data.customers_iva_default !== 'undefined' &&
        !isNaN(parseInt(cliente_raw_data.customers_iva_default)) &&
        parseInt(cliente_raw_data.customers_iva_default) > 0
    ) {

        var iva_id = parseInt(cliente_raw_data.customers_iva_default);
        $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option[value='" + iva_id + "']").prop('selected', true).attr('selected', 'selected').val(iva_id).trigger('change');
    } else {
        <?php if ($campo_iva): ?>
        $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option").removeAttr('selected').prop('selected', false);

        console.log(prodotto['<?php echo $campo_iva; ?>']);


        if (isNaN(parseInt(prodotto['<?php echo $campo_iva; ?>']))) {
            //$("select[name='products["+rowid+"][documenti_contabilita_articoli_iva_id]']").val('0');
        } else {

            $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option[value='" + parseInt(prodotto['<?php echo $campo_iva; ?>']) + "']").prop('selected', true).attr('selected', 'selected').val(parseInt(prodotto['<?php echo $campo_iva; ?>'])).trigger('change');

        }


        <?php endif; ?>
    }

    data.IV = $("select[name='products[" + rowid + "][documenti_contabilita_articoli_iva_id]'] option[selected]").data('perc');

    <?php if ($campo_provvigione): ?>
    data.PP = prodotto['<?php echo $campo_provvigione; ?>'];
    <?php else: ?>
    data.PP = 0;
    <?php endif; ?>

    <?php if ($campo_ricarico): ?>
    data.RP = prodotto['<?php echo $campo_ricarico; ?>'];
    <?php else: ?>
    data.RP = 0;
    <?php endif; ?>

    <?php if ($campo_sconto): ?>
    //Commento in quanto lo sconto prodotto viene sempre applicato
    //data.SP = prodotto['<?php echo $campo_sconto; ?>'];
    data.SP = 0;
    <?php else: ?>
    data.SP = 0;
    <?php endif; ?>

    <?php if ($campo_prezzo): ?>

    //Se il cliente ha un listino base associato, prendo il prezzo dalla price list di quel listino se presente
    if (typeof cliente_raw_data !== 'undefined' && typeof cliente_raw_data['customers_price_list'] !== 'undefined' && cliente_raw_data['customers_price_list']) {
        for (var i in prodotto.price_list) {
            if (prodotto.price_list[i].price_list_label == cliente_raw_data['customers_price_list']) {
                prodotto['<?php echo $campo_prezzo; ?>'] = prodotto.price_list[i].price_list_price;
            }
        }
    }
    prodotto['<?php echo $campo_prezzo; ?>'] = prodotto['<?php echo $campo_prezzo; ?>'].replace(',', '.');

    <?php if ($campo_prezzo_fornitore): ?>
    data.PF = prodotto['<?php echo $campo_prezzo_fornitore; ?>'];
    <?php endif; ?>

    if (tipo_documento == 6 && '<?php echo $campo_prezzo_fornitore; ?>' != '') { //Se è un ordine fornitore e ho impostato un campo prezzo_fornitore nei settings
        prodotto['<?php echo $campo_prezzo_fornitore; ?>'] = ricalcolaPrezzo(prodotto['<?php echo $campo_prezzo_fornitore; ?>'], prodotto).toString().replace(',', '.');
        console.log(prodotto['<?php echo $campo_prezzo_fornitore; ?>']);
    } else {
        prodotto['<?php echo $campo_prezzo; ?>'] = ricalcolaPrezzo(prodotto['<?php echo $campo_prezzo; ?>'], prodotto).toString().replace(',', '.');

    }
    data.PB = prodotto['<?php echo $campo_prezzo; ?>'];
    //PF,IV,SC,PA,PP,PB,PV,RP,SP
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_prezzo]']").val(parseFloat(applicaListino(data, tipo_documento)).toFixed(2)).trigger('change');

    <?php endif; ?>
    <?php if ($campo_sconto): ?>
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_sconto]']").val(prodotto['<?php echo $campo_sconto; ?>']).trigger('change');
    <?php endif; ?>
    <?php if ($campo_sconto2): ?>

    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_sconto2]']").val(prodotto['<?php echo $campo_sconto2; ?>']).trigger('change');
    <?php endif; ?>
    <?php if ($campo_sconto3): ?>
    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_sconto3]']").val(prodotto['<?php echo $campo_sconto3; ?>']).trigger('change');
    <?php endif; ?>

    <?php if ($campo_centro_costo): ?>
    $("select[name='products[" + rowid + "][documenti_contabilita_articoli_centro_costo_ricavo]'] option[value='" + parseInt(prodotto['<?php echo $campo_centro_costo; ?>']) + "']").prop('selected', true).attr('selected', 'selected').val(parseInt(prodotto['<?php echo $campo_centro_costo; ?>'])).trigger('change');
    <?php endif; ?>

    <?php if (!empty($campi_personalizzati)): ?>
    <?php foreach ($campi_personalizzati as $riga => $campi_personalizzati_riga): ?>
    <?php foreach ($campi_personalizzati_riga as $campo): ?>
    <?php if ($campo): ?>

    <?php if (in_array($campo['fields_draw_html_type'], ['select', 'select_ajax'])): ?>
    var sel = "select[name='products[" + rowid + "][<?php echo $campo['campi_righe_articoli_campo']; ?>]'] option[value=\"" + prodotto['<?php echo $campo['campi_righe_articoli_campo']; ?>'] + "\"]";
    //console.log(sel);
    $(sel).attr('selected', 'selected');
    $("select[name='products[" + rowid + "][<?php echo $campo['campi_righe_articoli_campo']; ?>]']").trigger('change');
    <?php else: ?>
    $("input[name='products[" + rowid + "][<?php echo $campo['campi_righe_articoli_campo']; ?>]']").val(prodotto['<?php echo $campo['campi_righe_articoli_campo']; ?>']).trigger('change');
    <?php endif; ?>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php endif; ?>


    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_prodotto_id]']").val(prodotto['<?php echo $campo_id; ?>']).trigger('change');

    $("input[name='products[" + rowid + "][documenti_contabilita_articoli_quantita]']").val(1).trigger('change');

    if (typeof afterPopolaProdotto === "function") {
        // This function exists
        afterPopolaProdotto(prodotto, rowid);
    }

    calculateTotals();
}


var cliente_raw_data;
var documento;
var autocomplete_anagrafiche;
$(document).ready(function() {

    <?php if ($documento_id): ?>
    documento = <?php echo json_encode($documento); ?>;
    <?php endif; ?>
    <?php if (($documento_id && $clone) || !$documento_id): ?>
    checkClienteCessato();
    <?php endif; ?>

    /****************** AUTOCOMPLETE Destinatario *************************/

    autocomplete_anagrafiche = "1,3,4"; // Di default cerca su customers type clienti, clienti/fornitori/leads

    $(".search_cliente").autocomplete({
        source: function(request, response) {
            var entity_anagrafiche = $('[name="dest_entity_name"]').val();
            $.ajax({
                method: 'post',
                url: base_url + "contabilita/documenti/autocomplete/" + entity_anagrafiche,
                dataType: "json",
                data: {
                    search: request.term,
                    type: autocomplete_anagrafiche,
                    [token_name]: token_hash
                },
                minLength: 0,
                /*search: function( event, ui ) {
                    loading(true);
                },*/
                success: function(data) {
                    var collection = [];
                    loading(false);

                    //                        if (data.count_total == 1) {
                    //
                    //                            popolaCliente(data.results.data[0]);
                    //                        } else {

                    $.each(data.results.data, function(i, p) {
                        // console.log(p);
                        // 2021-07-01 - michael e. - commento in quanto suppliers è stato unificato dentro customers con type 2
                        // if ($('[name="dest_entity_name"]').val() == '<?php echo $entita_clienti; ?>') {
                        var cliente_codice;

                        if (p.<?php echo $clienti_codice; ?> != null && p.<?php echo $clienti_codice; ?>.length != 0) {
                            cliente_codice = p.<?php echo $clienti_codice; ?> + ' - ';
                        } else {
                            cliente_codice = '';
                        }
                        var cliente_tipo = 'C';
                        if (p.customers_type == 2) {
                            cliente_tipo = 'F';
                        }
                        if (p.customers_type == 3) {
                            cliente_tipo += '/F';
                        }
                        if (typeof p.<?php echo $clienti_ragione_sociale; ?> !== 'undefined' && p.<?php echo $clienti_ragione_sociale; ?> !== null && p.<?php echo $clienti_ragione_sociale; ?> !== '') {
                            collection.push({
                                "id": p.<?php echo $clienti_id; ?>,
                                "label": '[' + cliente_tipo + '] ' + cliente_codice + p.<?php echo $clienti_ragione_sociale; ?>,
                                "value": cliente_codice + p.<?php echo $clienti_ragione_sociale; ?>,
                                "data": p
                            });
                        } else {
                            collection.push({
                                "id": p.<?php echo $clienti_id; ?>,
                                "label": '[' + cliente_tipo + '] ' + cliente_codice + p.<?php echo $clienti_nome; ?> + ' ' + p.<?php echo $clienti_cognome; ?>,
                                "value": cliente_codice + p.<?php echo $clienti_nome; ?> + ' ' + p.<?php echo $clienti_cognome; ?>,
                                "data": p
                            });
                        }

                        // } else {
                        //     collection.push({
                        //         "id": p.<?php echo $clienti_id; ?>,
                        //         "label": p.suppliers_business_name,
                        //         "value": p
                        //     });
                        // }
                    });
                    //                        }

                    //console.log(collection);
                    response(collection);
                }
            });
        },
        minLength: 3,
        //            focus: function (event, ui) {
        //                return false;
        //            },
        select: function(event, ui) {

            console.log('inside select');

            // fix per disabilitare la ricerca con il tab
            if (event.keyCode === 9)
                return false;

            //console.log(ui.item.value);

            // 2021-07-01 - michael e. - commento l'if e lascio solo "popolaCliente" in quanto suppliers è stato unificato dentro customers con type 2
            // if ($('[name="dest_entity_name"]').val() == '<?php echo $entita_clienti; ?>') {
            popolaCliente(ui.item.data);
            // } else {
            // popolaFornitore(ui.item.value);
            // }

            //Crea link per aprire dettaglio cliente in nuova scheda
            const customers_id = ui.item.data.customers_id;
            const btn_dettaglio_customer = $('#btn_dettaglio_customer');
            // if (btn_dettaglio_customer.length != 0) {
            //     console.log('devo modificare attr href con nuovo id cliente');
            //     btn_dettaglio_customer.attr("href", "<?php echo base_url('main/layout/customer-detail/'); ?>" + customers_id);
            // } else {
            //     $('.js_dest_type').after('<a href="<?php echo base_url('main/layout/customer-detail/'); ?>' + customers_id + '" target="_blank" class="btn btn-primary btn-xs pull-right" id="btn_dettaglio_customer">Vai all\'anagrafica</strong>');
            // }
            checkClienteCessato();

            $('#btn_dettaglio_customer').show();
            $('#btn_dettaglio_customer').attr("href", "<?php echo base_url('main/layout/customer-detail/'); ?>" + customers_id);

            //drawProdotto(ui.item.value, true);
            return false;
        }
    });

    function checkClienteCessato() {
        var customer_id = $('#js_dest_id').val();
        if (customer_id) {
            var entity_anagrafiche = $('[name="dest_entity_name"]').val();
            $.ajax({
                method: 'post',
                url: base_url + 'get_ajax/getJsonRecord/' + entity_anagrafiche + '/' + customer_id,
                dataType: "json",
                data: {
                    [token_name]: token_hash
                },
                async: false,
                success: function(res) {
                    console.log(res);
                    if (res.data.customers_status === '3') {
                        alert("Attenzione: il destinatario risulta cessato.");
                    }
                    /*$("input[name='leads_mail_object']").val(res.data.mailer_template_subject);
                    $("input[name='leads_mail_from']").val(res.data.mailer_smtp_from_email);
                    //$("input[name='leads_mail_from_name']").val(res.data.mailer_smtp_from_name);

                    fillEditor("textarea[name='leads_mail_text']", res.data.mailer_template_body);*/
                }
            })
        }
        /*$.ajax({
                method: 'post',
                url: base_url + "contabilita/documenti/autocomplete/" + entity_anagrafiche,
                dataType: "json",
                data: {
                    search: request.term,
                    type: autocomplete_anagrafiche,
                    [token_name]: token_hash
                },
                minLength: 0,
                /*search: function( event, ui ) {
                    loading(true);
                },
                success: function(data) {
                }*/


    }

    function showAddressButton() {
        var customer_id = $('#js_dest_id').val();
        if (customer_id) {
            $('.js_choose_address').attr('href', base_url + 'get_ajax/modal_layout/customer-shipping-address/' + customer_id + '?_size=extra&from_contabilita=1').show();
        } else {
            $('.js_choose_address').hide();
        }

    }

    function compilaSede(shipping_data) {



        var indirizzo = (shipping_data.customers_shipping_address_name ? shipping_data.customers_shipping_address_name : shipping_data.customers_company);
        indirizzo += '\n';
        indirizzo += (shipping_data.customers_shipping_address_street ?? '');
        indirizzo += '\n';
        indirizzo += shipping_data.customers_shipping_address_city + ' ' + (shipping_data.customers_shipping_address_zip_code ?? '');
        indirizzo += '\n';
        if (shipping_data.countries_name) {
            indirizzo += shipping_data.countries_name;
            indirizzo += '\n';
        } else if (shipping_data.customers_shipping_address_country) {
            indirizzo += shipping_data.customers_shipping_address_country;
            indirizzo += '\n';
        }
        if (shipping_data.customers_shipping_address_mobile) {
            indirizzo += shipping_data.customers_shipping_address_mobile;
            indirizzo += '\n';
        }
        if (shipping_data.customers_shipping_address_phone) {
            indirizzo += shipping_data.customers_shipping_address_phone;
        }

        $('[name="documenti_contabilita_luogo_destinazione"]').html(indirizzo);
        $('[name="documenti_contabilita_luogo_destinazione_id"]').val(shipping_data.customers_shipping_address_id);

        if (!$('.js_fattura_accompagnatoria_checkbox').is(':checked')) {
            $('.js_fattura_accompagnatoria_checkbox').trigger('click');
        } else if ($('.js_fattura_accompagnatoria_checkbox').is(':checked') && $('.js_fattura_accompagnatoria_row').hasClass('hide')) {
            $('.js_fattura_accompagnatoria_row').removeClass('hide');
        }
    }

    $('body').on('click', '.js_shipping_address_choose', function() {

        var shipping_data_base64 = atob($(this).data('shipping_data'));

        var shipping_data = JSON.parse(shipping_data_base64);


        compilaSede(shipping_data);

        $('#myModal').modal('toggle');
    });

    var popolaCliente = function(cliente) {
        cliente_raw_data = cliente;
        $('.js_cliente_container').data('cliente', cliente);
        //Cambio la label
        $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

        if (cliente['customers_template_pagamento'] != undefined) {
            $('[name="documenti_contabilita_template_pagamento"]').val(cliente['customers_template_pagamento']).trigger('change');
            //$('[data-name="documenti_contabilita_scadenze_saldato_con"]').val(metodi_pagamenti_map[cliente['documenti_contabilita_metodi_pagamento_valore']]);
        }


        <?php if (!empty($clienti_sottotipo)): ?>
        if (cliente['<?php echo $clienti_sottotipo; ?>']) {
            var sottotipo = 2;

            switch (cliente['<?php echo $clienti_sottotipo; ?>']) {
                case '1':
                    sottotipo = 2; //privato
                    break;
                case '2':
                case '3':
                    sottotipo = 1; // azienda
                    break;
                case '4':
                    sottotipo = 3; // pa
                    break;
            }

            $('.js_tipo_destinatario[value="' + sottotipo + '"]').trigger('click');
        }
        <?php endif; ?>

        if (typeof cliente['<?php echo $clienti_ragione_sociale; ?>'] !== 'undefined' && cliente['<?php echo $clienti_ragione_sociale; ?>'] !== null && cliente['<?php echo $clienti_ragione_sociale; ?>'] !== '') {
            $('.js_dest_ragione_sociale').val(cliente['<?php echo $clienti_ragione_sociale; ?>']);
        } else {
            $('.js_dest_ragione_sociale').val(cliente['<?php echo $clienti_nome; ?>'] + ' ' + cliente['<?php echo $clienti_cognome; ?>']);
        }

        $('.js_dest_codice').val(cliente['<?php echo $clienti_codice; ?>']);

        $('.js_dest_indirizzo').val(cliente['<?php echo $clienti_indirizzo; ?>']);


        $('.js_dest_citta').val(cliente['<?php echo $clienti_citta; ?>']);



        <?php if (!empty($clienti_nazione)): ?>

        <?php if (!empty($mappature_autocomplete['clienti_nazione']) && $mappature_autocomplete['clienti_nazione'] != $clienti_nazione): ?>
        $('.js_dest_nazione').val(cliente['<?php echo $mappature_autocomplete['clienti_nazione']; ?>']).trigger('change');
        <?php else: ?>
        $('.js_dest_nazione').val(cliente['<?php echo $clienti_nazione; ?>']).trigger('change');
        <?php endif; ?>
        <?php endif; ?>



        $('.js_dest_cap').val(cliente['<?php echo $clienti_cap; ?>']);

        $('.js_dest_provincia').val(cliente['<?php echo $clienti_provincia; ?>']);

        <?php if (!empty($clienti_partita_iva)): ?>
        $('.js_dest_partita_iva').val(cliente['<?php echo $clienti_partita_iva; ?>']);
        <?php endif; ?>

        $('.js_dest_codice_fiscale').val(cliente['<?php echo $clienti_codice_fiscale; ?>']);
        <?php if (!empty($clienti_codice_sdi)): ?>
        if (cliente['<?php echo $clienti_codice_sdi; ?>']) {
            $('.js_dest_codice_sdi').val(cliente['<?php echo $clienti_codice_sdi; ?>']);
        }
        <?php endif; ?>
        <?php if (!empty($clienti_pec)): ?>
        $('.js_dest_pec').val(cliente['<?php echo $clienti_pec; ?>']);
        <?php endif; ?>
        $('#js_dest_id').val(cliente['<?php echo $clienti_id; ?>']).trigger('change');

        <?php if (!empty($clienti_vettore)): ?>
        if (cliente['<?php echo $clienti_vettore; ?>']) {
            $('[name="documenti_contabilita_trasporto_a_cura_di"]').val(cliente['<?php echo $clienti_vettore; ?>']);

            <?php if (!empty($entita_vettori)): ?>
            $.ajax({
                url: base_url + 'contabilita/documenti/autocomplete/vettori',
                type: 'post',
                dataType: 'json',
                data: {
                    [token_name]: token_hash,
                    search: cliente['<?php echo $clienti_vettore; ?>'],
                    type: 'match'
                },
                success: function(res) {
                    // console.log(res);
                    if (res.results.data.length > 0) {
                        const data = res.results.data;
                        // console.log(data);
                        if (data[0]) {
                            const vettore = data[0];

                            // console.log(vettore);

                            popolaVettore(vettore);
                        }
                    }
                },
                error: function(status, request, error) {
                    console.error(error);
                }
            })
            <?php endif; ?>

            if (!$('.js_fattura_accompagnatoria_checkbox').is(':checked')) {
                $('.js_fattura_accompagnatoria_checkbox').trigger('click');
            }
        }
        <?php endif; ?>

        // gestisco il flag del campo $('[name="documenti_contabilita_split_payment"]') in base al campo customers_split se è 1
        if (cliente_raw_data.customers_split && cliente_raw_data.customers_split == 1) {
            $('[name="documenti_contabilita_split_payment"]').prop('checked', true);
        } else {
            $('[name="documenti_contabilita_split_payment"]').prop('checked', false);
        }

        //Cambio iva default sulle righe prodotto
        if (typeof cliente_raw_data !== 'undefined' &&
            typeof cliente_raw_data.customers_iva_default !== 'undefined' &&
            !isNaN(parseInt(cliente_raw_data.customers_iva_default)) &&
            parseInt(cliente_raw_data.customers_iva_default) > 0
        ) {
            //alert(1);
            var iva_id = parseInt(cliente_raw_data.customers_iva_default);
            $(".js_documenti_contabilita_articoli_iva_id option[value='" + iva_id + "']").prop('selected', true).attr('selected', 'selected').val(iva_id).trigger('change');
        }
        if (typeof cliente['customers_sconto_abituale'] !== undefined && cliente['customers_sconto_abituale'] > 0) {
            $('.js_sconto_totale').val(cliente['customers_sconto_abituale']).trigger('change');
        }

        <?php if (!empty($clienti_agente_vendita)): ?>
        if (cliente['<?php echo $clienti_agente_vendita; ?>']) {
            $('.js_agente_vendita').val(cliente['<?php echo $clienti_agente_vendita; ?>']).trigger('change');
        }
        <?php endif; ?>
        var tipo_documento = $('[name="documenti_contabilita_tipo"]').val();
        if (tipo_documento != "10") {


            showAddressButton();

            if (typeof cliente['sedi'] !== 'undefined') {
                var tipo_documento = $('.js_documenti_contabilita_tipo').val();

                if (cliente['sedi'].length == 1) {
                    var confirmed = false;
                    // console.log(movimenta_per);
                    // alert(1);
                    if (movimenta_per.includes(parseInt(tipo_documento)) && confirm('Ho trovato una sede per questo cliente, vuoi inserirla nei dati trasporto?')) {
                        confirmed = true;
                        compilaSede(cliente['sedi'][0]);
                    }

                } else if (cliente['sedi'].length > 1) {
                    var confirmed = false;

                    if (movimenta_per.includes(parseInt(tipo_documento)) && confirm('Ho trovato più sedi per questo cliente, vuoi inserirne una nei dati trasporto?')) {
                        confirmed = true;
                    }

                    if (confirmed) {
                        $('.js_choose_address').trigger('click');
                    }
                } else {
                    // no sedi trovate, faccio niente
                }
            }
        }
        if (typeof afterPopolaCliente === "function") {
            // This function exists
            afterPopolaCliente(cliente);
        }

        if (typeof afterPopolaCliente === "function") {
            // This function exists
            afterPopolaCliente(cliente);
        }

        applicaListino(false, false);
        applicaMetodoPagamento();

        //Sostituisco le select delle commesse
        if (cliente.hasOwnProperty('commesse') && cliente.commesse.length > 0) {
            var commesse = cliente.commesse;
            $('.js_documenti_contabilita_articoli_commessa').each(function() {
                //alert(1);
                var select = $(this);
                var selected = select.val();
                select.empty();
                select.append('<option value="">----</option>');
                for (var i in commesse) {
                    var commessa = commesse[i];
                    select.append('<option value="' + commessa.projects_id + '">' + commessa.projects_name + '</option>');
                }
                select.val(selected);
            });

        }

    }

    function popolaFornitore(fornitore) {
        //Cambio la label
        $('#js_label_rubrica').html('Modifica e sovrascrivi anagrafica');

        $('[name="documenti_contabilita_template_pagamento"]').val(fornitore['customers_template_pagamento']);

        $('.js_dest_nazione').val(fornitore['suppliers_country']);


        $('.js_dest_codice').val(fornitore['suppliers_code']);
        $('.js_dest_ragione_sociale').val(fornitore['suppliers_business_name']);
        $('.js_dest_indirizzo').val(fornitore['suppliers_address']);
        $('.js_dest_citta').val(fornitore['suppliers_city']);
        $('.js_dest_cap').val(fornitore['suppliers_zip_code']);
        $('.js_dest_provincia').val(fornitore['suppliers_province']);
        $('.js_dest_partita_iva').val(fornitore['suppliers_vat_number']);
        $('.js_dest_codice_fiscale').val(fornitore['suppliers_cf']);
        $('#js_dest_id').val(fornitore['suppliers_id']);
    }

    //20190712 - Matteo - Sbagliato! Non passa il data('id')... modifico con un foreach
    //initAutocomplete($('.js_autocomplete_prodotto'));
    $('.js_autocomplete_prodotto').each(function() {
        initAutocomplete($(this));
    });

    $('.js_select2').each(function() {
        var select = $(this);
        var placeholder = select.attr('data-placeholder');
        select.select2({
            placeholder: placeholder ? placeholder : '',
            allowClear: true
        });
    });

    <?php if ($documento_id || !empty($documento['articoli'])): ?>
    calculateTotals(<?php echo (!$clone) ? $documento_id : ''; ?>);

    cliente_raw_data = documento;
    $('#js_dest_id').filter(function() {
        return !this.value;
    }).trigger('change');

    showAddressButton();
    setTimeout(function() {
        applicaListino(false, false);
    }, 1000);



    <?php endif; ?>

    <?php if (!empty($customer)): ?>
    cliente_raw_data = <?php echo json_encode($customer); ?>;
    popolaCliente(cliente_raw_data);
    <?php endif; ?>
});
</script>

<script>
$(document).ready(function() {
    var tipo_documento = $('.js_documenti_contabilita_tipo').val();

    $('.js_btn_tipo').click(function(e) {
        var documento_id = <?php echo (!empty($documento_id) ? $documento_id : 0); ?>;
        var tipo = $(this).data('tipo');
        var clone = '<?php echo (!empty($this->input->get('clone')) && $this->input->get('clone') == '1') ? "1" : "0"; ?>';
        //$(".js_template_pdf option[data-tipo='" + tipo + "']").prop("selected", true);
        if (documento_id == 0 || clone == 1) {
            $(".js_template_pdf option").each(function() {
                if ($(this).attr('data-tipo') == tipo) {
                    $(this).prop('selected', true);
                    return false;
                }
            })
        }

        //Cambio eventuali label
        $('.scadenze_box').show();
        $('.js_rivalsa_container').show();

        if (tipo == 12) {
            alert('Indicare gli importi col segno meno davanti!');
        }

        switch (tipo) {
            case 11: // Fattura reverse
                $('.js_dest_type').html('fornitore');
                autocomplete_anagrafiche = "1,3,4";
                $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');

                //Toglie check da formato elettronico e nasconde campo
                $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').show();
                $.uniform.update();
                $('.js_tipologia_fatturazione').val('<?php if (!empty($documento['documenti_contabilita_tipologia_fatturazione'])): ?><?php echo $documento['documenti_contabilita_tipologia_fatturazione']; ?><?php else: ?>7<?php endif; ?>');
                $(".js_tipologia_fatturazione option").removeAttr("disabled");
                $(".js_tipologia_fatturazione option[data-tipologia_genitore*='" + 4 + "']").attr("disabled", "true"); // Mostra tutte le tipologie che non siano la 4 ovvero la nota di credito

                <?php if ($documento_id && $clone): ?>
                calculateTotals('<?php echo $documento_id ?>', true);
                <?php endif; ?>
                break;
            case 12: // Nota di credito Reverse
                $('.js_dest_type').html('fornitore');
                autocomplete_anagrafiche = "1,3,4";
                $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');

                //Toglie check da formato elettronico e nasconde campo
                $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').show();
                $.uniform.update();

                $('.js_tipologia_fatturazione').val('4');
                $(".js_tipologia_fatturazione option").removeAttr("disabled");
                $(".js_tipologia_fatturazione option[data-tipologia_genitore]:not([data-tipologia_genitore*='" + 4 + "'])").attr("disabled", "true");

                $('.js_rivalsa_container').show();

                <?php if ($documento_id && $clone): ?>
                calculateTotals('<?php echo $documento_id ?>', true);
                <?php endif; ?>
                break;
            case 6: //Ordine fornitore
            case 10: //DDT fornitore
                $('.js_dest_type').html('fornitore');
                autocomplete_anagrafiche = "2,3";
                $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                //if (tipo_documento != tipo) {
                if ($('.js_tipologia_fatturazione').is(':visible')) {
                    $('.js_tipologia_fatturazione').parent().hide();
                }

                $('.js_tipologia_fatturazione').val('');
                //Toglie check da formato elettronico e nasconde campo
                $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').hide();
                $.uniform.update();
                //}
                $('.real_rivalsa').hide();
                break;
            case 3: //Pro forma
                $('.js_dest_type').html('cliente');
                autocomplete_anagrafiche = "1,3,4";
                if (tipo_documento != tipo) {
                    if ($('.js_tipologia_fatturazione').is(':visible')) {
                        $('.js_tipologia_fatturazione').parent().hide();
                    }
                    $('.js_tipologia_fatturazione').val('');
                    //Toglie check da formato elettronico e nasconde campo
                    $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                    $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').hide();
                    $.uniform.update();
                }
                break;
            case 1: //Fattura
                $('.js_dest_type').html('cliente');

                $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                if (tipo_documento != tipo) {
                    //$('.js_tipologia_fatturazione').val('1').trigger('change');
                    //Toglie check da formato elettronico e nasconde campo
                    $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                    $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').show();
                    $.uniform.update();
                }
                //Attenzione!!! Questo break è stato rimosso quindi il codice prosegue dopo!!!!
                //break;
                case 2:
                    $('.js_dest_type').html('cliente');

                    $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                    if (tipo_documento != tipo<?php if (!$documento_id): ?> || true<?php endif; ?>) {
                        if ($('.js_tipologia_fatturazione').is(':hidden')) {
                            $('.js_tipologia_fatturazione').parent().show();
                        }
                        $('.js_tipologia_fatturazione').prop('disabled', false).val('1');
                        $(".js_tipologia_fatturazione option").removeAttr("disabled");
                        $(".js_tipologia_fatturazione option[data-tipologia_genitore]:not([data-tipologia_genitore*='" + 1 + "'])").attr("disabled", "true");
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                        $.uniform.update();
                    }
                    if (tipo == 1) {
                        autocomplete_anagrafiche = "1,3,4";
                    } else {
                        autocomplete_anagrafiche = "1,2,3,4";
                    }

                    var tipo_fatturazione_default = '<?php echo $impostazioni['documenti_contabilita_settings_tipo_fattura_default'] ?? ''; ?>';

                    <?php if (!$documento_id && empty($documento)): ?>
                    if (tipo_fatturazione_default.length > 0) {
                        $('.js_tipologia_fatturazione').val(tipo_fatturazione_default).trigger('change');
                    }
                    <?php endif; ?>

                    <?php if ($documento_id && $clone): ?>
                    calculateTotals('<?php echo $documento_id ?>', true);
                    <?php endif; ?>
                    break;
                case 4: //Nota di credito
                    $('.js_dest_type').html('cliente');
                    autocomplete_anagrafiche = "1,3,4";
                    $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                    if (tipo_documento != tipo<?php if (!$documento_id): ?> || true<?php endif; ?>) {
                        if ($('.js_tipologia_fatturazione').is(':hidden')) {
                            $('.js_tipologia_fatturazione').parent().show();
                        }
                        $('.js_tipologia_fatturazione').val('4');
                        $(".js_tipologia_fatturazione option").removeAttr("disabled");
                        $(".js_tipologia_fatturazione option[data-tipologia_genitore]:not([data-tipologia_genitore*='" + 4 + "'])").attr("disabled", "true");

                        //Rimette cheeck e mostra campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', true);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').show();
                        $.uniform.update();
                    }
                    break;
                case 7: //Preventivo
                    autocomplete_anagrafiche = "1,2,3,4";
                    if (tipo_documento != tipo) {
                        if ($('.js_tipologia_fatturazione').is(':visible')) {
                            $('.js_tipologia_fatturazione').parent().hide();
                        }
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').hide();
                        $.uniform.update();
                    }

                    $('.js_tipologia_fatturazione').prop('disabled', false).val('');
                    $('.js_dest_type').html('cliente');
                    $('.scadenze_box').show();
                    $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                    break;
                case 12:
                case 5: //Ordine cliente
                    if (tipo_documento != tipo) {
                        if ($('.js_tipologia_fatturazione').is(':visible')) {
                            $('.js_tipologia_fatturazione').parent().hide();
                        }
                        $('.js_tipologia_fatturazione').val('');
                        //Nascondo blocco scadenze
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').hide();
                        $.uniform.update();
                    }
                    $('.js_dest_type').html('cliente');
                    // $('.scadenze_box').hide(); // michael - 18/04/2023 - Si è deciso di mostrare sempre il box delle scadenze
                    $('[name="dest_entity_name"]').val('<?php echo $entita_clienti; ?>');
                    $('.real_rivalsa').hide();
                    break;
                case 8: //DDT cliente
                    if ($('.js_tipologia_fatturazione').is(':visible')) {
                        $('.js_tipologia_fatturazione').parent().hide();
                    }
                    $('.js_tipologia_fatturazione').val('');
                    // $('.scadenze_box').hide();
                    if (tipo_documento != tipo) {
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').hide();
                        $.uniform.update();
                    }
                    autocomplete_anagrafiche = "1,2,3,4";

                    $('.js_dest_type').html('cliente');
                    $('.real_rivalsa').hide();
                    break;
                default:
                    if (tipo_documento != tipo) {
                        //Toglie check da formato elettronico e nasconde campo
                        $('[name=documenti_contabilita_formato_elettronico]').prop('checked', false);
                        $('[name=documenti_contabilita_formato_elettronico]').closest('.form-group').hide();
                        $.uniform.update();
                    }
                    break;
        }

        <?php if ($documento_id && $clone): ?>
        <?php if (in_array($documento['documenti_contabilita_tipo'], [8, 10]) && dateFormat($documento['documenti_contabilita_data_emissione'], 'Y-m-d') !== date('Y-m-d')): ?>
        if (tipo == '1') {
            if (confirm('Stai generando una fattura da un ddt.\nVuoi impostare automaticamente la tipologia "TD24 - Fattura differita?')) {
                $('.js_tipologia_fatturazione').val('15').trigger('change');
            }
        }
        <?php endif; ?>
        <?php endif; ?>

        $('.js_btn_tipo').removeClass('btn-primary');
        $('.js_btn_tipo').addClass('btn-default');
        $(this).addClass('btn-primary');
        $(this).removeClass('btn-default');
        $('.js_documenti_contabilita_tipo').val(tipo).trigger('change');
        if (tipo_documento != tipo) {
            getNumeroDocumento();
        }
        tipo_documento = tipo;
        //getNumeroDocumento();
    });

    $('.js_btn_tipo[data-tipo="' + $('.js_documenti_contabilita_tipo').val() + '"]').trigger('click');

});
$('.documenti_contabilita_valuta').on('change', function() {

    //Se il tasso di cambio è uguale al default, nascondo il tasso di cambio perchè non ha senso
    if ($('option:selected', $(this)).data('id') == '<?php echo $impostazioni['documenti_contabilita_settings_valuta_base']; ?>') {
        $('.documenti_contabilita_tasso_di_cambio').parent().hide();
    } else {
        //Ajax per chiedere il tasso di cambio
        $.ajax({
            method: 'get',
            dataType: "json",
            url: base_url + "contabilita/documenti/tassoDiCambio/" + $('option:selected', $(this)).data('id'),
            success: function(data) {
                $('.documenti_contabilita_tasso_di_cambio').val(data.tassi_di_cambio_tasso);
                //console.log(data);
            }
        });
        $('.documenti_contabilita_tasso_di_cambio').parent().show();
    }
});
$('.documenti_contabilita_valuta').trigger('change');


function getNumeroAjax(tipo, serie) {
    var azienda = encodeURIComponent($('.documenti_contabilita_azienda').val());
    var tipoDocumento = encodeURIComponent($('.js_documenti_contabilita_tipo').val());
    var serieDocumento = $('.js_documenti_contabilita_serie').val();
    serieDocumento = serieDocumento ? '?serie=' + encodeURIComponent(serieDocumento) : '';

    $.ajax({
        method: 'post',
        data: {
            data_emissione: $('[name="documenti_contabilita_data_emissione"]').val(),
            [token_name]: token_hash
        },
        url: base_url + "contabilita/documenti/numeroSucessivo/" + azienda + '/' + tipoDocumento + '/' + serieDocumento,
        success: function(numero) {
            $('[name="documenti_contabilita_numero"]').val(numero);
        }
    });
}

function getNumeroDocumento(ignore_change_ricavo = false) {
    var is_modifica = !isNaN($('[name="documento_id"]').val());
    var is_clone = <?php echo $this->input->get('clone') == 1 ? 'true' : 'false'; ?>;
    var tipo = $('.js_btn_tipo.btn-primary').data('tipo');
    var serie = $('.js_btn_serie.button_selected').data('serie');
    if (is_modifica) {
        if (tipo == '<?php echo (empty($documento['documenti_contabilita_tipo'])) ? 'XXX' : $documento['documenti_contabilita_tipo']; ?>' && serie == '<?php echo (empty($documento['documenti_contabilita_serie'])) ? 'XXX' : $documento['documenti_contabilita_serie']; ?>') {
            $('[name="documenti_contabilita_numero"]').val(<?php echo (!empty($documento['documenti_contabilita_numero'])) ? $documento['documenti_contabilita_numero'] : ''; ?>);
        } else {
            getNumeroAjax(tipo, serie);
        }
    } else {
        getNumeroAjax(tipo, serie);
    }

    if (typeof ignore_change_ricavo != 'undefined' && ignore_change_ricavo === false) {
        // michael - 2024-01-23 - associazione centro di costo a serie. gestisco quindi il cambio del centro di costo in base alla serie selezionata
        var serie_centro_costo_ricavo = $('.js_btn_serie.button_selected').data('centro_costo_ricavo');

        if (typeof serie_centro_costo_ricavo !== 'undefined' && serie_centro_costo_ricavo !== false && serie_centro_costo_ricavo !== '' && !is_clone) {
            $('[name="documenti_contabilita_centro_di_ricavo"]').val(serie_centro_costo_ricavo).trigger('change');
        }
    }
    
}

function reloadTemplatePdf() {
    $.ajax({
        method: 'get',
        //type: 'json',
        url: base_url + "contabilita/documenti/getTemplatePdf/" + $('.documenti_contabilita_azienda').val(),
        success: function(templates) {
            $('[name="documenti_contabilita_template_pdf"] option').each(function() {
                if ($(this).val()) {
                    $(this).remove();
                }
            });
            templates = JSON.parse(templates);
            for (var i in templates) {
                $('[name="documenti_contabilita_template_pdf"]').append('<option value="' + templates[i].documenti_contabilita_template_pdf_id + '">' + templates[i].documenti_contabilita_template_pdf_nome + '</option>');
            }
        }
    });
}

function getAzienda() {
    var azienda;
    $.ajax({
        url: base_url + 'contabilita/documenti/get_azienda/' + $('.documenti_contabilita_azienda').val(),
        type: 'get',
        dataType: 'json',
        async: false,
        success: function(response) {
            if (response.status == '0') {
                alert('Errore: ' + response.txt);

                return false;
            }

            azienda = response.txt;
        },
        error: function(status, request, error) {
            alert('Errore: ' + error);

            return false;
        }
    });

    return azienda;
}

function reloadIvaDefault(azienda_iva_id) {
    if (!confirm("Vuoi applicare l'iva default di questa azienda su tutte le righe articolo?")) {
        return false;
    }

    $.each($('.js_documenti_contabilita_articoli_iva_id'), function() {
        $(this).val(azienda_iva_id).trigger('change');
    });
}

$('.js_btn_serie').click(function(e) {
    if ($(this).hasClass('button_selected')) {
        $('.js_btn_serie').removeClass('button_selected');
        $('.js_documenti_contabilita_serie').val('');
        $('[name="documenti_contabilita_centro_di_ricavo"]').val('').trigger('change');
    } else {
        $('.js_btn_serie').removeClass('button_selected');
        $(this).addClass('button_selected');

        $('.js_documenti_contabilita_serie').val($(this).data('serie'));
    }
    getNumeroDocumento();
});
$('[name="documenti_contabilita_data_emissione"]').on('change', function() {
    if (!confirm("Stai cambiando la data emissione.\nVuoi ricalcolare il numero progressivo?")) {
        return false;
    }

    getNumeroDocumento(true);
});

$('.documenti_contabilita_azienda').on('change', function() {
    const azienda = getAzienda();
    getNumeroDocumento();

    reloadTemplatePdf();

    if (azienda) {
        if (azienda.documenti_contabilita_settings_iva_default) {
            reloadIvaDefault(azienda.documenti_contabilita_settings_iva_default);
        }

        if (azienda.documenti_contabilita_settings_tipo_cassa_professionisti) {
            $('[name="documenti_contabilita_cassa_professionisti_tipo"]').val(azienda.documenti_contabilita_settings_tipo_cassa_professionisti).trigger('change');
        }

        if (azienda.documenti_contabilita_settings_perc_cassa_prof) {
            $('[name="documenti_contabilita_cassa_professionisti_perc"]').val(azienda.documenti_contabilita_settings_perc_cassa_prof);
        }
    }
});

if (!$('.js_documenti_contabilita_tipo').val()) {
    $('.js_btn_tipo').first().trigger('click');
}
$('[name="documenti_contabilita_data_emissione"]').on('change', function() {
    //getNumeroDocumento();
    <?php if ($documento_id): ?>
    calculateTotals('<?php echo (!$clone) ? $documento_id : ''; ?>', true);
    <?php else: ?>
    calculateTotals(false, true);
    <?php endif; ?>

});

<?php if (empty($documento['documenti_contabilita_numero']) || $clone): ?>
$('.js_btn_tipo[data-tipo="' + $('.js_documenti_contabilita_tipo').val() + '"]').trigger('click');
getNumeroDocumento();
//alert(1);
//$('.js_btn_serie').first().trigger('click');
<?php endif; ?>


var totale = 0;
var totale_iva = 0;
var competenze = 0;
var competenze_scontate = 0;
var competenze_no_ritenute = 0;
var iva_perc_max = 0;
var rivalsa_inps_percentuale = 0;
var rivalsa_inps_valore = 0;

var competenze_con_rivalsa = 0;

var cassa_professionisti_perc = 0;
var cassa_professionisti_valore = 0;

var imponibile = 0;

var ritenuta_acconto_perc = 0;
var ritenuta_acconto_perc_sull_imponibile = 0;

function reverseRowCalculate(tr) {
    //Calcolo gli importi basandomi sul totale...
    var qty = parseFloat($('.js_documenti_contabilita_articoli_quantita', tr).val());
    var sconto = parseFloat($('.js_documenti_contabilita_articoli_sconto', tr).val());
    var sconto2 = parseFloat($('.js_documenti_contabilita_articoli_sconto2', tr).val());
    var sconto3 = parseFloat($('.js_documenti_contabilita_articoli_sconto3', tr).val());
    var iva = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', tr).data('perc'));

    if (isNaN(qty)) {
        qty = 0;
    }
    if (isNaN(sconto)) {
        sconto = 0;
    }
    if (isNaN(sconto2)) {
        sconto2 = 0;
    }

    if (isNaN(sconto3)) {
        sconto3 = 0;
    }
    if (isNaN(iva)) {
        iva = 0;
    }

    var importo_ivato = parseFloat($('.js-importo', tr).val());

    //Applico lo sconto al rovescio
    var importo = parseFloat(importo_ivato / ((100 + iva) / 100));
    var importo_ricalcolato = (importo_ivato - ((importo_ivato / 100) * sconto));
    importo_ricalcolato = importo_ricalcolato / 100 * (100 - sconto2);
    importo_ricalcolato = parseFloat(importo_ricalcolato / 100 * (100 - sconto3));


    //console.log(importo);

    $('.js-importo', tr).val(importo_ricalcolato.toFixed(2));
    $('.js_documenti_contabilita_articoli_prezzo', tr).val(importo.toFixed(2));
    //
    calculateTotals();
}

var generaScadenze = function(totale, totale_iva) {

};

var applicaListino = function(data, tipo_documento) {
    //PF,IV,SC,PA,PP,PB,PV,RP,SP
    // console.log(data);


    if (tipo_documento == 6) { // fornitore
        return data.PF;
    } else {
        return data.PP;
    }


};

var applicaMetodoPagamento = function() {
    //console.log(template_scadenze);
    //alert(1);
    var template_pagamento_id = cliente_raw_data.customers_template_pagamento;

    for (var i in template_scadenze) { //Ciclo l'array delle scadenze (configurate a monte)
        var template_scadenza = template_scadenze[i];
        if (template_scadenza.documenti_contabilita_template_pagamenti_id == template_pagamento_id) {
            //var prima_scadenza = template_scadenza.documenti_contabilita_tpl_pag_scadenze.pop();
            var prima_scadenza = template_scadenza.documenti_contabilita_tpl_pag_scadenze[template_scadenza.documenti_contabilita_tpl_pag_scadenze.length - 1];
            //console.log(prima_scadenza);
            $('[name="documenti_contabilita_metodo_pagamento"]').val(prima_scadenza.documenti_contabilita_metodi_pagamento_valore);
        }
    }

}

function calculateTotals(documento_id, regenerate_scadenze) {
    totale = 0;
    iva_perc_max = 0;
    iva_id_perc_max = 0;
    totale_iva = 0;

    totale_iva_divisa = {};
    totale_iva_divisa[iva_id_perc_max] = [0, 0];
    totale_imponibile_divisa = {};
    totale_imponibile_divisa[iva_id_perc_max] = [0, 0];
    competenze = 0;
    competenze_scontate = 0;
    competenze_no_ritenute = 0;
    sconto_totale = $('.js_sconto_totale').val();

    var sconto_su_imponibile = $('.js_sconto_su_imponibile').is(':checked');


    if (sconto_totale == 0) {
        $('label.competenze_scontate').hide();
    } else {
        $('label.competenze_scontate').show();
    }
    $('#js_product_table > tbody > tr:not(.hidden)').each(function() {
        var riga_desc = $('.js-riga_desc', $(this)).is(':checked');
        if (riga_desc) {
            $('.js_documenti_contabilita_articoli_commessa,.js_documenti_contabilita_articoli_centro_costo_ricavo,.js_documenti_contabilita_articoli_unita_misura,.js_documenti_contabilita_articoli_quantita,.js_documenti_contabilita_articoli_prezzo,.js_documenti_contabilita_articoli_sconto,.js_documenti_contabilita_articoli_sconto2,.js_documenti_contabilita_articoli_sconto3,.js_documenti_contabilita_articoli_iva_id,.js-importo,.js-applica_ritenute,.js-applica_sconto', $(this)).attr('disabled', true);
            return;
        } else {
            $('.js_documenti_contabilita_articoli_commessa,.js_documenti_contabilita_articoli_centro_costo_ricavo,.js_documenti_contabilita_articoli_unita_misura,.js_documenti_contabilita_articoli_quantita,.js_documenti_contabilita_articoli_prezzo,.js_documenti_contabilita_articoli_sconto,.js_documenti_contabilita_articoli_sconto2,.js_documenti_contabilita_articoli_sconto3,.js_documenti_contabilita_articoli_iva_id,.js-importo,.js-applica_ritenute,.js-applica_sconto', $(this)).removeAttr('disabled');
        }

        var qty = parseFloat($('.js_documenti_contabilita_articoli_quantita', $(this)).val());
        var prezzo = parseFloat($('.js_documenti_contabilita_articoli_prezzo', $(this)).val());
        var sconto = parseFloat($('.js_documenti_contabilita_articoli_sconto', $(this)).val());
        var sconto2 = parseFloat($('.js_documenti_contabilita_articoli_sconto2', $(this)).val());
        var sconto3 = parseFloat($('.js_documenti_contabilita_articoli_sconto3', $(this)).val());
        var iva = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', $(this)).data('perc'));
        //alert(iva);
        var iva_id = parseFloat($('.js_documenti_contabilita_articoli_iva_id option:selected', $(this)).val());
        var appl_ritenute = $('.js-applica_ritenute', $(this)).is(':checked');
        var appl_sconto = $('.js-applica_sconto', $(this)).is(':checked');


        iva_perc_max = Math.max(iva_perc_max, iva);

        if (iva_perc_max == iva) {

            iva_id_perc_max = iva_id;
        }

        if (isNaN(qty)) {
            qty = 0;
        }
        if (isNaN(prezzo)) {
            prezzo = 0;
        }
        if (isNaN(sconto)) {
            sconto = 0;
        }
        if (isNaN(sconto2)) {
            sconto2 = 0;
        }
        if (isNaN(sconto3)) {
            sconto3 = 0;
        }
        if (isNaN(iva)) {
            iva = 0;
        }

        var totale_riga = prezzo * qty;
        var totale_riga_scontato = (totale_riga / 100) * (100 - sconto);
        totale_riga_scontato = totale_riga_scontato / 100 * (100 - sconto2);
        totale_riga_scontato = totale_riga_scontato / 100 * (100 - sconto3);

        var totale_riga_scontato_con_sconto_totale = totale_riga_scontato;

        //competenze += totale_riga_scontato;

        if (appl_sconto) {
            competenze += totale_riga_scontato;

            if (sconto_su_imponibile) {
                competenze_scontate += (totale_riga_scontato * (100 - sconto_totale) / 100);
            } else {
                competenze_scontate += totale_riga_scontato;
            }


            totale_riga_scontato_con_sconto_totale = parseFloat(totale_riga_scontato * (100 - sconto_totale) / 100);
        } else {
            competenze += totale_riga_scontato;
            competenze_scontate += totale_riga_scontato;
        }
        var totale_riga_scontato_ivato = parseFloat((totale_riga_scontato_con_sconto_totale * (100 + iva)) / 100);

        if (totale_riga_scontato_ivato != totale_riga_scontato_con_sconto_totale) {
            //                 console.log(totale_riga_scontato_ivato);
            //                 console.log(totale_riga_scontato_con_sconto_totale);
        }

        if (!appl_ritenute) {
            competenze_no_ritenute += totale_riga_scontato_con_sconto_totale;
        }

        if (totale_iva_divisa[iva_id] == undefined) {
            // Moltiplica per 100 per lavorare con numeri interi
            let iva_calcolata = (totale_riga_scontato_con_sconto_totale * iva);

            totale_iva_divisa[iva_id] = [iva, iva_calcolata / 100];
            totale_imponibile_divisa[iva_id] = [iva, totale_riga_scontato_con_sconto_totale];
        } else {
            // Aggiungi all'IVA già calcolata (lavorando con numeri interi)
            let iva_calcolata = (totale_riga_scontato_con_sconto_totale * iva) / 100;
            totale_iva_divisa[iva_id][1] += iva_calcolata;
            totale_imponibile_divisa[iva_id][1] += totale_riga_scontato_con_sconto_totale;


        }




        //            console.log(totale_riga);
        //            console.log(totale_riga_scontato);
        //            console.log(totale_riga_scontato_con_sconto_totale);
        //
        //console.log(totale_iva_divisa);
        if (sconto_su_imponibile) {
            // Moltiplica per 100 per lavorare con numeri interi, esegui il calcolo, poi dividi per 100
            let iva_calcolata = (totale_riga_scontato_con_sconto_totale * iva);
            totale_iva += iva_calcolata / 100;
        } else {
            // Stessa logica applicata qui
            let iva_calcolata = (totale_riga_scontato * iva);
            totale_iva += iva_calcolata / 100;
        }

        totale += totale_riga_scontato_ivato;


        $('.js-importo', $(this)).val(totale_riga_scontato_ivato.toFixed(2));
        $('.js_documenti_contabilita_articoli_iva', $(this)).val(parseFloat((totale_riga_scontato / 100) * iva).toFixed(2));
        $('.js_riga_imponibile', $(this)).html(parseFloat(totale_riga_scontato).toFixed(2));
        $('.js_documenti_contabilita_articoli_imponibile', $(this)).val(parseFloat(totale_riga_scontato).toFixed(2));

    });

    for (var i in totale_iva_divisa) {
        totale_iva_divisa[i][1] = Math.round(totale_iva_divisa[i][1] * 100) / 100;
    }

    //Fix per evitare di portarmi dietro troppe cifre decimali che poi creano problemi di arrotondamento...
    competenze = Math.round(competenze * 100) / 100;
    totale_iva = Math.round(totale_iva * 1000) / 1000;
    //alert(1);
    competenze_scontate = Math.round(competenze_scontate * 100) / 100;
    competenze_no_ritenute = Math.round(competenze_no_ritenute * 100) / 100;

    rivalsa_inps_percentuale = parseFloat($('[name="documenti_contabilita_rivalsa_inps_perc"]').val());
    rivalsa_inps_valore = parseFloat(((competenze_scontate - competenze_no_ritenute) / 100) * rivalsa_inps_percentuale);

    competenze_con_rivalsa = competenze_scontate + rivalsa_inps_valore;

    cassa_professionisti_perc = parseFloat($('[name="documenti_contabilita_cassa_professionisti_perc"]').val());
    cassa_professionisti_valore = parseFloat(((competenze_con_rivalsa - competenze_no_ritenute) / 100) * cassa_professionisti_perc);

    imponibile = competenze_con_rivalsa + cassa_professionisti_valore;

    var applica_split_payment = $('[name="documenti_contabilita_split_payment"]').is(':checked');

    var totale_imponibili_iva_diverse_da_max = 0;
    var totale_iva_diverse_da_max = 0;
    for (var iva_id in totale_iva_divisa) {
        if (totale_iva_divisa[iva_id][0] != iva_perc_max) {
            if (totale_iva_divisa[iva_id][0] != 0) {
                totale_imponibili_iva_diverse_da_max += parseFloat((totale_iva_divisa[iva_id][1] / totale_iva_divisa[iva_id][0]) * 100);
            } else {
                // console.log(totale_imponibile_divisa);
                // console.log(iva_id);
                totale_imponibili_iva_diverse_da_max += totale_imponibile_divisa[iva_id][1];

            }

            totale_iva_diverse_da_max += parseFloat(totale_iva_divisa[iva_id][1]);
        }

    }

    // console.log(totale_iva_divisa[1]);
    // console.log(parseFloat(((imponibile - totale_imponibili_iva_diverse_da_max) / 100) * iva_perc_max));
    //Aggiungo alla iva massima, ciò che manca tenendo conto delle modifiche ai totali dovute a rivalsa e cassa
    //20240123 - MP - Tolto perchè su ecoconfort questo ricalcolo sballava l'iva sull'id documento 2454... in pratica, a volte, decommentando le due righe di console log qui sopra l'importo differiva di un centesimo...
    //totale_iva_divisa[iva_id_perc_max][1] = parseFloat(((imponibile - totale_imponibili_iva_diverse_da_max) / 100) * iva_perc_max);

    //        alert('imponibile '+imponibile);
    //        alert('totale' + totale);
    //        alert('totale ivato' + totale_iva);
    //        alert('competenze scontate' + competenze_scontate);
    //        alert('???' + competenze_scontate / 100 * 22);

    //Valuto le ritenute
    ritenuta_acconto_perc = parseFloat($('[name="documenti_contabilita_ritenuta_acconto_perc"]').val());
    ritenuta_acconto_perc_sull_imponibile = parseFloat($('[name="documenti_contabilita_ritenuta_acconto_perc_imponibile"]').val());
    ritenuta_acconto_valore_sull_imponibile = ((competenze_con_rivalsa - competenze_no_ritenute) / 100) * ritenuta_acconto_perc_sull_imponibile;
    totale_ritenuta = (ritenuta_acconto_valore_sull_imponibile / 100) * ritenuta_acconto_perc;

    //console.log(totale_iva_divisa);
    totale = imponibile + totale_iva_diverse_da_max + totale_iva_divisa[iva_id_perc_max][1] - totale_ritenuta;

    $('.js_tot_fattura').html('€ ' + totale.toFixed(2));

    $('[name="documenti_contabilita_rivalsa_inps_valore"]').val(rivalsa_inps_valore);
    $('[name="documenti_contabilita_competenze_lordo_rivalsa"]').val(competenze_con_rivalsa);
    if (rivalsa_inps_percentuale && rivalsa_inps_valore > 0) {
        $('.js_rivalsa').html('Rivalsa INPS ' + rivalsa_inps_percentuale + '% <span>€ ' + rivalsa_inps_valore.toFixed(2) + '</span>').show();
        $('.js_competenze_rivalsa').html('Competenze (al lordo della rivalsa)<span>€ ' + competenze_con_rivalsa.toFixed(2) + '</span>').show();
    } else {
        $('.js_rivalsa').hide();
        $('.js_competenze_rivalsa').hide();
    }

    $('[name="documenti_contabilita_cassa_professionisti_valore"]').val(cassa_professionisti_valore);
    $('[name="documenti_contabilita_imponibile"]').val(imponibile.toFixed(2));
    $('[name="documenti_contabilita_imponibile_scontato"]').val(competenze_scontate.toFixed(2));

    if (cassa_professionisti_perc && cassa_professionisti_valore > 0) {
        $('.js_cassa_professionisti').html('Cassa professionisti ' + cassa_professionisti_perc + '% <span>€ ' + cassa_professionisti_valore.toFixed(2) + '</span>').show();
        $('.js_imponibile').html('Imponibile <span>€ ' + imponibile.toFixed(2) + '</span>').show();
    } else {
        $('.js_cassa_professionisti').hide();
        $('.js_imponibile').hide();
    }


    $('[name="documenti_contabilita_ritenuta_acconto_valore"]').val(totale_ritenuta);
    $('[name="documenti_contabilita_ritenuta_acconto_imponibile_valore"]').val(ritenuta_acconto_valore_sull_imponibile);
    if (ritenuta_acconto_perc > 0 && ritenuta_acconto_perc_sull_imponibile > 0 && totale_ritenuta > 0) {
        $('.js_ritenuta_acconto').html('Ritenuta d\'acconto -' + ritenuta_acconto_perc + '% di &euro; ' + ritenuta_acconto_valore_sull_imponibile.toFixed(2) + '<span>€ ' + totale_ritenuta.toFixed(2) + '</span>').show();
    } else {
        $('.js_ritenuta_acconto').hide();
    }

    $('[name="documenti_contabilita_competenze"]').val(competenze);
    $('.js_competenze').html('€ ' + competenze.toFixed(2));
    $('.js_competenze_scontate').html('€ ' + competenze_scontate.toFixed(2));

    $(".js_tot_iva:not(:first)").remove();
    $(".js_tot_iva:first").hide();


    $('[name="documenti_contabilita_iva_json"]').val(JSON.stringify(totale_iva_divisa));
    $('[name="documenti_contabilita_imponibile_iva_json"]').val(JSON.stringify(totale_imponibile_divisa));

    for (var iva_id in totale_iva_divisa) {
        if (iva_id != 0) {
            $(".js_tot_iva:last").clone().insertAfter(".js_tot_iva:last").show();
            $('.js_tot_iva:last').html(`IVA (` + (totale_iva_divisa[iva_id][0]) + `%): <span>€ ` + totale_iva_divisa[iva_id][1].toFixed(2) + `</span>`); //'€ '+totale_iva.toFixed(2));
        }
    }

    if (applica_split_payment) {
        $('.js_split_payment').html('Iva non dovuta (split payment) <span>€ -' + (totale_iva_diverse_da_max + totale_iva_divisa[iva_id_perc_max][1]).toFixed(2) + '</span>').show();
        totale -= (totale_iva_diverse_da_max + totale_iva_divisa[iva_id_perc_max][1]);
    } else {
        $('.js_split_payment').hide();
    }


    if (sconto_su_imponibile) { //Se ho già applicato lo sconto sull'imponibile, il totale sarà già correttamente scontato
        //Do nothing
        $('.js_tot_fattura_container').hide();
    } else { //Se lo sconto va applicato solo al totale (es.: sconto in fattura), lo calcolo qui
        //alert(totale);
        $('.js_tot_fattura_container').show();
        totale = (totale / 100) * (100 - sconto_totale);
        //alert(totale);
    }

    //20191029 - MP - Aggiungo l'importo di bollo al totale
    if ($('[name="documenti_contabilita_importo_bollo"]').val() && $('[name="documenti_contabilita_applica_bollo"]').is(':checked')) {

        totale += parseFloat($('[name="documenti_contabilita_importo_bollo"]').val());

    }

    totale = Math.round(totale * 100) / 100;
    $('.js_tot_da_saldare').html('€ ' + totale.toFixed(2));

    $('[name="documenti_contabilita_totale"]').val(totale.toFixed(2));
    $('[name="documenti_contabilita_iva"]').val(totale_iva.toFixed(2));

    if (isNaN(documento_id) && documento_id) {
        $('.documenti_contabilita_scadenze_ammontare').val(totale.toFixed(2));
        $('.documenti_contabilita_scadenze_ammontare:first').trigger('change');
    } else {
        //$('.documenti_contabilita_scadenze_ammontare:last').closest('.row_scadenza').remove();
        $('.documenti_contabilita_scadenze_ammontare:last').trigger('change');
    }

    if (!isNaN(regenerate_scadenze) && regenerate_scadenze == true) {
        generaScadenze(totale, totale_iva);
    }

}

function increment_scadenza() {
    var counter_scad = $('.row_scadenza').length;
    var rows_scadenze = $('.js_rows_scadenze');

    // Fix per clonare select inizializzata
    if ($('.js_table_select2').filter(':first').data('select2')) {
        $('.js_table_select2').filter(':first').select2('destroy');
    } else {

    }

    var newScadRow = $('.row_scadenza').filter(':first').clone();
    $('.documenti_contabilita_scadenze_data_saldo', newScadRow).val('');
    // Fix per clonare select inizializzata
    $('.js_table_select2').filter(':first').select2();

    /* Line manipulation begin */
    //newScadRow.removeClass('hidden');
    $('input, select, textarea', newScadRow).each(function() {
        var control = $(this);
        var name = control.attr('data-name');
        control.attr('name', 'scadenze[' + counter_scad + '][' + name + ']').removeAttr('data-name');
    });

    $('.js_form_datepicker input', newScadRow).datepicker({
        todayBtn: 'linked',
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        weekStart: 1,
        language: 'it'
    });
    $('.js_documenti_contabilita_scadenze_id', newScadRow).remove();
    /* Line manipulation end */
    counter_scad++;
    newScadRow.appendTo(rows_scadenze);

    // $('.js_table_select2', newScadRow).select2({
    //     //placeholder: "Seleziona prodotto",
    //     allowClear: true
    // });
}

$(document).ready(function() {
    var table = $('#js_product_table');
    var body = $('#js_product_table > tbody');
    var rows = $('tbody > tr', table);
    var increment = $('#js_add_product', table);

    var rows_scadenze = $('.js_rows_scadenze');
    //var increment_scadenza = $('#js_add_scadenza');


    var firstRow = rows.filter(':first');
    var counter = rows.length;

    $('#new_fattura').on('change', '[name="documenti_contabilita_template_pagamento"],[name="documenti_contabilita_importo_bollo"],[name="documenti_contabilita_split_payment"], [name="documenti_contabilita_rivalsa_inps_perc"],[name="documenti_contabilita_cassa_professionisti_perc"],[name="documenti_contabilita_ritenuta_acconto_perc"],[name="documenti_contabilita_ritenuta_acconto_perc_imponibile"]', function() {
        calculateTotals(false, true);
    });

    $('[name="documenti_contabilita_template_pagamento"]').on('change', function() {
        var selected = $(this).val();
        if (selected) {
            $.ajax({
                url: base_url + 'contabilita/documenti/get_tpl_pagamento_banca/' + selected,
                type: 'get',
                dataType: 'json',
                data: {},
                async: false,
                success: function(res) {
                    if (res.status == '1') {
                        $('[name="documenti_contabilita_conto_corrente"]').val(res.txt.documenti_contabilita_tpl_pag_scadenze_banca_di_riferimento ?? '').trigger('change');
                    }
                },
                error: function(status, request, error) {

                }
            })
        }
    });

    <?php if(empty($documento_id) && !$clone): ?>
    $('[name="documenti_contabilita_template_pagamento"]').trigger('change');
    <?php endif; ?>
    table.on('change', '.js_sconto_su_imponibile, .js-applica_ritenute,.js-applica_sconto, .js-riga_desc, .js_documenti_contabilita_articoli_quantita, .js_documenti_contabilita_articoli_prezzo, .js_documenti_contabilita_articoli_sconto,.js_documenti_contabilita_articoli_sconto2,.js_documenti_contabilita_articoli_sconto3, .js_documenti_contabilita_articoli_iva_id',
        function() {
            //console.log('dentro');
            setTimeout("calculateTotals(false,true)", 500);
        });

    table.on('change', '.js-importo', function() {

        reverseRowCalculate($(this).closest('tr'));
    });

    // Aggiungi prodotto
    increment.on('click', function() {
        var newRow = firstRow.clone();

        /* Line manipulation begin */
        newRow.removeClass('hidden');
        $('input, select, textarea', newRow).each(function() {
            var control = $(this);
            var name = control.attr('data-name');
            if (name) {
                control.attr('name', 'products[' + counter + '][' + name + ']').removeAttr('data-name');
            }
            //control.val("");
        });

        $('.js_table_select2', newRow).select2({
            placeholder: "Seleziona prodotto",
            allowClear: true
        });
        $('.js_autocomplete_prodotto', newRow).data('id', counter).prop('data-id', counter).attr('data-id', counter);
        initAutocomplete($('.js_autocomplete_prodotto', newRow));

        /* Line manipulation end */

        counter++;



        newRow.appendTo(body);

        initComponents(newRow);

        $('#js_product_table > tbody').trigger('sortupdate');
    });


    table.on('click', '.js_remove_product', function() {
        $(this).parents('tr').remove();
        calculateTotals(false, true);
    });
    $('#offerproducttable .js_remove_product').on('click', function() {
        $(this).parents('tr').remove();
    });

    $('.js_sconto_totale').on('change', function() {
        calculateTotals(false, true);
    });

    //Se cambio una scadenza ricalcolo il parziale di quella sucessiva, se c'è. Se non c'è la creo.
    rows_scadenze.on('change', '.documenti_contabilita_scadenze_ammontare', function() {
        //Se la somma degli ammontare è minore del totale procedo
        var totale_scadenze = 0;
        $('.documenti_contabilita_scadenze_ammontare').each(function() {
            totale_scadenze += parseFloat($(this).val());
        });

        /*
         * La logica è questa:
         * 1. se le scadenza superano l'importo totale, metto a posto togliendo ricorsivamente la riga sucessiva finchè non entro nel caso 2
         * 2. se le scadenza non superano l'importo totale, tolgo tutte le righe sucessiva all'ultima modificata, ne creo una nuova e forzo importo corretto sull'ultima
         */
        next_row_exists = $(this).closest('.row_scadenza').next('.row_scadenza').length != 0;

        if (totale_scadenze < totale) {
            if (next_row_exists) {
                //console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                $(this).trigger('change');
            } else {
                //console.log('Non esiste scadenza successiva. Creo...');
                //$('#js_add_scadenza').trigger('click');
                increment_scadenza();
                next_row = $(this).closest('.row_scadenza').next('.row_scadenza');
                $('.documenti_contabilita_scadenze_ammontare', next_row).val((totale - totale_scadenze).toFixed(2));
            }
        } else {
            if (next_row_exists) {
                //console.log('Rimuovo tutte le righe dopo e ritriggherò, così entra nell\'if precedente...');
                $(this).closest('.row_scadenza').next('.row_scadenza').remove();
                $(this).trigger('change');
            } else {
                //console.log('Non esiste scadenza successiva. Tutto a posto ma nel dubbio forzo questa = alla differenza tra totale e totale scadenze');
                $(this).val((totale - (totale_scadenze - $(this).val())).toFixed(2));

            }
        }

    });

    if (rows.length < 2) {
        increment.click();
    }
});
</script>


<script>
$(document).ready(function() {
    // trigger click on add product when tabkey is pressed and focus on last codice
    $('#js_add_product').on('keyup', function(e) {
        $(this).trigger('click');
        $('.js_documenti_contabilita_articoli_codice:last').focus();
    });


    //se il selettore è su "Non ancora saldato", il campo "Data saldo" viene svuotata
    $(".select2").on("change", function() {
        //console.log('entrato');
        if ($('#empty_select').val() == "") {
            //console.log('entrato if');
            $("#empty_date").val("");
        }
    });

    $('#js_dtable').dataTable({
        aoColumns: [null, null, null, null, null, null, null, {
            bSortable: false
        }],
        aaSorting: [
            [0, 'desc']
        ]
    });
    $('#js_dtable_wrapper .dataTables_filter input').addClass("form-control input-small"); // modify table search input
    $('#js_dtable_wrapper .dataTables_length select').addClass("form-control input-xsmall"); // modify table per page dropdown

});
</script>

<script>
var check_calculate = false;
$(document).ready(function() {
    var table = $('#js_product_table');

    //Fix per essere sicuri che i calcoli siano stati tutti fatti prima di inviare e salvare il documento.
    $('#new_fattura').on('submit', function(e) {
        /// CHECK CAMPI PER SDI SE FATTURA ELETTRONICA E SE CLIENTE E' UNA PA
        var _tipo_doc = $('[name="documenti_contabilita_tipo"]').val();
        var _tipo_dest = $("input[name='documenti_contabilita_tipo_destinatario']:checked").val();
        var _is_fattura_elettronica = $('[name="documenti_contabilita_formato_elettronico"]').is(':checked')

        var _fe_ordine_acquisto_id_documento = $("[name='documenti_contabilita_fe_ordineacquisto[id_documento]']").val();
        var _fe_ordine_acquisto_cig = $("[name='documenti_contabilita_fe_ordineacquisto[codice_cig]']").val();
        var _fe_ordine_acquisto_cup = $("[name='documenti_contabilita_fe_ordineacquisto[codice_cup]']").val();

        var _fe_dati_contratto_id_documento = $("[name='documenti_contabilita_fe_dati_contratto[id_documento]']").val();
        var _fe_dati_contratto_cig = $("[name='documenti_contabilita_fe_dati_contratto[codice_cig]']").val();
        var _fe_dati_contratto_cup = $("[name='documenti_contabilita_fe_dati_contratto[codice_cup]']").val();

        console.log(_fe_dati_contratto_id_documento, _fe_dati_contratto_cig, _fe_dati_contratto_cup);

        // entro in questo if solo se è selezionato "PA" ed è una fattura elettronica
        if (($.inArray(_tipo_doc, ['1', '4', '11', '12']) !== -1) && _tipo_dest == 3 && _is_fattura_elettronica) {
            if (_fe_ordine_acquisto_id_documento && (!_fe_ordine_acquisto_cig && !_fe_ordine_acquisto_cup)) {
                if (!confirm("Hai selezionato una PA, ma non hai indicato l'ID DOCUMENTO o il codice CIG/CUP sull'ordine acquisto, sei sicuro di voler procedere al salvataggio?")) {
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    e.preventDefault();

                    return false;
                }

            }

            if (_fe_dati_contratto_id_documento && (!_fe_dati_contratto_cig && !_fe_dati_contratto_cup)) {
                if (!confirm("Hai selezionato una PA, ma non hai indicato l'ID DOCUMENTO o il codice CIG/CUP sui dati contratto, sei sicuro di voler procedere al salvataggio?")) {
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    e.preventDefault();

                    return false;
                }
            }
        }
        //////////////////////////////////////////////////////////////////////////////////////

        if (!check_calculate) {
            e.stopImmediatePropagation();
            e.stopPropagation();
            e.preventDefault();
            <?php if ($documento_id): ?>
            calculateTotals('<?php echo (!$clone) ? $documento_id : ''; ?>', false);
            <?php else: ?>
            calculateTotals(false, false);
            <?php endif; ?>
            check_calculate = true;
            $('#new_fattura').trigger('submit');
        } else {
            check_calculate = false;
        }

        <?php if(!empty($ddt_gia_fatturati)): ?>
        var ddt_gia_fatturati = '<?php echo implode('\n', $ddt_gia_fatturati); ?>';

        var _ddt_fatturati_confirmed = false;
        if (!confirm("ATTENZIONE: I seguenti DDT son già stati fatturati.\n" + ddt_gia_fatturati + "\n\nContinuare?") && !_ddt_fatturati_confirmed) {
            return false;
        }
        <?php endif; ?>
    });

    <?php if($clone == DB_BOOL_TRUE && !empty($documento)): ?>
    /*if (!confirm('Stai clonando un documento. Vuoi mantenere la riga descrittiva con il suo riferimento?')) {
        var riga_desc_rif_doc_row = $('.js_documenti_contabilita_articoli_descrizione:visible').filter(':first').closest('tr');
        
        $('.js_remove_product', riga_desc_rif_doc_row).trigger('click');
    }*/
    <?php endif; ?>

    <?php if (!$documento_id && !$clone && empty($this->input->get('no_add_product'))): /* 20231120 - michael - aggiunto il parametro get "no_add_product" così si può gestire il fatto che non venga creata una riga nel caso si passino anche degli articoli in post (altrimenti in certe situazioni sballa i contenggi iva etc) */ ?>
    $('#js_add_product').trigger('click');
    <?php endif; ?>

    <?php if ($accorpamento_documenti || (!$accorpamento_documenti && $clone == DB_BOOL_TRUE) || (!empty($this->input->post('articoli')))): ?>
    $('.js_documenti_contabilita_articoli_prezzo:visible').filter(':first').trigger('change');
    <?php endif; ?>


    function updateDestinationFromWarehouse() {
        var tipo_documento = $('[name="documenti_contabilita_tipo"]').val();
        if (tipo_documento == "10") {
            $('[name="documenti_contabilita_luogo_destinazione"]').val('');
            var warehouseData = $('[name="documenti_contabilita_magazzino"] option:selected').data('json_data');
            if (warehouseData) {
                warehouseData = JSON.parse(atob(warehouseData));
                var address = warehouseData.magazzini_indirizzo || '';
                var city = warehouseData.magazzini_citta || '';
                var zip = warehouseData.magazzini_cap || '';
                var completeAddress = address + '\n' + city + ' ' + zip;
                $('[name="documenti_contabilita_luogo_destinazione"]').val(completeAddress);
            }
        }
    }
    // Bind the change event to the warehouse select box
    $('[name="documenti_contabilita_magazzino"]').change(updateDestinationFromWarehouse);
    <?php if (empty($documento['documenti_contabilita_numero']) || $clone): ?>
    updateDestinationFromWarehouse();
    <?php endif; ?>


});
</script>
<!-- END Module Related Javascript -->

<?php $this->layout->addModuleJavascript('contabilita', 'gestione_scadenze.js'); ?>
<?php $this->layout->addModuleJavascript('contabilita', 'gestione_listini.js'); ?>
<script>
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
    acc[i].onclick = function() {
        this.classList.toggle("active");
        this.nextElementSibling.classList.toggle("show");
    }
}
</script>