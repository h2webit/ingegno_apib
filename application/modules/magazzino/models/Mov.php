<?php

class Mov extends CI_Model
{
    public function creaMovimento(array $data = [])
    {
        $documento_id = array_get($data, 'documento_id', false);
        if ($documento_id) { //Sto creando un movimento da un documento, quindi è tutto relativamente semplice
            $check_movimento_presente = $this->db->query("SELECT * FROM movimenti 
                WHERE 
                    movimenti_creation_date >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) 
                    AND movimenti_user = '{$this->auth->get('id')}' 
                    AND movimenti_documento_id = $documento_id")->num_rows();
            if ($check_movimento_presente) {
                return false;
            }            //Per prima cosa verifico se è un documento di vendita o di acquisto. nel primo caso creo movimento di scarico, altrimenti di carico
            $documento = $this->apilib->view('documenti_contabilita', $documento_id);
            if (in_array($documento['documenti_contabilita_tipo'], [1, 3, 5, 8, 7])) { //SCARICO
                $movimenti_tipo_movimento = 2;
                //TODO: in base al documento posso recuperarmi i dati corretti, ma per ora va bene così
                $movimenti_causale = 18; //Vendita merci, generico
                $movimenti_documento_tipo = 5; //Ordine cliente
                $movimenti_mittente = 2; // Cliente
                $movimenti_clienti_id = $documento['documenti_contabilita_customer_id'];
                $movimenti_fornitori_id = null;
            } elseif (in_array($documento['documenti_contabilita_tipo'], [4, 6, 10])) { //CARICO
                $movimenti_tipo_movimento = 1;
                //TODO: in base al documento posso recuperarmi i dati corretti, ma per ora va bene così
                $movimenti_causale = 1; //Acquisto merci, generico
                $movimenti_documento_tipo = 6; //Ordine fornitore
                $movimenti_mittente = 1; // Fornitore
                $movimenti_fornitori_id = $documento['documenti_contabilita_supplier_id'];
                $movimenti_clienti_id = null;
            } else {
                throw new Exception("Documento di tipo '{$documento['documenti_contabilita_tipo']}' non riconosciuto");
            }
            $movimenti_documento_id = $documento_id;
            $movimenti_numero_documento = $documento['documenti_contabilita_numero'];

            if ($documento['documenti_contabilita_serie']) {
                $movimenti_numero_documento .= '/' . $documento['documenti_contabilita_serie'];
            }

            $movimenti_data_documento = $documento['documenti_contabilita_data_emissione'];
            $movimenti_totale = $documento['documenti_contabilita_totale'];
            $movimenti_destinatario = $documento['documenti_contabilita_destinatario'];

            $movimenti_articoli = [];
            foreach ($this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]) as $documento_articolo) {
                // 'movimenti_articoli_iva_id' => $documento_articolo[''],
                // 'movimenti_articoli_accantonamento'
                // 'movimenti_articoli_prodotto_id'
                // 'movimenti_articoli_movimento'
                // 'movimenti_articoli_unita_misura'
                // 'movimenti_articoli_creation_date'
                // 'movimenti_articoli_modified_date'
                // 'movimenti_articoli_codice'
                // 'movimenti_articoli_codice_fornitore'
                // 'movimenti_articoli_descrizione'
                // 'movimenti_articoli_quantita'
                // 'movimenti_articoli_prezzo'
                // 'movimenti_articoli_iva'
                // 'movimenti_articoli_importo_totale'
                // 'movimenti_articoli_sconto'
                // 'movimenti_articoli_name'
                // 'movimenti_articoli_data_scadenza'
                // 'movimenti_articoli_genera_movimenti'
                // 'movimenti_articoli_lotto'
                // 'movimenti_articoli_barcode'
                //debug($documento_articolo, true);
                $barcode = (!empty($documento_articolo['fw_products_barcode'])) ? $documento_articolo['fw_products_barcode'] : null;
                if ($barcode && is_array(json_decode($barcode))) {
                    $barcode = json_decode($barcode)[0];
                }
                $movimenti_articoli[] = [
                    'movimenti_articoli_iva_id' => $documento_articolo['documenti_contabilita_articoli_iva_id'],
                    'movimenti_articoli_accantonamento' => null,
                    'movimenti_articoli_prodotto_id' => $documento_articolo['documenti_contabilita_articoli_prodotto_id'],
                    'movimenti_articoli_unita_misura' => $documento_articolo['documenti_contabilita_articoli_unita_misura'],
                    'movimenti_articoli_codice' => $documento_articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_codice_fornitore' => $documento_articolo['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_descrizione' => $documento_articolo['documenti_contabilita_articoli_descrizione'],
                    'movimenti_articoli_quantita' => $documento_articolo['documenti_contabilita_articoli_quantita'],
                    'movimenti_articoli_prezzo' => $documento_articolo['documenti_contabilita_articoli_prezzo'],
                    'movimenti_articoli_iva' => $documento_articolo['documenti_contabilita_articoli_iva'],
                    'movimenti_articoli_importo_totale' => $documento_articolo['documenti_contabilita_articoli_importo_totale'],
                    'movimenti_articoli_sconto' => $documento_articolo['documenti_contabilita_articoli_sconto'],
                    'movimenti_articoli_name' => $documento_articolo['documenti_contabilita_articoli_name'],
                    'movimenti_articoli_data_scadenza' => null,
                    'movimenti_articoli_lotto' => null,
                    'movimenti_articoli_barcode' => $barcode,
                ];
            }
        } else {
            //debug("CreaMovimento senza documento associato non ancora gestito");
        }
        $movimenti_magazzino = $this->getMagazzino($data);
        $movimenti_data_registrazione = date('Y-m-d H:i:s');
        $movimenti_user = $this->auth->get('users_id');

        //Faccio un extract perchè qualunque campo passo in data va a sovrascrivere eventuali valori che mi son calcolato io (es.: se passo la causale, viene forzata quella)
        extract($data);

        $movimento = [
            'movimenti_user' => $movimenti_user,
            'movimenti_fornitori_id' => ($movimenti_fornitori_id ?? null),
            'movimenti_clienti_id' => ($movimenti_clienti_id ?? null),
            'movimenti_documento_id' => ($movimenti_documento_id ?? null),
            'movimenti_magazzino' => $movimenti_magazzino,
            'movimenti_causale' => $movimenti_causale,
            'movimenti_tipo_movimento' => $movimenti_tipo_movimento,
            'movimenti_documento_tipo' => ($movimenti_documento_tipo ?? null),
            'movimenti_mittente' => ($movimenti_mittente ?? null),
            'movimenti_data_registrazione' => $movimenti_data_registrazione,
            'movimenti_numero_documento' => ($movimenti_numero_documento ?? null),
            'movimenti_data_documento' => ($movimenti_data_documento ?? null),
            'movimenti_totale' => ($movimenti_totale ?? null),
            'movimenti_destinatario' => ($movimenti_destinatario ?? null),
        ];

        //Rifaccio extract perchè nulla mi vieta di passare direttamente il movimento con tutti i dati pronti all'inserimento
        extract($data);

        //A questo punto posso creare il movimento
        $movimento_id = $this->apilib->create('movimenti', $movimento, false);
        //Ora procedo a inserire i prodotti nel movimento

        //debug($movimenti_articoli, true);

        foreach ($movimenti_articoli as $articolo) {
            $articolo['movimenti_articoli_movimento'] = $movimento_id;
            $this->apilib->create('movimenti_articoli', $articolo);

            //Aggiorno le quantità movimentate nell'ordine
            if ($documento_id) {
                if ($articolo['movimenti_articoli_accantonamento']) {
                    $riga_ordine = $this->apilib->searchFirst('documenti_contabilita_articoli', [
                        'documenti_contabilita_articoli_documento' => $documento_id,
                        'documenti_contabilita_articoli_id' => $articolo['movimenti_articoli_accantonamento']
                    ]);
                    //debug($riga_ordine, true);
                    if ($riga_ordine) {
                        $this->apilib->edit('documenti_contabilita_articoli', $riga_ordine['documenti_contabilita_articoli_id'], [
                            'documenti_contabilita_articoli_qty_movimentate' => (int) $riga_ordine['documenti_contabilita_articoli_qty_movimentate'] + $articolo['movimenti_articoli_quantita']
                        ]);
                    }
                } else {
                    //debug('test', true);
                    $riga_ordine = $this->apilib->searchFirst('documenti_contabilita_articoli', [
                        'documenti_contabilita_articoli_documento' => $documento_id,
                        'documenti_contabilita_articoli_prodotto_id' => $articolo['movimenti_articoli_prodotto_id']
                    ]);
                    if ($riga_ordine) {
                        $this->apilib->edit('documenti_contabilita_articoli', $riga_ordine['documenti_contabilita_articoli_id'], [
                            'documenti_contabilita_articoli_qty_movimentate' => (int) $riga_ordine['documenti_contabilita_articoli_qty_movimentate'] + $articolo['movimenti_articoli_quantita']
                        ]);
                    }
                }
            }
        }

        return $this->apilib->view('movimenti', $movimento_id);
    }

    public function getMagazzino($data)
    {
        //TODO: funzione intelligente che scarica dal magazzino più pieno
        return $this->apilib->searchFirst('magazzini')['magazzini_id'];
    }


    public function calcolaQuantitaEvasa($documenti_contabilita_articoli_id)
    {
        $riga_ordine = $this->db
            ->join('documenti_contabilita', 'documenti_contabilita_id = documenti_contabilita_articoli_documento', 'LEFT')
            ->get_where('documenti_contabilita_articoli', [
                'documenti_contabilita_articoli_id' => $documenti_contabilita_articoli_id
            ])
            ->row_array();



        $prodotto_id = $riga_ordine['documenti_contabilita_articoli_prodotto_id'];
        if (!$prodotto_id) {
            return 0;
        }

        //Gli ordini fornitore li gestisco al rovescio (carico/scarico....)
        if (in_array($riga_ordine['documenti_contabilita_tipo'], [6, 10])) {
            $tipo_movimento = 1;
        } else {
            $tipo_movimento = 2;
        }

        $qty_evasa = $this->db->query("
        
            SELECT SUM(movimenti_articoli_quantita) as s
            FROM movimenti_articoli 
            LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento)
        
            WHERE 
                (
                    movimenti_articoli_rif_riga_doc = '$documenti_contabilita_articoli_id'
                )
                
                AND movimenti_tipo_movimento = $tipo_movimento
                AND movimenti_articoli_prodotto_id = $prodotto_id

        ")->row()->s;
        //debug($qty_evasa,true);

        return $qty_evasa;
    }
    // public function calcolaQuantitaRimanenteXRiga($documenti_contabilita_articoli_id) {
    //     $riga = $this->db->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $documenti_contabilita_articoli_id])->row_array();

    //     $documenti_contabilita_articoli_rif_riga_articolo = $riga['documenti_contabilita_articoli_rif_riga_articolo'];
    //     $riga_rif = $this->db->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $documenti_contabilita_articoli_rif_riga_articolo])->row_array();

    //     return $this->calcolaQuantitaRimanente($riga['documenti_contabilita_articoli_prodotto_id'], $riga_rif['documenti_contabilita_articoli_documento']);

    // }
    public function calcolaQuantitaRimanente($prodotto_id, $documento_id = null)
    {
        
        // michael, 20/09/2024 - aggiunto questo where in quanto mi serviva per calcolare la quantità rimanente per un documento specifico, ad esempio nella pagina di "prodotti in ordine"
        $where_documento = '';
        if ($documento_id && is_numeric($documento_id)) {
            $where_documento = "documenti_contabilita_articoli_documento = '$documento_id'";
        } else {
            $where_documento = "documenti_contabilita_tipo IN (5)";
        }
        
        $impegnate = $this->db->query("
            SELECT SUM(COALESCE(documenti_contabilita_articoli_quantita,0)-(COALESCE(documenti_contabilita_articoli_qty_movimentate,0)+COALESCE(documenti_contabilita_articoli_qty_evase_in_doc,0))) as s
            FROM documenti_contabilita_articoli
            LEFT JOIN documenti_contabilita ON (documenti_contabilita_id = documenti_contabilita_articoli_documento)
            WHERE
                (
                
                COALESCE(documenti_contabilita_articoli_quantita, 0) -
                (COALESCE(documenti_contabilita_articoli_qty_evase_in_doc, 0) +
                 COALESCE(documenti_contabilita_articoli_qty_movimentate, 0)
                ) > 0
                
                ) and
                documenti_contabilita_articoli_prodotto_id = '$prodotto_id'
                AND
                documenti_contabilita_stato IN (1,2,5)
                AND 
                $where_documento
        ")->row()->s;
        //debug($this->db->last_query());
        
        if ($impegnate < 0) {
            $impegnate = 0;
        }
        
        return $impegnate;
    }

    public function calcolaQuantitaOrdinata($prodotto_id)
    {

        $impegnate = $this->db->query("
            SELECT SUM(
                GREATEST(0, 
                    COALESCE(documenti_contabilita_articoli_quantita, 0) - 
                    (COALESCE(documenti_contabilita_articoli_qty_movimentate, 0) + 
                    COALESCE(documenti_contabilita_articoli_qty_evase_in_doc, 0))
                )
            ) as s 
            FROM documenti_contabilita_articoli
            LEFT JOIN documenti_contabilita ON (documenti_contabilita_id = documenti_contabilita_articoli_documento)
            WHERE
                documenti_contabilita_articoli_prodotto_id = $prodotto_id
                AND
                documenti_contabilita_stato IN (1, 2, 5)
                AND
                documenti_contabilita_tipo IN (6)
        ")->row()->s;
        //debug($this->db->last_query());
        return $impegnate;
    }

    public function calcolaQuantitaOrdinataDaiClienti($prodotto_id, $where_append = '')
    {

        $impegnate = $this->db->query("
            SELECT SUM(COALESCE(documenti_contabilita_articoli_quantita,0)-(COALESCE(documenti_contabilita_articoli_qty_movimentate,0)+COALESCE(documenti_contabilita_articoli_qty_evase_in_doc,0))) as s
            FROM documenti_contabilita_articoli
            LEFT JOIN documenti_contabilita ON (documenti_contabilita_id = documenti_contabilita_articoli_documento)
            WHERE
                documenti_contabilita_articoli_prodotto_id = $prodotto_id
                AND
                documenti_contabilita_stato IN (1,2, 5)
                AND
                documenti_contabilita_tipo IN (5)
                {$where_append}
        ")->row()->s;


        return $impegnate;
    }

    public function calcolaGiacenzaAttuale($product, $magazzino = null, $exclude_movimento_id = null)
    {
        if ($exclude_movimento_id) {
            $where_exclude = " AND movimenti_id <> $exclude_movimento_id";
        } else {
            $where_exclude = '';
        }
        if ($magazzino) {
            $quantity_carico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}' AND movimenti_magazzino = '{$magazzino}' $where_exclude")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}' AND movimenti_magazzino = '{$magazzino}' $where_exclude")->row()->qty;
        } else {
            $quantity_carico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) LEFT JOIN magazzini ON (magazzini_id = movimenti_magazzino) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}' AND (magazzini_deleted IS NULL OR magazzini_deleted = 0) $where_exclude")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) LEFT JOIN magazzini ON (magazzini_id = movimenti_magazzino) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id = '{$product['fw_products_id']}' AND (magazzini_deleted IS NULL OR magazzini_deleted = 0) $where_exclude")->row()->qty;
        }

        $quantity = $quantity_carico - $quantity_scarico;

        return $quantity;
    }

    public function calcolaGiacenzaAttualeCategoria($categories, $magazzino = null)
    {
        if (is_numeric($categories)) {
            $categories = [$categories];

        }
        //debug($product);
        if ($magazzino) {
            $quantity_carico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 1 AND movimenti_articoli_prodotto_id IN (SELECT fw_products_id FROM fw_products_fw_categories WHERE fw_categories_id IN (" . implode(',', $categories) . ")) AND movimenti_magazzino = '{$magazzino}'")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) WHERE movimenti_tipo_movimento = 2 AND movimenti_articoli_prodotto_id IN (SELECT fw_products_id FROM fw_products_fw_categories WHERE fw_categories_id IN (" . implode(',', $categories) . ")) AND movimenti_magazzino = '{$magazzino}'")->row()->qty;

        } else {
            $quantity_carico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) LEFT JOIN magazzini ON (magazzini_id = movimenti_magazzino) WHERE movimenti_tipo_movimento = 1 AND (magazzini_deleted IS NULL OR magazzini_deleted = 0) AND movimenti_articoli_prodotto_id IN (SELECT fw_products_id FROM fw_products_fw_categories WHERE fw_categories_id IN (" . implode(',', $categories) . ")) ")->row()->qty;
            $quantity_scarico = $this->db->query("SELECT COALESCE(SUM(movimenti_articoli_quantita), 0) as qty FROM movimenti_articoli LEFT JOIN movimenti ON (movimenti_id = movimenti_articoli_movimento) LEFT JOIN magazzini ON (magazzini_id = movimenti_magazzino) WHERE movimenti_tipo_movimento = 2 AND (magazzini_deleted IS NULL OR magazzini_deleted = 0) AND movimenti_articoli_prodotto_id IN (SELECT fw_products_id FROM fw_products_fw_categories WHERE fw_categories_id IN (" . implode(',', $categories) . "))")->row()->qty;
        }

        $quantity = $quantity_carico - $quantity_scarico;

        return $quantity;
    }

    /**
     * @param $documento_id
     *
     * @return int
     */
    public function getDocumentoStato($documento_id): int
    {
        debug('TODO REMOVE', true);
        // Metto stato aperto di default
        $stato = 1; // Aperto

        // Prendo gli articoli di quel documento
        $articoli = $this->apilib->search('documenti_contabilita_articoli', [
            'documenti_contabilita_articoli_documento' => $documento_id
        ]);

        // Ciclo gli articoli
        foreach ($articoli as $key => $articolo) {
            if (!$articolo['documenti_contabilita_articoli_prodotto_id']) {
                unset($articoli[$key]);
                continue;
            }
            // Calcolo la quantità movimentata
            // $qta_movimentata = $this->db
            //     ->select("SUM(COALESCE(movimenti_articoli_quantita, 0)) as qty")
            //     ->join('movimenti', 'movimenti_id = movimenti_articoli_movimento')
            //     ->where('movimenti_documento_id', $documento_id)
            //     ->where("(movimenti_articoli_prodotto_id IS NOT NULL AND movimenti_articoli_prodotto_id <> '')", null, false)
            //     ->where('movimenti_articoli_prodotto_id', $articolo['documenti_contabilita_articoli_prodotto_id'])
            //     ->get('movimenti_articoli')->row()->qty;

            //Rivista logica alla luce dei nuovi metodi di calcoloquantitaevasa...
            $qty_evasa = $this->calcolaQuantitaEvasa($articolo['documenti_contabilita_articoli_id']);

            $rimanente = $articolo['documenti_contabilita_articoli_quantita'] - $qty_evasa;


            // Se ci sono quantità, metto a prescindere "chiuso parzialmente"
            if ($qty_evasa > 0) {
                $stato = 2; // Chiuso parzialmente
            }
            //debug($rimanente);
            // Se invece la quantià corrisponde, unsetto l'articolo dall'array
            if ($rimanente <= 0) {
                unset($articoli[$key]);
            }
        }
        //debug($articoli,true);
        //A questo punto verifico: se sono entrambi vuoti, vuole dire che tutto coincide e posso chiudere l'ordine
        if (empty($articoli)) {
            $stato = 3; // Chiuso
        }

        return $stato;
    }

    public function ricalcolaGiacenzeMagazzini($prodotto_id = false)
    {
        if ($prodotto_id) {
            $where_delete = " AND magazzini_quantita_prodotto = $prodotto_id";
            $where = " AND movimenti_articoli_prodotto_id = $prodotto_id";
        } else {
            $where = $where_delete = "";
        }

        // Cancella le giacenze esistenti per il prodotto specifico o per tutti
        $this->db->query("DELETE FROM magazzini_quantita WHERE 1=1 $where_delete");

        // Inserisci le nuove giacenze calcolate, includendo anche i prodotti non movimentati
        $this->db->query("
        INSERT INTO magazzini_quantita (magazzini_quantita_prodotto, magazzini_quantita_magazzino, magazzini_quantita_quantita, magazzini_quantita_lotto)
            SELECT 
                fw_products.fw_products_id AS magazzini_quantita_prodotto,
                magazzini.magazzini_id AS magazzini_quantita_magazzino,
                IFNULL(SUM(CASE 
                    WHEN movimenti.movimenti_tipo_movimento = 1 
                        THEN movimenti_articoli.movimenti_articoli_quantita 
                        ELSE -movimenti_articoli.movimenti_articoli_quantita 
                    END), 0) AS magazzini_quantita_quantita,
                movimenti_articoli.movimenti_articoli_lotto AS magazzini_quantita_lotto
            FROM 
                fw_products
            
            LEFT JOIN 
                movimenti_articoli ON movimenti_articoli.movimenti_articoli_prodotto_id = fw_products.fw_products_id
            LEFT JOIN 
                movimenti ON movimenti.movimenti_id = movimenti_articoli.movimenti_articoli_movimento
                
            LEFT JOIN 
                magazzini ON magazzini_id = movimenti_magazzino
            WHERE 
                (magazzini_deleted IS NULL OR magazzini_deleted = 0) $where
            GROUP BY 
                fw_products.fw_products_id, 
                magazzini.magazzini_id,
                COALESCE(movimenti_articoli.movimenti_articoli_lotto, '')
        ");
        //debug($this->db->last_query(), true);
        // Pulizia della cache
        $this->mycache->clearCache();

        return true;
    }

}
