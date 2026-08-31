/**
 * PHPUnit Tests Component
 * Handles test management interface including running tests, viewing results,
 * test statistics, and individual test file management
 */
mb.registerComponent('phpunit-tests', function($element, componentData) {
    console.log('Initializing phpunit-tests component', $element);
    
    // Test management state
    let testResults = {};
    let runningTests = new Set();
    
    // DOM elements
    const globalOutput = document.getElementById('test-output-global');
    const passedCount = document.getElementById('passed-count');
    const failedCount = document.getElementById('failed-count');
    const runningCount = document.getElementById('running-count');
    
    // Initialize Materialize components
    if (typeof M !== 'undefined') {
        M.AutoInit();
    }
    
    // Setup all event handlers
    setupMainControls();
    setupIndividualTestButtons();
    updateStats();
    
    function setupMainControls() {
        // Event listeners for main controls
        const runTestsBtn = document.getElementById('run-tests');
        const runAllTestsBtn = document.getElementById('run-all-tests');
        const runUnitTestsBtn = document.getElementById('run-unit-tests');
        const runIntegrationTestsBtn = document.getElementById('run-integration-tests');
        
        if (runTestsBtn) {
            runTestsBtn.addEventListener('click', () => {
                const suite = document.getElementById('test-suite').value;
                const testFilter = document.getElementById('test-filter').value;
                const coverage = document.getElementById('coverage-enabled').checked;
                runPHPUnit(suite, testFilter, coverage);
            });
        }
        
        if (runAllTestsBtn) {
            runAllTestsBtn.addEventListener('click', () => {
                runPHPUnit('all', '', false);
            });
        }
        
        if (runUnitTestsBtn) {
            runUnitTestsBtn.addEventListener('click', () => {
                runPHPUnit('Unit', '', false);
            });
        }
        
        if (runIntegrationTestsBtn) {
            runIntegrationTestsBtn.addEventListener('click', () => {
                runPHPUnit('Integration', '', false);
            });
        }
    }
    
    function setupIndividualTestButtons() {
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
    }
    
    // Main PHPUnit runner
    async function runPHPUnit(suite = 'all', testFilter = '', coverage = false) {
        try {
            globalOutput.textContent = 'Running PHPUnit tests...';
            globalOutput.className = 'test-output-global';
            
            const formData = new FormData();
            formData.append('action', 'phpunit_run_tests');
            formData.append('suite', suite);
            formData.append('test_filter', testFilter);
            formData.append('coverage', coverage);
            
            const response = await fetch('/?api=admin', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            testResults[`phpunit-${suite}`] = result;
            
            // Update global output
            globalOutput.textContent = result.output;
            
            if (result.success) {
                globalOutput.className = 'test-output-global success';
                if (typeof M !== 'undefined') {
                    M.toast({html: `PHPUnit tests completed successfully!`, classes: 'green'});
                }
            } else {
                globalOutput.className = 'test-output-global error';
                if (typeof M !== 'undefined') {
                    M.toast({html: `PHPUnit tests failed. Check output for details.`, classes: 'red'});
                }
            }
            
            updateStats();
        } catch (error) {
            globalOutput.textContent = `Error running tests: ${error.message}`;
            globalOutput.className = 'test-output-global error';
            if (typeof M !== 'undefined') {
                M.toast({html: `Error: ${error.message}`, classes: 'red'});
            }
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
            formData.append('action', 'phpunit_run_single_test');
            formData.append('test_file', testFile);
            
            const response = await fetch('/?api=admin', {
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
            const response = await fetch(`/?api=admin&action=phpunit_get_test_content&test_file=${encodeURIComponent(testFile)}`);
            const result = await response.json();
            
            if (result.error) {
                if (typeof M !== 'undefined') {
                    M.toast({html: `Error: ${result.error}`, classes: 'red'});
                }
                return;
            }
            
            document.getElementById('modal-test-filename').textContent = result.file;
            document.getElementById('metadata-content').textContent = `${result.size} bytes | Modified: ${result.modified}`;
            document.getElementById('test-code-content').textContent = result.content;
            
            if (typeof M !== 'undefined') {
                M.Modal.getInstance(document.getElementById('test-code-modal')).open();
            }
        } catch (error) {
            if (typeof M !== 'undefined') {
                M.toast({html: `Error loading test code: ${error.message}`, classes: 'red'});
            }
        }
    }
    
    function updateStats() {
        let passed = 0, failed = 0, running = runningTests.size;
        
        Object.values(testResults).forEach(result => {
            if (result && result.success !== undefined) {
                if (result.success) passed++; else failed++;
            }
        });
        
        if (passedCount) passedCount.textContent = `${passed} Passed`;
        if (failedCount) failedCount.textContent = `${failed} Failed`;
        if (runningCount) runningCount.textContent = `${running} Running`;
    }
}, ['jQuery']);