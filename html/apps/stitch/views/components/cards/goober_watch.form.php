<div class="row animate fadeIn" style="background: rgba(255, 171, 64, 0.05); padding: 15px; border: 1px dashed #ffab40; border-radius: 12px;">
    <div class="col s12">
        <h6 class="orange-text text-lighten-2 monospace" style="letter-spacing: 2px;">
            <i class="material-icons left">face</i> GOOBER_WATCH_LOG
        </h6>
    </div>

    <div class="input-field col s12">
        <i class="material-icons prefix grey-text">fingerprint</i>
        <input id="goob_name" name="subject_alias" type="text" placeholder="e.g., 'The Pizza Dog' or 'Old Man Miller'">
        <label for="goob_name">Who or What is the Goober?</label>
    </div>

    <div class="input-field col s12">
        <i class="material-icons prefix grey-text">mode_comment</i>
        <textarea id="goob_body" name="body" class="materialize-textarea white-text" 
                  placeholder="What is the unusual behavior or quirk?"></textarea>
        <label for="goob_body">Observation Notes</label>
    </div>

    <div class="col s12" style="margin-top: 10px;">
        <label class="orange-text" style="font-size: 0.8rem; text-transform: uppercase;">Eccentricity Level</label>
        <p class="range-field">
            <input type="range" name="eccentricity" min="1" max="10" value="5" />
        </p>
        <span class="helper-text grey-text" style="font-size: 0.7rem;">(1: Slightly odd | 10: Total Goober Chaos)</span>
    </div>
</div>

<script>
    M.updateTextFields();
    // Character counter for the observation
    $('#goob_body').characterCounter();
    $('#goob_name').focus();
</script>