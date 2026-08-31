<?
// Secure the entry point
if (!defined('MB_RUNNING')) exit;

function init_db($title)
{
  echo "<h1>$title</h1>";
  //$_SESSION['admin_key'] = NULL;
  //exit();
  $key = get_var('key', false);
  if (((!$key) || ($key != $_SESSION['admin_key'])) && (!isset($_SESSION['bypass_admin_key']))) {
    $key = rand(100000, 999999);
    $_SESSION['admin_key'] = $key;
    echo '<a class="btn" href="?app=admin&p=download_db&type=json">Download a Backup of the Database First!</a><br/><br/>';
    echo '<a class="btn" href="?app=admin&p=install_db&key=' . $key . '">Install Database</a>';
    die();
  }
  $_SESSION['admin_key'] = NULL;

  echo " <br><br><br><br>                                  ( . Y . ) <br><br>";
  echo 'Here we gooooo!  ------------~~~~~~~';
}

init_db('INSTALL DATABASE');
$app = App::getInstance();

$result = [];
$result['admin '] = app_invoke('admin', 'install_db');
$result['stitch '] = app_invoke('stitch', 'install_db');
$result['neighborhub'] = app_invoke('neighborhub', 'install_db');


echo "TABLES_CREATED.  <br><br>";
