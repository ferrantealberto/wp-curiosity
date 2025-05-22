<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Inizializza il Post Manager per ottenere statistiche
$post_manager = new CG_Post_Manager();
$counts = $post_manager->get_posts_count_by_status();
$image_stats = $post_manager->get_posts_featured_image_stats();
?>

<div class="wrap">
    <h1><?php _e('Gestione Post Curiosità', 'curiosity-generator'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Gestisci tutti i post di curiosità generati dal plugin. Puoi filtrare, cercare e modificare lo stato dei post in batch.', 'curiosity-generator'); ?></p>
    </div>
    
    <!-- Statistiche -->
    <div class="cg-posts-stats">
        <div class="cg-stats-grid">
            <div class="cg-stat-item">
                <span class="cg-stat-number"><?php echo $counts['total']; ?></span>
                <span class="cg-stat-label"><?php _e('Totale Post', 'curiosity-generator'); ?></span>
            </div>
            <div class="cg-stat-item">
                <span class="cg-stat-number"><?php echo $counts['publish']; ?></span>
                <span class="cg-stat-label"><?php _e('Pubblicati', 'curiosity-generator'); ?></span>
            </div>
            <div class="cg-stat-item">
                <span class="cg-stat-number"><?php echo $counts['private']; ?></span>
                <span class="cg-stat-label"><?php _e('Privati', 'curiosity-generator'); ?></span>
            </div>
            <div class="cg-stat-item">
                <span class="cg-stat-number"><?php echo $counts['draft']; ?></span>
                <span class="cg-stat-label"><?php _e('Bozze', 'curiosity-generator'); ?></span>
            </div>
            <div class="cg-stat-item">
                <span class="cg-stat-number"><?php echo $image_stats['with_image']; ?></span>
                <span class="cg-stat-label"><?php _e('Con Immagine', 'curiosity-generator'); ?></span>
            </div>
            <div class="cg-stat-item">
                <span class="cg-stat-number"><?php echo $image_stats['without_image']; ?></span>
                <span class="cg-stat-label"><?php _e('Senza Immagine', 'curiosity-generator'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Filtri e ricerca -->
    <div class="cg-posts-filters">
        <div class="cg-filter-row">
            <div class="cg-filter-group">
                <label for="cg-search-posts"><?php _e('Cerca:', 'curiosity-generator'); ?></label>
                <input type="text" id="cg-search-posts" placeholder="<?php _e('Titolo, parola chiave, autore...', 'curiosity-generator'); ?>">
            </div>
            
            <div class="cg-filter-group">
                <label for="cg-filter-status"><?php _e('Stato:', 'curiosity-generator'); ?></label>
                <select id="cg-filter-status">
                    <option value="any"><?php _e('Tutti gli stati', 'curiosity-generator'); ?></option>
                    <option value="publish"><?php _e('Pubblicati', 'curiosity-generator'); ?></option>
                    <option value="private"><?php _e('Privati', 'curiosity-generator'); ?></option>
                    <option value="draft"><?php _e('Bozze', 'curiosity-generator'); ?></option>
                    <option value="pending"><?php _e('In attesa di revisione', 'curiosity-generator'); ?></option>
                    <option value="trash"><?php _e('Cestino', 'curiosity-generator'); ?></option>
                </select>
            </div>
            
            <div class="cg-filter-group">
                <label for="cg-filter-featured-image"><?php _e('Immagine in Evidenza:', 'curiosity-generator'); ?></label>
                <select id="cg-filter-featured-image">
                    <option value="any"><?php _e('Tutti', 'curiosity-generator'); ?></option>
                    <option value="with_image"><?php _e('Con immagine', 'curiosity-generator'); ?></option>
                    <option value="without_image"><?php _e('Senza immagine', 'curiosity-generator'); ?></option>
                </select>
            </div>
            
            <div class="cg-filter-group">
                <label for="cg-posts-per-page"><?php _e('Post per pagina:', 'curiosity-generator'); ?></label>
                <select id="cg-posts-per-page">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            
            <div class="cg-filter-group">
                <button type="button" id="cg-filter-posts" class="button"><?php _e('Filtra', 'curiosity-generator'); ?></button>
                <button type="button" id="cg-reset-filters" class="button"><?php _e('Reset', 'curiosity-generator'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Azioni bulk -->
    <div class="cg-bulk-actions">
        <div class="cg-bulk-row">
            <div class="cg-bulk-group">
                <select id="cg-bulk-action">
                    <option value=""><?php _e('Seleziona azione', 'curiosity-generator'); ?></option>
                    <option value="publish"><?php _e('Pubblica', 'curiosity-generator'); ?></option>
                    <option value="private"><?php _e('Rendi privato', 'curiosity-generator'); ?></option>
                    <option value="draft"><?php _e('Converti in bozza', 'curiosity-generator'); ?></option>
                    <option value="pending"><?php _e('Metti in attesa di revisione', 'curiosity-generator'); ?></option>
                    <option value="trash"><?php _e('Sposta nel cestino', 'curiosity-generator'); ?></option>
                    <option value="delete"><?php _e('Elimina definitivamente', 'curiosity-generator'); ?></option>
                    <option value="generate_featured_image"><?php _e('Genera Immagine in Evidenza', 'curiosity-generator'); ?></option>
                </select>
                <button type="button" id="cg-apply-bulk-action" class="button"><?php _e('Applica', 'curiosity-generator'); ?></button>
            </div>
            
            <div class="cg-bulk-group">
                <button type="button" id="cg-select-all-posts" class="button"><?php _e('Seleziona tutti', 'curiosity-generator'); ?></button>
                <button type="button" id="cg-deselect-all-posts" class="button"><?php _e('Deseleziona tutti', 'curiosity-generator'); ?></button>
                <button type="button" id="cg-select-no-image-posts" class="button button-primary"><?php _e('Seleziona senza immagine', 'curiosity-generator'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Loader -->
    <div id="cg-posts-loading" class="cg-loading" style="display:none;">
        <div class="cg-spinner"></div>
        <p><?php _e('Caricamento post...', 'curiosity-generator'); ?></p>
    </div>
    
    <!-- Messaggi -->
    <div id="cg-posts-message" class="notice" style="display:none;">
        <p></p>
    </div>
    
    <!-- Progress bar per generazione immagini di massa -->
    <div id="cg-image-generation-progress" class="cg-progress-container" style="display:none;">
        <h3><?php _e('Generazione Immagini in Corso...', 'curiosity-generator'); ?></h3>
        <div class="cg-progress-bar">
            <div class="cg-progress-fill" style="width: 0%;"></div>
        </div>
        <div class="cg-progress-text">
            <span id="cg-progress-current">0</span> di <span id="cg-progress-total">0</span> completate
        </div>
        <div id="cg-progress-status"></div>
        <button type="button" id="cg-stop-generation" class="button button-secondary"><?php _e('Interrompi', 'curiosity-generator'); ?></button>
    </div>
    
    <!-- Tabella dei post -->
    <div class="cg-posts-table-container">
        <table class="wp-list-table widefat fixed striped cg-posts-table">
            <thead>
                <tr>
                    <th scope="col" class="check-column">
                        <input type="checkbox" id="cg-select-all-checkbox">
                    </th>
                    <th scope="col" class="cg-status-column"><?php _e('Stato', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-title-column"><?php _e('Titolo', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-author-column"><?php _e('Autore', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-keyword-column"><?php _e('Parola Chiave', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-type-column"><?php _e('Tipo', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-language-column"><?php _e('Lingua', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-image-column"><?php _e('Immagine', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-date-column"><?php _e('Data', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-views-column"><?php _e('Visualizzazioni', 'curiosity-generator'); ?></th>
                    <th scope="col" class="cg-actions-column"><?php _e('Azioni', 'curiosity-generator'); ?></th>
                </tr>
            </thead>
            <tbody id="cg-posts-tbody">
                <!-- I post verranno caricati dinamicamente qui -->
            </tbody>
        </table>
    </div>
    
    <!-- Paginazione -->
    <div class="cg-pagination" id="cg-pagination">
        <!-- La paginazione verrà generata dinamicamente -->
    </div>
    
    <!-- Template per riga della tabella -->
    <script type="text/template" id="cg-post-row-template">
        <tr data-post-id="{{ID}}" class="{{no_image_class}}">
            <th scope="row" class="check-column">
                <input type="checkbox" class="cg-post-checkbox" value="{{ID}}">
            </th>
            <td class="cg-status-column">
                <span class="cg-status-badge cg-status-{{status}}">{{status_label}}</span>
            </td>
            <td class="cg-title-column">
                <strong>{{title}}</strong>
            </td>
            <td class="cg-author-column">{{author}}</td>
            <td class="cg-keyword-column">{{keyword}}</td>
            <td class="cg-type-column">{{type_label}}</td>
            <td class="cg-language-column">{{language}}</td>
            <td class="cg-image-column">
                <span class="cg-image-status cg-image-{{has_image_class}}">
                    {{image_status_text}}
                </span>
                {{#unless_has_image}}
                <button type="button" class="button button-small cg-generate-single-image" data-post-id="{{ID}}" title="<?php _e('Genera Immagine in Evidenza', 'curiosity-generator'); ?>">
                    <span class="dashicons dashicons-format-image"></span>
                </button>
                {{/unless_has_image}}
            </td>
            <td class="cg-date-column">{{date_formatted}}</td>
            <td class="cg-views-column">{{view_count}}</td>
            <td class="cg-actions-column">
                <div class="cg-row-actions">
                    <a href="{{edit_link}}" target="_blank"><?php _e('Modifica', 'curiosity-generator'); ?></a> |
                    <a href="{{view_link}}" target="_blank"><?php _e('Visualizza', 'curiosity-generator'); ?></a>
                </div>
            </td>
        </tr>
    </script>
</div>