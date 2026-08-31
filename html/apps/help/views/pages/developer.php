<?php
// Developer Docs help page
?>

<div class="help-breadcrumb">
    <a href="?app=help">Help Center</a> &gt; Developer Docs
</div>

<h1>MediaBrain Theme System: Developer Reference</h1>

<p>This guide provides a comprehensive reference for implementing themes, components, containers, and utility classes in MediaBrain apps. All code samples are copy-paste ready and follow a MaterializeCSS-like approach for rapid development.</p>

<h2>Getting Started</h2>
<ol>
    <li><strong>Choose a theme:</strong> <code>default</code>, <code>startrek</code>, or your custom theme.</li>
    <li><strong>Include theme assets in your view:</strong></li>
</ol>
<pre><code class="help-code-block">&lt;?php
include_theme_css('default', ['components.css', 'dashboard.css']);
include_theme_js('default', ['theme.js']);
include_theme_audio('default', ['click', 'notify']);
?&gt;
</code></pre>

<h2>Theme File Structure</h2>
<pre><code>/themes/{theme}/
    components.css
    dashboard.css
    theme.js
    audio/
        click.mp3
        notify.mp3
/apps/{app}/themes/{theme}/
    (app-level overrides)
</code></pre>

<h2>Utility Classes Reference</h2>
<h3>Buttons</h3>
<pre><code>&lt;button class="mb-btn"&gt;Default Button&lt;/button&gt;
&lt;button class="mb-btn mb-btn-primary"&gt;Primary Button&lt;/button&gt;
&lt;button class="mb-btn mb-btn-accent"&gt;Accent Button&lt;/button&gt;
&lt;button class="mb-btn mb-btn-large"&gt;Large Button&lt;/button&gt;
</code></pre>

<h3>Cards</h3>
<pre><code>&lt;div class="mb-card"&gt;
  &lt;div class="mb-card-content"&gt;
    &lt;h3 class="mb-card-title"&gt;Card Title&lt;/h3&gt;
    &lt;p class="mb-text"&gt;Card content goes here.&lt;/p&gt;
    &lt;button class="mb-btn mb-btn-primary"&gt;Action&lt;/button&gt;
  &lt;/div&gt;
&lt;/div&gt;
</code></pre>

<h3>Panels</h3>
<pre><code>&lt;div class="mb-panel"&gt;Basic Panel&lt;/div&gt;
&lt;div class="mb-panel mb-panel-primary"&gt;Primary Panel&lt;/div&gt;
&lt;div class="mb-panel mb-panel-dark"&gt;Dark Panel&lt;/div&gt;
</code></pre>

<h3>Alerts</h3>
<pre><code>&lt;div class="mb-alert mb-alert-info"&gt;Info message&lt;/div&gt;
&lt;div class="mb-alert mb-alert-success"&gt;Success message&lt;/div&gt;
&lt;div class="mb-alert mb-alert-error"&gt;Error message&lt;/div&gt;
</code></pre>

<h3>Navigation</h3>
<pre><code>&lt;nav class="mb-nav"&gt;
  &lt;a href="#" class="mb-nav-link mb-nav-active"&gt;Home&lt;/a&gt;
  &lt;a href="#" class="mb-nav-link"&gt;Dashboard&lt;/a&gt;
&lt;/nav&gt;
</code></pre>

<h3>Forms</h3>
<pre><code>&lt;input type="text" class="mb-input" placeholder="Text input"&gt;
&lt;select class="mb-select"&gt;
  &lt;option&gt;Option 1&lt;/option&gt;
&lt;/select&gt;
</code></pre>

<h3>Grid & Layout</h3>
<pre><code>&lt;div class="mb-container"&gt;
  &lt;div class="mb-row"&gt;
    &lt;div class="mb-col-6"&gt;Half width&lt;/div&gt;
    &lt;div class="mb-col-6"&gt;Half width&lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;
</code></pre>

<h3>Spacing Utilities</h3>
<pre><code>&lt;div class="mb-m-2"&gt;Medium margin&lt;/div&gt;
&lt;div class="mb-p-2"&gt;Medium padding&lt;/div&gt;
</code></pre>

<h3>Typography</h3>
<pre><code>&lt;h1 class="mb-heading mb-heading-1"&gt;Large Heading&lt;/h1&gt;
&lt;p class="mb-text mb-text-muted"&gt;Muted text&lt;/p&gt;
</code></pre>

<h2>Theme Audio Usage</h2>
<pre><code>&lt;audio id="theme-click" src="/themes/default/audio/click.mp3" preload="auto"&gt;&lt;/audio&gt;
&lt;button onclick="document.getElementById('theme-click').play()"&gt;Play Click&lt;/button&gt;
</code></pre>

