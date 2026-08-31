<?php
// Analytics Dashboard View
$isAdmin = isset($_SESSION['user']) && AuthManager::userIsAdmin($_SESSION['user']);
if (!$isAdmin) {
    AuthManager::requireAdmin();
}

use MediaBrain\Services\AnalyticsService;

$analytics = AnalyticsService::getInstance();

// Detect current theme
$currentTheme = $_SESSION['theme'] ?? 'default';
$isLCARS = ($currentTheme === 'startrek');
$themeClass = $isLCARS ? 'lcars-theme' : '';

// Get date range from query params
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$days = max(1, min(90, $days)); // Limit between 1 and 90 days

// Get analytics data
$overview = $analytics->getOverviewStats($days);
$chartData = $analytics->getChartData($days);
$recentVisits = $analytics->getRecentVisits(20);
$topPages = $analytics->getTopPages(10, $days);
$activeUsers = $analytics->getActiveUsers(5);
$topSearches = $analytics->getTopSearches(15, $days);
$errorStats = $analytics->getErrorStats($days);
?>

<?php if ($isLCARS): ?>
    <!-- LCARS Theme Styles -->
    <link rel="stylesheet" href="/themes/startrek/lcars-base.css">
    <link rel="stylesheet" href="/themes/startrek/analytics-lcars.css">
    <link rel="stylesheet" href="/themes/startrek/animations.css">
<?php endif; ?>

<style>
    .analytics-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .analytics-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 300;
        margin: 10px 0 5px 0;
    }

    .stat-icon {
        font-size: 3rem !important;
        opacity: 0.8;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin: 20px 0;
    }

    .progress-bar-container {
        margin: 10px 0;
    }

    .page-stats-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .page-stats-item:last-child {
        border-bottom: none;
    }

    .visitor-log-item {
        padding: 8px 12px;
        border-left: 3px solid #2196F3;
        margin-bottom: 8px;
        background: #f5f5f5;
        border-radius: 4px;
    }

    .visitor-log-item small {
        display: block;
        color: #666;
        margin-top: 4px;
    }

    .date-range-selector {
        margin: 20px 0;
    }

    .period-chip {
        display: inline-block;
        padding: 8px 16px;
        margin: 4px;
        background: #e0e0e0;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .period-chip:hover {
        background: #2196F3;
        color: white;
    }

    .period-chip.active {
        background: #2196F3;
        color: white;
    }
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php if ($isLCARS): ?>
    <!-- LCARS Sound Effects -->
    <script src="/apps/admin/js/analytics-lcars-sounds.js"></script>
<?php endif; ?>

<div class="row">
    <div class="col s12">
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=analytics" class="breadcrumb">Analytics</a>
                    <div class="grey-text right" style="white-space: nowrap;">
                        <a href="?p=dashboard">
                            <span>
                                Signed in as: <strong><?php echo is_array($_SESSION['user']) ? ($_SESSION['user']['username'] ?? 'User') : ($_SESSION['user'] ?? 'User'); ?></strong>
                                <? if (!empty($_SESSION['user']['profilePicture'])): ?>
                                    <img src="<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" alt="Profile Picture" class="circle responsive-img" style="width: 25px; height: 25px; vertical-align: middle; margin-left: 4px;">
                                <?php else: ?>
                                    <i style="display: inline-block;" class="material-icons tiny">account_circle</i>
                                <?php endif; ?>
                            </span>
                        </a>
                    </div>

                </div>
            </div>
        </nav>
        <h4><i class="fas fa-chart-bar left"></i>Visitor Analytics</h4>
        <p class="grey-text">Monitor traffic, user behavior, and platform usage</p>
    </div>
</div>

<!-- Date Range Selector -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title <?php echo $themeClass; ?>">Time Period</span>
                <div class="date-range-selector">
                    <a href="?app=admin&p=analytics&days=7" class="period-chip <?php echo $themeClass; ?> <?php echo $days === 7 ? 'active' : ''; ?>">Last 7 Days</a>
                    <a href="?app=admin&p=analytics&days=30" class="period-chip <?php echo $themeClass; ?> <?php echo $days === 30 ? 'active' : ''; ?>">Last 30 Days</a>
                    <a href="?app=admin&p=analytics&days=60" class="period-chip <?php echo $themeClass; ?> <?php echo $days === 60 ? 'active' : ''; ?>">Last 60 Days</a>
                    <a href="?app=admin&p=analytics&days=90" class="period-chip <?php echo $themeClass; ?> <?php echo $days === 90 ? 'active' : ''; ?>">Last 90 Days</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overview Statistics -->
