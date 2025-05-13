<?php
/**
 * Utility functions for the Curiosity Generator plugin.
 */

/**
 * Recupera i modelli disponibili dall'API OpenRouter.
 * 
 * @param bool $force_refresh Forza l'aggiornamento della cache
 * @return array Lista dei modelli disponibili o modelli predefiniti in caso di errore
 */
function cg_fetch_openrouter_models($force_refresh = false) {
    // Se non è richiesto l'aggiornamento forzato, controlla se abbiamo modelli in cache non scaduti
    if (!$force_refresh) {
        $cached_models = get_transient('cg_openrouter_models');
        if ($cached_models !== false) {
            return $cached_models;
        }
    }
    
    // Ottieni la chiave API dalle impostazioni
    $api_key = get_option('cg_openrouter_api_key', '');
    if (empty($api_key)) {
        return cg_get_default_models();
    }
    
    // Effettua la richiesta API a OpenRouter
    $response = wp_remote_get('https://openrouter.ai/api/v1/models', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => home_url()
        ),
        'timeout' => 15
    ));
    
    // Controlla se la richiesta ha avuto successo
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('OpenRouter API Error: ' . (is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response)));
        return cg_get_default_models();
    }
    
    // Analizza la risposta
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data) || !isset($data['data']) || !is_array($data['data'])) {
        error_log('OpenRouter API Invalid Response: ' . $body);
        return cg_get_default_models();
    }
    
    // Formatta i modelli per il dropdown
    $models = array();
    foreach ($data['data'] as $model) {
        // Salta i modelli che non supportano la generazione di testo
        if (!isset($model['id']) || empty($model['id'])) {
            continue;
        }
        
        // Crea un'etichetta descrittiva con nome del modello e provider
        $provider = isset($model['provider']) ? $model['provider'] : 'Sconosciuto';
        $name = isset($model['name']) ? $model['name'] : $model['id'];
        
        // Verifica se il modello supporta la generazione di immagini
        $supports_images = cg_model_can_generate_images($model['id']);
        
        // Aggiungi informazioni di contesto se disponibili (qualità, token al minuto, ecc.)
        $context_info = '';
        if (isset($model['context_length'])) {
            $context_info .= ' - ' . $model['context_length'] . ' ctx';
        }
        if (isset($model['pricing']['prompt'])) {
            $context_info .= ' - $' . number_format($model['pricing']['prompt'], 4) . '/1M token';
        }
        
        $models[$model['id']] = $name . ' (' . $provider . $context_info . ')' . ($supports_images ? ' (Supporta immagini)' : '');
    }
    
    // Se non sono stati trovati modelli, ritorna i predefiniti
    if (empty($models)) {
        return cg_get_default_models();
    }
    
    // Ordina i modelli alfabeticamente
    asort($models);
    
    // Salva i risultati in cache per 24 ore
    set_transient('cg_openrouter_models', $models, 24 * HOUR_IN_SECONDS);
    
    return $models;
}

/**
 * Ottieni i modelli LLM disponibili per OpenRouter.
 * Prima prova a recuperarli dall'API, poi utilizza i predefiniti come fallback.
 */
function cg_get_available_models() {
    return cg_fetch_openrouter_models();
}

/**
 * Ottieni i modelli LLM predefiniti per OpenRouter.
 * Utilizzati come fallback quando l'API non è disponibile.
 */
function cg_get_default_models() {
    return array(
        'anthropic/claude-3-opus' => 'Claude 3 Opus (massima qualità)',
        'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet (bilanciato)',
        'anthropic/claude-2' => 'Claude 2',
        'openai/gpt-4' => 'GPT-4 (alta qualità)',
        'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo (più veloce)',
        'google/gemini-pro' => 'Gemini Pro',
        'meta-llama/llama-3-70b-instruct' => 'Llama 3 70B',
        'openai/dall-e-3' => 'DALL·E 3 (Supporta immagini)',
        'stability/stable-diffusion-xl-1024-v1-0' => 'Stable Diffusion XL 1.0 (Supporta immagini)',
        'stability/stable-diffusion-3-large' => 'Stable Diffusion 3 Large (Supporta immagini)',
        'midjourney/mj' => 'Midjourney (Supporta immagini)',
        'google/imagen-2' => 'Google Imagen 2 (Supporta immagini)'
    );
}

/**
 * Verifica se un modello specifico può generare immagini.
 * @param string $model_id ID del modello da verificare
 * @return bool True se il modello supporta la generazione di immagini, false altrimenti
 */
function cg_model_can_generate_images($model_id) {
    $image_capable_models = array(
        'openai/dall-e-3',
        'stability/stable-diffusion-xl-1024-v1-0',
        'stability/stable-diffusion-3-large',
        'midjourney/mj',
        'google/imagen-2'
    );
    
    return in_array($model_id, $image_capable_models);
}

/**
 * Get default curiosity types.
 */
function cg_get_default_types() {
    return array(
        'historical-facts' => __('Fatti Storici', 'curiosity-generator'),
        'science-nature' => __('Scienza e Natura', 'curiosity-generator'),
        'technology' => __('Tecnologia', 'curiosity-generator'),
        'art-culture' => __('Arte e Cultura', 'curiosity-generator'),
        'geography' => __('Geografia', 'curiosity-generator'),
        'famous-people' => __('Personaggi Famosi', 'curiosity-generator'),
        'mysteries' => __('Misteri', 'curiosity-generator'),
        'statistics' => __('Statistiche Incredibili', 'curiosity-generator'),
        'word-origins' => __('Origine delle Parole', 'curiosity-generator'),
        'traditions' => __('Tradizioni Bizzarre', 'curiosity-generator')
    );
}

/**
 * Get available languages for curiosity generation.
 */
function cg_get_available_languages() {
    return array(
        'italiano' => __('Italiano', 'curiosity-generator'),
        'english' => __('Inglese', 'curiosity-generator'),
        'espanol' => __('Spagnolo', 'curiosity-generator'),
        'francais' => __('Francese', 'curiosity-generator'),
        'deutsch' => __('Tedesco', 'curiosity-generator'),
        'portugues' => __('Portoghese', 'curiosity-generator'),
        'русский' => __('Russo', 'curiosity-generator'),
        '中文' => __('Cinese', 'curiosity-generator'),
        '日本語' => __('Giapponese', 'curiosity-generator'),
        'العربية' => __('Arabo', 'curiosity-generator'),
        'हिन्दी' => __('Hindi', 'curiosity-generator')
    );
}

/**
 * Format a number of credits for display.
 */
function cg_format_credits($credits) {
    return number_format($credits);
}

/**
 * Check if an ad should be displayed based on batch count.
 */
function cg_should_display_fullscreen_ad($batch_count) {
    $frequency = get_option('cg_fullscreen_ad_frequency', 5);
    
    // Always show ad after first batch and last curiosity in any batch
    if ($batch_count == 1) {
        return true;
    }
    
    // Show ad based on frequency
    if ($batch_count % $frequency == 0) {
        return true;
    }
    
    return false;
}

/**
 * Get all WordPress users for the default author dropdown.
 */
function cg_get_users_for_dropdown() {
    $users = get_users(array('role__in' => array('administrator', 'editor', 'author')));
    $options = array();
    
    foreach ($users as $user) {
        $options[$user->ID] = $user->display_name . ' (' . $user->user_login . ')';
    }
    
    return $options;
}