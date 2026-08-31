/**
 * BibleBot Programmatic Page Component
 * Handles Blockly configuration and storage initialization
 */

mb.registerComponent('biblebot-programmatic-page', function($element, data) {
    // Initialize Blockly Storage if available
    if ('BlocklyStorage' in window) {
        BlocklyStorage.HTTPREQUEST_ERROR = 'There was a problem with the request.\n';
        BlocklyStorage.LINK_ALERT = 'Share your blocks with this link:\n\n%1';
        BlocklyStorage.HASH_ERROR = 'Sorry, "%1" doesn\'t correspond with any saved Blockly file.';
        BlocklyStorage.XML_ERROR = 'Could not load your saved file.\n'+
            'Perhaps it was created with a different version of Blockly?';
    } else {
        // Handle case where cloud storage is not available
        const contentArea = document.getElementById('content_area');
        if (contentArea) {
            contentArea.innerHTML = '<p id="sorry">Sorry, cloud storage is not available. This demo must be hosted on App Engine.</p>';
        }
    }
}, []);