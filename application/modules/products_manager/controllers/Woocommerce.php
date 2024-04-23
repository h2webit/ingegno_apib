<?php

use Automattic\WooCommerce\Client;

require __DIR__ . '/../vendor/autoload.php';

class Woocommerce extends MY_Controller
{
    private $wc;
    private $wc_settings;

    function __construct()
    {
        parent::__construct();

        $woocommerce = $this->apilib->searchFirst('woocommerce');

        if ($woocommerce['woocommerce_enabled'] !== DB_BOOL_TRUE) {
            throw new ApiException("Modulo woocommerce non abilitato");
            exit;
        }

        if (empty($woocommerce['woocommerce_consumer_key']) || empty($woocommerce['woocommerce_consumer_secret']) || empty($woocommerce['woocommerce_endpoint'])) {
            throw new ApiException('Consumer key, secret or endpoint url not declared');
            exit;
        }

        $this->wc_settings = $woocommerce;

        $url = (empty(parse_url($woocommerce['woocommerce_endpoint'])['scheme'])) ? 'https://' . $woocommerce['woocommerce_endpoint'] : $woocommerce['woocommerce_endpoint'];

        $this->wc = new Client(
            $url,
            $woocommerce['woocommerce_consumer_key'],
            $woocommerce['woocommerce_consumer_secret'],
            [
                'version' => 'wc/v3',
            ]
        );

        $this->load->model("products_manager/woocomm");
    }

    private function isWithinTimeRange($orarioFornito, $orarioAttuale)
    {
        // Converti l'orario fornito in oggetto DateTime
        $orarioFornitoObj = DateTime::createFromFormat('H:i', $orarioFornito);

        if ($orarioFornitoObj === false) {
            return false;
        }

        // Converti l'orario attuale in oggetto DateTime
        $orarioAttualeObj = DateTime::createFromFormat('H:i', $orarioAttuale);

        // Imposta l'orario fornito per l'1:00
        $orarioFornitoObj->setTime(1, 0);

        // Imposta l'orario fornito per l'1:05
        $orarioFornitoFine = clone $orarioFornitoObj;
        $orarioFornitoFine->add(new DateInterval('PT5M'));

        // Verifica se l'orario attuale è compreso tra l'1:00 e l'1:05
        return ($orarioAttualeObj > $orarioFornitoObj && $orarioAttualeObj <= $orarioFornitoFine);
    }


    public function testOra() {
        for ($i = 4; $i < 1440; $i+=5) {
            $oraFornita = '01:00';
            $oraAttuale = sprintf("%02d:%02d", floor($i / 60), $i % 60);

            $risultato = $this->isWithinTimeRange($oraFornita, $oraAttuale);

            echo "Orario fornito: $oraFornita, Orario attuale: $oraAttuale, Risultato: " . ($risultato ? "true" : "false") . "<br />";
        }
    }

