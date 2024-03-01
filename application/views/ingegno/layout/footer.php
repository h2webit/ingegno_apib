<!-- Fixed footer -->
<footer class="main-footer no-print">

    <div class="left_side pull-left hidden-xs">
        <div class="btn-group">
            <div class="">
                {tpl-pre-footer}
            </div>
        </div>
    </div>

    <div class="center_side pull-left hidden-xs">
        <a href="https://ingegnosuite.it" target="_blank" class="footer_link"><strong>INGEGNO SUITE</strong></a> -
        Copyright &copy; 2015-
        <?php echo date('Y'); ?> - All right reserved - Built with <a href="https://openbuilder.net" target="_blank" class="footer_link"><strong>Open Builder</strong></a> - By <a href="https://h2web.it" target="_blank" class="footer_link"><strong>H2 S.r.l.</strong></a>
    </div>

    <div class="right_side pull-right hidden-xs">
        <div class="">
            {tpl-post-footer}
        </div>

        <b>
            <?php e('Version'); ?>
        </b>
        <?php echo VERSION; ?>

    </div>

</footer>

<div id="js_modal_container"></div>

<!-- Side view modal -->
<div id="modal-side-view" class="modal-side-hidden mobile-full-width">
    <button id="close-modal-side-view" class="modal-side-close-button">×</button>
    <div id="modal-side-content-view">
        <div id="modal-side-content-form-view">
        </div>
    </div>
</div>