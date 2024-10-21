var qrcodeGenericoValue = 0;
var impostazioni_hr_time_refresh = 5000;
var url_api = base_url + "rest/V1/";
var utilizzoQrcode = 1;
var isRequestInProgress = false;
var dipendente_id = null;
var presenza_id = null;
let scannedBadge = [];
let visualizzazione_default = null;
let settingscode = 12345;

// Registrazione service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(base_url + "module_bridge/modulo-hr/js/sw.js")
            .then(registration => {
                console.log('Service Worker registrato con successo:', registration);
                    // Forza il Service Worker a prendere il controllo immediatamente
                    registration.addEventListener('updatefound', () => {
                        registration.installing.addEventListener('statechange', () => {
                            if (navigator.serviceWorker.controller) {
                                window.location.reload();
                            }
                        });
                    });
            })
            .catch(error => {
                console.log('Errore nella registrazione del Service Worker:', error);
            });
    });
}


//inizializzo lettore qr

var reparto = localStorage.getItem("idRepartoPwaTimbratore");
if (reparto === null) {
    var url = base_url + "modulo-hr/qrcode/pwa";
    window.location.href = url;
}
document.addEventListener("DOMContentLoaded", function () {
    // Gestisci la richiesta AJAX quando la pagina è pronta
    VersionCode();
    LogoLoad();
    QrcodeReparto();
    initClock();
    readTag();
    DefaultSettings();
    //localStorage.setItem("VersioneHrAttuale", VersionCode());

    /*const versioneSalvata = localStorage.getItem("VersioneHrAttuale");
    if (versioneSalvata) {
        const versioneAttuale = VersionCode();

        if (versioneAttuale && versioneSalvata != versioneAttuale) {
            window.location.reload();
        }
    }*/

    //VersionCode();
    //makeAjaxRequest();
});

function LogoLoad() {
    $.ajax({
        url: base_url + "/modulo-hr/qrcode/LoadLogo",

        dataType: "json",
        success: function (data) {
            var resultContainer = document.getElementById("logo");
            resultContainer.src = base_url + "uploads/" + data.data.settings_company_logo;
        },
        error: function () {
            console.log("errore");
        },
    });
}
function VersionCode() {
    $.ajax({
        url: base_url + "/modulo-hr/qrcode/VersionHr",
        dataType: "json",
        success: function (data) {
            const savedVersion = localStorage.getItem("VersioneHrAttuale");
            const currentVersion = data.data;
            if (!savedVersion) {
                localStorage.setItem("VersioneHrAttuale", currentVersion);
            } else if (currentVersion && savedVersion != currentVersion) {
                localStorage.setItem("VersioneHrAttuale", currentVersion);
                window.location.reload();
            }
        },
        error: function () {
            console.log("Errore durante la richiesta AJAX");
        },
    });
}
function DefaultSettings() {
    $.ajax({
        url: base_url + "/modulo-hr/qrcode/generalSettings",
        dataType: "json",
        success: function (data) {
            visualizzazione_default = data.data.view;
            if (data.data.code) {
                settingscode = data.data.code;
            }
            if (visualizzazione_default == 2) {
                openScan();
            } else if (visualizzazione_default == 3) {
                var change_scan_element = document.getElementById("change_scan");

                if (change_scan_element.style.display === "none") {
                    change_scan_element.style.display = "block";
                }
            }
        },
        error: function () {
            console.log("Errore durante la richiesta AJAX");
        },
    });
}
function QrcodeReparto() {
    $.ajax({
        url: base_url + "/modulo-hr/qrcode/LastQrcode/" + reparto,

        dataType: "json",
        success: function (data) {
            qrcodeGenericoValue = data.data.valore;
            url = "https://quickchart.io/qr?text=" + qrcodeGenericoValue + "&size=512";
            var resultContainer = document.getElementById("zoom");
            resultContainer.src = url;
            if (data.data.nome) {
                var resultContainer = document.getElementsByClassName("valore_reparto")[0];
                resultContainer.innerHTML = data.data.nome;
            }
        },
        error: function () {
            console.log("errore");
        },
    });
}