    /**
     * @param int $usa_movimenti
     * @param int $force_empty_to_zero
     * @return void
     * @description
     * Se $usa_movimenti è true, appende nella query di ricerca, un filtro per ottenere solo i prodotti che son stati movimentati;
     * (Rimosso parametro da Matteo, ritenuto inutile: woocommerce richiede sempre e comunque un int, quindi va sempre castato a prescindere... Se $force_empty_to_zero è true, forza il campo fw_products_quantity a 0 se esso è: null, vuoto ('') o non è numerico;
     */
    public function batch_qty_sync($usa_movimenti = 1)
    {
        /**
         * Michael - 25/07/2023
         *
         * Da ora il batch_qty_sync accetta anche il parametro IDS in $_POST (derivante ad esempio da bulk)
         * e (dopo vari controlli) aggiunge alla query di estrazione dei prodotti, solo gli id che vengono passati.
         *
         * Utile per fare batch limitate a specifici prodotti
         */
        $post_data = $this->input->post();

        $prodotti_ids = null;
        if (!empty($post_data['ids'])) {
            $ids_array = json_decode($post_data['ids'], true);
            if (is_array($ids_array) && !empty($ids_array)) {
                $prodotti_ids = implode(',', $ids_array);
            } else {
                // non continuo in quanto i pare che sia vuoto l'array... se entra qui, va verificato perchè in post non ci sono articoli validi
            }
        } else {
            // non faccio nulla
        }

        $force = $this->input->get('force') == 1;

        // Verifica se il flag "force" è impostato a 1
        if (!$force && empty($prodotti_ids)) {
            // Verifica se è stato impostato un orario per la sincronizzazione
            if (!empty($this->wc_settings['woocommerce_qty_sync_cron_time'])) {
                log_message("debug", "Starting batch qty sync to woocommerce");
                echo_flush("Starting batch qty sync to woocommerce", '<br/>');

                // Ottieni l'orario fornito per la sincronizzazione
                $orarioFornito = $this->wc_settings['woocommerce_qty_sync_cron_time'];
                $orarioAttuale = date('H:i');

                // Verifica se l'orario attuale rientra nell'intervallo di 5 minuti rispetto all'orario fornito
                if (!$this->isWithinTimeRange($orarioFornito, $orarioAttuale)) {
                    log_message("debug", "Full batch qty sync Setted hour {$this->wc_settings['woocommerce_qty_sync_cron_time']} does not match the 5 minutes range {$orarioAttuale}");
                    echo_flush("Full batch qty sync Setted hour {$this->wc_settings['woocommerce_qty_sync_cron_time']} does not match the 5 minutes range {$orarioAttuale}", '<br/>');

                    return;
                }
            } else {
                // Nessun orario valido impostato per la sincronizzazione
                log_message("debug", "Full batch qty sync does not have a valid time setted up");
                echo_flush("Full batch qty sync does not have a valid time setted up", '<br/>');

                return;
            }
        } else {
            // tutto ok, procedo
        }

        log_message("debug", "Starting batch qty sync to woocommerce");
        echo_flush("Starting batch qty sync to woocommerce", '<br/>');

        // cerco i prodotti che son stati movimentati (se flag attivo)
        $where_in_movimenti_arr = array_filter([
            "fw_products_woocommerce_external_code <> ''",
            "fw_products_woocommerce_external_code IS NOT NULL",
            "(fw_products_deleted IS NULL OR fw_products_deleted = '' OR fw_products_deleted = '0')",
            ($usa_movimenti == 1) ? 'fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli WHERE movimenti_articoli_prodotto_id IS NOT NULL)' : '',
            (!empty($prodotti_ids)) ? "fw_products_id IN ({$prodotti_ids})" : '', // 25/07/2023 - michael - aggiunta questa riga per il batch sync qty per una bulk di prodotti
        ]);

        $where_in_movimenti_str = implode(' AND ', $where_in_movimenti_arr);
        $sql = "SELECT * FROM fw_products WHERE $where_in_movimenti_str ";
        $crm_products = $this->db->query($sql)->result_array();

        if (!empty($crm_products)) {
            // li ciclo
            $t = count($crm_products);
            $c = 0;
            foreach ($crm_products as $crm_product) {
                $c++;
                try {
                    $wc_response = $this->woocomm->elaboraprodotto($crm_product);
                } catch (Exception $e) {
                    log_message("error", "[WC] sync qty error -> {$e->getMessage()} | " . json_encode($crm_product));
                }

                progress($c, $t);
            }
        } else {
            echo_flush("no products to sync", '<br/>');
            log_message("debug", "no products to sync");

        }

        echo_flush("finished batch qty sync to woocommerce", '<br/>');
        log_message("debug", "finished batch qty sync to woocommerce");
    }

