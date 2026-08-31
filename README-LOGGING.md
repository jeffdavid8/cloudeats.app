# Mediabrain Logging & Error Handling Guide

## Overview
All application errors, warnings, and debug logs are now routed through [Monolog](https://github.com/Seldaek/monolog) and the central `App::registerError()` handler. This provides structured, timestamped logs for easier debugging and monitoring.

## Log File Location
- **Main log file:** `logs/app.log` (at the project root)
- **Storage operations:** `var/data/mediabrain/storage/storage_operations.log` (for file/storage events)

## Log Format
- Each entry includes a timestamp, severity (e.g., ERROR), message, and context (such as backtrace, file, and line number).
- Example:
  ```
  [2025-11-07T00:43:25.704135+00:00] mediabrain.ERROR: PHP Error [2]: session_start(): Session cannot be started after headers have already been sent {"backtrace": [...], "file": "...", "line": 10}
  ```

## Viewing Logs
- **Windows PowerShell:**
  ```powershell
  Get-Content logs\app.log -Tail 50
  Get-Content logs\app.log -Wait
  ```
- **Linux/macOS:**
  ```sh
  tail -n 50 logs/app.log
  tail -f logs/app.log
  ```

## Log Rotation
- By default, logs grow indefinitely. For production, set up log rotation (e.g., with [logrotate](https://linux.die.net/man/8/logrotate) or Windows Task Scheduler) to archive and compress old logs.

## Custom Logging
- Use the `log_error($message, $context)` function anywhere in your PHP code to log custom messages.
- All legacy `error_log()` calls have been migrated to use this function.

## Troubleshooting
- If you do not see expected logs:
  - Ensure `logs/app.log` exists and is writable by the web server/PHP process.
  - Confirm Monolog is installed (`composer show monolog/monolog`).
  - Check for PHP errors in the web server error log.

## Advanced
- To change log level, format, or add new log channels, edit the Monolog initialization in `html/includes/app.php`.
- For more info, see the [Monolog documentation](https://github.com/Seldaek/monolog).

---
For questions or help, contact the project maintainer or see the developer docs in `/docs`.
