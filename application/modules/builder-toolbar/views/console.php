<!-- Builder console -->
<?php if ($this->auth->is_admin()): ?>

    <!-- CODEMIRROR -->


    <?php

    $this->layout->addModuleStylesheet('builder-toolbar', 'js/codemirror-5.4/lib/codemirror.css');
    $this->layout->addModuleStylesheet('builder-toolbar', 'js/codemirror-5.4/lib/show-hint.css');

    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/lib/codemirror.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/addon/edit/matchbrackets.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/addon/edit/closebrackets.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/addon/edit/closetag.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/htmlmixed/htmlmixed.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/xml/xml.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/javascript/javascript.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/css/css.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/clike/clike.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/php/php.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/mode/sql/sql.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/addon/display/autorefresh.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/addon/hint/sql-hint.js');
    $this->layout->addModuleJavascript('builder-toolbar', 'js/codemirror-5.4/addon/hint/show-hint.js');
    ?>



    <div class="row">

        <div class="col-md-2">
            <?php

            $output = shell_exec("git log -1");
            echo "<p>Last GIT Commit: </p><pre>$output</pre>";

            ?>
            <a target="_blank" href="<?php echo base_url('builder-toolbar/builder/git_pull'); ?>">Execute a Git pull</a>
            <br />
            <a target="_blank" href="<?php echo base_url('builder-toolbar/builder/git_push'); ?>">Execute a Git Commit &
                Push</a>
        </div>


        <div class="col-md-10">
            <h3>PHP Shell (eval)</h3>
            <small>Eval code, executed in Builder Toolbar controller.</small>
            <textarea id="php_shell_code" class="form-control js_code_php_html" name="php_code"></textarea>
            <button id="execute_php_shell">Execute PHP script</button>

            <h3>OUTPUT:</h3>
            <pre id="output"></pre>
        </div>
    </div>
    <div class="builder_console">
        <div class=fakeMenu>
            <div class="fakeButtons fakeClose"></div>
            <div class="fakeButtons fakeMinimize"></div>
            <div class="fakeButtons fakeZoom"></div>
        </div>
        <div class="fakeScreen">

            <!-- Hooks -->
            <p class="line1 js_console_command">$ get executed hooks</p>
            <p class="line2 hide">
                <?php foreach ($this->datab->executed_hooks as $hook): ?>
                    - Type:
                    <?php echo $hook['type']; ?> Ref:
                    <?php echo $hook['ref']; ?> Value id:
                    <?php echo $hook['value_id']; ?> <br />

                    <?php foreach ($hook['hooks'] as $single_hook): ?>
                        |- [
                        <?php echo $single_hook['hooks_id']; ?>] Title: <a
                            href="<?php echo OPENBUILDER_BUILDER_BASEURL; ?>main/events_builder/<?php echo $single_hook['hooks_id']; ?>"
                            target="_blank">
                            <?php echo $single_hook['hooks_title']; ?>
                        </a> Module:
                        <?php echo $single_hook['hooks_module']; ?> <span class="js_show_code">Show Code</span><br />
                        <span class="line4 hide"><br />
                            <?php echo htmlentities($single_hook['hooks_content']); ?><br /><br />
                        </span>
                    <?php endforeach; ?>
                    <br />
                <?php endforeach; ?>
            </p>

            <!-- Queries -->
            <p class="line1 js_console_command">$ get slowest queries</p>
            <p class="line2 hide">
                <?php foreach ($this->session->userdata('slow_queries') as $query => $execution_time): ?>
                    - (
                    <?php echo $execution_time; ?>s)
                    <?php echo $query; ?> <br />
                <?php endforeach; ?>
            </p>

            <!-- Queries -->
            <p class="line1 js_console_command">$ get executed queries</p>
            <p class="line2 hide">
                <?php foreach ($this->db->queries as $query): ?>
                    -
                    <?php echo $query; ?> <br />
                <?php endforeach; ?>
            </p>

            <!-- Crons -->
            <p class="line1 js_console_command">$ get crons</p>
            <p class="line2 hide">
                <?php foreach ($this->fi_events->getCrons() as $cron): ?>
                    - [
                    <?php echo $cron['fi_events_id']; ?>] <a
                        href="<?php echo OPENBUILDER_BUILDER_BASEURL; ?>main/events_builder/<?php echo $cron['fi_events_id']; ?>"
                        target="_blank">
                        <?php echo $cron['fi_events_title']; ?>
                    </a> Type:
                    <?php echo $cron['crons_type']; ?> Freq:
                    <?php echo $cron['crons_frequency']; ?> min Active: <span class="line4">
                        <?php echo $cron['crons_active']; ?>
                    </span> Last Exec:
                    <?php echo $cron['crons_last_execution']; ?> Module:
                    <?php echo $cron['crons_module']; ?> <span class="js_show_code">Show code/url</span><br />
                    <span
                        class="line4 hide"><br /><code><?php echo ($cron['crons_text']) ? htmlentities($cron['crons_text']) : $cron['crons_file']; ?></code><br /><br /></span>
                <?php endforeach; ?>
            </p>

            <p class="line1 js_console_command">$ count table records</p>
            <p class="line2 hide">
                ci_sessions (
                <?php echo $this->db->query("SELECT COUNT(*) AS c FROM ci_sessions")->row()->c; ?>) <a target="_blank"
                    href="<?php echo OPENBUILDER_BUILDER_BASEURL; ?>main/query/REVMRVRFIEZST00gY2lfc2Vzc2lvbnM=">Truncate</a>
                <br />log_crm (
                <?php echo $this->db->query("SELECT COUNT(*) AS c FROM log_crm")->row()->c; ?>) <a target="_blank"
                    href="<?php echo OPENBUILDER_BUILDER_BASEURL; ?>main/query/REVMRVRFIEZST00gbG9nX2NybQ==">Truncate</a>
                <br />log_api (
                <?php echo $this->db->query("SELECT COUNT(*) AS c FROM log_api")->row()->c; ?>) <a target="_blank"
                    href="<?php echo OPENBUILDER_BUILDER_BASEURL; ?>main/query/REVMRVRFIEZST00gbG9nX2FwaQ==">Truncate</a>
            </p>

            <p class="line3">[?] What are you looking for? (Click command to execute)<span class="cursor3">_</span></p>
            <p class="line4">><span class="cursor4">_</span></p>
        </div>
    </div>


    <script>

        // AJAX EVAL CODE
        $(document).ready(function () {

            var CodeMirrorEditor = CodeMirror.fromTextArea(document.getElementById("php_shell_code"), {
                lineNumbers: true,
                matchBrackets: true,
                mode: "text/x-php",
                indentUnit: 4,
                autoCloseTags: true,
                autoCloseBrackets: true,
                autoRefresh: true
            });

            if (typeof CodeMirrorEditor !== 'undefined' && ($(this).data('weight') || $(this).data('height'))) {
                w = ($(this).data('weight')) ? $(this).data('weight') : null;
                h = ($(this).data('height')) ? $(this).data('height') : null;
                CodeMirrorEditor.setSize(w, h);
            }

            $("#execute_php_shell").click(function () {


                var token = JSON.parse(atob($('body').data('csrf')));
                var token_name = token.name;
                var token_hash = token.hash;

                var code = CodeMirrorEditor.getValue();
                $.ajax({
                    url: "<?php echo base_url('builder-toolbar/builder/execute_eval'); ?>",
                    method: "POST",
                    data: { code: code, [token_name]: token_hash },
                    dataType: "json",
                    success: function (response) {
                        $("#output").text(response.output);
                    },
                    error: function () {
                        alert("Errore durante l'esecuzione del codice.");
                    }
                });
            });
        });


    </script>


<?php endif; ?>