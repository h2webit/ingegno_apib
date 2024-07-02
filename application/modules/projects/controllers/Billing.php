<?php
class Billing extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function saveData()
    {
        $data = $this->input->post();

        $this->load->library('form_validation');

        $this->form_validation->set_rules('billing_documents_date', 'Date', 'required');
        $this->form_validation->set_rules('billing_documents_due_date', 'Due Date', 'required');
        $this->form_validation->set_rules('billing_documents_number', 'Number', 'required');


        if ($this->form_validation->run() == FALSE) {
            die(json_encode(['status' => 0, 'txt' => validation_errors()]));
        } else {
            // Edit document
            if (isset($data['billing_documents_id']) && !empty($data['billing_documents_id'])) {
                $items = $data['items'];
                unset($data['items']);

                $this->apilib->edit('billing_documents', $data['billing_documents_id'], $data);

                // Remove all previus items
                $this->db->query("DELETE FROM billing_items WHERE billing_items_document = '{$data['billing_documents_id']}'");

                foreach ($items as $item) {
                    $item['billing_items_document'] = $data['billing_documents_id'];
                    $this->apilib->create('billing_items', $item);
                }
            } else {
                // New document
                $items = $data['items'];
                unset($data['items']);

                $document = $this->apilib->create('billing_documents', $data);
                if ($document['billing_documents_id']) {
                    foreach ($items as $item) {
                        $item['billing_items_document'] = $document['billing_documents_id'];
                        $this->apilib->create('billing_items', $item);
                    }
                }
            }

            die(json_encode(['status' => 1, 'txt' => base_url("main/layout/billing-documents")]));
        }
    }

    public function getCustomerAddresses()
    {
        $customer_id = $this->input->post('customer_id');

        if (empty($customer_id)) {
            echo json_encode([
                'status' => 0,
                'txt' => 'Customer id not declared'
            ]);
            exit;
        }

        $data = [];

        try {
            $billing = $this->apilib->search('customers_billing_address', ['customers_billing_address_customer_id' => $customer_id]);
            $shipping = $this->apilib->search('customers_shipping_address', ['customers_shipping_address_customer_id' => $customer_id]);

            $data['status'] = 1;
            $data['txt'] = [];
            if (!empty($billing)) {
                $data['txt']['billing'] = $billing;
            }

            if (!empty($shipping)) {
                $data['txt']['shipping'] = $shipping;
            }
        } catch (Exception $e) {
            log_message('error', "Error while fetching customer addresses: E: {$e->getMessage()}");

            $data['status'] = 0;
            $data['txt'] = 'An error has occurred.';
        }

        echo json_encode($data);
        exit;
    }
}
