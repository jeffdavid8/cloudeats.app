# Admin Database Migration Management UI Plan

## Goal

Provide an administrator-only interface to inspect migration state, safely adopt an existing schema baseline, apply reviewed migrations, and inspect execution results. The UI must use the same `DatabaseMigrationRunner` and migration files as `cmd/migrate.php`; it must not add a second migration engine or call the legacy `*_install_db()` hooks.

## Current context

- Supported migration apps are `admin`, `stitch`, and `neighborhub`.
- Migration files live in each app's `migrations/` directory and are PHP source under version control.
- The runner supports status, `up`, baseline adoption, and rollback when a migration defines `down`.
- Baseline adoption currently verifies table/column names and foreign-key relationships. It does not yet verify types, nullability, defaults, indexes, unique/check constraints, spatial definitions, or storage engines.
- `inspectStatus()` reports an absent `mb_schema_migrations` table without creating it; the Admin status API uses this read-only path.
- MySQL DDL may implicitly commit. Runner transactions cannot guarantee rollback of partially completed schema changes.
- The current baseline migrations intentionally have no `down` callbacks. Rollback is therefore unavailable for those baselines.
- Legacy install hooks remain separate and destructive; they must not be invoked by this UI.

## Scope

### Initial UI features

1. **Migration overview**
   - Show app, migration ID, applied/pending state, applied timestamp, and checksum health.
   - Show registered migration-history rows that no longer have a corresponding migration file as an error, not as a normal status.
   - Make unavailable history explicit (for example, “migration history table does not exist”) without creating it during a read.
   - Show the selected database/environment identity, without exposing credentials.

2. **Baseline adoption**
   - Offer adoption only when an app has exactly one eligible baseline, no migration history for that app, and a verifier.
   - Explain that adoption records history only and never runs the baseline `up` callback.
   - Require a POST action with CSRF validation, an explicit confirmation dialog, and an acknowledgement that a backup and full schema review have been completed.
   - Display the tables/columns/foreign keys checked by the verifier and clearly list schema dimensions it does not validate until that verification is strengthened.
   - Adopt one app at a time in dependency order: Admin, Stitch, then NeighborHub.

3. **Apply pending migrations**
   - Display pending migration IDs and the selected app scope before execution.
   - Support applying one app or all apps in dependency order, matching CLI semantics.
   - Require POST, CSRF validation, admin authorization, and confirmation before any DDL runs.
   - Warn when a migration can cause irreversible or partially applied MySQL DDL changes.
   - Return a structured result with the applied IDs and any failed migration. Stop at the first error; do not show a success-shaped fallback.

4. **Execution history**
   - Show applied time and app/migration ID from `mb_schema_migrations`.
   - Show the current migration file checksum and whether it matches the recorded checksum.
   - Provide a bounded, sanitized operation result or audit entry for each request.

### Explicitly out of scope for initial release

- Creating, editing, or deleting migration PHP files through the browser. Migration source is executable code and must remain reviewed in source control.
- Arbitrary SQL execution, schema editing, data restoration, or invoking legacy installers.
- Rolling back baselines or offering rollback where no safe `down` operation exists.
- Scheduling migrations or automatically applying them during page load/deployment requests.

## Design and integration

### Admin page

- Add an admin-only `migrations` page route in `html/apps/admin/admin.app.php`.
- Render a dedicated view under `html/apps/admin/views/pages/`.
- Add a discoverable Admin dashboard/navigation link following existing UI conventions.
- Use MaterializeCSS for layout, cards, tables, badges, dialogs, and responsive behavior; use Font Awesome 5 Free icons where useful and consistent with the existing frontend.
- Keep actions disabled until the server reports the migration system and database state are valid.
- Use the existing `mb.ajax()` JavaScript function for every migration API request. It provides the established CSRF handling; do not use raw `fetch`/XHR for these endpoints or manually duplicate token logic.
- Use POST for all state-changing actions; never perform adoption, apply, or rollback through GET.

### Admin API

Add explicit switch cases in `html/apps/admin/admin.api.php`, with `admin_require_admin()` in every case:

- `migration_status`: read-only app status and migration history.
- `migration_adopt_baseline`: adopt a single app baseline after server-side eligibility and schema verification.
- `migration_apply`: apply pending migrations for one app or all apps.
- Optionally, `migration_operation_status` if long-running work is moved out of the request lifecycle.

Add every mutating action to the API's CSRF-protected action list. Validate app names against the fixed allowlist (`admin`, `stitch`, `neighborhub`); never accept a filesystem path or PHP migration filename from the client. Use HTTP error codes and structured JSON for authorization, validation, conflict, and migration failures.

