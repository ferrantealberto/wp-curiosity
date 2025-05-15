<?php
/**
 * The main plugin class.
 */
class CG_Main {

    /**
     * Initialize the plugin.
     */
    public function run() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();
    }

    /**
     * Load required dependencies.
     */
    private function load_dependencies() {
        // Core plugin classes
        require_once CG_PLUGIN_DIR . 'includes/class-cg-admin.php';
        require_once CG_PLUGIN_DIR . 'includes/class-cg-frontend.php';
        require_once CG_PLUGIN_DIR . 'includes/class-cg-openrouter.php';
        require_once CG_PLUGIN_DIR . 'includes/class-cg-post-manager.php';
        require_once CG_PLUGIN_DIR . 'includes/class-cg-credits.php';
        require_once CG_PLUGIN_DIR . 'includes/class-cg-shortcodes.php';
        require_once CG_PLUGIN_DIR . 'includes/class-cg-scheduler.php';
        require_once CG_PLUGIN_DIR . 'includes/helpers/cg-utils.php';
    }

    /**
     * Set the locale for internationalization.
     */
    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'curiosity-generator',
            false,
            dirname(CG_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Register admin hooks.
     */
    private function define_admin_hooks() {
        $admin = new CG_Admin();
        
        // Admin menu and settings
        add_action('admin_menu', array($admin, 'add_plugin_admin_menu'));
        add_action('admin_init', array($admin, 'register_settings'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_admin_scripts'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_cg_refresh_models', array($admin, 'ajax_refresh_models'));
        add_action('wp_ajax_cg_update_user_credits', array($admin, 'ajax_update_user_credits'));
    }

    /**
     * Register public hooks.
     */
    private function define_public_hooks() {
        $frontend = new CG_Frontend();
        $credits = new CG_Credits();
        $scheduler = new CG_Scheduler();

        // Frontend scripts and styles
        add_action('wp_enqueue_scripts', array($frontend, 'enqueue_public_scripts'));
        
        // Enqueue dashicons for social sharing buttons
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_style('dashicons');
        });

        // AJAX handlers
        add_action('wp_ajax_generate_curiosity', array($frontend, 'handle_generate_curiosity'));
        add_action('wp_ajax_nopriv_generate_curiosity', array($frontend, 'handle_generate_curiosity'));
        
        // Handler AJAX per la generazione di immagini in evidenza
        add_action('wp_ajax_generate_featured_image', array($frontend, 'handle_generate_featured_image'));
        add_action('wp_ajax_nopriv_generate_featured_image', array($frontend, 'handle_generate_featured_image'));

        // Credits system
        add_action('the_content', array($credits, 'track_post_view'), 10);
    }

    /**
     * Register shortcodes.
     */
    private function define_shortcodes() {
        $shortcodes = new CG_Shortcodes();
        add_shortcode('curiosity_generator_form', array($shortcodes, 'render_generator_form'));
        add_shortcode('curiosita_ads_inline', array($shortcodes, 'render_inline_ads'));
    }

    /**
     * Plugin activation.
     */
    public static function activate() {
        // Create default options
        $default_options = array(
            'cg_openrouter_api_key' => '',
            'cg_llm_model' => 'anthropic/claude-3-opus',
            'cg_max_curiosities' => 5,
            'cg_min_curiosity_length' => 100,
            'cg_default_author' => 1,
            'cg_adsense_inline_code' => '',
            'cg_adsense_fullscreen_code' => '',
            'cg_adsense_header_code' => '',
            'cg_adsense_footer_code' => '',
            'cg_fullscreen_ad_frequency' => 5,
            'cg_generation_credits' => 5,
            'cg_view_credits' => 1,
            'cg_disable_demo_ads' => 0
        );

        // Only add options if they don't exist
        foreach ($default_options as $option => $value) {
            add_option($option, $value);
        }

        // Create "Curiosità" category if it doesn't exist
        if (!term_exists('Curiosità', 'category')) {
            wp_insert_term('Curiosità', 'category', array(
                'description' => 'Curiosità generate automaticamente',
                'slug' => 'curiosita'
            ));
        }
        
        // Create scheduler database table
        CG_Scheduler::create_table();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}