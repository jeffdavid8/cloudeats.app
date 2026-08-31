<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Dynamic Header User Dropdown with Neighborhub Fluid Role Integration
 * 
 * Queries the database to determine if the authenticated user possesses
 * active merchant staff or courier badges, and dynamically renders role
 * switching links in the profile dropdown menu.
 */
$db = $this->db;

// Initialize role badges
$userMerchantBadges = array();
$userCourierBadge = false;
$currentView = get_var('view', 'customer');
$merchant_id = get_var('merchant_id', false);
$merchant = $this->get('merchant', false);

// Verify user session exists
$isUserLoggedIn = isset($_SESSION['user']);
$username = '';

if ($isUserLoggedIn) {
    $username = $this->user->username;
    $userId = $this->user->id;

    // Query database for merchant and courier badges if user is logged in
    if ($userId && isset($db)) {
        try {

            // Query 1: Check for active merchant staff relationships
            $merchantStmt = $db->prepare(
                "SELECT DISTINCT 
                    nm.id, nm.business_name, nmu.staff_role
                FROM neighborhub_merchants nm
                JOIN neighborhub_merchant_users nmu ON nm.id = nmu.merchant_id
                WHERE nm.user_id = ? AND nmu.status = 'active' AND nm.status != 'disabled'
                ORDER BY nm.business_name ASC"
            );
            $merchantStmt->execute([$userId]);
            $userMerchantBadges = $merchantStmt->fetchAll(PDO::FETCH_ASSOC);

            // Query 2: Check for active courier registration
            $courierStmt = $db->prepare(
                "SELECT id, business_name, status
                FROM neighborhub_couriers
                WHERE user_id = ? AND status IN ('available', 'on_delivery', 'offline')"
            );
            $courierStmt->execute([$userId]);
            $courierResult = $courierStmt->fetch(PDO::FETCH_ASSOC);
            $userCourierBadge = !empty($courierResult);
        } catch (Exception $e) {
            error_log("Header role query error: " . $e->getMessage());
            // Silently fail - don't break the header
            $userMerchantBadges = array();
            $userCourierBadge = false;
        }
    }
}
?>

