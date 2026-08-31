<?
$merchant = $this->get('merchant');
?>
<ul class="left">
   <li>
      <? if (!$merchant): ?>
      <div style="display: inline-flex; align-items: center; gap: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.25rem; letter-spacing: -0.02em;">
         <a href="?app=neighborhub" data-target="slide-out" class="header-sidenav-trigger main-menu-btn show-on-large waves-effect waves-light" style="border-radius: 50%; padding: 0;">
            <i class="material-icons">menu</i>
            <? /* <img style="position: inherit;" class="brand-logo" src="<?= $this->config['site_logo_url'] ?>"> */ ?>
         </a>
         <span style="font-weight: 700; color: #2D3748;">Cloud</span><span style="font-weight: 400; color: #FF6B6B;">Eats</span>
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
      <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] . $_SERVER['REQUEST_URI'] ?>"><i class="material-icons">share</i></a>
   </li>

</ul>