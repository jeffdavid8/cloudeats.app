<div class="row animate fadeIn" style="border-left: 4px solid #ff9800; padding-left: 15px; background: rgba(255, 152, 0, 0.03);">
    <div class="col s12">
        <h6 class="orange-text text-darken-2">
            <i class="material-icons left">camera_roll</i> Historical Snapshot
        </h6>
        <p class="grey-text" style="font-size: 0.8rem; margin-top: -5px;">Capturing a moment from a different era.</p>
    </div>

    <div class="input-field col s12">
        <input id="hist_title" name="title" type="text" placeholder="e.g., The Old Bakery on Main St.">
        <label for="hist_title">What was here?</label>
    </div>

    <div class="input-field col s12">
        <textarea id="hist_body" name="body" class="materialize-textarea white-text" 
                  placeholder="Dad says it was owned by... they delivered pizzas to..."></textarea>
        <label for="hist_body">The Story / Memory</label>
        <span class="helper-text grey-text">Record the specific details—names, smells, specific events.</span>
    </div>

    <div class="input-field col s6">
        <input id="hist_era" name="era" type="text" placeholder="e.g., Late 60s, Mid 70s">
        <label for="hist_era">Estimated Era</label>
    </div>

    <div class="input-field col s6">
        <select name="reliability" class="browser-default black white-text" style="border: 1px solid #444; font-size: 0.8rem;">
            <option value="eyewitness">Eyewitness (First-hand)</option>
            <option value="passed_down">Oral History (Passed down)</option>
            <option value="rumor">Local Lore (Unverified)</option>
        </select>
    </div>
</div>

<script>
    M.updateTextFields();
    // Auto-focus the title so you can start typing as soon as it loads
    $('#hist_title').focus();
</script>