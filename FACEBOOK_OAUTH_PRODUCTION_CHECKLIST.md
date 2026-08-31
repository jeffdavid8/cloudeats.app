# Production Deployment Checklist for Facebook OAuth

## 1. Create Production OAuth Configuration
```bash
# On production server, create the OAuth config directory
sudo mkdir -p /var/data/mediabrain
sudo chown www-data:www-data /var/data/mediabrain

# Create production OAuth config file
sudo cat > /var/data/mediabrain/oauth_config.json << 'EOF'
{
    "google": {
        "enabled": false,
        "client_id": "",
        "client_secret": "",
        "redirect_uri": "https://mediabrain.app/oauth/google.php?action=callback",
        "scopes": ["openid", "email", "profile"]
    },
    "apple": {
        "enabled": false,
        "client_id": "",
        "team_id": "",
        "key_id": "",
        "private_key_path": "/var/data/mediabrain/apple_private_key.p8",
        "redirect_uri": "https://mediabrain.app/oauth/apple.php?action=callback",
        "scopes": ["name", "email"]
    },
    "facebook": {
        "enabled": false,
        "client_id": "YOUR_PRODUCTION_FACEBOOK_APP_ID",
        "client_secret": "YOUR_PRODUCTION_FACEBOOK_APP_SECRET",
        "redirect_uri": "https://mediabrain.app/oauth/facebook.php?action=callback",
        "scopes": ["email", "public_profile"]
    },
    "created_at": "2025-10-23 20:00:00",
    "updated_at": "2025-10-23 20:00:00"
}
EOF

# Set proper permissions
sudo chown www-data:www-data /var/data/mediabrain/oauth_config.json
sudo chmod 664 /var/data/mediabrain/oauth_config.json
```

## 2. Facebook App Configuration
- **App Domain**: Add `mediabrain.app` to Facebook app settings
- **OAuth Redirect URI**: `https://mediabrain.app/oauth/facebook.php?action=callback`
- **App Environment**: Switch to "Live" mode if in development
- **App Review**: Ensure email and public_profile permissions are approved

## 3. Environment Variables (if using)
- Consider using environment variables for sensitive OAuth credentials
- Update OAuthHandler to read from env vars in production

## 4. Testing in Production
1. Deploy code
2. Access admin panel: `https://mediabrain.app/apps/admin/`
3. Configure Facebook OAuth credentials
4. Test login page: `https://mediabrain.app/views/pages/login.php`
5. Verify Facebook button is active
6. Test complete OAuth flow

## 5. Security Considerations
- OAuth config file contains sensitive data - ensure proper permissions
- Consider encrypting secrets at rest
- Monitor OAuth callback endpoints for abuse
- Set up error logging for OAuth failures

## 6. Monitoring
- Check `/var/log/nginx/error.log` for OAuth-related errors
- Monitor OAuth callback success/failure rates
- Set up alerts for OAuth configuration issues