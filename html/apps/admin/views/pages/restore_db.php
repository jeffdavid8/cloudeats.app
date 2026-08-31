<?
if (!defined('MB_RUNNING')) exit;
/**
 * 
 * 
 * @var array $tables
 */
?>


<style>
  .restore-container {
    max-width: 700px;
    margin: 20px auto;
    font-family: sans-serif;
  }

  .drop-zone {
    border: 2px dashed #999;
    border-radius: 6px;
    padding: 30px;
    text-align: center;
    background: #fdfdfd;
    cursor: pointer;
    transition: background 0.2s;
  }

  .drop-zone.dragover {
    background: #eef7ff;
    border-color: #0076df;
  }

  .table-selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px;
    margin: 20px 0;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
  }

  .table-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
  }

  .btn-submit {
    background: #0076df;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    font-size: 15px;
    cursor: pointer;
    font-weight: bold;
    width: 100%;
  }

  .btn-submit:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  .bulk-controls {
    margin-bottom: 10px;
    font-size: 12px;
  }

  .bulk-controls a {
    color: #0076df;
    cursor: pointer;
    text-decoration: underline;
    margin-right: 15px;
  }

  #uploadStatus {
    margin-top: 15px;
    padding: 12px;
    border-radius: 4px;
    display: none;
    font-size: 14px;
    line-height: 1.4;
  }
</style>

<div class="restore-container">
  <form id="restoreDbForm">
    <h3>1. Select Tables to Target for Overwrite</h3>
    <div class="bulk-controls">
      <a onclick="toggleAllCheckboxes(true)">Select All</a>
      <a onclick="toggleAllCheckboxes(false)">Clear All</a>
    </div>

    <div class="table-selection-grid">
      <?php foreach ($tables as $index => $tableName): ?>
        <label class="table-item">
          <input type="checkbox" name="target_tables[]" value="<?php echo htmlspecialchars($tableName); ?>">
          <span><?php echo htmlspecialchars($tableName); ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <h3>2. Upload Source Payload</h3>
    <div class="drop-zone" id="dropZone">
      <p id="dropZoneText">Drag and drop your <strong>default_db.json</strong> file here or click to browse</p>
      <input type="file" id="fileInput" name="db_file" accept=".json" style="display: none;">
    </div>

    <textarea id="uploadStatus" style="width: 100%;"></textarea>

    <button type="submit" class="btn-submit" id="submitBtn" style="margin-top: 20px;" disabled>Execute Structural Restore</button>
  </form>
</div>

<script>
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  const dropZoneText = document.getElementById('dropZoneText');
  const form = document.getElementById('restoreDbForm');
  const submitBtn = document.getElementById('submitBtn');
  const uploadStatus = document.getElementById('uploadStatus');
  let selectedFile = null;

  function toggleAllCheckboxes(status) {
    document.querySelectorAll('input[name="target_tables[]"]').forEach(cb => cb.checked = status);
  }

  // Trigger file selection via wrapper click
  dropZone.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) handleFileSelection(e.target.files[0]);
  });

  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });

  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));

  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
      handleFileSelection(e.dataTransfer.files[0]);
    }
  });

  function handleFileSelection(file) {
    if (!file.name.endsWith('.json')) {
      showStatus('Invalid file. Please attach a structural JSON database payload.', 'error');
      selectedFile = null;
      submitBtn.disabled = true;
      dropZoneText.innerHTML = `Drag and drop your <strong>default_db.json</strong> file here or click to browse`;
      return;
    }
    selectedFile = file;
    submitBtn.disabled = false;
    dropZoneText.innerHTML = `📄 Selected File: <strong>${file.name}</strong> (${(file.size / 1024).toFixed(2)} KB)`;
    showStatus('File verified and staged successfully.', 'success');
  }

  function showStatus(msg, type) {
    uploadStatus.style.display = 'block';
    uploadStatus.style.backgroundColor = type === 'success' ? '#e6f4ea' : '#fce8e6';
    uploadStatus.style.color = type === 'success' ? '#137333' : '#c5221f';
    uploadStatus.style.border = `1px solid ${type === 'success' ? '#137333' : '#c5221f'}`;
    uploadStatus.innerHTML = msg;
  }

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Ensure at least one checkpoint array element is selected
    const checkedTables = document.querySelectorAll('input[name="target_tables[]"]:checked');
    if (checkedTables.length === 0) {
      showStatus('Error: You must check at least one structural table vector to target.', 'error');
      return;
    }

    if (!selectedFile) {
      showStatus('Error: No data snapshot source staged for restore loop.', 'error');
      return;
    }

    //submitBtn.disabled = true;
    showStatus('Executing serial stream injection, do not close or navigate away...', 'success');

    const formData = new FormData();
    formData.append('db_file', selectedFile);

    // Map individual target checked values
    checkedTables.forEach(cb => {
      formData.append('target_tables[]', cb.value);
    });

    // Native Framework AJAX Stream Wrapper
    mb.ajax({
      url: '?api=admin&action=restore_db',
      method: 'POST',
      data: formData,
      contentType: false, // Crucial: Tells framework not to alter content-type headers
      processData: false, // Crucial: Stops framework from trying to convert FormData to a query string
      success: function(result) {
        let notice = JSON.stringify(result, null, 2)
        // Output injection pipeline directly
        uploadStatus.style.backgroundColor = '#f8f9fa';
        uploadStatus.style.color = '#333';
        uploadStatus.style.border = '1px solid #ccc';
        uploadStatus.style.height = '250px';
        uploadStatus.innerHTML = `${notice}`;
      },
      error: function(xhr, status, error) {
        submitBtn.disabled = false;
        let errMsg = xhr.responseText || 'Critical network exception encountered during stream injection execution.';
        showStatus(`Error: ${errMsg}`, 'error');
      }
    });
  });
</script>