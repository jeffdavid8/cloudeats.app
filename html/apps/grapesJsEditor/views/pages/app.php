<!-- Save Block Button (in GrapesJS panel) -->
<button id="save-block-btn" style="position:absolute;top:1rem;left:1rem;z-index:10;background:#444;color:#fff;border:none;padding:0.5em 1em;border-radius:4px;cursor:pointer;">Save Block</button>
<!-- Blocks Library Modal -->
<div id="blocks-library-modal" style="display:none;position:fixed;z-index:10000;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
  <div style="background:#222;color:#fff;max-width:700px;width:90vw;max-height:90vh;overflow:auto;margin:5vh auto;padding:2rem;border-radius:8px;position:relative;">
    <button id="blocks-library-close" style="position:absolute;top:1rem;right:1rem;font-size:1.5rem;background:none;border:none;color:#fff;cursor:pointer;">&times;</button>
    <h2>Reusable Blocks Library</h2>
    <div id="blocks-library-tree"></div>
    <div style="margin-top:1.5rem;">
      <button id="blocks-library-import" class="tryit-toolbar-btn">Import Block File</button>
      <button id="blocks-library-newfolder" class="tryit-toolbar-btn">New Folder</button>
      <button id="blocks-library-refresh" class="tryit-toolbar-btn">Refresh</button>
    </div>
    <div id="blocks-library-status" style="margin-top:1rem;color:#aaa;"></div>
  </div>
</div>
<?php
// tryitEditor main app view
?>
<div class="tryit-editor-container">
  <div class="tryit-main-layout">
    <div class="tryit-full">
      <div id="tryit-grapejs">
        <div id="gjs-blocks"></div>
        <div id="grapesjs-editor"></div>
        <div class="tryit-panel tryit-panel-right" id="tryit-panel-right">
          <div id="gjs-style-manager"></div>
          <!-- Right panel content (settings, docs, output, etc.) -->
        </div>
      </div>
      <div id="tryit-source">
        <div class="tryit-editor-tabs">
          <button class="tryit-tab-btn" data-editor="html" id="tab-html">HTML</button>
          <button class="tryit-tab-btn" data-editor="css" id="tab-css">CSS</button>
          <button class="tryit-tab-btn" data-editor="js" id="tab-js">JS</button>
          <button class="tryit-tab-btn tryit-tab-restore" id="tab-restore" style="display:none;">&#9632; Restore</button>
        </div>
        <div class="tryit-editors">
          <div id="editor-html" class="tryit-editor-pane"></div>
          <div class="tryit-resizer" id="resizer-html-css"></div>
          <div id="editor-css" class="tryit-editor-pane"></div>
          <div class="tryit-resizer" id="resizer-css-js"></div>
          <div id="editor-js" class="tryit-editor-pane"></div>
        </div>
        <div class="tryit-preview">
          <iframe id="tryit-preview-iframe" sandbox="allow-scripts allow-same-origin" style="width:100%;height:200px;border:1px solid #ccc;"></iframe>
        </div>
      </div>
    </div>
  </div>