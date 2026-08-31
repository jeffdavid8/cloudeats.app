<div id="horizon-terminal" class="card horizon-terminal-container grey darken-4">
  <div class="card-content">

    <div class="cli-wrapper" style="background: #000; padding: 15px; border-radius: 4px; border: 1px solid #333;">
      <div id="terminal-output" class="monospace" style="font-size: 0.8rem; color: #b39ddb; margin-bottom: 10px;">
        READY_FOR_INPUT...
      </div>
      <div style="display: flex; align-items: center;">
        <span class="lavender-text monospace">> </span>
        <input type="text" id="stitch-cli" class="browser-default monospace"
          placeholder="Enter command (try /help or /scan)..."
          style="background: transparent; border: none; color: #fff; width: 100%; margin-left: 10px; outline: none;">
      </div>
    </div>
    <div class="terminal-header monospace grey-text" style="font-size: 0.7rem; margin-bottom: 10px;">
      <div class="command-shortcuts right">
        <div class="chip transparent grey-text border-lavender" onclick="runCmd('/scan orphans')">SCAN_ORPHANS</div>
        <div class="chip transparent grey-text border-lavender" onclick="runCmd('/nexus top')">MAP_TOPOLOGY</div>
        <div class="chip transparent grey-text border-lavender" onclick="runCmd('/jump random')">RANDOM_JUMP</div>
      </div>
      <span class="lavender-text pulse-dot">●</span> SYSTEM_KERNEL_ACCESS // HORIZON_COORDINATES: 0001.01.01
    </div>

  </div>
</div>

<script>
  
</script>