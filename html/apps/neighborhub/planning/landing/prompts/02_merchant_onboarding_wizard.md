# Task Prompt: 02_merchant_onboarding_wizard.md
# Goal: Build Multi-Step Merchant Onboarding Registration Endpoint & View Wizard

## Context & Requirements
We are building a multi-step onboarding wizard for new merchants (`/partner` or `/merchant/register`). 

Key database, role, & framework rules:
1. **Framework AJAX Standard**: ALL front-end API communication MUST use `mb.ajax()`. Do NOT use raw `fetch()` or `$.ajax()`.
2. **Merchant Record Creation**: Populates `neighborhub_merchants`. Note that spatial `location` point generation is already handled automatically in `Merchant::create()` when `latitude` and `longitude` are passed.
3. **Staff Assignment**: Automatically inserts a bridge record in `neighborhub_merchant_users` assigning the registering user's `user_id` as `staff_role = 'owner'`.
4. **Defaults**: Set `platform_fee_rate = 0.04`, `platform_flat_fee = 0.00`, `delivery_assignment_mode = 'auto'`, and `delivery_max_distance = 7.00` if not overridden.

---

## Instructions

### Task 1: Create Onboarding API Route / Controller
Create or update the endpoint handler (e.g., `?api=neighborhub&action=register_merchant`):
1. **Authentication Check**: Verify the current session user is logged in. If unauthenticated, prompt account creation/login first.
2. **Input Validation**:
   * Required fields: `business_name`, `address`, `latitude`, `longitude`, `phone`.
   * Optional fields: `store_hours`, `delivery_max_distance`, `website`, `facebook`, `type`, `meta`.
3. **Record Creation Workflow**:
   * Call `Merchant::create($data)` with validated payloads.
   * Call `Merchant::addStaffMember($merchantId, $userId, 'owner')` to bind the registering user as the merchant owner.
4. **JSON Response**: Return the created merchant object/ID and initial onboarding completion status.

### Task 2: Build Multi-Step Onboarding Front-End Wizard (`/partner`)
Build a 4-step interactive front-end wizard:

* **Step 1: Business Details**
  * Business Name, Phone Number, Business Category/Type.
* **Step 2: Location & Delivery Radius**
  * Interactive map / address search autocomplete (Google Places or Mapbox).
  * Allow dragging/dropping a pin to set precise `latitude` and `longitude`.
  * Input/Slider for `delivery_max_distance` (in miles).
* **Step 3: Operating Hours & Settings**
  * Store hours configuration and `delivery_assignment_mode` (`auto` vs `manual`).
* **Step 4: Payout Setup & Submission**
  * Hook for Stripe Connect onboarding link/button.
  * Form submission using `mb.ajax()`:
    ```javascript
    mb.ajax({
      url: "?api=neighborhub&action=register_merchant",
      method: "POST",
      data: JSON.stringify(formData),
      success: function (response) {
        if (response && response.success) {
          window.location.href = "/merchant/dashboard";
        } else {
          self.showError(response.message || "Failed to register merchant.");
        }
      },
      error: function () {
        self.showError("Failed to communicate with service pipeline.");
      }
    });
    ```

