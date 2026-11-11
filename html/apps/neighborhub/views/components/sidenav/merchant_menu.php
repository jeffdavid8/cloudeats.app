<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Merchant Nav Menu
 * 
 * @var object $merchant  - Active merchant profile
 * 
 */
$merchant = $this->get('merchant');
$rawStatus = strtolower(trim($merchant->status ?? ''));
$isShopOnline = ($rawStatus === 'active' || $rawStatus === 'online' || $rawStatus === '1' || $merchant->status === 1);

if ($this->user->is_admin):
  $merchants = Merchant::getAllMerchants();
?>
  <li style="margin-left: 10px;">
    <select id="merchant-select" class="browser-default" onchange="window.location.href = updateQueryStringParameter('merchant_id', this.value); loading(5);">
      <option value="">Select Merchant</option>
      <? foreach ($merchants as $m): ?>
        <option value="<?= $m->id ?>" <?= (get_var('merchant_id') == $m->id) ? 'selected' : '' ?>><?= $m->id . ' - ' . htmlspecialchars($m->business_name) ?></option>
      <? endforeach; ?>
    </select>
  </li>

  <script>
    function updateQueryStringParameter(key, value) {
      // return the current URL with only "&view&merchant_id&p" param
      const url = new URL(window.location.href);
      url.searchParams.set(key, value);
      // Remove all other query params except "view", "merchant_id", and "p"
      const allowedParams = ['app', 'view', 'merchant_id', 'p'];
      for (const param of url.searchParams.keys()) {
        if (!allowedParams.includes(param)) {
          url.searchParams.delete(param);
        }
      }
      return url.toString();
    }
  </script>
<?
endif;

// Get the current URL path and query string to match against hrefs
$current_url = $_SERVER['REQUEST_URI'];

$menu_items = [
    [
        'href' => '/?app=neighborhub&view=customer&p=merchant_products&merchant_id=' . $merchant->id,
        'icon' => 'fas fa-store',
        'title' => 'Storefront'
    ],
    [
        'href' => '/?view=merchant&p=storefront_editor&merchant_id=' . $merchant->id,
        'icon' => 'fas fa-edit',
        'title' => 'Storefront Editor'
    ],
    [
        'href' => '/?app=neighborhub&p=pos&view=merchant&merchant_id=' . $merchant->id,
        'icon' => 'fas fa-store',
        'title' => 'POS'
    ],
    [
        'href' => '/?app=neighborhub&view=merchant&merchant_id=' . $merchant->id . '&p=dashboard',
        'icon' => 'fas fa-store',
        'title' => 'Dashboard'
    ],
    [
        'href' => '/?app=neighborhub&view=merchant&merchant_id=' . $merchant->id . '&p=screen&screen=expo',
        'icon' => 'fas fa-stream',
        'title' => 'Expo',
        'style' => 'text-transform: capitalize;'
    ],
    [
        'href' => '/?app=neighborhub&view=merchant&merchant_id=' . $merchant->id . '&p=screen&screen=lobby',
        'icon' => 'fas fa-chair',
        'title' => 'Lobby',
        'style' => 'text-transform: capitalize;'
    ],
    [
        'type' => 'divider' // Structural element
    ],
    [
        'href' => '#data-export-modal',
        'icon' => 'fas fa-file-download left',
        'title' => 'Export Data',
        'class' => 'modal-trigger',
        'url_conditions' => [
            'view' => 'merchant',
            'merchant_id' => $merchant->id,
            'p' => 'merchant_management'
        ],
    ],
    [
        'href' => '/?app=neighborhub&view=merchant&merchant_id=' . $merchant->id . '&p=products',
        'icon' => 'fas fa-box-open',
        'title' => 'Products'
    ],
    [
        'href' => '/?app=neighborhub&view=merchant&merchant_id=' . $merchant->id . '&p=menus',
        'icon' => 'fas fa-concierge-bell',
        'title' => 'Menus'
    ],
    [
        'href' => '/?app=neighborhub&view=merchant&p=merchant_management&merchant_id=' . $merchant->id,
        'icon' => 'fas fa-cog',
        'title' => 'Store Settings & Staff'
    ],
    [
        'href' => '/?app=neighborhub&view=admin&p=edit_merchant&merchant_id=' . $merchant->id,
        'icon' => 'fas fa-user-edit',
        'title' => 'Edit Merchant Profile',
        'admin_only' => true
    ],
    [
        'href' => '/?app=neighborhub&view=admin&merchant_id=' . $merchant->id,
        'icon' => 'fas fa-user-shield',
        'title' => 'Neighborhub Admin',
        'style' => 'text-transform: capitalize;',
        'admin_only' => true
    ]
];

foreach ($menu_items as $item): 

    // Skip rendering if the item is admin-only and the user is not an admin
    if (!empty($item['admin_only']) && !$this->user->is_admin) {
        continue;
    }

    if (!empty($item['url_conditions'])) {
        $conditions_met = true;
        foreach ($item['url_conditions'] as $key => $value) {
            if (get_var($key) != $value) {
                $conditions_met = false;
                break;
            }
        }
        if (!$conditions_met) {
            continue; // Skip this item if conditions are not met
        }
    }
    
    // Render divider item
    if (isset($item['type']) && $item['type'] === 'divider'): ?>
        <li class="divider" tabindex="-1"></li>
    <?php continue; endif; ?>

    <?php 
    // Determine if this item matches the current page URL
    $isActive = ($current_url === $item['href']) ? 'class="active"' : '';
    
    // Fallback classes and inline styles
    $anchorClass = isset($item['class']) ? 'class="' . $item['class'] . '"' : '';
    $inlineStyle = isset($item['style']) ? 'style="' . $item['style'] . '"' : '';
    ?>

    <li <?= $isActive ?>>
        <a href="<?= htmlspecialchars($item['href']) ?>" <?= $anchorClass ?> <?= $inlineStyle ?>>
            <i class="<?= htmlspecialchars($item['icon']) ?>"></i> <?= htmlspecialchars($item['title']) ?>
        </a>
    </li>
<?php endforeach; ?>
<? /* 
<li>
  <a href="#!" class="store-status-option" data-status="<?= $isShopOnline ? 'offline' : 'online' ?>">
    <span class="status-dot-indicator <?= $isShopOnline ? 'online' : 'offline' ?>"></span>
    <?= $isShopOnline ? 'Go Offline' : 'Go Online'; ?>
  </a>
</li>
*/ ?>

<li class="divider" tabindex="-1"></li>
