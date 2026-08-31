<?php
// logviewer.php

// Define the path to your log file
$log_file = '../logs/app.log';

// Check if this script is being accessed via AJAX for the log data (e.g., ?action=get_log)
if (isset($_GET['action']) && $_GET['action'] === 'get_log') {
    // --- SERVER-SIDE (PHP) LOGIC FOR Tailing ---
    header('Content-Type: application/json');

    if (!file_exists($log_file) || !is_readable($log_file)) {
        echo json_encode(['error' => 'Log file not found or unreadable.']);
        exit;
    }

    // Get the current file size from the client request, or start at 0
    $last_size = isset($_GET['last_size']) ? (int)$_GET['last_size'] : 0;
    clearstatcache(); // Clear file stat cache
    $current_size = filesize($log_file);

    $new_content = '';

    if ($current_size > $last_size) {
        $handle = fopen($log_file, "r");
        if ($handle) {
            fseek($handle, $last_size);
            $new_content = stream_get_contents($handle);
            fclose($handle);
        }
    } elseif ($current_size < $last_size) {
        // Log file was likely rotated or truncated
        $handle = fopen($log_file, "r");
        if ($handle) {
            $new_content = stream_get_contents($handle);
            fclose($handle);
        }
    }
    
    // Return the new content and the updated file size as JSON
    echo json_encode([
        'content' => $new_content,
        'new_size' => $current_size
    ]);
    
    exit; // Stop execution after sending JSON response

} else {
    // --- CLIENT-SIDE (HTML/JS) LOGIC FOR DISPLAY ---
    // The rest of the script outputs the HTML page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Log Viewer</title>
    <style>
        #log-output {
            width: 100%;
            height: 500px;
            background-color: #f4f4f4;
            padding: 10px;
            font-family: monospace;
            overflow-y: scroll;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Live app.log Tail</h1>
    <pre id="log-output"></pre>

    <script>
        const logOutput = document.getElementById('log-output');
        let lastSize = 0;

        function tailLog() {
            // The request now goes to the same file (logviewer.php) but adds a query parameter
            fetch(`tail-app-log.php?action=get_log&last_size=${lastSize}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        logOutput.textContent += `Error: ${data.error}\n`;
                        return;
                    }

                    if (data.content) {
                        logOutput.textContent += data.content;
                        logOutput.scrollTop = logOutput.scrollHeight;
                    }
                    lastSize = data.new_size;

                    // Poll again after a short delay
                    setTimeout(tailLog, 1000);
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    logOutput.textContent += `Fetch error: ${error}\n`;
                    setTimeout(tailLog, 5000);
                });
        }

        // Start the log tailing process
        tailLog();
    </script>
</body>
</html>
<?php
}
?>
