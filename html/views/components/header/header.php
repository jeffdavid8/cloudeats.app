<?
if (!defined('MB_RUNNING')) exit;

$customer = $this->get('customer');
$merchant = $this->get('merchant', null);
$canOpenMerchantMenu = $this->appName === 'neighborhub'
   && $merchant && !empty($merchant->id)
   && $this->user && !empty($this->user->id)
   && ($this->user->is_admin || $this->user->isMerchantUser((int)$merchant->id));
$operationalStatus = null;
if (
   $merchant
   && !empty($merchant->id)
   && class_exists('Merchant')
) {
   $operationalStatus = Merchant::getOperationalStatus($merchant);
}
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

         <? render('components/header/header_left.php', array('search_string' => $search_string, 'canOpenMerchantMenu' => $canOpenMerchantMenu, 'operationalStatus' => $operationalStatus)); ?>

         <? render('components/header/header_right.php', array('search_string' => $search_string, 'canOpenMerchantMenu' => $canOpenMerchantMenu, 'headerMerchant' => $merchant, 'operationalStatus' => $operationalStatus)); ?>


      </div>
<?php if ($operationalStatus && $this->get('view') === 'customer'): ?>
<div
   id="nh-merchant-operational-status"
   role="status"
   data-merchant-id="<?= (int)$merchant->id ?>"
   data-status="<?= htmlspecialchars($operationalStatus['status'], ENT_QUOTES, 'UTF-8') ?>"
   class="nh-merchant-operational-status is-<?= $operationalStatus['status'] === 'open' ? 'open' : 'closed' ?><?= $operationalStatus['urgency'] ? ' is-' . htmlspecialchars($operationalStatus['urgency'], ENT_QUOTES, 'UTF-8') : '' ?>"
>
   <span class="nh-merchant-operational-dot" aria-hidden="true"></span>
   <strong data-status-label><?= htmlspecialchars($operationalStatus['label'], ENT_QUOTES, 'UTF-8') ?></strong>
   <span data-status-message><?= htmlspecialchars($operationalStatus['message'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
<?php endif; ?>
<?php if ($merchant && !empty($merchant->id) && $this->get('view') === 'customer'): ?>
      <div id="header-merchant-search-panel" class="header-merchant-search-panel" role="search" hidden>
         <div class="header-merchant-search-row">
            <div class="header-merchant-search-field">
               <i class="fas fa-search" aria-hidden="true"></i>
               <input type="search" id="header-merchant-search-input" placeholder="Search this menu" aria-label="Search <?= htmlspecialchars($merchant->business_name ?? 'merchant', ENT_QUOTES, 'UTF-8') ?> menu" autocomplete="off">
               <button type="button" class="header-merchant-search-clear" aria-label="Clear search" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <button type="button" class="header-merchant-search-cancel">Cancel</button>
         </div>
         <div class="header-merchant-search-feedback" role="status" aria-live="polite" aria-atomic="true">
            <p id="header-merchant-search-result-count" hidden></p>
            <p id="header-merchant-search-no-results" hidden>No items found. Try a different search.</p>
         </div>
      </div>
<?php endif; ?>
      <div class="progress"><div class="indeterminate"></div></div>
   </nav>

</header>

<? render('components/sidenav/main_left.php'); ?>
<?php if ($canOpenMerchantMenu): ?>
<ul id="merchant-slide-out" class="sidenav right-aligned" aria-label="Merchant navigation">
   <li class="merchant-sidenav-header">
      <a href="#!" class="merchant-sidenav-close" aria-label="Close merchant navigation"><i class="fas fa-times fa-lg" aria-hidden="true"></i></a>
   </li>
   <? render('components/sidenav/merchant_menu.php'); ?>
</ul>
<?php endif; ?>