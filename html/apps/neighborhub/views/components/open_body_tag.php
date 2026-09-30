<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var String $nightModeClass
 * 
 */
$customer = $this->get('customer');
?>
<body class="loading-preloader <?= (!$this->appName) ? ' mb-home' : $this->appName . ' app ' ?> <?= (!empty($bg_image)) ? ' image_bg' : '' ?><?= (!$customer || empty($customer->terms_accepted_at)) ? ' header-announcement' : '' ?>">