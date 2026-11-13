# Session notifications

Use `session_notify($message, $type)` with an active session to queue plain-text
notifications. Supported types are `success`, `info` (the default), `warning`,
and `error`. Messages are shown as dismissible in-page notices on the home page
or customer dashboard and are consumed on the first such page render.

The customer dashboard also renders the existing single `notification` session
entry used by checkout, preserving checkout's cart-clearing behavior. A successful
merchant application queues a confirmation before redirecting to that dashboard.
The application still requires manual merchant provisioning.
