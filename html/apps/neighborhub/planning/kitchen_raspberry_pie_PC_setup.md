To get a thin client or PC to boot up, automatically log in, and display your PHP web app via Google Login, you need to configure the machine as a Dedicated Kiosk.
Because Google Login (OAuth 2.0) explicitly blocks automated scripting (like Puppeteer or automated password filling) to prevent bot attacks, you cannot code your way past the login screen. Instead, you must leverage persistent browser sessions so the device only requires a manual login once during setup, and remains logged in forever after.
Here is the step-by-step architecture to achieve this:
------------------------------
## Step 1: Select the Right OS (Ubuntu or Raspberry Pi OS)
Avoid Windows for kitchen kiosks; update prompts and background pop-ups will constantly disrupt the kitchen screen. Use a lightweight Linux distribution like Ubuntu LTS or Raspberry Pi OS.
During the operating system installation, ensure you check the box for "Log in automatically". This bypasses the OS password screen on boot and drops the machine directly onto the desktop.
------------------------------
## Step 2: Configure a Persistent Chrome Profile
To ensure Google doesn't log the kitchen out when the PC restarts, Chrome must save cookies and session data to a dedicated local directory.

   1. Open a terminal on the kiosk machine.
   2. Create a permanent directory to store the kitchen's browser profile:
   
   mkdir -p /home/$USER/kiosk_profile
   
   3. Open Chrome manually from the terminal, forcing it to use this new directory:
   
   google-chrome --user-data-dir=/home/$USER/kiosk_profile
   
   4. The One-Time Setup: In this browser window, navigate to your PHP POS web app, click "Log in with Google," and complete the authentication.
   5. Close the browser. Because of the --user-data-dir flag, that Google login session is now permanently saved in that folder.

------------------------------
## Step 3: Write the Kiosk Startup Script
Next, create a shell script that suppresses system errors, prevents the screen from going to sleep, and launches Chrome in a borderless fullscreen "Kiosk" mode.

   1. Create a script file:
   
   nano /home/$USER/start_kitchen.sh
   
   2. Paste the following configuration:
   
   #!/bin/bash
   # Disable screen saver and power management (prevent sleep)
   xset s off
   xset -dpms
   xset s noblank
   # Hide the mouse cursor after 2 seconds of inactivity (requires: sudo apt install unclutter)
   unclutter -idle 2 -root &
   # Launch Chrome pointing to your PHP App using the saved Google login session
   google-chrome \
     --user-data-dir=/home/$USER/kiosk_profile \
     --kiosk \
     --no-first-run \
     --fast \
     --fast-start \
     --disable-infobars \
     --disable-session-crashed-bubble \
     "https://your-php-pos-app.com"
   
   3. Save the file (Ctrl+O, then Ctrl+X) and make it executable:
   
   chmod +x /home/$USER/start_kitchen.sh
   
   [1] 

------------------------------
## Step 4: Trigger the Script on OS Boot
Now, tell the operating system to run your script the exact moment the desktop environment loads.

* For Ubuntu / GNOME desktops: Open the application menu, launch "Startup Applications", click Add, and set the command path to /home/$USER/start_kitchen.sh.
* For lightweight environments (Openbox / Raspberry Pi): Edit the autostart file:

nano ~/.config/lxsession/LXDE-pi/autostart

Add a line at the bottom pointing to your script:

@/home/$USER/start_kitchen.sh


------------------------------
## Step 5: Adjust Your PHP App's Session Lifespan
By default, PHP handles sessions using short-lived cookies that expire when the browser closes, or garbage collection clears them after 24 minutes. If Chrome restarts, your user will be kicked out.
To keep the kitchen logged in indefinitely, you must force PHP to issue long-lived session tokens. Before you run session_start() in your PHP backend code, Gemini can help you implement this layout:

// Set session cookie lifetime to 1 year (31,536,000 seconds)
ini_set('session.cookie_lifetime', 31536000);
ini_set('session.gc_maxlifetime', 31536000);

// Ensure the cookie is secure and HTTP-only
session_set_cookie_params([
    'lifetime' => 31536000,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

## The Result
When the kitchen staff flips the physical power switch on the thin client, the machine will power on, skip the OS login, load the persistent Chrome profile, bypass the Google OAuth screen because the session cookie is active, and immediately lock into your fullscreen PHP kitchen interface.
Would you like Gemini to write a systemd service script to automatically relaunch Chrome if a kitchen staff member accidentally closes it, or do you need help optimizing your PHP app to handle real-time ticket updates (like WebSockets or long polling)?

[1] [https://vesterbusiness.freshdesk.com](https://vesterbusiness.freshdesk.com/support/solutions/articles/44002423080-how-to-open-client-browser-on-so-startup-fullscreen-no-closeable)
