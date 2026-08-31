<?
if (!defined('MB_RUNNING')) exit;
/**
 * Shopping Cart Sidenav
 * @var Object $customer
 * @var String $classList
 */
$merchant = $this->get('merchant', false);
//error_log(print_r($merchant, true));
$customer = $this->get('customer');

if (!isset($merchant->stripe_percent_fee)) {
  $merchant->stripe_percent_fee = floatval(getenv('STRIPE_FEE_PERCENT'));
}
if (!isset($merchant->stripe_flat_fee)) {
  $merchant->stripe_flat_fee = floatval(getenv('STRIPE_FEE_FLAT'));
}
?>

<ul id="nh-shopping-cart-sidenav" class="<?= $classList ?>" style="width: 360px; padding: 0 10px; display: flex; flex-direction: column; z-index: 1001;">

  <div class="nh-cart-header">
    <h5 style="margin: 0; font-weight: 700;"><i class="fas fa-shopping-bag teal-text"></i> Your Basket</h5>
    <a href="#!" class="sidenav-close grey-text"><i class="fas fa-times fa-lg"></i></a>
  </div>

  <div class="nh-cart-items-list-viewport" style="flex-shrink: 1; flex-grow: 0; margin-bottom: 1rem; padding-right: 10px;">
    <div class="center-align grey-text text-darken-1" style="padding: 40px 0;">
      <i class="fas fa-shopping-basket fa-3x" style="margin-bottom:10px; color:#cfd8dc;"></i>
      <p>Your basket is empty.</p>
    </div>
  </div>

  <div class="nh-cart-register" style="flex-shrink: 0; padding-top: 15px;">


    <div class="input-field" style="margin: 12px 0;">
      <i class="fas fa-phone prefix blue-text" style="font-size: 1.1rem; top: 10px;"></i>
      <input
        type="tell"
        pattern="[0-9]{10}"
        id="nh-cart-phone"
        class="validate"
        required="required"
        placeholder="Your contact phone number"
        value="<?= (!empty($customer->phone)) ? $customer->phone : '' ?>"
        style="height: 2.5rem; font-size: 13px;" />

    </div>


    <div id="nh-cart-delivery-container" style="flex-direction: column; padding: 10px; border-radius: 6px;">
      <p style="margin: 0 0 10px 0;">
        <label>
          <input type="checkbox" id="nh-cart-delivery-toggle" class="filled-in" />
          <span style="font-weight: 600;">Request Delivery</span>
        </label>
      </p>

      <div id="nh-cart-delivery-fields" style="display: none; margin-top: 10px;">

        <div class="input-field" style="margin: 12px 0 0 0;">
          <i class="fas fa-map-marker-alt prefix" style="font-size: 1.1rem; top: 10px;"></i>
          <button type="button" id="nh-cart-verify-location-btn" class="btn-small teal accent-4 waves-effect waves-light" style="width: auto; margin-left: 3rem; border-radius: 4px; height: 36px; line-height: 36px; font-weight:600;">Set Delivery Address & Pin
          </button>
        </div>

        <div id="nh-cart-delivery-summary-box" style="margin-top: 10px; font-size: 13px; display: none; padding: 8px; border-radius: 4px; border: 1px solid #3f3e3e;">
          <strong>Delivery To:</strong> <span id="nh-summary-address-text" class="grey-text text-darken-2"></span><br>
          <span style="font-size:11px; color:#9e9e9e; font-family:monospace;" id="nh-summary-coords-text"></span>
        </div>

        <div class="input-field" style="margin: 12px 0 0 0;">
          <i class="fas fa-comment-alt prefix" style="font-size: 1.1rem; top: 10px;"></i>
          <input id="nh-cart-notes-input" type="text" placeholder="Apartment #, gate code, instructions..." style="margin-bottom: 0; height: 2.5rem; font-size: 13px;">
        </div>

        <input type="hidden" id="nh-cart-address-string" value="<?= (!empty($customer->delivery_locations)) ? $customer->delivery_locations[0]['address'] : '' ?>">
        <input type="hidden" id="nh-cart-lat" value="<?= (!empty($customer->delivery_locations)) ? $customer->delivery_locations[0]['latitude'] : '' ?>">
        <input type="hidden" id="nh-cart-lon" value="<?= (!empty($customer->delivery_locations)) ? $customer->delivery_locations[0]['longitude'] : '' ?>">

      </div>

    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <span style="font-size: 16px; font-weight: 600; color: #555;">Subtotal:</span>
      <span class="nh-cart-subtotal-display" style="font-size: 22px; font-weight: 800; color: #26a69a;">$0.00</span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <span style="font-size: 16px; font-weight: 600; color: #555;">Processing:</span>
      <span id="nh-cart-processing-fee-display" style="margin-left: 1rem; font-size: 22px; font-weight: 800; color: #747474;">$0.00</span>
    </div>

    <? /*
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <span style="font-size: 16px; font-weight: 600; color: #555;">Platform/Basket:</span>
      <span id="nh-cart-fee-display" style="margin-left: 1rem; font-size: 22px; font-weight: 800; color: #747474;">$0.00</span>
    </div>
    */ ?>

    <div id="nh-cart-delivery-fee-display" style="justify-content: space-between; align-items: center; margin: 15px 0;">
      <span style="font-size: 16px; font-weight: 600; color: #555;">Delivery:</span>
      <span class="nh-cart-delivery-distance-mi" style="font-size: 16px; font-weight: 600; color: #1e7a71;"></span>
      <span class="nh-cart-delivery-fee" style="font-size: 18px; font-weight: 600; color: #1e7a71;">$0.00</span>
    </div>

    <div style="font-family: Roboto, sans-serif; max-width: 400px; margin: 0 auto; box-sizing: border-box;">

      <!-- Tip Input Wrapper -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; position: relative; width: 100%;">
        <!-- Left Label -->
        <span style="font-size: 16px; font-weight: 600; color: #555; white-space: nowrap; padding-right: 15px;">Tips:</span>

        <!-- Right Input Container -->
        <div style="position: relative; flex-grow: 1; display: flex; align-items: center;">
          <span style="position: absolute; left: 0; font-size: 22px; font-weight: 800; color: #555; pointer-events: none;">$</span>
          <input
            type="number"
            id="nh-cart-tips-display"
            step="0.01"
            min="0"
            value="0.00"
            style="width: 100%; font-size: 22px; font-weight: 800; text-align: right; border: none; border-bottom: 1px solid #9e9e9e; box-sizing: border-box; padding-left: 20px; height: 3rem; outline: none; margin: 0;">
        </div>
      </div>

      <!-- Tip Selector Buttons Wrapper -->
      <div style="display: flex; gap: 8px; justify-content: space-between; margin-bottom: 15px; width: 100%;">
        <button type="button" class="btn-flat tip-btn" data-pct="0.15" style="flex: 1; text-align: center; border: 1px solid #26a69a; padding: 0; font-weight: 600; color: #26a69a; height: 36px; line-height: 36px; border-radius: 2px; text-transform: uppercase;">15%</button>
        <button type="button" class="btn-flat tip-btn" data-pct="0.18" style="flex: 1; text-align: center; border: 1px solid #26a69a; padding: 0; font-weight: 600; color: #26a69a; height: 36px; line-height: 36px; border-radius: 2px; text-transform: uppercase;">18%</button>
        <button type="button" class="btn-flat tip-btn" data-pct="0.20" style="flex: 1; text-align: center; border: 1px solid #26a69a; padding: 0; font-weight: 600; color: #26a69a; height: 36px; line-height: 36px; border-radius: 2px; text-transform: uppercase;">20%</button>
        <button type="button" class="btn tip-btn active" data-pct="custom" style="flex: 1; text-align: center; padding: 0; font-weight: 600; background-color: #26a69a; color: #fff; height: 36px; line-height: 36px; border: none; border-radius: 2px; text-transform: uppercase; cursor: pointer;">Custom</button>
      </div>

    </div>


    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <span style="font-size: 16px; font-weight: 600; color: #555;">Tax:</span>
      <span id="nh-cart-tax-display" style="margin-left: 1rem; font-size: 22px; font-weight: 800; color: #747474;">$0.00</span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <span style="font-size: 16px; font-weight: 600; color: #555;">Total:</span>
      <span id="nh-cart-total-display" style="margin-left: 1rem; font-size: 22px; font-weight: 800; color: #747474;">$0.00</span>
    </div>


    <button id="nh-checkout-stripe-btn" class="btn btn-large teal accent-4 waves-effect waves-light" style="width: 100%; font-weight: 600; border-radius: 4px;">
      Checkout with Stripe <i class="fas fa-credit-card right"></i>
    </button>
    <button id="nh-clear-cart-btn" class="btn-flat grey-text text-darken-1 center-align" style="width: 100%; margin-top: 8px; font-size: 12px;">
      Clear All Items
    </button>
  </div>
