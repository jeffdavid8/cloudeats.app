/**
 * Edit Page Component  
 * Handles advanced Sortable interface, facet management, and clipboard operations
 */
mb.registerComponent('edit-page', function($element, componentData) {
    console.log('Initializing edit-page component', $element);
    
    // Initialize all functionality
    initializeEditorInterface();
    setupSortableSearchResults();
    setupEventHandlers();
    
    function initializeEditorInterface() {
        // Focus and select the facet editor input
        $('#facet_editor').focus().select();
    }
    
    function setupSortableSearchResults() {
        const searchResults = document.getElementById('searchResults');
        if (!searchResults) return;
        
        // Initialize Sortable for search results with multi-drag support
        window.sortableSearchResults = Sortable.create(searchResults, {
            group: 'searchResults',
            handle: '.drag_btn',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            multiDrag: true, // Enable multi-drag
            selectedClass: 'selected', // The class applied to the selected items
            animation: 150,
            onUpdate: () => {
                // Update facet editor and search button with new order
                const search_string = window.sortableSearchResults.toArray().join(';');
                $('#facet_editor').val(search_string);
                $('.header .view_search_btn').attr('href', 'search.php?s=' + encodeURIComponent(search_string));
            }
        });
    }
    
    function setupEventHandlers() {
        setupFacetTitleToggle();
        setupFacetEditorKeypress();
        setupCopyButton();
        setupPreviewButton();
    }
    
    function setupFacetTitleToggle() {
        const searchResults = document.getElementById('searchResults');
        if (!searchResults) return;
        
        // Handle facet title clicks for expanding/collapsing items
        $(searchResults).find('.facet_title').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('li').toggleClass('open');
        });
    }
    
    function setupFacetEditorKeypress() {
        // Handle Enter key in facet editor to add new facets
        $('#facet_editor').keypress((e) => {
            if (e.which === 13) {
                // Add to list
                addNewFacetsToSearchResultsList(e.target.value);
                return false;
            }
        });
    }
    
    function setupCopyButton() {
        // Handle copy button for copying verses to clipboard
        $('.header .left .copy_btn').on('click', function() {
            loading(1);
            let text = '';
            
            $('ul#searchResults.scriptureList .verse[data-reference]:not([data-reference=""])').each(function() {
                const temp = $(this).clone();
                temp.find('.verse_number').remove();
                text = text + temp.text() + "\n" + $(this).attr('data-reference');
            });
            
            copyText(text);
            loading(0);
            notify('Copied to clipboard');
        });
    }
    
    function setupPreviewButton() {
        // Handle preview button (currently placeholder functionality)
        $('.header .right .preview_btn').on('click', function() {
            let search_string = '';
            const bookmarks = document.getElementById('bookmarks');
            
            if (bookmarks) {
                $(bookmarks).find('li').each(function(bookmark) {
                    const prefix = search_string.length ? ';' : '';
                    search_string += prefix + $(this).attr('data-key');
                });
            }
        });
    }
    
    function addNewFacetsToSearchResultsList(search_string) {
        if (!search_string || !search_string.length) return;
        
        const facets = search_string.split(';');
        const searchResults = document.getElementById('searchResults');
        
        if (!searchResults) return;
        
        // Add each facet as a new list item
        $.each(facets, function(key, value) {
            const facet = value.trim();
            if (facet) {
                $(searchResults).prepend(`<li class="" data-id="${facet}">${facet}</li>`);
            }
        });
        
        // Update search URI string and view search button
        if (window.sortableSearchResults) {
            const search_uri_string = encodeURI(window.sortableSearchResults.toArray().join(';'));
            $('.header .view_search_btn').attr('href', 'search.php?s=' + search_uri_string);
        }
    }
}, ['jQuery']);