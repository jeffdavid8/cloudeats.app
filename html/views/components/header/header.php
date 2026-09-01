<?
if (!defined('MB_RUNNING')) exit;

logger(App::getInstance()->appName);
logger($this->app->appName);

$merchant = $this->get('merchant');
$products = $this->get('products');
$customer = $this->get('customer');
if (!$merchant) {
   $nh = App::getInstance('neighborhub');
   $nh->includeModel('merchant');
   $merchant = Merchant::getMerchantById(1);
}

if (!$customer->terms_accepted_at && get_var('app') == 'neighborhub' || ($_SERVER['REQUEST_URI'] === '/')):
?>
   <div id="nh-terms-banner">
      <div class="nh-terms-banner-content" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
         <span>By using Cloud Eats, you agree to our <a href="/?app=neighborhub&p=terms-and-conditions" target="_blank" style="text-decoration: underline;">Terms & Conditions</a>.</span>
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
         $groupedProducts = [];
         foreach ($products as $product) {
            $groupedProducts[$product['category']][] = $product;
         } 
         $logo_image = $this->get('app_logo_image', $this->config['site_logo_url']);
         ?>

         <div class="merchant-header-image">
            <? if ($_SERVER['REQUEST_URI'] === '/') { ?>
               <img class="responsive-img circle" src="<?= $logo_image; ?>" style="">
            <? } else { ?>
               <a href="/" style="display: inherit; padding: 0;">
                  <img class="responsive-img circle" src="<?= $logo_image; ?>" style="">
               </a>
            <? } ?>

            <?= ($merchant->status == 'online') ? '<span class="online-status-dot"></span>' : '' ?>
         </div>
         <? render('components/header/header_right.php', array('search_string' => $search_string)); ?>


      </div>
   </nav>

</header>

<? render('components/sidenav/main_left.php'); ?>