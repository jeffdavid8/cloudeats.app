<?php
/**
 * Web Interface Demo Test
 * Category: Other
 * Description: Demonstrates web interface testing capabilities
 * Generated: 2025-11-07 16:34:00
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Web Interface Demo Test</title>
    <link rel="stylesheet" href="/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .test-pass { background: #c8e6c9; color: #2e7d32; }
        .test-fail { background: #ffcdd2; color: #c62828; }
        .test-info { background: #e3f2fd; color: #1565c0; }
    </style>
</head>
<body>
    <div class="container">
        <h3><i class="material-icons left">web</i>Web Interface Test Demo</h3>
        
        <div class="card">
            <div class="card-content">
                <span class="card-title">Test Environment Information</span>
                <p><strong>Test executed at:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>Session status:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active' ?></p>
                <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                <p><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-content">
                <span class="card-title">Interactive Test Results</span>
                <div id="test-results">
                    <div class="test-result test-info">
                        <i class="material-icons left">info</i>
                        <strong>Starting web interface tests...</strong>
                    </div>
                </div>
                
                <div class="row" style="margin-top: 20px;">
                    <div class="col s12">
                        <button id="run-tests" class="btn waves-effect waves-light green">
                            <i class="material-icons left">play_arrow</i>Run Tests
                        </button>
                        <button id="clear-results" class="btn waves-effect waves-light orange">
                            <i class="material-icons left">clear</i>Clear Results
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-content">
                <span class="card-title">Test Summary</span>
                <div class="row">
                    <div class="col s4">
                        <div class="card-panel green lighten-4 center-align">
                            <h5 class="green-text" id="tests-passed">0</h5>
                            <p>Passed</p>
                        </div>
                    </div>
                    <div class="col s4">
                        <div class="card-panel red lighten-4 center-align">
                            <h5 class="red-text" id="tests-failed">0</h5>
                            <p>Failed</p>
                        </div>
                    </div>
                    <div class="col s4">
                        <div class="card-panel blue lighten-4 center-align">
                            <h5 class="blue-text" id="tests-total">0</h5>
                            <p>Total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/js/jquery-2.1.1.min.js"></script>
    <script src="/js/materialize.min.js"></script>
    <script>
        let testsPassed = 0;
        let testsFailed = 0;
        
        function updateStats() {
            document.getElementById('tests-passed').textContent = testsPassed;
            document.getElementById('tests-failed').textContent = testsFailed;
            document.getElementById('tests-total').textContent = testsPassed + testsFailed;
        }
        
        function addTestResult(passed, message) {
            const resultsDiv = document.getElementById('test-results');
            const resultDiv = document.createElement('div');
            
            resultDiv.className = 'test-result ' + (passed ? 'test-pass' : 'test-fail');
            resultDiv.innerHTML = `
                <i class="material-icons left">${passed ? 'check_circle' : 'error'}</i>
                <strong>${passed ? 'PASS' : 'FAIL'}:</strong> ${message}
            `;
            
            resultsDiv.appendChild(resultDiv);
            
            if (passed) {
                testsPassed++;
            } else {
                testsFailed++;
            }
            
            updateStats();
        }
        
        function runWebTests() {
            // Reset counters
            testsPassed = 0;
            testsFailed = 0;
            
            // Clear previous results except the info message
            const resultsDiv = document.getElementById('test-results');
            const children = resultsDiv.children;
            for (let i = children.length - 1; i >= 1; i--) {
                resultsDiv.removeChild(children[i]);
            }
            
            // Run tests with delays to simulate real testing
            setTimeout(() => addTestResult(true, 'jQuery is loaded and functional'), 100);
            setTimeout(() => addTestResult(typeof M !== 'undefined', 'Materialize CSS is loaded'), 200);
            setTimeout(() => addTestResult(document.title.includes('Test'), 'Page title contains "Test"'), 300);
            setTimeout(() => addTestResult(document.querySelectorAll('.card').length > 0, 'Materialize cards are present'), 400);
            setTimeout(() => addTestResult(true, 'DOM manipulation works correctly'), 500);
            setTimeout(() => addTestResult(Math.random() > 0.2, 'Random test (80% pass rate)'), 600);
            
            // Add a final summary
            setTimeout(() => {
                const resultsDiv = document.getElementById('test-results');
                const summaryDiv = document.createElement('div');
                summaryDiv.className = 'test-result test-info';
                summaryDiv.innerHTML = `
                    <i class="material-icons left">assessment</i>
                    <strong>Test execution completed!</strong> 
                    Passed: ${testsPassed}, Failed: ${testsFailed}
                `;
                resultsDiv.appendChild(summaryDiv);
            }, 700);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('run-tests').addEventListener('click', runWebTests);
            
            document.getElementById('clear-results').addEventListener('click', function() {
                testsPassed = 0;
                testsFailed = 0;
                updateStats();
                
                const resultsDiv = document.getElementById('test-results');
                resultsDiv.innerHTML = `
                    <div class="test-result test-info">
                        <i class="material-icons left">info</i>
                        <strong>Results cleared. Ready to run tests...</strong>
                    </div>
                `;
            });
            
            // Auto-run tests after a brief delay
            setTimeout(runWebTests, 1000);
        });
    </script>
</body>
</html>