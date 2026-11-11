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
    --brand-bg: #f5f7fa;
    --brand-card-bg: #ffffff;
    --brand-text-main: #18212b;
    --brand-text-muted: #536273;
    --brand-border: rgba(24, 33, 43, .14);
    --brand-nav-bg: #1d4058;
    --brand-nav-border: #e85d45;
    --brand-primary: #d94f3d;
    --brand-secondary: #1d4058;
    --brand-gold: #f0b35b;
    --brand-btn-add: #1d4058;
    --brand-btn-add-hover: #153247;
    --brand-pill-bg: #edf3f7;
  }

  .merchant-storefront-hero {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
    overflow: hidden;
    padding: clamp(22px, 4vw, 42px);
    color: #fff9e9;
    background:
      linear-gradient(130deg, #173f43 0%, #177e83 58%, #23565a 100%);
    border: 4px solid #e7b94e;
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(24, 61, 62, .18);
  }

  .merchant-storefront-hero:not([data-storefront-layout="hero-full"]) {
    background-image: linear-gradient(130deg, #173f43 0%, #177e83 58%, #23565a 100%) !important;
  }

  .merchant-storefront-hero::before,
  .merchant-storefront-hero::after,
  .merchant-storefront-block::before,
  .merchant-storefront-block::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    height: 9px;
    background: repeating-linear-gradient(
      90deg,
      #fff8e9 0 18px,
      #c93237 18px 36px
    );
    opacity: .95;
  }

  .merchant-storefront-hero::before,
  .merchant-storefront-block::before { top: 0; }

  .merchant-storefront-hero::after,
  .merchant-storefront-block::after { bottom: 0; }

  .merchant-storefront-hero-logo {
    margin-bottom: 1rem;
  }

  .merchant-storefront-hero h1,
  .merchant-storefront-block h3 {
    color: #fff9e9;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 900;
  }

  .merchant-storefront-hero h1 {
    font-size: clamp(2rem, 5vw, 3.8rem);
    line-height: 1.04;
    text-shadow: 0 3px 0 rgba(0, 0, 0, .12);
  }

  .merchant-storefront-hero p,
  .merchant-storefront-block p { color: #f7f4e8; }

  .merchant-storefront-block {
    position: relative;
    overflow: hidden;
    color: #fff9e9;
    background: linear-gradient(130deg, #173f43 0%, #177e83 58%, #23565a 100%);
    border: 4px solid #e7b94e;
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(24, 61, 62, .18);
  }

  .merchant-storefront-block .card-content {
    position: relative;
    z-index: 1;
    padding: clamp(22px, 4vw, 42px);
    text-align: center;
  }

  .merchant-storefront-block img {
    position: relative;
    z-index: 1;
    max-height: 280px;
  }

  .merchant-storefront-header-stack,
  .merchant-menu-dropdown .dropdown-content { background: #1d4058; }
</style>
