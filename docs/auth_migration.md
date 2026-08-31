# Authentication Refactoring: Migration to App::getAuthManager()

## Overview
Authentication, admin checks, and CSRF logic are now centralized in the `AuthManager` class, accessible via the main `App` class. This replaces legacy standalone functions and scripts.

## Migration Steps

1. **Remove legacy authentication files/functions**
   - Delete files like `check_auth.php` and any direct includes of legacy auth scripts.

2. **Update all authentication and CSRF logic**
   - Use `$app = App::getInstance();`
   - Access authentication via `$app->getAuthManager()->methodName()`.
   - Example: `$app->getAuthManager()->checkCredentials($user, $pass)`
   - Example: `$app->getAuthManager()->validateCsrf($token)`

3. **Centralize session and permission logic**
   - Store user/session data in `$_SESSION['user']` and related keys as set by login logic.
   - Use `AuthManager` for admin checks: `$app->getAuthManager()->userIsAdmin($username)`

4. **Document the new pattern for all developers**
   - All new code should use the `App` class and `AuthManager` for authentication, admin, and CSRF logic.
   - Avoid direct access to session or legacy functions for these tasks.

## Example Usage
```php
$app = App::getInstance();
$auth = $app->getAuthManager();
if ($auth->checkCredentials($username, $password)) {
    // Authenticated
}
if ($auth->userIsAdmin($username)) {
    // Admin logic
}
if ($auth->validateCsrf($token)) {
    // CSRF valid
}
```

## Next Steps
- Refactor permission checks, admin logic, and session handling to use centralized methods.
- Remove any remaining legacy code or direct session manipulation for authentication.
