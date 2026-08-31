<?php
// Setup & Configuration help page
?>

<div class="help-breadcrumb">
    <a href="?app=help">Help Center</a> &gt; Setup & Configuration
</div>

<h1>Setup & Configuration</h1>

<p>This section provides comprehensive setup instructions for administrators and advanced users to configure MediaBrain for optimal performance.</p>

<div class="help-toc">
    <h4><i class="fas fa-list"></i> Configuration Topics</h4>
    <ul>
        <li><a href="#initial-setup">Initial System Setup</a></li>
        <li><a href="#docker-deployment">Docker Deployment</a></li>
        <li><a href="#cloud-run-deployment">Google Cloud Run Deployment</a></li>
        <li><a href="#oauth-configuration">OAuth Provider Configuration</a></li>
        <li><a href="#storage-configuration">Storage Configuration</a></li>
        <li><a href="#user-management">User Management Setup</a></li>
        <li><a href="#security-settings">Security Settings</a></li>
        <li><a href="#troubleshooting-setup">Setup Troubleshooting</a></li>
    </ul>
</div>

<h2 id="initial-setup">Initial System Setup</h2>

<div class="help-info">
    <h5><i class="fas fa-info-circle"></i> Prerequisites</h5>
    <p>Before setting up MediaBrain, ensure you have:</p>
    <ul>
        <li>PHP 8.2 or higher</li>
        <li>Composer for PHP dependency management</li>
        <li>Docker and Docker Compose (for containerized deployment)</li>
        <li>Google Cloud account (for cloud features)</li>
        <li>Web server (Apache/Nginx) or Docker</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Download and Extract</h4>
    <p>Clone or download the MediaBrain repository to your server:</p>
    <div class="help-code-block">
git clone https://github.com/jeffdavid8/mediabrain.net.git
cd mediabrain.net
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Install Dependencies</h4>
    <p>Install PHP dependencies using Composer:</p>
    <div class="help-code-block">
composer install --no-dev --optimize-autoloader
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>Configure Environment</h4>
    <p>Set up your environment variables in <code>mediabrain.ini</code>:</p>
    <div class="help-code-block">
; Core configuration
version = "0.5"
domain = "yourdomain.com"
site_name = "MediaBrain"
environment = "production"

; Database settings (if applicable)
db_host = "localhost"
db_name = "mediabrain"
db_user = "your_db_user"
db_pass = "your_db_password"

; Storage configuration
storage_provider = "gcs"  ; or "local"
gcs_bucket = "your-bucket-name"
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <h4>Set File Permissions</h4>
    <p>Ensure proper file permissions for web server access:</p>
    <div class="help-code-block">
# For Apache/Nginx
chmod -R 755 html/
chown -R www-data:www-data html/

# Create data directories
mkdir -p /var/data/mediabrain
chmod 755 /var/data/mediabrain
    </div>
</div>

<h2 id="docker-deployment">Docker Deployment</h2>

<p>Docker deployment is the recommended method for production environments.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Configure Docker Compose</h4>
    <p>Review and modify <code>docker-compose.yml</code> for your environment:</p>
    <div class="help-code-block">
version: '3.8'
services:
  mediabrain-app:
    build: .
    ports:
      - "8080:80"
    environment:
      - ENVIRONMENT=production
      - GOOGLE_APPLICATION_CREDENTIALS=/tmp/service-account-key.json
    volumes:
      - ./data:/var/data/mediabrain
      - ./service-account-key.json:/tmp/service-account-key.json:ro
    restart: unless-stopped
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Build and Start</h4>
    <p>Build the Docker image and start the containers:</p>
    <div class="help-code-block">
# Build and start in detached mode
docker-compose up -d --build

# View logs
docker-compose logs -f mediabrain-app

# Check container status
docker-compose ps
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>Verify Installation</h4>
    <p>Access your MediaBrain installation:</p>
    <div class="help-code-block">
# Local testing
curl -I http://localhost:8080

# Or open in browser
http://localhost:8080
    </div>
</div>

<h2 id="cloud-run-deployment">Google Cloud Run Deployment</h2>

<p>Deploy MediaBrain to Google Cloud Run for scalable, serverless hosting.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Setup Google Cloud Project</h4>
    <p>Create and configure your Google Cloud project:</p>
    <div class="help-code-block">
# Install Google Cloud SDK
# https://cloud.google.com/sdk/docs/install

# Login and set project
gcloud auth login
gcloud config set project YOUR_PROJECT_ID

