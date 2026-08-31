================================================================================
PROMPT 8: THE CUSTOMER WORKSPACE VIEW (CUSTOMER/DASHBOARD.PHP)
================================================================================

Act as a Full-Stack Web Developer. Write the complete frontend script for the file:
`/html/apps/neighborhub/views/customer/dashboard.php`

This file runs inside our `neighborhub.app.php` structure and has access to the authenticated user's context (`$_SESSION['user']`).

It must implement:
1. A Merchant Browser section: Renders clean HTML grid cards looping through active local businesses using `Merchant` data catalog helpers.
2. A Product Selection Panel: Clicking a storefront card updates the interface to list available `neighborhub_products`, rendering a quantity-selector form with a checkout action trigger.
3. An Active Tracking Ledger: Displays an HTML table or list of all active orders matching the customer's user ID, mapping state keys directly to matching status badge colors.
4. Framework-Compliant AJAX Network Operations: 
   - All client-side requests MUST use `mb.ajax()` or standard jQuery `$.ajax()` calls to interact with `neighborhub.api.php`.
   - Do NOT use raw browser `fetch()` or pure `XMLHttpRequest`—we must rely on the framework's jQuery setup to automatically pass the CSRF token headers.
5. A Live Polling Script: Write an execution loop using `setInterval()` that fires a background `mb.ajax()` call to `neighborhub.api.php?action=get_orders` every 8 seconds to seamlessly update tracking metrics on screen without page reloads.

CRITICAL DIRECTIVE: Write out every single line of code from start to finish. Do not use code-compressing abbreviations or placeholders.