    public function base_sync() //, $force_empty_to_zero = 1)
    {
        log_message("debug", "Starting batch qty sync to woocommerce");
        echo_flush("Starting batch qty sync to woocommerce", '<br/>');

        // cerco i prodotti che son stati movimentati
        $where_in_movimenti_arr = [
            "fw_products_woocommerce_external_code <> ''",
            "fw_products_woocommerce_external_code IS NOT NULL",
            "fw_products_id IN (
                SELECT movimenti_articoli_prodotto_id 
                FROM movimenti_articoli 
                WHERE 
                    (movimenti_articoli_prodotto_id IS NOT NULL AND movimenti_articoli_prodotto_id <> '')
                    AND (
                        movimenti_articoli_sync_esterno = 1 
                        OR movimenti_articoli_sync_esterno IS NULL 
                        OR movimenti_articoli_sync_esterno = ''
                    )
            )",
        ];

        $where_in_movimenti_str = implode(' AND ', $where_in_movimenti_arr);
        $sql = "SELECT * FROM fw_products WHERE $where_in_movimenti_str";
        $crm_products = $this->db->query($sql)->result_array();

//        debug($crm_products, true);

        if (!empty($crm_products)) {
            // li ciclo
            $t = count($crm_products);
            $c = 0;
            foreach ($crm_products as $crm_product) {
                $c++;

                try {
                    $wc_response = $this->woocomm->elaboraprodotto($crm_product);
                    $wc_response = json_encode($wc_response);

                    $stato_da_aggiornare = 2;
                } catch (Exception $e) {
                    $wc_response = $e->getMessage();

                    log_message("error", "[WC] sync qty error -> {$e->getMessage()} | " . json_encode($crm_product));
                    $stato_da_aggiornare = 3;
                }

                // aggiorno tutte le righe articolo con il flag "elaborato", aggiornando anche i campi "sync_esterno_data" con la data nel momento in cui aggiorna e "sync_esterno_extra" che contiene o l'oggetto di ritorno di woocommerce oppure la stringa di errore in caso di errore woocommerce
                $this->db->where([
                    "(movimenti_articoli_sync_esterno = 1 OR movimenti_articoli_sync_esterno IS NULL OR movimenti_articoli_sync_esterno = '')",
                    'movimenti_articoli_prodotto_id' => $crm_product['fw_products_id'],
                ])->update('movimenti_articoli', [
                    'movimenti_articoli_sync_esterno' => $stato_da_aggiornare,
                    'movimenti_articoli_sync_esterno_data' => date('Y-m-d H:i:s'),
                    'movimenti_articoli_sync_esterno_extra' => $wc_response
                ]);

                progress($c, $t);
            }
        } else {
            echo_flush("no products to sync", '<br/>');
            log_message("debug", "no products to sync");
        }

