<?php
/**
 * Classe per il logging e monitoraggio degli eventi cron di Curiosity Generator
 */
class CG_Cron_Monitor {
    
    /**
     * File di log
     */
    private $log_file;
    
    /**
     * Inizializza il monitor
     */
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/cg-cron-monitor.log';
        
        // Registra gli hook per il monitoraggio
        add_action('cg_scheduler_error', array($this, 'log_scheduler_error'), 10, 2);
        add_action('cg_generate_scheduled_curiosity', array($this, 'log_scheduled_execution'), 5, 1);
        
        // Registra un hook per verificare periodicamente gli eventi cron
        add_action('init', array($this, 'maybe_check_missed_events'), 30);
    }
    
    /**
     * Verifica se è necessario controllare gli eventi mancati
     * Lo eseguiamo solo una volta al giorno per non sovraccaricare il sito
     */
    public function maybe_check_missed_events() {
        $last_check = get_option('cg_cron_last_check', 0);
        $now = time();
        
        // Se sono passate più di 24 ore dall'ultima verifica
        if (($now - $last_check) > 86400) {
            $this->check_for_missed_events();
            update_option('cg_cron_last_check', $now);
        }
    }
    
    /**
     * Verifica se ci sono eventi mancati nelle ultime 48 ore
     */
    public function check_for_missed_events() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        // Ottieni eventi programmati negli ultimi 2 giorni
        $start_date = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $end_date = date('Y-m-d H:i:s');
        
        $schedules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE active = 1 
                AND scheduled_time BETWEEN %s AND %s
                ORDER BY scheduled_time ASC",
                $start_date,
                $end_date
            )
        );
        
        if (empty($schedules)) {
            $this->log("Nessun evento programmato trovato nelle ultime 48 ore.");
            return;
        }
        
        $missed_events = 0;
        $executed_events = 0;
        
        foreach ($schedules as $schedule) {
            if (!$schedule->last_run || $schedule->last_run < $schedule->scheduled_time) {
                // Evento mancato
                $missed_events++;
                $this->log("EVENTO MANCATO: ID {$schedule->id}, Titolo: {$schedule->title}, Programmato per: {$schedule->scheduled_time}");
                
                // Riprogramma l'evento per il giorno successivo alla stessa ora
                $time_parts = date('H:i:s', strtotime($schedule->scheduled_time));
                $next_date = date('Y-m-d', strtotime('+1 day')) . ' ' . $time_parts;
                
                $wpdb->update(
                    $table_name,
                    array(
                        'scheduled_time' => $next_date,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $schedule->id)
                );
                
                // Pianifica l'evento
                wp_schedule_single_event(strtotime($next_date), 'cg_generate_scheduled_curiosity', array($schedule->id));
                
                $this->log("Evento riprogrammato: ID {$schedule->id} per {$next_date}");
            } else {
                // Evento eseguito
                $executed_events++;
            }
        }
        
        $this->log("Riepilogo verifica cron: {$executed_events} eventi eseguiti, {$missed_events} eventi mancati e riprogrammati.");
    }
    
    /**
     * Registra l'esecuzione di un evento programmato
     */
    public function log_scheduled_execution($schedule_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $schedule = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $schedule_id));
        
        if ($schedule) {
            $this->log("INIZIO ESECUZIONE: ID {$schedule_id}, Titolo: {$schedule->title}, Parola chiave: {$schedule->keyword}");
        } else {
            $this->log("INIZIO ESECUZIONE: ID {$schedule_id}, Dettagli non disponibili");
        }
    }
    
    /**
     * Registra gli errori dello scheduler
     */
    public function log_scheduler_error($schedule_id, $error_message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $schedule = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $schedule_id));
        
        if ($schedule) {
            $this->log("ERRORE: ID {$schedule_id}, Titolo: {$schedule->title}, Errore: {$error_message}");
        } else {
            $this->log("ERRORE: ID {$schedule_id}, Errore: {$error_message}");
        }
    }
    
    /**
     * Scrive un messaggio nel file di log
     */
    public function log($message) {
        $timestamp = current_time('mysql');
        $log_message = "[$timestamp] $message\n";
        
        // Scrivi nel file di log
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }
    
    /**
     * Ottiene gli ultimi N eventi dal log
     */
    public function get_last_events($limit = 100) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $content = file_get_contents($this->log_file);
        $lines = explode("\n", $content);
        $lines = array_filter($lines);
        $lines = array_slice($lines, -$limit);
        
        return $lines;
    }
    
    /**
     * Pulisce il file di log
     */
    public function clear_log() {
        file_put_contents($this->log_file, '');
        $this->log("Log cancellato");
    }
    
    /**
     * Crea una pagina di amministrazione per visualizzare il log
     */
    public function add_admin_page() {
        add_submenu_page(
            'curiosity-generator-settings',
            'Monitor Cron',
            'Monitor Cron',
            'manage_options',
            'cg-cron-monitor',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Renderizza la pagina di amministrazione
     */
    public function render_admin_page() {
        // Gestisci le azioni
        if (isset($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);
            
            if ($action === 'clear_log' && check_admin_referer('cg_clear_log')) {
                $this->clear_log();
                echo '<div class="notice notice-success"><p>Log cancellato con successo.</p></div>';
            } elseif ($action === 'check_events' && check_admin_referer('cg_check_events')) {
                $this->check_for_missed_events();
                echo '<div class="notice notice-success"><p>Verifica eventi completata.</p></div>';
            } elseif ($action === 'fix_missing' && check_admin_referer('cg_fix_missing')) {
                require_once(plugin_dir_path(__FILE__) . '../includes/cg-cron-helper.php');
                $result = cg_run_due_cron_events();
                echo '<div class="notice notice-info"><p>Risultato: ' . $result . '</p></div>';
            }
        }
        
        // Ottieni gli ultimi eventi dal log
        $events = $this->get_last_events();
        ?>
        <div class="wrap">
            <h1>Monitor Cron Curiosity Generator</h1>
            
            <div class="cg-cron-actions">
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cg-cron-monitor&action=check_events'), 'cg_check_events'); ?>" class="button button-primary">Verifica Eventi Mancati</a>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cg-cron-monitor&action=fix_missing'), 'cg_fix_missing'); ?>" class="button">Esegui Eventi Scaduti</a>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cg-cron-monitor&action=clear_log'), 'cg_clear_log'); ?>" class="button">Cancella Log</a>
            </div>
            
            <h2>Diagnostica Cron</h2>
            
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Informazione</th>
                        <th>Valore</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>DISABLE_WP_CRON</td>
                        <td><?php echo defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Attivo (il cron di WordPress è disattivato)' : 'Non attivo'; ?></td>
                    </tr>
                    <tr>
                        <td>Cron URL</td>
                        <td><?php echo site_url('wp-cron.php'); ?></td>
                    </tr>
                    <tr>
                        <td>Ora server</td>
                        <td><?php echo current_time('mysql'); ?></td>
                    </tr>
                    <tr>
                        <td>Eventi cron totali</td>
                        <td>
                            <?php 
                            $cron = _get_cron_array();
                            $count = 0;
                            if ($cron) {
                                foreach ($cron as $timestamp => $hooks) {
                                    foreach ($hooks as $hook => $events) {
                                        $count += count($events);
                                    }
                                }
                            }
                            echo $count;
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Eventi curiosity_generator</td>
                        <td>
                            <?php 
                            $cg_count = 0;
                            if ($cron) {
                                foreach ($cron as $timestamp => $hooks) {
                                    if (isset($hooks['cg_generate_scheduled_curiosity'])) {
                                        $cg_count += count($hooks['cg_generate_scheduled_curiosity']);
                                    }
                                }
                            }
                            echo $cg_count;
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Prossimo evento cron</td>
                        <td>
                            <?php 
                            if ($cron) {
                                $timestamps = array_keys($cron);
                                if (!empty($timestamps)) {
                                    $next = $timestamps[0];
                                    echo date('Y-m-d H:i:s', $next) . ' (' . human_time_diff($next) . ')';
                                } else {
                                    echo 'Nessun evento programmato';
                                }
                            } else {
                                echo 'Nessun evento programmato';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h2>Eventi Programmati</h2>
            
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Programmato per</th>
                        <th>Argomenti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($cron) {
                        $found = false;
                        foreach ($cron as $timestamp => $hooks) {
                            foreach ($hooks as $hook => $events) {
                                if ($hook === 'cg_generate_scheduled_curiosity') {
                                    foreach ($events as $key => $event) {
                                        $found = true;
                                        echo '<tr>';
                                        echo '<td>' . esc_html($hook) . '</td>';
                                        echo '<td>' . date('Y-m-d H:i:s', $timestamp) . ' (' . human_time_diff($timestamp) . ')</td>';
                                        echo '<td>' . esc_html(json_encode($event['args'])) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            }
                        }
                        
                        if (!$found) {
                            echo '<tr><td colspan="3">Nessun evento Curiosity Generator programmato</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3">Nessun evento cron programmato</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <h2>Log Eventi</h2>
            
            <?php if (empty($events)): ?>
                <p>Nessun evento registrato nel log.</p>
            <?php else: ?>
                <div class="cg-log-container" style="background: #f0f0f0; padding: 10px; max-height: 400px; overflow-y: scroll; font-family: monospace;">
                    <?php foreach ($events as $event): ?>
                        <div class="cg-log-entry" style="margin-bottom: 5px; padding: 5px; border-bottom: 1px solid #ddd;">
                            <?php 
                            // Colora in base al tipo di evento
                            $style = 'color: #333;';
                            if (stripos($event, 'ERRORE') !== false) {
                                $style = 'color: #f44336; font-weight: bold;';
                            } elseif (stripos($event, 'MANCATO') !== false) {
                                $style = 'color: #ff9800; font-weight: bold;';
                            } elseif (stripos($event, 'INIZIO') !== false) {
                                $style = 'color: #4caf50;';
                            }
                            
                            echo '<span style="' . $style . '">' . esc_html($event) . '</span>';
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Inizializza il monitor solo quando necessario
if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) {
    $cg_cron_monitor = new CG_Cron_Monitor();
    
    // Aggiungi la pagina di amministrazione solo nel contesto admin
    if (is_admin()) {
        add_action('admin_menu', array($cg_cron_monitor, 'add_admin_page'), 20);
    }
}