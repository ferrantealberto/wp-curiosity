<?php
/**
 * Credits system for logged-in users.
 */
class CG_Credits {
    
    /**
     * Add credits to a user for generating curiosities.
     */
    public function add_generation_credits($user_id) {
        if (!$user_id) {
            return false;
        }
        
        $credit_value = get_option('cg_generation_credits', 5);
        $current_credits = $this->get_user_credits($user_id);
        $new_credits = $current_credits + $credit_value;
        
        return update_user_meta($user_id, 'cg_user_credits', $new_credits);
    }
    
    /**
     * Add credits to a user when their curiosity is viewed.
     */
    public function add_view_credits($author_id, $post_id) {
        if (!$author_id || !$post_id) {
            return false;
        }
        
        // Don't give credits if author is viewing their own post
        if (get_current_user_id() == $author_id) {
            return false;
        }
        
        // Check if this is a curiosity post
        $category = get_term_by('name', 'Curiosità', 'category');
        if (!$category || !has_category($category->term_id, $post_id)) {
            return false;
        }
        
        // Track unique views using post meta
        $viewed_by = get_post_meta($post_id, 'cg_viewed_by', true);
        if (!$viewed_by) {
            $viewed_by = array();
        }
        
        // Get current viewer ID or IP for guests
        $viewer_id = get_current_user_id() ? get_current_user_id() : $_SERVER['REMOTE_ADDR'];
        
        // Check if this viewer has already been counted
        if (in_array($viewer_id, $viewed_by)) {
            return false;
        }
        
        // Add viewer to the list
        $viewed_by[] = $viewer_id;
        update_post_meta($post_id, 'cg_viewed_by', $viewed_by);
        
        // Increment view count
        $view_count = get_post_meta($post_id, 'cg_view_count', true);
        $view_count = $view_count ? $view_count + 1 : 1;
        update_post_meta($post_id, 'cg_view_count', $view_count);
        
        // Add credits to author
        $credit_value = get_option('cg_view_credits', 1);
        $current_credits = $this->get_user_credits($author_id);
        $new_credits = $current_credits + $credit_value;
        
        return update_user_meta($author_id, 'cg_user_credits', $new_credits);
    }
    
    /**
     * Get user's current credits.
     */
    public function get_user_credits($user_id) {
        if (!$user_id) {
            return 0;
        }
        
        $credits = get_user_meta($user_id, 'cg_user_credits', true);
        return $credits ? intval($credits) : 0;
    }
    
			
    /**
     * Track post view to add credits to the author.
     */
    public function track_post_view($content) {
        // Only track on single post view
        if (!is_singular('post')) {
            return $content;
        }
        
        global $post;
        
        // Check if this is a curiosity post
        if (get_post_meta($post->ID, 'cg_generated', true)) {
            // Add view credits to the author
            $this->add_view_credits($post->post_author, $post->ID);
        }
        
        return $content;
    }
}
