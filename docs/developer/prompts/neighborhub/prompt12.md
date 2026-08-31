================================================================================
PROMPT 12: FLUID USER DROPDOWN MATRIX INTEGRATION (HEADER_RIGHT.PHP & NEIGHBORHUB.APP.PHP)
Act as a Principal UI and Application Architect. Update the framework's global identity template file to natively embed Neighborhub's dynamic additive fluid roles:
/html/apps/admin/views/partials/header_right.php (or your active header_right.php path)

It must construct:

Dynamic Database Role Checking: At the top of header_right.php, if a unified user session exists (isset($_SESSION['user'])), fetch a reference to the MediaBrain application database connection. Safely execute quick, non-blocking queries against neighborhub_merchant_users and neighborhub_couriers to determine if the logged-in user possesses active commercial staff or courier transit badges.

Dynamic Context Switches in #user-dropdown: Inside the profile dropdown list (<ul id="user-dropdown"...>), inject a new, distinctive menu section containing Neighborhub's dynamic role triggers:

Always display a link to 🛒 Customer Marketplace targeting /?p=dashboard&view=customer.

If the user has a merchant badge, render a link to 🏪 Merchant Terminal targeting /?app=neighborhub&view=merchant&p=dashboard.

If the user has a courier driver badge, render a link to 🚴 Courier Board targeting /?p=dashboard&view=courier.

Active State Highlight Indicators: Look at the current URL parameters using $_GET['view']. Apply an active styling helper class or inline indicator color to the specific row representing the user's currently active view so they know exactly what context they are working in.

App Initialization Alignment (neighborhub.app.php): Ensure that the main neighborhub_init() or neighborhub_render_body() routines in the background capture these toggled parameters cleanly via $app->set('current_view', ...). If an unauthorized account manually tries to jump via the URL to a view they don't have badges for, fallback gracefully to view=customer and flash a warning notification.

Output the modified code structure from top to bottom with completely intact loops, clear formatting, and zero omissions.