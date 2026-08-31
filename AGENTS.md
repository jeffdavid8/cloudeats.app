# MediaBrain.app - Agent Development Guide

## Test Commands
- **Run all tests**: `vendor/bin/phpunit` or `composer test`
- **Run single test**: `vendor/bin/phpunit tests/Unit/CoreAppTest.php`
- **Run test suite**: `vendor/bin/phpunit --testsuite Unit` (Unit/Integration/API/Production)
- **Test with coverage**: `vendor/bin/phpunit --coverage-text`
- **Admin test UI**: Navigate to `/?app=admin&p=phpunit-tests`

## Build & Lint
- **Install dependencies**: `composer install`
- **Regenerate autoloader**: `php regenerate-autoloader.php`
- **Check PHP syntax**: `php -l file.php`

## Architecture
- **Framework**: Custom PHP 8.4 modular application (Drupal-inspired architecture)
- **Databases**: MySQL 8.0 (`db_mediabrain`, `db_lineagelink`)
- **Storage**: Google Cloud Storage with local fallback (html/storage/)
- **Apps**: Modular apps in html/apps/ (admin, bibleBot, recipes, weather, ancestry, help)
- **API Pattern**: `/?api={app}&action={action}` routes to `/apps/{app}/{app}.api.php`
- **Auth**: Unified AuthManager system, session stored as `$_SESSION['user']` (array format)
- **Logs**: Event logging in logs/event.log, error logs in logs/error.log

## Code Style
- **PSR-4 Autoloading**: Classes in `html/includes/Services/` use `MediaBrain\Services\` namespace
- **Imports**: Use `__DIR__`-based absolute paths: `require_once __DIR__ . '/includes/file.php'`
- **CSRF Protection**: Always use `mb.ajax()` for AJAX requests with CSRF tokens
- **Session Variables**: `$_SESSION['user']` is ARRAY with keys: username, role, is_admin
- **Admin Checks**: Use `AuthManager::requireAdmin()` and `AuthManager::userIsAdmin()`
- **Error Handling**: Use EventLogger for logging: `$app->getEventLogger()->log($category, $msg, $context)`
- **Naming**: PSR-12 conventions (camelCase methods, StudlyCaps classes)
- **API Structure**: Switch-based routing in {app}.api.php files (not function hooks)

## Development Environment
- **Primary URL**: `https://mediabrain.app.local` (NOT localhost)
- **Docker**: Multi-container setup with nginx reverse proxy (port 443→8080)
- **Container**: `mediabrainapp-mediabrain-app-1` (main PHP 8.4-apache)
- **Restart**: `docker-compose restart` after config changes
- **Logs**: `docker logs mediabrainapp-mediabrain-app-1 --tail 50`

## Critical References
- **Project Memory**: Read AI-DEVELOPMENT-NOTES.md for architectural patterns and gotchas
- **README**: Comprehensive setup and feature documentation in README.md
- **No Comments**: Don't add code comments unless complex or user requests
