<?
if (!defined('MB_RUNNING')) exit;
/**
 * Customer Settings Page
 *  - Providing access to request user data deletion 
 */
?>

<div class="container" style="padding-top: 3rem;">
  <h4>Customer Settings</h4>
  <div class="row">
    <div class="col">
      <p>Request User data deletion</p>
      <form method="post" action="">
        <input type="hidden" name="action" value="request_user_data_deletion">
        <button type="submit" class="btn waves-effect waves-light">Request User Data Deletion</button>
      </form>
    </div>
  </div>
</div>

<script>
  // Optional: Add any JavaScript needed for the settings page
  $(document).ready(function() {
    // Example: Handle form submission or other interactions
    $('form').on('submit', function(e) {
      e.preventDefault();
      if (confirm('Are you sure you want to request user data deletion? This action cannot be undone.')) {
        loading(4);
        mb.ajax({
          type: 'GET',
          url: '/?api=neighborhub&action=delete_user_data',
          success: function(response) {
            if (response.success) {
              M.toast({
                html: 'User data deletion requested!',
                displayLength: 2500
              });

            } else {
              loading(0);
              M.toast({
                html: 'Error: ' + response.error,
                displayLength: 2500
              });
            }
          },
          error: function() {
            loading(0);
            M.toast({
              html: 'Error: ' + response.error,
              displayLength: 2500
            });
          }
        });
      }

    });
  });
</script>