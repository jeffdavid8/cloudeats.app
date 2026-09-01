<?
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<ul class="left">
   <li>
      <? if (!$merchant): ?>
         <div style="display: inline-flex; align-items: center; gap: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.25rem; letter-spacing: -0.02em;">
            <a href="?app=neighborhub" data-target="slide-out" class="header-sidenav-trigger main-menu-btn show-on-large waves-effect waves-light" style="border-radius: 50%; padding: 0;">

               <? render('components/nav_trigger_icon.php', array('icon' => 'menu', 'size' => '50')); ?>

            </a>
            <span class="brand-cloud">Cloud</span><span class="brand-eats">Eats</span>
         </div>
      <? else: ?> <a href="#" data-target="slide-out" class="sidenav-trigger main-menu-btn show-on-large"><i class="material-icons">menu</i></a>
      <? endif; ?>
   </li>

   <? /*
   <li class="">
      <a class="share_btn" href="<?= $_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">link</i></a>
   </li>
   */ ?>
   <li>
      <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">share</i></a>
   </li>

</ul>