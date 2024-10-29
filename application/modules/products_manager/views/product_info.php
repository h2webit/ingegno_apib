<?php
$prodotto = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $value_id]);

// debug($prodotto);

$magazzini = $this->apilib->search('magazzini');
$light_colors = ['#f7f1e3','#fafafa','#f5f6fa','#dcdde1','#d2dae2','#4cd1370'];

if(!empty($prodotto['fw_products_fw_categories'])) {
    $categorie = $this->apilib->search('fw_products_fw_categories', ['fw_products_id' => $value_id]);
}

$provider_codes = $prodotto['fw_products_provider_code'] ? json_decode($prodotto['fw_products_provider_code'], true) : [];
//     [fw_products_provider_code] => [{"code":"IK436B","supplier":"554"},{"code":"E83NRR","supplier":"562"}]

// ottengo gli id dei supplier(se presenti) e li ottengo da db con un where_in
$supplier_ids = array_column($provider_codes, 'supplier');
$suppliers = $this->apilib->search('customers', ['customers_id' => $supplier_ids]);

// aggiungo il nome del fornitore in base all'id nell'arrayh dei codici
foreach($provider_codes as $key => $provider_code) {
    $supplier = array_filter($suppliers, function($supplier) use ($provider_code) {
        return $supplier['customers_id'] == $provider_code['supplier'];
    });
    $provider_codes[$key]['supplier_name'] = $supplier ? array_values($supplier)[0]['customers_full_name'] : '';
}
?>

