<?php
class Main extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        //$this->load->model('firecrm/general');
    }

    public function filter_project_status($status_id,$tipologia)
    {
        $field_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'projects_status'")->row()->fields_id;

        $projects_filter = (array) @$this->session->userdata(SESS_WHERE_DATA)['projects_filter'];

        $projects_filter[$field_id] = [
            'value' => $status_id,
            'field_id' => $field_id,
            'operator' => 'eq',
        ];

        $filtro = $this->session->userdata(SESS_WHERE_DATA);
        $filtro['projects_filter'] = $projects_filter;
        $this->session->set_userdata(SESS_WHERE_DATA, $filtro);

        redirect(base_url('main/layout/'.$tipologia));
    }
    public function associaArticoliCommessa()
    {
        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['articoli_ids']) || empty($post['project_id'])) {
            throw new ApiException("Controllo integrità dati fallita, riprovare o contattare l'assistenza");
            exit;
        }

        $project_id = $post['project_id'];
        $project = $this->db->get_where('projects', ['projects_id' => $project_id])->row_array();

        if (empty($project)) {
            throw new ApiException("Commessa non trovata, riprovare o contattare l'assistenza");
            exit;
        }

        $articoli_ids = json_decode($post['articoli_ids'], true);
        foreach ($articoli_ids as $articolo_id) {
            $prodotto = $this->apilib->searchFirst('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $articolo_id]);
            try {
                $this->apilib->create('projects_items', [
                    'projects_items_product' => $articolo_id,
                    'projects_items_project' => $project_id,
                    'projects_items_prezzo_acquisto' => $prodotto['fw_products_provider_price']
                ]);
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                throw new ApiException("Errore durante associazione articoli commessa, riprovare o contattare l'assistenza");
                exit;
            }
        }

        redirect(base_url('main/layout/dettaglio_progetto_commessa/' . $project_id), 'refresh');
    }
    public function print()
    {
        $view_data = $this->input->get();
    
        $this->load->view('projects/etichetta/print', $view_data);
    }
}
