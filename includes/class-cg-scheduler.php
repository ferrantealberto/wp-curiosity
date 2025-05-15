<?php
/**
 * Scheduler class for handling scheduled curiosity generation.
 */
class CG_Scheduler {
    
    /**
     * Initialize the scheduler.
     */
    public function __construct() {
        add_action('cg_generate_scheduled_curiosity', array($this, 'generate_scheduled_curiosity'), 10, 1);
    }
    
    /**
     * Create a new scheduled curiosity.
     */
    public function create_schedule($params) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $params['active'] = isset($params['active']) ? 1 : 0;
        
        // Check if random option is enabled for multiple-choice fields
        if (isset($params['use_random']) && $params['use_random']) {
            if (empty($params['type'])) {
                $types = array_keys(cg_get_default_types());
                $params['type'] = $types[array_rand($types)];
            }
            
            if (empty($params['param4'])) {
                $tones = array('Humorous', 'Serious', 'Surprising', 'Little-known');
                $params['param4'] = $tones[array_rand($tones)];
            }
            
            if (empty($params['param5'])) {
                $sources = array('Scientific studies', 'Historical records', 'Popular legends', 'Recent discoveries');
                $params['param5'] = $sources[array_rand($sources)];
            }
            
            if (empty($params['param6'])) {
                $audiences = array('General public', 'Children', 'Subject experts', 'Enthusiasts');
                $params['param6'] = $audiences[array_rand($audiences)];
            }
            
            if (empty($params['language'])) {
                $languages = array_keys(cg_get_available_languages());
                $params['language'] = $languages[array_rand($languages)];
            }
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => $params['title'],
                'keyword' => $params['keyword'],
                'type' => $params['type'],
                'language' => $params['language'],
                'period' => $params['period'],
                'param1' => $params['param1'],
                'param2' => $params['param2'],
                'param3' => $params['param3'],
                'param4' => $params['param4'],
                'param5' => $params['param5'],
                'param6' => $params['param6'],
                'param7' => $params['param7'],
                'param8' => $params['param8'],
                'count' => intval($params['count']),
                'use_random' => isset($params['use_random']) ? 1 : 0,
                'scheduled_time' => $params['scheduled_time'],
                'active' => $params['active'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            $schedule_id = $wpdb->insert_id;
            
            // Schedule the cron job if active
            if ($params['active']) {
                $this->schedule_cron($schedule_id, strtotime($params['scheduled_time']));
            }
            
            return $schedule_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing scheduled curiosity.
     */
    public function update_schedule($schedule_id, $params) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        // Get current schedule data to compare
        $current_schedule = $this->get_schedule($schedule_id);
        
        if (!$current_schedule) {
            return false;
        }
        
        $params['active'] = isset($params['active']) ? 1 : 0;
        
        // Check if random option is enabled for multiple-choice fields
        if (isset($params['use_random']) && $params['use_random']) {
            if (empty($params['type'])) {
                $types = array_keys(cg_get_default_types());
                $params['type'] = $types[array_rand($types)];
            }
            
            if (empty($params['param4'])) {
                $tones = array('Humorous', 'Serious', 'Surprising', 'Little-known');
                $params['param4'] = $tones[array_rand($tones)];
            }
            
            if (empty($params['param5'])) {
                $sources = array('Scientific studies', 'Historical records', 'Popular legends', 'Recent discoveries');
                $params['param5'] = $sources[array_rand($sources)];
            }
            
            if (empty($params['param6'])) {
                $audiences = array('General public', 'Children', 'Subject experts', 'Enthusiasts');
                $params['param6'] = $audiences[array_rand($audiences)];
            }
            
            if (empty($params['language'])) {
                $languages = array_keys(cg_get_available_languages());
                $params['language'] = $languages[array_rand($languages)];
            }
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'title' => $params['title'],
                'keyword' => $params['keyword'],
                'type' => $params['type'],
                'language' => $params['language'],
                'period' => $params['period'],
                'param1' => $params['param1'],
                'param2' => $params['param2'],
                'param3' => $params['param3'],
                'param4' => $params['param4'],
                'param5' => $params['param5'],
                'param6' => $params['param6'],
                'param7' => $params['param7'],
                'param8' => $params['param8'],
                'count' => intval($params['count']),
                'use_random' => isset($params['use_random']) ? 1 : 0,
                'scheduled_time' => $params['scheduled_time'],
                'active' => $params['active'],
                'updated_at' => current_time('mysql')
            ),
            array('id' => $schedule_id)
        );
        
        if ($result !== false) {
            // Unschedule old cron job
            $this->unschedule_cron($schedule_id);
            
            // Schedule new cron job if active
            if ($params['active']) {
                $this->schedule_cron($schedule_id, strtotime($params['scheduled_time']));
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete a scheduled curiosity.
     */
    public function delete_schedule($schedule_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        // Unschedule cron job
        $this->unschedule_cron($schedule_id);
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $schedule_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Activate or deactivate a scheduled curiosity.
     */
    public function toggle_schedule_status($schedule_id, $active = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        // Unschedule cron job if deactivating
        if (!$active) {
            $this->unschedule_cron($schedule_id);
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'active' => $active ? 1 : 0,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $schedule_id)
        );
        
        // Schedule cron job if activating
        if ($result !== false && $active) {
            $schedule = $this->get_schedule($schedule_id);
            if ($schedule) {
                $this->schedule_cron($schedule_id, strtotime($schedule->scheduled_time));
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Get a specific scheduled curiosity.
     */
    public function get_schedule($schedule_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $schedule_id
            )
        );
        
        return $schedule;
    }
    
    /**
     * Get all scheduled curiosities with optional filtering.
     */
    public function get_schedules($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $defaults = array(
            'active' => null,
            'orderby' => 'scheduled_time',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
            'date_from' => null,
            'date_to' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if ($args['active'] !== null) {
            $where[] = $wpdb->prepare("active = %d", $args['active']);
        }
        
        if ($args['date_from'] !== null) {
            $where[] = $wpdb->prepare("scheduled_time >= %s", $args['date_from']);
        }
        
        if ($args['date_to'] !== null) {
            $where[] = $wpdb->prepare("scheduled_time <= %s", $args['date_to']);
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $order_clause = "ORDER BY {$args['orderby']} {$args['order']}";
        
        $limit_clause = $args['limit'] > 0 ? "LIMIT {$args['offset']}, {$args['limit']}" : "";
        
        $query = "SELECT * FROM {$table_name} {$where_clause} {$order_clause} {$limit_clause}";
        
        $schedules = $wpdb->get_results($query);
        
        return $schedules;
    }
    
    /**
     * Get scheduled curiosities for a specific date range (for calendar view).
     */
    public function get_schedules_for_calendar($month, $year) {
        $start_date = date('Y-m-01 00:00:00', strtotime("{$year}-{$month}-01"));
        $end_date = date('Y-m-t 23:59:59', strtotime("{$year}-{$month}-01"));
        
        return $this->get_schedules(array(
            'date_from' => $start_date,
            'date_to' => $end_date
        ));
    }
    
    /**
     * Schedule a cron job for generating a curiosity.
     */
    private function schedule_cron($schedule_id, $timestamp) {
        // Unschedule any existing cron
        $this->unschedule_cron($schedule_id);
        
        // If the timestamp is in the past, don't schedule
        if ($timestamp <= time()) {
            return false;
        }
        
        // Schedule the cron event
        return wp_schedule_single_event($timestamp, 'cg_generate_scheduled_curiosity', array($schedule_id));
    }
    
    /**
     * Unschedule a cron job.
     */
    private function unschedule_cron($schedule_id) {
        return wp_clear_scheduled_hook('cg_generate_scheduled_curiosity', array($schedule_id));
    }
    
    /**
     * Generate curiosity based on scheduled parameters.
     */
    public function generate_scheduled_curiosity($schedule_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        // Get the schedule
        $schedule = $this->get_schedule($schedule_id);
        
        if (!$schedule || !$schedule->active) {
            return false;
        }
        
        // Prepare parameters for generation
        $params = array(
            'keyword' => $schedule->keyword,
            'type' => $schedule->type,
            'language' => $schedule->language,
            'period' => $schedule->period,
            'count' => $schedule->count,
            'param1' => $schedule->param1,
            'param2' => $schedule->param2,
            'param3' => $schedule->param3,
            'param4' => $schedule->param4,
            'param5' => $schedule->param5,
            'param6' => $schedule->param6,
            'param7' => $schedule->param7,
            'param8' => $schedule->param8
        );
        
        // Initialize OpenRouter and Post Manager
        $openrouter = new CG_OpenRouter();
        $post_manager = new CG_Post_Manager();
        
        // Generate curiosities
        $curiosities = $openrouter->generate_curiosities($params);
        
        if (is_wp_error($curiosities)) {
            $this->log_generation_error($schedule_id, $curiosities->get_error_message());
            return false;
        }
        
        // Get default author
        $user_id = get_option('cg_default_author', 1);
        
        // Create posts for each curiosity
        $post_ids = array();
        foreach ($curiosities as $curiosity) {
            $post_id = $post_manager->create_curiosity_post($curiosity, $params, $user_id);
            if (!is_wp_error($post_id)) {
                $post_ids[] = $post_id;
            }
        }
        
        // Update schedule with last generation info
        $wpdb->update(
            $table_name,
            array(
                'last_run' => current_time('mysql'),
                'last_result' => json_encode(array(
                    'success' => true,
                    'posts' => $post_ids
                )),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $schedule_id)
        );
        
        return $post_ids;
    }
    
    /**
     * Log errors during generation.
     */
    private function log_generation_error($schedule_id, $error_message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $wpdb->update(
            $table_name,
            array(
                'last_run' => current_time('mysql'),
                'last_result' => json_encode(array(
                    'success' => false,
                    'error' => $error_message
                )),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $schedule_id)
        );
    }
    
    /**
     * Create the database table for schedules.
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cg_schedules';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            keyword varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            language varchar(50) NOT NULL,
            period varchar(255) DEFAULT '',
            param1 varchar(255) DEFAULT '',
            param2 varchar(255) DEFAULT '',
            param3 varchar(255) DEFAULT '',
            param4 varchar(255) DEFAULT '',
            param5 varchar(255) DEFAULT '',
            param6 varchar(255) DEFAULT '',
            param7 varchar(255) DEFAULT '',
            param8 varchar(255) DEFAULT '',
            count int(3) NOT NULL DEFAULT 1,
            use_random tinyint(1) NOT NULL DEFAULT 0,
            scheduled_time datetime NOT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            last_run datetime DEFAULT NULL,
            last_result text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}