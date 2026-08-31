# Cloud Run Admin Login Fix

## Problem
Cloud Run containers are stateless - the `/var/data/mediabrain/users.json` file gets reset on every deployment, making admin login impossible.

## Solution
Updated `AdminAuth.php` to detect Cloud Run environment and use environment variables for authentication.

## Current Setup
- **Username**: `admin`
- **Password**: `YourSecurePassword123!` (set in cloudbuild.yaml)

## To Change Admin Password

### Option 1: Update cloudbuild.yaml (Recommended)
1. Edit `cloudbuild.yaml`
2. Change the `ADMIN_PASSWORD` value:
   ```yaml
   '--set-env-vars'
   'ADMIN_USERNAME=admin,ADMIN_PASSWORD=YourNewSecurePassword!'
   ```
3. Redeploy: `git push origin main`

### Option 2: Use gcloud command directly
```bash
gcloud run services update mediabrain-app \
  --region=us-central1 \
  --set-env-vars="ADMIN_USERNAME=admin,ADMIN_PASSWORD=YourNewPassword"
```

## Environment Variables Set
- `ADMIN_USERNAME`: `admin`
- `ADMIN_PASSWORD`: `YourSecurePassword123!`

## Security Notes
- Environment variables are encrypted at rest in Cloud Run
- Only visible to authorized users with Cloud Run Admin role
- Change the default password before deployment
- Consider using Google Secret Manager for even better security

## Testing After Deployment
1. Visit: https://your-cloud-run-url/views/pages/login.php
2. Login with: `admin` / `YourSecurePassword123!`
3. Should successfully access admin panel

## Next Steps for Better Security
Consider migrating to Google Secret Manager:
1. Store admin credentials in Secret Manager
2. Update AdminAuth.php to read from secrets
3. Grant Cloud Run service account secret access