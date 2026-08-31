<?
if (!defined('MB_RUNNING')) exit;
$total_count = $this->get('total_count');

render('components/terminal_view_card.php');
?>
<div class="command-prompt-container">
  <div class="toolbar-brand" style="float: left; display: block; width: 110px;">
    <span class="purple-text monospace" style="font-weight: 800;">THE_FIELD</span>
    <span class="grey-text" style="font-size: 0.7rem;">(<?= $total_count ?>_OBS)</span>
  </div>

  <div class="cli-wrapper" style="display: flex; align-items: center; min-height: 50px">
    <textarea id="stitch-cli" class="master-command-prompt"
      placeholder='Type "/help" for help... (Enter to send, Shift+Enter for new line)'
      rows="1"></textarea>
    <a href="javascript:void(0)" id="send-command-btn" class="lavender-text" style="margin-top: 12px; margin-left: 10px;">
      <i class="material-icons">send</i>
    </a>

  </div>
</div>

<script>
  $(document).ready(function() {
    const $prompt = $('.master-command-prompt');
    const $viewport = $('#cli-terminal-viewport');

    $('#terminal-toggle-btn a').on('click', function(e) {
      e.preventDefault();
      if ($viewport.hasClass('active-cli-output')) {
        $viewport.removeClass('active-cli-output');
        $('body').removeClass('desktop-cli-active');
      } else {
        $viewport.addClass('active-cli-output');
        $('body').addClass('desktop-cli-active');
        $prompt.focus();
      }
    });

    $('#cli-toggle').on('click', function() {
      $viewport.removeClass('active-cli-output');
      return false;
    });

    // 🚀 Auto-Expand Logic
    $prompt.on('input', function() {
      this.style.height = 'auto';
      this.style.height = (this.scrollHeight) + 'px';

      // If it reaches max-height, show scrollbar
      if (this.scrollHeight > 200) {
        $(this).css('overflow-y', 'auto');
      } else {
        $(this).css('overflow-y', 'hidden');
      }
    });

    // ⌨️ Keyboard Handler
    // 🏗️ CLI HISTORY STATE
    let historyIndex = -1;

    $prompt.on('keydown', function(e) {
      // Ensure we have a place to store history in our mb object
      if (!mb.storage.apps.stitch.cli_history) {
        mb.storage.apps.stitch.cli_history = [];
      }
      const history = mb.storage.apps.stitch.cli_history;

      // 🟢 1. HANDLE ENTER (Execute & Save)
      if (e.which == 13 && !e.shiftKey) {
        e.preventDefault();
        const val = $(this).val().trim();

        if (val) {
          // Only add to history if it's different from the last command (no duplicates)
          if (history[history.length - 1] !== val) {
            history.push(val);
            // Keep history lean - last 50 commands
            if (history.length > 50) history.shift();
            storage_set(); // Persist to browser storage
          }

          historyIndex = -1; // Reset index
          processMasterCommand(val);
          $(this).val('').css('height', 'auto');
        }
      }

      // 🔵 2. HANDLE ARROW UP (Previous Command)
      else if (e.which == 38) {
        if (history.length > 0) {
          if (historyIndex === -1) historyIndex = history.length;

          if (historyIndex > 0) {
            historyIndex--;
            $(this).val(history[historyIndex]);
            // Move cursor to end
            setTimeout(() => this.setSelectionRange(this.value.length, this.value.length), 0);
          }
        }
      }

      // 🔴 3. HANDLE ARROW DOWN (Next Command / Clear)
      else if (e.which == 40) {
        if (historyIndex !== -1) {
          if (historyIndex < history.length - 1) {
            historyIndex++;
            $(this).val(history[historyIndex]);
          } else {
            historyIndex = -1;
            $(this).val('');
          }
        }
      }
    });
    
    $prompt.focusin(function() {
      $viewport.addClass('active-cli-output');
      assignActiveViewport($('#terminal-container')[0]);
    });

    $('#send-command-btn').on('click', function(e) {
      e.preventDefault();
      const val = $prompt.val().trim();
      if (val) {
        processMasterCommand(val);
        $prompt.val('').css('height', 'auto'); // Reset after send
      }
    });

    // Event handler when the textarea loses focus
    /*
    $prompt.focusout(function() {
      $viewport.removeClass('active-cli-output');
    });
    */

    function processMasterCommand(input) {
      if (stitch.audio) stitch.audio.lcars_access();

      processCommand(input, $('#prompt_output')); // Use the logic we built for the Horizon Terminal
    }
  });
</script>