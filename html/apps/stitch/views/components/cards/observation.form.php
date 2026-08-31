<div class="row animate fadeln">
    <div class="input-field col s12">
        <i class="material-icons prefix purple-text">visibility</i>
        <textarea id="observation_body" name="body" class="materialize-textarea" data-length="500"></textarea>
        <label for="observation_body">What did you observe?</label>
        <span class="helper-text">Record the raw data of the moment.</span>
    </div>

    <div class="input-field col s12">
        <i class="material-icons prefix purple-text">psychology</i>
        <input id="observation_insight" name="insight" type="text">
        <label for="observation_insight">Immediate Insight (Optional)</label>
    </div>
</div>

<script>
    // Since this is injected via AJAX, we remind Materialize to listen
    M.updateTextFields();
    $('#observation_body').characterCounter();
</script>