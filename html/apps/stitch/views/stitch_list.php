<? if (!defined('MB_RUNNING')) exit; ?>
<?
/**
 * Stitch List
 * @var Object $dates
 * @var String $announcement
 * 
 */
?>
<div class="hero-container">
    <div class="container">
        <div class="container" style="position: relative;">
            <div class="ribbon-wrapper">
                <div class="">BETA</div>
            </div>
            <div class="field-hero">
                <div id="earth-terrain" class="earth-layer"></div>
                <div id="clouds-low" class="earth-layer"></div>
                <div id="clouds-high" class="earth-layer"></div>
                <div class="hero-hud" style="z-index: 2; position: relative; width: 100%;">
                    <div id="time-display" class="purple-text monospace"></div>
                    <h2 class="white-text" style="text-shadow: 0 4px 15px rgba(0,0,0,0.9); font-weight: 800; letter-spacing: 2px;">
                        OBSERVATION DECK
                    </h2>
                    <p class="grey-text text-lighten-1" style="margin: 0; font-family: monospace;">
                        SYSTEM STATUS: ACTIVE <br/>
                        // DRIFT: NOMINAL</p>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="terminal-output"></div>

<div id="stitch-list-container" class="container">


    <div id="stitch-card-container">

        <? /* ?>
        <?php if (empty($anchors)): ?>
            <div class="card-panel grey darken-4 grey-text center">
                <p>The field is quiet. Add a stitch to anchor a new observation.</p>
            </div>
        <?php else: ?>
            <?php foreach ($anchors as $index => $anchor): ?>
                <?php
                $isBranch = !empty($anchor->parent_id);
                $glowClass = ($anchor->vouch_count >= 5) ? 'high-fidelity-glow' : '';

                render('components/stitch_card.php', array('anchor' => $anchor, 'isBranch' => $isBranch, 'glowClass' => $glowClass));
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <? */ ?>

        <div id="horizon-sentinel" style="min-height: 50px; margin-top: 2rem; text-align: center;"></div>
    </div>

    <div id="terminal-toggle-btn" class="fixed-action-btn">
        <a class="btn-floating btn-large z-depth-3 waves-effect">
            <i class="fas fa-terminal"></i>
        </a>
    </div>

    <div id="ascension-btn" class="fixed-action-btn" style="display: none;">
        <a class="btn-floating btn-large purple darken-3 z-depth-3 waves-effect waves-light">
            <i class="large material-icons">arrow_upward</i>
        </a>
    </div>

</div>


<div id="modal-importer" class="modal mb-modal-fixed grey darken-4 white-text">
    <div class="modal-header">
        <h4><i class="material-icons">settings_input_component</i> Data_Ingestion_Engine</h4>
        <hr style="border-color: #444;">
    </div>
    <div class="modal-content">


        <div id="import-step-1">
            <p>Select a GEDCOM (.ged) or JSON file to begin the translation process.</p>
            <div class="file-field input-field">
                <div class="btn purple">
                    <span>File</span>
                    <input type="file" id="import-file-input" accept=".ged,.json">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate white-text" type="text" placeholder="Upload ancestry or historical data">
                </div>
            </div>
        </div>

        <div id="import-step-2" style="display:none;">
            <h6><i class="material-icons tiny">check_circle</i> DETECTED: <span id="detected-type" class="orange-text">GEDCOM</span></h6>
            <p>We found <span id="record-count" class="purple-text">0</span> ancestors. Review and select which ones to commit to your <strong>Private Vault</strong>.</p>

            <div id="import-preview">
            </div>
        </div>
    </div>
    <div class="modal-footer grey darken-4">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat white-text">Cancel</a>
        <a href="#!" id="btn-process-import" class="waves-effect waves-light btn purple disabled">Analyze File</a>
        <a href="#!" id="btn-commit-import" class="waves-effect waves-light btn green" style="display:none;">Commit to Vault</a>
    </div>
</div>

<div id="nexus-masque-overlay"></div>

