<?
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Product Modal Form
 * 
 * 
 * Context variables available:
 * @var Object $merchant
 * - $app->get('merchant') - active merchant 
 */
$merchant_id = $merchant->id;
?>
<style>
  .product-gallery-dropzone {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 6px;
    border: 2px dashed #b0bec5;
    transition: all 0.2s ease-in-out;
    min-height: 100px;
  }

  .nightMode .product-gallery-dropzone {
    background: #252525;
    border: 2px dashed #078fd3;

  }
</style>
<div id="modal-product-form" class="modal mb-modal-fixed">
  <div class="modal-header">
    <h5>
      <i class="fas fa-edit left" style="margin-right: 10px;"></i><span id="modal-title">Add New Product</span>
    </h5>
    <hr style="margin: 15px 0;">
  </div>
  <div class="modal-content">

    <div class="container">

      <form id="product-form" style="padding: 20px 0;">
        <input type="hidden" id="form-product-id" value="">
        <input type="hidden" id="form-merchant-id" value="<?php echo intval($merchant->id); ?>">
        <input type="hidden" id="form-existing-image-url" name="image_url" value="">


        <div id="primary-image-input-field" class="row" style="margin-bottom: 5px;">
          <div class="col s12 center-align" id="image-preview-box" style="margin-top: 15px; display: none;">
            <div style="position: relative; display: inline-block; border-radius: 8px; padding: 4px; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.12); border: 1px solid #e0e0e0;">
              <img id="form-image-preview" src="" style="max-height: 160px; max-width: 100%; border-radius: 6px; display: block; object-fit: contain;">
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
              <input type="file" name="product_image" id="product-primary-input" accept="image/*">
            </div>
            <div class="file-path-wrapper" style="margin-left: 3rem; padding: 0;">
              <input class="file-path validate" type="text" placeholder="Upload or drop main product photo here">
            </div>
          </div>
        </div>

        <!-- Product Name -->
        <div class="input-field col s12">
          <i class="fas fa-cube prefix"></i>
          <input id="form-product-name" type="text" class="validate" required="">
          <label for="form-product-name">Product Name <span style="color: red;">*</span></label>
          <span class="helper-text" data-error="Product name is required" data-success="✓"></span>
        </div>

        <!-- Product Description -->
        <div class="input-field col s12">
          <i class="fas fa-align-left prefix"></i>
          <textarea id="form-product-description" class="materialize-textarea" style="height: 100px;"></textarea>
          <label for="form-product-description">Description</label>
        </div>

        <!-- Product Tags -->
        <div class="input-field col s12 m6">
          <i class="fas fa-tag prefix"></i>
          <input id="form-product-tags" type="text" class="validate">
          <label for="form-product-tags">Tags</label>
          <span class="helper-text">e.g., Pizza, Burger, Desert, Morning, Weekend, Snack</span>
        </div>

        <!-- Product Price -->
        <div class="input-field col s12 m6">
          <i class="fas fa-dollar-sign prefix"></i>
          <input id="form-product-price" type="number" class="validate" step="0.01" min="0" required="">
          <label for="form-product-price">Price <span style="color: red;">*</span></label>
          <span class="helper-text" data-error="Valid price is required" data-success="✓"></span>
        </div>

        <!-- Product Type -->
        <div class="input-field col s12 m6">
          <i class="fas fa-tag prefix"></i>
          <input id="form-product-type" type="text" placeholder="default" value="">
          <label for="form-product-type">Type</label>
          <span class="helper-text" data-success="✓"></span>
        </div>

        <div class="row" style="margin-bottom: 10px;">
          <div class="file-field input-field col s12" style="margin-top: 5px; padding-left: 0;">
            <i class="fas fa-images prefix" style="font-size: 2rem; color: #6a1b9a;"></i>
            <div class="btn purple darken-1" style="margin-left: 3rem; float: none; border-radius: 4px;">
              <span>Add Gallery Docs</span>
              <input type="file" name="gallery_images[]" id="product-gallery-input" multiple accept="image/*,video/mp4">
            </div>
            <div class="file-path-wrapper" style="margin-left: 3rem; padding: 0; display:none; ">
              <input class="file-path validate" type="text" placeholder="Select multiple extra photos for your carousel">
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

        <div class="row" id="product-gallery-management-wrapper" style="margin-top: 5px; margin-bottom: 15px;">
          <div class="col s12" style="margin-left: 3rem; width: calc(100% - 3rem);">
            <span style="font-weight: bold; color: #37474f; text-transform: uppercase; font-size: 0.75rem; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">
              Active Image Gallery Matrix
            </span>

            <div id="product-gallery-dropzone" style="">

              <div id="dropzone-placeholder-text" style="text-align: center; color: #78909c; margin-bottom: 8px; font-size: 0.85rem; font-weight: 500;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 1.4rem; display: block; margin-bottom: 4px; color: #90a4ae;"></i>
                Drag & Drop multiple gallery images directly here
              </div>

              <div id="product-gallery-thumbnails-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 8px;">
              </div>
            </div>

          </div>
        </div>

        <div class="divider" style="margin-top: 2rem;"></div>

        <!-- Product Available -->
        <div class="input-field col s12 m6">
          <i class="fas fa-eye prefix"></i>
          <div class="switch" style="margin-left: 3rem;">
            <label>
              Sold Out
              <input id="form-product-available" type="checkbox" class="availability-toggle">
              <span class="lever"></span>
              Available
            </label>
          </div>
        </div>

        <div class="divider" style="margin: 2rem 0;"></div>

        <div class="row">
          <div class="col s12">
            <button type="button" id="toggle-advanced" class="btn-flat waves-effect waves-teal">
              Show Advanced Options
            </button>
          </div>
        </div>

        <!-- Advanced Form Section (Hidden by Default) -->
        <div id="advanced-section" style="display: none;">
          <div class="row">
            <div class="col s12">
              <label for="form-product-meta" style="font-size: 1rem; font-weight: 600; color: #424242;">
                Product Modification Options & Steps (JSON Schema)
              </label>
              <div style="margin: 8px 0 15px 0;">
                <span class="grey-text text-darken-1" style="font-size:13px; margin-right:10px;">Quick Templates:</span>
                <button type="button" class="btn-small btn-flat nh-template-btn" data-type="pizza" style="border: 1px solid #26a69a; color: #26a69a; margin-right:5px; border-radius:4px;">+ Pizza Setup</button>
                <button type="button" class="btn-small btn-flat nh-template-btn" data-type="taco" style="border: 1px solid #ff9800; color: #ff9800; border-radius:4px;">+ Taco Setup</button>
              </div>

              <div id="json-dropzone-wrapper" style="border: 2px dashed #b0bec5; border-radius: 8px; padding: 10px; position: relative; background: #fafafa; transition: background 0.2s ease;">

                <div id="dropzone-overlay-text" class="grey-text center-align" style="font-size: 12px; margin-bottom: 5px; pointer-events: none;">
                  <i class="fas fa-file-code"></i> Paste JSON below or drag & drop a <code>.json</code> file here to auto-load.
                </div>

                <textarea id="form-product-meta" class="json-document"
                  name="meta_json"
                  class="materialize-textarea "
                  style="font-family: monospace; font-size: 13px; line-height: 1.5; background: #263238; color: #80cbc4; padding: 15px; border-radius: 6px; min-height: 180px; max-height: 50vh; border: none; box-sizing: border-box; resize: vertical;"
                  placeholder='{"steps": []}'><?php echo isset($product->meta) ? htmlspecialchars(is_string($product->meta) ? $product->meta : json_encode($product->meta, JSON_PRETTY_PRINT)) : ''; ?></textarea>
              </div>

              <div id="json-syntax-feedback" class="helper-text font-weight-medium" style="margin-top: 6px; font-size: 13px;"></div>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>

  <!-- Modal Actions -->
  <div class="modal-footer" style="padding: 15px;">
    <button class="btn waves-effect waves-light grey modal-close" style="margin-right: 10px;">
      <i class="fas fa-times left"></i>Cancel
    </button>
    <button class="btn waves-effect waves-light blue" id="btn-save-product">
      <i class="fas fa-save left"></i>Save Product
    </button>
  </div>
