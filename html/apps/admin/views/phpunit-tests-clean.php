<?php
/**
 * PHPUnit Test Management Interface
 * Clean view file with API endpoints separated
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
    
    if (!is_dir($directory)) {
        return $files;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);
            $files[] = $relativePath;
        }
    }
    
    sort($files);
    return $files;
}

$testFiles = scanTestFiles($testDirectory);
?>

<div class="phpunit-tests">
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">verified_user</i>
                    PHPUnit Test Management
                    <span class="badge green" style="float: right;">
                        <?php echo count($testFiles); ?> test files
                    </span>
                </span>
                
                <?php if (!$composerInstalled): ?>
                <div class="card-panel red lighten-4">
                    <span class="red-text text-darken-2">
                        <i class="material-icons left">error</i>
                        Composer dependencies not installed. Run <code>composer install</code> first.
                    </span>
                </div>
                <?php elseif (!$phpunitAvailable): ?>
                <div class="card-panel orange lighten-4">
                    <span class="orange-text text-darken-2">
                        <i class="material-icons left">warning</i>
                        PHPUnit not found. Install with <code>composer require --dev phpunit/phpunit</code>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Test Suite Controls -->
                <div class="row">
                    <div class="col s12 m6 l3">
                        <div class="input-field">
                            <select id="test-suite">
                                <option value="all">All Test Suites</option>
                                <?php foreach ($testSuites as $suite): ?>
                                <option value="<?php echo htmlspecialchars($suite['name']); ?>">
                                    <?php echo htmlspecialchars($suite['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Test Suite</label>
                        </div>
                    </div>
                    <div class="col s12 m6 l3">
                        <div class="input-field">
                            <input type="text" id="test-filter" placeholder="TestClass::testMethod">
                            <label for="test-filter">Test Filter (Optional)</label>
                        </div>
                    </div>
                    <div class="col s12 m6 l3">
                        <div class="input-field">
                            <p>
                                <label>
                                    <input type="checkbox" id="coverage-enabled" />
                                    <span>Include Coverage Report</span>
                                </label>
                            </p>
                        </div>
                    </div>
                    <div class="col s12 m6 l3">
                        <button id="run-tests" class="btn green waves-effect waves-light" style="margin-top: 20px;">
                            <i class="material-icons left">play_arrow</i>
                            Run Tests
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col s12">
                        <div class="card grey lighten-5">
                            <div class="card-content">
                                <span class="card-title">Quick Actions</span>
                                <div class="row">
                                    <div class="col s12 m4">
                                        <button id="run-all-tests" class="btn blue waves-effect waves-light full-width">
                                            <i class="material-icons left">verified_user</i>
                                            Run All Tests
                                        </button>
                                    </div>
                                    <div class="col s12 m4">
                                        <button id="run-unit-tests" class="btn teal waves-effect waves-light full-width">
                                            <i class="material-icons left">build</i>
                                            Unit Tests Only
                                        </button>
                                    </div>
                                    <div class="col s12 m4">
                                        <button id="run-integration-tests" class="btn purple waves-effect waves-light full-width">
                                            <i class="material-icons left">settings_ethernet</i>
                                            Integration Tests
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Results Global Output -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <span class="card-title" style="margin: 0; flex-grow: 1;">
                        <i class="material-icons left">assessment</i>
                        Test Results
                    </span>
                    <div id="test-stats" class="test-stats">
                        <span class="badge green" id="passed-count">0 Passed</span>
                        <span class="badge red" id="failed-count">0 Failed</span>
                        <span class="badge blue" id="running-count">0 Running</span>
                    </div>
                </div>
                <div id="test-output-global" class="test-output-global"></div>
            </div>
        </div>
    </div>
</div>

<!-- Individual Test Files -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">list</i>
                    Individual Test Files
                </span>
                
                <?php if (empty($testFiles)): ?>
                <div class="card-panel yellow lighten-4">
                    <span class="amber-text text-darken-2">
                        <i class="material-icons left">info</i>
                        No test files found in <code><?php echo htmlspecialchars($testDirectory); ?></code>
                    </span>
                </div>
                <?php else: ?>
                <div class="test-files-container">
                    <?php foreach ($testFiles as $testFile): ?>
                    <div class="test-file-item" data-test-file="<?php echo htmlspecialchars($testFile); ?>">
                        <div class="card grey lighten-5">
                            <div class="card-content">
                                <div style="display: flex; align-items: center;">
                                    <div style="flex-grow: 1;">
                                        <h6 style="margin: 0; font-weight: bold;">
                                            <?php echo htmlspecialchars($testFile); ?>
                                        </h6>
                                        <p style="margin: 5px 0; color: #666;">
                                            <i class="material-icons tiny">folder</i>
                                            <?php echo htmlspecialchars(dirname($testFile)); ?>
                                        </p>
                                    </div>
                                    <div style="margin-left: 15px; text-align: center;">
                                        <span class="badge grey test-status" data-status="pending">Pending</span>
                                    </div>
                                    <div style="margin-left: 15px;">
                                        <button class="btn-small blue waves-effect waves-light run-single-test">
                                            <i class="material-icons">play_arrow</i>
                                        </button>
                                        <button class="btn-small teal waves-effect waves-light view-test-code" style="margin-left: 5px;">
                                            <i class="material-icons">code</i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Test Results Container (initially hidden) -->
                                <div class="test-results" style="display: none; margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                                    <pre class="test-output" style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; font-size: 12px; line-height: 1.4;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Test Code Viewer Modal -->
<div id="test-code-modal" class="modal modal-fixed-footer" style="width: 90%; max-width: 1200px; height: 90%;">
    <div class="modal-content">
        <h4>
            <i class="material-icons left">code</i>
            <span id="modal-test-filename"></span>
        </h4>
        <p id="metadata-content" style="color: #666; font-size: 14px;"></p>
        <pre id="test-code-content" style="background: #f5f5f5; padding: 15px; border-radius: 4px; height: 70%; overflow-y: auto; font-size: 13px; line-height: 1.5;"></pre>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
    </div>
</div>

<style>
.phpunit-tests .card {
    margin-bottom: 20px;
}

.test-output-global {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 15px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.4;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
    min-height: 100px;
    color: #495057;
}

.test-output-global.success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.test-output-global.error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.test-output {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 12px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    white-space: pre-wrap;
    max-height: 250px;
    overflow-y: auto;
    color: #495057;
}

.test-output.success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.test-output.error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.test-stats {
    display: flex;
    gap: 8px;
}

.test-stats .badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 12px;
}

.test-file-item {
    margin-bottom: 15px;
}

.test-status {
    font-size: 11px !important;
    padding: 4px 10px !important;
    border-radius: 12px !important;
    min-width: 70px;
    text-align: center;
}

.test-status[data-status="pending"] {
    background-color: #9e9e9e !important;
}

.test-status[data-status="running"] {
    background-color: #2196f3 !important;
    animation: pulse 1.5s infinite;
}

.test-status[data-status="passed"] {
    background-color: #4caf50 !important;
}

.test-status[data-status="failed"] {
    background-color: #f44336 !important;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.full-width {
    width: 100%;
}

/* Loading indicators */
.test-file-item.running {
    opacity: 0.7;
}

