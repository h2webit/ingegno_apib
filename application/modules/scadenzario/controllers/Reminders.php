<?php

class Reminders extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function deleteScadenzeFuture($id)
    {
        //$this->logAction(__FUNCTION__, func_get_args());
        $reminder = $this->apilib->view('reminders', $id);

        if (empty($reminder['reminders_generated_from'])) {
            $reminders = $this->apilib->search('reminders', ['reminders_generated_from' => $id]);
            $reminders[] = $reminder;
        } else {
            $reminders = $this->apilib->search('reminders', [
                'reminders_generated_from' => $reminder['reminders_generated_from'],
                "reminders_date >= '{$reminder['reminders_date']}'"
            ]);

            $id = $reminder['reminders_generated_from'];
        }

        //dd($reminders);

        foreach ($reminders as $reminder) {
            try {
                $this->apilib->delete('reminders', $reminder['reminders_id']);
            } catch (ApiException $e) {
                $this->showError($e->getMessage(), $e->getCode());
            }
        }

        echo json_encode(array(
            'status' => 0,
            'message' => null,
            'data' => []
        ));
    }
    
    public function filtra_categoria($categoria_id = null) {
        if (empty($categoria_id)) {
            e_json(['status' => 3, 'txt' => t('Category not declared')]);
            return;
        }
        
        $categoria = $this->apilib->view('reminder_categories', $categoria_id);
        
        if (empty($categoria)) {
            e_json(['status' => 3, 'txt' => t('Category not found')]);
            return;
        }
        
        $field_id = $this->db->where('fields_name', 'reminders_category')->get('fields')->row()->fields_id;

        $sessione = $this->session->userdata(SESS_WHERE_DATA);
        
        $sessione['filter_reminders'][$field_id] = [
            'field_id' => $field_id,
            'operator' => 'eq',
            'value' => $categoria_id,
        ];
        
        $this->session->set_userdata(SESS_WHERE_DATA, $sessione);
        
        e_json(['status' => 2, 'txt' => t('Filter applied')]);
    }
    
    private function jsonResponse($status = 0, $data = null, $die = false)
    {
        echo json_encode([
            'status' => $status,
            'txt' => $data
        ]);

        if ($die) {
            exit;
        }
    }
}
