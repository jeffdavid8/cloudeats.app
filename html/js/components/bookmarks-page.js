/**
 * Bookmarks Page Component
 * Complete bookmarks management functionality including sortable drag & drop,
 * bookmark removal, content loading, search functionality, and copy features
 */

mb.registerComponent('bookmarks-page', function($element, data) {
    var sortableContainer = document.getElementById('sortableContainer');
    var $sortableChildPrototype = $('#sortableChildPrototype').clone().removeClass('hide');
    $('#sortableChildPrototype').remove();
    $('#facet_editor').focus().select();
    var search_string = bibleBot.storage.bookmarks.join(';');
    $('#facet_editor').val(search_string);

    var reversed_bookmarks = Object.create(bibleBot.storage.bookmarks);
    reversed_bookmarks.reverse();
    
    loading(1);
    $('.header .view_search_btn').attr('href', '?app=bibleBot&s='+reversed_bookmarks.join(';'));
    
    mb.get('apps/bibleBot/api.php', {
        'action': 'search',
        'data': reversed_bookmarks.join(';'),
    }, function(response) {
        for (const [search, result] of Object.entries(response.search_results)) {
            var el = $sortableChildPrototype.clone();
            el.attr('id', '');
            el.attr('class', 'sortableChild');
            el.attr('data-id', search);
            el.find('.facet_title').html(search);
            el.find('.open_in_new_window_btn').attr('href', '?app=bibleBot&s='+ search);
            el.find('.remove_bookmark_btn').on('click', function() {
                bibleBot.storage.bookmarks.splice(-$(this).closest('li').index()-1, 1);
                $(this).closest('li.sortableChild').remove();
                notify('Removed bookmark from ' + search);
            });
            
            var text = '';
            var list = (typeof result.data[''] == 'undefined')
                ? Object.entries(result.data)
                : result.data[''];

            list.forEach(function(verse) {
                text = text + verse[1].v + '  ' + verse[1].t + "<br/>";
            });
            el.find('.contents .verse.result').html(text);
            $(sortableContainer).append(el);
        }
        
        $(sortableContainer).find('.facet_title').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('li').toggleClass('open');
        });

        loading(0);
    });

    window.sortableSearchResults = Sortable.create(sortableContainer, {
        group: 'sortableContainer',
        handle: '.drag_btn',
        fallbackOnBody: true,
        swapThreshold: 0.65,
        multiDrag: true,
        selectedClass: 'selected',
        animation: 150,
        onUpdate: function() {
            $('body').addClass('modified');
            bibleBot.storage.bookmarks = this.toArray();
            bibleBot.storage.bookmarks.reverse();
            var reversed_bookmarks = Object.create(bibleBot.storage.bookmarks);
            reversed_bookmarks.reverse();

            var search_string = reversed_bookmarks.join(';');
            $('#facet_editor').val(search_string);
            $('.header .view_search_btn').attr('href', '?app=bibleBot&s='+encodeURIComponent(search_string));
        }
    });

    $(sortableContainer).find('.facet_title').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).closest('li').toggleClass('open');
    });

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

}, ['bibleBot', 'sortable', 'loading', 'notify', 'copyText']);