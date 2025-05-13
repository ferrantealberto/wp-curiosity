<?php
/**
 * Admin class for handling settings page.
 */
class CG_Admin {
    
    /**
     * Add plugin admin menu.
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('Impostazioni Generatore di Curiosità', 'curiosity-generator'),
            'Curiosity Generator',
            'manage_options',
            'curiosity-generator-settings',
            array($this, 'render_settings_page'),
            'dashicons-lightbulb',
            80
        );
        
        add_submenu_page(
            'curiosity-generator-settings',
            __('User Credits Management', 'curiosity-generator'),
            __('User Credits', 'curiosity-generator'),
            'manage_options',
            'curiosity-generator-credits',
            array($this, 'render_credits_page')
        );
    }
    
    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('cg_settings_group', 'cg_openrouter_api_key');
        register_setting('cg_settings_group', 'cg_llm_model');
        register_setting('cg_settings_group', 'cg_adsense_inline_code');
        register_setting('cg_settings_group', 'cg_adsense_fullscreen_code');
        register_setting('cg_settings_group', 'cg_adsense_header_code');
        register_setting('cg_settings_group', 'cg_adsense_footer_code');
        register_setting('cg_settings_group', 'cg_disable_demo_ads');
        register_setting('cg_settings_group', 'cg_fullscreen_ad_frequency', array($this, 'sanitize_number'));
        register_setting('cg_settings_group', 'cg_generation_credits', array($this, 'sanitize_number'));
        register_setting('cg_settings_group', 'cg_view_credits', array($this, 'sanitize_number'));
        register_setting('cg_settings_group', 'cg_max_curiosities', array($this, 'sanitize_number'));
        register_setting('cg_settings_group', 'cg_min_curiosity_length', array($this, 'sanitize_number'));
        register_setting('cg_settings_group', 'cg_default_author', array($this, 'sanitize_number'));
    }
    
    /**
     * Gestisce la richiesta AJAX per aggiornare i modelli OpenRouter.
     */
    public function ajax_refresh_models() {
        // Verifica il nonce per la sicurezza
        check_ajax_referer('cg_admin_nonce', 'nonce');
        
        // Verifica se l'utente ha i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai il permesso di eseguire questa azione.', 'curiosity-generator')));
        }
        
        // Ottieni la chiave API dalla richiesta
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('È richiesta la chiave API.', 'curiosity-generator')));
        }
        
        // Aggiorna la chiave API nelle opzioni
        update_option('cg_openrouter_api_key', $api_key);
        
        // Elimina i modelli in cache per forzare l'aggiornamento
        delete_transient('cg_openrouter_models');
        
        // Ottieni modelli aggiornati con aggiornamento forzato
        $models = cg_fetch_openrouter_models(true);
        
        // Restituisci i modelli
        wp_send_json_success(array('models' => $models));
    }
    
    /**
     * Ajax handler to update user credits
     */
    public function ajax_update_user_credits() {
        // Check nonce for security
        check_ajax_referer('cg_admin_nonce', 'nonce');
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'curiosity-generator')));
        }
        
        // Get parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $generation_credits = isset($_POST['generation_credits']) ? intval($_POST['generation_credits']) : 0;
        $view_credits = isset($_POST['view_credits']) ? intval($_POST['view_credits']) : 0;
        
        if (empty($user_id)) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'curiosity-generator')));
        }
        
        // Update credits
        update_user_meta($user_id, 'cg_generation_credits', $generation_credits);
        update_user_meta($user_id, 'cg_view_credits', $view_credits);
        
        wp_send_json_success(array(
            'message' => __('User credits updated successfully.', 'curiosity-generator')
        ));
    }
    
    /**
     * Sanitize number input.
     */
    public function sanitize_number($input) {
        return absint($input);
    }
    
    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_curiosity-generator-settings' === $hook || 'curiosity-generator_page_curiosity-generator-credits' === $hook) {
            // Carica prima jQuery UI
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
            
            // Carica Select2
            wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
            wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
            
            // Carica gli stili e gli script admin
            wp_enqueue_style('cg-admin-styles', CG_PLUGIN_URL . 'admin/css/cg-admin-styles.css', array(), CG_VERSION);
            wp_enqueue_script('cg-admin-scripts', CG_PLUGIN_URL . 'admin/js/cg-admin-scripts.js', array('jquery', 'select2', 'jquery-ui-tabs'), CG_VERSION, true);
            
            // Localizzazione
            wp_localize_script('cg-admin-scripts', 'cg_admin_object', array(
                'nonce' => wp_create_nonce('cg_admin_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'refresh_models_text' => __('Aggiorna Modelli', 'curiosity-generator'),
                'loading_models_text' => __('Caricamento modelli...', 'curiosity-generator'),
                'api_key_required_text' => __('Inserisci prima una chiave API.', 'curiosity-generator'),
                'models_refreshed_text' => __('Modelli aggiornati con successo!', 'curiosity-generator'),
                'error_text' => __('Si è verificato un errore durante l\'aggiornamento dei modelli. Riprova.', 'curiosity-generator'),
                'select_model_text' => __('Seleziona un modello', 'curiosity-generator')
            ));
        }
    }
    
    /**
     * Render settings page.
     */
    public function render_settings_page() {
        require_once CG_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    /**
     * Render credits management page.
     */
    public function render_credits_page() {
        require_once CG_PLUGIN_DIR . 'admin/views/users-credits.php';
    }
}