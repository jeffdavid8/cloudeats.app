<?php
if (!defined('MB_RUNNING')) exit;
/**
 * View: /views/pages/merchant/merchant_menus.php
 * Expected variables passed from PHP Controller:
 * @var object $merchant   - Array/object of neighborhub_merchants record
 * @var array $products    - Array of products from neighborhub_products
 * @var array $menus       - Distinct list of menu names (e.g. ['Main Menu', 'Lunch Specials'])
 * @var string $activeMenu - Currently selected menu name
 */
$merchant = $this->get('merchant');
$menus = Menu::getMenusByMerchantId($merchant->id);
//$menus = empty($menus) ? array_map('trim', explode(',', $merchant->menus)) : $menus;
$menus = (empty($menus)) ? array([
  'id' => -1,
  'name' => 'Main Menu',
]) : $menus;
$activeMenuId = get_var('menu_id', ($menus[0] && $menus[0]['id']) ? $menus[0]['id'] : 0);
$products = $this->get('product_catalog') ?? [];
//error_log(print_r($menus, true));

// Group products by category for the right-hand canvas
$menuProductsByCategory = Menu::getProductsGroupedByCategory($activeMenuId);
// Unassigned / catalog items for the left-hand pool
$catalogProducts = [];

foreach ($products as $p) {
  $tags = !empty($p['tags']) ? explode(',', $p['tags']) : [];
  if (!empty($tags)) {
    foreach ($tags as $tag) {
      $catalogProducts[$tag][] = $p;
    }
  }
}
//error_log(print_r($menuProductsByCategory, true)); 
?>

