<?php
class Main extends MY_Controller
{
    public function elfinder_init()
    {
        $this->load->helper('path');

        $opts['roots'][] = [
            'driver' => 'LocalFileSystem',
            'path'   => FCPATH,
            'URL'    => base_url(),
            'alias'  => 'Local'
        ];

        $this->load->library('core-file-manager/elfinder_lib', $opts);
    }
}
