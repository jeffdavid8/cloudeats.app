/**
 * Merchant Storefront Handler
 */
class MerchantStorefront {
  constructor(options = {}) {
    this.options = Object.assign(
      {
        modalId: "#nh-custom-builder-modal",
        cartSidenavId: "#nh-shopping-cart-sidenav",
        toastIcon: "fa-check-circle",
        toastVerb: "basket",
        checkoutReturnUrl: "",
      },
      options,
    );

    this.isManualScrolling = false;
    this.visibleCategories = new Map();
    this.categoryObserver = null;
    nh.cart.checkoutReturnUrl = this.options.checkoutReturnUrl;

    this.init();
  }

  init() {
    this.initMaterializeComponents();
    this.initOperationalStatus();
    this.bindNavigationEvents();
    this.initIntersectionObserver();
    this.bindQuantityControls();
    this.bindCartActions();
  }

  initOperationalStatus() {
    const merchant = window.merchant || {};
    const status = String(merchant.status || "").toLowerCase();
    const hours = merchant.store_hours || {};
    const operational = this.getOperationalStatus(status, hours);
    const banner = document.createElement("div");

    banner.id = "nh-merchant-operational-status";
    banner.setAttribute("role", "status");
    banner.className = `nh-merchant-operational-status is-${operational.open ? "open" : "closed"} ${operational.urgency ? `is-${operational.urgency}` : ""}`;
    banner.innerHTML = `
      <span class="nh-merchant-operational-dot" aria-hidden="true"></span>
      <strong>${operational.label}</strong>
      <span>${operational.message}</span>
    `;
    document.body.insertBefore(banner, document.body.firstChild);

    if (!operational.open) {
      $(".nh-add-standard-btn, .nh-customize-trigger")
        .prop("disabled", true)
        .attr("aria-disabled", "true")
        .addClass("nh-merchant-closed-action");
    }

    this.addOperationalStatusStyles();
    this.operationalStatus = operational;
  }

  getOperationalStatus(status, hours) {
    if (status !== "active") {
      return { open: false, label: "Temporarily paused", message: "Temporarily paused by vendor" };
    }

    const schedule = this.normalizeSchedule(hours);
    const now = new Date();
    const current = this.findInterval(schedule, now);
    if (current) {
      const close = this.dateAt(now, current.close);
      if (close <= now) close.setDate(close.getDate() + 1);
      const minutes = Math.max(1, Math.ceil((close - now) / 60000));
      return {
        open: true,
        label: minutes <= 30 ? "Closing soon" : "Open now",
        urgency: minutes <= 30 ? "closing-soon" : "",
        message: `Closes in ${minutes} minute${minutes === 1 ? "" : "s"}`,
      };
    }

    const nextOpen = this.findNextOpen(schedule, now);
    const minutesUntilOpen = nextOpen ? Math.max(1, Math.ceil((nextOpen - now) / 60000)) : null;
    return {
      open: false,
      label: minutesUntilOpen !== null && minutesUntilOpen <= 30 ? "Opening soon" : "Closed",
      urgency: minutesUntilOpen !== null && minutesUntilOpen <= 30 ? "opening-soon" : "",
      message: nextOpen
        ? (minutesUntilOpen <= 30
          ? `Opens in ${minutesUntilOpen} minute${minutesUntilOpen === 1 ? "" : "s"}`
          : `Opens at ${this.formatDateTime(nextOpen, now)}`)
        : "Hours not listed",
    };
  }

  normalizeSchedule(hours) {
    if (typeof hours === "string") {
      try { hours = JSON.parse(hours); } catch (error) { return { weekly: {}, holidays: {} }; }
    }
    return {
      weekly: hours && typeof hours.weekly === "object" ? hours.weekly : {},
      holidays: hours && typeof hours.holidays === "object" ? hours.holidays : {},
    };
  }

  intervalsForDate(schedule, date) {
    const dateKey = this.dateKey(date);
    const day = date.toLocaleDateString("en-US", { weekday: "long" }).toLowerCase();
    const intervals = Object.prototype.hasOwnProperty.call(schedule.holidays, dateKey)
      ? schedule.holidays[dateKey]
      : schedule.weekly[day];
    return Array.isArray(intervals) ? intervals : [];
  }

