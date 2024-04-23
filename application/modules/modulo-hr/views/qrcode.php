<?php
//https://github.com/mebjas/html5-qrcode
if($this->input->get('reparto')){
    $reparto = $this->input->get('reparto');
    $ultimo_qrcode = $this->apilib->searchFirst('qrcode', [
      'qrcode_active' => DB_BOOL_TRUE,
      'qrcode_reparto' => $this->input->get('reparto'),
    ]);
    $qrcode_value = $ultimo_qrcode['qrcode_valore'];
    //nome reparto
    $reparto = $this->apilib->searchFirst('reparti', [
        'reparti_id' => $this->input->get('reparto'),
    ]);
    
    
    if (str_word_count($reparto['reparti_nome']) > 1) {
        $array_nome = explode(' ', $reparto['reparti_nome'], 2);
        $inizio_reparto = $array_nome['0'];
        unset($array_nome[array_search($array_nome['0'],$array_nome)]);
        $fine_reparto = $array_nome;
    } else {
        $inizio_reparto = $reparto['reparti_nome'];
        $fine_reparto = null;
    }

} else {
    //prendo il qrcode generico
    $ultimo_qrcode = $this->apilib->searchFirst('impostazioni_hr');
    if(empty($ultimo_qrcode['impostazioni_hr_qrcode_generico'])){
        $code_random = substr(str_shuffle('123456789'),1,12);
        $this->apilib->edit('impostazioni_hr', $ultimo_qrcode['impostazioni_hr_id'], ['impostazioni_hr_qrcode_generico' => $code_random]);
        $qrcode_value = $code_random;

    } else {
        $qrcode_value = $ultimo_qrcode['impostazioni_hr_qrcode_generico'];
    }
    $inizio_reparto = '';
    $fine_reparto = null;
}

$tempo_check_qrcode ='10000';
$settings = $this->apilib->searchFirst('settings');
$impostazioni_hr = $this->apilib->searchFirst('impostazioni_hr');

if(!empty($impostazioni_hr['impostazioni_hr_time_refresh'])){
  $tempo_check_qrcode = $impostazioni_hr['impostazioni_hr_time_refresh']*1000;
}

$logo = base_url_uploads("uploads/{$settings['settings_company_logo']}");

$print_link = $qrcode_value;
//$url = "https://chart.googleapis.com/chart?chs=512x512&cht=qr&chl={$print_link}&choe=UTF-8";
$url = "https://quickchart.io/qr?text=".$print_link."&size=512";


?>
<!--
<script src="https://unpkg.com/html5-qrcode"></script>
-->
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/scan_badge.js'); ?>
<style>
* {
    margin: 0;
    padding: 0;
    font-family: 'Inter', sans-serif;
    color: #086FA3;

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
    width: 200px;
    margin-left: auto;
    margin-right: auto;
}

.welcome {
    text-align: center;
    color: black;
}

#qr-reader {
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

/*#benvenuto_utente {
        margin-left: 100px;
        margin-right: 100px;
        padding-top: 10px;
        padding-bottom: 10px;
        border-radius: 3px;
        margin-bottom: 20px;
    }*/
