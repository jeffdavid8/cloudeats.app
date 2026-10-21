# NeighborHub Reporting and Usage Analytics Plan

## Goal

Add role-appropriate order views and CSV/JSON downloads for NeighborHub's admin, merchant, courier, and customer experiences. Capture NeighborHub business-usage events and provide admin-wide and authorized merchant-scoped analytics views and exports.

## Decisions and scope

- Order reporting covers orders and their line items. It does not add general customer, courier, or merchant profile exports in this phase.
- Reports and exports are available within each existing NeighborHub role context:
  - **Admin:** view and export all in-scope orders, line items, and NeighborHub analytics.
  - **Merchant:** view and export orders and attributable usage analytics only for a merchant account where the signed-in user is the owner or active staff member.
  - **Courier:** view and export only assigned deliveries/orders.
  - **Customer:** view and export only the signed-in customer's orders.
- Analytics capture business events, not generic page-view/navigation events. Admins may see all analytics; authorized merchant owners/staff may see analytics scoped to the selected merchant.
- For merchant analytics, use the `merchant_id` in the URL only as a requested selection. Resolve and authorize it server-side against the signed-in user's owner or active staff relationship; do not trust the URL value or active-merchant session value as authorization. Apply the same check to the report page, API reads, and downloads.
- Merchant analytics include only events attributable to that merchant. Global discovery/search events that cannot be unambiguously assigned to one merchant remain admin-only and are excluded from merchant totals and exports.
- Avoid collecting raw IP addresses, addresses, payment identifiers, contact details, or raw search text in usage events. Use the minimum identity/context needed for reporting and avoid exposing user-level detail in analytics views.
- Retain analytics events for 90 days and limit analytics views/exports to that rolling window. Do not persist older aggregates in this phase.
- Show funnel values as event counts, not unique-user conversion rates. Merchant payout values are estimates from amounts recorded at checkout and may differ from settlement adjustments.
- Do not expose analytics tied to other merchants or identify individual customers in merchant-facing totals, event details, or downloads.
- Use a new plan and feature scope; do not replace the existing NeighborHub architecture plan.

## Existing implementation and constraints

- The live schema is created in `neighborhub_install_db()` in `neighborhub.app.php` and uses MySQL/InnoDB. The older `neighborhub.plan.md` describes SQLite and is not authoritative for this feature.
- NeighborHub order, customer, courier, and merchant access patterns live in the existing models under `includes/models/`. Resolve role identities server-side: customer profile from the logged-in user, courier profile from the logged-in user, and merchant access from the user's active merchant/staff relationship.
- `neighborhub.api.php` already routes an `export_data` action to `handle_export_data()`. Audit and refactor this route rather than creating a competing export mechanism. Its existing report-type queries apply role scoping inconsistently; authorization and row scoping must be enforced for every report type, not based on client-supplied IDs.
- The site-wide `AnalyticsService` already records visitor/page and other general analytics to JSON. It does not provide the NeighborHub business-event reporting described here. Keep NeighborHub events distinct and structured; reuse existing utilities only where that does not mix the two data domains.
- Add the analytics table definition, including nullable merchant attribution and its index, to `$tableSql` in `neighborhub_install_db()`. The user confirmed this is a development environment where the installer will be rerun and its tables recreated; the installer may drop and recreate `neighborhub_usage_events`. A subsequent JSON restore can repopulate events present in the backup; older event rows without `merchant_id` remain admin-only.
- Important deployment constraint: `neighborhub_install_db()` currently drops and recreates the core NeighborHub tables. The deployment uses the NeighborHub JSON backup flow before running the installer, then restores the saved data. `BackupManager` must export every live NeighborHub data table and restore only tables present in the backup; absent keys must not clear destination data. Document and verify this backup/install/restore sequence, including table counts and relationships. The installer must not drop the new analytics table, so existing analytics records survive installer runs; on first deployment the new table starts empty.

## Functional design

### Role-specific order reports

