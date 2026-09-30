# Task Prompt: 03_location_search_frontend.md
# Goal: Build Landing Page Hero Location Search Bar & Dynamic Nearby Merchant Grid

## Context & Requirements
We are building the consumer-facing discovery engine on the home page (`/` (html\apps\neighborhub\views\pages\customer\onboarding.php)) leveraging MaterializeCSS and fontawesome v5 free icons. This includes a fast, location-aware search bar and a real-time nearby merchant grid powered by the `GET ?api=neighborhub&action=search_merchants` endpoint.

Key UX & Integration Rules:
1. Full height of page with search centerred on the page style like google home landing search or doordash home landing search 
2. **Browse First (Unauthenticated)**: Allow visitors to enter an address, query nearby merchants, and view menus without requiring a login/signup upfront.
3. **Framework AJAX Standard**: ALL front-end API communication MUST use `mb.ajax()`. Do NOT use raw `fetch()` or `$.ajax()`.
4. **Geocoding & Location Persistence**: Use Google Places Autocomplete or Mapbox Geocoder to extract `$lat` and `$lng`. Persist the selected location (address string, lat, lng) in `localStorage` or session state.
5. **Responsive Grid**: Render merchant cards showing store image, business name, distance in miles, delivery fee, and live open/closed status.

---

## Instructions

### Task 1: Address Autocomplete & GPS Detection Component
Build the Hero Search Bar component:
* **Interactive Address Input**: Attach Places API / Mapbox Autocomplete to the main home page input field. Upon selecting an address prediction, extract `latitude` and `longitude`.
* **"Use My Current Location" Button**: Add a GPS button using browser `navigator.geolocation.getCurrentPosition()`. Convert reverse-geocoded coordinates into an address display label.
* **State Management**: Save selected `{ address, lat, lng }` to state and trigger the merchant search query automatically.

### Task 2: Category & Cuisine Filter Pills
Add a horizontal scrollable category bar directly beneath the search input:
* Include top category badges (e.g., *All*, *Pizza*, *Wings*, *Grocery*, *Bakery*, *Late Night*).
* Toggling a category appends/filters the keyword query (`q`) sent to the backend.

### Task 3: Dynamic Nearby Merchant Grid using `mb.ajax()`
Build the merchant card list/grid container:
1. **API Integration**: Query the backend using `mb.ajax()` whenever location or category filter changes:
   ```javascript
   mb.ajax({
     url: "?api=neighborhub&action=search_merchants",
     method: "POST",
     data: JSON.stringify({ lat: self.lat, lng: self.lng, q: self.query }),
     success: function (response) {
       if (response && response.success) {
         self.renderMerchantGrid(response.merchants);
       } else {
         self.showError("No merchants found in your area.");
       }
     },
     error: function () {
       self.showError("Failed to communicate with service pipeline.");
     }
   });
   ```
2. **Loading & Empty States**:
   * Show skeleton loader cards while waiting for API response.
   * If no merchants are found within range, display a friendly message (*"No local stores delivering to this area yet"*) with a CTA for merchants to join.
3. **Merchant Card Elements**:
   * Store Header / Image (`image_url` fallback to default store avatar).
   * Business Name (`business_name`).
   * Distance badge (`distance_miles` formatted to 1 decimal, e.g., `1.2 mi`).
   * Operating Status (Open/Closed indicator based on `store_hours`).
   * Click action: Navigates to merchant menu page (`/store/{id}` or `/merchant/{id}`).
