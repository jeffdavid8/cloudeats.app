/**
 * BibleBot Home Page Component
 * Handles search field focus and styling
 */

mb.registerComponent('biblebot-home-page', function($element, data) {
    var $field = $('#index-search-field');
    $field.closest('.primary-search-field').addClass('focus');
    $field.focus().select();
}, []);