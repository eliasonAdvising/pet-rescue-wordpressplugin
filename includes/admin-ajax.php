<?php
/**
 * File: admin-ajax.php
 * Description: Admin AJAX request handlers
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test API connection
 */
add_action('wp_ajax_paf_test_api_connection', 'paf_test_api_connection');
function paf_test_api_connection() {
    check_ajax_referer('paf_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $api_key = get_option('paf_api_key');

    if (empty($api_key)) {
        wp_send_json_error(array('message' => 'API key is not set'));
    }

    // Test connection with a simple search
    $response = paf_search_pets('dogs', array(), 1, 1);

    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => $response->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => 'API connection successful! Your API key is working correctly.'
    ));
}

/**
 * Clear all cache
 */
add_action('wp_ajax_paf_clear_cache', 'paf_clear_cache_ajax');
function paf_clear_cache_ajax() {
    check_ajax_referer('paf_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }

    global $wpdb;

    // Delete all pet-related transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_paf_%'
         OR option_name LIKE '_transient_timeout_paf_%'"
    );

    wp_send_json_success(array(
        'message' => 'All cache cleared successfully!'
    ));
}

/**
 * Export logs to CSV
 */
add_action('wp_ajax_paf_export_logs', 'paf_export_logs');
function paf_export_logs() {
    check_ajax_referer('paf_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'paf_error_log';

    $logs = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY created_at DESC",
        ARRAY_A
    );

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pet-adoption-finder-error-log-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, array(
        'ID',
        'Date/Time',
        'Error Type',
        'Error Message',
        'Response Code',
        'User ID',
        'IP Address',
        'User Agent'
    ));

    // CSV rows
    foreach ($logs as $log) {
        fputcsv($output, array(
            $log['id'],
            $log['created_at'],
            $log['error_type'],
            $log['error_message'],
            $log['response_code'],
            $log['user_id'],
            $log['ip_address'],
            substr($log['user_agent'], 0, 100) // Truncate long user agents
        ));
    }

    fclose($output);
    exit;
}
