<?php
if (!defined('MB_RUNNING')) exit;
?>

<div class="container container-content py-5" style="max-width: 700px; margin-top: 40px; margin-bottom: 60px;">
  <div class="card shadow-lg rounded-xl border-0">
    <div class="card-content p-4 sm:p-6">

      <!-- Wizard Header -->
      <div class="mb-4">
        <span class="badge orange lighten-4 orange-text text-darken-3 px-3 py-1 rounded-pill font-weight-bold" style="position: absolute;
  right: 0;">Partner Onboarding</span>
        <h3 class="font-serif font-bold mt-2">Join Cloud Eats</h3>
        <p style="padding: 20px;" class="center-align ">Set up your local shop and start reaching neighbors in minutes.</p>
      </div>

      <!-- Step Indicator Bar -->
      <div class="row center-align mb-5" id="wizard-steps-indicator">
        <div class="col s3"><span class="badge circle orange white-text step-indicator-badge active" data-step="1">1</span><br><small>Details</small></div>
        <div class="col s3"><span class="badge circle grey lighten-2 text-darken-1 step-indicator-badge" data-step="2">2</span><br><small>Location</small></div>
        <div class="col s3"><span class="badge circle grey lighten-2 text-darken-1 step-indicator-badge" data-step="3">3</span><br><small>Operations</small></div>
        <div class="col s3"><span class="badge circle grey lighten-2 text-darken-1 step-indicator-badge" data-step="4">4</span><br><small>Payout</small></div>
      </div>

      <!-- Multi-Step Form -->
      <form id="merchant-onboarding-form" autocomplete="off">

        <!-- ================= STEP 1: BUSINESS DETAILS ================= -->
        <div class="wizard-step" id="step-1">
          <h5 class="font-weight-bold mb-3">1. Business Information</h5>

          <div class="input-field">
            <input id="business_name" name="business_name" type="text" class="validate" required>
            <label for="business_name">Business Name (e.g., Mario's Pizza)</label>
          </div>

          <div class="input-field">
            <input id="phone" name="phone" type="tel" class="validate" required>
            <label for="phone">Business Phone Number</label>
          </div>

          <div class="input-field">
            <select id="type" name="type">
              <option value="" disabled selected>Choose category</option>
              <option value="Restaurant">Restaurant / Food</option>
              <option value="Bakery">Bakery / Cafe</option>
              <option value="Grocery">Grocery / Market</option>
              <option value="Retail">Local Retail / Goods</option>
            </select>
            <label>Business Category</label>
          </div>

          <div class="row mt-4">
            <div class="col s12 right-align">
              <button type="button" class="btn waves-effect waves-light orange darken-2 next-step" data-target="2">
                Continue <i class="material-icons right">arrow_forward</i>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= STEP 2: LOCATION & RADIUS ================= -->
        <div class="wizard-step" id="step-2" style="display: none;">
          <h5 class="font-weight-bold mb-3">2. Location & Delivery Radius</h5>
          <p class="text-slate-600 text-sm mb-3">Search your address or use GPS to pin your storefront coordinates for precise delivery mapping.</p>

          <!-- Address Autocomplete & GPS Trigger Container -->
          <div class="relative mb-3">
            <div class="input-field mt-0">
              <input id="address" name="address" type="search" class="validate" required placeholder="Start typing business street address..." autocomplete="off">
              <label for="address" class="active">Business Address</label>
            </div>
            <!-- Suggestions Dropdown List -->
            <ul id="wizard-address-suggestions" class="dropdown-content collection" style="display: none; position: absolute; width: 100%; z-index: 99; max-height: 200px; overflow-y: auto;"></ul>
          </div>

          <!-- GPS & Map Picker Action Row -->
          <div class="mb-4 flex flex-wrap gap-3 items-center">
            <button type="button" id="btn-wizard-gps" class="btn-flat waves-effect orange-text text-darken-2 p-0 flex items-center gap-1">
              <i class="material-icons left" style="margin-right: 4px;">my_location</i> Use Current GPS
            </button>
            <span class="text-slate-300">|</span>
            <button type="button" id="btn-open-map-picker" class="btn-flat waves-effect indigo-text text-darken-2 p-0 flex items-center gap-1 modal-trigger" data-target="map-picker-modal">
              <i class="material-icons left" style="margin-right: 4px;">map</i> Pick on Map
            </button>
          </div>

          <!-- Latitude & Longitude Coordinate Fields -->
          <div class="row">
            <div class="input-field col s6">
              <input id="latitude" name="latitude" type="text" class="validate" required readonly>
              <label for="latitude" class="active">Latitude</label>
            </div>
            <div class="input-field col s6">
              <input id="longitude" name="longitude" type="text" class="validate" required readonly>
              <label for="longitude" class="active">Longitude</label>
            </div>
          </div>

          <!-- Max Distance Slider -->
          <div class="input-field mt-2">
            <p class="range-field">
              <label for="delivery_max_distance">Max Delivery Distance: <span id="distance-val">7.0</span> miles</label>
              <input type="range" id="delivery_max_distance" name="delivery_max_distance" min="1" max="25" value="7" step="0.5" />
            </p>
          </div>

          <div class="row mt-4">
            <div class="col s6">
              <button type="button" class="btn waves-effect waves-light prev-step" data-target="1">
                <i class="material-icons left">arrow_back</i> Back
              </button>
            </div>
            <div class="col s6 right-align">
              <button type="button" class="btn waves-effect waves-light orange darken-2 next-step" data-target="3">
                Continue <i class="material-icons right">arrow_forward</i>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= STEP 3: OPERATIONS & SETTINGS ================= -->
        <div class="wizard-step" id="step-3" style="display: none;">
          <h5 class="font-weight-bold mb-3">3. Store Hours & Dispatch Mode</h5>

          <div class="input-field">
            <textarea id="store_hours" name="store_hours" class="materialize-textarea" placeholder="e.g., Mon-Sun: 10:00 AM - 10:00 PM"></textarea>
            <label for="store_hours" class="active">Operating Store Hours</label>
          </div>

          <div class="input-field mt-4">
            <select id="delivery_assignment_mode" name="delivery_assignment_mode">
              <option value="auto" selected>Automatic Courier Dispatch (Recommended)</option>
              <option value="manual">Manual Dispatch</option>
              <option value="disabled">Pickup Only (No Delivery)</option>
            </select>
            <label>Delivery Assignment Mode</label>
          </div>

          <div class="row mt-4">
            <div class="col s6">
              <button type="button" class="btn waves-effect waves-light prev-step" data-target="2">
                <i class="material-icons left">arrow_back</i> Back
              </button>
            </div>
            <div class="col s6 right-align">
              <button type="button" class="btn waves-effect waves-light orange darken-2 next-step" data-target="4">
                Continue <i class="material-icons right">arrow_forward</i>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= STEP 4: PAYOUT SETUP & SUBMISSION ================= -->
        <div class="wizard-step" id="step-4" style="display: none;">
          <h5 class="font-weight-bold mb-3">4. Payout Setup & Confirmation</h5>
          <p class="text-slate-600 mb-4">Connect your payout account securely via Stripe to receive earnings directly from orders.</p>

          <div class="center-align p-4 rounded-lg mb-4" style="padding: 20px;">
            <button type="button" id="btn-stripe-connect" class="btn waves-effect waves-light indigo darken-3">
              <i class="material-icons left">account_balance</i> Connect Stripe Account
            </button>
            <p class="text-xs text-slate-500 mt-2" id="stripe-status-text"><?= $this->user->stripe_connect_id ? 'Stripe account connected.' : 'Stripe account not yet connected.' ?></p>
          </div>

          <div class="row mt-4">
            <div class="col s6">
              <button type="button" class="btn waves-effect waves-light prev-step" data-target="3">
                <i class="material-icons left">arrow_back</i> Back
              </button>
            </div>
            <div class="col s6 right-align">
              <!-- reCAPTCHA Widget Container -->
              <div class="row center-align mb-3">
                <div id="merchant-recaptcha-container" style="display: inline-block;"></div>
              </div>
              <button type="submit" id="btn-submit-merchant" class="btn waves-effect waves-light green darken-2">
                Complete Registration <i class="material-icons right">check</i>
              </button>
            </div>
          </div>
        </div>

      </form>
    </div>
    <div class="row center-align">
      <div id="recaptcha-inline-container" class="" style="display: inline-block;margin: 23px 0 0 0;"></div>
    </div>

  </div>
</div>

<!-- OpenStreetMap Leaflet Modal -->
<div id="map-picker-modal" class="modal mb-modal-fixed" style="width: 90%; max-width: 700px; height: 85vh; max-height: 650px; border-radius: 12px;">
  <div class="modal-content" style="padding: 20px; height: calc(100% - 64px); display: flex; flex-direction: column;">
    <h5 class="font-serif font-bold mb-1">Pin Business Location</h5>
    <p class="text-slate-600 text-sm mb-3">Click anywhere on the map to drop a pin at your exact storefront location.</p>

    <!-- Map Container -->
    <div id="osm-picker-map" style="width: 100%; flex-grow: 1; border-radius: 8px; border: 1px solid #e2e8f0;"></div>
  </div>
  <div class="modal-footer" style="padding: 0 24px;">
    <button type="button" class="modal-close btn-flat waves-effect grey-text text-darken-2">Cancel</button>
    <button type="button" id="btn-confirm-map-location" class="modal-close btn waves-effect orange darken-2 white-text">Confirm Location</button>
  </div>
</div>

<!-- Wizard Frontend Controller Script -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize Materialize Select & Range components
    M.FormSelect.init(document.querySelectorAll('select'));

    const distanceSlider = document.getElementById('delivery_max_distance');
    const distanceVal = document.getElementById('distance-val');
    if (distanceSlider) {
      distanceSlider.addEventListener('input', function() {
        distanceVal.textContent = this.value;
      });
    }

    // Step Navigation Logic
    const steps = document.querySelectorAll('.wizard-step');
    const indicators = document.querySelectorAll('.step-indicator-badge');

    function goToStep(stepNum) {
      steps.forEach(s => s.style.display = 'none');
      document.getElementById('step-' + stepNum).style.display = 'block';

      indicators.forEach((ind, idx) => {
        if (idx + 1 === parseInt(stepNum)) {
          ind.classList.remove('grey', 'lighten-2', 'text-darken-1');
          ind.classList.add('orange', 'white-text');
        } else {
          ind.classList.remove('orange', 'white-text');
          ind.classList.add('grey', 'lighten-2', 'text-darken-1');
        }
      });
      window.scrollTo({
        top: 100,
        behavior: 'smooth'
      });
    }

    document.querySelectorAll('.next-step').forEach(btn => {
      btn.addEventListener('click', function() {
        const target = this.getAttribute('data-target');
        // Basic validation for step 1
        if (target === '2') {
          const name = document.getElementById('business_name').value.trim();
          const phone = document.getElementById('phone').value.trim();
          if (!name || !phone) {
            M.toast({
              html: 'Please fill in business name and phone number.',
              classes: 'red'
            });
            return;
          }
        }
        goToStep(target);
      });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
      btn.addEventListener('click', function() {
        goToStep(this.getAttribute('data-target'));
      });
    });

    // Check if user already has an existing Stripe Connect account linked
    if (typeof mb !== 'undefined' && mb.user && mb.user.stripe_connect_id) {
      const stripeBtn = document.getElementById('btn-stripe-connect');
      const stripeStatus = document.getElementById('stripe-status-text');
      if (stripeBtn && stripeStatus) {
        stripeBtn.classList.remove('indigo', 'darken-3');
        stripeBtn.classList.add('green', 'darken-2');
        stripeBtn.innerHTML = '<i class="material-icons left">check_circle</i> Stripe Connected';
        stripeBtn.disabled = true;
        stripeStatus.textContent = 'Your Stripe payout account is already linked and active!';
        stripeStatus.className = 'text-xs text-green-600 mt-2 font-weight-bold';
      }
    }

    // Stripe Connect Setup Button Trigger
    const stripeBtn = document.getElementById('btn-stripe-connect');
    if (stripeBtn) {
      stripeBtn.addEventListener('click', function() {
        mb.ajax({
          url: "?api=neighborhub&action=setup_stripe_connect",
          method: "POST",
          success: function(response) {
            if (response && response.success && response.onboarding_url) {
              window.open(response.onboarding_url, '_blank');
              document.getElementById('stripe-status-text').textContent = 'Stripe connection in progress...';
              document.getElementById('stripe-status-text').className = 'text-xs text-green-600 mt-2 font-weight-bold';
            } else {
              M.toast({
                html: response.error || 'Failed to initialize Stripe connection.',
                classes: 'red'
              });
            }
          },
          error: function() {
            M.toast({
              html: 'Failed to communicate with service pipeline.',
              classes: 'red'
            });
          }
        });
      });
    }
    window.grecaptcha.ready(function() {
      recaptchaWidgetId = grecaptcha.render("recaptcha-inline-container", {
        sitekey: "<?= htmlspecialchars($this->config['recaptcha_site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>",
        badge: "inline",
        size: "invisible",
      });
    });
    // Final Form Submission using mb.ajax()
    const form = document.getElementById('merchant-onboarding-form');
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('btn-submit-merchant');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verifying Security...';

        // Use reCAPTCHA v3 invisible execution
        grecaptcha.ready(function() {
          grecaptcha.execute('<?= htmlspecialchars($this->config['recaptcha_site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>', {
            action: 'neighborhub_signup'
          }).then(function(token) {

            const formData = {
              business_name: document.getElementById('business_name').value,
              phone: document.getElementById('phone').value,
              type: document.getElementById('type').value,
              address: document.getElementById('address').value,
              latitude: document.getElementById('latitude').value,
              longitude: document.getElementById('longitude').value,
              delivery_max_distance: document.getElementById('delivery_max_distance').value,
              store_hours: document.getElementById('store_hours').value,
              delivery_assignment_mode: document.getElementById('delivery_assignment_mode').value,
              recaptcha_token: token // Passed securely to your new endpoint
            };

            submitBtn.textContent = 'Submitting Application...';

            mb.ajax({
              url: "?api=neighborhub&action=submit_merchant_application",
              method: "POST",
              data: JSON.stringify(formData),
              success: function(response) {
                if (response && response.success) {
                  M.toast({
                    html: 'Application submitted! We will reach out soon.',
                    classes: 'green'
                  });
                  setTimeout(function() {
                    window.location.href = "/?app=neighborhub&view=customer&p=dashboard";
                  }, 2000);
                } else {
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = 'Complete Registration <i class="material-icons right">check</i>';
                  M.toast({
                    html: response.error || 'Submission failed.',
                    classes: 'red'
                  });
                }
              },
              error: function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Complete Registration <i class="material-icons right">check</i>';
                M.toast({
                  html: 'Failed to communicate with service pipeline.',
                  classes: 'red'
                });
              }
            });
          });
        });
      });
    }


    // ================= STEP 2 GEOCODING & GPS LOGIC =================
    const addressInput = document.getElementById('address');
    const suggestionsList = document.getElementById('wizard-address-suggestions');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    let searchTimeout = null;

    // 1. Address Autocomplete via OpenStreetMap / Photon Proxy (`?api=neighborhub&action=geocode_proxy`)
    if (addressInput && suggestionsList) {
      addressInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);

        if (query.length < 3) {
          suggestionsList.style.display = 'none';
          return;
        }

        searchTimeout = setTimeout(function() {
          mb.ajax({
            url: "?api=neighborhub&action=geocode_proxy&q=" + encodeURIComponent(query),
            method: "GET",
            success: function(response) {
              if (Array.isArray(response) && response.length > 0) {
                suggestionsList.innerHTML = '';
                response.forEach(item => {
                  const li = document.createElement('li');
                  li.className = 'collection-item cursor-pointer hover:bg-slate-50';
                  li.style.padding = '10px 15px';
                  li.style.cursor = 'pointer';
                  li.innerHTML = `<span class="font-medium text-slate-800">${item.display_name}</span>`;

                  li.addEventListener('click', function() {
                    addressInput.value = item.display_name;
                    latInput.value = item.lat;
                    lngInput.value = item.lon;
                    suggestionsList.style.display = 'none';
                    M.updateTextFields();
                  });
                  suggestionsList.appendChild(li);
                });
                suggestionsList.style.display = 'block';
              } else {
                suggestionsList.style.display = 'none';
              }
            },
            error: function() {
              suggestionsList.style.display = 'none';
            }
          });
        }, 300);
      });

      // Hide suggestions when clicking outside
      document.addEventListener('click', function(e) {
        if (!addressInput.contains(e.target) && !suggestionsList.contains(e.target)) {
          suggestionsList.style.display = 'none';
        }
      });
    }

    // 2. Browser GPS Geolocation Lookup & Reverse Geocode Proxy
    const gpsBtn = document.getElementById('btn-wizard-gps');
    if (gpsBtn) {
      gpsBtn.addEventListener('click', function() {
        if (!navigator.geolocation) {
          M.toast({
            html: 'Geolocation is not supported by your browser.',
            classes: 'red'
          });
          return;
        }

        M.toast({
          html: 'Detecting your current location...',
          classes: 'orange'
        });

        navigator.geolocation.getCurrentPosition(function(position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          latInput.value = lat;
          lngInput.value = lng;
          M.updateTextFields();

          // Reverse geocode via backend proxy to fill the address label nicely
          mb.ajax({
            url: "?api=neighborhub&action=reverse_geocode_proxy&lat=" + lat + "&lng=" + lng,
            method: "GET",
            success: function(response) {
              if (response && response.display_name) {
                addressInput.value = response.display_name;
                M.updateTextFields();
              } else if (response && response.address) {
                // Fallback formatting from Nominatim v2 payload
                const addr = response.address;
                const formatted = [addr.road, addr.city || addr.town, addr.state].filter(Boolean).join(', ');
                addressInput.value = formatted || `${lat}, ${lng}`;
                M.updateTextFields();
              }
              M.toast({
                html: 'Location pinned successfully!',
                classes: 'green'
              });
            },
            error: function() {
              addressInput.value = `${lat}, ${lng}`;
              M.updateTextFields();
              M.toast({
                html: 'Coordinates locked, but address lookup failed.',
                classes: 'orange'
              });
            }
          });

        }, function(error) {
          M.toast({
            html: 'Unable to retrieve your location. Please check browser permissions.',
            classes: 'red'
          });
        }, {
          timeout: 10000,
          enableHighAccuracy: true
        });
      });
    }
    // ================= OPENSTREETMAP LEAFLET MAP MODAL LOGIC =================
    let pickerMap = null;
    let pickerMarker = null;
    let tempLat = 37.7749; // Default fallback (e.g. SF or central coords)
    let tempLng = -122.4194;

    // Initialize Materialize Modal
    const mapModalElem = document.getElementById('map-picker-modal');
    const mapModalInstance = M.Modal.init(mapModalElem, {
      onOpenEnd: function() {
        // Get existing lat/lng if already filled
        const currentLat = parseFloat(latInput.value);
        const currentLng = parseFloat(lngInput.value);
        if (!isNaN(currentLat) && !isNaN(currentLng)) {
          tempLat = currentLat;
          tempLng = currentLng;
        }

        // Initialize Leaflet map if not already created
        if (!pickerMap) {
          pickerMap = L.map('osm-picker-map').setView([tempLat, tempLng], 15);

          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
          }).addTo(pickerMap);

          pickerMarker = L.marker([tempLat, tempLng], {
            draggable: true
          }).addTo(pickerMap);

          // Update temp coords when marker is dragged
          pickerMarker.on('dragend', function(e) {
            const pos = pickerMarker.getLatLng();
            tempLat = pos.lat;
            tempLng = pos.lng;
          });

          // Click map to move marker
          pickerMap.on('click', function(e) {
            tempLat = e.latlng.lat;
            tempLng = e.latlng.lng;
            pickerMarker.setLatLng([tempLat, tempLng]);
          });
        } else {
          // Refresh size and pan to location if modal re-opens
          pickerMap.invalidateSize();
          pickerMap.setView([tempLat, tempLng], 15);
          pickerMarker.setLatLng([tempLat, tempLng]);
        }
      }
    });

    // Confirm Location Button Handler
    const confirmMapBtn = document.getElementById('btn-confirm-map-location');
    if (confirmMapBtn) {
      confirmMapBtn.addEventListener('click', function() {
        latInput.value = tempLat.toFixed(6);
        lngInput.value = tempLng.toFixed(6);
        M.updateTextFields();

        // Reverse geocode selected coordinates to populate the address field
        mb.ajax({
          url: "?api=neighborhub&action=reverse_geocode_proxy&lat=" + tempLat + "&lng=" + tempLng,
          method: "GET",
          success: function(response) {
            if (response && response.display_name) {
              addressInput.value = response.display_name;
              M.updateTextFields();
            } else if (response && response.address) {
              const addr = response.address;
              const formatted = [addr.road, addr.city || addr.town, addr.state].filter(Boolean).join(', ');
              addressInput.value = formatted || `${tempLat}, ${tempLng}`;
              M.updateTextFields();
            }
            M.toast({
              html: 'Map location pinned successfully!',
              classes: 'green'
            });
          },
          error: function() {
            addressInput.value = `${tempLat}, ${tempLng}`;
            M.updateTextFields();
            M.toast({
              html: 'Coordinates locked from map selection.',
              classes: 'orange'
            });
          }
        });
      });
    }
  });
</script>