<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css'/>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/material.css'>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/fold/foldgutter.css'>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/display/fullscreen.css'>

<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/sql/sql.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/hint/sql-hint.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/fold/foldcode.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/fold/foldgutter.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/fold/brace-fold.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/display/fullscreen.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/display/autorefresh.js'></script>

<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css'/>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js'></script>


<style>
    .CodeMirror-line {
        font-weight: bold;
    }

    .CodeMirror {
        resize: vertical;
        min-height: 200px; /* altezza minima dell'editor */
        overflow: auto;
    }
</style>

<?php
$query = null;
if (!empty($this->input->get('query'))) {
    $query = base64_decode($this->input->get('query'));
}

?>

<div class="row">
    <div class="col-sm-12">
        <div class='box box-primary'>
            <div class='box-header with-border'>
                <i class='fas fa-terminal fa-fw'></i>
                <h3 class='box-title'>Execute query</h3>

                <div class='box-tools pull-right'>
                    <div class="form-inline">
                        <div class="form-group">
                            <label for="">Safe mode</label>
                            <label class='radio-inline'>
                                <input type='radio' name="safe_mode" value='yes' checked> Yes
                            </label>
                            <label class='radio-inline'>
                                <input type='radio' name='safe_mode' value='no'> No
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <textarea id='sql-editor' name="query"><?php echo $query; ?></textarea>

            <div class="box-body clearfix">
                <button type='button' class='btn btn-primary btn-execute pull-right'>Execute query <i class='fas fa-play fa-fw'></i></button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class='box box-warning query-results hide'>
            <div class='box-header with-border'>
                <i class='fas fa-terminal fa-fw'></i>
                <h3 class='box-title'>Query Results</h3>
            </div>

            <div class='box-body'>
                <table id='query_results' class='table table-condensed table-striped table-hover display nowrap' style='width: 100%'></table>
            </div>
        </div>
    </div>
</div>

<script>
    const editor = CodeMirror.fromTextArea(document.getElementById('sql-editor'), {
        mode: 'sql',
        theme: 'material',
        lineNumbers: true,
        foldGutter: true,
        placeholder: 'SELECT * FROM users',
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: {
            'F11': function (cm) {
                cm.setOption('fullScreen', !cm.getOption('fullScreen'));
            },
            'Esc': function (cm) {
                if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
            }
        },
        autoRefresh: true
    });

    $(function () {
        $('[name="safe_mode"]').on('click', function () {
            var this_val = $(this).val();

            $('[name="safe_mode"]').removeAttr('checked');

            $('[name="safe_mode"][value="' + this_val + '"]').attr('checked', '').prop('checked', true)
        });

        $('.btn-execute').on('click', function () {
            const query = editor.getValue();

            if (!query) {
                alert("CANNOT RUN EMPTY QUERY");

                return false;
            }

            var keywords = ["DROP", "UPDATE", "ALTER", "TRUNCATE", "DELETE", "GRANT", "REVOKE"]; // l'array di parole chiave da cercare
            var regex = new RegExp(keywords.join('|'), 'gi'); // creiamo una regex che cerca una delle parole chiave

            const safe_mode = $('[name="safe_mode"]:checked').val();

            if (query.match(regex) && safe_mode === 'yes') {
                alert("Stai cercando di eseguire una query pericolosa con la Safe Mode attiva.");
                return false;
            }

            const $button = $(this);

            $('.query-results').addClass('hide');
            if ($.fn.DataTable.isDataTable('#query_results')) {
                $('#query_results').DataTable().destroy();
                $('#query_results').html('');
            }

            $button.prop('disabled', true);

            $.ajax({
                url: base_url + 'core-entities/core_entities/run_query',
                type: 'post',
                dataType: 'json',
                async: false,
                data: {
                    [token_name]: token_hash,
                    query: query
                },
                success: function (response) {
                    if (response.status == '0') {
                        $.toast({
                            heading: 'Error',
                            text: response.error ?? response.txt,
                            icon: 'error',
                            loader: true,
                            position: 'top-right',
                            loaderBg: '#dd4b39'
                        });

                        return false;
                    }

                    const data = response.data;

                    if (response.data && data.rows) {
                        const rows = data.rows;
                        const columns = data.columns;

                        $('.query-results').removeClass('hide');

                        var table = $('#query_results').DataTable({
                            columns: columns,
                            serverSide: false,
                            scrollX: true,
                            lengthMenu: [
                                [5, 10, 25, 50, -1],
                                [5, 10, 25, 50, 'All']
                            ],
                            pageLength: 10,
                            order: [
                                [0, 'asc']
                            ],
                        });

                        table.rows.add(rows);
                        table.columns.adjust().draw();
                    } else {
                        $.toast({
                            heading: 'Error',
                            text: 'No data',
                            icon: 'error',
                            loader: true,
                            position: 'top-right',
                            loaderBg: '#dd4b39'
                        });
                    }

                    $button.prop('disabled', false);
                },
                error: function (xhr, request, error) {
                    alert('Si è verificato un errore di database.\nMaggiori dettagli in console');
                    console.log(xhr.responseText);
                    $button.prop('disabled', false);
                }
            })
        });
    });
</script>
