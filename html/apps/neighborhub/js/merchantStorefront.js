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
    this.initTermsBannerOffset();
    this.initMerchantHeaderStack();
    this.bindDraftPreviewMessages();
    this.initHeroParallax();
    this.bindNavigationEvents();
    this.initIntersectionObserver();
    this.bindQuantityControls();
    this.bindCartActions();
  }

  initHeroParallax() {
    const hero = document.querySelector(".merchant-storefront-hero");
    if (!hero) return;

    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    let frame = null;
    const update = () => {
      frame = null;
      if (reducedMotion.matches || hero.dataset.storefrontLayout !== "hero-full") {
        hero.style.removeProperty("--merchant-hero-parallax");
        return;
      }
      const rect = hero.getBoundingClientRect();
      if (rect.bottom < 0 || rect.top > window.innerHeight) return;
      const offset = (window.innerHeight / 2 - (rect.top + rect.height / 2)) * 0.2;
      const limit = rect.height * 0.18;
      hero.style.setProperty("--merchant-hero-parallax", `${Math.max(-limit, Math.min(limit, offset))}px`);
    };
    const schedule = () => {
      if (frame === null) frame = window.requestAnimationFrame(update);
    };
    window.addEventListener("scroll", schedule, { passive: true });
    window.addEventListener("resize", schedule);
    reducedMotion.addEventListener("change", schedule);
    new MutationObserver(schedule).observe(hero, {
      attributes: true,
      attributeFilter: ["data-storefront-layout"],
    });
    schedule();
  }

  initTermsBannerOffset() {
    const update = () => {
      const banner = document.getElementById("nh-terms-banner");
      const height = banner && window.getComputedStyle(banner).display !== "none"
        ? banner.getBoundingClientRect().height
        : 0;
      document.documentElement.style.setProperty("--nh-terms-height", `${height}px`);
    };
    update();
    window.addEventListener("resize", update);
    const banner = document.getElementById("nh-terms-banner");
    if (banner) {
      new MutationObserver(update).observe(banner, {
        attributes: true,
        attributeFilter: ["style", "class"],
      });
    }
  }

  bindDraftPreviewMessages() {
    if (document.body.dataset.storefrontPreview !== "true") return;
    window.addEventListener("message", (event) => {
      if (event.origin !== window.location.origin || !event.data || event.data.type !== "merchant-storefront-draft") {
        return;
      }
      if (!event.data.configuration || typeof event.data.configuration !== "object") {
        return;
      }
      this.applyPublishedConfiguration(event.data.configuration);
    });
  }

  applyPublishedConfiguration(configuration) {
    const root = document.documentElement;
    const colors = configuration.colors || {};
    if (colors.primary) root.style.setProperty("--merchant-primary", colors.primary);
    if (colors.background) root.style.setProperty("--merchant-background", colors.background);
    if (colors.text) root.style.setProperty("--merchant-text", colors.text);
    if (colors.primary) document.body.style.setProperty("--merchant-primary", colors.primary);
    if (colors.background) document.body.style.setProperty("--merchant-background", colors.background);
    if (colors.text) document.body.style.setProperty("--merchant-text", colors.text);
    document.body.dataset.storefrontTheme = configuration.theme || "default";
    document.body.dataset.storefrontLayout = configuration.layout || "hero-centered";
    const hero = document.querySelector(".merchant-storefront-hero");
    if (hero && ["hero-centered", "hero-split", "hero-minimal", "hero-full"].includes(configuration.layout)) {
      hero.dataset.storefrontLayout = configuration.layout;
    }
    if (hero) {
      hero.dataset.storefrontLogoStyle = ["default", "original", "circle", "rectangle"].includes(configuration.logo_style)
        ? configuration.logo_style
        : "default";
    }
    this.applyStorefrontAssets(configuration);

    const visibility = configuration.visibility || {};
    $(".iv-product-search-input, #product-search-input, [id*='product-search']")
      .closest(".row")
      .toggle(visibility.search !== false);
    $(".category-menu-item, .category-menu-item-back-to-top")
      .closest("li")
      .toggle(visibility.categories !== false);
    $(".secondary-category-menu, .menu-toggle-wrapper")
      .toggle(visibility.categories !== false);
    $(".merchant-info-card").toggle(visibility.contact !== false);
    const operationalStatus = $("#nh-merchant-operational-status");
    operationalStatus.toggle(visibility.operational_status !== false);
    document.body.classList.toggle("nh-operational-status-hidden", visibility.operational_status === false);
  }

  applyStorefrontAssets(configuration) {
    const hero = document.querySelector(".merchant-storefront-hero");
    const logo = document.querySelector("[data-storefront-logo]");
    if (!hero) return;

    const assets = configuration.assets || {};
    const assetUrls = configuration.asset_urls || {};
    const profileImage = hero.dataset.storefrontProfileImage || "";
    const logoUrl = assets.logo_id && assetUrls[assets.logo_id] ? assetUrls[assets.logo_id] : profileImage;
    const heroUrl = assets.hero_id && assetUrls[assets.hero_id]
      ? assetUrls[assets.hero_id]
      : (hero.dataset.storefrontLayout === "hero-full" ? profileImage : "");

    if (logo) {
      logo.src = logoUrl;
    }
    hero.style.backgroundImage = heroUrl
      ? `linear-gradient(rgba(0, 0, 0, .35), rgba(0, 0, 0, .35)), url("${heroUrl.replace(/"/g, '\\"')}")`
      : "";
  }

  initOperationalStatus() {
    const banner = document.getElementById("nh-merchant-operational-status");
    if (!banner) return;
    const disabledByStatus = new WeakSet();
    const update = (open) => {
      this.operationalStatus = { open };
      document.querySelectorAll(".nh-add-standard-btn, .nh-customize-trigger").forEach((button) => {
        if (!open && !button.disabled) {
          disabledByStatus.add(button);
          button.disabled = true;
          button.setAttribute("aria-disabled", "true");
          button.classList.add("nh-merchant-closed-action");
        } else if (open && disabledByStatus.has(button)) {
          disabledByStatus.delete(button);
          button.disabled = false;
          button.removeAttribute("aria-disabled");
          button.classList.remove("nh-merchant-closed-action");
        }
      });
    };
    update(banner.dataset.status === "open" && banner.dataset.stale !== "true");
    banner.addEventListener("nh:merchant-status", (event) => update(event.detail.open));
  }

  initMerchantHeaderStack() {
    const header = document.querySelector("header.header");
    const logo = document.querySelector(".merchant-header-image");
    const context = document.querySelector(".merchant-context-subheader");
    const status = document.getElementById("nh-merchant-operational-status");
    if (!header || !context || !status) return;

    const stack = document.createElement("div");
    stack.className = "merchant-storefront-header-stack";
    stack.setAttribute("aria-label", "Merchant storefront header");

    if (logo) {
      const logoRow = document.createElement("div");
      logoRow.className = "merchant-header-logo-row";
      logoRow.appendChild(logo);
      stack.appendChild(logoRow);
    }
    stack.appendChild(context);
    stack.appendChild(status);

    header.insertAdjacentElement("afterend", stack);
    document.body.classList.add("nh-merchant-header-stack-ready");
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
        if (self.operationalStatus && !self.operationalStatus.open) {
          M.toast({ html: "This merchant is not currently accepting orders." });
          return;
        }
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