<ul class="right" data-component="header-right">

    <? if (($this->get('show_header_shopping_basket')) && ($merchant) && ($merchant->status == 'online')) { ?>
        <li><a href="#" style="display: block; overflow: visible; " data-target="nh-shopping-cart-sidenav" class="<?= $this->get('header_shopping_basket_class_list', 'waves-effect waves-light shopping-cart-sidenav-trigger accent-4 shadow-lift round-header-action') ?>">
                <i class="fas fa-shopping-basket"></i>
                <span class="nh-cart-count-badge badge red white-text circle" style="position: absolute; top: 0; right: 0px; font-size: 11px; font-weight:700; display:none; min-width:20px; height:20px; line-height:20px; padding:0;">0</span>
            </a></li>
    <? } ?>

    <?php if ($isUserLoggedIn): ?>
        <!-- User Profile Dropdown -->
        <li>
            <!-- Dropdown Trigger -->
            <a class="dropdown-trigger user-badge" title="Logged in (<?= htmlspecialchars($username) ?>)" href="#!" data-target="user-dropdown" style="color: inherit; display: flex; align-items: center; min-width: auto; margin: 0; padding: 5px 0 0 10px;">
                <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" alt="Profile Picture" class="circle responsive-img" style="width: 32px; height: 32px;">
                <?php else: ?>
                    <i class="material-icons">account_circle</i>
                <?php endif; ?>
                <i class="material-icons right hide-on-small-only" style="margin-right: 0; margin-left: 0; position: relative; right: 8px; top: 13px;">arrow_drop_down</i>
            </a>

            <!-- Dropdown Structure -->
            <ul id="user-dropdown" class="dropdown-content" style="top: 60px !important;">

                <!-- ============================================================================
                     NEIGHBORHUB DYNAMIC ROLE SWITCHING MATRIX
                     ============================================================================ -->

                <?php if ($userMerchantBadges || $userCourierBadge): ?>

                    <? /*
                    <!-- Section Header: Marketplace Roles -->
                    <li class="header" style="color: var(--gray-500); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 1rem;">
                        Marketplace Roles
                    </li>
                    */ ?>

                    <!-- Merchant Terminal (If has merchant badge) -->
                    <?php if (!empty($userMerchantBadges)): ?>
                        <?php foreach ($userMerchantBadges as $merchant): ?>
                            <li>
                                <a href="/?app=neighborhub&p=dashboard&view=merchant&merchant_id=<?php echo intval($merchant['id']); ?>"
                                    title="Switch to Merchant: <?php echo htmlspecialchars($merchant['business_name']); ?>"
                                    style="<?php echo ($currentView === 'merchant') ? 'background-color: var(--gray-100); border-left: 3px solid var(--primary-color); font-weight: 600;' : ''; ?>">
                                    <i class="fas fa-store" style="color: #4ECDC4;"></i>
                                    <span style="display: inline-block;">🏪 <?php echo htmlspecialchars($merchant['business_name']); ?></span>
                                    <span style="font-size: 0.65rem; color: var(--gray-500); margin-left: 0.5rem;">
                                        (<?php echo htmlspecialchars($merchant['staff_role']); ?>)
                                    </span>
                                    <?php if ($currentView === 'merchant'): ?>
                                        <span style="float: right; color: var(--primary-color); font-weight: 700;">●</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Customer Marketplace (Always Available) -->
                    <li>
                        <a href="/?app=neighborhub&p=dashboard&view=customer"
                            title="Switch to Customer view"
                            style="<?php echo ($currentView === 'customer') ? 'background-color: var(--gray-100); border-left: 3px solid var(--primary-color); font-weight: 600;' : ''; ?>">
                            <i class="material-icons" style="color: #FF6B6B;">shopping_cart</i>
                            Customer Marketplace
                            <?php if ($currentView === 'customer'): ?>
                                <span style="float: right; color: var(--primary-color); font-weight: 700;">●</span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Courier Board (If has courier badge) -->
                    <?php if ($userCourierBadge): ?>
                        <li>
                            <a href="/?app=neighborhub&p=dashboard&view=courier"
                                title="Switch to Courier view"
                                style="<?php echo ($currentView === 'courier') ? 'background-color: var(--gray-100); border-left: 3px solid var(--primary-color); font-weight: 600;' : ''; ?>">
                                <span class="emoji-icon">🚴</span> Courier Board
                                <?php if ($currentView === 'courier'): ?>
                                    <span style="float: right; color: var(--primary-color); font-weight: 700;">●</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Wonder City Dispatch (Admin/System view) -->
                    <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin']): ?>
                        <li>
                            <a href="/?app=neighborhub&p=dashboard&view=wondercity"
                                title="System Dispatch Feed"
                                style="<?php echo ($currentView === 'wondercity') ? 'background-color: var(--gray-100); border-left: 3px solid var(--primary-color); font-weight: 600;' : ''; ?>">
                                <span class="emoji-icon">📡</span> Wonder City Dispatch
                                <?php if ($currentView === 'wondercity'): ?>
                                    <span style="float: right; color: var(--primary-color); font-weight: 700;">●</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Divider -->
                    <li class="divider" role="separator"></li>
                <?php endif; ?>
                <!-- Dashboard Link -->
                <li><a href="?p=dashboard" title="Dashboard (<?= htmlspecialchars($username) ?>)"><i class="material-icons">dashboard</i>Dashboard</a></li>

                <!-- Admin Link (if user is admin) -->
                <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin']): ?>
                    <li><a href="?app=neighborhub&p=dashboard&view=admin" title="Admin Dashboard (<?= htmlspecialchars($username) ?>)"><i class="material-icons">settings</i>Admin</a></li>
                <?php endif; ?>


                <!-- Logout Link -->
                <li><a href="/?app=auth&action=logout&redirect=<?=  $_SERVER['REQUEST_URI'] ?>" class="logout-btn" title="Log out (<?= htmlspecialchars($username) ?>)"><i class="material-icons">exit_to_app</i>Logout</a></li>

            </ul>
        </li>

        <!-- Fullscreen Button -->
        <!--<li><a class="fullscreen-btn"><i class="fas fa-expand"></i></a></li>-->

    <?php else: ?>
        <!-- Login Link (Not Logged In) -->
        <li style="margin-right: 10px;">
            <a class="user-badge" href="?p=login&return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" title="Login">
                <i class="material-icons">account_circle</i>
            </a>
        </li>
    <?php endif; ?>

    <li class="hide-on-small-only"><a href="javascript: void(0);" onclick="mb.toggleNightMode();"><i class="fas fa-lightbulb"></i></a></li>

</ul>

<!-- Inline Styles for Dropdown Enhancement -->
<style>
    /*
    #user-dropdown {
        width: 280px !important;
    }
    */

    #user-dropdown li.header {
        display: list-item;
        cursor: default;
        padding: 0.75rem 1rem !important;
    }

    #user-dropdown li.divider {
        margin: 0.5rem 0;
    }

    #user-dropdown a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 150ms ease-in-out;
    }

    #user-dropdown a:hover {
        background-color: rgba(37, 99, 235, 0.05);
        padding-left: 1.75rem;
    }

    #user-dropdown a i.material-icons {
        font-size: 1.25rem;
        min-width: 24px;
    }

    #user-dropdown a[style*="border-left"] {
        padding-left: 1rem !important;
    }
</style>