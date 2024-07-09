<?php
    $this->load->config('geography');
    $nazioniReversed = array_flip($this->config->item('nazioni'));
    $destinatario = json_decode($dati['fattura']['documenti_contabilita_destinatario'], true);
?><?xml version="1.0" encoding="UTF-8" ?>
<p:FatturaElettronica versione="FPR12" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
    <FatturaElettronicaHeader>
        <DatiTrasmissione>
            <IdTrasmittente>
                <IdPaese><?php echo strtoupper($nazioniReversed[$this->settings['settings_company_country']]); ?></IdPaese><!-- <<< <<< <<< REQUIRED-->
                <IdCodice><?php echo $this->settings['settings_company_codice_fiscale']; ?></IdCodice><!-- <<< <<< <<< REQUIRED-->
            </IdTrasmittente>
<!--            TODO: creazione campo, identificativo alfanumerico a discrezione dell'azienda-->
            <ProgressivoInvio>00001</ProgressivoInvio><!-- <<< <<< <<< REQUIRED-->
<!--            valore fisso-->
            <FormatoTrasmissione>SDI11</FormatoTrasmissione><!-- <<< <<< <<< REQUIRED-->
<!--            Da rivedere, non chiaro-->
            <CodiceDestinatario>0000000</CodiceDestinatario><!-- <<< <<< <<< REQUIRED-->
<!--            Il campo sembra essere standard, http://www.fatturapa.gov.it/export/fatturazione/it/c-13.htm-->
            <PECDestinatario><?php echo ((!empty($destinatario['pec']))?$destinatario['pec']:''); ?></PECDestinatario>
            <ContattiTrasmittente>
                <Telefono></Telefono>
                <Email></Email>
            </ContattiTrasmittente>
        </DatiTrasmissione>
        <CedentePrestatore>
            <DatiAnagrafici>
                <IdFiscaleIVA>
<!--                TODO cambiare Italy in Italia nei settings-->
                    <IdPaese><?php echo strtoupper($nazioniReversed[$this->settings['settings_company_country']]); ?></IdPaese><!-- <<< <<< <<< REQUIRED-->
                    <IdCodice><?php echo $this->settings['settings_company_vat_number']; ?></IdCodice><!-- <<< <<< <<< REQUIRED-->
                </IdFiscaleIVA>
                <CodiceFiscale></CodiceFiscale>
                <Anagrafica>
                    <Denominazione><?php echo $this->settings['settings_company_name']; ?></Denominazione><!-- <<< <<< <<< REQUIRED-->
                    <Nome></Nome><!-- <<< <<< <<< REQUIRED se non presente la denominazione.-->
                    <Cognome></Cognome><!-- <<< <<< <<< REQUIRED se non presente la denominazione.-->
                    <Titolo></Titolo>
                    <CodEORI></CodEORI>
                </Anagrafica>
                <AlboProfessionale></AlboProfessionale>
                <ProvinciaAlbo></ProvinciaAlbo>
                <NumeroIscrizioneAlbo></NumeroIscrizioneAlbo>
                <DataIscrizioneAlbo></DataIscrizioneAlbo>
<!--                TODO: creazione campo deve contenere uno dei codici previsti nella lista valori associata-->
                <RegimeFiscale><?php echo (!empty($this->settings['settings_company_regime']))?$this->settings['settings_company_regime']:'RF01'; ?></RegimeFiscale><!-- <<< <<< <<< REQUIRED-->
            </DatiAnagrafici>
            <Sede>
                <Indirizzo><?php echo $this->settings['settings_company_address']; ?></Indirizzo><!-- <<< <<< <<< REQUIRED-->
                <NumeroCivico></NumeroCivico>
<!--                TODO: creazione campo-->
                <CAP><?php echo $this->settings['settings_company_cap']; ?></CAP><!-- <<< <<< <<< REQUIRED-->
                <Comune><?php echo strtoupper($this->settings['settings_company_city']); ?></Comune><!-- <<< <<< <<< REQUIRED-->
                <Provincia><?php echo $this->settings['settings_company_province']; ?></Provincia>
