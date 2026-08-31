/**
 * Ancestry Admin People Component
 * Handles people management interface with modal editing, CSRF token setup, and internationalization
 */

mb.registerComponent('ancestry-admin-people', function($element, data) {
    // Setup internationalization
    window.I18N = window.I18N || {};
    if (!window.I18N.logged_in) window.I18N.logged_in = 'You are now logged in';

    // Initialize Materialize modal
    $('.modal').modal();

    // Initialize date pickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        yearRange: [1800, new Date().getFullYear()]
    });

    // Refresh button handler
    $('#refreshBtn').on('click', function() {
        if (typeof refreshPeopleList === 'function') {
            refreshPeopleList();
        }
    });

    // New person button handler  
    $('#newBtn').on('click', function() {
        $('#editorTitle').text('New Person');
        $('#personForm')[0].reset();
        $('#pid').val('');
        $('#editorModal').modal('open');
        // Initialize labels for new form
        Materialize.updateTextFields();
    });

    // Save button handler
    $('#saveBtn').on('click', function() {
        if (typeof savePerson === 'function') {
            savePerson();
        }
    });

    // Cancel button handler
    $('#cancelBtn').on('click', function() {
        $('#editorModal').modal('close');
    });

    // Load external scripts dynamically to avoid blocking
    const scripts = [
        '/apps/ancestry/js/admin_people.js',
        '/apps/ancestry/js/auth.js'
    ];

    scripts.forEach(src => {
        if (!document.querySelector(`script[src="${src}"]`)) {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            document.head.appendChild(script);
        }
    });

}, ['materialize']);