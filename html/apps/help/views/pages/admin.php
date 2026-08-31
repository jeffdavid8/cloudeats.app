<?php
// Admin Panel help page
?>

<div class="help-breadcrumb">
    <a href="?app=help">Help Center</a> &gt; Admin Panel
</div>

<h1>Admin Panel Guide</h1>

<p>The Admin Panel provides comprehensive system management tools for administrators to manage users, configure settings, and monitor system health.</p>

<div class="help-warning">
    <h5><i class="fas fa-user-shield"></i> Administrator Access Required</h5>
    <p>This section is only available to users with administrator privileges. Contact your system administrator if you need admin access.</p>
</div>

<div class="help-toc">
    <h4><i class="fas fa-list"></i> Admin Topics</h4>
    <ul>
        <li><a href="#dashboard">Admin Dashboard</a></li>
        <li><a href="#user-management">User Management</a></li>
        <li><a href="#permissions">Permission Management</a></li>
        <li><a href="#oauth-setup">OAuth Configuration</a></li>
        <li><a href="#system-monitoring">System Monitoring</a></li>
        <li><a href="#backup-restore">Backup & Restore</a></li>
        <li><a href="#security-logs">Security & Logs</a></li>
    </ul>
</div>

<h2 id="dashboard">Admin Dashboard</h2>

<p>The admin dashboard provides an overview of system status and quick access to management tools.</p>

<div class="help-feature-grid">
    <div class="help-feature-card">
            <h4><i class="fas fa-chart-bar"></i> System Statistics</h4>
        <ul>
            <li>Total registered users</li>
            <li>Active sessions</li>
            <li>Storage usage</li>
            <li>Application activity</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
            <h4><i class="fas fa-stream"></i> Recent Activity</h4>
        <ul>
            <li>User login activity</li>
            <li>Data modifications</li>
            <li>System events</li>
            <li>Error occurrences</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
            <h4><i class="fas fa-heartbeat"></i> System Health</h4>
        <ul>
            <li>Server status</li>
            <li>Database connectivity</li>
            <li>Storage availability</li>
            <li>External service status</li>
        </ul>
    </div>
</div>

<h2 id="user-management">User Management</h2>

<p>Manage user accounts, roles, and access permissions through the user management interface.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Creating New Users</h4>
    <ol>
        <li>Navigate to <strong>Admin Panel → Users</strong></li>
        <li>Click <strong>"Add New User"</strong></li>
        <li>Fill in required information:
            <ul>
                <li>Username (unique identifier)</li>
                <li>Email address (for notifications)</li>
                <li>Initial password</li>
                <li>User role (Guest, User, Editor, Admin)</li>
            </ul>
        </li>
        <li>Configure initial permissions</li>
        <li>Click <strong>"Create User"</strong></li>
    </ol>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Modifying Existing Users</h4>
    <ol>
        <li>Find the user in the user list</li>
        <li>Click the <strong>"Edit"</strong> button</li>
        <li>Modify any of the following:
            <ul>
                <li>Contact information</li>
                <li>Password (if needed)</li>
                <li>User role and permissions</li>
                <li>Account status (active/inactive)</li>
            </ul>
        </li>
        <li>Save changes</li>
    </ol>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>User Roles Explained</h4>
    <div class="help-feature-grid">
        <div class="help-feature-card">
            <h4><i class="fas fa-user"></i> Guest</h4>
            <p>Limited access to basic features</p>
            <ul>
                <li>Weather information</li>
                <li>Bible search (read-only)</li>
                <li>No personal data storage</li>
            </ul>
        </div>
        
        <div class="help-feature-card">
            <h4><i class="fas fa-users"></i> User</h4>
            <p>Standard user with full app access</p>
            <ul>
                <li>All guest permissions</li>
                <li>Personal bookmarks and data</li>
                <li>Recipe management</li>
                <li>Ancestry research</li>
            </ul>
        </div>
        
        <div class="help-feature-card">
            <h4><i class="fas fa-pen-nib"></i> Editor</h4>
            <p>Enhanced content creation privileges</p>
            <ul>
                <li>All user permissions</li>
                <li>Advanced editing features</li>
                <li>Bulk operations</li>
                <li>Content sharing</li>
            </ul>
        </div>
        
        <div class="help-feature-card">
            <h4><i class="fas fa-user-shield"></i> Admin</h4>
            <p>Complete system access</p>
            <ul>
                <li>All editor permissions</li>
                <li>User management</li>
                <li>System configuration</li>
                <li>Security settings</li>
            </ul>
        </div>
    </div>
