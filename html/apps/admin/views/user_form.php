<?php
// admin user form view (add/edit)
$userManager = new UserManager();
$isEdit = isset($_GET['username']);
$user = null;
$formTitle = 'Add User';

if ($isEdit) {
  $user = $userManager->getUser($_GET['username']);
  $formTitle = 'Edit User';
  if (!$user) {
    echo '<div class="card-panel red lighten-4"><span class="red-text">User not found</span></div>';
    return;
  }
}
?>
<div class="row">
  <div class="col s12">
    <h4><?php echo $formTitle; ?></h4>
    <nav class="admin-breadcrumb">
      <div class="nav-wrapper">
        <div class="col s12">
          <a href="?app=admin" class="breadcrumb">Admin</a>
          <a href="?app=admin&p=users" class="breadcrumb">User Management</a>
          <a href="#" class="breadcrumb"><?php echo $formTitle; ?></a>
        </div>
      </div>
    </nav>
    <div class="section">
      <a href="?app=admin&p=users" class="btn-small">
        <i class="material-icons left">arrow_back</i>Back to Users
      </a>
    </div>
  </div>
</div>

<div id="user-form-messages"></div>
<?php if ($isEdit): ?>
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <span class="card-title">User Information - "<?php echo htmlspecialchars($user['username']); ?>"</span>
        <ul class="collection">
          <li class="collection-item">
            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
            <strong>Created:</strong><br>
            <?php echo date('M j, Y g:i A', strtotime($user['created'])); ?>
          </li>
          <?php if ($user['last_login']): ?>
            <li class="collection-item">
              <strong>Last Login:</strong><br>
              <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row">