<h2>Overriding Theme Assets</h2>
<p>To override any theme asset for a specific app, place your file in <code>/apps/{app}/themes/{theme}/</code>. The system will use the override if it exists, otherwise it falls back to the global theme asset.</p>
<pre><code>&lt;?php
$audioPath = get_theme_file('default', 'audio/click.mp3', 'myapp');
// Returns /apps/myapp/themes/default/audio/click.mp3 if it exists
// Otherwise returns /themes/default/audio/click.mp3
?&gt;
</code></pre>

<h2>Best Practices</h2>
<ul>
    <li>Always use utility functions for asset inclusion.</li>
    <li>Use utility classes for consistent styling.</li>
    <li>Document overrides and customizations in your app's README.</li>
    <li>Test your app with multiple themes for compatibility.</li>
</ul>

<h2>Advanced: Creating a New Theme</h2>
<ol>
    <li>Create a new directory in <code>/themes/{newtheme}/</code></li>
    <li>Add your CSS, JS, and audio files.</li>
    <li>Use the utility functions to include assets in your views.</li>
    <li>Override assets in <code>/apps/{app}/themes/{newtheme}/</code> as needed.</li>
</ol>

<h2>Reference: Utility Functions</h2>
<pre><code>include_theme_css($theme, $files)
include_theme_js($theme, $files)
include_theme_audio($theme, $files, $type)
get_theme_file($theme, $file, $app = null)
</code></pre>

<h2>Need Help?</h2>
<p>Contact the MediaBrain development team or see code comments in <code>includes/util.php</code> for more info.</p>

<h2>OAuth Provider Setup</h2>
<p>To enable Google, Facebook, and Apple login for your app, follow these steps:</p>
<ul>
  <li>Register your app with each provider to obtain credentials.</li>
  <li>Edit <code>C:\var\data\mediabrain\oauth_config.json</code> (Windows) or <code>/var/data/mediabrain/oauth_config.json</code> (Linux/Docker) and add your credentials as shown below.</li>
</ul>

<h3>Google OAuth</h3>
<ol>
  <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></li>
  <li>Create a project and OAuth client ID (Web application).</li>
  <li>Set redirect URI: <code>https://yourdomain.com/oauth/google.php?action=callback</code></li>
  <li>Copy your Client ID and Client Secret.</li>
  <li>Edit your config:
<pre><code class="help-code-block">"google": {
  "enabled": true,
  "client_id": "YOUR_GOOGLE_CLIENT_ID",
  "client_secret": "YOUR_GOOGLE_CLIENT_SECRET",
  "scopes": ["openid", "email", "profile"]
}</code></pre></li>
</ol>

<h3>Facebook Login</h3>
<ol>
  <li>Go to <a href="https://developers.facebook.com/apps/" target="_blank">Facebook for Developers</a></li>
  <li>Create an app and add Facebook Login.</li>
  <li>Set redirect URI: <code>https://yourdomain.com/oauth/facebook.php?action=callback</code></li>
  <li>Copy your App ID and App Secret.</li>
  <li>Edit your config:
<pre><code class="help-code-block">"facebook": {
  "enabled": true,
  "client_id": "YOUR_FACEBOOK_APP_ID",
  "client_secret": "YOUR_FACEBOOK_APP_SECRET",
  "scopes": ["email", "public_profile"]
}</code></pre></li>
</ol>

<h3>Apple Sign In</h3>
<ol>
  <li>Go to <a href="https://developer.apple.com/account/" target="_blank">Apple Developer Account</a></li>
  <li>Enroll, create a Service ID, and generate a key.</li>
  <li>Set redirect URI: <code>https://yourdomain.com/oauth/apple.php?action=callback</code></li>
  <li>Copy your Client ID, Team ID, Key ID, and download the private key file (.p8).</li>
  <li>Edit your config:
<pre><code class="help-code-block">"apple": {
  "enabled": true,
  "client_id": "YOUR_APPLE_CLIENT_ID",
  "team_id": "YOUR_APPLE_TEAM_ID",
  "key_id": "YOUR_APPLE_KEY_ID",
  "private_key_path": "C:\\var\\data\\mediabrain\\AuthKey_YOUR_KEY_ID.p8",
  "scopes": ["name", "email"]
}</code></pre></li>
</ol>

<p>After setup, your login and contact forms will support Google, Facebook, and Apple authentication. For more details, see <code>html/includes/OAuthHandler.php</code> and <code>apps/help/docs/oauth-provider-setup.md</code>.</p>
