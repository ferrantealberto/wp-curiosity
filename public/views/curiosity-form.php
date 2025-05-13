<div id="curiosity-generator-form" class="cg-container">
    <!-- Header Ad - sempre visibile -->
    <div class="cg-adsense-header">
        <?php
        $header_ad = get_option('cg_adsense_header_code', '');
        $disable_demo_ads = get_option('cg_disable_demo_ads', 0);
        echo !empty($header_ad) ? $header_ad : ($disable_demo_ads ? '' : '<div class="cg-demo-ad">ANNUNCIO DEMO<br>Questo è un annuncio demo per test</div>');
        ?>
    </div>
    
    <h2><?php echo esc_html($atts['title']); ?></h2>
    <p class="cg-description"><?php echo esc_html($atts['description']); ?></p>
    
    <?php if ($user_id): ?>
    <div class="cg-user-credits">
        <?php 
        $credits_class = new CG_Credits();
        $generation_credits = $credits_class->get_user_generation_credits($user_id);
        $view_credits = $credits_class->get_user_view_credits($user_id);
        ?>
        <div class="cg-credits-row">
            <?php echo sprintf(__('Crediti di Generazione: %s', 'curiosity-generator'), '<span class="cg-generation-credit-count">' . cg_format_credits($generation_credits) . '</span>'); ?>
        </div>
        <div class="cg-credits-row">
            <?php echo sprintf(__('Crediti di Visualizzazione: %s', 'curiosity-generator'), '<span class="cg-view-credit-count">' . cg_format_credits($view_credits) . '</span>'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Inline Ad Top - sempre visibile -->
    <div id="cg-inline-ad-top" class="cg-inline-ad">
        <?php
        $inline_ad = get_option('cg_adsense_inline_code', '');
        echo !empty($inline_ad) ? $inline_ad : ($disable_demo_ads ? '' : '<div class="cg-demo-ad">ANNUNCIO DEMO<br>Questo è un annuncio demo per test</div>');
        ?>
    </div>
    
    <form id="cg-form">
        <div class="cg-form-group">
            <label for="cg-keyword"><?php _e('Parola Chiave o Tema', 'curiosity-generator'); ?> <span class="required">*</span></label>
            <input type="text" id="cg-keyword" name="keyword" required placeholder="<?php _e('es., Spazio, Dinosauri, Rinascimento', 'curiosity-generator'); ?>">
        </div>
        
        <div class="cg-form-group">
            <label for="cg-type"><?php _e('Tipo di Curiosità', 'curiosity-generator'); ?> <span class="required">*</span></label>
            <select id="cg-type" name="type" required>
                <option value=""><?php _e('Seleziona un tipo', 'curiosity-generator'); ?></option>
                <?php
                $types = cg_get_default_types();
                foreach ($types as $type_id => $type_name) {
                    echo '<option value="' . esc_attr($type_id) . '">' . esc_html($type_name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="cg-form-group">
            <label for="cg-language"><?php _e('Lingua', 'curiosity-generator'); ?> <span class="required">*</span></label>
            <select id="cg-language" name="language" required>
                <?php
                $languages = cg_get_available_languages();
                foreach ($languages as $lang_id => $lang_name) {
                    $selected = ($lang_id === 'italiano') ? 'selected' : '';
                    echo '<option value="' . esc_attr($lang_id) . '" ' . $selected . '>' . esc_html($lang_name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="cg-form-group">
            <label for="cg-period"><?php _e('Periodo Temporale (Opzionale)', 'curiosity-generator'); ?></label>
            <input type="text" id="cg-period" name="period" placeholder="<?php _e('es., XIX Secolo, Anni \'80, Medioevo', 'curiosity-generator'); ?>">
        </div>
        
        <div class="cg-form-toggles">
            <button type="button" id="cg-show-advanced" class="cg-toggle-button"><?php _e('Mostra Opzioni Avanzate', 'curiosity-generator'); ?></button>
        </div>
        
        <div id="cg-advanced-options" class="cg-advanced-container" style="display:none">
            <div class="cg-form-group">
                <label for="cg-param1"><?php _e('Luogo o Contesto Geografico (Opzionale)', 'curiosity-generator'); ?></label>
                <input type="text" id="cg-param1" name="param1" placeholder="<?php _e('es., Italia, Foresta Amazzonica, Aree urbane', 'curiosity-generator'); ?>">
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param2"><?php _e('Persona o Gruppo Specifico (Opzionale)', 'curiosity-generator'); ?></label>
                <input type="text" id="cg-param2" name="param2" placeholder="<?php _e('es., Einstein, Popoli indigeni, Astronomi', 'curiosity-generator'); ?>">
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param3"><?php _e('Aspetto o Focus (Opzionale)', 'curiosity-generator'); ?></label>
                <input type="text" id="cg-param3" name="param3" placeholder="<?php _e('es., Impatto culturale, Record, Innovazioni', 'curiosity-generator'); ?>">
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param4"><?php _e('Tono (Opzionale)', 'curiosity-generator'); ?></label>
                <select id="cg-param4" name="param4">
                    <option value=""><?php _e('Seleziona un tono', 'curiosity-generator'); ?></option>
                    <option value="Humorous"><?php _e('Umoristico', 'curiosity-generator'); ?></option>
                    <option value="Serious"><?php _e('Serio', 'curiosity-generator'); ?></option>
                    <option value="Surprising"><?php _e('Sorprendente', 'curiosity-generator'); ?></option>
                    <option value="Little-known"><?php _e('Poco conosciuto', 'curiosity-generator'); ?></option>
                </select>
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param5"><?php _e('Tipo di Fonte (Opzionale)', 'curiosity-generator'); ?></label>
                <select id="cg-param5" name="param5">
                    <option value=""><?php _e('Seleziona un tipo di fonte', 'curiosity-generator'); ?></option>
                    <option value="Scientific studies"><?php _e('Studi scientifici', 'curiosity-generator'); ?></option>
                    <option value="Historical records"><?php _e('Documenti storici', 'curiosity-generator'); ?></option>
                    <option value="Popular legends"><?php _e('Leggende popolari', 'curiosity-generator'); ?></option>
                    <option value="Recent discoveries"><?php _e('Scoperte recenti', 'curiosity-generator'); ?></option>
                </select>
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param6"><?php _e('Pubblico di Riferimento (Opzionale)', 'curiosity-generator'); ?></label>
                <select id="cg-param6" name="param6">
                    <option value=""><?php _e('Seleziona un pubblico', 'curiosity-generator'); ?></option>
                    <option value="General public"><?php _e('Pubblico generale', 'curiosity-generator'); ?></option>
                    <option value="Children"><?php _e('Bambini', 'curiosity-generator'); ?></option>
                    <option value="Subject experts"><?php _e('Esperti del settore', 'curiosity-generator'); ?></option>
                    <option value="Enthusiasts"><?php _e('Appassionati', 'curiosity-generator'); ?></option>
                </select>
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param7"><?php _e('Parametro Personalizzato 1 (Opzionale)', 'curiosity-generator'); ?></label>
                <input type="text" id="cg-param7" name="param7" placeholder="<?php _e('Valore del parametro personalizzato', 'curiosity-generator'); ?>">
            </div>
            
            <div class="cg-form-group">
                <label for="cg-param8"><?php _e('Parametro Personalizzato 2 (Opzionale)', 'curiosity-generator'); ?></label>
                <input type="text" id="cg-param8" name="param8" placeholder="<?php _e('Valore del parametro personalizzato', 'curiosity-generator'); ?>">
            </div>
        </div>
        
        <div class="cg-form-group">
            <label for="cg-count"><?php _e('Numero di Curiosità', 'curiosity-generator'); ?></label>
            <input type="number" id="cg-count" name="count" min="1" max="<?php echo esc_attr(get_option('cg_max_curiosities', 5)); ?>" value="1">
            <p class="cg-help-text"><?php echo sprintf(__('Genera fino a %d curiosità alla volta.', 'curiosity-generator'), get_option('cg_max_curiosities', 5)); ?></p>
        </div>
        
        <div class="cg-form-actions">
            <button type="submit" id="cg-generate-button" class="cg-button-primary"><?php _e('Genera Curiosità', 'curiosity-generator'); ?></button>
        </div>
    </form>
    
    <div id="cg-loading" class="cg-loading" style="display:none">
        <div class="cg-spinner"></div>
        <p><?php _e('Generazione di curiosità in corso...', 'curiosity-generator'); ?></p>
    </div>
    
    <div id="cg-results" class="cg-results" style="display:none">
        <h3><?php _e('Curiosità Generate', 'curiosity-generator'); ?></h3>
        <div id="cg-curiosities-list"></div>
        <button id="cg-generate-more" class="cg-button-secondary"><?php _e('Genera Altre', 'curiosity-generator'); ?></button>
    </div>
    
    <div id="cg-fullscreen-ad-container" class="cg-fullscreen-ad-container" style="display:none">
        <div class="cg-fullscreen-ad-wrapper">
            <div class="cg-fullscreen-ad-close">&times;</div>
            <div id="cg-fullscreen-ad-content" class="cg-fullscreen-ad-content"></div>
        </div>
    </div>
    
    <div id="cg-error" class="cg-error" style="display:none"></div>
    
    <!-- Inline Ad Bottom - sempre visibile -->
    <div id="cg-inline-ad-bottom" class="cg-inline-ad">
        <?php
        echo !empty($inline_ad) ? $inline_ad : ($disable_demo_ads ? '' : '<div class="cg-demo-ad">ANNUNCIO DEMO<br>Questo è un annuncio demo per test</div>');
        ?>
    </div>
    
    <!-- Footer Ad - sempre visibile -->
    <div class="cg-adsense-footer">
        <?php
        $footer_ad = get_option('cg_adsense_footer_code', '');
        echo !empty($footer_ad) ? $footer_ad : ($disable_demo_ads ? '' : '<div class="cg-demo-ad">ANNUNCIO DEMO<br>Questo è un annuncio demo per test</div>');
        ?>
    </div>
</div>