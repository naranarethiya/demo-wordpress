<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

    exit;

}

class Activity_logger {
	const LOG_LIMIT = 1000;

    private $table_name;

    /**
     * Class constructor.
     * @param string $t_name an optional table name param, defaults to 'user_activity_log'
     */
    public function __construct($t_name = 'activity_log') {
        global $wpdb;
        // Set the table name with the WordPress database prefix
        $this->table_name = $wpdb->prefix . 'wpaas_' . $t_name;


        // Schedule the cron job to run once a day
        add_action('wpaas_ua_check_record_count', array($this, 'check_record_count'));
        if (!wp_next_scheduled('wpaas_ua_check_record_count')) {
            wp_schedule_event(time(), 'daily', 'wpaas_ua_check_record_count');
        }

        $GLOBALS['wpaas_activity_logger'] = $this;
    }

    /**
     * Create the user_activity_log table if it doesn't exist.
     */
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        // Define the SQL statement to create the table
        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            activity TEXT NOT NULL,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        // Include the WordPress upgrade.php file
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        // Create or modify the table using dbDelta()
        dbDelta( $sql );
    }

    /**
     * Log a user action in the wpaas_activity_log table.
     *
     * @param int $user_id The user ID of the user performing the action.
     * @param string $activity A description of the user action.
     */
    public function log_sp_action($user_id, $activity) {
        global $wpdb;

        // Insert the new log record
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'activity' => $activity,
                'timestamp' => current_time('mysql'),
            )
        );
		if ( $result === false ) {
			$this->create_table();
		}
    }

    /**
     * Check the current record count in the user_activity_log table and delete old records if needed.
     * Limit the number of records
     */
    public function check_record_count() {
        global $wpdb;

		$this->log_sp_action(0, 'CRON purge events');
        // Get the current record count using a SQL query
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        if ( $count >= self::LOG_LIMIT ) {
            // If the count is greater than or equal to LOG_LIMIT,
	        // delete the oldest records to bring the count down to exactly LOG_LIMIT
            $this->purge_old_records($count - self::LOG_LIMIT);
        }
    }

    /**
     * Delete the oldest records from the user_activity_log table.
     *
     * @param int $limit The number of records to delete.
     */
    private function purge_old_records($limit) {
        global $wpdb;
        // Use an SQL query to select the oldest records in the table and delete them using the LIMIT clause
        $wpdb->query("DELETE FROM {$this->table_name} ORDER BY timestamp ASC LIMIT {$limit}");
    }

}