<div class="row">
    <div class="col s12 m6 l3">
        <div class="card analytics-card <?php echo $themeClass; ?> <?php echo !$isLCARS ? 'blue lighten-5' : ''; ?>">
            <div class="card-content center">
                <i class="material-icons blue-text stat-icon">visibility</i>
                <div class="stat-number blue-text"><?php echo number_format($overview['total_visits']); ?></div>
                <p class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-orange);">Total Visits</span>' : 'Total Visits'; ?></p>
            </div>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card analytics-card <?php echo $themeClass; ?> <?php echo !$isLCARS ? 'green lighten-5' : ''; ?>">
            <div class="card-content center">
                <i class="material-icons green-text stat-icon">people</i>
                <div class="stat-number green-text"><?php echo number_format($overview['unique_visitors']); ?></div>
                <p class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-blue);">Unique Visitors</span>' : 'Unique Visitors'; ?></p>
            </div>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card analytics-card <?php echo $themeClass; ?> <?php echo !$isLCARS ? 'orange lighten-5' : ''; ?>">
            <div class="card-content center">
                <i class="material-icons orange-text stat-icon">account_circle</i>
                <div class="stat-number orange-text"><?php echo number_format($overview['authenticated_visits']); ?></div>
                <p class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-purple);">Authenticated Visits</span>' : 'Authenticated Visits'; ?></p>
            </div>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card analytics-card <?php echo $themeClass; ?> <?php echo !$isLCARS ? 'purple lighten-5' : ''; ?>">
            <div class="card-content center">
                <i class="material-icons purple-text stat-icon">public</i>
                <div class="stat-number purple-text"><?php echo number_format($overview['anonymous_visits']); ?></div>
                <p class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-orange);">Anonymous Visits</span>' : 'Anonymous Visits'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Real-Time & Error Stats Row -->
