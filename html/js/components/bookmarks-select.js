/**
 * Bookmarks Select Component  
 * Alternative bookmarks interface for selection/editing
 */

mb.registerComponent('bookmarks-select', function($element, data) {
    console.log('Initializing bookmarks-select component', $element);
    
    function addLinkToBookmarkMenu(key) {
        $element.find('.bookmark_links').prepend(`
            <li class="verse_link">
                <a href="?app=bibleBot&s=${encodeURIComponent(key)}" target="_blank" data-key="${key}">
                    <i class="left material-icons">bookmark_border</i>${key}
                </a>
            </li>
        `);
    }
    
    function renderBookmarksMenu() {
        // Clear existing bookmarks
        $element.find('.bookmark_links').empty();
        
        // Render current bookmarks
        if (bibleBot.storage && bibleBot.storage.bookmarks && bibleBot.storage.bookmarks.length > 0) {
            bibleBot.storage.bookmarks.forEach(function(key) {
                addLinkToBookmarkMenu(key);
            });
        }
    }
    
    // Share button functionality
    $element.find('.share_btn').on('click', function() {
        if (bibleBot.storage && bibleBot.storage.bookmarks) {
            const shareUrl = location.protocol + '//' + location.host + '/search.php?s=' + 
                           encodeURIComponent(bibleBot.storage.bookmarks.join(';'));
            
            if (typeof copyText === 'function') {
                copyText(shareUrl);
                if (typeof notify === 'function') {
                    notify('Share link copied to clipboard.');
                }
            }
        } else {
            if (typeof notify === 'function') {
                notify('No bookmarks to share.');
            }
        }
    });
    
    // Initial render
    renderBookmarksMenu();
    
    // Listen for bookmark updates from other components
    $(document).on('bookmarks-updated', function() {
        renderBookmarksMenu();
    });
    
    console.log('Bookmarks select component initialized');
}, ['jquery', 'bibleBot']);