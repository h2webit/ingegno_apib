<style>
    #pdf-canvas {
        cursor: crosshair;
        width: 700px;
        height: 100%;
        margin: 0 auto;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js"></script>

<div class="row" id="map_section" style="display:none">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading" style="display: flex; justify-content: space-between">
                <h3 class="panel-title">Mappatura</h3>
            </div>
            <div class="panel-body">
                <p>Seleziona un'area del cedolino per mapparla.</p>
                
                <div class="tpl" style="display:none; justify-content: space-between">
                    <div>
                        <label>Nome</label>
                        <input type="text" data-name="map_nome">
                    </div>
                    
                    <div>
                        <label style="margin-right: 5px;">Inizio coords.</label>
                        <code class="map_inizio_coords"></code>
                        <input type="hidden" data-name="map_inizio_coords">
                    </div>
                    
                    <div>
                        <label style="margin-right: 5px;">Fine coords.</label>
                        <code class="map_fine_coords"></code>
                        <input type="hidden" data-name="map_fine_coords">
                    </div>
                    
                    <button type="button" style="white-space: nowrap">Rimuovi riga</button>
                </div>
            </div>
            <div class="panel-footer clearfix">
                <form id="form_mappature" action="" method="post">
                    <?php add_csrf(); ?>
                    
                    <div id="mappature"></div>
                    
                    <button type="submit" class="btn btn-xs btn-primary pull-right">Salva mappatura</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-sm-offset-3">
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
    const fileInput = document.getElementById('file-input');
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    
    let pdfDoc = null;
    let pageNum = 1;
    let scale = 2; // Scala iniziale, può essere modificata
    let coordinates = [];
    let isDrawing = false;
    let startX, startY;
    let isRendering = false;
    
    function remove_row(btn_el) {
        // rimuove riga, andando a cercare il div "tpl" partendo dall'elemento button (btn_el)
        $(btn_el).closest('.tpl').remove();
    }
    
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file.type !== 'application/pdf') {
            alert('Please select a PDF file.');
            return;
        }
        
        const fileReader = new FileReader();
        fileReader.onload = function () {
            const typedarray = new Uint8Array(this.result);
            
            pdfjsLib.getDocument(typedarray).promise.then((pdf) => {
                pdfDoc = pdf;
                renderPage(pageNum);
            });
        };
        fileReader.readAsArrayBuffer(file);
        
        // resetto il form delle mappature
        $('#mappature').html('');
        // rendo visibile il div "#map_section"
        $('#map_section').show();
    });
    
    function update_map(coords) {
        const div_map = document.getElementById('mappature');
        div_map.innerHTML = '';
        
        coords.forEach((coord, index) => {
            // clono il div contenuto nel div ".tpl" e gli input devono essere convertiti in array, prendendo il "name" dall'attributo "data-name" e aggiunto l'indice, ad esempio: input[data-name="map_inizio_coords"] diventerà name=mappature[0][map_inizio_coords]  e il data-name viene cancellato. va quindi appeso poi al form "#mappature"
            
            const tpl = document.querySelector('.tpl').cloneNode(true);
            
            // ora cambio la classe del div clonato, in modo che non venga più considerato come "tpl", quindi ne imposto uno nuovo
            tpl.classList.remove('tpl');
            tpl.classList.add('mapped');
            
            tpl.style.display = 'flex';
            
            tpl.querySelector('input').setAttribute('name', `mappature[${index}][map_nome]`);
            tpl.querySelector('input').setAttribute('data-name', '');
            tpl.querySelector('.map_inizio_coords').textContent = `(${coord.startX}, ${coord.startY})`;
            tpl.querySelector('input[data-name="map_inizio_coords"]').setAttribute('name', `mappature[${index}][map_inizio_coords]`);
            tpl.querySelector('input[data-name="map_inizio_coords"]').setAttribute('value', `(${coord.startX}, ${coord.startY})`);
            tpl.querySelector('.map_fine_coords').textContent = `(${coord.endX}, ${coord.endY})`;
            tpl.querySelector('input[data-name="map_fine_coords"]').setAttribute('name', `mappature[${index}][map_fine_coords]`);
            tpl.querySelector('input[data-name="map_fine_coords"]').setAttribute('value', `(${coord.endX}, ${coord.endY})`);
            
            // appendo il div clonato al form, nel div con id "mappature"
            div_map.appendChild(tpl);
            
            tpl.querySelector('button').addEventListener('click', function () {
                remove_row(this);
                coordinates.splice(index, 1);
                update_map(coordinates);
            });
        });
    }
    
    function renderPage(num) {
        if (isRendering) return Promise.resolve(); // Evita rendering multipli
        
        isRendering = true;
        return pdfDoc.getPage(num).then((page) => {
            const viewport = page.getViewport({ scale: scale });
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            return page.render(renderContext).promise.then(() => {
                isRendering = false;
            }).catch((error) => {
                isRendering = false;
                console.error('Rendering error:', error);
            });
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
            coordinates.push({
                startX: startX / scale,
                startY: startY / scale,
                endX: endX,
                endY: endY
            });
            isDrawing = false;
            
            update_map(coordinates);
            
            console.log('Rectangle coordinates:', coordinates);
        }
    });
</script>