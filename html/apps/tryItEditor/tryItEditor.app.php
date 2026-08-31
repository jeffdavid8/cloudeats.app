<?php
// tryItEditor.app.php - App entry point

function tryItEditor_info() {
    return array(
        'title' => "Try-it Editor",
        'description' => "Visual code editor and component builder.",
        'keywords' => "code editor, visual editor, component builder, HTML, CSS, JavaScript, web development",
        'image' => '',
        'version' => "0.2",
        'requires_auth' => false,
        'requires_admin' => false,
        'public_app' => true,
        'styles' => array(
            "apps/tryItEditor/css/app.css",
        ),
        'scripts' => array(
            "https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js",
            'https://cdn.jsdelivr.net/npm/split.js/dist/split.min.js',
            "https://cdn.jsdelivr.net/npm/requirejs@2.3.6/require.min.js",
            "apps/tryItEditor/js/monaco.js",
            "apps/tryItEditor/js/preview.js",
            "apps/tryItEditor/js/app.js",
        ),
        'components' => array(),
    );
}

function tryItEditor_init() {
    $app = App::getInstance();
    $app->set('page_title', 'tryItEditor');
    $app->set('page', array(
        '#view' => 'pages/app.php',
    ));
    $examplesFile = 'apps/tryItEditor/data/tryit_100_real_examples_mixed.json';
    $examples = json_decode(file_get_contents($examplesFile), true);
    $example = get_var('example', false) ? $examples[array_search(get_var('example'), array_column($examples, 'id'))] : false;
    $app->set('examples', $examples);
    $app->set('example', $example);
    $meta = array(
        'title' => $example ? ($example['title'] . ' - Try-it Editor') : 'Try-it Editor',
        'description' => $example ? $example['description'] : tryItEditor_info()['description'],
        'keywords' => $example ? $example['keywords'] : tryItEditor_info()['keywords'],
    );
    $app->set('meta', $meta);
}

function tryItEditor_render_body() {
    $app = App::getInstance();
    $example = $app->get('example');
    $examples = $app->get('examples');
    render('pages/app.php', array(
        'example' => $example,
        'examples' => $examples,
    ));
    render('components/modals.php', array(
        'example' => $example,
        'examples' => $examples,
    ));
}