<!--                TODO cambiare Italy in Italia nei settings-->
                <Nazione><?php echo strtoupper($nazioniReversed[$this->settings['settings_company_country']]); ?></Nazione><!-- <<< <<< <<< REQUIRED-->
            </Sede>
            <StabileOrganizzazione>
                <Indirizzo></Indirizzo><!-- <<< <<< <<< REQUIRED nei soli casi in cui il cedente/prestatore è un soggetto non residente ed effettua la transazione oggetto del documento tramite l’organizzazione residente sul territorio nazionale.-->
                <NumeroCivico></NumeroCivico>
                <CAP></CAP><!-- <<< <<< <<< REQUIRED nei soli casi in cui il cedente/prestatore è un soggetto non residente ed effettua la transazione oggetto del documento tramite l’organizzazione residente sul territorio nazionale.-->
                <Comune></Comune><!-- <<< <<< <<< REQUIRED nei soli casi in cui il cedente/prestatore è un soggetto non residente ed effettua la transazione oggetto del documento tramite l’organizzazione residente sul territorio nazionale.-->
                <Provincia></Provincia>
                <Nazione></Nazione><!-- <<< <<< <<< REQUIRED nei soli casi in cui il cedente/prestatore è un soggetto non residente ed effettua la transazione oggetto del documento tramite l’organizzazione residente sul territorio nazionale.-->
            </StabileOrganizzazione>
            <IscrizioneREA>
                <Ufficio></Ufficio><!-- <<< <<< <<< REQUIRED nei casi di società soggette al vincolo dell’iscrizione nel registro delle imprese ai sensi dell'art. 2250 del codice civile.-->
                <NumeroREA></NumeroREA><!-- <<< <<< <<< REQUIRED nei casi di società soggette al vincolo dell’iscrizione nel registro delle imprese ai sensi dell'art. 2250 del codice civile.-->
                <CapitaleSociale></CapitaleSociale><!-- <<< <<< <<< REQUIRED nei casi di società soggette al vincolo dell’iscrizione nel registro delle imprese ai sensi dell'art. 2250 del codice civile.-->
                <SocioUnico></SocioUnico><!-- <<< <<< <<< REQUIRED nei casi di società soggette al vincolo dell’iscrizione nel registro delle imprese ai sensi dell'art. 2250 del codice civile.-->
                <StatoLiquidazione></StatoLiquidazione><!-- <<< <<< <<< REQUIRED nei casi di società soggette al vincolo dell’iscrizione nel registro delle imprese ai sensi dell'art. 2250 del codice civile.-->
            </IscrizioneREA>
            <Contatti>
                <Telefono></Telefono>
                <Fax></Fax>
                <Email></Email>
            </Contatti>
            <RiferimentoAmministrazione></RiferimentoAmministrazione>
        </CedentePrestatore>
        <RappresentanteFiscale>
            <DatiAnagrafici>
                <IdFiscaleIVA>
                    <IdPaese></IdPaese><!-- <<< <<< <<< REQUIRED qualora il cedente/prestatore si avvalga di un rappresentante fiscale in Italia, ai sensi del DPR 633 del 1972 e successive modifiche ed integrazioni.-->
                    <IdCodice></IdCodice><!-- <<< <<< <<< REQUIRED qualora il cedente/prestatore si avvalga di un rappresentante fiscale in Italia, ai sensi del DPR 633 del 1972 e successive modifiche ed integrazioni.-->
                </IdFiscaleIVA>
                <CodiceFiscale></CodiceFiscale>
                <Anagrafica>
                    <Denominazione></Denominazione><!-- <<< <<< <<< REQUIRED qualora il cedente/prestatore si avvalga di un rappresentante fiscale in Italia, ai sensi del DPR 633 del 1972 e successive modifiche ed integrazioni.-->
                    <Nome></Nome><!-- <<< <<< <<< REQUIRED se non presente la denominazione.-->
                    <Cognome></Cognome><!-- <<< <<< <<< REQUIRED se non presente la denominazione.-->
                    <Titolo></Titolo>
                    <CodEORI></CodEORI>
                </Anagrafica>
            </DatiAnagrafici>
        </RappresentanteFiscale>
        <CessionarioCommittente>
            <DatiAnagrafici>
                <IdFiscaleIVA>
                    <IdPaese></IdPaese><!-- <<< <<< <<< REQUIRED-->
                    <IdCodice></IdCodice><!-- <<< <<< <<< REQUIRED-->
                </IdFiscaleIVA>
                <CodiceFiscale><?php echo ($destinatario['codice_fiscale']); ?></CodiceFiscale><!-- <<< <<< <<< REQUIRED-->
                <Anagrafica>
                    <Denominazione><?php echo ($destinatario['ragione_sociale']); ?></Denominazione><!-- <<< <<< <<< REQUIRED-->
                    <Nome></Nome><!-- <<< <<< <<< REQUIRED se non presente la denominazione.-->
                    <Cognome></Cognome><!-- <<< <<< <<< REQUIRED se non presente la denominazione.-->
                    <Titolo></Titolo>
                    <CodEORI></CodEORI>
                </Anagrafica>
            </DatiAnagrafici>
            <Sede>
                <Indirizzo><?php echo ($destinatario['indirizzo']); ?></Indirizzo><!-- <<< <<< <<< REQUIRED-->
                <NumeroCivico></NumeroCivico><!-- <<< <<< <<< REQUIRED-->
                <CAP><?php echo ($destinatario['cap']); ?></CAP><!-- <<< <<< <<< REQUIRED-->
