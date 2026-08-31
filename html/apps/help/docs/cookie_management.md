# Cookie Management Best Practices

## Overview
All cookie operations should use the centralized methods provided by the `App` class for consistency, security, and maintainability.

## Usage
- **Set a cookie:**
  ```php
  App::getInstance()->setCookie('cookie_name', 'value');
  ```
- **Get a cookie:**
  ```php
  $value = App::getInstance()->getCookie('cookie_name');
  ```

## Migration Steps
- Replace all direct calls to `setcookie()` and `$_COOKIE` with the above methods.
- Avoid setting cookies outside the `App` class or its wrappers.

## Example
```php
// Set a cookie for user preference
App::getInstance()->setCookie('theme', 'dark');

// Retrieve a cookie value
$theme = App::getInstance()->getCookie('theme', 'light');
```

## Benefits
- Centralized control over cookie parameters (expiry, path, security).
- Easier to update cookie logic globally.
- Improved code readability and maintainability.
