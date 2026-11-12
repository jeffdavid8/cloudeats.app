<?php
if (!defined('MB_RUNNING')) exit;

$configuration = $this->get('storefront_config', MerchantStorefront::defaultConfiguration());
$merchant = $this->get('merchant');
$gallery = is_array($merchant->gallery ?? null) ? $merchant->gallery : [];
$storefrontAssetUrls = [];
foreach ($gallery as $asset) {
  if (isset($asset['id'], $asset['image_url'])) {
    $storefrontAssetUrls[(int)$asset['id']] = (string)$asset['image_url'];
  }
}
$configuration['asset_urls'] = $storefrontAssetUrls;
$theme = is_array($configuration) && isset($configuration['theme'])
  ? (string)$configuration['theme']
  : 'default';

$themeTemplates = [
  'default' => __DIR__ . '/themes/default.php',
  'pizza-king' => __DIR__ . '/themes/pizza-king.php',
  'bakery' => __DIR__ . '/themes/bakery.php',
  'diner' => __DIR__ . '/themes/diner.php',
];
$themeTemplate = $themeTemplates[$theme] ?? $themeTemplates['default'];

?>
<script>
  document.body.dataset.storefrontPreview = <?= !empty($this->get('storefront_preview')) ? '"true"' : '"false"' ?>;
  document.body.dataset.storefrontTheme = <?= json_encode($theme, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php
require $themeTemplate;