<!--                Problema: formato diverso, noi abbiamo un codice, es. 4326-->
                <Comune><?php echo ($destinatario['citta']); ?></Comune><!-- <<< <<< <<< REQUIRED-->
<!--                Problema: formato diverso, noi abbiamo un codice, es. 57-->
                <Provincia><?php echo ($destinatario['provincia']); ?></Provincia>
<!--                TODO: creazione campo-->
                <Nazione>IT</Nazione><!-- <<< <<< <<< REQUIRED-->
            </Sede>
<!--            Opzionali: TerzoIntermediarioOSoggettoEmittente, SoggettoEmittente.-->
        </CessionarioCommittente>
        <TerzoIntermediarioOSoggettoEmittente>
            <DatiAnagrafici>
                <IdFiscaleIVA>
                    <IdPaese></IdPaese>
                    <IdCodice></IdCodice>
                </IdFiscaleIVA>
                <CodiceFiscale></CodiceFiscale>
                <Anagrafica>
                    <Denominazione></Denominazione>
                    <Nome></Nome>
                    <Cognome></Cognome>
                    <Titolo></Titolo>
                    <CodEORI></CodEORI>
                </Anagrafica>
            </DatiAnagrafici>
        </TerzoIntermediarioOSoggettoEmittente>
        <SoggettoEmittente></SoggettoEmittente><!-- <<< <<< <<< REQUIRED nei casi di documenti emessi da un soggetto diverso dal cedente/prestatore va valorizzato il campo seguente.-->
    </FatturaElettronicaHeader>
    <FatturaElettronicaBody>
        <DatiGenerali>
            <DatiGeneraliDocumento>
