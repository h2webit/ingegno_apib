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
  <div class="scatola" style="overflow-y:scroll;">
    <br>
    <div class="welcome">
    <div id="qrcode">
      <img src="https://quickchart.io/qr?text=<?php echo $badge; ?>&size=512" class="img-responsive equipment_qr" alt="QR Code">
    </div>
    <?php if (!isset($nome_dipendente)): ?>
      <p style="font-weight: bold;">CONTROLLO ACCESSI</p>
    <?php endif; ?>
    <?php if (isset($nome_dipendente)): ?>
        <h1><?php echo $nome_dipendente; ?></h1>
    <?php else: ?>
        <p><?php echo $badge; ?></p>
    <?php endif; ?>
    </div>
    <br>

  </div>
</body>