<!-- SortableJS CDN for Drag-and-Drop functionality -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
  body.dayMode,
  body {
    --brand-bg: #e30101;
    --brand-bg-secondary: #db1f26;
    /* Soft Warm Parchment */
    --brand-card-bg: #FFFFFF;
    /* Clean Card Background */
    --brand-text-main: #1C1917;
    /* Deep Rustic Charcoal */
    --brand-text-muted: #57534E;
    /* Warm Slate Gray */
    --brand-border: rgba(139, 30, 30, 0.18);
    --brand-nav-bg: #fff;
    /* Classic Pizza King Crimson */
    --brand-nav-border: #D97706;
    /* Mason Jar Amber Gold */
    --brand-primary: #8B1E1E;
    --brand-secondary: #ce4b18;
    /* Signature Pizza King Red */
    --brand-gold: #D97706;
    /* Pub Amber Gold */
    --brand-btn-add: #1C1917;
    /* Charcoal Action Button */
    --brand-btn-add-hover: #292524;
    --brand-pill-bg: #F5EFE6;
  }

  /* Night / Pub Mode */
  body.nightMode {
    --brand-bg: #e30101;
    --brand-bg-secondary: #db1f26;
    /* Dark Tavern Backdrop */
    --brand-card-bg: #1C1816;
    /* Deep Timber Panel Card */
    --brand-text-main: #F5F5F4;
    /* Soft Off-White */
    --brand-text-muted: #A8A29E;
    /* Muted Ash */
    --brand-border: rgba(217, 119, 6, 0.25);
    --brand-nav-bg: #0C0A09;
    /* Dark Header */
    --brand-nav-border: #8B1E1E;
    /* Glowing Crimson Stripe */
    --brand-primary: #A32424;
    --brand-secondary: #ce4b18;
    /* Bright Crimson */
    --brand-gold: #F59E0B;
    /* Warm Amber Gold */
    --brand-btn-add: #292524;
    /* Dark Slate Button */
    --brand-btn-add-hover: #3D3735;
    --brand-pill-bg: #26211E;
  }

  /* Floating Branding Logo */
  .merchant-header-image {
    position: fixed;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    z-index: 999;
    top: 0;
    width: 60px;
    right: calc(50% - 30px);
  }

  .merchant-header-image img {
    margin: 0 auto;
    border: 4px solid var(--brand-gold);
    border-radius: 50%;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    transition: all 0.4s;
  }

  body.scrolled .merchant-header-image img {
    border: none;
  }

  .merchant-header-image .online-status-dot {
    top: 50px;
    bottom: 7px;
    right: -4px;
    width: 15px;
    height: 15px;
  }

  .menu-canvas-container {
    min-height: 500px;
    background: #fcfcfc;
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    padding: 15px;
  }

  .nightMode #menuCanvas .card {
    background-color: #1e1e1e !important;
    color: #e0e0e0 !important;
    border: 1px solid #333;
    border-top-width: 1px;
    border-top-style: solid;
    border-top-color: rgb(51, 51, 51);
    border-left-width: 1px;
    border-left-style: solid;
    border-left-color: rgb(51, 51, 51);
    border-top: 2px solid #63424b;
  }

  .dayMode #menuCanvas .card {
    background-color: #fff !important;
    color: #302f2f !important;
    border: 1px solid #333;
    border-top-width: 1px;
    border-top-style: solid;
    border-top-color: rgb(51, 51, 51);
    border-left-width: 1px;
    border-left-style: solid;
    border-left-color: rgb(51, 51, 51);
    border-top: 2px solid #63424b;
  }

  .nightMode #menuCanvas h1,
  .nightMode #menuCanvas h2,
  .nightMode #menuCanvas h3,
  .nightMode #menuCanvas h4,
  .nightMode #menuCanvas h5,
  .nightMode #menuCanvas h6 {
    color: #434343;
  }

  .catalog-pool {
    max-height: 650px;
    overflow-y: auto;
    padding-right: 5px;
  }

  .product-card {
    cursor: grab;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    margin-bottom: 10px !important;
  }

  .product-card:active {
    cursor: grabbing;
  }

  #menuCanvas .category-count {
    color: #333;
    margin-left: 5px;
  }

  .sortable-ghost {
    opacity: 0.4;
    background-color: #e0f2f1 !important;
    border: 2px dashed #009688;
  }

  /* Delphi Classic Form Canvas & Component Palette Dropzone */
  .category-dropzone {
    min-height: 80px;
    padding: 12px;
    background-color: #c0c0c0;
    /* Classic Win95 / Delphi Form Gray */

    /* Classic 3D Sunken / Etched Border */
    border-top: 2px solid #808080;
    border-left: 2px solid #808080;
    border-right: 2px solid #ffffff;
    border-bottom: 2px solid #ffffff;

    /* Subtle Delphi Form Grid Background Pattern */
    background-image: radial-gradient(#808080 1px, transparent 0);
    background-size: 8px 8px;

    box-shadow: inset 1px 1px 0px #000000;
    margin-bottom: 10px;
  }

  /* Category Block Box Styling */
  .category-block {
    background: #dbdbdb;
    padding: 10px;
    border-top: 2px solid #ffffff;
    border-left: 2px solid #ffffff;
    border-right: 2px solid #808080;
    border-bottom: 2px solid #808080;
    margin-bottom: 20px !important;
  }

  /* Remove button styling */
  .btn-remove-item {
    cursor: pointer;
    color: #c62828;
    transition: color 0.2s ease;
  }

  .btn-remove-item:hover {
    color: #b71c1c;
  }

  .price-input {
    width: 70px !important;
    height: 1.8rem !important;
    margin: 0 !important;
    font-weight: bold;
    text-align: right;
  }

  .sticky-column {
    position: -webkit-sticky;
    /* Support for older Safari */
    position: sticky;
    top: 7rem;
    /* Distance from the top of the viewport when it locks */
    z-index: 10;
    /* Ensures it stays above scrolling content if needed */
    max-height: calc(100vh - 20px);
    /* Keeps the column from expanding past the viewport */
    overflow-y: auto;
    /* Adds an internal scrollbar IF the left list is longer than the screen */
  }
</style>

<div class="row">
  <!-- Top Action Bar Section -->
  <div class="col s12 m12">
    <div class="input-field margin-0" style="margin:0; display: flex; align-items: center; gap: 8px;">
      <div style="flex-grow: 0;">
        <select id="menuSelector" onchange="switchMenu(this.value)">
          <?php foreach ($menus as $m): ?>
            <option value="<?= htmlspecialchars($m['id']); ?>" <?= $m['id'] == $activeMenuId ? 'selected' : ''; ?>><?= htmlspecialchars($m['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <label>Active Menu</label>
      </div>

      <!-- Action Buttons for Menu Management -->
      <a href="#modal-manage-menu" class="waves-effect waves-light btn-flat btn-floating modal-trigger tooltipped" data-position="top" data-tooltip="Rename Current Menu" onclick="openRenameMenuModal()">
        <i class="material-icons teal-text">edit</i>
      </a>
      <a href="#modal-manage-menu" class="waves-effect waves-light btn-flat btn-floating modal-trigger tooltipped" data-position="top" data-tooltip="Create New Menu" onclick="openCreateMenuModal()">
        <i class="material-icons teal-text">add_box</i>
      </a>
      <?php if (count($menus) > 1): ?>
        <a href="#!" class="waves-effect waves-light btn-flat btn-floating tooltipped" data-position="top" data-tooltip="Delete Current Menu" onclick="deleteCurrentMenu()">
          <i class="material-icons red-text">delete</i>
        </a>
      <?php endif; ?>

      <div class="nh-merchant-action-buttons" style="margin-left: auto;">
        <!-- DESKTOP VERSION: Visible on tablets and up, hidden on mobile -->
        <div class="hide-on-small-only" style="display: flex; gap: 5px;">
          <? render('components/admin/merchant_action_buttons.php', array('merchant' => $merchant->data())); ?>
        </div>
        <!-- MOBILE VERSION: Hidden on tablet/desktop, visible only on small screens -->
        <div class="hide-on-med-and-up">
          <!-- Dropdown Trigger Button -->
          <a class="dropdown-trigger btn-small btn-flat waves-effect blue-text" href="#" data-target="merchant-actions-dropdown" style="padding: 0 8px;">
            <i class="material-icons">more_vert</i>
          </a>
          <!-- Dropdown Structure -->
          <ul id="merchant-actions-dropdown" class="dropdown-content">
            <? render('components/admin/merchant_action_buttons.php', array('merchant' => $merchant->data(), 'layout' => 'list-item')); ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- LEFT PANE: Master Product Catalog Pool -->
  <div class="col s12 m5 l4 sticky-column">
    <div class="card white">
      <div class="card-content">
        <span class="card-title grey-text text-darken-4" style="font-size: 1.1rem; font-weight:bold;">
          <i class="material-icons left">inventory_2</i> Catalog Pool
        </span>
        <p class="caption grey-text" style="font-size:0.85rem;">Drag products into categories on the right menu canvas.</p>

        <div class="input-field style-input" style="margin-top:15px;">
          <i class="material-icons prefix">search</i>
          <input type="text" id="catalogSearch" placeholder="Filter pool...">
        </div>

        <button class="btn-flat waves-effect modal-trigger" href="#modal-product-form" style="margin-bottom: 1rem;">
          <i class="fas fa-plus left"></i> Add New Product
        </button>


        <div id="catalogPool" class="catalog-pool style-pool">
          <?php foreach ($catalogProducts as $tag => $products): ?>
            <span><?= $tag ?></span>
            <?php foreach ($products as $product): ?>

              <div class="card product-card grey lighten-5"
                data-category-id="<?= $product['category_id']; ?>"
                data-product-id="<?= $product['id']; ?>"
                data-menu-item-id="-1"
                data-name="<?= htmlspecialchars($product['name']); ?>"
                data-price="<?= number_format($product['price'], 2, '.', ''); ?>"
                data-sku="<?= htmlspecialchars($product['sku'] ?? ''); ?>"
                data-available="<?= !empty($product['is_available']) ? 1 : 0; ?>">
                <div class="card-content" style="padding: 12px;">
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong class="teal-text text-darken-2"><?= htmlspecialchars($product['name']); ?></strong>
                    <button class="btn-flat" onclick="editProduct(<?= $product['id'] ?>)"><i class="fas fa-edit"></i></button>
                    <span class="new badge blue-grey lighten-4 black-text" data-badge-caption="">
                      $<?= number_format($product['price'], 2); ?>
                    </span>
                  </div>
                  <p class="grey-text text-darken-1 truncate" style="font-size:0.8rem; margin-top:4px;">
                    <?= htmlspecialchars($product['description'] ?? 'No description'); ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANE: Active Menu Canvas -->
  <div class="col s12 m7 l8">
    <div class="card white">
      <div class="card-content">
        <div class="sticky-column" style="top: 60px; display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;padding: 1rem 0 0;  background-color: var(--brand-card-bg);">
          <h5 class="margin-0" style="margin:0;">
            Menu Manager: <span class="teal-text" id="activeMenuTitle"><?= htmlspecialchars($activeMenu); ?></span>
          </h5>
          <button class="btn-flat waves-effect" onclick="addNewCategorySection()">
            <i class="material-icons left">create_new_folder</i>Add Category
          </button>

        </div>

        <div id="menuCanvas" class="menu-canvas-container">
          <?php if (empty($menuProductsByCategory)): ?>
            <div id="emptyMenuNotice" class="center-align grey-text" style="padding: 40px 0;">
              <i class="material-icons large lighten-4">drag_indicator</i>
              <p>This menu has no products assigned yet. Drag items from the Left Catalog Pool or create a category.</p>
            </div>
          <?php else: ?>
            <?php foreach ($menuProductsByCategory as $categoryId => $catProducts):
              $categoryName = $catProducts[0]['category_name'];
            ?>
              <div class="category-block" data-category-id="<?= $catProducts[0]['category_id'] ?? $categoryId; ?>" data-category-name="<? echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>">

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                  <h6 style="font-weight:bold; margin:0; display:flex; align-items:center; cursor:grab;" class="category-handle">
                    <!-- Drag Handle Icon -->
                    <i class="material-icons left tiny grey-text">drag_handle</i>
                    <i class="material-icons left tiny">label</i>
                    <?= htmlspecialchars($categoryName); ?>

                    <a href="#!" class="btn-remove-item" onclick="removeCategoryFromMenu(this, <?= $catProducts[0]['category_id']; ?>)" title="Remove from Menu" style="margin-left: 1rem;">
                      <i class="material-icons tiny">delete</i>
                    </a>
                  </h6>

                  <span class="grey-text category-count" style="font-size:0.8rem;"><?= count($catProducts); ?> Items</span>
                </div>

                <div class="category-dropzone style-dropzone">
                  <?php foreach ($catProducts as $product): ?>
                    <div class="card product-card white"
                      data-product-id="<?= $product['id']; ?>"
                      data-menu-item-id="<?= $product['menu_item_id']; ?>"
                      data-name="<?= htmlspecialchars($product['name']); ?>"
                      data-price="<?= number_format($product['price'], 2, '.', ''); ?>"
                      data-sku="<?= htmlspecialchars($product['sku'] ?? ''); ?>"
                      data-available="<?= !empty($product['is_available']) ? 1 : 0; ?>"
                      style="border-left: 4px solid #009688;">
                      <div class="card-content" style="padding: 10px 15px;">
                        <div class="row margin-bottom-0" style="margin:0; display:flex; align-items:center;">
                          <div class="col s5">
                            <strong><?= htmlspecialchars($product['name']); ?></strong>
                            <button class="btn-flat" onclick="editProduct(<?= $product['id'] ?>)"><i class="fas fa-edit"></i></button>
                            <div class="grey-text" style="font-size: 0.75rem;">SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A'); ?></div>
                          </div>
                          <div class="col s3">
                            $<input type="number" step="0.01" class="price-input" value="<?= number_format($product['price'], 2, '.', ''); ?>" onchange="updateProductPrice(<?= $product['id']; ?>, this.value)">
                          </div>
                          <div class="col s3 right-align">
                            <div class="switch">
                              <label>
                                <input type="checkbox" <?= !empty($product['is_available']) ? 'checked' : ''; ?> onchange="toggleAvailability(<?= $product['id']; ?>, this.checked)">
                                <span class="lever"></span>
                              </label>
                            </div>
                          </div>
                          <!-- Remove Item Action Button -->
                          <div class="col s1 right-align">
                            <a href="#!" class="btn-remove-item" onclick="removeProductFromMenu(this, <?= $product['id']; ?>)" title="Remove from Menu">
                              <i class="material-icons tiny">delete</i>
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Product Form Modal -->
<?
render('components/modal_product_form.php', array(
  'merchant' => $merchant,
));
?>

<!-- Manage Menu Modal (Create / Rename) -->
<div id="modal-manage-menu" class="modal mb-modal-fixed" style="max-width: 450px;">
  <div class="modal-content">
    <h5 id="menu-modal-title" style="margin-top: 0;">Create New Menu</h5>
    <input type="hidden" id="menu-action-type" value="create">
    <input type="hidden" id="menu-id" name="menu_id" value="<?= htmlspecialchars($activeMenuId); ?>">

    <div class="input-field" style="margin-top: 25px;">
      <input type="text" id="menu-name-input" class="validate" required placeholder="e.g. Dinner Menu, Breakfast, Drinks">
      <label for="menu-name-input" class="active">Menu Name</label>
    </div>
  </div>
  <div class="modal-footer">
    <a href="#!" class="modal-close waves-effect waves-grey btn-flat">Cancel</a>
    <button type="button" class="waves-effect waves-light btn teal" onclick="submitMenuForm()">Save Menu</button>
  </div>
</div>

<script>
  /**
   * Switch selected menu and reload page canvas for that menu
   */
  function switchMenu(menuId) {
    const url = new URL(window.location.href);
    loading(1);
    if (url.searchParams.get('menu_id') == menuId) {
      location.reload();
    } else {
      url.searchParams.set('menu_id', menuId);
      window.location.href = url.toString();
    }
  }

  /**
   * Prepares modal for CREATING a new menu
   */
  function openCreateMenuModal() {
    document.getElementById('menu-modal-title').textContent = 'Create New Menu';
    document.getElementById('menu-action-type').value = 'create';
    var modalElement = document.querySelector('#modal-manage-menu');
    var instance = M.Modal.getInstance(modalElement);
    instance.open();

    const nameInput = document.getElementById('menu-name-input');
    nameInput.value = '';
    M.updateTextFields();
    nameInput.focus();
  }

  /**
   * Prepares modal for RENAMING the currently active menu
   */
  function openRenameMenuModal() {
    const currentMenu = document.getElementById('menuSelector').selectedOptions[0].innerHTML.trim();
    document.getElementById('menu-modal-title').textContent = 'Rename Menu';
    document.getElementById('menu-action-type').value = 'rename';
    var modalElement = document.querySelector('#modal-manage-menu');
    var instance = M.Modal.getInstance(modalElement);
    instance.open();

    const nameInput = document.getElementById('menu-name-input');
    nameInput.value = currentMenu;
    M.updateTextFields();
    nameInput.focus();
  }

  /**
   * Submits Creation or Renaming of Menu via AJAX
   */
  function submitMenuForm() {
    const actionType = document.getElementById('menu-action-type').value;
    const newMenuName = document.getElementById('menu-name-input').value.trim();
    const menuId = document.getElementById('menu-id').value.trim();
    const merchantId = '<?= $merchant->id; ?>';

    if (!newMenuName) {
      M.toast({
        html: ' Please enter a valid menu name.',
        classes: 'orange'
      });
      return;
    }

    const formData = new FormData();
    formData.append('action', actionType === 'rename' ? 'rename_menu' : 'create_menu');
    formData.append('merchant_id', merchantId);
    formData.append('menu_id', menuId);
    formData.append('menu_name', newMenuName);

    mb.ajax({
      url: '/?api=neighborhub',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: ' Menu saved successfully!',
            classes: 'teal'
          });

          // Refresh page with newly updated menu context
          switchMenu(response.data.menu_id);
        } else {
          M.toast({
            html: ' Error: ' + (response.error || 'Failed to save menu.'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: ' Server communication error.',
          classes: 'red'
        });
      }
    });
  }

  /**
   * Deletes the currently selected menu after confirmation
   */
  function deleteCurrentMenu() {
    const currentMenuId = document.getElementById('menuSelector').value;
    const currentMenu = document.getElementById('menuSelector').selectedOptions[0].innerHTML.trim();
    const merchantId = '<?= $merchant->id; ?>';

    if (!confirm(`Are you sure you want to delete "${currentMenu}"? Products attached to this menu will return to the general catalog pool.`)) {
      return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_menu');
    formData.append('merchant_id', merchantId);
    formData.append('menu_id', currentMenuId);

    mb.ajax({
      url: '/?api=neighborhub',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: ' Menu deleted successfully.',
            classes: 'teal'
          });
          // Redirect to remaining first menu or general page
          const url = new URL(window.location.href);
          url.searchParams.delete('menu');
          window.location.href = url.pathname + url.search;
        } else {
          M.toast({
            html: ' Error: ' + (response.error || 'Failed to delete menu.'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: ' Server error deleting menu.',
          classes: 'red'
        });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {

    $('#menuSelector').formSelect();
    $('#modal-product-form, #modal-manage-menu').modal();

    // Initialize the mobile action menu dropdown
    var elems = document.querySelectorAll('.dropdown-trigger');
    var instances = M.Dropdown.init(elems, {
      constrainWidth: false, // Prevents the dropdown from narrowing down to the trigger button width
      alignment: 'right' // Aligns the dropdown container nicely to the right side of the screen
    });

    // Initialize Drag & Drop on Catalog Pool
    const poolEl = document.getElementById('catalogPool');
    if (poolEl) {
      new Sortable(poolEl, {
        group: {
          name: 'menu-items',
          pull: 'clone',
          put: false
        },
        sort: false,
        animation: 150
      });
    }

    const categoriesContainer = document.getElementById('menuCanvas');
    if (categoriesContainer) {
      new Sortable(categoriesContainer, {
        animation: 150,
        handle: '.category-handle', // Restrict dragging to the category header/handle
        ghostClass: 'blue-lighten-5',
        onEnd: function(evt) {
          // Build array of category IDs in their new order
          const categoryOrder = Array.from(categoriesContainer.children).map((block, index) => ({
            id: block.dataset.categoryId,
            sort_order: index
          }));

          // Send new order to backend endpoint
          saveCategoryOrder(categoryOrder);
        }
      });
    }
    // Initialize Drag & Drop on existing dropzones
    initCategoryDropzones();

    // Search filter for Left Catalog Pool
    document.getElementById('catalogSearch').addEventListener('keyup', function(e) {
      const term = e.target.value.toLowerCase();
      const cards = poolEl.getElementsByClassName('product-card');
      Array.from(cards).forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(term) ? '' : 'none';
      });
    });

    // Switch Menu Dropdown Event
    document.getElementById('menuSelector').addEventListener('change', function(e) {
      const selectedMenuId = e.target.value;
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('menu_id', selectedMenuId);
      window.location.href = currentUrl.toString();
    });
  });

  // AJAX Function to Save Category Order
  function saveCategoryOrder(orderArray) {
    mb.ajax({
      url: '?api=neighborhub&action=reorder_categories',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        order: orderArray
      }),
      success: function(data) {
        if (!data.success) {
          M.toast({
            html: 'Failed to update category order',
            classes: 'red'
          });
        }
      },
      error: function(xhr, status, error) {
        M.toast({
          html: 'Error connecting to server',
          classes: 'red'
        });
      }
    });
  }

  function initCategoryDropzones() {
    const dropzones = document.querySelectorAll('.category-dropzone');

    dropzones.forEach(zone => {
      // Avoid double-initializing Sortable on the same zone
      if (Sortable.get(zone)) return;

      new Sortable(zone, {
        group: {
          name: 'menu-items',
          pull: true,
          put: true
        },
        animation: 150,
        onAdd: function(evt) {
          const itemEl = evt.item;
          const productId = itemEl.dataset.productId;
          const catBlock = evt.to.closest('.category-block');
          const targetCategoryId = catBlock ? catBlock.dataset.categoryId : 0;
          const targetCategoryName = catBlock ? catBlock.dataset.categoryName : 'Uncategorized';
          const currentMenuId = document.getElementById('menuSelector').value ?? -1;
          const currentMenuName = document.getElementById('menuSelector').selectedOptions[0].innerHTML.trim();

          // Save back via mb.ajax
          updateProductMenuCategory(productId, currentMenuId, currentMenuName, targetCategoryId, targetCategoryName, function(response) {
            const menuId = response.data.menu_id;

            itemEl.dataset.menuItemId = response.data.menu_item_id;

            // Transform dropped element from Pool style to Canvas card style
            renderCanvasCard(itemEl);

            // Update category count label
            if (catBlock) updateCategoryCount(catBlock);

            // Hide empty menu placeholder if present
            const notice = document.getElementById('emptyMenuNotice');
            if (notice) notice.style.display = 'none';

          });

        },
        onRemove: function(evt) {
          const itemEl = evt.item;
          const productId = itemEl.dataset.productId;
          const catBlock = evt.from.closest('.category-block');
          const sourceCategoryId = catBlock ? catBlock.dataset.categoryId : 0;
          const sourceCategoryName = catBlock ? catBlock.dataset.categoryName : 'Uncategorized';
          const currentMenuId = document.getElementById('menuSelector').value ?? -1;
          const currentMenuName = document.getElementById('menuSelector').selectedOptions[0].innerHTML.trim();

          // Update category count label
          if (catBlock) updateCategoryCount(catBlock);

          // If the dropzone is now empty, show the empty menu notice
          if (catBlock && catBlock.querySelectorAll('.product-card').length === 0) {
            const notice = document.getElementById('emptyMenuNotice');
            if (notice) notice.style.display = 'block';
          }

          // Save back via mb.ajax
          updateProductMenuCategory(productId, currentMenuId, currentMenuName, 0, 'Uncategorized');
        },
        onEnd: function(evt) {
          // Get the new order of products in the dropzone after drag-and-drop
          const categoryId = evt.to.closest('.category-block')?.dataset.categoryId ?? 0;
          const productOrder = Array.from(evt.to.children).map((card, index) => ({
            product_id: card.dataset.productId,
            menu_item_id: card.dataset.menuItemId,
            sort_order: index
          }));

          // Send new order to backend endpoint
          saveProductOrder(categoryId, productOrder);

        }
      });
    });
  }

  function saveProductOrder(categoryId, orderArray) {
    mb.ajax({
      url: '?api=neighborhub&action=reorder_products',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        category_id: categoryId,
        order: orderArray
      }),
      success: function(data) {
        if (!data.success) {
          M.toast({
            html: 'Failed to update product order',
            classes: 'red'
          });
        } else {
          /*
          M.toast({
            html: 'Product order updated successfully',
            classes: 'teal'
          });
          */
          
        }
      },
      error: function(xhr, status, error) {
        M.toast({
          html: 'Error connecting to server',
          classes: 'red'
        });
      }
    });
  }

  function renderCanvasCard(itemEl) {
    const pId = itemEl.dataset.id;
    const pName = itemEl.dataset.name || itemEl.querySelector('strong')?.textContent || '';
    const pPrice = itemEl.dataset.price || '0.00';
    const pSku = itemEl.dataset.sku || 'N/A';
    const isAvailable = itemEl.dataset.available !== '0';

    itemEl.className = 'card product-card white';
    itemEl.style.borderLeft = '4px solid #009688';
    itemEl.innerHTML = `
    <div class="card-content" style="padding: 10px 15px;">
      <div class="row margin-bottom-0" style="margin:0; display:flex; align-items:center;">
        <div class="col s5">
          <strong>${pName}</strong><button class="btn-flat" onclick="editProduct(${pId})" ><i class="fas fa-edit"></i></button>
          <div class="grey-text" style="font-size: 0.75rem;">SKU: ${pSku}</div>
        </div>
        <div class="col s3">
          $<input type="number" step="0.01" class="price-input" value="${pPrice}" onchange="updateProductPrice(${pId}, this.value)">
        </div>
        <div class="col s3 right-align">
          <div class="switch">
            <label>
              <input type="checkbox" ${isAvailable ? 'checked' : ''} onchange="toggleAvailability(${pId}, this.checked)">
              <span class="lever"></span>
            </label>
          </div>
        </div>
        <!-- Action Buttons Column -->
        <div class="col s1 right-align" style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
          <a href="#!" onclick="editProduct(${pId})" title="Edit Product">
            <i class="material-icons tiny teal-text">edit</i>
          </a>
          <a href="#!" class="btn-remove-item" onclick="removeProductFromMenu(this, ${pId})" title="Remove from Menu">
            <i class="material-icons tiny red-text">delete</i>
          </a>
        </div>
      </div>
    </div>
  `;
  }

  function addNewCategorySection() {
    const catName = prompt('Enter new category name:');
    if (!catName || !catName.trim()) return;

    const cleanName = catName.trim();
    const canvas = document.getElementById('menuCanvas');
    const notice = document.getElementById('emptyMenuNotice');
    if (notice) notice.style.display = 'none';

    const catBlock = document.createElement('div');
    catBlock.className = 'category-block';
    catBlock.dataset.categoryId = -1;
    catBlock.dataset.categoryName = cleanName;
    catBlock.style.marginBottom = '20px';
    catBlock.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
        <h6 style="font-weight:bold; margin:0;"><i class="material-icons left tiny">label</i>${cleanName}</h6>
        <span class="grey-text category-count" style="font-size:0.8rem;">0 Items</span>
      </div>
      <div class="category-dropzone style-dropzone"></div>
    `;

    canvas.appendChild(catBlock);
    initCategoryDropzones();
  }

  function removeCategoryFromMenu(btnEl, categoryId) {
    const catBlock = btnEl.closest('.category-block');
    if (!catBlock) return;

    if (!confirm('Are you sure you want to remove this category from the menu? Products will return to the catalog pool.')) {
      return;
    }

    mb.ajax({
      url: '/?api=neighborhub&action=remove_category_from_menu',
      type: 'POST',
      data: JSON.stringify({
        category_id: categoryId
      }),
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: '🗑️ Category removed from menu',
            classes: 'teal'
          });
          catBlock.remove();
        } else {
          M.toast({
            html: '❌ Error: ' + (response.message || 'Failed to remove category'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: '❌ Server error removing category from menu',
          classes: 'red'
        });
      }
    });
  }

  function updateCategoryCount(categoryBlock) {
    const countEl = categoryBlock.querySelector('.category-count');
    if (countEl) {
      const total = categoryBlock.querySelectorAll('.category-dropzone .product-card').length;
      countEl.textContent = `${total} Items`;
    }
  }

  // --- API CALLS (Matching mb.ajax Standard Pattern) ---

  function updateProductMenuCategory(productId, menuId, menuName, categoryId, categoryName, callback=null) {
    const merchantId = <?= intval($merchant->id ?? 0); ?>;

    mb.ajax({
      url: '/?api=neighborhub&action=assign_product_category',
      type: 'POST',
      data: JSON.stringify({
        product_id: productId,
        merchant_id: merchantId,
        menu_id: menuId,
        menu_name: menuName,
        category_id: categoryId, // Fixed: changed from 'category' to 'category_name'
        category_name: categoryName // Fixed: changed from 'category' to 'category_name'
      }),
      success: function(response) {
        if (response && response.success) {

          if (typeof callback === 'function') {
            callback(response);
          }

          if (parseInt(categoryId) === -1) {
            // If category is new, update the category block with the new ID from response
            const safeCategoryName = CSS.escape(categoryName);
            const catBlock = document.querySelector(`.category-block[data-category-id="${categoryId}"]`);
            if (catBlock && response.data && response.data.category_id) {
              catBlock.dataset.categoryId = response.data.category_id;
            }
          }
          M.toast({
            html: '📋 Item assigned to category!',
            classes: 'teal'
          });
        } else {
          // Fixed: changed response.error to response.message
          M.toast({
            html: '❌ Error: ' + (response.message || 'Assignment failed'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: '❌ Server error processing menu assignment',
          classes: 'red'
        });
        return;
      }
    });
  }

  function removeProductFromMenu(btnEl, productId) {
    const card = btnEl.closest('.product-card');
    const catBlock = card.closest('.category-block');
    const currentMenu = document.getElementById('menuSelector').value;

    mb.ajax({
      url: '/?api=neighborhub&action=remove_product_from_menu',
      type: 'POST',
      data: JSON.stringify({
        product_id: productId,
        menu_id: currentMenu // Fixed: changed from 'menu' to 'menu_id'
      }),
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: '🗑️ Item removed from menu',
            classes: 'teal'
          });
          card.remove();
          if (catBlock) updateCategoryCount(catBlock);
        } else {
          // Fixed: changed response.error to response.message
          M.toast({
            html: '❌ Error: ' + (response.message || 'Failed to remove item'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: '❌ Server error removing item from menu',
          classes: 'red'
        });
      }
    });
  }

  function updateProductPrice(productId, newPrice) {
    mb.ajax({
      url: '/?api=neighborhub&action=update_product_price',
      type: 'POST',
      data: JSON.stringify({
        product_id: productId,
        price: newPrice
      }),
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: '💰 Price updated!',
            classes: 'teal'
          });
        } else {
          // Fixed: changed response.error to response.message
          M.toast({
            html: '❌ Error: ' + (response.message || 'Failed to update price'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: '❌ Server error saving product price',
          classes: 'red'
        });
      }
    });
  }

  function toggleAvailability(productId, isAvailable) {
    mb.ajax({
      url: '/?api=neighborhub&action=toggle_product_availability',
      type: 'POST',
      data: JSON.stringify({
        product_id: productId,
        is_available: isAvailable ? 1 : 0
      }),
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: '⚡ Availability toggled!',
            classes: 'teal'
          });
        } else {
          // Fixed: changed response.error to response.message
          M.toast({
            html: '❌ Error: ' + (response.message || 'Failed to update availability'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: '❌ Server error toggling availability',
          classes: 'red'
        });
      }
    });
  }

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

          // Update UI components across the screen
          updateMainUI(productData, isUpdate);

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

  /**
   * Updates the Left Catalog Pool (#catalogPool) and 
   * Right Active Menu Canvas (#menuCanvas) in real time.
   */
  function updateMainUI(product, isUpdate) {
    const targetId = String(product.id || product.product_id);
    const formattedPrice = parseFloat(product.price || 0).toFixed(2);
    const safeName = escapeHtml(product.name || '');
    const safeDesc = escapeHtml(product.description || 'No description');

    // ==========================================
    // 1. UPDATE / INSERT INTO LEFT CATALOG POOL
    // ==========================================
    const poolEl = document.getElementById('catalogPool');
    if (poolEl) {
      let poolCard = poolEl.querySelector(`.product-card[data-product-id="${targetId}"]`);

      if (poolCard) {
        // Update existing pool card attributes & DOM structure
        poolCard.dataset.name = product.name;
        poolCard.dataset.price = formattedPrice;
        poolCard.dataset.available = product.is_available ? 1 : 0;

        const titleEl = poolCard.querySelector('strong');
        if (titleEl) titleEl.textContent = product.name;

        const priceBadge = poolCard.querySelector('.badge');
        if (priceBadge) priceBadge.textContent = `$${formattedPrice}`;

        const descEl = poolCard.querySelector('p');
        if (descEl) descEl.textContent = product.description || 'No description';
      } else if (!isUpdate) {
        // Prepend brand new product card to top of the catalog pool
        const newCard = document.createElement('div');
        newCard.className = 'card product-card grey lighten-5';
        newCard.dataset.productId = targetId;
        newCard.dataset.menuItemId = -1;
        newCard.dataset.name = product.name;
        newCard.dataset.price = formattedPrice;
        newCard.dataset.sku = product.sku || '';
        newCard.dataset.available = product.is_available ? 1 : 0;

        newCard.innerHTML = `
        <div class="card-content" style="padding: 12px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong class="teal-text text-darken-2">${safeName}</strong>
            <button class="btn-flat" onclick="editProduct(${targetId})" ><i class="fas fa-edit"></i></button>
            <span class="new badge blue-grey lighten-4 black-text" data-badge-caption="">
              $${formattedPrice}
            </span>
          </div>
          <p class="grey-text text-darken-1 truncate" style="font-size:0.8rem; margin-top:4px;">
            ${safeDesc}
          </p>
        </div>
      `;

        poolEl.insertBefore(newCard, poolEl.firstChild);
      }
    }

    // ==========================================
    // 2. UPDATE EXISTING CARDS ON RIGHT MENU CANVAS
    // ==========================================
    const menuCanvas = document.getElementById('menuCanvas');
    if (menuCanvas) {
      const canvasCards = menuCanvas.querySelectorAll(`.product-card[data-product-id="${targetId}"]`);

      canvasCards.forEach(card => {
        card.dataset.name = product.name;
        card.dataset.price = formattedPrice;
        card.dataset.available = product.is_available ? 1 : 0;

        // Update card title text
        const titleStrong = card.querySelector('.col.s5 strong');
        if (titleStrong) titleStrong.textContent = product.name;

        // Update price input value
        const priceInput = card.querySelector('.price-input');
        if (priceInput) priceInput.value = formattedPrice;

        // Update availability checkbox state
        const availCheckbox = card.querySelector('.switch input[type="checkbox"]');
        if (availCheckbox) availCheckbox.checked = Boolean(product.is_available);
      });
    }
  }

  // Helper utility for safe HTML rendering in JS
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  /**
   * Load product data into modal and open it for editing
   * @param {number|string} productId 
   */
  function editProduct(productId) {
    loading(1);
    // 1. Fetch full product record from API (or fallback to DOM element)
    mb.ajax({
      url: '/?api=neighborhub&action=get_product&product_id=' + productId,
      type: 'GET',
      success: function(response) {
        if (response && response.success && response.product) {
          populateProductForm(response.product);
        } else {
          // Fallback: Populate directly from card DOM attributes if API endpoint isn't ready
          populateFormFromDom(productId);
        }
        loading(0);
      },
      error: function() {
        loading(0);
        // Fallback on network/server error
        populateFormFromDom(productId);
      }
    });
  }

  function populateProductForm(product) {

    // Set Modal Title & IDs
    document.getElementById('modal-title').textContent = 'Edit Product: ' + (product.name || '');
    document.getElementById('form-product-id').value = product.id || '';

    // Fill text and input controls
    document.getElementById('form-product-name').value = product.name || '';
    document.getElementById('form-product-description').value = product.description || '';
    document.getElementById('form-product-tags').value = product.tags || '';
    document.getElementById('form-product-price').value = product.price ? parseFloat(product.price).toFixed(2) : '0.00';
    document.getElementById('form-product-type').value = product.type || '';
    // document.getElementById('form-product-meta').value = JSON.stringify(product.meta, null, 2) || '';

    // Availability Switch
    const isAvailable = product.is_available == 1 || product.is_available === true;
    document.getElementById('form-product-available').checked = isAvailable;

    // Primary Image Preview
    const imagePreviewBox = document.getElementById('image-preview-box');
    const formImagePreview = document.getElementById('form-image-preview');
    const formExistingImageUrl = document.getElementById('form-existing-image-url');

    if (product.image_url) {
      formExistingImageUrl.value = product.image_url;
      formImagePreview.src = product.image_url;
      imagePreviewBox.style.display = 'block';
    } else {
      formExistingImageUrl.value = '';
      formImagePreview.src = '';
      imagePreviewBox.style.display = 'none';
    }

    // Gallery Images Grid (if gallery array exists)
    const thumbnailsGrid = document.getElementById('product-gallery-thumbnails-grid');
    const dropzonePlaceholder = document.getElementById('dropzone-placeholder-text');
    if (thumbnailsGrid) {
      thumbnailsGrid.innerHTML = '';
      if (product.gallery && Array.isArray(product.gallery) && product.gallery.length > 0) {
        if (dropzonePlaceholder) dropzonePlaceholder.style.display = 'none';

        product.gallery.forEach(img => {
          const wrapper = document.createElement('div');
          wrapper.className = 'gallery-image-thumb-wrapper';
          wrapper.dataset.imageId = img.id;
          wrapper.style = 'position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; border: 1px solid #ccc;';
          wrapper.innerHTML = `
          <img src="${img.url}" style="width:100%; height:100%; object-fit:cover;">
          <button type="button" onclick="deleteGalleryImageAsset(${product.id}, ${img.id})" 
                  style="position:absolute; top:2px; right:2px; background:rgba(244,67,54,0.85); color:white; border:none; border-radius:50%; width:18px; height:18px; line-height:18px; text-align:center; padding:0; cursor:pointer; font-size:10px;">
            ✕
          </button>
        `;
          thumbnailsGrid.appendChild(wrapper);
        });
      } else if (dropzonePlaceholder) {
        dropzonePlaceholder.style.display = 'block';
      }
    }

    M.textareaAutoResize($('#form-product-description'));

    // Advanced JSON Meta Field
    const metaTextarea = document.getElementById('form-product-meta');
    console.log(typeof product.meta);
    if (metaTextarea) {
      if (typeof product.meta === 'object') {
        metaTextarea.value = JSON.stringify(product.meta, null, 4);
      } else {
        metaTextarea.value = JSON.stringify(JSON.parse(product.meta), null, 4) || '';
      }
      M.textareaAutoResize($(metaTextarea));
    }

    // Re-initialize Materialize CSS labels and open Modal
    M.updateTextFields();
    const modalEl = document.getElementById('modal-product-form');
    const modal = M.Modal.getInstance(modalEl);
    if (modal) modal.open();
  }

  // Fallback method to grab attributes directly off the card element in DOM
  function populateFormFromDom(productId) {
    const card = document.querySelector(`.product-card[data-product-id="${productId}"]`);
    if (!card) {
      M.toast({
        html: '❌ Could not find product card on page',
        classes: 'red'
      });
      return;
    }

    const pData = {
      id: productId,
      name: card.dataset.name || card.querySelector('strong')?.textContent.trim(),
      price: card.dataset.price || '0.00',
      description: card.querySelector('p')?.textContent.trim() || '',
      is_available: card.dataset.available !== '0',
      tags: '',
      type: ''
    };

    populateProductForm(pData);
  }
</script>