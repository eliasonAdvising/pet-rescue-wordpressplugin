<?php
/**
 * Plugin Name: Pet Adoption Finder
 * Plugin URI: https://github.com/ianeliason/pet-rescue-wordpressplugin
 * Description: Dynamic pet adoption search with filter box, infinite scroll grid, and shareable detail pages. Integrates with RescueGroups.org API.
 * Version: 1.0.0
 * Author: Ian Eliason
 * Author URI: https://github.com/ianeliason
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pet-adoption-finder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PAF_VERSION', '1.0.0');
define('PAF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAF_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once PAF_PLUGIN_DIR . 'includes/error-logger.php';
require_once PAF_PLUGIN_DIR . 'includes/api-handler.php';
require_once PAF_PLUGIN_DIR . 'includes/shortcodes.php';
require_once PAF_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once PAF_PLUGIN_DIR . 'includes/rewrite-rules.php';
require_once PAF_PLUGIN_DIR . 'includes/meta-tags.php';
require_once PAF_PLUGIN_DIR . 'includes/admin-menu.php';
require_once PAF_PLUGIN_DIR . 'includes/admin-settings.php';
require_once PAF_PLUGIN_DIR . 'includes/admin-ajax.php';

// Activation hook
register_activation_hook(__FILE__, 'paf_activate_plugin');
function paf_activate_plugin() {
    // Create custom rewrite rules
    paf_register_rewrite_rules();
    flush_rewrite_rules();

    // Create error log table
    paf_create_error_log_table();

    // Set default options
    add_option('paf_api_key', '');
    add_option('paf_api_endpoint', 'https://api.rescuegroups.org/v5');
    add_option('paf_cache_duration', 5);
    add_option('paf_results_per_page', 12);
    add_option('paf_enable_error_logging', true);

    // Log activation
    if (function_exists('paf_log_info')) {
        paf_log_info('PLUGIN_ACTIVATED', 'Pet Adoption Finder plugin activated successfully');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'paf_deactivate_plugin');
function paf_deactivate_plugin() {
    flush_rewrite_rules();

    // Log deactivation
    if (function_exists('paf_log_info')) {
        paf_log_info('PLUGIN_DEACTIVATED', 'Pet Adoption Finder plugin deactivated');
    }
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'paf_uninstall_plugin');
function paf_uninstall_plugin() {
    global $wpdb;

    // Delete all options
    delete_option('paf_api_key');
    delete_option('paf_api_endpoint');
    delete_option('paf_cache_duration');
    delete_option('paf_results_per_page');
    delete_option('paf_enable_error_logging');

    // Delete all transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_paf_%'
         OR option_name LIKE '_transient_timeout_paf_%'"
    );

    // Drop error log table
    $table_name = $wpdb->prefix . 'paf_error_log';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}

// Enqueue frontend scripts and styles
add_action('wp_enqueue_scripts', 'paf_enqueue_frontend_assets');
function paf_enqueue_frontend_assets() {
    // CSS
    wp_enqueue_style(
        'paf-styles',
        PAF_PLUGIN_URL . 'assets/css/pet-finder.css',
        array(),
        PAF_VERSION
    );

    // JavaScript
    wp_enqueue_script(
        'paf-script',
        PAF_PLUGIN_URL . 'assets/js/pet-finder.js',
        array('jquery'),
        PAF_VERSION,
        true
    );

    // Localize script for AJAX
    wp_localize_script('paf-script', 'petFinderAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pet_finder_nonce'),
        'siteUrl' => home_url()
    ));
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'paf_enqueue_admin_assets');
function paf_enqueue_admin_assets($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'pet-adoption-finder') === false) {
        return;
    }

    // Admin CSS
    wp_enqueue_style(
        'paf-admin-styles',
        PAF_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        PAF_VERSION
    );

    // Admin JavaScript
    wp_enqueue_script(
        'paf-admin-script',
        PAF_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery', 'jquery-ui-dialog'),
        PAF_VERSION,
        true
    );

    // Localize admin script
    wp_localize_script('paf-admin-script', 'pafAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('paf_admin_nonce')
    ));

    // jQuery UI Dialog styles
    wp_enqueue_style('wp-jquery-ui-dialog');
}

// Add settings link on plugins page
add_filter('plugin_action_links_' . PAF_PLUGIN_BASENAME, 'paf_add_settings_link');
function paf_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=pet-adoption-finder') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
