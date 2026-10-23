# NeighborHub Menu Visibility and Status Toggles

## Goal

Allow merchant owners/staff to activate or deactivate menus and menu categories from the merchant dashboard. Customers must only be shown active menus and active categories within active menus.

## Implementation status

The availability controls, authorized status APIs, active-only storefront filters, invalid-menu fallback, customer builder API check, and checkout revalidation are implemented. No schema change was needed because the existing `is_active`, category `status`, product availability, and menu-item availability fields are sufficient. Merchant management queries continue to include inactive records.

## Status behavior

- Treat `neighborhub_menus.is_active` as the authoritative menu visibility flag.
- Treat `neighborhub_menu_categories.status` as the authoritative category visibility flag, with only `active` visible to customers.
- Keep dashboard state and API responses consistent with these persisted fields.
- A category is customer-visible only when its own status is active and its parent menu is active.
- A product assignment is visible only through an active menu/category assignment and when the product and menu-item availability flags are active.
- If a product has multiple menu assignments, an active assignment may show it even if another assignment is inactive; inactive assignments themselves are never rendered.
- Customers selecting a menu explicitly by URL must not be able to load an inactive menu. Choose the first active menu when the requested menu is missing/inactive; show a clear empty state if no active menus exist.

## Implementation scope

1. **Dashboard controls**
   - Load menu `is_active` and category `status` explicitly for the dashboard controls.
   - Wire both switches using `mb.ajax()` and the existing NeighborHub API conventions.
   - Show pending/error feedback; restore the previous switch state if the API rejects the change.
   - When a parent menu is inactive, disable its category switches in the UI without changing the stored category statuses.

2. **Server-side status updates**
   - Persist menu toggles through `is_active` and validate status input as a strict boolean/active-inactive value.
   - Validate category transitions against the category's parent menu.
   - Enforce CSRF and authenticate each mutation.
   - Verify owner/active staff authorization for the merchant owning the menu/category; do not accept a client-supplied merchant ID as proof.
   - Return structured success/error responses and preserve stored category status when its parent menu is toggled.

3. **Customer-facing data**
   - Apply active menu, category, product, and menu-item predicates in the shared model/data access methods used by the generic merchant storefront.
   - Select only active menus when building the menu picker and category navigation; resolve inactive or invalid requested menu IDs safely.
   - Audit custom merchant storefront templates and their data sources so bespoke pages do not bypass the shared visibility rules.
   - Apply the same visibility policy to customer-facing API/product queries used to render or add menu items, not just to initial HTML output.
   - Keep merchant management views able to see inactive menus/categories and their products so the merchant can reactivate them.

4. **Tests and documentation**
   - Add unit/integration coverage for active menu/category combinations, inactive parent menus, products assigned to both active and inactive categories, and empty storefronts.
   - Cover menu/category status updates for owners and active staff, and rejection for unrelated or inactive staff.
   - Cover attempts to request inactive menu/category products through URLs and customer-facing API endpoints.
   - Update relevant NeighborHub developer documentation and schema notes if needed.

## Acceptance criteria

- Merchant switches reflect persisted status after page reload and report failures without leaving optimistic state behind.
- Deactivating a menu hides all its categories and products from customer-facing views and APIs without changing category statuses.
- Deactivating a category hides its category navigation and assigned products from customer-facing views and APIs.
- Reactivating a menu/category restores eligible products without requiring products to be re-created or re-assigned.
- A product assigned to multiple locations remains visible only through its active assignments.
- Merchant owners and active staff can update only menus/categories belonging to a merchant they are authorized to manage.
- Customers cannot access inactive menu/category products by changing request parameters or calling an API directly.
- Existing product availability and menu-item availability behavior remains in effect.
- Merchant management continues to display inactive records so their state can be changed.

## Existing cart behavior

- If a menu/category becomes inactive after its product was added to a customer's cart, checkout must revalidate the assignment and block checkout for that inactive item with a clear explanation. Keep the item in the cart so the customer can remove it or retry after reactivation.
