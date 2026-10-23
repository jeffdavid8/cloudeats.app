# Neighborhub Merchant Sandbox Mode

Merchant sandbox mode lets an owner or platform admin place test orders without starting a Stripe Checkout Session. The normal server-side catalog validation, tax, delivery, tip, and processing-fee calculations still apply; the displayed processing fee is simulated and no payment is collected.

Sandbox orders are marked on `neighborhub_orders.sandbox_mode` at creation, so changing the merchant setting later does not change existing orders. The order flag is part of the Neighborhub installer schema. Deployments must have that schema before enabling the feature.

Sandbox orders use the regular pending-confirmation, merchant fulfillment, and courier delivery workflows. Customer, merchant, courier, printed-ticket, and report views identify sandbox orders. Sandbox orders do not contain a Stripe payment intent and report a zero estimated merchant payout.

The merchant-management setting is available to merchant owners and platform admins. The API enforces these permissions; staff access to ordinary merchant settings does not grant permission to toggle sandbox mode.
