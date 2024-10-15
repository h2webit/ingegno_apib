<style>
    #pdf-canvas {
        cursor: crosshair;
        /*width: 700px;*/
        height: 100%;
        margin: 0 auto;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js"></script>

<div class="row">
    <div class="col-sm-6">
        <div class="callout callout-info" style="background-color: #3c8dbc!important; border-left: 5px solid #357ca5;">
            <h4>Guida rapida: Mappatura dei cedolini</h4>
            <ol>
                <li>Clicca su "Scegli file" e seleziona il PDF del cedolino da mappare.</li>
                <li>Una volta caricato il PDF, vedrai un'anteprima sulla sinistra.</li>
                <li>Sulla destra, inserisci un nome per questa mappatura.</li>
                <li>Per mappare un'area:
                    <ul>
                        <li>Clicca e trascina il mouse sull'anteprima per disegnare un rettangolo intorno al dato desiderato (es. saldo ferie).</li>
                        <li>Seleziona il tipo di dato dalla lista a destra (es. "Saldo Ferie").</li>
                        <li>Inserisci un'etichetta che sia <strong>identica</strong> al testo nel cedolino. Ad esempio, se nel cedolino c'è scritto "FERIE RES.", l'etichetta deve essere esattamente "FERIE RES." (maiuscole, punteggiatura inclusa).</li>
                    </ul>
                </li>
                <li>Ripeti il processo per mappare altri dati (es. saldo permessi, saldo ROL).</li>
                <li>Clicca "Salva mappatura" quando hai finito.</li>
            </ol>
            <p>Attenzione: L'esatta corrispondenza delle etichette è fondamentale per il corretto funzionamento del sistema.</p>
            <p>Questa mappatura ti permetterà di estrarre automaticamente questi dati da futuri cedolini con lo stesso formato.</p>
        </div>
    </div>
    
    <div class="col-sm-6" id="map_section" style="display:none">
        <div class="panel panel-default">
            <div class="panel-heading" style="display: flex; justify-content: space-between">
                <input type="text" class="form-control form-control-sm" style="width: 25rem;" form="form_mappature" name="nome_mappatura" placeholder="Inserisci il nome della mappatura" aria-required="true" required>
                
                <label for="">
                    <input type="checkbox" form="form_mappature" name="mappatura_default" value="1">
                    Default
                </label>
            
            </div>
            
            <div class="panel-body">
                <p>Seleziona un'area del cedolino per mapparla.</p>
                
                <div class="tpl row" style="display:none;">
                    <div class="col-sm-2">
                        <label>Nome</label><br>
                        <select data-name="campo">
                            <option value="dipendenti_saldo_ferie">Saldo Ferie</option>
                            <option value="dipendenti_saldo_permessi">Saldo Ore</option>
                            <option value="dipendenti_saldo_rol">Saldo ROL</option>
                        </select>
                    </div>
                    
                    <div class="col-sm-3">
                        <label>Label</label><br>
                        <input type="text" data-name="label" required>
                    </div>
                    
                    <div class="col-sm-2">
                        <label>Inizio coords.</label><br>
                        <code class="coord_inizio"></code>
                        <input type="hidden" data-name="coord_inizio">
                    </div>
                    
                    <div class="col-sm-2">
                        <label>Fine coords.</label><br>
                        <code class="coord_fine"></code>
                        <input type="hidden" data-name="coord_fine">
                    </div>
                    
                    <div class="col-sm-2"><button type="button" style="white-space: nowrap">Rimuovi riga</button></div>
                </div>
                
                <form id="form_mappature" class="formAjax" action="<?php echo base_url('modulo-hr/mappature/salva'); ?>" method="post">
                    <?php add_csrf(); ?>
                    
                    <div id="mappature"></div>
                </form>
            </div>
            <div class="panel-footer clearfix">
                <div class="alert alert-danger hide" id="msg_form_mappature"></div>
                
                <button type="submit" class="btn btn-sm btn-primary pull-right" form="form_mappature">Salva mappatura</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-default">
            <div class="panel-heading" style="display: flex; justify-content: space-between">
                <h3 class="panel-title">Carica il file</h3>
                
                <input type="file" id="file-input" accept="application/pdf">
            </div>
            
            <canvas id="pdf-canvas"></canvas>
        </div>
    </div>
</div>

