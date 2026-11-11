<?php
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
$configuration = is_array($configuration ?? null) ? $configuration : [];
$assetUrls = is_array($configuration['asset_urls'] ?? null) ? $configuration['asset_urls'] : [];
$logoId = (int)($configuration['assets']['logo_id'] ?? 0);
$logoUrl = $assetUrls[$logoId] ?? ($merchant->image_url ?? '');
$heroId = (int)($configuration['assets']['hero_id'] ?? 0);
$heroUrl = $assetUrls[$heroId] ?? '';
$layout = $configuration['layout'] ?? 'hero-centered';
if (!in_array($layout, ['hero-centered', 'hero-split', 'hero-minimal', 'hero-full'], true)) {
  $layout = 'hero-centered';
}
$logoStyle = $configuration['logo_style'] ?? 'default';
if (!in_array($logoStyle, ['default', 'original', 'circle', 'rectangle'], true)) {
  $logoStyle = 'default';
}
$heroUrl = $heroUrl !== '' ? $heroUrl : ($layout === 'hero-full' ? ($merchant->image_url ?? '') : '');
$heroStyle = $heroUrl !== ''
  ? ' style="background-image: linear-gradient(rgba(0, 0, 0, .35), rgba(0, 0, 0, .35)), url(\'' . htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') . '\')"'
  : '';
?>
<section
  class="merchant-storefront-hero"
  data-storefront-layout="<?= htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') ?>"
  data-storefront-logo-style="<?= htmlspecialchars($logoStyle, ENT_QUOTES, 'UTF-8') ?>"
  data-storefront-profile-image="<?= htmlspecialchars((string)($merchant->image_url ?? ''), ENT_QUOTES, 'UTF-8') ?>"
  <?= $heroStyle ?>
  aria-labelledby="merchant-storefront-title"
>
  <span class="merchant-storefront-hero-background" aria-hidden="true"></span>
  <?php if ($logoUrl !== ''): ?>
    <img
      class="merchant-storefront-hero-logo"
      data-storefront-logo
      src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"
      alt=""
    >
  <?php endif; ?>
  <div>
    <h1 id="merchant-storefront-title"><?= htmlspecialchars($merchant->business_name, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Browse our menu and place your order online.</p>
  </div>
</section>
