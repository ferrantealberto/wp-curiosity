(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Gestione della navigazione a schede
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Aggiorna la scheda attiva
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Mostra il contenuto della scheda selezionata
            var target = $(this).attr('href');
            $('.tab-content').hide();
            $(target).show();
        });
        
        // Mostra la prima scheda di default
        $('.nav-tab:first').click();
        
        // Inizializza Select2 per il dropdown dei modelli
        $('.cg-select2-models').select2({
            width: '100%',
            placeholder: cg_admin_object.select_model_text,
            allowClear: true,
            templateResult: formatModelOption
        });
        
        // Formatta le opzioni del modello con evidenziazione della ricerca e supporto immagini
        function formatModelOption(option) {
            if (!option.id) {
                return option.text;
            }
            
            var $option = $(option.element);
            
            // Verifica se il modello supporta la generazione di immagini
            if ($option.hasClass('cg-model-supports-images')) {
                var $container = $('<div class="cg-model-supports-images"></div>');
                $container.text(option.text);
                return $container;
            }
            
            return $('<span>' + option.text + '</span>');
        }
        
        // Gestione del toggle della legenda
        $('#cg-toggle-legend').on('click', function() {
            var $content = $('#cg-models-legend-content');
            var $button = $(this);
            
            if ($content.is(':visible')) {
                $content.slideUp();
                $button.text('Mostra');
            } else {
                $content.slideDown();
                $button.text('Nascondi');
            }
        });
        
        // Pulsante per aggiornare i modelli
        $('#cg-refresh-models').on('click', function() {
            var $button = $(this);
            var $loading = $('#cg-model-loading');
            var $select = $('#cg_llm_model');
            var apiKey = $('#cg_openrouter_api_key').val();
            
            if (!apiKey) {
                alert(cg_admin_object.api_key_required_text);
                return;
            }
            
            $button.prop('disabled', true);
            $loading.show();
            
            $.ajax({
                url: cg_admin_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cg_refresh_models',
                    nonce: cg_admin_object.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        // Salva il modello selezionato corrente
                        var currentModel = $select.val();
                        
                        // Svuota e ripopola il select
                        $select.empty();
                        
                        $.each(response.data.models, function(id, name) {
                            // Verifica se il nome del modello contiene l'indicazione di supporto immagini
                            var supportsImages = name.includes('(Supporta immagini)');
                            
                            var $option = $('<option></option>')
                                .val(id)
                                .text(name);
                                
                            if (supportsImages) {
                                $option.addClass('cg-model-supports-images');
                            }
                                
                            if (id === currentModel) {
                                $option.prop('selected', true);
                            }
                            
                            $select.append($option);
                        });
                        
                        // Aggiorna il dropdown Select2
                        $select.trigger('change');
                        
                        alert(cg_admin_object.models_refreshed_text);
                    } else {
                        alert(cg_admin_object.error_text + (response.data.message || ''));
                    }
                },
                error: function() {
                    alert(cg_admin_object.error_text);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $loading.hide();
                }
            });
        });
    });
})(jQuery);