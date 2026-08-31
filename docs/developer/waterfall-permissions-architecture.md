# Waterfall Permissions Architecture

## Overview
Successfully refactored the permissions system to implement proper **waterfall precedence** where stored admin data overrides app hook defaults. This eliminates app-specific authentication logic and creates a unified, scalable permissions system.

## Waterfall Precedence Architecture ⚡

### 1. **Stored Admin Data** (Highest Priority)
- Permissions customized through admin interface
- Stored in `custom_app_roles` section of permissions.json
- Completely override app hook definitions
- Allow runtime customization without code changes

### 2. **App Hook Defaults** (Middle Priority) 
- Defined in `appname_hook_permissions()` functions
- Serve as templates/defaults for admin customization
- Automatically discovered and registered at runtime
- Provide sensible defaults for fresh installations

### 3. **System Defaults** (Fallback)
- Basic fallback permissions for undefined cases
- Ensure system stability when configurations are incomplete

## Key Architecture Changes

### ✅ **Generic OAuth Logic**
- **Before**: App-specific functions like `checkAncestryFamilyAccess()`
- **After**: Generic `checkUserAppAccess()` works for any app
- **Benefit**: No app-specific logic in authentication layer

### ✅ **Waterfall Permission Checking**
```php
private function userHasAppPermissionFromRole($username, $appName, $permission)
{
    foreach ($userPermissions['app_roles'][$appName] as $roleKey) {
        
        // 1. STORED ADMIN DATA (highest priority)
        if (isset($allPermissions['custom_app_roles'][$appName][$roleKey]['permissions'])) {
            if (in_array($permission, $allPermissions['custom_app_roles'][$appName][$roleKey]['permissions'])) {
                return true;
            }
            continue; // Skip fallback if custom exists
        }
        
        // 2. APP HOOK DEFAULTS (fallback)
        if (isset($allPermissions['app_roles'][$appName][$roleKey]['permissions'])) {
            if (in_array($permission, $allPermissions['app_roles'][$appName][$roleKey]['permissions'])) {
                return true;
            }
        }
    }
}
```

### ✅ **Admin Override Capabilities**
- `customizeAppRolePermissions()` - Override app hook permissions
- `resetAppRoleToDefaults()` - Restore app hook defaults
- `getEffectiveAppRolePermissions()` - Query waterfall precedence

## Benefits

### 🎯 **For Developers**
- **Clean Separation**: No app-specific logic in authentication
- **Reusable Patterns**: OAuth works for any app
- **Maintainable Code**: Single source of truth for permissions

### 👑 **For Administrators**
- **Runtime Control**: Modify permissions without code changes
- **Override Flexibility**: Customize any app role through admin UI
- **Clear Precedence**: Understand exactly where permissions come from

### 🚀 **For Scalability**
- **App Independence**: New apps don't need OAuth modifications
- **Generic Systems**: Authentication layer works for any app
- **Future Proof**: Easy to add new permission types

## Usage Examples

### Generic OAuth (Works for Any App)
```php
// Before: App-specific
if ($app === 'ancestry') {
    $familyCheck = checkAncestryFamilyInvitation($email);
    // ...ancestry-specific logic...
}

// After: Generic
if ($targetApp) {
    $invitationCheck = checkUserAppInvitation($email, $targetApp);
    assignUserAppRoles($username, $email, $targetApp, $invitationCheck['roles']);
}
```

### Admin Permission Customization
```php
// Admin customizes role permissions (overrides app defaults)
$permissionsMatrix->customizeAppRolePermissions(
    'ancestry', 
    'ancestry_family_member',
    ['ancestry.family_tree.view'] // Removed research access
);

// Check what permissions are actually in effect
$effective = $permissionsMatrix->getEffectiveAppRolePermissions('ancestry', 'ancestry_family_member');
// Returns: { permissions: [...], source: 'custom_admin_override', customized: true }

// Reset back to app defaults
$permissionsMatrix->resetAppRoleToDefaults('ancestry', 'ancestry_family_member');
```

### Clean App Implementation
```php
// Before: Hardcoded ancestry role checks
return $permissionsMatrix->userHasAppRole($username, 'ancestry', 'ancestry_family_member') ||
       $permissionsMatrix->userHasAppRole($username, 'ancestry', 'ancestry_family_contributor') ||
       $permissionsMatrix->userHasAppRole($username, 'ancestry', 'ancestry_family_admin');

// After: Generic app access
return $permissionsMatrix->canAccessApp($username, 'ancestry');
```

## File Changes Summary

### Modified Files
1. **`oauth/google.php`** - Removed ancestry-specific logic, added generic app checking
2. **`PermissionsMatrix.php`** - Added waterfall precedence and admin override methods
3. **`ancestry.app.php`** - Updated to use generic permission checking
4. **`family_login.php`** - Generic app parameter handling

### New Methods Added
- `checkUserAppAccess()` - Generic app access validation
- `checkUserAppInvitation()` - Generic invitation checking
- `assignUserAppRoles()` - Generic role assignment
- `customizeAppRolePermissions()` - Admin override capability
- `resetAppRoleToDefaults()` - Restore app defaults
- `getEffectiveAppRolePermissions()` - Query waterfall precedence

## Success Metrics ✅

- ✅ **Eliminated App-Specific OAuth Logic**: Authentication layer is now generic
- ✅ **Implemented Waterfall Precedence**: Admin data properly overrides app defaults
- ✅ **Enhanced Admin Control**: Runtime permission management without code changes
- ✅ **Improved Scalability**: Pattern works for any future app
- ✅ **Maintained Functionality**: All existing features work seamlessly

This refactoring successfully addresses the concern about app-specific logic in OAuth handlers while providing a robust, scalable architecture that follows proper separation of concerns.