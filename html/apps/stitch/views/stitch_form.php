<?
$user = App::getInstance()->user;
?>

<form id="newStitchForm" action="?api=stitch" method="POST">
    <input type="hidden" name="architect_id" value="<?= $user->id ?? '' ?>" />
    <input type="hidden" name="action" value="add_stitch" />
    <input type="hidden" name="parent_id" value="<?= $parent_stitch['id'] ?? '' ?>" />
    <input type="hidden" name="nexus_ids" id="nexus_ids" value="" />

    <div class="action-bar">
        <? render('components/stitch_form_context_menu.php', array('style' => 'inline')) ?>
    </div>

    <?
    if (isset($parent_stitch) && !empty($parent_stitch)) {
        // collapsed view of parent stitch
    ?>
        <div class="branch-indicator" style="margin-bottom: 10px; color: #9b59b2; font-family: monospace;">
            <i class="material-icons tiny">call_split</i> BRANCHING_FROM_ID: <?= $parent_stitch['id'] ?>
        </div>
        <div class="card grey darken-3 white-text" style="border: 1px solid #333; margin-bottom: 1.5rem;">
            <div class="card-inner-bevel subtle-scanning-line-effect">
                <div class="card-content">
                    <span class="badge blue darken-4 white-text" style="float: right;">
                        OBSERVATION: <?= htmlspecialchars($parent_stitch['data_type']) ?>
                    </span>
                    <p style="font-size: 1.1rem; line-height: 1.4; margin-top: 0.5rem; opacity: 0.8;">
                        <?= nl2br(htmlspecialchars($parent_stitch['content'])) ?>
                    </p>
                    <div style="margin-top: 1rem; color: #555; font-family: monospace; font-size: 0.85rem;">
                        [ ANCHOR_ID: <span class="copy-target" style="cursor: pointer; color: #9b59b2;" onclick="copyText('<?= $parent_stitch['id'] ?>'); M.toast({html: 'ID COPIED', classes: 'blue'});">
                            <?= $parent_stitch['id'] ?>
                        </span> ] | [ TIMESTAMP: <?= date('m/d/Y H:i:s', strtotime($parent_stitch['created_at'])) ?> ]
                    </div>
                </div>
            </div>
        </div>
    <? } ?>
    <!-- Add the nexus button here -->

    <div id="text-editor" class="tab-content">
        <? render('components/cards/default.form.php'); ?>
    </div>
    <div id="nexus-preview-area" class="tab-content hidden" style="">
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <span style="color: #555; font-family: monospace; font-size: 0.8rem;"><a class="" href="#!" onclick="openNexusOverlay()">[ TEMPORAL_ANCHORS: <i class="material-icons" style="">explore</i> ]</a></span>
                    <div id="nexus-chips-container" style="display: inline-block; margin-left: 10px;">
                        <em id="nexus-empty-msg" style="color: #333; font-size: 0.8rem;">No anchors set...</em>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="new-stitch-form-visibility" class="tab-content hidden">
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <label class="grey-text">VISIBILITY_SCOPE</label>
                    <div class="input-field">
                        <select id="stitch_visibility" class="browser-default black white-text" style="border: 1px solid #444; border-radius: 4px; padding: 10px; background: #1a1a1a;">
                            <option value="private" selected>🔒 PRIVATE (Local Vault Only)</option>
                            <option value="unlisted">🔗 UNLISTED (Access via Secret Link)</option>
                            <option value="public">🌎 PUBLIC (Broadcast to Map & Pasture)</option>
                        </select>
                    </div>
                    <p class="grey-text text-lighten-1" style="font-size: 0.8rem; margin-top: -10px;">
                        <i class="material-icons tiny">info_outline</i>
                        <span id="visibility_hint">Only you can see this stitch. It will not be synced to the cloud.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div id="new-stitch-form-data-type" class="tab-content hidden">
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <div class="input-field" style="">
                        <? /*<p style="color: #888;">Data Type</p>*/ ?>
                        <select id="dataType" name="data_type" class="browser-default black white-text" style="border: 1px solid #444;">
                            <?php foreach (Stitch::getAllowedTypes() as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="new-stitch-form-projected-date" class="tab-content hidden">
        <div class="col s12">
            <div class="input-field temporal-navigation " style="">

                <input type="date" name="projected_to" id="projected_to"
                    value="<?= date('Y-m-d') ?>"
                    class="white-text"
                    style="border-bottom: 1px solid #444; font-family: monospace;">
                <label for="projected_to" class="active grey-text" style="transform: translateY(-1.2em) scale(0.8);">PROJECTED_TEMPORAL_COORDINATE</label>
            </div>
        </div>
    </div>
    <div id="new-stitch-form-location" class="tab-content hidden">
        <div id="stitch-geo-tools" class="grey darken-4 p-3 mb-3" style="border-radius: 8px; border: 1px solid #444;">
            <div class="row" style="margin: 20px 0 0 0;">
                <div class="input-field col s5">
                    <input id="stitch_lat" name="lat" type="number" step="any" class="white-text">
                    <label for="stitch_lat">LATITUDE</label>
                </div>
                <div class="input-field col s5">
                    <input id="stitch_lng" name="lng" type="number" step="any" class="white-text">
                    <label for="stitch_lng">LONGITUDE</label>
                </div>
                <div class="col s2 center-align">
                    <a id="btn-geo-locate" class="btn-floating btn-small waves-effect waves-light purple tooltipped" style="margin-top: 0.75rem;" data-tooltip="Use Current Location">
                        <i class="material-icons">my_location</i>
                    </a>
                </div>
            </div>
            <div class="center-align">
                <small class="grey-text">OR: <a href="#!" id="link-map-picker" class="orange-text">CLICK_ON_MAP_TO_PIN</a></small>
            </div>
        </div>
    </div>

    <div style="padding: 0 10px 0 2px;">
        <button type="submit" class="btn amethyst-anchor-btn waves-effect" style="width: 100%; border-radius: 0 0 8px 8px;">
            STITCH TO THE FIELD
        </button>
    </div>


</form>


<script>
    //$(document).ready(function() {

    document.addEventListener("DOMContentLoaded", function() {
        const $stage = $("#inline-stitch-stage");
        let $deck = $('#deck-container');
        let $form = $('#newStitchForm');
        let type = $('#dataType', $form).val();

        // Start checking for predefined coordinates every 5 seconds
        const checkInterval = setInterval(() => {
            if (typeof(mb.storage.apps.stitch.preferences.map_center) !== "undefined") {
                if ($('#stitch_lat', $form).val().length === 0) {
                    $('#stitch_lat', $form).val(mb.storage.apps.stitch.preferences.map_center.lat);
                    $('#stitch_lng', $form).val(mb.storage.apps.stitch.preferences.map_center.lng);
                    M.updateTextFields();
                }

                clearInterval(checkInterval); // Stops the timer
            }
        }, 1000);

        stitch.newStitchForm.init(type);



        $('.tab-buttons button, #new-stitch-form-context-menu a', $deck).each(function() {
            var $this = $(this);
            $targetID = $(this).data('target');
            $this.on('click', function(e) {
                e.preventDefault();
                $targetID = $(this).data('target');
                $target = $($targetID, $deck);
                $target.siblings().addClass('hidden');

                $('.tab-buttons button, #newStitchForm .context-menu', $deck).removeClass('active');
                $this.addClass('active');
                $target.removeClass('hidden');
                $stage.addClass("stage-active-pulse");
                setTimeout(() => $stage.removeClass("stage-active-pulse"), 1500);
            });
        })

        $(document).on('change', '#dataType', function() {
            const type = $(this).val();
            const container = $('#text-editor');

            container.scrollTop(0);
            container.html('<div class="progress"><div class="indeterminate"></div></div>');
            $('.tab-buttons button', $deck).first().click();

            mb.get('?api=stitch&action=get_form', {
                type: type
            }, function(res) {
                container.html(res.html);
                stitch.newStitchForm.init(type);
                $stage.addClass("stage-active-pulse");
                setTimeout(() => $stage.removeClass("stage-active-pulse"), 1500);

            });
        });

        $(document).on('change', '#stitch_visibility', function() {
            const val = $(this).val();
            let hint = "";
            switch (val) {
                case 'private':
                    hint = "Only you can see this. Stays in your local browser storage.";
                    break;
                case 'unlisted':
                    hint = "Hidden from the map, but anyone with the URL can view it.";
                    break;
                case 'public':
                    hint = "Visible to everyone on the 1706 Map and the Pasture feed.";
                    break;
            }
            $('#visibility_hint').text(hint);
            stitch.saveDraft(); // Save the preference to the draft immediately
        });

        // Trigger save on every input change
        // 1. Create a timer variable in the stitch scope
        stitch.newStitchAutoSaveTimeout = null;


        $('#projected_to').on('change', function() {
            if (typeof stitch !== 'undefined' && stitch.audio) {
                // A quick "Data Stream" blip to confirm the coordinate lock
                stitch.audio.lcars_stream(440);
                console.log("TEMPORAL_COORDINATE_SET: " + $(this).val());
            }
        });

        $('form#newStitchForm').on('submit', function(e) {
            e.preventDefault();

            if (!stitch.newStitchForm.validate()) {
                return;
            };

            const $form = $(this);
            const type = $('#dataType', $form).val();

            const data = new FormData(this);
            let formData = Object.fromEntries(data.entries());

            formData.content = stitch.newStitchForm.getContent(type);
            console.log($('#nexus_ids', $form).val());
            formData.content.nexus_ids = $('#nexus_ids', $form).val();
            //formData.nexus_ids = null;
            formData.content = JSON.stringify(formData.content);

            let jsonData = JSON.stringify(formData);

            mb.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: jsonData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        M.toast({
                            html: 'STITCH ADDED TO THE FIELD!',
                            classes: 'green'
                        });
                        mp3('computerbeep_44');

                        // --- THE SOVEREIGN HOOK ---
                        // We broadcast the new truth to Dad (and any other peers) INSTANTLY.
                        if (typeof pastureSync !== "undefined") {
                            pastureSync.broadcast(response.data.raw_data || response.data);
                            console.log("SHOUTED TO DAD! <3");
                        }
                        // --------------------------

                        //$form[0].reset();
                        $('#metaPreview').html('').hide();
                        stitch.newStitchForm.reset(type);
                        const $container = $("#stitch-card-container");
                        $container.prepend(response.data.html);
                        initStitchElements($container.find(".stitch-wrapper").first());
                        toggleStitchForm();
                    } else {
                        M.toast({
                            html: 'ERROR ADDING STITCH.',
                            classes: 'red'
                        });
                    }
                },
                error: function() {
                    M.toast({
                        html: 'ERROR ADDING STITCH.',
                        classes: 'red'
                    });
                }
            });
        });
    });
</script>