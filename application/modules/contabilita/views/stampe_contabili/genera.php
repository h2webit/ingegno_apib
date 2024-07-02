<?php $this->layout->addModuleJavascript('contabilita', 'stampe_contabili.js'); ?>
<?php
$filtri = @$this->session->userdata(SESS_WHERE_DATA)['filter_stampe_contabili'];
$filtri = (array) $filtri;
$azienda = false;
foreach ($filtri as $filtro) {
    $field_id = $filtro['field_id'];
    $value = $filtro['value'];
    if ($value == '-1') {
        continue;
    }
    $field_data = $this->db->query("SELECT * FROM fields LEFT JOIN fields_draw ON (fields_draw_fields_id = fields_id) WHERE fields_id = '$field_id'")->row_array();
    $field_name = $field_data['fields_name'];

    if ($field_name == 'prime_note_azienda') {
        $azienda = $value;
    }

}
if ($azienda) {
    $settings = $this->apilib->view('documenti_contabilita_settings', $azienda);
} else {
    $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
}
?>
<style>
    .create_iva {
        width: 100%;
        cursor: pointer;
        border-radius: 4px;
        margin-bottom: 16px;
        padding: 16px 32px;
        display: inline-block;
        font-weight: 500;
        font-size: 18px;
        transition: background .15s ease-in;
    }

    .create_iva.disabled {
        background-color: #f1f5f9;
        cursor: none;
        color: #AAA;
    }

    .create_iva:hover {
        background: #cbd5e1;
    }
</style>


<?php
$anno_corrente = date('Y');

if (
    $azienda && $this->input->get('anno') &&
    ($this->input->get('mese') || $this->input->get('trimestre'))
) {
    $filtro = [
        'contabilita_stampe_definitive_anno' => $this->input->get('anno'),
        'contabilita_stampe_definitive_azienda' => $azienda,
    ];

    if ($this->input->get('mese')) {
        $filtro['contabilita_stampe_definitive_mese'] = $this->input->get('mese');
    }
    if ($this->input->get('trimestre')) {
        $filtro['contabilita_stampe_definitive_trimestre'] = $this->input->get('trimestre');
    }
    $last_definitivo = $this->apilib->searchFirst('contabilita_stampe_definitive', $filtro);
    //debug($last_definitivo);

    $settings = $this->apilib->view('documenti_contabilita_settings', $azienda);

    $anno_esercizio = $settings['documenti_contabilita_settings_esercizio'];
    $conto_patrimoniale_chiusura = $settings['documenti_contabilita_settings_conto_patr_fin'];
    $conto_patrimoniale_apertura = $settings['documenti_contabilita_settings_conto_patr_ini'];
    $conto_riepilogo_costi = $settings['documenti_contabilita_settings_conto_riep_cos'];
    $conto_riepilogo_ricavi = $settings['documenti_contabilita_settings_conto_riep_ric'];



    $gpp_registrato = $this->apilib->searchFirst(
        'prime_note',
        [
            'prime_note_causali_codice' => 'GPP',
            'YEAR(prime_note_data_registrazione)' => $anno_esercizio
        ]
    );
    $blc_registrato = $this->apilib->searchFirst(
        'prime_note',
        [
            'prime_note_causali_codice' => 'BLC',
            'YEAR(prime_note_data_registrazione)' => $anno_esercizio
        ]
    );
    $chiusura_esercizio_precedente_gpp = $chiusura_esercizio_precedente_blc = false;

    if (date('Y') > $anno_esercizio && $conto_patrimoniale_chiusura && !$gpp_registrato) {
        $chiusura_esercizio_precedente_gpp = true;
    }
    if (date('Y') > $anno_esercizio && $conto_patrimoniale_chiusura && !$blc_registrato && $gpp_registrato) {
        $chiusura_esercizio_precedente_blc = true;
    }
    if (!$chiusura_esercizio_precedente_gpp && !$chiusura_esercizio_precedente_blc && $conto_patrimoniale_apertura) {
        $apertura_esercizio = true;
    } else {
        $apertura_esercizio = false;
    }

    if (empty($last_definitivo)) {
        $iva_vendite = true;
        $iva_acquisti = true;
        $iva_corrispettivi = true;
        $liquidazione = false;
        $libro_giornale = false;
        $lipe = false;

    } else {
        $iva_vendite = false;
        $iva_acquisti = false;
        $iva_corrispettivi = false;
        $liquidazione = false;
        $libro_giornale = false;
        $lipe = false;
        if (!$last_definitivo['contabilita_stampe_definitive_iva_vendite_pdf']) {
            $iva_vendite = true;
        }
        if (!$last_definitivo['contabilita_stampe_definitive_iva_acquisti_pdf']) {
            $iva_acquisti = true;
        }
        if (!$last_definitivo['contabilita_stampe_definitive_iva_corrispettivi_pdf']) {
            $iva_corrispettivi = true;
        }

        if (!$iva_corrispettivi && !$iva_acquisti && !$iva_vendite && !$last_definitivo['contabilita_stampe_definitive_liquidazione_iva_pdf']) {
            $liquidazione = true;
        }
        if (!$iva_corrispettivi && !$iva_acquisti && !$iva_vendite && !$liquidazione && !$last_definitivo['contabilita_stampe_definitive_lipe_xml']) {
            $lipe = true;
        }
        if (!$last_definitivo['contabilita_stampe_definitive_libro_giornale_pdf'] && $last_definitivo['contabilita_stampe_definitive_liquidazione_iva_pdf'] && $last_definitivo['contabilita_stampe_definitive_lipe_xml'] && ($this->input->get('trimestre') == 4 || $this->input->get('mese') == 12)) {
            $libro_giornale = true;
        }

        //debug($last_definitivo, true);
    }
} else {
    $iva_vendite = false;
    $iva_acquisti = false;
    $iva_corrispettivi = false;
    $liquidazione = false;
    $libro_giornale = false;
    $lipe = false;
    $chiusura_esercizio_precedente_gpp = $chiusura_esercizio_precedente_blc = $apertura_esercizio = false;
    $anno_esercizio = $anno_corrente - 1;
}

?>

<div class="row">
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_registro_iva_vendite_definitivo <?php if ($iva_vendite): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>genera registro <br />iva vendite</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_registro_iva_acquisti_definitivo <?php if ($iva_acquisti): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>genera registro <br />iva acquisti</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_registro_iva_corrispettivi_definitivo <?php if ($iva_corrispettivi): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>genera registro <br />iva corrispettivi</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_liquidazione_iva_definitivo <?php if ($liquidazione): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>genera <br /> liquidazione iva</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_lipe_definitivo <?php if ($lipe): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>genera <br /> LIPE</span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_libro_giornale_definitivo <?php if ($libro_giornale): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>genera <br /> libro giornale</span>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_chiusura_esercizio_precedente_gpp <?php if ($chiusura_esercizio_precedente_gpp): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>Chiusura esercizio Econ. =&gt; Patr. (GPP) <?php echo $anno_esercizio; ?></span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_chiusura_esercizio_precedente_blc <?php if ($chiusura_esercizio_precedente_blc): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>Chiusura esercizio Patr. =&gt; <?php echo $conto_patrimoniale_chiusura; ?> (BLC)
                <?php echo $anno_esercizio; ?></span>
        </div>
    </div>
    <div class="col-sm-3">
        <div
            class="create_iva text-uppercase text-center js_apertura_esercizio <?php if ($apertura_esercizio): ?>btn-success<?php else: ?>disabled<?php endif; ?>">
            <span>Apertura esercizio <?php echo $anno_corrente; ?></span>
        </div>
    </div>
</div>