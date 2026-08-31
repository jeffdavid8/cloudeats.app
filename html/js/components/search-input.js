/**
 * Search Input Component
 * Manages primary search functionality with autocomplete and command execution
 * Handles real-time search results and command processing
 */
mb.registerComponent('search-input', function($element, data) {
    console.log('Search Input component initialized');
    
    // Component data
    const autoIndex = data.autoIndex || [];
    let searchResults = [];
    let timer;
    
    // Find the search field within this component
    const $searchField = $element.find('#index-search-field');
    
    function updateSearch(e) {
        clearTimeout(timer);
        const search = $searchField.val().toLowerCase();
        
        if (search === '') {
            $('#search_results').html('');
            return;
        }

        // Command Execution
        if (e.keyCode === 13) { 
            if (typeof execute === 'function') {
                execute(search);
            } else if (typeof mb.commands !== 'undefined' && typeof mb.commands.execute === 'function') {
                mb.commands.execute(search);
            } else {
                console.warn('No execute function available for command:', search);
            }
        }

        // Update quick search results
        timer = setTimeout(function() {
            $('#search_results').html('');
            searchResults = []; // Reset results
            
            $.each(autoIndex, function(key, item) {
                if ((item.title.toLowerCase().includes(search)) || (item.keywords.includes(search))) {
                    searchResults.push({
                        'name': item.title,
                        'markup': item.markup,
                    });
                    
                    const $item = processElement(item.markup);
                    const $result = $('<div class="item center promo col s3 m4 l2"></div>');
                    $result.append($item);
                    $('#search_results').append($result);
                }
            });
        }, 300);
    }

    function processElement(markup) {
        // Process the markup for search results
        // This function might need to be defined elsewhere or passed as data
        if (typeof window.processElement === 'function') {
            return window.processElement(markup);
        } else {
            // Fallback: create a simple element with the markup
            return $(markup);
        }
    }

    // Initialize search functionality
    function initializeSearch() {
        $searchField.closest('.primary-search-field').addClass('focus');
        $searchField.on('keyup', updateSearch);
        
        // If there's an initial value, trigger search
        if ($searchField.val().length) {
            updateSearch({ keyCode: 0 }); // Trigger without enter key
        }
        
        // Focus the search field
        $searchField.focus();
    }
    
    // Initialize the search when component loads
    initializeSearch();
    
    // Expose public methods if needed
    return {
        updateSearch: updateSearch,
        getResults: function() { return searchResults; }
    };
});