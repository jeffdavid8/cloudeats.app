<div class="row animate fadeIn" style="background: rgba(3, 169, 244, 0.05); padding: 15px; border-radius: 8px;">
    <div class="col s12">
        <h6 class="light-blue-text text-lighten-3"><i class="material-icons left">cloud</i> Atmospheric Observation</h6>
    </div>

    <div class="input-field col s12">
        <input id="weather_title" name="title" type="text" placeholder="e.g., The Michigan Rotation of 2024">
        <label for="weather_title">Event Name</label>
    </div>

    <div class="input-field col s12">
        <textarea id="weather_desc" name="body" class="materialize-textarea white-text" placeholder="What did the radar look like? What was the sound?"></textarea>
        <label for="weather_desc">Meteorological Notes</label>
    </div>
    
    <div class="col s12">
        <p class="grey-text">Intensity Scale</p>
        <p class="range-field">
            <input type="range" name="intensity" min="1" max="5" />
        </p>
    </div>
</div>