<?php
//phpinfo();
require '../includes/app.php';
$mb = App::getInstance();
$app = get_var('app', false);

$search_string = get_var('s');
$search_results = array();

$endpoint = "http://vi/mediawiki/api.php";
$params_array = [
    "action" => "query",
    "list" => "search",
    "srsearch" => $search_string,
    "format" => "json"
];

$url = $endpoint . "?" . http_build_query( $params_array );

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
$output = curl_exec( $ch );
curl_close( $ch );

$wikipedia_results = json_decode( $output, true );

if (count($wikipedia_results['query']['search']))
{
   $search_results['wikipedia_results'] = $wikipedia_results;
}

$search_page = (empty($search_results)) ? 'search' : 'search_results';
$page_title = (empty($search_results)) ? 'Mediabrain.net' : 'Search results';
$view = get_var('p', $search_page);
$page = array(
   '#view' => 'pages/' . $view . '.php',
);

$meta = array(
   'title' => $page_title,
);

$night_mode = ($_SESSION['day_night_mode'] == 'night') || (!isset($_SESSION['day_night_mode']));

render('components/head.php', array('meta' => $meta));
?>


<body class="<?= (!$app) ? 'search' : '' ?> <?= ($night_mode) ? 'nightMode' : '' ?>">


<?php /*
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v11.0&appId=242567873466625&autoLogAppEvents=1" nonce="DBtXoems"></script>
*/ ?>

   <?php render($page['#view'], array('search_results' => $search_results, 'search_string' => $search_string)); ?>

   <?php if (config('request_donations')) 
         render('components/footer.php'); ?>

   <!--  Scripts-->
   <!--JavaScript at end of body for optimized loading-->
   <script src="/js/materialize.min.js" type="text/javascript"></script>
   <script src="/js/overlay.js" type="text/javascript"></script>
   <script src="/js/dialogs/share.js" type="text/javascript"></script>
   <script src="/js/dialogs/save.js" type="text/javascript"></script>
   <script src="/js/dialogs/open.js" type="text/javascript"></script>
   <script src="/js/init.js" type="text/javascript"></script>

   <?php //render('components/google_analytics.php'); ?>

   <?php
   if (!$_SESSION['under_construction_notification'] && config('under_construction'))
   {
      $_SESSION['under_construction_notification'] = true;
      render('components/under_construction.php');
   }
   ?>
   
   <div id="loadingIndicator">
      <?php render('components/loading_indicator.php'); ?>
   </div>

</body>

</html>
