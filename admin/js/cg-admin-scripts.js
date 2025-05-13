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
            var classes = [];
            
            // Verifica se il modello supporta la generazione di immagini
            if ($option.hasClass('cg-model-supports-images')) {
                classes.push('cg-model-supports-images');
            }
            
            // Verifica se il modello è gratuito
            if ($option.hasClass('cg-model-free')) {
                classes.push('cg-model-free');
            }
            
            // Verifica se il modello è di alta qualità
            if ($option.hasClass('cg-model-high-quality')) {
                classes.push('cg-model-high-quality');
            }
            
            if (classes.length > 0) {
                var $container = $('<div class="' + classes.join(' ') + '"></div>');
                $container.text(option.text);
                return $container;
            }
            
            return $('<span>' + option.text + '</span>');
        }
        
        // Gestione pulsanti filtro modelli
        $('.cg-filter-button').on('click', function() {
            var filter = $(this).data('filter');
            
            // Aggiorna stato attivo dei pulsanti
            $('.cg-filter-button').removeClass('active');
            $(this).addClass('active');
            
            // Applica filtro alle opzioni di select
            filterModels(filter);
        });
        
        // Funzione per filtrare i modelli in base al criterio selezionato
        function filterModels(filter) {
            var $select = $('#cg_llm_model');
            var currentValue = $select.val();
            
            // Ottieni tutte le opzioni e mostra/nascondi in base al filtro
            var $options = $select.find('option');
            
            // Rimuovi tutte le opzioni dal select
            $select.empty();
            
            // Filtra le opzioni
            $options.each(function() {
                var $option = $(this);
                var show = true;
                
                // Applica i filtri
                switch(filter) {
                    case 'free':
                        show = $option.data('free') === 1;
                        break;
                    case 'images':
                        show = $option.data('images') === 1;
                        break;
                    case 'best':
                        show = $option.data('quality') >= 4;
                        break;
                    case 'all':
                    default:
                        show = true;
                        break;
                }
                
                // Se l'opzione passa il filtro, aggiungila di nuovo
                if (show) {
                    $select.append($option);
                }
            });
            
            // Ripristina valore selezionato se esiste tra le opzioni filtrate
            if ($select.find('option[value="' + currentValue + '"]').length) {
                $select.val(currentValue);
            }
            
            // Aggiorna Select2
            $select.trigger('change');
        }
        
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
                        
                        $.each(response.data.models, function(id, modelData) {
                            var classes = [];
                            var dataAttrs = '';
                            
                            // Verifica se il modello supporta la generazione di immagini
                            if (modelData.supports_images) {
                                classes.push('cg-model-supports-images');
                                dataAttrs += ' data-images="1"';
                            } else {
                                dataAttrs += ' data-images="0"';
                            }
                            
                            // Verifica se il modello è gratuito
                            if (modelData.is_free) {
                                classes.push('cg-model-free');
                                dataAttrs += ' data-free="1"';
                            } else {
                                dataAttrs += ' data-free="0"';
                            }
                            
                            // Verifica se il modello è di alta qualità
                            if (modelData.quality_rating >= 4) {
                                classes.push('cg-model-high-quality');
                            }
                            
                            dataAttrs += ' data-quality="' + modelData.quality_rating + '"';
                            
                            var classAttr = classes.length > 0 ? ' class="' + classes.join(' ') + '"' : '';
                            var selected = id === currentModel ? ' selected' : '';
                            
                            var $option = $('<option value="' + id + '"' + classAttr + dataAttrs + selected + '>' + modelData.name + '</option>');
                            $select.append($option);
                        });
                        
                        // Aggiorna il dropdown Select2
                        $select.trigger('change');
                        
                        // Riapplica il filtro attivo
                        var activeFilter = $('.cg-filter-button.active').data('filter') || 'all';
                        filterModels(activeFilter);
                        
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
        
        // Attiva il filtro "tutti" come default
        $('.cg-filter-button[data-filter="all"]').addClass('active');
    });
})(jQuery);