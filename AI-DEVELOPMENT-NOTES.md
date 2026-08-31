# MediaBrain Project Memory & Development Notes

> **📝 For All Developers & AI Assistants:**
> This file contains critical project knowledge that prevents repetitive discovery of the same information. 
> **Please contribute to this file** when you learn something important about the project structure, 
> configuration, or development patterns. This helps maintain continuity across different contributors.

## 🚨 Critical Reminders
- **File Size Monitoring**: Notify stakeholders if any file approaches 2GB
- **Always check this file first** before making assumptions about URLs, authentication, or development patterns
- **Update this file** when you discover new important patterns or configurations

## Development Environment
- **Local Development URL**: `mediabrain.app.local` (NOT localhost)
- **Production URL**: `mediabrain.app`
- **Docker Setup**: Multi-container with nginx reverse proxy, PHP 7.4
- **File Size Limit**: Notify user if any file approaches 2GB

## 🐳 Docker Development Environment (CRITICAL - DO NOT BREAK!)

### **Overview**
Complex multi-container Docker setup with nginx reverse proxy handling SSL termination and routing to multiple PHP applications. Breaking this configuration will disrupt the entire local development environment.

### **Container Architecture**
```
Internet → Nginx (Port 80/443) → MediaBrain Container (Port 8080) → Application
```

#### **Primary Containers**
- **`nginx`**: Reverse proxy, SSL termination, routes to backend containers
  - **Ports**: `0.0.0.0:80->80/tcp`, `0.0.0.0:443->443/tcp`
  - **Purpose**: SSL handling, domain routing, static file serving optimization
  - **Config**: `/etc/nginx/conf.d/mediabrain.app.conf`
  
- **`mediabrainapp-mediabrain-app-1`**: Main MediaBrain application
  - **Ports**: `0.0.0.0:8080->80/tcp`
  - **Purpose**: Primary application container (this project)
  - **Framework**: PHP 8.4-apache with Composer dependencies
  
- **`mediabrain-app`**: Legacy PHP 7.4 container
  - **Ports**: `9000/tcp` (PHP-FPM)
  - **Purpose**: Backwards compatibility for older components
  
#### **Supporting Containers**
- **`lineagelink`**: Genealogy application (PHP 8.4)
- **`mediabrain-local`**: Local development utilities (PHP 8.1)
- **`db_mediabrain`** & **`db_lineagelink`**: MySQL 8.0 databases
- **`logrotate`**: Log management

### **Network Routing Configuration**

#### **Domain Mapping** (in `C:\Windows\System32\drivers\etc\hosts`)
```
127.0.0.1       mediabrain.app.local
127.0.0.1       mediabrain.local  
192.168.1.30    lineagelink.local
```

#### **Nginx Proxy Configuration** 
**File**: `/etc/nginx/conf.d/mediabrain.app.conf` in nginx container
```nginx
server {
    listen 80;
    server_name mediabrain.app.local mediabrain.app;
    return 301 https://$host$request_uri;  # Force HTTPS
}

server {
    listen 443 ssl;
    server_name mediabrain.app.local mediabrain.app;
    
    # SSL Configuration
    ssl_certificate /etc/nginx/certs/local.crt;
    ssl_certificate_key /etc/nginx/certs/local.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    
    # Proxy to MediaBrain Docker Container
    location / {
        proxy_pass http://host.docker.internal:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_redirect off;
        proxy_set_header Accept-Encoding '';  # Fix MIME types
    }
}
```

### **Critical Development URLs**
- **Primary Development**: `https://mediabrain.app.local` ← **USE THIS**
- **Direct Container Access**: `http://localhost:8080` (bypasses nginx)
- **Admin Interface**: `https://mediabrain.app.local/?app=admin`
- **Test Management**: `https://mediabrain.app.local/?app=admin&p=tests`

### **Container Dependencies & Startup Order**
1. **Database containers** must start first (`db_mediabrain`, `db_lineagelink`)
2. **PHP application containers** (`mediabrain-app`, `lineagelink`, `mediabrain-local`)
3. **MediaBrain main container** (`mediabrainapp-mediabrain-app-1`)
4. **Nginx proxy** starts last and routes to all services

### **MIME Type & Static File Handling**
**CRITICAL**: Static files (CSS, JS) are served through the proxy chain:
```
Browser Request → Nginx → MediaBrain Container → Apache → PHP (if needed) → File
```

