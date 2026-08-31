<!-- Modal Structure -->
<div id="under_construction" class="modal" data-component="under-construction" data-show-notice="<?= json_encode(!isset($_SESSION['development_notice']) || !$_SESSION['development_notice']); ?>">
   <div class="modal-content">
      <h4>Development Notice</h4>
      <p>This app is currently under development, and some of its features may not be fully functional yet.  Thank you for understanding.</p>
  </div>
  <div class="modal-footer">
    <a href="#!" class="modal-close waves-effect waves-green btn-flat">Ok</a>
  </div>
</div>

<?
// Mark that the development notice has been shown
if (!isset($_SESSION['development_notice']) || !$_SESSION['development_notice']) {
    $_SESSION['development_notice'] = true;
}
?>
