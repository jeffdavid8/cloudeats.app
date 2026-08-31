<?php
// admin permissions management view
require_once __DIR__ . '/../includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();
$permissionsSummary = $permissionsMatrix->getPermissionsSummary();
?>
<div class="row">
    <div class="col s12">
        <h4>Permissions Management</h4>
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=permissions" class="breadcrumb">Permissions</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div id="permissions-messages"></div>

<!-- User Permissions Section -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    User Permissions
                    <a href="?app=admin&p=roles" class="btn blue right">
                        <i class="material-icons left">group</i>Manage Roles
                    </a>
                </span>
                <div class="clearfix"></div>
                <table class="striped responsive-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Apps Access</th>
                            <th>Custom Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissionsSummary['users'] as $username => $userPerms): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($username); ?></td>
                                <td>
                                    <div class="chip <?php echo $userPerms['role'] === 'admin' ? 'red' : ($userPerms['role'] === 'editor' ? 'blue' : 'grey'); ?> white-text">
                                        <?php echo htmlspecialchars($userPerms['role']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $userApps = $permissionsMatrix->getUserApps($username);
                                    foreach ($userApps as $appName => $appInfo):
                                    ?>
                                        <div class="chip green white-text"><?php echo htmlspecialchars($appInfo['name']); ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <span class="badge"><?php echo count($userPerms['custom_permissions']); ?></span>
                                </td>
                                <td>
                                    <a href="javascript:void(0)" onclick="editUserPermissions('<?php echo htmlspecialchars($username); ?>')"
                                        class="btn-small blue">
                                        <i class="material-icons">edit</i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Roles Section -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">Role Definitions</span>
                <div class="row">
                    <?php foreach ($permissionsSummary['roles'] as $roleName => $roleConfig): ?>
                        <div class="col s12 m6 l4">
                            <div class="card-panel <?php echo $roleName === 'admin' ? 'red' : ($roleName === 'editor' ? 'blue' : 'grey'); ?> lighten-4">
                                <h6 class="<?php echo $roleName === 'admin' ? 'red' : ($roleName === 'editor' ? 'blue' : 'grey'); ?>-text">
                                    <?php echo htmlspecialchars($roleConfig['name']); ?>
                                </h6>
                                <p class="grey-text text-darken-2">
                                    <?php echo htmlspecialchars($roleConfig['description']); ?>
                                </p>
                                <div class="divider"></div>
                                <p><strong>Permissions:</strong> <?php echo count($roleConfig['permissions']); ?></p>
                                <?php if (isset($roleConfig['inherit'])): ?>
                                    <p><strong>Inherits from:</strong>
                                        <?php foreach ($roleConfig['inherit'] as $inherit): ?>
                                    <div class="chip grey white-text"><?php echo htmlspecialchars($inherit); ?></div>
                                <?php endforeach; ?>
                                </p>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Apps & Features Section -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">Application Features</span>
                <p>
                    <a href="javascript:void(0)" onclick="initializePermissions()" class="btn orange">Initialize Permissions</a>
                </p>

                <?php if (empty($permissionsSummary['apps'])): ?>
                    <div class="card-panel yellow lighten-4">
                        <span class="orange-text">
                            <i class="material-icons">warning</i>
                            No application permissions data found. The permissions system may need to be initialized.
                        </span>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($permissionsSummary['apps'] as $appName => $appConfig): ?>
                            <div class="col s12 m6">
                                <div class="card">
                                    <div class="card-content">
                                        <span class="card-title">
                                            <?php echo htmlspecialchars($appConfig['name']); ?>
                                            <div class="right">
                                                <a href="javascript:void(0)" onclick="editAppPermissions('<?php echo htmlspecialchars($appName); ?>')"
                                                    class="btn-small blue tooltipped" data-tooltip="Edit App Features">
                                                    <i class="material-icons">edit</i>
                                                </a>
                                                <a href="javascript:void(0)" onclick="viewAppUsers('<?php echo htmlspecialchars($appName); ?>')"
                                                    class="btn-small green tooltipped" data-tooltip="View Users with Access">
                                                    <i class="material-icons">people</i>
                                                </a>
                                            </div>
                                        </span>
                                        <p class="grey-text"><?php echo htmlspecialchars($appConfig['description']); ?></p>

                                        <h6>Features:</h6>
                                        <ul class="collection">
                                            <?php foreach ($appConfig['features'] as $featureName => $actions): ?>
                                                <li class="collection-item">
                                                    <strong><?php echo htmlspecialchars($featureName); ?></strong>
                                                    <div class="secondary-content">
                                                        <?php foreach ($actions as $action): ?>
                                                            <div class="chip grey lighten-2 clickable-action"
                                                                onclick="toggleFeatureAction('<?php echo htmlspecialchars($appName); ?>', '<?php echo htmlspecialchars($featureName); ?>', '<?php echo htmlspecialchars($action); ?>')"
                                                                data-tooltip="Click to disable this action">
                                                                <?php echo htmlspecialchars($action); ?>
                                                                <i class="material-icons tiny">close</i>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <a href="javascript:void(0)" onclick="addFeatureAction('<?php echo htmlspecialchars($appName); ?>', '<?php echo htmlspecialchars($featureName); ?>')"
                                                            class="btn-small blue tooltipped" data-tooltip="Add Action">
                                                            <i class="material-icons">add</i>
                                                        </a>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                            <li class="collection-item">
                                                <a href="javascript:void(0)" onclick="addFeature('<?php echo htmlspecialchars($appName); ?>')"
                                                    class="btn green tooltipped" data-tooltip="Add New Feature">
                                                    <i class="material-icons left">add</i>Add Feature
                                                </a>
                                            </li>
                                        </ul>

                                        <!-- Quick Access Stats -->
                                        <div class="divider"></div>
                                        <p class="small grey-text" style="margin-top: 10px;">
                                            <strong>Users with access:</strong>
                                            <span id="app-users-<?php echo htmlspecialchars($appName); ?>">Loading...</span>
                                            <br>
                                            <strong>Features:</strong> <?php echo count($appConfig['features']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Permissions Modal -->
<div id="edit-permissions-modal" class="modal">
    <div class="modal-content">
        <h4>Edit User Permissions</h4>
        <form id="permissions-form">
            <input type="hidden" id="edit-username" name="username">

            <div class="input-field">
                <select id="user-role" name="role">
                    <?php foreach ($permissionsSummary['roles'] as $roleName => $roleConfig): ?>
                        <option value="<?php echo htmlspecialchars($roleName); ?>">
                            <?php echo htmlspecialchars($roleConfig['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>User Role</label>
            </div>

            <h6>App Access</h6>
            <div id="app-permissions">
                <?php foreach ($permissionsSummary['apps'] as $appName => $appConfig): ?>
                    <p>
                        <label>
                            <input type="checkbox" name="apps[]" value="<?php echo htmlspecialchars($appName); ?>">
                            <span><?php echo htmlspecialchars($appConfig['name']); ?></span>
                        </label>
                    </p>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close btn-flat">Cancel</a>
        <a href="#!" onclick="saveUserPermissions()" class="btn">Save</a>
    </div>
</div>

<style>
    /* Fallback modal styles in case Materialize doesn't load */
    .modal.open {
        display: block !important;
        opacity: 1;
        transform: scaleX(1) scaleY(1);
        top: 10% !important;
    }

    .modal-overlay.open {
        display: block;
        opacity: 0.5;
    }

    /* Interactive features styling */
    .clickable-action {
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .clickable-action:hover {
        background-color: #ef5350 !important;
        color: white !important;
    }

    .clickable-action:hover .material-icons {
        color: white !important;
    }

    .card-title .right {
        margin-top: -5px;
    }

    .card-title .right .btn-small {
        margin-left: 5px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing Materialize components...');

        // Check if Materialize is loaded
        if (typeof M === 'undefined') {
            console.error('Materialize not loaded!');
            return;
        }

        // Initialize modals
        const modals = document.querySelectorAll('.modal');
        console.log('Found modals:', modals.length);
        M.Modal.init(modals);

        // Initialize selects
        M.FormSelect.init(document.querySelectorAll('select'));

        // Initialize tooltips
        M.Tooltip.init(document.querySelectorAll('.tooltipped'));

        console.log('Materialize components initialized');

        // Load user counts for each app
        loadAppUserCounts();
    });

    function editUserPermissions(username) {
        console.log('editUserPermissions called for:', username);
        document.getElementById('edit-username').value = username;

        // Load current user data
        fetch('api.php?app=admin&action=get_user_permissions&username=' + encodeURIComponent(username))
            .then(response => {
                console.log('API Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API Response data:', data);
                if (data.success) {
                    // Set role
                    document.getElementById('user-role').value = data.permissions.role;

                    // Set app checkboxes based on current access
                    const userApps = data.user_apps || [];
                    const checkboxes = document.querySelectorAll('input[name="apps[]"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = userApps.includes(checkbox.value);
                    });

                    // Reinitialize Materialize components
                    M.FormSelect.init(document.querySelectorAll('select'));

                    // Open modal
                    const modalElement = document.getElementById('edit-permissions-modal');
                    let modal = M.Modal.getInstance(modalElement);

                    if (!modal) {
                        console.log('Reinitializing modal...');
                        modal = M.Modal.init(modalElement);
                    }

                    if (modal) {
                        modal.open();
                    } else {
                        console.error('Failed to initialize modal');
                        // Fallback: show modal manually
                        modalElement.style.display = 'block';
                        modalElement.classList.add('open');
                    }
                } else {
                    console.error('API Error:', data.error);
                    alert('Error loading user permissions: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error loading user permissions:', error);
                alert('Network error loading user permissions');
            });
    }

    function saveUserPermissions() {
        console.log('Saving user permissions...');
        const formData = new FormData(document.getElementById('permissions-form'));

        // Debug form data
        for (let [key, value] of formData.entries()) {
            console.log('Form data:', key, value);
        }

        fetch('api.php?app=admin&action=update_user_permissions', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Save response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Save response data:', data);
                if (data.success) {
                    document.getElementById('permissions-messages').innerHTML =
                        '<div class="card-panel green lighten-4"><span class="green-text">' +
                        data.message + '</span></div>';

                    // Close modal
                    const modalElement = document.getElementById('edit-permissions-modal');
                    const modal = M.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.close();
                    } else {
                        modalElement.style.display = 'none';
                        modalElement.classList.remove('open');
                    }

                    // Reload page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    document.getElementById('permissions-messages').innerHTML =
                        '<div class="card-panel red lighten-4"><span class="red-text">' +
                        (data.error || 'Update failed') + '</span></div>';
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                document.getElementById('permissions-messages').innerHTML =
                    '<div class="card-panel red lighten-4"><span class="red-text">Network error: Update failed</span></div>';
            });
    }

    function initializePermissions() {
        if (!confirm('Initialize the permissions system? This will create default permissions for all apps.')) {
            return;
        }

        fetch('api.php?app=admin&action=initialize_permissions', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('permissions-messages').innerHTML =
                        '<div class="card-panel green lighten-4"><span class="green-text">' +
                        data.message + '</span></div>';

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    document.getElementById('permissions-messages').innerHTML =
                        '<div class="card-panel red lighten-4"><span class="red-text">' +
                        (data.error || 'Initialization failed') + '</span></div>';
                }
            })
            .catch(error => {
                console.error('Initialization error:', error);
                document.getElementById('permissions-messages').innerHTML =
                    '<div class="card-panel red lighten-4"><span class="red-text">Network error: Initialization failed</span></div>';
            });
    }

    // Load user count for each app
    function loadAppUserCounts() {
        fetch('api.php?app=admin&action=get_app_user_counts')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Object.keys(data.counts).forEach(appName => {
                        const element = document.getElementById('app-users-' + appName);
                        if (element) {
                            element.textContent = data.counts[appName] + ' users';
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Failed to load app user counts:', error);
            });
    }

    // Edit app permissions
    function editAppPermissions(appName) {
        alert('Edit App Permissions for: ' + appName + '\n\nThis feature allows you to modify the available features and actions for this application.');
        // TODO: Implement full app permissions editor
    }

    // View users with app access
    function viewAppUsers(appName) {
        fetch('api.php?app=admin&action=get_app_users&app=' + encodeURIComponent(appName))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let userList = data.users.length > 0 ?
                        data.users.map(user => `• ${user.username} (${user.role})`).join('\n') :
                        'No users have access to this app.';
                    alert(`Users with access to ${appName}:\n\n${userList}`);
                } else {
                    alert('Failed to load users for this app.');
                }
            })
            .catch(error => {
                console.error('Failed to load app users:', error);
                alert('Error loading app users.');
            });
    }

    // Toggle feature action (remove/disable)
    function toggleFeatureAction(appName, featureName, action) {
        if (!confirm(`Remove the "${action}" action from ${featureName} in ${appName}?\n\nThis will affect all users with this permission.`)) {
            return;
        }

        alert(`Feature: Remove "${action}" action\n\nApp: ${appName}\nFeature: ${featureName}\nAction: ${action}\n\nThis feature will be implemented to modify the permissions structure.`);
        // TODO: Implement action removal
    }

    // Add feature action
    function addFeatureAction(appName, featureName) {
        const action = prompt(`Add new action to "${featureName}" in ${appName}:\n\nEnter action name (e.g., "view", "create", "update", "delete"):`);
        if (!action || action.trim() === '') {
            return;
        }

        alert(`Feature: Add "${action}" action\n\nApp: ${appName}\nFeature: ${featureName}\nNew Action: ${action}\n\nThis feature will be implemented to modify the permissions structure.`);
        // TODO: Implement action addition
    }

    // Add new feature
    function addFeature(appName) {
        const featureName = prompt(`Add new feature to ${appName}:\n\nEnter feature name (e.g., "dashboard", "reports", "settings"):`);
        if (!featureName || featureName.trim() === '') {
            return;
        }

        const actions = prompt(`Enter actions for "${featureName}" (comma-separated):\n\nExample: view,create,update,delete`);
        if (!actions || actions.trim() === '') {
            return;
        }

        const actionList = actions.split(',').map(a => a.trim()).filter(a => a.length > 0);

        alert(`Feature: Add new feature\n\nApp: ${appName}\nFeature: ${featureName}\nActions: ${actionList.join(', ')}\n\nThis feature will be implemented to modify the permissions structure.`);
        // TODO: Implement feature addition
    }
</script>