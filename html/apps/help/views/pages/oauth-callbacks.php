<?php
// OAuth Callback URL Reference Page
// Shows the required callback URLs for Google, Facebook, and Apple

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

function renderCallback($provider, $path) {
    global $baseUrl;
    echo "<tr><td><strong>" . ucfirst($provider) . "</strong></td><td><code>" . $baseUrl . $path . "</code></td></tr>";
}
?>

<div class="help-breadcrumb">
    <a href="?app=help">Help Center</a> &gt; OAuth Callback URLs
</div>

<h1>OAuth Provider Callback URLs</h1>
<p>Register these callback URLs with each provider when setting up OAuth authentication. These must be publicly accessible for login to work.</p>

<table class="mb-table" style="width:100%; max-width:700px; margin:30px auto;">
    <thead>
        <tr><th>Provider</th><th>Callback URL</th></tr>
    </thead>
    <tbody>
        <?php
        renderCallback('google', '/oauth/google.php?action=callback');
        renderCallback('facebook', '/oauth/facebook.php?action=callback');
        renderCallback('apple', '/oauth/apple.php?action=callback');
        ?>
    </tbody>
</table>

<p>Copy and paste these URLs into the provider setup forms. For more details, see the <a href="?app=help&page=developer">Developer Docs</a>.</p>
