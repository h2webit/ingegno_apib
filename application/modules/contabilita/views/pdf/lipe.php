<?php
$this->load->model('contabilita/prima_nota');
function reverseDate($data)
{
    //debug($data);
    $data = explode('/', $data);
    return "{$data[2]}-{$data[1]}-{$data[0]}";
}
$settings = false;
// debug($anno);
// debug($mese);
// debug($trimestre);
// debug($azienda,true);

$contabilita_settings = $this->apilib->view('documenti_contabilita_settings', $azienda);


$codice_fiscale = (!empty($contabilita_settings['documenti_contabilita_settings_codice_fiscale_dichiarante']) ? $contabilita_settings['documenti_contabilita_settings_codice_fiscale_dichiarante'] : die(json_encode(['status' => 0, 'txt' => 'Codice Fiscale dichiarante non impostato!'])));
?><?php echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; ?>
<iv:Fornitura xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:iv="urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp">
    <iv:Intestazione>
        <iv:CodiceFornitura>IVP18</iv:CodiceFornitura>
        <iv:CodiceFiscaleDichiarante><?php echo $codice_fiscale; ?></iv:CodiceFiscaleDichiarante>
        <iv:CodiceCarica>1</iv:CodiceCarica>
    </iv:Intestazione>
    <iv:Comunicazione identificativo="00001">
        <iv:Frontespizio>
            <iv:CodiceFiscale><?php echo $contabilita_settings['documenti_contabilita_settings_company_codice_fiscale']; ?></iv:CodiceFiscale>
            <iv:AnnoImposta><?php echo $anno; ?></iv:AnnoImposta>
            <iv:PartitaIVA><?php echo $contabilita_settings['documenti_contabilita_settings_company_vat_number']; ?></iv:PartitaIVA>
            <iv:CFDichiarante><?php echo $codice_fiscale; ?></iv:CFDichiarante>
            <iv:CodiceCaricaDichiarante>1</iv:CodiceCaricaDichiarante>
            <iv:FirmaDichiarazione>1</iv:FirmaDichiarazione>
            <iv:FlagConferma>0</iv:FlagConferma>
            <iv:IdentificativoProdSoftware>INGEGNO</iv:IdentificativoProdSoftware>
        </iv:Frontespizio>
        <iv:DatiContabili>
