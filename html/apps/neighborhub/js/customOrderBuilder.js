class CustomOrderBuilder {
  constructor(viewportId, productId, basePrice, onAddToCart, editingState) {
    this.viewport = $("#" + viewportId);
    this.productId = productId;
    this.basePrice = parseFloat(basePrice || 0);
    this.onAddToCart = onAddToCart;
    this.currentPrice = this.basePrice;
    this.editingState = editingState; // Holds { lineId, choices }

    this.loadComponentView();
  }

  loadComponentView() {
    const self = this;
    this.viewport.html(`
      <div class="center-align" style="padding: 40px 0;">
        <div class="preloader-wrapper small active">
          <div class="spinner-layer spinner-green-only">
            <div class="circle-clipper left"><div class="circle"></div></div>
            <div class="gap-patch"><div class="circle"></div></div>
            <div class="circle-clipper right"><div class="circle"></div></div>
          </div>
        </div>
      </div>
    `);

    mb.ajax({
      url: "?api=neighborhub&action=get_product_builder_view",
      method: "POST",
      data: JSON.stringify({ product_id: self.productId }),
      success: function (response) {
        if (response && response.success) {
          self.viewport.html(response.html);

          // Restore selections if editing an existing cart line item
          if (self.editingState && self.editingState.choices) {
            self.restoreSelections(self.editingState.choices);
          }

          self.bindEvents();
          self.calculatePrice();
        } else {
          self.viewport.html(
            '<p class="red-text">Error loading builder options.</p>',
          );
        }
      },
      error: function () {
        self.viewport.html(
          '<p class="red-text">Failed to communicate with service pipeline.</p>',
        );
      },
    });
  }

  restoreSelections(choices) {
    const self = this;
    $.each(choices, function (key, value) {
      if (typeof value === "object" && !Array.isArray(value)) {
        // Quantity widgets
        $.each(value, function (itemName, qty) {
          const input = self.viewport.find(
            `.nh-builder-qty-input[data-name="${itemName}"]`,
          );
          if (input.length) input.val(qty);
        });
      } else if (Array.isArray(value)) {
        // Checkboxes
        value.forEach(function (val) {
          self.viewport
            .find(
              `input[name="${key}[]"][value="${val}"], input[name="${key}"][value="${val}"]`,
            )
            .prop("checked", true);
        });
      } else {
        // Radios
        self.viewport
          .find(`input[name="${key}"][value="${value}"]`)
          .prop("checked", true);
      }
    });
  }

  bindEvents() {
    const self = this;

    // Unbind existing events on this viewport to prevent duplicate handlers
    this.viewport.off("change", ".nh-builder-input");
    this.viewport.off("click", ".nh-widget-plus");
    this.viewport.off("click", ".nh-widget-minus");

    // Catch radio/checkbox/quantity input changes
    this.viewport.on("change", ".nh-builder-input", function () {
      self.calculatePrice();
    });

    // Widget Plus Button Handler
    this.viewport.on("click", ".nh-widget-plus", function (e) {
      e.preventDefault();
      const input = $(this).siblings(".nh-builder-qty-input");
      const currentVal = parseInt(input.val() || 0, 10);

      if (input.attr("data-max-quantity")) {
        const maxQty = parseInt(input.attr("data-max-quantity"), 10);
        if (currentVal >= maxQty) {
          M.toast({
            html: `Maximum quantity of ${maxQty} reached.`,
            classes: "red",
          });
          return;
        }
      }

      input.val(currentVal + 1).trigger("change");
    });

    // Widget Minus Button Handler
    this.viewport.on("click", ".nh-widget-minus", function (e) {
      e.preventDefault();
      const input = $(this).siblings(".nh-builder-qty-input");
      const currentVal = parseInt(input.val() || 0, 10);
      if (currentVal > 0) {
        input.val(currentVal - 1).trigger("change");
      }
    });
  }

  calculatePrice() {
    let total = this.basePrice;

    // 1. Process standard radio / checkbox choices
    this.viewport.find(".nh-builder-input:checked").each(function () {
      const isIncluded =
        parseInt($(this).attr("data-included") || "0", 10) === 1;
      const priceModifier = parseFloat($(this).attr("data-price") || 0);

      if (!isIncluded) {
        total += priceModifier;
      }
    });

    // Deduct unchecked included items
    this.viewport
      .find(".nh-builder-input:checkbox:not(:checked)")
      .each(function () {
        const isIncluded =
          parseInt($(this).attr("data-included") || "0", 10) === 1;
        const priceModifier = parseFloat($(this).attr("data-price") || 0);

        if (isIncluded) {
          total -= priceModifier;
        }
      });

    // 2. Process add-subtract quantity widgets
    this.viewport.find(".nh-builder-qty-input").each(function () {
      const qty = parseInt($(this).val() || 0, 10);
      const unitPrice = parseFloat($(this).attr("data-price") || 0);

      if (qty > 0) {
        total += qty * unitPrice;
      }
    });

    this.currentPrice = Math.max(0, total);
    $(".live-builder-total").text("$" + this.currentPrice.toFixed(2));
  }

  compileSelections() {
    const form = this.viewport.find("#custom-builder-form");
    if (!form.length) return null;

    const selections = {};

    form.find(".nh-builder-input").each(function () {
      const isQtyWidget = $(this).hasClass("nh-builder-qty-input");

      if (isQtyWidget) {
        const qty = parseInt($(this).val() || 0, 10);
        if (qty > 0) {
          const itemTitle = $(this).attr("data-name");
          const stepId = $(this).attr("name").split("[")[0];

          if (!selections[stepId]) selections[stepId] = {};
          selections[stepId][itemTitle] = qty;
        }
      } else {
        const nameAttr = $(this).attr("name");
        if (!nameAttr) return;
        const cleanName = nameAttr.replace("[]", "");

        if ($(this).is(":radio") && $(this).is(":checked")) {
          selections[cleanName] = $(this).val();
        } else if ($(this).is(":checkbox")) {
          if (!selections[cleanName]) selections[cleanName] = [];
          if ($(this).is(":checked")) {
            selections[cleanName].push($(this).val());
          }
        }
      }
    });

    return {
      line_id: this.editingState ? this.editingState.lineId : "line_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5),
      base_price: this.basePrice,
      final_price: this.currentPrice,
      choices: selections
    };
  }
}