<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js';
    
    const fileInput = document.getElementById('file-input');
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    
    if (!ctx) {
        console.error('Canvas context not available');
        // Handle the error appropriately, e.g., show an error message to the user
    }
    
    let pdfDoc = null;
    let pageNum = 1;
    let scale = 2; // Scala iniziale, può essere modificata
    let coordinates = [];
    let isDrawing = false;
    let startX, startY;
    let isRendering = false;
    
    function remove_row(btn_el) {
        const row = btn_el.closest('.mapped');
        const index = Array.from(row.parentNode.children).indexOf(row);
        coordinates.splice(index, 1);
        row.remove();
        update_map(coordinates);
        renderPage(pageNum);
    }
    
    const beforeUnloadHandler = (event) => {
        // Recommended
        event.preventDefault();
        
        // Included for legacy support, e.g. Chrome/Edge < 119
        event.returnValue = true;
    };
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.value !== "") {
            window.addEventListener("beforeunload", beforeUnloadHandler);
        } else {
            window.removeEventListener("beforeunload", beforeUnloadHandler);
        }
        
        const file = e.target.files[0];
        if (file.type !== 'application/pdf') {
            alert('Please select a PDF file.');
            return;
        }
        
        console.log('PDF file selected:', file.name);
        
        const fileReader = new FileReader();
        fileReader.onload = function () {
            console.log('File loaded into memory');
            const typedarray = new Uint8Array(this.result);

            pdfjsLib.getDocument(typedarray).promise.then((pdf) => {
                console.log('PDF document loaded');
                pdfDoc = pdf;
                return renderPage(pageNum);
            }).catch(error => {
                console.error('Error loading PDF:', error);
            });
        };
        fileReader.readAsArrayBuffer(file);
        
        // resetto il form delle mappature
        $('#mappature').html('');
        // rendo visibile il div "#map_section"
        $('#map_section').show();
    });
    
    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
    
    function update_map(coords) {
        const div_map = document.getElementById('mappature');
        
        // Preserve existing data
        const existingRows = div_map.querySelectorAll('.mapped');
        const existingData = Array.from(existingRows).map(row => ({
            campo: row.querySelector('select[name^="mappature"]').value,
            label: row.querySelector('input[name$="[label]"]').value
        }));
        
        // Clear existing rows
        div_map.innerHTML = '';
        
        coords.forEach((coord, index) => {
            const tpl = document.querySelector('.tpl').cloneNode(true);
            tpl.classList.remove('tpl');
            tpl.classList.add('mapped');
            tpl.style.display = 'flex';
            tpl.style.alignItems = 'center';
            
            const selectEl = tpl.querySelector('select');
            selectEl.setAttribute('name', `mappature[${index}][campo]`);
            selectEl.setAttribute('data-name', '');
            
            const labelEl = tpl.querySelector('input[data-name="label"]');
            labelEl.setAttribute('name', `mappature[${index}][label]`);
            labelEl.setAttribute('data-name', '');
            
            // Add color circle
            const colorCircle = document.createElement('div');
            colorCircle.style.width = '20px';
            colorCircle.style.height = '20px';
            colorCircle.style.borderRadius = '50%';
            colorCircle.style.backgroundColor = coord.color;
            colorCircle.style.marginRight = '10px';
            tpl.querySelector('.col-sm-2').insertBefore(colorCircle, selectEl);
            
            tpl.querySelector('.coord_inizio').textContent = `${coord.startX.toFixed(2)}, ${coord.startY.toFixed(2)}`;
            tpl.querySelector('input[data-name="coord_inizio"]').setAttribute('name', `mappature[${index}][coord_inizio]`);
            tpl.querySelector('input[data-name="coord_inizio"]').setAttribute('value', `${coord.startX},${coord.startY}`);
            
            tpl.querySelector('.coord_fine').textContent = `${coord.endX.toFixed(2)}, ${coord.endY.toFixed(2)}`;
            tpl.querySelector('input[data-name="coord_fine"]').setAttribute('name', `mappature[${index}][coord_fine]`);
            tpl.querySelector('input[data-name="coord_fine"]').setAttribute('value', `${coord.endX},${coord.endY}`);
            
            // Restore existing data if available
            if (existingData[index]) {
                selectEl.value = existingData[index].campo;
                labelEl.value = existingData[index].label;
            }
            
            div_map.appendChild(tpl);
            
            tpl.querySelector('button').addEventListener('click', function () {
                remove_row(this);
            });
        });
    }
    
    function renderPage(num) {
        if (isRendering) return Promise.resolve();
        
        isRendering = true;
        return pdfDoc.getPage(num).then((page) => {
            const viewport = page.getViewport({ scale: scale });
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            return page.render(renderContext).promise;
        }).then(() => {
            isRendering = false;
            renderBoxes();
        }).catch((error) => {
            console.error('Error rendering page:', error);
            isRendering = false;
        });
    }
    
    canvas.addEventListener('mousedown', (e) => {
        const rect = canvas.getBoundingClientRect();
        startX = (e.clientX - rect.left) * (canvas.width / rect.width);
        startY = (e.clientY - rect.top) * (canvas.height / rect.height);
        isDrawing = true;
    });
    
    canvas.addEventListener('mousemove', (e) => {
        if (isDrawing) {
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left) * (canvas.width / rect.width);
            const y = (e.clientY - rect.top) * (canvas.height / rect.height);
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            renderPage(pageNum).then(() => {
                ctx.strokeStyle = 'red';
                ctx.lineWidth = 2;
                ctx.strokeRect(startX, startY, x - startX, y - startY);
            });
        }
    });
    
    canvas.addEventListener('mouseup', (e) => {
        if (isDrawing) {
            const rect = canvas.getBoundingClientRect();
            const endX = (e.clientX - rect.left) * (canvas.width / rect.width) / scale;
            const endY = (e.clientY - rect.top) * (canvas.height / rect.height) / scale;
            const color = getRandomColor();
            coordinates.push({
                startX: startX / scale,
                startY: startY / scale,
                endX: endX,
                endY: endY,
                color: color
            });
            isDrawing = false;
            
            update_map(coordinates);
            renderBoxes();
        }
    });
    
    function renderBoxes() {
        ctx.save();
        coordinates.forEach(coord => {
            ctx.strokeStyle = coord.color;
            ctx.lineWidth = 2;
            ctx.strokeRect(
                coord.startX * scale,
                coord.startY * scale,
                (coord.endX - coord.startX) * scale,
                (coord.endY - coord.startY) * scale
            );
        });
        ctx.restore();
    }
    
    document.getElementById('form_mappature').addEventListener('submit', function (e) {
        window.removeEventListener("beforeunload", beforeUnloadHandler);
    });
</script>