.test-file-item.running .card {
    border-left: 4px solid #2196f3;
}

/* Responsive adjustments */
@media only screen and (max-width: 600px) {
    .test-stats {
        flex-direction: column;
        gap: 4px;
    }
    
    .btn-small {
        padding: 0 8px;
    }
    
    .btn-small i {
        font-size: 18px;
    }
}

/* Code highlighting for test viewer */
#test-code-content {
    tab-size: 4;
    -moz-tab-size: 4;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Courier New', monospace;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Materialize components
    M.AutoInit();
    
    // Test management state
    let testResults = {};
    let runningTests = new Set();
    
    // DOM elements
    const globalOutput = document.getElementById('test-output-global');
    const passedCount = document.getElementById('passed-count');
    const failedCount = document.getElementById('failed-count');
    const runningCount = document.getElementById('running-count');
    
    // Event listeners for main controls
    document.getElementById('run-tests').addEventListener('click', () => {
        const suite = document.getElementById('test-suite').value;
        const testFilter = document.getElementById('test-filter').value;
        const coverage = document.getElementById('coverage-enabled').checked;
        runPHPUnit(suite, testFilter, coverage);
    });
    
    document.getElementById('run-all-tests').addEventListener('click', () => {
        runPHPUnit('all', '', false);
    });
    
    document.getElementById('run-unit-tests').addEventListener('click', () => {
        runPHPUnit('Unit', '', false);
    });
    
    document.getElementById('run-integration-tests').addEventListener('click', () => {
        runPHPUnit('Integration', '', false);
    });
    
    // Event listeners for individual test files
    document.querySelectorAll('.run-single-test').forEach(button => {
        button.addEventListener('click', (e) => {
            const testItem = e.target.closest('.test-file-item');
            const testFile = testItem.dataset.testFile;
            runSingleTest(testFile);
        });
    });
    
    document.querySelectorAll('.view-test-code').forEach(button => {
        button.addEventListener('click', (e) => {
            const testItem = e.target.closest('.test-file-item');
            const testFile = testItem.dataset.testFile;
            viewTestCode(testFile);
        });
    });
    
    // Main PHPUnit runner
    async function runPHPUnit(suite = 'all', testFilter = '', coverage = false) {
        try {
            globalOutput.textContent = 'Running PHPUnit tests...';
            globalOutput.className = 'test-output-global';
            
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
                M.toast({html: `PHPUnit tests failed. Check output for details.`, classes: 'red'});
            }
            
            updateStats();
        } catch (error) {
            globalOutput.textContent = `Error running tests: ${error.message}`;
            globalOutput.className = 'test-output-global error';
            M.toast({html: `Error: ${error.message}`, classes: 'red'});
        }
    }
    
    // Individual test runner
    async function runSingleTest(testFile) {
        try {
            const testItem = document.querySelector(`[data-test-file="${testFile}"]`);
            const statusEl = testItem.querySelector('.test-status');
            const resultsEl = testItem.querySelector('.test-results');
            
            // Update status
            statusEl.textContent = 'Running';
            statusEl.className = 'badge blue test-status';
            statusEl.dataset.status = 'running';
            testItem.classList.add('running');
            runningTests.add(testFile);
            updateStats();
            
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
            
            statusEl.dataset.status = result.success ? 'passed' : 'failed';
            resultsEl.querySelector('.test-output').textContent = result.output;
            resultsEl.style.display = 'block';
            testItem.classList.remove('running');
            runningTests.delete(testFile);
            updateStats();
        } catch (error) {
            console.error('Error running test:', error);
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
            M.toast({html: `Error loading test code: ${error.message}`, classes: 'red'});
        }
    }
    
    function updateStats() {
        let passed = 0, failed = 0, running = runningTests.size;
        
        Object.values(testResults).forEach(result => {
            if (result && result.success !== undefined) {
                if (result.success) passed++; else failed++;
            }
        });
        
        passedCount.textContent = `${passed} Passed`;
        failedCount.textContent = `${failed} Failed`;
        runningCount.textContent = `${running} Running`;
    }
    
    // Initialize stats
    updateStats();
});
</script>