# Merchant operational status

The customer header renders the merchant status component when merchant
context is available. The component refreshes through
`get_merchant_operational_status` immediately, every 60 seconds, and when
the tab becomes visible. It uses `mb.ajax()` for the session CSRF token. A
failed refresh shows an unavailable state and disables storefront add actions
until a successful refresh. Failed refreshes retry after five seconds, with
exponential backoff up to one minute; normal minute polling and visibility
refreshes still apply. Published storefront visibility and draft preview
can hide the banner without changing ordering eligibility.
The merchant logo's green dot appears only while the calculated operational
status is open. It follows status refreshes even if the banner is hidden,
and disappears if a refresh fails.
Time until closing is shown in hours and minutes, omitting zero units; the
closing-soon threshold remains 30 minutes.
Status refresh GET requests are not counted as page views. After CSRF
validation, the read-only status handler releases the session lock before
querying the merchant, so refreshes do not block other requests in the session.

The API and checkout use the same server-side merchant status and schedule
calculation. A merchant must be active or online and within its listed hours
to start checkout, including in sandbox mode. Missing or invalid hours are
treated as closed. Schedule evaluation uses the application's server timezone
(`America/Indiana/Indianapolis` in the web entry point); individual merchant
timezones are not currently stored.

The banner emits `nh:merchant-status` with the merchant ID, status, open flag,
and stale flag. Consumers can use that event without duplicating schedule
calculations. Server-side checkout validation is authoritative; the banner
does not reserve a merchant's availability for a later checkout attempt.
