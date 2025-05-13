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
                    if (response.data.credits) {
                        $('.cg-credit-count').text(response.data.credits);
                    }
                    
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
        
        // Display the generated curiosities
        function displayResults(data) {
            var $list = $('#cg-curiosities-list');
            $list.empty();
            
            // Display each curiosity
            for (var i = 0; i < data.post_contents.length; i++) {
                var $item = $('<div class="cg-curiosity-item"></div>');
                var $title = $('<h4 class="cg-curiosity-title"></h4>').text(data.post_titles[i]);
                var $content = $('<div class="cg-curiosity-content"></div>').html(data.post_contents[i]);
                var $link = $('<a class="cg-curiosity-link" target="_blank"></a>')
                    .attr('href', data.post_urls[i])
                    .text('Visualizza Post Completo');
                
                $item.append($title, $content, $link);
                $list.append($item);
            }
            
            $('#cg-results').show();
        }
        
        // Display fullscreen ad
        function displayFullscreenAd(callback) {
            var adCode = cg_ajax_object.adsense_fullscreen_code || cg_ajax_object.adsense_demo_code;
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