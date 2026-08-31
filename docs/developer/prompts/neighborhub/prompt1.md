Please update Phase 1, Phase 3, and Phase 5 of our `plan.md` to support secure merchant staff management.

1. Add a new bridge table to Phase 3: `merchant_users` with foreign keys referencing `merchants` and `users`, including a `staff_role` enum ('owner', 'clerk').
2. Update Phase 1 Role Detection logic: When switching to the merchant view, verify the user's presence in `merchant_users`. If valid, store `active_merchant_id` in the session. If invalid, fall back safely to the customer view with an error toast.
3. Update Phase 5 API Layer: Ensure all merchant actions (`confirm_order`, `assign_courier`, etc.) validate that the active session's `user_id` has a valid staff record matching the order's target merchant.