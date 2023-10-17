<?php echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"; ?>
<iv:Fornitura xmlns:ds='http://www.w3.org/2000/09/xmldsig#' xmlns:iv='urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp'>
    <iv:Intestazione>
        <iv:CodiceFornitura>IVP18</iv:CodiceFornitura>
        <iv:CodiceFiscaleDichiarante>@todo</iv:CodiceFiscaleDichiarante>
        <iv:CodiceCarica>1</iv:CodiceCarica>
    </iv:Intestazione>
    <iv:Comunicazione identificativo='00001'>
        <iv:Frontespizio>
            <iv:CodiceFiscale><?php echo $azienda['documenti_contabilita_settings_company_codice_fiscale']; ?></iv:CodiceFiscale>
            <iv:AnnoImposta><?php echo date('Y'); ?></iv:AnnoImposta>
            <iv:PartitaIVA><?php echo $azienda['documenti_contabilita_settings_company_vat_number']; ?></iv:PartitaIVA>
            <iv:CFDichiarante>@todo</iv:CFDichiarante>
            <iv:CodiceCaricaDichiarante>1</iv:CodiceCaricaDichiarante>
            <iv:FirmaDichiarazione>1</iv:FirmaDichiarazione>
            <iv:FlagConferma>0</iv:FlagConferma>
            <iv:IdentificativoProdSoftware>@todo</iv:IdentificativoProdSoftware>
        </iv:Frontespizio>
        <iv:DatiContabili>
            <iv:Modulo>
                <iv:NumeroModulo>1</iv:NumeroModulo>
                <iv:Mese>1</iv:Mese>
                <iv:TotaleOperazioniAttive>211258,22</iv:TotaleOperazioniAttive>
                <iv:TotaleOperazioniPassive>42260,20</iv:TotaleOperazioniPassive>
                <iv:IvaEsigibile>2442,64</iv:IvaEsigibile>
                <iv:IvaDetratta>4188,57</iv:IvaDetratta>
                <iv:IvaCredito>1745,93</iv:IvaCredito>
                <iv:ImportoACredito>1745,93</iv:ImportoACredito>
            </iv:Modulo>
        </iv:DatiContabili>
    </iv:Comunicazione>
</iv:Fornitura>