Add a reports page/section to each existing role context, following the current `view` and `p` routing conventions in `neighborhub.app.php`. Reuse shared report controls and presentation where practical, while keeping each view's data and available fields appropriate to that role.

Provide:

- A paginated order list with a date range and order-state filter.
- An order detail/line-item view using the existing order and order-item relationships.
- CSV and JSON downloads that apply the exact same filters and authorization scope as the displayed report.
- Clear empty-result and invalid-filter feedback.

Use a server-derived authorization scope for every read and export:

| Role | Required scope |
|---|---|
| Admin | All NeighborHub orders |
| Merchant | Orders for a merchant where the user has authorized active access |
| Courier | Orders assigned to the courier profile belonging to the signed-in user |
| Customer | Orders belonging to the customer profile belonging to the signed-in user |

Never trust a submitted role, user ID, customer ID, courier ID, or merchant ID as proof of access. If an admin can select a merchant, validate that filter as an admin-only capability. Export only fields needed for the report and the user's role; do not include unnecessary addresses, contact details, payment tokens, or internal metadata.

### NeighborHub business-event analytics

Add a dedicated NeighborHub event-recording path and admin-wide plus merchant-scoped reporting/export. Use the existing MySQL/InnoDB database for structured event records; do not treat page visits from the global visitor service as NeighborHub business events.

Start with events that answer product and operational questions:

- Merchant or product discovery/search, recording event counts and safe categorical context but not raw search text.
- Product views and cart item additions/removals or quantity changes.
- Checkout started, completed, or failed, without payment credentials or sensitive failure payloads.
- Order created, cancelled, and successful order-state transitions.

Capture events at trusted server-side action boundaries where possible. Record lifecycle events only after the corresponding operation succeeds, and avoid duplicate events when an operation is retried. Keep the event schema minimal: event type and timestamp, role, minimal pseudonymous actor/context identifiers where needed, and narrowly scoped structured properties. Do not store raw IPs, full user agents, delivery addresses, payment identifiers, or free-form notes/searches.

The admin analytics page should show date-filtered system-wide event totals and useful aggregates such as discovery-to-checkout/order funnel counts, order-state transitions, and event trends. An authorized merchant report page should show the same date-filtered analytics only for the selected merchant, limited to attributable activity such as product views, cart activity, checkout, orders, and order-state transitions. Merchant views and CSV/JSON exports must not include system-wide discovery events, other merchants' activity, actor IDs, or customer-level details. Allow CSV/JSON exports to apply the same scope and filters as the corresponding view. Detail-level event data is retained for 90 days; do not persist aggregates beyond the same window in this phase.

Add merchant attribution to usage events using a dedicated nullable `merchant_id` column and index rather than trusting an arbitrary JSON property. Attribute merchant IDs from trusted server-side records where available (for example, the looked-up product or created order). For client-reported cart activity, validate the selected merchant/product context before recording; never use the client value as an access grant. Historical events without merchant attribution are available only to admins and are excluded from merchant-scoped analytics.

### Charts and presentation libraries

- Use Chart.js for analytics charts, matching the existing admin analytics implementation. Reuse the project's current loading approach and avoid adding a second charting library unless implementation requirements demonstrate a concrete gap.
- Query and aggregate analytics data on the server, returning only the filtered series and summary values needed by an authorized admin or merchant view. Charts are a presentation layer; CSV/JSON exports must remain available independently of JavaScript or chart rendering.
- S3 is object storage, not a graphing library, and is not needed for chart rendering or normal downloads. Stream CSV/JSON downloads from the authenticated API. If large/asynchronous exports later require temporary storage, use the existing `FileStorageManager` provider abstraction and short-lived, access-controlled downloads rather than introducing an S3-specific dependency.

### Export behavior

