//Global vars : enable and disable features and change the notes behaviour
$.fn.postitall.globals = {
    prefix: '#StickyNotes_', //Id note prefixe
    filter: 'domain', //Options: domain, page, all
    savable: false, //Save postit in storage
    randomColor: false, //Random color in new postits
    toolbar: true, //Show or hide toolbar
    autoHideToolBar: false, //Animation efect on hover over postit shoing/hiding toolbar options
    removable: false, //Set removable feature on or off
    askOnDelete: false, //Confirmation before note remove
    draggable: true, //Set draggable feature on or off
    resizable: true, //Set resizable feature on or off
    editable: true, //Set contenteditable and enable changing note content
    changeoptions: false, //Set options feature on or off
    blocked: false, //Postit can not be modified
    hidden: false, //The note can be hidden
    minimized: false, //true = minimized, false = maximixed
    expand: false, //Expand note
    fixed: false, //Allow to fix the note in page
    addNew: false, //Create a new postit
    showInfo: false, //Show info icon (info tab)
    showMeta: false, //Show info icon (meta tab)
    pasteHtml: true, //Allow paste html in contenteditor
    htmlEditor: false, //Html editor (trumbowyg)
    autoPosition: true, //Automatic reposition of the notes when user resize screen
    addArrow: 'back', //Add arrow to notes : none, front, back, all
    askOnHide: true, //Show configuration hideUntil back-panel (getBackPanelHideUntil)
    hideUntil: null, //Note will be hidden since that datetime
    export: false, //Note can be exported
    style: {
        tresd: true
    },
    cssclases: {
        note: "customNote"
    }
};

function getNotes() {
    $.ajax({
        url: base_url + 'sticky-notes/stickynotes/get',
        type: 'post',
        dataType: 'json',
        data: {
            [token_name]: token_hash,
        },
        success: function(res) {
            if (res.status == '1') {
                if (res.txt.length > 0) {
                    var notes = res.txt;

                    $.each(notes, function(index, note) {
                        var postit = createNote(note);
                    });
                } else {
                    var postit = createNote();
                }
            }
        },
        error: function() {

        }
    });
}

function createNote(note = null) {
    var options = {
        style: {
            tresd: true,
            textshadow: false,
            backgroundcolor: '#fff087'
        },
        onCreated: function(id, options, obj) {
            if (!id) {
                $.ajax({
                    url: base_url + 'sticky-notes/stickynotes/new',
                    type: 'post',
                    dataType: 'json',
                    data: {
                        [token_name]: token_hash,
                        stickynote: options
                    }
                });

            }

            createDeleteButton(id);
        },
        onChange: function(id) {
            if (id) {
                var options = $(id).postitall('options');

                editNote(options);
            }
        }
    };

    if (note) {
        Object.assign(options, {
            id: note.sticky_notes_uuid,
            content: note.sticky_notes_content,
            posX: note.sticky_notes_pos_x,
            posY: note.sticky_notes_pos_y,
            width: note.sticky_notes_width,
            height: note.sticky_notes_height,
        });
    } else {
        Object.assign(options.style, {
            width: 800,
            height: 200,
        });
    }

    var postit = $.PostItAll.new(options);

    return postit;
}

function editNote(note = null) {
    $.ajax({
        url: base_url + 'sticky-notes/stickynotes/edit',
        type: 'post',
        dataType: 'json',
        data: {
            [token_name]: token_hash,
            stickynote: note
        }
    });
}

function createDeleteButton(el_id) {
    var $sticky = $(el_id);
    var $toolbar = $sticky.find('.PIAIconTopToolbar');
    var options = $sticky.postitall('options');

    if ($('.deleteNoteBtn', $toolbar).length == 0) {
        var button = $(document.createElement('button')).prop({
            type: 'button',
            class: 'deleteNoteBtn'
        });

        button.on('click', function(e) {
            e.preventDefault();

            var this_btn = $(this);
            var note_id = this_btn.data('id');

            if (!confirm('Sei sicuro?')) {
                return false;
            }

            deleteNote(options);

            $.PostItAll.destroy(el_id);
            e.preventDefault();
        })

        $toolbar.append(button);
    }
}

function deleteNote(note) {
    $.ajax({
        url: base_url + 'sticky-notes/stickynotes/delete',
        type: 'post',
        dataType: 'json',
        data: {
            [token_name]: token_hash,
            stickynote: note
        }
    });
}

$(function() {
    var stickyNotesContainer = $('#stickyNotesOverlay');
    var btnToggleStickyNotes = $('#buttonToggleStickyNotes');
    var btnCreateStickyNote = $('#buttonCreateStickyNote');

    stickyNotesContainer.append('body');

    btnToggleStickyNotes.on('click', function() {
        if (stickyNotesContainer.is(':visible')) {
            stickyNotesContainer.hide();
            btnCreateStickyNote.hide();
            $.PostItAll.remove();
        } else {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            stickyNotesContainer.add(btnCreateStickyNote).show();

            getNotes();

            btnCreateStickyNote.on('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();

                var postit = createNote();

                return false;
            });
        }
    });

    $(document).click(function(e) {
        var clickedTarget = e.target.id;

        if (stickyNotesContainer.is(':visible') && clickedTarget == 'stickyNotesOverlay') {
            btnToggleStickyNotes.trigger('click');
        }
    });
})