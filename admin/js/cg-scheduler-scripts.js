(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Gestione della navigazione a schede
        $('.cg-scheduler-container .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Aggiorna la scheda attiva
            $('.cg-scheduler-container .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Mostra il contenuto della scheda selezionata
            var target = $(this).attr('href');
            $('.cg-scheduler-container .tab-content').hide();
            $(target).show();
            
            // Aggiorna l'hash nell'URL
            window.location.hash = target;
        });
        
        // Controlla se c'è un hash nell'URL e mostra la scheda corrispondente
        var hash = window.location.hash;
        if (hash && $(hash).length) {
            $('.cg-scheduler-container .nav-tab[href="' + hash + '"]').click();
        } else {
            // Mostra la prima scheda di default
            $('.cg-scheduler-container .nav-tab:first').click();
        }
        
        // Conferma eliminazione
        $('.cg-delete-btn').on('click', function(e) {
            if (!confirm(cg_scheduler_object.confirm_delete_text)) {
                e.preventDefault();
            }
        });
        
        // Gestione checkbox per opzioni casuali
        $('#cg_use_random').on('change', function() {
            var $select_fields = $('#cg_type, #cg_language, #cg_param4, #cg_param5, #cg_param6');
            
            if ($(this).is(':checked')) {
                $select_fields.closest('tr').find('.description').html(cg_scheduler_object.random_option_text);
            } else {
                $('#cg_type').closest('tr').find('.description').html(cg_scheduler_object.type_description_text);
                $('#cg_language').closest('tr').find('.description').html(cg_scheduler_object.language_description_text);
                $('#cg_param4').closest('tr').find('.description').html(cg_scheduler_object.tone_description_text);
                $('#cg_param5').closest('tr').find('.description').html(cg_scheduler_object.source_description_text);
                $('#cg_param6').closest('tr').find('.description').html(cg_scheduler_object.audience_description_text);
            }
        });
        
        // Trigger change to initialize
        $('#cg_use_random').trigger('change');
        
        // Set minimum date/time for scheduler
        var now = new Date();
        var year = now.getFullYear();
        var month = (now.getMonth() + 1).toString().padStart(2, '0');
        var day = now.getDate().toString().padStart(2, '0');
        var hours = now.getHours().toString().padStart(2, '0');
        var minutes = now.getMinutes().toString().padStart(2, '0');
        
        var minDateTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        $('#cg_scheduled_time').attr('min', minDateTime);
        
        // If no datetime is set, set it to current time + 1 hour
        if (!$('#cg_scheduled_time').val()) {
            now.setHours(now.getHours() + 1);
            year = now.getFullYear();
            month = (now.getMonth() + 1).toString().padStart(2, '0');
            day = now.getDate().toString().padStart(2, '0');
            hours = now.getHours().toString().padStart(2, '0');
            minutes = now.getMinutes().toString().padStart(2, '0');
            
            var defaultDateTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            $('#cg_scheduled_time').val(defaultDateTime);
        }
    });
})(jQuery);