<?php
// TryItEditor main app page view
$iframeContent = "
<!DOCTYPE html>
<html>
<head>
    <style>body {background-color: #111; color: #eee;}</style>
    <style>{$example['css']}</style>
    <script>{$example['js']}</script>
</head>
<body class=\"nightMode\">
    {$example['html']}
</body>
</html>
";
// Encode the content to ensure it's safe to place inside the srcdoc attribute
$encodedContent = htmlspecialchars($iframeContent, ENT_QUOTES, 'UTF-8');
?>
<div id="tryit-editor">
  <div class="row tryit-toolbar">
    <button id="run-preview" title="Run">Run</button>
    <button id="reset-editors" title="Reset">Reset</button>
    <button id="open-examples-modal" title="Examples">Examples</button>
    <?php if (AuthManager::isAdmin()) { ?>
      <button id="save-session" title="Save">Save</button>
      <button id="export-project" title="Export">Export</button>
      <button id="import-project" title="Import">Import</button>
      <button id="open-blocks-library" title="Blocks Library">Blocks</button>
      <span class="tryit-toolbar-spacer"></span>
      <button id="open-settings" title="Settings">⚙️</button>
    <?php } ?>
  </div>
  <?php if (AuthManager::isAdmin()) { ?>
    <div class="row tryit-editor-tabs">
      <button class="tryit-tab-btn" data-editor="html" id="tab-html">HTML</button>
      <button class="tryit-tab-btn" data-editor="css" id="tab-css">CSS</button>
      <button class="tryit-tab-btn" data-editor="js" id="tab-js">JS</button>
      <button class="tryit-tab-btn tryit-tab-restore" id="tab-restore" style="display:none;">&#9632; Restore</button>
    </div>
  <?php } ?>
  <div class="tryit-editors row">
    <div id="editor-html" class="col s12 m4 tryit-editor-pane"></div>
    <div id="editor-css" class="col s12 m4 tryit-editor-pane"></div>
    <div id="editor-js" class="col s12 m4 tryit-editor-pane"></div>
  </div>
  <div id="tryit-preview-panel" class="row tryit-preview">
    <iframe id="tryit-preview-iframe" sandbox="allow-scripts allow-same-origin" style="background-color: #131313; width:100%;border:1px solid #ccc;"></iframe>
  </div>
</div>
<script>
  let example = <?= json_encode($example) ?>;
</script>