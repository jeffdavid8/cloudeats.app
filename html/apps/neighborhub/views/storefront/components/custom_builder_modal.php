<?php
if (!defined('MB_RUNNING')) exit;
?>
<div id="nh-custom-builder-modal" class="modal mb-modal-fixed" style="border-radius: 8px;">
  <div class="modal-inner-overlay"></div>
  <div class="modal-header" style="max-width: 800px;">
    <h4 id="builder-modal-title" style="font-weight: 700; margin-top: 0;">Build Custom Selection</h4>
    <div style="display: flex; align-items: center; justify-content: flex-end; flex-direction: row;margin-bottom: 10px;">
      <span class="grey-text text-darken-2" style="font-size: 13px; font-weight: 600; margin-right: 4px;">Quantity </span>
      <div style="display: flex; align-items: center; background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; padding: 2px;">
        <button class="btn-flat nh-card-qty-minus" style="margin: 0.5em; padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
        <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 600;-webkit-appearance: none; margin: 0; -moz-appearance: textfield;">
        <button class="btn-flat nh-card-qty-plus" style="margin: 0.5em; padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
      </div>
    </div>
  </div>
  <div class="modal-content" style="padding: 24px; max-width: 700px;">
    <p class="grey-text text-darken-1" style="margin-bottom: 20px;">Personalize your recipe modifiers below.</p>
    <div id="builder-widget-mount-viewport"></div>
  </div>
  <div class="row" style="width:100%; margin: 0; padding: 0 24px 15px;">
    <div class="input-field col s12" style="margin-top: 10px; margin-bottom: 0;">
      <i class="fas fa-edit prefix pk-icon" style="top: 0.8rem; font-size: 1.2rem;"></i>
      <textarea id="builder-customer-notes" class="materialize-textarea nh-card-customer-notes-input pk-text-main" data-length="200" maxlength="200" placeholder="E.g., Dressing on the side, well done, extra crispy..." style="min-height: 45px; padding-bottom: 0; margin-bottom: 5px;"></textarea>
      <label for="builder-customer-notes" class="active" style="color: var(--brand-gold); font-weight: 600;">Special Instructions / Customer Notes</label>
      <span class="helper-text pk-text-muted" style="font-size: 11px;">Add any custom requests or prep preferences for the kitchen.</span>
    </div>
  </div>
  <div class="modal-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 0 24px;">
    <div>
      <h5 style="margin:0; font-size: 15px; color:#555; font-weight:600;">Total Item Cost:</h5>
      <span class="live-builder-total teal-text text-darken-2" style="font-weight: 800; font-size: 20px;">$0.00</span>
    </div>
    <div>
      <a href="#!" class="modal-close waves-effect waves-red btn-flat" style="margin-right: 8px;">Cancel</a>
      <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light orange" style="font-weight:600;">
        Add To Basket <i class="fas fa-plus right"></i>
      </button>
    </div>
  </div>
</div>
