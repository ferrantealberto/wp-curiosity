<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get the scheduler instance
$scheduler = new CG_Scheduler();

// Process form submissions
if (isset($_POST['cg_scheduler_action']) && check_admin_referer('cg_scheduler_nonce', 'cg_scheduler_nonce')) {
    $action = sanitize_text_field($_POST['cg_scheduler_action']);
    
    if ($action === 'create' || $action === 'update') {
        $schedule_data = array(
            'title' => sanitize_text_field($_POST['cg_title']),
            'keyword' => sanitize_text_field($_POST['cg_keyword']),
            'type' => sanitize_text_field($_POST['cg_type']),
            'language' => sanitize_text_field($_POST['cg_language']),
            'period' => sanitize_text_field($_POST['cg_period']),
            'param1' => sanitize_text_field($_POST['cg_param1']),
            'param2' => sanitize_text_field($_POST['cg_param2']),
            'param3' => sanitize_text_field($_POST['cg_param3']),
            'param4' => sanitize_text_field($_POST['cg_param4']),
            'param5' => sanitize_text_field($_POST['cg_param5']),
            'param6' => sanitize_text_field($_POST['cg_param6']),
            'param7' => sanitize_text_field($_POST['cg_param7']),
            'param8' => sanitize_text_field($_POST['cg_param8']),
            'count' => intval($_POST['cg_count']),
            'use_random' => isset($_POST['cg_use_random']) ? 1 : 0,
            'scheduled_time' => sanitize_text_field($_POST['cg_scheduled_time']),
            'active' => isset($_POST['cg_active']) ? 1 : 0
        );
        
        if ($action === 'create') {
            $result = $scheduler->create_schedule($schedule_data);
            if ($result) {
                $message = __('Programmazione creata con successo.', 'curiosity-generator');
                $message_type = 'success';
            } else {
                $message = __('Errore nella creazione della programmazione.', 'curiosity-generator');
                $message_type = 'error';
            }
        } else {
            $schedule_id = intval($_POST['cg_schedule_id']);
            $result = $scheduler->update_schedule($schedule_id, $schedule_data);
            if ($result) {
                $message = __('Programmazione aggiornata con successo.', 'curiosity-generator');
                $message_type = 'success';
            } else {
                $message = __('Errore nell\'aggiornamento della programmazione.', 'curiosity-generator');
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete' && isset($_POST['cg_schedule_id'])) {
        $schedule_id = intval($_POST['cg_schedule_id']);
        $result = $scheduler->delete_schedule($schedule_id);
        if ($result) {
            $message = __('Programmazione eliminata con successo.', 'curiosity-generator');
            $message_type = 'success';
        } else {
            $message = __('Errore nell\'eliminazione della programmazione.', 'curiosity-generator');
            $message_type = 'error';
        }
    } elseif ($action === 'toggle' && isset($_POST['cg_schedule_id'])) {
        $schedule_id = intval($_POST['cg_schedule_id']);
        $active = isset($_POST['cg_active']) ? 1 : 0;
        $result = $scheduler->toggle_schedule_status($schedule_id, $active);
        if ($result) {
            $status = $active ? __('attivata', 'curiosity-generator') : __('disattivata', 'curiosity-generator');
            $message = sprintf(__('Programmazione %s con successo.', 'curiosity-generator'), $status);
            $message_type = 'success';
        } else {
            $message = __('Errore nel cambiare lo stato della programmazione.', 'curiosity-generator');
            $message_type = 'error';
        }
    }
}

// Check for schedule edit
$editing = false;
$current_schedule = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $schedule_id = intval($_GET['edit']);
    $current_schedule = $scheduler->get_schedule($schedule_id);
    if ($current_schedule) {
        $editing = true;
    }
}

// Get current month/year for calendar
$current_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$current_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get schedules for table
$schedules = $scheduler->get_schedules();

// Get schedules for calendar
$calendar_schedules = $scheduler->get_schedules_for_calendar($current_month, $current_year);
?>

<div class="wrap">
    <h1><?php _e('Programmazione Curiosità', 'curiosity-generator'); ?></h1>
    
    <?php if (isset($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="cg-scheduler-container">
        <!-- Tabs -->
        <h2 class="nav-tab-wrapper">
            <a href="#tab-schedule-form" class="nav-tab nav-tab-active"><?php echo $editing ? __('Modifica Programmazione', 'curiosity-generator') : __('Nuova Programmazione', 'curiosity-generator'); ?></a>
            <a href="#tab-schedule-list" class="nav-tab"><?php _e('Lista Programmazioni', 'curiosity-generator'); ?></a>
            <a href="#tab-schedule-calendar" class="nav-tab"><?php _e('Calendario', 'curiosity-generator'); ?></a>
        </h2>
        
        <!-- Schedule Form Tab -->
        <div id="tab-schedule-form" class="tab-content">
            <form method="post" action="">
                <?php wp_nonce_field('cg_scheduler_nonce', 'cg_scheduler_nonce'); ?>
                <input type="hidden" name="cg_scheduler_action" value="<?php echo $editing ? 'update' : 'create'; ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="cg_schedule_id" value="<?php echo $current_schedule->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cg_title"><?php _e('Titolo', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_title" id="cg_title" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->title) : ''; ?>" required>
                            <p class="description"><?php _e('Un titolo descrittivo per questa programmazione.', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_scheduled_time"><?php _e('Data e Ora Programmata', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="datetime-local" name="cg_scheduled_time" id="cg_scheduled_time" class="regular-text" value="<?php echo $editing ? esc_attr(date('Y-m-d\TH:i', strtotime($current_schedule->scheduled_time))) : ''; ?>" required>
                            <p class="description"><?php _e('Quando la curiosità verrà generata.', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_active"><?php _e('Attiva', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="cg_active" id="cg_active" value="1" <?php checked($editing ? $current_schedule->active : 1); ?>>
                            <p class="description"><?php _e('Se deselezionato, la programmazione non verrà eseguita.', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_use_random"><?php _e('Usa Opzioni Casuali', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="cg_use_random" id="cg_use_random" value="1" <?php checked($editing ? $current_schedule->use_random : 1); ?>>
                            <p class="description"><?php _e('Se selezionato, i valori lasciati vuoti nei campi a scelta multipla saranno riempiti con valori casuali.', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_keyword"><?php _e('Parola Chiave o Tema', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_keyword" id="cg_keyword" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->keyword) : ''; ?>" required>
                            <p class="description"><?php _e('es., Spazio, Dinosauri, Rinascimento', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_type"><?php _e('Tipo di Curiosità', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <select name="cg_type" id="cg_type">
                                <option value=""><?php _e('Seleziona un tipo o lascia vuoto per casuale', 'curiosity-generator'); ?></option>
                                <?php
                                $types = cg_get_default_types();
                                foreach ($types as $type_id => $type_name) {
                                    echo '<option value="' . esc_attr($type_id) . '" ' . ($editing && $current_schedule->type === $type_id ? 'selected' : '') . '>' . esc_html($type_name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_language"><?php _e('Lingua', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <select name="cg_language" id="cg_language">
                                <option value=""><?php _e('Seleziona una lingua o lascia vuoto per casuale', 'curiosity-generator'); ?></option>
                                <?php
                                $languages = cg_get_available_languages();
                                foreach ($languages as $lang_id => $lang_name) {
                                    echo '<option value="' . esc_attr($lang_id) . '" ' . ($editing && $current_schedule->language === $lang_id ? 'selected' : '') . '>' . esc_html($lang_name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_period"><?php _e('Periodo Temporale (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_period" id="cg_period" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->period) : ''; ?>">
                            <p class="description"><?php _e('es., XIX Secolo, Anni \'80, Medioevo', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param1"><?php _e('Luogo o Contesto Geografico (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_param1" id="cg_param1" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->param1) : ''; ?>">
                            <p class="description"><?php _e('es., Italia, Foresta Amazzonica, Aree urbane', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param2"><?php _e('Persona o Gruppo Specifico (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_param2" id="cg_param2" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->param2) : ''; ?>">
                            <p class="description"><?php _e('es., Einstein, Popoli indigeni, Astronomi', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param3"><?php _e('Aspetto o Focus (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_param3" id="cg_param3" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->param3) : ''; ?>">
                            <p class="description"><?php _e('es., Impatto culturale, Record, Innovazioni', 'curiosity-generator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param4"><?php _e('Tono (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <select name="cg_param4" id="cg_param4">
                                <option value=""><?php _e('Seleziona un tono o lascia vuoto per casuale', 'curiosity-generator'); ?></option>
                                <option value="Humorous" <?php echo ($editing && $current_schedule->param4 === 'Humorous') ? 'selected' : ''; ?>><?php _e('Umoristico', 'curiosity-generator'); ?></option>
                                <option value="Serious" <?php echo ($editing && $current_schedule->param4 === 'Serious') ? 'selected' : ''; ?>><?php _e('Serio', 'curiosity-generator'); ?></option>
                                <option value="Surprising" <?php echo ($editing && $current_schedule->param4 === 'Surprising') ? 'selected' : ''; ?>><?php _e('Sorprendente', 'curiosity-generator'); ?></option>
                                <option value="Little-known" <?php echo ($editing && $current_schedule->param4 === 'Little-known') ? 'selected' : ''; ?>><?php _e('Poco conosciuto', 'curiosity-generator'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param5"><?php _e('Tipo di Fonte (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <select name="cg_param5" id="cg_param5">
                                <option value=""><?php _e('Seleziona un tipo di fonte o lascia vuoto per casuale', 'curiosity-generator'); ?></option>
                                <option value="Scientific studies" <?php echo ($editing && $current_schedule->param5 === 'Scientific studies') ? 'selected' : ''; ?>><?php _e('Studi scientifici', 'curiosity-generator'); ?></option>
                                <option value="Historical records" <?php echo ($editing && $current_schedule->param5 === 'Historical records') ? 'selected' : ''; ?>><?php _e('Documenti storici', 'curiosity-generator'); ?></option>
                                <option value="Popular legends" <?php echo ($editing && $current_schedule->param5 === 'Popular legends') ? 'selected' : ''; ?>><?php _e('Leggende popolari', 'curiosity-generator'); ?></option>
                                <option value="Recent discoveries" <?php echo ($editing && $current_schedule->param5 === 'Recent discoveries') ? 'selected' : ''; ?>><?php _e('Scoperte recenti', 'curiosity-generator'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param6"><?php _e('Pubblico di Riferimento (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <select name="cg_param6" id="cg_param6">
                                <option value=""><?php _e('Seleziona un pubblico o lascia vuoto per casuale', 'curiosity-generator'); ?></option>
                                <option value="General public" <?php echo ($editing && $current_schedule->param6 === 'General public') ? 'selected' : ''; ?>><?php _e('Pubblico generale', 'curiosity-generator'); ?></option>
                                <option value="Children" <?php echo ($editing && $current_schedule->param6 === 'Children') ? 'selected' : ''; ?>><?php _e('Bambini', 'curiosity-generator'); ?></option>
                                <option value="Subject experts" <?php echo ($editing && $current_schedule->param6 === 'Subject experts') ? 'selected' : ''; ?>><?php _e('Esperti del settore', 'curiosity-generator'); ?></option>
                                <option value="Enthusiasts" <?php echo ($editing && $current_schedule->param6 === 'Enthusiasts') ? 'selected' : ''; ?>><?php _e('Appassionati', 'curiosity-generator'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param7"><?php _e('Parametro Personalizzato 1 (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_param7" id="cg_param7" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->param7) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_param8"><?php _e('Parametro Personalizzato 2 (Opzionale)', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cg_param8" id="cg_param8" class="regular-text" value="<?php echo $editing ? esc_attr($current_schedule->param8) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cg_count"><?php _e('Numero di Curiosità', 'curiosity-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="cg_count" id="cg_count" min="1" max="<?php echo esc_attr(get_option('cg_max_curiosities', 5)); ?>" value="<?php echo $editing ? esc_attr($current_schedule->count) : '1'; ?>">
                            <p class="description"><?php echo sprintf(__('Numero di curiosità da generare (1-%d).', 'curiosity-generator'), get_option('cg_max_curiosities', 5)); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo $editing ? __('Aggiorna Programmazione', 'curiosity-generator') : __('Crea Programmazione', 'curiosity-generator'); ?>">
                    <?php if ($editing): ?>
                        <a href="<?php echo admin_url('admin.php?page=curiosity-generator-scheduler'); ?>" class="button"><?php _e('Annulla', 'curiosity-generator'); ?></a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <!-- Schedule List Tab -->
        <div id="tab-schedule-list" class="tab-content" style="display:none">
            <table class="wp-list-table widefat fixed striped cg-schedules-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'curiosity-generator'); ?></th>
                        <th><?php _e('Titolo', 'curiosity-generator'); ?></th>
                        <th><?php _e('Parola Chiave', 'curiosity-generator'); ?></th>
                        <th><?php _e('Tipo', 'curiosity-generator'); ?></th>
                        <th><?php _e('Data e Ora', 'curiosity-generator'); ?></th>
                        <th><?php _e('Stato', 'curiosity-generator'); ?></th>
                        <th><?php _e('Ultima Esecuzione', 'curiosity-generator'); ?></th>
                        <th><?php _e('Azioni', 'curiosity-generator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="8"><?php _e('Nessuna programmazione trovata.', 'curiosity-generator'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo $schedule->id; ?></td>
                                <td><?php echo esc_html($schedule->title); ?></td>
                                <td><?php echo esc_html($schedule->keyword); ?></td>
                                <td>
                                    <?php
                                    $types = cg_get_default_types();
                                    echo isset($types[$schedule->type]) ? esc_html($types[$schedule->type]) : esc_html($schedule->type);
                                    ?>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($schedule->scheduled_time)); ?></td>
                                <td>
                                    <form method="post" action="" class="cg-toggle-form">
                                        <?php wp_nonce_field('cg_scheduler_nonce', 'cg_scheduler_nonce'); ?>
                                        <input type="hidden" name="cg_scheduler_action" value="toggle">
                                        <input type="hidden" name="cg_schedule_id" value="<?php echo $schedule->id; ?>">
                                        <label class="cg-toggle-switch">
                                            <input type="checkbox" name="cg_active" <?php checked($schedule->active, 1); ?> onchange="this.form.submit()">
                                            <span class="cg-toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <?php
                                    if ($schedule->last_run) {
                                        echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($schedule->last_run));
                                        
                                        if ($schedule->last_result) {
                                            $result = json_decode($schedule->last_result, true);
                                            if (isset($result['success'])) {
                                                echo ' - ';
                                                if ($result['success']) {
                                                    echo '<span class="cg-success">' . __('Successo', 'curiosity-generator') . '</span>';
                                                } else {
                                                    echo '<span class="cg-error">' . __('Errore', 'curiosity-generator') . '</span>';
                                                }
                                            }
                                        }
                                    } else {
                                        echo __('Mai eseguita', 'curiosity-generator');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=curiosity-generator-scheduler&edit=' . $schedule->id); ?>" class="button button-small"><?php _e('Modifica', 'curiosity-generator'); ?></a>
                                    
                                    <form method="post" action="" class="cg-inline-form">
                                        <?php wp_nonce_field('cg_scheduler_nonce', 'cg_scheduler_nonce'); ?>
                                        <input type="hidden" name="cg_scheduler_action" value="delete">
                                        <input type="hidden" name="cg_schedule_id" value="<?php echo $schedule->id; ?>">
                                        <button type="submit" class="button button-small cg-delete-btn" onclick="return confirm('<?php _e('Sei sicuro di voler eliminare questa programmazione?', 'curiosity-generator'); ?>');"><?php _e('Elimina', 'curiosity-generator'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Calendar View Tab -->
        <div id="tab-schedule-calendar" class="tab-content" style="display:none">
            <div class="cg-calendar-navigation">
                <?php
                // Previous month link
                $prev_month = $current_month - 1;
                $prev_year = $current_year;
                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }
                $prev_link = admin_url(sprintf('admin.php?page=curiosity-generator-scheduler&month=%d&year=%d#tab-schedule-calendar', $prev_month, $prev_year));
                
                // Next month link
                $next_month = $current_month + 1;
                $next_year = $current_year;
                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }
                $next_link = admin_url(sprintf('admin.php?page=curiosity-generator-scheduler&month=%d&year=%d#tab-schedule-calendar', $next_month, $next_year));
                ?>
                
                <a href="<?php echo $prev_link; ?>" class="button">&laquo; <?php _e('Mese Precedente', 'curiosity-generator'); ?></a>
                <span class="cg-calendar-title"><?php echo date_i18n('F Y', strtotime("{$current_year}-{$current_month}-01")); ?></span>
                <a href="<?php echo $next_link; ?>" class="button"><?php _e('Mese Successivo', 'curiosity-generator'); ?> &raquo;</a>
            </div>
            
            <table class="cg-calendar">
                <thead>
                    <tr>
                        <?php
                        $days_of_week = array(
                            __('Dom', 'curiosity-generator'),
                            __('Lun', 'curiosity-generator'),
                            __('Mar', 'curiosity-generator'),
                            __('Mer', 'curiosity-generator'),
                            __('Gio', 'curiosity-generator'),
                            __('Ven', 'curiosity-generator'),
                            __('Sab', 'curiosity-generator')
                        );
                        
                        foreach ($days_of_week as $day) {
                            echo "<th>{$day}</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Calendar logic
                    $days_in_month = date('t', strtotime("{$current_year}-{$current_month}-01"));
                    $first_day_of_week = date('w', strtotime("{$current_year}-{$current_month}-01"));
                    $last_day_of_week = date('w', strtotime("{$current_year}-{$current_month}-{$days_in_month}"));
                    $total_calendar_cells = $first_day_of_week + $days_in_month + (6 - $last_day_of_week);
                    $total_calendar_rows = ceil($total_calendar_cells / 7);
                    
                    // Create an indexed array of schedules by day
                    $schedules_by_day = array();
                    foreach ($calendar_schedules as $schedule) {
                        $day = intval(date('j', strtotime($schedule->scheduled_time)));
                        if (!isset($schedules_by_day[$day])) {
                            $schedules_by_day[$day] = array();
                        }
                        $schedules_by_day[$day][] = $schedule;
                    }
                    
                    $day_counter = 1;
                    $current_day = intval(date('j'));
                    $is_current_month = ($current_month == intval(date('m')) && $current_year == intval(date('Y')));
                    
                    for ($row = 1; $row <= $total_calendar_rows; $row++) {
                        echo '<tr>';
                        
                        for ($col = 0; $col < 7; $col++) {
                            $cell_day = $day_counter - $first_day_of_week;
                            
                            if ($cell_day > 0 && $cell_day <= $days_in_month) {
                                $is_today = $is_current_month && $cell_day == $current_day;
                                $day_class = $is_today ? 'cg-today' : '';
                                
                                echo '<td class="' . $day_class . '">';
                                echo '<div class="cg-calendar-day">' . $cell_day . '</div>';
                                
                                // Display schedules for this day
                                if (isset($schedules_by_day[$cell_day])) {
                                    echo '<div class="cg-calendar-schedules">';
                                    
                                    foreach ($schedules_by_day[$cell_day] as $schedule) {
                                        $schedule_time = date_i18n(get_option('time_format'), strtotime($schedule->scheduled_time));
                                        $status_class = $schedule->active ? 'cg-schedule-active' : 'cg-schedule-inactive';
                                        
                                        echo '<div class="cg-calendar-schedule ' . $status_class . '">';
                                        echo '<a href="' . admin_url('admin.php?page=curiosity-generator-scheduler&edit=' . $schedule->id) . '">';
                                        echo '<span class="cg-schedule-time">' . $schedule_time . '</span>';
                                        echo '<span class="cg-schedule-title">' . esc_html($schedule->title) . '</span>';
                                        echo '</a>';
                                        
                                        // Toggle form
                                        echo '<form method="post" action="" class="cg-inline-toggle-form">';
                                        wp_nonce_field('cg_scheduler_nonce', 'cg_scheduler_nonce');
                                        echo '<input type="hidden" name="cg_scheduler_action" value="toggle">';
                                        echo '<input type="hidden" name="cg_schedule_id" value="' . $schedule->id . '">';
                                        echo '<input type="hidden" name="cg_active" value="' . ($schedule->active ? '0' : '1') . '">';
                                        echo '<button type="submit" class="cg-toggle-btn">';
                                        echo $schedule->active ? '&#10006;' : '&#10004;';
                                        echo '</button>';
                                        echo '</form>';
                                        
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                }
                                
                                echo '</td>';
                            } else {
                                echo '<td class="cg-other-month"></td>';
                            }
                            
                            $day_counter++;
                        }
                        
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="cg-calendar-legend">
                <div class="cg-legend-item">
                    <span class="cg-legend-color cg-schedule-active"></span>
                    <span class="cg-legend-label"><?php _e('Programmazione Attiva', 'curiosity-generator'); ?></span>
                </div>
                <div class="cg-legend-item">
                    <span class="cg-legend-color cg-schedule-inactive"></span>
                    <span class="cg-legend-label"><?php _e('Programmazione Inattiva', 'curiosity-generator'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>