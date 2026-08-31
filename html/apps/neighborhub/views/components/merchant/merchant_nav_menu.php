<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Merchant Nav Menu
 * 
 * @var object $merchant  - Active merchant profile
 * 
 */
$rawStatus = strtolower(trim($merchant->status ?? ''));
$isShopOnline = ($rawStatus === 'active' || $rawStatus === 'online' || $rawStatus === '1' || $merchant->status === 1);
?>

<div class="top-right-dropdown-wrapper">
  <a class="merchant-nav-dropdown-trigger btn-floating btn-large waves-effect waves-light" href="#" data-target="merchant-nav-menu">
    <i class="fas fa-ellipsis-v"></i>
  </a>
  <ul id="merchant-nav-menu" class="dropdown-content">
    <li><a href="/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>"><i class="fas fa-store"></i> Storefront</a></li>
    <li><a href="/?app=neighborhub&p=pos&view=merchant&merchant_id=<?= $merchant->id ?>"><i class="fas fa-store"></i> POS</a></li>
    <li><a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchant->id ?>&p=screen&screen=expo" style="text-transform: capitalize;"><i class="fas fa-stream"></i> Expo</a></li>
    <li><a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchant->id ?>&p=screen&screen=lobby" style="text-transform: capitalize;"><i class="fas fa-chair"></i> Lobby</a></li>
    <li class="divider" tabindex="-1"></li>
    <li>
      <a href="#data-export-modal" class="modal-trigger">
        <i class="fas fa-file-download left"></i> Export Data
      </a>
    </li>
    <li><a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchant->id ?>&p=products"><i class="fas fa-box-open"></i> Products</a></li>
    <li><a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchant->id ?>&p=menus"><i class="fas fa-concierge-bell"></i> Menus</a></li>
    <li>
      <a href="/?app=neighborhub&view=merchant&p=merchant_management&merchant_id=<?= $merchant->id ?>"><i class="fas fa-cog"></i> Manage</a>
    </li>
    <? if ($this->user->is_admin) : ?>
      <li><a href="/?app=neighborhub&view=admin&p=edit_merchant&merchant_id=<?= $merchant->id ?>"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
      <li><a href="/?app=neighborhub&view=admin&merchant_id=<?= $merchant->id ?>" style="text-transform: capitalize;"><i class="fas fa-user-shield"></i> Admin</a></li>
    <? endif; ?>
    <li class="divider" tabindex="-1"></li>
    <li>
      <a href="#!" class="store-status-option" data-status="<?= $isShopOnline ? 'offline' : 'online' ?>">
        <span class="status-dot-indicator <?= $isShopOnline ? 'online' : 'offline' ?>"></span>
        <?= $isShopOnline ? 'Go Offline' : 'Go Online'; ?>
      </a>
    </li>
  </ul>
</div>
