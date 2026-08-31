<?php
// admin users management view
$userManager = new UserManager();
$users = $userManager->getAllUsers();
?>
<div class="row">
    <div class="col s12">
        <h4>User Management</h4>
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=users" class="breadcrumb">User Management</a>
                </div>
            </div>
        </nav>
        <div class="right-align">
            <a href="?app=admin&p=users&action=add" class="btn">
                <i class="material-icons left">person_add</i>Add User
            </a>
        </div>
    </div>
</div>
    
    <div id="user-messages"></div>
    
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Admin</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                require_once __DIR__ . '/../includes/ProfileImageManager.php';
                                $defaultProfilePic = ProfileImageManager::getDefaultProfileImage();
                                $userProfilePic = !empty($user['profilePicture']) ? $user['profilePicture'] : $defaultProfilePic;
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <img src="<?php echo htmlspecialchars($userProfilePic); ?>" 
                                             alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 1px solid #ddd;">
                                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="green-text">Yes</span>
                                    <?php else: ?>
                                        <span class="grey-text">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['active']): ?>
                                        <span class="green-text">Active</span>
                                    <?php else: ?>
                                        <span class="red-text">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    echo $user['last_login'] ? 
                                        date('M j, Y g:i A', strtotime($user['last_login'])) : 
                                        'Never'; 
                                    ?>
                                </td>
                                <td>
                                    <a href="?app=admin&p=users&action=edit&username=<?php echo urlencode($user['username']); ?>" 
                                       class="btn-small blue">
                                        <i class="material-icons">edit</i>
                                    </a>
                                    <?php if ($user['username'] !== 'admin'): ?>
                                    <button onclick="deleteUser('<?php echo htmlspecialchars($user['username']); ?>')" 
                                            class="btn-small red">
                                        <i class="material-icons">delete</i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
function deleteUser(username) {
    if (!confirm('Are you sure you want to delete user "' + username + '"?')) {
        return;
    }
    
    fetch('api.php?app=admin&action=delete_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'username=' + encodeURIComponent(username)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-messages').innerHTML = 
                '<div class="card-panel green lighten-4"><span class="green-text">' + 
                data.message + '</span></div>';
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            document.getElementById('user-messages').innerHTML = 
                '<div class="card-panel red lighten-4"><span class="red-text">' + 
                (data.error || 'Delete failed') + '</span></div>';
        }
    })
    .catch(error => {
        document.getElementById('user-messages').innerHTML = 
            '<div class="card-panel red lighten-4"><span class="red-text">Delete failed</span></div>';
    });
}
</script>