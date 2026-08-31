// tryItEditor main JS
// Handles GrapesJS, Source View toggle, Monaco, and UI logic

document.addEventListener("DOMContentLoaded", function () {
  // Remove GrapesJS event handlers and logic
  // --- Blocks Library Modal UI ---
  function showBlocksLibraryModal() {
    const modal = document.getElementById('blocks-library-modal');
    if (!modal) return;
    modal.style.display = 'block';
    renderBlocksLibraryTree();
  }
  function hideBlocksLibraryModal() {
    const modal = document.getElementById('blocks-library-modal');
    if (modal) modal.style.display = 'none';
  }
  function renderBlocksLibraryTree() {
    const tree = document.getElementById('blocks-library-tree');
    if (!tree) return;
    // Build a tree from file paths
    const files = getBlocksFiles();
    const root = {};
    files.forEach(f => {
      const parts = f.path.split('/');
      let node = root;
      for (let i = 0; i < parts.length; ++i) {
        const part = parts[i];
        if (!node[part]) node[part] = (i === parts.length - 1) ? {__file: f} : {};
        node = node[part];
      }
    });
    function renderNode(node, path = '') {
      let html = '<ul style="list-style:none;padding-left:1em;">';
      for (const key in node) {
        if (key === '__file') continue;
        const child = node[key];
        const childPath = path ? path + '/' + key : key;
        if (child.__file) {
          html += `<li class='blocks-file-item' data-path='${childPath}' style='position:relative;'><span style='color:#8cf;'>&#128196;</span> ${key}</li>`;
        } else {
          html += `<li><span style='color:#fc8;'>&#128193;</span> <b>${key}</b> <button data-rmfolder='${childPath}'>Delete Folder</button>`;
          html += renderNode(child, childPath);
          html += '</li>';
        }
      }
      html += '</ul>';
      return html;
    }
    tree.innerHTML = renderNode(root);
    // Context menu for file items
    let contextMenu = document.getElementById('blocks-context-menu');
    if (!contextMenu) {
      contextMenu = document.createElement('div');
      contextMenu.id = 'blocks-context-menu';
      contextMenu.style.position = 'absolute';
      contextMenu.style.display = 'none';
      contextMenu.style.zIndex = '10000';
      contextMenu.style.background = '#222';
      contextMenu.style.border = '1px solid #444';
      contextMenu.style.padding = '6px 0';
      contextMenu.style.minWidth = '120px';
      contextMenu.style.boxShadow = '0 2px 8px #0006';
      document.body.appendChild(contextMenu);
    }
    function showContextMenu(x, y, path) {
      contextMenu.innerHTML = `
        <div class='blocks-menu-item' data-action='download' data-path='${path}' style='padding:6px 16px;cursor:pointer;'>Download</div>
        <div class='blocks-menu-item' data-action='insert' data-path='${path}' style='padding:6px 16px;cursor:pointer;'>Insert</div>
        <div class='blocks-menu-item' data-action='delete' data-path='${path}' style='padding:6px 16px;cursor:pointer;color:#f66;'>Delete</div>
      `;
      contextMenu.style.left = x + 'px';
      contextMenu.style.top = y + 'px';
      contextMenu.style.display = 'block';
    }
    function hideContextMenu() {
      contextMenu.style.display = 'none';
    }
    tree.querySelectorAll('.blocks-file-item').forEach(item => {
      item.oncontextmenu = function(e) {
        e.preventDefault();
        showContextMenu(e.pageX, e.pageY, item.getAttribute('data-path'));
      };
    });
    document.addEventListener('click', function(e) {
      if (!contextMenu.contains(e.target)) hideContextMenu();
    });
    contextMenu.onclick = function(e) {
      const action = e.target.getAttribute('data-action');
      const path = e.target.getAttribute('data-path');
      if (!action || !path) return;
      if (action === 'download') {
        const data = getBlocksFile(path);
        if (data) {
          const blob = new Blob([JSON.stringify(data, null, 2)], {type:'application/json'});
          downloadBlob(blob, path.split('/').pop());
        }
      } else if (action === 'insert') {
        const data = getBlocksFile(path);
        if (window.gjsEditor && data) {
          window.gjsEditor.addComponents(data);
          alert('Block inserted into editor!');
        }
      } else if (action === 'delete') {
        if (confirm('Delete ' + path + '?')) {
          deleteBlocksFile(path);
          renderBlocksLibraryTree();
        }
      }
      hideContextMenu();
    };
  }
  // Modal open/close
  document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'menu-reusable') {
      e.preventDefault();
      showBlocksLibraryModal();
    }
    if (e.target && e.target.id === 'blocks-library-close') {
      hideBlocksLibraryModal();
    }
    if (e.target && e.target.id === 'blocks-library-refresh') {
      renderBlocksLibraryTree();
    }
    if (e.target && e.target.id === 'blocks-library-import') {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = '.json,application/json';
      input.onchange = function(ev) {
        if (ev.target.files && ev.target.files[0]) {
          const reader = new FileReader();
          reader.onload = function(evt) {
            try {
              const data = JSON.parse(evt.target.result);
              const name = prompt('Enter file path (e.g. components/cards/CardBlock.json):');
              if (name) {
                addBlocksFile(name, data);
                renderBlocksLibraryTree();
              }
            } catch (err) { alert('Invalid JSON file.'); }
          };
          reader.readAsText(ev.target.files[0]);
        }
      };
      input.click();
    }
    if (e.target && e.target.id === 'blocks-library-newfolder') {
      const name = prompt('Enter new folder path (e.g. components/cards):');
      if (name) {
        ensureBlocksDir(name);
        renderBlocksLibraryTree();
      }
    }
  });
  // --- GrapesJS Blocks Library LocalStorage API ---
  const BLOCKS_KEY = 'tryitEditorBlocksFiles';
  function getBlocksFiles() {
    const raw = localStorage.getItem(BLOCKS_KEY);
    if (!raw) return [];
    try { return JSON.parse(raw); } catch { return []; }
  }
  function saveBlocksFiles(files) {
    localStorage.setItem(BLOCKS_KEY, JSON.stringify(files));
  }
  function listBlocksFiles(path = '') {
    // Returns files/folders at a given path
    const files = getBlocksFiles();
    const norm = path.replace(/\/+/g, '/').replace(/^\//, '').replace(/\/$/, '');
    const out = [];
    files.forEach(f => {
      if (f.path.startsWith(norm)) {
        const rel = f.path.slice(norm.length).replace(/^\//, '');
        if (rel === '') out.push(f);
        else if (!rel.includes('/')) out.push(f);
      }
    });
    return out;
  }
  function addBlocksFile(path, data) {
    const files = getBlocksFiles();
    // Remove if exists
    const idx = files.findIndex(f => f.path === path);
    if (idx !== -1) files.splice(idx, 1);
    files.push({ path, data, created: Date.now() });
    saveBlocksFiles(files);
  }
  function getBlocksFile(path) {
    const files = getBlocksFiles();
    const f = files.find(f => f.path === path);
    return f ? f.data : null;
  }
  function deleteBlocksFile(path) {
    let files = getBlocksFiles();
    files = files.filter(f => f.path !== path && !f.path.startsWith(path + '/'));
    saveBlocksFiles(files);
  }
  function moveBlocksFile(oldPath, newPath) {
    let files = getBlocksFiles();
    files.forEach(f => {
      if (f.path === oldPath || f.path.startsWith(oldPath + '/')) {
        f.path = newPath + f.path.slice(oldPath.length);
      }
    });
    saveBlocksFiles(files);
  }
  function ensureBlocksDir(path) {
    // No-op for now, as folders are simulated by file paths
    return true;
  }
  // Example usage: addBlocksFile('components/cards/CardBlock.json', { ...blockData... });
  // --- Export/Import UI and Logic ---
  function getProjectData() {
    return {
      html: window.monacoEditors?.html?.getValue() || '',
      css: window.monacoEditors?.css?.getValue() || '',
      js: window.monacoEditors?.js?.getValue() || '',
      timestamp: Date.now()
    };
  }
  function setProjectData(data) {
    if (window.monacoEditors) {
      if (data.html) window.monacoEditors.html.setValue(data.html);
      if (data.css) window.monacoEditors.css.setValue(data.css);
      if (data.js) window.monacoEditors.js.setValue(data.js);
    }
  }
  function exportProject(format) {
    const data = getProjectData();
    if (format === 'json') {
      const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
      downloadBlob(blob, 'tryit-project.json');
    } else if (format === 'zip') {
      if (!window.JSZip) {
        alert('JSZip library not loaded.');
        return;
      }
      const zip = new JSZip();
      zip.file('index.html', data.html);
      zip.file('style.css', data.css);
      zip.file('script.js', data.js);
      zip.file('project.json', JSON.stringify(data, null, 2));
      zip.generateAsync({type: 'blob'}).then(function(content) {
        downloadBlob(content, 'tryit-project.zip');
      });
    }
    localStorage.setItem('tryitEditorExportFormat', format);
  }
  function importProject(format, file) {
    if (format === 'json') {
      const reader = new FileReader();
      reader.onload = function(e) {
        try {
          const data = JSON.parse(e.target.result);
          setProjectData(data);
        } catch (err) { alert('Invalid JSON file.'); }
      };
      reader.readAsText(file);
    } else if (format === 'zip') {
      if (!window.JSZip) {
        alert('JSZip library not loaded.');
        return;
      }
      const zip = new JSZip();
      zip.loadAsync(file).then(function(zipObj) {
        zipObj.file('project.json').async('string').then(function(json) {
          try { setProjectData(JSON.parse(json)); } catch (err) { alert('Invalid project.json in zip.'); }
        });
      });
    }
    localStorage.setItem('tryitEditorExportFormat', format);
  }
  function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
  }
  // Add export/import UI to toolbar if not present
  function ensureExportImportUI() {
    if (document.getElementById('tryit-export-btn')) return;
    const toolbar = document.querySelector('.tryit-toolbar');
    if (!toolbar) return;
    const exportBtn = document.createElement('button');
    exportBtn.id = 'tryit-export-btn';
    exportBtn.className = 'tryit-toolbar-btn';
    exportBtn.textContent = 'Export';
    const importBtn = document.createElement('button');
    importBtn.id = 'tryit-import-btn';
    importBtn.className = 'tryit-toolbar-btn';
    importBtn.textContent = 'Import';
    const formatSel = document.createElement('select');
    formatSel.id = 'tryit-export-format';
    formatSel.className = 'tryit-toolbar-btn';
    formatSel.innerHTML = '<option value="json">JSON</option><option value="zip">ZIP</option>';
    // Set initial value from localStorage
    const pref = localStorage.getItem('tryitEditorExportFormat') || 'json';
    formatSel.value = pref;
    toolbar.appendChild(exportBtn);
    toolbar.appendChild(importBtn);
    toolbar.appendChild(formatSel);
    exportBtn.onclick = function() { exportProject(formatSel.value); };
    importBtn.onclick = function() {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = formatSel.value === 'json' ? '.json,application/json' : '.zip,application/zip';
      input.onchange = function(e) {
        if (e.target.files && e.target.files[0]) {
          importProject(formatSel.value, e.target.files[0]);
        }
      };
      input.click();
    };
    formatSel.onchange = function() {
      localStorage.setItem('tryitEditorExportFormat', formatSel.value);
    };
  }
  // Load JSZip if not present
  if (!window.JSZip) {
    var jszipScript = document.createElement('script');
    jszipScript.src = 'https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js';
    document.head.appendChild(jszipScript);
  }
  setTimeout(ensureExportImportUI, 500);
  // Debounced live preview sync
  function updatePreviewIframe() {
    if (!window.monacoEditors) return;
    var html = window.monacoEditors.html ? window.monacoEditors.html.getValue() : '';
    var css = window.monacoEditors.css ? window.monacoEditors.css.getValue() : '';
    var js = window.monacoEditors.js ? window.monacoEditors.js.getValue() : '';
    var doc = `<!DOCTYPE html>\n<html>\n<head>\n<meta charset='utf-8'>\n<style>${css}</style>\n</head>\n<body>\n${html}\n<script>${js}<\/script>\n</body>\n</html>`;
    var iframe = document.getElementById('tryit-preview-iframe');
    if (iframe) {
      iframe.srcdoc = doc;
    }
  }
  var previewDebounceTimer = null;
  function schedulePreviewUpdate() {
    if (previewDebounceTimer) clearTimeout(previewDebounceTimer);
    previewDebounceTimer = setTimeout(updatePreviewIframe, 350);
  }
  // Source View toggle logic
  function formatAllMonacoEditors() {
    if (window.monacoEditors) {
      Object.values(window.monacoEditors).forEach(editor => {
        if (editor.getAction) {
          const formatAction = editor.getAction('editor.action.formatDocument');
          if (formatAction) formatAction.run();
        }
      });
    }
  }
  function setSourceView(on) {
    if (on) {
      document.body.classList.add('source-view-on');
      document.body.classList.remove('source-view-off');
      $("#toggle-source-view").addClass('active');
      localStorage.setItem('tryitEditorSourceView', 'on');
      setTimeout(formatAllMonacoEditors, 100);
    } else {
      document.body.classList.remove('source-view-on');
      document.body.classList.add('source-view-off');
      $("#toggle-source-view").removeClass('active');
      localStorage.setItem('tryitEditorSourceView', 'off');
    }
    if (window.monacoEditors) {
      Object.values(window.monacoEditors).forEach(ed => ed.layout());
    }
  }
  // Initial state from localStorage
  setSourceView(localStorage.getItem('tryitEditorSourceView') === 'on');
  $("#toggle-source-view").on('click', function() {
    setSourceView(!document.body.classList.contains('source-view-on'));
  });

  // Robust GrapesJS init (wait for script)
  function initGrapes(attempts) {
    if (typeof attempts === 'undefined') attempts = 0;
    var $gjs = document.getElementById('grapesjs-editor');
    if (window.grapesjs && $gjs) {
      if (window.gjsEditor) return;
      $gjs.innerHTML = '';
      window.gjsEditor = grapesjs.init({
        container: '#grapesjs-editor',
        height: '100%',
        width: '100%',
        fromElement: false,
        storageManager: false,
        panels: { defaults: [] },
        blockManager: { appendTo: '#gjs-blocks' },
        styleManager: { appendTo: '#gjs-style-manager' },
        layerManager: { appendTo: '#gjs-style-manager' },
        selectorManager: { appendTo: '#gjs-style-manager' },
        traitManager: { appendTo: '#gjs-style-manager' },
      });
      // Add default blocks
      var bm = window.gjsEditor.BlockManager;
      bm.add('section', {
        label: '<b>Section</b>',
        category: 'Basic',
        attributes: { class: 'gjs-block-section' },
        content: '<section style="padding: 20px; min-height: 100px; background: #f5f5f5;">Section</section>'
      });
      bm.add('text', {
        label: 'Text',
        category: 'Basic',
        content: '<div data-gjs-type="text">Insert your text here</div>'
      });
      bm.add('image', {
        label: 'Image',
        category: 'Basic',
        select: true,
        content: { type: 'image' },
      });
      bm.add('button', {
        label: 'Button',
        category: 'Basic',
        content: '<button class="gjs-btn">Button</button>'
      });
      bm.add('2-cols', {
        label: '2 Columns',
        category: 'Layout',
        content: '<div class="row" style="display:flex"><div class="cell" style="flex:1;padding:10px;">Column 1</div><div class="cell" style="flex:1;padding:10px;">Column 2</div></div>'
      });
      bm.add('3-cols', {
        label: '3 Columns',
        category: 'Layout',
        content: '<div class="row" style="display:flex"><div class="cell" style="flex:1;padding:10px;">Col 1</div><div class="cell" style="flex:1;padding:10px;">Col 2</div><div class="cell" style="flex:1;padding:10px;">Col 3</div></div>'
      });
      bm.add('link', {
        label: 'Link',
        category: 'Basic',
        content: '<a href="https://example.com" target="_blank">Link</a>'
      });
      bm.add('list', {
        label: 'List',
        category: 'Basic',
        content: '<ul><li>Item 1</li><li>Item 2</li></ul>'
      });
      bm.add('card', {
        label: 'Card',
        category: 'Components',
        content: '<div style="border:1px solid #ccc;padding:16px;border-radius:8px;background:#fff;">Card content</div>'
      });
      // Optionally, add more blocks as needed
      console.log('GrapesJS loaded and initialized.');
      // Wire GrapesJS output to Monaco editors
      window.gjsEditor.on('update', function() {
        var html = window.gjsEditor.getHtml();
        var css = window.gjsEditor.getCss();
        if (window.monacoEditors) {
          if (window.monacoEditors.html) window.monacoEditors.html.setValue(html);
          if (window.monacoEditors.css) window.monacoEditors.css.setValue(css);
        }
      });
    } else if ($gjs && attempts < 30) {
      if (attempts === 0) console.log('Waiting for GrapesJS to load...');
      setTimeout(function() { initGrapes(attempts+1); }, 200);
    } else if ($gjs && !window.grapesjs) {
      $gjs.innerHTML = '<div style="color:#fff;padding:1em;">GrapesJS failed to load. Check your network or CSP.</div>';
      console.error('GrapesJS failed to load after multiple attempts.');
    }
  }
  $("#tryit-panel-left").removeClass("collapsed");
  initGrapes();

  // Monaco Editor loader
  function loadMonaco(callback) {
    if (typeof window.require === 'undefined') {
      setTimeout(function() { loadMonaco(callback); }, 50);
      return;
    }
    window.require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
    window.require(['vs/editor/editor.main'], callback);
  }
  loadMonaco(function () {
    window.monacoEditors = {};
    function ensureMonacoTheme() {
      if (window.monaco && window.monaco.editor) {
        monaco.editor.setTheme('vs-dark');
        // Check if Monaco theme CSS is present
        var found = false;
        for (const sheet of document.styleSheets) {
          if (sheet.href && sheet.href.includes('monaco-editor')) {
            found = true;
            break;
          }
        }
        if (!found) {
          console.error('Monaco theme CSS not loaded! Syntax highlighting will not work.');
        }
      }
    }
    window.monacoEditors.html = monaco.editor.create(
      document.getElementById("editor-html"),
      {
        value: "<!-- HTML here -->\n",
        language: "html",
        theme: "vs-dark",
        automaticLayout: true,
        minimap: { enabled: false },
      }
    );
    window.monacoEditors.css = monaco.editor.create(
      document.getElementById("editor-css"),
      {
        value: "/* CSS here */\n",
        language: "css",
        theme: "vs-dark",
        automaticLayout: true,
        minimap: { enabled: false },
      }
    );
    window.monacoEditors.js = monaco.editor.create(
      document.getElementById("editor-js"),
      {
        value: "// JavaScript here\n",
        language: "javascript",
        theme: "vs-dark",
        automaticLayout: true,
        minimap: { enabled: false },
      }
    );
    // Add change listeners for live preview sync
    window.monacoEditors.html.onDidChangeModelContent(schedulePreviewUpdate);
    window.monacoEditors.css.onDidChangeModelContent(schedulePreviewUpdate);
    window.monacoEditors.js.onDidChangeModelContent(schedulePreviewUpdate);
    // Initial preview
    setTimeout(updatePreviewIframe, 200);
    setTimeout(ensureMonacoTheme, 500);
  });
});
