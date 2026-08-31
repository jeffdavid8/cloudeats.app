# 2. Architectural Principles

This document outlines the core architectural philosophies and key design patterns for the MediaBrain project. Adhering to these principles is critical for maintaining a consistent, scalable, and maintainable codebase.

## 🏗️ Core Philosophy: Drupal-Inspired Modularity

The project owner strongly prefers a **Drupal-style modular architecture**. When designing new features or refactoring, always think in terms of self-contained modules.

### Modular Design Principles

-   **Self-Contained Apps**: Each application directory (e.g., `/apps/admin`) should be a self-contained module, like a Drupal module.
-   **Colocation**: Keep all related files for an app together (views, APIs, CSS, JS, includes, etc.).
-   **Separation of Concerns**: Maintain a clean separation between business logic (PHP) and presentation (views/templates).

### Directory Structure Example

This structure should be replicated for any new application.

```
/apps/admin/           ← Self-contained admin module
  ├── admin.app.php    ← App initialization & routing
  ├── admin.api.php    ← API endpoints (all admin APIs here)
  ├── views/           ← Template files (views)
  ├── css/             ← Module-specific styles
  ├── js/              ← Module-specific JavaScript  
  └── includes/        ← Helper classes/functions
```

## 🎯 API Architecture

### Modular API Routing

The system uses a modular routing pattern where the API endpoint is determined by URL query parameters.

-   **Pattern**: `/?api={app}&action={action_name}`
-   **How it Works**: A request to this URL will be routed to the `api.php` file within the specified app's directory.
-   **Example**: `/?api=biblebot&action=search_verses` is handled by `/apps/biblebot/biblebot.api.php`.

This is implemented in the main `index.php`:

```php
// Handle API requests first (modular routing)
$api_app = get_var('api');
if (!empty($api_ápp)) {
    $api_file = __DIR__ . "/apps/{$api_app}/{$api_app}.api.php";
    if (file_exists($api_file)) {
        header('Content-Type: application/json');
        include $api_file;
        exit;
    }
}
```

### API Action Handling: `switch` vs. `function` Hooks

For handling actions within an `api.php` file, the **required pattern is a `switch` statement.**

-   **Owner Preference**: `switch` statements are strongly preferred over function-based hooks (`call_user_func`).

#### Chosen Pattern (Example from `admin.api.php`):

```php
switch ($action) {
    case 'phpunit_run_tests':
        admin_require_admin();
        // ... implementation
        break;
    
    case 'get_users':
        admin_require_admin(); 
        // ... implementation
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
}
```

#### Rationale:

-   **Explicit & Discoverable**: All available endpoints for the module are clearly listed in one place.
-   **Performance**: Avoids the overhead of `call_user_func()`.
-   **Static Analysis**: Easier for IDEs and static analysis tools to understand and provide support for.
-   **Clear Validation**: Simplifies adding per-endpoint validation logic.
