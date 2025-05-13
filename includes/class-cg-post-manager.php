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
        
        // Clean and sanitize the text to prevent ASCII codes issues
        $sanitized_text = $this->sanitize_post_content($curiosity['text']);
        
        // Create post title from the first few words of the curiosity
        $text = wp_strip_all_tags($sanitized_text);
        $words = explode(' ', $text);
        $title_words = array_slice($words, 0, 8);
        $title = implode(' ', $title_words);
        if (count($words) > 8) {
            $title .= '...'; // Usa puntini di sospensione standard
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
        
        // Prepare post data
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $sanitized_text,
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_category' => $category_ids,
            'post_type'     => 'post'
        );
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Sanitize tags
        $sanitized_tags = array();
        foreach ($curiosity['tags'] as $tag) {
            $sanitized_tags[] = $this->sanitize_tag($tag);
        }
        
        // Add tags from parameters and suggested tags
        $this->add_post_tags($post_id, $sanitized_tags, $params);
        
        // Add meta data for tracking
        add_post_meta($post_id, 'cg_generated', true);
        add_post_meta($post_id, 'cg_keyword', $params['keyword']);
        add_post_meta($post_id, 'cg_type', $params['type']);
        add_post_meta($post_id, 'cg_language', $params['language']);
        add_post_meta($post_id, 'cg_view_count', 0);
        
        return $post_id;
    }
    
    /**
     * Sanitizza il contenuto del post per evitare problemi con i codici ASCII
     */
    private function sanitize_post_content($text) {
        // Prima decodifica tutte le entità HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Sostituisci manualmente i codici problematici
        $problematic_codes = array(
            '&#8220;' => '"', // virgolette aperte
            '&#8221;' => '"', // virgolette chiuse
            '&#8217;' => "'", // apostrofo
            '&#8216;' => "'", // apice singolo aperto
            '&#8211;' => '-', // trattino medio
            '&#8212;' => '--', // trattino lungo
            '&amp;' => '&',    // e commerciale
            '&lt;' => '<',     // minore
            '&gt;' => '>',     // maggiore
            '&quot;' => '"',   // virgolette
            '&nbsp;' => ' ',   // spazio non divisibile
            '&lsquo;' => "'",  // virgoletta singola aperta
            '&rsquo;' => "'",  // virgoletta singola chiusa
            '&ldquo;' => '"',  // virgoletta doppia aperta
            '&rdquo;' => '"',  // virgoletta doppia chiusa
            '&ndash;' => '-',  // trattino 
            '&mdash;' => '--', // trattino lungo
            '&hellip;' => '...', // puntini di sospensione
        );
        
        $text = str_replace(array_keys($problematic_codes), array_values($problematic_codes), $text);
        
        // Decodifica una seconda volta per catturare eventuali entità annidate
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Rimuovi caratteri di controllo e normalizza spazi
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        return $text;
    }
    
    /**
     * Sanitizza un tag per evitare problemi di codifica
     */
    private function sanitize_tag($tag) {
        // Applica la stessa sanitizzazione usata per il contenuto
        return $this->sanitize_post_content($tag);
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
        
        // Sanitize all tags
        $sanitized_tags = array();
        foreach ($tags as $tag) {
            $sanitized_tags[] = $this->sanitize_tag($tag);
        }
        
        // Set tags for the post
        wp_set_post_tags($post_id, $sanitized_tags);
    }
}