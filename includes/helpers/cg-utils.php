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
        
        // Verifica se il modello è gratuito
        $is_free = cg_model_is_free($model);
        
        // Determina la qualità del modello per la generazione di curiosità
        $quality_rating = cg_model_quality_rating($model);
        
        // Aggiungi informazioni di contesto se disponibili (qualità, token al minuto, ecc.)
        $context_info = '';
        if (isset($model['context_length'])) {
            $context_info .= ' - ' . $model['context_length'] . ' ctx';
        }
        if (isset($model['pricing']['prompt'])) {
            $context_info .= ' - $' . number_format($model['pricing']['prompt'], 4) . '/1M token';
        }
        
        // Aggiungi indicatori visivi per le varie categorie
        $indicators = array();
        if ($supports_images) {
            $indicators[] = 'immagini';
        }
        if ($is_free) {
            $indicators[] = 'gratis';
        }
        if ($quality_rating >= 4) {
            $indicators[] = 'alta qualità';
        }
        
        $indicators_text = !empty($indicators) ? ' (' . implode(', ', $indicators) . ')' : '';
        
        // Salva tutte le informazioni sul modello per i filtri
        $models[$model['id']] = array(
            'name' => $name . ' (' . $provider . $context_info . ')' . $indicators_text,
            'provider' => $provider,
            'supports_images' => $supports_images,
            'is_free' => $is_free,
            'quality_rating' => $quality_rating,
            'original_data' => $model
        );
    }
    
    // Se non sono stati trovati modelli, ritorna i predefiniti
    if (empty($models)) {
        return cg_get_default_models();
    }
    
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
    $default_models = array(
        'anthropic/claude-3-opus' => array(
            'name' => 'Claude 3 Opus (massima qualità)',
            'provider' => 'anthropic',
            'supports_images' => false,
            'is_free' => false,
            'quality_rating' => 5
        ),
        'anthropic/claude-3-sonnet' => array(
            'name' => 'Claude 3 Sonnet (bilanciato)',
            'provider' => 'anthropic',
            'supports_images' => false,
            'is_free' => false,
            'quality_rating' => 4
        ),
        'anthropic/claude-2' => array(
            'name' => 'Claude 2',
            'provider' => 'anthropic',
            'supports_images' => false,
            'is_free' => false,
            'quality_rating' => 3
        ),
        'openai/gpt-4' => array(
            'name' => 'GPT-4 (alta qualità)',
            'provider' => 'openai',
            'supports_images' => false,
            'is_free' => false,
            'quality_rating' => 5
        ),
        'openai/gpt-3.5-turbo' => array(
            'name' => 'GPT-3.5 Turbo (più veloce)',
            'provider' => 'openai',
            'supports_images' => false,
            'is_free' => false,
            'quality_rating' => 3
        ),
        'google/gemini-pro' => array(
            'name' => 'Gemini Pro',
            'provider' => 'google',
            'supports_images' => false,
            'is_free' => true,
            'quality_rating' => 4
        ),
        'meta-llama/llama-3-70b-instruct' => array(
            'name' => 'Llama 3 70B',
            'provider' => 'meta',
            'supports_images' => false,
            'is_free' => true,
            'quality_rating' => 4
        ),
        'openai/dall-e-3' => array(
            'name' => 'DALL·E 3 (Supporta immagini)',
            'provider' => 'openai',
            'supports_images' => true,
            'is_free' => false,
            'quality_rating' => 3
        ),
        'stability/stable-diffusion-xl-1024-v1-0' => array(
            'name' => 'Stable Diffusion XL 1.0 (Supporta immagini)',
            'provider' => 'stability',
            'supports_images' => true,
            'is_free' => true,
            'quality_rating' => 3
        ),
        'stability/stable-diffusion-3-large' => array(
            'name' => 'Stable Diffusion 3 Large (Supporta immagini)',
            'provider' => 'stability',
            'supports_images' => true,
            'is_free' => false,
            'quality_rating' => 4
        ),
        'midjourney/mj' => array(
            'name' => 'Midjourney (Supporta immagini)',
            'provider' => 'midjourney',
            'supports_images' => true,
            'is_free' => false,
            'quality_rating' => 5
        ),
        'google/imagen-2' => array(
            'name' => 'Google Imagen 2 (Supporta immagini)',
            'provider' => 'google',
            'supports_images' => true,
            'is_free' => false,
            'quality_rating' => 4
        ),
        'groq/llama3-70b-8192' => array(
            'name' => 'Groq LLama 3 70B (Velocissimo)',
            'provider' => 'groq',
            'supports_images' => false,
            'is_free' => true,
            'quality_rating' => 4
        ),
        'mistral/mistral-medium' => array(
            'name' => 'Mistral Medium',
            'provider' => 'mistral',
            'supports_images' => false,
            'is_free' => false,
            'quality_rating' => 3
        ),
        'mistral/mistral-small' => array(
            'name' => 'Mistral Small (gratis)',
            'provider' => 'mistral',
            'supports_images' => false,
            'is_free' => true,
            'quality_rating' => 2
        )
    );
    
    return $default_models;
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
    
    // Controlla con prefissi generici
    $provider_prefixes = array(
        'stability/',
        'midjourney/',
        'openai/dall-e',
        'openai/sdxl',
        'openai/imagen',
        'google/imagen'
    );
    
    if (in_array($model_id, $image_capable_models)) {
        return true;
    }
    
    foreach ($provider_prefixes as $prefix) {
        if (strpos($model_id, $prefix) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Verifica se un modello è gratuito da utilizzare.
 * @param array $model Dati del modello da verificare
 * @return bool True se il modello è gratuito, false altrimenti
 */
function cg_model_is_free($model) {
    // Lista di modelli noti per essere gratuiti
    $free_models = array(
        'google/gemini-pro',
        'google/gemini-pro-vision',
        'meta-llama/llama-3-8b-instruct',
        'meta-llama/llama-3-70b-instruct',
        'mistral/mistral-small',
        'mistral/mistral-tiny',
        'groq/llama3-70b-8192',
        'groq/llama3-8b-8192',
        'stability/stable-diffusion-xl-1024-v1-0'
    );
    
    // Provider noti per offrire modelli gratuiti
    $free_providers = array(
        'groq',
        'meta-llama',
        'openchat'
    );
    
    // Se è nella lista dei modelli gratuiti, ritorna true
    if (isset($model['id']) && in_array($model['id'], $free_models)) {
        return true;
    }
    
    // Se il pricing è 0 o assente, potrebbe essere gratuito
    if (isset($model['pricing']) && isset($model['pricing']['prompt']) && floatval($model['pricing']['prompt']) <= 0.0001) {
        return true;
    }
    
    // Controlla se il provider è noto per offrire modelli gratuiti
    if (isset($model['id'])) {
        foreach ($free_providers as $provider) {
            if (strpos($model['id'], $provider) === 0) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Assegna un rating di qualità al modello per la generazione di curiosità (1-5)
 * @param array $model Dati del modello da valutare
 * @return int Rating di qualità da 1 a 5
 */
function cg_model_quality_rating($model) {
    // Liste di modelli per diverse qualità
    $top_quality_models = array(
        'anthropic/claude-3-opus',
        'openai/gpt-4-turbo',
        'openai/gpt-4',
        'anthropic/claude-3-haiku',
        'midjourney/mj',
        'openai/gpt-4-vision'
    );
    
    $high_quality_models = array(
        'anthropic/claude-3-sonnet',
        'google/gemini-pro',
        'meta-llama/llama-3-70b-instruct',
        'groq/llama3-70b-8192',
        'google/imagen-2',
        'stability/stable-diffusion-3-large'
    );
    
    $medium_quality_models = array(
        'openai/gpt-3.5-turbo',
        'anthropic/claude-2',
        'mistral/mistral-medium',
        'openai/dall-e-3',
        'stability/stable-diffusion-xl-1024-v1-0'
    );
    
    $low_quality_models = array(
        'meta-llama/llama-3-8b-instruct',
        'mistral/mistral-small',
        'groq/llama3-8b-8192'
    );
    
    // Verifica in quale gruppo si trova il modello
    if (isset($model['id'])) {
        if (in_array($model['id'], $top_quality_models)) {
            return 5;
        } elseif (in_array($model['id'], $high_quality_models)) {
            return 4;
        } elseif (in_array($model['id'], $medium_quality_models)) {
            return 3;
        } elseif (in_array($model['id'], $low_quality_models)) {
            return 2;
        }
    }
    
    // Usa altre euristiche per determinare la qualità
    if (isset($model['context_length']) && $model['context_length'] > 16000) {
        return 4; // Modelli con context window grande sono spesso di qualità superiore
    }
    
    if (isset($model['id'])) {
        // Claude e GPT-4 sono generalmente di alta qualità
        if (strpos($model['id'], 'anthropic/claude') === 0 || strpos($model['id'], 'openai/gpt-4') === 0) {
            return 4;
        }
        
        // Gemini e Llama 3 sono di buona qualità
        if (strpos($model['id'], 'google/gemini') === 0 || strpos($model['id'], 'meta-llama/llama-3') === 0) {
            return 4;
        }
        
        // Mistral, GPT-3.5 sono di media qualità
        if (strpos($model['id'], 'mistral/') === 0 || strpos($model['id'], 'openai/gpt-3') === 0) {
            return 3;
        }
    }
    
    // Default per modelli sconosciuti
    return 2;
}

/**
 * Filtra un elenco di modelli in base a criteri specificati.
 * @param array $models Elenco dei modelli da filtrare
 * @param array $criteria Criteri di filtro (is_free, supports_images, min_quality)
 * @return array Elenco filtrato dei modelli
 */
function cg_filter_models($models, $criteria = array()) {
    $filtered_models = array();
    
    foreach ($models as $id => $model) {
        $pass_filter = true;
        
        // Filtra per modelli gratuiti
        if (isset($criteria['is_free']) && $criteria['is_free'] && !$model['is_free']) {
            $pass_filter = false;
        }
        
        // Filtra per supporto immagini
        if (isset($criteria['supports_images']) && $criteria['supports_images'] && !$model['supports_images']) {
            $pass_filter = false;
        }
        
        // Filtra per qualità minima
        if (isset($criteria['min_quality']) && $model['quality_rating'] < $criteria['min_quality']) {
            $pass_filter = false;
        }
        
        if ($pass_filter) {
            $filtered_models[$id] = $model;
        }
    }
    
    return $filtered_models;
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