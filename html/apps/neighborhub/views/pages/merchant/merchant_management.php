<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Generic Merchant Products Catalog & Spotlight Storefront
 * @var Object $customer
 * @var Object $merchant
 * @var array $products
 * @var int $current_merchant_id
 */

?>

<style>
  :root {
    --primary: #4f46e5;
    --primary-hover: #4338ca;
    --border: #e5e7eb;
    --danger: #ef4444;
    --success: #10b981;
  }

  h1 {
    margin-bottom: 1.5rem;
    font-size: 1.75rem;
  }

  textarea {
    min-height: 100px;
  }

  .tab-content {
    height: auto !important;
    min-height: auto !important;
    overflow: visible !important;
    display: none;
    padding: 2rem 0;
  }

  .tab-content.active {
    display: block !important;
  }

  .tabs .tab a {
    color: #6b7280;
    transition: color 0.2s ease-in-out;
  }

  .tabs .tab a:hover,
  .tabs .tab a.active {
    color: var(--primary) !important;
  }

  .tabs .indicator {
    background-color: var(--primary) !important;
  }

  .form-group {
    margin-bottom: 1.25rem;
  }

  .flex-end-row {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    margin-bottom: 2rem;
  }
</style>

<div class="container" style="margin-top: 2rem; margin-bottom: 5rem;">
  <input type="hidden" id="merchant_id" value="<?php echo $current_merchant_id; ?>">

  <h1>Store Management Panel
    <span class="right" style="margin-bottom: 1rem;">
      <a href="/?app=neighborhub&view=admin&p=dashboard&merchant_id=<?= $current_merchant_id ?>"
        class="btn-small btn-flat waves-effect blue-text"
        title="Admin Dashboard"
        style="padding: 0 8px; margin-right: 5px;">
        <i class="fas fa-user-shield"></i></a>

      <? render('components/admin/merchant_action_buttons.php', array('merchant' => $merchant)); ?>
    </span>
  </h1>
  <div class="row">
    <div class="col s12">
      <ul class="tabs">
        <li class="tab col s6"><a class="active" href="#settings-tab">Store Settings</a></li>
        <li class="tab col s6"><a href="#staff-tab">Staff Roster</a></li>
      </ul>
    </div>
  </div>

  <!-- 1. SETTINGS TAB (Populated from DB) -->
  <div id="settings-tab" class="tab-content active">
    <form id="settings-form">
      <div class="form-group">
        <label for="business_name">Business Name</label>
        <input type="text" id="business_name" required value="<?php echo htmlspecialchars($merchant['business_name'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="store_hours">Store Hours</label>
        <textarea
          id="store_hours"
          placeholder="Mon-Fri: 9am - 9pm"><?php echo htmlspecialchars($merchant['store_hours'] ?? ''); ?></textarea>
      </div>
      <div class="form-group">
        <label for="address">Address</label>
        <input type="text" id="address" value="<?php echo htmlspecialchars($merchant['address'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="delivery_max_distance">Max Delivery Distance (Miles)</label>
        <input type="number" step="0.1" id="delivery_max_distance" value="<?php echo htmlspecialchars($merchant['delivery_max_distance'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="delivery_assignment_mode">Delivery Assignment Mode</label>
        <select id="delivery_assignment_mode">
          <option value="auto" <?php echo ($merchant['delivery_assignment_mode'] ?? '') === 'auto' ? 'selected' : ''; ?>>Auto</option>
          <option value="manual" <?php echo ($merchant['delivery_assignment_mode'] ?? '') === 'manual' ? 'selected' : ''; ?>>Manual</option>
          <option value="disabled" <?php echo ($merchant['delivery_assignment_mode'] ?? '') === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
        </select>
      </div>
      <div class="form-group">
        <label for="status">Profile Operational Status</label>
        <select id="status">
          <option value="active" <?php echo ($merchant['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="online" <?php echo ($merchant['status'] ?? '') === 'online' ? 'selected' : ''; ?>>Online</option>
          <option value="offline" <?php echo ($merchant['status'] ?? '') === 'offline' ? 'selected' : ''; ?>>Offline</option>
          <option value="paused" <?php echo ($merchant['status'] ?? '') === 'paused' ? 'selected' : ''; ?>>Paused</option>
        </select>
      </div>
      <button onclick="saveSettings(event)" class="btn waves-effect waves-light">Save Configuration</button>
    </form>
  </div>

  <!-- 2. STAFF TAB (Populated from DB) -->
  <div id="staff-tab" class="tab-content">
    <h3>Add Team Member</h3>
    <form id="add-staff-form" onsubmit="addStaff(event)" class="flex-end-row">
      <div class="form-group" style="flex: 2; margin: 0;">
        <label for="target_email">Employee Email Address</label>
        <input type="email" id="target_email" required placeholder="e.g. employee@example.com">
      </div>
      <div class="form-group" style="flex: 1; margin: 0;">
        <label for="staff_role">System Role</label>
        <select id="staff_role">
          <option value="staff">Staff Member</option>
          <option value="delivery">Delivery Driver</option>
          <option value="screen">Screen Admin</option>
          <option value="owner">Co-Owner</option>
        </select>
      </div>
      <button class="btn waves-effect waves-light">Link Member</button>
    </form>

    <h3>Active Team Roster</h3>
    <table class="striped responded">
      <thead>
        <tr>
          <th>Name / Email</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="staff-table-body">
        <?php if (empty($staff_roster)): ?>
          <tr id="no-staff-row">
            <td colspan="3" class="center-align grey-text">No linked team members found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($staff_roster as $row): ?>
            <tr id="staff-row-<?php echo $row['user_id']; ?>">
              <td>
                <strong><?php echo htmlspecialchars($row['username'] ?: 'Unnamed User'); ?></strong><br>
                <span class="grey-text text-darken-1" style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['email']); ?></span>
              </td>
              <td>
                <span style="background: #e0e7ff; color: var(--primary); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                  <?php echo htmlspecialchars($row['staff_role']); ?>
                </span>
              </td>
              <td>
                <button class="btn btn-danger waves-effect waves-light" style="padding: 0 0.8rem; font-size: 0.85rem;" onclick="removeStaff(<?php echo $row['user_id']; ?>)">
                  Remove
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Keep your existing JavaScript block from earlier down here unchanged -->
<script>
  const API_PATH = '?api=neighborhub&action=update_merchant_settings';
  const merchantId = document.getElementById('merchant_id').value;

  document.addEventListener('DOMContentLoaded', function() {
    M.FormSelect.init(document.querySelectorAll('select'));
    M.Tabs.init(document.querySelectorAll('.tabs'));
    // Make sure populated Materialize labels don't visually overlap prefilled text inputs
    M.updateTextFields();
  });

  function saveSettings(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('here');
    mb.ajax({
      url: API_PATH,
      method: 'POST',
      data: JSON.stringify({
        merchant_id: merchantId,
        business_name: document.getElementById('business_name').value,
        store_hours: document.getElementById('store_hours').value,
        address: document.getElementById('address').value,
        delivery_max_distance: document.getElementById('delivery_max_distance').value,
        delivery_assignment_mode: document.getElementById('delivery_assignment_mode').value,
        status: document.getElementById('status').value
      }),
      success: function(res) {
        M.toast({
          html: res.message || 'Configuration saved!',
          classes: 'green'
        });
      },
      error: function(err) {
        M.toast({
          html: err.message || 'Failed to save settings.',
          classes: 'red'
        });
      }
    });
  }

  function addStaff(e) {
    e.preventDefault();
    mb.ajax({
      url: API_PATH,
      method: 'POST',
      data: {
        action: 'manage_staff_members',
        merchant_id: merchantId,
        staff_action: 'add',
        target_email: document.getElementById('target_email').value.trim(),
        staff_role: document.getElementById('staff_role').value
      },
      success: function(res) {
        M.toast({
          html: 'Staff member added! Refreshing roster...',
          classes: 'green'
        });
        setTimeout(() => window.location.reload(), 1000); // Fast structural update view fallback
      },
      error: function(err) {
        M.toast({
          html: err.message || 'Error.',
          classes: 'red'
        });
      }
    });
  }

  function removeStaff(userId) {
    if (!confirm('Are you sure you want to remove this staff member?')) return;
    mb.ajax({
      url: API_PATH,
      method: 'POST',
      data: {
        action: 'manage_staff_members',
        merchant_id: merchantId,
        staff_action: 'remove',
        target_user_id: userId
      },
      success: function(res) {
        M.toast({
          html: 'Staff member removed.',
          classes: 'green'
        });
        const row = document.getElementById('staff-row-' + userId);
        if (row) row.remove();
      },
      error: function(err) {
        M.toast({
          html: err.message || 'Error.',
          classes: 'red'
        });
      }
    });
  }
</script>