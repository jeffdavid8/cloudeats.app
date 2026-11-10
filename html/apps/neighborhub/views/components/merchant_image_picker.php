<?php
if (!defined('MB_RUNNING')) exit;

$picker = $imagePicker;
$pickerId = $picker['id'];
?>
<div class="merchant-image-picker" data-image-picker>
  <label for="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($picker['label'], ENT_QUOTES, 'UTF-8') ?></label>
  <div class="merchant-image-picker-dropzone" data-image-dropzone>
    <img data-image-preview alt="" hidden>
    <span data-image-copy>Drop an image here or click to upload</span>
    <input id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>-upload" type="file" data-image-upload accept="image/*" aria-label="<?= htmlspecialchars('Upload ' . $picker['label'], ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <select id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>" data-image-select>
    <option value="" data-image-url="<?= htmlspecialchars($picker['fallback_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($picker['fallback_label'], ENT_QUOTES, 'UTF-8') ?></option>
    <?php foreach ($picker['gallery'] as $image): ?>
      <option value="<?= (int)$image['id'] ?>" data-image-url="<?= htmlspecialchars($image['image_url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars('Image ' . $image['id'], ENT_QUOTES, 'UTF-8') ?></option>
    <?php endforeach; ?>
  </select>
</div>
