var changed = function (instance, cell, x, y, value) {

    if (colspanned_rows.includes(y + 1)) {
        //Skip this row
        //console.log('Skip change for colspanned rows');
    } else {

        var cellName = jexcel.getColumnNameFromId([x, y]);
        console.log('New change on cell ' + cellName + ' to: ' + value + '');

        // console.log(cell);
        //console.log(cell.getMeta());
        var meta = table.getMeta(cellName);


        if (meta.field_name && meta.id && meta.entity_name) {
            var entity_name = meta.entity_name;
            var field_name = meta.field_name;
            var id = meta.id;

            $.ajax({
                url: base_url + 'db_ajax/change_value/' + entity_name + '/' + id + '/' + field_name + '/' + value,
                type: "POST",
                data: {
                    [token_name]: token_hash
                },
                async: true,
                success: function (response, textStatus, jqXHR) {
                    console.log(response);
                    //alert('Salvato!');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        } else {
            alert("Questo campo non è modificabile tramite la tabella.");
        }
    }


}



var mergeCells = {};
$.each(colspanned_rows, function (i, el) {
    //A1: [12, 1],
    mergeCells['A' + el] = [14, 1];
});



var table = jspreadsheet(document.getElementById('spreadsheet'), {
    onload: function (el, instance) {
        //header background
        var x = 1 // column A
        $(instance.thead).find("tr td").css({
            'background-color': '#2b66c4',
            'color': '#ffffff',
            'font-weight': 'bold',
            'font-size': '16px'
        });
    },
    //TODO: le task devono contenere il nome progetto
    search: false,
    pagination: 200,
    data: data,

    defaultColAlign: 'left',
    columns: [{
        type: 'html',
        title: 'Progetto',
        width: 100,
    },
    {
        type: 'html',
        title: 'Task',
        width: 220,
    },
    {
        type: 'dropdown',
        title: 'Status',
        width: 100,
        source: tasks_status,
        //filter: dropdownFilter
    },
    {
        type: 'calendar',
        title: 'Inizio',
        width: 80,
        align: 'center',
    },
    {
        type: 'calendar',
        title: 'Fine',
        width: 80,
        align: 'center',
    },
    {
        type: 'calendar',
        title: 'Consegna',
        width: 80,
        align: 'center'
    },
    {
        type: 'dropdown',
        title: 'Priority',
        width: 70,
        align: 'center',
        source: tasks_priority
    },
    {
        type: 'numeric',
        title: 'EST',
        width: 60,
        align: 'center',
    },
    {
        type: 'numeric',
        title: 'Ore lav.',
        width: 60,
        align: 'center',
    },
    /*{
        type: 'numeric',
        title: '€',
        width: 60,
        align: 'center',
    },*/
    {
        type: 'numeric',
        title: '€',
        width: 60,
        mask: '€ #.##',
        align: 'center',
    },
    {
        type: 'numeric',
        title: 'B. Hours',
        width: 70,
        align: 'center',
    },
    {
        type: 'html',
        title: 'Progress',
        width: 80,
        align: 'center',
    },
    {
        type: 'checkbox',
        title: 'Delivered',
        width: 70,
        align: 'center',

    },
    {
        type: 'text',
        title: 'Note',
        width: 120,
    },

    ],
    onchange: changed,
    mergeCells: mergeCells,
    style: styles,
    meta: meta
});
//hide row number column
table.hideIndex();