<?php
$prodotto = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $value_id]);

$magazzini = $this->apilib->search('magazzini');
$light_colors = ['#f7f1e3','#fafafa','#f5f6fa','#dcdde1','#d2dae2','#4cd1370'];

if(!empty($prodotto['fw_products_fw_categories'])) {
    $categorie = $this->apilib->search('fw_products_fw_categories', ['fw_products_id' => $value_id]);
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
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <?php
            if (!empty($prodotto['fw_products_main_image'])) : ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="product_main_image_container">
                        <?php
                        $main_image = (is_valid_json($prodotto['fw_products_main_image'])) ? json_decode($prodotto['fw_products_main_image'], true) : $prodotto['fw_products_main_image'];
                        
                        $main_image_path = $main_image['path_local'] ?? $main_image;
                        
                        ?>
                        <a href='<?php echo base_url("uploads/{$main_image_path}"); ?>' class='fancybox'>
                            <img style="max-width: 200px; max-height: 100%;" src='<?php echo base_url("uploads/{$main_image_path}"); ?>' class='img-responsive'>
                        </a>
                    </div>
                </div>
            </div>
            <?php else :
                $link = base_url('modulesbridge/loadAssetFile/products_manager?file=product.png');
            ?>
            <img class="img-responsive" style="max-width: 100px;" src="<?php echo $link; ?>" />
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
                    echo "<div class='product_info'><strong>IVA</strong>: {$prodotto['fw_products_tax_iva_label']}</div>";
                }
                if(!empty($prodotto['fw_products_supplier']))  {
                    echo "<div class='product_info'><strong>Fornitore</strong>: {$prodotto['customers_company']}</div>";
                }
                if(!empty($prodotto['fw_products_provider_price']))  {
                    echo "<div class='product_info'><strong>Prezzo del fornitore</strong>: ".number_format($prodotto['fw_products_provider_price'], 2, ',', '.')." €</div>";
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
                <?php foreach ($magazzini as $key => $magazzino) : ?>
                <?php
                    $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$value_id}' AND movimenti_magazzino = '{$magazzino['magazzini_id']}'")->row()->qty;
                    $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$value_id}' AND movimenti_magazzino = '{$magazzino['magazzini_id']}'")->row()->qty;
                    $quantity = $quantity_carico - $quantity_scarico;
                ?>
                <div class="small-box" style="color:<?php echo (in_array($magazzino['magazzini_colore'], $light_colors)) ? 'black' : 'white'; ?>;background-color:<?php echo $magazzino['magazzini_colore']; ?>">
                    <div class="inner">
                        <h3><?php echo $quantity; ?></h3>
                        <p style="font-size: 1.2em !important;"><strong><?php echo $magazzino['magazzini_titolo']; ?></strong></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-warehouse" style="font-size: 0.75em !important;"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>


    </div>
</div>