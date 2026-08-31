/**
 * BibleBot Search Page Component
 * Handles search field focus, popular topics toggle, searchable select dropdown with keyboard navigation
 */

mb.registerComponent('biblebot-search-page', function($element, data) {
    
    function searchFieldFocus() {
        $('.home-search-container .searchPrompt').focus();
    }

    $('a.popular_topics_toggle').on('click', function(e) {
        e.preventDefault();
        $(this).toggleClass('open');
        if ($(this).hasClass('open')) {
            $('.popular_topics_container').css('display', 'block').find('.searchable-select-input').focus();
        } else {
            $('a.popular_topics_toggle').removeClass('open');
            $('.popular_topics_container').hide();
        }
    });

    function initPopularTopicsSelect() {
        const select = document.querySelector('.popular_topics_select');
        if (!select) return;

        // Hide the original select
        select.style.display = 'none';

        // Create custom dropdown container
        const container = document.createElement('div');
        container.className = 'searchable-select-container';
        container.style.position = 'relative';
        container.style.maxWidth = '400px';

        // Search input
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'searchable-select-input';
        input.placeholder = select.getAttribute('data-placeholder') || 'Search...';
        input.style.width = '100%';
        input.style.boxSizing = 'border-box';
        input.style.marginBottom = '5px';

        // Dropdown list
        const dropdown = document.createElement('div');
        dropdown.className = 'searchable-select-dropdown';
        dropdown.style.position = 'absolute';
        dropdown.style.top = '52px';
        dropdown.style.left = '0';
        dropdown.style.width = '100%';
        dropdown.style.overflowY = 'auto';
        dropdown.style.display = 'block';

        // Build options list
        function buildOptions(filter = '') {
            dropdown.innerHTML = '';
            Array.from(select.children).forEach(child => {
                if (child.tagName === 'OPTGROUP') {
                    const groupLabel = document.createElement('div');
                    groupLabel.textContent = child.label;
                    groupLabel.style.fontWeight = 'bold';
                    groupLabel.style.padding = '8px 10px 4px 10px';
                    dropdown.appendChild(groupLabel);

                    Array.from(child.children).forEach(option => {
                        addOption(option, filter);
                    });
                } else if (child.tagName === 'OPTION') {
                    addOption(child, filter);
                }
            });
        }

        function addOption(option, filter) {
            if (!option.textContent.trim()) return;
            if (filter && !option.textContent.toLowerCase().includes(filter.toLowerCase())) return;
            const item = document.createElement('a');
            item.className = 'searchable-select-option';
            item.textContent = option.textContent;
            item.style.padding = '8px 10px';
            item.style.cursor = 'pointer';
            item.style.display = 'block';
            item.href = "javascript: void(0);";
            item.addEventListener('click', function() {
                select.value = option.value || option.textContent;
                var selectedText = option.textContent;
                var reference;

                // Use a regular expression to find the content in the parentheses
                var match = selectedText.match(/\((.*?)\)/);

                // Check if a match was found and extract the content
                if (match && match[1]) {
                    reference = match[1];
                    var selectedUrl = '?app=bibleBot&s=' + encodeURIComponent(reference) + '&page_title=' + encodeURIComponent(selectedText);
                    if (selectedUrl) {
                        window.open(selectedUrl, '_blank');
                    }
                } else {
                    console.log('No reference found for this option.');
                }

                // Optionally trigger change event
                select.dispatchEvent(new Event('change'));
            });
            dropdown.appendChild(item);
        }

        // Show dropdown on input focus
        input.addEventListener('focus', function() {
            buildOptions(input.value);
        });

        // Filter options on input
        input.addEventListener('input', function() {
            buildOptions(input.value);
        });

        // Keyboard navigation
        let highlightedIndex = -1;

        function highlightOption(index) {
            const options = dropdown._optionElements || [];
            options.forEach((el, i) => {
                if (i === index) {
                    el.style.background = '#e0e0e0';
                    el.scrollIntoView({
                        block: 'nearest'
                    });
                } else {
                    el.style.background = '';
                }
            });
            highlightedIndex = index;
        }

        function clearHighlight() {
            const options = dropdown._optionElements || [];
            options.forEach(el => el.style.background = '');
            highlightedIndex = -1;
        }

        input.addEventListener('keydown', function(e) {
            const options = dropdown._optionElements || [];
            if (!options.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                let next = highlightedIndex + 1;
                if (next >= options.length) next = 0;
                highlightOption(next);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                let prev = highlightedIndex - 1;
                if (prev < 0) prev = options.length - 1;
                highlightOption(prev);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && options[highlightedIndex]) {
                    options[highlightedIndex].click();
                }
            } else if (e.key === 'Escape') {
                clearHighlight();
                input.blur();
            }
        });

        // Insert custom UI after select
        container.appendChild(input);
        container.appendChild(dropdown);
        select.parentNode.insertBefore(container, select.nextSibling);
    }

    initPopularTopicsSelect();
    searchFieldFocus();

}, []);