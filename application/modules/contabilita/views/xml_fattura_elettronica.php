<?php

//debug($fattura);

$this->load->config('geography');
$nazioniReversed = array_flip($this->config->item('nazioni'));

//die($dati['fattura']['documenti_contabilita_destinatario']);

$destinatario = json_decode($dati['fattura']['documenti_contabilita_destinatario'], true);
$settings = false;
if ($dati['fattura']['documenti_contabilita_azienda']) {
    $settings = $this->apilib->view('documenti_contabilita_settings', $dati['fattura']['documenti_contabilita_azienda']);
}
if ($settings) {
    $contabilita_settings = $settings;
} else {
    //Se il documento non è assegnato a un'azienda, premo i primi settings che trovo
    $contabilita_settings = $this->apilib->searchFirst('documenti_contabilita_settings', [], 0, 'documenti_contabilita_settings_id');
}

$codice_fiscale = (!empty($contabilita_settings['documenti_contabilita_settings_company_codice_fiscale']) ? $contabilita_settings['documenti_contabilita_settings_company_codice_fiscale'] : die(json_encode(['status' => 0, 'txt' => 'Codice Fiscale mittente non impostato'])));
/*
// Siamo sicuri di forzarlo a 0000000 se manca?
$codice_destinatario = (!empty($destinatario['codice_sdi']) ? $destinatario['codice_sdi'] : '0000000');

if ($codice_destinatario !== '0000000') {
$pec = (!empty($destinatario['pec']) ? $destinatario['pec'] : die('Pec e codice destinatario non impostati.'));
} else {
$pec = (!empty($destinatario['pec']) ? $destinatario['pec'] : '');
}
 */

// FIX Manuel 3 gennaio 2019: Se 0000000 = Privato quindi no check PEC e SDI. In alternativa uno dei due ci deve essere
// if ($destinatario['codice_sdi'] !== '0000000' || !empty($contabilita_settings['partita_iva'])) {
//     if (empty($destinatario['codice_sdi']) && empty($destinatario['pec'])) {
//         die(json_encode(['status' => 0, 'txt' => "Per le aziende la PEC o il Codice destinatario SDI devono essere compilati"]));
//     }
// }

$codice_destinatario = (!empty($destinatario['codice_sdi']) ? $destinatario['codice_sdi'] : '0000000');
$pec = (!empty($destinatario['pec']) ? $destinatario['pec'] : '');

/*echo "<pre>";
print_r($contabilita_settings);
exit();*/
$country = (!empty($contabilita_settings['documenti_contabilita_settings_company_country']) ? strtoupper($nazioniReversed[$contabilita_settings['documenti_contabilita_settings_company_country']]) : die(json_encode(['status' => 0, 'txt' => 'Nazione mittente non impostata correttamente'])));
if (!$country) {
    //debug($nazioniReversed);
    die(json_encode(['status' => 0, 'txt' => 'Nazione mittente non impostata correttamente']));
}
$vat_num = (!empty($contabilita_settings['documenti_contabilita_settings_company_vat_number']) ? $contabilita_settings['documenti_contabilita_settings_company_vat_number'] : die(json_encode(['status' => 0, 'txt' => 'Partita iva mittente non impostata'])));
$company_name = (!empty($contabilita_settings['documenti_contabilita_settings_company_name']) ? $contabilita_settings['documenti_contabilita_settings_company_name'] : die(json_encode(['status' => 0, 'txt' => 'Nome azienda mittente non mittente impostato'])));
$regime_fiscale = (!empty($contabilita_settings['documenti_contabilita_regimi_fiscali_valore']) ? $contabilita_settings['documenti_contabilita_regimi_fiscali_valore'] : die(json_encode(['status' => 0, 'txt' => 'Regime fiscale mittente non definito'])));
$company_address = (!empty($contabilita_settings['documenti_contabilita_settings_company_address']) ? $contabilita_settings['documenti_contabilita_settings_company_address'] : die(json_encode(['status' => 0, 'txt' => 'Indirizzo mittente non impostato'])));
$company_cap = (!empty($contabilita_settings['documenti_contabilita_settings_company_zipcode']) ? $contabilita_settings['documenti_contabilita_settings_company_zipcode'] : die(json_encode(['status' => 0, 'txt' => 'CAP azienda mittente non impostato'])));
$company_city = (!empty($contabilita_settings['documenti_contabilita_settings_company_city']) ? strtoupper($contabilita_settings['documenti_contabilita_settings_company_city']) : die(json_encode(['status' => 0, 'txt' => 'Città azienda non impostata'])));
$company_province = (!empty($contabilita_settings['documenti_contabilita_settings_company_province']) ? $contabilita_settings['documenti_contabilita_settings_company_province'] : die(json_encode(['status' => 0, 'txt' => 'Provincia azienda non impostata'])));

$company_ufficio_rea = (!empty($contabilita_settings['documenti_contabilita_settings_company_ufficio_rea']) ? $contabilita_settings['documenti_contabilita_settings_company_ufficio_rea'] : '');
$company_numero_rea = (!empty($contabilita_settings['documenti_contabilita_settings_company_numero_rea']) ? $contabilita_settings['documenti_contabilita_settings_company_numero_rea'] : '');
$company_capitale_sociale = (!empty($contabilita_settings['documenti_contabilita_settings_company_capitale_sociale']) ? $contabilita_settings['documenti_contabilita_settings_company_capitale_sociale'] : '');
$company_socio_unico = (!empty($contabilita_settings['documenti_contabilita_settings_socio_unico_value']) ? $contabilita_settings['documenti_contabilita_settings_socio_unico_value'] : '');
$company_stato_liquidazione = (!empty($contabilita_settings['documenti_contabilita_settings_stato_liquidazione_value']) ? $contabilita_settings['documenti_contabilita_settings_stato_liquidazione_value'] : '');

$dest_nazione = (strlen($destinatario['nazione']) > 2) ? strtoupper($nazioniReversed[ucfirst($destinatario['nazione'])]) : $destinatario['nazione'];
$dest_codicefiscale = ($destinatario['codice_fiscale']);

$dest_ragionesociale = str_ireplace(['&', '€', '™'], ['&amp;', 'EUR', ''], $destinatario['ragione_sociale']);

if ($dest_nazione == 'IT') {
    $dest_partitaiva = ($destinatario['partita_iva']);
} elseif ($destinatario['partita_iva']) {
    $dest_partitaiva = $destinatario['partita_iva'];
} else {
    $dest_partitaiva = substr($dest_ragionesociale, 0, 27);
}

$dest_indirizzo = substr($destinatario['indirizzo'], 0, 50);
$dest_cap = !empty($destinatario['cap']) ? $destinatario['cap'] : '00000';
$dest_citta = str_ireplace(['&', '€', '™'], ['&amp;', 'EUR', ''], ($destinatario['citta']));
$dest_provincia = ($destinatario['provincia']);
if ($dest_nazione != 'IT') {
    $dest_provincia = 'EE';
}

