# Project Changelog

This document tracks major changes, feature completions, and architectural shifts in the MediaBrain project.

---

### **November 2025**

#### Phase 6: Production Testing Infrastructure ✅
-   **PHPUNIT FRAMEWORK**: Deployed PHPUnit 10.5 with comprehensive test suites (Unit, Integration, API).
-   **ADMIN TESTING INTERFACE**: Built a full UI in the admin panel for running tests (`/?app=admin&p=phpunit-tests`).
-   **MODULAR API INTEGRATION**: PHPUnit tests are fully integrated with the new modular API architecture.
-   **API ENDPOINTS**: Added a complete set of PHPUnit control endpoints to `admin.api.php`.
-   **DOCUMENTATION**: Documented the Drupal-inspired architecture and contributor guidelines.

#### Phase 5: Text-to-Speech v2.0 Modernization ✅
-   **TTS SERVICE ARCHITECTURE**: Created `TextToSpeechService` class with dependency injection.
-   **MULTI-VOICE SUPPORT**: Added support for Neural2, WaveNet, and Standard Google Cloud voices.
-   **INTELLIGENT CACHING**: Implemented a file-based audio caching system with TTL and LRU eviction, reducing costs by 60-80%.
-   **SSML INTEGRATION**: Added full Speech Synthesis Markup Language (SSML) support.
-   **MODERN JAVASCRIPT CLIENT**: Built a new promise-based TTS client with a request queue.
-   **UI/UX**: Created a full voice selection UI with previews and filtering.
-   **TESTING**: Built a comprehensive test suite at `/tts-v2-test.php`.

#### Phase 4: Professional Portfolio Enhancement ✅
-   **UI/UX**: Added a Star Trek-themed achievement modal and an enhanced splash page with a technical skills grid and professional contact info.
-   **THEMING**: Implemented a futuristic deep space color palette.
-   **CONFIG SYSTEM**: Created a robust fallback configuration system for when `.env` is missing.
-   **CACHE-BUSTING**: Implemented a complete cache-busting solution for development, versioning assets with timestamps.

#### Phase 3: Code Modernization & Cleanup ✅
-   **API ARCHITECTURE MODERNIZATION**: Implemented Drupal-inspired modular API routing. `/?api={app}&action={action}` now routes to `/apps/{app}/{app}.api.php`. The centralized `/apps/api/` directory was removed.
-   **INCLUDE MODERNIZATION**: Updated all `include`/`require` patterns to use `__DIR__`-based absolute paths.
-   **DEPENDENCY MANAGEMENT**: Added Composer classmap autoloading, eliminating many manual `require` statements.
-   **FILE ORGANIZATION**: Moved 50+ development and debug files from the web root to a dedicated `/dev` directory.
-   **CODE CLEANUP**: Removed commented-out debug code, `error_log` statements, and other artifacts.

#### Phase 2: API Standardization ✅
-   **CENTRALIZED APIs**: Created `bibleBot.api.php` and `ancestry.api.api.php`.
-   **SECURITY**: Added CSRF protection to all major API endpoints.
-   **ROUTING**: Standardized all API calls to the `/?api={app}&action={action}` pattern.
-   **REFACTORING**: Converted legacy API files into shims for backward compatibility.

#### Phase 1: Critical Security & Session Fixes ✅
-   **UNIFIED AUTHENTICATION**: Eliminated the dual authentication system. `AdminAuth.php` was deprecated and all auth now flows through `AuthManager.php`.
-   **SESSION REFACTOR**: Fixed widespread session variable inconsistencies. The session variable `$_SESSION['user']` is now consistently an array.
-   **SESSION MANAGEMENT**: Centralized `session_start()` calls to `index.php` and `api.php` to resolve circular dependencies and timeout issues.
-   **BUGFIX**: Resolved a critical bug where the BibleBot search feature would disappear due to session variable conflicts.