- Keep CSV and JSON field names and filters stable and documented.
- Return a consistent JSON object containing report metadata/filters and a records array.
- Emit CSV column headers even when there are no records.
- Validate date inputs and supported filters, use prepared statements, and enforce sensible result/window limits or streaming for large exports. Do not persist routine report downloads to object storage.
- Protect CSV downloads against spreadsheet-formula injection and correctly encode UTF-8.
- Use Chart.js for admin analytics graphs, matching the existing admin analytics implementation; keep downloads independent of chart rendering.
- Stream routine CSV/JSON exports through the authenticated API; object storage is not required.
- Apply authentication, CSRF protections where required by the existing API convention, and the same server-side authorization checks to both report views and downloads.

## Deployment sequence

1. Deploy the feature code while retaining the existing database.
2. Export a fresh NeighborHub JSON backup using the updated `BackupManager::exportToJson()`; save a copy outside the application database/storage being reinitialized.
3. Verify that the export contains all current NeighborHub tables and records, including customers, menus, images, orders, and order items.
4. Run `neighborhub_install_db()` to recreate the core schema and create `neighborhub_usage_events`. This installer is destructive for the core NeighborHub tables; do not run it before the export is confirmed.
5. Restore the JSON backup with `BackupManager::importFromJsonFile()`. Missing table keys must be skipped, not interpreted as empty tables.
6. Compare pre-install and post-restore row counts and sample IDs/relationships for each table; verify orders and line items are intact and the analytics table exists.

The JSON backup made before the first analytics-table creation will not contain NeighborHub analytics events. This is expected; analytics starts empty on first deployment. Future backups include the analytics table.

## Implementation phases

1. **Audit and design:** Confirm the live schema and role identity joins; inventory current report links and callers of `export_data`; define report columns, event names/properties, and the 90-day analytics window.
2. **Persistence and shared query layer:** Add the analytics table DDL and merchant attribution index to `$tableSql` in `neighborhub_install_db()`. In this development environment the installer drops and recreates the analytics table. Implement shared report queries and role-scope resolution using the existing models and conventions.
3. **Usage-event capture:** Instrument the selected business actions at successful server-side boundaries. Add retention handling for event-level records and aggregate generation/retention as approved.
4. **Role views and downloads:** Add order-report access to admin, merchant, courier, and customer contexts. Refactor the existing export handler to share authorization and filters with the views; add admin-wide and authorized merchant-scoped analytics reports and exports, using Chart.js for visualizations and streamed downloads for exports.
5. **Validation and documentation:** Add focused unit/API/integration coverage, update NeighborHub developer documentation, and verify the documented JSON export, installer, and restore procedure preserves existing production order data, IDs, and relationships.

## Acceptance criteria

- Each role can view and download only its authorized order records; direct API requests cannot expand that scope by changing submitted IDs or role fields.
- Admin order reporting includes all matching records, while merchant, courier, and customer reports are constrained to their own authorized records.
- For a given filter set, the view, CSV, and JSON export contain the same records.
- Both formats handle empty results, date boundaries, special characters, and large result sets safely.
- Analytics reporting and exports reject unauthorized users. Admins can see system-wide analytics; merchants can see only activity attributed to a merchant they own or actively staff, selected by URL `merchant_id` and verified server-side.
- Selected business events are recorded only for successful operations, do not contain the prohibited sensitive values, are not double-counted on retries, and have reliable merchant attribution when a merchant context exists.
- Event-level records older than 90 days are removed; analytics reporting and exports are bounded to the rolling 90-day window.
- The analytics table and merchant attribution index are created by the established NeighborHub schema setup path. The development installer may reset analytics events; deployment documentation covers the JSON export → installer → restore procedure for preserving core order data and relationships.
- Existing supported `export_data` consumers continue to work or are migrated intentionally, with all report types subject to the new authorization rules.

## Privacy and retention defaults

- Only include order fields needed to understand order status, merchant, line items, and financial totals. Exclude customer contact/location data, payment identifiers, and internal metadata.
- Do not persist aggregate analytics beyond the 90-day event window in this phase. Revisit longer-term aggregates only with a separately approved retention period.
