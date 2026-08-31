# tryItEditor Documentation & Rebuild Guide

## Purpose
This document provides guidance for maintaining, refining, or rebuilding the tryItEditor app. It should be updated as the app evolves.

## Key Documentation Steps
1. **Keep `/docs/developer/project-plan.md` up to date**
   - Update after every major feature, refactor, or integration.
2. **Document integration details**
   - Note CSP requirements, loader order, and any 3rd-party library quirks.
3. **Record lessons learned and gotchas**
   - Add a section for issues encountered and how they were solved.
4. **Rebuild checklist**
   - Use the project plan as a step-by-step guide.
   - Note any changes in dependencies, CSP, or browser support.
5. **Add migration and upgrade notes**
   - If switching visual editors or code editors, document the migration process.
6. **Encourage contributions**
   - Ask all developers to add new findings, fixes, or patterns.

## If Rebuilding from Scratch
- Start with the project plan.
- Scaffold the UI and loader order first.
- Integrate GrapesJS and Monaco in isolation before wiring them together.
- Test CSP and CDN access early.
- Add Source View toggle and state persistence as core features.
- Document every step and update this file.

---
_Last updated: 2025-11-13_
