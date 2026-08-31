Act as a Frontend Engineer. Create a clean, responsive view component file at `/html/apps/neighborhub/views/pages/merchant/products.php` that lets a logged-in merchant manage their inventory catalog.

The interface must be designed completely around MaterializeCSS components, Font Awesome 5 icons, and our platform's custom JavaScript utility framework wrapper: mb.ajax().

Key UI Elements & Design Specifications:
1. Header Section:
   - Display a clear title (e.g., "Manage Product Catalog") alongside a right-aligned action trigger button: `<button class="btn waves-effect waves-light modal-trigger" href="#modal-product-form"><i class="fas fa-plus left"></i> Add New Product</button>`.

2. Product Catalog Accordion:
   - Group products by their 'category' utilizing Materialize Collapsible components (`<ul class="collapsible popout" data-collapsible="accordion">`). Each header should dynamically display the category title, a count of total items, and a Font Awesome folder icon (`<i class="fas fa-folder text-accent"></i>`).

3. Product Cards:
   - Within each collapsible body section, lay out items using the Materialize Grid system (`row` and `col s12 m6 l4`).
   - Style individual items inside Materialize Cards (`class="card hoverable"`) featuring:
     * A card image area (`card-image`) rendering the `image_url` asset string (or a modern asset placeholder image), complete with a floating context edit button tool element (`btn-floating halfway-fab waves-effect waves-light red`).
     * A card content area displaying the product name, details description, and the formatted price highlighted in an accent colored text span element.
     * A card action area (`card-action`) housing a Materialize Switch element for clean availability toggling:
       ```html
       <div class="switch">
         <label>
           Sold Out
           <input type="checkbox" class="availability-toggle" data-id="PRODUCT_ID">
           <span class="lever"></span>
           Available
         </label>
       </div>
       ```
     * A distinct "Delete" icon layout element matching `<i class="fas fa-trash-alt"></i>`.

4. Forms & Interactive Modals:
   - Build a shared Materialize Modal container (`<div id="modal-product-form" class="modal">`) that acts dynamically as both a creation or editing canvas. It should house semantic, responsive input structures (`input-field col s12`) for name, description, category, price, and image URLs. Use standard Font Awesome prefix layout icons next to the inputs.

5. Framework JavaScript integration & mb.ajax() Handlers:
   - Initialize standard elements safely inside a DOMContentLoaded container listener: `M.Collapsible.init(...)` and `M.Modal.init(...)`.
   - Ensure the current page's `merchant_id` context is automatically read and attached to payloads.
   - Attach click handlers to the availability switches. When shifted, use our global `mb.ajax()` handler instead of vanilla fetch() to send a POST payload to `neighborhub.api.php?action=update_product_availability`.
   - Wire form submissions or delete actions directly through `mb.ajax()` targeting the backend routing handles (`create_product`, `update_product`, `delete_product`).
   - On successful transactional loops, use Materialize's native toast framework utility `M.toast({html: 'Success message...'})` to inform the merchant cleanly without losing application focus.

Write out the view code completely with robust variable validation, full error checking, and zero placeholder shortcuts.