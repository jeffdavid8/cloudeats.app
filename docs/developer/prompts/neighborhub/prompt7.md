Act as a Full-Stack Web Developer. Write the complete frontend script for `/html/apps/neighborhub/views/customer/dashboard.php`.

This file runs inside our `neighborhub.app.php` structure and has access to the authenticated user's context (`$_SESSION['user']`). 

It must implement:
1. A Merchant Browser section: Loops through active local businesses using `Merchant::getMerchantByUserId()` or similar catalog fetching routines.
2. A Product Detail Panel: Opens when a storefront card is clicked, presenting a list of `neighborhub_products` with a small, state-managed HTML checkout form.
3. An Active Tracking Ledger: Uses standard loops to display all orders where customer_id matches the session ID. Next to each order, map its current status string directly to our designated style badges.
4. A JavaScript helper that polls `neighborhub.api.php?action=get_orders` every 8 seconds via standard AJAX/fetch loops to refresh status metrics smoothly without requiring a full page refresh.

Do not use summaries or placeholder syntax—write out the complete markup, inline template logic, and scripts down to the last tag.