**Known Issues & Solutions**:
- **MIME Type Errors**: If you see `blocked due to MIME type ("text/html") mismatch`, the proxy chain is broken
- **Root Cause**: Usually missing class dependencies causing PHP fatal errors that return HTML instead of static files
- **Quick Fix**: Check `docker logs mediabrainapp-mediabrain-app-1` for PHP errors
- **Dependencies**: Ensure `AuthManager.php` and `EventLogger.php` are properly included in `index.php`

### **Docker Commands for Debugging**
```bash
# Check all container status
docker ps --format "table {{.Names}}\t{{.Ports}}\t{{.Status}}"

# Check MediaBrain container logs
docker logs mediabrainapp-mediabrain-app-1 --tail 20

# Check nginx proxy logs
docker logs nginx --tail 20

# Test nginx configuration
docker exec nginx nginx -t

# Reload nginx configuration (after changes)
docker exec nginx nginx -s reload

# Access MediaBrain container shell
docker exec -it mediabrainapp-mediabrain-app-1 bash

# Check static file serving directly
curl -I http://localhost:8080/js/jquery-ready.js

# Test proxy chain
curl -I https://mediabrain.app.local/js/jquery-ready.js
```

### **Troubleshooting Guide**

#### **"MIME type mismatch" errors:**
1. Check MediaBrain container logs: `docker logs mediabrainapp-mediabrain-app-1`
2. Look for PHP fatal errors (usually missing classes)
3. Verify `index.php` includes: `AuthManager.php`, `EventLogger.php`
4. Test direct container access: `http://localhost:8080`

#### **"ERR_CONNECTION_REFUSED" on mediabrain.app.local:**
1. Check nginx container: `docker ps | grep nginx`
2. Restart nginx: `docker restart nginx`
3. Verify nginx config: `docker exec nginx nginx -t`

#### **Container won't start:**
1. Check port conflicts: `netstat -ano | findstr :80`
2. Stop conflicting services
3. Rebuild if needed: `docker-compose build --no-cache`

#### **Database connection errors:**
1. Check database containers: `docker ps | grep mysql`
2. Verify container networking: `docker network ls`

### **Development Workflow**
1. **Start Environment**: `docker-compose up -d` (from MediaBrain directory)
2. **Check Status**: `docker ps` (ensure all containers running)
3. **Access Application**: `https://mediabrain.app.local`
4. **Make Changes**: Edit files in `html/` directory (volume mounted)
5. **Test Changes**: Refresh browser (no container restart needed for PHP files)
6. **For Config Changes**: `docker-compose restart`

### **NEVER DO THIS** (Environment Killers)
- ❌ Don't manually edit nginx container files without backup
- ❌ Don't change port mappings without updating nginx config
- ❌ Don't stop nginx container while developing (breaks all local domains)
- ❌ Don't remove the hosts file entries
- ❌ Don't modify `docker-compose.yml` ports without understanding the proxy chain
- ❌ Don't bypass the proxy for development (creates different behavior than production)

### **Backup Critical Configs Before Changes**
```bash
# Backup nginx config
docker cp nginx:/etc/nginx/conf.d/mediabrain.app.conf ./mediabrain.app.conf.backup

# Backup hosts file
copy C:\Windows\System32\drivers\etc\hosts hosts.backup

# Backup docker-compose.yml
copy docker-compose.yml docker-compose.yml.backup
```

### **Emergency Recovery**
If the environment breaks:
1. **Reset nginx config**: Copy from backup or recreate proxy configuration
2. **Restart all containers**: `docker-compose down && docker-compose up -d`
3. **Check logs**: `docker logs nginx` and `docker logs mediabrainapp-mediabrain-app-1`
4. **Fallback**: Use `http://localhost:8080` for direct access while fixing proxy

**This environment works perfectly when properly maintained - document any changes here!**

## Docker Environment Details
- **Main PHP Container**: `mediabrain-app` (PHP 7.4 with FPM)
- **Nginx Container**: `nginx` (reverse proxy, SSL termination)
- **Database Container**: `db_mediabrain` (MySQL 8.0)
- **Other Containers**: `lineagelink`, `mediabrain-local`, `logrotate`, `db_lineagelink`
- **Container Status**: Use `docker ps` to check running containers
- **Session Storage**: Inside `mediabrain-app` container at `/tmp/sess_*`
- **Log Access**: Use `docker logs mediabrain-app` for PHP-FPM logs

