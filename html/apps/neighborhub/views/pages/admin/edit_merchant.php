<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Admin Provisioning Panel
 * * Administrative control console for managing and altering merchant businesses in the platform ledger.
 * Implements role-based access verification, atomic merchant profile modification, and 
 * email-to-user-id lookup chaining with integrated primary and multiple gallery image support.
 * @var Object $merchant
 * @var Int $merchant_id
 * @var String $owner_email
 * */
//error_log(print_r($merchant, true));
//error_log($merchant_id);
// ============================================================================
// ACCESS CLEARANCE ENFORCEMENT
// ============================================================================
// Verify authenticated user and admin privileges
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
  // Not authenticated - redirect to dashboard
  header('Location: ?p=dashboard');
  exit;
}

if (!$this->user->is_admin) {
  // Not an administrator - render 403 Forbidden card
?>
  <div class="nh-card nh-alert nh-alert-error" style="max-width: 600px; margin: 40px auto; padding: 30px; border-radius: 8px;">
    <h2 style="margin-top: 0; color: #ef4444;">403 Forbidden</h2>
    <p style="margin: 15px 0; font-size: 16px;">
      <strong>Administrative Authorization Required</strong>
    </p>
    <p style="margin: 15px 0; color: #666;">
      You do not have permission to access the merchant provisioning console.
      Only platform administrators can add new businesses to the Neighborhub ledger.
    </p>
    <a href="?p=dashboard" class="nh-btn nh-btn-secondary" style="display: inline-block; margin-top: 20px;">
      Return to Dashboard
    </a>
  </div>
<?php
  return;
}

// ============================================================================
// PROVISIONING LAYOUT CONSOLE
// ============================================================================
?>

