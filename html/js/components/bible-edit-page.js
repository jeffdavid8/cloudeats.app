/**
 * Bible Edit Page Component  
 * Handles sortable search results, facet management, copy functionality, and search interface
 */

mb.registerComponent('bible-edit-page', function($element, data) {
    $('#facet_editor').focus().select();

    var searchResults = document.getElementById('searchResults');
    
    if (searchResults) {
        window.sortableSearchResults = Sortable.create(searchResults, {
            group: 'searchResults',
            handle: '.drag_btn',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            multiDrag: true,
            selectedClass: 'selected',
            animation: 150,
            onUpdate: function() {
                var search_string = this.toArray().join(';');
                $('#facet_editor').val(search_string);
                $('.header .view_search_btn').attr('href', '?app=bibleBot&s='+encodeURIComponent(search_string));
            }
        });

        $(searchResults).find('.facet_title').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('li').toggleClass('open');
        });
    }

    $('#facet_editor').keypress(function (e) {
        if (e.which == 13) {
            addNewFacetsToSearchResultsList(this.value);
            return false;
        }
    });

    $('.header .left .copy_btn').on('click', function() {
        loading(1);
        var text = '';
        $('ul#searchResults.scriptureList .verse[data-reference]:not([data-reference=""])').each(function() {
            var temp = $(this).clone();
            temp.find('.verse_number').remove();
            text = text + temp.text() + "\n" + $(this).attr('data-reference');
        });
        copyText(text);
        loading(0);
        notify('Copied to clipboard');
    });

    $('.header .right .preview_btn').on('click', function() {
        var search_string = '';
        $(bookmarks).find('li').each(function(bookmark) {
            var prefix = (search_string.length) ? ';' : '';
            search_string += prefix + $(this).attr('data-key');
        });
    });

    function addNewFacetsToSearchResultsList(search_string) {
        if (!search_string.length) return;

        var facets = search_string.split(';');
        
        $.each(facets, function(key, value) {
            var facet = value.trim();
            $(searchResults).prepend('<li class="" data-id="'+facet+'">'+facet+'</li>');
        });

        search_uri_string = encodeURI(sortableSearchResults.toArray().join(';'));
        $('.header .view_search_btn').attr('href', '?app=bibleBot&s='+search_uri_string);
    }

}, ['sortable', 'loading', 'notify', 'copyText']);