<?php //debug($cartella, true); ?>
<?php //debug($interventi, true); ?>
<!DOCTYPE HTML>
<html>
    <head>

        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="<?php echo base_url_template('template/crm-v2/assets/global/plugins/bootstrap/css/bootstrap.min.css'); ?>">
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
        <style>
            html, body, p, * {
                font-family: 'Open Sans', sans-serif;
            }

            table thead tr th {
                /*background: #e4e4e4*/
            }
            .dl-horizontal dt {
                overflow: visible;
                text-overflow: none;
                width: auto!important;
            }
            .dl-horizontal dd {
                margin-left: 300px;
            }
            h2.new_page {page-break-before:always;}
        </style>
    </head>
    <body>
    <center>
        <img src="<?php echo base_url('images/pdf/cartella_clinica_imgs/bg1.png'); ?>" />
    </center>
        <br />
        <br />
        <br />
        <br />
        <br />
        <br />
        <div class="container">
            <div class="row" style="font-size:16px">
                <div class="col-lg-12" data-layout-box="130">

                    <div class="grid portlet light">
                        

                        <div class="portlet-body grid ">


                            <dl id="grid_70" data-id="1" class="dl-horizontal dl-horizontal-compact static-vertical-grid">
                                <dt class="js-grid-field-200">Nome:</dt>
                                <dd class="js-grid-field-200"><?php echo $cartella['domiciliari_nome']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Cognome:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_cognome']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Residente comune di:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_residenza_comune']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Indirizzo:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_residenza_indirizzo']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">C.F.:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_codice_fiscale']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Data di nascita:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_data_nascita']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Luogo di nascita:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_comune_nascita']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Domicilio:</dt>
                                <dd class="js-grid-field-201">
                                    <?php echo $cartella['domiciliari_domicilio_indirizzo']; ?>, <?php echo $cartella['domiciliari_domicilio_comune']; ?> (<?php echo $cartella['domiciliari_domicilio_provincia']; ?>)
                                    
                                </dd>
                                <hr>
                                <dt class="js-grid-field-201">Tel.:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_telefono']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Cell.:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_cellulare']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Email:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_email']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Medico curante:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_medico_curante']; ?></dd>
                                <hr>
                                <dt class="js-grid-field-201">Tel:</dt>
                                <dd class="js-grid-field-201"><?php echo $cartella['domiciliari_medico_curante_telefono']; ?></dd>
                                <hr>
                                
                                
                                
                                <dt class="js-grid-field-196">Allergie riferite:</dt>
                                <dd class="js-grid-field-196"><?php echo $cartella['cartelle_cliniche_allergie_riferite']; ?></dd>
                                <hr>
                                
                                
                                <dt class="js-grid-field-419">Patologia medica primaria:</dt>
                                <dd class="js-grid-field-419"><?php echo $cartella['cartelle_cliniche_patologia_medica_primaria']; ?></dd>
                          
                                <hr>
                                <dt class="js-grid-field-419">Terapia farmacologica di importanza rilevante:</dt>
                                <dd class="js-grid-field-419"><?php echo $cartella['cartelle_cliniche_terapia_farmacologica_rilevante']; ?></dd>
                                
                                <hr>
                                <dt class="js-grid-field-419">Compilata al primo accesso dall' I.P.:</dt>
                                <dd class="js-grid-field-419"><?php echo $cartella['cartelle_cliniche_compilata_primo_accesso']; ?></dd>
                                
                                
                                <hr>
                                <dt class="js-grid-field-419">Data di presa in carico:</dt>
                                <dd class="js-grid-field-419"><?php echo $cartella['cartelle_cliniche_data_presa_in_carico']; ?></dd>
                                
                                <hr>
                                <dt class="js-grid-field-419">Note:</dt>
                                <dd class="js-grid-field-419"><?php echo $cartella['cartelle_cliniche_note']; ?></dd>
                            </dl>

                        </div>
                    </div>

                </div>
            </div>
            <?php if (count($interventi)>0) : ?>
            <h2 class="new_page"></h2>

            <table class="table" style="font-size:16px">
                <thead>
                    <tr>
                        <th width="100">Data</th>
                        <th >Intervento assistenziale</th>
                        <th>Professionista</th>
                        <th>Note</th>
                        <th class="text-right">Firma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($interventi as $intervento): ?>
                    
                        <tr>
                            <td>
                                <?php echo dateFormat($intervento['cartelle_cliniche_interventi_data']); ?>
                            </td>
                            <td><?php echo ($intervento['cartelle_cliniche_interventi_intervento']); ?></td>
                            <td><?php echo $intervento['associati_nome']; ?> <?php echo $intervento['associati_cognome']; ?></td>
                            <td><?php echo ($intervento['cartelle_cliniche_interventi_note']); ?></td>
                            <td class="text-right"><?php echo (!empty($intervento['cartelle_cliniche_interventi_firma'])) ? '<img src="'.base_url('uploads/'.$intervento['cartelle_cliniche_interventi_firma']).'" class="img-responsive" style="height:50px;" />' : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                </tbody>
<!--                <tfoot>
                    <tr class="info">
                        <td ><small>Tutti i prezzi s'intendono iva inclusa</small></td>
                        <td class="">Totale</td>
                        <td class="text-right"></td>
                    </tr>
                </tfoot>-->
            </table>

            <?php endif; ?>

<!--            <div class="row" style="margin-top: 20mm;">
                <div class="col-xs-12">
                    <p style="font-size: 10pt;">I Vs. dati anagrafici e fiscali sono da noi considerati esatti sotto la Vs. responsabilità salvo Vs. diversa comunicazione come previsto dal D.P.R. n. 633 del 26/10/1972.</p>
                    <p style="font-size: 10pt;">Legge 675/96 Tutela privacy. I Vs. dati sono utilizzati per lo svolgimento della ns. attività. In aseenza di Vs. formale dissenso, ci riteniamo autorizzati a tale trattamento.</p>
                </div>
            </div>-->
            
        </div>
        <!--<h2 class="new_page"></h2>
         <center>
        
            <img src="<?php echo base_url('images/pdf/cartella_clinica_imgs/bgc.png'); ?>" />
         </center>-->
    </body>
</html>