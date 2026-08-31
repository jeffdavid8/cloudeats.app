<?php
$app = App::getInstance();
// Remove EventLogger instantiation to prevent page load timeout
// $eventLogger = EventLogger::resetInstance();
// $eventLogEnabled = $eventLogger->isEnabled();
?>

<div class="container">
    <h4><i class="fas fa-file-alt"></i> Log Management</h4>
    
    <div class="row">
        <div class="col s12">
            <ul class="tabs">
                <li class="tab col s4"><a href="#event-logs" class="active">Event Logs</a></li>
                <li class="tab col s4"><a href="#error-logs">Error Logs</a></li>
                <li class="tab col s4"><a href="#log-settings">Settings</a></li>
            </ul>
        </div>
    </div>

    <div class="row">
        <!-- Event Logs Tab -->
        <div id="event-logs" class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <div class="col s12 m6">
                            <span class="card-title">Live Event Logs</span>
                            <p>Status: <span id="event-log-status" class="green-text">Active</span></p>
                            <small class="grey-text">Shows real-time events for this session only</small>
                        </div>
                        <div class="col s12 m6 right-align">
                            <button id="refresh-event-logs" class="btn blue"><i class="fas fa-sync"></i> Refresh</button>
                            <button id="clear-and-refresh-event-logs" class="btn orange"><i class="fas fa-broom"></i> Add Clear Event</button>
                            <button id="toggle-event-logging" class="btn green">
                                <i class="fas fa-power-off"></i> <span id="logging-status">Toggle Logging</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12 m4">
                            <label for="event-log-lines">Lines to show:</label>
                            <select id="event-log-lines">
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="200">200</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="event-log-content" class="log-content">
                        <!-- Event log entries will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Logs Tab -->
        <div id="error-logs" class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <div class="col s12 m6">
                            <span class="card-title">Error Logs</span>
                        </div>
                        <div class="col s12 m6 right-align">
                            <button id="refresh-error-logs" class="btn blue"><i class="fas fa-sync"></i> Refresh</button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12 m4">
                            <label for="error-log-lines">Lines to show:</label>
                            <select id="error-log-lines">
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="200">200</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                    </div>
                
                <div id="error-log-content" class="log-content">
                    <!-- Error log entries will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Log Settings Tab -->
    <div id="log-settings" class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">Log Settings</span>
                
                <div class="row">
                    <div class="col s12">
                        <h5>Live Event Logging</h5>
                        <p>Live event logging shows real-time events for your current admin session without file storage.</p>
                        
                        <div class="switch">
                            <label>
                                <span class="green-text">Always Active</span>
                                <input type="checkbox" checked disabled>
                                <span class="lever"></span>
                                <span class="green-text">Live Mode</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col s12">
                        <h5>Log Files</h5>
                        <ul class="collection">
                            <li class="collection-item">
                                <span class="title">Live Event Logs</span>
                                <p>Real-time session-based event logging</p>
                                <small class="grey-text">Each browser session gets its own live event stream</small>
                            </li>
                            <li class="collection-item">
                                <span class="title">System Error Log</span>
                                <p><?= dirname(__DIR__, 3) . '/logs/app.log' ?></p>
                            </li>
                        </ul>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Close row wrapper -->
    </div>
</div>

<style>
/* Fix container overflow issues */
.container {
    max-width: 100%;
    overflow-x: hidden;
}

/* Fix tab content layout */
.tabs .tab {
    width: 33.33%;
}

.tabs .tab a {
    font-size: 12px;
    padding: 0 12px;
}

/* Ensure tab content doesn't overflow */
#event-logs, #error-logs, #log-settings {
    width: 100%;
    overflow-x: hidden;
}

.log-content {
    max-height: 500px;
    overflow-y: auto;
    overflow-x: hidden;
    border: 1px solid #ddd;
    padding: 10px;
    background-color: #f8f9fa;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    word-wrap: break-word;
}

