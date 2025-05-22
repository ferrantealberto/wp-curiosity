(function($) {
    'use strict';
    
    var postsTable = {
        currentPage: 1,
        totalPages: 1,
        loading: false,
        generationQueue: [],
        currentGeneration: 0,
        stopGeneration: false,
        
        init: function() {
            this.bindEvents();
            this.loadPosts();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Filtri
            $('#cg-filter-posts').on('click', function() {
                self.currentPage = 1;
                self.loadPosts();
            });
            
            $('#cg-reset-filters').on('click', function() {
                $('#cg-search-posts').val('');
                $('#cg-filter-status').val('any');
                $('#cg-filter-featured-image').val('any');
                $('#cg-posts-per-page').val('20');
                self.currentPage = 1;
                self.loadPosts();
            });
            
            // Ricerca con debounce
            var searchTimeout;
            $('#cg-search-posts').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.currentPage = 1;
                    self.loadPosts();
                }, 500);
            });
            
            // Cambio di stato o per page o filtro immagine
            $('#cg-filter-status, #cg-posts-per-page, #cg-filter-featured-image').on('change', function() {
                self.currentPage = 1;
                self.loadPosts();
            });
            
            // Selezione tutti/nessuno
            $('#cg-select-all-checkbox').on('change', function() {
                $('.cg-post-checkbox').prop('checked', $(this).is(':checked'));
            });
            
            $('#cg-select-all-posts').on('click', function() {
                $('.cg-post-checkbox').prop('checked', true);
                $('#cg-select-all-checkbox').prop('checked', true);
            });
            
            $('#cg-deselect-all-posts').on('click', function() {
                $('.cg-post-checkbox').prop('checked', false);
                $('#cg-select-all-checkbox').prop('checked', false);
            });
            
            // NUOVO: Seleziona solo post senza immagine
            $('#cg-select-no-image-posts').on('click', function() {
                $('.cg-post-checkbox').prop('checked', false);
                $('.cg-no-featured-image .cg-post-checkbox').prop('checked', true);
                self.updateSelectAllCheckbox();
            });
            
            // Azioni bulk
            $('#cg-apply-bulk-action').on('click', function() {
                self.applyBulkAction();
            });
            
            // NUOVO: Generazione singola immagine
            $(document).on('click', '.cg-generate-single-image', function() {
                var postId = $(this).data('post-id');
                self.generateSingleImage(postId, $(this));
            });
            
            // NUOVO: Stop generazione
            $('#cg-stop-generation').on('click', function() {
                self.stopGeneration = true;
                $(this).prop('disabled', true).text('Interruzione...');
            });
            
            // Delegated event per checkbox individuali
            $(document).on('change', '.cg-post-checkbox', function() {
                self.updateSelectAllCheckbox();
            });
            
            // Delegated event per paginazione
            $(document).on('click', '.cg-pagination-link', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                if (page && page !== self.currentPage) {
                    self.currentPage = page;
                    self.loadPosts();
                }
            });
        },
        
        updateSelectAllCheckbox: function() {
            var allChecked = $('.cg-post-checkbox').length === $('.cg-post-checkbox:checked').length;
            $('#cg-select-all-checkbox').prop('checked', allChecked);
        },
        
        loadPosts: function() {
            if (this.loading) return;
            
            var self = this;
            this.loading = true;
            
            $('#cg-posts-loading').show();
            $('#cg-posts-message').hide();
            
            var data = {
                action: 'cg_load_posts',
                nonce: cg_admin_object.nonce,
                search: $('#cg-search-posts').val(),
                status: $('#cg-filter-status').val(),
                featured_image: $('#cg-filter-featured-image').val(),
                per_page: $('#cg-posts-per-page').val(),
                page: this.currentPage,
                orderby: 'date',
                order: 'DESC'
            };
            
            $.ajax({
                url: cg_admin_object.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.renderPosts(response.data.posts);
                        self.totalPages = response.data.pages;
                        self.renderPagination();
                    } else {
                        self.showMessage(response.data.message || 'Errore nel caricamento dei post.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Errore di connessione durante il caricamento dei post.', 'error');
                },
                complete: function() {
                    $('#cg-posts-loading').hide();
                    self.loading = false;
                }
            });
        },
        
        renderPosts: function(posts) {
            var $tbody = $('#cg-posts-tbody');
            var template = $('#cg-post-row-template').html();
            
            $tbody.empty();
            $('#cg-select-all-checkbox').prop('checked', false);
            
            if (posts.length === 0) {
                $tbody.append('<tr><td colspan="11" style="text-align: center; padding: 20px;">' + 
                             'Nessun post trovato con i filtri correnti.' + '</td></tr>');
                return;
            }
            
            $.each(posts, function(index, post) {
                var row = template;
                
                // Sostituzioni dei placeholder
                row = row.replace(/\{\{ID\}\}/g, post.ID);
                row = row.replace(/\{\{title\}\}/g, this.escapeHtml(post.title));
                row = row.replace(/\{\{status\}\}/g, post.status);
                row = row.replace(/\{\{status_label\}\}/g, this.getStatusLabel(post.status));
                row = row.replace(/\{\{author\}\}/g, this.escapeHtml(post.author));
                row = row.replace(/\{\{keyword\}\}/g, this.escapeHtml(post.keyword || ''));
                row = row.replace(/\{\{type_label\}\}/g, this.getTypeLabel(post.type));
                row = row.replace(/\{\{language\}\}/g, this.escapeHtml(post.language || ''));
                row = row.replace(/\{\{date_formatted\}\}/g, this.formatDate(post.date));
                row = row.replace(/\{\{view_count\}\}/g, post.view_count || '0');
                row = row.replace(/\{\{edit_link\}\}/g, post.edit_link);
                row = row.replace(/\{\{view_link\}\}/g, post.view_link);
                
                // Gestione immagine in evidenza
                var hasImage = post.has_featured_image;
                var imageStatusClass = hasImage ? 'yes' : 'no';
                var imageStatusText = hasImage ? '✓' : '✗';
                var noImageClass = hasImage ? '' : 'cg-no-featured-image';
                
                row = row.replace(/\{\{has_image_class\}\}/g, imageStatusClass);
                row = row.replace(/\{\{image_status_text\}\}/g, imageStatusText);
                row = row.replace(/\{\{no_image_class\}\}/g, noImageClass);
                
                // Condizionale per il pulsante di generazione immagine
                if (hasImage) {
                    row = row.replace(/\{\{#unless_has_image\}\}.*?\{\{\/unless_has_image\}\}/gs, '');
                } else {
                    row = row.replace(/\{\{#unless_has_image\}\}/g, '');
                    row = row.replace(/\{\{\/unless_has_image\}\}/g, '');
                }
                
                $tbody.append(row);
            }.bind(this));
        },
        
        renderPagination: function() {
            var $pagination = $('#cg-pagination');
            $pagination.empty();
            
            if (this.totalPages <= 1) return;
            
            var html = '<div class="tablenav-pages">';
            html += '<span class="displaying-num">' + 'Pagina ' + this.currentPage + ' di ' + this.totalPages + '</span>';
            
            // Prima pagina
            if (this.currentPage > 1) {
                html += '<a class="cg-pagination-link button" data-page="1">&laquo; Prima</a>';
                html += '<a class="cg-pagination-link button" data-page="' + (this.currentPage - 1) + '">&lsaquo; Precedente</a>';
            }
            
            // Pagine numerate
            var start = Math.max(1, this.currentPage - 2);
            var end = Math.min(this.totalPages, this.currentPage + 2);
            
            for (var i = start; i <= end; i++) {
                if (i === this.currentPage) {
                    html += '<span class="button button-primary" style="cursor: default;">' + i + '</span>';
                } else {
                    html += '<a class="cg-pagination-link button" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            // Ultima pagina
            if (this.currentPage < this.totalPages) {
                html += '<a class="cg-pagination-link button" data-page="' + (this.currentPage + 1) + '">Successiva &rsaquo;</a>';
                html += '<a class="cg-pagination-link button" data-page="' + this.totalPages + '">Ultima &raquo;</a>';
            }
            
            html += '</div>';
            $pagination.html(html);
        },
        
        applyBulkAction: function() {
            var action = $('#cg-bulk-action').val();
            var selectedPosts = $('.cg-post-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action) {
                alert('Seleziona un\'azione da eseguire.');
                return;
            }
            
            if (selectedPosts.length === 0) {
                alert(cg_admin_object.no_posts_selected_text);
                return;
            }
            
            // NUOVO: Gestione generazione immagini di massa
            if (action === 'generate_featured_image') {
                this.startBulkImageGeneration(selectedPosts);
                return;
            }
            
            // Conferma per azioni distruttive
            if (action === 'delete') {
                if (!confirm(cg_admin_object.confirm_delete_text)) {
                    return;
                }
            }
            
            var self = this;
            $('#cg-posts-loading').show();
            
            $.ajax({
                url: cg_admin_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cg_bulk_posts_action',
                    nonce: cg_admin_object.nonce,
                    bulk_action: action,
                    post_ids: selectedPosts
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        self.loadPosts(); // Ricarica la tabella
                        $('#cg-bulk-action').val(''); // Reset azione
                    } else {
                        self.showMessage(response.data.message || 'Errore nell\'esecuzione dell\'azione.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Errore di connessione durante l\'esecuzione dell\'azione.', 'error');
                },
                complete: function() {
                    $('#cg-posts-loading').hide();
                }
            });
        },
        
        // NUOVO: Generazione singola immagine
        generateSingleImage: function(postId, $button) {
            var self = this;
            
            $button.prop('disabled', true)
                   .html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
            
            $.ajax({
                url: cg_admin_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cg_generate_featured_image',
                    nonce: cg_admin_object.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').removeClass('cg-no-featured-image');
                        $button.closest('.cg-image-column')
                               .find('.cg-image-status')
                               .removeClass('cg-image-no')
                               .addClass('cg-image-yes')
                               .text('✓');
                        $button.remove();
                        self.showMessage('Immagine generata con successo!', 'success');
                    } else {
                        $button.prop('disabled', false)
                               .html('<span class="dashicons dashicons-format-image"></span>');
                        self.showMessage(response.data.message || 'Errore nella generazione dell\'immagine.', 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false)
                           .html('<span class="dashicons dashicons-format-image"></span>');
                    self.showMessage('Errore di connessione durante la generazione dell\'immagine.', 'error');
                }
            });
        },
        
        // NUOVO: Generazione di massa immagini
        startBulkImageGeneration: function(postIds) {
            var self = this;
            
            // Filtra solo i post che non hanno già un'immagine
            var postsWithoutImage = [];
            postIds.forEach(function(postId) {
                var $row = $('tr[data-post-id="' + postId + '"]');
                if ($row.hasClass('cg-no-featured-image')) {
                    postsWithoutImage.push(postId);
                }
            });
            
            if (postsWithoutImage.length === 0) {
                alert('Tutti i post selezionati hanno già un\'immagine in evidenza.');
                return;
            }
            
            if (!confirm('Generare immagini in evidenza per ' + postsWithoutImage.length + ' post? Questa operazione potrebbe richiedere del tempo.')) {
                return;
            }
            
            this.generationQueue = postsWithoutImage;
            this.currentGeneration = 0;
            this.stopGeneration = false;
            
            // Mostra progress bar
            $('#cg-image-generation-progress').show();
            $('#cg-progress-total').text(this.generationQueue.length);
            $('#cg-progress-current').text(0);
            $('.cg-progress-fill').css('width', '0%');
            $('#cg-stop-generation').prop('disabled', false).text('Interrompi');
            
            this.processNextImageGeneration();
        },
        
        // NUOVO: Processa la prossima generazione nella coda
        processNextImageGeneration: function() {
            var self = this;
            
            if (this.stopGeneration || this.currentGeneration >= this.generationQueue.length) {
                this.finishBulkGeneration();
                return;
            }
            
            var postId = this.generationQueue[this.currentGeneration];
            var $row = $('tr[data-post-id="' + postId + '"]');
            var postTitle = $row.find('.cg-title-column strong').text();
            
            $('#cg-progress-status').text('Generando immagine per: ' + postTitle);
            
            $.ajax({
                url: cg_admin_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cg_generate_featured_image',
                    nonce: cg_admin_object.nonce,
                    post_id: postId
                },
                success: function(response) {
                    self.currentGeneration++;
                    
                    if (response.success) {
                        // Aggiorna la riga della tabella
                        $row.removeClass('cg-no-featured-image');
                        $row.find('.cg-image-status')
                            .removeClass('cg-image-no')
                            .addClass('cg-image-yes')
                            .text('✓');
                        $row.find('.cg-generate-single-image').remove();
                    }
                    
                    // Aggiorna progress bar
                    var progress = (self.currentGeneration / self.generationQueue.length) * 100;
                    $('.cg-progress-fill').css('width', progress + '%');
                    $('#cg-progress-current').text(self.currentGeneration);
                    
                    // Processa il prossimo dopo un breve delay
                    setTimeout(function() {
                        self.processNextImageGeneration();
                    }, 1000);
                },
                error: function() {
                    self.currentGeneration++;
                    
                    // Aggiorna progress bar anche in caso di errore
                    var progress = (self.currentGeneration / self.generationQueue.length) * 100;
                    $('.cg-progress-fill').css('width', progress + '%');
                    $('#cg-progress-current').text(self.currentGeneration);
                    
                    // Continua con il prossimo
                    setTimeout(function() {
                        self.processNextImageGeneration();
                    }, 1000);
                }
            });
        },
        
        // NUOVO: Termina generazione di massa
        finishBulkGeneration: function() {
            $('#cg-image-generation-progress').hide();
            
            if (this.stopGeneration) {
                this.showMessage('Generazione interrotta. ' + this.currentGeneration + ' di ' + this.generationQueue.length + ' immagini generate.', 'warning');
            } else {
                this.showMessage('Generazione completata! ' + this.currentGeneration + ' immagini generate.', 'success');
            }
            
            // Reset
            this.generationQueue = [];
            this.currentGeneration = 0;
            this.stopGeneration = false;
            
            // Ricarica la tabella
            this.loadPosts();
        },
        
        showMessage: function(message, type) {
            var $message = $('#cg-posts-message');
            $message.removeClass('notice-success notice-error notice-warning notice-info')
                   .addClass('notice-' + type)
                   .find('p').text(message);
            $message.show();
            
            // Auto-hide dopo 5 secondi
            setTimeout(function() {
                $message.fadeOut();
            }, 5000);
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        },
        
        getStatusLabel: function(status) {
            var labels = {
                'publish': 'Pubblicato',
                'private': 'Privato',
                'draft': 'Bozza',
                'pending': 'In attesa',
                'trash': 'Cestino'
            };
            return labels[status] || status;
        },
        
        getTypeLabel: function(type) {
            var types = {
                'historical-facts': 'Fatti Storici',
                'science-nature': 'Scienza e Natura',
                'technology': 'Tecnologia',
                'art-culture': 'Arte e Cultura',
                'geography': 'Geografia',
                'famous-people': 'Personaggi Famosi',
                'mysteries': 'Misteri',
                'statistics': 'Statistiche Incredibili',
                'word-origins': 'Origine delle Parole',
                'traditions': 'Tradizioni Bizzarre'
            };
            return types[type] || type || '';
        },
        
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString('it-IT') + ' ' + date.toLocaleTimeString('it-IT', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
    
    // Inizializza quando il documento è pronto
    $(document).ready(function() {
        // Verifica che siamo nella pagina corretta
        if ($('#cg-posts-tbody').length) {
            postsTable.init();
        }
    });
    
})(jQuery);