<?php
$ultima_stampa_definitiva = $this->apilib->searchFirst('contabilita_stampe_definitive', [], 0, 'contabilita_stampe_definitive_id');
//debug($ultima_stampa_definitiva,true);
$settings = $this->apilib->searchFirst('documenti_contabilita_settings');
//TODO: indagare perchè mettendo i filtri non va più...
$i = 0;
foreach ($mesi as $mese) {
    $liquidazione_iva_data = $this->prima_nota->getIvaData(
        [
            "MONTH(prime_note_data_registrazione)" => $mese,
            "YEAR(prime_note_data_registrazione)" => $anno,
            '(prime_note_id IN (SELECT prime_note_righe_iva_prima_nota FROM prime_note_righe_iva LEFT JOIN iva ON (iva_id = prime_note_righe_iva_iva) WHERE (iva_escludi_in_lipe IS NULL OR iva_escludi_in_lipe <> 1)))',
            '(sezionali_iva_escludi_in_lipe IS NULL OR sezionali_iva_escludi_in_lipe <> 1)',
        ],
        
        false,
        null,
        true
    );

    //debug($liquidazione_iva_data['acquisti'], true);

    unset($liquidazione_iva_data['acquisti']['primeNoteDataGroupSezionale']);
    unset($liquidazione_iva_data['vendite']['primeNoteDataGroupSezionale']);
    unset($liquidazione_iva_data['corrispettivi']['primeNoteDataGroupSezionale']);
    unset($liquidazione_iva_data['acquisti_reverse']['primeNoteDataGroupSezionale']);
    unset($liquidazione_iva_data['vendite_reverse']['primeNoteDataGroupSezionale']);
    

    // debug($liquidazione_iva_data['acquisti_reverse']['imponibili'],true);

    //debug($liquidazione_iva_data['acquisti_reverse'], true);
//debug($liquidazione_iva_data['vendite'], true);
    $totale_imposta_acquisti_reverse = $liquidazione_iva_data['acquisti_reverse']['totale_italia_imposta'] + $liquidazione_iva_data['acquisti_reverse']['totale_intra_imposta'] + $liquidazione_iva_data['acquisti_reverse']['totale_extra_imposta'];
    $totale_imposta_acquisti = $liquidazione_iva_data['acquisti']['totale_italia_imposta'] + $liquidazione_iva_data['acquisti']['totale_indetraibile_imposta'] + $liquidazione_iva_data['acquisti']['totale_intra_imposta'];
    $totale_imposta_acquisti_indetraibile = $liquidazione_iva_data['acquisti']['totale_indetraibile_imposta'];



    $totale_imposta_intra = $liquidazione_iva_data['acquisti']['totale_intra_imposta'];
    $totale_imposta_extra = $liquidazione_iva_data['acquisti']['totale_extra_imposta'];

    $totale_imoposta_acquisti_detraibile = $totale_imposta_extra + $totale_imposta_acquisti - $totale_imposta_acquisti_indetraibile;

    $totale_imposta_vendite_reverse = $liquidazione_iva_data['vendite_reverse']['totale_italia_imposta'] + $liquidazione_iva_data['vendite_reverse']['totale_intra_imposta'] + $liquidazione_iva_data['vendite_reverse']['totale_extra_imposta'];

    $totale_imposta_vendite = $liquidazione_iva_data['vendite']['totale_italia_imposta'];


    $totale_imposta_vendite_split = $liquidazione_iva_data['vendite']['totale_split_imposta'];

    $totale_imposta_corrispettivi = $liquidazione_iva_data['corrispettivi']['totale_italia_imposta'] + $liquidazione_iva_data['corrispettivi']['totale_intra_imposta'];

    $imposta_a_debito_intra = @$settings['documenti_contabilita_settings_imposta_a_debito_intra'];
    $imposta_a_debito_precedente = @$settings['documenti_contabilita_settings_imposta_a_debito_prec'];
    $imposta_a_debito_imposta_in_sospensione = @$settings['documenti_contabilita_settings_imposta_in_sospensione'];

    $imposta_a_debito_imposta_versata = @$settings['documenti_contabilita_settings_imposta_versata'];
    $imposta_a_debito_credito_iniziale = @$settings['documenti_contabilita_settings_credito_iniziale'];
    $imposta_a_debito_credito_precedente = $settings['documenti_contabilita_settings_credito_precedente_' . $mese];

    $totale_imposta_vendite_e_corrispettivi = $totale_imposta_vendite + $totale_imposta_corrispettivi + $totale_imposta_vendite_reverse;
    $differenza = $totale_imposta_vendite + $totale_imposta_corrispettivi - $totale_imoposta_acquisti_detraibile;

    //TODO: prendere dal conto speciale Erario c.to IVA, l'importo (sia negativo che positivo) e sottrarlo da quello che devo

    //Cerco sottoconto compensazione iva
    $sotto_conto_comp_iva = $this->apilib->searchFirst('documenti_contabilita_sottoconti', [
        'documenti_contabilita_sottoconti_compensazione_iva' => DB_BOOL_TRUE,
    ]);
    

    $credito_compensabile_portato_in_detrazione_avere = $credito_compensabile_portato_in_detrazione_dare = 0;
    $filtri = $liquidazione_iva_data['acquisti']['filtri'];

    if ($sotto_conto_comp_iva) {
        foreach ($filtri as $filtro) {
            if ($filtro['label'] == 'Data registrazione' && $filtro['value']) {
                $date_reg_expl = explode(' - ', $filtro['value']);

                $data_reg_da = reverseDate($date_reg_expl[0]);
                $data_reg_a = reverseDate($date_reg_expl[1]);
            }

            if ($filtro['label'] == 'Data documento' && $filtro['value']) {
                $date_doc_expl = explode(' - ', $filtro['value']);
                $data_doc_da = reverseDate($date_doc_expl[0]);
                $data_doc_a = reverseDate($date_doc_expl[1]);
            }

            if ($filtro['label'] == 'Azienda') {

                $azienda = $filtro['raw_data_value'];

            }
        }

        $registrazione_iva_compensazione = $this->apilib->searchFirst('prime_note_registrazioni', [
            "(prime_note_registrazioni_sottoconto_dare = '{$sotto_conto_comp_iva['documenti_contabilita_sottoconti_id']}' OR prime_note_registrazioni_sottoconto_avere = '{$sotto_conto_comp_iva['documenti_contabilita_sottoconti_id']}')",
            'prime_note_data_registrazione >=' => $data_reg_da,
            'prime_note_data_registrazione <=' => $data_reg_a,
            // 'prime_note_scadenza >=' => $data_doc_da,
            // 'prime_note_scadenza <=' => $data_doc_a,
            'prime_note_azienda' => $azienda,
        ], 0, 'prime_note_data_registrazione', 'DESC');

        if ($registrazione_iva_compensazione) {

            if ($registrazione_iva_compensazione['prime_note_registrazioni_importo_dare'] > 0) { //ATTENZIONE: è corretto che qui si inverta dare/avere (logiche italiane)
                $credito_compensabile_portato_in_detrazione_avere = $registrazione_iva_compensazione['prime_note_registrazioni_importo_dare']; //'xxx';
            } else {

                $credito_compensabile_portato_in_detrazione_dare = $registrazione_iva_compensazione['prime_note_registrazioni_importo_avere'];
            }

        }
    }

    if ($credito_compensabile_portato_in_detrazione_avere) {
        $totale_iva_da_versare_parziale = $differenza + $credito_compensabile_portato_in_detrazione_avere;
    } else {
        $totale_iva_da_versare_parziale = $differenza - $credito_compensabile_portato_in_detrazione_dare;
    }


    //Interessi trimestrali 1% su {parziale}
    if ($settings['documenti_contabilita_settings_liquidazione_iva_value'] == 'Mensile') {
        $interessi_trimestrali = 0;
    } else {
        $interessi_trimestrali = $totale_iva_da_versare_parziale / 100;

    }
    //TODO: prenderlo dal dato salvato su db. Il dato va salvato su db nel momento in cui faccio le stampe definitive

    $totale_da_versare = $totale_iva_da_versare_parziale + $interessi_trimestrali - $imposta_a_debito_credito_precedente;
    $i++;

    //debug(array_keys($liquidazione_iva_data),true);
    ?>
    <iv:Modulo>
        <iv:NumeroModulo><?php echo $i; ?></iv:NumeroModulo>
        <iv:Mese><?php echo $mese; ?></iv:Mese>
        <iv:TotaleOperazioniAttive><?php echo number_format($liquidazione_iva_data['vendite']['imponibili'] + $liquidazione_iva_data['corrispettivi']['imponibili'], 2, ',', ''); ?></iv:TotaleOperazioniAttive>
        <iv:TotaleOperazioniPassive><?php echo number_format($liquidazione_iva_data['acquisti']['imponibili']+ $liquidazione_iva_data['acquisti_reverse']['imponibili'], 2, ',', ''); ?></iv:TotaleOperazioniPassive>
        <iv:IvaEsigibile><?php echo number_format($totale_imposta_vendite_e_corrispettivi, 2, ',', ''); ?></iv:IvaEsigibile>
        <iv:IvaDetratta><?php echo number_format($totale_imposta_acquisti + $totale_imposta_extra + $totale_imposta_acquisti_reverse - $totale_imposta_acquisti_indetraibile, 2, ',', ''); ?></iv:IvaDetratta>
        <?php if ($differenza > 0): ?><iv:IvaDovuta><?php echo number_format($differenza, 2, ',', ''); ?></iv:IvaDovuta><?php endif; ?>
        <?php if ($differenza < 0): ?><iv:IvaCredito><?php echo number_format(abs($differenza), 2, ',', ''); ?></iv:IvaCredito><?php endif; ?>
        <?php if ($differenza + $interessi_trimestrali > 0): ?>
            <iv:ImportoDaVersare><?php echo number_format($differenza + $interessi_trimestrali, 2, ',', ''); ?></iv:ImportoDaVersare>
        <?php elseif ($differenza + $imposta_a_debito_precedente + $interessi_trimestrali < 0): ?>
            <iv:ImportoACredito><?php echo number_format(abs($differenza + $interessi_trimestrali), 2, ',', ''); ?></iv:ImportoACredito>
        <?php endif; ?>
    </iv:Modulo>
    <?php
}
?>
  </iv:DatiContabili>
 </iv:Comunicazione>
</iv:Fornitura>
