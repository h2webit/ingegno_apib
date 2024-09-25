<body onload="loadHandler()">
    <div style="width: 100%; height: 90vh; display: flex; justify-content: center; align-items: center; font-family: Helvetica, Arial, sans-serif; text-transform: uppercase;">
        <div style="text-align: center;">
            <p><?php echo $articolo['movimenti_articoli_codice'] ?></p>
            <p><?php echo $articolo['movimenti_articoli_name'] ?></p>
            <p>lotto: <?php echo $articolo['movimenti_articoli_lotto'] ?: '-' ?></p>
        </div>
    </div>
    
    <script>
        function loadHandler() {
            window.print();
        }
    </script>
</body>