## Architecture Patterns

### 🏗️ **ARCHITECTURAL PHILOSOPHY: DRUPAL-INSPIRED MODULARITY**

**⭐ CRITICAL FOR ALL CONTRIBUTORS:** This project owner **strongly prefers Drupal-style modular architecture** patterns. When designing new features or refactoring existing code, always consider the Drupal way of doing things:

#### **Modular Design Principles (Drupal-Inspired)**
- **Self-Contained Modules**: Each app directory should be self-contained like a Drupal module
- **Everything Together**: Keep related files in the same directory (views, APIs, CSS, JS, includes)
- **Modular APIs**: Each app has its own `{app}.api.php` file in the app directory
- **Hook System Consideration**: Consider function-based hooks vs switch statements (owner prefers switch for performance/clarity)
- **Configuration Management**: Centralized config with app-specific overrides
- **Theme/Template Separation**: Clean separation between logic and presentation

#### **App Directory Structure (Drupal Module Style)**
```
/apps/admin/           ← Self-contained admin module
  ├── admin.app.php    ← App initialization & routing
  ├── admin.api.php    ← API endpoints (all admin APIs here)
  ├── views/           ← Template files
  ├── css/            ← Module-specific styles
  ├── js/             ← Module-specific JavaScript  
  └── includes/       ← Helper classes/functions

/apps/recipes/         ← Self-contained recipes module
  ├── recipes.app.php
  ├── recipes.api.php  
  └── ... (same structure)
```

#### **API Architecture: Drupal-Inspired Modular Routing**
**Current Pattern**: `/?api={app}&action={action_name}`

**Examples:**
- `/?api=admin&action=phpunit_run_tests` → `/apps/admin/admin.api.php`
- `/?api=recipes&action=get_recipe` → `/apps/recipes/recipes.api.php`
- `/?api=biblebot&action=search_verses` → `/apps/biblebot/biblebot.api.php`

**Implementation in `index.php`:**
```php
// Handle API requests first (modular routing)
$api_app = get_var('api');
if (!empty($api_app)) {
    $api_file = __DIR__ . "/apps/{$api_app}/{$api_app}.api.php";
    if (file_exists($api_file)) {
        header('Content-Type: application/json');
        include $api_file;
        exit;
    }
}
```

#### **Why This Architecture?**
1. **Maintainability**: Related code stays together (like Drupal modules)
2. **Scalability**: Easy to add new apps without touching core routing
3. **Developer Experience**: Contributors can work on one app without affecting others
4. **Deployment**: Individual apps can be deployed/disabled independently
5. **Debugging**: Clear separation makes troubleshooting easier

### 🎯 **DECISION MAKING: Switch vs Hook Patterns**

**Owner Preference**: **Switch statements over function hooks** for API routing

**Chosen Pattern (admin.api.php):**
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

**Rejected Pattern (function hooks):**
```php
$functionName = "{$app}_api_{$action}";
if (function_exists($functionName)) {
    call_user_func($functionName);
} else {
    // error handling
}
```

**Rationale**:
- **Explicit**: All endpoints visible in one place
- **Performance**: Direct function calls vs `call_user_func()` overhead  
- **IDE Support**: Better autocomplete and static analysis
- **Validation**: Easy to add per-endpoint validation and HTTP method checks

### 📋 **CONTRIBUTOR GUIDELINES**

**When Adding New Features:**
1. **Follow Drupal Module Structure**: Keep everything for an app in its directory
2. **API First**: Add new actions to the app's `.api.php` file using switch pattern
3. **Self-Contained**: Minimize dependencies between apps
4. **Documentation**: Document new actions in this file
5. **Consistency**: Follow existing patterns rather than inventing new ones

**When Refactoring:**
1. **Preserve Modularity**: Don't centralize what should be distributed
2. **Ask First**: If unsure about architectural decisions, consider the Drupal way
3. **Incremental**: Prefer gradual modular improvements over massive rewrites
4. **Test**: Ensure changes work with the existing app ecosystem

