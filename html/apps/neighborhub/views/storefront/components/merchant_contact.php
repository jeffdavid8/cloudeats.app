<?php
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<div class="row">
  <div class="col s6">
    <div class="card horizontal border-radius-smooth" style="border-radius: 30px; box-shadow: none; margin-top: 2rem; border: none;">
      <div class="card-stacked">
        <div class="card-content">
          <h4 style="margin:0 0 10px 0; font-weight:700;"><?php echo htmlspecialchars($merchant->business_name); ?></h4>
          <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
            <span class="grey-text text-darken-2" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
              <i class="fas fa-map-marker-alt text-teal"></i>
            </span>
            <?php
            $address = $merchant->address . ', ' . $merchant->city . ', ' . $merchant->state . ' ' . $merchant->zip_code;
            $mapLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
            ?>
            <span class="pk-text-main" style="word-break: break-word; font-weight: 600;">
              <?php echo htmlspecialchars($address); ?>
              <a href="<?php echo htmlspecialchars($mapLink); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-small btn-outline waves-effect waves-light" style="margin-left: 10px;">
                <i class="fas fa-map-marked-alt"></i>
              </a>
            </span>
          </p>
          <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
            <span class="grey-text text-darken-2" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
              <i class="fas fa-phone text-teal"></i>
            </span>
            <span><?php echo htmlspecialchars($merchant->phone); ?></span>
          </p>
          <?php if (!empty($merchant->facebook)): ?>
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="grey-text text-darken-2" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
                <i class="fab fa-facebook text-teal"></i>
              </span>
              <a href="<?php echo htmlspecialchars($merchant->facebook); ?>" target="_blank" rel="noopener noreferrer" style="word-break: break-all;">
                <?php echo htmlspecialchars($merchant->facebook); ?>
              </a>
            </p>
          <?php endif; ?>
          <?php if (!empty($merchant->website)): ?>
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="grey-text text-darken-2" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
                <i class="fas fa-globe text-teal"></i>
              </span>
              <a href="<?php echo htmlspecialchars($merchant->website); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo htmlspecialchars($merchant->website); ?>
              </a>
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col s6">
    <div class="card horizontal border-radius-smooth" style="border-radius: 30px; box-shadow: none; margin-top: 2rem; border: none;">
      <div class="card-stacked">
        <div class="card-content">
          <?php if (!empty($merchant->store_hours)): ?>
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="grey-text text-darken-2" style="margin-right: 1rem; min-width: 1.25rem; text-align: center; margin-top: 2px;">
                <i class="fas fa-clock text-teal"></i>
              </span>
              <span><?= nl2br(htmlspecialchars(Merchant::formatStoreHours($merchant->store_hours), ENT_QUOTES, 'UTF-8')) ?></span>
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>