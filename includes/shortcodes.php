<?php
/**
 * File: shortcodes.php
 * Description: Shortcode registration and rendering
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all shortcodes
 */
add_action('init', 'paf_register_shortcodes');
function paf_register_shortcodes() {
    add_shortcode('pet_filter_box', 'paf_filter_box_shortcode');
    add_shortcode('pet_search_grid', 'paf_search_grid_shortcode');
    add_shortcode('pet_detail', 'paf_detail_shortcode');
}

/**
 * Filter box shortcode
 *
 * Usage: [pet_filter_box]
 */
function paf_filter_box_shortcode($atts) {
    $atts = shortcode_atts(array(
        'default_type' => 'dogs' // dogs, cats, or either
    ), $atts, 'pet_filter_box');

    ob_start();
    include PAF_PLUGIN_DIR . 'templates/filter-box.php';
    return ob_get_clean();
}

/**
 * Search results grid shortcode
 *
 * Usage: [pet_search_grid]
 */
function paf_search_grid_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'dogs', // dogs or cats
        'limit' => get_option('paf_results_per_page', 12)
    ), $atts, 'pet_search_grid');

    // Check if API is configured
    if (!paf_is_api_configured()) {
        return '<div class="paf-notice paf-notice-warning">
            <p><strong>Pet Adoption Finder is not configured.</strong><br>
            Please add your API key in the plugin settings.</p>
        </div>';
    }

    ob_start();
    ?>
    <div class="paf-search-results">
        <div id="pet-results-grid" class="paf-results-grid">
            <!-- Results will be loaded via AJAX -->
            <div class="paf-loading-initial">
                <div class="paf-spinner"></div>
                <p>Loading pets...</p>
            </div>
        </div>

        <div id="loading-spinner" class="paf-loading-more" style="display:none;">
            <div class="paf-spinner"></div>
            <p>Loading more pets...</p>
        </div>

        <div id="end-message" class="paf-end-message" style="display:none;">
            <p>You've seen all available pets matching your filters.</p>
        </div>

        <div id="no-results" class="paf-empty-state" style="display:none;">
            <div class="paf-empty-icon">🐾</div>
            <h3>No Pets Found</h3>
            <p>We couldn't find any pets matching your search criteria. Try adjusting your filters.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Pet detail page shortcode
 *
 * Usage: [pet_detail]
 */
function paf_detail_shortcode($atts) {
    // Get pet ID from query var (set by rewrite rule)
    $pet_id = get_query_var('pet_id');

    if (empty($pet_id)) {
        return '<div class="paf-notice paf-notice-error">
            <p>Pet ID not found. Please access this page through a pet detail link.</p>
        </div>';
    }

    // Check if API is configured
    if (!paf_is_api_configured()) {
        return '<div class="paf-notice paf-notice-warning">
            <p><strong>Pet Adoption Finder is not configured.</strong><br>
            Please add your API key in the plugin settings.</p>
        </div>';
    }

    // ALWAYS fetch fresh data for detail pages (no cache)
    $response = paf_get_pet_by_id($pet_id, true);

    // Handle pet not found (404)
    if ($response === null) {
        ob_start();
        include PAF_PLUGIN_DIR . 'templates/pet-not-found.php';
        return ob_get_clean();
    }

    // Handle API errors
    if (is_wp_error($response)) {
        return '<div class="paf-notice paf-notice-error">
            <p><strong>Unable to load pet information.</strong><br>
            ' . esc_html($response->get_error_message()) . '<br>
            Please try again later or contact the site administrator.</p>
        </div>';
    }

    // Extract pet data
    $pet = isset($response['data']) ? $response['data'] : null;
    $included = isset($response['included']) ? $response['included'] : array();

    if (empty($pet)) {
        return '<div class="paf-notice paf-notice-error">
            <p>Pet data is invalid or missing.</p>
        </div>';
    }

    // Check if pet is available
    $is_available = true;
    if (isset($pet['attributes']['isAdoptionPending']) && $pet['attributes']['isAdoptionPending']) {
        $is_available = false;
    }

    // Set page title for SEO
    add_filter('pre_get_document_title', function() use ($pet) {
        $name = isset($pet['attributes']['name']) ? esc_html($pet['attributes']['name']) : 'Adoptable Pet';
        return $name . ' - Available for Adoption';
    }, 99);

    ob_start();
    include PAF_PLUGIN_DIR . 'templates/detail-page.php';
    return ob_get_clean();
}
