/**
 * MediaBrain Component Definitions
 * Registers component types that will be auto-discovered via data-component attributes
 */

// Wait for component registry to be available
$(document).ready(function() {
    if (typeof mb === 'undefined' || typeof mb.registerComponent === 'undefined') {
        console.warn('Component registry not available, retrying component definitions...');
        setTimeout(arguments.callee, 50);
        return;
    }

    // Background Selector Component
    mb.registerComponent('background-selector', function($element, data) {
        console.log('Initializing background-selector component', $element);
        
        const bucketDir = data.bucketDir || '';
        
        // Get image data
        mb.getJson('apps/bibleBot/json/share_images.json', function(imageNames) {
            if (!imageNames || !Array.isArray(imageNames)) {
                console.error('Invalid image data for background-selector');
                return;
            }
            
            // Thumbnail button clicks
            $element.find('.thumb-btn').on('click', function() {
                const selectedIndex = $(this).data('index');
                
                if (typeof bibleBot !== 'undefined') {
                    bibleBot.backgroundImageIndex = selectedIndex;
                }
                
                // Update UI within this component
                $element.find('.active').removeClass('active');
                $(this).addClass('active');
                
                // Apply background
                $('body').addClass('image_bg');
                $('body').css('background-image', `url(${bucketDir}/${imageNames[selectedIndex]}.jpg)`);
                
                // Trigger custom event
                $element.trigger('background-changed', {
                    index: selectedIndex,
                    imageName: imageNames[selectedIndex]
                });
                
                console.log(`Background changed to: ${imageNames[selectedIndex]}`);
            });
            
            // Remove background button
            $element.find('.remove-bg-btn').on('click', function() {
                $('body').removeClass('image_bg');
                $('body').css('background-image', '');
                $element.find('.active').removeClass('active');
                
                if (typeof bibleBot !== 'undefined') {
                    bibleBot.backgroundImageIndex = null;
                }
                
                $element.trigger('background-removed');
                console.log('Background removed');
            });
            
            // Random background button
            $element.find('.random-bg-btn').on('click', function() {
                const randomIndex = Math.floor(Math.random() * imageNames.length);
                $element.find(`[data-index="${randomIndex}"]`).click();
            });
            
            console.log(`Background selector initialized with ${imageNames.length} images`);
        });
    }, ['jquery', 'mb', 'bibleBot']);

    // Bookmarks Menu Component  
    mb.registerComponent('bookmarks-menu', function($element, data) {
        console.log('Initializing bookmarks-menu component', $element);
        
        function addLinkToBookmarkMenu(key) {
            $element.find('.bookmark_links').prepend(
                `<li class="verse_link">
                    <a href="?app=bibleBot&s=${key}" target="_blank" data-key="${key}">
                        <i class="left material-icons">bookmark_border</i>${key}
                    </a>
                </li>`
            );
        }
        
        function renderBookmarksMenu() {
            $element.find('.bookmark_links').empty();
            
            if (bibleBot.storage && bibleBot.storage.bookmarks && bibleBot.storage.bookmarks.length > 0) {
                bibleBot.storage.bookmarks.forEach(function(key) {
                    addLinkToBookmarkMenu(key);
                });
            }
        }
        
        // Share button functionality
        $element.find('.share_btn').on('click', function() {
            if (bibleBot.storage && bibleBot.storage.bookmarks) {
                const shareUrl = location.protocol + '//' + location.host + '/search.php?s=' + bibleBot.storage.bookmarks;
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
        
        // Listen for bookmark updates
        $(document).on('bookmarks-updated', renderBookmarksMenu);
        
        // Expose refresh method
        window.refreshBookmarksMenu = renderBookmarksMenu;
        
        console.log('Bookmarks menu component initialized');
    }, ['jquery', 'bibleBot']);
    
    console.log('Component definitions loaded successfully');
});