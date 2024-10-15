<?php
if ($this->datab->module_installed('contabilita')):

    $articoli_ordinati = $this->apilib->search('documenti_contabilita_articoli', [
        "documenti_contabilita_articoli_documento IN (
        SELECT documenti_contabilita_commesse_documenti_contabilita_id 
        FROM documenti_contabilita_commesse 
        WHERE documenti_contabilita_commesse_projects_id = '{$value_id}' 
        AND documenti_contabilita_tipo = '14' 
        AND documenti_contabilita_commesse_documenti_contabilita_id IN (
            SELECT documenti_contabilita_id
            FROM documenti_contabilita
            WHERE YEAR(documenti_contabilita_data_emissione) = YEAR(CURDATE())
        )
    )"
    ]);


    if (!empty($articoli_ordinati)) {
        $articoliRaggruppati = [];
        $totaliMensili = array_fill(1, 12, 0); // Totali per ogni mese
        $totaleFinale = 0; // Totale finale

        foreach ($articoli_ordinati as $articolo) {
            $prodotto_id = $articolo['documenti_contabilita_articoli_prodotto_id'];
            $quantita_ordinata = $articolo['documenti_contabilita_articoli_quantita'] ?? 0;
            $dataEmissione = dateFormat($articolo['documenti_contabilita_data_emissione'], 'Y-m-d');

            // Estrai mese e anno dalla data di emissione
            $data_doc = DateTime::createFromFormat('Y-m-d', $dataEmissione);
            $mese = $data_doc->format('m');
            $anno = $data_doc->format('Y');

            // Inizializza l'array se l'articolo non esiste già
            if (!isset($articoliRaggruppati[$prodotto_id])) {
                $articoliRaggruppati[$prodotto_id] = [
                    'documenti_contabilita_articoli_prodotto_id' => $prodotto_id,
                    'articolo' => $articolo,
                    'totale_per_mese' => array_fill(1, 12, 0), // Crea un array con 12 zeri, uno per ogni mese
                    'totale' => 0
                ];
            }

            // Incrementa il conteggio per il mese corrispondente
            $articoliRaggruppati[$prodotto_id]['totale_per_mese'][(int) $mese] += $quantita_ordinata;
            // Incrementa il totale dell'articolo
            $articoliRaggruppati[$prodotto_id]['totale'] += $quantita_ordinata;

            // Incrementa i totali mensili e il totale finale
            $totaliMensili[(int) $mese] += $quantita_ordinata;
            $totaleFinale += $quantita_ordinata;
        }

        //dump($articoliRaggruppati);
        $mesi = [
            1 => 'GEN',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'APR',
            5 => 'MAG',
            6 => 'GIU',
            7 => 'LUG',
            8 => 'AGO',
            9 => 'SETT',
            10 => 'OTT',
            11 => 'NOV',
            12 => 'DIC'
        ];
    }
    ?>

    <style>
        .articoli_title_container {
            margin-bottom: 16px;
        }
    </style>

    <div class="articoli_title_container">
        <h4 class="text-center">Articoli ordinati nell'anno corrente</h4>
    </div>

    <?php if (empty($articoli_ordinati)): ?>
        <div class="callout callout-info">
            <h4>Nessun articolo ordinato per la commessa corrente</h4>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th>Articolo</th>
                        <?php foreach ($mesi as $mese): ?>
                            <th class="text-center"><?php echo $mese; ?></th>
                        <?php endforeach; ?>
                        <th class="text-center">TOT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articoliRaggruppati as $articolo): ?>
                        <tr>
                            <td>
                                <?php
                                if (!empty($articolo['articolo']['fw_products_main_image'])) {
                                    $main_image = (is_valid_json($articolo['articolo']['fw_products_main_image'])) ? json_decode($articolo['articolo']['fw_products_main_image'], true) : $articolo['articolo']['fw_products_main_image'];

                                    $main_image_path = $main_image['path_local'] ?? $main_image;
                                    $content = '<img class="img-responsive" src="' . base_url("thumb/50/50/1/uploads/" . $main_image_path) . '"/>';

                                    echo anchor(base_url("uploads/" . $main_image_path), $content, ['class' => 'fancybox']);
                                } elseif (!empty($articolo['articolo']['fw_products_images'])) {
                                    $fw_products_images = json_decode($articolo['articolo']['fw_products_images'], true);

                                    foreach ($fw_products_images as $fw_product_image) {
                                        $content = '<img class="img-responsive" src="' . base_url('thumb/50/50/1/uploads/' . $fw_product_image['path_local']) . '"/>';

                                        echo anchor(base_url('uploads/' . $fw_product_image['path_local']), $content, ['class' => 'fancybox']);
                                    }
                                } else {
                                    echo '<img class="img-responsive" style="width: 50px; height: 50px;" src="' . base_url('modulesbridge/loadAssetFile/products_manager?file=product.png') . '"/>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo $articolo['articolo']['fw_products_name']; ?>
                            </td>
                            <?php foreach ($articolo['totale_per_mese'] as $tot_mensile): ?>
                                <td class="text-center"><?php echo $tot_mensile; ?></td>
                            <?php endforeach; ?>
                            <td class="text-center">
                                <strong><?php echo $articolo['totale']; ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- Riga Totale -->
                    <tr>
                        <td colspan="2"><strong>Totali</strong></td>
                        <?php foreach ($totaliMensili as $tot_mensile): ?>
                            <td class="text-center"><strong><?php echo $tot_mensile; ?></strong></td>
                        <?php endforeach; ?>
                        <td class="text-center"><strong><?php echo $totaleFinale; ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    endif;
endif;
?>