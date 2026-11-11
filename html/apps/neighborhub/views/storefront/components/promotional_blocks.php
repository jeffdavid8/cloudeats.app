<?php
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
$configuration = is_array($configuration ?? null) ? $configuration : MerchantStorefront::defaultConfiguration();
$blocks = is_array($configuration['blocks'] ?? null) ? $configuration['blocks'] : [];
$imageUrls = [];

foreach ($blocks as $block) {
  if (($block['type'] ?? '') !== 'image_text') {
    continue;
  }
  $imageId = (int)($block['content']['image_id'] ?? 0);
  if ($imageId < 1) {
    continue;
  }
  $stmt = App::getInstance('neighborhub')->db->prepare(
    "SELECT image_url FROM neighborhub_images
     WHERE id = ? AND parent_type = 'merchant' AND parent_id = ? LIMIT 1"
  );
  $stmt->execute([$imageId, (int)$merchant->id]);
  $imageUrl = $stmt->fetchColumn();
  if ($imageUrl !== false) {
    $imageUrls[$imageId] = (string)$imageUrl;
  }
}
?>
<?php if ($blocks): ?>
  <div class="container merchant-storefront-blocks" aria-label="Storefront highlights">
    <?php foreach ($blocks as $block):
      $type = (string)($block['type'] ?? '');
      $content = is_array($block['content'] ?? null) ? $block['content'] : [];
      $title = trim((string)($content['title'] ?? ''));
      $text = trim((string)($content['text'] ?? ''));
    ?>
      <?php if ($type === 'announcement' && ($title !== '' || $text !== '')): ?>
        <section class="card merchant-storefront-block merchant-storefront-announcement">
          <div class="card-content">
            <?php if ($title !== ''): ?><h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3><?php endif; ?>
            <?php if ($text !== ''): ?><p><?= nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
          </div>
        </section>
      <?php elseif ($type === 'featured_product'):
        $productId = (int)($content['product_id'] ?? 0);
        $product = $productId > 0 ? Product::getProductById($productId) : null;
        if (is_array($product) && (int)($product['merchant_id'] ?? 0) === (int)$merchant->id):
      ?>
        <section class="card merchant-storefront-block merchant-storefront-featured-product">
          <?php if (!empty($product['image_url'])): ?><img class="responsive-img" src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php endif; ?>
          <div class="card-content">
            <h3><?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (!empty($product['description'])): ?><p><?= nl2br(htmlspecialchars((string)$product['description'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
            <strong>$<?= number_format((float)$product['price'], 2) ?></strong>
            <a class="btn waves-effect waves-light" href="<?= htmlspecialchars($this->config['base_url'] . '/?app=neighborhub&view=customer&p=merchant_products&merchant_id=' . (int)$merchant->id . '&product_id=' . $productId, ENT_QUOTES, 'UTF-8') ?>">View item</a>
          </div>
        </section>
      <?php endif; elseif ($type === 'image_text' && ($title !== '' || $text !== '' || !empty($imageUrls[(int)($content['image_id'] ?? 0)]))): ?>
        <?php $imageUrl = $imageUrls[(int)($content['image_id'] ?? 0)] ?? ''; ?>
        <section class="card merchant-storefront-block merchant-storefront-image-text">
          <?php if ($imageUrl !== ''): ?><img class="responsive-img" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt=""><?php endif; ?>
          <div class="card-content">
            <?php if ($title !== ''): ?><h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3><?php endif; ?>
            <?php if ($text !== ''): ?><p><?= nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
          </div>
        </section>
      <?php elseif ($type === 'call_to_action'):
        $label = trim((string)($content['label'] ?? ''));
        $url = trim((string)($content['url'] ?? ''));
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if ($label !== '' && ($scheme === '' || in_array($scheme, ['http', 'https'], true))):
      ?>
        <section class="card merchant-storefront-block merchant-storefront-call-to-action">
          <div class="card-content"><a class="btn-large waves-effect waves-light" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a></div>
        </section>
      <?php endif; elseif ($type === 'store_information'): ?>
        <section class="card merchant-storefront-block merchant-storefront-information">
          <div class="card-content">
            <h3>Store information</h3>
            <p><?= htmlspecialchars($merchant->address . ', ' . $merchant->city . ', ' . $merchant->state . ' ' . $merchant->zip_code, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($merchant->phone)): ?><p><?= htmlspecialchars($merchant->phone, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($merchant->store_hours)): ?><p><?= nl2br(htmlspecialchars(Merchant::formatStoreHours($merchant->store_hours), ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
          </div>
        </section>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
