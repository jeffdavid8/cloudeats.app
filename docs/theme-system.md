# MediaBrain Theme System Documentation

## Overview

The MediaBrain Theme System provides a comprehensive solution for managing visual themes across the entire MediaBrain application. It supports dynamic theme switching, user preferences, admin management, and site-wide customization.

## Features

- **Multiple Theme Support**: Built-in default and Star Trek LCARS themes
- **User Preferences**: Individual users can select their preferred theme
- **Admin Management**: Administrators can set system defaults and manage themes
- **Dynamic Switching**: Themes can be changed without page reload (with optional refresh)
- **Theme Inheritance**: Themes support CSS variables and component inheritance
- **API Integration**: RESTful API for theme management
- **Responsive Design**: All themes support mobile and desktop layouts

## Architecture

### Core Components

1. **ThemeManager.php** - Central theme management class
2. **Theme Configuration Files** - JSON files defining theme properties
3. **Theme Assets** - CSS and JavaScript files for each theme
4. **API Handler** - RESTful API for theme operations
5. **User Interface** - Admin and user-facing theme selectors

### File Structure

```
html/
├── includes/theme/
│   └── ThemeManager.php           # Core theme management
├── css/themes/
│   ├── default/                   # Default MediaBrain theme
│   │   ├── theme.json            # Theme configuration
│   │   ├── dashboard.css         # Dashboard-specific styles
│   │   ├── components.css        # Reusable component styles
│   │   └── theme.js             # Theme JavaScript enhancements
│   └── startrek/                  # Star Trek LCARS theme
│       ├── theme.json            # Theme configuration
│       ├── lcars-base.css        # Base LCARS styling
│       ├── dashboard.css         # Dashboard adaptations
│       ├── components.css        # LCARS component styles
│       ├── animations.css        # LCARS animations
│       ├── theme.js             # LCARS JavaScript
│       └── lcars-effects.js      # Advanced LCARS effects
├── apps/admin/
│   ├── api/theme-api.php         # Theme API endpoints
│   └── views/
│       ├── dashboard.php         # Theme-enabled dashboard
│       └── settings.php          # Theme management interface
└── storage/user_preferences/
    └── theme_preferences.json    # User theme preferences storage
```

## Theme Configuration

### Theme Manifest (theme.json)

Each theme requires a `theme.json` configuration file:

```json
{
    "name": "Theme Display Name",
    "description": "Theme description for users",
    "author": "Theme Author",
    "version": "1.0",
    "category": "Theme Category",
    "preview_image": "preview.png",
    "css_files": [
        "main.css",
        "components.css"
    ],
    "js_files": [
        "theme.js"
    ],
    "variables": {
        "primary_color": "#2196F3",
        "secondary_color": "#FF9800",
        "background_color": "#f5f5f5"
    },
    "features": [
        "Feature 1",
        "Feature 2"
    ]
}
```

### Required Properties

- **name**: Human-readable theme name
- **description**: Brief description of the theme
- **author**: Theme creator
- **version**: Theme version number

### Optional Properties

- **category**: Theme category (Professional, Futuristic, etc.)
- **preview_image**: Preview image filename
- **css_files**: Array of CSS files to include
- **js_files**: Array of JavaScript files to include
- **variables**: CSS custom properties and theme variables
- **features**: Array of theme features

## Creating a New Theme

### Step 1: Create Theme Directory

```bash
mkdir html/css/themes/mytheme
```

### Step 2: Create Theme Configuration

Create `html/css/themes/mytheme/theme.json`:

```json
{
    "name": "My Custom Theme",
    "description": "A custom theme for MediaBrain",
    "author": "Your Name",
    "version": "1.0",
    "category": "Custom",
    "css_files": ["main.css"],
    "variables": {
        "primary_color": "#673AB7",
        "secondary_color": "#FF5722"
    }
}
```

### Step 3: Create Theme Styles

Create `html/css/themes/mytheme/main.css`:

```css
:root {
    --primary-color: #673AB7;
    --secondary-color: #FF5722;
}

/* Your theme styles here */
body {
    background-color: var(--background-color);
    color: var(--text-color);
}

.card {
    border: 2px solid var(--primary-color);
    border-radius: 8px;
}
```

### Step 4: Add Theme JavaScript (Optional)

Create `html/css/themes/mytheme/theme.js`:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    console.log('My Custom Theme loaded!');
    
    // Theme-specific JavaScript here
});
```

## Using the Theme System

### In PHP Applications

```php
// Initialize theme manager
require_once __DIR__ . '/includes/theme/ThemeManager.php';
$themeManager = new ThemeManager();

// Get current theme
$currentTheme = $themeManager->getCurrentTheme();

// Include theme CSS
echo $themeManager->getThemeCSS();

// Include theme JavaScript
echo $themeManager->getThemeJS();
```

### In HTML Templates

```php
<!-- Include theme styles -->
<?php echo $themeManager->getThemeCSS(); ?>

<!-- Apply theme variables -->
<?php echo $themeManager->applyThemeVariables($templateContent); ?>

<!-- Include theme JavaScript -->
<?php echo $themeManager->getThemeJS(); ?>
```

### JavaScript API

```javascript
// Switch theme
window.ThemeSystem.switchTheme('startrek');

// Get available themes
window.ThemeSystem.getAvailableThemes();

