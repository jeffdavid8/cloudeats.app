<?
/**
 * Membership App
 */
if (!defined('MB_RUNNING')) exit;

function membership_info() {
  return array(
    'title' => 'Membership Manager',
  );
}

/**
 * Initialize membership app
 */
function membership_init(&$app) {

    // 📊 Load the Membership Models (The "Manuals")
    $app->includeModel('membership');
    $app->includeModel('promo');

    // 🎯 Check for the UUID in the URL variable 'promo'
    $promoUuid = get_var('promo', null);
    $page = get_var('p', 'index');
    
    if ($promoUuid) {
        // "Forensic" Lookup: Load the promo data by UUID
        $promo = Promo::getByUuid($promoUuid);
        
        if ($promo) {
            $app->set('promo', $promo);
            $app->set('tier', MembershipTier::getById($promo->tier_id));
            $app->set('page', 'promo');
        } else {
            // Invalid UUID? Back to the "Goober" filter
            $app->set('page', 'index');
        }
    } else {
        // No UUID? Show the "Public Ledger" of available memberships
        $app->set('page', $page);
    }
}

/**
 * Render membership app body
 */
function membership_render_body(&$app) {
    $app = App::getInstance('membership');
    $page = $app->get('page', 'index');

    echo '<div class="container app-container membership-app-container">';
    
    switch ($page) {
        case 'promo':
            // The "Lush" landing page for a specific UUID offer
            render('pages/promo.php', [
                'promo' => $app->get('promo'),
                'tier' => $app->get('tier')
            ]);
            break;

        case 'index':
        default:
            // The "Join the Church" general sales page
            render('pages/index.php');
            break;
    }

    echo '</div>';
}
