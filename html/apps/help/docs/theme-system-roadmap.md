# MediaBrain Theme System Roadmap

This roadmap outlines the evolution and future plans for the MediaBrain theme system, including file organization, utility classes, overrides, and developer support.

---

## 1. Theme File Organization
- All themes are stored in `/themes/`.
- Apps can override any theme file in `/apps/[app]/themes/[theme]/[file]`.

## 2. Utility Classes
- Standardized CSS classes for buttons, cards, panels, alerts, navigation, forms, typography, layout, spacing, and effects.
- Consistent usage across all themes.

## 3. Asset Types
- CSS, JS, and audio files supported.
- Audio files can be played for UI feedback and are overridable per app.

## 4. PHP Utility Functions
- `include_theme_css`, `include_theme_js`, `include_theme_audio`, `get_theme_file`.
- Simplifies asset inclusion and override logic.

## 5. Developer Documentation
- User guide and utilities reference available in help app docs.
- Example usage and best practices included.

## 6. Future Enhancements
- Add more themes and expand utility class coverage.
- Improve developer tooling for theme previews and testing.
- Integrate theme documentation into help app navigation and search.

---

## For Developers
- See `theme-user-guide.md` and `theme-utilities-guide.md` for details.
- Use utility functions and classes for all theme-related development.
- Place overrides in your app's theme folder for customization.
- Contact the MediaBrain team for support or suggestions.