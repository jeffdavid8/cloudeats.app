<?php
// Overview page for Help application
?>

<div class="help-breadcrumb">
    <a href="?app=help">Help Center</a> &gt; Overview
</div>

<h1>MediaBrain Help Center</h1>

<p>Welcome to the MediaBrain Help Center! This comprehensive guide will help you make the most of all MediaBrain applications and features.</p>

<div class="help-toc">
    <h4><i class="fas fa-list"></i> Quick Navigation</h4>
    <ul>
        <li><a href="#getting-started">Getting Started</a></li>
        <li><a href="#applications">Applications Overview</a></li>
        <li><a href="#user-roles">User Roles & Permissions</a></li>
        <li><a href="#common-tasks">Common Tasks</a></li>
        <li><a href="#troubleshooting">Quick Troubleshooting</a></li>
        <li><a href="?app=help&section=developer">Developer Docs</a></li>
    </ul>
</div>

<? /*
<div class="help-search">
    <div class="input-field">
    <i class="fas fa-search prefix"></i>
        <input type="text" id="help-search" placeholder="Search help topics...">
        <label for="help-search">Search Help</label>
    </div>
</div>
*/ ?>
<h2 id="getting-started">Getting Started</h2>

<div class="help-feature-grid">
    <div class="help-feature-card">
    <h4><i class="fas fa-user-circle"></i> Create Your Account</h4>
        <p>Sign up using your email or social media accounts (Facebook, Google, Apple) to access all MediaBrain features.</p>
        <a href="?p=login" class="btn-small">Get Started</a>
    </div>
    
    <div class="help-feature-card">
    <h4><i class="fas fa-tachometer-alt"></i> Explore the Dashboard</h4>
        <p>Your personalized dashboard provides quick access to all applications and recent activity.</p>
        <a href="?p=dashboard" class="btn-small">View Dashboard</a>
    </div>
    
    <div class="help-feature-card">
    <h4><i class="fas fa-cogs"></i> Customize Your Experience</h4>
        <p>Adjust settings, themes, and preferences to personalize your MediaBrain experience.</p>
        <a href="?app=help&section=setup" class="btn-small">Learn More</a>
    </div>
</div>

<h2 id="applications">Applications Overview</h2>

<p>MediaBrain includes five powerful applications designed to enhance different aspects of your digital life:</p>

<div class="help-feature-grid">
    <div class="help-feature-card">
    <h4><i class="fas fa-book"></i> BibleBot</h4>
        <p>Advanced Bible study with search, bookmarks, and audio features. Perfect for personal study and research.</p>
        <ul>
            <li>Full-text scripture search</li>
            <li>Personal bookmark collections</li>
            <li>Text-to-speech functionality</li>
            <li>Cross-reference tools</li>
        </ul>
        <a href="?app=help&section=biblebot" class="btn-small">Learn BibleBot</a>
    </div>
    
    <div class="help-feature-card">
    <h4><i class="fas fa-utensils"></i> Recipes</h4>
        <p>Voice-guided cooking with recipe management, image uploads, and smart organization features.</p>
        <ul>
            <li>Voice-controlled navigation</li>
            <li>Recipe photo management</li>
            <li>Category organization</li>
            <li>Ingredient tracking</li>
        </ul>
        <a href="?app=help&section=recipes" class="btn-small">Learn Recipes</a>
    </div>
    
    <div class="help-feature-card">
    <h4><i class="fas fa-sun"></i> Weather</h4>
        <p>Comprehensive weather information with forecasts, alerts, and location management capabilities.</p>
        <ul>
            <li>Current weather conditions</li>
            <li>7-day forecasts</li>
            <li>Multiple location tracking</li>
            <li>Severe weather alerts</li>
        </ul>
        <a href="?app=help&section=weather" class="btn-small">Learn Weather</a>
    </div>
    
    <div class="help-feature-card">
    <h4><i class="fas fa-sitemap"></i> Ancestry</h4>
        <p>Family tree research and genealogy tools for exploring and documenting your family history.</p>
        <ul>
            <li>Interactive family trees</li>
            <li>Document management</li>
            <li>Timeline visualization</li>
            <li>Research collaboration</li>
        </ul>
        <a href="?app=help&section=ancestry" class="btn-small">Learn Ancestry</a>
    </div>
    
    <?php if (isset($userRole) && $userRole === 'admin'): ?>
    <div class="help-feature-card">
        <h4><i class="fas fa-user-shield"></i> Admin Panel</h4>
        <p>System administration tools for managing users, permissions, and system configuration.</p>
        <ul>
            <li>User account management</li>
            <li>Permission configuration</li>
            <li>System monitoring</li>
            <li>OAuth provider setup</li>
        </ul>
        <a href="?app=help&section=admin" class="btn-small">Learn Admin</a>
    </div>
    <?php endif; ?>
