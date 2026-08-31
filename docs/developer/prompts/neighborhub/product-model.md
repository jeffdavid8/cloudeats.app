================================================================================
PROMPT: THE NEIGHBORHUB PRODUCT MODEL BLUEPRINT (PRODUCT.MODEL.PHP)
Act as a Principal Software Engineer and Domain Architect. Write the complete, production-grade object domain model file:
/html/apps/neighborhub/models/product.model.php

Parameters: 
db_engine = sqlite3

This class handles individual product catalog objects, pricing schemas, inventory tracking, and merchant menu listing relations inside the Neighborhub app space.

It must construct:

The Class Foundation (class Product):

Implement static accessors to interface seamlessly with the framework's primary database link (App::getInstance->db).

Product::getProductsByMerchant($merchantId, $activeOnly = true) Method:

Prepares and executes a query against the neighborhub_products table.

If $activeOnly is true, filter rows by WHERE merchant_id = ? AND status = 'active'.

Returns a structured array of associative data rows, or an empty array if no items are found.

Product::getProductById($productId) Method:

Queries neighborhub_products to safely pull an individual inventory item row matching WHERE id = ?.

Returns the associative row array, or false on an exceptional throw.

Product::create($merchantId, $data) Method:

Wraps database execution inside a localized try/catch layout block.

Extrapolates attributes from the $data matrix: title, description, price (float decimal), image_url (optional string link), and inventory_count (integer).

Inserts the record directly into the product ledger table, defaulting status to 'active', and writing standard execution timestamps (created_at, updated_at).

Returns the freshly generated auto-increment primary key lastInsertId() upon a successful save.

Product::updateInventory($productId, $quantityAdjustment) Method:

Executes an atomic database math modification statement:
UPDATE neighborhub_products SET inventory_count = inventory_count + ?, updated_at = datetime('now') WHERE id = ?.

This ensures safe concurrent ordering operations without risking race condition stock calculations.