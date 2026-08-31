<?php
/**
 * PHPUnit Test Management Interface
 * Comprehensive interface for running and managing PHPUnit tests
 */

// Paths
$projectRoot = dirname(dirname(dirname(dirname(__DIR__))));
$testDirectory = $projectRoot . '/tests';
$vendorPath = $projectRoot . '/vendor';
$phpunitPath = $vendorPath . '/bin/phpunit';

// Check if PHPUnit is available
$phpunitAvailable = file_exists($phpunitPath);
$composerInstalled = file_exists($vendorPath . '/autoload.php');

// Get test suites from phpunit.xml
$phpunitConfig = $projectRoot . '/phpunit.xml';
$testSuites = [];
if (file_exists($phpunitConfig)) {
    $xml = simplexml_load_file($phpunitConfig);
    foreach ($xml->testsuites->testsuite as $suite) {
        $testSuites[] = [
            'name' => (string)$suite['name'],
            'directory' => (string)$suite->directory
        ];
    }
}

// Scan for test files
function scanTestFiles($directory) {
    $files = [];
    if (!is_dir($directory)) return $files;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getBasename(), 'Test') !== false) {
            $relativePath = str_replace($directory . '/', '', $file->getPathname());
            $files[] = [
                'name' => $file->getBasename(),
                'path' => $file->getPathname(),
                'relative_path' => $relativePath,
                'size' => $file->getSize(),
                'modified' => $file->getMTime()
            ];
        }
    }
    return $files;
}

