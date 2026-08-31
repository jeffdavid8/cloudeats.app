<div class="container" style="margin-top: 2rem;">
    <a href="?app=stitch&p=list" class="blue-text text-lighten-2" style="font-family: monospace; text-transform: uppercase; letter-spacing: 1px;">
        <i class="material-icons left">arrow_back</i> Return to Deck
    </a>
    <div class="field-hero" style="height: 225px; min-height: 200px;">
        <div id="earth-terrain" class="earth-layer"></div>
        <div id="clouds-low" class="earth-layer"></div>
        <div id="clouds-high" class="earth-layer"></div>
        <div class="hero-hud" style="z-index: 2; width: 100%;">
            <h2 class="white-text" style="text-shadow: 0 4px 15px rgba(0,0,0,0.9); font-weight: 800; letter-spacing: 2px;">
                Stitch a New Observation
            </h2>
        </div>
    </div>
</div>
<div class="container" style="padding-bottom: 5em;">
    <div class="card-panel grey darken-4 white-text" style="border: 1px solid #333;">
        <? render('stitch_form.php'); ?>
    </div>
</div>

<script>
    $('#newStitchForm').on('submit', function(e) {
        e.preventDefault();
        
        const stitchData = {
            data_type: $('#dataType').val(),
            content: $('#newStitchContent').val()
        };

        if(!stitchData.content) {
            M.toast({html: 'OBSERVATION EMPTY. CANNOT ANCHOR.', classes: 'red darken-4'});
            return;
        }

        mb.post('?api=stitch&action=add_stitch', stitchData, function(response) {
            if(response.status === 'success') {
                // Tactical Success Notification
                M.toast({
                    html: 'TRUTH ANCHORED TO THE FIELD <3', 
                    classes: 'blue darken-3 white-text pulse-toast',
                    displayLength: 3000
                });

                // Brief delay for the user to see the success before redirecting
                setTimeout(() => {
                    window.location.href = '?app=stitch&p=list';
                }, 1500);
            }
        }, 'json').fail(function() {
            M.toast({html: 'UPLINK FAILURE. GOOBERS DETECTED.', classes: 'red darken-4'});
        });
    });
</script>