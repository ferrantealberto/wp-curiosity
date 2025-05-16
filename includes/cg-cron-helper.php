<?php
/**
 * Funzioni di supporto per il sistema di cron di WordPress
 * Questo file può essere incluso nel file functions.php del tema o in un plugin di supporto
 */

/**
 * Disabilita il WordPress cron predefinito e configura un vero cron job di sistema
 * Aggiungi questa definizione al file wp-config.php:
 * define('DISABLE_WP_CRON', true);
 */

/**
 * Forza manualmente l'esecuzione di un evento cron programmato
 * Utilizzare questa funzione per debug se un evento cron non viene eseguito
 */
function cg_force_run_scheduled_event($hook, $args = array()) {
    // Verifica se l'evento è programmato
    $timestamp = wp_next_scheduled($hook, $args);
    
    if ($timestamp) {
        // Registra informazioni di debug
        error_log("Evento cron forzato: {$hook}");
        error_log("Argomenti: " . json_encode($args));
        error_log("Timestamp programmato: " . date('Y-m-d H:i:s', $timestamp));
        error_log("Timestamp attuale: " . date('Y-m-d H:i:s', current_time('timestamp')));
        
        // Esegui l'azione
        do_action_ref_array($hook, $args);
        
        return true;
    } else {
        error_log("Evento cron non trovato: {$hook}");
        return false;
    }
}

/**
 * Debug e visualizzazione di tutti gli eventi cron programmati
 */
function cg_view_cron_events() {
    $cron_jobs = _get_cron_array();
    $output = array();
    
    if (empty($cron_jobs)) {
        return 'Nessun evento cron programmato.';
    }
    
    foreach ($cron_jobs as $timestamp => $cron_job) {
        foreach ($cron_job as $hook => $events) {
            foreach ($events as $key => $event) {
                $output[] = array(
                    'hook' => $hook,
                    'schedule' => $event['schedule'] ? $event['schedule'] : 'once',
                    'timestamp' => $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'args' => $event['args'] ? json_encode($event['args']) : 'nessun argomento'
                );
            }
        }
    }
    
    return $output;
}

/**
 * Funzione per controllare se il sistema cron di WordPress funziona correttamente
 */
function cg_is_wp_cron_working() {
    $wp_cron_test = get_transient('wp_cron_test_time');
    
    if (false === $wp_cron_test) {
        set_transient('wp_cron_test_time', time(), 60 * 5); // 5 minuti
        wp_schedule_single_event(time() + 60, 'wp_cron_test');
        return 'Test cron programmato. Controlla nuovamente tra 2 minuti.';
    }
    
    $elapsed = time() - $wp_cron_test;
    
    if ($elapsed > 300) { // 5 minuti
        delete_transient('wp_cron_test_time');
        return 'Il cron di WordPress potrebbe non funzionare correttamente. L\'ultimo test è stato eseguito ' . round($elapsed / 60) . ' minuti fa.';
    }
    
    return 'Il cron di WordPress sta funzionando correttamente. Ultimo test: ' . date('Y-m-d H:i:s', $wp_cron_test);
}

/**
 * Callback per il test del cron
 */
function cg_wp_cron_test_callback() {
    update_option('wp_cron_test_result', array(
        'time' => time(),
        'date' => date('Y-m-d H:i:s')
    ));
}
add_action('wp_cron_test', 'cg_wp_cron_test_callback');

/**
 * Pulisce tutti gli eventi cron programmati di un hook specifico
 */
function cg_clear_scheduled_hook($hook) {
    $count = 0;
    $cron_jobs = _get_cron_array();
    
    if (empty($cron_jobs)) {
        return 'Nessun evento cron trovato.';
    }
    
    foreach ($cron_jobs as $timestamp => $cron_job) {
        if (isset($cron_job[$hook])) {
            foreach ($cron_job[$hook] as $key => $event) {
                wp_unschedule_event($timestamp, $hook, $event['args']);
                $count++;
            }
        }
    }
    
    return "Rimossi {$count} eventi cron per l'hook '{$hook}'.";
}

/**
 * Esegui manualmente tutti gli eventi cron programmati per un hook specifico
 */
function cg_run_all_scheduled_hooks($hook) {
    $count = 0;
    $cron_jobs = _get_cron_array();
    
    if (empty($cron_jobs)) {
        return 'Nessun evento cron trovato.';
    }
    
    foreach ($cron_jobs as $timestamp => $cron_job) {
        if (isset($cron_job[$hook])) {
            foreach ($cron_job[$hook] as $key => $event) {
                do_action_ref_array($hook, $event['args']);
                $count++;
            }
        }
    }
    
    return "Eseguiti {$count} eventi cron per l'hook '{$hook}'.";
}

/**
 * Esegue manualmente tutti gli eventi cron scaduti
 */
function cg_run_due_cron_events() {
    $cron_jobs = _get_cron_array();
    $now = time();
    $executed = 0;
    
    if (empty($cron_jobs)) {
        return 'Nessun evento cron trovato.';
    }
    
    foreach ($cron_jobs as $timestamp => $cron_job) {
        if ($timestamp <= $now) {
            foreach ($cron_job as $hook => $events) {
                foreach ($events as $key => $event) {
                    do_action_ref_array($hook, $event['args']);
                    $executed++;
                }
            }
        }
    }
    
    return "Eseguiti {$executed} eventi cron scaduti.";
}

// Esempio di utilizzo per il debugging:
// add_action('admin_init', function() {
//     if (isset($_GET['debug_cron']) && current_user_can('manage_options')) {
//         echo '<pre>';
//         print_r(cg_view_cron_events());
//         echo '</pre>';
//         die();
//     }
// });