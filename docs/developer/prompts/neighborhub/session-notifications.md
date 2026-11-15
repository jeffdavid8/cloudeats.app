# Session notifications

Use `session_notify($message, $type)` with an active session to queue plain-text
notifications. Supported types are `success`, `info` (the default), `warning`,
and `error`. Messages are shown as dismissible in-page notices by the shared page
controller and consumed when rendered.

The component also renders any legacy single `notification` session entry.
Checkout uses a separate request-scoped order-saved flag to control cart clearing,
independently of the notification queue. A successful
merchant application queues a confirmation before redirecting to that dashboard.
The application still requires manual merchant provisioning.
