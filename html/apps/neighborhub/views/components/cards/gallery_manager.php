<?php if (!defined('MB_RUNNING')) exit; ?>
<?php
/**
 * Gallery Manager
 * @var String $type // merchant, product, courier, order
 * @var int $id // entity id
 */
?>

<div class="nh-gallery-manager-wrapper" style="margin-top: 20px; border-top: 1px dashed #e0e0e0; padding-top: 20px;" data-type="<?php echo $type; ?>" data-id="<?php echo $id; ?>">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h6 style="font-weight: 600; margin: 0; color: #37474f;" class="valign-wrapper">
            <i class="material-icons left teal-text">collections</i> Media Gallery Showcases
        </h6>
        <input type="file" id="nh_gallery_file_input" accept="image/*" style="display: none;" onchange="handleGalleryFileSelection(this)">
        <button type="button" onclick="document.getElementById('nh_gallery_file_input').click()" class="btn-small waves-effect waves-light blue-grey darken-2" style="border-radius: 4px;">
            <i class="material-icons left">cloud_upload</i> Upload Image
        </button>
    </div>

    <div class="row" id="nh_gallery_grid_container" style="margin-bottom: 0;">
        <div id="nh_gallery_empty_state" class="col s12 center-align grey-text text-darken-1" style="padding: 20px 0;">
            <i class="material-icons" style="font-size: 36px; opacity: 0.5;">add_photo_alternate</i>
            <p style="margin: 5px 0 0 0; font-size: 13px;">No secondary showcase assets linked to this product catalog listing.</p>
        </div>
    </div>
</div>

<style>
.nh-gallery-card-slot {
    position: relative;
    height: 110px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    overflow: hidden;
    background-color: #f5f5f5;
    margin-bottom: 15px;
    transition: box-shadow 0.25s ease;
}
.nh-gallery-card-slot:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}
.nh-gallery-card-slot img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.nh-gallery-action-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.65);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 6px;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.nh-gallery-card-slot:hover .nh-gallery-action-overlay {
    opacity: 1;
}
.nh-gallery-action-overlay .btn-flat {
    padding: 0 4px !important;
    height: 24px !important;
    line-height: 24px !important;
}
.nh-gallery-action-overlay .material-icons {
    font-size: 16px !important;
}
.nh-primary-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    background-color: #26a69a;
    color: white;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 5px;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
</style>