$testFiles = scanTestFiles($testDirectory);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start output buffering and suppress errors for clean JSON
    ob_start();
    error_reporting(0);
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'run_phpunit':
            $suite = $_POST['suite'] ?? 'all';
            $testFilter = $_POST['test_filter'] ?? '';
            $coverage = $_POST['coverage'] ?? false;
            
            $command = escapeshellcmd($phpunitPath);
            
            // Add test suite filter
            if ($suite !== 'all' && !empty($suite)) {
                $command .= ' --testsuite ' . escapeshellarg($suite);
            }
            
            // Add test filter
            if (!empty($testFilter)) {
                $command .= ' --filter ' . escapeshellarg($testFilter);
            }
            
            // Add coverage
            if ($coverage) {
                $command .= ' --coverage-text';
            }
            
            // Add configuration
            $command .= ' --configuration ' . escapeshellarg($phpunitConfig);
            
            // Change to project directory
            $originalDir = getcwd();
            chdir($projectRoot);
            
            $startTime = microtime(true);
            ob_start();
            
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $executionTime = microtime(true) - $startTime;
            
            chdir($originalDir);
            
            // Clean output buffer and send JSON response
            ob_clean();
            echo json_encode([
                'success' => $returnCode === 0,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
                'execution_time' => round($executionTime, 3),
                'command' => $command,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
            
        case 'run_single_test':
            $testFile = $_POST['test_file'] ?? '';
            
            if (empty($testFile)) {
                ob_clean();
                echo json_encode(['error' => 'No test file specified']);
                exit;
            }
            
            // Validate test file exists
            $fullPath = $testDirectory . '/' . $testFile;
            if (!file_exists($fullPath)) {
                ob_clean();
                echo json_encode(['error' => 'Test file not found']);
                exit;
            }
            
            $command = escapeshellcmd($phpunitPath) . ' ' . escapeshellarg($fullPath);
            
            $originalDir = getcwd();
            chdir($projectRoot);
            
            $startTime = microtime(true);
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $executionTime = microtime(true) - $startTime;
            chdir($originalDir);
            
            ob_clean();
            echo json_encode([
                'success' => $returnCode === 0,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
                'execution_time' => round($executionTime, 3),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
            
        case 'get_test_content':
            $testFile = $_GET['test_file'] ?? $_POST['test_file'] ?? '';
            
            if (empty($testFile)) {
                ob_clean();
                echo json_encode(['error' => 'No test file specified']);
                exit;
            }
            
            $fullPath = $testDirectory . '/' . $testFile;
            if (!file_exists($fullPath)) {
                ob_clean();
                echo json_encode(['error' => 'Test file not found']);
                exit;
            }
            
            ob_clean();
            echo json_encode([
                'content' => file_get_contents($fullPath),
                'file' => $testFile,
                'size' => filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ]);
            exit;
    }
}

// Handle GET requests for test content
if (isset($_GET['action']) && $_GET['action'] === 'get_test_content') {
    ob_start();
    error_reporting(0);
    header('Content-Type: application/json');
    $testFile = $_GET['test_file'] ?? '';
    
    if (empty($testFile)) {
        ob_clean();
        echo json_encode(['error' => 'No test file specified']);
        exit;
    }
    
    $fullPath = $testDirectory . '/' . $testFile;
    if (!file_exists($fullPath)) {
        ob_clean();
        echo json_encode(['error' => 'Test file not found']);
        exit;
    }
    
    ob_clean();
    echo json_encode([
        'content' => file_get_contents($fullPath),
        'file' => $testFile,
        'size' => filesize($fullPath),
        'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
    ]);
    exit;
}
?>

<div class="phpunit-tests">
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">verified_user</i>
                    PHPUnit Test Suite Management
                </span>
                
                <!-- System Status -->
                <div class="row">
                    <div class="col s12">
                        <div class="card-panel <?= $phpunitAvailable ? 'green' : 'red' ?> lighten-4">
                            <h6><i class="material-icons left"><?= $phpunitAvailable ? 'check_circle' : 'error' ?></i>PHPUnit Status</h6>
                            <?php if ($phpunitAvailable): ?>
                                <p class="green-text">PHPUnit is installed and ready to run</p>
                                <p><strong>Path:</strong> <?= htmlspecialchars($phpunitPath) ?></p>
                            <?php else: ?>
                                <p class="red-text">PHPUnit is not installed. Please run: <code>composer install</code></p>
                            <?php endif; ?>
                            
                            <p><strong>Composer:</strong> <?= $composerInstalled ? '✓ Installed' : '✗ Not found' ?></p>
                            <p><strong>Test Files:</strong> <?= count($testFiles) ?> found</p>
                            <p><strong>Test Suites:</strong> <?= count($testSuites) ?> configured</p>
                        </div>
                    </div>
                </div>

                <!-- Test Statistics -->
                <?php if ($phpunitAvailable): ?>
                <div class="row">
                    <div class="col s6 m3">
                        <div class="card-panel blue lighten-4 center-align">
                            <h4 class="blue-text"><?= count($testSuites) ?></h4>
                            <p>Test Suites</p>
                        </div>
                    </div>
                    <div class="col s6 m3">
                        <div class="card-panel green lighten-4 center-align">
                            <h4 class="green-text" id="tests-passed">0</h4>
                            <p>Passed</p>
                        </div>
                    </div>
                    <div class="col s6 m3">
                        <div class="card-panel red lighten-4 center-align">
                            <h4 class="red-text" id="tests-failed">0</h4>
                            <p>Failed</p>
                        </div>
                    </div>
                    <div class="col s6 m3">
                        <div class="card-panel orange lighten-4 center-align">
                            <h4 class="orange-text" id="tests-running">0</h4>
                            <p>Running</p>
                        </div>
                    </div>
                </div>

                <!-- Test Controls -->
                <div class="row">
                    <div class="col s12 m8">
                        <div class="input-field">
                            <select id="test-suite-selector">
                                <option value="all">All Test Suites</option>
                                <?php foreach ($testSuites as $suite): ?>
                                    <option value="<?= htmlspecialchars($suite['name']) ?>"><?= htmlspecialchars($suite['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label>Test Suite</label>
                        </div>
                    </div>
                    <div class="col s12 m4">
                        <div class="input-field">
                            <input type="text" id="test-filter" placeholder="TestMethodName">
                            <label for="test-filter">Test Filter (Optional)</label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col s12">
                        <p>
                            <label>
                                <input type="checkbox" id="include-coverage" />
                                <span>Include Code Coverage</span>
                            </label>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col s12">
                        <a href="#!" class="btn waves-effect waves-light green" id="run-phpunit-tests">
                            <i class="material-icons left">play_arrow</i>Run Tests
                        </a>
                        <a href="#!" class="btn waves-effect waves-light blue" id="run-all-suites">
                            <i class="material-icons left">playlist_play</i>Run All Suites
                        </a>
                        <a href="#!" class="btn waves-effect waves-light orange" id="stop-tests">
                            <i class="material-icons left">stop</i>Stop Tests
                        </a>
                        <a href="#!" class="btn waves-effect waves-light purple" id="clear-results">
                            <i class="material-icons left">clear</i>Clear Results
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Test Suites -->
<?php if ($phpunitAvailable): ?>
<div class="row">
    <?php foreach ($testSuites as $suite): ?>
    <div class="col s12 l6">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <?= htmlspecialchars($suite['name']) ?> Suite
                    <div class="right">
                        <a href="#!" class="btn-small waves-effect waves-light green run-suite" data-suite="<?= htmlspecialchars($suite['name']) ?>">
                            <i class="material-icons">play_arrow</i>
                        </a>
                    </div>
                </span>
                
                <p class="grey-text">Directory: <?= htmlspecialchars($suite['directory']) ?></p>
                
                <!-- Suite-specific test files -->
                <?php
                $suiteDir = $testDirectory . '/' . $suite['directory'];
                $suiteFiles = scanTestFiles($suiteDir);
                ?>
                
                <div class="collection">
                    <?php foreach ($suiteFiles as $testFile): ?>
                    <div class="collection-item">
                        <div class="row valign-wrapper">
                            <div class="col s8">
                                <span class="title"><?= htmlspecialchars($testFile['name']) ?></span>
                                <div class="test-status" data-test="<?= htmlspecialchars($testFile['relative_path']) ?>">
                                    <span class="badge grey lighten-3">Ready</span>
                                </div>
                            </div>
                            <div class="col s4 right-align">
                                <a href="#!" class="btn-small waves-effect waves-light green run-single-test" data-test="<?= htmlspecialchars($testFile['relative_path']) ?>">
                                    <i class="material-icons">play_arrow</i>
                                </a>
                                <a href="#!" class="btn-small waves-effect waves-light blue view-test-code" data-test="<?= htmlspecialchars($testFile['relative_path']) ?>">
                                    <i class="material-icons">code</i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Test Results Container -->
                        <div class="test-results" data-test="<?= htmlspecialchars($testFile['relative_path']) ?>" style="display: none;">
                            <div class="card-panel grey lighten-5">
                                <div class="test-output"></div>
                                <div class="test-metadata">
                                    <small class="grey-text">
                                        <span class="execution-time"></span> | 
                                        <span class="timestamp"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Global Test Results -->
<div class="row">
    <div class="col s12">
        <div class="card" id="global-results-card" style="display: none;">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">assessment</i>Test Results
                    <div class="right">
                        <a href="#!" class="btn-small waves-effect waves-light blue" id="export-results">
                            <i class="material-icons">file_download</i>Export
                        </a>
                    </div>
                </span>
                <div id="global-test-output" class="test-output-global"></div>
            </div>
        </div>
    </div>
</div>

<!-- Test Code Viewer Modal -->
<div id="test-code-modal" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4><i class="material-icons left">code</i>Test Code Viewer</h4>
        <div class="row">
            <div class="col s12">
                <h6 id="modal-test-filename">Loading...</h6>
                <div class="chip" id="modal-test-metadata">
                    <i class="material-icons">info</i>
                    <span id="metadata-content">Loading...</span>
                </div>
            </div>
        </div>
        <pre class="code-viewer"><code id="test-code-content">Loading test content...</code></pre>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        <a href="#!" class="waves-effect waves-light btn blue" id="copy-test-code">
            <i class="material-icons left">content_copy</i>Copy Code
        </a>
    </div>
</div>

<!-- CSS for PHPUnit interface -->
<style>
.test-status .badge {
    font-size: 0.8rem;
    margin-left: 10px;
}

.test-results {
    margin-top: 10px;
}

.test-output {
    max-height: 400px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    white-space: pre-wrap;
    background: #f5f5f5;
    padding: 15px;
    border-left: 3px solid #ddd;
    margin: 10px 0;
}

.test-output-global {
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    white-space: pre-wrap;
    background: #f5f5f5;
    padding: 20px;
    border-left: 5px solid #2196f3;
    margin: 15px 0;
}

.test-output.success, .test-output-global.success {
    border-left-color: #4caf50;
    background: #e8f5e8;
}

.test-output.error, .test-output-global.error {
    border-left-color: #f44336;
    background: #fdf2f2;
}

.code-viewer {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 4px;
    max-height: 500px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.4;
}

.test-metadata {
    margin-top: 10px;
}

.collection-item .row {
    margin-bottom: 0;
}

.running .badge {
    background-color: #ff9800 !important;
    color: white !important;
}

.success .badge {
    background-color: #4caf50 !important;
    color: white !important;
}

.error .badge {
    background-color: #f44336 !important;
    color: white !important;
}

.phpunit-tests h6 {
    margin: 0;
}

.card-panel p {
    margin: 5px 0;
}

.card-panel code {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 3px;
}
</style>

<!-- JavaScript for PHPUnit management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    M.Modal.init(document.querySelectorAll('.modal'));
    M.FormSelect.init(document.querySelectorAll('select'));
    
    let runningTests = new Set();
    let testResults = {};
    
    // Statistics tracking
    function updateStats() {
        const passed = Object.values(testResults).filter(r => r.success).length;
        const failed = Object.values(testResults).filter(r => r.success === false).length;
        const running = runningTests.size;
        
        document.getElementById('tests-passed').textContent = passed;
        document.getElementById('tests-failed').textContent = failed;
        document.getElementById('tests-running').textContent = running;
    }
    
    // Run PHPUnit tests
    document.getElementById('run-phpunit-tests')?.addEventListener('click', async function() {
        const suite = document.getElementById('test-suite-selector').value;
        const testFilter = document.getElementById('test-filter').value.trim();
        const coverage = document.getElementById('include-coverage').checked;
        
        await runPHPUnit(suite, testFilter, coverage);
    });
    
    // Run all suites
    document.getElementById('run-all-suites')?.addEventListener('click', async function() {
        const suites = ['Unit', 'Integration', 'API'];
        
        this.classList.add('disabled');
        this.innerHTML = '<i class="material-icons left">hourglass_empty</i>Running All...';
        
        for (const suite of suites) {
            await runPHPUnit(suite, '', false);
        }
        
        this.classList.remove('disabled');
        this.innerHTML = '<i class="material-icons left">playlist_play</i>Run All Suites';
    });
    
    // Run individual suite
    document.querySelectorAll('.run-suite').forEach(btn => {
        btn.addEventListener('click', async function() {
            const suite = this.getAttribute('data-suite');
            await runPHPUnit(suite, '', false);
        });
    });
    
    // Run single test
    document.querySelectorAll('.run-single-test').forEach(btn => {
        btn.addEventListener('click', async function() {
            const testFile = this.getAttribute('data-test');
            await runSingleTest(testFile);
        });
    });
    
    // View test code
    document.querySelectorAll('.view-test-code').forEach(btn => {
        btn.addEventListener('click', async function() {
            const testFile = this.getAttribute('data-test');
            await viewTestCode(testFile);
        });
    });
    
    // Clear results
    document.getElementById('clear-results')?.addEventListener('click', function() {
        document.querySelectorAll('.test-results').forEach(el => {
            el.style.display = 'none';
        });
        document.querySelectorAll('.test-status .badge').forEach(badge => {
            badge.textContent = 'Ready';
            badge.className = 'badge grey lighten-3';
        });
        document.getElementById('global-results-card').style.display = 'none';
        testResults = {};
        updateStats();
    });
    
    // Copy test code
    document.getElementById('copy-test-code')?.addEventListener('click', function() {
        const code = document.getElementById('test-code-content').textContent;
        navigator.clipboard.writeText(code).then(() => {
            M.toast({html: 'Code copied to clipboard!'});
        });
    });
    
    // Export results
    document.getElementById('export-results')?.addEventListener('click', function() {
        const results = {
            timestamp: new Date().toISOString(),
            summary: {
                total: Object.keys(testResults).length,
                passed: Object.values(testResults).filter(r => r.success).length,
                failed: Object.values(testResults).filter(r => r.success === false).length
            },
            results: testResults
        };
        
        const blob = new Blob([JSON.stringify(results, null, 2)], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `test-results-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
    });
    
    async function runPHPUnit(suite = 'all', testFilter = '', coverage = false) {
        const globalOutput = document.getElementById('global-test-output');
        const globalCard = document.getElementById('global-results-card');
        
        globalCard.style.display = 'block';
        globalOutput.textContent = 'Running PHPUnit tests...\n\n';
        globalOutput.className = 'test-output-global running';
        
        try {
            const formData = new FormData();
            formData.append('action', 'run_phpunit');
            formData.append('suite', suite);
            formData.append('test_filter', testFilter);
            formData.append('coverage', coverage);
            
            const response = await fetch('/?app=admin&api=phpunit', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            testResults[`phpunit-${suite}`] = result;
            
            // Update global output
            globalOutput.textContent = result.output;
            
            if (result.success) {
                globalOutput.className = 'test-output-global success';
                M.toast({html: `PHPUnit tests completed successfully!`, classes: 'green'});
            } else {
                globalOutput.className = 'test-output-global error';
                M.toast({html: `PHPUnit tests failed. See results for details.`, classes: 'red'});
            }
            
            updateStats();
            
        } catch (error) {
            globalOutput.textContent = 'Error running PHPUnit tests: ' + error.message;
            globalOutput.className = 'test-output-global error';
            console.error('PHPUnit execution failed:', error);
        }
    }
    
    async function runSingleTest(testFile) {
        if (runningTests.has(testFile)) return;
        
        runningTests.add(testFile);
        const statusEl = document.querySelector(`[data-test="${testFile}"] .test-status .badge`);
        const resultsEl = document.querySelector(`[data-test="${testFile}"].test-results`);
        
        // Update status to running
        statusEl.textContent = 'Running...';
        statusEl.className = 'badge orange running';
        updateStats();
        
        try {
            const formData = new FormData();
            formData.append('action', 'run_single_test');
            formData.append('test_file', testFile);
            
            const response = await fetch('/?app=admin&api=phpunit', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            testResults[testFile] = result;
            
            // Update status and show results
            if (result.success) {
                statusEl.textContent = 'Passed';
                statusEl.className = 'badge green success';
                resultsEl.querySelector('.test-output').className = 'test-output success';
            } else {
                statusEl.textContent = 'Failed';
                statusEl.className = 'badge red error';
                resultsEl.querySelector('.test-output').className = 'test-output error';
            }
            
            // Show output
            resultsEl.querySelector('.test-output').textContent = result.output || result.error || 'No output';
            resultsEl.querySelector('.execution-time').textContent = `${result.execution_time}s`;
            resultsEl.querySelector('.timestamp').textContent = result.timestamp;
            resultsEl.style.display = 'block';
            
        } catch (error) {
            statusEl.textContent = 'Error';
            statusEl.className = 'badge red error';
            console.error('Test execution failed:', error);
        } finally {
            runningTests.delete(testFile);
            updateStats();
        }
    }
    
    async function viewTestCode(testFile) {
        try {
            const response = await fetch(`/?app=admin&api=phpunit&action=get_test_content&test_file=${encodeURIComponent(testFile)}`);
            const result = await response.json();
            
            if (result.error) {
                M.toast({html: `Error: ${result.error}`, classes: 'red'});
                return;
            }
            
            document.getElementById('modal-test-filename').textContent = result.file;
            document.getElementById('metadata-content').textContent = `${result.size} bytes | Modified: ${result.modified}`;
            document.getElementById('test-code-content').textContent = result.content;
            
            M.Modal.getInstance(document.getElementById('test-code-modal')).open();
        } catch (error) {
            M.toast({html: 'Failed to load test code', classes: 'red'});
            console.error('Failed to load test code:', error);
        }
    }
    
    // Initialize stats
    updateStats();
});
</script>
</div> <!-- End phpunit-tests -->