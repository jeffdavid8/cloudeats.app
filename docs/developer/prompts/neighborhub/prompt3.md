================================================================================
PROMPT 3: COURIER & MERCHANT MODELS (PHASE 4 - COURIER.PHP & MERCHANT.PHP)
================================================================================

Continuing within our application architecture framework, act as a Principal PHP Backend Engineer. Write out the full data access class structures for both Merchant and Courier entities inside our system.

Generate the complete contents for these two individual files:
1. `/html/apps/neighborhub/includes/models/Merchant.php`
   - Include methods: `getMerchantById($id)`, `getProductsCatalog($merchantId)`, and `getStaffRelations($merchantId)` querying `neighborhub_merchant_users`.
2. `/html/apps/neighborhub/includes/models/Courier.php`
   - Include methods: `getCourierByUserId($userId)`, `updateLocation($courierId, $lat, $lng)`, and `getAvailableLocalJobs()` which locates all orders within `neighborhub_orders` where state='READY_FOR_PICKUP' and `locked_by_courier_id IS NULL`.

Ensure all data operations explicitly reference the namespaced tables defined in our master plan. Write both classes completely out from scratch with absolute data structure integrity, without any placeholders or code-skipping annotations.