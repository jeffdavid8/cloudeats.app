# MediaBrain Legacy Code Audit Report
*Generated: November 7, 2025*

## Executive Summary

This comprehensive audit reviewed all apps in the MediaBrain project for legacy code patterns, security issues, and inconsistent implementations across API usage, session management, authentication, CSRF protection, and outdated patterns.

**Key Finding:** While significant modernization has been done recently, several critical legacy patterns remain that pose security and maintainability risks.

---

## 🔴 **CRITICAL ISSUES (Fix Immediately)**

### 1. **Session Management Inconsistencies**
**Risk Level: HIGH** - Can cause authentication bypass

**Problems Found:**
- **Legacy session variables still in use:**
  - `help.app.php`: Still checking `$_SESSION['mb_user']` and `$_SESSION['admin_user']`
  - `ancestry/includes/auth.php`: Checking old session variables
  - Multiple files have inconsistent session variable handling

**Files Requiring Immediate Fix:**
```php
// html/apps/help/help.app.php (lines 47-51)
if (isset($_SESSION['mb_user']) || isset($_SESSION['admin_user'])) {
    $currentUser = $_SESSION['mb_user'] ?? $_SESSION['admin_user'];
}
if (isset($_SESSION['admin_user']) || isset($_SESSION['admin_username'])) {
    // Legacy pattern
}

// html/apps/ancestry/includes/auth.php (line 14)
if (isset($_SESSION['mb_user']) || isset($_SESSION['admin_user']) || isset($_SESSION['admin_username'])) {
    // Should use unified $_SESSION['user']
}
```

### 2. **Deprecated AdminAuth Usage**
**Risk Level: HIGH** - Security vulnerability

**Problems Found:**
- `AdminAuth.php` class still exists and contains legacy CSRF token management
- Some components may still rely on old admin authentication patterns

**Files Requiring Fix:**
- `html/apps/admin/includes/AdminAuth.php` - Should be deprecated/removed
- Any references to `AdminAuth` should be replaced with `AuthManager`

### 3. **Multiple session_start() Calls**
**Risk Level: MEDIUM** - Can cause session conflicts

**Files with session_start() issues:**
```php
// These should be removed - session already started in index.php
- html/apps/admin/admin.app.php (line 27)
- html/apps/admin/admin.api.php (line 16)
- html/apps/admin/views/migrate.php (line 9)
- html/apps/ancestry/includes/auth.php (line 10)
- Multiple other files
```

---

## 🟡 **MEDIUM PRIORITY ISSUES**

### 4. **API Implementation Inconsistencies**
**Risk Level: MEDIUM** - Inconsistent security and patterns

**Patterns Found:**
1. **Three Different API Patterns:**
   - **Modern:** `/api.php?app={app_name}` → `{app}.api.php` (admin app)
   - **Legacy:** Direct `apps/{app}/api.php` files (bibleBot, ancestry)
   - **Mixed:** Some apps use both patterns

2. **Inconsistent CSRF Protection:**
   - **Good:** BibleBot has comprehensive CSRF protection with whitelist
   - **Inconsistent:** Some endpoints may lack CSRF protection
   - **Legacy:** AdminAuth has separate CSRF token management

### 5. **Legacy Include Patterns**
**Risk Level: MEDIUM** - Maintainability issues

**Problems Found:**
- Heavy use of `require_once __DIR__ . '/../../includes/...'` 
- Some `mb_require()` usage that should be standardized
- Inconsistent path resolution

---

## 🟢 **LOW PRIORITY ISSUES**

### 6. **Code Quality & Modernization**
- Commented-out `error_log()` statements throughout codebase
- Some debug code left in production files
- Inconsistent error handling patterns

---

## 📋 **DETAILED FINDINGS BY APP**

### **Admin App** ⭐ (Best Practices)
- ✅ Uses modern AuthManager consistently
- ✅ Comprehensive API with proper CSRF protection
- ✅ Good session handling
- ❌ Still has legacy AdminAuth.php file
- ❌ Multiple session_start() calls

