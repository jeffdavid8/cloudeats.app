<?php

/**
 * Merchant Products Catalog Management View
 * 
 * Allows merchants to view, create, update, and delete products in their catalog.
 * Products are grouped by tags and displayed using Materialize components.
 * All API interactions use mb.ajax() for CSRF protection.
 * @var Object $merchant
 */

// Verify merchant access
$app = App::getInstance('neighborhub');
$merchant = $app->get('merchant');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
  http_response_code(401);
  echo "<div class='card red'><div class='card-content white-text'><span class='card-title'>Authentication Required</span><p>You must be logged in to access this page.</p></div></div>";
  exit;
}

if (!$merchant->id) {
  http_response_code(403);
  echo "<div class='card red'><div class='card-content white-text'><span class='card-title'>Merchant Context Required</span><p>No merchant ID provided. Please select a merchant to manage products.</p></div></div>";
  exit;
}

// Verify user has staff access to this merchant
$db = $app->db;
$staffStmt = $db->prepare(
  "SELECT id, staff_role FROM neighborhub_merchant_users 
   WHERE user_id = ? AND merchant_id = ? AND status = 'active'"
);
$staffStmt->execute([$app->user->id, $merchant->id]);
$staffRecord = $staffStmt->fetch(PDO::FETCH_ASSOC);

if ((!$staffRecord) && (!$app->user->is_admin)) {
  http_response_code(403);
  echo "<div class='card red'><div class='card-content white-text'><span class='card-title'>Access Denied</span><p>You do not have permission to manage products for this merchant.</p></div></div>";
  exit;
}

if (!$merchant) {
  http_response_code(404);
  echo "<div class='card red'><div class='card-content white-text'><span class='card-title'>Merchant Not Found</span><p>The merchant you are trying to access does not exist.</p></div></div>";
  exit;
}
?>
<div>
  <a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchant->id ?>&p=dashboard" class="btn" title="Back to Dashboard">
    <i class="fas fa-arrow-left"></i>
  </a>
</div>
<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
  <!-- Header Section -->
  <div class="row" style="margin-bottom: 30px;">
    <div class="col s12">
      <h4 style="display: inline-block; margin: 0;">
        <i class="fas fa-box" style="margin-right: 10px;"></i>Manage Product Catalog
      </h4>
      <span style="color: #999; font-size: 14px; margin-left: 10px;">
        <?php echo $merchant->business_name; ?>
      </span>
      <button class="btn waves-effect waves-light modal-trigger" href="#modal-product-form" style="float: right;">
        <i class="fas fa-plus left"></i> Add New Product
      </button>
    </div>
  </div>

  <!-- Products Container -->
  <div id="products-container">
    <div class="center" style="padding: 40px;">
      <p><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #2196F3;"></i></p>
      <p>Loading your products...</p>
    </div>
  </div>
</div>

<!-- Product Form Modal -->
<?
render('components/modal_product_form.php', array(
  'merchant' => $merchant,
));
?>
<style>
  #product-form .input-field {
    margin-top: 2rem;
  }

  .product-card-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    background-color: #f5f5f5;
    position: relative;
  }

  .product-card-image-placeholder {
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 60px;
    opacity: 0.3;
  }

  .card-content p {
    margin: 8px 0;
    color: #555;
  }

  .product-price {
    font-size: 20px;
    font-weight: bold;
    color: #2196F3;
  }

  .product-description {
    font-size: 13px;
    color: #888;
    margin: 8px 0;
  }

  .availability-toggle {
    cursor: pointer;
  }

  .collapsible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
  }

  .collapsible-header i {
    margin-right: 10px;
    color: #2196F3;
  }

  .tags-count {
    background-color: #2196F3;
    color: white;
    border-radius: 12px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: bold;
  }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
  }

  .empty-state i {
    font-size: 60px;
    margin-bottom: 20px;
    opacity: 0.3;
  }

  #image-drag-zone.drag-over {
    background-color: #e3f2fd;
    border-color: #1976d2;
    box-shadow: inset 0 0 10px rgba(33, 150, 243, 0.1);
  }

  @media (prefers-color-scheme: dark) {
    .product-card-image {
      background-color: #333;
    }

    .product-description {
      color: #aaa;
    }

    .card-content p {
      color: #bbb;
    }

    #image-drag-zone {
      background-color: #2a2a2a;
      border-color: #64b5f6;
    }

    #image-drag-zone.drag-over {
      background-color: #1a3a52;
    }
  }
