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
