# GEMINI.md - Project Overview for AI Assistants

This document provides a high-level summary of the MediaBrain.app project, intended to quickly orient AI assistants and developers.

Core Description exists in core-description.md

## Core Project Idea

MediaBrain is a self-hosted suite of web applications designed for productivity and organization. It's built on a PHP backend with a MaterializeCSS frontend, and is designed to be deployed with Docker or on Google Cloud Run.

## MEDIABRAIN MICRO-APP MANIFEST

| App Name | Entry Point | Primary Logic | Responsibility |
| :--- | :--- | :--- | :--- |
| admin | `admin.app.php` | PHP/JS | System control, user management, and PHPUnit testing interface. |
| ancestry | `ancestry.app.php` | PHP | Genealogy research and GEDCOM file processing. |
| api | [In Development] | PHP | Legacy centralized hub; currently being phased out for modular APIs. |
| audioLibrary | `audioLibrary.app.php` | PHP/JS | Cloud-based audio asset management and streaming. |
| auth | `auth.app.php` | PHP | User authentication views and session handling logic. |
| bibleBot | `bibleBot.app.php` | PHP/JS | Scripture search, bookmarking, and Text-to-Speech integration. |
| dashboard | `dashboard.app.php` | PHP/JS | Trello-style collaborative board with WebSocket support. |
| grapesJsEditor | `grapesJsEditor.app.php` | PHP/JS | Visual web page builder and content designer. |
| help | `help.app.php` | PHP | Internal documentation and theme user guides. |
| messages | `messages.app.php` | PHP | Internal messaging system utilizing Google Cloud Storage. |
| recipes | `recipes.app.php` | PHP/JS | Kitchen assistant with voice-controlled navigation. |
| researcher | `researcher.app.php` | PHP/JS | AI Research tool for LLM-based summarization and reporting. |
| splash | `splash.app.php` | PHP | Main landing page and professional showcase. |
| stitch | `stitch.app.php` | PHP/JS | Data field engine and Nexus relational node visualizer. |
| tryItEditor | `tryItEditor.app.php` | PHP/JS | Sandbox environment for real-time code editing. |
| vault | `vault.app.php` | PHP | Secure encrypted storage for sensitive data. |
| weather | `weather.app.php` | PHP/JS | Local and global weather tracking via NWS integration. |

## Stitch Integration
*   **Stitch-Ready Apps**: researcher, bibleBot, weather, recipes, ancestry. (These ingest observations directly into the field).
*   **Standalone Utilities**: tryItEditor, grapesJsEditor, vault, help, admin.
*   **Technical Debt (Dark Apps)**: The `api` folder exists but serves as a legacy placeholder following the modernization to `{app}.api.php` routing.

## Architecture and Technology

*   **Backend**: PHP 8.2
*   **Frontend**: JavaScript (ES6), MaterializeCSS
*   **Database**: File-based (JSON files), with a `FileStorageManager` for handling I/O.
*   **Storage**: Google Cloud Storage with a local fallback.
*   **Authentication**: OAuth 2.0 with support for Google, Facebook, and Apple. A role-based permission system is in place (`Guest`, `User`, `Editor`, `Admin`).
*   **Deployment**: Docker (via `docker-compose.yml`) and Google Cloud Run (via `cloudbuild.yaml`).

## Development

*   **Dependencies**: Managed with Composer.
*   **Testing**: PHPUnit is used for testing. `composer test` runs the test suite.
*   **Important Files**:
    *   `AI-DEVELOPMENT-NOTES.md`: **Critical information for development.** Contains gotchas and architectural decisions.
    *   `html/includes/`: Core PHP classes.
    *   `html/apps/`: Individual application modules.
    *   `html/json/`: JSON data files.
    *   `config/`: Configuration files.
    *   `tests/`: Test files.

API Response json standard (with sample data)
{
  "status": "success",
  "data": {
    "nodes": [
      { "id": "unique_hash", "label": "Node Name", "group": "category", "value": 10 }
    ],
    "edges": [
      { "from": "source_id", "to": "target_id", "label": "relationship" }
    ]
  },
  "metadata": {
    "count": 1,
    "source": "vault"
  }
}


## Current Project: AI and Real-Time Features

The current development focus is on stabilizing the Nexus gravity node viewer and expanding the field:

1. **STITCH APP**: Optimizing the Nexus Matrix (Vis-Network) viewer. Determining cause of XHR load errors in `get_matrix_data`.
2. **Researcher**: Expanding LLM reporting capabilities.
3. **Dashboard**: Finalizing WebSocket implementation for collaborative boards.
4. **Messaging**: Implementing GCS-based file locking for the messaging app.
5.  **AI Research Assistant ("Researcher")**: A new app to perform research, summarize articles, and generate reports using a Large Language Model (LLM).
6.  **Real-Time Collaborative Dashboard ("Dashboard")**: A Trello-like project board using WebSockets for real-time collaboration.
7.  **Internal Messaging System**: A file-based messaging system using Google Cloud Storage.

Before starting any work, review `AI-DEVELOPMENT-NOTES.md` and the relevant project plan in `docs/developer/`.



## Future Development

## Project: The "Eva" Magic Mirror (Sovereign Reflective Interface)

Concept: A distributed, physical hardware interface (Magic Mirror) powered by the BibleBot/Nexus engine. This isn't just a smart mirror; it’s a shared "Relief and Entertainment" terminal designed to bypass the "Proprietary Wrappers" of modern media.

The "Eva" Protocol:

    Non-Bogarting Access: A decentralized sharing model where users "pass the mirror" (digitally or physically), ensuring no single "Goober" or gatekeeper can monopolize the "Root" data.

    Forensic Reflection: Displays real-time BibleBot search results, high-frequency "Vibrations" (like the Native American flute tracks), and "State of Being" metrics.

    Anti-Deprivation Engine: Built to remind the Sovereign individual of their true value, effectively "Deleting" the demonization and deprivation scripts pushed by external "Pastoral" nodes.



    find out what really makes it good for everybody who has ever existed, and then do that
