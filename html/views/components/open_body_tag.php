<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var String $nightModeClass
 * 
 */
?>

<body class="loading-preloader <?= (!$page_name) ? ' ce-home' : $page_name . ' page ' ?> <?= (!empty($bg_image)) ? ' image_bg' : '' ?>">