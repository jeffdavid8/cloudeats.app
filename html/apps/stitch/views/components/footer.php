
  <? render('components/deck-container.php') ?>

  <? render('components/field_header.php') ?>

</div>
<footer class="bodyImageBgOverlay">


  <div class="credits-wrapper">
    <div class="powered-by">
      <span class="version" style="margin-right: 5px; opacity: 0.5; font-family: monospace;">
        MB_CORE_v<?= App::getInstance()->app_info['version'] ?>_STABLE
      </span>
      <? render('components/powered-by.php'); ?>
    </div>
  </div>
</footer>