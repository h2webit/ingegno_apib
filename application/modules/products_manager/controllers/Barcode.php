<?php
class Barcode extends MY_Controller {
    public function __construct() {
        parent::__construct();

        $this->load->model('products_manager/prod_model');
    }

    public function print($type)
    {
        $view_data = $this->input->get();

        $view_data['ean'] = base64_decode($view_data['ean']);
        $view_data['product_name'] = base64_decode($view_data['product_name']);
        $view_data['notes'] = (!empty($view_data['notes'])) ? base64_decode($view_data['notes']) : null;
        $view_data['type'] = $type;

        $this->load->view('products_manager/barcode/print', $view_data);
    }

    public function bulk_assign_ean($force_ean_assign = 0) {
        $post = $this->input->post(); // ottengo i dati $_post

        $where = [];
        // devo forzare la riassegnazione dell'ean?
        if ($force_ean_assign == 0) { // no, quindi inserisco un filtro che prende solo i prodotti che non hanno ancora il campo fw_products_barcode popolato
            $where[] = "(fw_products_barcode IS NULL OR fw_products_barcode = '')";
        }

        // mi stanno arrivando degli id in post?
        if (!empty($post['ids'])) {
            // si, quindi li appendo con un where_in separati da virgola
            $post_ids = json_decode($post['ids'], true);

            $prodotti_ids = implode(',', $post_ids);

            $where[] = "(fw_products_id IN ({$prodotti_ids}))";
        }

        // Ottengo quindi i prodotti (usando apilib così filtra già eventuali deleted)
        $products = $this->apilib->search('fw_products', $where);

        // verifico che ci siano prodotti
        if (!empty($products)) {
            $t = count($products);
            $c = 0;
            foreach ($products as $product) {
                // genero l'ean
                $generated_ean = $this->prod_model->generateEAN();

                $c++;

                // verifico che questo ean non sia già stato usato
                $check_ean = $this->db->where("(fw_products_id <> '{$product['fw_products_id']}') AND fw_products_barcode = '[\"{$generated_ean}\"]'", null, false)->get("fw_products");
                if ($check_ean->num_rows() <= 0) {
                    $this->db->where('fw_products_id', $product['fw_products_id'])->update('fw_products', ['fw_products_barcode' => '["'.$generated_ean.'"]']);
                    echo_flush("(ri)generato ean {$generated_ean} per il prodotto #{$product['fw_products_id']}", '<br/>');
                } else {
                    echo_flush("attenzione, pare ci sia un ean già usato nel prodotto #{$check_ean->row()->fw_products_id}", '<br/>');
                }

                progress($c, $t);
            }
            echo_flush("tutti i prodotti sono stati processati, puoi chiudere la pagina");
        } else { // altrimenti do un messaggio che non ci sono prodotti
            echo_flush("nessun prodotto da processare, puoi chiudere la pagina.");
        }
    }

    public function bulk_print_pdf() {
        // PULISCO I DATI IN POST, PER EVITARE XSS
        $post = $this->security->xss_clean($this->input->post());

        // VERIFICO CHE "ids" ARRIVI IN POST
        if (empty($post['ids'])) die('ANOMALIA: ID Prodotti non dichiarati');

        // CONVERTO IN ARRAY IL JSON CHE MI ARRIVA CON GLI ID
        $prodotti_ids = json_decode($post['ids'], true);

        // CERCO I PRODOTTI CON QUEI ID MA ANCHE FILTRANDO SOLO QUELLI CHE HANNO IL CAMPO BARCODE POPOLATO E ORDINANDO COME NOME ALFABETICAMENTE CRESCENTE
        $prodotti = $this->db
            ->where_in("fw_products_id", $prodotti_ids)
            ->where("(fw_products_barcode IS NOT NULL AND fw_products_barcode <> '')", null, false)
            ->order_by('fw_products_name', "ASC")->get('fw_products')->result_array();

        // SE NON TROVA PRODOTTI CON BARCODE, BLOCCO TUTTO
        if (empty($prodotti)) die("Nessun prodotto con barcode da stampare");

        // INIZIO LA GENERAZIONE HTML
        $barcodes_html = '';
        foreach ($prodotti as $prod) {
            // RENDO IN ARRAY IL JSON DEI BARCODES
            $barcodes = json_decode($prod['fw_products_barcode'], true);

            // VERIFICO PER SCRUPOLO CHE IL PRIMO ELEMENTO DELL'ARRAY NON SIA VUOTO E CHE SIA UN NUMERO
            if (!empty($barcodes[0])) {
                // FACCIO UN ARRAY CON I DATI CHE SERVONO ALLA VIEW, QUINDI LA GENERO E LA OTTENGO COME HTML
                $barcode_view_data = [
                    'ean' => $barcodes[0],
                    'product_name' => $prod['fw_products_name'],
                    'notes' => null,
                    'type' => "EAN13",
                    'w' => 100,
                    'h' => 30,
                    'left' => 0,
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'show_product_name' => true,
                    'show_ean' => true,
                    'show_print_dialog' => false,
                    'show_divider' => true,
                    'font_size_ct' => "1.2rem",
                    'font_size_label' => "10px",
                ];

                $view_html = $this->load->view('products_manager/barcode/print', $barcode_view_data, true);

                // APPENDO
                $barcodes_html .=  $view_html;
            }
        }

        // GENERO IL PDF
        $this->layout->generate_pdf($barcodes_html, 'portrait', '', [], 'proposal', true, [
            'useMpdf' => true,
            'mpdfInit' => [
                'mode' => 'utf-8',

                'margin_top' => 0,
                'margin_left' => 0,
                'margin_bottom' => 0,
                'margin_right' => 0,

                'margin_header' => 0,
                'margin_footer' => 0
            ],
            'mpdfTitle' => "Stampa Barcodes Prodotti"
        ]);
    }

    public function save_settings() {
        $post = $this->input->post();

        foreach ($post as $key => $val) {
            if (!in_array($key,['fw_products_settings_label_default_height', 'fw_products_settings_label_default_width', 'fw_products_settings_label_default_left_margin', 'fw_products_settings_label_default_top_margin'])) {
                unset($post[$key]);
                continue;
            }
            if (empty($val) || !is_numeric($val)) {
                $post[$key] = 0;
            }
        }

        if (empty($post)) {
            die(e_json(['status' => 0, 'txt' => t('No data')]));
        }

        //debug($post, true);

        $setting = $this->apilib->searchFirst('fw_products_settings');

        try {
            if (!empty($setting)) {
                $this->apilib->edit('fw_products_settings', $setting['fw_products_settings_id'], $post);
            } else {
                $this->apilib->create('fw_products_settings', $post);
            }

            die(e_json(['status' => 3, 'txt' => t('Saved')]));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => t('Error has occurred')]));
        }
    }
}
