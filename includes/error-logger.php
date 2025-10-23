<?php
/**
 * File: error-logger.php
 * Description: API error logging system with database storage
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create error log table on plugin activation
 */
function paf_create_error_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'paf_error_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        error_type varchar(100) NOT NULL,
        error_message text NOT NULL,
        request_data longtext,
        response_code int(11),
        user_id bigint(20),
        ip_address varchar(100),
        user_agent varchar(255),
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY error_type (error_type),
        KEY created_at (created_at),
        KEY response_code (response_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Log API error to database
 *
 * @param string $error_type Error category (e.g., 'API_CONNECTION', 'API_TIMEOUT')
 * @param string $error_message Human-readable error description
 * @param array $request_data Request parameters (will be JSON encoded)
 * @param int $response_code HTTP response code (if applicable)
 */
function paf_log_api_error($error_type, $error_message, $request_data = array(), $response_code = null) {
    global $wpdb;

    // Check if logging is enabled
    if (!get_option('paf_enable_error_logging', true)) {
        return;
    }

    $table_name = $wpdb->prefix . 'paf_error_log';

    $wpdb->insert(
        $table_name,
        array(
            'error_type' => sanitize_text_field($error_type),
            'error_message' => sanitize_textarea_field($error_message),
            'request_data' => wp_json_encode($request_data),
            'response_code' => $response_code ? absint($response_code) : null,
            'user_id' => get_current_user_id(),
            'ip_address' => paf_get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
    );
}

/**
 * Log info message (non-error events)
 *
 * @param string $event_type Event category
 * @param string $message Event description
 */
function paf_log_info($event_type, $message) {
    global $wpdb;

    if (!get_option('paf_enable_error_logging', true)) {
        return;
    }

    $table_name = $wpdb->prefix . 'paf_error_log';

    $wpdb->insert(
        $table_name,
        array(
            'error_type' => sanitize_text_field($event_type),
            'error_message' => sanitize_textarea_field($message),
            'request_data' => wp_json_encode(array()),
            'response_code' => null,
            'user_id' => get_current_user_id(),
            'ip_address' => paf_get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
    );
}

/**
 * Get user IP address safely
 *
 * @return string User's IP address
 */
function paf_get_user_ip() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return sanitize_text_field($ip);
}

/**
 * Get error logs with optional filtering
 *
 * @param array $args Query arguments
 * @return array Array of error log objects
 */
function paf_get_error_logs($args = array()) {
    global $wpdb;

    $defaults = array(
        'error_type' => '',
        'date_filter' => '', // 'today', 'week', 'month'
        'limit' => 20,
        'offset' => 0,
        'orderby' => 'created_at',
        'order' => 'DESC'
    );

    $args = wp_parse_args($args, $defaults);
    $table_name = $wpdb->prefix . 'paf_error_log';

    $where = "1=1";
    $where_params = array();

    // Filter by error type
    if (!empty($args['error_type'])) {
        $where .= " AND error_type = %s";
        $where_params[] = $args['error_type'];
    }

    // Filter by date
    if (!empty($args['date_filter'])) {
        switch ($args['date_filter']) {
            case 'today':
                $where .= " AND DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }

    // Build query
    $where_params[] = $args['limit'];
    $where_params[] = $args['offset'];

    if (empty($where_params) || count($where_params) === 2) {
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name
             WHERE $where
             ORDER BY {$args['orderby']} {$args['order']}
             LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name
             WHERE $where
             ORDER BY {$args['orderby']} {$args['order']}
             LIMIT %d OFFSET %d",
            $where_params
        );
    }

    return $wpdb->get_results($query);
}

/**
 * Get total error log count
 *
 * @param array $args Query arguments (same as paf_get_error_logs)
 * @return int Total count
 */
function paf_get_error_logs_count($args = array()) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'paf_error_log';

    $where = "1=1";
    $where_params = array();

    if (!empty($args['error_type'])) {
        $where .= " AND error_type = %s";
        $where_params[] = $args['error_type'];
    }

    if (!empty($args['date_filter'])) {
        switch ($args['date_filter']) {
            case 'today':
                $where .= " AND DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }

    if (empty($where_params)) {
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
    } else {
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE $where",
            $where_params
        ));
    }
}

/**
 * Delete error logs
 *
 * @param array $log_ids Array of log IDs to delete
 * @return int|false Number of rows deleted or false on error
 */
function paf_delete_error_logs($log_ids) {
    global $wpdb;

    if (empty($log_ids) || !is_array($log_ids)) {
        return false;
    }

    $table_name = $wpdb->prefix . 'paf_error_log';
    $ids = array_map('absint', $log_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));

    return $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE id IN ($placeholders)",
        $ids
    ));
}

/**
 * Clear all error logs
 *
 * @return int|false Number of rows deleted or false on error
 */
function paf_clear_all_error_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'paf_error_log';
    return $wpdb->query("TRUNCATE TABLE $table_name");
}
