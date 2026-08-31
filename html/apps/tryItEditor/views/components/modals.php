<?php
?>
<!-- Example Selector Modal -->
<div class="modal" id="modal-example-selector">
  <div class="modal-content">
    <div class="modal-header">
      <span class="modal-close">&times;</span>
      <h4>Examples</h4>
    </div>
    <div class="modal-body"> 
      <ul class="example-list">
        <?php foreach ($examples as $example): ?>
          <li>
          <button title="<?= htmlspecialchars($example['id']) ?>" 
          data-example='<?= htmlspecialchars(json_encode($example)) ?>'><?= htmlspecialchars($example['title']) ?></button>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="modal-footer">
      <button class="cancel">Cancel</button>
    </div>
  </div>
</div>
</div>

<!-- Save Session Modal -->
<div class="modal" id="modal-save">
  <div class="modal-content">
    <h4>Save Session</h4>
    <div>
      <label for="save-session-name">Session Name:</label>
      <input type="text" id="save-session-name" placeholder="Enter session name">
    </div>
    <button id="save-session-confirm">Save</button>
    <button class="close">Close</button>
  </div>
</div>

<!-- Export Project Modal -->
<div class="modal" id="modal-export">
  <div class="modal-content">
    <h4>Export Project</h4>
    <div>
      <label for="export-format">Format:</label>
      <select id="export-format">
        <option value="json">JSON</option>
        <option value="zip">ZIP</option>
      </select>
    </div>
    <button id="export-project-confirm">Export</button>
    <button class="close">Close</button>
  </div>
</div>

<!-- Import Project Modal -->
<div class="modal" id="modal-import">
  <div class="modal-content">
    <h4>Import Project</h4>
    <input type="file" id="import-project-file" accept=".json,.zip">
    <button id="import-project-confirm">Import</button>
    <button class="close">Close</button>
  </div>
</div>

<!-- Blocks Library Modal -->
<div class="modal" id="modal-blocks">
  <div class="modal-content">
    <h4>Blocks Library</h4>
    <div id="blocks-list"></div>
    <button id="add-block">Add Block</button>
    <button class="close">Close</button>
  </div>
</div>

<!-- Settings Modal -->
<div class="modal" id="modal-settings">
  <div class="modal-content">
    <h4>Settings</h4>
    <div id="settings-content">
      <!-- Settings UI goes here -->
    </div>
    <button class="close">Close</button>
  </div>
</div>

<!-- Confirm Reset Modal -->
<div class="modal" id="modal-confirm-reset">
  <div class="modal-content">
    <div class="modal-header">
      <h4>Confirm Reset</h4>
      <span class="modal-close">&times;</span>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to reset all editors? This will erase your current code.</p>
    </div>
    <div class="modal-footer">
      <button class="confirm-reset-yes">Reset</button>
      <button class="confirm-reset-cancel">Cancel</button>
    </div>
  </div>
</div>