//$dest_nazione = (strtoupper($nazioniReversed[$destinatario['nazione']]));
$fattura_valuta = $dati['fattura']['documenti_contabilita_valuta'];
$fattura_dataemissione = date("Y-m-d", strtotime($dati['fattura']['documenti_contabilita_data_emissione']));
$fattura_numero = $dati['fattura']['documenti_contabilita_numero'];
$fattura_serie = $dati['fattura']['documenti_contabilita_serie'];
if ($fattura_serie) {
    $fattura_numero = "{$fattura_numero}/{$fattura_serie}";
}
$fattura_id = $dati['fattura']['documenti_contabilita_id'];
$articoli = $dati['fattura']['articoli'];
foreach ($articoli as $key => $articolo) {
    if ($articolo['documenti_contabilita_articoli_riga_desc']) {
        unset($articoli[$key]);
    }
}

$imponibile_scontato = $dati['fattura']['documenti_contabilita_imponibile_scontato'];
$fattura_totale = $dati['fattura']['documenti_contabilita_totale'];

$conto_corrente_iban = (!empty($dati['fattura']['conti_correnti_iban'])) ? $dati['fattura']['conti_correnti_iban'] : '';
$conto_corrente_nome_istituto = (!empty($dati['fattura']['conti_correnti_nome_istituto'])) ? $dati['fattura']['conti_correnti_nome_istituto'] : '';

if (!empty($dati['fattura']['documenti_contabilita_tipologia_fatturazione'])) {
    $tipologia_fatturazione = $this->apilib->view('documenti_contabilita_tipologie_fatturazione', $dati['fattura']['documenti_contabilita_tipologia_fatturazione']);

    $fattura_tipo = $tipologia_fatturazione['documenti_contabilita_tipologie_fatturazione_codice'];
} else {
    die(json_encode(['status' => 0, 'txt' => 'Tipologia fatturazione mancante']));
    switch ($dati['fattura']['documenti_contabilita_tipo']) {
        case 1:
        case '1':
            $fattura_tipo = 'TD01';
            break;
        case 4:
        case '4':
            $fattura_tipo = 'TD04';
            break;
        default:
            die(json_encode(['status' => 0, 'txt' => "Tipo documento '{$dati['fattura']['documenti_contabilita_tipo']}:{$dati['fattura']['documenti_contabilita_tipo_value']}' non riconosciuto!"]));
            break;
    }
}

//$tipo_ritenuta = (!empty($dest_partitaiva)) ? 'RT02' : 'RT01'; //2 persone giuridiche, 1 persone fisiche

//$fattura_scadenza = date("Y-m-d", strtotime($dati['fattura']['scadenze'][0]['documenti_contabilita_scadenze_scadenza']));

$segno = (($dati['fattura']['documenti_contabilita_tipo'] == 4 && false) ? '-' : '');

$_metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');
$metodi_pagamento = array_key_value_map($_metodi_pagamento, 'documenti_contabilita_metodi_pagamento_id', 'documenti_contabilita_metodi_pagamento_codice');

$_iva = $this->apilib->search('iva');
$classi_iva = [];
foreach ($_iva as $i) {
    $classi_iva[$i['iva_id']] = $i;
}

$rif_ddt = $dati['fattura']['documenti_contabilita_rif_ddt'];

if (!empty($rif_ddt)) {
    $ddt = $this->apilib->view('documenti_contabilita', $rif_ddt);
} else {
    $ddt = '';
}

if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    debug($articoli, true);
}

