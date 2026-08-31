/**
 * Background Selector Component
 * Handles background image selection and management
 */

mb.registerComponent('background-selector', function($element, data) {
    console.log('Initializing background-selector component', $element);
    
    const bucketDir = data.bucketDir || '';
    
    // Get image data from JSON
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
        $element.find('.remove-bg-btn, .transparentBtn').on('click', function() {
            $('body').removeClass('image_bg');
            $('body').css('background-image', '');
            $element.find('.active').removeClass('active');
            $(this).addClass('active');
            
            if (typeof bibleBot !== 'undefined') {
                bibleBot.backgroundImageIndex = null;
            }
            
            $element.trigger('background-removed');
            console.log('Background removed');
        });
        
        // Random background button
        $element.find('.random-bg-btn, .randomBtn').on('click', function() {
            const randomIndex = Math.floor(Math.random() * imageNames.length);
            $element.find(`[data-index="${randomIndex}"]`).click();
        });
        
        // Set current background button
        $element.find('.selectCurrentBackgroundBtn').on('click', function() {
            const currentIndex = $(this).data('index');
            if (currentIndex !== undefined && currentIndex !== '') {
                $element.find(`[data-index="${currentIndex}"]`).click();
            }
        });
        
        console.log(`Background selector initialized with ${imageNames.length} images`);
    });
}, ['jquery', 'mb', 'bibleBot']);