</div>


<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Configuration
    const merchantId = parseInt(document.getElementById('form-merchant-id').value);
    const productFormModal = M.Modal.getInstance(document.getElementById('modal-product-form'));
    const productForm = document.getElementById('product-form');
    const btnSaveProduct = document.getElementById('btn-save-product');
    const btnCancelProduct = document.querySelector('.modal-footer .modal-close');
    //const productFileInput = document.getElementById('product-file-input');
    //const imageDragZone = document.getElementById('image-drag-zone');
    const imagePreviewBox = document.getElementById('image-preview-box');
    const formImagePreview = document.getElementById('form-image-preview');
    const formExistingImageUrl = document.getElementById('form-existing-image-url');
    const removePreviewBtn = document.getElementById('remove-preview-btn');

    // Track selected product file in memory
    let selectedProductFile = null;

    // Initialize Materialize components
    M.Modal.init(document.getElementById('modal-product-form'), {
      dismissible: true,
      onOpenStart: function(modalElement, triggerElement) {
        console.log("Modal is opening!");
        //const productId = document.getElementById('form-product-id').value;
        //if (!productId) {
        //resetProductForm();
        //}

        // 'modalElement' is the raw DOM element of your modal
        // 'triggerElement' is the button/link that opened it (if clicked)

        // Example: If you need to fix or clear those resetting fields on open
        // $(modalElement).find('input').val(''); 
      },
      // Alternative callback that fires AFTER the open animation finishes
      onOpenEnd: function(modalElement, triggerElement) {
        console.log("Modal is now fully open and visible.");
      }
    });


    const primaryInput = document.getElementById('product-primary-input');
    const galleryInput = document.getElementById('product-gallery-input');

    const stagedGalleryWrapper = document.getElementById('staged-gallery-previews-wrapper');
    const stagedGalleryGrid = document.getElementById('staged-gallery-grid');
    const stagedGalleryCount = document.getElementById('staged-gallery-count');

    // --- 1. Primary Single Image Local Target Preview ---
    if (primaryInput) {
      primaryInput.addEventListener('change', function() {
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
        if (confirm('Are you sure you want to delete this product image?  This action will permanently remove it from cloud storage.')) {
          if (primaryInput) primaryInput.value = '';
          const pathInput = document.querySelector('.file-field input[type="text"]');
          const productId = document.getElementById('form-product-id').value;
          if (pathInput) pathInput.value = '';
          formImagePreview.src = '';
          imagePreviewBox.style.display = 'none';
          deleteProductImage(productId);

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
    const dropzone = document.getElementById('product-gallery-dropzone');

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
          const galleryInput = document.getElementById('product-gallery-input');

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
            // Guard checking: Make sure it's an image file object
            if ((file.type.match('image.*')) || file.type.match('video.mp4')) {
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

    // Cancel button handler
    btnCancelProduct.addEventListener('click', function() {
      const productFormModal = M.Modal.getInstance(document.getElementById('modal-product-form'));
      productFormModal.close();
      setTimeout(function() {
        resetProductForm();
      }, 500);
    });

    // Form submission
    btnSaveProduct.addEventListener('click', saveProduct);

    /**
     * Handle file selection from drag-drop or file input
     * @param {File} file
     */
    function handleFileSelection(file) {
      // Validate file is an image
      if (!file.type.startsWith('image/')) {
        M.toast({
          html: '<i class="fas fa-exclamation-circle"></i> Please select a valid image file'
        });
        //productFileInput.value = '';
        return;
      }

      // Validate file size (max 5MB)
      const maxSizeBytes = 5 * 1024 * 1024;
      if (file.size > maxSizeBytes) {
        M.toast({
          html: '<i class="fas fa-exclamation-circle"></i> Image must be smaller than 5MB'
        });
        //productFileInput.value = '';
        return;
      }

      // Store file in memory
      selectedProductFile = file;

      // Create preview using FileReader
      const reader = new FileReader();
      reader.onload = function(event) {
        formImagePreview.src = event.target.result;
        imagePreviewBox.style.display = 'block';
        imageDragZone.style.display = 'none';
      };
      reader.readAsDataURL(file);
    }


    window.deleteGalleryImageAsset = function(productId, imageId) {
      if (!confirm("Are you sure you want to delete this image? This action will permanently remove it from cloud storage.")) {
        return;
      }

      const merchantId = document.getElementById('form-merchant-id').value;

      mb.ajax({
        url: '/?api=neighborhub&action=delete_product_gallery_image',
        type: 'POST',
        data: JSON.stringify({
          product_id: productId,
          merchant_id: merchantId,
          image_id: imageId
        }),
        success: function(response) {
          if (response && response.success) {
            M.toast({
              html: '🗑️ Image removed successfully from storage matrix!'
            });

            // Find and remove the specific layout node locally from the DOM tree
            const targetNode = document.querySelector(`.gallery-image-thumb-wrapper[data-image-id="${imageId}"]`);
            if (targetNode) {
              targetNode.remove();
            }

            // If the grid is now completely empty, bring back the placeholder text guidelines
            const grid = document.getElementById('product-gallery-thumbnails-grid');
            const placeholderText = document.getElementById('dropzone-placeholder-text');
            if (grid && grid.children.length === 0 && placeholderText) {
              placeholderText.style.display = 'block';
            }
          } else {
            M.toast({
              html: '❌ Deletion Error: ' + (response.error || 'Access denied')
            });
          }
        },
        error: function() {
          M.toast({
            html: '❌ Server encountered errors performing file destruction.'
          });
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

    const textarea = $('#form-product-meta');
    const wrapper = $('#json-dropzone-wrapper');
    const feedback = $('#json-syntax-feedback');
    const form = textarea.closest('form');

    // Hardcoded Dictionary definitions right in the view for immediate template insertion
    const templates = {
      pizza: {
        builder_template: "pizza",
        steps: [{
            id: "size",
            title: "Choose your Size",
            type: "radio",
            required: true,
            options: [{
              name: 'Personal 10"',
              price: 0
            }, {
              name: 'Medium 12"',
              price: 2.5
            }, {
              name: 'Large 14"',
              price: 5
            }]
          },
          {
            id: "crust",
            title: "Choose your Crust",
            type: "radio",
            required: true,
            options: [{
              name: 'Classic Hand-Tossed',
              price: 0
            }, {
              name: 'Gluten-Free',
              price: 3
            }]
          },
          {
            id: "toppings",
            title: "Select Toppings",
            type: "checkbox",
            options: [{
              name: 'Pepperoni',
              price: 1.5
            }, {
              name: 'Mushrooms',
              price: 1.0
            }, {
              name: 'Extra Cheese',
              price: 2.0
            }]
          }
        ]
      },
      taco: {
        builder_template: "taco",
        steps: [{
            id: "shell",
            title: "Tortilla Type",
            type: "radio",
            required: true,
            options: [{
              name: 'Soft Corn',
              price: 0
            }, {
              name: 'Crispy Corn Shell',
              price: 0.25
            }]
          },
          {
            id: "protein",
            title: "Choose Filling",
            type: "radio",
            required: true,
            options: [{
              name: 'Pollo (Chicken)',
              price: 0
            }, {
              name: 'Carne Asada (Steak)',
              price: 1.5
            }]
          }
        ]
      }
    };

    // 1. Live Validation Engine Loop Routine
    function validateJsonInput() {
      const val = textarea.val().trim();
      if (!val) {
        feedback.text('Empty payload (Default view will render)').css('color', '#757575');
        textarea.css('border-left', 'none');
        return true;
      }

      try {
        JSON.parse(val);
        feedback.html('<i class="fas fa-check-circle"></i> JSON Syntax Valid! Perfect.').css('color', '#2e7d32');
        textarea.css('border-left', '5px solid #4caf50');
        return true;
      } catch (e) {
        feedback.html('<i class="fas fa-exclamation-triangle"></i> Invalid Syntax: ' + e.message).css('color', '#c62828');
        textarea.css('border-left', '5px solid #f44336');
        return false;
      }
    }

    // Bind real-time tracking behavior listeners
    textarea.on('input keyup blur', function() {
      validateJsonInput();
    });
    validateJsonInput(); // Run initial sanity check on load

    // 2. Handle Template quick-loads
    $('.nh-template-btn').on('click', function(e) {
      e.preventDefault();
      const type = $(this).data('type');
      if (templates[type]) {
        let builderJson = {
          'form_builder': {}
        }
        Object.assign(builderJson.form_builder, templates[type]);
        textarea.val(JSON.stringify(builderJson, null, 4));
        M.textareaAutoResize(textarea);
        validateJsonInput();
        M.toast({
          html: type.toUpperCase() + ' preset schema loaded!'
        });
      }
    });

    // 3. Drag and Drop File API Interceptor
    wrapper.on('dragover dragenter', function(e) {
      e.preventDefault();
      e.stopPropagation();
      wrapper.css('background', '#cfd8dc');
    });

    wrapper.on('dragleave drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      wrapper.css('background', '#fafafa');
    });

    wrapper.on('drop', function(e) {
      const files = e.originalEvent.dataTransfer.files;
      if (files.length > 0) {
        const file = files[0];
        if (file.type === "application/json" || file.name.endsWith('.json')) {
          const reader = new FileReader();
          reader.onload = function(evt) {
            try {
              const parsed = JSON.parse(evt.target.result);
              textarea.val(JSON.stringify(parsed, null, 4));
              M.textareaAutoResize(textarea);
              validateJsonInput();
              M.toast({
                html: 'File loaded successfully!'
              });
            } catch (err) {
              M.toast({
                html: 'Error parsing file JSON text.'
              });
            }
          };
          reader.readAsText(file);
        } else {
          M.toast({
            html: 'Please drop a valid .json text file.'
          });
        }
      }
    });

    // 4. Form Submission Block Guard Interceptor
    form.on('submit', function(e) {
      if (!validateJsonInput()) {
        e.preventDefault();
        M.toast({
          html: '❌ Cannot save. Fix your JSON formatting error first.'
        });
        textarea.focus();
      }
    });

    /**
     * Reset product form
     */
    function resetProductForm() {
      document.getElementById('form-product-id').value = '';
      document.getElementById('form-existing-image-url').value = '';
      //document.getElementById('primary-image-input-field').style.display = 'none';
      //productFileInput.value = '';
      //selectedProductFile = null;
      imagePreviewBox.style.display = 'none';
      //imageDragZone.style.display = 'block';
      //imageDragZone.classList.remove('drag-over');
      formImagePreview.src = '';
      productForm.reset();
      document.getElementById('modal-title').textContent = 'Add New Product';
      M.updateTextFields();
    }

    /**
     * Validate product form
     * @returns {boolean}
     */
    function validateProductForm() {
      const name = document.getElementById('form-product-name').value.trim();
      const price = document.getElementById('form-product-price').value.trim();

      if (!name || !price || isNaN(parseFloat(price)) || parseFloat(price) < 0) {
        return false;
      }

      return true;
    }

    /**
     * Create a new product
     */
    function createProduct() {
      const productFormModal = M.Modal.getInstance(document.getElementById('modal-product-form'));
      const formData = new FormData();

      // Append standard form fields
      formData.append('merchant_id', merchantId);
      formData.append('name', document.getElementById('form-product-name').value.trim());
      formData.append('description', document.getElementById('form-product-description').value.trim());
      formData.append('tags', document.getElementById('form-product-tags').value.trim());
      formData.append('price', parseFloat(document.getElementById('form-product-price').value));
      formData.append('type', parseFloat(document.getElementById('form-product-type').value));
      formData.append('meta', parseFloat(document.getElementById('form-product-meta').value));

      // Handle image: append file if selected, otherwise use empty string
      if (selectedProductFile) {
        formData.append('product_image', selectedProductFile);
      }

      mb.ajax({
        url: '/?api=neighborhub&action=create_product',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        timeout: 30000,
        success: function(response) {
          if (response.success) {
            M.toast({
              html: '<i class="fas fa-check-circle"></i> Product created successfully!'
            });
            productFormModal.close();
            resetProductForm();
            loadProducts();
          } else {
            M.toast({
              html: '<i class="fas fa-exclamation-circle"></i> ' + (response.error || 'Failed to create product')
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Create product error:', error);
          M.toast({
            html: '<i class="fas fa-exclamation-circle"></i> Error creating product'
          });
        }
      });
    }

    /**
     * Update an existing product
     */
    function updateProduct() {
      const productFormModal = M.Modal.getInstance(document.getElementById('modal-product-form'));
      const formData = new FormData();

      // Append standard form fields
      const productId = parseInt(document.getElementById('form-product-id').value);
      formData.append('product_id', productId);
      formData.append('merchant_id', merchantId);
      formData.append('name', document.getElementById('form-product-name').value.trim());
      formData.append('description', document.getElementById('form-product-description').value.trim());
      formData.append('tags', document.getElementById('form-product-tags').value.trim());
      formData.append('price', parseFloat(document.getElementById('form-product-price').value));
      formData.append('type', parseFloat(document.getElementById('form-product-type').value));
      formData.append('meta', parseFloat(document.getElementById('form-product-meta').value));

      // Handle image: append file if selected, otherwise append existing URL
      if (selectedProductFile) {
        formData.append('product_image', selectedProductFile);
      } else {
        const existingUrl = document.getElementById('form-existing-image-url').value.trim();
        if (existingUrl) {
          formData.append('image_url', existingUrl);
        }
      }

      mb.ajax({
        url: '/?api=neighborhub&action=update_product',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        timeout: 30000,
        success: function(response) {
          if (response.success) {
            M.toast({
              html: '<i class="fas fa-check-circle"></i> Product updated successfully!'
            });
            productFormModal.close();
            resetProductForm();
            loadProducts();
          } else {
            M.toast({
              html: '<i class="fas fa-exclamation-circle"></i> ' + (response.error || 'Failed to update product')
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Update product error:', error);
          M.toast({
            html: '<i class="fas fa-exclamation-circle"></i> Error updating product'
          });
        }
      });
    }

    /**
     * Delete a product image
     * @param {number} productId
     */
    function deleteProductImage(productId) {
      const data = {
        product_id: productId,
        merchant_id: merchantId
      };

      mb.ajax({
        url: '/?api=neighborhub&action=delete_product_image',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        processData: false,
        timeout: 10000,
        success: function(response) {
          if (response.success) {
            M.toast({
              html: '<i class="fas fa-check-circle"></i> Product image deleted successfully!'
            });
            loadProducts();
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
     * Load a product for editing
     * @param {number} productId
     */
    function loadProductForEditing(productId) {
      // Get product data from the page
      const productFormModal = M.Modal.getInstance(document.getElementById('modal-product-form'));
      const productCard = document.querySelector('[data-id="' + productId + '"]').closest('.card');
      if (!productCard) {
        M.toast({
          html: '<i class="fas fa-exclamation-circle"></i> Product not found'
        });
        return;
      }

      const cardTitle = productCard.querySelector('.card-title').textContent || '';
      const productDescription = productCard.querySelector('.product-description')?.textContent || '';
      const productPrice = productCard.querySelector('.product-price').textContent.replace('$', '') || '0';
      const productType = productCard.querySelector('.product-type').textContent.replace('$', '') || '0';
      const menuHeader = productCard.closest('li')?.querySelector('.collapsible-header strong');
      const menu = menuHeader ? menuHeader.textContent : '';
      const tagsHeader = productCard.closest('li')?.querySelector('.collapsible-header strong');
      const tags = tagsHeader ? tagsHeader.textContent : '';
      const imageUrl = productCard.querySelector('.card-image img')?.src || '';

      // Populate form
      document.getElementById('form-product-id').value = productId;
      document.getElementById('form-product-name').value = cardTitle;
      document.getElementById('form-product-description').value = productDescription;
      document.getElementById('form-product-tags').value = tags;
      document.getElementById('form-product-price').value = productPrice;
      document.getElementById('form-product-type').value = productType;
      document.getElementById('form-existing-image-url').value = imageUrl;

      // Show preview if image exists
      if (imageUrl) {
        formImagePreview.src = imageUrl;
        imagePreviewBox.style.display = 'block';
        imageDragZone.style.display = 'none';
      } else {
        imagePreviewBox.style.display = 'none';
        imageDragZone.style.display = 'block';
      }

      // Reset file input and selected file
      //productFileInput.value = '';
      //selectedProductFile = null;

      // Update modal title
      document.getElementById('modal-title').textContent = 'Edit Product';

      // Reinitialize Materialize labels
      M.updateTextFields();

      // Open modal
      productFormModal.open();
    }

  });
</script>