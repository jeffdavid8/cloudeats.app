// Monaco Editor setup for TryItEditor
window.setupMonacoEditors = function() {
  require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
  require(['vs/editor/editor.main'], function () {
    window.monacoEditors = {
      html: monaco.editor.create(document.getElementById('editor-html'), {
        value: '<!-- HTML here -->',
        language: 'html',
        theme: 'vs-dark',
        automaticLayout: true
      }),
      css: monaco.editor.create(document.getElementById('editor-css'), {
        value: '/* CSS here */',
        language: 'css',
        theme: 'vs-dark',
        automaticLayout: true
      }),
      js: monaco.editor.create(document.getElementById('editor-js'), {
        value: '// JS here',
        language: 'javascript',
        theme: 'vs-dark',
        automaticLayout: true
      })
    };
  });
};