<div class="neighborhub-admin-provisioning-panel">
  <style>
    .neighborhub-admin-provisioning-panel {
      max-width: 700px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .provisioning-header {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--primary-color);
    }

    .provisioning-header h1 {
      margin: 0 0 10px 0;
      font-size: 28px;
      color: var(--text-primary);
    }

    .provisioning-header p {
      margin: 0;
      color: var(--text-secondary);
      font-size: 15px;
    }

    .nh-form-group {
      margin-bottom: 20px;
    }

    .nh-form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-primary);
      font-size: 14px;
    }

    .nh-form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
      background-color: var(--input-bg, #fff);
      color: var(--text-primary);
      box-sizing: border-box;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .nh-form-group input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .nh-form-group input:required:valid {
      border-color: #34d399;
    }

    .provisioning-form {
      background-color: var(--card-bg, #fff);
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .provisioning-form-actions {
      display: flex;
      gap: 12px;
      margin-top: 30px;
    }

    .provisioning-submit-btn {
      flex: 1;
      padding: 14px 20px;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s, opacity 0.2s;
    }

    .provisioning-submit-btn:hover:not(:disabled) {
      background-color: var(--primary-color-hover, #2563eb);
    }

    .provisioning-submit-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .provisioning-reset-btn {
      padding: 14px 20px;
      background-color: var(--gray-200);
      color: var(--text-primary);
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .provisioning-reset-btn:hover {
      background-color: var(--gray-300);
    }

    .provisioning-alert {
      margin-bottom: 20px;
      padding: 15px;
      border-radius: 6px;
      display: none;
    }

    .provisioning-alert.show {
      display: block;
    }

    .provisioning-alert-success {
      background-color: #d1fae5;
      color: #065f46;
      border: 1px solid #6ee7b7;
    }

    .provisioning-alert-error {
      background-color: #fee2e2;
      color: #7f1d1d;
      border: 1px solid #fca5a5;
    }

    .provisioning-alert-message {
      font-size: 14px;
      line-height: 1.5;
    }

    .loading-spinner {
      display: inline-block;
      width: 14px;
      height: 14px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-right: 8px;
      vertical-align: middle;
    }

    /* Enhanced Media Upload Dropzone Styles */
    .media-dropzone-container {
      border: 2px dashed var(--border-color, #d1d5db);
      border-radius: 8px;
      padding: 25px;
      text-align: center;
      background-color: var(--input-bg, #f9fafb);
      cursor: pointer;
      transition: border-color 0.2s, background-color 0.2s;
      margin-bottom: 15px;
      position: relative;
    }

    .media-dropzone-container:hover,
    .media-dropzone-container.dragover {
      border-color: var(--primary-color, #3b82f6);
      background-color: rgba(59, 130, 246, 0.04);
    }

    .media-dropzone-container i {
      font-size: 32px;
      color: var(--text-secondary, #9ca3af);
      margin-bottom: 10px;
    }

    .media-dropzone-text {
      font-size: 14px;
      color: var(--text-primary, #4b5563);
    }

    .media-dropzone-subtext {
      font-size: 12px;
      color: var(--text-secondary, #9ca3af);
      margin-top: 4px;
    }

    .hidden-file-input {
      display: none !important;
    }

    .primary-preview-box {
      margin-top: 15px;
      padding: 10px;
      border: 1px solid var(--border-color, #e5e7eb);
      border-radius: 6px;
      display: flex;
      align-items: center;
      gap: 12px;
      background: var(--card-bg, #fff);
    }

    .primary-preview-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid #e5e7eb;
    }

    .primary-file-info {
      flex: 1;
      min-width: 0;
    }

    .primary-file-name {
      font-size: 13px;
      font-weight: 500;
      color: var(--text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .remove-media-btn {
      background: none;
      border: none;
      color: #ef4444;
      cursor: pointer;
      padding: 6px;
      font-size: 16px;
      transition: color 0.2s;
    }

    .remove-media-btn:hover {
      color: #b91c1c;
    }

    .gallery-grid-preview {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(80px, 100px));
      gap: 12px;
      margin-top: 15px;
    }

    .gallery-preview-item {
      position: relative;
      aspect-ratio: 1;
      border: 1px solid var(--border-color, #e5e7eb);
      border-radius: 6px;
      overflow: hidden;
    }

    .gallery-preview-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .gallery-remove-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.2s;
      cursor: pointer;
    }

    .gallery-preview-item:hover .gallery-remove-overlay {
      opacity: 1;
    }

    .gallery-remove-overlay i {
      color: #fff;
      font-size: 16px;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    @media (prefers-color-scheme: dark) {
      .provisioning-form {
        /* background-color: var(--card-bg, #1f2937); */
      }

      .nh-form-group input {
        background-color: var(--input-bg, #111827);
        border-color: var(--border-color, #374151);
      }

      .provisioning-reset-btn {
        background-color: var(--gray-700);
      }

      .provisioning-reset-btn:hover {
        background-color: var(--gray-600);
      }

      .media-dropzone-container {
        background-color: var(--input-bg, #111827);
      }

      .primary-preview-box {
        background: var(--input-bg, #111827);
      }
    }

    body.nightMode .provisioning-form {
      background-color: #0d0f11;
    }

    body.nightMode .nh-form-group input {
      background-color: #111827;
      border-color: #374151;
    }

    body.nightMode .provisioning-reset-btn {
      background-color: #374151;
    }

    body.nightMode .provisioning-reset-btn:hover {
      background-color: #4b5563;
    }

    body.nightMode .media-dropzone-container {
      background-color: #111827;
    }

    body.nightMode .primary-preview-box {
      background: #111827;
    }
  </style>

  <div class="provisioning-header">
    <h1>
      <?= ($merchant->id ?? -1) === -1
        ? 'Add New Merchant'
        : 'Edit - ' . htmlspecialchars($merchant->business_name); ?>
    </h1>
    <div class="right">
      <a href="/?app=neighborhub&view=admin&p=dashboard&merchant_id=<?= intval($merchant->id) ?>"
        class="btn-small btn-flat waves-effect blue-text"
        title="Admin Dashboard"
        style="padding: 0 8px; margin-right: 5px;">
        <i class="fas fa-user-shield"></i></a>

      <? render('components/admin/merchant_action_buttons.php', array('merchant' => $merchant->data())); ?>
    </div>

    <p>Modify local business details and catalog presentation attachments.</p>
  </div>

  <div class="provisioning-form">
    <div id="provisioning-alert-success" class="provisioning-alert provisioning-alert-success">
      <div class="provisioning-alert-message">
        <strong>Success!</strong> Merchant profile modified with ID: <span id="new-merchant-id"></span>
      </div>
    </div>

    <div id="provisioning-alert-error" class="provisioning-alert provisioning-alert-error">
      <div class="provisioning-alert-message">
        <strong>Error:</strong> <span id="error-message"></span>
      </div>
    </div>

    <form id="edit-merchant-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" id="merchant-id" name="merchant_id" value="<?= $merchant->id ?? -1 ?>" />

      <div class="row" style="margin-bottom: 5px;">
        <div class="col s12 center-align" id="image-preview-box" style="margin-top: 15px; <?= (empty($merchant->image_url) ? 'display: none;' : '') ?>">
          <div style="position: relative; display: inline-block; border-radius: 8px; padding: 4px; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.12); border: 1px solid #e0e0e0;">
            <img id="form-image-preview" src="<?= (!empty($merchant->image_url) ? $merchant->image_url : ''); ?>" style="max-height: 160px; max-width: 100%; border-radius: 6px; display: block; object-fit: contain;">
            <button type="button" class="btn-floating btn-small red waves-effect waves-light" id="remove-preview-btn" style="position: absolute; top: -10px; right: -10px;padding: 0;" title="Clear Selection">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <p style="font-size: 11px; color: #757575; margin: 6px 0 0 0; font-weight: 500;">
            <i class="fas fa-check-circle green-text"></i> Main display photo staged
          </p>
        </div>

        <div class="file-field input-field col s12" style="margin-top: 15px; padding-left: 0;">
          <i class="fas fa-image prefix" style="font-size: 2rem; color: #546e7a;"></i>
          <div class="btn blue-grey darken-1" style="margin-left: 3rem; float: none; border-radius: 4px;">
            <span>Primary Image</span>
            <input type="file" name="product_image" id="product-merchant-image-input" accept="image/*">
          </div>
          <div class="file-path-wrapper" style="margin-left: 3rem; padding: 0;">
            <input class="file-path validate" type="text" placeholder="Upload or drop main product photo here">
          </div>
        </div>
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-store prefix"></i>
        <label for="business-name">Business Name *</label>
        <input
          type="text"
          id="business-name"
          name="business_name"
          placeholder="Enter merchant business name"
          value="<?php echo htmlspecialchars($merchant->business_name); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-map-marked-alt prefix"></i>
        <label for="address">Physical Storefront Address *</label>
        <input
          type="text"
          id="address"
          name="address"
          placeholder="Street address, building, suite"
          value="<?php echo htmlspecialchars($merchant->address); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-compass prefix"></i>
        <label for="latitude">Latitude Coordinate *</label>
        <input
          type="number"
          id="latitude"
          name="latitude"
          placeholder="e.g., 37.7749"
          step="any"
          min="-90"
          max="90"
          value="<?php echo htmlspecialchars($merchant->latitude); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-compass prefix"></i>
        <label for="longitude">Longitude Coordinate *</label>
        <input
          type="number"
          id="longitude"
          name="longitude"
          placeholder="e.g., -122.4194"
          step="any"
          min="-180"
          max="180"
          value="<?php echo htmlspecialchars($merchant->longitude); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-phone prefix"></i>
        <label for="phone">Contact Phone Number *</label>
        <input
          type="tel"
          id="phone"
          name="phone"
          placeholder="(555) 123-4567"
          value="<?php echo htmlspecialchars($merchant->phone); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-envelope prefix"></i>
        <label for="owner-email">Primary Owner Account Email *</label>
        <input
          type="email"
          id="owner-email"
          name="owner_email"
          placeholder="owner@example.com"
          value="<?php echo htmlspecialchars($owner_email); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-globe prefix"></i>
        <label for="owner-email">Website</label>
        <input
          type="text"
          id="website"
          name="website"
          placeholder="www.example.com"
          value="<?php echo htmlspecialchars($merchant->website); ?>" />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fab fa-facebook-f prefix"></i>
        <label for="owner-email">Facebook</label>
        <input
          type="text"
          id="facebook"
          name="facebook"
          placeholder="https://www.facebook.com/your-facebook-id"
          value="<?php echo htmlspecialchars($merchant->facebook); ?>" />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-percent prefix"></i>
        <label for="platform-fee-rate">Platform Fee Rate</label>
        <input
          type="text"
          id="platform-fee-rate"
          name="platform_fee_rate"
          placeholder="2.9"
          value="<?php echo htmlspecialchars($merchant->platform_fee_rate); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-shopping-basket prefix"></i>
        <label for="platform-flat-fee">Platform Flat Fee</label>
        <input
          type="text"
          id="platform-flat-fee"
          name="platform_flat_fee"
          placeholder="0.30"
          value="<?php echo htmlspecialchars($merchant->platform_flat_fee); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-percent prefix"></i>
        <label for="platform-percent-fee">Stripe Percent Fee</label>
        <input
          type="text"
          id="stripe-percent-fee"
          name="stripe_percent_fee"
          placeholder="2.9"
          value="<?php echo htmlspecialchars($merchant->stripe_percent_fee); ?>"
          required />
      </div>

      <div class="nh-form-group input-field col s12">
        <i class="fas fa-shopping-basket prefix"></i>
        <label for="stripe-flat-fee">Stripe Flat Fee</label>
        <input
          type="text"
          id="stripe-flat-fee"
          name="stripe_flat_fee"
          placeholder="0.30"
          value="<?php echo htmlspecialchars($merchant->stripe_flat_fee); ?>"
          required />
      </div>


      <div class="nh-form-group input-field col s12">
        <i class="fas fa-clock prefix"></i>
        <label for="store-hours">Store Hours</label>
        <textarea
          style="min-height: 200px;"
          id="store-hours"
          name="store_hours"
          placeholder=""
          value=""><?php echo htmlspecialchars($merchant->store_hours); ?></textarea>
      </div>

<? //Pizza King, The Mason Jar, Food, Drinks, Hand Dipped Ice Cream, Sundaes ?>
      <div class="nh-form-group input-field col s12">
        <i class="fas fa-utensils prefix"></i>
        <label for="menus">Menus</label>
        <input
          type="text"
          id="menus"
          name="menus"
          placeholder="e.g., Food, Drinks, Hand Dipped Ice Cream, Sundaes"
          value="<?php echo htmlspecialchars($merchant->menus); ?>"
           />
      </div>


      <div class="nh-form-group input-field col s12">
        <i class="fas fa-truck-loading prefix"></i>
        <? // Delivery Assignment Mode Select - options "Auto", "Manual", "Disabled" 
        ?>
        <select id="delivery-assignment-mode" name="delivery_assignment_mode" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; background-color: var(--input-bg, #fff); color: var(--text-primary); box-sizing: border-box;">
          <option value="auto" <?= ($merchant->delivery_assignment_mode === 'auto') ? 'selected' : ''; ?>>Auto</option>
          <option value="manual" <?= ($merchant->delivery_assignment_mode === 'manual') ? 'selected' : ''; ?>>Manual</option>
          <option value="disabled" <?= ($merchant->delivery_assignment_mode === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
        </select>
        <label>Delivery Assignment Mode</label>
      </div>


      <div class="nh-form-group input-field col s12">
        <i class="fas fa-ruler-combined prefix"></i>
        <label for="delivery_max_distance">Delivery Distance Threshold</label>
        <input
          type="text"
          id="delivery-max-distance"
          name="delivery_max_distance"
          placeholder="7"
          value="<?php echo htmlspecialchars($merchant->delivery_max_distance); ?>"
          required />
      </div>



      <div class="row" id="staged-gallery-previews-wrapper" style="display: none; margin-top: -10px; margin-bottom: 15px;">
        <div class="col s12" style="margin-left: 3rem; width: calc(100% - 3rem);">
          <span style="font-weight: bold; color: #7b1fa2; text-transform: uppercase; font-size: 0.75rem; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">
            Staged Gallery Selection Preview (<span id="staged-gallery-count">0</span> files)
          </span>
          <div id="staged-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 8px; background: #fef7ff; padding: 10px; border-radius: 6px; border: 1px dashed #ba68c8;">
          </div>
        </div>
      </div>

      <div class="row" style="margin-bottom: 10px;">
        <div class="file-field input-field col s12" style="margin-top: 5px; padding-left: 0;">
          <i class="fas fa-images prefix" style="font-size: 2rem; color: #6a1b9a;"></i>
          <div class="btn purple darken-1" style="margin-left: 3rem; float: none; border-radius: 4px;">
            <span>Add Gallery Docs</span>
            <input type="file" name="gallery_images[]" id="product-gallery-input" multiple accept="image/*">
          </div>
          <div class="file-path-wrapper" style="margin-left: 3rem; padding: 0; display:none; ">
            <input class="file-path validate" type="text" placeholder="Select multiple extra photos for your carousel">
          </div>
        </div>
      </div>

      <div class="row" id="product-gallery-management-wrapper" style="margin-top: 5px; margin-bottom: 15px;">
        <div class="col s12" style="margin-left: 3rem; width: calc(100% - 3rem);">
          <span style="font-weight: bold; color: #fba83e; text-transform: uppercase; font-size: 0.75rem; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">
            Active Image Gallery Matrix
          </span>

          <div id="merchant-gallery-dropzone" style="background: #f5f5f5; padding: 15px; border-radius: 6px; border: 2px dashed #b0bec5; transition: all 0.2s ease-in-out; min-height: 100px;">

            <div id="dropzone-placeholder-text" style="text-align: center; color: #78909c; margin-bottom: 8px; font-size: 0.85rem; font-weight: 500;">
              <i class="fas fa-cloud-upload-alt" style="font-size: 1.4rem; display: block; margin-bottom: 4px; color: #90a4ae;"></i>
              Drag & Drop multiple gallery images directly here
            </div>
            <div id="product-gallery-thumbnails-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 8px;">
              <?
              foreach ($merchant->gallery as $id => $image) {
                echo '
              <div class="gallery-image-thumb-wrapper" style="position: relative; width: 70px; height: 70px; border-radius: 4px; border: 1px solid #cfd8dc; overflow: visible;" data-image-id="' . $image['id'] . '">
                <img src="' . $image['image_url'] . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 3px;">
                <button type="button" class="btn-floating btn-small red waves-effect waves-light remove-gallery-img-btn" 
                        onclick="deleteGalleryImageAsset(' . $merchant->id . ', ' . $image['id'] . ')"
                        style="position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; line-height: 20px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 5;" 
                        title="Delete Image from GCS Bucket">
                  <i class="fas fa-trash-alt" style="font-size: 10px;"></i>
                </button>
              </div>
                ';
              }
              ?>
            </div>
          </div>

        </div>
      </div>

      <div class="row" id="staged-gallery-previews-wrapper" style="display: none; margin-top: -10px; margin-bottom: 15px;">
        <div class="col s12" style="margin-left: 3rem; width: calc(100% - 3rem);">
          <span style="font-weight: bold; color: #7b1fa2; text-transform: uppercase; font-size: 0.75rem; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">
            Staged Gallery Selection Preview (<span id="staged-gallery-count">0</span> files)
          </span>
          <div id="staged-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 8px; background: #fef7ff; padding: 10px; border-radius: 6px; border: 1px dashed #ba68c8;">
          </div>
        </div>
      </div>

      <div class="divider" style="margin: 2rem 0;"></div>

      <div class="row">
        <div class="nh-form-group input-field col s12">

          <a id="toggle-advanced" class="btn waves-effect waves-light">
            Show Advanced Options
          </a>
        </div>
      </div>

      <!-- Advanced Form Section (Hidden by Default) -->
      <div id="advanced-section" style="display: none;">
        <div class="row">
          <?php
          // Example status state from your Product model object or DB row
          $currentStatus = $merchant->status ?? 'active';
          $statusOptions = [
            'active'    => 'Active',
            'online'    => 'Online',
            'offline'   => 'Offline',
            'paused'    => 'Paused',
            'suspended' => 'Suspended',
            'disabled'  => 'Disabled',
          ];
          ?>
          <div class="input-field col s12 m6">
            <select id="status" name="status">
              <option value="" disabled>Choose status</option>
              <?php foreach ($statusOptions as $value => $label): ?>
                <option value="<?= $value; ?>" <?= ($currentStatus === $value) ? 'selected' : ''; ?>>
                  <?= $label; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label for="status">Merchant Status</label>
          </div>

        </div>
        <div class="row">
          <div class="nh-form-group input-field col s12">
            <i class="fab fa-stripe prefix"></i>
            <label for="stripe_api_key">Stripe API Key</label>
            <input
              type="text"
              id="stripe-api-key"
              name="stripe_api_key"
              placeholder="sk_test_"
              value="<?php echo htmlspecialchars($merchant->stripe_api_key); ?>" />
          </div>
        </div>
      </div>



      <div class="provisioning-form-actions">
        <? /*
        <button type="button" id="custom-reset-btn" class="provisioning-reset-btn">
          Reset Form
        </button>
        */ ?>
        <a href="javascript: history.go(-1)" class="nh-btn provisioning-reset-btn">
          Cancel
        </a>
        <button type="submit" id="submit-btn" class="provisioning-submit-btn">
          Update Merchant
        </button>
      </div>

    </form>
  </div>
</div>

<script>
  //(function() {
  //'use strict';
  document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('edit-merchant-form');
    const submitBtn = document.getElementById('submit-btn');
    const successAlert = document.getElementById('provisioning-alert-success');
    const errorAlert = document.getElementById('provisioning-alert-error');
    const errorMessage = document.getElementById('error-message');
    const newMerchantId = document.getElementById('new-merchant-id');
    const imagePreviewBox = document.getElementById('image-preview-box');
    const formImagePreview = document.getElementById('form-image-preview');
    const formExistingImageUrl = document.getElementById('form-existing-image-url');
    const removePreviewBtn = document.getElementById('remove-preview-btn');

    const primaryImageInput = document.getElementById('product-merchant-image-input');
    const galleryInput = document.getElementById('product-gallery-input');

    const stagedGalleryWrapper = document.getElementById('staged-gallery-previews-wrapper');
    const stagedGalleryGrid = document.getElementById('staged-gallery-grid');
    const stagedGalleryCount = document.getElementById('staged-gallery-count');

    $('select').formSelect();
    // --- 1. Primary Single Image Local Target Preview ---
    if (primaryImageInput) {
      primaryImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          const file = this.files[0];
          // Stream object path directly straight into DOM frame representation
          formImagePreview.src = URL.createObjectURL(file);
          imagePreviewBox.style.display = 'block';
        }
      });
    }

    if (removePreviewBtn) {
      removePreviewBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this merchant image?  This action will permanently remove it from cloud storage.')) {
          if (primaryImageInput) primaryImageInput.value = '';
          const pathInput = document.querySelector('.file-field input[type="text"]');
          const merchantId = document.getElementById('merchant-id').value;

          if (pathInput) pathInput.value = '';
          formImagePreview.src = '';
          imagePreviewBox.style.display = 'none';
          deleteMerchantImage(merchantId);

        }
      });
    }

    // --- 2. Multi-File Additional Gallery Local Previews ---
    if (galleryInput) {
      galleryInput.addEventListener('change', function() {
        stagedGalleryGrid.innerHTML = '';

        if (this.files && this.files.length > 0) {
          stagedGalleryCount.textContent = this.files.length;
          stagedGalleryWrapper.style.display = 'block';

          // Loop over target selection matrix elements 
          Array.from(this.files).forEach(file => {
            const thumbContainer = document.createElement('div');
            thumbContainer.style = 'position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; border: 1px solid #e0e0e0;';

            const localImgUrl = URL.createObjectURL(file);
            thumbContainer.innerHTML = `
            <img src="${localImgUrl}" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; bottom: 0; background: rgba(0,0,0,0.5); width: 100%; text-align: center; color: white; font-size: 8px; padding: 1px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              Staged
            </div>
          `;
            stagedGalleryGrid.appendChild(thumbContainer);
          });
        } else {
          stagedGalleryWrapper.style.display = 'none';
        }
      });
    }
    // --- 🚀 NEW: Drag & Drop Multi-Image Collector Engine ---
    const dropzone = document.getElementById('merchant-gallery-dropzone');

    if (dropzone) {
      // Prevent default browser behavior (which is opening the image file in a tab)
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => e.preventDefault(), false);
      });

      // Visually illuminate the drop container zone when files are floating over it
      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
          dropzone.style.background = '#ede7f6'; // Light purple tint indication
          dropzone.style.borderColor = '#6a1b9a';
        }, false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
          dropzone.style.background = '#f5f5f5'; // Restore baseline style defaults
          dropzone.style.borderColor = '#b0bec5';
        }, false);
      });

      // Handle the drop execution layout injection step
      dropzone.addEventListener('drop', function(e) {
        e.preventDefault();

        const dt = e.dataTransfer;
        const newFiles = dt.files;

        if (newFiles && newFiles.length > 0) {
          const grid = document.getElementById('product-gallery-thumbnails-grid');
          //const galleryInput = document.getElementById('product-gallery-input');

          // Create a new DataTransfer container to accumulate our aggregated file set
          const aggregateDataTransfer = new DataTransfer();

          // 1. Maintain backward selection: Keep files that were already staged in the file input
          if (galleryInput && galleryInput.files.length > 0) {
            Array.from(galleryInput.files).forEach(file => {
              aggregateDataTransfer.items.add(file);
            });
          }

          let addedImagesCount = 0;

          // 2. Loop through the newly dropped files
          Array.from(newFiles).forEach(file => {
            console.log(file);
            // Guard checking: Make sure it's an image file object
            if ((file.type.match('image.*')) || (file.type.match('video.*'))) {
              // Add the file binary into our hidden input pipeline queue
              aggregateDataTransfer.items.add(file);
              addedImagesCount++;

              // 3. Generate an instant visual preview frame inside the grid
              if (grid) {
                const placeholder = document.createElement('div');
                placeholder.className = 'staged-local-preview-node';
                placeholder.style = 'position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; border: 2px dashed #ba68c8;';
                placeholder.innerHTML = `
            <img src="${URL.createObjectURL(file)}" style="width:100%; height:100%; object-fit:cover;">
            <div style="position:absolute; bottom:0; background:rgba(106,27,154,0.85); color:white; width:100%; font-size:8px; text-align:center; font-weight:bold; padding: 2px 0;">
              Staged
            </div>
          `;
                grid.appendChild(placeholder);
              }
            }
          });

          // 4. Bind our completely combined file list back onto the native form input field!
          if (galleryInput && addedImagesCount > 0) {
            galleryInput.files = aggregateDataTransfer.files;

            // Update the text path display in the Materialize input box to reflect total staged files
            const pathWrapper = galleryInput.closest('.file-field').querySelector('input[type="text"]');
            if (pathWrapper) {
              pathWrapper.value = galleryInput.files.length + " gallery files selected for upload";
            }
          }

          // Toggle text placeholder guidelines out of focus area dynamically if we have images
          const textPlaceholder = document.getElementById('dropzone-placeholder-text');
          if (textPlaceholder && grid.querySelectorAll('.staged-local-preview-node, .gallery-image-thumb').length > 0) {
            textPlaceholder.style.display = 'none';
          }

          if (addedImagesCount > 0) {
            M.toast({
              html: `📸 Staged ${addedImagesCount} more image(s). Click Save to upload.`
            });
          }
        }
      });
    }

    $('#toggle-advanced').click(function(e) {
      e.preventDefault(); // Prevents form submission if using a regular button

      $('#advanced-section').slideToggle(300, function() {
        // Change button text based on visibility
        if ($(this).is(':visible')) {
          $('#toggle-advanced').text('Hide Advanced Options');
        } else {
          $('#toggle-advanced').text('Show Advanced Options');
        }
      });
    });

    /**
     * Hide all alerts
     */
    function hideAlerts() {
      successAlert.classList.remove('show');
      errorAlert.classList.remove('show');
    }

    /**
     * Show success alert with merchant ID
     */
    function showSuccessAlert(merchantId) {
      newMerchantId.textContent = merchantId;
      successAlert.classList.add('show');
      errorAlert.classList.remove('show');
    }

    /**
     * Show error alert with message
     */
    function showErrorAlert(message) {
      errorMessage.textContent = message || 'An unexpected error occurred. Please try again.';
      errorAlert.classList.add('show');
      successAlert.classList.remove('show');
    }

    /**
     * Enable/disable form submission
     */
    function setSubmitting(isSubmitting) {
      submitBtn.disabled = isSubmitting;
      if (isSubmitting) {
        submitBtn.innerHTML = '<span class="loading-spinner"></span>Locking Ledger &amp; Provisioning Node...';
      } else {
        submitBtn.innerHTML = 'Update Merchant';
      }
    }

    /**
     * Lookup user ID by email address
     */
    function lookupUserByEmail(email) {
      return new Promise(function(resolve, reject) {
        mb.ajax({
          type: 'POST',
          url: '/?api=neighborhub&action=lookup_user_by_email',
          data: JSON.stringify({
            email: email
          }),
          contentType: 'application/json',
          dataType: 'json',
          timeout: 10000,
          success: function(response) {
            if (response.success && response.user_id) {
              resolve(response.user_id);
            } else {
              reject(response.error || 'User email not found in system');
            }
          },
          error: function(xhr, status, error) {
            if (xhr.status === 404) {
              reject('Email address not found in system. Please verify the owner account email.');
            } else if (xhr.status === 403) {
              reject('You do not have permission to perform this action.');
            } else {
              reject('Failed to lookup user email. Please try again.');
            }
          }
        });
      });
    }

    /**
     * Delete a merchant image
     * @param {number} merchantId
     */
    function deleteMerchantImage(merchantId) {
      const data = {
        merchant_id: merchantId
      };

      mb.ajax({
        url: '/?api=neighborhub&action=delete_merchant_image',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        processData: false,
        timeout: 10000,
        success: function(response) {
          if (response.success) {
            M.toast({
              html: '<i class="fas fa-check-circle"></i> Merchant image deleted successfully!'
            });
          } else {
            M.toast({
              html: '<i class="fas fa-exclamation-circle"></i> ' + (response.error || 'Failed to delete product image')
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Delete product error:', error);
          M.toast({
            html: '<i class="fas fa-exclamation-circle"></i> Error deleting product'
          });
        }
      });
    }

    /**
     * Submit merchant with populated fields via atomic multi-part FormData
     */
    function provisionMerchant(formData) {
      loading(1);
      return new Promise(function(resolve, reject) {
        mb.ajax({
          type: 'POST',
          url: '/?api=neighborhub&action=provision_merchant',
          data: formData,
          processData: false, // Prevent jQuery processing behavior loops
          contentType: false, // Let browser define multipart bound definitions safely
          timeout: 15000,
          success: function(response) {
            loading(0);
            if (response.success && response.merchant_id) {
              resolve(response.merchant_id);
            } else {
              reject(response.error || 'Failed to update merchant profile properties');
            }
          },
          error: function(xhr, status, error) {
            if (xhr.status === 403) {
              reject('Administrative authorization required to provision merchants.');
            } else if (xhr.status === 500) {
              reject('Database or file system serialization error. Please verify input structures.');
            } else {
              reject('Failed to update merchant profile properties. Please try again.');
            }
          }
        });
      });
    }

    /**
     * Handle form submission
     */
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      // Hide previous alerts
      hideAlerts();

      // Get form values
      const merchantId = document.getElementById('merchant-id').value;
      const businessName = document.getElementById('business-name').value.trim();
      const address = document.getElementById('address').value.trim();
      const latitude = parseFloat(document.getElementById('latitude').value);
      const longitude = parseFloat(document.getElementById('longitude').value);
      const phone = document.getElementById('phone').value.trim();
      const ownerEmail = document.getElementById('owner-email').value.trim();
      const website = document.getElementById('website').value.trim();
      const facebook = document.getElementById('facebook').value.trim();
      const platformFeeRate = document.getElementById('platform-fee-rate').value.trim();
      const platformFlatFee = document.getElementById('platform-flat-fee').value.trim();
      const storeHours = document.getElementById('store-hours').value.trim();
      const menus = document.getElementById('menus').value.trim();
      const deliveryAssignmentMode = document.getElementById('delivery-assignment-mode').value.trim();
      const deliveryMaxDistance = document.getElementById('delivery-max-distance').value.trim();
      const stripeApiKey = document.getElementById('stripe-api-key').value.trim();
      const stripePercentFee = document.getElementById('stripe-percent-fee').value.trim();
      const stripeFlatFee = document.getElementById('stripe-flat-fee').value.trim();

      // FIXED: Switched from .value checking to accurate boolean representation (.checked)
      const statusSelect = document.getElementById('status');
      const status = statusSelect.value;

      // Validate form fields
      if (!businessName || !address || !latitude || !longitude || !phone || !ownerEmail) {
        showErrorAlert('All fields are required. Please fill in all inputs.');
        return;
      }

      // Validate latitude/longitude ranges
      if (latitude < -90 || latitude > 90) {
        showErrorAlert('Latitude must be between -90 and 90 degrees.');
        return;
      }

      if (longitude < -180 || longitude > 180) {
        showErrorAlert('Longitude must be between -180 and 180 degrees.');
        return;
      }

      // Set submitting state
      setSubmitting(true);

      // Step 1: Lookup user ID from email address chaining console
      lookupUserByEmail(ownerEmail)
        .then(function(userId) {
          // Step 2: Build multipart payload tracking parameters explicitly
          const primaryImageInput = document.getElementById('product-merchant-image-input');
          const submissionPayload = new FormData();
          submissionPayload.append('merchant_id', merchantId);
          submissionPayload.append('business_name', businessName);
          submissionPayload.append('address', address);
          submissionPayload.append('latitude', latitude);
          submissionPayload.append('longitude', longitude);
          submissionPayload.append('phone', phone);
          submissionPayload.append('owner_user_id', userId);
          submissionPayload.append('stripe_api_key', stripeApiKey);
          submissionPayload.append('stripe_percent_fee', stripePercentFee);
          submissionPayload.append('stripe_flat_fee', stripeFlatFee);
          submissionPayload.append('website', website);
          submissionPayload.append('facebook', facebook);
          submissionPayload.append('platform_fee_rate', platformFeeRate);
          submissionPayload.append('platform_flat_fee', platformFlatFee);
          submissionPayload.append('store_hours', storeHours);
          submissionPayload.append('menus', menus);
          submissionPayload.append('delivery_assignment_mode', deliveryAssignmentMode);
          submissionPayload.append('delivery_max_distance', deliveryMaxDistance);
          submissionPayload.append('status', status);
          /*
          console.log('Constructed submission payload with user ID lookup:', {
            merchant_id: merchantId,
            business_name: businessName,
            address: address,
            latitude: latitude,
            longitude: longitude,
            phone: phone,
            owner_user_id: userId,
            stripe_api_key: stripeApiKey,
            stripe_percent_fee: stripePercentFee,
            stripe_flat_fee: stripeFlatFee,
            
            status: status
          });
          */
          // Append binary fields if modified
          if (primaryImageInput) {
            console.log('Primary image file to upload:', primaryImageInput.files[0]);
            submissionPayload.append('merchant_image', primaryImageInput.files[0]);
          }

          if (galleryInput && galleryInput.files.length > 0) {
            // Single loop across the correct file collection reference
            for (let i = 0; i < galleryInput.files.length; i++) {
              submissionPayload.append('gallery_images[]', galleryInput.files[i]);
            }
          }
          submissionPayload.append('gallery_count', galleryInput.length);

          return provisionMerchant(submissionPayload);
        })
        .then(function(returnedMerchantId) {
          // Success: Show confirmation badge
          setSubmitting(false);
          document.getElementById('merchant-id').value = returnedMerchantId;
          showSuccessAlert(returnedMerchantId);

          if (!merchantId) {
            const url = new URL(window.location.href);
            url.searchParams.set('merchant_id', returnedMerchantId);
            window.location.href = url.href;
          } else {
            //location.reload(); // Refresh to update existing merchant details on the page after edit
          }
        })
        .catch(function(errorMsg) {
          // Error: Show error message
          setSubmitting(false);
          showErrorAlert(errorMsg);
        });
    });

    // Reset layout elements cleanly
    /*
    document.getElementById('custom-reset-btn').addEventListener('click', function() {
      form.reset();
      hideAlerts();
      nativePrimaryFile = null;
      nativeGalleryArr = [];
      primaryPreviewBox.style.display = 'none';
      primaryPreviewImg.src = '#';
      primaryFileNameSpan.textContent = 'No primary image selected';
      galleryGridPreview.innerHTML = '';
    });
    */

    // Hide error alert when user starts typing
    form.addEventListener('input', function() {
      if (errorAlert.classList.contains('show')) {
        hideAlerts();
      }
    });

    //})();
  });
</script>