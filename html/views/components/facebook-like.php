<?
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$og_url = $protocol . '://' . $host . $uri;
?>

<div class="fb-like" data-href="<?= $og_url ?>" data-width="300" data-layout="" data-action="" data-size="" data-share="true"></div>

