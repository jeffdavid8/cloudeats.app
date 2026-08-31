# Logging and Monitoring Commands

This document contains commands for monitoring logs and debugging the MediaBrain application in both local and cloud environments.

## Local Development Logs

### Application Logs (Windows PowerShell)
Monitor the main application log file with live updates:

```powershell
Get-Content c:\docker-dev\mediabrain.app\logs\app.log -Tail 40 -Wait
```

### Docker Container Logs

#### PHP Error Logs
Monitor PHP errors from within the container:

```bash
docker exec -it mediabrain-app tail -f //var/log/php74_app/php74_app_errors.log
```

#### Storage Operation Logs
Monitor storage-related operations:

```bash
docker exec -it mediabrain-app tail -f //var/data/mediabrain/storage/storage_operations.log
```

## Google Cloud Run Logs

### Error Logs
View recent error logs from Cloud Run (update revision name as needed):

```bash
gcloud logging read 'resource.type="cloud_run_revision" AND resource.labels.service_name="mediabrain-app" AND resource.labels.revision_name="mediabrain-app-00232-g44" AND severity=ERROR' --limit=20 --order=DESC
```
```bash
gcloud beta run services logs tail mediabrain-app --project=mediabrain --region=us-central1 --log-filter="severity>=DEFAULT"
```

### General Cloud Run Logs
View all logs for the service:

```bash
gcloud logging read 'resource.type="cloud_run_revision" AND resource.labels.service_name="mediabrain-app"' --limit=50 --order=DESC
```

## Log File Locations

### Local Development
- **Application logs**: `c:\docker-dev\mediabrain.app\logs\app.log`
- **PHP errors**: `/var/log/php74_app/php74_app_errors.log` (inside container)
- **Storage operations**: `/var/data/mediabrain/storage/storage_operations.log` (inside container)

### Cloud Run
- Logs are available through Google Cloud Console or `gcloud logging` commands
- Structured logs are sent to Cloud Logging automatically

## Tips

- Use `--limit` parameter to control number of log entries returned
- Add `--format=json` to gcloud commands for structured output
- For real-time monitoring in Cloud Run, use the Cloud Console Log Explorer