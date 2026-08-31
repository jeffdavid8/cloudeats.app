<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var String $nightModeClass
 * 
 */
$customer = $this->get('customer');
?>
<body class="<?= (!$this->appName) ? ' mb-home' : $this->appName . ' app ' ?> <?= $nightModeClass ?> <?= (!empty($bg_image)) ? ' image_bg' : '' ?><?= (!$customer->terms_accepted_at) ? ' header-announcement' : '' ?>">