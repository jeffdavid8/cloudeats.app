
<div id="deck-container" class="modal mb-modal-fixed subtle-scanning-line-effect">
  <div class="modal-header">
    <div style="display: block; width: 70%;text-align: left;">
      <h4 style="font-size: 1.1em;"><i class="material-icons">settings_input_component</i> Data_Ingestion_Module</h4>
    </div>
    <div style="display: flex; justify-content: flex-end; width: 100px; text-align: right;">
        <? render('components/stitch_form_context_menu.php', array('style' => 'context-menu')) ?>
        <button class="close" onclick="toggleStitchForm(); return false;" ><i class="material-icons">close</i></button>
    </div>
  </div>


  <div class="modal-content">
    <? /* <div id="content-masque"></div> */ ?>
    <div id="inline-stitch-stage" style="margin-bottom: 0;">

      <div class="" style="border: 1px solid #9b59b2; border-radius: 12px;">
        <div class="card-inner-bevel">

          <? render('stitch_form.php'); ?>

        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer grey darken-4">

  </div>
</div>