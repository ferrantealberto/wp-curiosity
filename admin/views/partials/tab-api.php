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
                        echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_model, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                    }
                    ?>
                </select>
                <button type="button" id="cg-refresh-models" class="button-secondary">
                    <span class="dashicons dashicons-update"></span> <?php _e('Aggiorna Modelli', 'curiosity-generator'); ?>
                </button>
            </div>
            <p class="description"><?php _e('Seleziona il modello LLM da utilizzare per generare curiosità. I modelli di qualità superiore potrebbero costare più crediti su OpenRouter.', 'curiosity-generator'); ?></p>
            <div id="cg-model-loading" style="display:none;">
                <span class="spinner is-active"></span> <?php _e('Caricamento modelli...', 'curiosity-generator'); ?>
            </div>
        </td>
    </tr>
</table>
