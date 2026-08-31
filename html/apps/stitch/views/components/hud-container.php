<div id="hud-container" class="card sub-processor-terminal grey darken-4 " style="border: 2px solid #b39ddb; margin: 50px 0; position: relative; overflow: hidden; border-radius: 0 0 20px 20px;">

  <button class="close right btn-round-action" onclick="toggleChronosFilter()" style="margin: 5px 5px  0 0;"><i class="material-icons">close</i></button>

  <div class="lavender-pulse-glow"></div>

  <div class="card-content">
    <div class="field-toolbar container center-align" style="padding: 3px 5px 0 3px; margin-bottom: 1em; border-left: 2px solid #b39ddb; border-right: 2px solid #b39ddb; border-bottom: 1px solid #1b1631; border-radius: 20px;">
      <div id="chronos-module" class="row">
        <div class="col s4">
          <input name="date-start" type="date" id="date-start" class="datetimepicker toolbar-date-input" value="">
        </div>
        <div class="col s4">

          <div id="dimension-status" class="purple-text x-small monospace center-align" style="margin-top: 5px;">
            MODE: DISCOVERY_FEED
          </div>

        </div>
        <div class="col s4">
          <input name="date-end" type="date" id="date-end" class="datetimepicker toolbar-date-input" value="">
        </div>
      </div>
      <div class="row">
        <? /* Chronos / Discovery - Materialize Toggle Switch */ ?>
        <div class="dimension-toggle-container center-align">
          <div class="switch" style="margin: 0 auto;">
            <label>
              <span class="mode-label discovery">OBSERVER</span>

              <input type="checkbox" id="dimension-toggle" onchange="toggleStitchDimension()">
              <span class="lever"></span>

              <span class="mode-label historical">HISTORICAL</span>
            </label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col s3"></div>
        <div class="col s6 center-align">
          <p class="range-field" style="margin: 0;">
            <input type="range" id="time-slider" min="0" max="100" value="100" />
          </p>
          <span id="time-display" class="monospace" style="font-size: 0.8rem; color: #aaa;">ALL_TIME</span>
        </div>
        <div class="col s3"></div>

      </div>
    </div>
  </div>
</div>