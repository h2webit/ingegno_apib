<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timbratore INGEGNO SUITE</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/pwa.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/toastr.min.js');?>
<?php $this->layout->addModuleStylesheet('modulo-hr', 'css/toastr.min.css');?>

<script src="<?php echo base_url_scripts("script/lib/moment.min.js"); ?>"></script>

<link rel="manifest" href="<?php echo $this->layout->moduleAssets('modulo-hr', 'pwa/manifest.json'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" crossorigin="anonymous" />
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/scan_badge.js'); ?>

<link rel="icon" type="image/png" href="<?php echo $this->layout->moduleAssets('modulo-hr', 'pwa/icon.png'); ?>">

<style>
* {
    margin: 0;
    padding: 0;
    font-family: 'Inter', sans-serif;
    color: #086FA3;
}

#toast-container .toast {
    opacity: 1;
}

.toast-title,
.toast-message {
    color: #ffffff !important;
}


/* Stile per l'overlay sfocato */
#loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    /* Sfondo sfocato */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    /* Z-index alto per essere sopra il resto del contenuto */
    visibility: hidden;
    /* Inizialmente nascosto */
}


/* Stile per l'icona di caricamento */
#loading {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}


.datetime {
    margin: auto;
    margin-top: 60px;
    width: 60%;
    color: #1D1CE5;
    transition: 0.5s;
    transition-property: background, box-shadow;
}

.date {
    font-size: 40px;
    text-align: center;
}

.time {
    font-size: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.time span {
    position: relative;
    margin: 0 6px;
    text-align: center;
}

#logo {
    display: block;
    width: 200px;
    margin-left: auto;
    margin-right: auto;
}

.welcome {
    text-align: center;
    color: black;
}

#qr-reader {
    position: absolute;
    width: 600px;
    margin-left: auto;
    margin-right: auto;
    display: block;
    border: none;
}

body {
    background: linear-gradient(0deg, #0A90D4 0%, #0A90D4 0%, #9BD2EE 0.01%, #FFFFFF 23.44%), #D9D9D9 !important;
    height: 100vh;

}

#qrcode img {
    margin-left: auto;
    margin-right: auto;
    display: block;
}

.scatola {
    /*background: rgba(25, 25, 25, .1);*/
    color: white;
    font-size: 20px;
    position: fixed;
    top: 5%;
    bottom: 5%;
    left: 5%;
    right: 5%;
    margin-bottom: 50px;
}

#html5-qrcode-anchor-scan-type-change {
    visibility: hidden;
}

#html5-qrcode-button-camera-stop {
    visibility: hidden;
}

#qr-shaded-region {
    border-width: 0px;
}

#nome_reparto {
    font-family: 'Inter', sans-serif;
    font-size: 48px;
    text-transform: uppercase;
    margin-top: 20px;
}

.bold {
    font-weight: bold;
}

/*#openScan {
            width: 350px;
            height: 60px;
            padding-left: 30px;
            padding-right: 30px;
            background: #086FA3 url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAKCAYAAACE2W/HAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAChSURBVHgBnVHbDYMwDHQq/ssGZIOuQCdpu0E3QIzABIgN2ABGYAPYADYIZ2RDlA9eJ13iOOdcnBABzrkUHMFemJIAcSM53s80H9GG2hjzowDIveWAL6ZE8w+6CXXswPhAy5phvYl/7wuoopNuIaZlhKsF/3tKefmPrrVHC75oH6xJwkJGzM4c4AsGz8lK+PRP0UIWcp+liHMUt7JXevpCgxloZEp4+WMF4AAAAABJRU5ErkJggg==);
            background-position: 80px 24px;
            background-repeat: no-repeat;
        }
    
        #openScan span {
            margin-left: 30px;
            color: white;
            font-size: 20px;
        }*/

#zoom {
    max-width: 40%;
}

#qr-reader__scan_region {
    width: 100%;
}

#scanner {
    margin-top: 50px;
}

#html5-qrcode-select-camera {
    display: none;
}

#scanner #qr-reader__scan_region video {
    width: 500px !important;
    display: inline !important;
}

.uppercase {
    text-transform: uppercase;
}

#benvenuto_utente {
    text-align: center;
    padding: 8px;
}

.change_scan {
    position: absolute;
    top: 0px;
    right: 180px;
    width: 40px;
    height: 40px;
    padding: 10px;
    background-color: rgb(8, 111, 163, 0.05);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 3px;
}
.setting {
    position: absolute;
    top: 0px;
    right: 120px;
    width: 40px;
    height: 40px;
    padding: 10px;
    background-color: rgb(8, 111, 163, 0.05);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 3px;
}
#contenitore_refresh {
    position: absolute;
    top: 0px;
    right: 0px;
    width: 40px;
    height: 40px;
    padding: 10px;
    background-color: rgb(8, 111, 163, 0.05);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 3px;
}

.fullscreen_container {
    position: absolute;
    top: 0px;
    right: 60px;
    width: 40px;
    height: 40px;
    padding: 10px;
    background-color: rgb(8, 111, 163, 0.05);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 3px;
}

.modal_qr_title {
    margin-bottom: 20px;
    color: #111111;
}

.btn_save_pausa {
    margin-top: 32px;
    padding-left: 32px;
    padding-right: 32px;
}

#modal_straordinario {
    display: none;
}
#modal_settings {
    display: none;
}

