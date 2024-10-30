<?php //debug($contents,true); ?>
<!DOCTYPE HTML>
<html>
    <head>

        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="<?php echo base_url_template('template/crm-v2/assets/global/plugins/bootstrap/css/bootstrap.min.css'); ?>">
        <link href='css/bootstrap.css' rel='stylesheet' type='text/css'>
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
        <style>
            html, body, p, * {
                font-family: 'Open Sans', sans-serif; 
                font-size: 11px;
            }
            table {
                width:80%;
                margin: 0 auto;
                
            }
            table td {
                border:1px solid black;
                font-size:1em;
            }
            .center {
                text-align: center;
            }
            .right {
                text-align: right;
            }
            .no-border {
                border: none;
            }
        </style>
    </head>
    <body>
        <?php
            if (!empty($contents)){
                echo implode('', $contents);
            }
        ?>
    </body>
</html>