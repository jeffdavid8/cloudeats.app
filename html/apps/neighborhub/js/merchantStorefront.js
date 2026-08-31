/**
 * Merchant Storefront Handler
 */
class MerchantStorefront {
  constructor(options = {}) {
    this.options = Object.assign({
      modalId: '#nh-custom-builder-modal',
      cartSidenavId: '#nh-shopping-cart-sidenav',
      toastIcon: 'fa-check-circle',
      toastVerb: 'basket',
      checkoutReturnUrl: '',
    }, options);

    this.isManualScrolling = false;
    this.visibleCategories = new Map();
    this.categoryObserver = null;
    nh.cart.checkoutReturnUrl = this.options.checkoutReturnUrl;

    this.init();
  }

  init() {
    this.initMaterializeComponents();
    this.bindNavigationEvents();
    this.initIntersectionObserver();
    this.bindQuantityControls();
    this.bindCartActions();
  }

  initMaterializeComponents() {

    $(this.options.modalId).modal();
    $('.materialboxed').materialbox();

    const dropdownElems = document.querySelectorAll('.dropdown-trigger');
    if (dropdownElems.length) {
      M.Dropdown.init(dropdownElems, {
        alignment: 'right',
        constrainWidth: false,
        coverTrigger: false
      });
    }
  }

  bindNavigationEvents() {
    const self = this;

    // Top-right dropdown category navigation click
    $('#top-right-menu a.category-menu-item').on('click', function(e) {
      const cleanAnchor = this.dataset.categoryAnchor
        ? this.dataset.categoryAnchor.toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-')
        : '';

      const $target = $('#' + cleanAnchor);
      if ($target.length) {
        $('html, body').animate({
          scrollTop: $target.offset().top - 100
        }, 800);
      }
    });

    // General Smooth Scroll & Manual Active Class Assignment
    $('a.category-menu-item').on('click', function(e) {
      e.preventDefault();
      const anchor = $(this).attr('data-category-anchor');

      if (anchor === 'top') {
        $('html, body').animate({ scrollTop: 0 }, 600);
        return;
      }

      const $target = $('#' + anchor);
      if ($target.length) {
        self.isManualScrolling = true;

        $('a.category-menu-item').removeClass('active');
        $(`a.category-menu-item[data-category-anchor="${anchor}"]`).addClass('active');

        $('html, body').animate({
          scrollTop: $target.offset().top - 120
        }, 600, function() {
          setTimeout(() => {
            self.isManualScrolling = false;
          }, 50);
        });
      }
    });
  }

  initIntersectionObserver() {
    const observerOptions = {
      rootMargin: '-120px 0px -50% 0px',
      threshold: [0, 0.2, 0.5]
    };

    this.categoryObserver = new IntersectionObserver((entries) => {
      if (this.isManualScrolling) return;

      entries.forEach(entry => {
        const sectionId = entry.target.querySelector('[id]')?.id || entry.target.id;

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
          $('a.category-menu-item').removeClass('active');
          $(`a.category-menu-item[data-category-anchor="${topCategory}"]`).addClass('active');
        }
      }
    }, observerOptions);

    const self = this;
    $('.category-section, [id]').each(function() {
      const anchorId = $(this).attr('id');
      if (anchorId && $(`a.category-menu-item[data-category-anchor="${anchorId}"]`).length > 0) {
        self.categoryObserver.observe(this);
      }
    });
  }

  bindQuantityControls() {
    $('.nh-card-qty-plus').off('click').on('click', function() {
      const input = $(this).siblings('.nh-card-qty-input');
      input.val(parseInt(input.val() || 0) + 1);
    });

    $('.nh-card-qty-minus').off('click').on('click', function() {
      const input = $(this).siblings('.nh-card-qty-input');
      const currentVal = parseInt(input.val() || 1);
      if (currentVal > 1) {
        input.val(currentVal - 1);
      }
    });
  }