<div class="row">
    <div class="col s12 m6">
        <div class="card analytics-card <?php echo $themeClass; ?> active-users <?php echo !$isLCARS ? 'teal lighten-5' : ''; ?>">
            <div class="card-content center">
                <i class="material-icons teal-text stat-icon">online_prediction</i>
                <div class="stat-number teal-text" id="activeUsersCount"><?php echo number_format($activeUsers['count']); ?></div>
                <p class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-green);">Active Users (Last 5 min)</span>' : 'Active Users (Last 5 min)'; ?></p>
                <small class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-blue);">Auto-refreshes every 10 seconds</span>' : 'Auto-refreshes every 10 seconds'; ?></small>
            </div>
        </div>
    </div>
    <div class="col s12 m6">
        <div class="card analytics-card <?php echo $themeClass; ?> error-card <?php echo !$isLCARS ? 'red lighten-5' : ''; ?>">
            <div class="card-content center">
                <i class="material-icons red-text stat-icon">error_outline</i>
                <div class="stat-number red-text"><?php echo number_format($errorStats['total_errors']); ?></div>
                <p class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-red);">Errors (Last ' . $days . ' days)</span>' : 'Errors (Last ' . $days . ' days)'; ?></p>
                <small class="<?php echo $isLCARS ? '' : 'grey-text'; ?>"><?php echo $isLCARS ? '<span style="color: var(--lcars-orange);">Error Rate: ' . $errorStats['error_rate'] . '%</span>' : 'Error Rate: ' . $errorStats['error_rate'] . '%'; ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Traffic Chart -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title"><i class="material-icons left">show_chart</i>Traffic Trend</span>
                <div class="chart-container">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="row">
    <!-- Top Pages -->
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title"><i class="material-icons left">insert_chart</i>Top Pages</span>
                <?php if (empty($topPages)): ?>
                    <p class="grey-text">No page data available for this period.</p>
                <?php else: ?>
                    <?php
                    $maxViews = max($topPages);
                    foreach ($topPages as $page => $views):
                        $percentage = $maxViews > 0 ? ($views / $maxViews) * 100 : 0;
                    ?>
                        <div class="page-stats-item">
                            <div class="row valign-wrapper" style="margin-bottom: 5px;">
                                <div class="col s8">
                                    <strong><?php echo htmlspecialchars($page); ?></strong>
                                </div>
                                <div class="col s4 right-align">
                                    <span class="blue-text"><?php echo number_format($views); ?> views</span>
                                </div>
                            </div>
                            <div class="progress">
                                <div class="determinate blue" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Browser & Device Stats -->
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title"><i class="material-icons left">devices</i>Browsers & Devices</span>

                <h6 class="grey-text">Browsers</h6>
                <?php if (empty($overview['browsers'])): ?>
                    <p class="grey-text">No browser data available.</p>
                <?php else: ?>
                    <?php
                    $maxBrowser = max($overview['browsers']);
                    foreach ($overview['browsers'] as $browser => $count):
                        $percentage = $maxBrowser > 0 ? ($count / $maxBrowser) * 100 : 0;
                    ?>
                        <div style="margin: 10px 0;">
                            <div class="row valign-wrapper" style="margin-bottom: 5px;">
                                <div class="col s6">
                                    <?php echo htmlspecialchars($browser); ?>
                                </div>
                                <div class="col s6 right-align">
                                    <span class="grey-text"><?php echo number_format($count); ?></span>
                                </div>
                            </div>
                            <div class="progress">
                                <div class="determinate green" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h6 class="grey-text" style="margin-top: 20px;">Devices</h6>
                <?php if (empty($overview['devices'])): ?>
                    <p class="grey-text">No device data available.</p>
                <?php else: ?>
                    <?php
                    $maxDevice = max($overview['devices']);
                    foreach ($overview['devices'] as $device => $count):
                        $percentage = $maxDevice > 0 ? ($count / $maxDevice) * 100 : 0;
                    ?>
                        <div style="margin: 10px 0;">
                            <div class="row valign-wrapper" style="margin-bottom: 5px;">
                                <div class="col s6">
                                    <?php echo htmlspecialchars($device); ?>
                                </div>
                                <div class="col s6 right-align">
                                    <span class="grey-text"><?php echo number_format($count); ?></span>
                                </div>
                            </div>
                            <div class="progress">
                                <div class="determinate orange" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Searches & Error Log Row -->
