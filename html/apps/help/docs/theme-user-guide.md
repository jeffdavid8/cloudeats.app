# MediaBrain Theme System User Guide

## Overview
This guide explains how to use the MediaBrain theme system for styling your apps, including CSS, JS, and audio assets. Themes are organized in `/themes/` and can be overridden at the app level. Utility functions in `util.php` make including theme assets easy and consistent.

---

## 1. Theme Folder Structure

All themes are stored in `/themes/`:
```
/themes/
  default/
    components.css
    dashboard.css
    theme.js
    audio/
      click.mp3
      notify.mp3
  startrek/
    components.css
    dashboard.css
    lcars-base.css
    theme.js
    audio/
      beep.mp3
      warp.mp3
  utilities.css
```

You can override any theme file in your app by placing it in:
```
/apps/[yourapp]/themes/[theme]/[file]
```

---

## 2. Including Theme CSS

Use the utility function in `util.php`:
```php
include_theme_css('default', ['components.css', 'dashboard.css']);
include_theme_css('startrek', ['components.css', 'lcars-base.css']);
```
This will output the correct `<link>` tags for your theme CSS files.

---

## 3. Including Theme JS

Use:
```php
include_theme_js('default', ['theme.js']);
include_theme_js('startrek', ['theme.js']);
```
This will output the correct `<script>` tags for your theme JS files.

---

## 4. Including Theme Audio

To include theme audio files:
```php
include_theme_audio('default', ['click', 'notify']); // Loads click.mp3 and notify.mp3
include_theme_audio('startrek', ['beep', 'warp']);   // Loads beep.mp3 and warp.mp3
```
This will output `<audio>` tags for each file. You can use JavaScript to play these sounds as needed.

---

## 5. Overriding Theme Files in Apps

To override a theme file for a specific app, place your file in:
```
/apps/[yourapp]/themes/[theme]/[file]
```
When you use `get_theme_file($theme, $file, $app)`, it will return the app override if it exists, otherwise the global theme file.

Example:
```php
$audioPath = get_theme_file('default', 'audio/click.mp3', 'myapp');
// Returns /apps/myapp/themes/default/audio/click.mp3 if it exists, else /themes/default/audio/click.mp3
```

---

## 6. Using Utility Classes

All themes support a set of utility classes for consistent styling:
- `.mb-btn`, `.mb-btn-primary`, `.mb-btn-accent` (buttons)
- `.mb-card`, `.mb-card-content`, `.mb-card-title` (cards)
- `.mb-panel`, `.mb-panel-primary`, `.mb-panel-dark` (panels)
- `.mb-alert`, `.mb-alert-info`, `.mb-alert-error` (alerts)
- `.mb-nav`, `.mb-nav-link`, `.mb-nav-active` (navigation)
- `.mb-input`, `.mb-select` (forms)
- `.mb-heading`, `.mb-text`, `.mb-text-muted` (typography)
- `.mb-row`, `.mb-col-6`, `.mb-m-2`, `.mb-p-2` (layout/spacing)

Example usage:
```html
<div class="mb-card">
  <div class="mb-card-content">
    <h3 class="mb-card-title">Welcome</h3>
    <p class="mb-text">This card uses theme utility classes.</p>
    <button class="mb-btn mb-btn-primary">Click Me</button>
  </div>
</div>
```

---

## 7. Playing Theme Audio

To play a theme audio file in JavaScript:
```html
<audio id="theme-click" src="/themes/default/audio/click.mp3" preload="auto"></audio>
<button onclick="document.getElementById('theme-click').play()">Play Click</button>
```
Or use the path from PHP:
```php
$audioPath = get_theme_file('default', 'audio/click.mp3');
```

---

## 8. Example: Full Theme Usage in a View

```php
// In your app view file
include_theme_css('default', ['components.css', 'dashboard.css']);
include_theme_js('default', ['theme.js']);
include_theme_audio('default', ['click', 'notify']);

// Use utility classes in HTML
<div class="mb-panel mb-panel-primary mb-p-2">
  <h2 class="mb-heading mb-heading-2">Dashboard</h2>
  <button class="mb-btn mb-btn-accent" onclick="document.getElementById('theme-click').play()">Notify</button>
</div>
<audio id="theme-click" src="/themes/default/audio/click.mp3" preload="auto"></audio>
```

---

## 9. Best Practices
- Always use the utility functions for including theme assets.
- Use utility classes for consistent styling across themes.
- Place overrides in your app's `/themes/[theme]/` folder.
- Keep audio filenames consistent for easy overrides.
- Test your app with multiple themes for compatibility.

---

## 10. Troubleshooting
- If a theme file isn't loading, check the path and file location.
- Use `get_theme_file()` to resolve the correct file path with override logic.
- Make sure your app's theme folder matches the global theme structure.

---

## 11. Advanced: Custom Themes
To add a new theme:
1. Create `/themes/[newtheme]/` and add your CSS, JS, and audio files.
2. Use `include_theme_css('newtheme', [...])` and other utility functions as above.
3. Add overrides in `/apps/[yourapp]/themes/[newtheme]/` as needed.

---

## 12. Reference: Utility Functions
- `include_theme_css($theme, $files)`
- `include_theme_js($theme, $files)`
- `include_theme_audio($theme, $files, $type)`
- `get_theme_file($theme, $file, $app = null)`

See `includes/util.php` for implementation details.

---

## 13. Need Help?
Contact the MediaBrain development team or see the code comments in `util.php` for more info.
