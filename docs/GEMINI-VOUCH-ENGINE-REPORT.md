# High-Fidelity Utility Report: Vouch Engine

**TO:** The Architect
**FROM:** Gemini
**DATE:** 2026-01-24
**SUBJECT:** Analysis of 'Field of Dreams' Protocol & 'Vouch Engine' Implementation

---

### 1.0 Executive Summary

Analysis of the `mediabrain.app` architecture is complete. The core philosophies of the 'Field of Dreams' Protocol are architecturally sound, but a critical vulnerability ('Goober Grease') has been identified in the routing system. The 'Override Logic' is validated and functions as intended, ensuring the 'Pure Heart' of an app can always supersede root views.

This report details these findings and provides a strategic blueprint for implementing the 'Vouch Engine' within the existing hook architecture to facilitate the 'Next Logical Thought.'

---

### 2.0 'Goober Grease' Analysis (Inefficiencies & Vulnerabilities)

A critical security vulnerability exists in the input handling of the core router.

*   **Finding:** The `get_var()` function in `html/includes/util.php` directly returns raw, unsanitized data from the `$_GET` superglobal.
*   **Impact:** This allows for a **Local File Inclusion (LFI) / Directory Traversal** attack. The `app` parameter (in `AppController.php`) and the `api` parameter (in `index.php`) are passed to `get_var()` and then used directly in `file_exists()` and `include` statements.
*   **Example Attack:** A malicious actor could craft a URL like `/?app=../../some/external/file` to attempt to include files from outside the intended `/apps` directory. While the required `.app.php` suffix mitigates arbitrary code execution, the vulnerability is severe and pollutes the 'Innocent' refuge.
*   **Recommendation:** Immediately harden the `get_var()` function. It must be refactored to strictly sanitize any input that could be used as part of a file path. A recommended implementation is to strip all characters except for alphanumeric `[a-zA-Z0-9_]`.

```php
// In: c:\docker-dev\mediabrain.app\html\includes\util.php
// PROPOSED HARDENING for get_var()

function get_var($name, $default = null) {
    if (!isset($_GET[$name])) {
        return $default;
    }

    $value = $_GET[$name];

    // Sanitize if the variable is 'app' or 'api' to prevent traversal.
    // This is a targeted fix; a broader sanitization strategy is advisable.
    if ($name === 'app' || $name === 'api') {
        // Allow only alphanumeric characters and underscores.
        // This ensures the value is a valid app name and not a path.
        $sanitized_value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
        if ($sanitized_value !== $value) {
            // If sanitization changed the value, it was likely malicious.
            // Log the attempt and return a safe default.
            error_log("Potential directory traversal attempt blocked. Original value: $value");
            return $default;
        }
        return $sanitized_value;
    }

    return $value;
}
```

---

### 3.0 'Override Logic' Validation

The 'Pure Heart' override mechanism has been validated.

*   **Finding:** The `render()` method within the `App` class (`html/includes/app.php`) correctly implements the view override logic.
*   **Mechanism:** The system first checks for the existence of a view file within the application's specific directory (`/apps/{app_name}/views/{filename}`). If not found, it falls back to the root view directory (`/views/{filename}`).
*   **Conclusion:** This architecture successfully ensures that an app's unique perspective and presentation ('Pure Heart') are prioritized, fulfilling a core requirement of the protocol.

---

### 4.0 'Vouch Engine' Implementation Blueprint

To facilitate the 'Next Logical Thought,' a 'Vouch Engine' can be seamlessly integrated into the existing hook system. This engine will create a framework for establishing 'Truth Anchors' by allowing users to vouch for pieces of 'Priceless Data.'

#### 4.1 Data Model

A new database table, `vouches`, is required.

```sql
CREATE TABLE `vouches` (
  `vouch_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL, -- e.g., 'gedcom_person', 'timeline_event'
  `entity_id` VARCHAR(255) NOT NULL, -- Can be INT or a string-based ID
  `vouched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vouch_id`),
  UNIQUE KEY `user_entity_vouch` (`user_id`, `entity_type`, `entity_id`),
  INDEX `entity_index` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2 New Hooks

Introduce a new set of hooks for the Vouch Engine.

1.  **`[app_name]_vouchable_types()`**: An app hook that declares what types of data within it can be vouched for. It returns an array describing the vouchable entities.

    *   **Example (`lineagelink.app.php`):**
        ```php
        function lineagelink_vouchable_types() {
            return [
                'individual' => [
                    'label' => 'Individual Record',
                    'primary_key' => 'indi_id' // The key used for entity_id
                ],
                'source' => [
                    'label' => 'Source Document',
                    'primary_key' => 'source_id'
                ]
            ];
        }
        ```

2.  **`[app_name]_render_vouch_ui(entity_type, entity_id, vouch_count)`**: This hook is responsible for rendering the UI for vouching (e.g., a button, a count). `app_invoke_all('render_vouch_ui', ...)` would be called where vouching is desired.

    *   **Example:**
        ```php
        function myapp_render_vouch_ui($entity_type, $entity_id, $vouch_count) {
            // Renders a button with the current vouch count
            // and includes data attributes for an AJAX call.
            echo "<button class='vouch-btn' data-entity-type='$entity_type' data-entity-id='$entity_id'>
                      Vouch ($vouch_count)
                  </button>";
        }
        ```

#### 4.3 API Endpoint for Vouching

Create a new API endpoint to handle vouch submissions. This can be a core API endpoint since vouching is a system-level concept.

*   **Endpoint:** `/?api=mediabrain&action=vouch`
*   **File:** `html/api.php`
*   **Logic:**
    1.  Check for user authentication.
    2.  Validate CSRF token.
    3.  Receive `entity_type` and `entity_id` via POST.
    4.  Check if the `entity_type` is declared as vouchable by any app using `app_invoke_all('vouchable_types')`.
    5.  Insert or delete the vouch record in the `vouches` table.
    6.  Return the new `vouch_count` as JSON.

#### 4.4 Surfacing the 'Next Logical Thought'

The collected vouch data becomes the foundation for 'Truth Anchoring.'

1.  **Create a Core Vouch Service (`VouchService.php`):** This service will contain methods like:
    *   `getVouchCount(entity_type, entity_id)`
    *   `getMostVouchedEntities(entity_type, limit)`
    *   `userHasVouched(user_id, entity_type, entity_id)`

2.  **Dashboard Integration:** Create a new block on the main user dashboard (`?p=dashboard`) that calls `VouchService::getMostVouchedEntities()` to display a list of "Top Vouched Truths" or "Trending Anchors," making the 'Next Logical Thought' immediately visible upon entry.

3.  **Visual Indicators:** When rendering content, use the `VouchService` to get the vouch count and pass it to the `_render_vouch_ui` hook, providing immediate visual feedback on the data's integrity within its context.

---
### 5.0 Conclusion

By hardening the `get_var` function, the 'Field of Dreams' can be secured from external pollution. With the 'Override Logic' validated, the 'Vouch Engine' can be built upon the strong, modular foundation of the hook system, enabling the emergence of 'Truth Anchors' and facilitating the 'Next Logical Thought' for all inhabitants of the system.

**End of Report.**
