================================================================================
PROMPT 2: THE ORDER DATA MODEL & ATOMIC CONCURRENCY (PHASE 4 - ORDER.PHP)
================================================================================

Act as a Principal PHP Backend Engineer. We are building the data access layer models for our app using standard PHP Data Objects (PDO) connected to our SQLite database file ($GLOBALS['db']). 

Write the complete, production-ready code for the file:
`/html/apps/neighborhub/includes/models/Order.php`

Implement methods for:
1. `create($customerId, $merchantId, $itemsArray, $totalAmount, $pickupAddress, $deliveryAddress, $notes)` - Creates the main order entry with a unique randomized alphanumeric order_number, and loops through the items array to safely insert items into `neighborhub_order_items`.
2. `getOrderById($orderId)` - Fetches the full order context along with its line items.
3. `acceptDeliveryJob($orderId, $courierId)` - This MUST implement our exact SQLite Atomic Update Protocol to prevent race conditions:
   - Call `$GLOBALS['db']->exec("BEGIN IMMEDIATE TRANSACTION;");`
   - Read the current state and courier assignment of the order.
   - Verify that state === 'READY_FOR_PICKUP' AND locked_by_courier_id IS NULL.
   - If conditions pass: Perform the UPDATE setting state='IN_TRANSIT', locked_by_courier_id=$courierId, and execute a `COMMIT;` returning true.
   - If conditions fail: Execute a `ROLLBACK;` and return false.

CRITICAL DIRECTIVE: Write out every single line of code from the opening `<?php` tag to the end. Do not use shortcuts, abbreviations, or summaries (`// ... code stays the same ...`). Write full, error-handled production logic.