async function fetchXSD(url) {
    const response = await fetch(url);
    const text = await response.text();
    return new DOMParser().parseFromString(text, 'application/xml');
}

function generateFormFromXSD(xsdDocument, elementsToShow, json_data) {
    const rootElement = xsdDocument.querySelector('element');
    generateFieldsForElement(rootElement, '', xsdDocument, null, elementsToShow, json_data);
}
function checkAndOpenFieldsets(fieldset) {
    // Verifica se dentro il fieldset ci sono input con valore
    const hasValue = Array.from(fieldset.querySelectorAll('input, select')).some(input => input.value !== '');

    if (hasValue) {
        // Se troviamo un valore, rimuoviamo la classe 'collapsed'
        fieldset.classList.remove('collapsed');
    } else {
        // Se non ci sono valori, assicurati che il fieldset sia chiuso,
        // a meno che non vogliamo aprire tutti i fieldset vuoti per default
        fieldset.classList.add('collapsed');
    }

    // Propaga la verifica verso l'alto per i fieldset genitori, se esistono
    let parent = fieldset.parentElement;
    while (parent && parent !== document) {
        if (parent.tagName === 'FIELDSET') {
            // Qui assumiamo che vogliamo aprire i genitori se almeno un figlio ha valori
            if (hasValue) parent.classList.remove('collapsed');
        }
        parent = parent.parentElement;
    }
}
function updateFieldsetIndexes(wrapper) {
    
    // Ottiene tutti i fieldset figli diretti del wrapper
    const fieldsets = wrapper.querySelectorAll(':scope > fieldset');
    fieldsets.forEach((fieldset, index) => {
        // Aggiorna il nome di ogni input nel fieldset per riflettere l'indice corrente
        const inputs = fieldset.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.name.replace(/\[\d+\]/g, ''); // Rimuovi l'indice esistente
            const newName = `${name}[${index}]`; // Aggiungi il nuovo indice
            input.name = newName;
        });
    });
}
function addRemoveButton(fieldset) {
    const removeButton = document.createElement('button');
    removeButton.textContent = "-";
    removeButton.className = 'remove';
    removeButton.style.position = 'absolute';
    removeButton.style.top = '10px';
    removeButton.style.right = '30px'; // Regola questa distanza in base alla posizione del pulsante "+"
    removeButton.onclick = function (e) {
        e.preventDefault();
        const wrapper = fieldset.parentNode;
        wrapper.removeChild(fieldset);
        updateFieldsetIndexes && updateFieldsetIndexes(wrapper);
    };
    fieldset.style.position = 'relative'; // Assicurati che il fieldset abbia position: relative per posizionare correttamente i pulsanti
    fieldset.appendChild(removeButton);
}
// Funzione aggiuntiva per trasformare il percorso corrente nel formato desiderato
function currentPathToBrackets(currentPath) {
    const parts = currentPath.split('/'); // Supponendo che usi '/' come separatore nel currentPath
    if (parts.length > 0) {
        // Trasforma ogni parte in una stringa con parentesi, escludendo la prima parte
        const formattedParts = parts.map((part, index) => index === 0 ? part : `[${part}]`);
        return formattedParts.join('');
    }
    return ''; // Gestisce il caso di percorso vuoto, se necessario
}
// Funzione di utility per ottenere il valore da un oggetto JavaScript basato su un percorso in dot notation
function getValueFromPath(obj, path) {
    console.log(path);
    console.log(obj);
    //alert(1);
    if (path === 'FatturaElettronica.FatturaElettronicaBody.DatiGenerali.DatiFattureCollegate.IdDocumento') {
        console.log(obj.FatturaElettronica);

    }
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
}
function generateFieldsForElement(element, parentPath, xsdDocument, parentFieldset, elementsToShow, json_data) {
    // Se elementsToShow è vuoto, procedi senza filtrare gli elementi
    const showAll = elementsToShow.length === 0;

    const elementName = element.getAttribute('name');
    // Crea il percorso corrente aggiungendo il nome dell'elemento al percorso del genitore
    const currentPath = parentPath ? `${parentPath}/${elementName}` : elementName;

    // Verifica se l'elemento corrente o uno dei suoi antenati è da mostrare
    const isElementOrAncestorToShow = showAll || elementsToShow.some(targetElement =>
        currentPath.includes(targetElement) || targetElement.includes(currentPath)
    );

    // Controlla se l'elemento corrente è esattamente uno dei nodi specificati in elementsToShow
    const isExactTargetNode = showAll || elementsToShow.includes(currentPath);

    //rimosso perchè il nodo deve comunque esistere come hidden
    // if (!isElementOrAncestorToShow && !isExactTargetNode) {
    //     return; // Se l'elemento non è da mostrare e non è un nodo target, non generarlo.
    // }

    

    const childType = element.getAttribute('type');
    const isComplexType = xsdDocument.querySelector(`complexType[name="${childType}"]`);

    const maxOccurs = element.getAttribute('maxOccurs');

    if (isComplexType) {
        const fieldset = document.createElement('fieldset');
        fieldset.classList.add('collapsible', 'collapsed');
        const legend = document.createElement('legend');
        legend.textContent = elementName;
        legend.onclick = function () { // Aggiungi l'evento onclick per collassare/espandere
            fieldset.classList.toggle('collapsed');
        };
        fieldset.appendChild(legend);
        if (!isElementOrAncestorToShow && !isExactTargetNode) {
            // Nascondi il fieldset se l'elemento o i suoi antenati non devono essere visualizzati
            fieldset.style.display = 'none';
        }
        if (maxOccurs === 'unbounded') {
            const repeatableWrapper = document.createElement('div');
            const addButton = document.createElement('button');
            addButton.textContent = "+";
            addButton.className = 'add';
            addButton.onclick = function (e) {
                e.preventDefault();
                const clonedFieldset = fieldset.cloneNode(true);
                repeatableWrapper.insertBefore(clonedFieldset, addButton);
                addRemoveButton(clonedFieldset, () => updateFieldsetIndexes(repeatableWrapper));
                updateFieldsetIndexes(repeatableWrapper); // Aggiorna gli indici dopo l'aggiunta
            };

            if (!isElementOrAncestorToShow && !isExactTargetNode) {
                // Nascondi il wrapper se l'elemento o i suoi antenati non devono essere visualizzati
                addButton.style.display = 'none';
            }

            repeatableWrapper.appendChild(fieldset);
            repeatableWrapper.appendChild(addButton);
            repeatableWrapper.className = 'repeatable';

            
            
            if (parentFieldset) {
                parentFieldset.appendChild(repeatableWrapper);
            } else {
                document.getElementById('jsEditorContainer').appendChild(repeatableWrapper);
            }
            
        } else {

            if (parentFieldset) {
                parentFieldset.appendChild(fieldset);
            } else {
                document.getElementById('jsEditorContainer').appendChild(fieldset);
            }
        }

        // Continua a cercare elementi figlio
        const childElements = isComplexType.querySelectorAll('element');
        childElements.forEach(childElement => {
            // Se l'elemento è uno dei nodi target, forza la visualizzazione dei suoi figli
            if (isExactTargetNode) {
                generateFieldsForElement(childElement, currentPath, xsdDocument, fieldset, [currentPath], json_data);
            } else {
                generateFieldsForElement(childElement, currentPath, xsdDocument, fieldset, elementsToShow, json_data);
            }
        });
    } else {
        const label = document.createElement('label');
        label.setAttribute('for', currentPathToBrackets(currentPath)); // Usa 'currentPath' con un trattamento per renderlo un valido id HTML
        label.textContent = elementName;

        const inputType = determineInputType(element, xsdDocument);
        let input;
        if (inputType === 'select') {
            input = document.createElement('select');
            // [Codice per popolare il select...]
        } else {
            input = document.createElement('input');
            input.type = inputType;
        }
        input.name = currentPathToBrackets(currentPath);

        // Converte il percorso corrente nel formato di bracket e verifica se esiste un valore corrispondente in `data`
        let formattedPath = currentPathToBrackets(currentPath).replace(/\[/g, '.').replace(/\]/g, ''); // Converti in formato dot notation per accesso più semplice
        let value = getValueFromPath(json_data, formattedPath); // Ottieni il valore utilizzando una funzione di utility

        if (value !== undefined) {
            input.value = value;
        }

        if (!isElementOrAncestorToShow && !isExactTargetNode) {
            // Nascondi il fieldset se l'elemento o i suoi antenati non devono essere visualizzati
            input.style.display = 'none';
            label.style.display = 'none';
        }
        // ... [Resto del codice per minLength, maxLength, ecc.]

        if (parentFieldset) {
            parentFieldset.appendChild(label);
            parentFieldset.appendChild(input);
            checkAndOpenFieldsets(parentFieldset);
        } else {
            const form = document.getElementById('fatturaForm');
            form.appendChild(label);
            form.appendChild(input);
        }
    }

    $('.repeatable').each(function () {
        updateFieldsetIndexes(this);
    });
}