// Theme-specific notifications
if (window.StarTrekTheme) {
    StarTrekTheme.showNotification('LCARS Online', 'success');
    StarTrekTheme.speak('Welcome to the LCARS interface');
}
```

## Built-in Themes

### Default Theme

**Features:**
- Material Design components
- Responsive layout
- Professional color scheme
- Smooth animations
- Enhanced dashboard cards

**Files:**
- `dashboard.css` - Dashboard enhancements
- `components.css` - Reusable components
- `theme.js` - JavaScript enhancements

### Star Trek LCARS Theme

**Features:**
- Authentic LCARS interface design
- Futuristic glowing elements
- Animated scanning lines
- Sound effects (optional)
- Computer voice synthesis
- Advanced visual effects

**Files:**
- `lcars-base.css` - Base LCARS styling
- `dashboard.css` - Dashboard adaptations
- `components.css` - LCARS components
- `animations.css` - LCARS animations
- `theme.js` - Core LCARS functionality
- `lcars-effects.js` - Advanced effects

**Special Features:**
- Keyboard shortcuts (Ctrl+Alt+L, Ctrl+Alt+A, Ctrl+Alt+S)
- Mouse trail effects
- Click animations
- Voice synthesis integration
- System alert modes

## API Reference

### Endpoints

All theme API endpoints are available under `?app=admin&api=themes`:

#### GET /themes
Get available themes and current selection.

**Response:**
```json
{
    "success": true,
    "data": {
        "themes": {...},
        "current_theme": "default"
    }
}
```

#### POST /switch-theme
Switch user's theme.

**Request:**
```json
{
    "theme": "startrek",
    "persistent": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Theme switched successfully",
    "new_theme": "startrek"
}
```

#### POST /reset-theme
Reset user's theme to default.

**Response:**
```json
{
    "success": true,
    "message": "Theme reset successfully",
    "new_theme": "default"
}
```

#### POST /set-system-default
Set system default theme (admin only).

**Request:**
```json
{
    "action": "set_system_default",
    "theme": "default"
}
```

## User Interface

### Quick Theme Selector

Press `Ctrl+Alt+T` anywhere in the admin interface to open the quick theme selector modal.

### Admin Settings

Navigate to **Admin → Settings → Theme Management** to:
- View all available themes
- Set system default theme
- Change personal theme
- Preview themes before applying
- Reset themes to defaults

### Theme Gallery

The admin settings include a visual theme gallery showing:
- Theme previews
- Theme descriptions
- Author information
- Version numbers
- Current selection status

## User Preferences

### Storage

User theme preferences are stored in:
```
storage/user_preferences/theme_preferences.json
```

### Structure

```json
{
    "username1": {
        "theme": "startrek"
    },
    "username2": {
        "theme": "default"
    },
    "_system": {
        "default_theme": "default"
    }
}
```

## Advanced Features

### Theme Variables

Themes can define CSS custom properties that are automatically applied:

```css
:root {
    --primary-color: #2196F3;
    --secondary-color: #FF9800;
    --border-radius: 8px;
}
```

### Template Variables

Use template variables in content:

```php
$content = "Welcome to {{theme.name}}!";
$processedContent = $themeManager->applyThemeVariables($content);
```

### Theme Inheritance

Themes can extend base styles:

```css
/* Base theme styles */
@import url('../default/components.css');

/* Theme-specific overrides */
.card {
    border-color: var(--accent-color);
}
```

## Troubleshooting

### Common Issues

1. **Theme not loading**
   - Check file permissions
   - Verify theme.json syntax
   - Ensure CSS/JS files exist

2. **Styles not applying**
   - Clear browser cache
   - Check CSS syntax
   - Verify file paths

3. **JavaScript errors**
   - Check browser console
   - Verify JS syntax
   - Check for conflicts

### Debug Mode

Enable debug mode by adding to theme configuration:

```json
{
    "debug": true,
    "debug_console": true
}
```

## Best Practices

### Theme Development

1. **Use CSS Custom Properties**: Define colors and values as CSS variables
2. **Follow Responsive Design**: Ensure themes work on all devices
3. **Test Thoroughly**: Test on different browsers and screen sizes
4. **Document Features**: Include comprehensive documentation
5. **Version Control**: Use semantic versioning for theme updates

### Performance

1. **Optimize CSS**: Minimize CSS file sizes
2. **Optimize Images**: Compress preview images
3. **Lazy Load**: Load theme assets only when needed
4. **Cache Assets**: Enable browser caching for theme files

### Accessibility

1. **Color Contrast**: Ensure sufficient color contrast
2. **Focus States**: Provide clear focus indicators
3. **Screen Readers**: Include proper ARIA labels
4. **Keyboard Navigation**: Support keyboard-only navigation

## Contributing

### Adding New Themes

1. Fork the repository
2. Create a new theme following the structure above
3. Test thoroughly across different pages
4. Submit a pull request with documentation

### Theme Guidelines

- Follow existing naming conventions
- Include comprehensive documentation
- Ensure cross-browser compatibility
- Test with all MediaBrain apps
- Include preview images

## Support

For theme-related issues:

1. Check this documentation
2. Review the built-in themes for examples
3. Test with default theme to isolate issues
4. Submit issues with detailed reproduction steps

## License

The MediaBrain Theme System is released under the same license as MediaBrain itself. Individual themes may have their own licenses - check theme.json for license information.