        echo_flush("finished batch qty sync to woocommerce", '<br/>');
        log_message("debug", "finished batch qty sync to woocommerce");
    }
    public function importorders($page = 1, $force = false)
    {
        $last_order = $this->db
            ->where("documenti_contabilita_tipo", 5)
            ->like('documenti_contabilita_codice_esterno', 'WOOCOMM-', 'after')
            ->limit(1)->order_by("documenti_contabilita_data_emissione", 'DESC')
            ->get("documenti_contabilita")->row_array();

        if (!empty($last_order) && !$force) {
            $dateobj = DateTime::createFromFormat('Y-m-d H:i:s', $last_order['documenti_contabilita_data_emissione']);
            $orderdate = $dateobj->format('Y-m-d\TH:i:s');
        } else {
            $orderdate = null;
        }

        echo_flush(date('Y-m-d H:i:s') . " Inizio import ordini\n");

        if ($page == null) $page = 1;

        for ($i = $page; $i <= 10; $i++) {
            $ordini_woocommerce = $this->wc->get('orders', ['per_page' => 100, 'page' => $i, 'after' => $orderdate]);

//            debug($ordini_woocommerce, true);

            $wc_orders = $this->db
                ->where("documenti_contabilita_tipo", 5)
                ->like('documenti_contabilita_codice_esterno', 'WOOCOMM-', 'after')
                ->order_by("documenti_contabilita_data_emissione", 'DESC')
                ->get("documenti_contabilita")->result_array();

            $wc_order_ids = array_map(function ($wc_order) {
                return $wc_order['documenti_contabilita_codice_esterno'];
            }, $wc_orders);

            if (empty($ordini_woocommerce)) {
                echo_flush("Nessun ordine da importare");
                break;
            }

            $t = count($ordini_woocommerce);
            $c = 0;
            foreach ($ordini_woocommerce as $ordine_woocommerce) {
                if (in_array('WOOCOMM-' . $ordine_woocommerce->id, $wc_order_ids)) {
                    echo_flush("Skip ordine 'WOOCOMM-{$ordine_woocommerce->id}' già esistente");
                    continue;
                }

                $cliente_woocommerce = $ordine_woocommerce->billing;
                $email_cliente = $cliente_woocommerce->email;

                $cliente_exists = $this->apilib->searchFirst('customers', ['customers_email' => $email_cliente]);
                if (!empty($cliente_exists)) {
                    $cliente_id = $cliente_exists['customers_id'];
                } else {
                    $nazione_id = $this->db->get_where('countries', ['countries_iso' => $cliente_woocommerce->country])->row()->countries_id;

                    $cliente = [
                        'customers_zip_code' => $cliente_woocommerce->postcode,
                        'customers_mobile' => preg_replace('/\D+/', '', $cliente_woocommerce->phone),
                        'customers_city' => ucfirst($cliente_woocommerce->city),
                        'customers_description' => 'Importato automaticamente da woocommerce il ' . date('d/m/Y H:i'),
                        'customers_email' => $email_cliente,
                        'customers_address' => $cliente_woocommerce->address_1,
                        'customers_country_id' => $nazione_id,
                        'customers_province' => ucfirst($cliente_woocommerce->state),
                        'customers_phone' => preg_replace('/\D+/', '', $cliente_woocommerce->phone),
                        'customers_cf' => null,
                        'customers_vat_number' => null,
                        'customers_type' => 1,
                        'customers_status' => 1,
                    ];

                    if (!empty($cliente_woocommerce->company)) {
                        $cliente['customers_company'] = $cliente_woocommerce->company;
                        $cliente['customers_group'] = 2;
                    } else {
                        $cliente['customers_name'] = $cliente_woocommerce->first_name;
                        $cliente['customers_last_name'] = $cliente_woocommerce->last_name;
                        $cliente['customers_group'] = 1;
                    }

                    $cliente_id = $this->apilib->create('customers', $cliente, false);
                }

                $note = '';

                if (!empty($ordine_woocommerce->coupon_lines)) {
                    foreach ($ordine_woocommerce->coupon_lines as $coupon_line) {
                        foreach ($coupon_line->meta_data as $coupon) {
                            $coupon = $coupon->value;

                            $note .= "Applicato coupon {$coupon->code} del {$coupon->amount}%\n";
                        }
                    }
                }

                $ordine = [
                    'tipo_documento' => 5,
                    'cliente_id' => $cliente_id,
                    'codice_esterno' => 'WOOCOMM-' . $ordine_woocommerce->id,
                    'numero' => $ordine_woocommerce->number,
                    'data_emissione' => dateFormat($ordine_woocommerce->date_created, 'Y-m-d'),
                    'utente' => null,
                    'note_interne' => $note,
                    'imponibile' => $ordine_woocommerce->total,
                    'iva' => $ordine_woocommerce->total_tax,
                    'iva_json' => '{"1":[22,' . $ordine_woocommerce->total_tax . ']}',
                    'imponibile_iva_json' => '{"1":[22,' . $ordine_woocommerce->total_tax . ']}',
                    'competenze' => $ordine_woocommerce->total,
                    'documenti_contabilita_totale' => $ordine_woocommerce->total,
                    'stato' => ($this->wc_settings['woocommerce_chiudi_ordini_importati'] == DB_BOOL_TRUE) ? 3 : 1,
                ];

                $articoli_data = [];

                $movimento_articoli = [];
                foreach ($ordine_woocommerce->line_items as $articolo_woocommerce) {
                    $prodotto_id = ($articolo_woocommerce->variation_id > 0) ? $articolo_woocommerce->variation_id : $articolo_woocommerce->product_id;
                    $prodotto = $this->apilib->searchFirst('fw_products', ['fw_products_woocommerce_external_code' => $prodotto_id]);

                    $riga_articolo = [
                        'documenti_contabilita_articoli_applica_ritenute' => '1',
                        'documenti_contabilita_articoli_applica_sconto' => '1',
                        'documenti_contabilita_articoli_codice' => $articolo_woocommerce->sku,
                        'documenti_contabilita_articoli_descrizione' => $prodotto['fw_products_description'],
                        'documenti_contabilita_articoli_imponibile' => '0',
                        'documenti_contabilita_articoli_importo_totale' => $articolo_woocommerce->total,
                        'documenti_contabilita_articoli_iva_id' => '1',
                        'documenti_contabilita_articoli_iva_perc' => 22,
                        'documenti_contabilita_articoli_name' => $articolo_woocommerce->name,
                        'documenti_contabilita_articoli_prezzo' => ($articolo_woocommerce->price / 122) * 100,
                        'documenti_contabilita_articoli_prodotto_id' => $prodotto['fw_products_id'],
                        'documenti_contabilita_articoli_quantita' => $articolo_woocommerce->quantity,
                        'documenti_contabilita_articoli_sconto' => 0,
                        'documenti_contabilita_articoli_unita_misura' => '',
                    ];

                    if ($this->wc_settings['woocommerce_movimenta_ordini_importati'] == DB_BOOL_TRUE) {
                        // converto in array il json dei barcode
                        $barcodes_prodotto = (!empty($prodotto['fw_products_barcode'])) ? json_decode($prodotto['fw_products_barcode'], true) : [];

                        $movimento_articoli[] = [
                            'movimenti_articoli_prodotto_id' => $prodotto['fw_products_id'],
                            'movimenti_articoli_unita_misura' => 1, // forzo u.m. a "pz"
                            'movimenti_articoli_barcode' => $barcodes_prodotto[0] ?? null, // prendo il primo barcode, se c'è, altrimenti null
                            'movimenti_articoli_codice' => $prodotto['fw_products_sku'] ?? null,
                            'movimenti_articoli_name' => $prodotto['fw_products_name'],
                            'movimenti_articoli_quantita' => $articolo_woocommerce->quantity,
                        ];
                    }

                    $articoli_data[] = $riga_articolo;
                }

                foreach ($ordine_woocommerce->shipping_lines as $shipping_line) {
                    $riga_articolo_sped = [
                        'documenti_contabilita_articoli_imponibile' => '0.00',
                        'documenti_contabilita_articoli_importo_totale' => $shipping_line->total,
                        'documenti_contabilita_articoli_iva' => '0',
                        'documenti_contabilita_articoli_iva_id' => '6',
                        'documenti_contabilita_articoli_iva_perc' => '0',
                        'documenti_contabilita_articoli_name' => 'Sped. ' . $shipping_line->method_title,
                        'documenti_contabilita_articoli_prezzo' => $shipping_line->total,
                        'documenti_contabilita_articoli_sconto' => 0,
                        'documenti_contabilita_articoli_quantita' => '1',
                    ];

                    $articoli_data[] = $riga_articolo_sped;
                }

                $ordine['articoli_data'] = $articoli_data;

                $billing = (array)$ordine_woocommerce->billing;
                $shipping = (array)$ordine_woocommerce->shipping;

                if (!empty(array_diff($billing, $shipping))) {
                    if (!empty($shipping['company'])) {
                        $luogo_dest = "{$shipping['company']}\n";
                    } else {
                        $luogo_dest = "{$shipping['first_name']} {$shipping['last_name']}\n";
                    }

                    $luogo_dest .= "{$shipping['address_1']}\n";
                    $luogo_dest .= "{$shipping['city']}, cap: {$shipping['postcode']}\n";
                    if (!empty($shipping['phone'])) {
                        $luogo_dest .= "{$shipping['phone']}";
                    }

                    $ordine['luogo_destinazione'] = $luogo_dest;
                }

//                    $pagamento = 'bonifico';
//
//                    switch ($ordine_woocommerce->payment_method) {
//                        case 'cod':
//                            $pagamento = 'contrassegno';
//                            break;
//                        case 'bacs':
//                            $pagamento = 'bonifico';
//                            break;
//                        case 'cheque':
//                            $pagamento = 'assegno';
//                            break;
//                        case 'stripe':
//                        case 'paypal':
//                            $pagamento = 'carta di credito';
//                            break;
//                        case 'stripe_sepa':
//                            $pagamento = 'SEPA Direct Debit';
//                            break;
//                        default:
//                            debug($ordine_woocommerce->payment_method, true);
//                            break;
//                    }

                $this->load->model('contabilita/docs');

                try {
                    $ordine_id = $this->docs->doc_express_save($ordine);

                    $c++;
                    progress($c, $t, "import ordini pag {$i}");
                } catch (Exception $e) {
                    log_message("error", "ERRORE IMPORT ORDINE {$ordine_woocommerce->id} -> {$e->getMessage()}");
                }

                if ($this->wc_settings['woocommerce_movimenta_ordini_importati'] == DB_BOOL_TRUE && is_numeric($ordine_id)) {
                    // cerco il primo magazzino da usare, ordinando prima quello default
                    $magazzino = $this->apilib->searchFirst('magazzini', [], 0, 'magazzini_default', 'DESC');

                    // se non c'è nessun magazzino, do errore
                    if (empty($magazzino)) return;

                    $movimento = [
                        'movimenti_clienti_id' => $cliente_id, // cliente derivante dall'ordine scaricato
                        'movimenti_documento_id' => $ordine_id, // ordine cliente creato poco fa
                        'movimenti_mittente' => 3, // movimento semplice
                        'movimenti_tipo_movimento' => 2, // movimento di scarico
                        'movimenti_causale' => 18, // vendita merce
                        'movimenti_magazzino' => $magazzino['magazzini_id'], // magazzino
                        'movimenti_data_registrazione' => date('Y-m-d H:i:s'), // data del movimento
                    ];

                    try {
                        $id_movimento_creato = $this->apilib->create('movimenti', $movimento, false);

                        progress(1, 1, "creazione movimento");
                    } catch (Exception $e) {
                        echo_flush("Errore import ordine, creazione movimento: " . $e->getMessage(), '<br/>');
                        log_message("error", "Errore import ordine, creazione movimento: " . $e->getMessage());
                    }

                    if (is_numeric($id_movimento_creato)) {
                        $t_mov_art = count($movimento_articoli);
                        $c_mov_art = 0;
                        foreach ($movimento_articoli as $movimento_articolo) {
                            $riga_movimento_articolo = $movimento_articolo;
                            $riga_movimento_articolo['movimenti_articoli_movimento'] = $id_movimento_creato;

                            try {
                                $this->apilib->create('movimenti_articoli', $riga_movimento_articolo);

                                $c_mov_art++;
                                progress($c_mov_art, $t_mov_art, "creazione righe articoli movimento");
                            } catch (Exception $e) {
                                echo_flush("Errore import ordine, creazione movimento, creazione riga articolo: " . $e->getMessage(), '<br/>');
                                log_message("error", "Errore import ordine, creazione movimento, creazione riga articolo: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
    }

    public function bulk_get_updates() {
        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['ids'])) die('Nessun prodotto selezionato.');

        $ids = json_decode($post['ids'], true);
        $sync_images = $post['sync_images'] ?? 0;

        debug($sync_images, true);

        $fw_products = $this->db
            ->where("(fw_products_woocommerce_external_code IS NOT NULL AND fw_products_woocommerce_external_code <> '')")
            ->where("(fw_products_deleted IS NULL OR fw_products_deleted = '' OR fw_products_deleted = 0)")
            ->where_in('fw_products_id', $ids)
            ->get('fw_products')->result_array();

        if (empty($fw_products)) die("Nessun prodotto trovato.");

        $wc_external_ids = [];
        $wc_external_id_map = [];
        foreach ($fw_products as $fw_product) {
            if (!empty($fw_product['fw_products_parent'])) {
                $fw_product_parent = $this->db
                    ->where("(fw_products_woocommerce_external_code IS NOT NULL AND fw_products_woocommerce_external_code <> '')")
                    ->where("(fw_products_deleted IS NULL OR fw_products_deleted = '' OR fw_products_deleted = 0)")
                    ->where('fw_products_id', $fw_product['fw_products_parent'])
                    ->get('fw_products')->row_array();

                if (empty($fw_product_parent)) {
                    echo_flush("Prodotto padre non trovato, probabilmente stai cercando di sincronizzare un prodotto padre eliminato.", '<br/>');
                    continue;
                }

                $wc_parent_id = $fw_product_parent['fw_products_woocommerce_external_code'];

                $wc_external_ids[] = $wc_parent_id;
                $wc_external_id_map[$wc_parent_id] = $fw_product_parent['fw_products_name'];
            } else {
                $wc_id = $fw_product['fw_products_woocommerce_external_code'];
                $wc_external_ids[] = $wc_id;
                $wc_external_id_map[$wc_id] = $fw_product['fw_products_name'];
            }
        }

        $wc_external_ids = array_filter(array_unique($wc_external_ids));

        $totals = count($wc_external_ids);
        $elaborated = 0;
        foreach ($wc_external_ids as $wc_external_id) {
            $elaborated++;

            try {
                $product = $this->wc->get('products/' . $wc_external_id);
            } catch (Exception $e) {
                echo_flush("Prodotto <b>{$wc_external_id_map[$wc_external_id]} (#{$wc_external_id})</b> non trovato su woocommerce", '<br/>');
                progress($elaborated, $totals);
                continue;
            }

            $this->importSingle($product, $sync_images);

            progress($elaborated, $totals);
        }
    }

    public function importproducts($page_max = 3, $page = 1, $per_page = 20)
    {
        echo_flush("starting import products", "<br/>");
        $this->load->model('products_manager/woocomm');

        if ($page == null) $page = 1;
        if ($page_max == null) $page_max = 100;

        $product_id = $this->input->get('product_id') ?? null;

        if ($product_id && is_numeric($product_id)) {
            $product = $this->wc->get("products/{$product_id}");

            $this->importSingle($product);
        } else {
            for ($i = $page; $i <= $page_max; $i++) {
                echo_flush("<b>starting page $i of $page_max</b>", '<br/>');
                $products = $this->wc->get('products', ['per_page' => $per_page, 'page' => $i]);

                if (empty($products)) {
                    echo_flush("<b>reached max page $i with products</b>", "<br/>");
                    echo_flush("<b>done page $i of $page_max</b>", "<br/>");
                    break;
                }

                foreach ($products as $product) {
                    $this->importSingle($product);
                    sleep(1);
                }

                echo_flush("<b>done page $page of $page_max</b>", "<br/>");
                usleep(500000);
            }
        }
    }

    private function importSingle($product, $sync_images = 0)
    {
        echo_flush("starting import for $product->id", "<br/>");
        $inserted_product = $this->woocomm->import_product($product);

        if (!empty($inserted_product)) {
            $this->woocomm->import_images($product, $inserted_product, $sync_images);

            $this->woocomm->import_categories($product, $inserted_product);

            $attributes = $this->woocomm->import_attributes($product);

            $json_attributes = json_encode($attributes);

            try {
                $this->apilib->edit('fw_products', $inserted_product['fw_products_id'], ['fw_products_json_attributes' => $json_attributes]);
                echo_flush("imported attributes for $product->id", "<br/>");
            } catch (Exception $e) {
                echo_flush("error import attributes for $product->id - {$e->getMessage()}", "<br/>");
            }

            if (!empty($product->variations) && is_array($product->variations)) {
                echo_flush("starting import variants for $product->id", "<br/>");
                $editprod = $this->apilib->edit('fw_products', $inserted_product['fw_products_id'], ['fw_products_type' => 2]);

                foreach ($product->variations as $variant_id) {
                    $variant = $this->wc->get("products/{$variant_id}");

                    $inserted_variant = $this->woocomm->import_product($variant, $editprod['fw_products_id']);

                    $this->woocomm->import_images($variant, $inserted_variant, $sync_images);

                    $this->woocomm->import_categories($product, $inserted_variant);

                    $variant_attributes = $this->woocomm->import_attributes($variant, true);

                    $json_variant_attributes = json_encode($variant_attributes);

                    try {
                        $this->apilib->edit('fw_products', $inserted_variant['fw_products_id'], ['fw_products_json_attributes' => $json_variant_attributes]);
                        echo_flush("imported variant {$variant_id} done for $product->id", "<br/>");
                    } catch (Exception $e) {
                        echo_flush("error import variant {$variant_id} done for $product->id - {$e->getMessage()}", "<br/>");
                    }
                }
                echo_flush("import variants done for $product->id", "<br/>");
            }
        }
        echo_flush("import done for $product->id", "<br/>");
    }

    public function importbrands()
    {
        die('not allowed');
        echo date('Y-m-d H:i:s') . " Starting importing brands\n\n";

        for ($i = 1; $i <= 1000; $i++) {
            echo date('Y-m-d H:i:s') . " Estrazione prodotti da woocomm - pag {$i}\n\n";

            $products = $this->woocommerce->get('products', ['per_page' => 100, 'page' => $i]);

            if (!empty($products)) {
                echo date('Y-m-d H:i:s') . " Starting products cycling\n";

                foreach ($products as $product) {
                    try {
                        $crm_product = $this->db->get_where('fw_products', ['fw_products_woocommerce_external_code' => $product->id])->row_array();

                        $brand_id = null;
                        foreach ($product->attributes as $attr) {
                            if ($attr->name == 'Brand') {

                                $crm_brand = $this->db->get_where('fw_products_brand', ['fw_products_brand_value' => $attr->options[0]])->row_array();

                                if (empty($crm_brand)) {
                                    $created_brand = $this->apilib->create('fw_products_brand', [
                                        'fw_products_brand_value' => $attr->options[0],
                                    ]);

                                    $brand_id = $created_brand['fw_products_brand_id'];
                                    echo date('Y-m-d H:i:s') . ' {+} ' . $attr->options[0] . "\n";
                                } else {
                                    $brand_id = $crm_brand['fw_products_brand_id'];
                                    echo date('Y-m-d H:i:s') . ' {*} ' . $attr->options[0] . "\n";
                                }
                                break;
                            }
                        }

                        if (!empty($crm_product)) {
                            if (!empty($brand_id)) {
                                $this->db
                                    ->where(['fw_products_woocommerce_external_code' => $product->id])
                                    ->update('fw_products', ['fw_products_brand' => $brand_id]);

                                echo date('Y-m-d H:i:s') . " [#] {$product->name} [{$product->id}]\n";
                            } else {
                                echo date('Y-m-d H:i:s') . " [!!] empty brand\n";
                            }
                        }

                        unset($brand_id);
                    } catch (Exception $e) {
                        echo date('Y-m-d H:i:s') . " [!!] Error {$e->getMessage()}\n";
                    }
                }
            } else {
                echo date('Y-m-d H:i:s') . " This was the last page\n";
                break;
            }
        }

        echo date('Y-m-d H:i:s') . " Finished import\n";
    }
}
