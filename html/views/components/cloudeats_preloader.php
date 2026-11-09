<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var String $text
 * @var String $merchant_logo
 * @var String $merchant_name
 */
?>

<div id="cloudeats-preloader" class="preloader-overlay">
  <div class="cloudeats-preloader-wrap">
    <?php if (!empty($merchant_logo)): ?>
      <img
        class="merchant-preloader-logo"
        src="<?= htmlspecialchars($merchant_logo, ENT_QUOTES, 'UTF-8') ?>"
        alt="<?= htmlspecialchars($merchant_name ?: 'Merchant logo', ENT_QUOTES, 'UTF-8') ?>"
      >
    <?php endif; ?>
    <svg class="nav-trigger-favicon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="46" class="fav-bg"></circle>
      <path d="M 72,26 H 44 A 24,24 0 0,0 44,74 H 72 M 44,50 H 66" class="fav-letters"></path>
    </svg>
    <div class="preloader-text"><?= $text ?></div>
  </div>
</div>