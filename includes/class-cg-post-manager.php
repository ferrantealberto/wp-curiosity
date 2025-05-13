<?php
/**
 * Post Manager class for handling curiosity posts.
 */
class CG_Post_Manager {
    
    /**
     * Create a new post for a generated curiosity.
     */
    public function create_curiosity_post($curiosity, $params, $user_id = 0) {
        // Get default author if user is not logged in
        if (!$user_id) {
            $user_id = get_option('cg_default_author', 1);
        }
        
        // Create post title from the first few words of the curiosity
        $words = explode(' ', $curiosity['text']);
        $title_words = array_slice($words, 0, 8);
        $title = implode(' ', $title_words);
        if (count($words) > 8) {
            $title .= '...';
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $curiosity['text'],
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_category' => array($this->get_curiosity_category_id()),
            'post_type'     => 'post'
        );
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Add tags from parameters and suggested tags
        $this->add_post_tags($post_id, $curiosity['tags'], $params);
        
        // Add meta data for tracking
        add_post_meta($post_id, 'cg_generated', true);
        add_post_meta($post_id, 'cg_keyword', $params['keyword']);
        add_post_meta($post_id, 'cg_type', $params['type']);
        add_post_meta($post_id, 'cg_view_count', 0);
        
        return $post_id;
    }
    
    /**
     * Get or create the Curiosità category.
     */
    private function get_curiosity_category_id() {
        $category = get_term_by('name', 'Curiosità', 'category');
        
        if (!$category) {
            $result = wp_insert_term('Curiosità', 'category', array(
                'description' => 'Curiosità generate automaticamente',
                'slug' => 'curiosita'
            ));
            
            if (is_wp_error($result)) {
                return 1; // Default category
            }
            
            return $result['term_id'];
        }
        
        return $category->term_id;
    }
    
    /**
     * Add tags to the post based on parameters and suggested tags.
     */
    private function add_post_tags($post_id, $suggested_tags, $params) {
        $tags = array();
        
        // Add keyword as tag
        if (!empty($params['keyword'])) {
            $tags[] = $params['keyword'];
        }
        
        // Add type as tag
        if (!empty($params['type'])) {
            $tags[] = $params['type'];
        }
        
        // Add period as tag if not empty
        if (!empty($params['period'])) {
            $tags[] = $params['period'];
        }
        
        // Add other parameters as tags
        $optional_params = array('param1', 'param2', 'param3', 'param4', 'param5', 'param6', 'param7', 'param8');
        foreach ($optional_params as $param) {
            if (!empty($params[$param])) {
                $tags[] = $params[$param];
            }
        }
        
        // Add suggested tags from LLM
        if (is_array($suggested_tags) && !empty($suggested_tags)) {
            $tags = array_merge($tags, $suggested_tags);
        }
        
        // Remove duplicates and empty values
        $tags = array_unique(array_filter($tags));
        
        // Set tags for the post
        wp_set_post_tags($post_id, $tags);
    }
}
