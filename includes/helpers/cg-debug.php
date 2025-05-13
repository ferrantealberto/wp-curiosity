<?php
/**
 * Debug functions for Curiosity Generator
 */

/**
 * Print debug information about loaded scripts and styles
 */
function cg_debug_enqueued_scripts() {
    if (current_user_can('manage_options')) {
        global $wp_scripts, $wp_styles;
        
        echo '<div style="background: #f5f5f5; padding: 10px; border: 1px solid #ccc; margin: 20px 0;">';
        echo '<h3>Debug: Enqueued Scripts</h3>';
        echo '<ul>';
        foreach ($wp_scripts->queue as $handle) {
            echo '<li>' . $handle . ' (' . $wp_scripts->registered[$handle]->src . ')</li>';
        }
        echo '</ul>';
        
        echo '<h3>Debug: Enqueued Styles</h3>';
        echo '<ul>';
        foreach ($wp_styles->queue as $handle) {
            echo '<li>' . $handle . ' (' . $wp_styles->registered[$handle]->src . ')</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Uncomment to enable debug
// add_action('admin_footer', 'cg_debug_enqueued_scripts');