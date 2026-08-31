<li class="audio-control-unit" style="padding: 10px 20px;">
  <span class="grey-text monospace" style="font-size: 0.7rem; display: block; margin-bottom: 5px;">SONIC_CHANNELS</span>

  <div style="display: flex; align-items: center; gap: 15px;">
    <a href="javascript:void(0);" id="master-mute-btn" class="waves-effect waves-light" style="color: #9e9e9e;">
      <i class="material-icons" id="mute-icon">volume_up</i>
    </a>

    <div style="flex-grow: 1;">
      <p class="range-field" style="margin: 0;">
        <input type="range" id="audio-volume-slider" min="0" max="100" value="50" />
      </p>
    </div>
  </div>
</li>
<script>
  $(document).ready(function() {

// 🕵️‍♂️ THE SIDENAV BLACK BOX
    const sideNavInstance = M.Sidenav.getInstance($('.sidenav'));
    
    if (sideNavInstance) {
        const originalClose = sideNavInstance.close;
        
        sideNavInstance.close = function() {
            console.warn("⚠️ SIDENAV_CLOSE_DETECTED!");
            console.log("Trace:", new Error().stack); // 📜 This shows the 'Who' and 'Where'
            
            // Check if it was a click on the overlay
            if (arguments.length > 0 && arguments[0].target) {
                console.log("Triggered by DOM Element:", arguments[0].target);
            }

            return originalClose.apply(this, arguments);
        };
    }



    // 1. Initial UI Sync
    const prefs = mb.storage.apps.stitch.preferences;
    const isMuted = prefs.mute_audio;
    const currentVol = (prefs.audio_volume ?? 0.5) * 100;

    $('#audio-volume-slider').val(currentVol);
    updateMuteUI(isMuted);

    // 2. Mute Button Click
    $('#master-mute-btn').on('click', function() {
      updateMuteAudio();
    });

    function updateMuteAudio() {
      const newState = !mb.storage.apps.stitch.preferences.mute_audio;
      mb.storage.apps.stitch.preferences.mute_audio = newState;
      storage_set();

      updateMuteUI(newState);

      if (!newState) {
        stitch.audio.lcars_access(); // Chirp on unmute!
      }
    }

    // 3. Slider Input
    $('#audio-volume-slider').on('input', function() {
      const val = $(this).val() / 100;
      mb.storage.apps.stitch.preferences.audio_volume = val;
      storage_set();
      mp3('computerbeep_9');
      // If they move the slider, usually we want to unmute automatically
      if (mb.storage.apps.stitch.preferences.mute_audio) {
        updateMuteAudio();
      }
    });

    function updateMuteUI(muted) {
      $('#mute-icon').text(muted ? 'volume_off' : 'volume_up');
      $('#master-mute-btn').css('color', muted ? '#f44336' : '#9e9e9e'); // Red when muted
      //$('#audio-volume-slider').prop('disabled', muted);
    }
  });
</script>