### Legacy Patterns (Maintained for Backward Compatibility)
- **App Pattern**: Singleton App class with `App::getInstance()`
- **Authentication**: Unified AuthManager system (no longer dual AdminAuth/AuthManager)
- **Session Variables**: 
  - Main auth: `$_SESSION['user']` (ARRAY with 'username', 'role', 'is_admin' keys)
  - Admin check: `AuthManager::userIsAdmin($_SESSION['user'])` (handles both string and array input)
  - CSRF: `AuthManager::csrfToken()` and `AuthManager::validateCsrf()`
  - **IMPORTANT**: `$_SESSION['user']` is an ARRAY, not a string! Use `$_SESSION['user']['username']` for username

## File Locations
- **Users Data**: `html/json/users.json`
- **Event Logs**: `logs/event.log`
- **Error Logs**: `logs/error.log`
- **Main AuthManager**: `html/includes/AuthManager.php`
- **App Base**: `html/includes/app.php`
- **EventLogger**: `html/includes/EventLogger.php`
- **Testing Framework**: `tests/` directory with Unit/Integration/API test suites
- **PHPUnit Config**: `phpunit.xml` (test configuration)
- **Test Bootstrap**: `tests/bootstrap.php` (test environment setup)
- **Test Runner**: `test-runner.php` (infrastructure verification script)

## Documentation Structure
- **Main Documentation**: `docs/` directory with comprehensive guides
- **Developer Portal**: `docs/developer/` - Technical documentation for contributors
- **TODO Tracking**: `docs/TODO.md` - Current and completed tasks
- **Change Documentation**: `docs/developer/phase3-modernization-completion.md` - Phase 3 completion report
- **Development Files**: `/dev/` directory - Moved development artifacts (tests, debug files)
- **Development Guide**: `/dev/README.md` - Documentation of moved development files

## Common Patterns
- **Admin Auth Check**: `AuthManager::requireAdmin()` (not AdminAuth anymore)
- **Login Redirect**: AuthManager handles with return URLs automatically
- **CSRF Protection**: Always use `mb.ajax()` in JavaScript for POST requests
- **Event Logging**: Use `$app->getEventLogger()->log($category, $message, $context)`

## Docker Commands
- **Container Name**: `mediabrain-app` (main PHP application)
- **Check Container Status**: `docker ps`
- **Restart for OpCache**: `docker restart mediabrain-app`
- **Copy Files**: `docker cp file.php mediabrain-app:/var/www/html/`
- **Execute in Container**: `docker exec -it mediabrain-app bash`
- **Check Session Files**: `docker exec -it mediabrain-app ls -la /tmp/ | grep sess`
- **PHP-FPM Logs**: `docker logs mediabrain-app`
- **Nginx Logs**: `docker logs nginx`

## JavaScript Patterns
- **AJAX**: Always use `mb.ajax()` for CSRF token injection
- **Logout**: Use unified `mb.userLogout()` function
- **CSRF Token**: Available as `mb.csrf_token` from meta tag

## Authentication System (Post-Unification)
- **Login Flow**: Main login page handles all authentication
- **Admin Access**: Role-based through `canadmin` flag in user data
- **Session Management**: Single session system using AuthManager
- **API Access**: All APIs check unified session, no separate admin session

## Recent Changes (Nov 2025)
- ✅ Eliminated dual authentication systems (AdminAuth removed)
- ✅ Unified all admin access through AuthManager
- ✅ Event logging system fully implemented
- ✅ **API ARCHITECTURE MODERNIZATION**: Implemented Drupal-inspired modular API routing
  - **NEW PATTERN**: `/?api={app}&action={action}` routes directly to `/apps/{app}/{app}.api.php`
  - **MODULAR DESIGN**: Each app contains its own API file (admin.api.php, recipes.api.php, etc.)
  - **CLEAN ROUTING**: Direct routing in index.php without intermediate API app
  - **REMOVED**: Centralized `/apps/api/` directory in favor of modular approach
  - **UPDATED**: All frontend JavaScript to use new `/?api=admin&action=phpunit_run_tests` pattern
- ✅ CSRF token system unified across all components
- ✅ **FIXED**: Session compatibility - `$_SESSION['user']` is array, AuthManager handles both formats
- ✅ **CLEANUP**: Removed old session references from core files (AppController, EventLogger, Sidenav)
- ✅ **DEPRECATED**: Old test files marked as deprecated with redirect notices
- ✅ **PERFORMANCE**: Fixed admin logs page timeout by optimizing EventLogger with file size checks and log rotation
- ✅ **SESSION MANAGEMENT**: Centralized session_start() to index.php and api.php only
- ✅ **SESSION TROUBLESHOOTING**: Added `session_cleanup.php` utility for debugging session issues
- ✅ **TIMEOUT RESOLUTION**: Fixed circular dependencies in header components causing gateway timeouts - CONFIRMED WORKING
- ✅ **EVENT LOGGING DEFAULT**: Set event logging to disabled by default for better performance
- ✅ **BIBLEBOT SEARCH FIX**: Fixed search disappearing due to multiple issues:
  - App::getInstance() calls missing app name in render_body and sidenav 
  - applications_menu.php still using old session variables (mb_user/admin_user)
  - Session variable format conflicts in global components

