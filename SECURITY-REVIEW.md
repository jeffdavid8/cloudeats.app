# Vault Security Review

**Review date:** 2026-11-08  
**Scope:** Focused review of Vault transfers, payouts, Stripe Connect onboarding,
and their authentication and CSRF boundaries. This is not a comprehensive
security audit of the application.

## Summary

| Severity | Finding | Confidence | Status |
| --- | --- | --- | --- |
| High | Vault transfers can create unbacked credits that the payout endpoint may convert into real platform funds | 10/10 | Open |

Real-money payouts should remain disabled until the ledger and Stripe payout
flow have been secured and reconciled. This report does not implement that
restriction or remediate the finding.

## High: Transfers can create unbacked credits

The [transfer API](html/apps/vault/vault.api.php) checks that a recipient ID
is present and that the amount is greater than zero, but
[`Vault::transfer`](html/apps/vault/includes/models/vault.model.php) records
the sender's debit and recipient's credit without checking the sender's
available balance. An authenticated user can therefore send more than their
balance, including to another account they control.

[`Vault::get_balance`](html/apps/vault/includes/models/vault.model.php) counts
the recipient's credit as available balance. The Vault payout API checks that
balance before attempting a Stripe transfer from the platform to the user's
connected account. If the payout path is operational and Stripe accepts the
transfer, unbacked credits can therefore expose real platform funds.

**Recommended remediation:**

- Validate amounts as finite, positive monetary values and use integer minor
  units for money.
- Enforce sufficient funds inside an atomic transfer transaction so concurrent
  requests cannot spend the same balance. Apply the invariant to all callers
  of the shared transfer method, not only the API.
- Prevent existing unbacked or virtual credits from being treated as
  withdrawable money without a separately verified funding and reconciliation
  process.
- Add regression tests for insufficient funds, concurrent requests, invalid
  amounts, and payout eligibility.

## Additional observations

- The Vault transfer and payout cases lack explicit authentication checks.
  The normal API route checks a session-bound CSRF token, but CSRF protection
  is not a substitute for authentication. The review did not confirm an
  anonymous exploit. Add explicit authentication and authorization checks.
- The [BANK OUT UI](html/apps/vault/views/pages/lobby.php) posts to the
  Neighborhub API, while the payout handler is in the
  [Vault API](html/apps/vault/vault.api.php). This routing mismatch is a
  functional issue, not a security control: the Vault endpoint remains
  reachable directly.
- The review did not find Vault- or Stripe-specific tests covering transfer
  balance enforcement or payout authorization.

## Follow-up

Fix and independently verify the finding before enabling real-money payouts.
Review Stripe eligibility and obtain appropriate legal advice before offering
transferable or withdrawable stored value. A broader application security
audit remains separate work.
