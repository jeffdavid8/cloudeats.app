<div id="chronicle-monitor" class="grey darken-4 z-depth-3" style="position: relative; bottom: auto; width: 100%; height: 60px; border-top: 1px solid #9b59b2; overflow: hidden; z-index: 999; font-family: monospace;">
  <div class="vitals-overlay" style="position: absolute; left: 0; top: 0; height: 100%; background: #1a1a1a; z-index: 10; padding: 18px 15px; border-right: 1px solid #9b59b2; display: flex; align-items: center; gap: 10px;"><span class="meta-title-display"><?= $this->get('meta')['title']; ?></span>
    <div style="display: none">
      <span class="purple-text" style="font-weight: bold; font-size: 0.8rem;">FIELD_STABILITY:</span>
      <span id="total-vouch-count" class="white-text">0</span>
    </div>
    <div class="heartbeat-led"></div>
  </div>

  <div class="ticker-wrap" style="overflow: hidden; white-space: nowrap; display: flex;">
    <div id="chronicle-scroller" class="ticker-move purple-text text-lighten-3">
      <div class="ticker-buffer crt-flicker-text"> ... [ SYSTEM_SYNC ] ... </div>
      <div class="ticker-buffer crt-flicker-text"> ... [ SYSTEM_SYNC ] ... </div>
    </div>
  </div>
</div>

