<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all options
$options = array(
    'cg_openrouter_api_key',
    'cg_llm_model',
    'cg_adsense_inline_code',
    'cg_adsense_fullscreen_code',
    'cg_adsense_header_code',
    'cg_adsense_footer_code',
    'cg_fullscreen_ad_frequency',
    'cg_generation_credits',
    'cg_view_credits',
    'cg_max_curiosities',
    'cg_min_curiosity_length',
    'cg_default_author',
    'cg_disable_demo_ads'
);

foreach ($options as $option) {
    delete_option($option);
}

// Drop scheduler table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cg_schedules");

// We don't remove the Curiosità category or posts by default
// to avoid accidental data loss. Uncomment the following code
// if you want to remove them.

/*
// Get the Curiosità category ID
$category = get_term_by('name', 'Curiosità', 'category');
if ($category) {
    // Delete all posts in the Curiosità category
    $posts = get_posts(array(
        'category' => $category->term_id,
        'numberposts' => -1,
        'post_type' => 'post',
        'fields' => 'ids'
    ));
    
    foreach ($posts as $post_id) {
        wp_delete_post($post_id, true);
    }
    
    // Delete the category
    wp_delete_category($category->term_id);
}
*/

// Remove user meta for credits
$wpdb->delete($wpdb->usermeta, array('meta_key' => 'cg_generation_credits'));
$wpdb->delete($wpdb->usermeta, array('meta_key' => 'cg_view_credits'));