<?php
// admin role form (create/edit)
require_once __DIR__ . '/../includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();
$permissionsSummary = $permissionsMatrix->getPermissionsSummary();

$isEdit = isset($_GET['role']);
$roleName = $_GET['role'] ?? '';
$roleData = $isEdit ? ($permissionsSummary['roles'][$roleName] ?? null) : null;

if ($isEdit && !$roleData) {
    echo '<div class="card-panel red lighten-4"><span class="red-text">Role not found</span></div>';
    echo '<a href="?app=admin&p=roles" class="btn">Back to Roles</a>';
    return;
}

// Get all available apps for permission assignment
$allApps = $permissionsSummary['apps'] ?? [];
$allRoles = $permissionsSummary['roles'] ?? [];
?>

<div class="row">
    <div class="col s12">
        <h4><?php echo $isEdit ? 'Edit Role: ' . htmlspecialchars($roleData['name']) : 'Create New Role'; ?></h4>
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=roles" class="breadcrumb">Roles</a>
                    <a href="#" class="breadcrumb"><?php echo $isEdit ? 'Edit' : 'Create'; ?></a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div id="role-messages"></div>

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <form id="role-form">
                    <input type="hidden" id="is-edit" value="<?php echo $isEdit ? 'true' : 'false'; ?>">
                    <input type="hidden" id="original-role-name" value="<?php echo htmlspecialchars($roleName); ?>">
                    
                    <div class="row">
                        <div class="col s12 m6">
                            <div class="input-field">
                                <input type="text" id="role-name" name="role_name" 
                                       value="<?php echo htmlspecialchars($roleData['name'] ?? ''); ?>" 
                                       required <?php echo ($isEdit && in_array($roleName, ['admin', 'guest', 'user', 'editor'])) ? 'readonly' : ''; ?>>
                                <label for="role-name" class="<?php echo $roleData ? 'active' : ''; ?>">Role Name</label>
                                <?php if ($isEdit && in_array($roleName, ['admin', 'guest', 'user', 'editor'])): ?>
                                <span class="helper-text">System roles cannot be renamed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col s12 m6">
                            <div class="input-field">
                                <input type="text" id="role-key" name="role_key" 
                                       value="<?php echo htmlspecialchars($roleName); ?>" 
                                       required <?php echo $isEdit ? 'readonly' : ''; ?>>
                                <label for="role-key" class="<?php echo $roleName ? 'active' : ''; ?>">Role Key (unique identifier)</label>
                                <?php if ($isEdit): ?>
                                <span class="helper-text">Role keys cannot be changed after creation</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12">
                            <div class="input-field">
                                <textarea id="role-description" name="role_description" class="materialize-textarea"><?php echo htmlspecialchars($roleData['description'] ?? ''); ?></textarea>
                                <label for="role-description" class="<?php echo $roleData ? 'active' : ''; ?>">Description</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Role Inheritance -->
                    <div class="row">
                        <div class="col s12">
                            <h6>Role Inheritance</h6>
                            <p class="grey-text">Select roles that this role should inherit permissions from:</p>
                            <?php foreach ($allRoles as $inheritRoleName => $inheritRoleData): ?>
                                <?php if ($inheritRoleName !== $roleName): // Don't allow self-inheritance ?>
                                <p>
                                    <label>
                                        <input type="checkbox" name="inherit_roles[]" value="<?php echo htmlspecialchars($inheritRoleName); ?>" 
                                               <?php echo (isset($roleData['permissions']['inherit']) && in_array($inheritRoleName, $roleData['permissions']['inherit'])) ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($inheritRoleData['name']); ?> (<?php echo htmlspecialchars($inheritRoleName); ?>)</span>
                                    </label>
                                </p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- App Permissions -->
                    <div class="row">
                        <div class="col s12">
                            <h6>Application Access</h6>
                            <p class="grey-text">Select which applications this role can access:</p>
                            <div class="app-permissions-grid">
                                <?php foreach ($allApps as $appKey => $appData): ?>
                                <div class="col s12 m6 l4">
                                    <div class="card app-permission-card">
                                        <div class="card-content">
                                            <h6>
                                                <label>
                                                    <input type="checkbox" name="app_access[]" value="<?php echo htmlspecialchars($appKey); ?>" 
                                                           class="app-checkbox" data-app="<?php echo htmlspecialchars($appKey); ?>"
                                                           <?php echo (isset($roleData['permissions']["apps.{$appKey}"]) && in_array('access', $roleData['permissions']["apps.{$appKey}"])) ? 'checked' : ''; ?>>
                                                    <span class="app-title"><?php echo htmlspecialchars($appData['name']); ?></span>
                                                </label>
                                            </h6>
                                            <p class="grey-text"><?php echo htmlspecialchars($appData['description']); ?></p>
                                            
                                            <!-- Feature-level permissions -->
                                            <div class="features-section" style="<?php echo (isset($roleData['permissions']["apps.{$appKey}"]) && in_array('access', $roleData['permissions']["apps.{$appKey}"])) ? '' : 'display:none;'; ?>">
                                                <h6>Features:</h6>
                                                <?php foreach ($appData['features'] as $featureName => $actions): ?>
                                                <div class="feature-permissions">
                                                    <p><strong><?php echo htmlspecialchars($featureName); ?>:</strong></p>
                                                    <?php foreach ($actions as $action): ?>
                                                    <p>
                                                        <label>
                                                            <input type="checkbox" name="feature_permissions[<?php echo htmlspecialchars($appKey); ?>][<?php echo htmlspecialchars($featureName); ?>][]" 
                                                                   value="<?php echo htmlspecialchars($action); ?>"
                                                                   <?php echo (isset($roleData['permissions']["apps.{$appKey}.features.{$featureName}"]) && in_array($action, $roleData['permissions']["apps.{$appKey}.features.{$featureName}"])) ? 'checked' : ''; ?>>
                                                            <span><?php echo htmlspecialchars($action); ?></span>
                                                        </label>
                                                    </p>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Permissions -->
                    <div class="row">
                        <div class="col s12">
                            <h6>System Permissions</h6>
                            <p class="grey-text">Advanced system-level permissions:</p>
                            <p>
                                <label>
                                    <input type="checkbox" name="system_permissions[]" value="system.permissions.manage" 
                                           <?php echo (isset($roleData['permissions']['system.permissions']) && in_array('manage', $roleData['permissions']['system.permissions'])) ? 'checked' : ''; ?>>
                                    <span>Manage Permissions (can edit user permissions and roles)</span>
                                </label>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12">
                            <button type="submit" class="btn green">
                                <i class="material-icons left"><?php echo $isEdit ? 'save' : 'add'; ?></i>
                                <?php echo $isEdit ? 'Update Role' : 'Create Role'; ?>
                            </button>
                            <a href="?app=admin&p=roles" class="btn grey">
                                <i class="material-icons left">cancel</i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.app-permissions-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.app-permission-card {
    margin: 0;
    min-height: 200px;
}

