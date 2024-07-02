<?php


class Support_table extends MY_Controller
{
    public function __construct()
    {

        parent::__construct();
    }

    public function getSupportGrid($grid_id)
    {
        if (empty($grid_id)) {
            return false;
        }

        $grid = $this->db
            ->where('grids_id', $grid_id)
            ->join('entity', 'entity_id = grids_entity_id', 'LEFT')
            ->limit(1)
            ->get('grids')
            ->row_array();

        $fooBox = [
            'layouts_boxes_layout' => null,
            'layouts_boxes_content_type' => 'grid',
            'layouts_boxes_content_ref' => $grid['grids_id'],
        ];
        $html = $this->datab->getBoxContent($fooBox);

        echo $html;
    }
}