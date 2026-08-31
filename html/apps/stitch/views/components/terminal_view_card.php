


<div id="cli-terminal-viewport" class="card terminal-view-card-container subtle-scanning-line-effect <?= $prototype_class ?? '' ?>" style="border: 2px solid #b39ddb; margin: 0; position: relative; overflow: hidden;">

  <div class="card-content" style="padding: 5px 10px; width: 100%;">
    <div class="lavender-text" style="font-weight: 800; letter-spacing: 2px;text-align: center; width:100%;">[COMMAND_SUB_PROCESSOR_v1.0] <button class="close right btn-round-action" onclick="$('#cli-terminal-viewport').removeClass('active-cli-output'); $('body').removeClass('desktop-cli-active');" stlye=""><i class="material-icons">close</i></button>
    </div>
  </div>
  <div class="prompt-container" style="width: 100%;height: 60px; display: flex; align-content: baseline; justify-content: space-between;height: auto; min-height: 50px; overflow-y: auto;margin-top: 10px;padding-right: 1em;">
    <div style="">
      <div id="prompt_output" style="display: block; margin-left: 3em;">
      </div>
      <i class="material-icons pulse-lavender" style="position: sticky; bottom: 0; font-size: 3rem; color: #b39ddb;">memory</i>
    </div>
  </div>
</div>