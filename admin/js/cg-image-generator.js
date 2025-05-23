(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Inizializza Select2 per i modelli OpenRouter
        $('#cg_image_openrouter_model').select2({
            width: '100%',
            placeholder: 'Seleziona un modello per le immagini'
        });
        
        // Gestisci la visibilità delle opzioni in base al metodo di generazione
        function toggleGenerationMethod() {
            var method = $('#cg_image_generation_method').val();
            
            if (method === 'ai_direct') {
                $('.cg-ai-direct-options').show();
                $('.cg-n8n-options').hide();
                
                // Controlla anche quale modello AI è selezionato
                toggleAIModel();
            } else {
                $('.cg-ai-direct-options').hide();
                $('.cg-openrouter-model-option').hide();
                $('.cg-n8n-options').show();
            }
        }
        
        // Gestisci la visibilità delle opzioni in base al modello AI selezionato
        function toggleAIModel() {
            var model = $('#cg_image_ai_model').val();
            
            if (model === 'openrouter') {
                $('.cg-openrouter-model-option').show();
            } else {
                $('.cg-openrouter-model-option').hide();
            }
            
            // Aggiorna anche le opzioni avanzate in base al modello
            updateAdvancedOptions(model);
        }
        
        // Aggiorna le opzioni avanzate in base al modello selezionato
        function updateAdvancedOptions(model) {
            // Resetta tutte le opzioni a disponibili
            $('#cg_image_size option, #cg_image_quality option, #cg_image_style option').prop('disabled', false);
            
            switch (model) {
                case 'dalle3':
                    // DALL-E 3 supporta tutte le opzioni
                    break;
                    
                case 'deepseek':
                    // DeepSeek supporta solo formato quadrato e non ha opzioni di qualità
                    $('#cg_image_size option[value="1792x1024"]').prop('disabled', true);
                    $('#cg_image_size option[value="1024x1792"]').prop('disabled', true);
                    $('#cg_image_quality option[value="hd"]').prop('disabled', true);
                    $('#cg_image_style option[value="vivid"]').prop('disabled', true);
                    
                    // Se l'opzione corrente è disabilitata, seleziona un'opzione disponibile
                    if ($('#cg_image_size').val() !== '1024x1024') {
                        $('#cg_image_size').val('1024x1024');
                    }
                    if ($('#cg_image_quality').val() !== 'standard') {
                        $('#cg_image_quality').val('standard');
                    }
                    if ($('#cg_image_style').val() !== 'natural') {
                        $('#cg_image_style').val('natural');
                    }
                    break;
                    
                case 'openrouter':
                    // OpenRouter dipende dal modello specifico
                    var openrouterModel = $('#cg_image_openrouter_model').val();
                    
                    if (openrouterModel.includes('dall-e-3')) {
                        // DALL-E 3 supporta tutte le opzioni
                    } else if (openrouterModel.includes('midjourney')) {
                        // Midjourney ha limitazioni specifiche
                        $('#cg_image_quality option[value="hd"]').prop('disabled', true);
                        $('#cg_image_style option[value="vivid"]').prop('disabled', true);
                    } else if (openrouterModel.includes('stability')) {
                        // Stable Diffusion ha limitazioni specifiche
                        $('#cg_image_size option[value="1792x1024"]').prop('disabled', true);
                        $('#cg_image_size option[value="1024x1792"]').prop('disabled', true);
                    }
                    break;
            }
        }
        
        // Eventi per cambiamenti nelle selezioni
        $('#cg_image_generation_method').on('change', toggleGenerationMethod);
        $('#cg_image_ai_model').on('change', toggleAIModel);
        $('#cg_image_openrouter_model').on('change', function() {
            updateAdvancedOptions('openrouter');
        });
        
        // Inizializza la visibilità delle opzioni
        toggleGenerationMethod();
        
        // Gestisci il click sui metodi di generazione tramite le opzioni visive
        $('.cg-image-method-option').on('click', function() {
            var method = $(this).data('method');
            $('#cg_image_generation_method').val(method).trigger('change');
            
            // Aggiorna la selezione visiva
            $('.cg-image-method-option').removeClass('selected');
            $(this).addClass('selected');
        });
        
        // Gestisci il click sui modelli AI tramite le opzioni visive
        $('.cg-model-option').on('click', function() {
            var model = $(this).data('model');
            $('#cg_image_ai_model').val(model).trigger('change');
            
            // Aggiorna la selezione visiva
            $('.cg-model-option').removeClass('selected');
            $(this).addClass('selected');
        });
        
        // Gestisci la visualizzazione degli esempi di immagine
        $('.cg-show-examples').on('click', function(e) {
            e.preventDefault();
            $('.cg-image-examples').slideToggle();
        });
        
        // Gestisci il pulsante test webhook n8n
        $('#cg-test-n8n-webhook').on('click', function() {
            var webhookUrl = $('#cg_n8n_webhook_url').val();
            
            if (!webhookUrl) {
                alert('Inserisci un URL webhook valido prima di testare.');
                return;
            }
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Test in corso...');
            
            $.ajax({
                url: cg_admin_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cg_test_n8n_webhook',
                    nonce: cg_admin_object.nonce,
                    webhook_url: webhookUrl,
                    api_token: $('#cg_n8n_api_token').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test riuscito! Il webhook n8n è accessibile e ha risposto correttamente.');
                    } else {
                        alert('Errore nel test del webhook: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function() {
                    alert('Errore di connessione durante il test del webhook.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Inizializza gli esempi visivi del metodo e modello selezionati
        function initVisualSelections() {
            var method = $('#cg_image_generation_method').val();
            var model = $('#cg_image_ai_model').val();
            
            // Seleziona il metodo visivamente
            $('.cg-image-method-option[data-method="' + method + '"]').addClass('selected');
            
            // Seleziona il modello visivamente
            $('.cg-model-option[data-model="' + model + '"]').addClass('selected');
        }
        
        // Esegui l'inizializzazione
        initVisualSelections();
    });
})(jQuery);