<?php
class App extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With
    }

    public function save()
    {
        //dump($this->input->post());
        $magazzino_id = $this->input->post('magazzino_id');
        $tipo_movimento = $this->input->post('tipo_movimento');
        $causale = $this->input->post('causale');
        $utente = $this->input->post('user');
        $articoli = json_decode($this->input->post('prodotti'), true);

        if(empty($magazzino_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Magazzino non riconosciuto']));
        }
        if(empty($tipo_movimento)) {
            die(json_encode(['status' => 0, 'txt' => 'Tipo di movimento non riconosciuto']));
        }
        if(empty($causale)) {
            die(json_encode(['status' => 0, 'txt' => 'Causale non riconosciuta']));
        }
        if(empty($utente)) {
            die(json_encode(['status' => 0, 'txt' => 'Utente non riconosciuto']));
        }
        if(empty($articoli)) {
            die(json_encode(['status' => 0, 'txt' => 'Prodotti non riconosciuti']));
        }

        try {
            $this->load->model('magazzino/mov');

            $data['movimenti_user'] = $utente;
            $data['movimenti_magazzino'] = $magazzino_id;
            $data['movimenti_causale'] = $causale;
            $data['movimenti_tipo_movimento'] = $tipo_movimento;
            $data['movimenti_data_registrazione'] = date('Y-m-d H:i:s');

            foreach ($articoli as $articolo) {
                //dump($articolo);
                $prodotto = [
                    'movimenti_articoli_prodotto_id' => $articolo['product']['fw_products_id'],
                    'movimenti_articoli_prezzo' => $articolo['product']['fw_products_sell_price'],
                    'movimenti_articoli_descrizione' => $articolo['product']['fw_products_description'],
                    'movimenti_articoli_name' => $articolo['product']['fw_products_name'],
                    'movimenti_articoli_codice' => $articolo['product']['fw_products_sku'],
                    'movimenti_articoli_iva_id' => $articolo['product']['fw_products_tax'],
                    //'movimenti_articoli_accantonamento' => $articolo['product']['documenti_contabilita_articoli_id'],
                    'movimenti_articoli_unita_misura' => $articolo['product']['fw_products_unita_misura'],
                    //'movimenti_articoli_codice_fornitore' => $articolo['product']['documenti_contabilita_articoli_codice'],
                    'movimenti_articoli_quantita' => 1,
                    // 'movimenti_articoli_iva' => $articolo['product']['documenti_contabilita_articoli_iva'],
                    'movimenti_articoli_importo_totale' => $articolo['product']['fw_products_sell_price_tax_included'],
                    //'movimenti_articoli_sconto' => $articolo['product']['documenti_contabilita_articoli_sconto'],
                    'movimenti_articoli_data_scadenza' => null,
                    'movimenti_articoli_lotto' => null,
                    'movimenti_articoli_barcode' => (!empty($articolo['product']['fw_products_barcode'])) ? json_decode($articolo['product']['fw_products_barcode'])[0] : null,
                ];

                $prodotti[] = $prodotto;
            }

            $data['movimenti_articoli'] = $prodotti;

            $this->mov->creaMovimento($data);

            die(json_encode([
                'status' => 1,
                'txt' => 'Movimento creato con successo'
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'status' => 0,
                'txt' => $e->getMessage()
            ]));
        }
    }
}