<?php
// admin roles management view
require_once __DIR__ . '/../includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();
$permissionsSummary = $permissionsMatrix->getPermissionsSummary();
?>
<div class="row">
    <div class="col s12">
        <h4>Role Management</h4>
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=roles" class="breadcrumb">Roles</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div id="roles-messages"></div>

<!-- Role Management Section -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    System Roles
                    <a href="?app=admin&p=roles&action=add" class="btn green right">
                        <i class="material-icons left">add</i>Create Role
                    </a>
                </span>
                <div class="clearfix"></div>
                
                <div class="row">
                    <?php foreach ($permissionsSummary['roles'] as $roleName => $roleConfig): ?>
                    <div class="col s12 m6 l4">
                        <div class="card role-card <?php echo $roleName === 'admin' ? 'red' : ($roleName === 'editor' ? 'blue' : ($roleName === 'user' ? 'green' : 'grey')); ?> lighten-4">
                            <div class="card-content">
                                <span class="card-title <?php echo $roleName === 'admin' ? 'red' : ($roleName === 'editor' ? 'blue' : ($roleName === 'user' ? 'green' : 'grey')); ?>-text">
                                    <?php echo htmlspecialchars($roleConfig['name']); ?>
                                    <div class="right">
                                        <a href="?app=admin&p=roles&action=edit&role=<?php echo urlencode($roleName); ?>" 
                                           class="btn-small <?php echo $roleName === 'admin' ? 'red' : ($roleName === 'editor' ? 'blue' : ($roleName === 'user' ? 'green' : 'grey')); ?> tooltipped" 
                                           data-tooltip="Edit Role">
                                            <i class="material-icons">edit</i>
                                        </a>
                                        <?php if (!in_array($roleName, ['admin', 'guest'])): // Prevent deletion of critical roles ?>
                                        <a href="javascript:void(0)" onclick="deleteRole('<?php echo htmlspecialchars($roleName); ?>')" 
                                           class="btn-small red tooltipped" 
                                           data-tooltip="Delete Role">
                                            <i class="material-icons">delete</i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </span>
                                <p class="grey-text text-darken-2">
                                    <?php echo htmlspecialchars($roleConfig['description']); ?>
                                </p>
                                
                                <div class="divider"></div>
                                
                                <h6>App Access</h6>
                                <div class="app-permissions">
                                    <?php 
                                    $appAccess = [];
                                    foreach ($roleConfig['permissions'] as $perm => $actions) {
                                        if (strpos($perm, 'apps.') === 0 && strpos($perm, '.features') === false) {
                                            $appName = substr($perm, 5);
                                            $appAccess[] = $appName;
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($appAccess)): ?>
                                        <?php foreach ($appAccess as $app): ?>
                                        <div class="chip grey lighten-2">
                                            <?php echo htmlspecialchars($app); ?>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <em class="grey-text">No app permissions</em>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="divider" style="margin: 10px 0;"></div>
                                
                                <div class="role-stats">
                                    <p><strong>Total Permissions:</strong> <?php echo count($roleConfig['permissions']); ?></p>
                                    <?php if (isset($roleConfig['inherit'])): ?>
                                    <p><strong>Inherits from:</strong> 
                                        <?php foreach ($roleConfig['inherit'] as $inherit): ?>
                                        <div class="chip grey white-text"><?php echo htmlspecialchars($inherit); ?></div>
                                        <?php endforeach; ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <!-- Show user count for this role -->
                                    <p><strong>Users with this role:</strong> 
                                        <span id="role-users-<?php echo htmlspecialchars($roleName); ?>">Loading...</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Role Details Modal -->
<div id="role-details-modal" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4>Role Details</h4>
        <div id="role-details-content">
            <!-- Content loaded dynamically -->
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close btn-flat">Close</a>
    </div>
</div>

<style>
.role-card {
    min-height: 280px;
}

.role-card .card-title {
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.role-card .card-title .right {
    margin-top: -5px;
}

.role-card .card-title .right .btn-small {
    margin-left: 5px;
}

.app-permissions .chip {
    margin: 2px;
    font-size: 0.8rem;
}

.role-stats p {
    margin: 5px 0;
    font-size: 0.9rem;
}

.clearfix {
    clear: both;
    height: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    M.Tooltip.init(document.querySelectorAll('.tooltipped'));
    
    // Initialize modals
    M.Modal.init(document.querySelectorAll('.modal'));
    
    // Load user counts for each role
    loadRoleUserCounts();
});

function loadRoleUserCounts() {
    fetch('api.php?app=admin&action=get_role_user_counts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Object.keys(data.counts).forEach(roleName => {
                    const element = document.getElementById('role-users-' + roleName);
                    if (element) {
                        element.textContent = data.counts[roleName] + ' users';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Failed to load role user counts:', error);
        });
}

function deleteRole(roleName) {
    if (!confirm(`Are you sure you want to delete the role "${roleName}"?\n\nThis action cannot be undone and will affect all users with this role.`)) {
        return;
    }
    
    fetch('api.php?app=admin&action=delete_role', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            role: roleName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('roles-messages').innerHTML = 
                '<div class="card-panel green lighten-4"><span class="green-text">' + 
                data.message + '</span></div>';
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            document.getElementById('roles-messages').innerHTML = 
                '<div class="card-panel red lighten-4"><span class="red-text">' + 
                (data.error || 'Delete failed') + '</span></div>';
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        document.getElementById('roles-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Network error: Delete failed</span></div>';
    });
}

function viewRoleDetails(roleName) {
    fetch('api.php?app=admin&action=get_role_details&role=' + encodeURIComponent(roleName))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('role-details-modal');
                const content = document.getElementById('role-details-content');
                
                let html = `<h5>${data.role.name}</h5>`;
                html += `<p><strong>Description:</strong> ${data.role.description}</p>`;
                
                if (data.role.permissions && Object.keys(data.role.permissions).length > 0) {
                    html += '<h6>Permissions:</h6><ul class="collection">';
                    Object.keys(data.role.permissions).forEach(perm => {
                        const actions = data.role.permissions[perm];
                        html += `<li class="collection-item">${perm}: ${Array.isArray(actions) ? actions.join(', ') : actions}</li>`;
                    });
                    html += '</ul>';
                }
                
                content.innerHTML = html;
                M.Modal.getInstance(modal).open();
            } else {
                alert('Failed to load role details');
            }
        })
        .catch(error => {
            console.error('Error loading role details:', error);
            alert('Error loading role details');
        });
}
</script>