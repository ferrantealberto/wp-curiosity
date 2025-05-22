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
     * AGGIORNATO: Genera un'immagine utilizzando direttamente DALL-E 3.
     */
    public function generate_image($prompt, $post_id) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenRouter API key is missing');
        }

        // Crea un prompt ottimizzato direttamente per DALL-E 3
        $optimized_prompt = $this->optimize_image_prompt($prompt, $post_id);

        // Usa direttamente l'endpoint per la generazione di immagini
        $image_endpoint = 'https://openrouter.ai/api/v1/images/generations';
        
        $image_request_body = array(
            'model' => 'openai/dall-e-3',
            'prompt' => $optimized_prompt,
            'n' => 1,
            'size' => '1792x1024', // NUOVO: Dimensione ottimale per WordPress (16:9)
            'quality' => 'hd',
            'response_format' => 'url',
            'style' => 'natural' // NUOVO: Stile naturale per blog educativi
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

        // Log per debug
        error_log('OpenRouter Image Response: ' . $image_body);

        if (empty($image_data) || !isset($image_data['data'][0]['url'])) {
            return new WP_Error('api_error', 'Invalid response from OpenRouter API for image generation: ' . $image_body);
        }

        // Ottieni l'URL dell'immagine generata
        $image_url = $image_data['data'][0]['url'];
        
        // Scarica l'immagine e impostala come featured image
        return $this->set_image_as_featured($image_url, $post_id);
    }

    /**
     * NUOVO: Ottimizza il prompt per la generazione di immagini.
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

        // Limita la lunghezza del prompt per DALL-E 3
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