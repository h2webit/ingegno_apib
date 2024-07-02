<?php
class Activities extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function saveActivity()
    {
        $data = $this->input->post();

        if ($data['project_activities_type'] == 3) { //note
            unset(
                $data['project_activities_assign_to'],
                $data['project_activities_reminder_type'],
                $data['project_activities_date']
            );
        }

        if (!empty($data['project_activities_project_id']) && !empty($data['project_activities_created_by'])) {
            try {
                $this->apilib->create('project_activities', $data);
                die(json_encode([
                    'status' => 2,
                    'txt' => t('Succesfully saved.'),
                ]));
            } catch (Exception $e) {
                die(json_encode([
                    'status' => 0,
                    'txt' => t('An error has occurred.'),
                ]));
            }
        }
    }

    public function editActivityState($activity_id)
    {
        try {
            $project_activity = $this->apilib->edit('project_activities', $activity_id, [
                'project_activities_done' => DB_BOOL_TRUE,
                'project_activities_done_date' => date('d/m/Y H:i')
            ]);
            die(json_encode([
                'status' => 2,
                'txt' => t('Succesfully saved.'),
            ]));
        } catch (Exception $e) {
            $error = json_encode([
                'status' => 0,
                'error' => 'An error has occurred'
            ]);
            die($error);
        }
    }
}
