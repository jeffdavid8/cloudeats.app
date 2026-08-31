/**
 * Bookmarks Menu Component
 * Displays and manages bookmark navigation menu
 */

mb.registerComponent('bookmarks-menu', function($element, data) {
    console.log('Initializing bookmarks-menu component', $element);
    
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
    
    // Import bookmarks functionality
    $element.find('.import_bookmarks_btn').on('click', function() {
        $element.find('#bookmarks_file_import').click();
    });
    
    $element.find('#bookmarks_file_import').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const importedBookmarks = JSON.parse(e.target.result);
                    if (Array.isArray(importedBookmarks)) {
                        bibleBot.storage.bookmarks = importedBookmarks;
                        renderBookmarksMenu();
                        if (typeof notify === 'function') {
                            notify(`Imported ${importedBookmarks.length} bookmarks`);
                        }
                    }
                } catch (error) {
                    console.error('Invalid bookmark file format:', error);
                    if (typeof notify === 'function') {
                        notify('Invalid bookmark file format');
                    }
                }
            };
            reader.readAsText(file);
        }
    });
    
    // Initial render
    renderBookmarksMenu();
    
    // Listen for bookmark updates from other components
    $(document).on('bookmarks-updated', function() {
        renderBookmarksMenu();
    });
    
    // Expose refresh method for external use
    window.refreshBookmarksMenu = renderBookmarksMenu;
    
    console.log('Bookmarks menu component initialized');
}, ['jquery', 'bibleBot']);