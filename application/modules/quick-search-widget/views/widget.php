<style>
    .search-wrapper {
        position: relative;
        /*width: 50px;*/
        /* Dimensione iniziale */
        width: 40px;
        height: 40px;
        list-style-type: none;
    }

    .search-input {
        position: absolute;
        margin: 20px;
        /*top: 0;
            left: 0;*/
        width: 0;
        height: 40px;
        opacity: 0;
        border: none;
        border-radius: 25px;
        /* Per renderlo circolare / ovale */
        padding: 0 25px;
        transition: all 0.7s ease;
    }

    .search-btn {
        position: absolute;
        width: 100%;
        height: 100%;
        /*border: 2px solid black;*/
        border: none;
        border-radius: 50%;
        /* Per renderlo completamente rotondo */
        background-color: white;
        transition: all 0.5s ease;
        cursor: pointer;
        font-size: 20px;
        /* Aumenta la dimensione dell'icona */
        text-align: center;
        line-height: 40px;
        /* Centra verticalmente l'icona */
    }

    .search-wrapper.active {
        width: 250px;
    }

    .search-wrapper.active .search-input {
        width: 250px;
        /* Si espande a tutta la larghezza del container */
        opacity: 1;
    }

    .search-wrapper.active .search-btn,
    .search-wrapper.active .submit-btn {
        display: none;
    }

    .search-wrapper.active .submit-btn {
        display: block;
    }

    .custom_search_wrapper .search-input {
        margin: 0;
    }
</style>

<li class="search-wrapper navbar-circle custom_search_wrapper">
    <input type="text" id="search-input-topbar" class="search-input" placeholder="Cerca...">
    <button id="search-btn" class="search-btn">
        <i id="icon-search-btn" class="fas fa-search"></i>
    </button>
</li>
<div id="autocomplete-list"></div>

<script>
    // Funzione per verificare se l'elemento di destinazione o uno dei suoi genitori è l'elemento specificato
    document.addEventListener('click', function (event) {
        const searchWrapper = document.querySelector('.search-wrapper');
        const searchInput = document.getElementById('search-input-topbar');
        const searchBtn = document.getElementById('search-btn');
        const iconSearchBtn = document.getElementById('icon-search-btn');
        const modalSideView = document.getElementById('modal-side-view');

        // Verifica se il clic è avvenuto all'interno dell'elemento di ricerca o del pulsante di ricerca
        if (event.target === searchBtn || event.target === iconSearchBtn) {
            // Mostra l'input di ricerca solo se non è già attivo
            if (!searchWrapper.classList.contains('active')) {
                searchWrapper.classList.add('active');
                searchInput.focus(); // Opzionale, per focus automatico
            }
        } else if (event.target !== searchInput && !isInsideElement(event.target, searchWrapper) && !isInsideElement(event.target, modalSideView)) {
            // Chiudi l'input di ricerca se il clic è avvenuto al di fuori dell'input, della modal side view e dei loro discendenti
            searchWrapper.classList.remove('active');
            // Rimuovi il focus dall'input di ricerca
            searchInput.blur();
            // Rimuovi il contenuto dall'input di ricerca
            searchInput.value = '';
        }
    });

    // Funzione per verificare se l'elemento di destinazione o uno dei suoi genitori è l'elemento specificato
    function isInsideElement(target, element) {
        let node = target;
        while (node != null) {
            if (node === element) {
                return true;
            }
            node = node.parentNode;
        }
        return false;
    }


    var ricerca_data;


    // Funzione di debouncing
    let debounceTimeout;
    const debounce = (func, delay) => {
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => func.apply(context, args), delay);
        }
    }
    document.getElementById('search-input-topbar').addEventListener('input', debounce(function () {

        var inputValue = this.value;

        if (inputValue.length < 2) {
            return;
        }
        loading(true);

        // Metodo con sideview
        post_data = [];
        post_data.push({
            name: 'search',
            value: inputValue
        });
        loadModal(base_url + 'quick-search-widget/main/search?_mode=side_view', post_data);

        // Metodo con request

        /*request(base_url + 'quick-search-widget/main/search', {
            "search": inputValue,
            [token_name]: token_hash
        }, 'POST', false, false, {}).then(data => {
    
            console.log(data);
            
            if (!data.success) {
                toast('Errore', 'error', data.message, 'toastr', false);
                return;
            }
    
            if (data.success) {
                ricerca_data = data.data;
                
                console.log(data);
               
            }
        }).catch(error => {
            console.log(error);
        });*/
    }, 500));
</script>