### **BibleBot App** ⭐ (Good Security)
- ✅ Excellent CSRF protection with action whitelist
- ✅ Proper App::getInstance() usage (recently fixed)
- ✅ Good API structure
- ❌ Uses direct api.php instead of centralized pattern

### **Ancestry App** ⚠️ (Mixed)
- ✅ Has proper authentication checks
- ❌ Uses legacy session variables in auth.php
- ❌ Complex authentication patterns with multiple files
- ❌ Direct API pattern instead of centralized

### **Recipes App** ⚠️ (Needs Work)
- ✅ Has admin requirement checks
- ❌ Uses `mb_require()` patterns
- ❌ No centralized API file found
- ❌ Permission helper dependencies

### **Help App** ❌ (Needs Immediate Fix)
- ❌ **CRITICAL:** Still checking legacy session variables
- ❌ Mixed authentication patterns
- ❌ No modern AuthManager integration

### **Weather/Splash Apps** ✅ (Simple/Clean)
- ✅ Simple, no major issues
- ✅ Basic authentication patterns

---

## 🛠️ **RECOMMENDED ACTIONS (Prioritized)**

### **PHASE 1: Critical Security Fixes (Do First)**

1. **Fix Session Variable Inconsistencies**
   ```bash
   Priority: CRITICAL
   Effort: 2-3 hours
   Files: help.app.php, ancestry/auth.php, others
   Action: Replace all $_SESSION['mb_user']/$_SESSION['admin_user'] with $_SESSION['user']
   ```

2. **Remove Legacy AdminAuth**
   ```bash
   Priority: CRITICAL  
   Effort: 1-2 hours
   Files: AdminAuth.php and any references
   Action: Deprecate AdminAuth class, ensure all code uses AuthManager
   ```

3. **Consolidate Session Management**
   ```bash
   Priority: HIGH
   Effort: 1-2 hours
   Files: All files with session_start()
   Action: Remove redundant session_start() calls, centralize in index.php/api.php
   ```

### **PHASE 2: API Standardization (Medium Term)**

4. **Standardize API Patterns**
   ```bash
   Priority: MEDIUM
   Effort: 4-6 hours
   Action: Migrate all apps to use centralized /api.php?app={app} pattern
   Benefits: Consistent security, easier maintenance
   ```

5. **Audit and Fix CSRF Protection**
   ```bash
   Priority: MEDIUM
   Effort: 2-3 hours
   Action: Ensure all state-changing endpoints have CSRF protection
   ```

### **PHASE 3: Code Modernization (Long Term)**

6. **Standardize Include Patterns**
   ```bash
   Priority: LOW
   Effort: 2-3 hours
   Action: Replace direct require_once with standardized helper functions
   ```

7. **Clean Up Debug Code**
   ```bash
   Priority: LOW
   Effort: 1 hour
   Action: Remove commented error_log statements and debug code
   ```

---

## 🧪 **TESTING RECOMMENDATIONS**

After each phase:
1. **Authentication Testing:** Verify all apps work with unified session system
2. **CSRF Testing:** Test all POST endpoints for CSRF protection
3. **Session Testing:** Verify no session conflicts or leakage
4. **Permission Testing:** Ensure admin-only functions are properly protected

---

## 🔒 **SECURITY CHECKLIST**

- [ ] All apps use unified `$_SESSION['user']` format
- [ ] No legacy AdminAuth references remain
- [ ] All session_start() calls are centralized
- [ ] All POST endpoints have CSRF protection
- [ ] All admin functions use AuthManager::requireAdmin()
- [ ] No direct file includes use relative paths
- [ ] All authentication checks are consistent

---

## 📝 **NOTES FOR DEVELOPERS**

- **Session Format:** Always use `$_SESSION['user']['username']` for username
- **Admin Checks:** Always use `AuthManager::userIsAdmin($_SESSION['user'])`
- **CSRF:** Use `AuthManager::validateCsrf()` for all state changes
- **API Pattern:** Prefer `/api.php?app={app_name}` over direct app API files
- **Authentication:** Use `AuthManager::requireLogin()` and `AuthManager::requireAdmin()`

---

**Next Steps:** Start with Phase 1 critical fixes, then proceed systematically through each phase while maintaining testing coverage.