# Enable required APIs
gcloud services enable run.googleapis.com
gcloud services enable storage.googleapis.com
gcloud services enable iam.googleapis.com
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Create Service Account</h4>
    <p>Create a service account with appropriate permissions:</p>
    <div class="help-code-block">
# Create service account
gcloud iam service-accounts create mediabrain-service \\
    --display-name="MediaBrain Service Account"

# Grant storage permissions
gcloud projects add-iam-policy-binding YOUR_PROJECT_ID \\
    --member="serviceAccount:mediabrain-service@YOUR_PROJECT_ID.iam.gserviceaccount.com" \\
    --role="roles/storage.admin"

# Create and download key
gcloud iam service-accounts keys create service-account-key.json \\
    --iam-account=mediabrain-service@YOUR_PROJECT_ID.iam.gserviceaccount.com
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>Deploy to Cloud Run</h4>
    <p>Deploy your application to Google Cloud Run:</p>
    <div class="help-code-block">
# Deploy from source
gcloud run deploy mediabrain-app \\
    --source . \\
    --region us-central1 \\
    --allow-unauthenticated \\
    --set-env-vars="ENVIRONMENT=production,GOOGLE_APPLICATION_CREDENTIALS=/tmp/service-account-key.json" \\
    --memory 512Mi \\
    --cpu 1 \\
    --max-instances 10
    </div>
</div>

<h2 id="oauth-configuration">OAuth Provider Configuration</h2>

<p>Configure social login providers for enhanced user authentication.</p>

<div class="help-feature-grid">
    <div class="help-feature-card">
        <h4><i class="fab fa-facebook"></i> Facebook OAuth</h4>
        <ol>
            <li>Go to <a href="https://developers.facebook.com" target="_blank">Facebook for Developers</a></li>
            <li>Create a new app or use existing</li>
            <li>Add Facebook Login product</li>
            <li>Configure OAuth redirect URI: <code>https://yourdomain.com/oauth/facebook.php</code></li>
            <li>Copy App ID and App Secret</li>
        </ol>
    </div>
    
    <div class="help-feature-card">
        <h4><i class="fab fa-google"></i> Google OAuth</h4>
        <ol>
            <li>Go to <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></li>
            <li>Create OAuth 2.0 credentials</li>
            <li>Add authorized redirect URI: <code>https://yourdomain.com/oauth/google.php</code></li>
            <li>Copy Client ID and Client Secret</li>
        </ol>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Configure OAuth Settings</h4>
    <p>Add OAuth credentials to your configuration:</p>
    <div class="help-code-block">
{
    "facebook": {
        "enabled": true,
        "app_id": "YOUR_FACEBOOK_APP_ID",
        "app_secret": "YOUR_FACEBOOK_APP_SECRET",
        "redirect_uri": "https://yourdomain.com/oauth/facebook.php"
    },
    "google": {
        "enabled": true,
        "client_id": "YOUR_GOOGLE_CLIENT_ID",
        "client_secret": "YOUR_GOOGLE_CLIENT_SECRET",
        "redirect_uri": "https://yourdomain.com/oauth/google.php"
    }
}
    </div>
</div>

<h2 id="storage-configuration">Storage Configuration</h2>

<p>Configure storage backends for file management and data persistence.</p>

<div class="help-expandable">
    <h3>Google Cloud Storage Setup</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>Create Storage Bucket</h4>
            <div class="help-code-block">
# Create bucket with appropriate naming
gsutil mb gs://mediabrain-prod-storage

# Set bucket permissions
gsutil iam ch serviceAccount:mediabrain-service@YOUR_PROJECT_ID.iam.gserviceaccount.com:roles/storage.admin gs://mediabrain-prod-storage
            </div>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Configure Application</h4>
            <p>Update storage configuration:</p>
            <div class="help-code-block">
{
    "provider": "gcs",
    "gcs": {
        "bucket": "mediabrain-prod-storage",
        "key_file": "/path/to/service-account-key.json"
    },
    "fallback": "local"
}
            </div>
        </div>
    </div>
</div>

<div class="help-expandable">
    <h3>Local Storage Setup</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>Create Storage Directories</h4>
            <div class="help-code-block">
# Create storage directories
mkdir -p /var/data/mediabrain/{users,permissions,recipes,ancestry}
chmod -R 755 /var/data/mediabrain
chown -R www-data:www-data /var/data/mediabrain
            </div>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Configure Application</h4>
            <div class="help-code-block">
{
    "provider": "local",
    "local": {
        "base_path": "/var/data/mediabrain"
    }
}
            </div>
        </div>
    </div>