<div class="row">
    <!-- Top Searches -->
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title"><i class="material-icons left">search</i>Top Searches</span>
                <?php if (empty($topSearches)): ?>
                    <p class="grey-text">No search data available for this period.</p>
                <?php else: ?>
                    <?php foreach ($topSearches as $search): ?>
                        <div class="page-stats-item">
                            <div class="row valign-wrapper" style="margin-bottom: 5px;">
                                <div class="col s8">
                                    <strong>"<?php echo htmlspecialchars($search['query']); ?>"</strong>
                                </div>
                                <div class="col s4 right-align">
                                    <span class="blue-text"><?php echo number_format($search['count']); ?> searches</span>
                                </div>
                            </div>
                            <small class="grey-text">Avg results: <?php echo number_format($search['total_results'] / max(1, $search['count'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Error Log Tail -->
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">error</i>Recent Errors
                    <label class="right">
                        <input type="checkbox" id="errorLogLiveTail" />
                        <span>Live Tail</span>
                    </label>
                </span>
                <div id="errorLogContainer" class="<?php echo $themeClass; ?>" style="max-height: 400px; overflow-y: auto; <?php echo !$isLCARS ? 'background: #f5f5f5;' : ''; ?> padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    <?php if (empty($errorStats['recent_errors'])): ?>
                        <p class="grey-text">No errors recorded for this period.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($errorStats['recent_errors'], 0, 10) as $error): ?>
                            <div style="margin-bottom: 8px; padding: 4px; border-left: 3px solid #f44336;">
                                <div><strong><?php echo htmlspecialchars($error['message']); ?></strong></div>
                                <small class="grey-text">
                                    <?php echo htmlspecialchars($error['file']); ?>:<?php echo $error['line']; ?>
                                    | <?php echo date('M j, g:i A', $error['timestamp']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Visitors -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title"><i class="material-icons left">history</i>Recent Visitors</span>
                <?php if (empty($recentVisits)): ?>
                    <p class="grey-text">No recent visitor data available.</p>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($recentVisits as $visit): ?>
                            <div class="visitor-log-item">
                                <div class="row valign-wrapper" style="margin-bottom: 0;">
                                    <div class="col s12 m8">
                                        <i class="material-icons tiny blue-text">
                                            <?php echo $visit['device_type'] === 'Mobile' ? 'smartphone' : 'computer'; ?>
                                        </i>
                                        <strong><?php echo htmlspecialchars($visit['user']); ?></strong>
                                        visited
                                        <strong><?php echo htmlspecialchars($visit['page']); ?></strong>
                                        <?php if ($visit['subpage']): ?>
                                            <span class="grey-text">(<?php echo htmlspecialchars($visit['subpage']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col s12 m4 right-align">
                                        <span class="grey-text"><?php echo date('M j, g:i A', $visit['timestamp']); ?></span>
                                    </div>
                                </div>
                                <small>
                                    <?php echo htmlspecialchars($visit['browser']); ?> on <?php echo htmlspecialchars($visit['os']); ?>
                                    | IP: <?php echo htmlspecialchars($visit['ip']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart.js configuration
    const ctx = document.getElementById('trafficChart').getContext('2d');
    const chartData = <?php echo json_encode($chartData); ?>;

    const trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                    label: 'Total Visits',
                    data: chartData.visits,
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Unique Visitors',
                    data: chartData.unique_visitors,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Active Users Auto-Refresh
    let activeUsersInterval;
    const isLCARS = <?php echo $isLCARS ? 'true' : 'false'; ?>;

    function updateActiveUsers() {
        fetch('/?api=admin&action=analytics_active_users&time_window=5')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const countElement = document.getElementById('activeUsersCount');
                    const oldCount = parseInt(countElement.textContent.replace(/,/g, ''));
                    const newCount = result.data.count;

                    countElement.textContent = newCount.toLocaleString();

                    // Play sound if count changed and LCARS theme active
                    if (isLCARS && newCount !== oldCount && window.lcarsAnalyticsSounds) {
                        window.lcarsAnalyticsSounds.play('activeUser');
                    }

                    // Add pulse animation
                    countElement.style.animation = 'none';
                    setTimeout(() => {
                        countElement.style.animation = 'lcars-pulse 1s';
                    }, 10);
                }
            })
            .catch(err => console.error('Active users update error:', err));
    }

    // Auto-refresh every 10 seconds
    activeUsersInterval = setInterval(updateActiveUsers, 10000);

    // Error Log Live Tail
    let errorLogTailInterval;
    const errorLogCheckbox = document.getElementById('errorLogLiveTail');
    const errorLogContainer = document.getElementById('errorLogContainer');

    errorLogCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Play acknowledge sound for LCARS
            if (isLCARS && window.lcarsAnalyticsSounds) {
                window.lcarsAnalyticsSounds.play('acknowledge');
            }

            // Add live-tail class for LCARS animation
            if (isLCARS) {
                errorLogContainer.classList.add('live-tail');
            }

            // Start live tail
            updateErrorLog();
            errorLogTailInterval = setInterval(updateErrorLog, 3000); // Every 3 seconds
        } else {
            // Stop live tail
            if (errorLogTailInterval) {
                clearInterval(errorLogTailInterval);
                errorLogTailInterval = null;
            }

            // Remove live-tail class
            if (isLCARS) {
                errorLogContainer.classList.remove('live-tail');
            }
        }
    });

    function updateErrorLog() {
        fetch('/?api=admin&action=analytics_error_log_tail&lines=20')
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data.length > 0) {
                    // Play scan sound for LCARS
                    if (isLCARS && window.lcarsAnalyticsSounds) {
                        window.lcarsAnalyticsSounds.play('scan');
                    }

                    errorLogContainer.innerHTML = result.data.map(log =>
                        `<div style="margin-bottom: 4px; padding: 2px; font-size: 11px; border-left: 2px solid ${isLCARS ? 'var(--lcars-red)' : '#f44336'};">
                        ${escapeHtml(log.line)}
                    </div>`
                    ).join('');

                    // Auto-scroll to bottom
                    errorLogContainer.scrollTop = errorLogContainer.scrollHeight;
                }
            })
            .catch(err => console.error('Error log tail error:', err));
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (activeUsersInterval) clearInterval(activeUsersInterval);
        if (errorLogTailInterval) clearInterval(errorLogTailInterval);
    });
</script>