<?php

class Movimenti extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('magazzino/mappature');
        $this->load->model('magazzino/mov');
        $this->load->model('contabilita/docs');
        $this->magazzino_settings = $this->apilib->searchFirst('magazzino_settings');
    }

    /**
     * @param int $verifica_qta  0|1, default 0 - Imposta un filtro per cui prende solo i prodotti con quantità > 0
     * @param int $usa_apilib  0|1, default 0 - Usa o meno APILIB per creare il record su "movimenti" e "movimenti_articoli", utile se si vuole includere o meno gli automatismi dei db-events
     * @return void
     */
    public function genera_movimento_massivo_di_carico($verifica_qta = 0, $usa_apilib = 0) {
        // verifico che solo i superadmin possano utilizzare questo metodo
        if (!$this->auth->is_admin()) die("Non autorizzato");

        // cerco il primo magazzino da usare, ordinando prima quello default
        $magazzino = $this->apilib->searchFirst('magazzini', [], 0, 'magazzini_default', 'DESC');

        // se non c'è nessun magazzino, do errore
        if (empty($magazzino)) die('ERRORE: Nessun magazzino trovato.');

        // ottengo anche già i prodotti
        $where_prodotti = [];
        if ($verifica_qta) {
            $where_prodotti[] = "(fw_products_quantity IS NOT NULL AND fw_products_quantity <> '' AND fw_products_quantity > 0)"; // ...che hanno una quantità maggiore di 0
        }
        $prodotti = $this->apilib->search('fw_products', $where_prodotti);

        // e verifico che ce ne siano, altrimenti errore
        if (empty($prodotti)) die("ERRORE: Nessun prodotto trovato");

        // costruisco l'array con le informazioni che mi servono
        $movimento = [
            'movimenti_mittente' => 3, // movimento semplice
            'movimenti_tipo_movimento' => 1, // movimento di carico
            'movimenti_causale' => 26, // inventario (carico di base)
            'movimenti_magazzino' => $magazzino['magazzini_id'], // magazzino
            'movimenti_data_registrazione' => date('Y-m-d H:i:s'), // data del movimento
        ];

        try {
            // faccio l'inserimento del movimento, a scelta (in base al flag in entrata) tra apilib (scatenando quindi poi i vari db-events) o $this->db, più veloce ma senza automatismi.
            // in entrambi i casi, mi assegno una variabile $id_movimento_creato contenente appunto l'id del movimento che ho appena creato, da usare successivamente all'inserimento delle righe articolo
            if ($usa_apilib == 1) {
                $id_movimento_creato = $this->apilib->create('movimenti', $movimento, false);
            } else {
                $movimento['movimenti_creation_date'] = date('Y-m-d H:i:s');

                $this->db->insert('movimenti', $movimento);
                $id_movimento_creato = $this->db->insert_id();
            }
        } catch (Exception $e) {
            die("ERRORE: Si è verificato un errore di database - " . $e->getMessage());
        }

        $total = count($prodotti);
        $elaborated = 0;
        foreach ($prodotti as $prodotto) {
            // converto in array il json dei barcode
            $barcodes_prodotto = (!empty($prodotto['fw_products_barcode'])) ? json_decode($prodotto['fw_products_barcode'], true) : [];

            // costruisco l'array che andrà in movimenti_articoli
            $movimento_articolo = [
                'movimenti_articoli_movimento' => $id_movimento_creato,
                'movimenti_articoli_prodotto_id' => $prodotto['fw_products_id'],
                'movimenti_articoli_unita_misura' => 1, // forzo u.m. a "pz"
                'movimenti_articoli_barcode' => $barcodes_prodotto[0] ?? null, // prendo il primo barcode, se c'è, altrimenti null
                'movimenti_articoli_codice' => $prodotto['fw_products_sku'] ?? null,
                'movimenti_articoli_name' => $prodotto['fw_products_name'],
                'movimenti_articoli_quantita' => ($verifica_qta == 0 && !is_numeric($prodotto['fw_products_quantity'])) ? 0 : $prodotto['fw_products_quantity'],
            ];

            try {
                // faccio l'inserimento della riga articolo del movimento, a scelta (in base al flag in entrata) tra apilib (scatenando quindi poi i vari db-events) o $this->db, più veloce ma senza automatismi.
                if ($usa_apilib == 1) {
                    $this->apilib->create('movimenti_articoli', $movimento_articolo, false);
                } else {
                    $movimento_articolo['movimenti_articoli_creation_date'] = date('Y-m-d H:i:s');

                    $this->db->insert('movimenti_articoli', $movimento_articolo);
                }
            } catch (Exception $e) {
                echo_flush("ERRORE: Si è verificato un errore di database - " . $e->getMessage(), '<br/>');
            }

            $elaborated++;
            progress($elaborated, $total, "creazione righe articolo");
        }
    }

    public function getProdottoByCode($codice = null)
    {
        // 2024-04-29 - michael - supporto a codice in post, cambiato per supportare anche codici che hanno spazi o caratteri che in get fallirebbe
        if (empty($codice)) {
            if (!empty($this->input->post('codice'))) {
                $codice = $this->input->post('codice');
            } else {
                e_json(['prodotto' => false]);
                return;
            }
        }
        $prodotto = $this->apilib->searchFirst('fw_products', ['fw_products_sku' => trim($codice)]);
        if (!$prodotto) {
            $prodotto = false;
        }
        echo json_encode(['prodotto' => $prodotto]);
    }

    public function searchBarcode()
    {
        $barcode = trim($this->input->post('barcode'));

        if (empty($barcode)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun barcode specificato']));
        }

        try {
            $mappature = $this->mappature->getMappature();
            extract($mappature);

            //$products = $this->apilib->search($entita_prodotti, [$campo_barcode_prodotto => $barcode, $campo_gestione_giacenza_prodotto => DB_BOOL_TRUE, $campo_prodotto_eliminato => DB_BOOL_FALSE]);
            $products = $this->apilib->search($entita_prodotti, ["{$campo_barcode_prodotto} LIKE '%{$barcode}%'", $campo_gestione_giacenza_prodotto => DB_BOOL_TRUE, $campo_prodotto_eliminato => DB_BOOL_FALSE]);

            if (empty($products)) {
                die(json_encode(['status' => 0, 'data' => 'Nessun prodotto trovato con il barcode specificato']));
            }

            if (count($products) == 1) {
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }

            if (count($products) > 1) {
                // ho aggiunto questa riga per mostrare comunque il primo prodotto, prima era vuoto.
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'data' => 'Si è verificato un errore tecnico']));
        }
    }

    public function searchBarcodeFiltered()
    {
        $magazzino = $this->input->post('magazzino');
        $barcode = trim($this->input->post('barcode'));

        if (empty($barcode)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun barcode specificato']));
        }

        if (empty($magazzino)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun magazzino specificato']));
        }

        try {
            $mappature = $this->mappature->getMappature();
            extract($mappature);

            $where = [
                "{$campo_barcode_prodotto} LIKE '%{$barcode}%'",
                $campo_gestione_giacenza_prodotto => DB_BOOL_TRUE,
                $campo_prodotto_eliminato => DB_BOOL_FALSE,
//"fw_products_id IN (SELECT movimenti_articoli_prodotto_id FROM movimenti_articoli LEFT JOIN movimenti ON movimenti_id = movimenti_articoli_movimento WHERE movimenti.movimenti_magazzino = '{$magazzino}')"

            ];

            $products = $this->apilib->search($entita_prodotti, $where);

            if (empty($products)) {
                die(json_encode(['status' => 0, 'data' => 'Nessun prodotto trovato con il barcode specificato']));
            }

            if (count($products) == 1) {
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }

            if (count($products) > 1) {
                // ho aggiunto questa riga per mostrare comunque il primo prodotto, prima era vuoto.
                die(json_encode(['status' => 1, 'data' => $products[0]]));
            }
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'data' => 'Si è verificato un errore tecnico']));
        }
    }

    public function insertProduct()
    {
        $product = json_decode($this->input->post('product'), true);
        $magazzino = $this->input->post('magazzino');

        if (empty($product)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun prodotto specificato']));
        }
        if (empty($magazzino)) {
            die(json_encode(['status' => 0, 'data' => 'Nessun magazzino specificato']));
        }

        try {
            $barcode = (array) (json_decode($product['fw_products_barcode']));
            $barcode = $barcode[0];

            //debug('TODO: Se ci sono movimenti di inventario (25,26 o 27), eliminare quella riga da movimenti_articoli. Se il movimento rimane privo di articoli, eliminare anche il movimento stesso in modo da ottimizzare il numero di movimenti...', true);
            // $movimenti_precedenti = $this->apilib->search('movimenti_articoli', [
            //     'movimenti_articoli_prodotto_id' => $product['fw_products_id'],
            //     'movimenti_causale' => [25, 26, 27],
            //     'movimenti_magazzino' => $magazzino,
            //     'DATEDIFF(NOW(), movimenti_creation_date) < 30', //COnsidero solo inventari fatti nell'ultimo mese. Oltre questa data i movimenti rimangono bloccati
            // ]);
            // //debug($movimenti_precedenti,true);
            // foreach ($movimenti_precedenti as $movimento_precedente) {
            //     $this->apilib->delete('movimenti_articoli', $movimento_precedente['movimenti_articoli_id']);

            //     //Se il movimento rimane orfano (senza prodotti), cancello direttamente il movimento.
            //     if ($this->apilib->count('movimenti_articoli', ['movimenti_articoli_movimento' => $movimento_precedente['movimenti_articoli_movimento']]) == 0) {
            //         $this->apilib->delete('movimenti', $movimento_precedente['movimenti_articoli_movimento']);
            //     }
            // }

            $quantity = $this->mov->calcolaGiacenzaAttuale($product, $magazzino);
            $exists = $this->db->get_where('prodotti_inventario', [
                'prodotti_inventario_prodotto_id' => $product['fw_products_id'],
                //'prodotti_inventario_movimento' => null,
            ]);
            if ($riga = $exists->row_array()) {

                $this->apilib->edit('prodotti_inventario', $riga['prodotti_inventario_id'], [
                    'prodotti_inventario_qta_sparata' => $riga['prodotti_inventario_qta_sparata'] + 1,
                    'prodotti_inventario_qta_attesa' => $quantity,
                    'prodotti_inventario_confermato' => DB_BOOL_FALSE,
                ]);
            } else {

                $this->apilib->create('prodotti_inventario', [
                    'prodotti_inventario_barcode' => $barcode,
                    'prodotti_inventario_nome' => $product['fw_products_name'],
                    'prodotti_inventario_prezzo' => $product['fw_products_sell_price'],
                    'prodotti_inventario_qta_attesa' => $quantity,
                    'prodotti_inventario_qta_sparata' => 1,
                    'prodotti_inventario_prodotto_id' => $product['fw_products_id'],
                    'prodotti_inventario_magazzino' => $magazzino,
                    'prodotti_inventario_confermato' => DB_BOOL_FALSE,
                ]);
            }

            //$this->mycache->clearCache();

            die(json_encode(['status' => 0, 'data' => 'Prodotti inseriti correttamente in inventario']));
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'data' => 'Si è verificato un errore tecnico']));
        }
    }
    public function azzera_quantita()
    {
        $filters = $this->session->userdata(SESS_WHERE_DATA);

        // Costruisco uno specchietto di filtri autogenerati leggibile
        $where = array();

        if (!empty($filters["filtro_inventario_azzeramento_quantita"])) {
            foreach ($filters["filtro_inventario_azzeramento_quantita"] as $field) {
                if ($field['value'] == '-1') {
                    continue;
                }
                $filter_field = $this->datab->get_field($field["field_id"], true);
                $field_name = $filter_field['fields_name'];
                switch ($field_name) {
                    case 'movimenti_magazzino':
                        $magazzino = $field['value'];
                        break;
                    case 'fw_products_brand':
                        if ($field['value']) {
                            $where[] = "fw_products_brand = '{$field['value']}'";
                        }

                        break;
                    case 'fw_products_categories':
                        if ($field['value']) {
                            $where[] = "fw_products_id IN (SELECT fw_products_id FROM fw_products_fw_categories  WHERE fw_categories_id IN (" . implode(',', $field['value']) . "))";
                        }

                        break;
                    default:
                        debug("Filtro {$field_name} non riconosciuto!");
                        break;
                }
            }

            if ($magazzino) {
                $where[] = "fw_products_id NOT IN (SELECT prodotti_inventario_prodotto_id FROM prodotti_inventario WHERE prodotti_inventario_magazzino = '$magazzino')";
                $where_str = implode(' AND ', $where);
                $prodotti_da_azzerare = $this->db->where($where_str, null, false)->get('fw_products')->result_array();

                //debug($prodotti_da_azzerare, true);
                $this->azzera_quantita_prodotti($prodotti_da_azzerare, $magazzino);
                redirect(base_url('main/layout/inventario'));

            } else {
                die("Errore: filtro magazzino non impostato!");

            }

        } else {
            die("Errore: filtro non impostato!");
        }

    }

    private function azzera_lotti_matricole($prodotto_sparato, $magazzino, $exclude_movimento_id = null) {
        if ($exclude_movimento_id == null) {
            $exclude_movimento_id = -1;
        }
        $articoli_da_azzerare = $this->db->query("SELECT * FROM movimenti_articoli WHERE movimenti_articoli_lotto IS NOT NULL AND movimenti_articoli_lotto <> '' AND movimenti_articoli_prodotto_id = '{$prodotto_sparato['fw_products_id']}' AND movimenti_articoli_movimento IN (SELECT movimenti_id FROM movimenti WHERE movimenti_magazzino = '{$magazzino}' AND movimenti_id != '{$exclude_movimento_id}')")->result_array();
        // debug($articoli_da_azzerare);
        // debug($this->db->last_query(),true);
        foreach ($articoli_da_azzerare as $articolo_da_azzerare) {
            
            $this->apilib->edit('movimenti_articoli', $articolo_da_azzerare['movimenti_articoli_id'], ['movimenti_articoli_quantita' => 0]);
            
        }
        
        //$this->db->query("DELETE FROM movimenti WHERE movimenti_id NOT IN (SELECT movimenti_articoli_movimento FROM movimenti_articoli WHERE movimenti_articoli_movimento IS NOT NULL)");
    }
    private function azzera_quantita_prodotti($prodotti, $magazzino, $exclude_movimento_id = null)
    {
        //Creo due movimenti in tutto...
        $prodotti_scarico = $prodotti_carico = [];
        
        foreach ($prodotti as $prodotto_sparato) {
            //Prima di tutto azzero eventuali prodotti con matricola
            $this->azzera_lotti_matricole($prodotto_sparato, $magazzino, $exclude_movimento_id = null);

            //Poi calcolo la quantità rimanente
            $quantity = $this->mov->calcolaGiacenzaAttuale($prodotto_sparato, $magazzino, $exclude_movimento_id);
            $prodotto_sparato['fw_products_barcode'] = json_decode($prodotto_sparato['fw_products_barcode']);
            if (is_array($prodotto_sparato['fw_products_barcode'])) {
                $prodotto_sparato['fw_products_barcode'] = $prodotto_sparato['fw_products_barcode'][0];
            }

            $prodotto = [
                'movimenti_articoli_prodotto_id' => $prodotto_sparato['fw_products_id'],
                'movimenti_articoli_prezzo' => $prodotto_sparato['fw_products_sell_price'],
                'movimenti_articoli_descrizione' => $prodotto_sparato['fw_products_description'],
                'movimenti_articoli_name' => $prodotto_sparato['fw_products_name'],
                'movimenti_articoli_codice' => $prodotto_sparato['fw_products_sku'],
                'movimenti_articoli_iva_id' => $prodotto_sparato['fw_products_tax'],
                'movimenti_articoli_unita_misura' => $prodotto_sparato['fw_products_unita_misura'],
                'movimenti_articoli_codice_fornitore' => $prodotto_sparato['fw_products_provider_code'],
                'movimenti_articoli_quantita' => abs($quantity),
                'movimenti_articoli_importo_totale' => abs($quantity * $prodotto_sparato['fw_products_sell_price']),
                'movimenti_articoli_barcode' => ($prodotto_sparato['fw_products_barcode']) ?? null,
            ];

            if ($quantity > 0) {

                $prodotti_scarico[] = $prodotto;
            }
            if ($quantity < 0) {

                $prodotti_carico[] = $prodotto;
            }

        }

        if ($prodotti_scarico) {
            $this->mov->creaMovimento(
                [
                    'movimenti_magazzino' => $magazzino,
                    'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                    'movimenti_articoli' => $prodotti_scarico,
                    'movimenti_tipo_movimento' => 2, //Scarico
                    'movimenti_causale' => 25, //    Inventario (scarico base)
                ]
            );
        }

        if ($prodotti_carico) {
            $this->mov->creaMovimento(
                [
                    'movimenti_magazzino' => $magazzino,
                    'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                    'movimenti_articoli' => $prodotti_carico,
                    'movimenti_tipo_movimento' => 1, //Carico
                    'movimenti_causale' => 26, //    Inventario (carico base)
                ]
            );
        }

    }
    public function salva_inventario($magazzino)
    {
        $prodotti_inventario = $this->apilib->search('prodotti_inventario', [
            'prodotti_inventario_magazzino' => $magazzino,
            'prodotti_inventario_confermato IS NULL OR prodotti_inventario_confermato = 0',
        ]);
        foreach ($prodotti_inventario as $key => $prodotto_sparato) {
            $prodotti_inventario[$key]['fw_products_id'] = $prodotto_sparato['prodotti_inventario_prodotto_id'];

        }
        $this->azzera_quantita_prodotti($prodotti_inventario, $magazzino);

        //Una volta portate a 0 le quantità creo un movimento di carico inventario per allineare le quantità
        $giacenze_finali = [];
        foreach ($prodotti_inventario as $prodotto_sparato) {
            $prodotto_sparato['fw_products_id'] = $prodotto_sparato['prodotti_inventario_prodotto_id'];
            $quantity = $this->mov->calcolaGiacenzaAttuale($prodotto_sparato, $magazzino);

            $prodotto = [
                'movimenti_articoli_prodotto_id' => $prodotto_sparato['prodotti_inventario_prodotto_id'],
                'movimenti_articoli_prezzo' => $prodotto_sparato['fw_products_sell_price'],
                'movimenti_articoli_descrizione' => $prodotto_sparato['fw_products_description'],
                'movimenti_articoli_name' => $prodotto_sparato['fw_products_name'],
                'movimenti_articoli_codice' => $prodotto_sparato['fw_products_sku'],
                'movimenti_articoli_iva_id' => $prodotto_sparato['fw_products_tax'],
                'movimenti_articoli_unita_misura' => $prodotto_sparato['fw_products_unita_misura'],
                'movimenti_articoli_codice_fornitore' => $prodotto_sparato['fw_products_provider_code'],
                'movimenti_articoli_quantita' => $prodotto_sparato['prodotti_inventario_qta_sparata'],
                'movimenti_articoli_importo_totale' => abs($quantity * $prodotto_sparato['fw_products_sell_price']),
                'movimenti_articoli_barcode' => (!empty($prodotto_sparato['fw_products_barcode'])) ? json_decode($prodotto_sparato['fw_products_barcode'])[0] : null,
            ];

            $giacenze_finali[] = $prodotto;

            $this->apilib->edit('prodotti_inventario', $prodotto_sparato['prodotti_inventario_id'], [
                'prodotti_inventario_confermato' => DB_BOOL_TRUE,
            ]);

        }
        $this->mov->creaMovimento(
            [
                'movimenti_magazzino' => $magazzino,
                'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                'movimenti_articoli' => $giacenze_finali,
                'movimenti_tipo_movimento' => 1, //Carico
                'movimenti_causale' => 27, //    Inventario (carico finale)
            ]
        );

        redirect(base_url('main/layout/inventario'));
    }

    public function nuovo_movimento()
    {
        $input = $this->input->post();
        //TODO: FORZATURA DI TEST! Togliere...
        //$input['movimenti_causale'] = 21;
        $this->load->library('form_validation');

        $this->form_validation->set_rules('movimenti_magazzino', 'Magazzino', 'required');
        $this->form_validation->set_rules('movimenti_data_registrazione', 'Data movimento', 'required');
        $this->form_validation->set_rules('movimenti_causale', 'Causale', 'required');
        //$this->form_validation->set_rules('movimenti_documento_tipo', 'Tipo documento', 'required');
        $this->form_validation->set_rules('movimenti_mittente', 'Mittente', 'required');
        $causali_inventario = [21]; //Giacenze iniziali
        //Barbatrucco matteo: non è detto che sia 1 nel caso di riga eliminata (può partire da 2, da 3 o altro...)
        $chiave = 1;
        if (!empty($input['products'])) {
            foreach (@$input['products'] as $key => $p) {
                $this->form_validation->set_rules('products[' . $key . '][movimenti_articoli_name]', t('product name'), 'required');
                if (!in_array($input['movimenti_causale'], $causali_inventario)) { //In caso di inventario devo permettere di sparare a 0 il prodotto
                    $this->form_validation->set_rules('products[' . $key . '][movimenti_articoli_quantita]', t('product quantity'), 'required|integer|greater_than[0]');
                }
                break;
            }
        } else {
            $input['products'] = [];
        }

        if ($input['movimenti_mittente'] != 3 && $input['movimenti_mittente'] != 4) {
            $this->form_validation->set_rules('ragione_sociale', 'ragione sociale', 'required');
            // $this->form_validation->set_rules('indirizzo', 'indirizzo', 'required');
            // $this->form_validation->set_rules('citta', 'città', 'required');
            // $this->form_validation->set_rules('nazione', 'nazione', 'required');
            // $this->form_validation->set_rules('provincia', 'provincia', 'required');
            // $this->form_validation->set_rules('codice_fiscale', 'codice fiscale', 'required');
            // $this->form_validation->set_rules('cap', 'CAP', 'required');
        } elseif ($input['movimenti_mittente'] != 4) {
            $this->form_validation->set_rules('movimenti_tipo_movimento', 'Tipo movimento', 'required');
        }
        
        if ($this->form_validation->run() == false) {
            echo json_encode(array(
                'status' => 0,
                'txt' => validation_errors(),
                'data' => '',
            ));
        } else {
            $magazzino = $this->apilib->view('magazzini', $input['movimenti_magazzino']);
            
            if (!empty($magazzino['magazzini_utenti_abilitati']) && !empty($this->auth->get('users_id'))) {
                $utente_corrente = $this->auth->get('users_id');
                
                $utenti_abilitati = array_keys($magazzino['magazzini_utenti_abilitati']);
                if (!in_array($utente_corrente, $utenti_abilitati)) {
                    e_json([
                        'status' => 0,
                        'txt' => 'Non sei abilitato a fare movimenti in questo magazzino',
                    ]);
                    return;
                }
            }
            
            $mappature = $this->mappature->getMappature();
            extract($mappature);

            $dest_entity_name = $input['dest_entity_name'];

            // **************** DESTINATARIO ****************** //

            $dest_fields = array("ragione_sociale", "indirizzo", "citta", "provincia", "nazione", "codice_fiscale", "partita_iva", 'cap');
            foreach ($input as $key => $value) {
                if (in_array($key, $dest_fields)) {
                    $destinatario_json[$key] = $value;
                    $destinatario_entity[$dest_entity_name . "_" . $key] = $value;
                }
            }
            // Serialize
            $movimento['movimenti_destinatario'] = json_encode($destinatario_json);

            // Se già censito lo collego altrimenti lo salvo se richiesto
            if ($input['dest_id']) {
                if ($dest_entity_name == 'customers') {
                    $movimento['movimenti_clienti_id'] = $input['dest_id'];
                } else {
                    $movimento['movimenti_fornitori_id'] = $input['dest_id'];
                }

                //Se ho comunque richiesto la sovrascrittura dei dati
                if (isset($input['save_dest']) && $input['save_dest'] == "true") {
                    $this->apilib->edit($dest_entity_name, $input['dest_id'], $destinatario_entity);
                }
            } elseif (isset($input['save_dest']) && $input['save_dest'] == "true") {
                $dest_id = $this->apilib->create($dest_entity_name, $destinatario_entity, false);
                if ($dest_entity_name == 'customers') {
                    $movimento['movimenti_clienti_id'] = $dest_id;
                } else {
                    $movimento['movimenti_fornitori_id'] = $dest_id;
                }
            }

            // **************** DOCUMENTO ****************** //

            $movimento['movimenti_magazzino'] = $input['movimenti_magazzino'];
            $movimento['movimenti_causale'] = $input['movimenti_causale'];
            $movimento['movimenti_tipo_movimento'] = $input['movimenti_tipo_movimento'];
            $movimento['movimenti_documento_tipo'] = $input['movimenti_documento_tipo'];
            $movimento['movimenti_mittente'] = $input['movimenti_mittente'];
            $movimento['movimenti_data_registrazione'] = $input['movimenti_data_registrazione'];
            $movimento['movimenti_numero_documento'] = $input['movimenti_numero_documento'];
            $movimento['movimenti_documento_id'] = $input['movimenti_documento_id'] ?? null;
            $movimento['movimenti_spesa_id'] = $input['movimenti_spesa_id'] ?? null;

            $movimento['movimenti_data_documento'] = $input['movimenti_data_documento'];
            $movimento['movimenti_totale'] = $input['movimenti_totale'];

            if ($this->magazzino_settings['magazzino_settings_show_scaffale'] == 1) {
                $movimento['movimenti_scaffale'] = $input['movimenti_scaffale'] ?? null;
                $movimento['movimenti_scaffale_ricevente'] = $input['movimenti_scaffale_ricevente'] ?? null;
            }
            if ($this->magazzino_settings['magazzino_settings_show_ripiano'] == 1) {
                $movimento['movimenti_ripiano'] = $input['movimenti_ripiano'] ?? null;
                $movimento['movimenti_ripiano_ricevente'] = $input['movimenti_ripiano_ricevente'] ?? null;
            }


            // se è uno giro magazzino, lo setto prima come scarico, poi come carico
            if($input['movimenti_mittente'] == 4 && empty($input['movimenti_id'])){
                $movimento['movimenti_tipo_movimento'] = 2;
            }

            if (!empty($input['movimenti_ordine_produzione_id'])) {
                $movimento['movimenti_ordine_produzione_id'] = $input['movimenti_ordine_produzione_id'];
            }

            $movimento['movimenti_user'] = $this->auth->get('id');

            if (in_array($movimento['movimenti_causale'], $causali_inventario)) {
                //debug($input['products'],true);
                $ids = array_key_map($input['products'], 'movimenti_articoli_prodotto_id');

                $fw_products = $this->apilib->search('fw_products', ['fw_products_id' => $ids]);
                //debug($fw_products,true);
                $this->azzera_quantita_prodotti($fw_products, $movimento['movimenti_magazzino'], $input['movimenti_id']);
            }

            if (!empty($input['movimenti_id'])) {

                $movimenti_id = $input['movimenti_id'];
                //controllo che non sia stato cambiato da giro magazzino ad altro
                $movimento_attuale = $this->apilib->searchFirst('movimenti', ['movimenti_id' => $movimenti_id]);
                if($movimento_attuale['movimenti_mittente'] == 4 AND $input['movimenti_mittente'] != 4){
                    //@TODO gestire questo caso
                    //echo "ho modificato da giro magazzino ad altro!";
                    throw new ApiException('Non puoi modificare da giro magazzino ad altro movimento, elimina il giro magazzino e ricrea il movimento.');
                    exit;
                    //TODO da gestire
                }

                //se è un giro magazzino, prendo la tipologia altrimenti selezionava sempre 1
                if($input['movimenti_mittente'] == 4){

                    $movimento['movimenti_tipo_movimento'] = $movimento_attuale['movimenti_tipo_movimento'];
                    if($movimento['movimenti_tipo_movimento'] == 1){
                        //questo movimento è un carico, devo cercare lo scarico
                        //quindi il carico è su quello che ho nella riga
                        $scarico = $this->apilib->searchFirst('movimenti', ['movimenti_id' => $movimento_attuale['movimenti_giro_magazzino']]);
                        if(!empty($scarico)){
                            $movimenti_id_scarico = $scarico['movimenti_id'];
                            $this->apilib->edit('movimenti', $movimenti_id_scarico, ['movimenti_magazzino' => $input['movimenti_magazzino']]);
                            $movimento['movimenti_magazzino'] = $input['movimenti_magazzino_ricevente'];
                        }
                    } else{
                        //qua ci sono gli scarichi
                        //quindi scarico deve essere quello che ho nella riga attuale
                        $carico = $this->apilib->searchFirst('movimenti', ['movimenti_giro_magazzino' => $movimenti_id]);
                        if(!empty($carico)){
                            $movimenti_id_carico = $carico['movimenti_id'];
                            /*echo "Al movimento : ".$movimenti_id_carico. " associo: ".$input['movimenti_magazzino_ricevente']."<br>";*/
                            $this->apilib->edit('movimenti', $movimenti_id_carico, ['movimenti_magazzino' => $input['movimenti_magazzino_ricevente']]);
                        }
                    }
                }
                /*
                echo "Al movimento : ".$movimenti_id. " associo: ".$movimento['movimenti_magazzino'];

                debug($movimento,true);*/

                $this->apilib->edit('movimenti', $movimenti_id, $movimento);
                
            } else {
                //Se è un movimento di carico iniziale, azzero prima le quantità per questi prodotti
                //debug($movimento['movimenti_causale'],true);
                
                $movimenti_id = $this->apilib->create('movimenti', $movimento, false);
                //se è uno spostamento di magazzino, devo aggiungere, oltre allo scarico, anche il carico.
                if($input['movimenti_mittente'] == 4){
                    $movimento['movimenti_tipo_movimento'] = 1;
                    $movimento['movimenti_magazzino'] = $input['movimenti_magazzino_ricevente'];
                    $movimento['movimenti_giro_magazzino'] = $movimenti_id;
                    $movimenti_id_carico = $this->apilib->create('movimenti', $movimento, false);
                }
            }

            // **************** PRODOTTI ****************** //
            if (!empty($input['movimenti_id'])) {
                //Devo usare le apilib per scatenare il pp!!!
                //debug($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $input['movimenti_id']]),true);
                foreach ($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $input['movimenti_id']]) as $movimento_articolo) {
                    $this->apilib->delete('movimenti_articoli', $movimento_articolo['movimenti_articoli_id']);
                }
                //cancello gli articoli di scarico o carico, se esistono
                if(isset($movimenti_id_carico)){
                    foreach ($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $movimenti_id_carico]) as $movimento_articolo) {
                        $this->apilib->delete('movimenti_articoli', $movimento_articolo['movimenti_articoli_id']);
                    }
                }
                elseif(isset($movimenti_id_scarico)){
                    foreach ($this->apilib->search('movimenti_articoli', ['movimenti_articoli_movimento' => $movimenti_id_scarico]) as $movimento_articolo) {
                        $this->apilib->delete('movimenti_articoli', $movimento_articolo['movimenti_articoli_id']);
                    }
                }
            }
            
            $tmp_prodotti = [];
            foreach ($input['products'] as $prodotto) {
                if ($prodotto['movimenti_articoli_name']) { //Almeno il name ci deve essere, altrimenti devo considerarla come riga vuota.
                    $prodotto['movimenti_articoli_movimento'] = $movimenti_id;
                    if ($prodotto['movimenti_articoli_prodotto_id']) { //} && $prodotto_esistente = $this->apilib->view($entita_prodotti, $prodotto['movimenti_articoli_prodotto_id'])) {
                        //$prodotto['movimenti_articoli_prodotto_id'] = $prodotto_esistente[$campo_id_prodotto];
                    } else {
                        $prodotto['movimenti_articoli_name'] = trim($prodotto['movimenti_articoli_name']);
                        
                        if (empty($tmp_prodotti[$prodotto['movimenti_articoli_name']])) {
                            //debug($input['products'], true);
                            //TODO: se ho impostato un fornitore, metterlo nei supplier
                            $nuovo_prodotto = [
                                $campo_barcode_prodotto => $prodotto['movimenti_articoli_barcode'],
                                $campo_codice_prodotto => $prodotto['movimenti_articoli_codice'],
                        
                                $campo_descrizione_prodotto => $prodotto['movimenti_articoli_descrizione'],
                                //'prodotti_unita_di_misura' => $prodotto['movimenti_articoli_unita_misura'],
                                $campo_prezzo_prodotto => ($prodotto['movimenti_articoli_prezzo']) ?: '0.0',
                                $campo_prezzo_fornitore_prodotto => $prodotto['movimenti_articoli_prezzo'],
                                $campo_preview_prodotto => $prodotto['movimenti_articoli_name'],
                        
                                $campo_nascondi_prodotto => ($input['missing_products_insert']) ? DB_BOOL_FALSE : DB_BOOL_TRUE,
                        
                                $campo_iva_prodotto => $prodotto['movimenti_articoli_iva_id'],
                                $campo_tipo_prodotto => '1',
                                $campo_brand_prodotto => $prodotto['movimenti_articoli_marchio_id'] ?? null,
                                $campo_prodotto_supplier => $prodotto['movimenti_articoli_fornitore_id'] ?? null,
                            ];
                        
                            if ($campo_gestione_giacenza_prodotto) {
                                $nuovo_prodotto[$campo_gestione_giacenza_prodotto] = DB_BOOL_TRUE;
                            }
                            // debug($campo_prezzo_prodotto);
                            // debug($nuovo_prodotto, true);
                        
                            $prodotto_id = $this->apilib->create($entita_prodotti, $nuovo_prodotto, false);
                            $prodotto['movimenti_articoli_prodotto_id'] = $prodotto_id;
                        
                            $tmp_prodotti[$prodotto['movimenti_articoli_name']] = $prodotto_id;
                        } else {
                            $prodotto['movimenti_articoli_prodotto_id'] = $tmp_prodotti[$prodotto['movimenti_articoli_name']];
                        }
                    }
                    
                    //die("C'è un pp che crea il prodotto... tenere o questo codice o l'altro...");
                    if ($prodotto['movimenti_articoli_name']) {


                        //Se sto movimentando in scarico e l'articolo è un bundle, verifico se questo bundle deve movimentare anche i figli, solo i figli o solo se stesso
                        if ($input['movimenti_tipo_movimento'] == 2) {
                            
                            if ($prodotto['movimenti_articoli_prodotto_id'] && $fw_product = $this->apilib->view('fw_products', $prodotto['movimenti_articoli_prodotto_id'])) {
                                if ($fw_product['fw_products_kind'] == 3) {//Bundle
                                    if (in_array($fw_product['fw_products_movimentazione_bundle'],[2,3])) { //Se devo movimentare i figli (2 solo i figli, 3 anche i figli)
                                        //Movimento i figli in automatico
                                        $prodotti_figli = $this->apilib->search('fw_products_bundle', ['fw_products_bundle_main_product' => $prodotto['movimenti_articoli_prodotto_id']]);
                                        $prodotti_scarico = [];
                                        foreach ($prodotti_figli as $prodotto_figlio) {
                                            $quantity = ($prodotto_figlio['fw_products_bundle_quantity'] ?? 1) * $prodotto['movimenti_articoli_quantita'];
                                            
                                            $prodotto_figlio_da_movimentare = [
                                                'movimenti_articoli_prodotto_id' => $prodotto_figlio['fw_products_bundle_product'],
                                                'movimenti_articoli_prezzo' => $prodotto_figlio['fw_products_sell_price'],
                                                'movimenti_articoli_descrizione' => $prodotto_figlio['fw_products_description'],
                                                'movimenti_articoli_name' => $prodotto_figlio['fw_products_name'],
                                                'movimenti_articoli_codice' => $prodotto_figlio['fw_products_sku'],
                                                'movimenti_articoli_iva_id' => $prodotto_figlio['fw_products_tax'],
                                                'movimenti_articoli_unita_misura' => $prodotto_figlio['fw_products_unita_misura'],
                                                'movimenti_articoli_codice_fornitore' => $prodotto_figlio['fw_products_provider_code'],
                                                'movimenti_articoli_quantita' => abs($quantity),
                                                'movimenti_articoli_importo_totale' => abs($quantity * $prodotto_figlio['fw_products_sell_price']),
                                                'movimenti_articoli_barcode' => (!empty($prodotto_sparato['fw_products_barcode'])) ? json_decode($prodotto_sparato['fw_products_barcode'])[0] : null,
                                            ];

                                            

                                                $prodotti_scarico[] = $prodotto_figlio_da_movimentare;
                                           

                                        }
                                        if ($prodotti_scarico) {
                                            $this->mov->creaMovimento(
                                                [
                                                    'movimenti_magazzino' => $movimento['movimenti_magazzino'],
                                                    'movimenti_mittente' => 3, //semplice
                                                    'movimenti_data_registrazione' => date('Y-m-d H:i:s'),
                                                    'movimenti_articoli' => $prodotti_scarico,
                                                    'movimenti_tipo_movimento' => 2,
                                                    'movimenti_causale' => 22, //Scarico ad uso interno
                                                    //    Inventario (carico base)
                                                ]
                                            );
                                        }
                                        

                                        //Scarica solo i prodotti figli: il caso 2 è diverso perchè non deve movimentare anche il padre ma solo il figlio, quindi al bundle forzo una quantità 0...
                                        if ($fw_product['fw_products_movimentazione_bundle'] == 2) {
                                            $prodotto['movimenti_articoli_quantita'] = 0;
                                        }
                                    } else {//Scarica solo questo prodotto
                                        //Procedo come un normale prodotto
                                    }
                                    
                                }
                                
                            }
                        }
                        
                        //TODO: serve ancora? Lo usava healthaid per non movimentare alcuni prodotti... secondo me se creo un movimento deve "movimentarli" e quindi scaricare correttamente le quantità...
                        $prodotto['movimenti_articoli_genera_movimenti'] = (!empty($prodotto['movimenti_articoli_genera_movimenti']) && $prodotto['movimenti_articoli_genera_movimenti'] == DB_BOOL_TRUE) ? DB_BOOL_TRUE : DB_BOOL_FALSE;
                        $this->apilib->create("movimenti_articoli", $prodotto);
                        if($input['movimenti_mittente'] == 4){
                            $prodotto['movimenti_articoli_movimento'] = (!empty($movimenti_id_carico)) ? $movimenti_id_carico : $movimenti_id_scarico;
                            $this->apilib->create("movimenti_articoli", $prodotto);
                        }
                        
                    }
                    
                }
            }
            //die('test3');
            //Alla fine di tutto aggiorno il documento associato
            if (!empty($movimento['movimenti_documento_id']) && $movimento['movimenti_documento_id'] != -1) {
                //Uso il model docs per valutare se chiudere l'ordine
                $this->docs->aggiornaStatoDocumento($movimento['movimenti_documento_id']);

                //Non serve più... fa tutto il model doc
                //$stato_documento = $this->mov->getDocumentoStato($movimento['movimenti_documento_id']);
                //debug($stato_documento,true);
                //$this->apilib->edit('documenti_contabilita', $movimento['movimenti_documento_id'], ['documenti_contabilita_stato' => $stato_documento]);

                //Non serve più aggiornare i padri in quanto si aggiornano già alla creazione dell'ordine con le quantità evase in docs...
                // //Aggiorno lo stato ricorsivamente anche ai documenti collegati a questo...
                // $padri = $this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_id IN (SELECT documenti_contabilita_id FROM rel_doc_contabilita_rif_documenti WHERE rel_doc_contabilita_rif_documenti_padre = '{$movimento['movimenti_documento_id']}')")->result_array();
                // //debug($padri,true);
                // foreach ($padri as $padre) {
                //     $stato_documento = $this->mov->getDocumentoStato($padre['documenti_contabilita_id']);
                //     //debug($stato_documento,true);
                //     $this->apilib->edit('documenti_contabilita', $padre['documenti_contabilita_id'], ['documenti_contabilita_stato' => $stato_documento]);
                // }


            }

            if (!empty($movimento['movimenti_ordine_produzione_id'])) {
                $ord_prod = $this->apilib->view('ordini_produzione', $movimento['movimenti_ordine_produzione_id']);
                //debug($ord_prod['ordini_produzione_distinta_base'],true);
                die(json_encode(['status' => 1, 'txt' => base_url('main/layout/dettaglio-distinta-base/' . $ord_prod['ordini_produzione_distinta_base'])]));
            }

            echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/movements-list?save=1&movimento=' . $movimenti_id)));
        }
    }

    public function autocomplete($entity = null)
    {
        if (!$entity) {
            echo json_encode(['count_total' => 0, 'results' => []]);
            die();
        }
        $input = $this->input->get_post('search');

        $count_total = 0;

        $input = trim($input);
        if (empty($input) or strlen($input) < 2) {
            echo json_encode(['count_total' => -1]);
            return;
        }

        $results = [];

        $input = strtolower($input);

        $input = str_ireplace("'", "''", $input);

        $res = [];

        $mappature = $this->mappature->getMappature();
        extract($mappature);

        if ($entity == $entita_prodotti) {
            $campo_preview = $campo_preview_prodotto;
            $campo_codice = $campo_codice_prodotto;
            $campo_barcode = $campo_barcode_prodotto;

            //$res = $this->apilib->search($entity, ["((LOWER($campo_barcode) LIKE '%{$input}%' OR LOWER($campo_preview) LIKE '%{$input}%' OR $campo_preview LIKE '{$input}%') OR (LOWER($campo_codice) LIKE '%{$input}%' OR $campo_codice LIKE '{$input}%'))"]);
            if (!empty($campo_prodotto_eliminato)) {
                //$this->db->where($campo_prodotto_eliminato, DB_BOOL_FALSE);
                $this->db->where("({$campo_prodotto_eliminato} = '".DB_BOOL_FALSE."' OR {$campo_prodotto_eliminato} IS NULL OR {$campo_prodotto_eliminato} = '')", null, false);
            }
    
            if (!empty($campo_nascondi_prodotto)) {
                //$this->db->where("({$campo_nascondi_prodotto} = '".DB_BOOL_FALSE."' OR {$campo_nascondi_prodotto} IS NULL OR {$campo_nascondi_prodotto} = '')", null, false);
            }
            
            if (!empty($campo_gestione_giacenza_prodotto)) {
                $this->db->where($campo_gestione_giacenza_prodotto, DB_BOOL_TRUE);
            }

            $res = $this->db->where("((LOWER($campo_barcode) LIKE '%{$input}%' OR LOWER($campo_preview) LIKE '%{$input}%' OR $campo_preview LIKE '{$input}%') OR (LOWER($campo_codice) LIKE '%{$input}%' OR $campo_codice LIKE '{$input}%'))", null, false)
                ->get($entity)
                ->result_array();
                //debug($this->db->last_query());
        } elseif ($entity == 'customers') {
            $res = $this->apilib->search('clienti', ["(LOWER(customers_company) LIKE '%{$input}%')"], 100, 0, 'customers_company', 'ASC');
        } elseif ($entity == 'suppliers') {
            $res = $this->apilib->search('suppliers', ["(LOWER(suppliers_business_name) LIKE '%{$input}%')", 100, 0, 'suppliers_business_name', 'ASC']);
        } elseif ($entity == 'vettori') {
            $res = $this->apilib->search('vettori', ["(LOWER(vettori_ragione_sociale) LIKE '%{$input}%') ORDER BY vettori_ragione_sociale ASC"]);
        }

        if ($res) {
            $count_total = count($res);
            $results = [
                'data' => $res,
            ];
        }

        echo json_encode(['count_total' => $count_total, 'results' => $results]);
    }
    
    // public function check_quantity_icon_show($product_id, $movimenti_id = -1)
    // {
    //     $quantity_carico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_id <> '$movimenti_id'")->row()->qty;
    //     $quantity_scarico = $this->db->query("SELECT SUM(movimenti_articoli_quantita) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_id <> '$movimenti_id'")->row()->qty;
    //     //debug($quantity_scarico);
    //     $quantity = $quantity_carico - $quantity_scarico;

    //     echo $quantity;
    // }
    public function check_quantity_available($product_id, $magazzino_id, $movimenti_id = -1, $return = false)
    {
        $quantity_carico  = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_magazzino = '$magazzino_id' AND movimenti_id <> '$movimenti_id'")->row()->qty;
        $quantity_scarico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '$product_id' AND movimenti_magazzino = '$magazzino_id' AND movimenti_id <> '$movimenti_id'")->row()->qty;
        $quantity = $quantity_carico - $quantity_scarico;

        if ($return) {
            return $quantity;
        } else {
            echo $quantity;

            exit;
        }
    }

    /**
     * @param int $prodotto_id
     * @param bool $return mettere true se si vuole richiamare questo metodo tramite un altro di questo stesso controller
     * @param bool $use_apilib mettere false se si vuole usare $this->db invece di APILIB, così da rendere più veloce la chiamata
     * @return array|void
     */
    public function getProdotto($prodotto_id, $return = false, $use_apilib = true)
    {
        $mappature = $this->mappature->getMappature();

        extract($mappature);

        try {
            if ($use_apilib) {
                $prodotto = $this->apilib->view($entita_prodotti, $prodotto_id);
            } else {
                $prodotto = $this->db->get_where($entita_prodotti, [$entita_prodotti . '_id' => $prodotto_id])->row_array();
            }
        } catch (Exception $e) {
            $prodotto = [];
        }

        if ($return) {
            return $prodotto;
        } else {
            e_json($prodotto);
        }
    }

    /**
     * @param $magazzino_id
     * @param $movimenti_id
     * @return void
     * 20230705 - michael - queste due funzioni (bulk_check_quantity_available / bulkGetProdotto) vengono richiamate tramite ajax dal nuovo_movimento.php e servono ad evitare che venga fatta una chiamata per ogni riga articolo. il che è meglio soprattutto su movimenti con più di 10 righe articolo.
     */
    public function bulk_check_quantity_available($magazzino_id, $movimenti_id = -1) {
        $post = $this->input->post();

        if (empty($post) || empty($post['products_rows'])) return;

        $products_rows = $post['products_rows'];
        foreach ($products_rows as $row_index => $product_row) {
            $quantity_available = $this->check_quantity_available($product_row['product_id'], $magazzino_id, $movimenti_id, true);

            $products_rows[$row_index]['quantity_available'] = $quantity_available;
        }

        e_json($products_rows);
    }

    /**
     * @return void
     */
    public function bulkGetProdotto() {
        $post = $this->input->post();

        if (empty($post) || empty($post['righe_articoli'])) return;

        $products_rows = $post['righe_articoli'];
        foreach ($products_rows as $row_index => $product_row) {
            $prodotto = $this->getProdotto($product_row['product_id'], true, false);

            $products_rows[$row_index]['prodotto'] = $prodotto;
        }

        e_json($products_rows);
    }

    public function getLotti($product_id, $magazzino = -1)
    {
        if (empty($product_id)) {
            echo json_encode(['status' => 0, 'error' => 'Devi indicare un id prodotto']);
            exit;
        }
        

        //$getlottiprod = $this->apilib->search('movimenti_articoli', ['movimenti_articoli_prodotto_id' => $product_id]);
        $getlottiprod = $this->db->query(
            "SELECT 
                *, 
                SUM(CASE WHEN movimenti_tipo_movimento = 1 THEN movimenti_articoli_quantita ELSE -movimenti_articoli_quantita END) as s 
            FROM 
                movimenti_articoli 
                LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) 
                LEFT JOIN fw_products ON (fw_products_id = movimenti_articoli_prodotto_id) 
                LEFT JOIN customers ON (fw_products_supplier = customers_id)
                LEFT JOIN fw_products_brand ON (fw_products_brand = fw_products_brand_id)
            WHERE 
                (movimenti_magazzino = '$magazzino' OR '-1' = '$magazzino')
                AND movimenti_articoli_prodotto_id = '$product_id' 
            GROUP BY movimenti_articoli_lotto
            Having s > 0
            "
        )->result_array();
        //TODO: non basta, bisogna mostrare le quantità corrette raggruppate per lotti
    
        if (empty($getlottiprod)) {
            echo json_encode(['status' => 0, 'error' => 'Questo prodotto non è presente in nessun lotto di questo magazzino!']);
            exit;
        }
        
        foreach ($getlottiprod as $getlottiprod_index => $lottoprod) {
            $qta_impegnate = $this->mov->calcolaQuantitaOrdinataDaiClienti($product_id, "AND documenti_contabilita_articoli_lotto = '{$lottoprod['movimenti_articoli_lotto']}'");
            
            $getlottiprod[$getlottiprod_index]['qta_impegnate'] = $qta_impegnate;
        }

        $response = ['status' => 1, 'data' => $getlottiprod];

        echo json_encode($response, 128);
    }

    /**
     *
     *
     * BELLES
     *
     *
     */

    public function autocompleteBarcodeProduct($movimento_id)
    {
        $keyword = trim($this->input->post('query'));

        if (empty($keyword)) {
            die(json_encode(['status' => 0, 'txt' => 'Ricerca vuota']));
        }

        $keyword = str_pad($keyword, 13, 0, STR_PAD_LEFT);

        // debug($keyword, true);

        $supplier = $this->apilib->view('movimenti', $movimento_id)['movimenti_fornitori_id'];
        $documenti_contabilita_articoli = $this->db->query("
            SELECT * FROM documenti_contabilita_articoli
                LEFT JOIN documenti_contabilita ON documenti_contabilita_id = documenti_contabilita_articoli_documento
                LEFT JOIN accantonamenti ON (accantonamenti_riga_ordine = documenti_contabilita_articoli_id AND accantonamenti_prodotto = documenti_contabilita_articoli_prodotto_id AND (accantonamenti_movimento = $movimento_id OR accantonamenti_movimento IS NULL))
                WHERE documenti_contabilita_articoli_prodotto_id IN (
                    SELECT fw_products_id FROM fw_products WHERE fw_products_barcode = '{$keyword}' AND fw_products_supplier = '$supplier'
                ) AND documenti_contabilita_stato IN (1,2,5,6)
                AND (documenti_contabilita_articoli_id,documenti_contabilita_articoli_quantita) NOT IN (
                    SELECT accantonamenti_riga_ordine,COALESCE(accantonamenti_stk,0)+COALESCE(accantonamenti_shp,0)+COALESCE(accantonamenti_del,0)-accantonamenti_qty  FROM accantonamenti

                    )
                AND documenti_contabilita_tipo = 5
                AND (
                    documenti_contabilita_articoli_quantita > (
                        accantonamenti_qty+
                        COALESCE(accantonamenti_shp,0)
                        +COALESCE(accantonamenti_stk,0)
                        +COALESCE(accantonamenti_del,0)
                    ) OR accantonamenti_qty IS NULL)

        ")->result_array();

        //die($this->db->last_query());
        // debug($documenti_contabilita_articoli, true);
        if (count($documenti_contabilita_articoli) == 1) {
            $riga_ordine = $documenti_contabilita_articoli[0];
            $accantonamento = $this->doAccantona($movimento_id, $riga_ordine['documenti_contabilita_articoli_prodotto_id'], $riga_ordine);
        }
        e_json([
            'status' => 1,
            'data' => $documenti_contabilita_articoli,
        ]);
    }

    public function accantona($movimento_id)
    {
        $riga_ordine = json_decode($this->input->post('riga_ordine'), true);

        $accantonamento = $this->doAccantona($movimento_id, $riga_ordine['documenti_contabilita_articoli_prodotto_id'], $riga_ordine);

        e_json([
            'status' => 1,
            'data' => $accantonamento,
        ]);
    }
    public function change_qty_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_qty' => $newqty]);
    }
    public function change_bo_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_bo' => $newqty]);
    }
    public function change_stk_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_stk' => $newqty]);
    }
    public function change_del_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_del' => $newqty]);
    }
    public function change_shp_accantonamento($id, $newqty)
    {
        $this->apilib->edit('accantonamenti', $id, ['accantonamenti_shp' => $newqty]);
    }
    private function doAccantona($movimento_id, $prodotto_id, $riga_ordine)
    {

        //Verifico se il prodotto esiste già nel movimento
        $exists = $this->apilib->searchFirst('movimenti_articoli', [
            'movimenti_articoli_prodotto_id' => $prodotto_id,
            'movimenti_articoli_movimento' => $movimento_id,
        ]);

        //debug($exists, true);
        if ($exists) {
            $this->apilib->edit('movimenti_articoli', $exists['movimenti_articoli_id'], [
                'movimenti_articoli_quantita' => $exists['movimenti_articoli_quantita'] + 1,
            ]);
        } else {
            $prodotto = $this->apilib->view('fw_products', $prodotto_id);
            //debug($prodotto, true);
            $riga_articolo = [
                'movimenti_articoli_barcode' => $prodotto['fw_products_barcode'],
                'movimenti_articoli_codice' => $prodotto['fw_products_barcode'],
                'movimenti_articoli_name' => $prodotto['fw_products_name'],
                'movimenti_articoli_descrizione' => $prodotto['fw_products_description'],
                'movimenti_articoli_lotto' => '',
                'movimenti_articoli_data_scadenza' => '',
                'movimenti_articoli_quantita' => 1,
                'movimenti_articoli_prezzo' => $prodotto['fw_products_sell_price'],
                'movimenti_articoli_iva_id' => $prodotto['fw_products_tax'],
                'movimenti_articoli_iva' => 0,
                'movimenti_articoli_importo_totale' => $prodotto['fw_products_sell_price'],
                'movimenti_articoli_prodotto_id' => $prodotto_id,
                'movimenti_articoli_movimento' => $movimento_id,
                'movimenti_articoli_genera_movimenti' => 0,

            ];
            $this->apilib->create('movimenti_articoli', $riga_articolo);
        }

        //Verifico che non sia stato forzato un accantonamento privo di movimento per la gestione delle quantità nella pagina riepilogo ordini (vedi eval BO...)
        //Se è così associio quell'accantonamento a questo movimento.
        $accantonamento = $this->apilib->searchFirst('accantonamenti', [
            'accantonamenti_prodotto' => $prodotto_id,
            'accantonamenti_riga_ordine' => $riga_ordine['documenti_contabilita_articoli_id'],

        ]);
        if ($accantonamento) {
            $this->apilib->edit('accantonamenti', $accantonamento['accantonamenti_id'], ['accantonamenti_movimento' => $movimento_id]);
        }

        $exists = $this->apilib->searchFirst('accantonamenti', [
            'accantonamenti_prodotto' => $prodotto_id,
            'accantonamenti_riga_ordine' => $riga_ordine['documenti_contabilita_articoli_id'],
            'accantonamenti_movimento' => $movimento_id,
            'accantonamenti_bo > accantonamenti_qty - COALESCE(accantonamenti_stk,0)',
        ]);

        //debug($exists, true);

        $accantonamento = [
            'accantonamenti_prodotto' => $prodotto_id,
            'accantonamenti_riga_ordine' => $riga_ordine['documenti_contabilita_articoli_id'],
            'accantonamenti_movimento' => $movimento_id,

        ];
        if ($exists) {
            $accantonamento['accantonamenti_qty'] = $exists['accantonamenti_qty'] + 1;
            $this->apilib->edit('accantonamenti', $exists['accantonamenti_id'], $accantonamento);
        } else {
            $accantonamento['accantonamenti_qty'] = 1;
            $accantonamento['accantonamenti_stk'] = '0';
            $accantonamento['accantonamenti_del'] = '0';
            $accantonamento['accantonamenti_shp'] = '0';
            $accantonamento['accantonamenti_bo'] = $riga_ordine['documenti_contabilita_articoli_quantita'];

            $this->apilib->create('accantonamenti', $accantonamento);
        }

        return $accantonamento;
    }

    public function salva_accantonamento($movimento_id)
    {
        //$movimento = $this->apilib->view('movimenti', $movimento_id);
        $accantonamenti = $this->apilib->search('accantonamenti', ['accantonamenti_movimento' => $movimento_id]);
        foreach ($accantonamenti as $accantonamento) {
            //Sposto qty su stk e tolgo qty da bo
            $this->apilib->edit('accantonamenti', $accantonamento['accantonamenti_id'], [
                'accantonamenti_stk' => $accantonamento['accantonamenti_stk'] + $accantonamento['accantonamenti_qty'],
                'accantonamenti_qty' => 0,
                'accantonamenti_bo' => $accantonamento['accantonamenti_bo'] - $accantonamento['accantonamenti_qty'],
            ]);
        }
        redirect(base_url('main/layout/accantonamenti/' . $movimento_id));
    }

    public function ricalcolaQuantita($limit = 100, $offset = 0)
    {
        if ($offset == 0) {
            $this->db->query("DELETE FROM movimenti_articoli WHERE movimenti_articoli_movimento NOT IN (SELECT movimenti_id FROM movimenti)");

        }

        $where = ['fw_products_type' => 1];

        $products = $this->apilib->search('fw_products', $where, $limit, $offset);

        $count = $this->apilib->count('fw_products', $where);

        $c = 0;
        foreach ($products as $key => $prodotto) {
            $c++;
            $prodotto_id = $prodotto['fw_products_id'];

            $quantity_carico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '$prodotto_id' ")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '$prodotto_id' ")->row()->qty;
            $quantity = ($quantity_carico - $quantity_scarico);

            $this->apilib->edit('fw_products', $prodotto_id, [
                'fw_products_quantity' => $quantity,
            ]);

            progress($c + $offset, $count);
            echo_flush(' .');

        }
        if ($offset <= $count) {
            $new_offset = $offset + $limit;
            echo "<script>location.href='" . base_url() . "magazzino/movimenti/ricalcolaQuantita/{$limit}/$new_offset'</script>";
        }

    }
    public function parseLottoFile()
    {
        $file = $_FILES['file']['tmp_name'];
        $file_content = file_get_contents($file);

        $separ = (substr_count($file_content, ',') > substr_count($file_content, ';')) ? ',' : ';';

        //Lo ciclo
        $csv = array_map(function ($foo) use ($separ) {
            return array_map("trim", str_getcsv($foo, $separ));
        }, file($file, FILE_SKIP_EMPTY_LINES));


        //Controlli e rimappature su csv
        $csv = array_map(function ($data) {

            if (empty($data[0]) || empty($data[2])) {
                return null;
            } else {
                if (!empty($data[3])) {
                $data_scadenza = $data[3];
                $expl = explode('/', $data_scadenza);
                if (count($expl) == 3) {
                    $a_date = "{$expl[2]}-{$expl[1]}-{$expl[0]}";
                    $data[3] = date("t/m/Y", strtotime($a_date));
                } else {
                    $data[3] = '';
                }
                }

                //Quantità
                if (strpos($data['2'], '.') && strpos($data['2'], ',')) {
                    $data['2'] = str_replace('.', '', $data['2']);
                    $data['2'] = str_replace(',', ',', $data['2']);
                    $data['2'] = floatval($data['2']);
                }


                return $data;
            }
        }, $csv);

        $csv = array_filter($csv);

        //debug($csv,true);

        echo json_encode($csv);
        exit;
    }

    public function fixQuantitaEvase($doc_id)
    {
        $documenti_articoli = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $doc_id]);
        $c = 0;
        $total = count($documenti_articoli);
        foreach ($documenti_articoli as $articolo) {
            $c++;
            progress($c, $total);
            $this->docs->calcolaQuantitaEvasaDoc($articolo['documenti_contabilita_articoli_id']);
        }
    }


    public function ricalcolaGiacenzeMagazzini ($prodotto_id = false) {
        return $this->mov->ricalcolaGiacenzeMagazzini($prodotto_id);
}
    
    public function get_commesse($cliente_id = null) {
        if (!$this->datab->module_installed('projects')) {
            e_json(['status' => 0, 'txt' => 'Il modulo non è installato']);
            return;
        }
        
        $where_commesse = [];
        
        if ($cliente_id) {
            $where_commesse['projects_customer_id'] = $cliente_id;
        }
        
        $commesse = $this->apilib->search('projects', $where_commesse, null, 0, 'projects_name', 'ASC');
        
        e_json(['status' => 1, 'txt' => $commesse]);

    }
}
