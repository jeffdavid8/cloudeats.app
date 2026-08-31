<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Neighborhub Master Administration Dashboard Portal
 * * High-fidelity oversight panel tracking network throughput metrics,
 * onboarding workflows, business management pipelines, and status modifications.
 */

$app = App::getInstance();

// Secure view constraint check
if (!$app->user->is_admin) {
  echo "<div class='container' style='margin-top:20px;'><div class='card red lighten-4'><div class='card-content'><span class='card-title red-text'>Access Denied</span><p>Administrative authorization required to access this resource console.</p></div></div></div>";
  return;
}

// Retrieve data arrays compiled during neighborhub_init()
$stats = $app->get('admin_stats', array(
  'total_merchants' => 0,
  'pending_merchants' => 0,
  'total_orders' => 0,
  'active_couriers' => 0
));
$merchantsList = $app->get('admin_merchants_list', array());
?>
<style>
  span.badge {
    color: #fff;
  }
  .responsive-table a {
    color: #ec6e2a;
    text-decoration: none;
  }
  .responsive-table a:hover {
    color: #26a69a;
    text-decoration: underline;
  }
</style>
<div class="nh-admin-dashboard-wrapper" style="padding: 20px 10px;">
  <div class="row" style="margin-bottom: 30px;">
    <div class="col s12">
      <h4 style="margin: 0; font-weight: 300;" class="valign-wrapper">
        <i class="material-icons left text-darken-2" style="font-size: 36px; color: #26a69a;">security</i>
        Network Management Hub
      </h4>
      <p style="margin: 5px 0 0 42px; color: #777; font-size: 15px;">Platform metrics oversight, merchant configuration management, and systems tracking ledger.</p>
    </div>
  </div>

  <div class="row">
    <div class="col s12 m6 l3">
      <div class="card hoverable white" style="border-radius: 8px; border-left: 5px solid #26a69a;">
        <div class="card-content" style="padding: 20px;">
          <div class="valign-wrapper justify-between" style="display: flex; justify-content: space-between;">
            <div>
              <h5 style="margin: 0; font-weight: 700;"><?= $stats['total_merchants'] ?></h5>
              <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 13px; text-transform: uppercase; font-weight: 600;">Total Registered Shops</p>
            </div>
            <div class="teal lighten-5 valign-wrapper center-align" style="padding: 10px; border-radius: 50%;">
              <i class="material-icons teal-text" style="font-size: 28px;">store</i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col s12 m6 l3">
      <div class="card hoverable white" style="border-radius: 8px; border-left: 5px solid #ff9800;">
        <div class="card-content" style="padding: 20px;">
          <div class="valign-wrapper justify-between" style="display: flex; justify-content: space-between;">
            <div>
              <h5 style="margin: 0; font-weight: 700;"><?= $stats['pending_merchants'] ?></h5>
              <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 13px; text-transform: uppercase; font-weight: 600;">Pending Approvals</p>
            </div>
            <div class="amber lighten-5 valign-wrapper center-align" style="padding: 10px; border-radius: 50%;">
              <i class="material-icons orange-text" style="font-size: 28px;">assignment_late</i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col s12 m6 l3">
      <div class="card hoverable white" style="border-radius: 8px; border-left: 5px solid #2196f3;">
        <div class="card-content" style="padding: 20px;">
          <div class="valign-wrapper justify-between" style="display: flex; justify-content: space-between;">
            <div>
              <h5 style="margin: 0; font-weight: 700;"><?= $stats['total_orders'] ?></h5>
              <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 13px; text-transform: uppercase; font-weight: 600;">Platform Orders Processed</p>
            </div>
            <div class="blue lighten-5 valign-wrapper center-align" style="padding: 10px; border-radius: 50%;">
              <i class="material-icons blue-text" style="font-size: 28px;">local_shipping</i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col s12 m6 l3">
      <div class="card hoverable white" style="border-radius: 8px; border-left: 5px solid #9c27b0;">
        <div class="card-content" style="padding: 20px;">
          <div class="valign-wrapper justify-between" style="display: flex; justify-content: space-between;">
            <div>
              <h5 style="margin: 0; font-weight: 700;"><?= $stats['active_couriers'] ?></h5>
              <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 13px; text-transform: uppercase; font-weight: 600;">Couriers Online Now</p>
            </div>
            <div class="purple lighten-5 valign-wrapper center-align" style="padding: 10px; border-radius: 50%;">
              <i class="material-icons purple-text" style="font-size: 28px;">directions_bike</i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row" style="margin-top: 15px;">
    <div class="col s12">
      <div class="card-panel white valign-wrapper" style="padding: 15px 20px; border-radius: 6px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div class="valign-wrapper">
          <i class="material-icons left blue-grey-text text-lighten-1">build</i>
          <span style="font-weight: 600; ">Quick Actions:</span>
        </div>
        <div>
          <a href="?app=neighborhub&view=admin&p=overview_map" class="btn waves-effect waves-light teal" style="border-radius: 4px; font-weight: 500;">
            <i class="material-icons">map</i>
          </a>
          <a href="?app=neighborhub&view=admin&p=add_courier" class="btn waves-effect waves-light teal" style="border-radius: 4px; font-weight: 500;">
            <i class="material-icons left">person_add</i> Provision New Courier
          </a>
          <a href="?app=neighborhub&view=admin&p=edit_merchant&merchant_id=0" class="btn waves-effect waves-light teal" style="border-radius: 4px; font-weight: 500;">
            <i class="material-icons left">add_business</i> Provision New Merchant
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col s12">
      <div class="card" style="border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <div class="card-content" style="padding: 24px;">
          <span class="card-title" style="margin-bottom: 20px; font-weight: 400; color: #333; display: flex; align-items: center;">
            <i class="material-icons left text-lighten-1" style="color:#777;">format_list_bulleted</i>
            System Merchant Registry Directory
          </span>

          <?
          $status_colors = [
            'active' => '#26a69a',
            'pending' => '#ff9800',
            'suspended' => '#e53935',
            'inactive' => '#9e9e9e',
            'disabled' => '#9e9e9e',
            'archived' => '#9e9e9e',
            'deleted' => '#9e9e9e',
            'banned' => '#e53935',
            'online' => '#26a69a',
            'offline' => '#9e9e9e',
          ];
          ?>
          <?php if (empty($merchantsList)): ?>
            <div class="center-align" style="padding: 40px 20px;">
              <i class="material-icons grey-text text-lighten-1" style="font-size: 64px;">storefront</i>
              <h6 style=" margin-top: 15px;">No registered merchant profiles located on network databases.</h6>
            </div>
          <?php else: ?>
            <table class="striped responsive-table highlight" style="font-size: 14px;">
              <thead>
                <tr style=" border-bottom: 2px solid #ddd;">
                  <th style="font-weight: 600; width: 60px;">ID</th>
                  <th style="font-weight: 600;">Business Name</th>
                  <th style="font-weight: 600;">Location Address</th>
                  <th style="font-weight: 600;">Contact Phone</th>
                  <th style="font-weight: 600; width: 120px;" class="center-align">System Status</th>
                  <th style="font-weight: 600; width: 150px;" class="right-align">Administrative Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($merchantsList as $m): ?>
                  <tr style="border-bottom: 1px solid #e0e0e0;">
                    <td style="font-weight: 600; color: #777;">#<?= intval($m['id']) ?></td>
                    <td style="font-weight: 600; font-size: 15px;"><a href="?app=neighborhub&view=merchant&p=dashboard&merchant_id=<?= intval($m['id']) ?>" title="Edit <?= htmlspecialchars($m['business_name']) ?>"><?= htmlspecialchars($m['business_name']) ?></a></td>
                    <td style=" font-size: 13px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($m['address']) ?></td>
                    <td style=" font-family: monospace;"><?= !empty($m['phone']) ? htmlspecialchars($m['phone']) : '<span class="grey-text">None</span>' ?></td>
                    <td class="center-align">
                      <?php
                      $status = strtolower($m['status']);
                      $statusColor = $status_colors[$status] ?? '#9e9e9e';
                      ?>
                      <?php if ($m['status'] === 'active'): ?>
                        <span class="badge" style="background-color: <?= $statusColor ?>;border-radius: 4px; font-weight: 600; float: none; padding: 2px 8px; font-size: 11px; text-transform: uppercase;">Active</span>
                      <?php elseif ($m['status'] === 'pending'): ?>
                        <span class="badge" style="background-color: <?= $statusColor ?>;border-radius: 4px; font-weight: 600; float: none; padding: 2px 8px; font-size: 11px; text-transform: uppercase;">Pending</span>
                      <?php else: ?>
                        <span class="badge" style="background-color: <?= $statusColor ?>; border-radius: 4px; font-weight: 600; float: none; padding: 2px 8px; font-size: 11px; text-transform: uppercase;"><?= htmlspecialchars($m['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="right-align" style="white-space: nowrap;">
                      <? render('components/admin/merchant_action_buttons.php', array('merchant' => $m)); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  /**
   * Changes active merchant worker session context dynamically via XHR injection
   */
  function switchMerchantContext(merchantId) {
    if (!merchantId) return;

    // Uses the transactional API router we set up in neighborhub.api.php
    mb.ajax({
      url: '?api=neighborhub&action=select_merchant',
      type: 'POST',
      data: {
        merchant_id: merchantId
      },
      success: function(response) {
        if (response && response.success) {
          M.toast({
            html: '<i class="material-icons left">done</i> Context updated! Loading shop dashboard...',
            classes: 'green'
          });
          setTimeout(function() {
            window.location.href = '?app=neighborhub&p=dashboard&view=merchant';
          }, 800);
        } else {
          M.toast({
            html: '<i class="material-icons left">error</i> ' + (response.error || 'Authorization failed'),
            classes: 'red'
          });
        }
      },
      error: function() {
        M.toast({
          html: '<i class="material-icons left">warning</i> Networking connection failure',
          classes: 'red'
        });
      }
    });
  }
</script>