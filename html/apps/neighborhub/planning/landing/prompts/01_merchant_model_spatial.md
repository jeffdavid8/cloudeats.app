# Task Prompt: 01_merchant_model_spatial.md
# Goal: Update Merchant Model with Spatial GIS Point Management & Add Nearby Search API

## Context & Requirements
We are updating the `Merchant` model (`merchant.model.php`) and related API endpoint in Neighborhub to fully utilize MySQL/MariaDB spatial GIS features (`POINT` columns and `SPATIAL INDEX`). 

Key rules for SRID 4326 / WGS84:
1. Spatial point syntax requires **Longitude FIRST**: `ST_PointFromText(CONCAT('POINT(', longitude, ' ', latitude, ')'), 4326)`.
2. Do NOT run SQL UPDATE statements inside PHP response/getter check logic (do not write to DB during read requests). Spatial point synchronization must happen strictly within `create()`, `update()`, or dedicated DB migration scripts.
3. In `SELECT` queries, avoid returning raw binary `location` columns directly into PHP array/object mappings. Select `latitude`, `longitude`, or calculated spatial expressions.

---

## Instructions

### Task 1: Update `Merchant::create()` in `merchant.model.php`
* Modify the `INSERT` query to set the `location` column using `ST_PointFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)` whenever `$latitude` and `$longitude` are provided.
* Pass `$longitude` first and `$latitude` second into the spatial function parameters.
* Fallback to `NULL` if coordinates are not provided.

### Task 2: Update `Merchant::update()` in `merchant.model.php`
* When `$data['latitude']` or `$data['longitude']` are updated, update both scalar columns AND automatically synchronize the `location` point column:
  `location = ST_PointFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)`
* Ensure proper parameter order (Longitude then Latitude).

### Task 3: Add `Merchant::searchNearby()` Static Method
Add a static helper method to `Merchant`:
```php
public static function searchNearby($lat, $lng, $query = '', $limit = 30)