.log-entry {
    margin-bottom: 8px;
    padding: 5px;
    border-radius: 3px;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.log-entry.info { background-color: #d1ecf1; border-left: 3px solid #17a2b8; }
.log-entry.warning { background-color: #fff3cd; border-left: 3px solid #ffc107; }
.log-entry.error { background-color: #f8d7da; border-left: 3px solid #dc3545; }
.log-entry.debug { background-color: #e2e3e5; border-left: 3px solid #6c757d; }

.log-timestamp { font-weight: bold; color: #495057; }
.log-level { font-weight: bold; padding: 2px 6px; border-radius: 3px; }
.log-level.info { background-color: #17a2b8; color: white; }
.log-level.warning { background-color: #ffc107; color: black; }
.log-level.error { background-color: #dc3545; color: white; }
.log-level.debug { background-color: #6c757d; color: white; }
.log-event { font-weight: bold; color: #343a40; }
.log-message { margin-top: 3px; }
.log-context { 
    font-size: 10px; 
    color: #6c757d; 
    margin-top: 3px; 
    max-height: 100px; 
    overflow-y: auto; 
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    M.Tabs.init(document.querySelectorAll('.tabs'));
    M.FormSelect.init(document.querySelectorAll('select'));
    
    // Load initial data
    loadEventLogs();
    loadErrorLogs();
    updateLoggingStatus();
    
    function toggleEventLogging() {
        const button = document.getElementById('toggle-event-logging');
        const statusSpan = document.getElementById('logging-status');
        
        // Disable button during request
        button.disabled = true;
        statusSpan.textContent = 'Toggling...';
        
        fetch('/api.php?app=admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=toggle_event_logging'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Toggle response:', data);
            updateLoggingStatus();
            loadEventLogs(false); // Refresh the log view
        })
        .catch(error => {
            console.error('Error toggling event logging:', error);
            statusSpan.textContent = 'Error';
        })
        .finally(() => {
            button.disabled = false;
        });
    }
    
    function updateLoggingStatus() {
        const button = document.getElementById('toggle-event-logging');
        const statusSpan = document.getElementById('logging-status');
        
        fetch('/api.php?app=admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_event_logging_status'
        })
        .then(response => response.json())
        .then(data => {
            if (data.enabled) {
                button.className = 'btn red';
                statusSpan.textContent = 'Disable Logging';
            } else {
                button.className = 'btn green';
                statusSpan.textContent = 'Enable Logging';
            }
        })
        .catch(error => {
            console.error('Error getting logging status:', error);
            statusSpan.textContent = 'Status Unknown';
        });
    }
    
    // Event listeners
    document.getElementById('refresh-event-logs').addEventListener('click', function() {
        loadEventLogs(false); // Don't clear on refresh
    });
    document.getElementById('clear-and-refresh-event-logs').addEventListener('click', function() {
        loadEventLogs(true); // Add clear event
    });
    document.getElementById('toggle-event-logging').addEventListener('click', toggleEventLogging);
    document.getElementById('refresh-error-logs').addEventListener('click', loadErrorLogs);
    document.getElementById('event-log-lines').addEventListener('change', function() {
        loadEventLogs(false); // Don't clear when changing lines
    });
    document.getElementById('error-log-lines').addEventListener('change', loadErrorLogs);
    
    function loadEventLogs(clearFirst = false) {
        const lines = document.getElementById('event-log-lines').value;
        console.log('Loading live event logs, lines:', lines, 'clearFirst:', clearFirst);
        
        const content = document.getElementById('event-log-content');
        content.innerHTML = '<div class="center-align">Loading live events...</div>';
        
        fetch('/api.php?app=admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_event_logs&lines=${lines}&clear_first=${clearFirst ? 'true' : 'false'}`
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Live event logs data:', data);
            
            if (data.entries && data.entries.length > 0) {
                console.log('Rendering', data.entries.length, 'live entries');
                content.innerHTML = data.entries.map(entry => {
                    const level = (entry.level || 'info').toLowerCase();
                    const context = entry.context && Object.keys(entry.context).length > 0 
                        ? JSON.stringify(entry.context, null, 2) 
                        : '';
                    
                    return `
                        <div class="log-entry ${level}">
                            <div class="log-timestamp">${entry.timestamp || 'Unknown'}</div>
                            <span class="log-level ${level}">${entry.level || 'INFO'}</span>
                            <span class="log-event">${entry.event || 'Unknown'}</span>
                            <div class="log-message">${entry.message || ''}</div>
                            <div class="log-context">User: ${entry.user || 'Unknown'} | IP: ${entry.ip || 'Unknown'}</div>
                            ${context ? `<div class="log-context"><pre>${context}</pre></div>` : ''}
                        </div>
                    `;
                }).join('');
            } else {
                console.log('No entries in response');
                const message = clearFirst ? 
                    'Clear event added. New events will appear here as you use the application.' :
                    'Live event logging active. Events will appear here as you use the application.';
                content.innerHTML = `<div class="center-align grey-text">${message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading live event logs:', error);
            
            let errorMessage = 'Error loading live event logs: ' + error.message;
            content.innerHTML = '<div class="center-align red-text">' + errorMessage + '</div>';
        });
    }
    
    function loadErrorLogs() {
        const lines = document.getElementById('error-log-lines').value;
        
        fetch('/api.php?app=admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_error_logs&lines=${lines}`
        })
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('error-log-content');
            
            if (data.entries && data.entries.length > 0) {
                content.innerHTML = data.entries.map(entry => `
                    <div class="log-entry error">
                        <div class="log-message">${entry.message}</div>
                    </div>
                `).join('');
            } else {
                content.innerHTML = '<div class="center-align grey-text">No error log entries found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading error logs:', error);
        });
    }
});
</script>