<?php
    class Subscriptions extends MY_Controller
    {
        public function updateFuturePricesFromProduct($product_id)
        {
            $product = $this->apilib->searchFirst('fw_products', ['fw_products_id' => $product_id]);
            $subscriptions = $this->apilib->search('subscriptions', ['subscriptions_product' => $product_id]);

            if (!empty($subscriptions)) {
                foreach ($subscriptions as $subscription) {
                    if ($subscription['subscriptions_price'] !== $product['fw_products_sell_price']) {
                        $this->apilib->edit('subscriptions', $subscription['subscriptions_id'], ['subscriptions_price' => $product['fw_products_sell_price']]);
                        echo_flush('modificato prezzo subscription');
                    } else {
                        echo_flush('skip prezzo non cambiato');
                    }
                }
            } else {
                echo_flush('skip no subscription trovate');
            }

            redirect(base_url(), 'refresh');
        }
    }
