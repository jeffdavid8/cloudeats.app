<div class="card sub-processor-terminal grey darken-4 <?= $prototype_class ?? '' ?>" style="border: 2px solid #b39ddb; margin: 50px 0; position: relative; overflow: hidden;">
    
    <div class="card-content">
        <div class="row" style="margin-bottom: 0;">
            <div class="col s12 monospace">
                <span class="lavender-text" style="font-weight: 800; letter-spacing: 2px;">[ COMMAND_SUB_PROCESSOR_v1.0 ]</span>
                <span class="right grey-text" style="font-size: 0.7rem;">STATUS: ORIGIN_STABLE</span>
                <hr style="border: 0; border-top: 1px solid rgba(179, 157, 219, 0.3); margin: 10px 0;">
            </div>
        </div>

        <div class="row">
            <div class="col s12 m6 monospace grey-text" style="font-size: 0.8rem;">
                <p>> LOCATION: TEMPORAL_HORIZON_0001</p>
                <p>> DATA_INTEGRITY: 100%</p>
                <p>> ECHO_COUNT: <?= $total_count ?>_STITCHES</p>
            </div>
            <div class="col s12 m6 center-align">
                 <button onclick="scrollToTop()" class="btn-flat lavender-text border-lavender waves-effect">
                    <i class="material-icons left">vertical_align_top</i> RETURN_TO_PRESENT
                 </button>
                 <div style="margin-top: 10px;">
                    <button onclick="$('.master-command-prompt').focus()" class="btn-flat grey-text waves-effect" style="font-size: 0.7rem;">
                        LAUNCH_QUERY_PROBE
                    </button>
                 </div>
            </div>
        </div>
        
        <div class="center-align" style="margin-top: 20px;">
            <i class="material-icons pulse-lavender" style="font-size: 3rem; color: #b39ddb;">memory</i>
            <p class="monospace lavender-text" style="font-size: 0.6rem; margin-top: 10px;">SYSTEM_KERNEL_IDLE // STANDBY_FOR_INPUT</p>
        </div>
    </div>
</div>