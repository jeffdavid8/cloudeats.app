# Maintenance Mode

Maintenance mode is enforced at the main request entry point and the legacy
`api.php` endpoint. It returns HTTP 503 for customer-facing pages and APIs,
while authenticated administrators can continue using the app. The admin
control is at `/?app=admin&p=maintenance`.

The admin toggle stores its enabled state, customer message, and optional
estimated return in the shared MySQL `app_system_settings` table. The table is
created by `admin_install_db()` and included in `admin_db_tables()` for backup
and restore.

For an existing deployment, apply the additive SQL migration at
`sql/maintenance-mode.sql` before deploying code that reads the table. The JSON
data import does not create missing tables; it only imports rows into tables
that already exist. Do not run the full `admin_install_db()` installer solely
to create this table: that installer drops and recreates the user and
permission tables. The application database user needs permission to read and
update the settings table, not to create it during normal requests.

Set `MAINTENANCE_MODE=true` or `MAINTENANCE_MODE=false` in the deployment
environment to override the admin setting. When unset, the stored admin
setting applies. Optional `MAINTENANCE_MESSAGE` and `MAINTENANCE_ETA`
environment variables override the stored notice and ETA. Invalid
`MAINTENANCE_MODE` values fail closed with HTTP 503.

The `/healthz` liveness endpoint, login/logout routes, registration
verification, and OAuth callback scripts remain available. A `neighborhub`
`stripe_webhook` POST action is exempted for payment callbacks. The current
application does not contain a production Stripe webhook handler; any such
handler must verify the Stripe signature before processing the event.

Maintenance responses are marked no-store and return an HTTP 503. API
responses use JSON; browser page requests receive a simple HTML notice. When
the setting cannot be read, the request fails closed and the reason is logged.
