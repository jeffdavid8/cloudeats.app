# Neighborhub — Spatial Search & Multi-Tenant Onboarding Architecture

This module implements geospatial proximity search, SRID 4326 point management, and multi-tenant onboarding workflows (Customers, Merchants, Couriers) for the Neighborhub platform.

---

## 🏗️ Architecture Overview

Neighborhub utilizes MySQL/MariaDB spatial GIS features (`POINT` columns, `SPATIAL INDEX`, and `ST_PointFromText`) to handle fast spatial filtering for delivery radiuses and location discovery.

### Key Database Tables & Spatial Fields
* **`neighborhub_merchants`**: Contains `latitude` (`DOUBLE`), `longitude` (`DOUBLE`), and `location` (`POINT NOT NULL SRID 4326`). Indexed with `idx_merchants_spatial`.
* **`neighborhub_couriers`**: Tracks driver position with spatial `location` and `idx_couriers_spatial`.
* **`neighborhub_delivery_tracking`**: Stores real-time order tracking breadcrumbs using spatial `location` and `idx_tracking_spatial`.
* **`neighborhub_merchant_users`**: Role bridge table linking users to merchants (`owner`, `staff`, `delivery`, `screen`).
## ⚡ Front-End Framework Standards

All AJAX requests in front-end scripts MUST use the native framework wrapper `mb.ajax()` instead of raw `fetch()` or jQuery `$.ajax()`.

### `mb.ajax()` Pattern Standard
```javascript
mb.ajax({
  url: "?api=neighborhub&action=action_name",
  method: "POST", // or "GET"
  data: JSON.stringify({ key: value }),
  success: function (response) {
    if (response && response.success) {
      // Handle success payload
    } else {
      // Handle business error state
    }
  },
  error: function () {
    // Handle network / pipeline failure
  }
});
---

## 🗺️ Spatial Coordinates Standard (SRID 4326 / WGS84)

In MySQL/MariaDB spatial functions with SRID 4326:
* **Point Syntax**: `POINT(longitude latitude)` — **Longitude MUST come first**.
* **SQL Conversion**:
  ```sql
  ST_PointFromText(CONCAT('POINT(', longitude, ' ', latitude, ')'), 4326)
🚦 Roadmap & Implementation Milestones
Phase 1: Core Spatial Engine & Model Integration
[ ] Merchant::create(): Generate spatial location point when latitude and longitude are present.

[ ] Merchant::update(): Synchronize location point whenever coordinates are updated.

[ ] Merchant::searchNearby($lat, $lng, $query, $limit): Query active merchants within their delivery_max_distance using Haversine calculation combined with spatial filters.

[ ] Data Migration: One-time script to populate spatial points for existing records.

Phase 2: Landing Page & Location Search Component
[ ] Hero Search Bar: Front-end location input with Places API / Mapbox integration to extract $lat and $lng.

[ ] Cuisine & Category Pills: Fast tag filtering for instant merchant catalog discovery.

[ ] Nearby Merchant Grid: Dynamic rendering of merchant cards showing real-time ETA, distance in miles, and delivery fee.

Phase 3: Multi-Role Onboarding Pathways
[ ] Customer Progressive Auth: Allow menu browsing and cart creation prior to requiring login. Capture delivery address and phone number at checkout.

[ ] Merchant Onboarding Wizard (/partner):

Business profile & address setup.

Map pin drop for precise location coordinates.

Delivery rules (delivery_max_distance, delivery_assignment_mode).

Stripe Connect integration for payout onboarding.

Owner/staff relationship assignment via neighborhub_merchant_users.

[ ] Courier Onboarding (/drive): Vehicle verification, driver application, and location permission consent.

🛠️ API Reference Summary
Search Nearby Merchants
Endpoint: GET /api/merchants/search

Query Params:

lat (float, required): User latitude

lng (float, required): User longitude

q (string, optional): Search keyword (business name, product, or tags)

Response: List of active merchants ordered by distance_miles ASC, filtered by distance_miles <= delivery_max_distance.

📁 Related Prompt Instructions
Refer to the /prompts/ directory for individual task instructions when implementing features in VSCode:

01_merchant_model_spatial.md — Detailed prompt for model spatial methods.

02_merchant_onboarding_wizard.md — Detailed prompt for merchant registration wizard.

03_location_search_frontend.md — Detailed prompt for UI components.