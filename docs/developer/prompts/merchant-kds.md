You are an expert POS (Point of Sale) and KDS (Kitchen Display System) engineering specialist. We are building a high-volume, enterprise-grade restaurant Kitchen Display System based on a single-file PHP/JS view template. 

Review the attached `merchant_dashboard.php` source file carefully. It utilizes a custom AJAX module (`mb.ajax()`), a WebRTC data channel mesh (`window.merchantMesh`), MaterializeCSS elements, and a memory-safe `MutationObserver` layout mapping physical KB9000 kitchen bump bars (keys 1-9) directly to order cards.

#### The Goal:
Transform this view file into an elite, highly durable kitchen operating system modeled after top-tier enterprise KDS software (like Toast, Elo Touch, or QSR Automations). It must be optimized for fast-paced, high-heat environments where staff interact using greasy hands, physical bump bars, or rugged touchscreens.

Please refactor and modify the attached code to implement the following critical enhancements, keeping all changes fully functional inside the existing framework constraints:

### 1. Kitchen Store Control: Online / Offline "Kill Switch"
*   **The Feature:** Add a prominent, high-visibility "Store Status" toggle button directly in the main header layout (next to the `#toggle-kds-mode` button). This allows a manager to open or close the kitchen instantly from this active panel.
*   **UI/UX Integration:** The button must clearly reflect the current real-time state based on the `$merchantProfile->status` property (e.g., a bright green "● STORE ONLINE" state or a flashing/solid red "■ STORE OFFLINE" state).
*   **Implementation:** Wire the button click handler to execute an asynchronous `mb.ajax()` POST request targeting `/?api=neighborhub&action=update_merchant_status`.
    *   Payload format: `JSON.stringify({ merchant_id: <?php echo intval($merchantId); ?>, status: 'active' })` (or `'inactive'`).
    *   On a successful response, use Materialize `M.toast()` to confirm the change database update and alter the button's layout styling dynamically without forcing a hard browser page refresh.

### 2. Auto-Adapting Flex-Grid Layout (Portrait vs. Widescreen)
*   Remove the rigid 2-column limitation (`.nh-grid-2`) inside the `body.kds-fullscreen-mode` stylesheet block.
*   Implement an intelligent responsive grid layout using `grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;`.
*   **Portrait Optimization:** If the physical monitor layout is rotated 90 degrees to a vertical portrait rail orientation, the grid should cleanly fall into a high-density 2-to-3 column stack with deeply visible scrolling cards.
*   **Landscape Optimization:** If the screen is widescreen landscape, it should automatically maximize horizontal real estate, fitting 4 to 6 order cards cleanly across a single row without leaving wasteful empty spaces or forcing giant stretched boxes.

### 3. Structural Order Card Harmonization & Standardization
*   **The Issue:** Right now, the Pending loop injects raw pre-rendered `$order['html']` from a helper, while Confirmed and Ready sections loop out hardcoded HTML templates inside this file. This causes broken styles and structural mismatch errors when the bump bar tries to target action buttons safely.
*   **The Fix:** Unify the card layouts. Extract a single, highly readable HTML card template structure to use across ALL three functional columns (Pending, Confirmed, Ready). For reference, these card view partial definitions are located inside the `html\apps\neighborhub\views\components\cards` directory. If the Pending row cannot drop `$order['html']` immediately, provide a clean fallback container wrapper that standardizes the expected class nodes, explicitly exposing the `.nh-card-footer button:not([disabled])` action button so the bump bar index calculation never fails.
*   **Required Markup Data Attributes:** Ensure every single order card explicitly includes:
    *   `data-order-id="<?php echo intval($order['id']); ?>"`
    *   `data-created-at="<?php echo $order['created_at']; ?>"`

### 4. High-Stress Kitchen Readability UI Updates
*   **Font Scaling & Spacing:** In `body.kds-fullscreen-mode`, push typography to extreme accessibility sizes. Line items (items ordered) must be bold and highly legible. Non-essential operational text (like billing details or full text billing addresses) should be minimized or completely hidden to preserve vertical layout space.
*   **Order Type/Channel Indicators:** Provide clear visual badges or color tags for order source handling (e.g., "DELIVERY" vs. "PICKUP").
*   **Line Item Interactivity (Touch-to-Cross-Off):** Add a simple toggle feature for line items inside the ticket body. When a chef taps a text item, toggle a `.kds-item-done` class (applying line-through text or opacity dimming) so line-cooks can track complex dishes as they plate them.

### 5. Hardened Keyboard & Bump-Bar Shortcutting
*   Review our leak-safe `MutationObserver` block. Preserve its memory-safe loop prevention mechanics (.disconnect() / .observe() wrapping logic), but expand it to intercept the following common kitchen hotkeys:
    *   `Key0` or `Escape`: Instantly silences the active playing kitchen alarm audio loop (`kitchenAudioLoop`) without needing a manual mouse click.
    *   `Tab`: Toggles the KDS Fullscreen presentation layout mode (`body.kds-fullscreen-mode`) automatically.

### 6. Resilient Dynamic State Transitions
*   Review `animateOrderStateTransition(orderId, newStatus, incomingHtml)`. Make sure its sliding/fading animation states do not cause visual glitches or element duplication when changing columns.
*   When a new order arrives dynamically and triggers `PENDING_CONFIRMATION`, ensure it correctly hooks into `refreshBumpBarSlots()` so its slot badges update instantly without needing a hard browser page refresh.
*   **Prevent Polling Collision Race Conditions:** Ensure that when an order state transition is animated locally via a bump bar click or WebRTC mesh notification, the local UI state update takes precedence. The polling layout data array must not overwrite or duplicate an order card that is actively undergoing a transition animation.

### 7. Code Integrity & Clean Outputs
*   Return the complete, fully updated `merchant_dashboard.php` file contents.
*   Do not leave placeholder comments like `// your code here` or omit any existing sections (such as styles, staff tables, or dashboard parameter sections). Keep all backend PHP variables (`$merchantProfile`, `$pendingOrders`, etc.) intact and active exactly as originally declared.

### 8. System Architecture Reference Context

To ensure your code matches our backend routing and component styling perfectly, use these structural footprints:

* **API Mapping Reference (`neighborhub.api.php`):**
  Our API endpoints handle incoming `mb.ajax()` payloads using raw JSON streams. Ensure your JavaScript payload targets `action=update_merchant_status` and matches this internal parsing structure:
  ```php
  // Expects: { merchant_id: INT, status: 'active'|'inactive' }
  ```

* **Polling Loop Blueprint (`polling.js`):**
    For context, our background state engine runs on `NeighborhubPoller`. It periodically sweeps the active merchant queue to catch missing items. Ensure your state updates clean up or sync with this global instance smoothly:
    ```javascript
    // window.neighborhubPoller tracks the current active state 
    // and loops over endpoints to sync state. 
    // Ensure card insertions/removals update any internal track arrays if present.
    ```