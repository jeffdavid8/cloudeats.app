<?php
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<div class="row">
  <div class="col s12 m6">
    <div class="card horizontal border-radius-smooth" style="border-radius: 30px; box-shadow: none; margin-top: 2rem; border: none;">
      <div class="card-stacked">
        <div class="card-content">
          <h4 style="margin:0 0 10px 0; font-weight:700;"><?php echo htmlspecialchars($merchant->business_name); ?></h4>
          <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
            <!-- Address -->
            <p style="text-align: left; margin-bottom: 1rem;">
              <?
              $map_link = "https://www.google.com/maps/search/?api=1&query=" . urlencode($merchant->address . ', ' . $merchant->city . ', ' . $merchant->state . ' ' . $merchant->zip_code);
              ?>
              <a href="<?php echo $map_link; ?>" target="_blank" class="btn btn-small btn-flat waves-effect waves-light" style="">
                <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                  <i class="fas fa-map-marker-alt"></i>
                </span>
                <span class="pk-text-main" style="word-break: break-word; font-weight: 600; text-align: left; text-transform: uppercase;">
                  <?php echo htmlspecialchars($merchant->address . ', ' . $merchant->city . ', ' . $merchant->state . ' ' . $merchant->zip_code); ?>
                </span>
              </a>
            </p>

            <!-- Phone -->
            <p style="text-align: left; margin-bottom: 1rem;">
              <a href="tel: <?= $merchant->phone ?>" class="btn btn-small btn-flat waves-effect waves-light" style="">
                <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                  <i class="fas fa-phone"></i>
                </span>
                <span class="sweets-text-brown" style="font-weight: 600;"><?php echo htmlspecialchars($merchant->phone); ?></span></a>
            </p>

            <!-- Website -->
            <?php if (!empty($merchant->website)): ?>
              <p style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                <a href="<?php echo htmlspecialchars($merchant->website); ?>" target="_blank" rel="noopener noreferrer" style="font-weight: 700;" class="btn btn-small btn-flat waves-effect waves-light">
                  <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                    <i class="fas fa-globe"></i>
                  </span>

                  Visit Website
                </a>
              </p>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col s12 m6">
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