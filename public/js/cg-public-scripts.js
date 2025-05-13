(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Toggle advanced options
        $('#cg-show-advanced').on('click', function() {
            var $advanced = $('#cg-advanced-options');
            var $button = $(this);
            
            if ($advanced.is(':visible')) {
                $advanced.slideUp();
                $button.text(cg_ajax_object.show_advanced_text);
            } else {
                $advanced.slideDown();
                $button.text(cg_ajax_object.hide_advanced_text);
            }
        });
        
        // Handle form submission
        $('#cg-form').on('submit', function(e) {
            e.preventDefault();
            
            // Show fullscreen ad before starting generation
            displayFullscreenAd(function() {
                // Start the generation process after closing the ad
                startGeneration();
            });
        });
        
        // Handle "Generate More" button
        $('#cg-generate-more').on('click', function() {
            // Show fullscreen ad between generations
            displayFullscreenAd(function() {
                // Hide results and scroll to form
                $('#cg-results').slideUp();
                $('html, body').animate({
                    scrollTop: $('#curiosity-generator-form').offset().top - 50
                }, 500);
            });
        });
        
        // Close fullscreen ad
        $(document).on('click', '.cg-fullscreen-ad-close', function() {
            $('#cg-fullscreen-ad-container').fadeOut();
        });
        
        // Gestione click sul pulsante "Genera Immagine in Evidenza"
        $(document).on('click', '.cg-generate-image-btn', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            
            // Disabilita il pulsante e mostra il testo di caricamento
            $button.prop('disabled', true).text('Generazione in corso...');
            
            // Invia la richiesta AJAX
            $.ajax({
                url: cg_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_featured_image',
                    nonce: cg_ajax_object.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Mostra un'anteprima dell'immagine
                        var $imagePreview = $('<div class="cg-image-preview"></div>');
                        var $image = $('<img>').attr('src', response.data.image_url).attr('alt', 'Immagine in evidenza');
                        $imagePreview.append($image);
                        $button.after($imagePreview);
                        
                        // Aggiorna il testo del pulsante e lo rende non cliccabile
                        $button.text('Immagine generata!').addClass('cg-image-generated');
                    } else {
                        // Mostra l'errore e riabilita il pulsante
                        alert(response.data || 'Errore durante la generazione dell\'immagine.');
                        $button.prop('disabled', false).text('Genera Immagine in Evidenza');
                    }
                },
                error: function() {
                    // Gestisci l'errore e riabilita il pulsante
                    alert('Errore di connessione. Riprova più tardi.');
                    $button.prop('disabled', false).text('Genera Immagine in Evidenza');
                }
            });
        });
        
        // Gestione click sul pulsante "Copia Link"
        $(document).on('click', '.cg-copy-link-btn', function() {
            var link = $(this).data('link');
            
            // Creazione di un elemento temporaneo per copiare il link
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(link).select();
            document.execCommand('copy');
            tempInput.remove();
            
            // Mostra messaggio di conferma
            var $successMessage = $('<div class="cg-copy-success">Link copiato negli appunti!</div>');
            $('body').append($successMessage);
            
            // Mostra il messaggio
            setTimeout(function() {
                $successMessage.addClass('show');
            }, 10);
            
            // Rimuovi il messaggio dopo 2 secondi
            setTimeout(function() {
                $successMessage.removeClass('show');
                setTimeout(function() {
                    $successMessage.remove();
                }, 300);
            }, 2000);
        });
        
        // Function to start the generation process
        function startGeneration() {
            // Show loading
            $('#cg-results').hide();
            $('#cg-error').hide();
            $('#cg-loading').show();
            
            // Get form data
            var formData = {
                action: 'generate_curiosity',
                nonce: cg_ajax_object.nonce,
                keyword: $('#cg-keyword').val(),
                type: $('#cg-type').val(),
                language: $('#cg-language').val(),
                period: $('#cg-period').val(),
                count: $('#cg-count').val(),
                param1: $('#cg-param1').val(),
                param2: $('#cg-param2').val(),
                param3: $('#cg-param3').val(),
                param4: $('#cg-param4').val(),
                param5: $('#cg-param5').val(),
                param6: $('#cg-param6').val(),
                param7: $('#cg-param7').val(),
                param8: $('#cg-param8').val()
            };
            
            // Send AJAX request
            $.post(cg_ajax_object.ajax_url, formData, function(response) {
                $('#cg-loading').hide();
                
                if (response.success) {
                    displayResults(response.data);
                    
                    // Update user credits if available
                    updateCredits(response.data);
                    
                    // Show fullscreen ad after generation
                    displayFullscreenAd();
                } else {
                    $('#cg-error')
                        .text(response.data)
                        .show();
                }
            }).fail(function() {
                $('#cg-loading').hide();
                $('#cg-error')
                    .text(cg_ajax_object.error_text)
                    .show();
            });
        }
        
        // Update user credits
        function updateCredits(data) {
            if (data.generation_credits !== undefined) {
                $('.cg-generation-credit-count').text(data.generation_credits);
            }
            
            if (data.view_credits !== undefined) {
                $('.cg-view-credit-count').text(data.view_credits);
            }
        }
        
        // Display the generated curiosities
        function displayResults(data) {
            var $list = $('#cg-curiosities-list');
            $list.empty();
            
            // Display each curiosity
            for (var i = 0; i < data.post_contents.length; i++) {
                var $item = $('<div class="cg-curiosity-item"></div>');
                var $title = $('<h4 class="cg-curiosity-title"></h4>').text(data.post_titles[i]);
                var $content = $('<div class="cg-curiosity-content"></div>').html(data.post_contents[i]);
                
                // Container per i pulsanti di azione
                var $postActions = $('<div class="cg-post-actions"></div>');
                
                // Link al post completo
                var $link = $('<a class="cg-curiosity-link" target="_blank"></a>')
                    .attr('href', data.post_urls[i])
                    .text('Visualizza Post Completo');
                
                // Pulsante Copia Link
                var $copyLinkBtn = $('<button class="cg-copy-link-btn" data-link="' + data.post_urls[i] + '"></button>')
                    .html('<i class="dashicons dashicons-admin-links"></i> Copia Link');
                
                // Aggiungi sempre link e copia link
                $postActions.append($link, $copyLinkBtn);
                
                // Mostra il pulsante "Genera Immagine in Evidenza" solo se il modello supporta immagini
                if (data.can_generate_images && data.can_generate_images[i]) {
                    var $generateImageBtn = $('<button class="cg-generate-image-btn" data-post-id="' + data.post_ids[i] + '"></button>')
                        .text('Genera Immagine in Evidenza');
                    $postActions.append($generateImageBtn);
                }
                
                // Create social sharing buttons
                var $socialShare = $('<div class="cg-social-share"></div>');
                
                // Facebook
                var facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(data.post_urls[i]);
                var $facebookBtn = $('<a class="cg-social-button facebook" target="_blank"></a>')
                    .attr('href', facebookUrl)
                    .html('<i class="dashicons dashicons-facebook"></i> Facebook');
                
                // Twitter
                var tweetText = encodeURIComponent(data.post_titles[i]);
                var twitterUrl = 'https://twitter.com/intent/tweet?text=' + tweetText + '&url=' + encodeURIComponent(data.post_urls[i]);
                var $twitterBtn = $('<a class="cg-social-button twitter" target="_blank"></a>')
                    .attr('href', twitterUrl)
                    .html('<i class="dashicons dashicons-twitter"></i> Twitter');
                
                // LinkedIn
                var linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(data.post_urls[i]);
                var $linkedinBtn = $('<a class="cg-social-button linkedin" target="_blank"></a>')
                    .attr('href', linkedinUrl)
                    .html('<i class="dashicons dashicons-linkedin"></i> LinkedIn');
                
                // Pinterest
                var pinterestUrl = 'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(data.post_urls[i]) + '&description=' + encodeURIComponent(data.post_titles[i]);
                var $pinterestBtn = $('<a class="cg-social-button pinterest" target="_blank"></a>')
                    .attr('href', pinterestUrl)
                    .html('<i class="dashicons dashicons-pinterest"></i> Pinterest');
                
                // WhatsApp
                var whatsappText = encodeURIComponent(data.post_titles[i] + ' - ' + data.post_urls[i]);
                var whatsappUrl = 'https://wa.me/?text=' + whatsappText;
                var $whatsappBtn = $('<a class="cg-social-button whatsapp" target="_blank"></a>')
                    .attr('href', whatsappUrl)
                    .html('<i class="dashicons dashicons-whatsapp"></i> WhatsApp');
                
                // Add all buttons to social share container
                $socialShare.append($facebookBtn, $twitterBtn, $linkedinBtn, $pinterestBtn, $whatsappBtn);
                
                // Aggiungi tutto all'item della curiosità
                $item.append($title, $content, $postActions, $socialShare);
                $list.append($item);
            }
            
            $('#cg-results').show();
        }
        
        // Display fullscreen ad
        function displayFullscreenAd(callback) {
            var adCode = cg_ajax_object.adsense_fullscreen_code || cg_ajax_object.adsense_demo_fullscreen_code;
            if (adCode) {
                $('#cg-fullscreen-ad-content').html(adCode);
                $('#cg-fullscreen-ad-container').fadeIn();
                
                // Set up the close button event
                $('.cg-fullscreen-ad-close').one('click', function() {
                    $('#cg-fullscreen-ad-container').fadeOut();
                    if (typeof callback === 'function') {
                        callback();
                    }
                });
            } else if (typeof callback === 'function') {
                // If no ad code is available, just call the callback
                callback();
            }
        }
    });
})(jQuery);