</div>

<h2 id="user-management">User Management Setup</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Create Initial Admin User</h4>
    <p>Set up the first administrator account:</p>
    <div class="help-code-block">
# Access admin setup (one-time only)
https://yourdomain.com/?app=admin&setup=true

# Or create via command line
php html/apps/admin/create_admin.php --username=admin --password=secure_password --email=admin@yourdomain.com
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Configure User Roles</h4>
    <p>Define user roles and permissions in the admin panel:</p>
    <ol>
        <li>Login as administrator</li>
        <li>Navigate to User Management</li>
        <li>Configure role permissions</li>
        <li>Set default user role for new registrations</li>
    </ol>
</div>

<h2 id="security-settings">Security Settings</h2>

<div class="help-warning">
    <h5><i class="fas fa-shield-alt"></i> Security Checklist</h5>
    <ul>
        <li>✅ Enable HTTPS in production</li>
        <li>✅ Use strong passwords for admin accounts</li>
        <li>✅ Configure firewall rules</li>
        <li>✅ Regular security updates</li>
        <li>✅ Enable audit logging</li>
        <li>✅ Secure file permissions</li>
        <li>✅ Configure CSRF protection</li>
        <li>✅ Set up backup procedures</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Enable HTTPS</h4>
    <p>Configure SSL/TLS certificates:</p>
    <div class="help-code-block">
# Using Let's Encrypt with Certbot
certbot --apache -d yourdomain.com

# Or configure in Apache/Nginx
# Ensure redirect from HTTP to HTTPS
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Configure Session Security</h4>
    <p>Set secure session parameters in <code>php.ini</code>:</p>
    <div class="help-code-block">
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"
    </div>
</div>

<h2 id="troubleshooting-setup">Setup Troubleshooting</h2>

<div class="help-expandable">
    <h3>Common Setup Issues</h3>
    <div class="expandable-content">
        <h4>Permission Denied Errors</h4>
        <div class="help-code-block">
# Fix file permissions
sudo chown -R www-data:www-data /path/to/mediabrain
sudo chmod -R 755 /path/to/mediabrain

# Fix storage directory permissions
sudo mkdir -p /var/data/mediabrain
sudo chown -R www-data:www-data /var/data/mediabrain
        </div>
        
        <h4>Database Connection Issues</h4>
        <ul>
            <li>Verify database credentials in configuration</li>
            <li>Check database server is running</li>
            <li>Ensure database user has proper permissions</li>
            <li>Test connection manually</li>
        </ul>
        
        <h4>Google Cloud Storage Issues</h4>
        <ul>
            <li>Verify service account has storage.admin role</li>
            <li>Check bucket name matches configuration</li>
            <li>Ensure service account key file is accessible</li>
            <li>Verify GOOGLE_APPLICATION_CREDENTIALS environment variable</li>
        </ul>
    </div>
</div>

<div class="help-expandable">
    <h3>Performance Optimization</h3>
    <div class="expandable-content">
        <h4>PHP Configuration</h4>
        <div class="help-code-block">
# Recommended php.ini settings
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 50M
post_max_size = 50M
opcache.enable = 1
opcache.memory_consumption = 128
        </div>
        
        <h4>Caching Setup</h4>
        <ul>
            <li>Enable OPcache for PHP</li>
            <li>Configure browser caching headers</li>
            <li>Use CDN for static assets</li>
            <li>Enable gzip compression</li>
        </ul>
    </div>
</div>

<div class="help-success">
    <h5><i class="fas fa-check-circle"></i> Setup Complete!</h5>
    <p>Once setup is complete, verify your installation:</p>
    <ol>
        <li>Test login functionality</li>
        <li>Verify all applications load correctly</li>
        <li>Check file upload capabilities</li>
        <li>Test OAuth providers (if configured)</li>
        <li>Verify user role permissions</li>
    </ol>
</div>

<div style="margin-top: 40px; text-align: center;">
    <h3>Need Additional Help?</h3>
    <p>Explore specific application help sections or troubleshooting guides.</p>
    <div style="margin: 20px 0;">
        <a href="?app=help&section=troubleshooting" class="btn">Troubleshooting Guide</a>
        <a href="?app=help&section=admin" class="btn">Admin Panel Help</a>
        <a href="?app=admin" class="btn" target="_blank">Open Admin Panel</a>
    </div>
</div>