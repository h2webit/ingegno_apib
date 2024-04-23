<?php
//$chart = "https://chart.googleapis.com/chart?chs=512x512&cht=qr&chl=".$badge."&choe=UTF-8";
$chart = "https://quickchart.io/qr?text=".$badge."&size=512";

// Ottieni il contenuto dell'immagine dal link
$imageData = file_get_contents($chart);

// Codifica l'immagine in base64
$base64Image = base64_encode($imageData);

// Ottieni il tipo di immagine (estensione)
$imageType = pathinfo($chart, PATHINFO_EXTENSION);

// Crea la stringa dell'immagine base64 con il prefisso corretto
$base64ImageString = 'data:image/' . $imageType . ';base64,' . $base64Image;
?>


<!DOCTYPE html>

<html>
<head>
  <title>Badge accessi</title>
  <style>
  * {
    font-family: 'Helvetica', 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    font-size: 30px;
  }
  .welcome {
    text-align: center;
    color: black;
  }

  #qrcode {
    margin-top: 10px;

  }

  #qrcode img {
    margin-left: auto;
    margin-right: auto;
    display: block;
    width: 100%;
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
</style>
</head>
<body>

                        <div id="qrcode">
                          <img src="<?php echo $base64ImageString; ?>" class="img-responsive equipment_qr" alt="QR Code">
                        </div>
                        <?php if ($info == 'true'): ?>
                          <?php if (!isset($nome_dipendente)): ?>
                            <p style="font-weight: bold;">CONTROLLO ACCESSI</p>
                          <?php endif; ?>
                          <?php if (isset($nome_dipendente)): ?>
                              <h1><?php echo $nome_dipendente; ?></h1>
                          <?php else: ?>
                              <p><?php echo $badge; ?></p>
                          <?php endif; ?>
                        <?php endif; ?>
                  

</body>
</html>
