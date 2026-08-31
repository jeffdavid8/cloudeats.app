<?php
if (!defined('MB_RUNNING')) exit;
if (!$this->user->is_admin) {
    echo "Access Denied";
    return;
}
?>
<nav class="clean-nav breadcrumb-nav">
    <div class="nav-wrapper">
        <div class="col s12">
            <a href="?app=neighborhub&view=dashboard" class="breadcrumb">Neighborhub Admin</a>
            <a href="#!" class="breadcrumb">Add System Courier</a>
        </div>
    </div>
</nav>
<div class="container" style="margin-top:20px;">

    <div class="card">
        <div class="card-content">
            <span class="card-title">Provision New Courier Account</span>
            <form id="admin-courier-form" style="margin-top:20px;">
                <input type="hidden" name="id" value="0">

                <div class="row">
                    <div class="input-field col s12 m6">
                        <input id="user_email" name="user_email" type="email" required class="validate">
                        <label for="user_email">Account Owner Email Address</label>
                        <span class="helper-text" data-error="Please enter a valid email format">Enter the registered account email for this courier.</span>
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="business_name" name="business_name" type="text" required>
                        <label for="business_name">Courier Operating / Display Name</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12 m4">
                        <input id="phone" name="phone" type="tel" required>
                        <label for="phone">Phone Contact</label>
                    </div>
                    <div class="input-field col s12 m4">
                        <label style="position:static;">Vehicle System Profile</label>
                        <select name="vehicle_type" class="browser-default" style="margin-top:5px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                            <option value="WALKING">Walking / Foot</option>
                            <option value="BICYCLE">Bicycle / Scooter</option>
                            <option value="CAR">Car / Logistics Truck</option>
                        </select>
                    </div>
                    <div class="input-field col s12 m4">
                        <label style="position:static;">Operational Clear Status</label>
                        <select name="status" class="browser-default" style="margin-top:5px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                            <option value="pending">Pending Review</option>
                            <option value="offline">Offline / Approved</option>
                            <option value="available">Available (Active Live)</option>
                        </select>
                    </div>
                </div>

                <div class="card-action" style="padding-left:0; padding-right:0; margin-top:20px;">
                    <button type="submit" class="btn blue waves-effect waves-light">
                        <i class="material-icons left">save</i> Save Courier Profile
                    </button>
                    <a href="?app=neighborhub&view=dashboard" class="btn-flat waves-effect" style="margin-left:10px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#admin-courier-form').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                url: 'api.php?app=neighborhub&action=admin_save_courier',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        M.toast({
                            html: 'Courier record created successfully!',
                            classes: 'green'
                        });
                        setTimeout(function() {
                            window.location.href = '?app=neighborhub&view=dashboard';
                        }, 1000);
                    } else {
                        M.toast({
                            html: 'Error: ' + (res.error || 'Submission failed'),
                            classes: 'red'
                        });
                    }
                },
                error: function() {
                    M.toast({
                        html: 'API Communication Breakdown.',
                        classes: 'red'
                    });
                }
            });
        });
    });
</script>