if ($dati['fattura']['documenti_contabilita_sconto_su_imponibile']) {
    $importo_sconto = number_format($dati['fattura']['documenti_contabilita_competenze'] - $dati['fattura']['documenti_contabilita_imponibile_scontato'], 2, '.', '');
} else {
    $importo_sconto = number_format($dati['fattura']['documenti_contabilita_iva'] + $dati['fattura']['documenti_contabilita_imponibile_scontato'] - $dati['fattura']['documenti_contabilita_totale'], 2, '.', '');
}
// debug($importo_sconto);
// debug($dati['fattura'],true);
//verifico se è privato o azienda
if ($dati['fattura']['documenti_contabilita_tipo_destinatario'] == 2 and (!empty($dati['fattura']['documenti_contabilita_customer_id']))) {
    if (!empty($dati['fattura']['customers_name'])) {
        $customers_name = $dati['fattura']['customers_name'];
    }
    if (!empty($dati['fattura']['customers_last_name'])) {
        $customers_last_name = $dati['fattura']['customers_last_name'];
    }
}
if (function_exists('extractJsonEditorData') == false) {
    function extractJsonEditorData($path, $data)
    {
        $pathParts = explode('/', $path); // Dividi il percorso in parti basate su '/'
        $currentData = $data; // Inizia con i dati JSON completi
        // debug($pathParts);
        // debug($data,true);
        foreach ($pathParts as $part) {
            // Se la parte corrente esiste nei dati correnti, vai più in profondità
            if (isset($currentData[$part])) {
                $currentData = $currentData[$part];
            } else {
                // Se una parte del percorso non esiste, termina (nodo non trovato)

                return null;
            }
        }

        // A questo punto, $currentData contiene i dati del nodo desiderato
        // Costruisci il frammento XML

        // Assicurati che l'array non sia vuoto e che l'ultimo elemento sia un nome valido
        $lastPart = end($pathParts); // Prende l'ultimo elemento dell'array senza usare indici negativi

        if (!$lastPart || preg_match('/[^a-zA-Z0-9_\-]/', $lastPart)) {
            // Se l'ultimo elemento è vuoto o contiene caratteri non validi, usa un placeholder
            $lastPart = 'InvalidNodeName';
        }

        $xml = new SimpleXMLElement("<$lastPart></$lastPart>");

        // Aggiungi i figli al nodo XML
        foreach ($currentData as $key => $value) {
            if (is_array($value)) {
                // Se il valore è un array, considera il caso di più nodi con lo stesso nome
                foreach ($value as $subValue) {
                    if (is_array($subValue)) {
                        $child = $xml->addChild($key);
                        arrayToXml($subValue, $child); // Funzione ausiliaria per gestire array nidificati
                    } else {
                        if ($subValue) {
                            $xml->addChild($key, htmlspecialchars($subValue));
                        }

                    }
                }
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }

        return str_replace('<?xml version="1.0"?>', '', $xml->asXML());
    }
}

if (function_exists('arrayToXml') == false) {
    function arrayToXml($data, &$xml)
    {
        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}

if (!empty($dati['fattura']['documenti_contabilita_json_editor_xml'])) {
    $json_editor_xml = json_decode($dati['fattura']['documenti_contabilita_json_editor_xml'], true);
} else {
    $json_editor_xml = [];
}
?>
<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<p:FatturaElettronica
    versione="<?php if ($dati['fattura']['documenti_contabilita_tipo_destinatario'] == 3): ?>FPA12<?php else: ?>FPR12<?php endif; ?>"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 https://www.fatturapa.gov.it/export/documenti/fatturapa/v1.2.1/Schema_del_file_xml_FatturaPA_v1.2.2.xsd">
    <FatturaElettronicaHeader>
        <DatiTrasmissione>
            <IdTrasmittente>
                <IdPaese>
                    <?php echo $country; ?>
                </IdPaese>
                <IdCodice>
                    <?php echo $codice_fiscale; ?>
                </IdCodice>
            </IdTrasmittente>

            <ProgressivoInvio>
                <?php echo str_pad($dati['fattura']['documenti_contabilita_progressivo_invio'], 10, '0', STR_PAD_LEFT); ?>
            </ProgressivoInvio>
            <?php if ($dati['fattura']['documenti_contabilita_tipo_destinatario'] == 3): ?>
                <FormatoTrasmissione>FPA12</FormatoTrasmissione>
            <?php else: ?>
                <FormatoTrasmissione>FPR12</FormatoTrasmissione>
            <?php endif; ?>
            <?php /* <!--            Da rivedere, non chiaro--> */ ?>
            <CodiceDestinatario>
                <?php echo $codice_destinatario; ?>
            </CodiceDestinatario>
            <?php /* <!--            Il campo sembra essere standard, http://www.fatturapa.gov.it/export/fatturazione/it/c-13.htm--> */ ?>
            <?php if (!empty($pec)): ?>
                <PECDestinatario>
                    <?php echo $pec; ?>
                </PECDestinatario>
            <?php endif; ?>
            <?php /* <!--<ContattiTrasmittente>
<Telefono></Telefono>
<Email></Email>
</ContattiTrasmittente>--> */ ?>

        </DatiTrasmissione>
        <CedentePrestatore>
            <DatiAnagrafici>
                <IdFiscaleIVA>
                    <IdPaese>
                        <?php echo $country; ?>
                    </IdPaese>
                    <IdCodice>
                        <?php echo $vat_num; ?>
                    </IdCodice>
                </IdFiscaleIVA>

                <?php /* <!--<CodiceFiscale></CodiceFiscale>--> */ ?>
                <Anagrafica>
                    <Denominazione>
                        <?php echo htmlspecialchars($company_name); ?>
                    </Denominazione>
                    <?php /* <!--<Nome></Nome>--> */ ?>
                    <?php /* <!--<Cognome></Cognome>--> */ ?>
                    <?php /* <!--<Titolo></Titolo>
<CodEORI></CodEORI>--> */ ?>
                </Anagrafica>

                <RegimeFiscale>
                    <?php echo $regime_fiscale; ?>
                </RegimeFiscale>
            </DatiAnagrafici>
            <Sede>
                <Indirizzo>
                    <?php echo htmlspecialchars($company_address); ?>
                </Indirizzo>
                <CAP>
                    <?php echo $company_cap; ?>
                </CAP>
                <Comune>
                    <?php echo $company_city; ?>
                </Comune>
                <Provincia>
                    <?php echo strtoupper($company_province); ?>
                </Provincia>
                <Nazione>
                    <?php echo $country; ?>
                </Nazione>
            </Sede>
            <?php if (!empty($company_numero_rea)): ?>
                <IscrizioneREA>
                    <Ufficio>
                        <?php echo strtoupper($company_ufficio_rea); ?>
                    </Ufficio>
                    <NumeroREA>
                        <?php echo $company_numero_rea; ?>
                    </NumeroREA>
                    <CapitaleSociale>
                        <?php echo number_format($company_capitale_sociale, 2, '.', ''); ?>
                    </CapitaleSociale>
                    <SocioUnico>
                        <?php echo $company_socio_unico; ?>
                    </SocioUnico>
                    <StatoLiquidazione>
                        <?php echo $company_stato_liquidazione; ?>
                    </StatoLiquidazione>
                </IscrizioneREA>
            <?php endif; ?>
            <?php if (!empty($dati_contratto = json_decode($dati['fattura']['documenti_contabilita_fe_dati_contratto'], true)) && array_filter($dati_contratto)): ?>
                <?php if (!empty($dati_contratto['riferimento_amministrazione'])): ?>
                    <RiferimentoAmministrazione>
                        <?php echo $dati_contratto['riferimento_amministrazione']; ?>
                    </RiferimentoAmministrazione>
                <?php endif; ?>
            <?php endif; ?>

            <?php /* <!--<RiferimentoAmministrazione><?php echo $company_name; ?></RiferimentoAmministrazione>--> */ ?>
        </CedentePrestatore>

        <CessionarioCommittente>
            <DatiAnagrafici>
                <?php if (!empty($dest_partitaiva)): ?>
                    <IdFiscaleIVA>
                        <IdPaese>
                            <?php echo $dest_nazione; ?>
                        </IdPaese>
                        <IdCodice>
                            <?php echo $dest_partitaiva; ?>
                        </IdCodice>
                    </IdFiscaleIVA>
                <?php endif; ?>
                <?php if ($dest_nazione == 'IT' && $dest_codicefiscale): ?>
                    <CodiceFiscale>
                        <?php echo strtoupper($dest_codicefiscale); ?>
                    </CodiceFiscale>
                <?php endif; ?>
                <Anagrafica>
                    <?php if (!isset($customers_name) and !isset($customers_last_name)): ?>
                        <Denominazione>
                            <?php echo htmlspecialchars($dest_ragionesociale); ?>
                        </Denominazione>
                    <?php else: ?>
                        <Nome><?php echo htmlspecialchars($customers_name); ?></Nome>
                        <Cognome><?php echo htmlspecialchars($customers_last_name); ?></Cognome>
                    <?php endif; ?>
                    <?php /* <!--<Titolo></Titolo>
<CodEORI></CodEORI>--> */ ?>
                </Anagrafica>
            </DatiAnagrafici>
            <Sede>
                <Indirizzo>
                    <?php echo $dest_indirizzo; ?>
                </Indirizzo>
                <?php /* <!--<NumeroCivico></NumeroCivico>--> */ ?>
                <CAP>
                    <?php echo $dest_cap; ?>
                </CAP>
                <Comune>
                    <?php echo $dest_citta; ?>
                </Comune>
                <Provincia>
                    <?php echo strtoupper($dest_provincia); ?>
                </Provincia>
                <Nazione>
                    <?php echo $dest_nazione; ?>
                </Nazione>
            </Sede>
        </CessionarioCommittente>

        <?php /*<TerzoIntermediarioOSoggettoEmittente>
<DatiAnagrafici>
<IdFiscaleIVA>
<IdPaese>IT</IdPaese>
<IdCodice>02675040303</IdCodice>
</IdFiscaleIVA>
<Anagrafica>
<Denominazione>H2 S.R.L. di Manuel Aiello e Matteo Puppis</Denominazione>
</Anagrafica>
</DatiAnagrafici>
</TerzoIntermediarioOSoggettoEmittente>
<SoggettoEmittente>TZ</SoggettoEmittente>
*/ ?>


    </FatturaElettronicaHeader>
    <FatturaElettronicaBody>
        <DatiGenerali>
            <DatiGeneraliDocumento>
                <?php /* <!-- TODO: Creazione campo --> */ ?>
                <TipoDocumento>
                    <?php echo $fattura_tipo; ?>
                </TipoDocumento>
                <Divisa>
                    <?php echo $fattura_valuta; ?>
                </Divisa>
                <Data>
                    <?php echo $fattura_dataemissione; ?>
                </Data>
                <Numero>
                    <?php echo $fattura_numero; ?>
                </Numero>
                <?php if (false && $dati['fattura']['documenti_contabilita_sconto_percentuale'] != 0): //20230906 - Rimosso in quanto ora lo sconto viene applicato alle singole righe articolo sopra ?>
                    <ScontoMaggiorazione>
                        <Tipo>SC</Tipo>
                        <Percentuale>
                            <?php echo number_format($dati['fattura']['documenti_contabilita_sconto_percentuale'], 2, '.', ''); ?>
                        </Percentuale>
                        <Importo>
                            <?php echo $importo_sconto ?>
                        </Importo>
                    </ScontoMaggiorazione>
                <?php endif; ?>

                <?php if ($dati['fattura']['documenti_contabilita_ritenuta_acconto_valore'] != 0): ?>
                    <DatiRitenuta>
                        <TipoRitenuta>
                            <?php echo $dati['fattura']['documenti_contabilita_tipo_ritenuta_value']; ?>
                        </TipoRitenuta>
                        <ImportoRitenuta>
                            <?php echo number_format($dati['fattura']['documenti_contabilita_ritenuta_acconto_valore'], 2, '.', ''); ?>
                        </ImportoRitenuta>
                        <AliquotaRitenuta>
                            <?php echo number_format($dati['fattura']['documenti_contabilita_ritenuta_acconto_perc'], 2, '.', ''); ?>
                        </AliquotaRitenuta>
                        <CausalePagamento>
                            <?php echo $dati['fattura']['documenti_contabilita_causale_pagamento_ritenuta']; ?>
                        </CausalePagamento>
                    </DatiRitenuta>
                <?php endif; ?>

                <?php if ($dati['fattura']['documenti_contabilita_importo_bollo'] != 0 && $dati['fattura']['documenti_contabilita_applica_bollo'] == DB_BOOL_TRUE && $dati['fattura']['documenti_contabilita_bollo_virtuale'] == DB_BOOL_TRUE): ?>
                    <DatiBollo>
                        <BolloVirtuale>SI</BolloVirtuale>
                        <ImportoBollo>
                            <?php echo number_format($dati['fattura']['documenti_contabilita_importo_bollo'], 2, '.', ''); ?>
                        </ImportoBollo>

                    </DatiBollo>
                <?php endif; ?>


                <?php
                $importoCassa = 0;
                if ($dati['fattura']['documenti_contabilita_cassa_professionisti_perc'] > 0) {
                    $cassa_tipo = $this->apilib->view('documenti_contabilita_cassa_professionisti_tipo', $dati['fattura']['documenti_contabilita_cassa_professionisti_tipo']);
                    if ($cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_iva'] && $iva_cassa = $this->apilib->view('iva', $cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_iva'])) {
                        //debug($iva_cassa,true);                
                        $aliquota_iva_cassa = $iva_cassa['iva_valore'];
                        $natura_cassa = $iva_cassa['iva_codice'];
                    } else {
                        $aliquota_iva_cassa = 0;
                        $natura_cassa = '';
                    }

                    //debug($dati['fattura'], true);
                    $percentuale_contributo = $dati['fattura']['documenti_contabilita_cassa_professionisti_perc'];
                    $imponibile_fattura = $dati['fattura']['documenti_contabilita_competenze'];
                    $imponibile_calcolo = $imponibile_fattura;
                    $importoCassa = $imponibile_fattura / 100 * $percentuale_contributo;
                    //debug($imponibile_fattura,true); 
                    ?>
                    <DatiCassaPrevidenziale>
                        <TipoCassa>
                            <?php echo $cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_codice']; ?>
                        </TipoCassa>
                        <AlCassa>
                            <?php echo $percentuale_contributo; ?>
                        </AlCassa>
                        <ImportoContributoCassa>
                            <?php echo number_format(round($importoCassa, 2), 2, '.', ''); ?>
                        </ImportoContributoCassa>
                        <ImponibileCassa>
                            <?php echo $imponibile_calcolo; ?>
                        </ImponibileCassa>
                        <AliquotaIVA>
                            <?php echo $aliquota_iva_cassa; ?>
                        </AliquotaIVA>
                        <?php /*<Ritenuta></Ritenuta>*/ ?>
                        <?php if ($aliquota_iva_cassa == 0): ?>
                            <Natura>
                                <?php echo $natura_cassa; ?>
                            </Natura>
                        <?php endif; ?>
                        <?php /*<RiferimentoAmministrazione><?php echo $cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_value']; ?></RiferimentoAmministrazione>*/ ?>
                    </DatiCassaPrevidenziale>
                <?php } ?>
                <?php
                $importoCassa2 = 0;
                if ($dati['fattura']['documenti_contabilita_rivalsa_inps_perc'] > 0) {
                    $cassa_tipo = $this->apilib->view('documenti_contabilita_cassa_professionisti_tipo', $dati['fattura']['documenti_contabilita_cassa_professionisti_tipo']);
                    if ($cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_iva'] && $iva_cassa = $this->apilib->view('iva', $cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_iva'])) {
                        //debug($iva_cassa,true);                
                        $aliquota_iva_cassa = $iva_cassa['iva_valore'];
                        $natura_cassa = $iva_cassa['iva_codice'];
                    } else {
                        $aliquota_iva_cassa = 0;
                        $natura_cassa = '';
                    }

                    //debug($dati['fattura'], true);
                    $percentuale_contributo = $dati['fattura']['documenti_contabilita_rivalsa_inps_perc'];
                    $imponibile_fattura = $dati['fattura']['documenti_contabilita_competenze'];
                    $imponibile_calcolo = $imponibile_fattura;
                    $importoCassa2 = $imponibile_fattura / 100 * $percentuale_contributo;
                    //debug($imponibile_fattura,true); 
                    ?>
                    <DatiCassaPrevidenziale>
                        <TipoCassa>
                            <?php echo $cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_codice']; ?>
                        </TipoCassa>
                        <AlCassa>
                            <?php echo $percentuale_contributo; ?>
                        </AlCassa>
                        <ImportoContributoCassa>
                            <?php echo number_format(round($importoCassa2, 2), 2, '.', ''); ?>
                        </ImportoContributoCassa>
                        <ImponibileCassa>
                            <?php echo $imponibile_calcolo; ?>
                        </ImponibileCassa>
                        <AliquotaIVA>
                            <?php echo $aliquota_iva_cassa; ?>
                        </AliquotaIVA>
                        <?php /*<Ritenuta></Ritenuta>*/ ?>
                        <?php if ($aliquota_iva_cassa == 0): ?>
                            <Natura>
                                <?php echo $natura_cassa; ?>
                            </Natura>
                        <?php endif; ?>
                        <?php /*<RiferimentoAmministrazione><?php echo $cassa_tipo['documenti_contabilita_cassa_professionisti_tipo_value']; ?></RiferimentoAmministrazione>*/ ?>
                    </DatiCassaPrevidenziale>
                <?php } ?>
                <?php

                if ($dati['fattura']['documenti_contabilita_split_payment'] == DB_BOOL_TRUE) {
                    $importo_totale_documento = number_format($fattura_totale + $dati['fattura']['documenti_contabilita_iva'], 2, '.', '');
                } elseif ($dati['fattura']['documenti_contabilita_ritenuta_acconto_valore'] != 0) {
                    $importo_totale_documento = number_format($fattura_totale + $dati['fattura']['documenti_contabilita_ritenuta_acconto_valore'], 2, '.', '');
                } else {
                    $importo_totale_documento = number_format($fattura_totale, 2, '.', '');
                }
                ?>

                <ImportoTotaleDocumento>
                    <?php echo trim($importo_totale_documento); ?>
                </ImportoTotaleDocumento>
                <?php if (!empty($dati['fattura']['documenti_contabilita_oggetto'])): ?>
                    <Causale>
                        <?php echo $dati['fattura']['documenti_contabilita_oggetto']; ?>
                    </Causale>
                <?php endif; ?>
                <?php /* <!--
<Arrotondamento></Arrotondamento>

<Causale></Causale>
<Art73></Art73>--> */ ?>
            </DatiGeneraliDocumento>
            <?php
            $ordine_acquisto = json_decode($dati['fattura']['documenti_contabilita_fe_ordineacquisto'] , true);
            $ordine_acquisto = array_filter($ordine_acquisto);
            unset($ordine_acquisto['riferimento_amministrazione']);
            
            ?>
            <?php if (!empty($ordine_acquisto)): ?>
                <DatiOrdineAcquisto>
                    <?php if (!empty($ordine_acquisto['riferimento_numero_linea'])): ?>
                        <RiferimentoNumeroLinea>
                            <?php echo $ordine_acquisto['riferimento_numero_linea']; ?>
                        </RiferimentoNumeroLinea>
                    <?php endif; ?>
                    <?php if (!empty($ordine_acquisto['id_documento'])): ?>
                        <IdDocumento>
                            <?php echo $ordine_acquisto['id_documento']; ?>
                        </IdDocumento>
                    <?php endif; ?>
                    <?php if (!empty($ordine_acquisto['data'])): ?><Data>
                            <?php echo $ordine_acquisto['data']; ?>
                        </Data>
                    <?php endif; ?>
                    <?php if (!empty($ordine_acquisto['num_item'])): ?>
                        <NumItem>
                            <?php echo $ordine_acquisto['num_item']; ?>
                        </NumItem>
                    <?php endif; ?>
                    <?php if (!empty($ordine_acquisto['codice_commessa_convenzione'])): ?>
                        <CodiceCommessaConvenzione>
                            <?php echo $ordine_acquisto['codice_commessa_convenzione']; ?>
                        </CodiceCommessaConvenzione>
                    <?php endif; ?>
                    <?php if (!empty($ordine_acquisto['codice_cup'])): ?>
                        <CodiceCUP>
                            <?php echo $ordine_acquisto['codice_cup']; ?>
                        </CodiceCUP>
                    <?php endif; ?>
                    <?php if (!empty($ordine_acquisto['codice_cig'])): ?>
                        <CodiceCIG>
                            <?php echo $ordine_acquisto['codice_cig']; ?>
                        </CodiceCIG>
                    <?php endif; ?>
                </DatiOrdineAcquisto>
            <?php endif; ?>

            <?php if (!empty($ddt)): ?>
                <DatiDDT>
                    <NumeroDDT>
                        <?= $ddt['documenti_contabilita_numero']; ?>
                    </NumeroDDT>
                    <DataDDT><?= date('Y-m-d', strtotime($ddt['documenti_contabilita_data_emissione'])); ?></DataDDT>
                    <!--<RiferimentoNumeroLinea></RiferimentoNumeroLinea>-->
                </DatiDDT>
            <?php endif; ?>
                <?php
                $dati_contratto = json_decode($dati['fattura']['documenti_contabilita_fe_dati_contratto'] , true);
                $dati_contratto = array_filter($dati_contratto);
                unset($dati_contratto['riferimento_amministrazione']);
                ?>
            <?php if (!empty($dati_contratto)): ?>
                <DatiContratto>
                    <?php if (!empty($dati_contratto['riferimento_numero_linea'])): ?>
                        <RiferimentoNumeroLinea>
                            <?php echo $dati_contratto['riferimento_numero_linea']; ?>
                        </RiferimentoNumeroLinea>
                    <?php endif; ?>
                    <?php if (!empty($dati_contratto['id_documento'])): ?>
                        <IdDocumento>
                            <?php echo $dati_contratto['id_documento']; ?>
                        </IdDocumento>
                    <?php endif; ?>
                    <?php if (!empty($dati_contratto['data'])): ?><Data>
                            <?php echo $dati_contratto['data']; ?>
                        </Data>
                    <?php endif; ?>
                    <?php if (!empty($dati_contratto['num_item'])): ?>
                        <NumItem>
                            <?php echo $dati_contratto['num_item']; ?>
                        </NumItem>
                    <?php endif; ?>
                    <?php if (!empty($dati_contratto['codice_commessa_convenzione'])): ?>
                        <CodiceCommessaConvenzione>
                            <?php echo $dati_contratto['codice_commessa_convenzione']; ?>
                        </CodiceCommessaConvenzione>
                    <?php endif; ?>
                    <?php if (!empty($dati_contratto['codice_cup'])): ?>
                        <CodiceCUP>
                            <?php echo $dati_contratto['codice_cup']; ?>
                        </CodiceCUP>
                    <?php endif; ?>
                    <?php if (!empty($dati_contratto['codice_cig'])): ?>
                        <CodiceCIG>
                            <?php echo $dati_contratto['codice_cig']; ?>
                        </CodiceCIG>
                    <?php endif; ?>
                </DatiContratto>
            <?php endif; ?>
            <?php /* <!--
<DatiOrdineAcquisto>
<RiferimentoNumeroLinea></RiferimentoNumeroLinea>
<IdDocumento><?php echo $fattura_id; ?></IdDocumento>
<Data></Data>
<NumItem><?php echo $fattura_id; ?></NumItem>
<CodiceCommessaConvenzione></CodiceCommessaConvenzione>
<CodiceCUP></CodiceCUP>
<CodiceCIG></CodiceCIG>
</DatiOrdineAcquisto>
<DatiContratto>
<RiferimentoNumeroLinea>
<IdDocumento></IdDocumento>
<Data></Data>
<NumItem></NumItem>
<CodiceCommessaConvenzione></CodiceCommessaConvenzione>
<CodiceCUP></CodiceCUP>
<CodiceCIG></CodiceCIG>
</DatiContratto>
<DatiConvenzione>
<RiferimentoNumeroLinea></RiferimentoNumeroLinea>
<IdDocumento></IdDocumento>
<Data></Data>
<NumItem></NumItem>
<CodiceCommessaConvenzione></CodiceCommessaConvenzione>
<CodiceCUP></CodiceCUP>
<CodiceCIG></CodiceCIG>
</DatiConvenzione>
<DatiRicezione>
<RiferimentoNumeroLinea></RiferimentoNumeroLinea>
<IdDocumento></IdDocumento>
<Data></Data>
<NumItem></NumItem>
<CodiceCommessaConvenzione></CodiceCommessaConvenzione>
<CodiceCUP></CodiceCUP>
<CodiceCIG></CodiceCIG>
</DatiRicezione>
*/ ?>
            <?php
            $path = "FatturaElettronica/FatturaElettronicaBody/DatiGenerali/DatiFattureCollegate";
            echo extractJsonEditorData($path, $json_editor_xml); ?>
            <?php /*
              */ ?>
            <?php
            // $path = "FatturaElettronica/FatturaElettronicaBody/DatiGenerali/DatiSAL";
