<?
if (!defined('MB_RUNNING')) exit;

$customer = $this->get('customer');
if ($this->get('view') === 'customer' && !$customer->terms_accepted_at):
?>
<div id="nh-terms-banner">
      <div class="nh-terms-banner-content" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
      <span>By using Cloud Eats, you agree to our <a href="/?app=neighborhub&p=terms-and-conditions" target="_blank" style="text-decoration: underline;">Terms & Conditions</a></span>
      <button type="button" id="nh-accept-terms-btn" class="nh-btn" style="margin-left: 1rem; padding: 0.25rem 0.75rem; font-size: 0.8rem; background: #e65100;">
         Accept
      </button>
   </div>
</div>
<? endif; ?>
<header class="header">

   <nav>
      <div class="nav-wrapper ">

         <? render('components/header/header_left.php', array('search_string' => $search_string)); ?>

         <?php
         if (in_array($this->get('page'), array('merchant_products', 'menus', 'dashboard', 'pos'))):
            $merchant = $this->get('merchant');

            if (!empty($merchant->image_url)): ?>
               <div class="merchant-header-image">
                  <img class="materialboxed responsive-img circle" src="<?php echo htmlspecialchars($merchant->image_url); ?>" style="">
                  <?= ($merchant->status == 'online') ? '<span class="online-status-dot"></span>' : '' ?>
               </div>

            <?php endif; ?>
         <?php endif; ?>



         <? render('components/header/header_right.php', array('search_string' => $search_string)); ?>


      </div>
      <div class="progress"><div class="indeterminate"></div></div>
   </nav>

</header>

<? render('components/sidenav/main_left.php'); ?>