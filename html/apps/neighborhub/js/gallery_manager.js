/**
 * Global Context Pointer tracking the active operational product entity
 */
let activeGalleryProductId = null;

/**
 * Initializes and populates the view interface for a target product's media collection
 * @param {number} productId 
 */
function loadProductGallery(productId) {
    if (!productId) return;
    activeGalleryProductId = productId;
    
    const container = $('#nh_gallery_grid_container');
    
    // Trigger localized visual loader loop
    container.html(`
        <div class="col s12 center-align" style="padding: 30px 0;">
            <div class="preloader-wrapper small active">
                <div class="spinner-layer spinner-teal-only">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
        </div>
    `);

    // Fetch matching data from your product image mapping endpoints
    mb.ajax({
        url: '?api=neighborhub&action=get_product_images',
        type: 'GET',
        data: { product_id: productId },
        success: function(response) {
            container.empty();
            
            if (response && response.success && response.images && response.images.length > 0) {
                response.images.forEach(function(img) {
                    // Check if it's explicitly assigned as the primary layout slot
                    const isPrimary = parseInt(img.is_primary) === 1;
                    
                    const slotHtml = `
                        <div class="col s6 m4 l3" id="gallery_item_${img.id}">
                            <div class="nh-gallery-card-slot">
                                ${isPrimary ? '<span class="nh-primary-badge">Primary</span>' : ''}
                                <img src="${escapeHtml(img.image_url)}" alt="Gallery Asset">
                                <div class="nh-gallery-action-overlay">
                                    <button type="button" onclick="setPrimaryGalleryImage(${img.id})" 
                                            class="btn-flat white-text waves-effect" 
                                            title="${isPrimary ? 'Currently Primary' : 'Make Primary Thumbnail'}">
                                        <i class="material-icons ${isPrimary ? 'amber-text' : 'grey-text text-lighten-2'}">
                                            ${isPrimary ? 'star' : 'star_border'}
                                        </i>
                                    </button>
                                    <button type="button" onclick="deleteGalleryImage(${img.id})" 
                                            class="btn-flat red-text text-lighten-2 waves-effect" 
                                            title="Remove Image">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.append(slotHtml);
                });
            } else {
                // Revert to fallback clean empty UI frame
                container.html(`
                    <div id="nh_gallery_empty_state" class="col s12 center-align grey-text text-darken-1" style="padding: 20px 0;">
                        <i class="material-icons" style="font-size: 36px; opacity: 0.5;">add_photo_alternate</i>
                        <p style="margin: 5px 0 0 0; font-size: 13px;">No secondary showcase assets linked to this product catalog listing.</p>
                    </div>
                `);
            }
        },
        error: function() {
            container.html('<div class="col s12 red-text center-align">Failed to communicate with media registry pipeline.</div>');
        }
    });
}

/**
 * Handles raw binary file selection, bundling it up into FormData for the upload endpoint
 */
function handleGalleryFileSelection(inputElement) {
    if (!inputElement.files || inputElement.files.length === 0 || !activeGalleryProductId) return;

    const file = inputElement.files[0];
    const formData = new FormData();
    formData.append('image_asset', file);
    formData.append('product_id', activeGalleryProductId);

    M.toast({ html: '<i class="material-icons left">cloud_upload</i> Buffering file chunks to storage server...', classes: 'blue-grey' });

    // Multi-part asynchronous endpoint submission pipeline
    $.ajax({
        url: '?api=neighborhub&action=upload_gallery_image',
        type: 'POST',
        data: formData,
        processData: false, // Tell jQuery not to process the data
        contentType: false, // Tell jQuery not to set contentType
        headers: {
            // Include your default framework global CSRF header verification if applicable
            'X-CSRF-Token': (typeof mb !== 'undefined' && mb.csrf_token) ? mb.csrf_token : ''
        },
        success: function(response) {
            // Clear input element buffer
            inputElement.value = '';
            
            if (response && response.success) {
                M.toast({ html: '<i class="material-icons left">cloud_done</i> Image committed successfully!', classes: 'green' });
                loadProductGallery(activeGalleryProductId); // Refresh view grid
            } else {
                M.toast({ html: '<i class="material-icons left">error</i> Upload denied: ' + (response.error || 'Unknown network error'), classes: 'red' });
            }
        },
        error: function() {
            inputElement.value = '';
            M.toast({ html: '<i class="material-icons left">warning</i> File pipe communication fault', classes: 'red' });
        }
    });
}

/**
 * Updates an asset entry's structural flags to toggle primary thumbnail routing states (is_primary = 1)
 */
function setPrimaryGalleryImage(imageId) {
    if (!imageId || !activeGalleryProductId) return;

    mb.ajax({
        url: '?api=neighborhub&action=set_primary_image',
        type: 'POST',
        data: {
            id: imageId,
            product_id: activeGalleryProductId
        },
        success: function(response) {
            if (response && response.success) {
                M.toast({ html: '<i class="material-icons left">stars</i> Primary catalog graphic updated', classes: 'green' });
                loadProductGallery(activeGalleryProductId); // Reload to shift UI layouts cleanly
            } else {
                M.toast({ html: '<i class="material-icons left">error</i> Structural index mutation denied', classes: 'red' });
            }
        }
    });
}

/**
 * Executes a structural physical deletion of an entry from the collection ledger rows
 */
function deleteGalleryImage(imageId) {
    if (!imageId || !confirm("Are you sure you want to completely discard this promotional image element?")) return;

    mb.ajax({
        url: '?api=neighborhub&action=delete_gallery_image',
        type: 'POST',
        data: { id: imageId },
        success: function(response) {
            if (response && response.success) {
                M.toast({ html: '<i class="material-icons left">delete_sweep</i> Media element cleared', classes: 'green' });
                $(`#gallery_item_${imageId}`).fadeOut(400, function() {
                    $(this).remove();
                    // If no slots left, refresh to show the clean placeholder template
                    if ($('#nh_gallery_grid_container .nh-gallery-card-slot').length === 0) {
                        loadProductGallery(activeGalleryProductId);
                    }
                });
            } else {
                M.toast({ html: '<i class="material-icons left">error</i> File clearance action rejected', classes: 'red' });
            }
        }
    });
}

/**
 * Sanitization utility for safe HTML injection output loops
 */
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}