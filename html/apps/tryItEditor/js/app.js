// tryItEditor main JS (ES6+)
// Monaco Editor panels (HTML, CSS, JS), Split.js panel resizing, live preview, export/import, blocks library modal
// Modern, robust UI

$(document).ready(function(){
  // --- Monaco Editor & JSZip Initialization (AMD) ---
  function loadMonacoAndJSZip(callback) {
    window.require.config({
      paths: {
        vs: "https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs",
        jszip: "https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min",
      },
    });
    window.require(["vs/editor/editor.main"], (monacoMain, JSZip) => {
      window.JSZip = JSZip;
      callback(monacoMain);
      window.updatePreviewIframe(); // Initial preview update
    });
  }

  loadMonacoAndJSZip(() => {
    let html = "";
    let css = "";
    let js = "";
    if (typeof example !== "undefined")
    {
      html = example.html || "";
      css = example.css || "";
      js = example.js || "";
    }
    
    window.monacoEditors = {
      html: monaco.editor.create(document.getElementById("editor-html"), {
        value: "<!-- HTML here -->\n" + html,
        language: "html",
        theme: "vs-dark",
        automaticLayout: true,
        wordWrap: "on",
        minimap: { enabled: false },
        autoIndent: 'full', // or true/advanced
        formatOnPaste: true,
        //formatOnType: true
      }),
      css: monaco.editor.create(document.getElementById("editor-css"), {
        value: "/* CSS here */\n" + css,
        language: "css",
        theme: "vs-dark",
        automaticLayout: true,
        wordWrap: "on",
        minimap: { enabled: false },
        autoIndent: 'full', // or true/advanced
        formatOnPaste: true,
        //formatOnType: true
      }),
      js: monaco.editor.create(document.getElementById("editor-js"), {
        value: "// JavaScript here\n" + js,
        language: "javascript",
        theme: "vs-dark",
        automaticLayout: true,
        wordWrap: "on",
        minimap: { enabled: false },
        autoIndent: 'full', // or true/advanced
        formatOnPaste: true,
        //formatOnType: true
      }),
    };

    // Live preview sync
    window.updatePreviewIframe = () => {
      const html = window.monacoEditors.html.getValue();
      const css = window.monacoEditors.css.getValue();
      const js = window.monacoEditors.js.getValue();
      const headAssets = $('head link')
                         .map(function() { return this.outerHTML; })
                         .get()
                         .join('\n');
      const doc = `<!DOCTYPE html>
      <html>
      <head>
      <meta charset='utf-8'>
      <style>body {background-color: #111; color: #eee;}</style>
      <style>${css}</style>
      </head>
      <body>
      ${html}
      <script>${js.replace(/<\/script>/gi, "<\\/script>")}</script>
      </body>
      </html>`;
      const iframe = document.getElementById("tryit-preview-iframe");
      const panel = document.getElementById("tryit-preview-panel");

      panel.style.height =
        iframe.contentWindow.document.body.scrollHeight + parseInt(iframe.contentWindow.getComputedStyle(iframe.contentWindow.document.body).marginBottom) + parseInt(iframe.contentWindow.getComputedStyle(iframe.contentWindow.document.body).marginTop) + 6 + "px";
      iframe.style.height =
        iframe.contentWindow.document.body.scrollHeight + parseInt(iframe.contentWindow.getComputedStyle(iframe.contentWindow.document.body).marginBottom) + parseInt(iframe.contentWindow.getComputedStyle(iframe.contentWindow.document.body).marginTop) + 6 + "px";

      if (iframe) iframe.srcdoc = doc;

    };
    window.monacoEditors.html.onDidChangeModelContent(updatePreviewIframe);
    window.monacoEditors.css.onDidChangeModelContent(updatePreviewIframe);
    window.monacoEditors.js.onDidChangeModelContent(updatePreviewIframe);
    //setTimeout(updatePreviewIframe, 200);
  });

  // --- Export/Import ---
  const getProjectData = () => ({
    html: window.monacoEditors?.html?.getValue() || "",
    css: window.monacoEditors?.css?.getValue() || "",
    js: window.monacoEditors?.js?.getValue() || "",
    timestamp: Date.now(),
  });

  const setProjectData = (data) => {
    if (window.monacoEditors) {
      if (data.html) {
        window.monacoEditors.html.setValue("<!-- HTML -->\n" + data.html);
        window.monacoEditors.html.getAction('editor.action.formatDocument').run();
      }
      if (data.css) {
        window.monacoEditors.css.setValue("/* CSS */\n" + data.css);
        window.monacoEditors.css.getAction('editor.action.formatDocument').run();
      }
      if (data.js) {
        window.monacoEditors.js.setValue("// JavaScript \n" + data.js);
        window.monacoEditors.js.getAction('editor.action.formatDocument').run();
      }
    }
  };

  const downloadBlob = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }, 100);
  };

  const exportProject = (format) => {
    const data = getProjectData();
    if (format === "json") {
      const blob = new Blob([JSON.stringify(data, null, 2)], {
        type: "application/json",
      });
      downloadBlob(blob, "tryit-project.json");
    } else if (format === "zip") {
      if (!window.JSZip) {
        alert("JSZip library not loaded.");
        return;
      }
      const zip = new JSZip();
      zip.file("index.html", data.html);
      zip.file("style.css", data.css);
      zip.file("script.js", data.js);
      zip.file("project.json", JSON.stringify(data, null, 2));
      zip.generateAsync({ type: "blob" }).then((content) => {
        downloadBlob(content, "tryit-project.zip");
      });
    }
    localStorage.setItem("tryitEditorExportFormat", format);
  };

  const importProject = (format, file) => {
    if (format === "json") {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = JSON.parse(e.target.result);
          setProjectData(data);
        } catch (err) {
          alert("Invalid JSON file.");
        }
      };
      reader.readAsText(file);
    } else if (format === "zip") {
      if (!window.JSZip) {
        alert("JSZip library not loaded.");
        return;
      }
      const zip = new JSZip();
      zip.loadAsync(file).then((zipObj) => {
        zipObj
          .file("project.json")
          .async("string")
          .then((json) => {
            try {
              setProjectData(JSON.parse(json));
            } catch (err) {
              alert("Invalid project.json in zip.");
            }
          });
      });
    }
    localStorage.setItem("tryitEditorExportFormat", format);
  };

  // --- Export/Import Toolbar UI ---
  const ensureExportImportUI = () => {
    if (document.getElementById("tryit-export-btn")) return;
    const toolbar = document.querySelector(".tryit-toolbar");
    if (!toolbar) return;
    const exportBtn = document.createElement("button");
    exportBtn.id = "tryit-export-btn";
    exportBtn.className = "tryit-toolbar-btn";
    exportBtn.textContent = "Export";
    const importBtn = document.createElement("button");
    importBtn.id = "tryit-import-btn";
    importBtn.className = "tryit-toolbar-btn";
    importBtn.textContent = "Import";
    const formatSel = document.createElement("select");
    formatSel.id = "tryit-export-format";
    formatSel.className = "tryit-toolbar-btn";
    formatSel.innerHTML =
      '<option value="json">JSON</option><option value="zip">ZIP</option>';
    // Set initial value from localStorage
    const pref = localStorage.getItem("tryitEditorExportFormat") || "json";
    formatSel.value = pref;
    toolbar.append(exportBtn, importBtn, formatSel);
    exportBtn.onclick = () => exportProject(formatSel.value);
    importBtn.onclick = () => {
      const input = document.createElement("input");
      input.type = "file";
      input.accept =
        formatSel.value === "json"
          ? ".json,application/json"
          : ".zip,application/zip";
      input.onchange = (e) => {
        if (e.target.files && e.target.files[0]) {
          importProject(formatSel.value, e.target.files[0]);
        }
      };
      input.click();
    };
    formatSel.onchange = () => {
      localStorage.setItem("tryitEditorExportFormat", formatSel.value);
    };
  };

  // JSZip is loaded via AMD above; no need for <script> injection

  // --- Blocks Library Modal ---
  // (Add modal logic here as needed)

  // --- API Integration (CSRF) ---
  const getCSRFToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  };

  // --- Example Gallery, Versioning, Theming, etc. ---
  // (Add additional features as needed)

  // --- Toolbar Button Event Wiring (jQuery, matching actual HTML IDs) ---
  const buttonMap = {
    "save-session": "modal-save",
    "export-project": "modal-export",
    "import-project": "modal-import",
    "open-blocks-library": "modal-blocks",
    "open-settings": "modal-settings",
    // Add more mappings as needed
  };
  $.each(buttonMap, function (btnId, modalId) {
    $("#" + btnId).on("click", function () {
      // Open the corresponding modal if it exists
      const $modal = $("#" + modalId);
      if ($modal.length) {
        $modal.addClass("open");
      } else {
        console.log("Modal not found for:", btnId);
      }
    });
  });

  // Direct actions for other toolbar buttons
  $("#toggle-source-view").on("click", function () {
    // TODO: Implement source view toggle logic
    console.log("Toggle Source View clicked");
  });
  $("#run-preview").on("click", function () {
    // Manually refresh the preview iframe
    if (typeof updatePreviewIframe === "function") {
      updatePreviewIframe();
    }
  });

  $("#reset-editors").on("click", function () {
    // Show confirm modal for reset
    $("#modal-confirm-reset").addClass("open");
    $("body").css("overflow", "hidden");
    // Confirm/cancel reset modal actions
  });

  $("#open-examples-modal").on("click", function () {
    // Show confirm modal for reset
    $("#modal-example-selector").addClass("open");
    $("body").css("overflow", "hidden");
    // Confirm/cancel reset modal actions
  });

  $('#modal-example-selector .modal-body button').on("click", function () {
    let example = $(this).data('example');
    console.log('Loading example project:', example);
    setProjectData(example);
    $(this).closest(".modal").removeClass("open");
    restoreBodyScroll();
    const url = new URL(window.location.href);
    url.searchParams.set('example', example.id);
    history.replaceState(null, '', url.toString());
  });
  
  /*
  */
  $("#modal-example-selector, #modal-example-selector .modal-close, #modal-example-selector .cancel").on("click", function (e) {
    if (e.target === this) {
      e.preventDefault();
      e.stopPropagation();
      $(this).closest(".modal").removeClass("open");
      restoreBodyScroll();
      return false;
    }
  });

  function resetEditorsToDefault() {
    // Reset all editors to default values
    if (window.monacoEditors) {
      window.monacoEditors.html.setValue("<!-- HTML here -->\n");
      window.monacoEditors.css.setValue("/* CSS here */\n");
      window.monacoEditors.js.setValue("// JavaScript here\n");
      setTimeout(updatePreviewIframe, 200);
    }
  }

  $("#modal-confirm-reset .confirm-reset-yes").on("click", function () {
    resetEditorsToDefault();
    $("#modal-confirm-reset").removeClass("open");
  });

  // Modal close buttons (jQuery)
  $("#modal-confirm-reset, #modal-confirm-reset .modal-close, #modal-confirm-reset .confirm-reset-cancel").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).closest(".modal").removeClass("open");
    restoreBodyScroll();
    return false;
  });

  function restoreBodyScroll() {
    let $modals = $(".modal.open");
    if ($modals.length === 0) {
      $("body").css("overflow", "auto");
    } else {
      $("body").css("overflow", "hidden");
    }
  }

});