</div>
<div class="row">
  <div class="col s12">
    <div class="card">
      <div class="card-content">
        <div class="card">
          <div class="card-content">
            <form id="user-form" enctype="multipart/form-data">
              <?php if ($isEdit): ?>
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
              <?php endif; ?>

              <div class="input-field">
                <input type="text" id="username" name="username"
                  <?php if ($isEdit): ?>readonly<?php endif; ?>
                  value="<?php echo $isEdit ? htmlspecialchars($user['username']) : ''; ?>" required>
                <label for="username" class="<?php echo $isEdit ? 'active' : ''; ?>">Username</label>
              </div>

              <div class="input-field">
                <input type="email" id="email" name="email"
                  value="<?php echo $isEdit ? htmlspecialchars($user['email']) : ''; ?>" required>
                <label for="email" class="<?php echo $isEdit ? 'active' : ''; ?>">Email</label>
              </div>

              <div class="input-field">
                <input type="password" id="password" name="password"
                  <?php if (!$isEdit): ?>required<?php endif; ?>>
                <label for="password" class="<?php echo $isEdit ? 'active' : ''; ?>">Password <?php if ($isEdit): ?>(leave blank to keep current)<?php endif; ?></label>
              </div>

              <!-- Profile Picture -->
              <div class="section">
                <h6>Profile Picture</h6>
                <div class="row">
                  <div class="col s12 m8">
                    <div class="file-field input-field">
                      <div class="btn blue">
                        <span>Choose Image</span>
                        <input type="file" id="profileImageFile" name="profileImageFile" accept="image/*">
                      </div>
                      <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Upload a profile image">
                      </div>
                      <span class="helper-text">Max 500KB, JPEG/PNG/GIF/WebP format, will be resized to 400x400px</span>
                    </div>
                    <?php if ($isEdit && !empty($user['profilePicture'])): ?>
                      <p>
                        <label>
                          <input type="checkbox" id="removeProfilePicture" name="removeProfilePicture" value="1">
                          <span>Remove current profile picture</span>
                        </label>
                      </p>
                    <?php endif; ?>
                  </div>
                  <div class="col s12 m4">
                    <div class="profile-pic-preview">
                      <?php
                      require_once __DIR__ . '/../includes/ProfileImageManager.php';
                      $defaultProfilePic = ProfileImageManager::getDefaultProfileImage();
                      $currentProfilePic = ($isEdit && !empty($user['profilePicture'])) ? $user['profilePicture'] : $defaultProfilePic;
                      ?>
                      <img id="profile-pic-preview" src="<?php echo htmlspecialchars($currentProfilePic); ?>"
                        alt="Profile preview"
                        style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;">
                      <p class="small grey-text center-align">Preview</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- OAuth Provider Information -->
              <?php if ($isEdit): ?>
                <div class="section">
                  <h6>OAuth Provider Accounts</h6>
                  <div id="oauth-providers-section">
                    <div class="preloader-wrapper small active" id="oauth-loading">
                      <div class="spinner-layer spinner-blue-only">
                        <div class="circle-clipper left">
                          <div class="circle"></div>
                        </div>
                        <div class="gap-patch">
                          <div class="circle"></div>
                        </div>
                        <div class="circle-clipper right">
                          <div class="circle"></div>
                        </div>
                      </div>
                    </div>
                    <span>Loading OAuth provider information...</span>
                  </div>
                </div>
              <?php endif; ?>

              <div class="input-field">
                <select id="role" name="role">
                  <option value="user" <?php echo ($isEdit && $user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                  <option value="editor" <?php echo ($isEdit && $user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                  <option value="admin" <?php echo ($isEdit && $user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <label>Role</label>
              </div>

              <p>
                <label>
                  <input type="checkbox" id="is_admin" name="is_admin" value="1"
                    <?php echo ($isEdit && $user['is_admin']) ? 'checked' : ''; ?>>
                  <span>Administrator Privileges</span>
                </label>
              </p>

              <?php if ($isEdit): ?>
                <p>
                  <label>
                    <input type="checkbox" id="active" name="active" value="1"
                      <?php echo ($isEdit && $user['active']) ? 'checked' : ''; ?>>
                    <span>Active Account</span>
                  </label>
                </p>
              <?php endif; ?>

              <!-- Granular App Permissions Section -->
              <div class="section">
                <h5>App Permissions</h5>
                <p class="grey-text">Select which applications and features this user can access.</p>

                <?php
                // Load permissions matrix to get available apps and features
                require_once __DIR__ . '/../includes/PermissionsMatrix.php';
                $permissionsMatrix = new PermissionsMatrix();
                $permissionsSummary = $permissionsMatrix->getPermissionsSummary();
                $userApps = [];
                $userPermissions = [];
                $rolePermissions = [];

                if ($isEdit) {
                  $userApps = $permissionsMatrix->getUserApps($user['username']);
                  $userPermissions = $permissionsMatrix->getUserPermissions($user['username']);
                  $allPermissions = $permissionsMatrix->getPermissionsStructure();
                  $userRole = $user['role'] ?? 'guest';
                  $rolePermissions = $permissionsMatrix->getRolePermissions($userRole, $allPermissions);
                }

                // Helper function for merged permission check
                function userHasPermission($userPermissions, $rolePermissions, $resource, $action = null) {
                    if (isset($userPermissions['custom_permissions'][$resource])) {
                        if ($action === null) return true;
                        return in_array($action, $userPermissions['custom_permissions'][$resource]);
                    }
                    if (isset($rolePermissions[$resource])) {
                        if ($action === null) return true;
                        return in_array($action, $rolePermissions[$resource]);
                    }
                    return false;
                }
                //debug($userApps);
                //echo '<pre>$permissionsSummary - ' . print_r($permissionsSummary, true) . '</pre>';
                //echo '<pre>$userPermissions - ' . print_r($userPermissions, true) . '</pre>';
                ?>

                <div class="row">
                  <!-- Collapsible App Permissions -->
                  <div class="col s12">
                    <ul class="collapsible">
                      <?php foreach ($permissionsSummary['apps'] as $appName => $appConfig): ?>
                        <li>
                          <div class="collapsible-header">
                            <label class="app-header-label" onclick="event.stopPropagation();">
               <input type="checkbox"
                 name="app_access[]"
                 value="<?php echo htmlspecialchars($appName); ?>"
                 class="app-checkbox"
                 data-app="<?php echo htmlspecialchars($appName); ?>"
                 <?php
                 $resource = "apps.$appName";
                 $isChecked = userHasPermission($userPermissions, $rolePermissions, $resource, 'access');
                 echo $isChecked ? 'checked' : '';
                 ?>>
                              <span class="app-title"><?php echo htmlspecialchars($appConfig['name']); ?></span>
                            </label>
                            <i class="material-icons">expand_more</i>
                          </div>
                          <div class="collapsible-body">
                            <div class="app-permissions-content">
                              <p class="grey-text small"><?php echo htmlspecialchars($appConfig['description']); ?></p>

                              <div class="features-section">
                                <h6>Features:</h6>
                                <?php foreach ($appConfig['features'] as $featureName => $actions): ?>
                                  <div class="feature-group">
                                    <p>
                                      <label>
              <input type="checkbox"
                name="feature_access[<?php echo htmlspecialchars($appName); ?>][]"
                value="<?php echo htmlspecialchars($featureName); ?>"
                class="feature-checkbox"
                data-app="<?php echo htmlspecialchars($appName); ?>"
                data-feature="<?php echo htmlspecialchars($featureName); ?>"
                <?php
                $resource = "apps.$appName.features.$featureName";
                $isChecked = userHasPermission($userPermissions, $rolePermissions, $resource);
                echo $isChecked ? 'checked' : '';
                ?>>
                                        <span><strong><?php echo htmlspecialchars($featureName); ?></strong></span>
                                      </label>
                                    </p>

                                    <div class="actions-section">
                                      <?php foreach ($actions as $action): ?>
                                        <p style="margin: 5px 0;">
                                          <label>
               <input type="checkbox"
                 name="action_access[<?php echo htmlspecialchars($appName); ?>][<?php echo htmlspecialchars($featureName); ?>][]"
                 value="<?php echo htmlspecialchars($action); ?>"
                 class="action-checkbox"
                 data-app="<?php echo htmlspecialchars($appName); ?>"
                 data-feature="<?php echo htmlspecialchars($featureName); ?>"
                 <?php
                 $resource = "apps.$appName.features.$featureName";
                 $isChecked = userHasPermission($userPermissions, $rolePermissions, $resource, $action);
                 echo $isChecked ? 'checked' : '';
                 ?>>
                                            <span class="action-label"><?php echo htmlspecialchars($action); ?></span>
                                          </label>
                                        </p>
                                      <?php endforeach; ?>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="center">
                <button type="submit" class="btn btn-large">
                  <?php echo $isEdit ? 'Update User' : 'Create User'; ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Initialize Materialize components
        M.FormSelect.init(document.querySelectorAll('select'));
        M.Collapsible.init(document.querySelectorAll('.collapsible'), {
          accordion: false // Allow multiple sections to be open
        });

        <?php if ($isEdit): ?>
          loadOAuthProviders('<?php echo htmlspecialchars($user['username']); ?>');
        <?php endif; ?>
      }); // Handle app checkbox changes
      document.addEventListener('change', function(e) {
        if (e.target.classList.contains('app-checkbox')) {
          const appName = e.target.dataset.app;
          const isChecked = e.target.checked;

          // Toggle all features and actions for this app
          const featureCheckboxes = document.querySelectorAll(`input[data-app="${appName}"].feature-checkbox`);
          const actionCheckboxes = document.querySelectorAll(`input[data-app="${appName}"].action-checkbox`);

          featureCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            checkbox.dispatchEvent(new Event('change'));
          });

          actionCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
          });
        }

        if (e.target.classList.contains('feature-checkbox')) {
          const appName = e.target.dataset.app;
          const featureName = e.target.dataset.feature;
          const isChecked = e.target.checked;

          // Toggle all actions for this feature
          const actionCheckboxes = document.querySelectorAll(`input[data-app="${appName}"][data-feature="${featureName}"].action-checkbox`);
          actionCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
          });

          // Update app checkbox state
          updateAppCheckboxState(appName);
        }

        if (e.target.classList.contains('action-checkbox')) {
          const appName = e.target.dataset.app;
          const featureName = e.target.dataset.feature;

          // Update feature checkbox state
          updateFeatureCheckboxState(appName, featureName);
          // Update app checkbox state
          updateAppCheckboxState(appName);
        }
      });

      // Profile picture file upload preview functionality
      const profileImageFileInput = document.getElementById('profileImageFile');
      const profilePicPreview = document.getElementById('profile-pic-preview');

      if (profileImageFileInput && profilePicPreview) {
        const defaultProfilePic = profilePicPreview.src; // Store default image

        profileImageFileInput.addEventListener('change', function() {
          const file = this.files[0];
          if (file) {
            // Validate file size (500KB max)
            if (file.size > 512000) {
              M.toast({
                html: 'Image too large! Maximum size is 500KB.',
                classes: 'red'
              });
              this.value = '';
              return;
            }

            // Validate file type
            if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
              M.toast({
                html: 'Invalid file type! Use JPEG, PNG, GIF, or WebP.',
                classes: 'red'
              });
              this.value = '';
              return;
            }

            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
              profilePicPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
          } else {
            // No file selected, revert to default
            profilePicPreview.src = defaultProfilePic;
          }
        });
      }

      function updateFeatureCheckboxState(appName, featureName) {
        const actionCheckboxes = document.querySelectorAll(`input[data-app="${appName}"][data-feature="${featureName}"].action-checkbox`);
        const featureCheckbox = document.querySelector(`input[data-app="${appName}"][data-feature="${featureName}"].feature-checkbox`);

        if (featureCheckbox) {
          const checkedActions = Array.from(actionCheckboxes).filter(cb => cb.checked);
          featureCheckbox.checked = checkedActions.length > 0;
        }
      }

      function updateAppCheckboxState(appName) {
        const featureCheckboxes = document.querySelectorAll(`input[data-app="${appName}"].feature-checkbox`);
        const appCheckbox = document.querySelector(`input[data-app="${appName}"].app-checkbox`);

        if (appCheckbox) {
          const checkedFeatures = Array.from(featureCheckboxes).filter(cb => cb.checked);
          appCheckbox.checked = checkedFeatures.length > 0;
        }
      }

      // Load OAuth provider information
      function loadOAuthProviders(username) {
        fetch(`api.php?app=admin&action=oauth_user_info&username=${encodeURIComponent(username)}`)
          .then(response => response.json())
          .then(data => {
            const section = document.getElementById('oauth-providers-section');
            const loading = document.getElementById('oauth-loading');

            loading.style.display = 'none';

            if (data.success) {
              const providers = data.oauth_providers;
              let html = '';

              if (Object.keys(providers).length === 0) {
                html = '<div class="card-panel grey lighten-4"><span class="grey-text">No OAuth providers linked to this account</span></div>';
              } else {
                html = '<div class="collection">';

                for (const [providerName, providerData] of Object.entries(providers)) {
                  const providerIcon = providerName === 'google' ? 'fab fa-google' : 'fab fa-apple';
                  const providerColor = providerName === 'google' ? 'blue' : 'black';
                  const linkedDate = new Date(providerData.linked_at).toLocaleDateString();
                  const lastLogin = new Date(providerData.last_login).toLocaleDateString();

                  html += `
                <div class="collection-item">
                  <div class="row valign-wrapper">
                    <div class="col s1">
                      <i class="${providerIcon} ${providerColor}-text" style="font-size: 24px;"></i>
                    </div>
                    <div class="col s8">
                      <strong>${providerName.charAt(0).toUpperCase() + providerName.slice(1)}</strong><br>
                      <small class="grey-text">
                        Email: ${providerData.email}<br>
                        Name: ${providerData.name || 'Not provided'}<br>
                        Linked: ${linkedDate}<br>
                        Last Login: ${lastLogin}
                      </small>
                    </div>
                    <div class="col s3">
                      <button class="btn-small red" onclick="unlinkOAuthProvider('${username}', '${providerName}')">
                        <i class="material-icons left">link_off</i>Unlink
                      </button>
                    </div>
                  </div>
                </div>
              `;
                }

                html += '</div>';
              }

              section.innerHTML = html;
            } else {
              section.innerHTML = `<div class="card-panel red lighten-4"><span class="red-text">Error loading OAuth providers: ${data.error}</span></div>`;
            }
          })
          .catch(error => {
            document.getElementById('oauth-loading').style.display = 'none';
            document.getElementById('oauth-providers-section').innerHTML =
              '<div class="card-panel red lighten-4"><span class="red-text">Failed to load OAuth provider information</span></div>';
          });
      }

      // Unlink OAuth provider
      function unlinkOAuthProvider(username, provider) {
        if (!confirm(`Are you sure you want to unlink the ${provider} account from this user?`)) {
          return;
        }

        fetch('api.php?app=admin&action=unlink_oauth_provider', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              username: username,
              provider: provider
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              M.toast({
                html: `${provider} account unlinked successfully!`,
                classes: 'green'
              });
              loadOAuthProviders(username); // Reload the provider list
            } else {
              M.toast({
                html: `Error unlinking ${provider}: ${data.error}`,
                classes: 'red'
              });
            }
          })
          .catch(error => {
            M.toast({
              html: `Network error while unlinking ${provider}`,
              classes: 'red'
            });
          });
      }

      document.getElementById('user-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const isEdit = formData.has('username') && document.getElementById('username').readOnly;

        const action = isEdit ? 'update_user' : 'add_user';

        fetch('api.php?app=admin&action=' + action, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById('user-form-messages').innerHTML =
                '<div class="card-panel green lighten-4"><span class="green-text">' +
                data.message + '</span></div>';

              if (!isEdit) {
                // Clear form for new user
                document.getElementById('user-form').reset();
                M.updateTextFields();
              }
            } else {
              document.getElementById('user-form-messages').innerHTML =
                '<div class="card-panel red lighten-4"><span class="red-text">' +
                (data.error || 'Operation failed') + '</span></div>';
            }
          })
          .catch(error => {
            document.getElementById('user-form-messages').innerHTML =
              '<div class="card-panel red lighten-4"><span class="red-text">Operation failed</span></div>';
          });
      });

      // Add CSS for permissions styling
      const style = document.createElement('style');
      style.textContent = `
.app-permissions-card {
    border-left: 4px solid #2196f3;
}

.app-permissions-card .card-title {
    margin-bottom: 10px;
}

.app-title {
    font-size: 1.1em;
    font-weight: 500;
}

.features-section {
    margin-top: 15px;
}

.feature-group {
    margin-bottom: 15px;
    padding: 10px;
    background-color: #f5f5f5;
    border-radius: 4px;
}

.actions-section {
    background-color: #fafafa;
    padding: 8px;
    border-radius: 3px;
    margin-top: 5px;
    margin-left: 15px;
}

.action-label {
    font-size: 0.9em;
    color: #666;
}

/* Collapsible App Permissions Styling */
.collapsible .app-header-label {
    display: flex;
    align-items: center;
    flex: 1;
    margin: 0;
}

.collapsible .app-header-label .app-title {
    margin-left: 10px;
    font-size: 1.1em;
    font-weight: 500;
}

.collapsible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #ddd;
    padding: 12px 20px;
}

.collapsible-body {
    padding: 20px;
}

.app-permissions-content {
    padding: 0;
}

.collapsible li {
    border: 1px solid #ddd;
    border-bottom: none;
}

.collapsible li:last-child {
    border-bottom: 1px solid #ddd;
}

.collapsible li.active .collapsible-header {
    background-color: #f5f5f5;
}

/* Dark mode support */
.nightMode .feature-group {
    background-color: #2d2d2d;
}

.nightMode .actions-section {
    background-color: #1e1e1e;
}

.nightMode .action-label {
    color: #b0b0b0;
}

.nightMode .collapsible-header {
    background-color: #2d2d2d;
    border-bottom: 1px solid #555;
    color: #e0e0e0;
}

.nightMode .collapsible li {
    border: 1px solid #555;
    border-bottom: none;
}

.nightMode .collapsible li:last-child {
    border-bottom: 1px solid #555;
}

.nightMode .collapsible li.active .collapsible-header {
    background-color: #1e1e1e;
}

.nightMode .collapsible-body {
    background-color: #1e1e1e;
    color: #e0e0e0;
}

/* Profile Picture Styling */
.profile-pic-preview {
    text-align: center;
    padding: 10px;
}

.profile-pic-preview img {
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-pic-preview img:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.nightMode .profile-pic-preview img {
    border-color: #555;
    box-shadow: 0 2px 8px rgba(255,255,255,0.1);
}

.nightMode .profile-pic-preview img:hover {
    box-shadow: 0 4px 12px rgba(255,255,255,0.2);
}

/* Profile Picture Styling */
.profile-pic-preview {
    text-align: center;
    padding: 10px;
}

.profile-pic-preview img {
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-pic-preview img:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.nightMode .profile-pic-preview img {
    border-color: #555;
    box-shadow: 0 2px 8px rgba(255,255,255,0.1);
}

.nightMode .profile-pic-preview img:hover {
    box-shadow: 0 4px 12px rgba(255,255,255,0.2);
}
`;
      document.head.appendChild(style);
    </script>