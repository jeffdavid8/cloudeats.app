<?
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<ul class="left">
   <li>
      <? if (!$merchant): ?>
         <div style="display: inline-flex; align-items: center; gap: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.25rem; letter-spacing: -0.02em;">
            <a href="?app=neighborhub" data-target="slide-out" class="header-sidenav-trigger main-menu-btn show-on-large waves-effect waves-light" style="border-radius: 50%; padding: 0;">

               <svg class="nav-trigger-icon" viewBox="0 0 100 100" xmlns="http://w3.org">
                  <!-- Outer circular background track -->
                  <circle cx="50" cy="50" r="46" class="icon-bg" />

                  <!-- "C" Outer ring envelope -->
                  <path d="M 76,34 A 32,32 0 1,0 76,66" class="letter-c" />

                  <!-- "E" Inner core element -->
                  <path d="M 44,38 H 64 M 44,50 H 60 M 44,62 H 64 M 44,38 V 62" class="letter-e" />
               </svg>

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