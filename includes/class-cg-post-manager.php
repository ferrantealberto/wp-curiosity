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
        $text = html_entity_decode(wp_strip_all_tags($curiosity['text']), ENT_QUOTES, 'UTF-8');
        $words = explode(' ', $text);
        $title_words = array_slice($words, 0, 8);
        $title = implode(' ', $title_words);
        if (count($words) > 8) {
            $title .= '…'; // Usa il carattere Unicode per l'ellipsis
        }
        
        // Get the default "Curiosità" category ID
        $curiosity_category_id = $this->get_curiosity_category_id();
        
        // Get or create the category for the selected curiosity type
        $type_category_id = $this->get_or_create_type_category($params['type']);
        
        // Create array of category IDs to assign
        $category_ids = array($curiosity_category_id);
        if ($type_category_id && $type_category_id != $curiosity_category_id) {
            $category_ids[] = $type_category_id;
        }
        
        // Prepare post data - MODIFICATO: i post vengono creati come privati
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $curiosity['text'],
            'post_status'   => 'private', // Cambiato da 'publish' a 'private'
            'post_author'   => $user_id,
            'post_category' => $category_ids,
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
        add_post_meta($post_id, 'cg_language', $params['language']);
        add_post_meta($post_id, 'cg_view_count', 0);
        add_post_meta($post_id, 'cg_created_date', current_time('mysql'));
        
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
     * Get or create a category for the selected curiosity type.
     * 
     * @param string $type_id The type ID (e.g., 'historical-facts', 'science-nature')
     * @return int The category ID
     */
    private function get_or_create_type_category($type_id) {
        if (empty($type_id)) {
            return 0;
        }
        
        // Get the human-readable name for this type
        $types = cg_get_default_types();
        if (!isset($types[$type_id])) {
            return 0;
        }
        
        $type_name = $types[$type_id];
        
        // Check if this category already exists
        $category = get_term_by('name', $type_name, 'category');
        if ($category) {
            return $category->term_id;
        }
        
        // Create the category if it doesn't exist
        $slug = sanitize_title($type_name);
        $result = wp_insert_term($type_name, 'category', array(
            'description' => sprintf('Curiosità nella categoria "%s"', $type_name),
            'slug' => $slug
        ));
        
        if (is_wp_error($result)) {
            return 0;
        }
        
        return $result['term_id'];
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
    
    /**
     * Get all curiosity posts with optional filtering and sorting.
     * 
     * @param array $args Query arguments
     * @return array Array of post objects
     */
    public function get_curiosity_posts($args = array()) {
        $defaults = array(
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => 'cg_generated',
                    'value' => true,
                    'compare' => '='
                )
            )
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return get_posts($args);
    }
    
    /**
     * Update multiple posts status.
     * 
     * @param array $post_ids Array of post IDs
     * @param string $status New post status
     * @return bool Success
     */
    public function update_posts_status($post_ids, $status) {
        if (empty($post_ids) || !is_array($post_ids)) {
            return false;
        }
        
        $valid_statuses = array('publish', 'private', 'draft', 'pending', 'trash');
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $success_count = 0;
        foreach ($post_ids as $post_id) {
            $post_id = intval($post_id);
            
            // Verify this is a curiosity post
            if (!get_post_meta($post_id, 'cg_generated', true)) {
                continue;
            }
            
            $result = wp_update_post(array(
                'ID' => $post_id,
                'post_status' => $status
            ));
            
            if (!is_wp_error($result)) {
                $success_count++;
            }
        }
        
        return $success_count > 0;
    }
    
    /**
     * Delete multiple curiosity posts.
     * 
     * @param array $post_ids Array of post IDs
     * @param bool $force_delete Whether to bypass trash
     * @return bool Success
     */
    public function delete_posts($post_ids, $force_delete = false) {
        if (empty($post_ids) || !is_array($post_ids)) {
            return false;
        }
        
        $success_count = 0;
        foreach ($post_ids as $post_id) {
            $post_id = intval($post_id);
            
            // Verify this is a curiosity post
            if (!get_post_meta($post_id, 'cg_generated', true)) {
                continue;
            }
            
            $result = wp_delete_post($post_id, $force_delete);
            
            if ($result) {
                $success_count++;
            }
        }
        
        return $success_count > 0;
    }
    
    /**
     * Get curiosity posts count by status.
     * 
     * @return array Counts by status
     */
    public function get_posts_count_by_status() {
        global $wpdb;
        
        $query = "SELECT post_status, COUNT(*) as count 
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE pm.meta_key = 'cg_generated' 
                 AND pm.meta_value = '1'
                 AND p.post_type = 'post'
                 GROUP BY post_status";
        
        $results = $wpdb->get_results($query);
        
        $counts = array(
            'publish' => 0,
            'private' => 0,
            'draft' => 0,
            'pending' => 0,
            'trash' => 0,
            'total' => 0
        );
        
        foreach ($results as $result) {
            $counts[$result->post_status] = intval($result->count);
            $counts['total'] += intval($result->count);
        }
        
        return $counts;
    }
}