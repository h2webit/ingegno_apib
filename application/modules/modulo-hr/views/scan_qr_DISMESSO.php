<script src="https://unpkg.com/html5-qrcode"></script>
<?php
//nome reparto
$reparto = $this->apilib->searchFirst('reparti', [
	'reparti_id' => $this->input->get('reparto'),
  ]);
$nome_reparto = $reparto['reparti_nome'];
$settings = $this->apilib->searchFirst('settings');
$logo = base_url_uploads("uploads/{$settings['settings_company_logo']}");
?>
<style>
  * {
    margin: 0;
    padding: 0;
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
  .welcome #qr-reader {
    margin-left: auto;
    margin-right: auto;
    display: block;
  }
  body {
    background: rgb(34, 193, 195) !important;
    background: linear-gradient(0deg, #DECBA4 0%, rgba(45, 177, 253, 0) 70%) !important;
    height: 100vh;
  }
  .scatola {
    /*background: rgba(25, 25, 25, .1);*/
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
    <img id="logo" src="<?php echo $logo; ?>" class="img-responsive">
    <br>
    <div class="welcome">
		<h1><?php echo $nome_reparto; ?></h1>
		<div id="benvenuto_utente" style="display:none;color:blue;"><h3>Benvenuto </h3></div>
		<div id="qr-reader" style="position:absolute;width:600px"></div>
		<div id="qr-reader-results"></div>
		</div>
    <br>
	<!--
    <div id="qr-reader" style="position:absolute; width:100%; height:100%">
-->



    </div>
    <div id="errore_connessione" class="welcome" style="display:none;">
      <h3>Errore, controlla la connessione e ricarica la pagina</h3>
    </div>
    <div class="row">


    </div>
  </div>
</body>
<script>
const urlParams = new URLSearchParams(window.location.search);
const reparto = urlParams.get('reparto');
var html5QrcodeScanner = new Html5QrcodeScanner(
	"qr-reader", { 
		fps: 10, 
		qrbox: 250,
		rememberLastUsedCamera: true
	});

        
		function onScanSuccess(decodedText, decodedResult) {
			// Handle on success condition with the decoded text or result.
			console.log(`Scan result: ${decodedText}`, decodedResult);
			html5QrcodeScanner.clear();
			// ^ this will stop the scanner (video feed) and clear the scan area.
			if(decodedText){
				$.ajax({
				url: base_url + 'modulo-hr/qrcode/scansiona_badge/'+decodedText+'/'+reparto,

				dataType: 'json',
				success: function(data) {
					console.log(data);
					reinizialize();
					document.getElementById("benvenuto_utente").style.display = "block";
					if(data.status===1){
						document.getElementById("benvenuto_utente").innerHTML ="<h2>" + data.txt + " " + data.data.dipendenti_nome + " " + data.data.dipendenti_cognome + "</h2>";
					} else {
						document.getElementById("benvenuto_utente").innerHTML ="<h2>" + data.txt + "</h2>";
					}
					setTimeout(function(){document.getElementById('benvenuto_utente').style.display="none"}, 5000);
				},
				error: function() {
						document.getElementById("benvenuto_utente").style.display = "none";
						reinizialize();
					},
				});
			}
			//setTimeout("reinizialize()", 100000);
		}
		function reinizialize(){
			html5QrcodeScanner.render(onScanSuccess);
		}
		
		html5QrcodeScanner.render(onScanSuccess);


</script>