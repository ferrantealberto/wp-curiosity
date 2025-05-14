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
        
        // Get demo codes for different ad types
        $demo_inline_code = $this->get_demo_adsense_code('inline');
        $demo_fullscreen_code = $this->get_demo_adsense_code('fullscreen');
        $demo_header_code = $this->get_demo_adsense_code('header');
        $demo_footer_code = $this->get_demo_adsense_code('footer');
        
        wp_localize_script('cg-public-scripts', 'cg_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cg_generate_nonce'),
            'fullscreen_ad_frequency' => get_option('cg_fullscreen_ad_frequency', 5),
            'adsense_fullscreen_code' => get_option('cg_adsense_fullscreen_code', ''),
            'adsense_inline_code' => get_option('cg_adsense_inline_code', ''),
            'adsense_header_code' => get_option('cg_adsense_header_code', ''),
            'adsense_footer_code' => get_option('cg_adsense_footer_code', ''),
            'adsense_demo_inline_code' => $demo_inline_code,
            'adsense_demo_fullscreen_code' => $demo_fullscreen_code,
            'adsense_demo_header_code' => $demo_header_code,
            'adsense_demo_footer_code' => $demo_footer_code,
            'show_advanced_text' => __('Mostra Opzioni Avanzate', 'curiosity-generator'),
            'hide_advanced_text' => __('Nascondi Opzioni Avanzate', 'curiosity-generator'),
            'error_text' => __('Si è verificato un errore. Riprova.', 'curiosity-generator')
        ));
    }
    
    /**
     * Returns a demo AdSense code for testing.
     * @param string $type Il tipo di annuncio: 'inline', 'fullscreen', 'header', 'footer'
     * @return string Il codice HTML dell'annuncio demo
     */
    private function get_demo_adsense_code($type = 'inline') {
        // Check if demo ads are disabled
        if (get_option('cg_disable_demo_ads', 0)) {
            return '';
        }
        
        $backgroundColor = '#f0f0f0';
        $borderColor = '#ccc';
        $height = '250px';
        $width = '100%';
        $margin = '10px 0';
        
        switch($type) {
            case 'fullscreen':
                $backgroundColor = '#e5f2ff';
                $borderColor = '#0073aa';
                $height = '400px';
                $title = 'ANNUNCIO A SCHERMO INTERO DEMO';
                break;
            case 'header':
                $backgroundColor = '#ffe8e8';
                $borderColor = '#ff6b6b';
                $height = '100px';
                $title = 'ANNUNCIO HEADER DEMO';
                break;
            case 'footer':
                $backgroundColor = '#e6ffe8';
                $borderColor = '#5cb85c';
                $height = '100px';
                $title = 'ANNUNCIO FOOTER DEMO';
                break;
            case 'inline':
            default:
                $backgroundColor = '#f0f0f0';
                $borderColor = '#ccc';
                $height = '250px';
                $title = 'ANNUNCIO INTERNO DEMO';
                break;
        }
        
        return '<div style="width: ' . $width . '; min-height: ' . $height . '; background-color: ' . $backgroundColor . '; border: 1px solid ' . $borderColor . '; display: flex; align-items: center; justify-content: center; margin: ' . $margin . '; padding: 20px; box-sizing: border-box;"><div style="text-align: center;"><strong>' . $title . '</strong><br>Questo è un annuncio demo per test<br><small>Sostituiscilo con il tuo codice AdSense reale</small></div></div>';
    }
    
    /**
     * Handle AJAX request for generating curiosities.
     */
    public function handle_generate_curiosity() {
        check_ajax_referer('cg_generate_nonce', 'nonce');
        
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'italiano';
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
            'language' => $language,
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
        
        // Get ad codes or use demo ads
        $inline_ad_code = get_option('cg_adsense_inline_code', '');
        $header_ad_code = get_option('cg_adsense_header_code', '');
        $footer_ad_code = get_option('cg_adsense_footer_code', '');
        $fullscreen_ad_code = get_option('cg_adsense_fullscreen_code', '');
        
        // Prepare response data
        $data = array(
            'post_ids' => $post_ids,
            'post_urls' => $post_urls,
            'post_titles' => $post_titles,
            'post_contents' => $post_contents,
            'message' => __('Curiosità generate con successo!', 'curiosity-generator'),
            'generation_credits' => $user_id ? $credits->get_user_generation_credits($user_id) : 0,
            'view_credits' => $user_id ? $credits->get_user_view_credits($user_id) : 0,
            'inline_ad' => !empty($inline_ad_code) ? $inline_ad_code : $this->get_demo_adsense_code('inline'),
            'header_ad' => !empty($header_ad_code) ? $header_ad_code : $this->get_demo_adsense_code('header'),
            'footer_ad' => !empty($footer_ad_code) ? $footer_ad_code : $this->get_demo_adsense_code('footer'),
            'fullscreen_ad' => !empty($fullscreen_ad_code) ? $fullscreen_ad_code : $this->get_demo_adsense_code('fullscreen')
        );
        
        wp_send_json_success($data);
    }

    /**
     * Handle AJAX request for generating featured image.
     */
    public function handle_generate_featured_image() {
        check_ajax_referer('cg_generate_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(__('ID del post non valido.', 'curiosity-generator'));
        }
        
        // Verifica se il post esiste e se è una curiosità
        $post = get_post($post_id);
        if (!$post || !get_post_meta($post_id, 'cg_generated', true)) {
            wp_send_json_error(__('Post non valido o non è una curiosità generata.', 'curiosity-generator'));
        }
        
        // Ottieni il titolo e il contenuto per la generazione del prompt
        $title = get_the_title($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $keyword = get_post_meta($post_id, 'cg_keyword', true);
        $type = get_post_meta($post_id, 'cg_type', true);
        
        // Crea un prompt per la generazione dell'immagine
        $prompt = "Crea un'immagine che illustri questa curiosità: '{$title}'. ";
        $prompt .= "Argomento principale: {$keyword}. ";
        $prompt .= "Tipo di curiosità: {$type}. ";
        $prompt .= "L'immagine deve essere dettagliata, realistica e adatta per un blog educativo.";
        
        // Limita la lunghezza del prompt
        $prompt = substr($prompt, 0, 1000);
        
        // Inizializza OpenRouter
        $openrouter = new CG_OpenRouter();
        
        // Genera l'immagine
        $result = $openrouter->generate_image($prompt, $post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Ottieni l'URL dell'immagine
        $image_url = wp_get_attachment_image_url($result, 'full');
        
        wp_send_json_success(array(
            'message' => __('Immagine in evidenza generata con successo!', 'curiosity-generator'),
            'image_url' => $image_url
        ));
    }
}