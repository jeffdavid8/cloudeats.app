<?
//

?>


<div id="applicationsModal" class="applications-modal star-trek-modal">
  <div class="modal-content applications-container">
    <div class="modal-header applications-header">
      <div class="modal-close" onclick="window.history.back(); return false;">&times;</div>
      <h1 class="applications-title">Applications</h1>

    </div>
    <div class="modal-body applications-content">
      <? render('components/applications_list.php'); ?>
    </div>
    <div class="applications-footer">
    </div>
  </div>
</div>