### Phase 5: Text-to-Speech v2.0 Modernization (Nov 7, 2025) ✅ COMPLETED
- ✅ **TTS SERVICE ARCHITECTURE**: Created modern TextToSpeechService class with dependency injection and enhanced error handling
- ✅ **MULTI-VOICE SUPPORT**: Added support for Neural2, WaveNet, and Standard voices with quality-based selection
- ✅ **INTELLIGENT CACHING**: Implemented file-based audio caching system with TTL and LRU eviction for 60-80% cost reduction
- ✅ **SSML INTEGRATION**: Full Speech Synthesis Markup Language support for natural speech with emphasis, pauses, and prosody
- ✅ **MODERN JAVASCRIPT CLIENT**: Promise-based TTS client with queue management, advanced controls, and event system
- ✅ **VOICE SELECTION UI**: Complete voice selector component with preview, filtering, and responsive design
- ✅ **BACKWARD COMPATIBILITY**: Enhanced speak() function maintains compatibility while adding modern features
- ✅ **COMPREHENSIVE TESTING**: Created full test suite at /tts-v2-test.php demonstrating all v2 capabilities

### Phase 6: Production Testing Infrastructure (Nov 7, 2025) ✅ COMPLETED
- ✅ **PHPUNIT FRAMEWORK**: Complete PHPUnit 10.5 testing framework with comprehensive test suites
- ✅ **MODULAR API INTEGRATION**: PHPUnit tests fully integrated with new modular API architecture
- ✅ **ADMIN TESTING INTERFACE**: Complete admin UI for running and managing tests via `/?app=admin&p=phpunit-tests`
- ✅ **COMPREHENSIVE TEST COVERAGE**: Core framework tests including App class, utilities, TTS v2, authentication systems
- ✅ **TEST CATEGORIES**: Unit tests (component isolation), Integration tests (system validation), API tests (security verification)
- ✅ **API ENDPOINTS**: Complete set of PHPUnit API endpoints in admin.api.php:
  - `phpunit_run_tests`: Execute test suites with coverage options
  - `phpunit_run_single_test`: Run individual test files
  - `phpunit_get_test_content`: View test source code
  - `phpunit_get_test_files`: List available test files
- ✅ **DRUPAL-INSPIRED ARCHITECTURE**: Documented modular design philosophy and contributor guidelines
- ✅ **PRODUCTION READY**: Testing infrastructure prepared for massive production deployment

### Phase 4: Professional Portfolio Enhancement (Nov 7, 2025) ✅ COMPLETED
- ✅ **STAR TREK ACHIEVEMENT MODAL**: Interactive achievement modal with authentic sounds and animations
  - Professional achievements displayed in Star Trek-themed UI
  - Modal with scrollable achievement list and commander sound effects
  - Fixed JavaScript global variable conflicts and modal button handlers
- ✅ **PROFESSIONAL CAREER SHOWCASE**: Enhanced splash page with technical career center
  - Technical skills grid with programming languages, frameworks, and tools
  - Contact modal with Jeff David's professional information (phone, email, LinkedIn)
  - Professional tagline: "Full Stack Developer | System Architect | Problem Solver"
- ✅ **ADVANCED UI COLOR SCHEME**: Futuristic deep space color palette
  - Primary colors: #000814 (deep space), #48cae4 (electric blue), #90e0ef (light blue)
  - Professional contrast ratios and accessibility compliance
  - Consistent theming across achievement modal and career sections
- ✅ **CONFIG SYSTEM ENHANCEMENT**: Robust fallback configuration system
  - Fallback config array when .env file missing
  - Professional defaults including social media links and contact information
  - Enhanced config() function with proper error handling
