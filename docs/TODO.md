# TODO

This section tracks major refactoring, improvement, and maintenance tasks for the Mediabrain project. Each item should be moved to its own detailed doc in `/docs/developer` as work progresses.

## Recent Completions

### Phase 4: Professional Portfolio Enhancement (Nov 7, 2025)
- ✅ **COMPLETED:** Star Trek Achievement Modal - Interactive achievement system with authentic sounds and animations
- ✅ **COMPLETED:** Professional Career Showcase - Technical skills grid and contact integration for job applications
- ✅ **COMPLETED:** Advanced UI Color Scheme - Futuristic deep space color palette with professional contrast ratios
- ✅ **COMPLETED:** Cache-Busting System - Comprehensive solution eliminating development workflow caching issues
- ✅ **COMPLETED:** Config System Enhancement - Robust fallback configuration with professional defaults

### Phase 3 Modernization (Nov 7, 2025)
- ✅ **COMPLETED:** Include pattern modernization - Updated all include/require statements to use __DIR__-based absolute paths
- ✅ **COMPLETED:** Debug code cleanup - Moved 50+ development files to /dev directory structure, removed commented debug code
- ✅ **COMPLETED:** Dependency management updates - Added classmap autoloading to composer.json, updated autoloader
- ✅ **COMPLETED:** Manual include cleanup - Removed manual EventLogger includes, now handled by Composer autoloader

## Current Tasks

- [ ] **Project: AI Research Assistant App**: Build a new app that uses a large language model to perform research, summarize articles, and generate reports.
- [ ] **Project: Real-Time Collaborative Dashboard App**: Build a new Trello-like project board app that uses WebSockets for real-time collaboration.
- ✅ **COMPLETED:** Add an event logging system that logs events to logs/event.log that can be enabled and disabled with event.log and error.log monitoring and inspection in the admin app
- ✅ **COMPLETED:** Add robust unit testing (PHPUnit framework with unit, integration, and API security tests)
- ✅ **COMPLETED:** Text-to-Speech major version update
- ✅ **COMPLETED:** Text-to-Speech User Preferences System - Complete user preference interface with 17+ voice options, real-time preview, volume control, speech rate adjustment, and persistent storage. Includes comprehensive API endpoints, security hardening, and integration with existing authentication system. See `/docs/developer/tts-user-preferences.md` for full documentation.
- [x] Ancestry Authentication Migration - COMPLETED
  - Successfully migrated Ancestry app from legacy authentication system to unified AuthManager! COMPLETED: Removed legacy auth/ directory, updated all admin interfaces to use centralized authentication, migrated API endpoints to unified system, updated all logout links to use unified endpoints, fixed CSP issues for weather API, and ensured logout functionality works site-wide. App now seamlessly integrates with unified authentication while maintaining all genealogy features with proper admin protection.
- [x] Ancestry Family Access & Social Login - COMPLETED
  - Successfully implemented private family-only access for ancestry app with comprehensive social login integration! COMPLETED: Created Drupal-style hook_permissions() system in ancestry.app.php defining 3 family roles with 16 granular permissions, extended PermissionsMatrix with hook discovery and app-specific role management, built admin interface for email-based family member invitations, integrated Google OAuth with automatic role assignment for invited family members, created family_login.php with social login buttons, and established secure family access workflow. See `/docs/developer/ancestry-family-access-implementation.md` for comprehensive documentation.

- complete "views js componentization"
- Perform a security audit
- Perform an overall audit
- Add admin interface for viewing, running, managing, editing tests
- Refactor recipes.app.php includes
  - Refactor legacy require/include statements in recipes.app.php to use app_require() for app-specific includes.
- Integrate Monolog error handling
  - Integrate Monolog for all error handling via App::registerError() throughout the codebase. (Init Monolog in App, add global handlers, add log_error wrapper, replace key error_log calls.)
- Add automated tests
  - Set up PHPUnit and add basic unit/integration tests for authentication, API endpoints, and business logic.
- Move sensitive config to .env
  - Refactor config to use environment variables and .env files for secrets and credentials. Integrate vlucas/phpdotenv.
- Security hardening
  - Review and improve session, CSRF, and input validation. Audit permission checks and cookie flags.
- Update dependencies
  - Run composer update, audit for vulnerabilities, and remove unused packages.
- Expand documentation
  - Add setup, deployment, API, and troubleshooting guides to /docs.
- Add performance monitoring
  - Add request timing, error rate metrics, and profile slow endpoints.
- Optimize Docker & deployment
  - Review Docker images, automate builds/deployments, and ensure secure configuration.
- Modernize frontend
  - Audit and update JS/CSS assets, consider modern frameworks, and optimize static files.
- Centralize access control
  - Audit and refactor permission checks and roles for all endpoints.
- Improve user error reporting
  - Add user-friendly error pages/messages and ensure safe error handling for users.

---

## Developer Docs Structure

📋 **Also see: [AI-DEVELOPMENT-NOTES.md](../AI-DEVELOPMENT-NOTES.md)** for critical project memory, patterns, and development context.

Organize `/docs/developer` as follows:

- **setup/**: Environment setup, installation, and configuration guides
- **api/**: API endpoint documentation and usage examples  
- **security/**: Security policies, authentication, and permission docs
- **testing/**: Automated test guides and coverage reports
- **deployment/**: Docker, CI/CD, and deployment instructions
- **frontend/**: Frontend architecture, assets, and modernization plans
- **monitoring/**: Performance, error, and log monitoring guides
- **troubleshooting/**: Common issues and solutions
- **changelog.md**: Project change history
- **README.md**: Developer portal overview

### Cross-References
- **AI Development Notes**: `AI-DEVELOPMENT-NOTES.md` - Critical project patterns and gotchas
- **Phase 3 Completion**: `docs/developer/phase3-modernization-completion.md` - Recent modernization details
- **Development Files**: `/dev/README.md` - Relocated development artifacts documentation
- **Testing Infrastructure**: `tests/` directory and `phpunit.xml` - Automated testing framework

Add new docs in the relevant subfolder as you work on each area. Update this TODO list as tasks are completed or added.


### Future Develpment Ideas

1. Admin Analytics Dashboard (From Logs Only)

Parse your access logs and visualize:

Most visited pages

Traffic by hour/day

User agents

Geo IP lookups (free API)

Showcases:

Log parsing in PHP

Data visualization (Chart.js)

Good for recruiters: “He built his own analytics platform.”

2. Serverless Form Handler (Email-Based)

Everything is processed in PHP, no DB.

Examples:

Contact form

Resume feedback form

Showcases:

PHP mailing (SMTP or mail API)

Anti-spam techniques

Honeypot protection

Clean validation code

3. AI-Powered Tools (using API calls only)

No database needed—everything is ephemeral.

Ideas:

Text summarizer

Image caption generator

Code explainer

Keyword extractor

Resume bullet point optimizer

Showcases:

PHP curl / Guzzle

REST API architecture

Frontend interactivity

Rate-limiting / caching (simple file cache)

4. Static JSON-Driven CMS

A lightweight “mini-CMS” where content is stored in JSON files instead of a database.
Showcases:

CRUD using PHP filesystem

Secure file operations

Real-world content management logic

Features you can add:

Admin login using hashed password stored in a JSON file

Markdown support

Search / filter

Versioning (old JSON stored automatically)

