<?php
$this->layout->addModuleStylesheet('sticky-notes', 'css/jquery.postitall.css');
$this->layout->addModuleJavascript('sticky-notes', 'js/jquery.postitall.js');

$this->layout->addModuleStylesheet('sticky-notes', 'css/sticky-notes.css');
$this->layout->addModuleJavascript('sticky-notes', 'js/sticky-notes.js');

$notes_count = $this->db->query("SELECT COUNT(*) AS c FROM sticky_notes WHERE sticky_notes_user_id = '{$this->auth->get('users_id')}' AND sticky_notes_deleted = '0'")->row()->c;

$notes_count_badge = ($notes_count > 0) ? '<span class="badge bg-red btn-notes-badge">' . $notes_count . '</span>' : null;
?>

<button id="buttonToggleStickyNotes" type="button" class="btn btn-sm btn_footer btn-notes" data-toggle="tooltip" title="" data-original-title="Sticky Notes">
    <?php echo $notes_count_badge ?>
    <i class="far fa-sticky-note fa-fw"></i>
</button>

<button id="buttonCreateStickyNote" style="display:none; background-color: #fff087;" type="button" class="btn btn-circle btn-br" data-toggle="tooltip" title="" data-original-title="Create Sticky Note">
    <i class="fas fa-plus fa-2x"></i>
</button>

<div id="stickyNotesOverlay">

</div>