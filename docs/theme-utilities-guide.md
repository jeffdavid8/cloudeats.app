# MediaBrain Theme Utilities Framework

## Overview

The MediaBrain Theme Utilities Framework provides a **Materialize CSS-like** approach to theming across your entire MediaBrain application. Instead of complex inheritance systems, it offers simple, consistent utility classes that automatically adapt to the active theme.

## 🎯 Philosophy

- **Utility-First**: Use predefined CSS classes in your HTML
- **Theme-Aware**: Same classes, different visual treatments per theme
- **Developer-Friendly**: Similar to popular frameworks like Materialize CSS
- **Consistent**: Predictable behavior across all themes

## 🚀 Quick Start

### 1. Include Theme CSS
The utilities are automatically loaded with any theme:

```php
<?php
require_once __DIR__ . '/includes/theme/ThemeManager.php';
$themeManager = new ThemeManager();
echo $themeManager->getThemeCSS(); // Includes utilities.css + theme CSS
?>
```

### 2. Use Utility Classes in HTML
```html
<!-- Cards -->
<div class="mb-card">
    <div class="mb-card-content">
        <h3 class="mb-card-title">Card Title</h3>
        <p class="mb-text">Card content goes here.</p>
    </div>
    <div class="mb-card-action">
        <button class="mb-btn mb-btn-primary">Action</button>
    </div>
</div>

<!-- Panels -->
<div class="mb-panel mb-panel-primary mb-hover-glow">
    <h4 class="mb-heading">Panel Title</h4>
    <p class="mb-text mb-text-muted">Panel content</p>
</div>
```

### 3. Theme Switching
```javascript
// Switch themes at runtime
fetch('/?app=admin&api=themes', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'switch',
        theme: 'startrek' // or 'default'
    })
});
```

## 📦 Available Components

### Buttons (.mb-btn)
```html
<button class="mb-btn">Default Button</button>
<button class="mb-btn mb-btn-primary">Primary Button</button>
<button class="mb-btn mb-btn-secondary">Secondary Button</button>
<button class="mb-btn mb-btn-accent">Accent Button</button>
<button class="mb-btn mb-btn-large">Large Button</button>
<button class="mb-btn mb-btn-flat">Flat Button</button>
```

**Theme Adaptation:**
- **Default Theme**: Material Design styling with smooth transitions
- **Star Trek LCARS**: Orange/blue gradients, glowing effects, Orbitron font

### Cards (.mb-card)
```html
<div class="mb-card">
    <div class="mb-card-content">
        <h3 class="mb-card-title">Title</h3>
        <p class="mb-text">Content</p>
    </div>
    <div class="mb-card-action">
        <button class="mb-btn">Action</button>
    </div>
</div>

<!-- Statistics Card -->
<div class="mb-card mb-card-stat">
    <h3 class="mb-heading mb-heading-3">42</h3>
    <p class="mb-text mb-text-muted">Users Online</p>
</div>
```

### Panels (.mb-panel)
```html
<div class="mb-panel">Basic Panel</div>
<div class="mb-panel mb-panel-primary">Primary Panel</div>
<div class="mb-panel mb-panel-secondary">Secondary Panel</div>
<div class="mb-panel mb-panel-accent">Accent Panel</div>
<div class="mb-panel mb-panel-dark">Dark Panel</div>
<div class="mb-panel mb-panel-light">Light Panel</div>
```

### Alerts (.mb-alert)
```html
<div class="mb-alert mb-alert-info">Info message</div>
<div class="mb-alert mb-alert-success">Success message</div>
<div class="mb-alert mb-alert-warning">Warning message</div>
<div class="mb-alert mb-alert-error">Error message</div>
```

### Navigation (.mb-nav)
```html
<nav class="mb-nav">
    <div class="mb-nav-item">
        <a href="#" class="mb-nav-link mb-nav-active">Home</a>
    </div>
    <div class="mb-nav-item">
        <a href="#" class="mb-nav-link">Dashboard</a>
    </div>
</nav>
```

### Form Elements
```html
<input type="text" class="mb-input" placeholder="Text input">
<select class="mb-select">
    <option>Option 1</option>
</select>
```

## 🎨 Theme Variations

