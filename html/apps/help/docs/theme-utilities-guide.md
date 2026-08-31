# MediaBrain Theme Utilities Reference

This guide provides a quick reference for all theme utility classes and functions available in MediaBrain. Use these for consistent, theme-aware styling and asset inclusion.

---

## Utility Functions (PHP)

- `include_theme_css($theme, $files)`
- `include_theme_js($theme, $files)`
- `include_theme_audio($theme, $files, $type)`
- `get_theme_file($theme, $file, $app = null)`

See `includes/util.php` for implementation details.

---

## Utility Classes (CSS)

### Buttons
- `.mb-btn`, `.mb-btn-primary`, `.mb-btn-secondary`, `.mb-btn-accent`, `.mb-btn-large`, `.mb-btn-flat`

### Cards
- `.mb-card`, `.mb-card-content`, `.mb-card-title`, `.mb-card-action`, `.mb-card-stat`

### Panels
- `.mb-panel`, `.mb-panel-primary`, `.mb-panel-secondary`, `.mb-panel-accent`, `.mb-panel-dark`, `.mb-panel-light`

### Alerts
- `.mb-alert`, `.mb-alert-info`, `.mb-alert-success`, `.mb-alert-warning`, `.mb-alert-error`

### Navigation
- `.mb-nav`, `.mb-nav-item`, `.mb-nav-link`, `.mb-nav-active`

### Forms
- `.mb-input`, `.mb-select`, `.mb-checkbox`, `.mb-radio`

### Typography
- `.mb-heading`, `.mb-heading-1`, `.mb-heading-2`, `.mb-heading-3`, `.mb-text`, `.mb-text-muted`, `.mb-text-small`, `.mb-text-large`

### Layout & Spacing
- `.mb-container`, `.mb-row`, `.mb-col`, `.mb-col-1` ... `.mb-col-12`, `.mb-m-0` ... `.mb-m-4`, `.mb-p-0` ... `.mb-p-4`

### Display & Responsive
- `.mb-hide`, `.mb-show`, `.mb-flex`, `.mb-inline`, `.mb-inline-block`, `.mb-hide-mobile`, `.mb-show-mobile`, `.mb-hide-desktop`, `.mb-show-desktop`, `.mb-col-mobile-12`, `.mb-col-mobile-6`

### Effects & Animation
- `.mb-hover-lift`, `.mb-hover-glow`, `.mb-hover-scale`, `.mb-fade-in`, `.mb-slide-in`, `.mb-active`, `.mb-disabled`, `.mb-loading`

---

## Example Usage
```html
<div class="mb-card mb-hover-lift">
  <div class="mb-card-content">
    <h3 class="mb-card-title">Example Card</h3>
    <p class="mb-text">This uses theme utility classes.</p>
    <button class="mb-btn mb-btn-primary">Action</button>
  </div>
</div>
```

---

## Best Practices
- Use utility classes for all theme-aware UI elements.
- Include theme assets using the provided PHP functions.
- Override theme files in your app as needed for customization.
- Test your UI with multiple themes for compatibility.

---

## For More Information
See the full user guide in `theme-user-guide.md` or contact the MediaBrain development team.