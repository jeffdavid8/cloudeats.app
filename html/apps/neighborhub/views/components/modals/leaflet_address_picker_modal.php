
<div id="nh-map-picker-modal" class="modal mb-modal-fixed" style="height: 550px; border-radius: 8px;">

  <div class="modal-inner-overlay"></div>

  <div class="modal-header">
    <h2>Place Delivery Location Pin</h2>
    <div style="display: flex; justify-content: flex-end; width: 100px; text-align: right;">
      <button class="modal-close close" onclick=""><i class="material-icons">close</i></button>
    </div>
  </div>

  <div class="modal-content" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; height: 100%; position: relative;">

    <div style="position: absolute; top: 12px; left: 12px; right: 12px; z-index: 1000; padding: 12px; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); display: flex; flex-direction: column; gap: 8px;">
      <div style="display: flex; gap: 6px; align-items: center;">
        <div style="flex-grow: 1; position: relative;">
          <i class="fas fa-search" style="position: absolute; left: 10px; top: 11px; color: #9e9e9e;"></i>
          <input id="nh-modal-search-address" type="text" placeholder="Type delivery street address..." style="margin: 0; height: 36px; border: 1px solid #ccc; border-radius: 4px; padding: 0 10px 0 32px; box-sizing: border-box; font-size: 14px; ">
        </div>
        <button type="button" id="nh-modal-geocode-btn" class="btn teal waves-effect waves-light" style="height: 36px; line-height: 36px; padding: 0 14px; border-radius: 4px;" title="Find Address on Map">
          <i class="fas fa-search-location"></i>
        </button>
        <button type="button" id="nh-modal-gps-btn" class="btn blue darken-1 waves-effect waves-light" style="height: 36px; line-height: 36px; padding: 0 14px; border-radius: 4px;" title="Use My Current Location">
          <i class="fas fa-gps"></i> <i class="fas fa-crosshairs"></i>
        </button>
      </div>
    </div>

    <div id="nh-modal-leaflet-canvas" style="flex-grow: 1;  width: 100%; height: 100%;"></div>

    <div id="nh-modal-loading-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.8); display: none; align-items: center; justify-content: center; z-index: 1001;">
      <div style="text-align: center;">
        <div class="preloader-wrapper small active">
          <div class="spinner-layer spinner-teal-only">
            <div class="circle-clipper left">
              <div class="circle"></div>
            </div>
            <div class="gap-patch">
              <div class="circle"></div>
            </div>
            <div class="circle-clipper right">
              <div class="circle"></div>
            </div>
          </div>
        </div>
        <p style="margin-top: 10px;">Loading Map...</p>
      </div>
    </div>
  </div>

  <div class="modal-footer" style="padding: 0 20px; display: flex; align-items: center; justify-content: space-between;">
    <span id="nh-modal-coords-display" class="grey-text text-darken-2" style="font-size: 11px; font-family: monospace; font-weight: 600;">Pin unplaced</span>
    <div>
      <a href="#!" class="modal-close waves-effect btn-flat" style="margin-right: 8px; font-weight: 600;">Cancel</a>
      <button id="nh-modal-save-coords-btn" class="btn waves-effect waves-light orange white-text" style="font-weight: 700; border-radius: 4px;">Confirm Pin Location</button>
    </div>
  </div>
</div>