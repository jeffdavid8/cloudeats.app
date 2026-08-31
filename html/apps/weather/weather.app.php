<?


function weather_info()
{
  return array(
    'title' => "Weather",
    'image' => config('base_url') . '/apps/weather/images/Weather-app-index-logo.png',
    'image_width' => '1200',
    'image_height' => '630',
    'description' => "Here is a look at the weather in your area",
    'version' => "1.0",
    'requires_auth' => false,
    'requires_admin' => false,
    'no_header' => false,
    'public_app' => true,
    'styles' => array(
      "apps/weather/css/app.css",
    ),
    'scripts' => array(
      "apps/weather/js/app.js",
    ),
  );
}

function weather_init()
{
  $app = App::getInstance();
  $meta = array(
    'title' => 'Local Weather Forecast & Radar',
    'description' => 'Here is a look at local weather in your area.',
  );
  $meta['image'] = isset($app->app_info['image']) ? $app->app_info['image'] : $meta['image'];
  $meta['image_width'] = isset($app->app_info['image_width']) ? $app->app_info['image_width'] : $meta['image_width'];
  $meta['image_height'] = isset($app->app_info['image_height']) ? $app->app_info['image_height'] : $meta['image_height'];

  $app->set('meta', $meta);
}


function weather_render_body()
{
  render('components/header/header.php', array('search_string' => ''));
  render('components/body.php', array('search_string' => ''));
}
?>
