<?php
/**
 * OpenRouter API integration.
 */
class CG_OpenRouter {

    /**
     * OpenRouter API endpoint.
     */
    private $api_endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Get OpenRouter API key from settings.
     */
    private function get_api_key() {
        return get_option('cg_openrouter_api_key', '');
    }

    /**
     * Get selected LLM model from settings.
     */
    private function get_model() {
        return get_option('cg_llm_model', 'anthropic/claude-3-opus');
    }

    /**
     * Generate curiosities using OpenRouter API.
     */
    public function generate_curiosities($params) {
        $api_key = $this->get_api_key();
        $model = $this->get_model();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenRouter API key is missing');
        }

        // Build prompt from parameters
        $prompt = $this->build_prompt($params);

        // Prepare API request
        $request_body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 2000
        );

        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url()
            ),
            'body' => json_encode($request_body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid response from OpenRouter API');
        }

        // Parse the response to extract curiosities and tags
        return $this->parse_response($data['choices'][0]['message']['content']);
    }

    /**
     * Build prompt for the LLM based on user parameters.
     */
    private function build_prompt($params) {
        $keyword = sanitize_text_field($params['keyword']);
        $type = sanitize_text_field($params['type']);
        $count = intval($params['count']);
        $min_length = get_option('cg_min_curiosity_length', 100);
        $language = !empty($params['language']) ? sanitize_text_field($params['language']) : 'italiano';

        // Genera contenuto nella lingua selezionata (default: italiano)
        $prompt = "Genera {$count} curiosità uniche e interessanti in lingua {$language}. Ogni curiosità deve essere un paragrafo ben scritto, di almeno {$min_length} parole, adatto per un articolo di blog e per l'indicizzazione su Google News. Le curiosità devono essere fattualmente accurate per quanto possibile.\n\n";
        $prompt .= "Tema principale/Parola chiave: \"{$keyword}\"\n";
        $prompt .= "Tipologia di curiosità (obbligatoria): \"{$type}\"\n";
        $prompt .= "Lingua (obbligatoria): \"{$language}\"\n\n";

        // Add optional parameters if provided
        if (!empty($params['period'])) {
            $prompt .= "Periodo/Contesto Temporale: \"" . sanitize_text_field($params['period']) . "\"\n";
        }

        // Add other optional parameters
        $optional_params = array('param1', 'param2', 'param3', 'param4', 'param5', 'param6', 'param7', 'param8');
        foreach ($optional_params as $index => $param_key) {
            if (!empty($params[$param_key])) {
                $prompt .= "Parametro " . ($index + 1) . ": \"" . sanitize_text_field($params[$param_key]) . "\"\n";
            }
        }

        $prompt .= "\nPer ogni curiosità generata, fornisci anche una lista di 3-5 tag pertinenti in {$language} che descrivano il suo contenuto specifico, oltre ai parametri usati.\n\n";
        $prompt .= "Formato di output desiderato per ogni curiosità:\n";
        $prompt .= "Testo della curiosità: [Testo generato qui]\n";
        $prompt .= "Tag suggeriti: [tag1, tag2, tag3, tag4, tag5]\n\n";

        if ($count > 1) {
            $prompt .= "Separa ogni curiosità con una linea di tre trattini (---).";
        }

        return $prompt;
    }


    /**
     * Parse the LLM response to extract curiosities and tags.
     */
    private function parse_response($content) {
        $curiosities = array();

        // Split response by separator if multiple curiosities
        $items = explode('---', $content);

        foreach ($items as $item) {
            if (empty(trim($item))) {
                continue;
            }

            // Extract text and tags
            preg_match('/Testo della curiosità:\s*(.*?)(?=Tag suggeriti:|$)/s', $item, $text_matches);
            preg_match('/Tag suggeriti:\s*(.*?)$/s', $item, $tags_matches);

            // Ottieni il testo e decodifica subito le entità HTML
            $text = isset($text_matches[1]) ? trim($text_matches[1]) : '';
            // Decodifica le entità HTML in caratteri normali
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Rimuovi ogni possibile carattere di controllo e normalizza gli spazi
            $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
            $text = preg_replace('/\s+/', ' ', $text);

            $tags_string = isset($tags_matches[1]) ? trim($tags_matches[1]) : '';

            // Clean up tags
            $tags = array();
            if (!empty($tags_string)) {
                $tags_string = str_replace(array('[', ']'), '', $tags_string);
                $tags_array = explode(',', $tags_string);
                foreach ($tags_array as $tag) {
                    $tags[] = trim($tag);
                }
            }

            if (!empty($text)) {
                $curiosities[] = array(
                    'text' => $text,
                    'tags' => $tags
                );
            }
        }

        return $curiosities;
    }

    /**
     * Genera un'immagine utilizzando il metodo selezionato.
     * 
     * @param string $prompt Il prompt per la generazione dell'immagine
     * @param int $post_id L'ID del post per cui generare l'immagine
     * @return int|WP_Error ID dell'immagine generata o errore
     */
    public function generate_image($prompt, $post_id) {
        // Ottieni il metodo selezionato per la generazione di immagini
        $generation_method = get_option('cg_image_generation_method', 'ai_direct');
        
        // Genera l'immagine con il metodo appropriato
        if ($generation_method === 'n8n') {
            return $this->generate_image_with_n8n($prompt, $post_id);
        } else {
            // Metodo AI diretto (DALL-E, DeepSeek, OpenRouter)
            $ai_model = get_option('cg_image_ai_model', 'dalle3');
            
            switch ($ai_model) {
                case 'dalle3':
                    return $this->generate_image_with_dalle3($prompt, $post_id);
                case 'deepseek':
                    return $this->generate_image_with_deepseek($prompt, $post_id);
                case 'openrouter':
                    $model_id = get_option('cg_image_openrouter_model', 'openai/dall-e-3');
                    return $this->generate_image_with_openrouter($prompt, $post_id, $model_id);
                default:
                    return $this->generate_image_with_dalle3($prompt, $post_id);
            }
        }
    }

    /**
     * Genera un'immagine utilizzando DALL-E 3 tramite OpenRouter.
     */
    private function generate_image_with_dalle3($prompt, $post_id) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenRouter API key is missing');
        }

        // Usa direttamente l'endpoint per la generazione di immagini
        $image_endpoint = 'https://openrouter.ai/api/v1/images/generations';
        
        $image_request_body = array(
            'model' => 'openai/dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1792x1024', // Dimensione ottimale per WordPress (16:9)
            'quality' => 'hd',
            'response_format' => 'url',
            'style' => 'natural'
        );

        $image_response = wp_remote_post($image_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url()
            ),
            'body' => json_encode($image_request_body),
            'timeout' => 60
        ));

        if (is_wp_error($image_response)) {
            error_log('OpenRouter Image API Error: ' . $image_response->get_error_message());
            return $image_response;
        }

        $image_body = wp_remote_retrieve_body($image_response);
        $image_data = json_decode($image_body, true);

        if (empty($image_data) || !isset($image_data['data'][0]['url'])) {
            return new WP_Error('api_error', 'Invalid response from OpenRouter API for image generation: ' . $image_body);
        }

        // Ottieni l'URL dell'immagine generata
        $image_url = $image_data['data'][0]['url'];
        
        // Scarica l'immagine e impostala come featured image
        return $this->set_image_as_featured($image_url, $post_id);
    }

    /**
     * Genera un'immagine utilizzando DeepSeek tramite OpenRouter.
     */
    private function generate_image_with_deepseek($prompt, $post_id) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenRouter API key is missing');
        }

        // Usa direttamente l'endpoint per la generazione di immagini
        $image_endpoint = 'https://openrouter.ai/api/v1/images/generations';
        
        $image_request_body = array(
            'model' => 'deepseek/deepseek-coder-v2', // DeepSeek Coder V2
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024', // Dimensione standard
            'response_format' => 'url'
        );

        $image_response = wp_remote_post($image_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url()
            ),
            'body' => json_encode($image_request_body),
            'timeout' => 60
        ));

        if (is_wp_error($image_response)) {
            error_log('DeepSeek Image API Error: ' . $image_response->get_error_message());
            return $image_response;
        }

        $image_body = wp_remote_retrieve_body($image_response);
        $image_data = json_decode($image_body, true);

        if (empty($image_data) || !isset($image_data['data'][0]['url'])) {
            return new WP_Error('api_error', 'Invalid response from DeepSeek API for image generation: ' . $image_body);
        }

        // Ottieni l'URL dell'immagine generata
        $image_url = $image_data['data'][0]['url'];
        
        // Scarica l'immagine e impostala come featured image
        return $this->set_image_as_featured($image_url, $post_id);
    }

    /**
     * Genera un'immagine utilizzando un modello specifico di OpenRouter.
     */
    private function generate_image_with_openrouter($prompt, $post_id, $model_id) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenRouter API key is missing');
        }

        // Usa direttamente l'endpoint per la generazione di immagini
        $image_endpoint = 'https://openrouter.ai/api/v1/images/generations';
        
        $image_request_body = array(
            'model' => $model_id,
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024', // Dimensione standard
            'response_format' => 'url'
        );

        // Aggiungi parametri specifici per alcuni modelli
        if ($model_id === 'openai/dall-e-3') {
            $image_request_body['quality'] = 'hd';
            $image_request_body['size'] = '1792x1024';
            $image_request_body['style'] = 'natural';
        } elseif (strpos($model_id, 'stability') !== false) {
            // Parametri specifici per Stable Diffusion
            $image_request_body['cfg_scale'] = 7.5;
            $image_request_body['steps'] = 30;
        } elseif (strpos($model_id, 'midjourney') !== false) {
            // Parametri specifici per Midjourney
            $image_request_body['quality'] = 1;
        }

        $image_response = wp_remote_post($image_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url()
            ),
            'body' => json_encode($image_request_body),
            'timeout' => 120 // Aumento del timeout per modelli più complessi
        ));

        if (is_wp_error($image_response)) {
            error_log('OpenRouter Image API Error: ' . $image_response->get_error_message());
            return $image_response;
        }

        $image_body = wp_remote_retrieve_body($image_response);
        $image_data = json_decode($image_body, true);

        if (empty($image_data) || !isset($image_data['data'][0]['url'])) {
            return new WP_Error('api_error', 'Invalid response from OpenRouter API for image generation: ' . $image_body);
        }

        // Ottieni l'URL dell'immagine generata
        $image_url = $image_data['data'][0]['url'];
        
        // Scarica l'immagine e impostala come featured image
        return $this->set_image_as_featured($image_url, $post_id);
    }

    /**
     * Genera un'immagine utilizzando n8n.
     */
    private function generate_image_with_n8n($prompt, $post_id) {
        // Ottieni l'URL e il token dell'webhook n8n
        $n8n_webhook_url = get_option('cg_n8n_webhook_url', '');
        $n8n_api_token = get_option('cg_n8n_api_token', '');

        if (empty($n8n_webhook_url)) {
            return new WP_Error('missing_n8n_url', 'n8n webhook URL is missing');
        }

        // Prepara i dati da inviare a n8n
        $data = array(
            'prompt' => $prompt,
            'post_id' => $post_id,
            'site_url' => site_url(),
            'token' => $n8n_api_token
        );

        // Invia la richiesta a n8n
        $response = wp_remote_post($n8n_webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 120 // Timeout più lungo per l'elaborazione di n8n
        ));

        if (is_wp_error($response)) {
            error_log('n8n Webhook Error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Verifica la risposta di n8n
        if (empty($data) || !isset($data['success']) || !$data['success']) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error';
            return new WP_Error('n8n_error', 'Error from n8n: ' . $error_message);
        }

        // Se n8n ha creato l'immagine direttamente e ha restituito l'ID dell'allegato
        if (isset($data['attachment_id']) && $data['attachment_id'] > 0) {
            // Imposta l'immagine come featured image
            set_post_thumbnail($post_id, $data['attachment_id']);
            return $data['attachment_id'];
        }

        // Se n8n ha restituito un URL dell'immagine
        if (isset($data['image_url']) && !empty($data['image_url'])) {
            // Scarica l'immagine e impostala come featured image
            return $this->set_image_as_featured($data['image_url'], $post_id);
        }

        return new WP_Error('n8n_no_image', 'n8n did not return an image');
    }

    /**
     * Ottimizza il prompt per la generazione di immagini.
     */
    private function optimize_image_prompt($base_prompt, $post_id) {
        // Ottieni informazioni dal post
        $post_title = get_the_title($post_id);
        $keyword = get_post_meta($post_id, 'cg_keyword', true);
        $type = get_post_meta($post_id, 'cg_type', true);
        $language = get_post_meta($post_id, 'cg_language', true);

        // Mappa dei tipi per elementi visivi
        $visual_elements = array(
            'historical-facts' => 'historical scene, period architecture, vintage elements',
            'science-nature' => 'scientific illustration, natural phenomena, educational diagram',
            'technology' => 'modern technology, clean design, futuristic elements',
            'art-culture' => 'artistic composition, cultural symbols, museum quality',
            'geography' => 'landscape, geographical features, natural beauty',
            'famous-people' => 'portrait style, elegant composition, biographical elements',
            'mysteries' => 'mysterious atmosphere, intriguing elements, dramatic lighting',
            'statistics' => 'infographic style, data visualization, clean charts',
            'word-origins' => 'typography, book elements, scholarly atmosphere',
            'traditions' => 'cultural ceremony, traditional elements, festive atmosphere'
        );

        // Costruisci il prompt ottimizzato
        $optimized = "Create a high-quality, educational illustration for a blog post about: {$post_title}. ";
        $optimized .= "Main topic: {$keyword}. ";
        
        if (!empty($type) && isset($visual_elements[$type])) {
            $optimized .= "Visual style: " . $visual_elements[$type] . ". ";
        }
        
        $optimized .= "The image should be professional, engaging, and suitable for an educational blog. ";
        $optimized .= "Use vibrant but tasteful colors, clear composition, good lighting. ";
        $optimized .= "Avoid text overlays. 16:9 aspect ratio. Photorealistic style.";

        // Limita la lunghezza del prompt
        return substr($optimized, 0, 4000);
    }

    /**
     * Scarica un'immagine da un URL e la imposta come featured image per un post.
     */
    private function set_image_as_featured($image_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Genera un nome file descrittivo
        $post_title = get_the_title($post_id);
        $safe_title = sanitize_file_name($post_title);
        $filename = 'curiosity-' . $post_id . '-' . substr($safe_title, 0, 50) . '.jpg';
        
        try {
            // Scarica l'immagine
            $image_id = media_sideload_image($image_url, $post_id, $filename, 'id');
            
            if (is_wp_error($image_id)) {
                error_log('Media sideload error: ' . $image_id->get_error_message());
                return $image_id;
            }
            
            // Imposta l'immagine come featured image
            set_post_thumbnail($post_id, $image_id);
            
            // Aggiungi metadati all'immagine
            update_post_meta($image_id, '_wp_attachment_image_alt', 'Immagine in evidenza per: ' . get_the_title($post_id));
            
            // Log di successo
            error_log('Featured image set successfully for post ID: ' . $post_id);
            
            return $image_id;
        } catch (Exception $e) {
            error_log('Exception in set_image_as_featured: ' . $e->getMessage());
            return new WP_Error('image_processing_error', 'Error processing image: ' . $e->getMessage());
        }
    }
}