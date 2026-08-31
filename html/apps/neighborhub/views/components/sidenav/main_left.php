<?php
if (!defined('MB_RUNNING')) exit;

$night_mode = (App::getInstance()->get('day_night_mode') == 'night');
$merchant = $this->get('merchant');
?>
<ul id="slide-out" class="sidenav show-on-large">
  <li style="padding: 0; margin: 0; display: flex; justify-content: flex-end; align-items: center; background-color: var(--icon-bg-color);">
    <a href="#" data-target="slide-out" class="sidenav-trigger main-menu-btn"><i class="fas fa-times fa-lg"></i></a>
  </li>
  <li>
    <? render('components/sidenav/app_menu.php'); ?>
  </li>
  <? /*
    <li class="no-padding">
      <? render('components/sidenav/documentation_menu.php'); ?>
    </li>
    */ ?>
  <li class="no-padding">
    <? render('components/sidenav/user_sidenav_menu.php'); ?>
  </li>

  <? /*
  <li class="no-padding">

    <ul class="collapsible collapsible-accordion">
      <li>
        <a class="collapsible-header waves-effect waves-light">Appearance <i class="fas fa-paint-brush"></i></a>
        <div class="collapsible-body">
          <ul>
            <li class="no-padding">
              <? render('components/sidenav/displayModeToggle.php', array('night_mode' => $night_mode)); ?>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </li>
  */ ?>

  <li>
    <hr />
  </li>

  <li class="no-padding">
    <? render('components/sidenav/displayModeToggle.php', array('night_mode' => $night_mode)); ?>
  </li>

  <li>
    <hr />
  </li>
  <? /*
  <li>
    <a target="_self" href="?app=help"><i class="fas fa-question-circle"></i>Help</a>
  </li>
  <li>
    <hr />
  </li>
  */ ?>

  <? /*
    <li class="no-padding">
    <? render('components/sidenav/applications_menu.php'); ?>
  </li>
  */ ?>
  <li>
    <a href="/?app=neighborhub&p=terms-and-conditions" target="_blank"><i class="fas fa-file-contract"></i>Terms & Conditions</a>
  </li>
  <? /*
  <li>
    <a href="/?app=neighborhub&p=privacy-policy" target="_blank"><i class="fas fa-user-secret"></i>Privacy Policy</a>
  </li>
  */ ?>
  <li>
    <a href="/?app=neighborhub&view=public&p=public.splash" target="_blank"><i class="fas fa-info-circle"></i>About Neighborhub</a>
    <?
    if (get_var('app', false)) { ?>
  <li class="no-padding">
    <a href="/"><i class="fas fa-sign-out-alt"></i>Exit</a>
  </li>
<? } ?>
</ul>


<? //render('components/dialogs/contribute.php'); 
?>