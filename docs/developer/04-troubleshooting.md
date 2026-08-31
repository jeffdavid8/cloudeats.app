# 4. Troubleshooting Guide

This guide contains solutions to common issues encountered during development, along with essential debugging commands.

## 🐛 Common Development Issues

### Issue: "MIME type mismatch" errors for CSS/JS files

You see errors in the browser console like:
`Refused to apply style from 'https://mediabrain.app.local/css/style.css' because its MIME type ('text/html') is not a supported stylesheet MIME type...`

This almost always means a fatal PHP error occurred. The server sent an HTML error page instead of the requested static asset, causing the browser to reject it.

-   **Root Cause**: A PHP fatal error, often from a missing `require` or a class not being found (especially `AuthManager.php` or `EventLogger.php`). The error breaks the script before it can correctly process the request.
-   **Solution**:
    1.  Check the MediaBrain container logs for the PHP fatal error message:
        ```bash
        docker logs mediabrainapp-mediabrain-app-1 --tail 50
        ```
    2.  Fix the underlying PHP error reported in the logs.
    3.  Verify includes in `index.php` and other entry points are correct.

### Issue: "ERR_CONNECTION_REFUSED" on `mediabrain.app.local`

This error means the Nginx proxy is likely down or misconfigured.

1.  **Check if the Nginx container is running**:
    ```bash
    docker ps --format "table {{.Names}}\t{{.Status}}" | findstr "nginx"
    ```
2.  If it's not running or restarting, check its logs for errors:
    ```bash
    docker logs nginx --tail 50
    ```
3.  If you recently changed the Nginx config, test it for syntax errors:
    ```bash
    docker exec nginx nginx -t
    ```
4.  If the config is valid, reload Nginx to apply the changes:
    ```bash
    docker exec nginx nginx -s reload
    ```
5.  Ensure your `hosts` file entry for `mediabrain.app.local` is correct.

### Issue: Session Problems (Can't log in, etc.)

If you experience login issues, or if private/incognito mode works when normal mode doesn't, you may have a corrupted session file.

-   **Debug Tool**: Use the built-in session utility to diagnose and clear out old session data.
    -   `https://mediabrain.app.local/session_cleanup.php`
-   **Manual Fix**: Clear your browser cookies and site data for `mediabrain.app.local`.

## 🛠️ Essential Docker Debugging Commands

These commands are invaluable for inspecting the state of the application.

-   **Check all container statuses**:
    ```bash
    docker ps --format "table {{.Names}}\t{{.Ports}}\t{{.Status}}"
    ```
-   **View live logs for the main app**:
    ```bash
    docker logs -f mediabrainapp-mediabrain-app-1
    ```
-   **View live logs for the Nginx proxy**:
    ```bash
    docker logs -f nginx
    ```
-   **Get a shell inside the main app container**:
    ```bash
    docker exec -it mediabrainapp-mediabrain-app-1 bash
    ```
-   **Test Nginx configuration syntax**:
    ```bash
    docker exec nginx nginx -t
    ```
-   **Force-reload Nginx configuration**:
    ```bash
    docker exec nginx nginx -s reload
    ```
-   **Check static file serving (bypassing proxy)**:
    This helps determine if an issue is in the app container or the Nginx proxy.
    ```bash
    curl -I http://localhost:8080/js/jquery-ready.js
    ```
-   **Check static file serving (through proxy)**:
    ```bash
    curl -I https://mediabrain.app.local/js/jquery-ready.js
    ```
