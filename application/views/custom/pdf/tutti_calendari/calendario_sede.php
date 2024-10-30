
        <?php
        $layoutEntityData = [];
        $grid = $this->datab->get_grid(91);
        $grid_layout = DEFAULT_LAYOUT_GRID;//$grid['grids']['grids_layout']? : DEFAULT_LAYOUT_GRID;
        $grid_data['data'] = $this->datab->get_grid_data($grid, empty($layoutEntityData) ? $sede['sedi_operative_id'] : ['value_id' => $sede['sedi_operative_id'], 'additional_data' => $layoutEntityData]);
        //echo $this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => $sede['sedi_operative_id'], 'layout_data_detail' => $layoutEntityData), true);
        
        echo $this->load->view('pdf/calendario_sede/calendario_sede_richieste_disponibilita', ['sede' => $sede, 'value_id' => $sede['sedi_operative_id'], 'year' => $year, 'month' => $month], false);
        
        ?>
    