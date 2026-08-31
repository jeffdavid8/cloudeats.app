# Developer Theme Reference

## Theme Utilities
- `include_theme_css($theme, $app = null)`: Includes CSS for a theme, with app-level override support.
- `include_theme_js($theme, $app = null)`: Includes JS for a theme, with app-level override support.
- `include_theme_audio($theme, $app = null)`: Includes audio assets for a theme, with app-level override support.
- `get_theme_file($theme, $type, $filename, $app = null)`: Returns the path to a theme asset, checking for app override first.

## Theme File Structure
- Global themes: `/themes/{theme}/[css|js|audio]/`
- App overrides: `/apps/{app}/themes/{theme}/[css|js|audio]/`

## Usage Example
```php
// Include theme CSS
include_theme_css('materialize');

// Include theme JS for a specific app
include_theme_js('materialize', 'myapp');

// Get theme audio file path
$audioPath = get_theme_file('materialize', 'audio', 'notify.mp3', 'myapp');
```

## Best Practices
- Store shared theme assets in `/themes/`
- Use app-level overrides for customizations
- Reference theme assets using utility functions for consistency
- Document new themes and overrides in markdown files

## Troubleshooting
- If a theme asset is missing, check both global and app-level paths
- Ensure asset filenames match expected conventions
- Use the help app for updated documentation and guides

---
See also: `theme-user-guide.md`, `theme-utilities-guide.md`, `theme-system-roadmap.md`, `theme-system.md`