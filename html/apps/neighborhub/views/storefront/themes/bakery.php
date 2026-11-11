<?php
if (!defined('MB_RUNNING')) exit;

require __DIR__ . '/default.php';
$merchant = $this->get('merchant');
$assetUrls = is_array($configuration['asset_urls'] ?? null) ? $configuration['asset_urls'] : [];
$heroId = (int)($configuration['assets']['hero_id'] ?? 0);
$merchantImage = htmlspecialchars((string)($assetUrls[$heroId] ?? ($merchant->image_url ?? '')), ENT_QUOTES, 'UTF-8');
?>
<style>
  body {
    --brand-bg: #fffaf3;
    --brand-card-bg: #fffdf9;
    --brand-text-main: #3f2a20;
    --brand-text-muted: #806858;
    --brand-border: rgba(126, 87, 67, .18);
    --brand-nav-bg: #6f3f2b;
    --brand-nav-border: #d79a4a;
    --brand-primary: #b65f32;
    --brand-secondary: #d79a4a;
    --brand-gold: #d79a4a;
    --brand-btn-add: #7d4931;
    --brand-btn-add-hover: #5f3525;
    --brand-pill-bg: #fff4e3;
  }

  .merchant-storefront-hero {
    background:
      linear-gradient(90deg, rgba(63, 42, 32, .88), rgba(126, 87, 67, .48)),
      url('<?= $merchantImage ?>') center / cover;
    border-color: rgba(215, 154, 74, .45);
  }

  .merchant-storefront-hero h1,
  .merchant-storefront-block h3 { color: #fffaf3; }
  .merchant-storefront-hero p,
  .merchant-storefront-block p { color: #f7e8d4; }
  .merchant-storefront-block {
    background: #fffdf9;
    border: 1px solid rgba(126, 87, 67, .18);
  }
  .merchant-storefront-header-stack,
  .merchant-menu-dropdown .dropdown-content { background: #6f3f2b; }
</style>
