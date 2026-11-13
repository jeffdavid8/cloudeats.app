<?php if (!defined('MB_RUNNING')) exit; ?>
<li>
   <span class="header-merchant-brand" title="<?= htmlspecialchars($merchant->business_name ?? 'Merchant storefront', ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($canOpenMerchantMenu): ?>
      <a href="#merchant-slide-out" class="header-merchant-action merchant-sidenav-trigger" aria-controls="merchant-slide-out" aria-expanded="false" aria-label="Open merchant navigation for <?= htmlspecialchars($merchant->business_name ?? 'Merchant', ENT_QUOTES, 'UTF-8') ?>">
      <?php elseif ($this->get('view') === 'merchant'): ?>
      <a href="?app=neighborhub&amp;view=merchant&amp;p=dashboard&amp;merchant_id=<?= (int)$merchant->id ?>" class="header-merchant-action" aria-label="Merchant dashboard for <?= htmlspecialchars($merchant->business_name ?? 'Merchant', ENT_QUOTES, 'UTF-8') ?>">
      <?php else: ?>
      <a href="#" class="header-merchant-action scroll-to-top-link" aria-label="Return to top of <?= htmlspecialchars($merchant->business_name ?? 'merchant', ENT_QUOTES, 'UTF-8') ?>">
      <?php endif; ?>
         <?php if (!empty($merchant->image_url)): ?>
         <img src="<?= htmlspecialchars($merchant->image_url, ENT_QUOTES, 'UTF-8') ?>" alt="">
         <?php else: ?>
         <i class="fas fa-store header-merchant-fallback" aria-hidden="true"></i>
         <?php endif; ?>
      </a>
      <?php if (!empty($operationalStatus)): ?>
         <span class="header-merchant-status-dot" data-merchant-id="<?= (int)$merchant->id ?>" aria-label="Open now"<?= $operationalStatus['status'] === 'open' ? '' : ' hidden' ?>></span>
      <?php endif; ?>
   </span>
</li>
