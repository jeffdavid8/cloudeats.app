================================================================================
PROMPT 4: FRONT APPLICATION FRONT-CONTROLLER ENGINE (PHASE 1 & 7)
================================================================================

Act as a Core System Architect. Write the complete code for our master app interface manager:
`/html/apps/neighborhub/neighborhub.app.php`

This script serves as the main application gateway inside our environment and must expose the three standard structural interface hooks:
1. `neighborhub_info()` - Returns an array of application metadata attributes (title: "Neighborhub", requires_auth: true, styles/scripts configuration targets).
2. `neighborhub_init(&$app)` - Processes routing variables. Defaults the page pointer to `dashboard` ($page = get_var('p', 'dashboard')) and the layout context view parameter to `customer` ($view = get_var('view', 'customer')).
3. `neighborhub_render_body()` - Evaluates the resolved active view layer context and performs our Fluid Permission Matrix verification against the logged-in session profile (`$_SESSION['user']`).

Implement the precise step-by-step verification rules for fluid multi-role context shifting:
- If view='customer': Load normal customer dependencies and render the customer panel layout.
- If view='merchant': Pull the target merchant ID context. Query `neighborhub_merchant_users` where user_id = ? and merchant_id = ? and status = 'active'. If matched, commit the clear scope badges to the active session parameters (`$_SESSION['user']['active_merchant_id']` and `$_SESSION['user']['merchant_staff_role']`). If unverified, drop the session context flags, render a graceful access denial notice alert banner, and fall back safely to customer view context.
- If view='courier': Confirm user registration within `neighborhub_couriers`. If verified, permit access to the courier terminal panel.

At the top of the initialization stack, explicitly execute an inline SQLite configuration call: `$GLOBALS['db']->exec("PRAGMA foreign_keys = ON;");` to guarantee relational referential consistency. Output the complete source file directly with zero compression.