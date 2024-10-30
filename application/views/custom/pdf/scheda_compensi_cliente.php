<?php //debug($cartella,true);    ?>
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
                font-size: 13px;
            }
            table {
                width:100%;
            }
            table thead tr th {
                /*background: #e4e4e4*/
                
            }
            .dl-horizontal dt {
                overflow: visible;
                text-overflow: none;
                width: auto!important;
            }
            .dl-horizontal dd {
                margin-left: 300px;
            }
            .tg  {border-collapse: collapse;
                  border-spacing:0;
                  margin:0px auto;
            }
            .tg td{font-family:'Open Sans', sans-serif;
                   
                   padding:10px 5px;
                   border-style:solid;border-width:1px;
                   overflow:hidden;
                   word-break:normal;
            }
            .tg th{font-family:'Open Sans', sans-serif;
                   
                   font-weight:normal;
                   padding:6px 3px;
                   border-style:solid;
                   border-width:1px;
                   overflow:hidden;
                   word-break:normal;
            }
            .tg .tg-yw4l{vertical-align:top
            }
            .tg  {border-collapse:collapse;
                  border-spacing:0;
                  border-color: black;
                  background-color: #e4e4e4;
            }
            .tg td{font-family:'Open Sans', sans-serif;
                   
                   padding:6px 3px;
                   border-style:solid;
                   border-width:1px;
                   overflow:hidden;
                   word-break:normal;
                   background-color: #e4e4e4;
            }
            .tg th{font-family:'Open Sans', sans-serif;
                   
                   font-weight:normal;
                   padding:6px 3px;
                   border-style:solid;
                   border-width:1px;
                   overflow:hidden;
                   word-break:normal;
                   background-color: #e4e4e4;
            }
            .tg .tg-msp3{background-color:#ffce93;
                         color:#000000;text-align:center;
                         vertical-align:top;
            }
            .tg .tg-0ord{text-align:right;
                         background-color: #e4e4e4;
            }
            .tg .tg-wqtr{background-color:#ffffc7;
                         color:#000000;
                         text-align:center;
                         vertical-align:top;
            }
            .tg .tg-lqy6{text-align:right;
                         vertical-align:top;
                         background-color: #e4e4e4;
            }
            .tg .tg-7nj3{background-color:#ffce93;
                         color:#000000;text-align:center;
            }
            .tg .tg-pbc0{background-color:#ffce93;
                         color:#000000;
                         text-align:center;
            }
            .tg .tg-slju{
                background-color:#ffce93;
                         color:#000000;
                         text-align:center;
                         vertical-align:top;
            }
            .tg .tg-yw4l{vertical-align:top;
                         background-color: #e4e4e4;

            }
            .tg .tg-xr8r{background-color:#ffffc7;
                         text-align:right;
                         vertical-align:top;
            }
            .tg .tg-kjho{background-color:#ffffc7;
                         vertical-align:top;
            }
            .tg .tg-mejs{color:#000000;
                         vertical-align:top;
                         background-color: #e4e4e4;
            }
            .tg .tg-fl7z{color:#000000;
                         text-align:right;
                         vertical-align:top;
            }

            .red {
                color: red;
            }
            .right {text-align: right;

            }

            .note_per_fattura {
                font-size: 1.6em;
            }
            h2.new_page {page-break-before:always;border:1px solid white;}
            
        </style>
    </head>
    <body>
        <?php echo implode('<h2 class="new_page"></h2>', $contents); ?>
        <?php //echo $content; ?>
    </body>
</html>