<?php

class Woocomm extends CI_Model
{
    public $wc_settings;
    public $wc_enabled = false;
    public $wc_qty_sync;

    public $wc = null;
    public function __construct()
    {
        $this->wc_settings = $this->db->get('woocommerce')->row_array();

        if (empty($this->wc_settings['woocommerce_enabled']) || $this->wc_settings['woocommerce_enabled'] == DB_BOOL_FALSE) {
            die("Modulo Woocommerce non attivo. Contattare l'assistenza");
        } else {
            if (
                empty($this->wc_settings['woocommerce_consumer_key'])
                || empty($this->wc_settings['woocommerce_consumer_secret'])
                || empty($this->wc_settings['woocommerce_endpoint'])
            ) {
                return false;
            }

            $this->wc_enabled = true;
        }

        $this->wc_qty_sync = $this->wc_settings['woocommerce_qty_sync'];

        require APPPATH . 'modules/products_manager/vendor/autoload.php';

        $endpoint = $this->wc_settings['woocommerce_endpoint'];
        $url = (empty(parse_url($endpoint)['scheme'])) ? 'https://' . $endpoint : $endpoint;
        $key = $this->wc_settings['woocommerce_consumer_key'];
        $secret = $this->wc_settings['woocommerce_consumer_secret'];

        $this->wc = new Automattic\WooCommerce\Client($url, $key, $secret, ['version' => 'wc/v3']);

        $this->load->model('products_manager/prod_model');
    }

    public function import_product($product, $parent_id = null)
    {
        echo_flush("importing product $product->id", "<br/>");

        $product_name = $product->name;
        $product_name = trim(preg_replace('/<[^>]*>/', ' ', $product_name));
        $product_name = trim(preg_replace('!\s+!', ' ', $product_name));

        $crm_product = $this->apilib->searchFirst('fw_products', ['fw_products_woocommerce_external_code' => $product->id]);

        if (!empty($product->regular_price)) {
            $price = $product->regular_price;
        } elseif (!empty($product->price)) {
            $price = $product->price;
        } else {
            $price = 0;
        }

        $vat_perc = 10;

        $price_no_vat = 0;
        if ($price > 0) {
            $price_no_vat = $price / (1 + ($vat_perc / 100));
        }

        $discounted_price = 0;
        if (!empty($product->sale_price)) {
            $discounted_price = $product->sale_price;
        }

        $product_fields = [
            'fw_products_name' => $product_name,
            'fw_products_description' => strip_tags($product->description),
            'fw_products_sku' => $product->sku,
            'fw_products_weight' => $product->weight ?? null,
            'fw_products_height' => $product->dimensions->height ?? null,
            'fw_products_width' => $product->dimensions->width ?? null,
            'fw_products_discounted_price' => number_format($discounted_price, 2, '.', ''),
            'fw_products_sell_price' => number_format($price_no_vat, 2, '.', ''),
            'fw_products_sell_price_tax_included' => number_format($price, 2, '.', ''),
            'fw_products_tax' => 4,
            'fw_products_type' => 1,
            'fw_products_is_active' => DB_BOOL_TRUE,
            'fw_products_ecommerce_export' => DB_BOOL_FALSE,
            'fw_products_stock_management' => DB_BOOL_TRUE,
            'fw_products_woocommerce_external_code' => $product->id,
        ];

        foreach ($product->meta_data as $metadata) {
            if ($metadata->key === '_woosea_ean' && !empty($metadata->value)) {
                $product_fields['fw_products_ean'] = $metadata->value;
            }
        }

        if ((!empty($crm_product) && empty($crm_product['fw_products_barcode'])) && $this->wc_settings['woocommerce_generate_barcode_on_import'] == DB_BOOL_TRUE) {
            $ean = $this->prod_model->generateEAN(); // genero il barcode

            $product_fields['fw_products_barcode'] = json_encode([$ean]);
        }

        if (!$parent_id) {
            $brand_id = null;
            foreach ($product->attributes as $attr) {
                if ($attr->name == 'Brand') {
                    $crm_brand = $this->apilib->searchFirst('fw_products_brand', [
                        "LOWER(fw_products_brand_value) = LOWER(\"{$attr->options[0]}\")"
                    ]);

                    if (empty($crm_brand)) {
                        $new_brand = $this->apilib->create('fw_products_brand', [
                            'fw_products_brand_value' => $attr->options[0]
                        ]);

                        $brand_id = $new_brand['fw_products_brand_id'];
                        echo ("+ {$attr->options[0]}\n");
                    } else {
                        $brand_id = $crm_brand['fw_products_brand_id'];
                        echo ("* {$attr->options[0]}\n");
                    }
                }
                break;
            }

            if (!empty($brand_id)) {
                $product_fields['fw_products_brand'] = $brand_id;
            }
        }

        if (!empty($parent_id)) {
            $product_fields['fw_products_parent'] = $parent_id;

            $parent_product = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $parent_id]);