</div>

<h2 id="user-roles">User Roles & Permissions</h2>

<p>MediaBrain uses a role-based permission system to control access to features and applications:</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Guest Users</h4>
    <p>Limited access to basic features like weather information and Bible search. No account required.</p>
    <ul>
        <li>View weather information</li>
        <li>Search Bible text (read-only)</li>
        <li>Basic application browsing</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Regular Users</h4>
    <p>Full access to all applications with personal data management capabilities.</p>
    <ul>
        <li>All guest permissions</li>
        <li>Personal bookmarks and favorites</li>
        <li>Recipe creation and management</li>
        <li>Family tree research</li>
        <li>Data export and backup</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>Editors</h4>
    <p>Enhanced permissions for content creation and advanced features.</p>
    <ul>
        <li>All user permissions</li>
        <li>Advanced content editing</li>
        <li>Bulk data operations</li>
        <li>Enhanced sharing capabilities</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <h4>Administrators</h4>
    <p>Complete system access including user management and system configuration.</p>
    <ul>
        <li>All editor permissions</li>
        <li>User account management</li>
        <li>System configuration</li>
        <li>Security and audit logs</li>
        <li>OAuth provider management</li>
    </ul>
</div>

<h2 id="common-tasks">Common Tasks</h2>

<div class="help-expandable">
    <h3>How to Switch Between Applications</h3>
    <div class="expandable-content">
        <ol>
            <li>Use the sidebar navigation menu on the left</li>
            <li>Click the MediaBrain logo to return to the dashboard</li>
            <li>Use the applications menu in the top navigation</li>
            <li>Access apps directly via URL: <code>?app=appname</code></li>
        </ol>
    </div>
</div>

<div class="help-expandable">
    <h3>How to Change Your Theme</h3>
    <div class="expandable-content">
        <ol>
            <li>Look for the theme toggle button (sun/moon icon)</li>
            <li>Click to switch between light and dark modes</li>
            <li>Your preference is automatically saved</li>
            <li>The theme syncs across all applications</li>
        </ol>
    </div>
</div>

<div class="help-expandable">
    <h3>How to Export Your Data</h3>
    <div class="expandable-content">
        <ol>
            <li>Navigate to your user profile or settings</li>
            <li>Look for "Export Data" or "Backup" options</li>
            <li>Select the data types you want to export</li>
            <li>Download the generated file(s)</li>
        </ol>
        <div class="help-info">
            <h5><i class="material-icons">info</i> Note</h5>
            <p>Data export options may vary based on your user role and the specific application.</p>
        </div>
    </div>
</div>

<h2 id="troubleshooting">Quick Troubleshooting</h2>

<div class="help-warning">
    <h5><i class="fas fa-exclamation-triangle"></i> Login Issues</h5>
    <p>If you're having trouble logging in:</p>
    <ul>
        <li>Check your internet connection</li>
        <li>Verify your username and password</li>
        <li>Try clearing your browser cache</li>
        <li>Ensure cookies are enabled</li>
        <li>Try logging in with a different browser</li>
    </ul>
</div>

<div class="help-info">
    <h5><i class="fas fa-info-circle"></i> Performance Tips</h5>
    <p>For the best MediaBrain experience:</p>
    <ul>
        <li>Use a modern browser (Chrome, Firefox, Safari, Edge)</li>
        <li>Keep your browser updated</li>
        <li>Enable JavaScript</li>
        <li>Allow location access for weather features</li>
        <li>Use a stable internet connection</li>
    </ul>
</div>

<div class="help-success">
    <h5><i class="fas fa-question-circle"></i> Getting Help</h5>
    <p>Need additional assistance?</p>
    <ul>
        <li>Check the specific application help sections</li>
        <li>Visit our troubleshooting guide</li>
        <li>Contact your system administrator</li>
        <li>Browse the detailed setup documentation</li>
    </ul>
</div>

<div style="margin-top: 40px; text-align: center;">
    <h3>Ready to Get Started?</h3>
    <p>Choose an application to begin exploring MediaBrain's features!</p>
    <div style="margin: 20px 0;">
        <a href="?app=bibleBot" class="btn">Try BibleBot</a>
        <a href="?app=recipes" class="btn">Try Recipes</a>
        <a href="?app=weather" class="btn">Try Weather</a>
        <?php if (isset($userRole) && $userRole !== 'guest'): ?>
        <a href="?app=ancestry" class="btn">Try Ancestry</a>
        <?php endif; ?>
    </div>
</div>