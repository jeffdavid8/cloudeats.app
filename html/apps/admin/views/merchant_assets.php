<?php
if (!defined('MB_RUNNING')) exit;

$neighborhub = App::getInstance('neighborhub');
$neighborhub->includeModel('merchant');
$db = $neighborhub->db;
$merchants = $db->query(
  "SELECT id, business_name FROM neighborhub_merchants ORDER BY business_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);
$merchantId = (int)($_GET['merchant_id'] ?? 0);
?>
<div class="container">
  <h1>Merchant Assets</h1>
  <p>Upload logo and hero images for merchant storefronts.</p>

  <form method="get" class="row">
    <input type="hidden" name="app" value="admin">
    <input type="hidden" name="p" value="merchant_assets">
    <div class="input-field col s12 m8">
      <select name="merchant_id" id="merchant-assets-merchant" required>
        <option value="" disabled <?= $merchantId ? '' : 'selected' ?>>Choose a merchant</option>
        <?php foreach ($merchants as $merchantOption): ?>
          <option value="<?= (int)$merchantOption['id'] ?>" <?= (int)$merchantOption['id'] === $merchantId ? 'selected' : '' ?>>
            <?= htmlspecialchars($merchantOption['business_name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <label for="merchant-assets-merchant">Merchant</label>
    </div>
    <div class="col s12 m4" style="margin-top: 1rem;">
      <button class="btn waves-effect" type="submit">Load assets</button>
    </div>
  </form>

  <?php if ($merchantId > 0): ?>
    <?php $asset_merchant_id = $merchantId; include __DIR__ . '/../../neighborhub/views/components/merchant_asset_manager.php'; ?>
  <?php endif; ?>
</div>