</ul>

<script>
  /**
   * Reactively Render Items Inside the Sidenav Drawer Panel Context
   */
  function renderSidenavCartList(cartState) {
    const container = $('.nh-cart-items-list-viewport');
    const merchant_id = NHCart.activeMerchantId;
    container.empty();

    if (!cartState || NHCart.getActiveMerchantItems().length === 0) {
      container.html(`
                <div class="center-align grey-text text-darken-1" style="padding: 40px 0;">
                    <i class="fas fa-shopping-basket fa-3x" style="margin-bottom:10px; color:#cfd8dc;"></i>
                    <p>Your basket is empty.</p>
                </div>
            `);
      $('[id="nh-checkout-stripe-btn"]').addClass('disabled');
      $('[id="nh-cart-delivery-fee-display"]').hide();
      return;
    }

    $('[id="nh-checkout-stripe-btn"]').removeClass('disabled');

    for (const key in cartState.items) {
      const item = cartState.items[key];
      const isCustomized = !!item.customizations;
      let modifierHtml = '';

      if (merchant_id && item.merchant_id !== merchant_id) continue;

      if (item.customizations) {
        modifierHtml += '<div style="font-size:12px; color:#ff9800; line-height:14px; margin-top:2px;">';
        for (const stepKey in item.customizations) {
          const selection = item.customizations[stepKey];
          let stepLabel = stepKey.replace('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
          if (Array.isArray(selection) && selection.length > 0) {
            // Arrays (e.g. Checkboxes)
            modifierHtml += `<strong>${stepLabel}:</strong> ${selection.join(', ')}<br>`;
          } else if (typeof selection === 'object' && selection !== null) {
            // Objects (e.g. Add-Subtract Quantity Widgets)
            const qtyStrings = [];
            for (const itemName in selection) {
              qtyStrings.push(`${itemName} (x${selection[itemName]})`);
            }
            if (qtyStrings.length > 0) {
              modifierHtml += `<strong>${stepLabel}:</strong> ${qtyStrings.join(', ')}<br>`;
            }
          } else if (selection) {
            // Strings / Radios
            modifierHtml += `<strong>${stepLabel}:</strong> ${selection}<br>`;
          }
        }
        modifierHtml += '</div>';
      }
      const customerNotesHtml = item.customer_notes ? `<div style="font-size:12px; color:#9e9e9e; line-height:14px; margin-top:2px;"><strong>Notes:</strong> ${item.customer_notes}</div>` : '';

      const row = `
                <div class="cart-item-row" data-item-key="${key}" style="padding: 1rem 0;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="max-width: 70%;">
                            <div>
                              <span style="font-size:12px; display:none;" class="grey-text">${item.merchant_name}</span>
                              <span style="font-weight:600; font-size:14px; display:block;">${item.name}</span>
                              <span style="font-size:12px; display:block;" class="grey-text">$${item.unit_price.toFixed(2)} each</span>
                            </div>
                            ${modifierHtml}
                            ${customerNotesHtml}
                        </div>
                        <span style="font-weight:700; font-size:14px;">$${(item.unit_price * item.quantity).toFixed(2)}<br/>
                        <button class="btn-flat edit-cart-item-btn right">
                          <i class="material-icons">edit</i>
                        </button></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                        <div style="display:flex; align-items:center; border-radius:4px; padding:2px;">
                            <button class="btn-flat nh-qty-minus-btn" data-key="${key}" style="padding:0 8px; height:24px; line-height:24px;"><i class="fas fa-minus fa-xs"></i></button>
                            <span style="padding: 0 10px; font-weight:600; font-size:13px;">${item.quantity}</span>
                            <button class="btn-flat nh-qty-plus-btn" data-key="${key}" style="padding:0 8px; height:24px; line-height:24px;"><i class="fas fa-plus fa-xs"></i></button>
                        </div>
                    </div>
                </div>
            `;
      container.append(row);
    }
  }

  // Open builder prepopulated with existing cart line data
  $(document).on('click', '.edit-cart-item-btn', function() {
    const itemKey = $(this).closest('.cart-item-row').data('item-key');
    const cartItem = NHCart.getItem(itemKey);
    if (!cartItem) return;

    // 1. Store Product Metadata for Modal
    nh.cart.activeCustomProductMetadata = {
      id: cartItem.product_id,
      name: cartItem.name,
      price: cartItem.base_price,
      merchantId: cartItem.merchant_id,
      merchantName: cartItem.merchant_name,
      merchantAddress: cartItem.merchant_address,
      merchantLat: cartItem.merchant_lat,
      merchantLon: cartItem.merchant_lon
    };

    // 2. Set Modal Titles and Inputs
    $('#builder-modal-title').text('Update Customization for ' + cartItem.name);
    $('#nh-custom-builder-modal .nh-card-qty-input').val(cartItem.quantity);

    const $notesInput = $('#nh-custom-builder-modal .nh-card-customer-notes-input');
    $notesInput.val(cartItem.customer_notes || '');
    M.textareaAutoResize($notesInput);

    // 3. Update Submit Button for EDIT mode
    $('#nh-modal-submit-add-to-cart')
      .data('editing-key', itemKey)
      .html('Update Basket <i class="fas fa-check right"></i>');

    // 4. Mount Builder populated with previous selections
    $('#builder-widget-mount-viewport').empty();
    nh.cart.activeBuilderInstance = new CustomOrderBuilder(
      'builder-widget-mount-viewport',
      cartItem.product_id,
      cartItem.base_price,
      null, {
        lineId: itemKey,
        choices: cartItem.customizations
      }
    );

    $('#nh-custom-builder-modal').modal('open');
  });

  NHCart.verifyLocation = function() {
    M.Modal.getInstance(document.getElementById('nh-map-picker-modal')).open();
    $('#nh-modal-search-address').focus();

    setTimeout(function() {
      let initialLat = parseFloat($('#nh-cart-lat').val()) || '39.768507';
      let initialLng = parseFloat($('#nh-cart-lon').val()) || '-86.158047';
      const addressVal = $('#nh-cart-address-string').val();

      updateStagedCoords(initialLat, initialLng);

      if (addressVal) {
        $('#nh-modal-search-address').val(addressVal);
      }

      if (!NHCart.leafletMapInstance) {
        NHCart.leafletMapInstance = L.map('nh-modal-leaflet-canvas', {
          zoomControl: false // Move or hide zoom controls so they don't fight our top overlay bar
        }).setView([initialLat, initialLng], ($('#nh-cart-lat').val() ? 20 : 4));

        L.control.zoom({
          position: 'bottomright'
        }).addTo(NHCart.leafletMapInstance);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap'
        }).addTo(NHCart.leafletMapInstance);

        if (NHCart.draggableMarkerInstance) NHCart.leafletMapInstance.removeLayer(NHCart.draggableMarkerInstance);

        /*
        NHCart.draggableMarkerInstance = L.marker([initialLat, initialLng], {
          draggable: true
        }).addTo(NHCart.leafletMapInstance);
        */
        NHCart.draggableMarkerInstance = L.marker([initialLat, initialLng], {
          draggable: false
        });

        // Synchronize coordinate display panel on drag completion
        NHCart.draggableMarkerInstance.on('dragend', function() {
          const pos = NHCart.draggableMarkerInstance.getLatLng();
          updateStagedCoords(pos.lat, pos.lng);
          $("#nh-modal-loading-overlay").show();

          mb.reverseGeoCode(pos.lat, pos.lng, function(data) {
            //console.log('Reverse Geocoded Address -', data);
            $("#nh-modal-loading-overlay").hide();
            $('#nh-modal-search-address').val(data.display_name);
            M.updateTextFields();
          });
        });

        NHCart.leafletMapInstance.on("click", function(mapEvent) {
          const coords = mapEvent.latlng;

          // Reset cursor
          $(NHCart.leafletMapInstance).css("cursor", "");


          if (NHCart.draggableMarkerInstance) NHCart.leafletMapInstance.removeLayer(NHCart.draggableMarkerInstance);

          // Add a temporary "Ghost Marker" to show where they clicked
          if (!NHCart.draggableMarkerInstance) {
            NHCart.draggableMarkerInstance = L.marker([coords.lat, coords.lng], {
              draggable: true
            });
            // Synchronize coordinate display panel on drag completion
            NHCart.draggableMarkerInstance.on('dragend', function() {
              const pos = NHCart.draggableMarkerInstance.getLatLng();
              updateStagedCoords(pos.lat, pos.lng);
              $("#nh-modal-loading-overlay").show();
              mb.reverseGeoCode(pos.lat, pos.lng, function(data) {
                $('#nh-modal-search-address').val(data.display_name);
                M.updateTextFields();
                $("#nh-modal-loading-overlay").hide();
              });
            });

            NHCart.draggableMarkerInstance.addTo(NHCart.leafletMapInstance);
          } else {
            //console.log(coords);
            //NHCart.draggableMarkerInstance.setLatLng([coords.lat, coords.lng]);
            NHCart.draggableMarkerInstance = L.marker([coords.lat, coords.lng], {
              draggable: true
            });
            
            NHCart.draggableMarkerInstance.on('dragend', function() {
              const pos = NHCart.draggableMarkerInstance.getLatLng();
              updateStagedCoords(pos.lat, pos.lng);
              $("#nh-modal-loading-overlay").show();
              mb.reverseGeoCode(pos.lat, pos.lng, function(data) {
                $('#nh-modal-search-address').val(data.display_name);
                M.updateTextFields();
                $("#nh-modal-loading-overlay").hide();
              });
            });

            
            NHCart.draggableMarkerInstance.addTo(NHCart.leafletMapInstance);

          }
          $("#nh-modal-loading-overlay").show();

          mb.reverseGeoCode(coords.lat, coords.lng, function(data) {
            //console.log('Reverse Geocoded Address -', data);
            $('#nh-modal-search-address').val(data.display_name);
            M.updateTextFields();
            $("#nh-modal-loading-overlay").hide();

          });

          updateStagedCoords(coords.lat, coords.lng);

        });

      }

    }, 250);
  }

  function updateStagedCoords(lat, lng) {
    nh.stagedLat = parseFloat(lat).toFixed(6);
    nh.stagedLng = parseFloat(lng).toFixed(6);
    $('#nh-modal-coords-display').html(`📍 Lat: <strong>${nh.stagedLat}</strong>, Lng: <strong>${nh.stagedLng}</strong>`);
  }

  NHCart.leafletMapInstance = null;
  NHCart.draggableMarkerInstance = null;
  nh.stagedLat = null;
  nh.stagedLng = null;

  // Inside $(document).ready() inside merchant_products.php


  $(document).ready(function() {
    var shoppingCartSlideOut = document.getElementById("nh-shopping-cart-sidenav");
    var $sidenavOverlay = $(".sidenav-overlay");
    var instance = M.Sidenav.init(shoppingCartSlideOut, {
      outDuration: 200,
      edge: 'right',
      onOpenStart: function() {
        $('body').css('overflow-y', 'hidden');
        $sidenavOverlay.css("opacity", "1").show();
      },
      // Triggered immediately when close animation starts
      onCloseStart: function() {
        $('body').css('overflow-y', '');
        $sidenavOverlay.css("opacity", "0").hide();
      },
    });

    function openBasketCheckout() {
      if (!instance.isOpen) {
        instance.open(); // This naturally creates and animates the overlay
        $('body').css('overflow-y', 'hidden');
        $sidenavOverlay.css("opacity", "1").show();
      }
    }

    $(".shopping-cart-sidenav-trigger").on('click', function(e) {
      e.preventDefault();
      e.stopPropagation(); // Stops click from bubbling to document and auto-closing

      if (instance.isOpen) {
        instance.close();
        $('body').css('overflow-y', 'auto');
        $sidenavOverlay.css("opacity", "0").hide();
      } else {
        $('body').css('overflow-y', 'hidden');
        openBasketCheckout();
      }
    });

    $('#nh-map-picker-modal').modal();

    // 2. MODAL FOOTER QUANTITY BUTTON CONTROL PACKS
    $('#nh-modal-qty-plus').on('click', function() {
      const input = $('#nh-modal-qty-input');
      input.val(parseInt(input.val()) + 1);
      // Trigger price recalc if your CustomOrderBuilder hooks into quantity multiplication
      if (nh.cart.activeBuilderInstance) nh.cart.activeBuilderInstance.calculatePrice();
    });

    $('#nh-modal-qty-minus').on('click', function() {
      const input = $('#nh-modal-qty-input');
      const currentVal = parseInt(input.val());
      if (currentVal > 1) {
        input.val(currentVal - 1);
        if (nh.cart.activeBuilderInstance) nh.cart.activeBuilderInstance.calculatePrice();
      }
    });

    // 
    $('#nh-cart-tips-display').on('change', function() {
      NHCart.updateUI();
    });

    // Toggle delivery fields visibility
    $('[id="nh-cart-delivery-toggle"]').on('change', function() {
      if ($(this).is(':checked')) {
        $('[id="nh-cart-delivery-fields"]').slideDown();
      } else {
        $('[id="nh-cart-delivery-fields"]').slideUp();
        // Clear variables upon opt-out
        //$('#nh-cart-address-input, #nh-cart-notes-input, #nh-cart-lat, #nh-cart-lon').val('');
        $('[id="nh-cart-map-container"]').hide();
      }
      NHCart.updateUI();
    });
    const tipButtons = document.querySelectorAll('.tip-btn');
    const tipInput = document.getElementById('nh-cart-tips-display');

    tipButtons.forEach(button => {
      button.addEventListener('click', function() {
        // 1. Reset all buttons to the "flat" outline style
        tipButtons.forEach(btn => {
          btn.classList.remove('active');
          btn.style.backgroundColor = 'transparent';
          btn.style.color = '#26a69a';
          btn.style.border = '1px solid #26a69a';
        });

        // 2. Turn the active clicked button into a solid style
        this.classList.add('active');
        this.style.backgroundColor = '#26a69a';
        this.style.color = '#fff';
        this.style.border = 'none';

        // 3. Handle Tip Calculation
        const pct = this.getAttribute('data-pct');
        if (pct === 'custom') {
          tipInput.removeAttribute('readonly');
          tipInput.focus();
        } else {
          tipInput.setAttribute('readonly', true);
          const calculatedTip = (NHCart.getTotals().subtotal * parseFloat(pct)).toFixed(2);
          tipInput.value = calculatedTip;

          // Trigger total recalculation event if needed
          tipInput.dispatchEvent(new Event('change'));
        }
      });
    });

    // Keep the 'Custom' button active if the user updates the input box directly
    tipInput.addEventListener('input', function() {
      const customBtn = document.querySelector('.tip-btn[data-pct="custom"]');
      if (!customBtn.classList.contains('active')) {
        customBtn.click();
      }
    });

    // Map reveal & initialization simulation (Using standard browser geolocation or Leaflet map hook)
    // Inside $(document).ready() inside merchant_products.php


    // Opens Dialog Center View
    $('[id="nh-cart-verify-location-btn"]').on('click', function() {
      NHCart.verifyLocation();
    });

    let typingTimer;
    const doneTypingInterval = 1000; // Time in milliseconds (1.0 seconds)

    $('#nh-modal-search-address').on('keyup', function(e) {

      // Clear the timer every time the user types a character
      clearTimeout(typingTimer);
      // Start a new timer
      typingTimer = setTimeout(modalGeoCode, doneTypingInterval);

      if (e.key === "Enter") {
        event.preventDefault(); // Prevents the default action (like form submission) if needed
        clearTimeout(typingTimer);
        modalGeoCode();
      }

    });

    // ACTION 1: Geocode Text Address Input string to map point
    $('#nh-modal-geocode-btn').on('click', function() {
      modalGeoCode();
    });

    function modalGeoCode() {
      const query = $('#nh-modal-search-address').val().trim();
      if (!query) return M.toast({
        html: 'Please enter an address first!'
      });

      $(this).addClass('disabled').html('<i class="fas fa-circle-notch fa-spin"></i>');

      $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`, (data) => {
        $(this).removeClass('disabled').html('<i class="fas fa-search-location"></i>');
        if (data && data.length > 0) {
          const lat = parseFloat(data[0].lat);
          const lon = parseFloat(data[0].lon);

          // Set map view first
          NHCart.leafletMapInstance.setView([lat, lon], 16);

          // ✅ FIX: Create the marker if it doesn't exist, otherwise update its position
          if (!NHCart.draggableMarkerInstance) {
            NHCart.draggableMarkerInstance = L.marker([lat, lon], {
              draggable: true
            }).addTo(NHCart.leafletMapInstance);

            // Bind dragend logic to the newly created marker
            NHCart.draggableMarkerInstance.on('dragend', function() {
              const pos = NHCart.draggableMarkerInstance.getLatLng();
              updateStagedCoords(pos.lat, pos.lng);
              mb.reverseGeoCode(pos.lat, pos.lng, function(revData) {
                $('#nh-modal-search-address').val(revData.display_name);
                M.updateTextFields();
              });
            });
          } else {
            // If it already exists, just update its position and ensure it's on the map
            NHCart.draggableMarkerInstance.setLatLng([lat, lon]);
            if (!NHCart.leafletMapInstance.hasLayer(NHCart.draggableMarkerInstance)) {
              NHCart.draggableMarkerInstance.addTo(NHCart.leafletMapInstance);
            }
          }

          updateStagedCoords(lat, lon);
        } else {
          M.toast({
            html: '❌ Address not found. Try adding a city or zip code.'
          });
        }
      }).fail(() => {
        $(this).removeClass('disabled').html('<i class="fas fa-search-location"></i>');
      });
    }

    // ACTION 2: Triangulate current Hardware coordinates via Browser GPS link
    $('#nh-modal-gps-btn').on('click', function() {
      if (!navigator.geolocation) return M.toast({
        html: 'GPS location is not supported by your browser.'
      });

      $(this).addClass('disabled');

      // We add options as the 3rd parameter here 👇
      navigator.geolocation.getCurrentPosition((position) => {
        $(this).removeClass('disabled');
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        NHCart.leafletMapInstance.setView([lat, lon], 17);
        NHCart.draggableMarkerInstance.setLatLng([lat, lon]);
        updateStagedCoords(lat, lon);

        // Reverse-geocode coordinates to text so the user sees a readable address label!
        $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`, (data) => {
          if (data && data.display_name) {
            $('#nh-modal-search-address').val(data.display_name);
          }
        });
      }, (error) => { // Handle errors gracefully
        $(this).removeClass('disabled');
        M.toast({
          html: '❌ Unable to access your current location.'
        });
      }, {
        enableHighAccuracy: true, // 🌟 Forces mobile/GPS devices to use hardware GPS instead of IP guesses
        timeout: 10000, // Give up after 10 seconds if it can't lock
        maximumAge: 0 // Prevent the browser from returning a cached (old) location
      });
    });

    // ACTION 3: Save results and update sidebar layout summary references
    $('#nh-modal-save-coords-btn').on('click', function() {
      const finalAddressText = $('#nh-modal-search-address').val().trim();

      if (!finalAddressText) {
        M.toast({
          html: '❌ Please specify a verified address designation.'
        });
        return;
      }

      // Save out inputs to the form registers
      $('#nh-cart-address-string').val(finalAddressText);
      $('#nh-cart-lat').val(nh.stagedLat);
      $('#nh-cart-lon').val(nh.stagedLng);


      NHCart.updateUI();

      M.Modal.getInstance(document.getElementById('nh-map-picker-modal')).close();
    });

    // Update Stripe Button click to package up delivery variables
    $('[id="nh-checkout-stripe-btn"]').on('click', function() {
      let currentCartState = {
        'items': NHCart.getActiveMerchantItems()
      };
      //currentCartState = NHCart.load();

      if (!currentCartState || Object.keys(currentCartState.items).length === 0) return;

      const isDeliveryRequested = $('#nh-cart-delivery-toggle').is(':checked');

      // Pull down the dynamic textual address and structural coordinate pairs we bound earlier
      const customerPhone = $('#nh-cart-phone').val() || '';
      if (customerPhone.length) {
        mb.storage.apps.neighborhub.preferences.guest.phone = customerPhone;
        storage_set();
      }
      const deliveryAddress = $('#nh-cart-address-string').val() || $('#nh-cart-address-input').val() || '';
      const deliveryNotes = $('#nh-cart-notes-input').val() || '';
      const latVal = $('#nh-cart-lat').val();
      const lngVal = $('#nh-cart-lon').val();

      if (!NHCart.deliveryDisabled && isDeliveryRequested) {
        if (!deliveryAddress.trim()) {
          M.toast({
            html: '❌ Please provide an address for home delivery.'
          });
          $('#nh-modal-search-address').focus();
          return;
        }

        // ============================================================================
        // FRONTEND RADIUS GUARD ENGINE INTERCEPTOR
        // ============================================================================
        // Check if global merchant positioning lookup metrics are available on the window scope
        if (latVal && lngVal && typeof allMerchantsLookupList !== 'undefined') {

          // Scan for distance violations (Maximum 15 miles restriction)
          const blockedMerchants = NHCart.getIneligibleDeliveryMerchants(latVal, lngVal);

          if (blockedMerchants.length > 0) {
            const problematicNames = blockedMerchants.map(m => `${m.name} (${m.distance} mi)`).join(', ');

            Swal.fire({
              title: 'Out of Delivery Range',
              text: `The following kitchens are located too far from your address for delivery: ${problematicNames}. Please select an alternate address, or switch to Store Pickup.`,
              icon: 'error',
              confirmButtonText: 'Understood'
            });
            return; // Halt checkout process immediately
          }
        }
      }

      //$(this).addClass('disabled').html('Preparing Secure Checkout <i class="fas fa-circle-notch fa-spin right"></i>');

      loading(1);

      // Build out combined parameters payload object dictionary
      const checkoutOptions = {
        merchant_id: NHCart.activeMerchantId,
        basket: currentCartState,
        totals: NHCart.getTotals(),
        delivery: isDeliveryRequested,
        customer_phone: customerPhone,
        delivery_notes: deliveryNotes.trim(),
        delivery_address: deliveryAddress.trim(),
        notes: $('#nh-cart-notes-input').val().trim(),
        delivery_lat: latVal,
        delivery_lon: lngVal,
        distanceIneligibleMerchants: NHCart.distanceIneligibleMerchants,
        return_url: nh.cart.checkoutReturnUrl
      };

      mb.ajax({
        url: '?api=neighborhub&action=create_checkout_session',
        method: 'POST',
        data: JSON.stringify(checkoutOptions),
        success: function(response) {
          if (response && response.success && response.checkout_url) {

            window.location.href = response.checkout_url;

          } else {
            M.toast({
              html: 'Error: ' + (response.error || 'Failed to initialize session.')
            });
            $('#nh-checkout-stripe-btn').removeClass('disabled').html('Checkout with Stripe <i class="fas fa-credit-card right"></i>');
          }
        },
        error: function() {
          M.toast({
            html: 'Network connection failure across payment channels.'
          });
          $('#nh-checkout-stripe-btn').removeClass('disabled').html('Checkout with Stripe <i class="fas fa-credit-card right"></i>');
        }
      });
    });

    // Connect global window handler listener loop to the side summary rendering step
    window.addEventListener('nhCartUpdated', function(e) {
      renderSidenavCartList(e.detail);
    });
    NHCart.updateUI(); // Run immediate baseline evaluation

    // Handle Quantity adjustments inside drawer layout using event delegation
    $('#nh-shopping-cart-sidenav').on('click', '.nh-qty-minus-btn', function() {
      NHCart.changeQuantity($(this).data('key'), -1);
    });

    $('#nh-shopping-cart-sidenav').on('click', '.nh-qty-plus-btn', function() {
      NHCart.changeQuantity($(this).data('key'), 1);
    });

    $('#nh-clear-cart-btn').on('click', function() {
      if (confirm("Are you sure you want to completely empty your basket?")) {
        NHCart.clear();
        renderSidenavCartList({
          'items': {}
        });
      }
    });

  });
</script>