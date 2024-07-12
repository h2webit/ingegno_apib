<?php
class Stickynotes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->auth->check()) {
            show_404();
            exit;
        }

        $this->user_id = $this->auth->get('users_id');
        
        // questo è un test
    }

    public function get()
    {
        try {
            $notes = $this->apilib->search('sticky_notes', ['sticky_notes_user_id' => $this->user_id]);

            die(json_encode(['status' => 1, 'txt' => $notes]));
        } catch (Exception $e) {
            die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
        }
    }

    public function new()
    {
        $note = $this->input->post('stickynote');

        if (!empty($note)) {
            try {
                $note_data = [
                    'sticky_notes_content' => $note['content'],
                    'sticky_notes_pos_x' => $note['posX'],
                    'sticky_notes_pos_y' => $note['posY'],
                    'sticky_notes_user_id' => $this->user_id,
                    'sticky_notes_uuid' => $note['id'],
                    'sticky_notes_width' => $note['width'],
                    'sticky_notes_height' => $note['height'],
                    'sticky_notes_color' => $note['style']['backgroundcolor']
                ];

                $new_note = $this->apilib->create('sticky_notes', $note_data);

                echo json_encode(['status' => 1, 'txt' => $new_note]);
            } catch (Exception $e) {
                die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
            }
        } else {
            die(json_encode(['status' => 0, 'txt' => 'No data']));
        }
    }

    public function edit()
    {
        $note = $this->input->post('stickynote');

        if (!empty($note)) {
            try {
                $note_data = [
                    'sticky_notes_content' => $note['content'],
                    'sticky_notes_pos_x' => $note['posX'],
                    'sticky_notes_pos_y' => $note['posY'],
                    'sticky_notes_user_id' => $this->user_id,
                    'sticky_notes_uuid' => $note['id'],
                    'sticky_notes_width' => $note['width'],
                    'sticky_notes_height' => $note['height'],
                    'sticky_notes_color' => $note['style']['backgroundcolor']
                ];

                $note_db = $this->apilib->searchFirst('sticky_notes', ['sticky_notes_uuid' => $note['id']]);

                if (!empty($note_db)) {
                    $edit_note = $this->apilib->edit('sticky_notes', $note_db['sticky_notes_id'], $note_data);
                } else {
                    $edit_note = $this->apilib->create('sticky_notes', $note_data);
                }
                echo json_encode(['status' => 1, 'txt' => $edit_note]);
            } catch (Exception $e) {
                die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
            }
        } else {
            die(json_encode(['status' => 0, 'txt' => 'No data']));
        }
    }

    public function delete()
    {
        $note = $this->input->post('stickynote');

        if (!empty($note)) {
            try {
                $note_db = $this->apilib->searchFirst('sticky_notes', ['sticky_notes_uuid' => $note['id']]);

                if (!empty($note_db)) {
                    $edit_note = $this->apilib->delete('sticky_notes', $note_db['sticky_notes_id']);
                }

                echo json_encode(['status' => 1, 'txt' => $edit_note]);
            } catch (Exception $e) {
                die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
            }
        } else {
            die(json_encode(['status' => 0, 'txt' => 'No data']));
        }
    }
}
