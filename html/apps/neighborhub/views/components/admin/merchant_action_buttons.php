<?php
if (!defined('MB_RUNNING')) exit;
/**
 *  @var array $merchant The merchant data array passed to the view.
 *  @var array $layout 
 */
$layout = (isset($layout)) ? $layout : 'default';

switch ($layout) {
  case 'list-item':
    ?>
    <li>
    <a
      href="/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant['id'] ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Visit Storefront"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="fas fa-store"></i>
    </a></li>
    <li>
    <a href="?app=neighborhub&view=merchant&p=dashboard&merchant_id=<?= intval($merchant['id']) ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Merchant Dashboard & Screens"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="fas fa-desktop"></i></a>
    </li>
    <li>
    <a href="?app=neighborhub&view=admin&p=edit_merchant&merchant_id=<?= intval($merchant['id']) ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Edit Merchant Properties"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="material-icons">edit</i></a>
    <? /*
  <button onclick="switchMerchantContext(<?= intval($merchant['id']) ?>)"
    class="btn-small teal waves-effect waves-light"
    title="Manage Shop - Enter Business Operational Mode"
    style="padding: 0 10px; border-radius:3px;"><i class="material-icons" style="margin-right:4px; font-size: 16px;">lock_open</i></button>
    */
    $owner_username = User::getByEmail($merchant['owner_email'])->username;
    ?>
    </a>
    </li>
    <li>
    <a
      href="/?app=neighborhub&view=merchant&p=merchant_management&merchant_id=<?= $merchant['id'] ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Manage Merchant Settings & Staff"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="fas fa-cog"></i></a>
    </li>
  <?
    break;

  case 'default':
  default:
  ?>
    <a
      href="/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant['id'] ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Visit Storefront"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="fas fa-store"></i></a>
    <a href="?app=neighborhub&view=merchant&p=dashboard&merchant_id=<?= intval($merchant['id']) ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Merchant Dashboard & Screens"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="fas fa-desktop"></i></a>
    <a href="?app=neighborhub&view=admin&p=edit_merchant&merchant_id=<?= intval($merchant['id']) ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Edit Merchant Properties"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="material-icons">edit</i></a>
    <? /*
  <button onclick="switchMerchantContext(<?= intval($merchant['id']) ?>)"
    class="btn-small teal waves-effect waves-light"
    title="Manage Shop - Enter Business Operational Mode"
    style="padding: 0 10px; border-radius:3px;"><i class="material-icons" style="margin-right:4px; font-size: 16px;">lock_open</i></button>
    */
    $owner_username = User::getByEmail($merchant['owner_email'])->username;
    ?>
    </a>
    <a
      href="/?app=neighborhub&view=merchant&p=merchant_management&merchant_id=<?= $merchant['id'] ?>"
      class="btn-small btn-flat waves-effect blue-text"
      title="Manage Merchant Settings & Staff"
      style="padding: 0 8px; margin-right: 5px;">
      <i class="fas fa-cog"></i></a>
<?
    break;
} ?>