function determineInputType(element, xsdDocument) {
    const typeName = element.getAttribute('type');
    const typeElement = xsdDocument.querySelector(`simpleType[name="${typeName}"]`);
    if (!typeElement) { // Se il typeElement non è stato trovato, potrebbe essere un tipo base di xsd
        switch (typeName) {
            case 'xs:string':
            case 'xs:normalizedString':
                return 'text';
            case 'xs:date':
                return 'date';
            case 'xs:decimal':
            case 'xs:float':
                return 'number';
            default:
                return 'text';  // default
        }
    }
    // Se il tipo ha valori enumerati, restituiamo "select"
    if (typeElement && typeElement.querySelector('enumeration')) {
        return 'select';
    }

    const baseType = typeElement.querySelector('restriction').getAttribute('base');
    switch (baseType) {
        case 'xs:string':
        case 'xs:normalizedString':
            return 'text';
        case 'xs:decimal':
        case 'xs:float':
            return 'number';
        default:
            return 'text';  // default
    }
    return 'text';
}

function getOptions(element, xsdDocument) {
    const typeName = element.getAttribute('type');
    const typeElement = xsdDocument.querySelector(`simpleType[name="${typeName}"]`);

    const options = [];

    if (element.getAttribute('minOccurs') === '0') {
        options.push({ value: '', text: '-- Seleziona --' });
    }

    if (typeElement) {
        const enumerations = typeElement.querySelectorAll('enumeration');
        enumerations.forEach(enumeration => {
            const value = enumeration.getAttribute('value');
            const documentation = enumeration.querySelector('documentation');
            const text = documentation ? `${value} - ${documentation.textContent.trim()}` : value;
            options.push({ value, text });
        });
    }

    return options;
}

$(() => { 
    async function init() {
        
        var editor_container = $('#jsEditorContainer');
        if (editor_container.children().length > 0) {
            console.log('Form già generato, skip della rigenerazione.');
            return;
        }
        var json_string = atob(editor_container.data('json_data'));
        
        var json_data = JSON.parse(json_string);

        var xsd_link = editor_container.data('fetchurl');
        
        const xsd = await fetchXSD(xsd_link);
        try {
            var elementsToShow = $('#modalEditorXml').data('elements').split(',');
            
        } catch (error) {
            var elementsToShow = [];
        }
        
        generateFormFromXSD(xsd, elementsToShow, json_data);
    }
    $('#modalEditorXml').on('show.bs.modal', function (event) {
        init();
    });

});

