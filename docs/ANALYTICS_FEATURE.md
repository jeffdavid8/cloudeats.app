# Visitor Analytics Feature

## Overview
Comprehensive visitor analytics system for MediaBrain admin panel with real-time tracking, traffic analysis, and detailed visitor insights.

## Features Implemented

### 1. Core Analytics Service
**File**: `html/includes/Services/AnalyticsService.php`
- **Automatic Page Tracking**: Captures every page view automatically
- **Privacy-Focused**: IP addresses anonymized (last octet removed)
- **Smart Data Storage**: JSON-based storage with automatic rotation (keeps last 10,000 visits, 90 days of daily stats)
- **Performance Optimized**: Minimal overhead, fails silently if errors occur

### 2. Analytics Dashboard
**File**: `html/apps/admin/views/analytics.php`
**URL**: `https://mediabrain.app.local/?app=admin&p=analytics`

#### Dashboard Features:
- **Overview Statistics Cards**:
  - Total Visits
  - Unique Visitors (by IP)
  - Authenticated Visits
  - Anonymous Visits

- **Traffic Trend Chart**:
  - Interactive Chart.js line graph
  - Total visits vs unique visitors
  - Configurable time periods (7, 30, 60, 90 days)

- **Top Pages Analysis**:
  - Most visited pages/apps
  - Visual progress bars showing relative popularity
  - Page view counts

- **Browser & Device Stats**:
  - Browser breakdown (Chrome, Firefox, Safari, Edge, etc.)
  - Device type distribution (Desktop, Mobile, Tablet)
  - OS information (Windows, macOS, Linux, Android, iOS)

- **Recent Visitors Log**:
  - Last 20 visitors with details
  - Timestamp, user, page visited
  - Browser, OS, and anonymized IP
  - Device type indicators

### 3. API Endpoints
**File**: `html/apps/admin/admin.api.php`

All endpoints require admin authentication:

- `/?api=admin&action=analytics_overview&days=30`
  - Returns overview statistics for specified period
  
- `/?api=admin&action=analytics_chart_data&days=30`
  - Returns chart data (labels, visits, unique visitors)
  
- `/?api=admin&action=analytics_recent_visits&limit=50`
  - Returns recent visitor log (max 100)
  
- `/?api=admin&action=analytics_top_pages&days=30&limit=10`
  - Returns top pages by view count (max 50)

### 4. Data Storage
**Files Created Automatically**:
- `html/json/analytics_visits.json` - Individual visit records
- `html/json/analytics_daily.json` - Aggregated daily statistics

**Data Collected Per Visit**:
```json
{
    "timestamp": 1234567890,
    "datetime": "2025-11-18 14:30:00",
    "page": "admin",
    "subpage": "analytics",
    "url": "/path",
    "referrer": "https://...",
    "ip": "192.168.1.xxx",
    "user_agent": "...",
    "browser": "Chrome",
    "os": "Windows",
    "device_type": "Desktop",
    "session_id": "...",
    "user": "admin",
    "is_authenticated": true
}
```

### 5. Integration Points

#### Index.php Integration
**File**: `html/index.php`
Automatic tracking added after session start:
```php
use MediaBrain\Services\AnalyticsService;
$analytics = AnalyticsService::getInstance();
$analytics->trackPageView();
```

#### Admin Dashboard Quick Action
**File**: `html/apps/admin/views/dashboard.php`
Added to Quick Actions section with teal analytics icon.

#### Admin App Routing
**File**: `html/apps/admin/admin.app.php`
Added `case 'analytics':` route to display analytics page.

## Usage

### For Administrators:
1. Navigate to Admin panel: `/?app=admin`
2. Click "Analytics" in Quick Actions
3. View comprehensive visitor statistics
4. Change time period using period chips (7, 30, 60, 90 days)
5. Scroll to see top pages, browser/device stats, and recent visitors

### For Developers:
```php
use MediaBrain\Services\AnalyticsService;

$analytics = AnalyticsService::getInstance();

// Get overview for last 30 days
$stats = $analytics->getOverviewStats(30);

// Get chart data
$chartData = $analytics->getChartData(30);

// Get recent visits
$visits = $analytics->getRecentVisits(50);

// Get top pages
$topPages = $analytics->getTopPages(10, 30);

// Track custom event
$analytics->trackPageView([
    'custom_field' => 'custom_value'
]);
```

## Performance Considerations

- **Minimal Overhead**: Tracking happens in try/catch block, fails silently
- **Automatic Cleanup**: Old data automatically purged to prevent file bloat
- **No Database Required**: Uses lightweight JSON storage
- **Session-Based**: Leverages existing session infrastructure
- **Privacy-Respecting**: No cookies, IP anonymization

## Data Retention

- **Individual Visits**: Last 10,000 visits retained
- **Daily Aggregates**: Last 90 days retained
- **Automatic Rotation**: Old data automatically removed

## Privacy & Security

- **IP Anonymization**: Last octet of IP addresses replaced with 'xxx'
- **Admin-Only Access**: All analytics endpoints require admin privileges
- **CSRF Protection**: Inherits admin API security
- **No External Services**: All data stored locally
- **Session-Based**: Uses existing authentication system

## Browser Compatibility

- Modern browsers with ES6 support
- Chart.js 4.4.0 loaded from CDN
- Responsive design works on mobile/tablet/desktop

## Additional Features Added

### Real-Time Active Users
- Live counter showing active users in last 5 minutes
- Auto-refreshes every 10 seconds
- Displays session count and user details
- API Endpoint: `/?api=admin&action=analytics_active_users`

### Search Query Tracking
- Tracks all BibleBot searches automatically
- Records query, result count, timestamp, user
- Top Searches dashboard shows most popular queries
- Average result counts per query
- API Endpoint: `/?api=admin&action=analytics_top_searches`
- Data stored in: `html/json/analytics_searches.json`

### Error Rate Integration
- Automatic error tracking and logging
- Error statistics with error rate calculation
- Live error log tail with toggle switch
- Real-time error.log monitoring (updates every 3 seconds when enabled)
- API Endpoints:
  - `/?api=admin&action=analytics_error_stats`
  - `/?api=admin&action=analytics_error_log_tail`
- Data stored in: `html/json/analytics_errors.json`

## Future Enhancements (Optional)

- Export analytics to CSV/JSON
- Email reports (weekly/monthly summaries)
- Custom date range picker
- Conversion funnel tracking
- Heatmap visualization
- A/B testing integration
- Geographic visualization (if IP geolocation added)

## Files Modified

1. `html/includes/Services/AnalyticsService.php` (NEW)
2. `html/apps/admin/views/analytics.php` (NEW)
3. `html/apps/admin/admin.api.php` (MODIFIED - added 4 endpoints)
4. `html/apps/admin/admin.app.php` (MODIFIED - added analytics route)
5. `html/apps/admin/views/dashboard.php` (MODIFIED - added quick action link)
6. `html/index.php` (MODIFIED - added tracking call)

## Testing

1. Visit any page on the site
2. Navigate to `/?app=admin&p=analytics`
3. Verify data appears in overview cards
4. Check that chart displays properly
5. Verify recent visitors log shows your visit
6. Test different time period filters
7. Verify JSON files created in `html/json/`

## Maintenance

- No scheduled maintenance required
- Data auto-rotates to prevent bloat
- Check `html/json/` directory size periodically
- Consider archiving old analytics data if needed