<!--                TODO: Creazione campo-->
                <TipoDocumento>TD01</TipoDocumento><!-- <<< <<< <<< REQUIRED-->
                <Divisa><?php echo $dati['fattura']['documenti_contabilita_valuta']; ?></Divisa><!-- <<< <<< <<< REQUIRED-->
                <Data><?php echo date("Y-m-d", strtotime($dati['fattura']['documenti_contabilita_data_emissione'])); ?></Data><!-- <<< <<< <<< REQUIRED-->
                <Numero><?php echo $dati['fattura']['documenti_contabilita_numero']; ?></Numero><!-- <<< <<< <<< REQUIRED-->
                <DatiRitenuta>
                    <TipoRitenuta></TipoRitenuta><!-- <<< <<< <<< REQUIRED nei casi in cui sia applicabile la ritenuta.-->
                    <ImportoRitenuta></ImportoRitenuta><!-- <<< <<< <<< REQUIRED nei casi in cui sia applicabile la ritenuta.-->
                    <AliquotaRitenuta></AliquotaRitenuta><!-- <<< <<< <<< REQUIRED nei casi in cui sia applicabile la ritenuta.-->
                    <CausalePagamento></CausalePagamento><!-- <<< <<< <<< REQUIRED nei casi in cui sia applicabile la ritenuta.-->
                </DatiRitenuta>
                <DatiBollo>
                    <NumeroBollo></NumeroBollo><!-- <<< <<< <<< REQUIRED nei casi in cui sia prevista l’imposta di bollo.-->
                    <ImportoBollo></ImportoBollo><!-- <<< <<< <<< REQUIRED nei casi in cui sia prevista l’imposta di bollo.-->
                </DatiBollo>
                <DatiCassaPrevidenziale>
                    <TipoCassa></TipoCassa><!-- <<< <<< <<< REQUIRED nei casi in cui sia previsto il contributo cassa previdenziale.-->
                    <AlCassa></AlCassa><!-- <<< <<< <<< REQUIRED nei casi in cui sia previsto il contributo cassa previdenziale.-->
                    <ImportoContributoCassa></ImportoContributoCassa><!-- <<< <<< <<< REQUIRED nei casi in cui sia previsto il contributo cassa previdenziale.-->
                    <ImponibileCassa></ImponibileCassa>
                    <AliquotaIVA></AliquotaIVA><!-- <<< <<< <<< REQUIRED nei casi in cui sia previsto il contributo cassa previdenziale.-->
                    <Ritenuta></Ritenuta>
                    <Natura></Natura>
                    <RiferimentoAmministrazione></RiferimentoAmministrazione>
                </DatiCassaPrevidenziale>
                <ScontoMaggiorazione>
                    <Tipo></Tipo>
                    <Percentuale></Percentuale>
                    <Importo></Importo>
                </ScontoMaggiorazione>
                <ImportoTotaleDocumento></ImportoTotaleDocumento>
                <Arrotondamento></Arrotondamento>
<!--                Opzionali: DatiRitenuta, DatiBollo, DatiCassaPrevidenziale, ScontoMaggiorazione-->
<!--                Non ho trovato un campo corrispondente-->
                <Causale>LA FATTURA FA RIFERIMENTO AD UNA OPERAZIONE AAAA BBBBBBBBBBBBBBBBBB CCC DDDDDDDDDDDDDDD E FFFFFFFFFFFFFFFFFFFF GGGGGGGGGG HHHHHHH II LLLLLLLLLLLLLLLLL MMM NNNNN OO PPPPPPPPPPP QQQQ RRRR SSSSSSSSSSSSSS</Causale>
                <Art73></Art73>
            </DatiGeneraliDocumento>
            <DatiOrdineAcquisto>
<!--                Tricky: Lasciare valore nullo nel caso i dati ordine acquisto facciano riferimento a tutte le linee di dettaglio.-->
                <RiferimentoNumeroLinea></RiferimentoNumeroLinea><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione.-->
