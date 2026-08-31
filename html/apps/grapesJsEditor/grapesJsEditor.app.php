<?php
// grapesJsEditor.app.php - App entry point

function grapesJsEditor_info() {
    return array(
        'title' => "grapesJsEditor",
        'description' => "Visual code editor and component builder.",
        'image' => '',
        'version' => "0.2",
        'requires_auth' => false,
        'requires_admin' => false,
        'public_app' => true,
        'styles' => array(
            "https://cdn.jsdelivr.net/npm/grapesjs@0.21.8/dist/css/grapes.min.css",
            "apps/grapesJsEditor/css/app.css",
        ),
        'scripts' => array(
            "https://cdn.jsdelivr.net/npm/grapesjs@0.21.8/dist/grapes.min.js",
            "apps/grapesJsEditor/js/app.js",
            "https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"
        ),
        'components' => array(),
    );
}

function grapesJsEditor_init() {
    $app = App::getInstance();
    $app->set('page_title', 'grapesJsEditor');
    $app->set('page', array(
        '#view' => 'pages/app.php',
    ));
}

function grapesJsEditor_render_body() {
    render('pages/app.php');
}
