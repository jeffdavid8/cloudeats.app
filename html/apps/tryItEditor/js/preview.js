// Live preview logic for TryItEditor
window.setupPreview = function() {
  const iframe = document.getElementById('tryit-preview-iframe');
  function updatePreview() {
    const html = window.monacoEditors.html.getValue();
    const css = window.monacoEditors.css.getValue();
    const js = window.monacoEditors.js.getValue();
    const doc = `<!DOCTYPE html><html><head><style>${css}</style></head><body>${html}<script>${js}<\/script></body></html>`;
    iframe.srcdoc = doc;
  }
  // Update preview on editor changes
  ['html', 'css', 'js'].forEach(key => {
    window.monacoEditors[key]?.onDidChangeModelContent(updatePreview);
  });
  // Initial preview
  setTimeout(updatePreview, 500);
}
