# 3. Developer Cookbook

This document provides practical, step-by-step guides for common development tasks.

## How to Add a New API Endpoint

All API endpoints belong to a specific application module (e.g., `admin`, `recipes`). Follow the Drupal-inspired modular pattern.

1.  **Identify the Application**: Determine which application the endpoint belongs to (e.g., `biblebot`).
2.  **Open the API File**: Open the corresponding API file in that app's directory (e.g., `apps/biblebot/biblebot.api.php`).
3.  **Add a `case` to the `switch`**: Add a new `case` for your action to the main `switch` statement.
4.  **Add Logic**: Implement the logic for your endpoint within the `case`.
5.  **Add Security**: Ensure you add an authentication/permission check if the endpoint is not public (e.g., `admin_require_admin()`).
6.  **Test**: Call your new endpoint to verify it works. The URL will be `/?api={app}&action={your_new_action}`.

**Example**: Adding a `get_commentary` action to BibleBot.

```php
// In /apps/biblebot/biblebot.api.php

switch ($action) {
    case 'search_verses':
        // ... existing code
        break;

    // New Endpoint
    case 'get_commentary':
        // Assumes this requires a logged-in user
        AuthManager::requireUser();

        $verse = get_var('verse');
        $commentary = find_commentary_for_verse($verse);

        echo json_encode(['commentary' => $commentary]);
        break;

    default:
        // ...
}
```

## How to Work with Authentication

The `AuthManager` class is the single source of truth for all authentication and session management.

-   **Location**: `html/includes/AuthManager.php`
-   **Session Data**: The user's session is stored in `$_SESSION['user']`, which is an **array**. Access the username via `$_SESSION['user']['username']`.

### Requiring a Specific Role

-   **Require Admin**: Place this at the top of a script or case statement.
    ```php
    AuthManager::requireAdmin();
    ```
-   **Require Logged-in User**:
    ```php
    AuthManager::requireUser();
    ```

### Checking a User's Role

-   **Check for Admin**:
    ```php
    if (AuthManager::userIsAdmin()) {
        // ... logic for admins
    }
    ```

## How to Use CSRF Protection

CSRF protection is mandatory for all state-changing operations (POST, PUT, DELETE).

### JavaScript (Client-Side)

Use the global `mb.ajax()` helper for any AJAX request that changes data. It automatically includes the current CSRF token in the request headers. The token is available in the global `mb.csrf_token` JavaScript variable.

```javascript
// GOOD: Uses the helper
mb.ajax({
    url: '/?api=admin&action=delete_user',
    method: 'POST',
    data: { userId: 123 },
    success: function(response) {
        console.log('User deleted');
    }
});

// BAD: Manually using jQuery without the token
$.ajax({
    url: '/?api=admin&action=delete_user', // This will be blocked
    method: 'POST',
    data: { userId: 123 }
});
```

### PHP (Server-Side)

Validate the token at the beginning of your API action handler using `AuthManager::validateCsrf()`. This function will automatically handle the validation and exit if the token is invalid.

```php
// In an api.php file

switch ($action) {
    case 'delete_user':
        // Validate the CSRF token first
        AuthManager::validateCsrf();
        
        // Require admin permissions
        AuthManager::requireAdmin();

        // Now, proceed with the logic
        $userId = get_var('userId');
        delete_user($userId);
        break;
}
```

## How to Log Events

Use the global EventLogger instance to log significant actions.

-   **Get the Logger**: `$logger = $app->getEventLogger();`
-   **Log an Event**: `$logger->log($category, $message, $context);`

```php
// Example: Logging a failed login
$logger = $app->getEventLogger();
$logger->log(
    'Authentication',
    'Failed login attempt',

    ['username' => $username, 'ip_address' => $_SERVER['REMOTE_ADDR']]
);
```
