================================================================================
PROMPT 9: THE MERCHANT DASHBOARD TERMINAL (MERCHANT/DASHBOARD.PHP)
================================================================================

Act as a Senior Application Developer. Write the full layout and processing script for:
`/html/apps/neighborhub/views/merchant/dashboard.php`

This file handles the storefront fulfillment workspace, reading context details directly from `$_SESSION['user']['active_merchant_id']`.

It must cleanly build and output:
1. Storefront Parameters Block: Displays the merchant's business name, location parameters, and operating stats.
2. The Live Fulfillment Queue: Segregates processing orders into two clean layout panels:
   - "Pending & Confirmed Queue": Shows incoming orders with manual buttons to update state targets to 'CONFIRMED' or 'READY_FOR_PICKUP'.
   - "Dispatched Deliveries": Tracks active orders currently in transit with their assigned couriers.
3. Framework-Compliant API Integration:
   - All actions (like confirming an order) must fire network payloads using `mb.ajax()` or standard jQuery `$.ajax()` to point to `neighborhub.api.php?action=confirm_order`.
   - Do NOT let the model output standard `fetch()` syntax, ensuring our framework security prefilters are consistently preserved.
4. Automatic Polling Loop: Implements a 5-second recurring `mb.ajax()` script checking for newly created orders, popping open a UI alert indicator when a fresh transaction hits the database ledger.

Provide the complete source file with absolute data structure integrity from beginning to end.