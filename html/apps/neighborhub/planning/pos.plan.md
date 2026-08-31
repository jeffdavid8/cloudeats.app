You are an expert full-stack developer specializing in PHP, MySQL, JavaScript, and Materialize CSS frameworks. We are building a high-efficiency Merchant Point of Sale (POS) Counter Checkout View for an application platform.

CONTEXT:
Our system is a multi-tenant platform. We have a product catalog (`neighborhub_products`) where products are owned by a `merchant_id`. Some products use an advanced customization template (stored as JSON in a `meta` column) allowing for dynamic modifiers/pricing. Orders are recorded in a central `neighborhub_orders` table, and individual lines are mapped in a `neighborhub_order_items` junction table.

GOAL:
Provide the production-ready code modifications to implement an in-store counter checkout register terminal. This terminal will allow merchant employees to tap products on a touch-grid menu, choose customizations using our existing modal framework, manage a running ticket tape, calculate an 8.25% sales tax precisely, and save the transaction directly to the database as an immediately finalized transaction when paid via Cash or an External Card Terminal.

TASK 1: DATABASE INITIALIZATION STRING ALTERATION
Modify our table creation query string for `neighborhub_orders` to add these two native columns securely:
1. `order_type` VARCHAR(30) (Default 'ONLINE_DELIVERY'; can also accept 'WALK_IN' or 'STORE_PICKUP').
2. `payment_method` VARCHAR(30) (Default 'STRIPE'; can also accept 'CASH' or 'EXTERNAL_CARD').

TASK 2: REQ-TO-ROUTE ENDPOINT FOR `neighborhub.api.php`
Write a clean, secure backend handler block to be placed inside our main action switch matrix:
- Case name: `create_pos_order`
- It must accept `merchant_id`, `payment_method`, and an array of selected `items`.
- It must include authorization protection: Check if `validateMerchantStaff($_SESSION['user']['id'], $merchantId)` is true. If not, reject with HTTP 403.
- It must iterate through the submitted items array, pull fresh, pristine rows from `neighborhub_products` to verify the item pricing safely on the server side, compute modifier additions if customizations are present, and track the running subtotal.
- Calculate an 8.25% sales tax using `round($subtotal * 0.0825, 2)`.
- Wrap the execution in a PDO database transaction block (`$db->beginTransaction();`).
- Write a record into `neighborhub_orders` with a state of 'PAID_RETAIL', `order_type` as 'WALK_IN', and the verified financial fields. Set `customer_id` to NULL (since counter retail traffic is anonymous walk-in).
- Write individual line rows into `neighborhub_order_items`, ensuring `unit_price` holds the final customized item cost, and `customizations` saves the serialized JSON choices context.

TASK 3: FRONTEND USER INTERFACE LAYOUT (HTML & MATERIALIZE CSS)
Provide an elegant, high-contrast, touch-optimized split-pane interface:
- Left Sidebar (4-columns wide): Displays the running ticket text tape, line item quantities, individual costs, delete buttons, dynamic tallies for Subtotal, Sales Tax, and Total, along with two large, full-width touch buttons for "CASH" and "CARD".
- Right Grid Panel (8-columns wide): Loops through a provided `$products` array and displays large, accessible tap cards showing the product name and price. Tapping a product card must trigger a JavaScript click event handler.

TASK 4: STOREFRONT CONTROLLER & AJAX DISPATCH (JAVASCRIPT)
Provide the accompanying frontend JavaScript logic to manage state on-screen:
- Maintain a local in-memory array tracking items on the active ticket: `let posTicket = [];`
- Write a function `handlePOSProductTap(id, name, price)` that increments the item's quantity if it already exists on the ticket, or adds a clean line object if it is new, then refreshes the ticket rendering viewport.
- Write a `renderPOSTicket()` function that dynamically updates the ticket container HTML, multiplies line items by their quantities, formats calculations precisely using `.toFixed(2)`, and updates the Subtotal, Tax, and Grand Total DOM indicators.
- Write a `submitPOSTicket(paymentMethod)` function that fires an AJAX post request (`mb.ajax`) to our newly created API endpoint (`?api=neighborhub&action=create_pos_order`). On success, it must show a Materialize success toast notification and entirely clear out the current on-screen ticket state matrix ready for the next customer in line.

Please write clean, self-contained code blocks with explanatory documentation so that I can copy, paste, and execute this integration immediately.