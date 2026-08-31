class ShoppingCart {
  constructor(merchant = null) {
    if (!merchant) {
      console.warn(
        "ShoppingCart initialized without a merchant context. Please provide a merchant object.",
      );
    }
    this.storageKey = "mb_neighborhub_cart";
    this.cart = this.load();
    this.merchant = merchant;
    this.activeMerchantId = merchant ? merchant.id : null;
    this.distanceIneligibleMerchants = [];
    this.deliveryDisabled = merchant.delivery_assignment_mode === "disabled";
  }

  load() {
    const data = localStorage.getItem(this.storageKey);
    // We now just keep a flat dictionary of items. No global merchant_id lock!
    return data ? JSON.parse(data) : { items: {} };
  }

  save() {
    localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
    this.updateUI();
  }

  // Pass the merchantId directly into the item compilation logic
  // Add an explicit quantity parameter that defaults to 1 if missing
  addItem(merchantId, productData, customizationReceipt = null, quantity = 1) {
    const parsedQty = parseInt(quantity || 1);
    const choicesString = customizationReceipt
      ? JSON.stringify(customizationReceipt.choices)
      : "";
    const itemKey =
      `prod_${productData.id}_` +
      btoa(unescape(encodeURIComponent(choicesString)));

    if (this.cart.items[itemKey]) {
      console.log("Increasing quantity");
      // Increment by the chosen layout quantity block amount
      this.cart.items[itemKey].quantity += parsedQty;
    } else {
      this.cart.items[itemKey] = {
        product_id: parseInt(productData.id),
        merchant_id: merchantId,
        merchant_image: productData.merchantImage,
        merchant_name: productData.merchantName,
        merchant_address: productData.merchantAddress,
        merchant_lat: productData.merchantLat,
        merchant_lon: productData.merchantLon,
        name: productData.name,
        base_price: parseFloat(productData.price),
        unit_price: customizationReceipt
          ? parseFloat(customizationReceipt.final_price)
          : parseFloat(productData.price),
        quantity: parsedQty, // Assign parsed quantity selection directly
        customizations: customizationReceipt
          ? customizationReceipt.choices
          : null,
        customer_notes: productData.customer_notes, // Assign parsed quantity selection directly
      };
      console.log("Adding new object", this.cart.items[itemKey]);
    }

    this.save();
    return true;
  }

  /**
   * Retrieves a specific cart line item by its unique item key.
   * @param {string} itemKey
   * @returns {Object|null}
   */
  getItem(itemKey) {
    return this.cart.items[itemKey] || null;
  }

  /**
   * Updates an existing cart item. Since modifying customizations changes
   * the item key hash, this removes the old entry and adds the updated one.
   * @param {string} oldItemKey
   * @param {Object} productData
   * @param {Object} customizationReceipt
   * @param {number} quantity
   */
  updateItem(oldItemKey, productData, customizationReceipt, quantity) {
    if (this.cart.items[oldItemKey]) {
      // Preserve existing quantity if none is explicitly passed
      const qty =
        quantity !== undefined
          ? quantity
          : this.cart.items[oldItemKey].quantity;
      delete this.cart.items[oldItemKey];
      this.addItem(this.merchant.id, productData, customizationReceipt, qty);
    }
  }
  /**
   * Updates an existing cart item's customization choices, unit price, and notes.
   * Handles re-keying if the customization choices changed.
   * @param {string} oldKey - The original cart item key
   * @param {Object} customizationReceipt - The compiled object from CustomOrderBuilder
   * @param {string} [customerNotes] - Optional notes
   */
  updateItemCustomization(oldKey, customizationReceipt, customerNotes = null) {
    const existingItem = this.cart.items[oldKey];
    if (!existingItem) return false;

    const newChoicesString = customizationReceipt
      ? JSON.stringify(customizationReceipt.choices)
      : "";
    const newKey =
      `prod_${existingItem.product_id}_` +
      btoa(unescape(encodeURIComponent(newChoicesString)));

    // Extract current quantity and product info
    const currentQty = existingItem.quantity;
    const newPrice = customizationReceipt
      ? parseFloat(customizationReceipt.final_price)
      : existingItem.base_price;

    // Delete the old cart entry
    delete this.cart.items[oldKey];

    // If a line item with the new customization already exists, merge quantities
    if (this.cart.items[newKey]) {
      this.cart.items[newKey].quantity += currentQty;
      this.cart.items[newKey].unit_price = newPrice;
      if (customerNotes !== null)
        this.cart.items[newKey].customer_notes = customerNotes;
    } else {
      // Create updated entry under new key
      this.cart.items[newKey] = {
        ...existingItem,
        unit_price: newPrice,
        customizations: customizationReceipt
          ? customizationReceipt.choices
          : null,
        customer_notes:
          customerNotes !== null ? customerNotes : existingItem.customer_notes,
      };
    }

    this.save();
    return newKey;
  }

  /**
   * Directly removes an item line from the cart by its key.
   * @param {string} itemKey - The item key to remove
   */
  removeItem(itemKey) {
    if (this.cart.items[itemKey]) {
      delete this.cart.items[itemKey];
      this.save();
    }
  }

  changeQuantity(itemKey, amount) {
    if (!this.cart.items[itemKey]) return;
    this.cart.items[itemKey].quantity += amount;

    if (this.cart.items[itemKey].quantity <= 0) {
      delete this.cart.items[itemKey];
    }
    this.save();
  }

  // Helper to group items by merchant on the checkout screen
  getItemsByMerchant() {
    const groups = {};
    for (const key in this.cart.items) {
      const item = this.cart.items[key];
      if (!groups[item.merchant_id]) {
        groups[item.merchant_id] = [];
      }
      groups[item.merchant_id].push(item);
    }
    return groups;
  }

  // Helper to group items by merchant on the checkout screen
  getActiveMerchantItems() {
    const items = [];
    for (const key in this.cart.items) {
      const item = this.cart.items[key];
      if (item.merchant_id === this.merchant.id) {
        items.push(item); // Fix: Creates a valid, sequential array
      }
    }
    return items;
  }

  getTotals() {
    let subtotal = 0;
    let count = 0;
    let uniqueMerchants = new Set();

    // CHANGE 1: Use an object {} instead of an array [] to prevent empty index loops
    let merchantCoordinates = {};

    let distanceMi = 0;
    let deliveryFee = 0;
    let deliveryFeeCalc = null;

    // CHANGE 2: Wrap jQuery values in parseFloat() to guarantee they are numbers
    let custLat = parseFloat($("#nh-cart-lat").val());
    let custLon = parseFloat($("#nh-cart-lon").val());

    let taxAmount = 0;
    let tips = 0;
    let processingFee = 0;
    let platformFee = parseFloat(this.merchant.platform_flat_fee) + parseFloat(this.merchant.platform_fee_rate) * subtotal;
    let grandTotal = 0;

    for (const key in this.cart.items) {
      const item = this.cart.items[key];
      if (item.merchant_id == this.merchant.id) {
        subtotal += item.unit_price * item.quantity;
        count += item.quantity;
        merchantCoordinates[item.merchant_id] = {
          lat: parseFloat(item.merchant_lat),
          lon: parseFloat(item.merchant_lon),
        };
      }
      uniqueMerchants.add(item.merchant_id);

      // CHANGE 3: Convert merchant string coordinates to floats just to be safe
    }

    if (
      !this.deliveryDisabled &&
      $("#nh-cart-delivery-toggle").is(":checked")
    ) {
      for (const key in merchantCoordinates) {
        const merchantCoord = merchantCoordinates[key];

        // CHANGE 4: Add a safety guard to ensure valid coordinate data exists
        if (!merchantCoord || isNaN(merchantCoord.lat) || isNaN(custLat)) {
          continue;
        }

        deliveryFeeCalc = this.calculateClientDeliveryFee(
          merchantCoord.lat,
          merchantCoord.lon,
          custLat,
          custLon,
        );
        deliveryFee += deliveryFeeCalc.fee;
        distanceMi += deliveryFeeCalc.distanceMi;
      }
    }

    taxAmount = Number(subtotal * 0.0825);
    tips = parseFloat($("#nh-cart-tips-display").val()) || 0;
    // 🌟 FIX: Calculate the core transaction value before processing fees
    let totalBeforeProcessing = subtotal + taxAmount + tips + deliveryFee;
    // 🌟 FIX: Apply Stripe's real math against the entire checkout total, rounded cleanly
    processingFee = Number(
      (Number(this.merchant.stripe_flat_fee) +
        totalBeforeProcessing * (Number(this.merchant.stripe_percent_fee) / 100)) /
        (1 - (Number(this.merchant.stripe_percent_fee) / 100)),
    );
    processingFee = Math.round(processingFee * 100) / 100;

    // Final Grand Total matching the server completely
    grandTotal = totalBeforeProcessing + processingFee;

    return {
      deliveryFee: deliveryFee.toFixed(2),
      subtotal: subtotal.toFixed(2),
      processingFee: processingFee.toFixed(2),
      platformFee: platformFee.toFixed(2),
      count,
      merchantCount: uniqueMerchants.size,
      distanceMi: distanceMi.toFixed(2),
      tips: tips.toFixed(2),
      taxAmount: taxAmount.toFixed(2),
      grandTotal: grandTotal.toFixed(2),
    };
  }

  clear() {
    if (this.activeMerchantId) {
      //this.cart = { items: {} };
      for (const key in this.cart.items) {
        const item = this.cart.items[key];
        if (item.merchant_id == this.activeMerchantId) {
          delete this.cart.items[key];
        }
      }

      this.save();
    }
  }

  updateUI() {
    let totals = this.getTotals();
    let basePrice = totals.subtotal;
    $(".nh-cart-count-badge").text(totals.count);

    if (totals.count > 0) {
      $(".nh-cart-count-badge").show();
      $(".nh-cart-register").show();
      $('[id="nh-cart-delivery-container"]').css("display", "flex");

      if ($('[id="nh-cart-delivery-toggle"]').is(":checked")) {
        $('[id="nh-cart-delivery-fee-display"]').css("display", "flex");
      } else {
        $('[id="nh-cart-delivery-fee-display"]').css("display", "none");
      }

      $('[id="nh-checkout-stripe-btn"]').removeClass("disabled");
    } else {
      $(".nh-cart-count-badge").hide();
      $(".nh-cart-register").hide();
      $('[id="nh-cart-delivery-container"]').css("display", "none");
      $('[id="nh-checkout-stripe-btn"]').addClass("disabled");
    }

    if (this.deliveryDisabled) {
      $('[id="nh-cart-delivery-container"]').hide();
      $('[id="nh-cart-delivery-fields"]').css("display", "none");
    }

    $(".nh-cart-delivery-fee").text("$" + totals.deliveryFee);

    if ($('[id="nh-cart-delivery-toggle"]').is(":checked")) {
      $('[id="nh-cart-delivery-fields"]').css("display", "block");
    } else {
      $('[id="nh-cart-delivery-fields"]').css("display", "none");
    }

    let address = $('[id="nh-cart-address-string"]').val();
    if (
      !address.length ||
      totals.distanceMi >= this.merchant.delivery_max_distance
    ) {
      $(".nh-cart-delivery-distance-mi").html(totals.distanceMi + " mi ");
      $(".nh-cart-delivery-fee").hide();
      let btn = $(
        '<a href="#!" onclick="NHCart.verifyLocation()">(set delivery address)</button>',
      );
      $(".nh-cart-delivery-distance-mi").append(btn);
      $('[id="nh-summary-address-text"]').html(
        "Distance cannot exceed " +
          this.merchant.delivery_max_distance +
          " miles.  Please select a delivery location within " +
          this.merchant.delivery_max_distance +
          " miles.",
      );
      $('[id="nh-cart-delivery-summary-box"]').slideDown();
    } else {
      $(".nh-cart-delivery-distance-mi").text("(" + totals.distanceMi + " mi)");
      $(".nh-cart-delivery-distance-mi").html("");
      $(".nh-cart-delivery-fee").show();
      $('[id="nh-cart-delivery-summary-box"]')
        .html(`<strong>Delivery To:</strong> <span id="nh-summary-address-text" class="grey-text text-darken-2">${address}</span><br>
          <span style="font-size:11px; color:#9e9e9e; font-family:monospace;" id="nh-summary-coords-text"></span>`);
      // Update Sidebar summaries dynamically
      $('[id="nh-summary-coords-text"]').text(
        `GPS Coordinate Markers: ${$('[id="nh-cart-lat"]').val()}, ${$('[id="nh-cart-lon"]').val()}`,
      );
      $('[id="nh-cart-delivery-summary-box"]').slideDown();

      $('[id="nh-cart-verify-location-btn"]').html(
        '<i class="fas fa-check-circle left"></i> Change Address / Pin Location',
      );
    }

    $(".nh-cart-subtotal-display").text("$" + totals.subtotal);

    $('[id="nh-cart-processing-fee-display"]').text("$" + totals.processingFee);

    $('[id="nh-cart-fee-display"]').text("$" + totals.platformFee);

    // 2. Format it to 2 decimals ONLY when displaying it
    $('[id="nh-cart-tax-display"]').text(totals.taxAmount);

    $('[id="nh-cart-total-display"]').text("$" + totals.grandTotal);

    this.flagDistanceIneligableMerchants();

    window.dispatchEvent(
      new CustomEvent("nhCartUpdated", { detail: this.cart }),
    );
  }

  calculateClientDeliveryFee(lat1, lon1, lat2, lon2) {
    const R = 3958.8; // Miles
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distanceMi = R * c;

    // Mirror backend rules precisely
    const baseFee = 3.5;
    const baseMiles = 2.0;
    const perMile = 1.25;

    let fee =
      distanceMi <= baseMiles
        ? baseFee
        : baseFee + (distanceMi - baseMiles) * perMile;
    if (distanceMi > 10) fee += 3.0;

    return {
      fee: fee,
      distanceMi: distanceMi,
      //distanceMi: distanceMi.toFixed(1),
    };
  }

  /**
   * Calculates the straight-line distance between two sets of GPS coordinates using the Haversine formula.
   * @param {number} lat1 - Latitude of point 1
   * @param {number} lon1 - Longitude of point 1
   * @param {number} lat2 - Latitude of point 2
   * @param {number} lon2 - Longitude of point 2
   * @returns {number} Distance in miles
   */
  calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 3958.8; // Earth's radius in miles
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;

    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c; // Distance in miles
  }

  /**
   * Scans items in the cart and crosses coordinates with active merchants to flag distance violations.
   * @param {number} deliveryLat - User's selected delivery latitude
   * @param {number} deliveryLng - User's selected delivery longitude
   * @param {Array} merchantListWithCoords - Array of active merchant records matching [{id, business_name, latitude, longitude}]
   * @param {number} maxRadiusMiles - Maximum boundary limit (Defaults to 15 miles)
   * @returns {Array} List of problematic merchants failing validation checks
   */
  getIneligibleDeliveryMerchants(deliveryLat, deliveryLng) {
    let merchantListWithCoords = [];
    for (const key in this.cart.items) {
      const item = this.cart.items[key];
      merchantListWithCoords[item.merchant_id] = {
        lat: item.merchant_lat,
        lon: item.merchant_lon,
      };
    }

    if (!deliveryLat || !deliveryLng || !merchantListWithCoords) return [];

    // Find all distinct merchant IDs present in the current basket
    const activeMerchantIdsInCart = new Set();
    for (const key in this.cart.items) {
      activeMerchantIdsInCart.add(this.cart.items[key]);
    }

    const ineligibleMerchants = [];

    activeMerchantIdsInCart.forEach((merchant) => {
      // Find the corresponding coordinates metadata payload for this merchant
      const merchantData = merchantListWithCoords[merchant.merchant_id];

      if (merchantData && merchantData.lat && merchantData.lon) {
        const distance = this.calculateDistance(
          parseFloat(deliveryLat),
          parseFloat(deliveryLng),
          parseFloat(merchantData.lat),
          parseFloat(merchantData.lon),
        );

        if (distance > this.merchant.delivery_max_distance) {
          ineligibleMerchants.push({
            id: merchant.merchant_id,
            name:
              merchantData.business_name || `Merchant #${merchant.merchant_id}`,
            distance: distance.toFixed(1),
          });
        }
      }
    });

    return ineligibleMerchants;
  }

  flagDistanceIneligableMerchants() {
    let delivery_loc = {
      lat: $("#nh-cart-lat").val(),
      lon: $("#nh-cart-lon").val(),
    };
    if (!delivery_loc.lat.length || !delivery_loc.lat.length) return;

    this.distanceIneligibleMerchants = this.getIneligibleDeliveryMerchants(
      delivery_loc.lat,
      delivery_loc.lon,
    );
  }
}
