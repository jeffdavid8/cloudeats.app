# Event Logging System

The MediaBrain application includes a comprehensive event logging system for tracking user actions, system events, and debugging purposes.

## Overview

The event logging system captures structured events to `logs/event.log` in JSON format, providing detailed information about user activities, authentication events, application flow, and system operations.

## Components

### EventLogger Class (`includes/EventLogger.php`)

The core logging class that provides:
- **Structured logging** - JSON format with timestamp, user, IP, context
- **Log levels** - INFO, WARNING, ERROR, DEBUG
- **Enable/disable control** - Can be toggled on/off
- **Persistent configuration** - Settings saved to `logs/event_config.json`

### App Integration

Event logging is integrated into the App class:
- Auto-initialized in App constructor
- Available via `$app->getEventLogger()`
- Convenience method `$app->logEvent()`

### Admin Interface

The admin app includes a logs section (`?app=admin&p=logs`) with:
- **Real-time log viewing** - Event and error logs
- **Enable/disable controls** - Toggle logging on/off
- **Log management** - Clear logs, adjust view settings
- **Multiple tabs** - Separate views for events, errors, and settings

## Usage

### Basic Logging

```php
// Get logger instance
$eventLogger = EventLogger::getInstance();

// Log different levels
$eventLogger->info('USER_LOGIN', 'User logged in successfully', ['username' => $username]);
$eventLogger->warning('FAILED_LOGIN', 'Login attempt failed', ['username' => $username, 'reason' => 'invalid_password']);
$eventLogger->error('DATABASE_ERROR', 'Database connection failed', ['error' => $error_message]);
$eventLogger->debug('API_CALL', 'External API called', ['endpoint' => $url, 'response_time' => $time]);
```

### Via App Class

```php
$app = App::getInstance();
$app->logEvent('INFO', 'PAGE_VIEW', 'User viewed page', ['page' => $page_name]);
```

### Current Auto-Logged Events

The system automatically logs:
- **APP_INIT** - Application initialization
- **APP_REQUEST** - App requests with method and parameters
- **APP_NOT_FOUND** - 404 errors for missing apps
- **AUTH_REQUIRED** - Authentication required events
- **AUTH_SUCCESS** - Successful authentication
- **ADMIN_REQUIRED** - Admin privilege requirements
- **ADMIN_ACCESS** - Admin user access events
- **LOGGING_ENABLED/DISABLED** - Logging state changes
- **LOG_CLEARED** - Log clearing events

## Log Format

Each log entry is a JSON object containing:

```json
{
  "timestamp": "2025-11-07 14:30:15",
  "level": "INFO",
  "event": "USER_LOGIN",
  "message": "User logged in successfully",
  "user": "john_doe",
  "session_id": "abc123",
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "request_uri": "/app/dashboard",
  "context": {
    "additional": "data"
  }
}
```

## File Locations

- **Event logs**: `logs/event.log`
- **Configuration**: `logs/event_config.json`
- **Error logs**: `logs/app.log` (existing Monolog)

## Admin Management

Access the logs section at `?app=admin&p=logs` to:

1. **View Events** - Browse recent event log entries with filtering
2. **View Errors** - Browse application error logs
3. **Control Logging** - Enable/disable event logging
4. **Clear Logs** - Clear event log files
5. **Configure Settings** - Adjust logging parameters

## Security Considerations

- Event logging can be disabled for performance or privacy
- Logs may contain sensitive information - secure access appropriately
- Log files should be rotated regularly to prevent disk space issues
- Admin privileges required to access log management

## Performance Impact

- Minimal performance impact when logging is enabled
- JSON encoding and file I/O per logged event
- Consider disabling in high-traffic production environments if needed
- Log files grow over time - implement rotation as needed

## Extending the System

### Custom Event Types

Add new event types by calling the logger with descriptive event names:

```php
$eventLogger->info('RECIPE_CREATED', 'New recipe created', [
    'recipe_id' => $recipe_id,
    'recipe_name' => $recipe_name,
    'category' => $category
]);
```

### Additional Context

Include relevant context data for better debugging:

```php
$eventLogger->error('PAYMENT_FAILED', 'Payment processing failed', [
    'transaction_id' => $transaction_id,
    'amount' => $amount,
    'error_code' => $error_code,
    'gateway_response' => $response
]);
```

### Integration Points

Add logging to key application flows:
- User registration/login
- Data creation/modification/deletion
- External API calls
- File uploads/downloads
- Security-related events
- Performance bottlenecks

## Best Practices

1. **Use appropriate log levels** - INFO for normal flow, WARNING for issues, ERROR for failures
2. **Provide clear event names** - Use UPPERCASE with underscores (e.g., USER_LOGIN, DATA_EXPORT)
3. **Include relevant context** - Add data that helps debugging without exposing sensitive info
4. **Avoid logging passwords** - Never log sensitive authentication data
5. **Use structured data** - Pass arrays/objects as context rather than concatenating strings
6. **Monitor log growth** - Implement rotation or cleanup for production environments