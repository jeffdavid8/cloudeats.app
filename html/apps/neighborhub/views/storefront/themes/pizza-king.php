<?php
if (!defined('MB_RUNNING')) exit;

require __DIR__ . '/default.php';
$merchant = $this->get('merchant');
$assetUrls = is_array($configuration['asset_urls'] ?? null) ? $configuration['asset_urls'] : [];
$heroId = (int)($configuration['assets']['hero_id'] ?? 0);
$merchantImage = htmlspecialchars((string)($assetUrls[$heroId] ?? ($merchant->image_url ?? '')), ENT_QUOTES, 'UTF-8');
?>
<style>
  .dayMode body,
  body {
    --brand-bg: #fff7ed;
    --brand-card-bg: #fffdf8;
    --brand-text-main: #30221b;
    --brand-text-muted: #6f5b50;
    --brand-border: rgba(139, 30, 30, .22);
    --brand-nav-bg: #531b16;
    --brand-nav-border: #8b1e1e;
    --brand-primary: #a32424;
    --brand-secondary: #ce4b18;
    --brand-gold: #ad6500;
    --brand-btn-add: #8b1e1e;
    --brand-btn-add-hover: #681616;
    --brand-pill-bg: #f9e9d8;
  }

  .nightMode body {
    --brand-bg: #1c1917;
    --brand-card-bg: #1c1816;
    --brand-text-main: #f5f5f4;
    --brand-text-muted: #a8a29e;
    --brand-border: rgba(217, 119, 6, 0.25);
    --brand-nav-bg: #0c0a09;
    --brand-nav-border: #8b1e1e;
    --brand-primary: #a32424;
    --brand-secondary: #ce4b18;
    --brand-gold: #f59e0b;
    --brand-btn-add: #292524;
    --brand-btn-add-hover: #3d3735;
    --brand-pill-bg: #26211e;
  }

  .merchant-storefront-hero {
    background:
      linear-gradient(90deg, rgba(12, 10, 9, 0.94), rgba(58, 24, 16, 0.76)),
      url('<?= $merchantImage ?>') center / cover;
    border-color: rgba(245, 158, 11, 0.35);
  }

  .merchant-storefront-hero h1,
  .merchant-storefront-block h3 {
    color: #fff7ed;
  }

  .merchant-storefront-hero p,
  .merchant-storefront-block p {
    color: #d6d3d1;
  }

  .merchant-storefront-block {
    background: #1c1816;
    border: 1px solid rgba(245, 158, 11, 0.25);
  }

  .merchant-storefront-header-stack,
  .merchant-menu-dropdown .dropdown-content {
    background: #0c0a09;
  }
</style>