<div id="nexus-custom-window">

    <div class="custom-window-header">
        <a href="#!" onclick="closeNexusWindow()" class="modal-close btn-flat purple-text font-monospace waves-effect waves-light" style="font-weight: bold;">×</a>
        <h4 class="purple-text text-lighten-2 font-monospace">TEMPORAL_VIEWPORT: <span id="nexus-layer-year">....</span></h4>
    </div>

    <div class="custom-window-body">
        <div id="nexus-modal-results">


        </div>
    </div>

    <div class="custom-window-footer">
        <div id="modal-back-btn" style="display:none; margin-bottom: 10px;">
            <button onclick="goBackInTime()" class="btn-small waves-effect waves-light purple lighten-2 black-text" style="font-family: monospace; font-weight: bold;">
                <i class="material-icons left">arrow_back</i>
                <span id="modal-back-btn-text">RETURN_TO_PREVIOUS_ERA</span>
            </button>
        </div>
    </div>
</div>

<div id="nexus-overlay" class="positronic-matrix-container" style="display:none;">
    <div id="matrix-loader" class="matrix-grid-overlay">
        <div class="sync-text">SYNCING_POSITRONIC_FUNCTIONS...</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill"></div>
        </div>
    </div>

    <div id="nexus-chip-cloud" class="chip-cloud">
    </div>
    <div id="positronic-matrix-view" class="nexus-overlay-viewport" style="width: 100%; height: 80vh;">
        <div class="" style="display: flex; height: 100%; width: 100%; text-align: center;">
            <div class="quantum-spinner" style="margin: auto;height: 75px;"></div>
        </div>
    </div>

    <div id="leaflet-map-view" class="nexus-overlay-viewport" style="width: 100%; height: 100%; background: #111; border-bottom: 2px solid #333; position: relative; z-index: 5;">
    </div>

    <div class="nexus-overlay-actions">
        <button onclick="stitch.geo.view();">MAP</button>
        <button onclick="$('#nexus-overlay .nexus-overlay-viewport').hide();$('#positronic-matrix-view').show();">VIS</button>
        <button class="btn-exit-field" onclick="closeNexusOverlay()">EXIT_MATRIX</button>
    </div>
</div>

<div id="map-picker-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; background:rgba(0,0,0,0.85); backdrop-filter: blur(5px);">
    <a href="#!" id="close-map-picker" class="btn-floating btn-large red" style="position:absolute; top:20px; left:20px; z-index:10001;">
        <i class="material-icons">close</i>
    </a>

    <div id="picker-map-canvas" style="width:100%; height:100%;"></div>

    <div style="position:absolute; bottom:30px; left:50%; transform:translateX(-50%); z-index:10001; background:rgba(0,0,0,0.7); padding:10px 20px; border-radius:20px; border:1px solid #9b59b2;">
        <span class="white-text uppercase">Click to Set Anchor Coordinates</span>
    </div>
</div>

<? /*
<div class="hide-on-med-and-up" style="position: fixed; bottom: 100px; width: 100%; bottom: 100px;">
    <div class="command-prompt-container">
        <div style="display: flex; align-items: flex-start;">
            <textarea class="master-command-prompt"
                placeholder="Type a command or search the field... (Enter to send, Shift+Enter for new line)"
                rows="1"></textarea>
            <a href="javascript:void(0)" id="send-command-btn" class="lavender-text" style="margin-top: 12px; margin-left: 10px;">
                <i class="material-icons">send</i>
            </a>
        </div>
    </div>
</div>
  */ ?>

<? render('components/command-sub-processor-card.php', array('prototype_class' => 'prototype')); ?>


