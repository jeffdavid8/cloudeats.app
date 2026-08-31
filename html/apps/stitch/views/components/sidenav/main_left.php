<?php
if (!defined('MB_RUNNING')) exit;

$night_mode = ($this->get('day_night_mode') == 'night');

?>
<ul id="slide-out" class="sidenav show-on-large">
  <li>
    <a href="<?= app_root_url() ?>">
      <i class="material-icons">home</i>
      Home
    </a>
  </li>
  <li class="no-padding">
    <a href="#modal-importer" class="modal-trigger">
      <i class="material-icons">cloud_upload</i>
      Import Data
    </a>
  </li>
  <li>
    <ul class="collapsible collapsible-accordion">
      <li>
        <a class="collapsible-header waves-effect waves-light" style="padding-left: 10px;">
          <i class="material-icons">palette</i>
          Admin
        </a>
        <div class="collapsible-body no-padding">
          <? render('components/sidenav/stitch_admin_menu.php'); ?>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <ul class="collapsible collapsible-accordion">
      <li>
        <a class="collapsible-header waves-effect waves-light" style="padding-left: 10px;">
          <i class="material-icons">palette</i>
          Appearance
        </a>
        <div class="collapsible-body no-padding">
          <ul>
            <li class="no-padding">
              <? render('components/sidenav/displayModeToggle.php', array('night_mode' => $night_mode)); ?>
            </li>
            <li class="no-padding">
              <? render('components/sidenav/audioModeToggle.php'); ?>
            </li>
            <li class="no-padding">
              <? render('components/background_selector_menu.php'); ?>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <hr />
  </li>
  <li class="no-padding">
    <? render('components/sidenav/applications_menu.php'); ?>
  </li>
  <? /*
    <li class="no-padding">
      <? render('components/sidenav/documentation_menu.php'); ?>
    </li>
    */ ?>
  <li class="no-padding">
    <? render('components/sidenav/user_sidenav_menu.php'); ?>
  </li>
  <li>
    <hr />
  </li>
  <li class="no-padding">
    <a href="/"><i class="fas fa-sign-out-alt"></i>Exit</a>
  </li>
  <? /*
    <li class="no-padding">
        <a class="modal-trigger" data-target="contribute_dialog" ><i class="fas fa-donate"></i>Contribute</a>
    </li>
    <li><hr/></li>
    */ ?>
</ul>