### Runner prerequisites before UI implementation

1. Make status inspection read-only. Add a status path that can report an absent migration-history table without creating it; use migration history only when it already exists.
2. Expand adoption verification to compare schema attributes needed for safe adoption: table engine, column type/length, nullability, default, primary/unique/index definitions, check constraints, foreign keys, and spatial indexes where applicable. Unknown or unsupported schema features must fail closed.
3. Reconcile baseline definitions with the deployed schema and validate verifiers against a database backup or disposable copy. Do not assume the existing database matches because table names exist.
4. Make failed MySQL DDL outcomes visible and diagnosable. Do not automatically retry partially applied baselines or mark them applied.
5. Ensure the UI and CLI use the same app ordering, checksums, and migration-operation behavior.

## Authorization, safety, and audit requirements

- Admin authorization is enforced server-side for every endpoint; hiding buttons is not authorization.
- CSRF protection is required for adoption and apply operations; frontend calls must use `mb.ajax()`.
- Mutating operations must use POST and reject unknown apps or malformed input.
- Confirm the target environment/database and exact operation before execution. Production-targeted actions require a distinct, prominent warning.
- Never include DB connection credentials or sensitive environment variables in the UI, JSON response, or logs.
- Record actor, timestamp, app, migration IDs, operation, result, and correlation identifier through the project's established logging/audit mechanism. Avoid logging SQL values or secrets.
- Use the runner's migration lock to prevent concurrent actions; report lock contention as an actionable conflict.
- Do not offer rollback for a baseline without `down`. Any future rollback UI must require a safe reversible migration, show its data-loss consequences, enforce dependency ordering, and require explicit confirmation.

## Delivery phases

### Phase 1: Stabilize backend operations

- Implement non-mutating status inspection and history retrieval.
- Strengthen baseline verifiers and add tests for schema matches and each mismatch class.
- Add runner/API result types that distinguish pending, applied, drifted, failed, and unavailable states.
- Confirm the admin/stitch/neighborhub migration sequence and app dependency rules.

### Phase 2: Add read-only Admin screen

- Add route, navigation link, status API action, and table/list presentation.
- Show checksum mismatch, missing migration file, missing history, and pending migrations as distinct states.
- Verify read-only behavior does not create tables or change schema.

### Phase 3: Add guarded operations

- Add adopt and apply API actions.
- Add CSRF-protected confirmation flows, preflight display, operation results, and audit logging.
- Do not add baseline rollback.
- Verify repeated requests are safe: already-adopted apps are rejected or reported as already applied; applied migrations are not rerun.

### Phase 4: Verify deployment readiness

- Test with disposable MySQL databases: empty/fresh schema, exact existing schema, each schema mismatch, missing tables, checksum drift, migration failure, and lock contention.
- Test an anonymized/restored copy of the populated database before any real adoption.
- Compare table/row counts before and after adoption to prove application data is unchanged.
- Review authorization, CSRF, and error responses; test production warning UX.
- Update CLI and migration how-to documentation with the final operational procedure.

## Acceptance criteria

- A normal status visit changes no database schema or migration history.
- An existing database can only be adopted after full schema verification; a mismatch records no baseline and changes no application tables or data.
- Fresh-database migration application and existing-database adoption are distinct, clearly labeled operations.
- Only authenticated administrators can inspect or operate migrations.
- Mutations require CSRF validation and explicit confirmation.
- The UI never invokes the destructive install hooks and never executes browser-supplied SQL or PHP.
- Failures are reported explicitly, include the failed app/migration, and do not falsely indicate success.
- Baseline rollback is unavailable in the UI.

## Implementation status and go/no-go

The first Admin UI implementation is available at `/?app=admin&p=migrations`. It provides read-only status, admin-only API endpoints, CSRF-protected POST operations, a typed-database confirmation dialog, backup/schema-review acknowledgements, operation logging with correlation IDs, and a fresh-baseline table-collision preflight. It does not invoke legacy install hooks or expose rollback.

The current verifier still checks table/column names and foreign keys rather than complete schema equivalence. The UI calls this out and requires an explicit manual full-schema-review acknowledgement, but this is not a substitute for the planned stronger automated verifier. Do not use adoption or apply against a populated/production database until the schema has been independently reviewed, a recoverable backup exists, and the remaining Phase 4 disposable-MySQL checks have passed. No migration or adoption operation has been run against the configured application database during this implementation.