</div>

<h2 id="permissions">Permission Management</h2>

<p>Configure granular permissions for different user roles and individual users.</p>

<div class="help-expandable">
    <h3>Understanding the Permission Matrix</h3>
    <div class="expandable-content">
        <p>MediaBrain uses a hierarchical permission system:</p>
        <div class="help-code-block">
Application Level:
├── apps.bibleBot (access to Bible app)
├── apps.recipes (access to Recipe app)
├── apps.weather (access to Weather app)
├── apps.ancestry (access to Ancestry app)
└── apps.admin (access to Admin panel)

Feature Level:
├── apps.recipes.features.recipes (recipe CRUD operations)
├── apps.recipes.features.voice_control (voice features)
├── apps.bibleBot.features.bookmarks (bookmark management)
└── apps.admin.features.users (user management)

Action Level:
├── view (read access)
├── create (add new items)
├── update (modify existing items)
├── delete (remove items)
└── access (basic app access)
        </div>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Configuring Role Permissions</h4>
    <ol>
        <li>Go to <strong>Admin Panel → Permissions</strong></li>
        <li>Select a role to modify</li>
        <li>Check/uncheck permissions for:
            <ul>
                <li>Application access</li>
                <li>Feature availability</li>
                <li>Specific actions (view, create, update, delete)</li>
            </ul>
        </li>
        <li>Save permission changes</li>
        <li>Changes take effect immediately for all users with that role</li>
    </ol>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Setting Individual User Permissions</h4>
    <ol>
        <li>Navigate to user management</li>
        <li>Select a specific user</li>
        <li>Click <strong>"Custom Permissions"</strong></li>
        <li>Override role permissions as needed</li>
        <li>Individual permissions take precedence over role permissions</li>
    </ol>
</div>

<h2 id="oauth-setup">OAuth Configuration</h2>

<p>Configure social login providers to enhance user authentication options.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Accessing OAuth Settings</h4>
    <ol>
        <li>Navigate to <strong>Admin Panel → Settings → OAuth</strong></li>
        <li>View current provider status</li>
        <li>Add or modify OAuth providers</li>
    </ol>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Configuring Facebook Login</h4>
    <div class="help-code-block">
{
    "facebook": {
        "enabled": true,
        "app_id": "YOUR_FACEBOOK_APP_ID",
        "app_secret": "YOUR_FACEBOOK_APP_SECRET",
        "redirect_uri": "https://yourdomain.com/oauth/facebook.php",
        "scope": ["email", "public_profile"]
    }
}
    </div>
    <p><strong>Required Setup:</strong></p>
    <ol>
        <li>Create Facebook Developer account</li>
        <li>Create new app or use existing</li>
        <li>Add Facebook Login product</li>
        <li>Configure Valid OAuth Redirect URIs</li>
        <li>Copy App ID and App Secret to MediaBrain</li>
    </ol>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>Configuring Google Login</h4>
    <div class="help-code-block">
{
    "google": {
        "enabled": true,
        "client_id": "YOUR_GOOGLE_CLIENT_ID",
        "client_secret": "YOUR_GOOGLE_CLIENT_SECRET",
        "redirect_uri": "https://yourdomain.com/oauth/google.php",
        "scope": ["openid", "email", "profile"]
    }
}
    </div>
    <p><strong>Required Setup:</strong></p>
    <ol>
        <li>Go to Google Cloud Console</li>
        <li>Create OAuth 2.0 credentials</li>
        <li>Add authorized redirect URIs</li>
        <li>Copy Client ID and Client Secret</li>
    </ol>
</div>

<h2 id="system-monitoring">System Monitoring</h2>

<p>Monitor system performance, user activity, and application health.</p>