<!--                Il campo deve contenere il numero dell’ordine di acquisto nel rispetto delle caratteristiche stabilite nello schema XSD.-->
                <IdDocumento>66685</IdDocumento><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione.-->
                <Data></Data><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione.-->
                <NumItem><?php echo $dati['fattura']['documenti_contabilita_id']; ?></NumItem><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione.-->
                <CodiceCommessaConvenzione></CodiceCommessaConvenzione>
                <CodiceCUP></CodiceCUP>
                <CodiceCIG></CodiceCIG>
            </DatiOrdineAcquisto>
            <DatiContratto>
                <RiferimentoNumeroLinea></RiferimentoNumeroLinea><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione scaturita da contratto.-->
                <IdDocumento></IdDocumento><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione scaturita da contratto.-->
                <Data></Data><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione scaturita da contratto.-->
                <NumItem></NumItem><!-- <<< <<< <<< REQUIRED in caso di cessione/prestazione scaturita da contratto.-->
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
                <RiferimentoNumeroLinea></RiferimentoNumeroLinea><!-- <<< <<< <<< REQUIRED in caso di dati relativi alla ricezione dei beni/servizi.-->
                <IdDocumento></IdDocumento><!-- <<< <<< <<< REQUIRED in caso di dati relativi alla ricezione dei beni/servizi.-->
                <Data></Data><!-- <<< <<< <<< REQUIRED in caso di dati relativi alla ricezione dei beni/servizi.-->
                <NumItem></NumItem><!-- <<< <<< <<< REQUIRED in caso di dati relativi alla ricezione dei beni/servizi.-->
                <CodiceCommessaConvenzione></CodiceCommessaConvenzione>
                <CodiceCUP></CodiceCUP>
                <CodiceCIG></CodiceCIG>
            </DatiRicezione>
            <DatiFattureCollegate>
                <RiferimentoNumeroLinea></RiferimentoNumeroLinea><!-- <<< <<< <<< REQUIRED in caso di fatture collegate.-->
                <IdDocumento></IdDocumento><!-- <<< <<< <<< REQUIRED in caso di fatture collegate.-->
                <Data></Data><!-- <<< <<< <<< REQUIRED in caso di fatture collegate.-->
                <NumItem></NumItem><!-- <<< <<< <<< REQUIRED in caso di fatture collegate.-->
                <CodiceCommessaConvenzione></CodiceCommessaConvenzione>
                <CodiceCUP></CodiceCUP>
                <CodiceCIG></CodiceCIG>
            </DatiFattureCollegate>
            <DatiSAL>
                <RiferimentoFase></RiferimentoFase>
            </DatiSAL>
            <DatiDDT>
                <NumeroDDT></NumeroDDT><!-- <<< <<< <<< REQUIRED nei casi in cui sia presente un documento di trasporto collegato alla fattura, casi di fatturazione differita.-->
                <DataDDT></DataDDT><!-- <<< <<< <<< REQUIRED nei casi in cui sia presente un documento di trasporto collegato alla fattura, casi di fatturazione differita (espressa secondo il formato ISO 8601:2004).-->
                <RiferimentoNumeroLinea></RiferimentoNumeroLinea>
            </DatiDDT>
            <DatiTrasporto>
                <DatiAnagraficiVettore>
                    <IdFiscaleIVA>
                        <IdPaese></IdPaese>
                        <IdCodice></IdCodice>
                    </IdFiscaleIVA>
                    <CodiceFiscale></CodiceFiscale>
                    <Anagrafica>
                        <Denominazione></Denominazione>
                        <Nome></Nome>
                        <Cognome></Cognome>
                        <Titolo></Titolo>
                        <CodEORI></CodEORI>
                    </Anagrafica>
                    <NumeroLicenzaGuida></NumeroLicenzaGuida>
                </DatiAnagraficiVettore>
                <MezzoTrasporto></MezzoTrasporto>
                <CausaleTrasporto></CausaleTrasporto>
                <NumeroColli></NumeroColli>
                <Descrizione></Descrizione>
                <UnitaMisuraPeso></UnitaMisuraPeso>
                <PesoLordo></PesoLordo>
                <PesoNetto></PesoNetto>
                <DataOraRitiro></DataOraRitiro>
                <DataInizioTrasporto></DataInizioTrasporto>
                <TipoResa></TipoResa>
                <IndirizzoResa>
                    <Indirizzo></Indirizzo>
                    <NumeroCivico></NumeroCivico>
                    <CAP></CAP>
                    <Comune></Comune>
                    <Provincia></Provincia>
                    <Nazione></Nazione>
                </IndirizzoResa>
                <DataOraConsegna></DataOraConsegna>
            </DatiTrasporto>
            <NormaDiRiferimento></NormaDiRiferimento><!-- <<< <<< <<< REQUIRED nei casi in cui il cessionario/committente è debitore di imposta in luogo del cedente/prestatore (reverse charge), o nei casi in cui sia tenuto ad emettere autofattura. -->
            <FatturaPrincipale>
                <NumeroFatturaPrincipale></NumeroFatturaPrincipale><!-- <<< <<< <<< REQUIRED nei casi di fatture per operazioni accessorie, emesse dagli ‘autotrasportatori’ per usufruire delle agevolazioni in materia di registrazione e pagamento IVA. -->
                <DataFatturaPrincipale></DataFatturaPrincipale><!-- <<< <<< <<< REQUIRED nei casi di fatture per operazioni accessorie, emesse dagli ‘autotrasportatori’ per usufruire delle agevolazioni in materia di registrazione e pagamento IVA (espressa secondo il formato ISO 8601:2004). -->
            </FatturaPrincipale>
        </DatiGenerali>
        <DatiBeniServizi>
            <?php foreach ($dati['fattura']['articoli'] as $articolo): ?>
            <DettaglioLinee>
                <NumeroLinea>1</NumeroLinea><!-- <<< <<< <<< REQUIRED-->
                <TipoCessionePrestazione></TipoCessionePrestazione><!-- <<< <<< <<< REQUIRED qualora si tratti di sconto, premio, abbuono o spesa accessoria. -->
                <CodiceArticolo>
                    <CodiceTipo></CodiceTipo>
                    <CodiceValore></CodiceValore>
                </CodiceArticolo>
                <Descrizione><?php echo $articolo['documenti_contabilita_articoli_descrizione']; ?></Descrizione><!-- <<< <<< <<< REQUIRED-->
                <Quantita><?php echo number_format($articolo['documenti_contabilita_articoli_quantita'], 2); ?></Quantita><!-- <<< <<< <<< REQUIRED  può non essere valorizzato nei casi in cui la prestazione non sia quantificabile. -->
                <UnitaMisura></UnitaMisura><!-- <<< <<< <<< REQUIRED se il campo Quantita è valorizzato.-->
                <DataInizioPeriodo></DataInizioPeriodo><!-- <<< <<< <<< REQUIRED solo se servizio (espressa secondo il formato ISO 8601:2004). -->
                <DataFinePeriodo></DataFinePeriodo><!-- <<< <<< <<< REQUIRED solo se servizio (espressa secondo il formato ISO 8601:2004). -->
                <PrezzoUnitario><?php echo number_format($articolo['documenti_contabilita_articoli_prezzo'], 2); ?></PrezzoUnitario><!-- <<< <<< <<< REQUIRED. Se i beni sono ceduti a titolo di sconto, premio o abbuono, l'importo indicato rappresenta il "valore normale”.-->
                <ScontoMaggiorazione>
                    <Tipo></Tipo><!-- <<< <<< <<< REQUIRED se presente sconto o maggiorazione.-->
                    <Percentuale></Percentuale><!-- <<< <<< <<< REQUIRED se presente sconto o maggiorazione.-->
                    <Importo></Importo><!-- <<< <<< <<< REQUIRED se presente sconto o maggiorazione.-->
                </ScontoMaggiorazione>
                <PrezzoTotale><?php echo number_format($articolo['documenti_contabilita_articoli_prezzo'] * $articolo['documenti_contabilita_articoli_quantita'], 2); ?></PrezzoTotale><!-- <<< <<< <<< REQUIRED-->
                <AliquotaIVA><?php echo number_format($articolo['iva_valore'], 2); ?></AliquotaIVA><!-- <<< <<< <<< REQUIRED nel caso di non applicabilità, il campo deve essere valorizzato a zero.-->
                <Ritenuta></Ritenuta>
                <Natura></Natura>
                <RiferimentoAmministrazione></RiferimentoAmministrazione>
                <AltriDatiGestionali>
                    <TipoDato></TipoDato>
                    <RiferimentoTesto></RiferimentoTesto>
                    <RiferimentoNumero></RiferimentoNumero>
                    <RiferimentoData></RiferimentoData>
                </AltriDatiGestionali>
            </DettaglioLinee>
            <?php endforeach; ?>
            <DatiRiepilogo>
                <AliquotaIVA>22.00</AliquotaIVA><!-- <<< <<< <<< REQUIRED-->
                <Natura></Natura>
                <SpeseAccessorie></SpeseAccessorie>
                <Arrotondamento></Arrotondamento>
                <ImponibileImporto>25.00</ImponibileImporto><!-- <<< <<< <<< REQUIRED-->
                <Imposta>5.50</Imposta><!-- <<< <<< <<< REQUIRED-->
                <EsigibilitaIVA>D</EsigibilitaIVA><!-- <<< <<< <<< REQUIRED-->
                <RiferimentoNormativo></RiferimentoNormativo>
            </DatiRiepilogo>
        </DatiBeniServizi>
        <DatiVeicoli>
            <Data></Data><!-- <<< <<< <<< REQUIRED nei casi di cessioni tra paesi membri di mezzi di trasporto nuovi.-->
            <TotalePercorso></TotalePercorso><!-- <<< <<< <<< REQUIRED nei casi di cessioni tra paesi membri di mezzi di trasporto nuovi.-->
        </DatiVeicoli>
        <DatiPagamento>
