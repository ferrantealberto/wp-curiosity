<?php
/**
 * File di supporto per debug e risoluzione problemi del scheduler
 * 
 * Questo file può essere caricato nella cartella principale del plugin
 * e accessibile tramite https://tuosito.com/wp-content/plugins/curiosity-generator/cg-scheduler-fix.php
 */

// Esci se chiamato direttamente senza parametri di autenticazione
if (!isset($_GET['fix_key']) || $_GET['fix_key'] !== 'segreto_scheduler_2025') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Accesso non autorizzato';
    exit;
}

// Carica WordPress
require_once('../../../../wp-load.php');

// Verifica se l'utente è un amministratore
if (!current_user_can('manage_options')) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Accesso non autorizzato';
    exit;
}

// Includi le funzioni di supporto per cron
require_once('includes/cg-cron-helper.php');

// Funzione per testare una programmazione specifica
function cg_test_schedule($schedule_id) {
    if (!$schedule_id) {
        return 'ID programmazione non valido';
    }
    
    // Includi le classi necessarie
    require_once('includes/class-cg-scheduler.php');
    require_once('includes/class-cg-openrouter.php');
    require_once('includes/class-cg-post-manager.php');
    
    // Crea un'istanza dello scheduler
    $scheduler = new CG_Scheduler();
    
    // Ottieni i dettagli della programmazione
    global $wpdb;
    $table_name = $wpdb->prefix . 'cg_schedules';
    $schedule = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $schedule_id));
    
    if (!$schedule) {
        return 'Programmazione non trovata';
    }
    
    // Prova ad eseguire la programmazione forzatamente
    try {
        // Esegui direttamente la funzione di generazione
        $result = $scheduler->generate_scheduled_curiosity($schedule_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Programmazione eseguita con successo',
                'post_ids' => $result
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Errore nell\'esecuzione della programmazione',
                'schedule' => $schedule
            );
        }
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Eccezione: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        );
    }
}

// Funzione per reimpostare e correggere tutte le programmazioni
function cg_fix_all_schedules() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cg_schedules';
    
    // Ottieni tutte le programmazioni attive
    $schedules = $wpdb->get_results("SELECT * FROM {$table_name} WHERE active = 1");
    
    if (empty($schedules)) {
        return 'Nessuna programmazione attiva trovata';
    }
    
    $results = array();
    $scheduler = new CG_Scheduler();
    
    foreach ($schedules as $schedule) {
        // Cancella eventuali job pianificati
        wp_clear_scheduled_hook('cg_generate_scheduled_curiosity', array($schedule->id));
        
        // Pianifica nuovamente
        $timestamp = strtotime($schedule->scheduled_time);
        
        // Se la data programmata è nel passato, riprogramma per domani alla stessa ora
        if ($timestamp <= time()) {
            // Calcola lo stesso orario di domani
            $parts = explode(' ', $schedule->scheduled_time);
            $time_parts = isset($parts[1]) ? $parts[1] : '00:00:00';
            $tomorrow = date('Y-m-d', strtotime('+1 day')) . ' ' . $time_parts;
            $timestamp = strtotime($tomorrow);
            
            // Aggiorna il database
            $wpdb->update(
                $table_name,
                array(
                    'scheduled_time' => $tomorrow,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $schedule->id)
            );
        }
        
        // Pianifica l'evento
        $scheduled = wp_schedule_single_event($timestamp, 'cg_generate_scheduled_curiosity', array($schedule->id));
        
        $results[] = array(
            'id' => $schedule->id,
            'title' => $schedule->title,
            'old_time' => $schedule->scheduled_time,
            'new_time' => date('Y-m-d H:i:s', $timestamp),
            'success' => $scheduled ? true : false
        );
    }
    
    return $results;
}

// Output come JSON per maggiore leggibilità
header('Content-Type: application/json');

// Esegui azioni in base ai parametri
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'debug':
        // Mostra informazioni sul cron di WordPress
        echo json_encode(cg_view_cron_events(), JSON_PRETTY_PRINT);
        break;
        
    case 'test_cron':
        // Verifica se il cron di WordPress funziona
        echo json_encode(cg_is_wp_cron_working(), JSON_PRETTY_PRINT);
        break;
        
    case 'fix_all':
        // Correggi tutte le programmazioni
        echo json_encode(cg_fix_all_schedules(), JSON_PRETTY_PRINT);
        break;
        
    case 'test_schedule':
        // Testa una programmazione specifica
        $schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        echo json_encode(cg_test_schedule($schedule_id), JSON_PRETTY_PRINT);
        break;
        
    case 'run_overdue':
        // Esegui tutti gli eventi cron scaduti
        echo json_encode(cg_run_due_cron_events(), JSON_PRETTY_PRINT);
        break;
    
    case 'clear_hook':
        // Pulisci tutti gli eventi cron programmati per un hook specifico
        echo json_encode(cg_clear_scheduled_hook('cg_generate_scheduled_curiosity'), JSON_PRETTY_PRINT);
        break;
    
    default:
        // Mostra istruzioni
        echo json_encode(array(
            'message' => 'Strumento di debug e riparazione scheduler',
            'available_actions' => array(
                'debug' => 'Visualizza tutti gli eventi cron programmati',
                'test_cron' => 'Verifica se il cron di WordPress funziona',
                'fix_all' => 'Correggi tutte le programmazioni',
                'test_schedule' => 'Testa una programmazione specifica (richiede id)',
                'run_overdue' => 'Esegui tutti gli eventi cron scaduti',
                'clear_hook' => 'Pulisci tutti gli eventi cron programmati per il nostro hook'
            ),
            'usage' => 'Aggiungi ?action=nome_azione ai parametri URL'
        ), JSON_PRETTY_PRINT);
}