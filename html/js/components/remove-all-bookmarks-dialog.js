/**
 * Remove All Bookmarks Dialog Component
 * Handles the confirmation dialog for removing all bookmarks
 * Manages modal display, user confirmation, and API calls for bookmark removal
 */
mb.registerComponent('remove-all-bookmarks-dialog', function($element, data) {
    console.log('Remove All Bookmarks Dialog component initialized');
    
    // Initialize the modal
    function initializeModal() {
        const $modal = $element.find('#remove_all_bookmarks_dialog');
        
        if ($modal.length && typeof M !== 'undefined' && M.Modal) {
            // Initialize the modal
            const modalInstance = M.Modal.init($modal[0], {
                onOpenStart: false,
            });
            
            return modalInstance;
        } else {
            console.warn('Modal element not found or Materialize not available');
            return null;
        }
    }
    
    // Bind events
    function bindEvents(modalInstance) {
        // Bind clear all bookmarks button
        $('.bookmark_tools .clear_all_bookmarks_btn').on('click', function() {
            if (modalInstance) {
                modalInstance.open();
            }
        });
        
        // Bind confirmation button
        $element.find('.modal-footer .btnOk, .confirm-remove-btn').on('click', function() {
            performBookmarkClearance();
        });
        
        // Bind cancel button
        $element.find('.modal-footer .btnCancel, .cancel-btn').on('click', function() {
            if (modalInstance) {
                modalInstance.close();
            }
        });
    }
    
    // Perform bookmark clearance via API
    function performBookmarkClearance() {
        // Try API approach first
        if (typeof $ !== 'undefined' && $.ajax) {
            const package = {
                'action': 'clear_all_bookmarks',
            };
            
            $.ajax({
                url: 'api.php',
                dataType: 'json',
                data: package,
                success: function(data) {
                    // Show success toast
                    if (typeof M !== 'undefined' && M.toast) {
                        M.toast({
                            html: 'Bookmarks removed',
                            displayLength: 5000
                        });
                    }
                    
                    // Remove bookmark elements from UI
                    $('.sidenav .bookmark_menu .bookmark_links li').remove();
                    
                    console.log('All bookmarks cleared successfully');
                },
                error: function(response) {
                    console.error('Error clearing bookmarks:', response);
                    
                    // Show error toast
                    if (typeof M !== 'undefined' && M.toast) {
                        M.toast({
                            html: 'Failed to remove bookmarks',
                            displayLength: 5000,
                            classes: 'red'
                        });
                    }
                },
            });
        }
        
        // Fallback to local storage approach
        else if (typeof bibleBot !== 'undefined' && bibleBot.storage) {
            // Clear all bookmarks
            bibleBot.storage.bookmarks = [];
            
            // Save to storage if storage function exists
            if (typeof storage_set === 'function') {
                storage_set();
            }
            
            // Trigger update event for other components
            $(document).trigger('bookmarks-updated');
            
            // Show notification
            if (typeof notify === 'function') {
                notify('All bookmarks have been removed');
            }
            
            console.log('All bookmarks removed (local storage)');
        }
    }
    
    // Wait for dependencies
    function waitForDependencies() {
        if (typeof M !== 'undefined' && M.Modal) {
            const modalInstance = initializeModal();
            bindEvents(modalInstance);
            return modalInstance;
        } else {
            // Initialize basic event binding even without modal
            bindEvents(null);
            // Wait for Materialize to load
            setTimeout(function() {
                if (typeof M !== 'undefined' && M.Modal) {
                    const modalInstance = initializeModal();
                    bindEvents(modalInstance);
                }
            }, 100);
        }
    }
    
    // Initialize as Materialize modal if available (fallback)
    if (typeof M !== 'undefined' && M.Modal) {
        M.Modal.init($element[0]);
    }
    
    // Start initialization
    const modalInstance = waitForDependencies();
    
    console.log('Remove all bookmarks dialog component initialized');
    
    // Expose public methods
    return {
        showDialog: function() {
            if (modalInstance && typeof modalInstance.open === 'function') {
                modalInstance.open();
            }
        },
        hideDialog: function() {
            if (modalInstance && typeof modalInstance.close === 'function') {
                modalInstance.close();
            }
        },
        clearBookmarks: performBookmarkClearance
    };
}, ['materialize.min.js', 'jquery']); // Depends on Materialize and jQuery