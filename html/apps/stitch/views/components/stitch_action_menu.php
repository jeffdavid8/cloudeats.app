<div style="height: 60px;display: flex; align-items: center; justify-content: space-between;">
  <? /*
  <div class="toolbar-actions show-on-small">
    <div class="action-unit">
      <span class="action-badge" style="display: none;">0</span>
      <button onclick="$('.cli-wrapper').toggle();$('.command-prompt-container').toggleClass('cli-active');$('#master-command-prompt').focus()" id="cli-toggle" class="btn-round-action waves-effect waves-light purple darken-3">
        <i class="material-icons">search</i>
      </button>
      <span class="action-label">CLI/SEARCH</span>
    </div>
  </div>
  */ ?>

  <div class="toolbar-actions">
    <div class="action-unit">
      <span id="new-stitch-nexus-badge" class="action-badge" style="display: none;">0</span>
      <button class="btn-new-stitch btn-round-action waves-effect waves-light purple darken-3">
        <i class="material-icons">add</i>
      </button>
      <span class="action-label">NEW_STITCH</span>
    </div>
  </div>

  <div id="nexus-action-btn" class="toolbar-actions">
    <div class="action-unit">
      <span id="new-stitch-nexus-badge" class="action-badge" style="display: none;">0</span>
      <button onclick="openNexusOverlay()" class="btn-round-action waves-effect waves-light purple darken-3">
        <i class="material-icons">explore</i>
      </button>
      <span class="action-label">NEXUS_MATRIX</span>
    </div>
  </div>
  <div class="toolbar-actions">
    <div class="action-unit">
      <button id="toggle-filter-btn" class="btn-round-action waves-effect waves-light purple darken-3" style="" onclick="toggleChronosFilter()">
        <i class="material-icons">filter_list</i>
      </button>
      <span class="action-label">FILTER</span>
    </div>
  </div>
</div>