<style>
.product_main_image_container {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.product_main_image_container a.fancybox {
    width: 100% !important;
    display: flex;
    justify-content: center;
}

.product_thumb_container {
    overflow: auto;
    white-space: nowrap;
    padding: 10px;
}

.product_thumb_container a {
    display: inline-block;
    padding: 10px;
}

.product_thumb_container a img.product_thumb {
    max-height: 60px;
}

.product_description {
    margin-top: 12px;
    font-size: 12px;
}

.product_info {
    margin: 4px 0;
    font-size: 14px;
}

.counter_qty_container {
    display: flex;
    flex-direction: column;
    max-height: 360px;
    overflow-y: scroll;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <?php if (!empty($prodotto['fw_products_main_image'])):
                $main_image = (is_valid_json($prodotto['fw_products_main_image'])) ? json_decode($prodotto['fw_products_main_image'], true) : $prodotto['fw_products_main_image'];
                $main_image_path = $main_image['path_local'] ?? $main_image;
                $full_image_path = FCPATH . "uploads/{$main_image_path}";

                if (is_valid_image($full_image_path)): ?>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="product_main_image_container">
                                <a href='<?php echo base_url("uploads/{$main_image_path}"); ?>' class='fancybox'>
                                    <img style="max-width: 200px; max-height: 100%;"
                                        src='<?php echo base_url("uploads/{$main_image_path}"); ?>' class='img-responsive'>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <img class="img-responsive" style="max-width: 100px;"
                        src="<?php echo base_url('modulesbridge/loadAssetFile/products_manager?file=product.png'); ?>" />
                <?php endif; ?>
            <?php else: ?>
                <img class="img-responsive" style="max-width: 100px;"
                    src="<?php echo base_url('modulesbridge/loadAssetFile/products_manager?file=product.png'); ?>" />
            <?php endif; ?>
            <!-- Thumbnails -->
            <div class="product_thumb_container">
                <?php
                if(!empty($prodotto['fw_products_images'])) :
                    $product_images = json_decode($prodotto['fw_products_images'], true);
                    foreach($product_images as $image) :
            ?>
                <a href='<?php echo base_url('uploads/'.$image['path_local']); ?>' data-lightbox="1">
                    <img src='<?php echo base_url('uploads/'.$image['path_local']); ?>' class="img-responsive img-rounded product_thumb" />
                </a>
                <?php
            endforeach;
                endif;
            ?>
            </div>
        </div>



        <!-- Product info -->
        <div class="col-md-5">
            <h3><?php echo $prodotto['fw_products_name']; ?></h3>
            <?php
            if (ctype_digit($prodotto['fw_products_barcode'])) {
                $barcode = $prodotto['fw_products_barcode'];
            } elseif ($prodotto['fw_products_barcode']) {

                $barcode = implode(",\n", json_decode($prodotto['fw_products_barcode'],true));
            } else {
                $barcode = '';
            }
            ?>
            <h4><?php echo $barcode ?> / <?php echo $prodotto['fw_products_sku']; ?></h4>
            <h4 class="label label-success" style="font-size: 18px;  margin-top:15px;"><?php echo number_format($prodotto['fw_products_sell_price'], 2, ',', '.'); ?> €</h4>

            <!-- Dati aggiuntivi -->
            <?php
                if(!empty($prodotto['fw_products_description']))  {
                    echo "<div class='product_description'>{$prodotto['fw_products_description']}</div>";
                }
                if(!empty($prodotto['fw_products_categories']))  {
                    $categories = implode(", ", $prodotto['fw_products_categories']);
                    echo "<div class='product_info'><strong>Categorie</strong>: {$categories}</div>";
                }
                if (!empty($provider_codes)) {
                    echo "<div class='product_info'><strong>Codici fornitore</strong>: ";
                    foreach($provider_codes as $provider_code) {
                        $supplier_name = $provider_code['supplier_name'] ? "({$provider_code['supplier_name']})" : '';
                        echo "<div><strong>{$provider_code['code']}</strong> $supplier_name</div>";
                    }
                    echo "</div>";
                
                }
                if(!empty($prodotto['fw_products_brand']))  {
                    echo "<div class='product_info'><strong>Marchio</strong>: {$prodotto['fw_products_brand_value']}</div>";
                }
                if(!empty($prodotto['fw_products_sconto']))  {
                    echo "<div class='product_info'><strong>Sconto</strong>: {$prodotto['fw_products_sconto']}%</div>";
                }
                if(!empty($prodotto['fw_products_discounted_price']))  {
                    echo "<div class='product_info'><strong>Prezzo scontato (IVA esc.)</strong>: ".number_format($prodotto['fw_products_discounted_price'], 2, ',', '.')." €</div>";
                }
                if(!empty($prodotto['fw_products_tax']))  {
                    echo "<div class='product_info'><strong>IVA</strong>: {$prodotto['iva_label']}</div>";
                }
                if(!empty($prodotto['fw_products_supplier']))  {
                    echo "<div class='product_info'><strong>Fornitore</strong>: {$prodotto['customers_company']}</div>";
                }
                if(!empty($prodotto['fw_products_provider_price']))  {
                    echo "<div class='product_info'><strong>Prezzo del fornitore</strong>: ".number_format($prodotto['fw_products_provider_price'], 2, ',', '.')." €</div>";
                }

                if (!empty($prodotto['fw_products_avg_sell_price'])) {
                    echo "<div class='product_info'><strong>Prezzo medio di vendita</strong>: " . number_format($prodotto['fw_products_avg_sell_price'], 2, ',', '.') . " €</div>";
                    
                }
                if (!empty($prodotto['fw_products_avg_prov_price'])) {
                    echo "<div class='product_info'><strong>Prezzo medio di acquisto</strong>: " . number_format($prodotto['fw_products_avg_prov_price'], 2, ',', '.') . " €</div>";

                }

                if(!empty($prodotto['fw_products_warehouse_location']))  {
                    echo "<div class='product_info'><strong>Ubicazione magazzino</strong>: {$prodotto['fw_products_warehouse_location']}</div>";
                }
            ?>


            <!-- Listini -->
            <?php
                $sql_listini = <<<EOT
                SELECT price_list.*, price_list_labels.price_list_labels_name
                FROM price_list
                INNER JOIN price_list_labels ON price_list.price_list_label = price_list_labels.price_list_labels_id
                WHERE price_list.price_list_product = '{$value_id}'
                EOT;

                $prezzi_listini_prodotto = $this->db->query($sql_listini)->result_array();
                if(!empty($prezzi_listini_prodotto)) : 
            ?>
            <hr />
            <div>
                <h4>Listini</h4>
                <div>
                    <?php foreach($prezzi_listini_prodotto as $prezzo_listino) : ?>
                    <div style="font-size: 16px;"><?php echo '<strong>'.$prezzo_listino['price_list_labels_name']. '</strong>: '. number_format($prezzo_listino['price_list_price'], 2, ',', '.');?> €</div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>



        <div class="col-md-3">
            <?php if(!empty($magazzini)) : ?>
            <div class="counter_qty_container">
                <?php
                $magazzini = $this->apilib->search('magazzini');
                $light_colors = ['#f7f1e3', '#fafafa', '#f5f6fa', '#dcdde1', '#d2dae2', '#4cd1370'];
                
                function getQuantity($db, $value_id, $magazzino_id, $tipo_movimento) {
                    $query = "SELECT SUM(movimenti_articoli_quantita) as qty
              FROM movimenti_articoli
              LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento)
              WHERE movimenti_tipo_movimento = ?
              AND movimenti_articoli_prodotto_id = ?
              AND movimenti_magazzino = ?";
                    return $db->query($query, [$tipo_movimento, $value_id, $magazzino_id])->row()->qty ?? 0;
                }
                
                $show_as_list = count($magazzini) > 5;
                
                if (!$show_as_list) {
                    echo '<div class="row">';
                }
                
                foreach ($magazzini as $magazzino) :
                    $quantity_carico = getQuantity($this->db, $value_id, $magazzino['magazzini_id'], 1);
                    $quantity_scarico = getQuantity($this->db, $value_id, $magazzino['magazzini_id'], 2);
                    $quantity = $quantity_carico - $quantity_scarico;
                    
                    $text_color = in_array($magazzino['magazzini_colore'], $light_colors) ? 'black' : 'white';
                    
                    if ($show_as_list) :
                        ?>
                        <p style="color: <?= $magazzino['magazzini_colore']; ?>; font-size: 1.2em;">
                            <?= $magazzino['magazzini_titolo'] . ': ' . $quantity; ?>
                        </p>
                    <?php else : ?>
                        <div class="col-sm-12">
                            <div class="small-box" style="color: <?= $text_color; ?>; background-color: <?= $magazzino['magazzini_colore']; ?>">
                                <div class="inner">
                                    <h3><?= $quantity; ?></h3>
                                    <p style="font-size: 1.2em;"><?= $magazzino['magazzini_titolo']; ?></p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif;
                endforeach;
                
                if (!$show_as_list) {
                    echo '</div>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>


    </div>
</div>
