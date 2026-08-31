<?
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<ul class="left">
   <li>
      <? if (!$merchant): ?>
      <a href="?app=neighborhub" data-target="slide-out" class="header-sidenav-trigger main-menu-btn show-on-large waves-effect waves-light" style="border-radius: 50%; padding: 0;">
         <img style="position: inherit;" class="brand-logo" src="apps/neighborhub/images/neighborhub-logo-black-circle-2020-600.png">
      </a>
      <? else: ?> <a href="#" data-target="slide-out" class="sidenav-trigger main-menu-btn show-on-large"><i class="material-icons">menu</i></a>
      <? endif; ?>
   </li>
   <? /*
   <li class="">
      <a class="share_btn" href="<?= $_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">link</i></a>
   </li>
   */ ?>
   <li>
      <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'].$_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">share</i></a>
   </li>

</ul>