  bindCartActions() {
    const self = this;

    // Standard Add-To-Cart Action
    $('.nh-add-standard-btn').off('click').on('click', function() {
      const btn = $(this);
      const cardQty = parseInt(btn.closest('.card-action').find('.nh-card-qty-input').val() || 1);

      const productInfo = {
        id: btn.data('id'),
        name: btn.data('name'),
        price: btn.data('price'),
        merchantImage: btn.data('merchant-image'),
        merchantId: btn.data('merchant-id'),
        merchantName: btn.data('merchant-name'),
        merchantAddress: btn.data('merchant-address'),
        merchantLat: btn.data('merchant-lat'),
        merchantLon: btn.data('merchant-lon')
      };

      const targetMerchantId = btn.data('merchant-id') || (window.nh && nh.cart ? nh.cart.activeMerchantIdReference : null);

      if (window.NHCart) {
        NHCart.addItem(targetMerchantId, productInfo, null, cardQty);
      }

      $(self.options.cartSidenavId).sidenav('open');
      btn.closest('.card-action').find('.nh-card-qty-input').val(1);

      M.toast({
        html: `<i class="fas ${self.options.toastIcon}"></i> Added (${cardQty}) ${productInfo.name} to ${self.options.toastVerb}!`
      });
    });

    // Customize Modal Open Trigger
    $('.nh-customize-trigger').off('click').on('click', function() {
      const btn = $(this);
      const productId = btn.data('id');
      const productName = btn.data('name');
      const price = btn.data('price');

      const initialCardQty = parseInt(btn.closest('.card-action').find('.nh-card-qty-input').val() || 1);

      if (nh && nh.cart) {
        nh.cart.activeCustomProductMetadata = {
          id: productId,
          name: productName,
          price: price,
          merchantImage: btn.data('merchant-image'),
          merchantId: btn.data('merchant-id'),
          merchantName: btn.data('merchant-name'),
          merchantAddress: btn.data('merchant-address'),
          merchantLat: btn.data('merchant-lat'),
          merchantLon: btn.data('merchant-lon')
        };
      }

      $('#builder-modal-title').text('Customize Your ' + productName);
      $(`${self.options.modalId} .nh-card-qty-input`).val(initialCardQty);

      const modalElement = document.querySelector(self.options.modalId);
      if (modalElement) {
        const modalInstance = M.Modal.getInstance(modalElement);
        if (modalInstance) modalInstance.open();
      }

      const $notesInput = $(`${self.options.modalId} .nh-card-customer-notes-input`);
      if ($notesInput.length) {
        $notesInput.val('');
        M.textareaAutoResize($notesInput);
        if ($notesInput[0] && M.CharacterCounter) {
          M.CharacterCounter.init($notesInput[0]);
        }
      }

      $('#nh-modal-submit-add-to-cart')
        .removeData('editing-key')
        .html('Add To Basket <i class="fas fa-plus right"></i>');

      $('#builder-widget-mount-viewport').empty();

      if (typeof CustomOrderBuilder !== 'undefined') {
        nh.cart.activeBuilderInstance = new CustomOrderBuilder('builder-widget-mount-viewport', productId, price, null);
      }

      btn.closest('.card-action').find('.nh-card-qty-input').val(1);
    });

    // Custom Item Modal Submit
    $('#nh-modal-submit-add-to-cart').off('click').on('click', function() {
      
      if (nh && nh.cart && nh.cart.activeBuilderInstance) {
        const receiptBlob = nh.cart.activeBuilderInstance.compileSelections();
        if (!receiptBlob) return;

        const finalModalQty = parseInt($(`${self.options.modalId} .nh-card-qty-input`).val() || 1);
        const notes = $(`${self.options.modalId} .nh-card-customer-notes-input`).val();
        nh.cart.activeCustomProductMetadata.customer_notes = notes;

        const editingKey = $(this).data('editing-key');

        if (editingKey && NHCart) {
          NHCart.updateItemCustomization(editingKey, receiptBlob, notes);
          if (NHCart.cart && NHCart.cart.items[editingKey]) {
            NHCart.cart.items[editingKey].quantity = finalModalQty;
          }
          NHCart.save();

          M.toast({
            html: `<i class="fas ${self.options.toastIcon}"></i> Updated ${nh.cart.activeCustomProductMetadata.name} in ${self.options.toastVerb}!`
          });
        } else if (NHCart) {
          NHCart.addItem(
            nh.cart.activeCustomProductMetadata.merchantId,
            nh.cart.activeCustomProductMetadata,
            receiptBlob,
            finalModalQty
          );

          M.toast({
            html: `<i class="fas ${self.options.toastIcon}"></i> Added (${finalModalQty}) custom ${nh.cart.activeCustomProductMetadata.name} to ${self.options.toastVerb}!`
          });
        }

        if ($(self.options.cartSidenavId).hasClass('sidenav')) {
          $(self.options.cartSidenavId).sidenav('open');
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