  findInterval(schedule, now) {
    const todayIntervals = this.intervalsForDate(schedule, now);
    for (const interval of todayIntervals) {
      if (this.inInterval(interval, now)) return interval;
    }
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    for (const interval of this.intervalsForDate(schedule, yesterday)) {
      if (interval.close <= interval.open && this.inInterval(interval, now, yesterday)) return interval;
    }
    return null;
  }

  inInterval(interval, now, date = now) {
    if (!interval || !interval.open || !interval.close) return false;
    const open = this.dateAt(date, interval.open);
    const close = this.dateAt(date, interval.close);
    if (close <= open) close.setDate(close.getDate() + 1);
    return now >= open && now < close;
  }

  findNextOpen(schedule, now) {
    for (let offset = 0; offset <= 7; offset++) {
      const date = new Date(now);
      date.setHours(0, 0, 0, 0);
      date.setDate(date.getDate() + offset);
      for (const interval of this.intervalsForDate(schedule, date)) {
        const open = this.dateAt(date, interval.open);
        if (offset === 0 && open <= now) continue;
        return open;
      }
    }
    return null;
  }

  dateAt(date, value) {
    const match = typeof value === "string" && value.match(/^([01]\d|2[0-3]):([0-5]\d)$/);
    const result = new Date(date);
    if (!match) return new Date(NaN);
    result.setHours(Number(match[1]), Number(match[2]), 0, 0);
    return result;
  }

