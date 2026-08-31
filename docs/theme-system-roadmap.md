# MediaBrain Enhanced Theme System Roadmap

## Vision: Site-Wide Theme Framework

Transform the current admin-only theme system into a comprehensive, site-wide theming framework that provides:

1. **Global Theme Management** - Admin-configurable site defaults
2. **App-Level Overrides** - Per-app theme customization
3. **Markup-Based Utilities** - CSS classes for theme-aware styling
4. **Theme Inheritance** - Cascading theme resolution
5. **Developer-Friendly APIs** - Easy integration for developers

## Current State (✅ Completed)

- ThemeManager class with user preferences
- Default and Star Trek LCARS themes
- Admin dashboard integration
- Theme switching API
- Basic CSS compilation and loading

## Phase 1: Site-Wide Foundation (🎯 Priority 1)

### 1.1 Global Theme Configuration
**Goal**: Allow admins to set site-wide default theme

**Implementation**:
```php
// In Admin Settings
class GlobalThemeSettings {
    public function setSiteDefaultTheme($themeName);
    public function getSiteDefaultTheme();
    public function getThemeInheritanceChain($app, $page, $user);
}
```

**Files to modify**:
- `ThemeManager.php` - Add global theme support
- `admin/views/settings.php` - Add global theme section
- `includes/App.php` - Integrate theme loading in app initialization

### 1.2 Theme Resolution Engine
**Goal**: Implement cascade: Global → App → Page → User

**Logic**:
```
1. Check user preference (if logged in)
2. Check page-level override (if set)
3. Check app-level override (if configured)
4. Fall back to global default
5. Fall back to 'default' theme
```

### 1.3 Auto-Loading Integration
**Goal**: Themes load automatically on every page

**Integration Points**:
- `includes/App.php` constructor
- Base template/layout files
- CSS/JS inclusion in `<head>`

## Phase 2: App-Level Overrides (🎯 Priority 2)

### 2.1 App Configuration Files
**Goal**: Each app can specify its preferred theme

**Structure**:
```
html/apps/[app]/
  ├── theme.json          # App theme configuration
  ├── css/themes/         # App-specific theme overrides
  └── views/themes/       # App-specific theme templates
```

**Example `theme.json`**:
```json
{
  "default_theme": "startrek",
  "allowed_themes": ["default", "startrek", "custom"],
  "theme_overrides": {
    "startrek": {
      "primary_color": "#ff6600",
      "custom_css": "app-specific-lcars.css"
    }
  }
}
```

### 2.2 Theme Override System
**Goal**: Apps can customize existing themes

**Features**:
- CSS variable overrides
- Additional CSS files
- Custom theme variants
- App-specific color schemes

## Phase 3: Markup-Based Theme Utilities (🎯 Priority 3)

### 3.1 CSS Utility Classes
**Goal**: Developers can use theme-aware classes in HTML

**Examples**:
```html
<!-- Theme-aware components -->
<div class="theme-card">Content</div>
<button class="theme-btn theme-btn-primary">Action</button>
<nav class="theme-nav">Navigation</nav>

<!-- Theme-responsive elements -->
<section class="theme-panel theme-glow">
  <h2 class="theme-heading">Title</h2>
  <p class="theme-text">Content</p>
</section>

<!-- Theme state classes -->
<div class="theme-active theme-scanning">
  Animated element
</div>
```

### 3.2 JavaScript Theme API
**Goal**: Runtime theme interaction

**API**:
```javascript
// Theme switching
MediaBrain.Theme.switch('startrek');

// Theme detection
if (MediaBrain.Theme.is('startrek')) {
  // LCARS-specific behavior
}

// Theme events
MediaBrain.Theme.on('change', function(newTheme) {
  // Handle theme change
});

// Component theming
MediaBrain.Theme.applyTo('.my-component', {
  variant: 'primary',
  effects: ['glow', 'scan']
});
```

## Phase 4: Advanced Features (🎯 Priority 4)

### 4.1 Theme Builder Interface
**Goal**: Visual theme customization

### 4.2 Theme Marketplace
**Goal**: Community themes and sharing

### 4.3 Real-Time Preview
**Goal**: Live theme preview without page reload

## Implementation Priority

### Week 1: Foundation
- [ ] Enhance ThemeManager for site-wide support
- [ ] Add global theme admin interface
- [ ] Integrate theme loading in App.php

### Week 2: App Overrides
- [ ] Create app theme configuration system
- [ ] Implement theme inheritance cascade
- [ ] Add app-specific theme settings

### Week 3: Markup Utilities
- [ ] Create theme CSS utility framework
- [ ] Add JavaScript theme API
- [ ] Create developer documentation

### Week 4: Polish & Testing
- [ ] Comprehensive testing across all apps
- [ ] Performance optimization
- [ ] Final documentation

## Technical Considerations

### Performance
- Theme CSS compilation and caching
- Minimize HTTP requests
- Lazy loading for theme assets

### Compatibility
- Backward compatibility with existing themes
- Progressive enhancement approach
- Fallback mechanisms

### Maintainability
- Clear separation of concerns
- Consistent naming conventions
- Comprehensive documentation

## Success Metrics

1. **Developer Adoption**: Easy theme integration in new apps
2. **User Experience**: Seamless theme switching site-wide
3. **Performance**: No significant impact on page load times
4. **Flexibility**: Easy customization for specific use cases

---

This roadmap provides a clear path from the current admin-only theme system to a comprehensive, site-wide theming framework that rivals modern CSS frameworks while maintaining the unique MediaBrain aesthetic.