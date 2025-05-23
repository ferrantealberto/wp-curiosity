<h2><?php _e('Impostazioni API OpenRouter', 'curiosity-generator'); ?></h2>
<p><?php _e('Inserisci la tua chiave API OpenRouter e seleziona il modello LLM da utilizzare per generare curiosità.', 'curiosity-generator'); ?></p>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="cg_openrouter_api_key"><?php _e('Chiave API', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <input type="password" name="cg_openrouter_api_key" id="cg_openrouter_api_key" class="regular-text" value="<?php echo esc_attr(get_option('cg_openrouter_api_key', '')); ?>" />
            <p class="description"><?php _e('Ottieni la tua chiave API da', 'curiosity-generator'); ?> <a href="https://openrouter.ai/keys" target="_blank">OpenRouter.ai</a></p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="cg_llm_model"><?php _e('Modello LLM', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <div class="cg-model-selector-wrapper">
                <select name="cg_llm_model" id="cg_llm_model" class="cg-select2-models">
                    <?php
                    $current_model = get_option('cg_llm_model', 'anthropic/claude-3-opus');
                    $models = cg_get_available_models();
                    
                    foreach ($models as $model_id => $model_name) {
                        $can_generate_images = cg_model_can_generate_images($model_id);
                        $class = $can_generate_images ? 'class="cg-model-supports-images"' : '';
                        echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_model, $model_id, false) . ' ' . $class . '>' . esc_html($model_name) . ($can_generate_images ? ' (Supporta immagini)' : '') . '</option>';
                    }
                    ?>
                </select>
                <button type="button" id="cg-refresh-models" class="button-secondary">
                    <span class="dashicons dashicons-update"></span> <?php _e('Aggiorna Modelli', 'curiosity-generator'); ?>
                </button>
            </div>
            <p class="description"><?php _e('Seleziona il modello LLM da utilizzare per generare curiosità. I modelli evidenziati supportano anche la generazione di immagini.', 'curiosity-generator'); ?></p>
            <div id="cg-model-loading" style="display:none;">
                <span class="spinner is-active"></span> <?php _e('Caricamento modelli...', 'curiosity-generator'); ?>
            </div>
        </td>
    </tr>
</table>

<h2><?php _e('Impostazioni Generazione Immagini', 'curiosity-generator'); ?></h2>
<p><?php _e('Configura come verranno generate le immagini in evidenza per le curiosità.', 'curiosity-generator'); ?></p>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="cg_image_generation_method"><?php _e('Metodo di Generazione', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <select name="cg_image_generation_method" id="cg_image_generation_method">
                <option value="ai_direct" <?php selected(get_option('cg_image_generation_method', 'ai_direct'), 'ai_direct'); ?>><?php _e('AI Diretta (DALL-E, DeepSeek, OpenRouter)', 'curiosity-generator'); ?></option>
                <option value="n8n" <?php selected(get_option('cg_image_generation_method', 'ai_direct'), 'n8n'); ?>><?php _e('n8n Workflow', 'curiosity-generator'); ?></option>
            </select>
            <p class="description"><?php _e('Scegli se generare immagini direttamente con l\'AI o utilizzare un workflow n8n.', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <!-- Opzioni per AI Diretta -->
    <tr class="cg-ai-direct-options">
        <th scope="row">
            <label for="cg_image_ai_model"><?php _e('Modello AI', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <select name="cg_image_ai_model" id="cg_image_ai_model">
                <option value="dalle3" <?php selected(get_option('cg_image_ai_model', 'dalle3'), 'dalle3'); ?>><?php _e('DALL-E 3', 'curiosity-generator'); ?></option>
                <option value="deepseek" <?php selected(get_option('cg_image_ai_model', 'dalle3'), 'deepseek'); ?>><?php _e('DeepSeek', 'curiosity-generator'); ?></option>
                <option value="openrouter" <?php selected(get_option('cg_image_ai_model', 'dalle3'), 'openrouter'); ?>><?php _e('OpenRouter (modello personalizzato)', 'curiosity-generator'); ?></option>
            </select>
            <p class="description"><?php _e('Seleziona il modello AI da utilizzare per generare immagini.', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <!-- Opzione modello OpenRouter specifico -->
    <tr class="cg-openrouter-model-option" style="display: none;">
        <th scope="row">
            <label for="cg_image_openrouter_model"><?php _e('Modello OpenRouter', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <select name="cg_image_openrouter_model" id="cg_image_openrouter_model">
                <?php
                $current_openrouter_model = get_option('cg_image_openrouter_model', 'openai/dall-e-3');
                $image_models = cg_get_image_capable_openrouter_models();
                
                foreach ($image_models as $model_id => $model_name) {
                    echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_openrouter_model, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                }
                ?>
            </select>
            <p class="description"><?php _e('Seleziona il modello specifico da utilizzare tramite OpenRouter per generare immagini.', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <!-- Opzioni per n8n -->
    <tr class="cg-n8n-options" style="display: none;">
        <th scope="row">
            <label for="cg_n8n_webhook_url"><?php _e('URL Webhook n8n', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <input type="text" name="cg_n8n_webhook_url" id="cg_n8n_webhook_url" class="regular-text" value="<?php echo esc_attr(get_option('cg_n8n_webhook_url', '')); ?>" />
            <p class="description"><?php _e('Inserisci l\'URL del webhook n8n che gestirà la generazione delle immagini.', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <tr class="cg-n8n-options" style="display: none;">
        <th scope="row">
            <label for="cg_n8n_api_token"><?php _e('Token API n8n', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <input type="password" name="cg_n8n_api_token" id="cg_n8n_api_token" class="regular-text" value="<?php echo esc_attr(get_option('cg_n8n_api_token', '')); ?>" />
            <p class="description"><?php _e('Token opzionale per l\'autenticazione con n8n (se il tuo workflow lo richiede).', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <!-- Opzioni avanzate per la generazione di immagini -->
    <tr>
        <th scope="row">
            <label for="cg_image_size"><?php _e('Dimensione Immagine', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <select name="cg_image_size" id="cg_image_size">
                <option value="1024x1024" <?php selected(get_option('cg_image_size', '1792x1024'), '1024x1024'); ?>><?php _e('1024x1024 (quadrata)', 'curiosity-generator'); ?></option>
                <option value="1792x1024" <?php selected(get_option('cg_image_size', '1792x1024'), '1792x1024'); ?>><?php _e('1792x1024 (16:9, orizzontale)', 'curiosity-generator'); ?></option>
                <option value="1024x1792" <?php selected(get_option('cg_image_size', '1792x1024'), '1024x1792'); ?>><?php _e('1024x1792 (9:16, verticale)', 'curiosity-generator'); ?></option>
            </select>
            <p class="description"><?php _e('Seleziona la dimensione delle immagini generate (non tutti i modelli supportano tutte le dimensioni).', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="cg_image_quality"><?php _e('Qualità Immagine', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <select name="cg_image_quality" id="cg_image_quality">
                <option value="standard" <?php selected(get_option('cg_image_quality', 'hd'), 'standard'); ?>><?php _e('Standard', 'curiosity-generator'); ?></option>
                <option value="hd" <?php selected(get_option('cg_image_quality', 'hd'), 'hd'); ?>><?php _e('Alta Definizione (HD)', 'curiosity-generator'); ?></option>
            </select>
            <p class="description"><?php _e('Seleziona la qualità delle immagini generate (non tutti i modelli supportano tutte le qualità).', 'curiosity-generator'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="cg_image_style"><?php _e('Stile Immagine', 'curiosity-generator'); ?></label>
        </th>
        <td>
            <select name="cg_image_style" id="cg_image_style">
                <option value="natural" <?php selected(get_option('cg_image_style', 'natural'), 'natural'); ?>><?php _e('Naturale/Realistico', 'curiosity-generator'); ?></option>
                <option value="vivid" <?php selected(get_option('cg_image_style', 'natural'), 'vivid'); ?>><?php _e('Vivido/Artistico', 'curiosity-generator'); ?></option>
            </select>
            <p class="description"><?php _e('Seleziona lo stile delle immagini generate (non tutti i modelli supportano tutti gli stili).', 'curiosity-generator'); ?></p>
        </td>
    </tr>
</table>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Gestione delle opzioni visibili in base al metodo di generazione
    function toggleGenerationOptions() {
        var method = $('#cg_image_generation_method').val();
        
        if (method === 'ai_direct') {
            $('.cg-ai-direct-options').show();
            $('.cg-n8n-options').hide();
            
            // Controlla se dobbiamo mostrare l'opzione del modello OpenRouter
            if ($('#cg_image_ai_model').val() === 'openrouter') {
                $('.cg-openrouter-model-option').show();
            } else {
                $('.cg-openrouter-model-option').hide();
            }
        } else {
            $('.cg-ai-direct-options').hide();
            $('.cg-openrouter-model-option').hide();
            $('.cg-n8n-options').show();
        }
    }
    
    // Esegui all'avvio
    toggleGenerationOptions();
    
    // Cambio del metodo di generazione
    $('#cg_image_generation_method').on('change', toggleGenerationOptions);
    
    // Cambio del modello AI
    $('#cg_image_ai_model').on('change', function() {
        if ($(this).val() === 'openrouter') {
            $('.cg-openrouter-model-option').show();
        } else {
            $('.cg-openrouter-model-option').hide();
        }
    });
});
</script>