/**
 * Search Page Component
 * Handles search field focus and UI management
 */
mb.registerComponent('search-page', function($element, componentData) {
    console.log('Initializing search-page component', $element);
    
    setupSearchField();
    
    function setupSearchField() {
        const $field = $('#index-search-field');
        if ($field.length) {
            // Add focus class and focus/select the field
            $field.closest('.primary-search-field').addClass('focus');
            $field.focus().select();
        }
    }
}, ['jQuery']);