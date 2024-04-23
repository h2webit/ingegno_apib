<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="manifest" href="<?php echo $this->layout->moduleAssets('modulo-hr', 'pwa/manifest.json'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" crossorigin="anonymous" />
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/scan_badge.js'); ?>

<link rel="icon" type="image/png" href="<?php echo $this->layout->moduleAssets('modulo-hr', 'pwa/icon.png'); ?>">
    <style>
        #scatola {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .square {
            width: 300px;
            height: 300px;
            margin: 10px;
            background-color: rgba(8, 111, 163, 0.05);
            border-radius: 15px; /* Imposta il bordo arrotondato */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white; /* Cambia il colore del testo se preferisci */
        }
        .square p {
            color: #086FA3;
            font-size: 30px;
            font-weight: bold;
        }

        button {
            font-size: 20px;
            width: 50%;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #086FA3; /* Cambia il colore del pulsante se preferisci */
            color: white; /* Cambia il colore del testo del pulsante se preferisci */
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
    <script>
    // Verifica se c'è già un valore per 'idRepartoPwaTimbratore' nel localStorage
    var idRepartoPwaTimbratore = localStorage.getItem('idRepartoPwaTimbratore');

    // Se c'è un valore, effettua il redirect automatico
    if (idRepartoPwaTimbratore) {
        var baseUrl = "<?php echo base_url(); ?>";
        var url = baseUrl + 'modulo-hr/qrcode/timbratore';
        window.location.href = url;
    }

    function selezionaReparto(idReparto) {
        // Salva l'ID del reparto nel localStorage con la chiave 'idRepartoPwaTimbratore'
        localStorage.setItem('idRepartoPwaTimbratore', idReparto);

        // Costruisci l'URL completo
        var baseUrl = "<?php echo base_url(); ?>";
        var url = baseUrl + 'modulo-hr/qrcode/timbratore';

        // Apri la pagina pwa.php
        window.location.href = url;
    }
</script>
    <title>Scegli reparto</title>
</head>
<?php
$reparti = $this->apilib->search('reparti');
?>
<body>
<h1 style="text-align:center;color: #086FA3;">Seleziona il reparto</h1>
    <div id="scatola">
        <div class="container">
            <div class="square" onclick="selezionaReparto(0)">
                <p>Timbratore generale</p>
                <button>Seleziona</button>
            </div>
            <?php
            foreach ($reparti as $reparto):
                ?>
                <div class="square" onclick="selezionaReparto(<?php echo $reparto['reparti_id']; ?>)">
                    <p><?php echo $reparto['reparti_nome']; ?></p>
                    <button>Seleziona</button>
                </div>
                <?php
            endforeach;
            ?>
        </div>
    </div>
</body>
</html>
