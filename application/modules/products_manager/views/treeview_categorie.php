<?php
$fw_categories = $this->apilib->search('fw_categories', [], null, 0, 'fw_categories_name', 'ASC', 1);

/**
 * Array singola categoria
 * (
 * [fw_categories_id] => 23
 * [fw_categories_creation_date] => 2023-06-27 10:42:56
 * [fw_categories_deleted] => 0
 * [fw_categories_ecommerce_export] =>
 * [fw_categories_giorni_anticipo_riordine] =>
 * [fw_categories_listino_codice] =>
 * [fw_categories_modified_date] =>
 * [fw_categories_name] => Accessories
 * [fw_categories_order] => 1
 * [fw_categories_parent_category] =>
 * [fw_categories_punto_cassa] => 0
 * [fw_categories_woocommerce_external_code] => 18
 * )
 */
?>

<style>
    .fw-tree-container { }
    .fw-tree-view {
        max-height: 250px;
        overflow-y: auto;
        font-size: 14px;
    }
    .fw-tree-view::-webkit-scrollbar {
        width: 8px;
    }
    .fw-tree-view::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .fw-tree-view::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .fw-tree-view::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    .fw-tree-view ul {
        list-style-type: none;
        padding-left: 15px;
        margin: 0;
    }
    .fw-tree-view > ul {
        padding-left: 0;
    }
    .fw-tree-view li {
        margin: 0;
        position: relative;
    }
    .fw-tree-view .fw-toggle {
        cursor: pointer;
        user-select: none;
        position: absolute;
        right: 0;
        top: 0;
        width: 16px;
        height: 16px;
        line-height: 16px;
        text-align: center;
        background-color: #e0e0e0;
        border-radius: 3px;
        color: #333;
        font-weight: bold;
        font-size: 12px;
    }
    .fw-tree-view .fw-toggle::before {
        content: '+';
    }
    .fw-tree-view .fw-toggle-down::before {
        content: '-';
    }
    .fw-tree-view .fw-nested {
        display: none;
        margin-top: 0;
    }
    .fw-tree-view .fw-active {
        display: block;
    }
    .fw-tree-view label {
        margin-left: 3px;
        cursor: pointer;
        padding-right: 20px;
        display: inline-block;
        margin-bottom: 0 !important;
    }
    .fw-tree-view input[type="checkbox"] {
        margin-right: 3px;
    }
    .fw-global-toggle {
        margin-bottom: 0;
    }

    .fw-tree-guide {
        font-size: 12px;
        margin-bottom: 10px;
        padding: 5px;
        background-color: #f0f0f0;
        border-radius: 3px;
    }
    .fw-tree-guide p {
        margin: 0;
    }

    .fw-tree-view .fw-top-level {
        font-weight: bold;
    }
    .fw-tree-view .fw-nested .fw-nested label {
        font-weight: normal;
    }
</style>

<div id="fw_tree_view" class="fw-tree-view"></div>
<hr style="margin-top: 10px; margin-bottom: 10px;">
<div class="fw-tree-guide">
    <p><strong>Utilizzo:</strong> Usa '+' per espandere le sottocategorie. La selezione di una categoria include tutte le sue sottocategorie.</p>
</div>

