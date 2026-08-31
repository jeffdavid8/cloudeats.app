# MediaBrain Theme System Overview

The MediaBrain theme system provides a flexible, organized way to style your apps using CSS, JS, and audio assets. Themes are stored in `/themes/` and can be overridden at the app level. Utility functions in `util.php` make asset inclusion and overrides easy.

---

## Key Features
- Organized theme folders for all assets
- Utility classes for consistent UI styling
- Audio support for UI feedback
- App-level overrides for customization
- Simple PHP functions for asset inclusion

---

## How It Works
- Include theme assets using `include_theme_css`, `include_theme_js`, and `include_theme_audio`.
- Use utility classes in your HTML for theme-aware styling.
- Override any theme file in your app's `/themes/[theme]/` folder.
- Use `get_theme_file` to resolve the correct asset path with override logic.

---

## Example
```php
include_theme_css('default', ['components.css']);
include_theme_audio('default', ['click']);
$audioPath = get_theme_file('default', 'audio/click.mp3', 'myapp');
```

---

## For More Information
- See the user guide and utilities reference in the help app docs.
- Contact the MediaBrain team for support.