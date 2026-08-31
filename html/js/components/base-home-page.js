/**
 * Base System Home Page Component
 * Handles command execution and command element interactions
 */

mb.registerComponent('base-home-page', function($element, data) {
    
    function execute(string) {
        var command = string.split(" ")[0];
        var params = string.split(" ");
        params.shift();
        params = params.join(' ');
        if (command.length && (command in mb.commands)) {
            mb.commands[command](params);
        }
    }

    // Make execute function globally available for any inline command calls
    window.execute = execute;

    // Initialize tooltips
    $('.tooltipped').tooltip();

    // Handle command element clicks
    $('.command-element').on('click', function(e) {
        e.preventDefault();
        var command = $(this).data('command');
        if (command) {
            execute(command);
        }
    });

}, ['mb']);