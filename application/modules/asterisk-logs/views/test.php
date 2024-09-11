<!DOCTYPE html>
<html>
<head>
  <title>Mia Applicazione</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.0.1/socket.io.js"></script>
</head>
<body>
  <p>ciao</p>
  <script>
    // Dichiara le variabili globali
// Dichiara le variabili globali
let socket;

// Crea una nuova connessione WebSocket
socket = new io('https://centralino.h2web.it:443', {
  "upgrade": false,
  "transports": ['websocket'],
  "reconnection": true,
  "reconnectionDelay": 2000
});

// Esegue il login
function login() {
  // Genera il token di autenticazione
  /*let nonce = socket.handshake.headers["www-authenticate"].split(":")[1];
  let token = CryptoJS.HmacSHA1(username + ":" + password + ":" + nonce, password).toString();*/

  // Invia il messaggio di login
  socket.emit('login', {
    accessKeyId: 'michael',
    token: '<?php echo $token; ?>'
  });
}

// Gestisce l'evento di login
socket.on('login', function(data) {
  // Se il login ha avuto successo
  if (data.status === "authe_ok") {
    // Esegue il codice necessario
    console.log("Login riuscito");
  } else {
    // Se il login ha fallito
    console.log("Login fallito");
    socket.disconnect();
  }
});

// Esegue il login al caricamento della pagina
login();
    // Chiamate alle funzioni per rispondere o rifiutare la chiamata in base all'interazione dell'utente
  </script>
</body>
</html>