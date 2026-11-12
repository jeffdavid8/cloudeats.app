<?php if (!defined('MB_RUNNING')) exit; ?>
<li>
   <span class="header-merchant-brand" title="<?= htmlspecialchars($merchant->business_name ?? 'Merchant storefront', ENT_QUOTES, 'UTF-8') ?>">
      <a href="#merchant-slide-out" class="merchant-sidenav-trigger" aria-controls="merchant-slide-out" aria-expanded="false" aria-label="Open merchant navigation for <?= htmlspecialchars($merchant->business_name ?? 'Merchant', ENT_QUOTES, 'UTF-8') ?>">
         <img src="<?= htmlspecialchars($merchant->image_url, ENT_QUOTES, 'UTF-8') ?>" alt="">
      </a>
      <?php if (!empty($operationalStatus)): ?>
         <span class="header-merchant-status-dot" data-merchant-id="<?= (int)$merchant->id ?>" aria-label="Open now"<?= $operationalStatus['status'] === 'open' ? '' : ' hidden' ?>></span>
      <?php endif; ?>
   </span>
</li>
