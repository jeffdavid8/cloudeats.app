<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var String $nightModeClass
 * 
 */
?>

<body class="<?= (!$this->appName) ? ' mb-home' : $this->appName . ' app ' ?> <?= $nightModeClass ?> <?= (!empty($bg_image)) ? ' image_bg' : '' ?>">