  dateKey(date) {
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, "0"),
      String(date.getDate()).padStart(2, "0"),
    ].join("-");
  }

  formatDateTime(date, now) {
    const time = date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
    return this.dateKey(date) === this.dateKey(now)
      ? time
      : `${date.toLocaleDateString([], { weekday: "long" })} at ${time}`;
  }

  addOperationalStatusStyles() {
    if (document.getElementById("nh-merchant-operational-status-styles")) return;
    const style = document.createElement("style");
    style.id = "nh-merchant-operational-status-styles";
    style.textContent = `
      .nh-merchant-operational-status{position:relative;z-index:1000;display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.65rem 1rem;font:600 14px/1.3 system-ui,sans-serif}
      .nh-merchant-operational-status.is-open{background:#ecfdf5;color:#065f46}
      .nh-merchant-operational-status.is-closed{background:#fff7ed;color:#9a3412}
      .nh-merchant-operational-status.is-closing-soon{background:#fffbeb;color:#92400e}
      .nh-merchant-operational-status.is-opening-soon{background:#eff6ff;color:#1e40af}
      .nh-merchant-operational-dot{width:9px;height:9px;border-radius:50%;background:currentColor}
      .nh-merchant-closed-action{opacity:.5!important;cursor:not-allowed!important}
    `;
    document.head.appendChild(style);
  }

  initMaterializeComponents() {
    $(this.options.modalId).modal();
    $(".materialboxed").materialbox();

    const dropdownElems = document.querySelectorAll(".dropdown-trigger");
    if (dropdownElems.length) {
      M.Dropdown.init(dropdownElems, {
        alignment: "right",
        constrainWidth: false,
        coverTrigger: false,
      });
    }

    $(".merchant-header-image img.scroll-to-top-link").on(
      "click",
      function (e) {
        $("html, body").animate(
          {
            scrollTop: 0,
          },
          200,
        );
      },
    );
    
  }

  bindNavigationEvents() {
    const self = this;


    // General Smooth Scroll & Manual Active Class Assignment
    $("a.category-menu-item").on("click", function (e) {
      e.preventDefault();
      const anchor = this.dataset.categoryAnchor
        ? this.dataset.categoryAnchor
            .toLowerCase()
            .replace(/&/g, "and")
            .replace(/[^a-z0-9\s-]/g, "")
            .trim()
            .replace(/\s+/g, "-")
        : "";

      if (anchor === "top") {
        $("html, body").animate({ scrollTop: 0 }, 600);
        return;
      }

      const $target = $("#" + anchor);
      
      if ($target.length) {
        self.isManualScrolling = true;

        $("a.category-menu-item").removeClass("active");
        $(`a.category-menu-item[data-category-anchor="${anchor}"]`).addClass(
          "active",
        );

        $("html, body").animate(
          {
            scrollTop: $target.offset().top - 120,
          },
          600,
          function () {
            setTimeout(() => {
              self.isManualScrolling = false;
            }, 50);
          },
        );
      }
    });
  }

  initIntersectionObserver() {
    const observerOptions = {
      rootMargin: "-120px 0px -50% 0px",
      threshold: [0, 0.2, 0.5],
    };

    this.categoryObserver = new IntersectionObserver((entries) => {
      if (this.isManualScrolling) return;

      entries.forEach((entry) => {
        const sectionId =
          entry.target.querySelector("[id]")?.id || entry.target.id;

        if (entry.isIntersecting) {
          this.visibleCategories.set(sectionId, entry.boundingClientRect.top);
        } else {
          this.visibleCategories.delete(sectionId);
        }
      });

      if (this.visibleCategories.size > 0) {
        let topCategory = null;
        let minTop = Infinity;

        this.visibleCategories.forEach((top, id) => {
          if (top < minTop) {
            minTop = top;
            topCategory = id;
          }
        });

        if (topCategory) {
          $("a.category-menu-item").removeClass("active");
          $(
            `a.category-menu-item[data-category-anchor="${topCategory}"]`,
          ).addClass("active");
        }
      }
    }, observerOptions);

    const self = this;
    $(".category-section, [id]").each(function () {
      const anchorId = $(this).attr("id");
      if (
        anchorId &&
        $(`a.category-menu-item[data-category-anchor="${anchorId}"]`).length > 0
      ) {
        self.categoryObserver.observe(this);
      }
    });
  }

  bindQuantityControls() {
    $(".nh-card-qty-plus")
      .off("click")
      .on("click", function () {
        const input = $(this).siblings(".nh-card-qty-input");
        input.val(parseInt(input.val() || 0) + 1);
      });

    $(".nh-card-qty-minus")
      .off("click")
      .on("click", function () {
        const input = $(this).siblings(".nh-card-qty-input");
        const currentVal = parseInt(input.val() || 1);
        if (currentVal > 1) {
          input.val(currentVal - 1);
        }
      });
  }

  bindCartActions() {
    const self = this;

    // Standard Add-To-Cart Action
    $(".nh-add-standard-btn")
      .off("click")
      .on("click", function () {
        if (self.operationalStatus && !self.operationalStatus.open) return;
        const btn = $(this);
        const cardQty = parseInt(
          btn.closest(".card-action").find(".nh-card-qty-input").val() || 1,
        );

        const productInfo = {
          id: btn.data("id"),
          name: btn.data("name"),
          price: btn.data("price"),
          customizable: btn.data("customizable") === 1,
          merchantImage: btn.data("merchant-image"),
          merchantId: btn.data("merchant-id"),
          merchantName: btn.data("merchant-name"),
          merchantAddress: btn.data("merchant-address"),
          merchantLat: btn.data("merchant-lat"),
          merchantLon: btn.data("merchant-lon"),
        };

        const targetMerchantId =
          btn.data("merchant-id") ||
          (window.nh && nh.cart ? nh.cart.activeMerchantIdReference : null);

        if (window.NHCart) {
          NHCart.addItem(targetMerchantId, productInfo, null, cardQty);
        }

        $(self.options.cartSidenavId).sidenav("open");
        btn.closest(".card-action").find(".nh-card-qty-input").val(1);

        M.toast({
          html: `<i class="fas ${self.options.toastIcon}"></i> Added (${cardQty}) ${productInfo.name} to ${self.options.toastVerb}!`,
          classes: "notify-success notify-bottom",
          displayLength: 2000,
        });
      });

    // Customize Modal Open Trigger
    $(".nh-customize-trigger")
      .off("click")
      .on("click", function () {
        if (self.operationalStatus && !self.operationalStatus.open) return;
        const btn = $(this);
        const productId = btn.data("id");
        const productName = btn.data("name");
        const price = btn.data("price");

        const initialCardQty = parseInt(
          btn.closest(".card-action").find(".nh-card-qty-input").val() || 1,
        );

        if (nh && nh.cart) {
          nh.cart.activeCustomProductMetadata = {
            id: productId,
            name: productName,
            price: price,
            merchantImage: btn.data("merchant-image"),
            merchantId: btn.data("merchant-id"),
            merchantName: btn.data("merchant-name"),
            merchantAddress: btn.data("merchant-address"),
            merchantLat: btn.data("merchant-lat"),
            merchantLon: btn.data("merchant-lon"),
            customizable: btn.data("customizable") === 1,
          };
        }

        $("#builder-modal-title").text("Customize Your " + productName);
        $(`${self.options.modalId} .nh-card-qty-input`).val(initialCardQty);

        const modalElement = document.querySelector(self.options.modalId);
        if (modalElement) {
          const modalInstance = M.Modal.getInstance(modalElement);
          if (modalInstance) modalInstance.open();
        }

        const $notesInput = $(
          `${self.options.modalId} .nh-card-customer-notes-input`,
        );
        if ($notesInput.length) {
          $notesInput.val("");
          M.textareaAutoResize($notesInput);
          if ($notesInput[0] && M.CharacterCounter) {
            M.CharacterCounter.init($notesInput[0]);
          }
        }

        $("#nh-modal-submit-add-to-cart")
          .removeData("editing-key")
          .html('Add To Basket <i class="fas fa-plus right"></i>');

        $("#builder-widget-mount-viewport").empty();

        if (typeof CustomOrderBuilder !== "undefined") {
          nh.cart.activeBuilderInstance = new CustomOrderBuilder(
            "builder-widget-mount-viewport",
            productId,
            price,
            null,
          );
        }

        btn.closest(".card-action").find(".nh-card-qty-input").val(1);
      });

    // Custom Item Modal Submit
    $("#nh-modal-submit-add-to-cart")
      .off("click")
      .on("click", function () {
        if (nh && nh.cart && nh.cart.activeBuilderInstance) {
          const receiptBlob = nh.cart.activeBuilderInstance.compileSelections();
          if (!receiptBlob) return;

          const finalModalQty = parseInt(
            $(`${self.options.modalId} .nh-card-qty-input`).val() || 1,
          );
          const notes = $(
            `${self.options.modalId} .nh-card-customer-notes-input`,
          ).val();
          nh.cart.activeCustomProductMetadata.customer_notes = notes;

          const editingKey = $(this).data("editing-key");

          if (editingKey && NHCart) {
            NHCart.updateItemCustomization(editingKey, receiptBlob, notes);
            if (NHCart.cart && NHCart.cart.items[editingKey]) {
              NHCart.cart.items[editingKey].quantity = finalModalQty;
            }
            NHCart.save();

            M.toast({
              html: `<i class="fas ${self.options.toastIcon}"></i> Updated ${nh.cart.activeCustomProductMetadata.name} in ${self.options.toastVerb}!`,
            });
          } else if (NHCart) {
            NHCart.addItem(
              nh.cart.activeCustomProductMetadata.merchantId,
              nh.cart.activeCustomProductMetadata,
              receiptBlob,
              finalModalQty,
            );

            M.toast({
              html: `<i class="fas ${self.options.toastIcon}"></i> Added (${finalModalQty}) custom ${nh.cart.activeCustomProductMetadata.name} to ${self.options.toastVerb}!`,
            });
          }

          if ($(self.options.cartSidenavId).hasClass("sidenav")) {
            $(self.options.cartSidenavId).sidenav("open");
          }

          const modalElement = document.querySelector(self.options.modalId);
          if (modalElement) {
            const modalInstance = M.Modal.getInstance(modalElement);
            if (modalInstance) modalInstance.close();
          }
        }
      });
  }
}
