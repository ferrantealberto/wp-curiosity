<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get all users with credits
$users = get_users();
?>

<div class="wrap">
    <h1><?php _e('User Credits Management', 'curiosity-generator'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Manage curiosity generator credits for users. Generation credits are earned when generating curiosities, view credits are earned when other users view a user\'s curiosities.', 'curiosity-generator'); ?></p>
    </div>
    
    <div id="cg-credits-updated" class="notice notice-success" style="display:none;">
        <p><?php _e('User credits updated successfully!', 'curiosity-generator'); ?></p>
    </div>
    
    <div id="cg-credits-error" class="notice notice-error" style="display:none;">
        <p></p>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-username"><?php _e('Username', 'curiosity-generator'); ?></th>
                <th scope="col" class="manage-column column-name"><?php _e('Name', 'curiosity-generator'); ?></th>
                <th scope="col" class="manage-column column-email"><?php _e('Email', 'curiosity-generator'); ?></th>
                <th scope="col" class="manage-column column-generation-credits"><?php _e('Generation Credits', 'curiosity-generator'); ?></th>
                <th scope="col" class="manage-column column-view-credits"><?php _e('View Credits', 'curiosity-generator'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'curiosity-generator'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): 
                $generation_credits = get_user_meta($user->ID, 'cg_generation_credits', true) ?: 0;
                $view_credits = get_user_meta($user->ID, 'cg_view_credits', true) ?: 0;
            ?>
                <tr id="user-<?php echo $user->ID; ?>">
                    <td class="column-username"><?php echo esc_html($user->user_login); ?></td>
                    <td class="column-name"><?php echo esc_html($user->display_name); ?></td>
                    <td class="column-email"><?php echo esc_html($user->user_email); ?></td>
                    <td class="column-generation-credits">
                        <span class="credits-display"><?php echo esc_html($generation_credits); ?></span>
                        <input type="number" class="credits-input generation-credits" value="<?php echo esc_attr($generation_credits); ?>" min="0" style="display:none;">
                    </td>
                    <td class="column-view-credits">
                        <span class="credits-display"><?php echo esc_html($view_credits); ?></span>
                        <input type="number" class="credits-input view-credits" value="<?php echo esc_attr($view_credits); ?>" min="0" style="display:none;">
                    </td>
                    <td class="column-actions">
                        <button class="button edit-credits" data-user-id="<?php echo $user->ID; ?>"><?php _e('Edit', 'curiosity-generator'); ?></button>
                        <button class="button button-primary save-credits" data-user-id="<?php echo $user->ID; ?>" style="display:none;"><?php _e('Save', 'curiosity-generator'); ?></button>
                        <button class="button cancel-edit" data-user-id="<?php echo $user->ID; ?>" style="display:none;"><?php _e('Cancel', 'curiosity-generator'); ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Edit credits
        $('.edit-credits').on('click', function() {
            const userId = $(this).data('user-id');
            const row = $('#user-' + userId);
            
            // Show inputs, hide displays
            row.find('.credits-display').hide();
            row.find('.credits-input').show();
            
            // Show save/cancel buttons, hide edit button
            row.find('.edit-credits').hide();
            row.find('.save-credits, .cancel-edit').show();
        });
        
        // Cancel edit
        $('.cancel-edit').on('click', function() {
            const userId = $(this).data('user-id');
            const row = $('#user-' + userId);
            
            // Hide inputs, show displays
            row.find('.credits-input').hide();
            row.find('.credits-display').show();
            
            // Reset input values to current display values
            row.find('.generation-credits').val(row.find('.column-generation-credits .credits-display').text());
            row.find('.view-credits').val(row.find('.column-view-credits .credits-display').text());
            
            // Show edit button, hide save/cancel buttons
            row.find('.save-credits, .cancel-edit').hide();
            row.find('.edit-credits').show();
        });
        
        // Save credits
        $('.save-credits').on('click', function() {
            const userId = $(this).data('user-id');
            const row = $('#user-' + userId);
            const generationCredits = row.find('.generation-credits').val();
            const viewCredits = row.find('.view-credits').val();
            
            // Disable buttons during AJAX request
            row.find('button').prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cg_update_user_credits',
                    nonce: '<?php echo wp_create_nonce('cg_admin_nonce'); ?>',
                    user_id: userId,
                    generation_credits: generationCredits,
                    view_credits: viewCredits
                },
                success: function(response) {
                    // Update display values
                    row.find('.column-generation-credits .credits-display').text(generationCredits);
                    row.find('.column-view-credits .credits-display').text(viewCredits);
                    
                    // Return to display mode
                    row.find('.credits-input').hide();
                    row.find('.credits-display').show();
                    row.find('.save-credits, .cancel-edit').hide();
                    row.find('.edit-credits').show();
                    
                    // Show success message
                    $('#cg-credits-updated').fadeIn().delay(3000).fadeOut();
                },
                error: function(xhr) {
                    let errorMessage = '<?php _e('An error occurred while updating credits.', 'curiosity-generator'); ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    
                    $('#cg-credits-error p').text(errorMessage);
                    $('#cg-credits-error').fadeIn().delay(3000).fadeOut();
                },
                complete: function() {
                    // Re-enable buttons
                    row.find('button').prop('disabled', false);
                }
            });
        });
    });
</script>