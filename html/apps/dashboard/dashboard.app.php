<?php
function dashboard_info()
{
  return array(
    'title' => "Dashboard",
    'description' => "Real-time collaborative dashboard.",
    'image' => config('base_url') . '/apps/dashboard/images/dashboard-app-index-logo.png',
    'version' => "1.0",
    'requires_auth' => true,
    'requires_admin' => false,
    'public_app' => false,
    'styles' => array(
      "apps/dashboard/css/style.css",
    ),
    'scripts' => array(
      "apps/dashboard/js/app.js",
    ),
  );
}

function dashboard_init()
{
  $app = App::getInstance();
  $app->set('page', array(
    '#view' => 'views/main.php',
  ));
}

function dashboard_render_body()
{
  $app = App::getInstance('dashboard');
  render($app->get('page')['#view']);
}
