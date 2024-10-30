<!DOCTYPE HTML>
<html>
    <head>

        <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="<?php echo base_url_template('template/crm-v2/assets/global/plugins/bootstrap/css/bootstrap.min.css'); ?>">
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
        <style>
            html, body, p, * {
                font-family: 'Open Sans', sans-serif;
                font-size:0.8em;
            }
        </style>
    </head>
    <body>
        <?php
        
        echo $this->load->view('pdf/calendario_sede/calendario_sede_richieste_disponibilita', ['sede' => $sede, 'value_id' => $sede['projects_id']], false);
        
        ?>
    </body>
</html>