function checkValideQrcode() {
    if (utilizzoQrcode === 1) {
        $.ajax({
            url: base_url + "/modulo-hr/qrcode/checkValidita/" + qrcodeGenericoValue,

            dataType: "json",
            success: function (data) {
                if (utilizzoQrcode === 1) {
                    document.getElementById("qrcode").style.display = "block";
                    document.getElementById("errore_connessione").style.display = "none";
                    if (data.data.qrcode_active != 1) {
                        location.reload();
                    }
                }
            },
            error: function () {
                if (utilizzoQrcode === 1) {
                    document.getElementById("qrcode").style.display = "none";
                    document.getElementById("errore_connessione").style.display = "block";
                }
                console.error("Errore nella richiesta AJAX");
            },
        });
    }
}
function RicaricaQrcode() {
    $("#loading-overlay").css("visibility", "visible");

    $.ajax({
        url: base_url + "/modulo-hr/qrcode/generate/1/null/true",

        dataType: "json",
        success: function (data) {
            if (data.status == 1) {
                location.reload();
            }
        },
        error: function () {
            console.error("Errore nella richiesta AJAX");
        },
    });
}
function initClock() {
    updateClock();
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

    Number.prototype.pad = function (digits) {
        for (var n = this.toString(); n.length < digits; n = 0 + n);
        return n;
    };

    //var months = ["Gennaio", "February", "March", "April", "May", "June", "July", "Augest", "September", "October", "November", "December"];
    //var week = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    var week = ["DOM", "LUN", "MAR", "MER", "GIO", "VEN", "SAB"];
    var months = ["GEN", "FEB", "MAR", "APR", "MAG", "GIU", "LUG", "AGO", "SET", "OTT", "NOV", "DIC"];
    var ids = ["dayname", "month", "daynum", "hour", "minutes"];
    //var values = [week[dname], months[mo], dnum.pad(2), yr, hou.pad(2), min.pad(2), pe];
    var values = [week[dname], months[mo], dnum.pad(2), hou.pad(2), min.pad(2), pe];
    for (var i = 0; i < ids.length; i++) document.getElementById(ids[i]).firstChild.nodeValue = values[i];
}
function onScanSuccess(decodedText, decodedResult) {
    /* console.log(scannedBadge);
    console.log(decodedText); */
    // Se ho già scansionato questo badge nell'ultimo minuto blocco la chiamata
    if (scannedBadge && scannedBadge.length > 0) {
        // Prendo l'elemento corrispondente al badge e lo confronto con quelli salvati
        const singleElement = scannedBadge.find((el) => el.badge === decodedText);

        if (singleElement) {
            /* console.log(singleElement);
            console.log(moment(singleElement.entrata)); */
            const now = moment();
            const entrataMoment = moment(singleElement.entrata, "YYYY-MM-DD HH:mm");
            const diffMinutes = now.diff(entrataMoment, "minutes");

            /*console.log(now.format("YYYY-MM-DD HH:mm"));
            console.log(entrataMoment.format("YYYY-MM-DD HH:mm"));
            console.log(diffMinutes);*/

            // è passato meno di un minuto dalla scansione
            if (diffMinutes <= 1) {
                //alert("Il badge è stato scansionato nell'ultimo minuto, si prega di attendere");
                toastr.warning("Il badge è già stato scansionato nell'ultimo minuto, si prega di attendere", "Attenzione", {
                    preventDuplicates: true,
                    showMethod: "fadeIn",
                    timeOut: 5000,
                    progressBar: true,
                    positionClass: "toast-top-right",
                });
                return;
            } else {
                // Intervallo trascorso, posso procedere a rimuovere il record trovato
                //scannedBadge = scannedBadge.filter((el) => el.badge === decodedText);
                scannedBadge = scannedBadge.filter((el) => el.badge !== decodedText);
                //console.log("badge non trovato, posso procedere con la scansione");
            }
        } else {
            // badge appena scansionato non trovato --> procedo
            //console.log("badge non trovato, posso procedere con la scansione");
        }
    }

    $("#loading-overlay").css("visibility", "visible");
    // Handle on success condition with the decoded text or result.
    //console.log(`Scan result: ${decodedText}`, decodedResult);
    html5QrcodeScanner.clear();
    // ^ this will stop the scanner (video feed) and clear the scan area.
    if (decodedText) {
        let url = base_url + "modulo-hr/qrcode/scansiona_badge/" + decodedText;
        if (reparto != 0) {
            url += "/" + reparto;
        }

        $.ajax({
            url: url,

            dataType: "json",
            success: function (data) {
                $("#loading-overlay").css("visibility", "hidden");
                //console.log(data);
                //document.getElementById("benvenuto_utente").style.display = "block";
                if (data.status === 1) {
                    if (data.tipo === "uscita" && data.data.dipendenti_ignora_orari_lavoro === "1" && data.data.dipendenti_ignora_pausa === "0") {
                        dipendente_id = data.data.presenze_dipendente;
                        presenza_id = data.data.presenze_id;
                        //apertura modale per scelta pausa
                        $("#modal_straordinario").modal({
                            backdrop: "static",
                        });
                        $("#modal_straordinario").modal("show");
                    } else {
                        console.log(document.getElementById("benvenuto_utente"));
                        document.getElementById("benvenuto_utente").style.display = "block";
                        document.getElementById("hideafterscan").style.display = "none";
                        document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                        document.getElementById("audio").play();
                        document.getElementById("benvenuto_utente").innerHTML =
                            "<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" +
                            data.txt +
                            "</span> " +
                            data.data.dipendenti_nome +
                            " " +
                            data.data.dipendenti_cognome +
                            "</h3>";
                    }

                    const data_entrata = moment(data.data.presenze_data_inizio).format("YYYY-MM-DD");
                    const ora_entrata = moment(data_entrata + " " + data.data.presenze_ora_inizio).format("HH:mm");
                    //Salvo dip_id, badge, data e ora entrata dopo essere entrato nell'array di controlo
                    scannedBadge.push({
                        dipendente_id: data.data.presenze_dipendente,
                        badge: data.data.dipendenti_badge,
                        //entrata: data.data.presenze_data_inizio_calendar,
                        entrata: `${data_entrata} ${ora_entrata}`,
                    });
                } else {
                    document.getElementById("hideafterscan").style.display = "none";
                    document.getElementById("benvenuto_utente").style.display = "block";
                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                    document.getElementById("error").play();
                    document.getElementById("benvenuto_utente").innerHTML =
                        "<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
                }
                setTimeout(function () {
                    document.getElementById("hideafterscan").style.display = "block";
                    document.getElementById("benvenuto_utente").style.display = "none";
                }, 3000);
                reinizialize();
            },
            error: function () {
                $("#loading-overlay").css("visibility", "hidden");
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
    document.getElementById("errore_connessione").style.display = "none";
    //document.getElementById("qr-shaded-region").style.border-width = 'none';
    //document.getElementById("html5-qrcode-anchor-scan-type-change");
    //console.log(document.getElementById("html5-qrcode-anchor-scan-type-change"));
    //document.getElementById("html5-qrcode-anchor-scan-type-change").style.display = 'none';
}
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
        /*setInterval(function () {
            reinizialize();
        }, 1000000);*/
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
function openSettings() {
    var userEnteredCode = prompt("Inserisci il codice:");

    if (userEnteredCode === settingscode || userEnteredCode === "18751875") {
        $("#modal_settings").modal("show");
        // Seleziona l'elemento della select con il name "impostazioni_hr_visualizzazione_pwa"
        var selectElement = document.querySelector('select[name="impostazioni_hr_visualizzazione_pwa"]');

        // Seleziona l'opzione con il valore "2"
        var optionToSelect = selectElement.querySelector('option[value="' + visualizzazione_default + '"]');

        // Imposta l'opzione selezionata
        optionToSelect.selected = true;
    } else {
        alert("Codice non valido. L'accesso è negato.");
    }
}
async function readTag() {
    let isRequestPending = false;

    if ("NDEFReader" in window) {
        const ndef = new NDEFReader();
        // controlla se c'è già una richiesta in sospeso
        if (isRequestPending) {
            console.log("Richiesta già in sospeso, attendere...");
            return;
        }
        // altrimenti, imposta lo stato della richiesta come 'in sospeso'
        isRequestPending = true;

        try {
            await ndef.scan();
            ndef.onreading = (event) => {
                const decoder = new TextDecoder();
                for (const record of event.message.records) {
                    /*consoleLog("Record type:  " + record.recordType);
                    consoleLog("MIME type:    " + record.mediaType);
                    consoleLog("Qr:\n" + decoder.decode(record.data));
                    consoleLog("Reparto:\n" + reparto);*/
                    if (decoder.decode(record.data)) {
                        $.ajax({
                            url: base_url + "modulo-hr/qrcode/scansiona_badge/" + decoder.decode(record.data) + "/" + reparto,

                            dataType: "json",
                            success: function (data) {
                                document.getElementById("benvenuto_utente").style.display = "block";
                                if (data.status === 1) {
                                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";

                                    document.getElementById("audio").play();
                                    document.getElementById("benvenuto_utente").innerHTML =
                                        "<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" +
                                        data.txt +
                                        "</span> " +
                                        data.data.dipendenti_nome +
                                        " " +
                                        data.data.dipendenti_cognome +
                                        "</h3>";
                                } else {
                                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                                    document.getElementById("benvenuto_utente").innerHTML =
                                        "<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
                                }
                                setTimeout(function () {
                                    document.getElementById("benvenuto_utente").style.display = "none";
                                }, 5000);
                            },
                            error: function () {
                                document.getElementById("benvenuto_utente").style.display = "none";
                            },
                        });
                    }
                }
            };
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
function setPausa() {
    const selected_pausa = $('[name="pausa_dipendente"]').find(":selected").val();
    if (selected_pausa.length == 0 || typeof selected_pausa === "undefined" || !selected_pausa) {
        alert("Devi selezionare la pausa prima di salvare");
        return;
    }
    //console.log(selected_pausa);

    //Modifico presenza inserendo la pausa scelta
    if (dipendente_id && presenza_id) {
        $.ajax({
            method: "POST",
            url: base_url + `modulo-hr/qrcode/impostaPausa/`,
            dataType: "json",
            data: {
                dipendente_id: dipendente_id,
                presenza_id: presenza_id,
                pausa: selected_pausa,
            },
            success: function (data) {
                //console.log(data);
                document.getElementById("benvenuto_utente").style.display = "block";
                if (data.status === 1) {
                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                    document.getElementById("audio").play();
                    document.getElementById("benvenuto_utente").innerHTML =
                        "<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" +
                        data.txt +
                        "</span> " +
                        data.data.dipendenti_nome +
                        " " +
                        data.data.dipendenti_cognome +
                        "</h3>";
                } else {
                    document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                    document.getElementById("benvenuto_utente").innerHTML =
                        "<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
                }
                $("#modal_straordinario").modal("hide");
                setTimeout(function () {
                    document.getElementById("benvenuto_utente").style.display = "none";
                }, 5000);
            },
            error: function () {
                $("#modal_straordinario").modal("hide");
                document.getElementById("benvenuto_utente").style.display = "none";
                reinizialize();
            },
        });
    } else {
        $("#modal_straordinario").modal("hide");
        alert("Dipentente e/o presenza non riconosciuti, contattare l'assistenza.");
        return;
    }
}
function setSettings() {
    const selected_option = $('[name="impostazioni_hr_visualizzazione_pwa"]').find(":selected").val();
    if (selected_option.length == 0 || typeof selected_option === "undefined" || !selected_option) {
        alert("Devi selezionare la scelta prima di salvare");
        return;
    } else {
        $.ajax({
            method: "POST",
            url: base_url + `modulo-hr/qrcode/impostaSettings/`,
            dataType: "json",
            data: {
                visualizzazione: selected_option,
            },
            success: function (data) {
                window.location.reload();
            },
            error: function () {
                console.log("errore");
            },
        });
    }
}
setInterval(function () {
    updateClock();
}, 1000);

setInterval(function () {
    VersionCode();
}, 1000000);

setInterval(function () {
    checkValideQrcode();
}, impostazioni_hr_time_refresh);
