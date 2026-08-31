================================================================================
PROMPT 10: THE COURIER ROUTE DISPLAY (COURIER/DASHBOARD.PHP)
================================================================================

Act as a Principal UI Engineer. Write the complete presentation script for the file:
`/html/apps/neighborhub/views/courier/dashboard.php`

This view serves as the interactive dashboard for town delivery couriers.

It must construct:
1. The Open Town Job Pool: Scans for open delivery opportunities using available model array lists. Each entry must display an "Accept Delivery Job" button that immediately attempts an assignment lock.
2. The Active Assignment Panel: If the courier holds a job in state='IN_TRANSIT', lock the viewport context down onto this active route and display a "Mark Order as Delivered" execution trigger.
3. Framework-Compliant Assignment Processing:
   - When a courier accepts or completes a job, the action MUST trigger via an `mb.ajax()` or jQuery `$.ajax()` payload hitting `neighborhub.api.php?action=accept_delivery`.
   - Ensure it passes proper JSON payload parameters and captures the true/false response from our SQLite atomic state transaction lock.
4. Geolocation Engine Mockup: Includes a lightweight client-side script utilizing `navigator.geolocation` (or an immediate spatial coordinate fallback simulation) that automatically sends location data back to `neighborhub.api.php?action=update_location` every 15 seconds using an asynchronous `mb.ajax()` routine.

Output the code block cleanly from top to bottom with zero omissions or summarized comments.