            if (!empty($parent_product['fw_products_brand'])) {
                $product_fields['fw_products_brand'] = $parent_product['fw_products_brand'];
            }
        }

        if (empty($crm_product)) {
            echo_flush("created $product->id", "<br/>");
            return $this->apilib->create('fw_products', $product_fields);
        } else {
            echo_flush("edited $product->id", "<br/>");
            return $this->apilib->edit('fw_products', $crm_product['fw_products_id'], $product_fields);
        }
    }

    public function import_attributes($product, $is_variant = false)
    {
        $wc_attributes = [];

        foreach ($product->attributes as $attribute) {
            if (!stristr($attribute->name, 'Brand')) {
                $crm_attribute = $this->apilib->searchFirst('attributi', ['attributi_external_code' => $attribute->id]);

                $attribute_id = null;
                if (!empty($crm_attribute)) {
                    $attribute_id = $crm_attribute['attributi_id'];
                } else {
                    $new_attribute = $this->apilib->create('attributi', [
                        'attributi_nome' => $attribute->name,
                        'attributi_external_code' => $attribute->id
                    ]);

                    $attribute_id = $new_attribute['attributi_id'];
                }

                if ($is_variant) {
                    $crm_attribute_opt = $this->apilib->searchFirst('attributi_valori', [
                        'attributi_valori_attributo' => $attribute_id,
                        "LOWER(attributi_valori_label) = LOWER('{$attribute->option}')"
                    ]);

                    $crm_attribute_opt_id = null;
                    if (!empty($crm_attribute_opt)) {
                        $crm_attribute_opt_id = $crm_attribute_opt['attributi_valori_id'];
                    } else {
                        $new_attribute_opt = $this->apilib->create('attributi_valori', [
                            'attributi_valori_attributo' => $attribute_id,
                            'attributi_valori_label' => $attribute->option
                        ]);

                        $crm_attribute_opt_id = $new_attribute_opt['attributi_valori_id'];
                    }

                    if ($crm_attribute_opt_id) {
                        $wc_attributes[$attribute_id] = $crm_attribute_opt_id;
                    }
                } else {
                    // OPTIONS SECTION
                    foreach ($attribute->options as $attribute_opt) {
                        $crm_attribute_opt = $this->apilib->searchFirst('attributi_valori', [
                            'attributi_valori_attributo' => $attribute_id,
                            "LOWER(attributi_valori_label) = LOWER('{$attribute_opt}')"
                        ]);

                        $crm_attribute_opt_id = null;
                        if (!empty($crm_attribute_opt)) {
                            $crm_attribute_opt_id = $crm_attribute_opt['attributi_valori_id'];
                        } else {
                            $new_attribute_opt = $this->apilib->create('attributi_valori', [
                                'attributi_valori_attributo' => $attribute_id,
                                'attributi_valori_label' => $attribute_opt
                            ]);

                            $crm_attribute_opt_id = $new_attribute_opt['attributi_valori_id'];
                        }

                        if ($crm_attribute_opt_id) {
                            $wc_attributes[$attribute_id][] = $crm_attribute_opt_id;
                        }
                    }
                }
            }
        }

        return $wc_attributes;
    }

    public function import_images($product, $crm_product, $sync_images = 0)
    {
        echo_flush("importing images for $product->id", "<br/>");
        if (empty($product->images)) return false;

        $uploads_folder = FCPATH . 'uploads/';
        $wc_folder = 'wc_imported/';
        $full_wc_folder = $uploads_folder . $wc_folder;

        if (!file_exists($full_wc_folder) || !is_dir($full_wc_folder)) {
            @mkdir($full_wc_folder);
        }

        $crm_images = [];
        $main_image = '';
        foreach ($product->images as $index => $image) {
            $url = $image->src;
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            $name = pathinfo($url, PATHINFO_FILENAME);
            $filename = pathinfo($url, PATHINFO_BASENAME);

            $image_bin = file_get_contents($url);

            $dest_file = $wc_folder . $filename;

            if (!file_exists($dest_file) || $sync_images == 1) {
                $image_bin = file_get_contents($url);

                $downloaded = file_put_contents($uploads_folder . $dest_file, $image_bin);

                if (!$downloaded && !file_exists($dest_file)) continue;
            }

            if (array_key_first($product->images) == $index) {
                $main_image = $dest_file;
                continue;
            }

            $filesize = filesize($uploads_folder . $dest_file);
            $filesize_kb = round($filesize / 1024, 2);

            $mime_type = mime_content_type($uploads_folder . $dest_file);

            $file = [
                'file_name' => $filename,
                'file_type' => $mime_type,
                'file_path' => $full_wc_folder,
                'full_path' => $full_wc_folder . $filename,
                'raw_name' => $name,
                'orig_name' => $filename,
                'client_name' => $name,
                'file_ext' => $ext,
                'file_size' => $filesize_kb,
                'is_image' => false,
                'image_width' => null,
                'image_height' => null,
                'image_type' => "",
                'image_size_str' => "",
                'original_filename' => $name,
                'path_local' => $dest_file,
            ];

            if (stripos($mime_type, 'image/') !== false) {
                $img_info = getimagesizefromstring($image_bin);

                if (!empty($img_info)) {
                    $width = $img_info[0];
                    $height = $img_info[1];
                    $wh_string = $img_info[3];

                    $image_data = [
                        'is_image' => true,
                        'image_width' => $width,
                        'image_height' => $height,
                        'image_type' => mime2ext($mime_type),
                        'image_size_str' => $wh_string,
                    ];

                    $file = array_merge($file, $image_data);
                }
            }

            $crm_images[] = $file;
        }

        if (!empty($crm_images) || !empty($main_image)) {
            $fw_product = [];
            if (!empty($crm_images)) {
                $fw_product['fw_products_images'] = json_encode($crm_images);
            }

            if (!empty($main_image)) {
                $fw_product['fw_products_main_image'] = $main_image;
            }

            try {
                $this->apilib->edit('fw_products', $crm_product['fw_products_id'], $fw_product);
                echo_flush("imported images for $product->id", "<br/>");
            } catch (Exception $e) {
                echo_flush("error importing images for $product->id", "<br/>");
            }
        }

        return true;
    }

    public function import_categories($product, $crm_product)
    {
        echo_flush("importing categories for $product->id", "<br/>");
        if (empty($product->categories)) return false;

        $this->db->where('fw_products_id', $crm_product['fw_products_id'])->delete('fw_products_fw_categories');

        foreach ($product->categories as $category) {
            $crm_category = $this->apilib->searchFirst('fw_categories', ['fw_categories_woocommerce_external_code' => $category->id]);

            if (empty($crm_category)) {
                $cat_id = $this->apilib->create('fw_categories', [
                    'fw_categories_woocommerce_external_code' => $category->id,
                    'fw_categories_name' => $category->name
                ], false);
            } else {
                $cat_id = $crm_category['fw_categories_id'];
            }

            $this->db->insert('fw_products_fw_categories', [
                'fw_categories_id' => $cat_id,
                'fw_products_id' => $crm_product['fw_products_id']
            ]);

            echo_flush("Imported category $category->name for $product->id", "<br/>");
        }

        return true;
    }

    public function elaboraprodotto($crm_product)
    {
        // Forzo a 0 il campo "fw_products_quantity" nel caso sia vuoto, null o non sia un valore numerico
        if ($crm_product['fw_products_quantity'] == null || $crm_product['fw_products_quantity'] == '' || !is_numeric($crm_product['fw_products_quantity'])) {
            $crm_product['fw_products_quantity'] = 0;
        }

        $wc_response = null;
        // verifico che il prodotto, a prescindere da cosa sia, abbia l'id woocommerce
        if (!empty($crm_product['fw_products_woocommerce_external_code'])) {
            // e aggiorno la quantità woocommerce se è una variante o un prodotto padre
            if (empty($crm_product['fw_products_parent'])) { // se è un prodotto singolo senza varianti
                $wc_response = $this->wc->put("products/{$crm_product['fw_products_woocommerce_external_code']}", ['stock_quantity' => $crm_product['fw_products_quantity']]);
            } else { //  oppure se è una variante
                // quindi mi tiro fuori il prodotto padre perchè mi serve il suo codice woocommerce
                $parent = $this->db->get_where("fw_products", ['fw_products_id' => $crm_product['fw_products_parent']])->row_array();
                if (!empty($parent) && !empty($parent['fw_products_woocommerce_external_code'])) {
                    $wc_response = $this->wc->put("products/{$parent['fw_products_woocommerce_external_code']}/variations/{$crm_product['fw_products_woocommerce_external_code']}", ['stock_quantity' => $crm_product['fw_products_quantity']]);
                }
            }

            echo_flush("synced qty {$crm_product['fw_products_quantity']} for  {$crm_product['fw_products_name']}\n", '<br/>');
            log_message("debug", "synced qty {$crm_product['fw_products_quantity']} for  {$crm_product['fw_products_name']}\n");
        } else {
            log_message('error', "Anomalia imprevista!!! Prodotto senza extenal code nonostatne sia filtrata la query.");
        }

        return $wc_response;
    }

    /**
     * @param $movimento
     * @return bool
     * @description Questo metodo viene chiamato prima di sync_qty, perchè deve verificare diverse casistiche:
     *
     * 1. Che il modulo woocommerce sia attivo e che l'impostazione di sync quantità sia fattibile;
     * 2. Che il sync quantità sia fattibile seguendo questo ragionamento:
     *  a. Se è #1, allora sincronizzo sempre le quantità, a prescindere dal magazzino;
     *  b. Se è #2, devo sincronizzare solo se il magazzino selezionato ha il flag "sync quantità ecommerce" a true
     *  c. Se è #3, non devo mai sincronizzare le quantità
     */
    public function can_sync_qty($movimento) {
        if (!$this->wc_enabled) // verifico che il modulo sia attivo
            return DB_BOOL_FALSE;

        // verifico se la quantità si deve sincronizzare
        if ($this->wc_qty_sync == 1) {// sempre
            return DB_BOOL_TRUE;
        } elseif ($this->wc_qty_sync == 2) { // solo se il magazzino ha il flag attivo
            if (!empty($movimento) && $movimento['magazzini_sync_quantita_ecommerce'] == DB_BOOL_TRUE) {
                return DB_BOOL_TRUE;
            } else {
                return DB_BOOL_FALSE;
            }
        } elseif ($this->wc_qty_sync == 3) { // mai
            return DB_BOOL_FALSE;
        } else {
            // c'è una anomalia quindi evito di fare sync, per sicurezza
            return DB_BOOL_FALSE;
        }
    }

    public function sync_qty($product, $new_qty, $parent_product = null) {
        // verifico che il wc_id non sia vuoto
        if (empty($product['fw_products_woocommerce_external_code'])) {
            log_message("error", "Anomalia sync qta #{$product['fw_products_id']} - WC_ID vuoto");
            return DB_BOOL_FALSE;
        }

        $wc_id = $product['fw_products_woocommerce_external_code'];

        // se c'è un product_parent (quindi il $product è di fatto una variante), allora verifico che anche di quello il parent abbia il wc_id
        if (!empty($parent_product) && empty($parent_product['fw_products_woocommerce_external_code'])) {
            log_message("error", "Anomalia sync qta #{$product['fw_products_id']} - Parent #{$parent_product['fw_products_id']} WC_ID vuoto");
            return DB_BOOL_FALSE;
        }

        $parent_wc_id = $parent_product['fw_products_woocommerce_external_code'] ?? null;

        // sincronizzo le quantità
        if (!empty($parent_product)) {
            $wc_response = $this->wc->put("products/{$parent_wc_id}/variants/{$wc_id}", [
                'stock_quantity' => $new_qty
            ]);

            if (empty($wc_response->code) && empty($wc_response->message)) {
                log_message("debug", "Fatta sync qta ({$new_qty}) #{$product['fw_products_id']} - Parent #{$parent_product['fw_products_id']}");
            } else {
                log_message("error", "Anomalia sync qta #{$product['fw_products_id']} - Parent #{$parent_product['fw_products_id']} {$wc_response->message}");
            }
        } else {
            $wc_response = $this->wc->put("products/{$wc_id}", [
                'stock_quantity' => $new_qty
            ]);

            if (empty($wc_response->code) && empty($wc_response->message)) {
                log_message("debug", "Fatta sync qta ({$new_qty}) #{$product['fw_products_id']}");
            } else {
                log_message("error", "Anomalia sync qta #{$product['fw_products_id']} - {$wc_response->message}");
            }
        }
    }
}