<script>
  mb.logMission = function(entry) {
    const timestamp = new Date().toLocaleTimeString();
    const scroller = $('#chronicle-scroller');

    // 🎯 Use a separator that is consistent
    const separator = " &nbsp;&nbsp;&nbsp; [🛰️] &nbsp;&nbsp;&nbsp; ";
    const newEntry = `<span class="ticker-item">${timestamp} - ${entry}</span>${separator}`;

    // To prevent the "Jump," keep the last few entries instead of wiping everything
    scroller.find('.ticker-content').append(newEntry);
    scroller.find('.ticker-content').append(newEntry);
    scroller.find('.ticker-content').append(newEntry);

    // Optional: If the ticker gets TOO long, prune the first few items
    if (scroller.children().length > 10) {
      scroller.find('span:first').remove();
    }
  };

  // The High-Fidelity Queue
  mb.tickerQueue = [];
  mb.currentIndex = 0;

  // 1. REFINED ANNOUNCE (The Gatekeeper)
  mb.announce = function(announcement) {
    // Normalize string to object
    if (typeof announcement === "string") {
      announcement = {
        intel: announcement,
        mood: "nominal",
        intensity: 1.0,
        pilot: "OBSERVER",
        timestamp: new Date().toLocaleTimeString(),
      };
    }

    this.tickerQueue.push(announcement);
    // THE COMMANDER'S PRIVILEGE
    // If a non-commander tries to force a 'vouch' mood, we downgrade it to 'nominal'
    if (!this.isCommander && announcement.mood === "vouch") {
      console.warn(
        "ANYWAY_LOG: Non-Commander attempted high-intensity pulse. Downgrading to nominal.",
      );
      announcement.mood = "nominal";
    }

    this.renderNextAnnouncement([announcement]);
  };

  // 2. REFINED RENDER LOOP (The Heartbeat)
  mb.renderNextAnnouncement = function() {
    if (this.tickerQueue.length === 0) return; // Anyway Safety

    const announcement = this.tickerQueue[this.currentIndex];

    // Update the UI with the Ghost Fade
    this.updateTickerStealth(announcement);

    // Move the needle
    this.currentIndex = (this.currentIndex + 1) % this.tickerQueue.length;

    // Clear existing timeout to prevent "Timer Stacking"
    if (this.announcementTimer) clearTimeout(this.announcementTimer);

    this.announcementTimer = setTimeout(() => {
      this.renderNextAnnouncement();
    }, 15000); // The Blues Traveler Constant
  };

  /* 🚂🚃💖🚃💖🚃💖🚃💨 */

  mb.updateTickerStealth = function(newEntry) {
    if (!newEntry || !newEntry.intel) return;

    const timestamp = new Date().toLocaleTimeString();
    const spacing = "          "; // 10 non-breaking spaces
    const unitText = `<span class="mood-${newEntry.mood || "nominal"}"> < ${timestamp} // ${newEntry.intel.toUpperCase()}${spacing}</span>`;

    const $scroller = $("#chronicle-scroller");
    const $parent = $scroller.parent();

    // 1. THE JETTISON
    $parent.css({
      transition: "all 0.4s ease-in",
      opacity: "0",
      transform: "translateY(20px)"
    });

    setTimeout(() => {
      if (!$scroller.is(":visible")) return; // (The scroller is hidden on mobile)
      // Kill all movement and clear the runway
      $scroller.css({
        "animation": "none",
        "display": "inline-block",
        "white-space": "nowrap"
      });

      // 2. ATOMIC MEASUREMENT
      $scroller.html(unitText);
      // We use offsetWidth for an absolute integer
      const exactWidth = $scroller[0].offsetWidth;

      // Repeat it enough times to fill 3 screen widths (The 'Infinite Buffer')
      const repeatCount = Math.ceil((window.innerWidth * 3) / exactWidth) + 1;
      $scroller.html(unitText.repeat(repeatCount));

      const duration = exactWidth / 60; // Smooth photon speed

      // 3. THE FRAME-PERFECT KEYFRAME
      let $styleTag = $("#kinetic-force-style");
      if (!$styleTag.length) $styleTag = $('<style id="kinetic-force-style">').appendTo("head");

      // We move exactly ONE unit width. 
      // This makes the reset point mathematically identical to the start point.
      $styleTag.html(`
            @keyframes KINETIC_REBOOT { 
                from { transform: translate3d(0,0,0); } 
                to { transform: translate3d(-${exactWidth}px,0,0); } 
            }
        `);

      // 4. THE LANDING (Pre-positioning)
      $parent.css({
        transition: "none",
        transform: "translateY(-30px)",
        opacity: "0"
      });
      $parent[0].offsetHeight; // The "Force Reflow" magic trick

      // Start the horizontal engine BEFORE we fade back in
      // This ensures the wheels are already turning when the plane lands
      $scroller.css({
        "animation": `KINETIC_REBOOT ${duration}s linear infinite`,
        "will-change": "transform"
      });

      // 5. THE REVEAL
      $parent.css({
        transition: "all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)",
        opacity: "1",
        transform: "translateY(0px)"
      });

    }, 450);
  };

  mb.spreadCheer = function() {
    const $ticker = $("#chronicle-monitor");
    $ticker.toggleClass("music-sync");

    mb.logMission({
      intel: "I'M IN LOVE AND I DON'T CARE WHO KNOWS IT!",
      mood: "vouch",
      icon: "favorite",
      intensity: "ELF_MODE",
    });
  };


  $(document).ready(function() {

    // 2. THE KICKSTART: This triggers the movement immediately on load
    if (typeof mb !== 'undefined' && mb.logMission) {
      if (mb.user !== 'undefined') {
        if (mb.user.role == 'admin') {
          mb.announce(`Vessel Commander: ${mb.user.username.toUpperCase()} // CONNECTION_SECURE // WELCOME HOME... <3`);
        } else {
          // { username: "admin", email: "admin@mediabrain.app", role: "admin", is_admin: true, created: "2025-10-25T18:06:33+00:00", last_login: "2025-11-06T15:34:08+00:00", active: true }
          // Great Regular User
          mb.announce(`Vessel Crew Member: ${mb.user.username.toUpperCase()} // CONNECTION_SECURE // WELCOME ABOARD... <3`);
        }
      } else {
        mb.announce("SYSTEM INITIALIZED... LINEAGE_SYNC_COMPLETE... UNKNOWN_USER... <3");
        //mb.logMission("SYSTEM INITIALIZED... LINEAGE_SYNC_COMPLETE... <3");
      }
    }

    // 3. The Vital Scan Function (Odometer & Heartbeat)
    window.updateVitals = function() {
      let total = 0;
      // 1. Sum up the vouches
      $('[id^="vouch-count-"]').each(function() {
        total += parseInt($(this).text()) || 0;
      });

      const currentTotal = parseInt($('#total-vouch-count').text()) || 0;

      // 2. ONLY update the counter (The Odometer)
      mb.animateValue("total-vouch-count", currentTotal, total, 1000);

      // 3. ONLY update the Heartbeat LED
      // The more stability, the faster the pulse!
      const speed = Math.max(0.4, 2.0 - (total * 0.05));
      $('.heartbeat-led').css('animation-duration', speed + 's');

      // CRITICAL: Ensure NO code here refers to #chronicle-scroller or scroller.html()
      // We want the ticker to just keep 'sailing' on its own.
    };

    // 4. Initial Boot
    updateVitals();
  });

  // Odometer Effect for the Stability Counter
  mb.animateValue = function(id, start, end, duration) {
    if (start === end) return;
    const obj = document.getElementById(id);
    const range = end - start;
    const minTimer = 50;
    let stepTime = Math.abs(Math.floor(duration / range));
    stepTime = Math.max(stepTime, minTimer);

    let startTime = new Date().getTime();
    let endTime = startTime + duration;
    let timer;

    function run() {
      let now = new Date().getTime();
      let remaining = Math.max((endTime - now) / duration, 0);
      let value = Math.round(end - (remaining * range));
      obj.innerHTML = value;
      if (value == end) {
        clearInterval(timer);
      }
    }
    timer = setInterval(run, stepTime);
    run();
  };
</script>