// echo extractJsonEditorData($path, $json_editor_xml); ?>
            <?php /*
              */ ?>
            <?php
            // $path = "FatturaElettronica/FatturaElettronicaBody/DatiGenerali/DatiDDT";
// echo extractJsonEditorData($path, $json_editor_xml); ?>
            <?php /*
              */ ?>
            <?php
            // $path = "FatturaElettronica/FatturaElettronicaBody/DatiGenerali/DatiTrasporto";
// echo extractJsonEditorData($path, $json_editor_xml); ?>
            <?php /*
<NormaDiRiferimento></NormaDiRiferimento>
<FatturaPrincipale>
<NumeroFatturaPrincipale></NumeroFatturaPrincipale>
<DataFatturaPrincipale></DataFatturaPrincipale>
</FatturaPrincipale>--> */ ?>

        </DatiGenerali>
        <DatiBeniServizi>
            <?php foreach ($articoli as $key => $articolo): ?>

                <DettaglioLinee>
                    <NumeroLinea>
                        <?php echo $key + 1; ?>
                    </NumeroLinea>
                    <?php /* <!--<TipoCessionePrestazione></TipoCessionePrestazione>--> */ ?>

                    <?php if (!empty(trim($articolo['documenti_contabilita_articoli_codice']))): ?>
                        <CodiceArticolo>
                            <CodiceTipo>SKU</CodiceTipo>
                            <CodiceValore>
                                <?= htmlspecialchars($articolo['documenti_contabilita_articoli_codice']); ?>
                            </CodiceValore>
                        </CodiceArticolo>
                    <?php endif; ?>

                    <?php if (!empty(trim($articolo['documenti_contabilita_articoli_codice_ean']))): ?>
                        <CodiceArticolo>
                            <CodiceTipo>EAN</CodiceTipo>
                            <CodiceValore>
                                <?= htmlspecialchars($articolo['documenti_contabilita_articoli_codice_ean']); ?>
                            </CodiceValore>
                        </CodiceArticolo>
                    <?php endif; ?>

                    <?php if (!empty(trim($articolo['documenti_contabilita_articoli_codice_asin']))): ?>
                        <CodiceArticolo>
                            <CodiceTipo>ASIN</CodiceTipo>
                            <CodiceValore>
                                <?= htmlspecialchars($articolo['documenti_contabilita_articoli_codice_asin']); ?>
                            </CodiceValore>
                        </CodiceArticolo>
                    <?php endif; ?>

                    <?php // michael - 06.09.2023 - ho cambiato il replace di € da &euro; a EUR perchè altrimenti si rompe completamente la generazione dell'xml con errore "Message: DOMDocument::loadXML(): Entity 'euro' not defined in Entity, line: 138".. E' già stato testato un replace con ad esempio l'unicode ma poi non viene interpretato ne visualizzato correttamente sulle conversioni in pdf e nel xml da inviare allo sdi ?>
                    <Descrizione>
                        <?php echo substr(str_ireplace(['&', '€', '™', '<', '>'], ['&amp;', 'EUR', '', '&lt;', '&gt;'], $articolo['documenti_contabilita_articoli_name'] . ' - ' . $articolo['documenti_contabilita_articoli_descrizione']), 0, 900); ?>
                    </Descrizione>
                    <Quantita>
                        <?php echo number_format($articolo['documenti_contabilita_articoli_quantita'], 2, '.', ''); ?>
                    </Quantita>
                    <UnitaMisura>
                        <?php echo ($articolo['documenti_contabilita_articoli_unita_misura']) ?: 'Pz'; ?>
                    </UnitaMisura>

                    <?php /* <!--<DataInizioPeriodo></DataInizioPeriodo>
<DataFinePeriodo></DataFinePeriodo>--> */ ?>
                    <?php
                    $prezzo_senza_seri = number_format($articolo['documenti_contabilita_articoli_prezzo'], 8, '.', '');
                    $prezzo_esploso = explode('.', $prezzo_senza_seri);
                    $parte_decimale = $prezzo_esploso[1];
                    $parte_intera = $prezzo_esploso[0];

                    while (strrpos($parte_decimale, '0') === strlen($parte_decimale) - 1 && strlen($parte_decimale) > 2) {
                        $parte_decimale = rtrim($parte_decimale, '0');
                    }

                    $prezzo_esploso[1] = $parte_decimale;
                    $prezzo_senza_seri = implode('.', $prezzo_esploso);
                    ?>

                    <PrezzoUnitario>
                        <?php echo number_format($prezzo_senza_seri, max([2, strlen($parte_decimale)]), '.', ''); ?>
                    </PrezzoUnitario>

                    <?php
                    //Se ci sono due sconti, prima devo applicarne uno, poi l'altro... non posso sommare i due sconti e dividere per 100!
                    $sconto_da_applicare = ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE) ? ($articolo['documenti_contabilita_articoli_sconto'] + ($dati['fattura']['documenti_contabilita_sconto_percentuale'] / 100 * (100 - $articolo['documenti_contabilita_articoli_sconto']))) : 0;
                    if ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE && $articolo['documenti_contabilita_articoli_sconto2'] > 0) {
                        $sconto_da_applicare += (100 - $sconto_da_applicare) / 100 * $articolo['documenti_contabilita_articoli_sconto2'];
                    }
                    if ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE && $articolo['documenti_contabilita_articoli_sconto3'] > 0) {
                        $sconto_da_applicare += (100 - $sconto_da_applicare) / 100 * $articolo['documenti_contabilita_articoli_sconto3'];
                    }
                    ?>

                    <?php if ($sconto_da_applicare > 0 && ($articolo['documenti_contabilita_articoli_sconto'] > 0 || $articolo['documenti_contabilita_articoli_applica_sconto'] || ($dati['fattura']['documenti_contabilita_sconto_percentuale'] > 0 && $dati['fattura']['documenti_contabilita_sconto_su_imponibile']))): ?>
                        <ScontoMaggiorazione>

                            <Tipo>SC</Tipo>
                            <Percentuale>
                                <?php echo number_format($sconto_da_applicare, 2, '.', ''); ?>
                            </Percentuale>
                        </ScontoMaggiorazione>
                    <?php endif; ?>

                    <PrezzoTotale>
                        <?php echo $segno . number_format((($articolo['documenti_contabilita_articoli_prezzo'] * $articolo['documenti_contabilita_articoli_quantita']) / 100 * (100 - $sconto_da_applicare)), 2, '.', ''); ?>
                    </PrezzoTotale>
                    <?php /*<PrezzoTotale><?php echo $segno . number_format(($articolo['documenti_contabilita_articoli_prezzo'] / 100 * (100 - ($articolo['documenti_contabilita_articoli_sconto']))) * $articolo['documenti_contabilita_articoli_quantita'], 2, '.', ''); ?></PrezzoTotale>*/ ?>
                    <AliquotaIVA>
                        <?php echo number_format($articolo['iva_valore'], 2, '.', ''); ?>
                    </AliquotaIVA>

                    <?php if (!empty($articolo['iva_codice'])): ?>
                        <Natura>
                            <?php echo $articolo['iva_codice']; ?>
                        </Natura>
                        <?php /* <RiferimentoAmministrazione><?php echo $articolo['iva_descrizione']; ?></RiferimentoAmministrazione>*/ ?>
                    <?php endif; ?>
                    <?php
                    /* 
                    <Ritenuta></Ritenuta>
*/
                    $attributi_avanzati_assoc = [];
                    if (!empty($articolo['documenti_contabilita_articoli_attributi_sdi']) && $attributi_avanzati = json_decode($articolo['documenti_contabilita_articoli_attributi_sdi'], true)) {

                        foreach ($attributi_avanzati as $attributo_sdi) {
                            if (!empty($attributo_sdi['value'])) {
                                $attributi_avanzati_assoc[$attributo_sdi['name']] = $attributo_sdi['value'];
                            }
                        }
                    }
                    if ($attributi_avanzati_assoc):
                        ?>
                        <AltriDatiGestionali>
                            <?php if (!empty($attributi_avanzati_assoc['AltriDatiGestionali[TipoDato]'])): ?>
                                <TipoDato>
                                    <?php echo $attributi_avanzati_assoc['AltriDatiGestionali[TipoDato]']; ?>
                                </TipoDato>
                            <?php endif; ?>
                            <?php if (!empty($attributi_avanzati_assoc['AltriDatiGestionali[RiferimentoTesto]'])): ?>
                                <RiferimentoTesto>
                                    <?php echo $attributi_avanzati_assoc['AltriDatiGestionali[RiferimentoTesto]']; ?>
                                </RiferimentoTesto>
                            <?php endif; ?>
                            <?php if (!empty($attributi_avanzati_assoc['AltriDatiGestionali[RiferimentoNumero]'])): ?>
                                <RiferimentoNumero>
                                    <?php echo $attributi_avanzati_assoc['AltriDatiGestionali[RiferimentoNumero]']; ?>
                                </RiferimentoNumero>
                            <?php endif; ?>
                            <?php if (!empty($attributi_avanzati_assoc['AltriDatiGestionali[RiferimentoData]'])): ?>
                                <RiferimentoData>
                                    <?php echo $attributi_avanzati_assoc['AltriDatiGestionali[RiferimentoData]']; ?>
                                </RiferimentoData>
                            <?php endif; ?>
                        </AltriDatiGestionali>
                    <?php endif; ?>
                </DettaglioLinee>
            <?php endforeach; ?>


            <?php foreach (json_decode($dati['fattura']['documenti_contabilita_iva_json'], true) as $iva_id => $__iva):
                if ($iva_id == 0) {
                    continue;
                }
                $aliquota = $__iva[0];
                $iva = $__iva[1]; ?>
                <DatiRiepilogo>
                    <AliquotaIVA>
                        <?php echo number_format($aliquota, 2, '.', ''); ?>
                    </AliquotaIVA>

                    <?php if (!$aliquota): ?>
                        <Natura>
                            <?php echo $classi_iva[$iva_id]['iva_codice']; ?>
                        </Natura>

                    <?php endif; ?>

                    <?php /* <!--<SpeseAccessorie></SpeseAccessorie>
<Arrotondamento></Arrotondamento>--> */ ?>

                    <?php
                    ini_set('precision', 17);
                    if (array_key_exists($iva_id, json_decode($dati['fattura']['documenti_contabilita_imponibile_iva_json'], true))) {

                        $imponibile = json_decode($dati['fattura']['documenti_contabilita_imponibile_iva_json'], true)[$iva_id][1];



                    } else {
                        //devo fare così per capire quant'è la base imponibile di questa classe iva sulla quale è stata calcolata l'imposta totale)
                        $imponibile = 0;
                        //debug($articoli, true);
                        foreach ($articoli as $articolo) {
                            if ($articolo['documenti_contabilita_articoli_iva_id'] == $iva_id) {
                                if ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE) {
                                    $sconto_da_applicare = ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE) ? ($articolo['documenti_contabilita_articoli_sconto'] + ($dati['fattura']['documenti_contabilita_sconto_percentuale'] / 100 * (100 - $articolo['documenti_contabilita_articoli_sconto']))) : 0;
                                    if ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE && $articolo['documenti_contabilita_articoli_sconto2'] > 0) {
                                        $sconto_da_applicare += (100 - $sconto_da_applicare) / 100 * $articolo['documenti_contabilita_articoli_sconto2'];
                                    }
                                    if ($articolo['documenti_contabilita_articoli_applica_sconto'] == DB_BOOL_TRUE && $articolo['documenti_contabilita_articoli_sconto3'] > 0) {
                                        $sconto_da_applicare += (100 - $sconto_da_applicare) / 100 * $articolo['documenti_contabilita_articoli_sconto3'];
                                    }




                                    $imponibile += (($articolo['documenti_contabilita_articoli_prezzo'] * $articolo['documenti_contabilita_articoli_quantita']) / 100) * (100 - $sconto_da_applicare);
                                } else {
                                    //$imponibile += (($articolo['documenti_contabilita_articoli_prezzo'] * $articolo['documenti_contabilita_articoli_quantita']) / 100) * (100 - $dati['fattura']['documenti_contabilita_sconto_percentuale']);
                                    $imponibile += ($articolo['documenti_contabilita_articoli_prezzo'] * $articolo['documenti_contabilita_articoli_quantita']);
                                }
                            } else {
                                // debug($iva_id);
                                // debug($articolo,true);
                            }
                        }
                    }

                    $imponibile_rounded = round($imponibile, 2, PHP_ROUND_HALF_DOWN);
                    //debug(number_format($imponibile_rounded , 2, '.', ''), true);
                    ?>

                    <ImponibileImporto>
                        <?php echo number_format($imponibile_rounded + $importoCassa + $importoCassa2, 2, '.', ''); ?>
                    </ImponibileImporto>
                    <Imposta>
                        <?php echo number_format($iva, 2, '.', ''); ?>
                    </Imposta>
                    <EsigibilitaIVA>
                        <?php if ($dati['fattura']['documenti_contabilita_split_payment'] == DB_BOOL_TRUE): ?>S
                        <?php else: ?>I
                        <?php endif; ?>
                    </EsigibilitaIVA>
                    <?php if (!empty($classi_iva[$iva_id]['iva_descrizione'])): ?>
                        <RiferimentoNormativo>
                            <?php echo $classi_iva[$iva_id]['iva_descrizione']; ?>
                        </RiferimentoNormativo>
                    <?php elseif ($dati['fattura']['documenti_contabilita_split_payment'] == DB_BOOL_TRUE): ?>
                        <RiferimentoNormativo>Emessa ai sensi dell'articolo 17 ter DPR 633/1972 e s.m.i.</RiferimentoNormativo>
                    <?php endif; ?>

                </DatiRiepilogo>
            <?php endforeach; ?>
        </DatiBeniServizi>

        <?php /* <!--<DatiVeicoli>
<Data></Data>
<TotalePercorso></TotalePercorso>
</DatiVeicoli>--> */ ?>
        <?php foreach ($dati['fattura']['scadenze'] as $key => $scadenza): ?>
            <?php if ($scadenza['documenti_contabilita_scadenze_ammontare'] != '0.00'): ?>
                <DatiPagamento>
                    <CondizioniPagamento>
                        <?php if (count($dati['fattura']['scadenze']) > 1 && $key == 0): //Acconto
                                        ?>TP03
                        <?php elseif (count($dati['fattura']['scadenze']) > 1): //A rate
                                        ?>TP01
                        <?php else: //Soluzione unice
                                        ?>TP02
                        <?php endif; ?>
                    </CondizioniPagamento>
                    <DettaglioPagamento>
                        <?php /* <!--<Beneficiario></Beneficiario>--> */ ?>
                        <ModalitaPagamento>
                            <?php echo $metodi_pagamento[$scadenza['documenti_contabilita_scadenze_saldato_con']]; ?>
                        </ModalitaPagamento>
                        <?php /* <!--<DataRiferimentoTerminiPagamento></DataRiferimentoTerminiPagamento>
      <GiorniTerminiPagamento></GiorniTerminiPagamento>--> */ ?>


                        <DataScadenzaPagamento>
                            <?php echo date('Y-m-d', strtotime($scadenza['documenti_contabilita_scadenze_scadenza'])); ?>
                        </DataScadenzaPagamento>

                        <ImportoPagamento>
                            <?php echo $segno . number_format($scadenza['documenti_contabilita_scadenze_ammontare'], 2, '.', ''); ?>
                        </ImportoPagamento>
                        <?php /* <!--<CodUfficioPostale></CodUfficioPostale>
      <CognomeQuietanzante></CognomeQuietanzante>
      <NomeQuietanzante></NomeQuietanzante>
      <CFQuietanzante></CFQuietanzante>
      <TitoloQuietanzante></TitoloQuietanzante>
      <IstitutoFinanziario></IstitutoFinanziario>-->*/ ?>
                        <?php if (!empty($conto_corrente_nome_istituto)): ?>
                            <IstitutoFinanziario>
                                <?php echo $conto_corrente_nome_istituto; ?>
                            </IstitutoFinanziario>
                        <?php endif; ?>
                        <?php if (!empty($conto_corrente_iban)): ?>
                            <IBAN>
                                <?php echo $conto_corrente_iban; ?>
                            </IBAN>
                        <?php endif; ?>

                        <?php /*<!--<ABI></ABI>
      <CAB></CAB>
      <BIC></BIC>
      <ScontoPagamentoAnticipato></ScontoPagamentoAnticipato>
      <DataLimitePagamentoAnticipato></DataLimitePagamentoAnticipato>
      <PenalitaPagamentiRitardati></PenalitaPagamentiRitardati>
      <DataDecorrenzaPenale></DataDecorrenzaPenale>
      --> */ ?>
                        <?php if (1 == 5 && $dati['fattura']['documenti_contabilita_note_interne']): ?>
                            <CodicePagamento>
                                <?php //echo substr($dati['fattura']['documenti_contabilita_note_interne'], 0, 59);
                                            ?>
                            </CodicePagamento>
                        <?php endif; ?>
                    </DettaglioPagamento>
                </DatiPagamento>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($dati['fattura']['documenti_contabilita_file_pdf'] && file_exists(FCPATH . "uploads/" . $dati['fattura']['documenti_contabilita_file_pdf'])): ?>
            <Allegati>
                <NomeAttachment>
                    <?php echo basename(FCPATH . "uploads/" . $dati['fattura']['documenti_contabilita_file_pdf']); ?>
                </NomeAttachment>
                <FormatoAttachment>PDF</FormatoAttachment>
                <Attachment>
                    <?php echo base64_encode(file_get_contents(FCPATH . "uploads/" . $dati['fattura']['documenti_contabilita_file_pdf'])); ?>
                </Attachment>
            </Allegati>
        <?php endif; ?>
    </FatturaElettronicaBody>
</p:FatturaElettronica>