.change_scan {
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

@media (orientation: landscape) {
    #nome_reparto {
        font-size: 24px;
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
        font-size: 40px;
    }

    .datetime .time {
        font-size: 40px;
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

.qr_footer {
    background: fixed;
    position: absolute;
    bottom: -12px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
}

.qr_footer .logo_ingegno_qr {
    max-width: 220px;
}
</style>


</head>

<body onload="initClock()">
    <div class="scatola" style="overflow-y:scroll;">
        <img id="logo" src="<?php echo $logo; ?>" class="img-responsive">
        <div class="change_scan">
            <i onclick="openScan()" class='fas fa-camera'></i>
        </div>
        <div id="contenitore_refresh">
            <a onclick='RicaricaQrcode()'>
                <i id='img_refresh' class='fas fa-sync'></i>
            </a>
        </div>
        <div class="fullscreen_container">
            <i id='toggleFullscreen' class='fas fa-expand'></i>
        </div>
        <!--
        <div id="button_zoom">
        <button class="button_zoom" id="zoomOut"> - </button>
        <button class="button_zoom" id="zoomIn">+</button>
        </div>
        -->
        <audio id="audio">
            <source src="<?php echo $this->layout->moduleAssets('modulo-hr', 'audio/success.mp3'); ?>" type="audio/mpeg">
        </audio>
        <div style="margin-top:40px;" class="welcome">
            <span id="nome_reparto">
                <span class="bold"><?php echo $inizio_reparto; ?></span>
                <?php
                if(isset($fine_reparto)):
                foreach($fine_reparto as $nome):
                    echo $nome." ";
                endforeach;
                endif;
                ?>
            </span>
        </div>
        <div id="qrcode" style="width:100%;">
            <img id="zoom" src="<?php echo $url; ?>" class="img-responsive equipment_qr">
        </div>
        <div id="errore_connessione" class="welcome" style="display:none;">
            <h3>Errore, controlla la connessione e ricarica la pagina</h3>
        </div>
        <div id="scanner" style="display:none;width:100%;text-align:center;color:black;">
            <div id="qr-reader" style="position:absolute;width:600px"></div>
            <div id="qr-reader-results"></div>
        </div>
        <div class="welcome">


            <!-- Button trigger modal 
            <button type="button" class="btn btn-primary" onclick="openScan()" id="openScan"><span>Scansione badge</span></button>-->
        </div>
        <!--digital clock start-->
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
                    <span class="bold" id="hour">00</span>.
                    <span class="bold" id="minutes">00</span>
                </div>
            </div>
        </div>
        <!--digital clock end-->
        <!-- tolto il 25 gennaio
        <div class="welcome">
            <div id="benvenuto_utente" style="display:none;"><h3>Benvenuto </h3></div>
        </div> -->
        <section class="qr_footer">
            <img src="<?php echo $this->layout->moduleAssets('modulo-hr', 'images/logo_ingegno.png'); ?>" alt="Logo INGEGNO" class="img-responsive logo_ingegno_qr">
        </section>
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
                                <button class="btn btn-success btn_save_pausa text-uppercase">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <!-- Overlay sfocato -->
    <div id="loading-overlay">
        <!-- Icona di caricamento -->
        <div id="loading">
            <i class="fa fa-spinner fa-spin"></i> Attendere..
        </div>
    </div>

</body>
<script>
$(function() {
    $('[name="pausa_dipendente"]').select2();
})

window.addEventListener('load', function() {
    readTag();
});

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


//inizializzo lettore qr
const urlParams = new URLSearchParams(window.location.search);
let reparto = urlParams.get('reparto');

if (reparto === null) {
    // Assegna un valore predefinito a reparto solo se è null
    reparto = '<?php echo $qrcode_value; ?>';
}

//qua se è vuoto, prendo il qrcode generico!
var html5QrcodeScanner = new Html5QrcodeScanner(
    "qr-reader", {
        fps: 10,
        qrbox: 500,
        rememberLastUsedCamera: true,
    });
var utilizzoQrcode = 1;


var dipendente_id = null;
var presenza_id = null;

/* function setDipendentePausa(dipendente, presenza_id) {
    console.log(dipendente, presenza);
    if (dipendente && presenza) {
        $.ajax({
            url: base_url + `modulo-hr/qrcode/impostaPausa/${presenza_id}`,
            dataType: 'json',
            data: {
                reparto: reparto,
            },
            success: function(data) {
                //location.reload();
                console.log(data);
}
});
}
else {
    alert("Dipentente e/o presenza non riconosciuti, contattare l'assistenza.");
    return;
}
}*/

//Blocco chiusura modale se non ho inviato la pausa
$('.modal_close_custom').on('click', function() {
    if (!presenza_id || dipendente_id) {
        return false;
    }
})


$('.btn_save_pausa').on('click', function() {
    const selected_pausa = $('[name="pausa_dipendente"]').find(':selected').val();
    if (selected_pausa.length == 0 || typeof(selected_pausa) === 'undefined' || !selected_pausa) {
        alert('Devi selezionare la pausa prima di salvare');
        return;
    }
    //console.log(selected_pausa);

    //Modifico presenza inserendo la pausa scelta
    if (dipendente_id && presenza_id) {
        $.ajax({
            method: "POST",
            url: base_url + `modulo-hr/qrcode/impostaPausa/`,
            dataType: 'json',
            data: {
                dipendente_id: dipendente_id,
                presenza_id: presenza_id,
                pausa: selected_pausa,
            },
            success: function(data) {
                //console.log(data);
                document.getElementById("benvenuto_utente").style.display = "block";
                if (data.status === 1) {
                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                    document.getElementById('audio').play();
                    document.getElementById("benvenuto_utente").innerHTML = "<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" + data.txt + "</span> " + data.data.dipendenti_nome + " " + data.data.dipendenti_cognome + "</h3>";
                } else {
                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                    document.getElementById("benvenuto_utente").innerHTML = "<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
                }
                $('#modal_straordinario').modal('hide');
                setTimeout(function() {
                    document.getElementById('benvenuto_utente').style.display = "none"
                }, 5000);
            },
            error: function() {
                $('#modal_straordinario').modal('hide');
                document.getElementById("benvenuto_utente").style.display = "none";
                reinizialize();
            },
        });
    } else {
        $('#modal_straordinario').modal('hide');
        alert("Dipentente e/o presenza non riconosciuti, contattare l'assistenza.");
        return;
    }
});

function onScanSuccess(decodedText, decodedResult) {
    $('#loading-overlay').css('visibility', 'visible');
    // Handle on success condition with the decoded text or result.
    //console.log(`Scan result: ${decodedText}`, decodedResult);
    html5QrcodeScanner.clear();
    // ^ this will stop the scanner (video feed) and clear the scan area.
    if (decodedText) {
        $.ajax({
            url: base_url + 'modulo-hr/qrcode/scansiona_badge/' + decodedText + '/' + reparto,

            dataType: 'json',
            success: function(data) {
                $('#loading-overlay').css('visibility', 'hidden');
                //console.log(data);
                //document.getElementById("benvenuto_utente").style.display = "block";
                if (data.status === 1) {
                    if (data.tipo === 'uscita' && data.data.dipendenti_ignora_orari_lavoro === '1' && data.data.dipendenti_ignora_pausa === '0') {
                        dipendente_id = data.data.presenze_dipendente;
                        presenza_id = data.data.presenze_id;
                        //console.log(dipendente_id, presenza_id)
                        //apertura modale per scelta pausa
                        $('#modal_straordinario').modal({
                            backdrop: 'static'
                        })
                        $('#modal_straordinario').modal('show');
                    } else {

                        document.getElementById("benvenuto_utente").style.display = "block";
                        document.getElementById("hideafterscan").style.display = "none";
                        document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                        document.getElementById('audio').play();
                        document.getElementById("benvenuto_utente").innerHTML = "<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" + data.txt + "</span> " + data.data.dipendenti_nome + " " + data.data.dipendenti_cognome + "</h3>";
                    }
                } else {
                    document.getElementById("hideafterscan").style.display = "none";
                    document.getElementById("benvenuto_utente").style.display = "block";
                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                    document.getElementById("benvenuto_utente").innerHTML = "<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
                }
                setTimeout(function() {
                    document.getElementById("hideafterscan").style.display = "block";
                    document.getElementById('benvenuto_utente').style.display = "none";
                }, 5000);
                reinizialize();
            },
            error: function() {
                $('#loading-overlay').css('visibility', 'hidden');
                document.getElementById("hideafterscan").style.display = "block";
                document.getElementById("benvenuto_utente").style.display = "none";
                reinizialize();
            },
        });
    }
}

function reinizialize() {
    html5QrcodeScanner.render(onScanSuccess);
    // console.log(document.getElementById("qr-reader__dashboard_section"));
    // document.getElementById("qr-reader__dashboard_section").style.display = 'none';
    document.getElementById("errore_connessione").style.display = 'none';
    //document.getElementById("qr-shaded-region").style.border-width = 'none';    
    //document.getElementById("html5-qrcode-anchor-scan-type-change");
    //console.log(document.getElementById("html5-qrcode-anchor-scan-type-change"));
    //document.getElementById("html5-qrcode-anchor-scan-type-change").style.display = 'none';

}

//html5QrcodeScanner.render(onScanSuccess);
//fine inizializzazione lettore qr

function openScan() {
    var scan = document.getElementById("scanner");
    var qrcode = document.getElementById("qrcode");
    if (scan.style.display === "none") {
        scan.style.display = "block";
        reinizialize();
        /*document.getElementById("openScan").style.marginTop = '10px';
        document.getElementById("openScan").innerHTML = "<span>Scansiona Qrcode</span>";*/
        //document.getElementByTagName("video").style.display = 'inline';

        //ogni 10 minuti rinizializzo perchè ogni tanto si bloccava.
        setInterval(function() {
            reinizialize();
        }, 1000000);
    } else {
        scan.style.display = "none";
        /*document.getElementById("openScan").style.marginTop = '0px';
        document.getElementById("openScan").innerHTML = "<span>Scansione badge</span>";*/
    }
    if (qrcode.style.display === "none") {
        utilizzoQrcode = 1;
        qrcode.style.display = "block";
    } else {
        qrcode.style.display = "none";
        utilizzoQrcode = 0;
    }
}

function RicaricaQrcode() {
    $('#loading-overlay').css('visibility', 'visible');
    $.ajax({
        url: base_url + 'modulo-hr/qrcode/generate/1',
        dataType: 'json',
        data: {
            reparto: reparto,
        },
        complete: function() {
            // Questa parte verrà eseguita indipendentemente dal successo o dall'insuccesso dell'operazione AJAX
            location.reload();
        },
        error: function(xhr, status, error) {
            // Gestisci eventuali errori qui, se necessario
            $('#loading-overlay').css('visibility', 'hidden');
            console.error(error);
        }
    });
}

function updateClock() {
    /*
    moment.locale('it');
   // console.log(moment().format('DD/MM/YYYY'));
    const giorno = moment().format('DD MMM');
    document.getElementById('dayname').innerText=giorno;
    console.log(giorno);*/

    var now = new Date();
    var dname = now.getDay(),
        mo = now.getMonth(),
        dnum = now.getDate(),
        //yr = now.getFullYear(),
        hou = now.getHours(),
        min = now.getMinutes(),
        pe = "AM";


    Number.prototype.pad = function(digits) {
        for (var n = this.toString(); n.length < digits; n = 0 + n);
        return n;
    }

    //var months = ["Gennaio", "February", "March", "April", "May", "June", "July", "Augest", "September", "October", "November", "December"];
    //var week = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    var week = ['DOM', 'LUN', 'MAR', 'MER', 'GIO', 'VEN', 'SAB'];
    var months = ['GEN', 'FEB', 'MAR', 'APR', 'MAG', 'GIU', 'LUG', 'AGO', 'SET', 'OTT', 'NOV', 'DIC'];
    var ids = ["dayname", "month", "daynum", "hour", "minutes"];
    //var values = [week[dname], months[mo], dnum.pad(2), yr, hou.pad(2), min.pad(2), pe];
    var values = [week[dname], months[mo], dnum.pad(2), hou.pad(2), min.pad(2), pe];
    for (var i = 0; i < ids.length; i++)
        document.getElementById(ids[i]).firstChild.nodeValue = values[i];

}
setInterval(function() {
    updateClock();
}, 1000);

function initClock() {
    updateClock();
    //window.setInterval("updateClock()", 1);
}
var attuale = <?php echo $qrcode_value; ?>;
var tempo_check_qrcode = <?php echo $tempo_check_qrcode; ?>;

function checkValideQrcode() {
    if (utilizzoQrcode === 1) {
        $.ajax({
            url: base_url + 'modulo-hr/qrcode/checkValidita/' + attuale,
            dataType: 'json',
            success: function(data) {
                if (utilizzoQrcode === 1) {
                    document.getElementById("qrcode").style.display = "block";
                    document.getElementById("errore_connessione").style.display = "none";
                    if (data.data.qrcode_active != 1) {
                        location.reload();
                    }
                }
            },
            error: function() {
                if (utilizzoQrcode === 1) {
                    document.getElementById("qrcode").style.display = "none";
                    document.getElementById("errore_connessione").style.display = "block";
                }
            },
        });
    }
}
setInterval(function() {
    checkValideQrcode();
}, tempo_check_qrcode);
/*
var zoom = 0.8;
var zoomStep = 0.2;
var max_zoom = 1.6;
var min_zoom = 0.6;
document.getElementById("zoomIn").addEventListener("click", function() {
  zoom += zoomStep;
  if (max_zoom > zoom) {
    document.getElementById("zoom").style.transform = "scale(" + zoom + ")";
  } else {
    zoom -= zoomStep;
  }
});
document.getElementById("zoomOut").addEventListener("click", function() {
  zoom -= zoomStep;
  if (min_zoom < zoom) {
    document.getElementById("zoom").style.transform = "scale(" + zoom + ")";
  } else {
    zoom += zoomStep;
  }
});*/
let isRequestInProgress = false;

async function readTag() {
    let isRequestPending = false;

    if ("NDEFReader" in window) {
        const ndef = new NDEFReader();
        // controlla se c'è già una richiesta in sospeso
        if (isRequestPending) {
            console.log('Richiesta già in sospeso, attendere...');
            return;
        }
        // altrimenti, imposta lo stato della richiesta come 'in sospeso'
        isRequestPending = true;

        try {
            await ndef.scan();
            ndef.onreading = event => {
                const decoder = new TextDecoder();
                for (const record of event.message.records) {
                    /*consoleLog("Record type:  " + record.recordType);
                    consoleLog("MIME type:    " + record.mediaType);
                    consoleLog("Qr:\n" + decoder.decode(record.data));
                    consoleLog("Reparto:\n" + reparto);*/
                    if (decoder.decode(record.data)) {
                        $.ajax({
                            url: base_url + 'modulo-hr/qrcode/scansiona_badge/' + decoder.decode(record.data) + '/' + reparto,

                            dataType: 'json',
                            success: function(data) {
                                document.getElementById("benvenuto_utente").style.display = "block";
                                if (data.status === 1) {
                                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";

                                    document.getElementById('audio').play();
                                    document.getElementById("benvenuto_utente").innerHTML = "<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" + data.txt + "</span> " + data.data.dipendenti_nome + " " + data.data.dipendenti_cognome + "</h3>";
                                } else {

                                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                                    document.getElementById("benvenuto_utente").innerHTML = "<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
                                }
                                setTimeout(function() {
                                    document.getElementById('benvenuto_utente').style.display = "none"
                                }, 5000);
                            },
                            error: function() {
                                document.getElementById("benvenuto_utente").style.display = "none";
                            },
                        });
                    }
                }
            }
        } catch (error) {
            console.log(error);
        }
        // alla fine della richiesta, attendi un secondo e reimposta lo stato della variabile booleana
        setTimeout(() => {
            isRequestPending = false;
        }, 500);
    } else {
        console.log("Web NFC is not supported.");
    }
}
</script>