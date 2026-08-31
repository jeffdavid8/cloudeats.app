<?php
/**
 * Test Management Interface
 * Comprehensive interface for viewing, running, managing, and editing tests
 */

$testDirectory = dirname(dirname(dirname(__DIR__))) . '/dev/test/';
$testFiles = [];

// Scan for test files
if (is_dir($testDirectory)) {
    $files = scandir($testDirectory);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== '.' && $file !== '..') {
            $testFiles[] = $file;
        }
    }
}

// Handle AJAX requests for test execution
if (isset($_POST['action']) && $_POST['action'] === 'run_test') {
    header('Content-Type: application/json');
    
    $testFile = $_POST['test_file'] ?? '';
    if (empty($testFile) || !in_array($testFile, $testFiles)) {
        echo json_encode(['error' => 'Invalid test file']);
        exit;
    }
    
    $testPath = $testDirectory . $testFile;
    
    // Capture output
    ob_start();
    $startTime = microtime(true);
    
    try {
        // Change to project root for test execution
        $originalDir = getcwd();
        chdir(dirname(dirname(dirname(__DIR__))));
        
        // Include and execute test
        include $testPath;
        
        chdir($originalDir);
        
        $output = ob_get_contents();
        $executionTime = microtime(true) - $startTime;
        
        ob_end_clean();
        
        echo json_encode([
            'success' => true,
            'output' => $output,
            'execution_time' => round($executionTime, 3),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        chdir($originalDir);
        ob_end_clean();
        
        echo json_encode([
            'error' => $e->getMessage(),
            'execution_time' => round(microtime(true) - $startTime, 3),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    exit;
}

// Handle test file content retrieval
if (isset($_GET['action']) && $_GET['action'] === 'get_test_content') {
    header('Content-Type: application/json');
    
    $testFile = $_GET['test_file'] ?? '';
    if (empty($testFile) || !in_array($testFile, $testFiles)) {
        echo json_encode(['error' => 'Invalid test file']);
        exit;
    }
    
    $testPath = $testDirectory . $testFile;
    $content = file_get_contents($testPath);
    
    echo json_encode([
        'content' => $content,
        'file' => $testFile,
        'size' => filesize($testPath),
        'modified' => date('Y-m-d H:i:s', filemtime($testPath))
    ]);
    exit;
}

// Categorize tests
function categorizeTests($files) {
    $categories = [
        'auth' => ['admin_auth', 'admin_login', 'oauth', 'login', 'facebook'],
        'storage' => ['storage'],
        'api' => ['api', 'web_api'],
        'permissions' => ['permission', 'role'],
        'environment' => ['environment', 'docker'],
        'logging' => ['logs', 'eventlogger'],
        'other' => []
    ];
    
    $categorized = array_fill_keys(array_keys($categories), []);
    
    foreach ($files as $file) {
        $placed = false;
        foreach ($categories as $category => $keywords) {
            if ($category === 'other') continue;
            foreach ($keywords as $keyword) {
                if (stripos($file, $keyword) !== false) {
                    $categorized[$category][] = $file;
                    $placed = true;
                    break 2;
                }
            }
        }
        if (!$placed) {
            $categorized['other'][] = $file;
        }
    }
    
    return $categorized;
}

$categorizedTests = categorizeTests($testFiles);
?>

<div class="test-management">
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">bug_report</i>
                    Test Management Dashboard
                </span>
                
                <!-- Test Statistics -->
                <div class="row">
                    <div class="col s6 m3">
                        <div class="card-panel blue lighten-4 center-align">
                            <h4 class="blue-text"><?= count($testFiles) ?></h4>
                            <p>Total Tests</p>
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
                    <div class="col s12">
                        <a href="#!" class="btn waves-effect waves-light green" id="run-all-tests">
                            <i class="material-icons left">play_arrow</i>Run All Tests
                        </a>
                        <a href="#!" class="btn waves-effect waves-light orange" id="stop-all-tests">
                            <i class="material-icons left">stop</i>Stop All
                        </a>
                        <a href="#!" class="btn waves-effect waves-light blue" id="clear-results">
                            <i class="material-icons left">clear</i>Clear Results
                        </a>
                        <a href="#test-creator-modal" class="btn waves-effect waves-light purple modal-trigger">
                            <i class="material-icons left">add</i>Create Test
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Categories -->
<div class="row">
    <?php foreach ($categorizedTests as $category => $tests): ?>
    <?php if (!empty($tests)): ?>
    <div class="col s12 l6">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <?= ucfirst($category) ?> Tests
                    <span class="badge"><?= count($tests) ?></span>
                </span>
                
                <div class="collection">
                    <?php foreach ($tests as $test): ?>
                    <div class="collection-item">
                        <div class="row valign-wrapper">
                            <div class="col s8">
                                <span class="title"><?= htmlspecialchars($test) ?></span>
                                <div class="test-status" data-test="<?= htmlspecialchars($test) ?>">
                                    <span class="badge grey lighten-3">Ready</span>
                                </div>
                            </div>
                            <div class="col s4 right-align">
                                <a href="#!" class="btn-small waves-effect waves-light green run-single-test" data-test="<?= htmlspecialchars($test) ?>">
                                    <i class="material-icons">play_arrow</i>
                                </a>
                                <a href="#!" class="btn-small waves-effect waves-light blue view-test-code" data-test="<?= htmlspecialchars($test) ?>">
                                    <i class="material-icons">code</i>
                                </a>
                                <a href="#!" class="btn-small waves-effect waves-light orange edit-test" data-test="<?= htmlspecialchars($test) ?>">
                                    <i class="material-icons">edit</i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Test Results Container -->
                        <div class="test-results" data-test="<?= htmlspecialchars($test) ?>" style="display: none;">
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
    <?php endif; ?>
    <?php endforeach; ?>
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

<!-- Test Creator Modal -->
<div id="test-creator-modal" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4><i class="material-icons left">add</i>Create New Test</h4>
        <form id="test-creator-form">
            <div class="row">
                <div class="input-field col s12 m6">
                    <input id="test-name" type="text" class="validate">
                    <label for="test-name">Test Name</label>
                    <span class="helper-text">e.g., test_new_feature</span>
                </div>
                <div class="input-field col s12 m6">
                    <select id="test-category">
                        <option value="" disabled selected>Choose category</option>
                        <option value="auth">Authentication</option>
                        <option value="storage">Storage</option>
                        <option value="api">API</option>
                        <option value="permissions">Permissions</option>
                        <option value="environment">Environment</option>
                        <option value="logging">Logging</option>
                        <option value="other">Other</option>
                    </select>
                    <label>Test Category</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <textarea id="test-description" class="materialize-textarea"></textarea>
                    <label for="test-description">Test Description</label>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <p>
                        <label>
                            <input type="checkbox" id="test-template-basic" checked />
                            <span>Use basic test template</span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" id="test-template-web" />
                            <span>Include web interface template</span>
                        </label>
                    </p>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancel</a>
        <a href="#!" class="waves-effect waves-light btn green" id="create-test-btn">
            <i class="material-icons left">add</i>Create Test
        </a>
    </div>
</div>

<!-- Test Results Modal -->
<div id="test-results-modal" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4><i class="material-icons left">assessment</i>Test Execution Results</h4>
        <div id="bulk-test-results"></div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        <a href="#!" class="waves-effect waves-light btn blue" id="export-results">
            <i class="material-icons left">file_download</i>Export Results
        </a>
    </div>
</div>

<!-- CSS for test interface -->
<style>
.test-status .badge {
    font-size: 0.8rem;
    margin-left: 10px;
}

.test-results {
    margin-top: 10px;
}

.test-output {
    max-height: 300px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    white-space: pre-wrap;
    background: #f5f5f5;
    padding: 10px;
    border-left: 3px solid #ddd;
}

.test-output.success {
    border-left-color: #4caf50;
}

.test-output.error {
    border-left-color: #f44336;
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
</style>

<!-- JavaScript for test management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    M.Modal.init(document.querySelectorAll('.modal'));
    M.FormSelect.init(document.querySelectorAll('select'));
    
    let runningTests = new Set();
    let testResults = {};
    
    // Statistics tracking
    function updateStats() {
        const passed = Object.values(testResults).filter(r => r.success).length;
        const failed = Object.values(testResults).filter(r => r.error).length;
        const running = runningTests.size;
        
        document.getElementById('tests-passed').textContent = passed;
        document.getElementById('tests-failed').textContent = failed;
        document.getElementById('tests-running').textContent = running;
    }
    
    // Run single test
    document.querySelectorAll('.run-single-test').forEach(btn => {
        btn.addEventListener('click', async function() {
            const testFile = this.getAttribute('data-test');
            await runTest(testFile);
        });
    });
    
    // View test code
    document.querySelectorAll('.view-test-code').forEach(btn => {
        btn.addEventListener('click', async function() {
            const testFile = this.getAttribute('data-test');
            await viewTestCode(testFile);
        });
    });
    
    // Run all tests
    document.getElementById('run-all-tests').addEventListener('click', async function() {
        const allTests = document.querySelectorAll('.run-single-test');
        this.classList.add('disabled');
        this.innerHTML = '<i class="material-icons left">hourglass_empty</i>Running...';
        
        for (const btn of allTests) {
            const testFile = btn.getAttribute('data-test');
            await runTest(testFile);
        }
        
        this.classList.remove('disabled');
        this.innerHTML = '<i class="material-icons left">play_arrow</i>Run All Tests';
    });
    
    // Clear results
    document.getElementById('clear-results').addEventListener('click', function() {
        document.querySelectorAll('.test-results').forEach(el => {
            el.style.display = 'none';
        });
        document.querySelectorAll('.test-status .badge').forEach(badge => {
            badge.textContent = 'Ready';
            badge.className = 'badge grey lighten-3';
        });
        testResults = {};
        updateStats();
    });
    
    // Copy test code
    document.getElementById('copy-test-code').addEventListener('click', function() {
        const code = document.getElementById('test-code-content').textContent;
        navigator.clipboard.writeText(code).then(() => {
            M.toast({html: 'Code copied to clipboard!'});
        });
    });
    
    async function runTest(testFile) {
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
            formData.append('action', 'run_test');
            formData.append('test_file', testFile);
            
            const response = await fetch('/?app=admin&p=tests', {
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
            const response = await fetch(`/?app=admin&p=tests&action=get_test_content&test_file=${encodeURIComponent(testFile)}`);
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
    
    // Create new test
    document.getElementById('create-test-btn').addEventListener('click', async function() {
        const testName = document.getElementById('test-name').value.trim();
        const testCategory = document.getElementById('test-category').value;
        const testDescription = document.getElementById('test-description').value.trim();
        const useBasic = document.getElementById('test-template-basic').checked;
        const useWeb = document.getElementById('test-template-web').checked;
        
        if (!testName) {
            M.toast({html: 'Test name is required', classes: 'red'});
            return;
        }
        
        this.classList.add('disabled');
        this.innerHTML = '<i class="material-icons left">hourglass_empty</i>Creating...';
        
        try {
            const formData = new FormData();
            formData.append('api_action', 'create_test');
            formData.append('test_name', testName);
            formData.append('test_category', testCategory);
            formData.append('test_description', testDescription);
            formData.append('use_basic_template', useBasic);
            formData.append('use_web_template', useWeb);
            
            const response = await fetch('/apps/admin/test-api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                M.toast({html: `Test ${result.filename} created successfully!`, classes: 'green'});
                M.Modal.getInstance(document.getElementById('test-creator-modal')).close();
                
                // Reload the page to show the new test
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                M.toast({html: `Error: ${result.error}`, classes: 'red'});
            }
        } catch (error) {
            M.toast({html: 'Failed to create test', classes: 'red'});
            console.error('Failed to create test:', error);
        } finally {
            this.classList.remove('disabled');
            this.innerHTML = '<i class="material-icons left">add</i>Create Test';
        }
    });
    
    // Initialize stats
    updateStats();
});
</script>
</div> <!-- End test-management -->