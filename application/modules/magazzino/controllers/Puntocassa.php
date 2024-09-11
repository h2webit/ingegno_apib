<?php
class Puntocassa extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function save()
    {
        $post = json_decode($this->input->post('cartContent'), true);

        if (empty($post['cart'])) {
            die(json_encode(['status' => 0, 'txt' => t('Cart is empty')]));
        }

        // Tipo ricevuta selezionata
        $tipo_ricevuta = !empty($this->input->post('cart_tipo_ricevuta')) ? $this->input->post('cart_tipo_ricevuta') : null;

        /* if (empty($post['user'])) {
            die(json_encode(['status' => 0, 'txt' => t('Customer not declared')]));
        } */

        $cart = $post['cart'];

        $articoli = [];
        foreach ($cart as $articolo) {
            $doc_cont_art = [
                'documenti_contabilita_articoli_codice' => $articolo['prodotto']['fw_products_sku'],
                'documenti_contabilita_articoli_prezzo' => $articolo['prezzo_no_iva'],
                'documenti_contabilita_articoli_prodotto_id' => $articolo['prodotto']['fw_products_id'],
                'documenti_contabilita_articoli_iva_perc' => $articolo['perc_iva'],
                'documenti_contabilita_articoli_quantita' => $articolo['quantita'],
                'documenti_contabilita_articoli_name' => $articolo['prodotto']['fw_products_name'],
                'documenti_contabilita_articoli_descrizione' => $articolo['prodotto']['fw_products_description'],
                'documenti_contabilita_articoli_codice_ean' => $articolo['prodotto']['fw_products_barcode'],
                'documenti_contabilita_articoli_unita_misura' => 'Pz', // @todo Michael - 2021-07-20 - Da capire se andrebbe reso dinamico
                'documenti_contabilita_articoli_sconto' => $articolo['sconto'],
                'documenti_contabilita_articoli_iva_id' => $articolo['iva_id'],
                'documenti_contabilita_articoli_importo_totale' => $articolo['prezzo_ivato'],
                'documenti_contabilita_articoli_iva' => $articolo['_iva_totale_scontato'],
            ];

            $articoli[] = $doc_cont_art;
        }


        $magazzino_id = $post['magazzino'] ?? $this->auth->get('magazzino');
        $magazzino_db = $this->apilib->view('magazzini', $magazzino_id);
        $magazzino_nome = $magazzino_db['magazzini_titolo'] ?? '';
        //Se cliente non c'è lo creo al volo mettendo "Cliente punto cassa - magazzino NOME MAGAZZINO"
        if (empty($post['user'])) {
            //Se esiste già un cliente per quel magazzino devo associare questo ordine a quel cliente così da averli ragruppati
            $customer_exists = $this->apilib->searchFirst('customers', ['customers_name' => 'Cliente punto cassa', 'customers_last_name' => 'Magazzino ' . $magazzino_nome]);
            if (!empty($customer_exists)) {
                $customer_id = $customer_exists['customers_id'];
            } else {
                try {
                    $fake_customer = $this->apilib->create('customers', [
                        'customers_type' => 1,
                        'customers_status' => 1,
                        'customers_group' => 1,
                        'customers_name' => 'Cliente punto cassa',
                        'customers_last_name' => 'Magazzino ' . $magazzino_nome,
                        'customers_description' => 'Cliente creato per ordine in punto cassa dal ' . date('Y-m-d H:i'),
                    ]);
                    $customer_id = $fake_customer['customers_id'];
                } catch (Exception $e) {
                    //die(json_encode(['status' => 0, 'txt' => 'Errore durante la creazione del cliente']));
                    die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
                }
            }
        } else {
            $customer_id = $post['user'];
        }

        $tpl_pdf = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'ASC');

        $data = [
            'cliente_id' => $customer_id,
            'articoli_data' => $articoli,
            'tipo_documento' => 5,
            'stato' => 3,
            'template' => $tpl_pdf['documenti_contabilita_template_pdf_id'],
        ];

        $pc_settings = $this->apilib->searchFirst('punto_cassa_settings');

        if (!empty($this->input->post('serie'))) {
            $data['serie'] = $this->input->post('serie');
        } elseif (!empty($pc_settings['documenti_contabilita_serie_value'])) {
            $data['serie'] = $pc_settings['documenti_contabilita_serie_value'];
        }

        if ($this->input->post('totale_scontato') > 0) {
            $data['documenti_contabilita_totale'] = $this->input->post('totale_scontato');
        }

        if (!empty($post['centro_di_costo'])) {
            $data['centro_di_costo'] = $post['centro_di_costo'];
        }

        $this->load->model('contabilita/docs');

        try {
            $documento_id = $this->docs->doc_express_save($data);
            //redirect al dettaglio documento
            //die(json_encode(['status' => 1, 'txt' => base_url()]));

            if ($this->datab->module_installed('magazzino')) {
                $this->load->model('magazzino/mov');
                $data = ['documento_id' => $documento_id];
                //se magazzino non è stato scelto dalla select lo imposto prendendolo dalla sessione
                if (empty($post['magazzino']) && $this->auth->get('magazzino')) {
                    $data['movimenti_magazzino'] = $this->auth->get('magazzino');
                } else {
                    $data['movimenti_magazzino'] = $post['magazzino'];
                }
                $this->mov->creaMovimento($data);
            }

            $return_status = 1;

            // Emissione documento fiscale / ricevuta / fattura
            switch ($pc_settings['cart_tipo_ricevuta']) { // Todo sostituire con campo che arriva dal form

                case 1: // Nessuna ricevuta
                    break;

                case 2: // Fattura
                    // Todo emettere fattura
                    break;

                case 3: // Ricevuta fiscale
                case 4: // Ricevuta non fiscale
                    $art_desc = count($articoli) > 1 ? 'articoli' : 'articolo';
                    $ricevuta['ricevute_cassa_tipo'] = $pc_settings['cart_tipo_ricevuta'] == 3 ? 1 : 2;
                    $ricevuta['ricevute_cassa_articolo'] = count($articoli) . ' ' . $art_desc;
                    $ricevuta['ricevute_cassa_qty'] = count($articoli);
                    $ricevuta['ricevute_cassa_prezzo'] = $data['documenti_contabilita_totale'];
                    $ricevuta['ricevute_cassa_totale'] = $data['documenti_contabilita_totale'];
                    $ricevuta_id = $this->apilib->create('ricevute_cassa', $ricevuta, false);

                    // Verifico se la ricevuta fa stampata lato client o remota (se remota la fa il PP e torno lo stato di redirect)
                    $this->load->model('registratori-cassa-fiscali/Registratori');
                    $registratore = $this->registratori->get_registratore_default();

                    if ($registratore['registratori_cassa_settings_connessione'] == 1) {
                        $return_status = 2; // Stampa ricevuta dopo salvataggio ordine apre modale con stampa ricevuta lato client
                    }

                    break;

                default:
                    log_message('error', 'Tipo ricevuta non valido');
                    break;
            }

            // Gestisco l'output cosi il punto casssa sa se fare redirect a se stesso oppure aprire modale di stampa ricevuta
            if ($return_status == 1) {
                echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/punto-cassa')));
            } else {
                echo json_encode(array('status' => $return_status, 'txt' => $ricevuta_id));
            }


        } catch (Exception $e) {

            die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));

        }
    }
}