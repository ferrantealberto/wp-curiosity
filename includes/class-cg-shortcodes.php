<?php
/**
 * Shortcodes class.
 */
class CG_Shortcodes {
    
    /**
     * Render the curiosity generator form.
     */
    public function render_generator_form($atts) {
        // Extract any attributes from the shortcode
        $atts = shortcode_atts(
            array(
                'title' => __('Generate Curiosities', 'curiosity-generator'),
                'description' => __('Discover fascinating facts and curiosities!', 'curiosity-generator')
            ),
            $atts,
            'curiosity_generator_form'
        );
        
        // Check if API key is set
        $api_key = get_option('cg_openrouter_api_key', '');
        if (empty($api_key)) {
            if (current_user_can('manage_options')) {
                return '<div class="cg-error">' . __('Per favore configura la chiave API nelle impostazioni del plugin.', 'curiosity-generator') . '</div>';
            } else {
                return '<div class="cg-error">' . __('Il generatore di curiosità è attualmente non disponibile. Riprova più tardi.', 'curiosity-generator') . '</div>';
            }
        }
        
        // Get current user credits if logged in
        $user_id = get_current_user_id();
        $credits = 0;
        if ($user_id) {
            $credits_class = new CG_Credits();
            $credits = $credits_class->get_user_credits($user_id);
        }
        
        // Get form template
        ob_start();
        require_once CG_PLUGIN_DIR . 'public/views/curiosity-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render inline ads.
     */
    public function render_inline_ads($atts) {
        $adsense_code = get_option('cg_adsense_inline_code', '');
        if (empty($adsense_code)) {
            return '';
        }
        
        return '<div class="cg-inline-ad">' . $adsense_code . '</div>';
    }
}