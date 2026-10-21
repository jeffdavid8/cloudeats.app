<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var String $nightModeClass
 * 
 */
$customer = $this->get('customer');
$view = $this->get('view');
$page = $this->get('page');
?>
<body class="loading-preloader <?= (!$this->appName) ? ' mb-home' : $this->appName . ' app ' ?> <?= $view.'-view' ?> <?= $page.'-page' ?> <?= (!empty($bg_image)) ? ' image_bg' : '' ?><?= (!$this->user->isAdmin && empty($customer->terms_accepted_at)) ? ' header-announcement' : '' ?>">
  
  <div id="confetti-cannon"></div>
