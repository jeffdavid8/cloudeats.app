# Theme Include Patterns and Overrides

## Rendering Views with Theme Assets

To render a view with theme assets, use the utility functions in `util.php`:

### Example: Rendering a Dashboard View
```php
// In your view file
include_theme_css('default', ['components.css', 'dashboard.css']);
include_theme_js('default', ['theme.js']);
include_theme_audio('default', ['click', 'notify']);
render('dashboard.php', ['user' => $user]);
```
- `include_theme_css`, `include_theme_js`, and `include_theme_audio` output the correct HTML tags for theme assets.
- `render($filename, $vars, $return, $cascading)` loads the view, passing variables and supporting cascading overrides.

## How Theme Asset Overrides Work

When you request a theme asset, the system checks for app-level overrides first:

### Override Logic
```php
$audioPath = get_theme_file('default', 'audio/click.mp3', 'myapp');
// Returns /apps/myapp/themes/default/audio/click.mp3 if it exists
// Otherwise returns /themes/default/audio/click.mp3
```
- Place override files in `/apps/{app}/themes/{theme}/` to customize theme assets for a specific app.
- If no override exists, the global theme asset in `/themes/{theme}/` is used.

## Detailed Usage for New Themes

1. **Create a Theme Directory:**
   - `/themes/{theme}/` for global assets
   - `/apps/{app}/themes/{theme}/` for app-specific overrides
2. **Add CSS, JS, and audio files to your theme directory.**
3. **Use utility functions to include assets in your views.**
   - Example:
     ```php
     include_theme_css('startrek', ['components.css', 'lcars-base.css']);
     include_theme_js('startrek', ['theme.js']);
     include_theme_audio('startrek', ['beep', 'warp']);
     ```
4. **Override any asset by placing a file in your app's theme directory.**
5. **Use utility classes in your HTML for consistent styling.**
   - Example:
     ```html
     <button class="mb-btn mb-btn-primary">Action</button>
     <div class="mb-card mb-card-stat">...</div>
     ```

## Best Practices
- Always use the utility functions for asset inclusion.
- Document overrides and customizations in your app's README or help docs.
- Test your app with multiple themes to ensure compatibility.

---
For more details, see the Theme User Guide and Developer Theme Reference in the help app.