<div class="help-feature-grid">
    <div class="help-feature-card">
        <h4><i class="material-icons">monitor</i> Performance Metrics</h4>
        <ul>
            <li>Response times</li>
            <li>Memory usage</li>
            <li>CPU utilization</li>
            <li>Database performance</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
        <h4><i class="material-icons">people</i> User Activity</h4>
        <ul>
            <li>Active user sessions</li>
            <li>Login/logout events</li>
            <li>Feature usage statistics</li>
            <li>Error occurrences</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
        <h4><i class="material-icons">storage</i> Storage Monitoring</h4>
        <ul>
            <li>Disk space usage</li>
            <li>Cloud storage quota</li>
            <li>File upload activity</li>
            <li>Backup status</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Viewing System Status</h4>
    <ol>
        <li>Access <strong>Admin Panel → Monitoring</strong></li>
        <li>Review real-time system metrics</li>
        <li>Check for any alerts or warnings</li>
        <li>Monitor resource usage trends</li>
    </ol>
</div>

<h2 id="backup-restore">Backup & Restore</h2>

<p>Manage data backups and restore operations to protect against data loss.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Creating Backups</h4>
    <ol>
        <li>Navigate to <strong>Admin Panel → Backup</strong></li>
        <li>Select backup options:
            <ul>
                <li>User data</li>
                <li>Application settings</li>
                <li>Uploaded files</li>
                <li>Database content</li>
            </ul>
        </li>
        <li>Choose backup destination (local/cloud)</li>
        <li>Start backup process</li>
        <li>Download or verify backup completion</li>
    </ol>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Restoring from Backup</h4>
    <div class="help-warning">
        <h5><i class="fas fa-exclamation-triangle"></i> Caution</h5>
        <p>Restore operations will overwrite current data. Ensure you have a recent backup before proceeding.</p>
    </div>
    <ol>
        <li>Go to <strong>Admin Panel → Restore</strong></li>
        <li>Select backup file to restore from</li>
        <li>Choose restoration options</li>
        <li>Confirm restoration (irreversible)</li>
        <li>Monitor restoration progress</li>
        <li>Verify data integrity after completion</li>
    </ol>
</div>

<h2 id="security-logs">Security & Logs</h2>

<p>Monitor security events and system logs for troubleshooting and audit purposes.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Security Event Monitoring</h4>
    <p>Track important security events:</p>
    <ul>
        <li>Failed login attempts</li>
        <li>Password changes</li>
        <li>Permission modifications</li>
        <li>Suspicious activity patterns</li>
        <li>OAuth authentication events</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>System Log Analysis</h4>
    <ol>
        <li>Access <strong>Admin Panel → Logs</strong></li>
        <li>Filter logs by:
            <ul>
                <li>Date range</li>
                <li>Log level (Error, Warning, Info)</li>
                <li>Application component</li>
                <li>User activity</li>
            </ul>
        </li>
        <li>Export logs for external analysis</li>
        <li>Set up log alerts for critical events</li>
    </ol>
</div>

<div class="help-info">
    <h5><i class="fas fa-info-circle"></i> Log Retention</h5>
    <p>Configure log retention policies:</p>
    <ul>
        <li>Security logs: 90 days minimum</li>
        <li>Error logs: 30 days</li>
        <li>Access logs: 7 days</li>
        <li>Debug logs: 24 hours</li>
    </ul>
</div>

<div class="help-success">
    <h5><i class="fas fa-lightbulb"></i> Admin Best Practices</h5>
    <ul>
    <li><i class="fas fa-check"></i> Regular security audits</li>
    <li><i class="fas fa-check"></i> Monitor user activity patterns</li>
    <li><i class="fas fa-check"></i> Keep OAuth providers updated</li>
    <li><i class="fas fa-check"></i> Implement strong password policies</li>
    <li><i class="fas fa-check"></i> Schedule regular backups</li>
    <li><i class="fas fa-check"></i> Review permission assignments periodically</li>
    <li><i class="fas fa-check"></i> Monitor system resource usage</li>
    <li><i class="fas fa-check"></i> Keep security logs for compliance</li>
    </ul>
</div>

<div style="margin-top: 40px; text-align: center;">
    <h3>Ready to Manage Your System?</h3>
    <p>Access the admin panel to start managing users and system settings.</p>
    <div style="margin: 20px 0;">
        <a href="?app=admin" class="btn" target="_blank">Open Admin Panel</a>
        <a href="?app=help&section=setup" class="btn">Setup Guide</a>
        <a href="?app=help&section=troubleshooting" class="btn">Troubleshooting</a>
    </div>
</div>