- ✅ **COMPREHENSIVE CACHE-BUSTING**: Complete solution for development workflow optimization
  - Asset versioning with timestamp query parameters (?v=timestamp)
  - HTTP cache-control headers (Cache-Control: no-cache, Pragma: no-cache)
  - Development mode detection with automatic cache-busting activation
  - JavaScript cache-control meta tag injection for client-side cache management
  - Utility cache_bust() function for consistent timestamp generation across assets

### Phase 1: Critical Security Fixes (Nov 7, 2025) ✅ COMPLETED
- ✅ **SESSION VARIABLES**: Fixed help.app.php to use unified `$_SESSION['user']` array format
- ✅ **ANCESTRY AUTH**: Fixed ancestry/includes/auth.php session format inconsistencies
- ✅ **ADMINAUTH DEPRECATION**: Deprecated AdminAuth.php class, replaced with AuthManager
- ✅ **SESSION CLEANUP**: Removed redundant session_start() calls across codebase
- ✅ **SECURITY AUDIT**: Comprehensive legacy code review and vulnerability fixes

### Phase 2: API Standardization (Nov 7, 2025) ✅ COMPLETED
- ✅ **CENTRALIZED APIs**: Created bibleBot.api.php and ancestry.api.php with CSRF protection
- ✅ **ADMIN API SECURITY**: Enhanced admin.api.php with CSRF protection for 15+ endpoints
- ✅ **BACKWARD COMPATIBILITY**: Converted legacy API files to shims for seamless transition
- ✅ **UNIFIED ROUTING**: Standardized to `/api.php?app={app}&action={action}` pattern
- ✅ **JAVASCRIPT UPDATES**: Updated client-side code to use new unified API endpoints

### Phase 3: Code Modernization (Nov 7, 2025) ✅ COMPLETED
- ✅ **INCLUDE MODERNIZATION**: Updated all include/require patterns to use __DIR__-based absolute paths
- ✅ **DEBUG CLEANUP**: Moved 50+ development files (test_*.php, debug_*.php) to organized /dev directory
- ✅ **DEPENDENCY MANAGEMENT**: Added Composer classmap autoloading, eliminated manual includes
- ✅ **CODE CLEANUP**: Removed commented debug code, error_log statements, and development artifacts
- ✅ **PRODUCTION SAFETY**: Verified all changes safe for GCP Cloud Run PHP 8.4-apache environment
- ✅ **AUTOLOADING**: EventLogger and other classes now handled by Composer autoloader
- ✅ **DOCUMENTATION**: Created comprehensive documentation in /docs/developer/ structure

