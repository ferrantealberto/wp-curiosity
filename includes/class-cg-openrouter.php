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

        // Specifica istruzioni aggiuntive per evitare codici ASCII e caratteri speciali problematici
        $prompt .= "\n\nIMPORTANTE: Usa virgolette normali (\") e non tipografiche. Non usare caratteri speciali o entità HTML. Usa semplice testo UTF-8 standard.";

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
            
            // Decodifica le entità HTML in caratteri normali - prima passata
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Sostituisci manualmente i codici ASCII problematici che potrebbero non essere decodificati
            $problematic_codes = array(
                '&#8220;' => '"', // virgolette aperte
                '&#8221;' => '"', // virgolette chiuse
                '&#8217;' => "'", // apostrofo
                '&#8216;' => "'", // apice singolo aperto
                '&#8211;' => '-', // trattino medio
                '&#8212;' => '--', // trattino lungo
                '&amp;' => '&',    // e commerciale
                '&lt;' => '<',     // minore
                '&gt;' => '>',     // maggiore
                '&quot;' => '"',   // virgolette
                '&nbsp;' => ' ',   // spazio non divisibile
                '&lsquo;' => "'",  // virgoletta singola aperta
                '&rsquo;' => "'",  // virgoletta singola chiusa
                '&ldquo;' => '"',  // virgoletta doppia aperta
                '&rdquo;' => '"',  // virgoletta doppia chiusa
                '&ndash;' => '-',  // trattino 
                '&mdash;' => '--', // trattino lungo
                '&hellip;' => '...', // puntini di sospensione
            );
            
            $text = str_replace(array_keys($problematic_codes), array_values($problematic_codes), $text);
            
            // Decodifica una seconda volta per catturare eventuali entità annidate
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
                    $clean_tag = trim($tag);
                    // Applica la stessa pulizia anche ai tag
                    $clean_tag = html_entity_decode($clean_tag, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $clean_tag = str_replace(array_keys($problematic_codes), array_values($problematic_codes), $clean_tag);
                    $clean_tag = html_entity_decode($clean_tag, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    if (!empty($clean_tag)) {
                        $tags[] = $clean_tag;
                    }
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
     * Genera un'immagine utilizzando il modello LLM selezionato.
     */
    public function generate_image($prompt, $post_id) {
        $api_key = $this->get_api_key();
        $model = $this->get_model();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenRouter API key is missing');
        }

        // Verifica se il modello selezionato può generare immagini
        $can_generate_images = cg_model_can_generate_images($model);
        if (!$can_generate_images) {
            return new WP_Error('model_not_supported', 'Il modello selezionato non supporta la generazione di immagini');
        }

        // Prepara la richiesta API
        $endpoint = 'https://openrouter.ai/api/v1/images/generations';
        
        $request_body = array(
            'model' => $model, // Usa direttamente il modello LLM selezionato
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url'
        );

        $response = wp_remote_post($endpoint, array(
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

        if (empty($data) || !isset($data['data'][0]['url'])) {
            return new WP_Error('api_error', 'Invalid response from OpenRouter API. Il modello selezionato potrebbe non supportare la generazione di immagini.');
        }

        // Ottieni l'URL dell'immagine generata
        $image_url = $data['data'][0]['url'];
        
        // Scarica l'immagine e impostala come featured image
        return $this->set_image_as_featured($image_url, $post_id);
    }

    /**
     * Scarica un'immagine da un URL e la imposta come featured image per un post.
     */
    private function set_image_as_featured($image_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Scarica l'immagine
        $image_id = media_sideload_image($image_url, $post_id, '', 'id');
        
        if (is_wp_error($image_id)) {
            return $image_id;
        }
        
        // Imposta l'immagine come featured image
        set_post_thumbnail($post_id, $image_id);
        
        return $image_id;
    }
}