# MEDIABRAIN CORE ARCHITECTURE (The Hab)

## Mission Statement
To provide a high-fidelity, sovereign observation deck for tracking "Truths" (Stitches) through kinetic narrative.

## Core Principles
- **Kinetic Sovereignty:** No "jumps" or "stutters" in UI. Transitions must be "Ghost Fades" (Opacity 0 to 1).
- **The "Anyway" Logic:** The system acknowledges disturbances but maintains momentum.
- **Goodie Bag Objects:** Data is passed as JSON objects with metadata (mood, intensity, commander) to build narrative insight.

## Technical Stack
- **Backend:** PHP (App::getInstance()) for User Auth and Data Persistence.
- **Engine:** Sub-pixel JavaScript loops for ticker recalibration.
- **Vessel:** GPU-accelerated CSS (Flexbox, @keyframes, Translate3d).

## The "Slinky" Rule
The Ticker must always appear infinite. When a "Vouch" occurs, the text updates via a stealth-fade to prevent visual breaks.

## Sovereign Override (View Loading)
The application follows a "Sovereign Override" pattern for loading view files. This architectural choice empowers individual applications to have their own customized views, while still relying on a shared set of default views.

The View loader checks for the existence of a view file in the following order:
1.  **App-Specific View:** `html/apps/{app_name}/views/`
2.  **Root View (Fallback):** `html/views/`

This ensures that an app can override a default view by simply creating a file with the same name in its own `views` directory. If the file is not found in the app-specific directory, the loader seamlessly falls back to the root `views` directory.


System Moods (The Narrative Engine)

    Nominal: The Slinky is walking at standard velocity. Standard CSS variables apply.

    Vouch-Surge: Triggered by user validation. Initiates the vouch-surge CSS class and the music-sync pulse.

    Anyway-Recovery: Detected when an error or disturbance occurs. The system logs a "Ghost Packet" and maintains kinetic momentum without a UI stutter.

The Stitch Lifecycle

    Ingestion: Data enters as a "Goodie Bag" JSON object via AppController.php.

    Refinement: PHP filters for permissions and app-specific overrides.

    Manifestation: The JS loop injects the Stitch into the Ticker using a Stealth-Fade to ensure zero-jump transitions.


Resilience & Anti-Stutter

    Conflict Resolution: If a View Override is missing, the fallback to html/views/ must be invisible to the user.

    Noise Filtration: The system prioritizes "Commander" inputs over "Goober" static.

    Hardware Acceleration: Always utilize Translate3d to keep the Slinky on the GPU, leaving the CPU free for logic.

Authentication & The User Vessel

    Unified Identity: The App->user object (located in /html/includes/app.php) is the single source of truth for identity.

    Social Handshakes: Support for Google, Facebook, and LinkedIn is abstracted; these services serve only to hydrate the App->user object.

    Sovereign Session: Once initialized, the user's "Goodie Bag" (permissions and metadata) is carried across all app views, from ancestry to stitch.

The User Vessel (App->user)

    Initialization: The user object is hydrated in html/includes/app.php and attached to the App singleton.

    App::getInstance()->user
    
      object(User)#111 (7) {
        ["username"]=>
        string(5) "admin"
        ["email"]=>
        string(20) "admin@mediabrain.app"
        ["role"]=>
        string(5) "admin"
        ["is_admin"]=>
        bool(true)
        ["created"]=>
        string(25) "2025-10-25T18:06:33+00:00"
        ["last_login"]=>
        string(25) "2025-11-06T15:34:08+00:00"
        ["active"]=>
        bool(true)
      }

    Identity Translation: External logins (Google, FB, LinkedIn) are normalized into the local App->user schema, ensuring consistent "Sovereign" behavior regardless of the entry point.

    Goodie Bag Integration: The user's metadata—identity, permission levels, and "Commander Status"—is injected into JSON objects to fuel the narrative ticker.

# Insights

  Provider Independence: The system acknowledges external auth disturbances but maintains the local session momentum ("Anyway" logic).

  Permission Inheritance: App-specific view overrides check the App->user object to determine if the Slinky should display "Commander" or "Observer" level data.

  The Identity normalize: Look for a method like initUser() or hydrateFromSocial(). This is where the "Rhino Piles" of external data are filtered into the lean User object we see in your manifest.

  Sovereign Authorization: Most singleton patterns of this grade include a $app->isAdmin() or $app->canAccess($app_name) shortcut. These are the "Airlock" controls that keep the Goobers in the hallway and the Commanders in the cockpit.

  Shared DNA: Both the stitch_list.php (Vertical History) and the Ticker (Horizontal Pulse) consume the same "Goodie Bag" objects.

  Markup Divergence: Duplication is minimized by separating the Data Logic (The Queue) from the Vessel Logic (The CSS/Markup).

  Kinetic Choice: The Ticker uses Translate3d for sub-pixel flow, while the List utilizes standard flex-scrolling for deep-history review.

  The Contextual Mirror (List & Ticker)

      Shared Class Architecture: UI elements use a unified "Mood" class system (e.g., .mood-vouch, .mood-ghost) to ensure visual fidelity across different view vessels.

      Necessary Duplication: While the Markup/CSS structure differs (Ticker = Horizontal/Kinetic, List = Vertical/Static), they are bound to the same mb.tickerQueue array.

      The "Anyway" Sync: Updating a Stitch in the data layer automatically pushes the change to both the Ticker and the stitch_list.php view.

  The Global Megaphone (mb.announce)

      announcement : [
          "intel" : "Sovereign Auth Verified: " + mb.user.username,
          "mood" : mb.user.is_admin ? "gold-pulse" : "standard-blue",
          "intensity" : 1.0,
          "pilot" : mb.user.is_admin
      ];

      Global Logic: mb.announce is the universal method for injecting "Truths" into the Ticker Queue.

      App Logic: The term "Vouch" is reserved for internal app validation (e.g., in /html/apps/stitch).

      The Mirror: Every announce call mirrors the data to the horizontal Ticker and the vertical renderNextAnnouncement history.



## Mission Patch: The Great Slinky Run (Jan 27, 2026)
- **Status:** Record-Breaking Descent.
- **Achievement:** Successfully bypassed "Counter-Revolutionary" interference through the deployment of "Anyway" logic and a high-fidelity focused scan.
- **Vibe:** Buddy the Elf Energy (100% Infidelity).
- **Commander:** Jeff (The Architect).