<?php
if (!defined('MB_RUNNING')) exit;

$assetMerchantId = (int)($asset_merchant_id ?? 0);
$assetMerchant = Merchant::getMerchantWithGallery($assetMerchantId, 'object');
$assetGallery = $assetMerchant && is_array($assetMerchant->gallery ?? null)
  ? $assetMerchant->gallery
  : [];
?>
<section class="merchant-asset-manager">
  <h2>Storefront Assets</h2>
  <p class="grey-text">Upload reusable logo, hero, and promotional images for this merchant.</p>
  <?php if (!$assetMerchant): ?>
    <p class="red-text">Merchant not found.</p>
  <?php else: ?>
    <form class="merchant-assets-upload card-panel" enctype="multipart/form-data">
      <input type="hidden" name="merchant_id" value="<?= $assetMerchantId ?>">
      <div class="file-field input-field">
        <div class="btn"><span>Choose images</span><input type="file" name="gallery_images[]" multiple accept="image/*"></div>
        <div class="file-path-wrapper"><input class="file-path validate" type="text" placeholder="Upload logo, hero, or promotional images"></div>
      </div>
      <button class="btn blue waves-effect" type="submit">Upload gallery images</button>
      <span class="merchant-assets-status" role="status"></span>
    </form>
    <div class="row merchant-assets-grid">
      <?php foreach ($assetGallery as $image): ?>
        <div class="col s12 m4 l3" id="merchant-asset-<?= (int)$image['id'] ?>">
          <div class="card">
            <div class="card-image"><img src="<?= htmlspecialchars($image['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Merchant gallery asset"></div>
            <div class="card-content"><small>Asset #<?= (int)$image['id'] ?></small></div>
            <div class="card-action">
              <button type="button" class="btn-small red delete-merchant-asset" data-image-id="<?= (int)$image['id'] ?>">Delete</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$assetGallery): ?>
      <p class="grey-text">No gallery assets have been uploaded for this merchant yet.</p>
    <?php endif; ?>
  <?php endif; ?>
</section>
<script>
(function () {
  var merchantId = <?= json_encode($assetMerchantId) ?>;
  document.querySelectorAll('.merchant-assets-upload').forEach(function (upload) {
    upload.addEventListener('submit', function (event) {
      event.preventDefault();
      var status = upload.querySelector('.merchant-assets-status');
      loading(4);
      mb.ajax({
        url: '?api=neighborhub&action=upload_merchant_image',
        type: 'POST',
        data: new FormData(upload),
        processData: false,
        contentType: false,
        success: function () { window.location.reload(); },
        error: function (xhr) {
          var response = xhr.responseJSON || {};
          status.textContent = response.error || response.message || 'Upload failed.';
        },
        complete: function () { loading(0); }
      });
    });
  });
  document.querySelectorAll('.delete-merchant-asset').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!window.confirm('Delete this merchant gallery image?')) return;
      loading(4);
      mb.ajax({
        url: '?api=neighborhub&action=delete_merchant_gallery_image',
        type: 'POST',
        data: JSON.stringify({merchant_id: merchantId, image_id: Number(button.dataset.imageId)}),
        success: function () {
          var card = document.getElementById('merchant-asset-' + button.dataset.imageId);
          if (card) card.remove();
        },
        error: function () { window.alert('Unable to delete this image.'); },
        complete: function () { loading(0); }
      });
    });
  });
}());
</script>
