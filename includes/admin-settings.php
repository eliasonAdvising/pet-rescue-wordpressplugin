<?php
/**
 * File: admin-settings.php
 * Description: Settings registration and field rendering
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register plugin settings
 */
add_action('admin_init', 'paf_register_settings');
function paf_register_settings() {
    // Register settings
    register_setting('paf_settings_group', 'paf_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));

    register_setting('paf_settings_group', 'paf_api_endpoint', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => 'https://api.rescuegroups.org/v5'
    ));

    register_setting('paf_settings_group', 'paf_cache_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 5
    ));

    register_setting('paf_settings_group', 'paf_results_per_page', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 12
    ));

    register_setting('paf_settings_group', 'paf_enable_error_logging', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ));

    // Add settings sections
    add_settings_section(
        'paf_api_section',
        'API Configuration',
        'paf_api_section_callback',
        'pet-adoption-finder'
    );

    add_settings_section(
        'paf_display_section',
        'Display Settings',
        'paf_display_section_callback',
        'pet-adoption-finder'
    );

    add_settings_section(
        'paf_advanced_section',
        'Advanced Settings',
        'paf_advanced_section_callback',
        'pet-adoption-finder'
    );

    // Add settings fields
    add_settings_field(
        'paf_api_key',
        'API Key',
        'paf_api_key_field_callback',
        'pet-adoption-finder',
        'paf_api_section'
    );

    add_settings_field(
        'paf_api_endpoint',
        'API Endpoint',
        'paf_api_endpoint_field_callback',
        'pet-adoption-finder',
        'paf_api_section'
    );

    add_settings_field(
        'paf_results_per_page',
        'Results Per Page',
        'paf_results_per_page_field_callback',
        'pet-adoption-finder',
        'paf_display_section'
    );

    add_settings_field(
        'paf_cache_duration',
        'Cache Duration (minutes)',
        'paf_cache_duration_field_callback',
        'pet-adoption-finder',
        'paf_advanced_section'
    );

    add_settings_field(
        'paf_enable_error_logging',
        'Enable Error Logging',
        'paf_enable_error_logging_field_callback',
        'pet-adoption-finder',
        'paf_advanced_section'
    );
}

// Section callbacks
function paf_api_section_callback() {
    echo '<p>Configure your RescueGroups.org API credentials. Get your API key from <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">RescueGroups.org</a>.</p>';
}

function paf_display_section_callback() {
    echo '<p>Customize how pets are displayed on your website.</p>';
}

function paf_advanced_section_callback() {
    echo '<p>Advanced options for performance and debugging.</p>';
}

// Field callbacks
function paf_api_key_field_callback() {
    $value = get_option('paf_api_key', '');
    $show_key = isset($_GET['show_key']) && $_GET['show_key'] === '1';
    ?>
    <input type="<?php echo $show_key ? 'text' : 'password'; ?>"
           name="paf_api_key"
           id="paf_api_key"
           value="<?php echo esc_attr($value); ?>"
           class="regular-text"
           placeholder="Enter your API key"
           autocomplete="off">

    <?php if (!empty($value)): ?>
        <p class="description">
            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            API key is set.
            <?php if (!$show_key): ?>
                <a href="?page=pet-adoption-finder&tab=settings&show_key=1">Show key</a>
            <?php else: ?>
                <a href="?page=pet-adoption-finder&tab=settings">Hide key</a>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p class="description">
            <span class="dashicons dashicons-warning" style="color: #f56e28;"></span>
            API key is required.
            <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">Get an API key</a>
        </p>
    <?php endif; ?>

    <?php if (!empty($value)): ?>
        <p>
            <button type="button"
                    class="button"
                    id="test-api-connection">
                <span class="dashicons dashicons-update"></span>
                Test API Connection
            </button>
            <span id="api-test-result"></span>
        </p>
    <?php endif; ?>
    <?php
}

function paf_api_endpoint_field_callback() {
    $value = get_option('paf_api_endpoint', 'https://api.rescuegroups.org/v5');
    ?>
    <input type="url"
           name="paf_api_endpoint"
           id="paf_api_endpoint"
           value="<?php echo esc_attr($value); ?>"
           class="regular-text"
           placeholder="https://api.rescuegroups.org/v5">
    <p class="description">The base URL for the RescueGroups.org API. Default should work for most users.</p>
    <?php
}

function paf_results_per_page_field_callback() {
    $value = get_option('paf_results_per_page', 12);
    ?>
    <input type="number"
           name="paf_results_per_page"
           id="paf_results_per_page"
           value="<?php echo esc_attr($value); ?>"
           min="1"
           max="100"
           step="1"
           class="small-text">
    <p class="description">Number of pets to show per page (1-100). Recommended: 12-20 for best performance.</p>
    <?php
}

function paf_cache_duration_field_callback() {
    $value = get_option('paf_cache_duration', 5);
    ?>
    <input type="number"
           name="paf_cache_duration"
           id="paf_cache_duration"
           value="<?php echo esc_attr($value); ?>"
           min="0"
           max="60"
           step="1"
           class="small-text">
    <span class="description">minutes</span>
    <p class="description">
        How long to cache search results (0-60 minutes).
        <strong>Note:</strong> Detail pages are NEVER cached to ensure availability is always current.
        <br>Recommended: 5 minutes for good balance between freshness and API load.
    </p>
    <?php
}

function paf_enable_error_logging_field_callback() {
    $value = get_option('paf_enable_error_logging', true);
    ?>
    <label>
        <input type="checkbox"
               name="paf_enable_error_logging"
               id="paf_enable_error_logging"
               value="1"
               <?php checked($value, true); ?>>
        Enable detailed API error logging
    </label>
    <p class="description">
        Log all API errors for troubleshooting.
        Logs can be viewed in the "API Error Log" tab.
        Disable this only if you're concerned about database storage.
    </p>
    <?php
}
