# Vault Security Review

**Review date:** 2026-11-08  
**Scope:** Focused review of Vault transfers, payouts, Stripe Connect onboarding,
and their authentication and CSRF boundaries. This is not a comprehensive
security audit of the application.

## Summary

| Severity | Finding | Confidence | Status |
| --- | --- | --- | --- |
| High | Vault transfers could create unbacked credits that the payout endpoint could convert into real platform funds | 10/10 | Mitigated; historical ledger review remains open |

The payout endpoint and BANK OUT control are disabled. They must remain disabled
until the ledger and Stripe payout flow have been secured and reconciled.

## High: Transfers could create unbacked credits

Previously, [`Vault::transfer`](html/apps/vault/includes/models/vault.model.php)
recorded a sender debit and recipient credit without checking available funds.
An authenticated user could send more than their balance, including to
another account they controlled.

[`Vault::get_balance`](html/apps/vault/includes/models/vault.model.php) counts
the recipient's credit as available balance. The previous payout API used that
balance to authorize a Stripe transfer from the platform. Unbacked credits could
therefore have exposed platform funds if Stripe accepted the transfer.

**Mitigations implemented:**

- The shared transfer method validates positive amounts to the cent, locks the
  sender before calculating the balance, uses exact decimal arithmetic, and
  rejects overspending. Invalid or fractional-cent historical ledger amounts
  cause transfers to fail closed and need investigation.
- The payout endpoint rejects requests and the UI explains that bank
  withdrawals are unavailable. This also resolves the misleading misrouted
  BANK OUT control without enabling a payout flow.
- The Vault API requires an authenticated matching session and POST requests.
  Neighborhub order handlers log and report settlement failures explicitly.
- Focused regression tests cover insufficient funds, invalid values,
  concurrent SQLite transfers, and the disabled payout endpoint.

The existing ledger still stores monetary amounts as JSON numbers; this change
does not migrate it to integer minor units or prove that historical credits are
backed by funds.

## Additional observations

- The normal API route checks a session-bound CSRF token; the Vault API now
  separately checks authentication.
- A synthetic test also exercises two competing transfers against a disposable
  MySQL 8 InnoDB database while the sender row is locked. This is not a
  production-load test or a verification of the deployed schema and settings.
- A merchant order can already have changed state before its Vault settlement
  fails. The failure is now reported and logged, but a durable settlement
  retry and reconciliation workflow is still needed.

## Follow-up

Before enabling payouts, independently review the changes, reconcile
historical ledger entries, design durable idempotent Stripe settlement and
recovery, and verify the production database concurrency behavior. Review
Stripe eligibility and obtain appropriate legal advice before offering
transferable or withdrawable stored value. A broader application security audit
remains separate work.

## Production-readiness work in progress

The payout rebuild is intended for verified merchant and courier order earnings,
separate from the general Vault balance. Stripe's
[restricted businesses policy](https://stripe.com/legal/restricted-businesses)
lists peer-to-peer money transmission as prohibited. An account owner must
describe all intended product and money flows to Stripe and obtain a written
determination; a generic Connect integration is not approval. Qualified counsel
must separately assess the relevant jurisdictions, licensing, consumer
protections, safeguarding, and contractual requirements. Neither approval has
been obtained. Do not enable money movement or treat this document as legal
advice.

### Historical ledger

`VaultLedgerAudit::inspect()` performs a preliminary, read-only analysis of
rows supplied by its caller. Synthetic tests cover invalid amounts, broken
transfer pairs, negative balances, and unverified credits. It does not connect
to a database, inspect actual accounts, establish transaction chronology,
match payments or payouts to Stripe, or prove that balances are backed. Existing
JSON entries lack reliable funding provenance, and unmatched credits are
identified as unverified, not presumed fraudulent. An authorized operator must
review the data source and retention/privacy safeguards before inspecting
actual records; no balance changes are authorized by this audit.

### Settlement prerequisites

The agreed direction is:

- Eligible amounts are limited to verified merchant and courier earnings linked
  to paid orders. Daily provisions and user-to-user Vault transfers do not
  become withdrawable. Historical credits without verified provenance remain
  ineligible until reconciled and approved.
- Delivery fee allocation depends on the merchant's delivery assignment mode:
  `manual` credits the merchant and `auto` credits the assigned courier.
  Neighborhub currently includes the fee in the merchant share regardless of
  mode; the new rule requires implementation and tests. The `disabled` mode
  must not result in a delivery fee payout.
- Users may request a partial withdrawal up to their eligible earnings balance.
  Hold unpaid earnings affected by a refund or dispute and send already-paid
  cases for manual review.
- A Stripe Connect transfer sends funds to a connected Stripe account; the
  subsequent bank payout is separate and follows its applicable schedule.

Subject to Stripe and legal confirmation of an approved product design, a
future settlement implementation needs:

- A ledger with immutable, uniquely identified entries in integer minor
  units, explicit provenance, and atomic reservations that cannot spend
  unbacked credits.
- Durable settlement records created before external calls, a persisted
  Stripe idempotency key and request parameters, and states for pending,
  confirmed, failed, and unknown outcomes. Never assume a timeout means the
  transfer failed. Stripe idempotency keys may be pruned after at least
  24 hours; delayed recovery must reconcile with Stripe before retrying.
- Verified webhook signatures, duplicate-event handling, reconciliation of
  Stripe objects and balance transactions, dispute/refund handling, operator
  alerts, and an auditable recovery process.
- Synthetic fault-injection tests and Stripe test-mode end-to-end tests for
  concurrent attempts, webhook replay, timeouts, process crashes, and database
  failures; no live transfers until the design and approvals are verified.

The existing payout API remains disabled. No Stripe settlement implementation
or live account verification was performed as part of this work.
