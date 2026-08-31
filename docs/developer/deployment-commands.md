# Deployment Commands

This document contains useful commands for deploying and managing the MediaBrain application in Google Cloud Run and local Docker environments.

## Google Cloud Run Deployment

### Update Cloud Run Service with Service Account
Updates the MediaBrain app service to use the specified service account for authentication:

```bash
gcloud run services update mediabrain-app \
  --service-account=mediabrain-secret-sa@mediabrain.iam.gserviceaccount.com \
  --region=us-central1 \
  --project=mediabrain
```

### Grant Secret Manager Access
Grants the storage service account access to Secret Manager:

```bash
gcloud projects add-iam-policy-binding mediabrain \
  --member="serviceAccount:mediabrain-php-storage-sa@mediabrain.iam.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"
```

## Local Development

### Container Management
The application runs in a Docker container managed from the parent directory:

```bash
# From C:\docker-dev directory:
docker-compose stop php74           # Stop MediaBrain app container
docker-compose start php74          # Start MediaBrain app container
docker-compose up -d --force-recreate php74  # Force recreate container
```

### File Sync Verification
Verify that local files are properly mounted in the container:

```bash
docker inspect mediabrain-app --format="{{range .Mounts}}{{.Source}} -> {{.Destination}} ({{.Type}}){{println}}{{end}}"
```

Expected output should show `C:\docker-dev\mediabrain.app -> /var/www/mediabrain.app.local (bind)`