@media (orientation: landscape) {
    #nome_reparto {
        font-size: 40px;
    }

    img.equipment_qr {
        max-width: 300px !important;
    }

    #qr-reader {
        width: 350px !important;
    }

    #scanner #qr-reader__scan_region video {
        width: 350px !important;
    }

    .datetime {
        margin-top: 32px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
    }

    .datetime .date {
        font-size: 50px;
    }

    .datetime .time {
        font-size: 50px;
    }

    #qrcode,
    #scanner {
        width: 50% !important;
        float: left;
    }

    .datetime {
        margin-top: 100px;
        width: 50% !important;
        float: right;
    }
}

.footer {
    font-weight: bold;
    position: fixed;
    bottom: 0;
    width: 100%;
    color: #086FA3;
    /* Colore del testo del footer */
    text-align: center;
    padding: 10px;
    /* Padding del footer, puoi personalizzarlo */
}

.footer img {
    width: 100%;
    max-width: 400px;
}

.welcome {
    margin-top: 40px;
}

/**
* Solo mobile
*/
@media only screen and (max-width: 767px) {

    body {
        background: linear-gradient(0deg, #0A90D4 0%, #0A90D4 0%, #9BD2EE 0.01%, #FFFFFF 50.44%), #D9D9D9 !important;
    }

    img#logo {
        /*margin-top: 50px;*/
        display: none;
    }

    .welcome {
        margin-top: 60px;
    }

    #nome_reparto {
        font-size: 20px;
    }

    .date,
    .time {
        font-size: 32px;
    }

    .footer img {
        max-width: 200px;
    }

    #qr-reader {
        /*position: absolute;*/
        width: 100%;
        /*margin-left: auto;
    margin-right: auto;
    display: block;
    border: none;*/
    }

    #scanner #qr-reader__scan_region video {
        width: 100% !important;
        display: inline !important;
    }
}
</style>


<body>
    <div class="scatola" style="overflow-y:scroll;">
        <img id="logo" src="">
        <div id="change_scan" class="change_scan" style="display:none;">
            <i onclick="openScan()" class='fas fa-camera'></i>
        </div>
        <div id="setting" class="setting">
            <i onclick="openSettings()" class='fas fa-cog'></i>
        </div>
        <div id="contenitore_refresh">
            <a onclick='RicaricaQrcode()'>
                <i id='img_refresh' class='fas fa-sync'></i>
            </a>
        </div>
        <div class="fullscreen_container">
            <i id='toggleFullscreen' class='fas fa-expand'></i>
        </div>
        <audio id="audio">
            <source src="<?php echo $this->layout->moduleAssets('modulo-hr', 'audio/success.mp3'); ?>" type="audio/mpeg">
        </audio>
        <audio id="error">
            <source src="<?php echo $this->layout->moduleAssets('modulo-hr', 'audio/error.mp3'); ?>" type="audio/mpeg">
        </audio>
        <div class="welcome">
            <span id="nome_reparto">
                <span class="bold valore_reparto"></span>
            </span>
        </div>
        <div id="qrcode" style="width:100%;">
            <img id="zoom" src="" class="img-responsive equipment_qr">
        </div>
        <div id="errore_connessione" class="welcome" style="display:none;">
            <h3>Errore, controlla la connessione e ricarica la pagina</h3>
        </div>
        <div id="scanner" style="display:none;width:100%;text-align:center;color:black;">
            <div id="qr-reader"></div>
            <div id="qr-reader-results"></div>
        </div>
        <div class="welcome"></div>
        <div class="datetime">
            <div id="benvenuto_utente" style="display:none;">
                <h3>Benvenuto </h3>
            </div>
            <div id="hideafterscan">
                <div class="date">
                    <span id="dayname">Day</span>
                    <span class="bold" id="daynum">00</span>
                    <span class="bold" id="month">Month</span>
                </div>
                <div class="time">
                    <span class="bold" id="hour">00</span> :
                    <span class="bold" id="minutes">00</span>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <img src="https://crm.h2web.it/uploads/7/f/e/7fe3c8fde52540fa526a29259238f634.png" alt="Logo Footer">
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="modal_straordinario">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close modal_close_custom" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-xs-12">
                                <h4 class="modal_qr_title text-uppercase">Seleziona la pausa effettuata</h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <select class="form-control select2_standard js_select2" name="pausa_dipendente" id="">
                                    <option value="1">0 minuti</option>
                                    <option value="2">30 minuti</option>
                                    <option value="3" selected>60 minuti</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <button id="btn_save_pausa" class="btn btn-success btn_save_pausa text-uppercase">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="modal_settings">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close modal_close_custom" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-xs-12">
                                <h4 class="modal_qr_title text-uppercase">Seleziona la modalità di timbratura</h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <select class="form-control select2_standard js_select2" name="impostazioni_hr_visualizzazione_pwa">
                                    <option value="1">Inquadra qrcode</option>
                                    <option value="2">Scansiona badge</option>
                                    <option value="3">Ibrida</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <button id="btn_save_settings" class="btn btn-success btn_save_pausa text-uppercase">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <div id="loading-overlay">
        <!-- Icona di caricamento -->
        <div id="loading">
            <i class="fa fa-spinner fa-spin"></i> Attendere...
        </div>
    </div>



    <script>
    document.getElementById("btn_save_pausa").addEventListener("click", setPausa);
    document.getElementById("btn_save_settings").addEventListener("click", setSettings);

    const toggleFullscreen = document.getElementById('toggleFullscreen');
    let isFullscreen = false;

    toggleFullscreen.addEventListener(
        'click',
        function() {
            if (isFullscreen) {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
                isFullscreen = false;
            } else {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                }
                isFullscreen = true;
            }
        },
        false
    );

    var html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader", {
            fps: 10,
            qrbox: 500,
            rememberLastUsedCamera: true,
        });
    </script>
</body>

</html>