<!--            In questo campo va indicato “TP01” nel caso di pagamento a rate, “TP02” nel caso di pagamento totale in unica soluzione, “TP03” in caso di pagamento di un anticipo. Manca campo.-->
            <CondizioniPagamento>TP01</CondizioniPagamento>
            <DettaglioPagamento>
                <Beneficiario></Beneficiario>
<!--                Il campo deve contenere uno dei valori codificati presenti nella lista associata.-->
                <ModalitaPagamento><?php echo $dati['fattura']['documenti_contabilita_metodo_pagamento']; ?>MP01</ModalitaPagamento>
                <DataRiferimentoTerminiPagamento></DataRiferimentoTerminiPagamento>
                <GiorniTerminiPagamento></GiorniTerminiPagamento>
                <DataScadenzaPagamento>2015-01-30</DataScadenzaPagamento>
                <ImportoPagamento><?php echo $dati['fattura']['documenti_contabilita_totale']; ?>30.50</ImportoPagamento>
                <CodUfficioPostale></CodUfficioPostale>
                <CognomeQuietanzante></CognomeQuietanzante>
                <NomeQuietanzante></NomeQuietanzante>
                <CFQuietanzante></CFQuietanzante>
                <TitoloQuietanzante></TitoloQuietanzante>
                <IstitutoFinanziario></IstitutoFinanziario>
                <IBAN></IBAN>
                <ABI></ABI>
                <CAB></CAB>
                <BIC></BIC>
                <ScontoPagamentoAnticipato></ScontoPagamentoAnticipato>
                <DataLimitePagamentoAnticipato></DataLimitePagamentoAnticipato>
                <PenalitaPagamentiRitardati></PenalitaPagamentiRitardati>
                <DataDecorrenzaPenale></DataDecorrenzaPenale>
                <CodicePagamento></CodicePagamento>
            </DettaglioPagamento>
        </DatiPagamento>
        <Allegati>
            <NomeAttachment></NomeAttachment>
            <AlgoritmoCompressione></AlgoritmoCompressione>
            <FormatoAttachment></FormatoAttachment>
            <DescrizioneAttachment></DescrizioneAttachment>
            <Attachment></Attachment>
        </Allegati>
    </FatturaElettronicaBody>
</p:FatturaElettronica>