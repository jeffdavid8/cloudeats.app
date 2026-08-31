<div class="row animate fadeIn" style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px;">
    <div class="col s12 center-align">
        <i class="material-icons large pink-text text-lighten-3" style="opacity: 0.5;">favorite</i>
        <h6 class="pink-text text-lighten-4">A Memory of the Heart</h6>
    </div>

    <div class="input-field col s12">
        <textarea id="heart_memory" name="body" class="materialize-textarea white-text" placeholder="What feeling does this place or person evoke?"></textarea>
        <label for="heart_memory">The Pure Memory</label>
    </div>

    <div class="input-field col s12">
        <input id="heart_mood" name="mood" type="text" placeholder="e.g., Nostalgic, Warm, Bittersweet">
        <label for="heart_mood">The Emotional Resonance (Mood)</label>
    </div>
</div>

<script>
    M.updateTextFields();
    M.textareaAutoResize($('#heart_memory'));
</script>