<?php
/**
 * File: rewrite-rules.php
 * Description: Custom rewrite rules for pet detail pages
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom rewrite rules for pet URLs
 * URL format: yoursite.com/pet/12345/
 */
add_action('init', 'paf_register_rewrite_rules');
function paf_register_rewrite_rules() {
    add_rewrite_rule(
        '^pet/([0-9]+)/?$',
        'index.php?pet_id=$matches[1]',
        'top'
    );
}

/**
 * Add custom query variable
 */
add_filter('query_vars', 'paf_register_query_vars');
function paf_register_query_vars($vars) {
    $vars[] = 'pet_id';
    return $vars;
}

/**
 * Generate pet detail page URL
 *
 * @param int|string $pet_id The pet ID
 * @return string Full URL to pet detail page
 */
function paf_get_pet_detail_url($pet_id) {
    return home_url('/pet/' . $pet_id . '/');
}

/**
 * Template redirect to handle pet detail pages
 */
add_action('template_redirect', 'paf_handle_pet_detail_page');
function paf_handle_pet_detail_page() {
    $pet_id = get_query_var('pet_id');

    if (!empty($pet_id)) {
        // Find a page that has the [pet_detail] shortcode
        global $wpdb;

        $page = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'page'
             AND post_status = 'publish'
             AND post_content LIKE '%[pet_detail]%'
             LIMIT 1"
        );

        if ($page) {
            // Set the main query to this page
            global $wp_query;
            $wp_query->query_vars['page_id'] = $page;
            $wp_query->queried_object_id = $page;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->is_404 = false;
        }
    }
}