### Default Theme
- **Colors**: Material Design blue (#2196F3), professional grays
- **Style**: Clean, modern, subtle shadows
- **Typography**: Roboto font family
- **Effects**: Gentle hover animations, soft shadows

### Star Trek LCARS Theme
- **Colors**: LCARS orange (#ff9900), blue (#6699ff), green (#66ff99)
- **Style**: Futuristic panels, glowing borders, sharp angles
- **Typography**: Orbitron monospace font
- **Effects**: Glow effects, scanning animations, sci-fi sounds

## 🔧 Layout System

### Grid System
```html
<div class="mb-container">
    <div class="mb-row">
        <div class="mb-col-6">Half width</div>
        <div class="mb-col-6">Half width</div>
    </div>
    <div class="mb-row">
        <div class="mb-col-4">One third</div>
        <div class="mb-col-8">Two thirds</div>
    </div>
</div>
```

### Spacing Utilities
```html
<!-- Margin -->
<div class="mb-m-0">No margin</div>
<div class="mb-m-1">Small margin (8px)</div>
<div class="mb-m-2">Medium margin (16px)</div>
<div class="mb-m-3">Large margin (24px)</div>

<!-- Padding -->
<div class="mb-p-1">Small padding</div>
<div class="mb-p-2">Medium padding</div>
```

### Typography
```html
<h1 class="mb-heading mb-heading-1">Large Heading</h1>
<h2 class="mb-heading mb-heading-2">Medium Heading</h2>
<h3 class="mb-heading mb-heading-3">Small Heading</h3>
<p class="mb-text">Body text</p>
<p class="mb-text mb-text-muted">Muted text</p>
<p class="mb-text mb-text-small">Small text</p>
```

## ✨ Interactive Effects

### Hover Effects
```html
<div class="mb-card mb-hover-lift">Lift on hover</div>
<div class="mb-panel mb-hover-glow">Glow on hover</div>
<div class="mb-card mb-hover-scale">Scale on hover</div>
```

### Animations
```html
<div class="mb-alert mb-fade-in">Fade in animation</div>
<div class="mb-card mb-slide-in">Slide in animation</div>
```

### Loading States
```html
<button class="mb-btn mb-loading">Loading button</button>
<div class="mb-card mb-disabled">Disabled card</div>
```

## 📱 Responsive Design

### Responsive Utilities
```html
<!-- Hide/show based on screen size -->
<div class="mb-hide-mobile">Hidden on mobile</div>
<div class="mb-show-mobile">Visible on mobile only</div>
<div class="mb-hide-desktop">Hidden on desktop</div>

<!-- Responsive columns -->
<div class="mb-col-12 mb-col-mobile-6">Full width, half on mobile</div>
```

## 🔌 User Preferences

### Admin Configuration
Administrators can set site-wide default themes in the admin panel:
1. Go to **Admin → Settings → Themes**
2. Select global default theme
3. Configure per-app overrides (future feature)

### User Preferences
Users can override themes:
```javascript
// Save user preference
fetch('/?app=admin&api=themes', {
    method: 'POST',
    body: JSON.stringify({
        action: 'switch',
        theme: 'startrek',
        save_preference: true
    })
});
```

## 🏗️ Development Usage

### In Your App Views
```php
<!-- In any app view file -->
<div class="mb-container">
    <div class="mb-card">
        <div class="mb-card-content">
            <h2 class="mb-card-title">My App Content</h2>
            <p class="mb-text">This automatically adapts to the active theme.</p>
            <button class="mb-btn mb-btn-primary">Take Action</button>
        </div>
    </div>
</div>
```

### In JavaScript
```javascript
// Theme-aware JavaScript
if (document.body.classList.contains('theme-startrek')) {
    // LCARS-specific behavior
    console.log('Engaging warp drive...');
} else {
    // Default theme behavior
    console.log('Standard operation mode');
}
```

## 🎬 Demo

Visit `/theme-demo.php` to see all components in action with theme switching capabilities.

## 🔄 Theme Resolution Order

1. **User Preference** (highest priority)
2. **Session Override** (temporary)
3. **Admin Default** (site-wide setting)
4. **System Default** (fallback to 'default' theme)

## 🚀 Extending the System

### Adding New Utility Classes
1. Add base class in `utilities.css`
2. Implement styling in each theme's `components.css`
3. Document usage examples

### Creating New Themes
1. Create theme directory: `/css/themes/mytheme/`
2. Implement utility class styling in `components.css`
3. Register theme in `ThemeManager.php`

## 📖 Best Practices

1. **Use Semantic Classes**: Prefer `.mb-btn-primary` over custom styling
2. **Test Both Themes**: Ensure components work in both Default and LCARS
3. **Leverage Effects**: Use hover classes for better UX
4. **Stay Consistent**: Follow the established naming patterns
5. **Document Custom Usage**: When extending, maintain documentation

This approach gives you the power of a full theming system with the simplicity of utility classes, making it easy to create theme-aware interfaces throughout your MediaBrain application.