<script>
    // @todo - mini input ricerca
    $(function() {
        console.log("Initializing tree view...");
        
        // Variabili globali
        var fw_categories = <?php echo json_encode($fw_categories); ?>;
        var delay = 2000; // secondi di ritardo
        var timer;
        var userActive = false;
        var lastActivityTime = Date.now();
        
        // Funzione per costruire l'albero delle categorie
        function fw_build_tree(fw_categories, fw_parent_id = null) {
            console.log("Building tree for parent ID:", fw_parent_id);
            
            // Filtra i nodi per il genitore corrente
            var fw_nodes = $.grep(fw_categories, function(fw_category) {
                var parent_category = fw_category.fw_categories_parent_category;
                if (fw_parent_id === null) {
                    return !parent_category || parent_category === '' || parent_category === '0';
                } else {
                    return String(parent_category) === String(fw_parent_id);
                }
            });
            
            if (fw_nodes.length === 0) return '';
            
            // Crea l'elemento UL per il livello corrente
            var fw_html = $('<ul>').addClass('fw-nested');
            $.each(fw_nodes, function(i, fw_node) {
                // Costruisci ricorsivamente i figli
                var fw_children = fw_build_tree(fw_categories, fw_node.fw_categories_id);
                var fw_has_children = fw_children !== '';
                var fw_li = $('<li>');
                
                // Crea il checkbox
                var fw_checkbox = $('<input>')
                    .attr('type', 'checkbox')
                    .attr('id', 'fw_cat_' + fw_node.fw_categories_id)
                    .attr('data-id', fw_node.fw_categories_id);
                
                fw_li.append(fw_checkbox);
                
                // Crea la label
                var labelClass = fw_parent_id === null ? 'fw-top-level' : '';
                fw_li.append($('<label>')
                    .attr('for', 'fw_cat_' + fw_node.fw_categories_id)
                    .addClass(labelClass)
                    .text(fw_node.fw_categories_name));
                
                // Aggiungi il toggle se ci sono figli
                if (fw_has_children) {
                    var fw_toggle = $('<span>').addClass('fw-toggle');
                    fw_li.append(fw_toggle);
                    fw_li.append(fw_children);
                }
                
                fw_html.append(fw_li);
            });
            
            return fw_html;
        }
        
        // Funzione per inizializzare la vista ad albero
        function fw_initialize_tree_view() {
            console.log("Initializing tree view...");
            
            var $fw_tree_view = $('#fw_tree_view');
            
            // Wrappa la vista ad albero in un container
            $fw_tree_view.wrap('<div class="fw-tree-container"></div>');
            var $fw_container = $fw_tree_view.parent();
            
            // Aggiungi il toggle globale
            var $fw_global_toggle = $('<div class="fw-global-toggle"><label><input type="checkbox" id="fw_global_toggle"> Seleziona/Deseleziona tutto</label></div><hr style="margin-bottom: 5px; margin-top: 0;">');
            $fw_container.prepend($fw_global_toggle);
            
            // Costruisci l'albero
            $fw_tree_view.empty().append(fw_build_tree(fw_categories));
            
            // Event listener per il toggle delle sottocategorie
            $fw_tree_view.on('click', '.fw-toggle', function(e) {
                console.log("Toggle clicked");
                e.preventDefault();
                e.stopPropagation();
                $(this).toggleClass('fw-toggle-down')
                    .closest('li').children('.fw-nested').toggleClass('fw-active');
                updateUserActivity();
            });
            
            // Event listener per il cambio di stato dei checkbox
            $fw_tree_view.on('change', 'input[type="checkbox"]', function() {
                console.log("Checkbox changed");
                var $this = $(this);
                var is_checked = $this.prop('checked');
                
                // Aggiorna i checkbox figli
                $this.closest('li').find('input[type="checkbox"]').prop('checked', is_checked);
                
                // Espandi le sottocategorie se selezionato
                if (is_checked) {
                    $this.parents('li').children('.fw-nested').addClass('fw-active');
                    $this.parents('li').children('.fw-toggle').addClass('fw-toggle-down');
                }
                
                // Aggiorna immediatamente il multiselect
                fw_update_multiselect();
                
                // Resetta il timer e aggiorna l'attività dell'utente
                updateUserActivity();
                
                // Avvia un nuovo timer per il submit del form
                timer = setTimeout(function() {
                    if (!isUserActive()) {
                        triggerFormSubmit();
                    }
                }, delay);
            });
            
            // Event listener per il toggle globale
            $('#fw_global_toggle').on('change', function() {
                console.log("Global toggle changed");
                var is_checked = $(this).prop('checked');
                $fw_tree_view.find('input[type="checkbox"]').prop('checked', is_checked).trigger('change');
            });
            
            // Espandi il primo livello dell'albero
            $fw_tree_view.children('.fw-nested').addClass('fw-active');
        }
        
        // Funzione per aggiornare il multiselect
        function fw_update_multiselect() {
            console.log("Updating multiselect");
            var selected_ids = [];
            $('#fw_tree_view input[type="checkbox"]:checked').each(function() {
                selected_ids.push($(this).data('id'));
            });
            
            $('[data-field_name="fw_products_categories"]').val(selected_ids).trigger('change');
            console.log("Selected IDs:", selected_ids);
        }
        
        // Funzione per sincronizzare l'albero dal multiselect
        function fw_sync_from_multiselect() {
            console.log("Syncing from multiselect");
            var selected_ids = $('[data-field_name="fw_products_categories"]').val() || [];
            
            if (!Array.isArray(selected_ids)) {
                selected_ids = [selected_ids];
            }
            
            // Deseleziona tutti i checkbox
            $('#fw_tree_view input[type="checkbox"]').prop('checked', false);
            
            // Seleziona i checkbox corrispondenti agli ID selezionati
            $.each(selected_ids, function(i, id) {
                var $checkbox = $('#fw_cat_' + id);
                if ($checkbox.length) {
                    $checkbox.prop('checked', true);
                    $checkbox.parents('li').children('.fw-nested').addClass('fw-active');
                    $checkbox.parents('li').children('.fw-toggle').addClass('fw-toggle-down');
                }
            });
            
            // Aggiorna lo stato del toggle globale
            var total_checkboxes = $('#fw_tree_view input[type="checkbox"]').length;
            var checked_checkboxes = $('#fw_tree_view input[type="checkbox"]:checked').length;
            $('#fw_global_toggle').prop('checked', checked_checkboxes > 0 && checked_checkboxes === total_checkboxes);
            
            console.log("Sync complete. Selected IDs:", selected_ids);
        }
        
        // Funzione per inviare il form
        function triggerFormSubmit() {
            console.log("Triggering form submission");
            $('form.js_form_filtro_prodotti').submit();
        }
        
        // Funzione per aggiornare l'attività dell'utente
        function updateUserActivity() {
            userActive = true;
            lastActivityTime = Date.now();
            clearTimeout(timer);
            console.log("User activity updated");
            toast('Filtri aggiornati', 'info', 'I filtri sono stati aggiornati con successo.');
        }
        
        // Funzione per verificare se l'utente è ancora attivo
        function isUserActive() {
            return (Date.now() - lastActivityTime) < delay;
        }
        
        // Aggiungi listener per l'attività dell'utente
        $('#fw_tree_view').on('click', 'input[type="checkbox"], .fw-toggle', updateUserActivity);
        
        // Inizializza la vista ad albero e sincronizza con il multiselect
        fw_initialize_tree_view();
        fw_sync_from_multiselect();
        
        // Aggiungi listener per il cambio del campo multiselect
        $('[data-field_name="fw_products_categories"]').on('change', fw_sync_from_multiselect);
        
        console.log("Tree view initialization complete");
    });
</script>