</style>

<script>
  /**
   * Handles saving both NEW and EXISTING products and updates 
   * the DOM UI elements (#catalogPool and #menuCanvas) dynamically.
   */
  function saveProduct(e) {
    if (e) e.preventDefault();

    const productId = document.getElementById('form-product-id').value;
    const merchantId = document.getElementById('form-merchant-id').value;
    const isUpdate = Boolean(productId && productId !== '0' && productId !== '');

    // Build FormData manually to cleanly pass inputs & files
    const formData = new FormData();
    formData.append('action', isUpdate ? 'update_product' : 'create_product');
    formData.append('merchant_id', merchantId);
    if (isUpdate) {
      formData.append('product_id', productId);
    }

    // Baseline input fields
    formData.append('name', document.getElementById('form-product-name').value.trim());
    formData.append('description', document.getElementById('form-product-description').value.trim());
    formData.append('tags', document.getElementById('form-product-tags').value.trim());
    formData.append('price', parseFloat(document.getElementById('form-product-price').value || 0).toFixed(2));
    formData.append('type', document.getElementById('form-product-type').value.trim());
    formData.append('meta', document.getElementById('form-product-meta').value.trim());
    formData.append('is_available', document.getElementById('form-product-available').checked ? 1 : 0);

    // Single main photo upload
    const primaryFileInput = document.getElementById('product-primary-input');
    if (primaryFileInput && primaryFileInput.files[0]) {
      formData.append('product_image', primaryFileInput.files[0]);
    }

    // Gallery multi-file upload
    const galleryFileInput = document.getElementById('product-gallery-input');
    if (galleryFileInput && galleryFileInput.files.length > 0) {
      for (let i = 0; i < galleryFileInput.files.length; i++) {
        formData.append('gallery_images[]', galleryFileInput.files[i]);
      }
    }

    M.toast({
      html: isUpdate ? 'Updating product...' : 'Creating product...'
    });

    mb.ajax({
      url: '/?api=neighborhub',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: isUpdate ? '🎉 Product updated successfully!' : '🎉 Product created successfully!',
            classes: 'teal'
          });

          // Close Modal & Reset
          const modalEl = document.getElementById('modal-product-form');
          const modal = M.Modal.getInstance(modalEl);
          if (modal) modal.close();

          // Standardize payload details from API response or fallback form fields
          const productData = response.product || response.data || {
            id: response.product_id || productId,
            name: formData.get('name'),
            price: formData.get('price'),
            description: formData.get('description'),
            is_available: parseInt(formData.get('is_available')),
            sku: ''
          };

          location.reload();

          // Clear staged gallery previews & reset form inputs
          const stagedWrapper = document.getElementById('staged-gallery-previews-wrapper');
          if (stagedWrapper) stagedWrapper.style.display = 'none';

          if (typeof resetProductForm === 'function') {
            resetProductForm();
          }
        } else {
          M.toast({
            html: '❌ Sync Error: ' + (response.error || response.message || 'Validation failed'),
            classes: 'red'
          });
        }
      },
      error: function(xhr, status, error) {
        console.error("Product Save Error:", error);
        M.toast({
          html: '❌ Server error saving product.',
          classes: 'red'
        });
      }
    });
  }
  const merchantId = parseInt(document.getElementById('form-merchant-id').value);

  document.addEventListener('DOMContentLoaded', function() {
    const productsContainer = document.getElementById('products-container');

    // Load products on page load
    loadProducts();

    /**
     * Load all products from API
     */
    function loadProducts() {
      mb.ajax({
        url: '/?api=neighborhub&action=get_merchant_products&merchant_id=' + merchantId,
        type: 'GET',
        timeout: 10000,
        success: function(response) {
          if (response.success && response.products) {
            renderProducts(response.products);
          } else {
            productsContainer.innerHTML = '<div class="card red"><div class="card-content white-text"><p>' + (response.error || 'Failed to load products') + '</p></div></div>';
          }
        },
        error: function(xhr, status, error) {
          console.error('Load products error:', error);
          productsContainer.innerHTML = '<div class="card red"><div class="card-content white-text"><p>Error loading products. Please refresh the page.</p></div></div>';
        }
      });
    }

    /**
     * Render products grouped by tags
     * @param {Array} products
     */
    function renderProducts(products) {
      if (!Array.isArray(products) || products.length === 0) {
        productsContainer.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No products yet. Create your first product to get started!</p></div>';
        return;
      }

      // Group products by tags
      const groupedByTags = {};
      products.forEach(function(product) {
        const tags = product.tags && product.tags.trim() ? product.tags : 'Uncategorized';
        if (!groupedByTags[tags]) {
          groupedByTags[tags] = [];
        }
        groupedByTags[tags].push(product);
      });

      // Build collapsible HTML
      let html = '<ul class="collapsible popout" data-collapsible="accordion">';

      Object.keys(groupedByTags).sort().forEach(function(tags) {
        const tagsProducts = groupedByTags[tags];
        const productCount = tagsProducts.length;

        // Collapsible header
        html += '<li>';
        html += '<div class="collapsible-header">';
        html += '<div style="flex: 1;">';
        html += '<i class="fas fa-folder"></i>';
        html += '<strong>' + escapeHtml(tags) + '</strong>';
        html += '<span class="tags-count">' + productCount + ' item' + (productCount !== 1 ? 's' : '') + '</span>';
        html += '</div>';
        html += '<i class="fas fa-chevron-down"></i>';
        html += '</div>';

        // Collapsible body with product cards
        html += '<div class="collapsible-body">';
        html += '<div class="row">';

        tagsProducts.forEach(function(product) {
          html += '<div class="col s12 m6 l4">';
          html += '<div class="card hoverable">';

          // Card image
          if (product.image_url && product.image_url.trim()) {
            html += '<div class="card-image">';
            html += '<img src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.name) + '" style="width: 100%; height: 100%; object-fit: cover;">';
            html += '<button class="btn-floating halfway-fab waves-effect waves-light red edit-product-btn" data-id="' + product.id + '" title="Edit Product">';
            html += '<i class="fas fa-edit"></i>';
            html += '</button>';
            html += '</div>';
          } else {
            html += '<div class="product-card-image">';
            html += '<div class="product-card-image-placeholder">';
            html += '<i class="fas fa-image"></i>';
            html += '</div>';
            html += '<button class="btn-floating halfway-fab waves-effect waves-light red edit-product-btn" data-id="' + product.id + '" title="Edit Product">';
            html += '<i class="fas fa-edit"></i>';
            html += '</button>';
            html += '</div>';
          }

          // Card content
          html += '<div class="card-content">';
          html += '<span class="card-title">' + escapeHtml(product.name) + '</span>';

          if (product.description && product.description.trim()) {
            html += '<p class="product-description">' + escapeHtml(product.description.substring(0, 80)) + (product.description.length > 80 ? '...' : '') + '</p>';
          }

          html += '<p class="product-price">$' + parseFloat(product.price).toFixed(2) + '</p>';
          html += '<p class="product-type" style="display: none;">' + product.type + '</p>';
          html += '</div>';

          // Card action
          html += '<div class="card-action">';
          /*
          html += '<div class="switch">';
          html += '<label>';
          html += 'Sold Out';
          html += '<input type="checkbox" class="availability-toggle" data-id="' + product.id + '"' + (product.is_available ? ' checked' : '') + '>';
          html += '<span class="lever"></span>';
          html += 'Available';
          html += '</label>';
          html += '</div>';
          */
          html += '<button class="btn-flat red-text delete-product-btn" data-id="' + product.id + '" title="Delete Product">';
          html += '<i class="fas fa-trash-alt"></i> Delete';
          html += '</button>';
          html += '</div>';

          html += '</div>';
          html += '</div>';
        });

        html += '</div>';
        html += '</div>';
        html += '</li>';
      });

      html += '</ul>';
      productsContainer.innerHTML = html;

      // Initialize collapsible after rendering
      const collapsibles = productsContainer.querySelectorAll('.collapsible');
      collapsibles.forEach(function(collapsible) {
        M.Collapsible.init(collapsible, {
          accordion: true
        });
      });

      // Attach event handlers
      attachProductEventHandlers();
    }

    /**
     * Attach event handlers to product cards
     */
    function attachProductEventHandlers() {
      // Edit button handlers
      document.querySelectorAll('.edit-product-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const productId = parseInt(this.getAttribute('data-id'));
          openProductModal(productId);
        });
      });

      // Delete button handlers
      document.querySelectorAll('.delete-product-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const productId = parseInt(this.getAttribute('data-id'));
          const productName = $(this).closest('.card-content').find('.card-title').text();

          if (confirm('Are you sure you want to delete "' + productName + '"?')) {
            deleteProduct(productId);
          }
        });
      });

      // Availability toggle handlers
      document.querySelectorAll('.availability-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
          const productId = parseInt(this.getAttribute('data-id'));
          const isAvailable = this.checked ? 1 : 0;
          updateAvailability(productId, isAvailable);
        });
      });
    }
    /**
     * Opens the product modal for creating or editing (Augmented for Multi-Image)
     */
    function openProductModal(productId) {
      // Clear existing inputs and local staged nodes
      document.getElementById('product-form').reset();
      document.getElementById('form-product-id').value = productId;

      const thumbnailsGrid = document.getElementById('product-gallery-thumbnails-grid');
      const galleryWrapper = document.getElementById('product-gallery-management-wrapper');
      const placeholderText = document.getElementById('dropzone-placeholder-text');

      if (thumbnailsGrid) thumbnailsGrid.innerHTML = '';

      if (!productId) {
        // Mode: Creating a brand new product
        document.getElementById('modal-title').textContent = 'Add New Product';
        document.getElementById('primary-image-input-field').style.display = 'none';
        console.log('Creating new product. Resetting form and hiding gallery matrix.');

        if (galleryWrapper) galleryWrapper.style.display = 'none'; // Hide gallery matrix for unsaved items
        M.updateTextFields();
        return;
      }

      // Mode: Updating an existing product row
      document.getElementById('modal-title').textContent = 'Edit Product Catalog Item';

      document.getElementById('primary-image-input-field').style.display = 'block';

      loading(1);
      // Fetch hydrated parameters from your API
      mb.ajax({
        url: '/?api=neighborhub&action=get_product&product_id=' + productId,
        type: 'GET',
        success: function(response) {
          if (response && response.success && response.product) {
            const prod = response.product;

            loading(0);

            // Populate standard input strings
            document.getElementById('form-product-name').value = prod.name;
            document.getElementById('form-product-description').value = prod.description;
            document.getElementById('form-product-tags').value = prod.tags;
            document.getElementById('form-product-price').value = prod.price;
            document.getElementById('form-product-type').value = prod.type;
            document.getElementById('form-product-meta').value = prod.meta;
            document.getElementById('form-product-available').checked = (parseInt(prod.is_available) === 1);
            document.getElementById('form-existing-image-url').value = prod.image_url;

            // Render Primary Image Preview if available
            const previewBox = document.getElementById('image-preview-box');
            const previewImg = document.getElementById('form-image-preview');
            if (prod.image_url) {
              previewImg.src = prod.image_url;
              previewBox.style.display = 'block';
            } else {
              previewBox.style.display = 'none';
            }

            // --- Hydrate Gallery Matrix Assets ---
            if (galleryWrapper) {
              galleryWrapper.style.display = 'block'; // Make container visible

              if (prod.gallery && prod.gallery.length > 0) {
                if (placeholderText) placeholderText.style.display = 'none';

                prod.gallery.forEach(img => {
                  // Construct active grid item node complete with absolute-positioned delete button overlay
                  const thumbNode = document.createElement('div');
                  thumbNode.className = 'gallery-image-thumb-wrapper';
                  thumbNode.setAttribute('data-image-id', img.id);
                  thumbNode.style = 'position: relative; width: 70px; height: 70px; border-radius: 4px; border: 1px solid #cfd8dc; overflow: visible;';

                  thumbNode.innerHTML = `
                <img src="${img.image_url}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 3px;">
                <button type="button" class="btn-floating btn-small red waves-effect waves-light remove-gallery-img-btn" 
                        onclick="deleteGalleryImageAsset(${prod.id}, ${img.id})"
                        style="position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; line-height: 20px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 5;" 
                        title="Delete Image from GCS Bucket">
                  <i class="fas fa-trash-alt" style="font-size: 10px;"></i>
                </button>
              `;
                  thumbnailsGrid.appendChild(thumbNode);
                });
              } else {
                if (placeholderText) placeholderText.style.display = 'block';
              }
            }

            // Recalculate Materialize label animation frames
            M.updateTextFields();
            M.textareaAutoResize(document.getElementById('form-product-description'));
            $('select').formSelect();
            const instance = M.Modal.getInstance(document.getElementById('modal-product-form'));
            instance.open();

          } else {
            M.toast({
              html: '❌ Failed to load item catalog parameters.'
            });
          }
        }
      });
    }


    /**
     * Delete a product
     * @param {number} productId
     */
    function deleteProduct(productId) {
      const data = {
        product_id: productId,
        merchant_id: merchantId
      };

      mb.ajax({
        url: '/?api=neighborhub&action=delete_product',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        processData: false,
        timeout: 10000,
        success: function(response) {
          if (response.success) {
            M.toast({
              html: '<i class="fas fa-check-circle"></i> Product deleted successfully!'
            });
            loadProducts();
          } else {
            M.toast({
              html: '<i class="fas fa-exclamation-circle"></i> ' + (response.error || 'Failed to delete product')
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
     * Update product availability status
     * @param {number} productId
     * @param {number} isAvailable
     */
    function updateAvailability(productId, isAvailable) {
      const data = {
        product_id: productId,
        merchant_id: merchantId,
        is_available: isAvailable
      };

      mb.ajax({
        url: '/?api=neighborhub&action=update_product_availability',
        type: 'POST',
        data: data,
        timeout: 10000,
        success: function(response) {
          if (response.success) {
            const status = isAvailable ? 'available' : 'unavailable';
            M.toast({
              html: '<i class="fas fa-check-circle"></i> Product marked as ' + status
            });
          } else {
            // Revert the toggle
            const toggle = document.querySelector('.availability-toggle[data-id="' + productId + '"]');
            if (toggle) {
              toggle.checked = !toggle.checked;
            }
            M.toast({
              html: '<i class="fas fa-exclamation-circle"></i> ' + (response.error || 'Failed to update availability')
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Update availability error:', error);
          // Revert the toggle
          const toggle = document.querySelector('.availability-toggle[data-id="' + productId + '"]');
          if (toggle) {
            toggle.checked = !toggle.checked;
          }
          M.toast({
            html: '<i class="fas fa-exclamation-circle"></i> Error updating availability'
          });
        }
      });
    }

    /**
     * Escape HTML special characters
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
      if (!text) return '';
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function(m) {
        return map[m];
      });
    }
  });
</script>