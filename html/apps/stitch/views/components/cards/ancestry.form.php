<div class="row animate fadeIn" style="border-left: 4px solid #2e7d32; padding-left: 15px; background: rgba(46, 125, 50, 0.03);">
    <div class="col s12">
        <h6 class="green-text text-darken-3">
            <i class="material-icons left">account_tree</i> Ancestry & Lineage
        </h6>
    </div>

    <div class="input-field col s12 m6">
        <input id="anc_name" name="subject_name" type="text" placeholder="Full Name">
        <label for="anc_name">Subject Name</label>
    </div>

    <div class="input-field col s12 m6">
        <input id="anc_relation" name="relation" type="text" placeholder="e.g., Great Aunt, Neighbor">
        <label for="anc_relation">Relation to Architect</label>
    </div>

    <div class="input-field col s6">
        <input id="anc_birth" name="birth_year" type="text" placeholder="YYYY">
        <label for="anc_birth">Birth Year</label>
    </div>
    <div class="input-field col s6">
        <input id="anc_death" name="death_year" type="text" placeholder="YYYY">
        <label for="anc_death">Passing Year</label>
    </div>

    <div class="input-field col s12">
        <textarea id="anc_notes" name="body" class="materialize-textarea white-text" 
                  placeholder="Who were their parents? What was their trade?"></textarea>
        <label for="anc_notes">Family Lore & Biography</label>
    </div>
</div>

<script>
    M.updateTextFields();
    // A little specialized focus for Ancestry
    $('#anc_name').focus();
</script>