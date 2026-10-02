<?
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<ul class="header-left">
   <li>
      <div style="margin-left: 5px; display: inline-flex; align-items: center; gap: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.25rem; letter-spacing: -0.02em;">
         <a href="?app=neighborhub" data-target="slide-out" class="header-sidenav-trigger main-menu-btn show-on-large waves-effect waves-light" style="border-radius: 50%; padding: 0;">

            <? render('components/nav_trigger_icon.php', array('icon' => 'menu', 'size' => '50')); ?>

         </a>
         <a class="header-ce-logo-home-link hover-grow hide-on-small-only" style="width: auto; margin-left: 0; padding: 0 5px; font-size: 1.3rem;border-radius: 10px;" href="/"><span class="brand-cloud">Cloud</span><span class="brand-eats">Eats</span><span class="brand-extension">.app</span></a>
      </div>
   </li>

   <? /*
   <li class="">
      <a class="share_btn" href="<?= $_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">link</i></a>
   </li>
   */ ?>
   <li class="hide-on-med-and-up">
      <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">share</i></a>
   </li>

</ul>