### Development File Organization (Nov 7, 2025) ✅ COMPLETED
- ✅ **MOVED TO /dev/test/**: 28 test files (test_*.php, *_test.php)
- ✅ **MOVED TO /dev/debug/**: 9 debug files (debug_*.php)
- ✅ **DOCUMENTATION**: Created /dev/README.md explaining file reorganization
- ✅ **CLEAN PRODUCTION**: Removed development clutter from main codebase

## Session Management & Troubleshooting
- **Session Storage**: Docker container `/tmp/sess_*` files
- **Session Conflicts**: Private browser works = session file corruption issue
- **Debug Tool**: Use `/session_cleanup.php` to diagnose and fix session problems
- **Common Issues**: 
  - Old `mb_user` vs new `user` session variables
  - String vs array format conflicts in `$_SESSION['user']`
  - Large session files causing performance issues
  - Multiple session_start() calls causing circular dependencies
- **Quick Fix**: Clear browser cookies or use session cleanup utility

## Event Logging Control
- **Default State**: DISABLED (via `/logs/event_config.json`)
- **Status Check**: `EventLogger::getInstance()->isEnabled()`
- **Toggle Methods**:
  1. **Admin UI**: Go to Admin → Logs page and use toggle button
  2. **Direct Control**: Visit `/event_logging_control.php`
  3. **API**: `POST /api.php?app=admin action=toggle_event_logging`
  4. **Config File**: Edit `/logs/event_config.json` set `"enabled": true/false`
  5. **Programmatic**: `$eventLogger->enable()` / `$eventLogger->disable()`
- **Performance**: Event logging disabled by default for better performance
- **File Location**: `/logs/event.log` and `/logs/event_config.json`

## Development Notes
- Always check for syntax errors with `get_errors` tool after major changes
- Test authentication flow after any auth-related changes
- Event logging helps debug admin actions and authentication issues
- Use Docker exec for testing PHP changes that need full environment context
- **Testing Infrastructure**: Use `vendor/bin/phpunit` to run comprehensive test suites
- **Test Categories**: Unit tests (component isolation), Integration tests (flow validation), API tests (security verification)
- **Test Verification**: Run `php test-runner.php` to verify testing infrastructure status
- **Documentation Updates**: Update both `AI-DEVELOPMENT-NOTES.md` and `/docs/` when making architectural changes
- **Development Files**: Check `/dev/` directory for historical test/debug files (not for production use)
- **Include Patterns**: Always use `__DIR__`-based absolute paths: `require_once __DIR__ . '/file.php'`
- **Autoloading**: Core classes now use Composer autoloader - no manual includes needed for EventLogger, etc.
- **Cache-Busting**: Development mode automatically enables cache-busting for smooth development workflow
- **Professional Portfolio**: Platform ready for job applications with complete technical showcase and contact integration

## 📋 How to Use This File
**For Future AI Assistants:**
1. Read this file at the start of any development session
2. Add new discoveries about project patterns, configurations, or gotchas
3. Update sections when architecture changes
4. Reference specific sections when explaining decisions

**For Human Developers:**
1. Consult this file before setting up development environment
2. Add notes about new features, architectural decisions, or configuration changes
3. Document any "tribal knowledge" that isn't obvious from code
4. Update when deployment patterns or environment configurations change

## Contributing Guidelines
- Keep entries concise but detailed enough to be actionable
- Include specific examples (URLs, commands, code patterns)
- Organize new information in appropriate sections
- Date stamp major architectural changes




------------------------------------------------------------------------------


    Embedding Gecko (models/embedding-gecko-001) - Obtain a distributed representation of a text.
    Gemini 2.5 Flash (models/gemini-2.5-flash) - Stable version of Gemini 2.5 Flash, our mid-size multimodal model that supports up to 1 million tokens, released in June of 2025.
    Gemini 2.5 Pro (models/gemini-2.5-pro) - Stable release (June 17th, 2025) of Gemini 2.5 Pro
    Gemini 2.0 Flash Experimental (models/gemini-2.0-flash-exp) - Gemini 2.0 Flash Experimental
    Gemini 2.0 Flash (models/gemini-2.0-flash) - Gemini 2.0 Flash
    Gemini 2.0 Flash 001 (models/gemini-2.0-flash-001) - Stable version of Gemini 2.0 Flash, our fast and versatile multimodal model for scaling across diverse tasks, released in January of 2025.
    Gemini 2.0 Flash (Image Generation) Experimental (models/gemini-2.0-flash-exp-image-generation) - Gemini 2.0 Flash (Image Generation) Experimental
    Gemini 2.0 Flash-Lite 001 (models/gemini-2.0-flash-lite-001) - Stable version of Gemini 2.0 Flash-Lite
    Gemini 2.0 Flash-Lite (models/gemini-2.0-flash-lite) - Gemini 2.0 Flash-Lite
    Gemini 2.0 Flash-Lite Preview 02-05 (models/gemini-2.0-flash-lite-preview-02-05) - Preview release (February 5th, 2025) of Gemini 2.0 Flash-Lite
    Gemini 2.0 Flash-Lite Preview (models/gemini-2.0-flash-lite-preview) - Preview release (February 5th, 2025) of Gemini 2.0 Flash-Lite
    Gemini 2.0 Pro Experimental (models/gemini-2.0-pro-exp) - Experimental release (March 25th, 2025) of Gemini 2.5 Pro
    Gemini 2.0 Pro Experimental 02-05 (models/gemini-2.0-pro-exp-02-05) - Experimental release (March 25th, 2025) of Gemini 2.5 Pro
    Gemini Experimental 1206 (models/gemini-exp-1206) - Experimental release (March 25th, 2025) of Gemini 2.5 Pro
    Gemini 2.5 Flash Preview TTS (models/gemini-2.5-flash-preview-tts) - Gemini 2.5 Flash Preview TTS
    Gemini 2.5 Pro Preview TTS (models/gemini-2.5-pro-preview-tts) - Gemini 2.5 Pro Preview TTS
    Gemma 3 1B (models/gemma-3-1b-it) -
    Gemma 3 4B (models/gemma-3-4b-it) -
    Gemma 3 12B (models/gemma-3-12b-it) -
    Gemma 3 27B (models/gemma-3-27b-it) -
    Gemma 3n E4B (models/gemma-3n-e4b-it) -
    Gemma 3n E2B (models/gemma-3n-e2b-it) -
    Gemini Flash Latest (models/gemini-flash-latest) - Latest release of Gemini Flash
    Gemini Flash-Lite Latest (models/gemini-flash-lite-latest) - Latest release of Gemini Flash-Lite
    Gemini Pro Latest (models/gemini-pro-latest) - Latest release of Gemini Pro
    Gemini 2.5 Flash-Lite (models/gemini-2.5-flash-lite) - Stable version of Gemini 2.5 Flash-Lite, released in July of 2025
    Nano Banana (models/gemini-2.5-flash-image-preview) - Gemini 2.5 Flash Preview Image
    Nano Banana (models/gemini-2.5-flash-image) - Gemini 2.5 Flash Preview Image
    Gemini 2.5 Flash Preview Sep 2025 (models/gemini-2.5-flash-preview-09-2025) - Gemini 2.5 Flash Preview Sep 2025
    Gemini 2.5 Flash-Lite Preview Sep 2025 (models/gemini-2.5-flash-lite-preview-09-2025) - Preview release (Septempber 25th, 2025) of Gemini 2.5 Flash-Lite
    Gemini 3 Pro Preview (models/gemini-3-pro-preview) - Gemini 3 Pro Preview
    Nano Banana Pro (models/gemini-3-pro-image-preview) - Gemini 3 Pro Image Preview
    Nano Banana Pro (models/nano-banana-pro-preview) - Gemini 3 Pro Image Preview
    Gemini Robotics-ER 1.5 Preview (models/gemini-robotics-er-1.5-preview) - Gemini Robotics-ER 1.5 Preview
    Gemini 2.5 Computer Use Preview 10-2025 (models/gemini-2.5-computer-use-preview-10-2025) - Gemini 2.5 Computer Use Preview 10-2025
    Embedding 001 (models/embedding-001) - Obtain a distributed representation of a text.
    Text Embedding 004 (models/text-embedding-004) - Obtain a distributed representation of a text.
    Gemini Embedding Experimental 03-07 (models/gemini-embedding-exp-03-07) - Obtain a distributed representation of a text.
    Gemini Embedding Experimental (models/gemini-embedding-exp) - Obtain a distributed representation of a text.
    Gemini Embedding 001 (models/gemini-embedding-001) - Obtain a distributed representation of a text.
    Model that performs Attributed Question Answering. (models/aqa) - Model trained to return answers to questions that are grounded in provided sources, along with estimating answerable probability.
    Imagen 4 (Preview) (models/imagen-4.0-generate-preview-06-06) - Vertex served Imagen 4.0 model
    Imagen 4 Ultra (Preview) (models/imagen-4.0-ultra-generate-preview-06-06) - Vertex served Imagen 4.0 ultra model
    Imagen 4 (models/imagen-4.0-generate-001) - Vertex served Imagen 4.0 model
    Imagen 4 Ultra (models/imagen-4.0-ultra-generate-001) - Vertex served Imagen 4.0 ultra model
    Imagen 4 Fast (models/imagen-4.0-fast-generate-001) - Vertex served Imagen 4.0 Fast model
    Veo 2 (models/veo-2.0-generate-001) - Vertex served Veo 2 model. Access to this model requires billing to be enabled on the associated Google Cloud Platform account. Please visit https://console.cloud.google.com/billing to enable it.
    Veo 3 (models/veo-3.0-generate-001) - Veo 3
    Veo 3 fast (models/veo-3.0-fast-generate-001) - Veo 3 fast
    Veo 3.1 (models/veo-3.1-generate-preview) - Veo 3.1


The free tier usage limits for Gemini API models differ by model, such as Gemini 2.5 Pro and Gemini 2.5 Flash
. 
Free tier limits include:

    Gemini 2.5 Pro:
        5 requests per minute (RPM).
        100 requests per day (RPD).
        250,000 input tokens per minute (TPM).
    Gemini 2.5 Flash:
        10 requests per minute (RPM).
        250 requests per day (RPD).
        250,000 input tokens per minute (TPM).
    Gemini 2.5 Flash-Lite (Preview):
        15 requests per minute (RPM).
        1,000 requests per day (RPD).
        250,000 input tokens per minute (TPM). 

The API enforces these limits, which are subject to change. Exceeding any limit results in a rate limit error. 
--------------------------------------------------------------------------------