<script>
    $(document).on('change', '#import-file-input', function() {
        $('#btn-process-import').removeClass('disabled');
    });

    $(document).on('click', '#btn-process-import', function() {
        const file = $('#import-file-input')[0].files[0];
        console.log(file);
        if (!file) return;
        let fname = file.name;

        // 1. Show loading state
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Analyzing...');

        // 2. Send to your PHP ImporterEngine via AJAX
        let formData = new FormData();
        formData.append('file', file);

        mb.ajax({
            url: '?api=stitch&action=import_file&file_name=' + fname, // Your new endpoint
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                // We override the default JSON content-type from mb.ajax
                // so the browser can automatically set multipart/form-data + boundary
                "Content-Type": false,
                "X-CSRF-TOKEN": mb.csrf_token,
                "Authorization": "Bearer " + mb.csrf_token
            },
            success: function(response) {
                response = JSON.parse(response);
                // response contains the 'staged' ancestor_data
                if (response.status === 'success') {
                    $('#import-step-1').hide();

                    // 1. Inject the PHP-rendered HTML
                    $('#import-preview').html(response.html);

                    // 2. Store the raw data in a global variable so we can use it when "Commit" is clicked
                    mb.stagedImportData = response.raw;

                    $('#import-step-2').fadeIn();
                    $('#btn-process-import').hide();
                    $('#btn-commit-import').show();

                    // Update a counter if you have one
                    $('#record-count').text(response.raw.length);
                } else {
                    M.toast({
                        html: 'Import failed: ' + response.message
                    });
                }

                // Populate the preview table
                // we need to figure out how to render php views with javascript i think...
                console.log(response);
            }
        });
    });

    $(document).on('change', '#select-all-import', function() {
        $('.import-person-check').prop('checked', $(this).prop('checked'));
    });

    $(document).on('click', '#btn-commit-import', function() {
        // 1. Find which rows the user actually wants to keep
        let selectedIndices = [];
        let btn = this;
        $('.import-person-check:checked').each(function() {
            selectedIndices.push($(this).val());
        });

        if (selectedIndices.length === 0) {
            M.toast({
                html: 'Please select at least one person to import.'
            });
            return;
        }
        const originalHtml = btn.innerHTML;

        // 1. Start the animation
        // 1. Show loading state
        $(btn).html('<i class="fas fa-spinner fa-spin"></i> COMMITTING...');
        btn.classList.add('disabled');
        /*
        btn.innerHTML = `
        <div class="preloader-wrapper tiny active" style="width:20px; height:20px; vertical-align:middle; margin-right:8px;">
            <div class="spinner-layer spinner-white-only">
                <div class="circle-clipper left"><div class="circle"></div></div>
                <div class="gap-patch"><div class="circle"></div></div>
                <div class="circle-clipper right"><div class="circle"></div></div>
            </div>
        </div> 
        COMMITTING...`; 
        */

        // 2. Filter our staged data
        let dataToSave = selectedIndices.map(idx => mb.stagedImportData[idx]);

        // 3. Send it to the "commit_import" action
        mb.ajax({
            url: '?api=stitch&action=commit_import',
            type: 'POST',
            data: JSON.stringify({
                people: dataToSave
            }),
            contentType: 'application/json',
            success: function(response) {
                //response = JSON.parse(response);
                if (response.status === "success") {
                    M.toast({
                        html: 'Successfully imported ' + response.count + ' ancestors!'
                    });
                    $('#modal-import').modal('close');
                    btn.innerHTML = '<i class="material-icons left">cloud_done</i> SECURED';
                    btn.classList.remove('disabled');

                    // Optional: Refresh your map/view here

                } else {
                    M.toast({
                        html: 'Error: ' + response.message
                    });
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('disabled');
                }
            }
        });
    });

    // 🧪 This function fires when the YouTube API is ready
    window.onYouTubeIframeAPIReady = function() {
        $('.stitch-video').each(function() {
            var id = $(this).attr('id');
            if (!window.videoPlayers[id]) {
                window.videoPlayers[id] = new YT.Player(id);
            }
            
        });
    }

    window.initializeVideoPlayers = function(container) {
        container = (!container) ? $(document) : container;
        // 🕵️ THE OBSERVER: Watches for videos entering/leaving view
        const observerOptions = {
            root: null, // Use the viewport
            threshold: 0.6 // 🎯 Trigger when 60% of the video is visible
        };
        const videoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                var id = $(entry.target).attr('id');

                if (window.videoPlayers[id] && typeof window.videoPlayers[id].playVideo === 'function') {
                    if (entry.isIntersecting) {
                        //console.log("▶️ SIGNAL_PLAY: Video in view ->", id);
                        window.videoPlayers[id].playVideo();
                    } else {
                        //console.log("⏸️ SIGNAL_PAUSE: Video out of view ->", id);
                        window.videoPlayers[id].pauseVideo();
                    }
                }
            });
        }, observerOptions);

        // 🏗️ Attach the observer to all videos
        $('.stitch-video', container).each(function() {
            videoObserver.observe(this);
        });
    }


    $(document).ready(function() {


        mb.announce(<?php echo json_encode($announcement); ?>);
        $('#modal-importer').modal();

        // Initial video interfaces
        window.videoPlayers = {}; // 🗃️ The Ledger of active players
        
        initializeVideoPlayers();        

        $('#nexus-masque-overlay').click(function() {
            closeNexusWindow();
        });

        $('.btn-exit-field').on('click', function() {
            // 🔊 The "Power Down" Sound
            const ctx = stitch.audio.init();

            if (ctx) {
                ctx.resume().then(() => {
                    // 🔊 2. PLAY THE THUD
                    // We'll use a slightly higher frequency (300) because 200 might be 
                    // too low for some laptop speakers to hear clearly.
                    stitch.audio.lcars_stream(300);
                    console.log("🔊 EXIT_SONIC_DISCHARGE_SUCCESSFUL");
                });
            } // Low frequency thud

            // Fade out the matrix
            $('#nexus-overlay').fadeOut(400, function() {
                // Stop any background processes if needed
                console.log("SYSTEM: Matrix Disengaged.");
            });
        });
        stitch.filter.start_date.created_at = '<?= date('Y-m-d', strtotime($dates['created_at_start_date'])) ?>';
        stitch.filter.start_date.projected_to = '<?= date('Y-m-d', strtotime($dates['projected_to_start_date'])) ?>';
        stitch.filter.end_date.created_at = '<?= date('Y-m-d', strtotime($dates['created_at_end_date'])) ?>';
        stitch.filter.end_date.projected_to = '<?= date('Y-m-d', strtotime($dates['projected_to_end_date'])) ?>';
        let dimension = mb.storage.apps.stitch.preferences.stitch_dimension;
        let isHistorical = (dimension === 'created_at');
        let start_date = (isHistorical) ?
            stitch.filter.start_date.created_at :
            stitch.filter.start_date.projected_to;
        let end_date = (isHistorical) ?
            stitch.filter.end_date.created_at :
            stitch.filter.end_date.projected_to;

        $('#date-end').val(end_date);
        $('#date-end').trigger('change');
        $('#date-start').val(start_date);
        $('#date-start').trigger('change');


        const label = isHistorical ? "HISTORICAL_MAP" : "DISCOVERY_FEED";
        $('#dimension-status').text(`MODE: ${label}`).toggleClass('cyan-text', !isHistorical).toggleClass('purple-text', isHistorical);
        // Set the initial dimension toggle state
        $("#dimension-toggle").attr("checked", function() {
            return (mb.storage.apps.stitch.preferences.stitch_dimension === 'created_at');
        });


        // 🛰️ THE UNIVERSAL FETCH
        function fetchStitchBatch(params) {
            if (window.isQuerying) return;
            window.isQuerying = true;

            $('#time-display').addClass('pulse').text("SYNCING_THE_FIELD...");

            $.getJSON('?api=stitch&action=load_more', params, function(response) {
                if (response.status === 'success' && response.data.html.length > 0) {
                    const $newElements = $(response.data.html);
                    $('#stitch-card-container').append($newElements);

                    // 🎯 Call the one function to rule them all!
                    initStitchElements($newElements);

                    mb.logMission(`MATERIALIZED ${response.count} NEW TRUTHS.`);
                }
                window.isQuerying = false;
                $('#time-display').removeClass('pulse');
            });
        }
        let isQuerying = false;

        // A: THE VISUAL FEEDBACK (While dragging)
        let isTicking = false;

        // 1. REAL-TIME HUD UPDATE (Lightweight)
        $(document).on('input', '#time-slider', function() {
            const depth = $(this).val();
            // Grab dates from your Materialize DatePickers or Inputs
            const start = new Date($('#date-start').val()).getTime();
            const end = new Date($('#date-end').val()).getTime();
            // Same math as the PHP
            const target = start + ((end - start) * (depth / 100));
            const dateLabel = new Date(target).toLocaleDateString() + ' ' + new Date(target).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

            $('#time-display').html(`<span class="purple-text monospace">WARP_TO:</span> ${dateLabel}`);
        });

        $(document).on('change', '#time-slider', function() {

            $("#time-slider").prop('disabled', true);
            // 🎯 Just trigger the unified fetcher in 'replace' mode!
            stitch.api('chronos_dial', {
                callback: function(data) {

                    renderNewStitches(data.html, function() {
                        window.isQuerying = false;
                        $("#time-slider").prop("disabled", false);
                    });

                }
            });
        });

        // Intercept the 'New Stitch' buttons
        $('.btn-new-stitch, .btn-floating.btn-new-stitch').on('click', function(e) {
            e.preventDefault();
            const $stage = $('#inline-stitch-stage');
            stitch.audio.lcars_stream(900);
            // 1. If it's already open, just scroll to it (in case they lost it)
            toggleStitchForm();
            return;

        });

    });

    function filterLocalCards(depth) {
        const $cards = $("#stitch-card-container .stitch-wrapper"); // 🎯 FIX: Targeted the correct wrapper class
        const total = $cards.length;

        // Calculate how many cards to keep visible
        const visibleCount = Math.floor((depth / 100) * total);

        $cards.each(function(index) {
            // Reversing the logic: keep the 'Past' (bottom of list) visible
            // and peel away the 'Present' (top of list)
            if (index >= total - visibleCount) {
                $(this).stop(true, true).fadeIn(300).removeClass("chronos-glitch");
            } else {
                $(this).stop(true, true).fadeOut(300).addClass("chronos-glitch");
            }
        });

        //const status = depth == 100 ? "ALL_TIME" : `CHRONOS_DEPTH: ${depth}%`;
        //$("#time-display").text(status);
        mb.logMission(`Temporal Shift: Depth set to ${status}.`);
    }

    // 🛰️ SENSOR: Determine if the requested depth is beyond our current cache
    function needsMoreData(depth) {
        const $cards = $('#stitch-card-container .stitch-wrapper');
        if ($cards.length === 0) return true;

        // Get the timestamp of the oldest card we currently have
        // (Assuming the list is sorted Newest -> Oldest)
        const oldestLoaded = parseInt($cards.last().attr('data-timestamp'));

        // Simple logic: If they slide below 20% and we only have recent stuff, fetch more.
        // In a more advanced version, we'd map 'depth' to a specific Unix timestamp.
        return depth < 20;
    }

    // 🛰️ ACTUATOR: Quantum Tunneling the data from the server
    function fetchOlderStitches() {
        if (window.isQuerying) return;
        window.isQuerying = true;
        // 🧹 KILL THE OLD DATA
        $('#stitch-card-container').empty();
        $('#horizon-sentinel').html('<div class="purple-text">WARPING...</div>');
        const $sentinel = $('#horizon-sentinel');
        // 🌀 Show loading state
        $sentinel.html('<div class="quantum-spinner"></div> <span class="purple-text monospace">PROBING_DEEP_ARCHIVE...</span>');

        const lastId = $('#stitch-card-container .stitch-wrapper').last().attr('data-id');
        const searchTerm = $('#observationSearch').val() || '';
        const depth = parseInt($('#time-slider').val());
        let actionKey = (depth < 5) ? 'after_id' : 'before_id';
        let url = `?api=stitch&action=fetch_history&depth=${depth}&search=${encodeURIComponent(searchTerm)}&csrf_token=${mb.csrf_token}`;

        $.get(url, function(response) {
                if (response.data.html.trim().length > 0) {
                    $('#stitch-card-container').append(response.data.html);
                    $sentinel.html(''); // Clear loading for now

                    // Re-trigger the slider check for the new cards
                    $('#time-slider').trigger('change');
                } else {
                    // 🛑 HORIZON REACHED
                    $sentinel.html('<div class="grey-text monospace" style="padding: 20px;">[ HORIZON_REACHED ]</div>');
                    // Stop observing so we don't keep trying to fetch nothing
                    if (window.stitchObserver) window.stitchObserver.unobserve($sentinel[0]);
                }
            })
            .always(() => {
                window.isQuerying = false;
            });
    }
</script>