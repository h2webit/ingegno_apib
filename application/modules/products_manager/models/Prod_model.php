<?php
    class Prod_model extends CI_Model {
        public function __construct() {
            $this->listini = $this->db->get('price_list_labels')->result_array();
        }

        public function generateEAN() {
            $digits = '';
            $checksum = 0;

            // Generate the first 12 random digits
            for ($i = 0; $i < 12; $i++) {
                $digits .= mt_rand(0, 9);
            }

            // Calculate the checksum digit
            for ($i = 0; $i < 12; $i += 2) {
                $checksum += $digits[$i];
                $checksum += $digits[$i + 1] * 3;
            }
            $checksum = (10 - ($checksum % 10)) % 10;

            // Combine the digits and checksum to form the complete EAN-13 number
            $ean13 = $digits . $checksum;

            return $ean13;
        }
        
        public function calcolaPrezzoPadreDaFiglio($prodotto_id) {
            if (!is_numeric($prodotto_id)) {
                return false;
            }
            
            // ottengo l'info del bundle dal prodotto che mi arriva
            $bundles = $this->db->where('fw_products_bundle_product', $prodotto_id)->get('fw_products_bundle')->result_array();
            
//            debug($bundles, true);
            
            if (!empty($bundles)) {
                foreach ($bundles as $bundle) {
                    $this->calcolaPrezzoPadre($bundle['fw_products_bundle_main_product']);
                }
            }
            
            return true;
        }
        
        public function calcolaPrezzoPadre($prodotto_padre_id) {
            // estraggo i prodotti del bundle del prodotto principale
            $prodotti_bundle = $this->db
                ->join('fw_products', 'fw_products_bundle_product = fw_products_id', 'LEFT')
                ->where('fw_products_bundle_main_product', $prodotto_padre_id)
                ->get('fw_products_bundle')->result_array();

//                    debug($prodotti_bundle);
            
            if (!empty($prodotti_bundle)) {
                // inizializzo variabili vuote (0 per i prezzi, array vuoto per i listini)
                $sell_price = $sell_price_taxed = $provider_price = $discounted_price = 0;
                $prezzi_listini = [];
                // ciclo i prodotti del bundle
                foreach ($prodotti_bundle as $prodotto_bundle) {
                    // sommo i prezzi di tutti i prodotti
                    $sell_price += ($prodotto_bundle['fw_products_sell_price'] * ($prodotto_bundle['fw_products_bundle_quantity'] > 1 ? $prodotto_bundle['fw_products_bundle_quantity'] : 1));
                    $sell_price_taxed += ($prodotto_bundle['fw_products_sell_price_tax_included'] * ($prodotto_bundle['fw_products_bundle_quantity'] > 1 ? $prodotto_bundle['fw_products_bundle_quantity'] : 1));
                    $provider_price += ($prodotto_bundle['fw_products_provider_price'] * ($prodotto_bundle['fw_products_bundle_quantity'] > 1 ? $prodotto_bundle['fw_products_bundle_quantity'] : 1));
                    $discounted_price += ($prodotto_bundle['fw_products_discounted_price'] * ($prodotto_bundle['fw_products_bundle_quantity'] > 1 ? $prodotto_bundle['fw_products_bundle_quantity'] : 1));
                    
                    // verifico che esistano dei listini
                    if (!empty($this->listini)) {
                        foreach ($this->listini as $listino) {
                            // inizializzo un valore a 0 per ogni listino, se già non presente nell'array $prezzi_listini
                            if (!array_key_exists($listino['price_list_labels_id'], $prezzi_listini)) {
                                $prezzi_listini[$listino['price_list_labels_id']] = 0;
                            }
                            
                            // ottengo e sommo il prezzo del listino del prodotto
                            $listino_prodotto = $this->db
                                ->select("SUM(COALESCE(price_list_price, 0)) AS prezzo_listino_prodotto")
                                ->where('price_list_label', $listino['price_list_labels_id'])
                                ->where('price_list_product', $prodotto_bundle['fw_products_id'])
                                ->get('price_list')->row_array();
                            
                            $prezzi_listini[$listino['price_list_labels_id']] += ($listino_prodotto['prezzo_listino_prodotto'] * ($prodotto_bundle['fw_products_bundle_quantity'] > 1 ? $prodotto_bundle['fw_products_bundle_quantity'] : 1));
                        }
                    }
                }

//                        debug([$sell_price, $sell_price_taxed, $provider_price, $discounted_price, $prezzi_listini], true);
                
                try {
                    // aggiorno i prezzi del prodotto padre
                    $this->apilib->edit('fw_products', $prodotto_padre_id, [
                        'fw_products_sell_price' => $sell_price,
                        'fw_products_sell_price_tax_included' => $sell_price_taxed,
                        'fw_products_provider_price' => $provider_price,
                        'fw_products_discounted_price' => $discounted_price
                    ]);
                } catch (Exception $e) {
                    log_message("error", "ERRORE AGGIORNAMENTO PREZZI PRODOTTO BUNDLE: " . $e->getMessage());
                }
                
                if (!empty($prezzi_listini)) {
                    foreach ($prezzi_listini as $listino_id => $prezzo) {
                        $listino_prodotto_main = $this->apilib->searchFirst('price_list', ['price_list_label' => $listino_id, 'price_list_product' => $prodotto_padre_id]);
                        if (!empty($listino_prodotto_main)) {
                            // aggiorno i prezzi dei listini del prodotto padre
                            try {
                                $this->apilib->edit('price_list', $listino_prodotto_main['price_list_id'], [
                                    'price_list_price' => $prezzo
                                ]);
                            } catch (Exception $e) {
                                log_message("error", "ERRORE AGGIORNAMENTO LISTINO {$listino_prodotto_main['price_list_labels_name']} PRODOTTO BUNDLE: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
    }
