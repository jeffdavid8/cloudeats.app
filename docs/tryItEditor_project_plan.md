## [2025-11-13] Export/Import Project (JSON & ZIP)

- Added export/import feature supporting both JSON and ZIP formats.
- User can select preferred format (JSON or ZIP) in the toolbar; preference is saved in browser localStorage (`tryitEditorExportFormat`).
- Exported ZIP contains HTML, CSS, JS, and a project.json file.
- Import supports both formats and restores all code editors.
- Uses JSZip (CDN) for ZIP support.
## [2025-11-13] Source View Formatting & Syntax Highlighting

- Implemented automatic source formatting for HTML, CSS, and JS editors using Monaco's formatDocument action when toggling Source View.
- Ensured Monaco syntax highlighting is active for all source editors.
- Improves readability and editing experience for all code in Source View.
## [2025-11-13] Reusable Components Library (Blocks)

- Users can save, organize, and reuse custom GrapesJS blocks/components in a local library (stored in browser browser localStorage).
- Library supports simulated directory nesting for organizing saved blocks files (e.g., `components/cards/CardBlock.json`).
- Users can download any saved blocks file as JSON, and re-import it later.
- The primary export link in the app menu still exports the whole project (zip or JSON), but a separate link in `app_menu.php` opens a modal for managing the blocks library.
- Modal UI displays a tree/list of saved files and folders, with options to download, import, delete, and organize blocks files.
- All logic is client-side; no server/cloud sync required (future enhancement possible).

# tryItEditor App Project Plan (GrapesJS Studio)

## Overview
The tryItEditor app is a modern, extensible visual code and component builder for MediaBrain. It uses GrapesJS as the primary interface for visual editing, with Monaco for source editing. The plan below is refined for maintainability, documentation, and future rebuilds.


## Features
- **UI State Persistence**: Store panel state, last view, and preferences in browser browser localStorage with a browser localStorage manager to manage app configurations and save html, css, js tryItEditor files
- **Custom modals for browser localStorage file display**: Browse files in custom modals for export and saving html, css, and js.  The file browsing modals should also provide a way to manage files and folders as you would on an operating system
- **Use browser localStorage for Saving and accessing local files**: Save html, css, and js as json, and provide access to download as json or zip files containing the html, css, and js
- **Component/Template Library**: Save, reuse, and organize custom components or view template files (from browser localStorage files or import)
- **Export as json or zip**: Export code as json or zip using checkboxes in the modal, and save user preference in browser localStorage
- **Classic Try it Editor layout with Split.js adjustable panels**:  Classing Try it Editor layout using Split.js for adjuting the html, css, js, and preview panels that remember their state using browser localStorage config manager
- **Live Preview with App Context**: Preview components within the app’s styles and scripts
- **Integration API**: Push created components/views into the app codebase or staging area (secure with CSRF)
- **Documentation Generator**: Auto-generate documentation or usage notes for each component/view
- **Versioning/History**: Track changes and allow reverting to previous versions
- **Example Gallery**: Curated set of example projects/snippets
- **Save/Load**: Save user code locally and load previous sessions
- **Shareable Links**: Generate URLs to share code
- **Theming**: Optional LCARS/Star Trek theme toggle


## Technical Stack
- **Frontend**: JavaScript (with jQuery for all DOM manipulation and event handling), HTML5, CSS3, GrapesJS, Monaco Editor, optional LCARS theme CSS
    - use ES6 for javascript where possible, but jQuery is already included in the head for other native features; using jQuery for DOM manipulation and event handling wherever needed for consistency and maintainability is acceptable.
- **Backend**: (admin role only) PHP for code execution sandboxing, user authentication, and persistent storage
- **Hosting**: Integrate into existing mediabrain.app platform


## Implementation Steps
1. Scaffold app structure and base files
2. Implement classic TryItEditor layout with Split.js adjustable panels for HTML, CSS, JS, and Preview
3. Integrate Monaco Editor for HTML, CSS, JS panels with syntax highlighting and formatting
4. Add live preview panel with app context (iframe)
5. Add toolbar with source view toggle, run, reset, save, export, and other controls
6. Implement UI state persistence (panel sizes, last view, preferences) using browser localStorage
7. Implement custom modals for browsing, saving, and managing browser localStorage files and folders
8. Add export/import functionality for JSON and ZIP formats, with user preference saved in browser localStorage
9. Build reusable component/template library with file/folder management
10. Add code snippet insertion and example gallery features
11. Implement versioning/history and shareable links
12. Add theming (LCARS/Star Trek toggle) and polish responsive UI/UX, accessibility
13. Integrate with app codebase via secure API (CSRF)
14. Write and refine user/developer documentation (in `/docs/developer/`)
15. Analyze and document code structure for future rebuilds (add lessons learned, gotchas, and migration notes)
16. Audit and refactor as needed for maintainability
17. **All DOM manipulation and event handling should use jQuery, not vanilla JS, to ensure maintainability and avoid issues with element selection, timing, and event binding.**

## Routing and App Entry (Important)

- The main entry point for the app is `/index.php?app=tryItEditor` (not a separate index.php in the app folder).
- This route should load `/apps/tryItEditor/tryItEditor.app.php` as the main app view, following the pattern used by bibleBot and other apps.
- All API calls for the app should be handled by `/apps/tryItEditor/tryItEditor.api.php`, structured similarly to bibleBot.api.php.

- API calls to `tryItEditor.api.php` should use the pattern `?api=tryItEditor&action=...` (e.g., `fetch('?api=tryItEditor&action=save', ...)`), matching the routing convention for MediaBrain apps.
- This ensures proper routing and integration with the master app system and is the recommended approach for all AJAX/API requests in TryItEditor.

- Do **not** create a standalone index.php in the app folder; use the root index.php router and app-specific .app.php/.api.php files.

Refer to bibleBot for routing and API structure best practices.

## Stretch Goals
- Collaboration (real-time editing)
- Additional language support (for base app authenticated admin role users only (PHP, Ruby, etc.)
- Advanced output visualization (charts, graphics)
- Automated codebase analysis and documentation refinement for future rebuilds


## References
- [Monaco Editor](https://microsoft.github.io/monaco-editor/)
- [LCARS UI Inspiration](https://www.thelcars.com/)


---
*Last updated: November 13, 2025*

## UI Structure and Navigation
- **Toolbar**: Contains the toggle button for switching between GrapesJS visual builder and Source View (HTML/CSS/JS/Preview). Additional controls (Run, Reset, Export, etc.) can be added here.
- **Sidenav App Menu**: Located in `views/components/sidenav/app_menu.php`, this menu provides links to advanced features:
    - Export/Import
    - Reusable Components
    - Views
    - Pages
    - Master App System compatibility

## Implementation Notes
- The toolbar toggle is implemented as a button with id `toggle-source-view` in the main toolbar.
- The sidenav app menu is a PHP component for easy extension and integration with the master app system.