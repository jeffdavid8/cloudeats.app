<?php
/**
 * Data Migration Admin Page
 * Migrates JSON data from local storage to cloud storage
 */

// Check admin authentication
require_once __DIR__ . '/../../../includes/AuthManager.php';
// Session already started in index.php

if (!isset($_SESSION['user']) || !AuthManager::userIsAdmin($_SESSION['user'])) {
    AuthManager::requireAdmin();
}

require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';
?>

<div class="row">
    <div class="col s12">
        <h4>Data Migration</h4>
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=migrate" class="breadcrumb">Data Migration</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">JSON Data Migration to Cloud Storage</span>
                <p>This tool will migrate your JSON data files (users, permissions, OAuth config, etc.) from local storage to Google Cloud Storage.</p>
                
                <div id="migration-status" class="card-panel grey lighten-4" style="display: none;">
                    <h6>Migration Progress</h6>
                    <div class="progress">
                        <div class="determinate" id="migration-progress-bar" style="width: 0%"></div>
                    </div>
                    <p>Status: <span id="current-migration-status">Preparing...</span></p>
                    <div id="migration-log" style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;"></div>
                </div>
                
                <div class="row">
                    <div class="col s12 m6">
                        <button id="scan-files-btn" class="btn blue waves-effect waves-light">
                            <i class="material-icons left">search</i>Scan for JSON Files
                        </button>
                    </div>
                    <div class="col s12 m6">
                        <button id="start-migration-btn" class="btn green waves-effect waves-light" disabled>
                            <i class="material-icons left">cloud_upload</i>Start Migration
                        </button>
                    </div>
                </div>
                
                <div id="scan-results" style="display: none;">
                    <h6>Found JSON Files</h6>
                    <table class="striped">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Size</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="files-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let foundFiles = [];

// Scan for JSON files
document.getElementById('scan-files-btn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Scanning...';
    
    fetch('api.php?action=scan_json_files', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            foundFiles = data.files;
            displayScanResults(data.files);
            
            if (data.files.length > 0) {
                document.getElementById('start-migration-btn').disabled = false;
                M.toast({html: `Found ${data.files.length} JSON files ready for migration`, classes: 'green'});
            } else {
                M.toast({html: 'No JSON files found to migrate', classes: 'orange'});
            }
        } else {
            M.toast({html: 'Scan failed: ' + data.error, classes: 'red'});
        }
    })
    .catch(error => {
        M.toast({html: 'Scan error: ' + error.message, classes: 'red'});
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="material-icons left">search</i>Scan for JSON Files';
    });
});

// Start migration
document.getElementById('start-migration-btn').addEventListener('click', function() {
    if (!foundFiles.length) {
        M.toast({html: 'Please scan for files first', classes: 'red'});
        return;
    }
    
    if (!confirm(`Migrate ${foundFiles.length} JSON files to cloud storage?`)) {
        return;
    }
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Migrating...';
    
    document.getElementById('migration-status').style.display = 'block';
    document.getElementById('current-migration-status').textContent = 'Starting migration...';
    
    migrateFiles(foundFiles);
});

function displayScanResults(files) {
    const resultsDiv = document.getElementById('scan-results');
    const tbody = document.getElementById('files-table-body');
    
    tbody.innerHTML = '';
    
    files.forEach(file => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${file.filename}</td>
            <td>${formatFileSize(file.size)}</td>
            <td>${file.path}</td>
            <td><span class="chip blue white-text">Ready</span></td>
        `;
        tbody.appendChild(row);
    });
    
    resultsDiv.style.display = 'block';
}

function migrateFiles(files) {
    const log = document.getElementById('migration-log');
    const progress = document.getElementById('migration-progress-bar');
    const status = document.getElementById('current-migration-status');
    
    let completed = 0;
    let errors = 0;
    
    function migrateNext(index) {
        if (index >= files.length) {
            // Migration complete
            status.textContent = `Complete! Migrated ${completed} files, ${errors} errors`;
            progress.style.width = '100%';
            
            if (errors === 0) {
                M.toast({html: 'All files migrated successfully!', classes: 'green'});
            } else {
                M.toast({html: `Migration completed with ${errors} errors`, classes: 'orange'});
            }
            
            document.getElementById('start-migration-btn').disabled = false;
            document.getElementById('start-migration-btn').innerHTML = '<i class="material-icons left">cloud_upload</i>Start Migration';
            return;
        }
        
        const file = files[index];
        status.textContent = `Migrating ${file.filename}...`;
        log.innerHTML += `Migrating ${file.filename}...\n`;
        log.scrollTop = log.scrollHeight;
        
        fetch('api.php?action=migrate_json_file', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({file: file})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                log.innerHTML += `✓ ${file.filename} migrated successfully\n`;
                completed++;
            } else {
                log.innerHTML += `✗ ${file.filename} failed: ${data.error}\n`;
                errors++;
            }
            
            const progressPercent = ((index + 1) / files.length) * 100;
            progress.style.width = progressPercent + '%';
            
            log.scrollTop = log.scrollHeight;
            
            // Continue with next file
            setTimeout(() => migrateNext(index + 1), 500);
        })
        .catch(error => {
            log.innerHTML += `✗ ${file.filename} error: ${error.message}\n`;
            errors++;
            log.scrollTop = log.scrollHeight;
            
            setTimeout(() => migrateNext(index + 1), 500);
        });
    }
    
    migrateNext(0);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>