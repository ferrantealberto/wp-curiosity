<?php
/**
 * Frontend class for handling form and AJAX requests.
 */
class CG_Frontend {
    
    /**
     * Enqueue public scripts and styles.
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style('cg-public-styles', CG_PLUGIN_URL . 'public/css/cg-public-styles.css', array(), CG_VERSION);
        wp_enqueue_script('cg-public-scripts', CG_PLUGIN_URL . 'public/js/cg-public-scripts.js', array('jquery'), CG_VERSION, true);
        
        // Demo AdSense code for testing
        $demo_ad_code = $this->get_demo_adsense_code();
        
        wp_localize_script('cg-public-scripts', 'cg_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cg_generate_nonce'),
            'fullscreen_ad_frequency' => get_option('cg_fullscreen_ad_frequency', 5),
            'adsense_fullscreen_code' => get_option('cg_adsense_fullscreen_code', ''),
            'adsense_inline_code' => get_option('cg_adsense_inline_code', ''),
            'adsense_header_code' => get_option('cg_adsense_header_code', ''),
            'adsense_footer_code' => get_option('cg_adsense_footer_code', ''),
            'adsense_demo_code' => $demo_ad_code,
            'show_advanced_text' => __('Mostra Opzioni Avanzate', 'curiosity-generator'),
            'hide_advanced_text' => __('Nascondi Opzioni Avanzate', 'curiosity-generator'),
            'error_text' => __('Si è verificato un errore. Riprova.', 'curiosity-generator')
        ));
    }
    
    /**
     * Returns a demo AdSense code for testing.
     */
    private function get_demo_adsense_code() {
        return '<div style="width: 100%; min-height: 250px; background-color: #f0f0f0; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; margin: 10px 0; padding: 20px; box-sizing: border-box;"><div style="text-align: center;"><strong>ANNUNCIO DEMO</strong><br>Questo è un annuncio demo per test</div></div>';
    }
    
    /**
     * Handle AJAX request for generating curiosities.
     */
    public function handle_generate_curiosity() {
        check_ajax_referer('cg_generate_nonce', 'nonce');
        
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '';
        $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
        
        // Check if count is within limits
        $max_curiosities = get_option('cg_max_curiosities', 5);
        if ($count < 1 || $count > $max_curiosities) {
            $count = min(max(1, $count), $max_curiosities);
        }
        
        // Sanitize other optional parameters
        $optional_params = array('param1', 'param2', 'param3', 'param4', 'param5', 'param6', 'param7', 'param8');
        $params = array(
            'keyword' => $keyword,
            'type' => $type,
            'period' => $period,
            'count' => $count
        );
        foreach ($optional_params as $param_key) {
            if (isset($_POST[$param_key])) {
                $params[$param_key] = sanitize_text_field($_POST[$param_key]);
            }
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Initialize classes
        $openrouter = new CG_OpenRouter();
        $post_manager = new CG_Post_Manager();
        $credits = new CG_Credits();
        
        // Generate curiosities using API
        $result = $openrouter->generate_curiosities($params);
        
        if (is_wp_error($result)) {
            wp_send_json_error(__('Errore durante la generazione delle curiosità. Riprova.', 'curiosity-generator'));
        }
        
        // Create posts for each curiosity
        $post_ids = array();
        $post_urls = array();
        $post_titles = array();
        $post_contents = array();
        
        foreach ($result as $curiosity) {
            $post_id = $post_manager->create_curiosity_post($curiosity, $params, $user_id);
            if (is_wp_error($post_id)) {
                wp_send_json_error(__('Errore durante la creazione dei post. Riprova.', 'curiosity-generator'));
            }
            $post_ids[] = $post_id;
            $post_urls[] = get_permalink($post_id);
            $post_titles[] = get_the_title($post_id);
            $post_contents[] = $curiosity['text'];
        }
        
        // Add credits to the user
        if ($user_id) {
            $credits->add_generation_credits($user_id);
        }
        
        // Get ad codes
        $inline_ad_code = get_option('cg_adsense_inline_code', '');
        $header_ad_code = get_option('cg_adsense_header_code', '');
        $footer_ad_code = get_option('cg_adsense_footer_code', '');
        $demo_ad_code = $this->get_demo_adsense_code();
        
        // Prepare response data
        $data = array(
            'post_ids' => $post_ids,
            'post_urls' => $post_urls,
            'post_titles' => $post_titles,
            'post_contents' => $post_contents,
            'message' => __('Curiosità generate con successo!', 'curiosity-generator'),
            'credits' => $user_id ? $credits->get_user_credits($user_id) : 0,
            'inline_ad' => !empty($inline_ad_code) ? $inline_ad_code : $demo_ad_code,
            'header_ad' => !empty($header_ad_code) ? $header_ad_code : $demo_ad_code,
            'footer_ad' => !empty($footer_ad_code) ? $footer_ad_code : $demo_ad_code
        );
        
        wp_send_json_success($data);
    }
}
