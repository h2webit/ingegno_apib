<?php if(!$this->auth->is_admin()) die(redirect(base_url(), 'refresh')); ?>
<link href='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css' rel='stylesheet'/>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js'></script>

<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/elfinder/2.1.61/css/elfinder.full.min.css' />
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/elfinder/2.1.61/css/theme.min.css' />

<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/elfinder-material-theme@2.1.15/Material/css/theme-light.min.css' />

<script src='https://cdnjs.cloudflare.com/ajax/libs/elfinder/2.1.61/js/elfinder.full.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/elfinder/2.1.61/js/extras/editors.default.min.js'></script>

<script>
    $(function () {
        var elf = $('#elfinder').elfinder({
            url: base_url + 'core-file-manager/main/elfinder_init',
            width: '100%',
            height: '1024px',
            resizable: false,
            customData: {
                [token_name]: token_hash,
            },
            uiOptions: {
                toolbar: [
                    ['back', 'forward'],
                    ['reload'],
                    ['up'],
                    ['mkdir', 'mkfile', 'upload'],
                    ['open', 'download', 'getfile'],
                    ['info'],
                    ['quicklook'],
                    ['copy', 'cut', 'paste'],
                    ['rm'],
                    ['duplicate', 'rename', 'edit', 'resize'],
                    ['extract', 'archive'],
                    ['search'],
                    ['view'],
                ],
            },
            contextmenu: {
                navbar: ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],
                cwd: ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],
                files: [
                    'getfile', '|', 'open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|',
                    'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info'
                ]
            },
        }).elfinder('instance');

        // fit to window.height on window.resize
        var resizeTimer = null;
        $(window).resize(function () {
            resizeTimer && clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                var h = parseInt($(window).height()) - 20;
                if (h != parseInt($('#elfinder').height())) {
                    elf.resize('100%', h);
                }
            }, 200);
        });
    });
</script>

<div id='elfinder'></div>
