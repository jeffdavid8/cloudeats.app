/**
 * Star Trek Sounds Component
 * Manages Star Trek sound file links and potential downloading functionality
 * Contains disabled download functionality for audio files from TrekCore
 */
mb.registerComponent('star-trek-sounds', function($element, data) {
    // Initialize the component
    console.log('Star Trek Sounds component initialized');
    
    // The downloading functionality is currently disabled
    // When enabled, this would iterate through all audio links and download them
    function initializeDownloader() {
        const downloadingEnabled = false; // Downloading Disabled until needed again
        
        if (downloadingEnabled) {
            $('#links a').each(function() {
                const url = 'https://www.trekcore.com/audio/' + $(this).attr('href');
                const filename = url.split('/').pop();
                const toPath = 'I:/cloud/web/www/mediabrain.net/local/audio/star trek sounds/' + filename;
                const package = {
                    'action': 'download',
                    'data': {
                        'url': url,
                        'toPath': toPath,
                    }
                };
                
                $.ajax({
                    url: 'api.php',
                    dataType: 'json',
                    data: package,
                    success: function(data) {
                        // console.log(data);
                    },
                    error: function(response) {
                        // console.log('There was a problem with the api call');
                        console.log(response);
                    },
                });
            });
        }
    }
    
    // Initialize the downloader (currently disabled)
    initializeDownloader();
    
    // Add any future interactive functionality here
    // For example, play sound previews, organize sounds, etc.
});