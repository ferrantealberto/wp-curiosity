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
        
        add_submenu_page(
            'curiosity-generator-settings',
            __('Programmazione Curiosità', 'curiosity-generator'),
            __('Programmazione', 'curiosity-generator'),
            'manage_options',
            'curiosity-generator-scheduler',
            array($this, 'render_scheduler_page')
        );
        
        // Aggiunta della pagina di gestione post
        add_submenu_page(
            'curiosity-generator-settings',
            __('Gestione Post Curiosità', 'curiosity-generator'),
            __('Gestione Post', 'curiosity-generator'),
            'manage_options',
            'curiosity-generator-posts',
            array($this, 'render_posts_management_page')
        );
    }
    
    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Impostazioni esistenti
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
        
        // Nuove impostazioni per la generazione di immagini
        register_setting('cg_settings_group', 'cg_image_generation_method');
        register_setting('cg_settings_group', 'cg_image_ai_model');
        register_setting('cg_settings_group', 'cg_image_openrouter_model');
        register_setting('cg_settings_group', 'cg_n8n_webhook_url');
        register_setting('cg_settings_group', 'cg_n8n_api_token');
        register_setting('cg_settings_group', 'cg_image_size');
        register_setting('cg_settings_group', 'cg_image_quality');
        register_setting('cg_settings_group', 'cg_image_style');
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
     * Handler AJAX per le azioni bulk sui post
     */
    public function ajax_bulk_posts_action() {
        // Verifica il nonce per la sicurezza
        check_ajax_referer('cg_admin_nonce', 'nonce');
        
        // Verifica se l'utente ha i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai il permesso di eseguire questa azione.', 'curiosity-generator')));
        }
        
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($action) || empty($post_ids)) {
            wp_send_json_error(array('message' => __('Azione o post non validi.', 'curiosity-generator')));
        }
        
        $post_manager = new CG_Post_Manager();
        
        switch ($action) {
            case 'publish':
            case 'private':
            case 'draft':
            case 'pending':
                $result = $post_manager->update_posts_status($post_ids, $action);
                if ($result) {
                    $status_labels = array(
                        'publish' => __('pubblicati', 'curiosity-generator'),
                        'private' => __('privati', 'curiosity-generator'),
                        'draft' => __('bozze', 'curiosity-generator'),
                        'pending' => __('in attesa di revisione', 'curiosity-generator')
                    );
                    $message = sprintf(__('Post aggiornati come %s con successo.', 'curiosity-generator'), $status_labels[$action]);
                    wp_send_json_success(array('message' => $message));
                } else {
                    wp_send_json_error(array('message' => __('Errore nell\'aggiornamento dei post.', 'curiosity-generator')));
                }
                break;
                
            case 'trash':
                $result = $post_manager->update_posts_status($post_ids, 'trash');
                if ($result) {
                    wp_send_json_success(array('message' => __('Post spostati nel cestino con successo.', 'curiosity-generator')));
                } else {
                    wp_send_json_error(array('message' => __('Errore nello spostamento dei post nel cestino.', 'curiosity-generator')));
                }
                break;
                
            case 'delete':
                $result = $post_manager->delete_posts($post_ids, true);
                if ($result) {
                    wp_send_json_success(array('message' => __('Post eliminati definitivamente con successo.', 'curiosity-generator')));
                } else {
                    wp_send_json_error(array('message' => __('Errore nell\'eliminazione definitiva dei post.', 'curiosity-generator')));
                }
                break;
                
            default:
                wp_send_json_error(array('message' => __('Azione non riconosciuta.', 'curiosity-generator')));
        }
    }
    
    /**
     * Handler AJAX per il caricamento dei post con filtri
     */
    public function ajax_load_posts() {
        // Verifica il nonce per la sicurezza
        check_ajax_referer('cg_admin_nonce', 'nonce');
        
        // Verifica se l'utente ha i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai il permesso di eseguire questa azione.', 'curiosity-generator')));
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $featured_image = isset($_POST['featured_image']) ? sanitize_text_field($_POST['featured_image']) : '';
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
        
        $args = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => array(
                array(
                    'key' => 'cg_generated',
                    'value' => true,
                    'compare' => '='
                )
            )
        );
        
        // Filtro per status
        if (!empty($status) && $status !== 'any') {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = 'any';
        }
        
        // Filtro per immagine in evidenza
        if (!empty($featured_image) && $featured_image !== 'any') {
            if ($featured_image === 'with_image') {
                $args['meta_query'][] = array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                );
            } elseif ($featured_image === 'without_image') {
                $args['meta_query'][] = array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                );
            }
        }
        
        // Filtro per ricerca
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $posts = get_posts($args);
        
        // Conta il totale per la paginazione
        $count_args = $args;
        $count_args['posts_per_page'] = -1;
        unset($count_args['paged']);
        $all_posts = get_posts($count_args);
        $total = count($all_posts);
        
        $response_data = array(
            'posts' => array(),
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        );
        
        foreach ($posts as $post) {
            $response_data['posts'][] = array(
                'ID' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'date' => $post->post_date,
                'author' => get_the_author_meta('display_name', $post->post_author),
                'keyword' => get_post_meta($post->ID, 'cg_keyword', true),
                'type' => get_post_meta($post->ID, 'cg_type', true),
                'language' => get_post_meta($post->ID, 'cg_language', true),
                'view_count' => get_post_meta($post->ID, 'cg_view_count', true),
                'has_featured_image' => has_post_thumbnail($post->ID),
                'edit_link' => get_edit_post_link($post->ID),
                'view_link' => get_permalink($post->ID)
            );
        }
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Handler AJAX per la generazione di immagini in evidenza
     */
    public function ajax_generate_featured_image() {
        // Verifica il nonce per la sicurezza
        check_ajax_referer('cg_admin_nonce', 'nonce');
        
        // Verifica se l'utente ha i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai il permesso di eseguire questa azione.', 'curiosity-generator')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('ID del post non valido.', 'curiosity-generator')));
        }
        
        // Verifica se il post esiste e se è una curiosità
        $post = get_post($post_id);
        if (!$post || !get_post_meta($post_id, 'cg_generated', true)) {
            wp_send_json_error(array('message' => __('Post non valido o non è una curiosità generata.', 'curiosity-generator')));
        }
        
        // Verifica se il post ha già un'immagine in evidenza
        if (has_post_thumbnail($post_id)) {
            wp_send_json_error(array('message' => __('Il post ha già un\'immagine in evidenza.', 'curiosity-generator')));
        }
        
        // Ottieni il titolo e il contenuto per la generazione del prompt
        $title = get_the_title($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $keyword = get_post_meta($post_id, 'cg_keyword', true);
        $type = get_post_meta($post_id, 'cg_type', true);
        $language = get_post_meta($post_id, 'cg_language', true);
        
        // Crea un prompt per la generazione dell'immagine basato sulla lingua
        if ($language === 'italiano' || empty($language)) {
            $prompt = "Crea un'immagine dettagliata e realistica che illustri questa curiosità: '{$title}'. ";
            $prompt .= "Argomento principale: {$keyword}. ";
            if (!empty($type)) {
                $types_map = array(
                    'historical-facts' => 'fatti storici',
                    'science-nature' => 'scienza e natura',
                    'technology' => 'tecnologia',
                    'art-culture' => 'arte e cultura',
                    'geography' => 'geografia',
                    'famous-people' => 'personaggi famosi',
                    'mysteries' => 'misteri',
                    'statistics' => 'statistiche',
                    'word-origins' => 'origine delle parole',
                    'traditions' => 'tradizioni'
                );
                $type_italian = isset($types_map[$type]) ? $types_map[$type] : $type;
                $prompt .= "Tipo di curiosità: {$type_italian}. ";
            }
            $prompt .= "L'immagine deve essere educativa, coinvolgente e adatta per un blog di divulgazione. ";
            $prompt .= "Stile realistico e di alta qualità, con colori vivaci e composizione interessante.";
        } else {
            $prompt = "Create a detailed and realistic image that illustrates this curiosity: '{$title}'. ";
            $prompt .= "Main topic: {$keyword}. ";
            if (!empty($type)) {
                $prompt .= "Type of curiosity: {$type}. ";
            }
            $prompt .= "The image should be educational, engaging and suitable for an educational blog. ";
            $prompt .= "Realistic style and high quality, with vivid colors and interesting composition.";
        }
        
        // Limita la lunghezza del prompt
        $prompt = substr($prompt, 0, 1000);
        
        // Inizializza OpenRouter
        $openrouter = new CG_OpenRouter();
        
        // Genera l'immagine
        $result = $openrouter->generate_image($prompt, $post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Ottieni l'URL dell'immagine
        $image_url = wp_get_attachment_image_url($result, 'full');
        
        wp_send_json_success(array(
            'message' => __('Immagine in evidenza generata con successo!', 'curiosity-generator'),
            'image_url' => $image_url
        ));
    }
    
    /**
     * Test n8n webhook connection.
     */
    public function ajax_test_n8n_webhook() {
        // Verifica il nonce per la sicurezza
        check_ajax_referer('cg_admin_nonce', 'nonce');
        
        // Verifica se l'utente ha i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $webhook_url = isset($_POST['webhook_url']) ? esc_url_raw($_POST['webhook_url']) : '';
        $api_token = isset($_POST['api_token']) ? sanitize_text_field($_POST['api_token']) : '';
        
        if (empty($webhook_url)) {
            wp_send_json_error('URL webhook mancante');
        }
        
        // Prepara i dati per il test
        $test_data = array(
            'action' => 'test',
            'site_url' => site_url(),
            'token' => $api_token
        );
        
        // Invia una richiesta di test al webhook
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($test_data),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Errore nella connessione: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            wp_send_json_error('Risposta non valida (codice ' . $status_code . ')');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['success']) || !$data['success']) {
            $error_message = isset($data['message']) ? $data['message'] : 'Risposta non valida dal webhook';
            wp_send_json_error($error_message);
        }
        
        wp_send_json_success();
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
        if ('toplevel_page_curiosity-generator-settings' === $hook || 
            'curiosity-generator_page_curiosity-generator-credits' === $hook ||
            'curiosity-generator_page_curiosity-generator-scheduler' === $hook ||
            'curiosity-generator_page_curiosity-generator-posts' === $hook) {
            
            wp_enqueue_style('cg-admin-styles', CG_PLUGIN_URL . 'admin/css/cg-admin-styles.css', array(), CG_VERSION);
            
            // Nuovo stile per la generazione di immagini
            wp_enqueue_style('cg-image-generator-styles', CG_PLUGIN_URL . 'admin/css/cg-image-generator-styles.css', array(), CG_VERSION);
            
            // Carica Select2 per i dropdown avanzati
            wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
            wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
            
            wp_enqueue_script('cg-admin-scripts', CG_PLUGIN_URL . 'admin/js/cg-admin-scripts.js', array('jquery', 'select2'), CG_VERSION, true);
            
            // Carica lo script per la generazione di immagini
            wp_enqueue_script('cg-image-generator', CG_PLUGIN_URL . 'admin/js/cg-image-generator.js', array('jquery', 'select2'), CG_VERSION, true);
            
            // Localizza lo script per l'AJAX
            wp_localize_script('cg-admin-scripts', 'cg_admin_object', array(
                'nonce' => wp_create_nonce('cg_admin_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'refresh_models_text' => __('Aggiorna Modelli', 'curiosity-generator'),
                'loading_models_text' => __('Caricamento modelli...', 'curiosity-generator'),
                'api_key_required_text' => __('Inserisci prima una chiave API.', 'curiosity-generator'),
                'models_refreshed_text' => __('Modelli aggiornati con successo!', 'curiosity-generator'),
                'error_text' => __('Si è verificato un errore durante l\'aggiornamento dei modelli. Riprova.', 'curiosity-generator'),
                'select_model_text' => __('Seleziona un modello', 'curiosity-generator'),
                'confirm_delete_text' => __('Sei sicuro di voler eliminare definitivamente i post selezionati?', 'curiosity-generator'),
                'no_posts_selected_text' => __('Seleziona almeno un post per eseguire questa azione.', 'curiosity-generator'),
                'loading_text' => __('Caricamento...', 'curiosity-generator')
            ));
            
            // Carica gli stili e gli script per la pagina di programmazione solo se necessario
            if ('curiosity-generator_page_curiosity-generator-scheduler' === $hook) {
                wp_enqueue_style('cg-scheduler-styles', CG_PLUGIN_URL . 'admin/css/cg-scheduler-styles.css', array(), CG_VERSION);
                wp_enqueue_script('cg-scheduler-scripts', CG_PLUGIN_URL . 'admin/js/cg-scheduler-scripts.js', array('jquery'), CG_VERSION, true);
                
                wp_localize_script('cg-scheduler-scripts', 'cg_scheduler_object', array(
                    'confirm_delete_text' => __('Sei sicuro di voler eliminare questa programmazione?', 'curiosity-generator'),
                    'random_option_text' => __('Lascia vuoto per utilizzare un valore casuale.', 'curiosity-generator'),
                    'type_description_text' => __('Seleziona un tipo o lascia vuoto per casuale', 'curiosity-generator'),
                    'language_description_text' => __('Seleziona una lingua o lascia vuoto per casuale', 'curiosity-generator'),
                    'tone_description_text' => __('Seleziona un tono o lascia vuoto per casuale', 'curiosity-generator'),
                    'source_description_text' => __('Seleziona un tipo di fonte o lascia vuoto per casuale', 'curiosity-generator'),
                    'audience_description_text' => __('Seleziona un pubblico o lascia vuoto per casuale', 'curiosity-generator')
                ));
            }
            
            // Script per la gestione dei post
            if ('curiosity-generator_page_curiosity-generator-posts' === $hook) {
                wp_enqueue_script('cg-posts-management', CG_PLUGIN_URL . 'admin/js/cg-posts-management.js', array('jquery'), CG_VERSION, true);
            }
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
    
    /**
     * Render scheduler page.
     */
    public function render_scheduler_page() {
        require_once CG_PLUGIN_DIR . 'admin/views/scheduler-page.php';
    }
    
    /**
     * Render posts management page.
     */
    public function render_posts_management_page() {
        require_once CG_PLUGIN_DIR . 'admin/views/posts-management-page.php';
    }
}