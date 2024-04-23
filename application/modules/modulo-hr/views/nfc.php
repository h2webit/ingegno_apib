
<p>
  <button onclick="readTag()">Abilita Lettura</button>
  <button onclick="writeTag()">Test NFC Write</button>
</p>
<div id="benvenuto_utente" style="display:none;"><h3> </h3></div>
<pre id="log"></pre>
<p><small>Based on the code snippets from <a href="https://w3c.github.io/web-nfc/#examples">specification draft</a>.</small></p>

<script>
  window.addEventListener('load', function() {
  readTag();
});

async function readTag() {
  if ("NDEFReader" in window) {
    const ndef = new NDEFReader();
    try {
      await ndef.scan();
      ndef.onreading = event => {
        const decoder = new TextDecoder();
        for (const record of event.message.records) {
          /*consoleLog("Record type:  " + record.recordType);
          consoleLog("MIME type:    " + record.mediaType);*/
          consoleLog("Qr:\n" + decoder.decode(record.data));
          var reparto = 3;
          if(decoder.decode(record.data)){
            consoleLog("Inizio");
            $.ajax({
            url: base_url + 'modulo-hr/qrcode/scansiona_badge/'+decoder.decode(record.data)+'/'+reparto,

            dataType: 'json',
            success: function(data) {
              console.log(data);
              document.getElementById("benvenuto_utente").style.display = "block";
              if(data.status===1){
                document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                
                document.getElementById('audio').play();
                document.getElementById("benvenuto_utente").innerHTML ="<h3 style='color:#0870a3;'><span style='color:#0870a3;' class='bold uppercase'>" + data.txt + "</span> " + data.data.dipendenti_nome + " " + data.data.dipendenti_cognome + "</h3>";
              } else {
                
                document.getElementById("benvenuto_utente").style.background = "#f2f9fd";
                document.getElementById("benvenuto_utente").innerHTML ="<h3><span style='color:#991b1b;' class='bold uppercase'>" + data.txt + "</span></h3>";
              }
              setTimeout(function(){document.getElementById('benvenuto_utente').style.display="none"}, 5000);
            },
            error: function() {
                document.getElementById("benvenuto_utente").style.display = "none";
              },
            });
          }
        }
      }
    } catch(error) {
      consoleLog(error);
    }
  } else {
    consoleLog("Web NFC is not supported.");
  }
}

async function writeTag() {
  if ("NDEFReader" in window) {
    const ndef = new NDEFReader();
    try {
      await ndef.write("07684");
      consoleLog("NDEF message written!");
    } catch(error) {
      consoleLog(error);
    }
  } else {
    consoleLog("Web NFC is not supported.");
  }
}

function consoleLog(data) {
  var logElement = document.getElementById('log');
  logElement.innerHTML += data + '\n';
};
</script>