.app-permission-card .card-content {
    padding: 15px;
}

.app-title {
    font-weight: bold;
    color: #333;
}

.features-section {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.feature-permissions {
    margin-left: 15px;
    margin-bottom: 10px;
}

.feature-permissions p {
    margin: 5px 0;
}

.admin-breadcrumb {
    margin-bottom: 20px;
}

#role-messages {
    margin-bottom: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize materialize components
    M.updateTextFields();
    M.textareaAutoResize(document.getElementById('role-description'));
    
    // Handle app checkbox changes to show/hide features
    document.querySelectorAll('.app-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const appKey = this.dataset.app;
            const featuresSection = this.closest('.app-permission-card').querySelector('.features-section');
            
            if (this.checked) {
                featuresSection.style.display = 'block';
            } else {
                featuresSection.style.display = 'none';
                // Uncheck all feature permissions when app is unchecked
                featuresSection.querySelectorAll('input[type="checkbox"]').forEach(featureCheckbox => {
                    featureCheckbox.checked = false;
                });
            }
        });
    });
    
    // Auto-generate role key from role name for new roles
    const isEdit = document.getElementById('is-edit').value === 'true';
    if (!isEdit) {
        document.getElementById('role-name').addEventListener('input', function() {
            const roleKey = this.value.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
            document.getElementById('role-key').value = roleKey;
            M.updateTextFields();
        });
    }
    
    // Handle form submission
    document.getElementById('role-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveRole();
    });
});

function saveRole() {
    const isEdit = document.getElementById('is-edit').value === 'true';
    const formData = new FormData(document.getElementById('role-form'));
    
    const roleData = {
        role_name: formData.get('role_name'),
        role_key: formData.get('role_key'),
        role_description: formData.get('role_description'),
        inherit_roles: formData.getAll('inherit_roles'),
        app_access: formData.getAll('app_access'),
        feature_permissions: {},
        system_permissions: formData.getAll('system_permissions')
    };
    
    // Process feature permissions
    formData.forEach((value, key) => {
        if (key.startsWith('feature_permissions[')) {
            const match = key.match(/feature_permissions\[([^\]]+)\]\[([^\]]+)\]\[\]/);
            if (match) {
                const appKey = match[1];
                const featureName = match[2];
                
                if (!roleData.feature_permissions[appKey]) {
                    roleData.feature_permissions[appKey] = {};
                }
                if (!roleData.feature_permissions[appKey][featureName]) {
                    roleData.feature_permissions[appKey][featureName] = [];
                }
                roleData.feature_permissions[appKey][featureName].push(value);
            }
        }
    });
    
    if (isEdit) {
        roleData.original_role_key = document.getElementById('original-role-name').value;
    }
    
    const action = isEdit ? 'update_role' : 'create_role';
    
    fetch(`api.php?app=admin&action=${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(roleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('role-messages').innerHTML = 
                '<div class="card-panel green lighten-4"><span class="green-text">' + 
                data.message + '</span></div>';
            
            setTimeout(() => {
                window.location.href = '?app=admin&p=roles';
            }, 1500);
        } else {
            document.getElementById('role-messages').innerHTML = 
                '<div class="card-panel red lighten-4"><span class="red-text">' + 
                (data.error || 'Save failed') + '</span></div>';
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        document.getElementById('role-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Network error: Save failed</span></div>';
    });
}
</script>