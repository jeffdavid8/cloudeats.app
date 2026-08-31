/**
 * Command Elements Component
 * Handles command element click interactions on the home page
 */

mb.registerComponent('command-elements', function($element, data) {
  // All dependencies (jQuery) are guaranteed to be ready
  
  function processElement(el) {
    var $el = $(el).on('click', function(e){
      var command = $(this).attr('data-command');
      if (command.length) {
        e.preventDefault();
      }
      e.stopPropagation();

      // Execute command using global execute function
      if (typeof execute === 'function') {
        execute(command);
      }
    });
    $el.attr('title', $el.attr('data-command'));
    return $el;
  }
  
  // Process all command elements within this component
  $element.find('.command-element').each(function(){
    processElement(this);
  });

}, ['jquery']); // Dependencies: jQuery only