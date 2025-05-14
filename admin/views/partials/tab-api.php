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
            
            <div class="cg-models-legend">
                <div class="cg-legend-header">
                    <h4><?php _e('Legenda Modelli', 'curiosity-generator'); ?></h4>
                    <button type="button" class="cg-legend-toggle" id="cg-toggle-legend"><?php _e('Nascondi', 'curiosity-generator'); ?></button>
                </div>
                
                <div class="cg-models-legend-container" id="cg-models-legend-content">
                    <!-- Modelli per generazione di testo -->
                    <div class="cg-legend-section">
                        <h5><?php _e('Modelli per Generazione di Testo:', 'curiosity-generator'); ?></h5>
                        
                        <div class="cg-legend-subsection">
                            <p><strong><?php _e('Piano Gratuito / Economico:', 'curiosity-generator'); ?></strong></p>
                            <div class="cg-legend-models">
                                <?php
                                $legend_data = cg_get_models_legend_data();
                                foreach ($legend_data['text']['free'] as $model_id => $description) {
                                    echo '<div class="cg-legend-model-item cg-legend-free">';
                                    echo '<span class="cg-legend-model-name">' . esc_html($model_id) . '</span>';
                                    echo '<span class="cg-legend-model-desc">' . esc_html($description) . '</span>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="cg-legend-subsection">
                            <p><strong><?php _e('Piano Premium:', 'curiosity-generator'); ?></strong></p>
                            <div class="cg-legend-models">
                                <?php
                                foreach ($legend_data['text']['premium'] as $model_id => $description) {
                                    echo '<div class="cg-legend-model-item cg-legend-premium">';
                                    echo '<span class="cg-legend-model-name">' . esc_html($model_id) . '</span>';
                                    echo '<span class="cg-legend-model-desc">' . esc_html($description) . '</span>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modelli per generazione di immagini -->
                    <div class="cg-legend-section">
                        <h5><?php _e('Modelli per Generazione di Immagini:', 'curiosity-generator'); ?></h5>
                        
                        <div class="cg-legend-subsection">
                            <p><strong><?php _e('Piano Gratuito / Economico:', 'curiosity-generator'); ?></strong></p>
                            <div class="cg-legend-models">
                                <?php
                                foreach ($legend_data['image']['free'] as $model_id => $description) {
                                    echo '<div class="cg-legend-model-item cg-legend-free">';
                                    echo '<span class="cg-legend-model-name"><span class="cg-supports-images-icon dashicons dashicons-format-image"></span>' . esc_html($model_id) . '</span>';
                                    echo '<span class="cg-legend-model-desc">' . esc_html($description) . '</span>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="cg-legend-subsection">
                            <p><strong><?php _e('Piano Premium:', 'curiosity-generator'); ?></strong></p>
                            <div class="cg-legend-models">
                                <?php
                                foreach ($legend_data['image']['premium'] as $model_id => $description) {
                                    echo '<div class="cg-legend-model-item cg-legend-premium">';
                                    echo '<span class="cg-legend-model-name"><span class="cg-supports-images-icon dashicons dashicons-format-image"></span>' . esc_html($model_id) . '</span>';
                                    echo '<span class="cg-legend-model-desc">' . esc_html($description) . '</span>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cg-legend-section">
                        <p class="description">
                            <?php _e('Nota: Per generare immagini, devi selezionare un modello contrassegnato con "Supporta immagini" o uno dei modelli elencati nella sezione "Modelli per Generazione di Immagini" di questa legenda.', 'curiosity-generator'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </td>
    </tr>
</table>