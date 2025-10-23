# Pet Adoption Finder - Product Requirements Document (PRD)

**Version:** 1.0.0  
**Date:** October 23, 2025  
**Project Type:** WordPress Plugin Development  
**Target API:** RescueGroups.org API v5

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Technical Stack](#technical-stack)
3. [Phase 1: Foundation & Setup](#phase-1-foundation--setup)
4. [Phase 2: Core Plugin Architecture](#phase-2-core-plugin-architecture)
5. [Phase 3: API Integration Layer](#phase-3-api-integration-layer)
6. [Phase 4: Frontend Components](#phase-4-frontend-components)
7. [Phase 5: Admin Interface](#phase-5-admin-interface)
8. [Phase 6: Error Logging System](#phase-6-error-logging-system)
9. [Phase 7: Styling & Design System](#phase-7-styling--design-system)
10. [Phase 8: Testing & Quality Assurance](#phase-8-testing--quality-assurance)
11. [Phase 9: Deployment & Documentation](#phase-9-deployment--documentation)
12. [Appendices](#appendices)

---

## Executive Summary

### Project Goal
Build a WordPress plugin that integrates with RescueGroups.org API to provide a dynamic, filterable pet adoption search experience with shareable detail pages, comprehensive admin controls, and detailed error logging.

### Key Features
- ✅ Filter box for dogs/cats with location, breed, age, sex, size filters
- ✅ Dynamic AJAX-powered search results grid with infinite scroll
- ✅ Shareable detail pages with SEO optimization
- ✅ Real-time data freshness (always current availability)
- ✅ Comprehensive WordPress admin interface
- ✅ Detailed API error logging with filtering/export
- ✅ Modern, accessible design system (red/blue color scheme)
- ✅ No theme modifications required (shortcode-based)

### Success Criteria
1. Plugin installs and activates without errors
2. All three shortcodes render correctly
3. Filters dynamically update results without page reload
4. Detail pages always show current pet availability
5. Admin interface provides full control and monitoring
6. Error logging captures all API issues
7. Passes WCAG 2.1 AA accessibility standards
8. Works on all major browsers and devices

---

## Technical Stack

### WordPress Requirements
- **WordPress Version:** 5.8+
- **PHP Version:** 7.4+
- **MySQL Version:** 5.6+

### Frontend Technologies
- **JavaScript:** Vanilla JS / jQuery (WordPress bundled)
- **CSS:** Custom CSS with CSS variables
- **AJAX:** WordPress AJAX API

### Backend Technologies
- **API Communication:** WordPress HTTP API (`wp_remote_get`, `wp_remote_post`)
- **Caching:** WordPress Transients API
- **Database:** WordPress wpdb for error logs
- **Security:** WordPress nonces, data sanitization

### External Dependencies
- **RescueGroups.org API v5**
- **API Key Required:** Yes (user-provided)

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari (iOS 14+)
- Chrome Mobile (Android 10+)

---

## Phase 1: Foundation & Setup

### 1.1 Plugin File Structure Setup

**Objective:** Create the complete plugin directory structure and main plugin file.

**Steps:**

1. Create root plugin directory: `pet-adoption-finder/`

2. Create main plugin file: `pet-adoption-finder.php` with proper header:

```php
<?php
/**
 * Plugin Name: Pet Adoption Finder
 * Plugin URI: https://yoursite.com/pet-adoption-finder
 * Description: Dynamic pet adoption search with filter box, infinite scroll grid, and shareable detail pages. Integrates with RescueGroups.org API.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
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
require_once PAF_PLUGIN_DIR . 'includes/api-handler.php';
require_once PAF_PLUGIN_DIR . 'includes/shortcodes.php';
require_once PAF_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once PAF_PLUGIN_DIR . 'includes/rewrite-rules.php';
require_once PAF_PLUGIN_DIR . 'includes/meta-tags.php';
require_once PAF_PLUGIN_DIR . 'includes/admin-menu.php';
require_once PAF_PLUGIN_DIR . 'includes/admin-settings.php';
require_once PAF_PLUGIN_DIR . 'includes/admin-ajax.php';
require_once PAF_PLUGIN_DIR . 'includes/error-logger.php';

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
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'paf_deactivate_plugin');
function paf_deactivate_plugin() {
    flush_rewrite_rules();
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
        'nonce' => wp_create_nonce('pet_finder_nonce')
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
```

3. Create directory structure:

```
pet-adoption-finder/
├── pet-adoption-finder.php          # Main plugin file
├── readme.txt                        # WordPress.org readme
├── includes/
│   ├── api-handler.php              # RescueGroups API integration
│   ├── shortcodes.php               # Shortcode registration
│   ├── ajax-handlers.php            # Frontend AJAX handlers
│   ├── rewrite-rules.php            # Custom URL structure
│   ├── meta-tags.php                # SEO and social meta tags
│   ├── admin-menu.php               # WordPress admin menu
│   ├── admin-settings.php           # Settings registration
│   ├── admin-ajax.php               # Admin AJAX handlers
│   └── error-logger.php             # API error logging system
├── assets/
│   ├── js/
│   │   ├── pet-finder.js            # Frontend JavaScript
│   │   └── admin.js                 # Admin interface JavaScript
│   └── css/
│       ├── pet-finder.css           # Plugin styles
│       └── admin.css                # Admin interface styles
└── templates/
    ├── filter-box.php               # Filter form template
    ├── grid-item.php                # Single grid item template
    └── detail-page.php              # Pet detail page template
```

**Deliverable:** Complete plugin structure with main file and all subdirectories created.

---

### 1.2 Create Placeholder Files

**Objective:** Create all required PHP, JS, and CSS files with basic structure.

**Steps:**

1. Create each PHP file in `includes/` with opening PHP tag and file header comment:

```php
<?php
/**
 * File: api-handler.php
 * Description: Handles all communication with RescueGroups.org API v5
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Functions will be added in Phase 3
```

2. Create JavaScript files with IIFE structure:

```javascript
/**
 * File: pet-finder.js
 * Description: Frontend functionality for pet search and infinite scroll
 */

(function($) {
    'use strict';
    
    // Code will be added in Phase 4
    
})(jQuery);
```

3. Create CSS files with root variables:

```css
/**
 * File: pet-finder.css
 * Description: Main plugin styles
 */

:root {
    /* Design system variables will be added in Phase 7 */
}
```

4. Create template files with basic HTML structure:

```php
<?php
/**
 * Template: filter-box.php
 * Description: Pet filter sidebar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="paf-filter-sidebar">
    <!-- Template content will be added in Phase 4 -->
</div>
```

**Deliverable:** All files created with proper headers and basic structure.

---

## Phase 2: Core Plugin Architecture

### 2.1 Rewrite Rules for Pet Detail Pages

**Objective:** Implement custom URL structure for shareable pet detail pages.

**File:** `includes/rewrite-rules.php`

**Implementation:**

```php
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
```

**Testing Steps:**
1. Activate plugin
2. Go to Settings > Permalinks and click "Save Changes" (flushes rewrite rules)
3. Test URL: `yoursite.com/pet/123456/` should not 404
4. Verify `get_query_var('pet_id')` returns the ID

**Deliverable:** Custom URLs working, query var accessible.

---

### 2.2 Database Setup for Error Logging

**Objective:** Create database table for storing API error logs.

**File:** `includes/error-logger.php`

**Implementation:**

```php
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
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE $where 
         ORDER BY {$args['orderby']} {$args['order']} 
         LIMIT %d OFFSET %d",
        array_merge($where_params, array($args['limit'], $args['offset']))
    );
    
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
```

**Testing Steps:**
1. Activate plugin (table should be created)
2. Check database for `wp_paf_error_log` table
3. Verify table structure includes all columns
4. Test `paf_log_api_error()` function manually

**Deliverable:** Database table created, logging functions operational.

---

## Phase 3: API Integration Layer

### 3.1 Base API Handler Setup

**Objective:** Create foundation for RescueGroups.org API communication.

**File:** `includes/api-handler.php`

**Implementation:**

```php
<?php
/**
 * File: api-handler.php
 * Description: RescueGroups.org API v5 integration
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get API base URL
 * 
 * @return string API endpoint URL
 */
function paf_get_api_endpoint() {
    return get_option('paf_api_endpoint', 'https://api.rescuegroups.org/v5');
}

/**
 * Get API key from settings
 * 
 * @return string API key
 */
function paf_get_api_key() {
    return get_option('paf_api_key', '');
}

/**
 * Build API request headers
 * 
 * @return array Request headers
 */
function paf_get_api_headers() {
    return array(
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => paf_get_api_key()
    );
}

/**
 * Check if API is configured
 * 
 * @return bool True if API key is set
 */
function paf_is_api_configured() {
    $api_key = paf_get_api_key();
    return !empty($api_key);
}

/**
 * Make GET request to RescueGroups API
 * 
 * @param string $endpoint API endpoint (without base URL)
 * @param array $params Query parameters
 * @return array|WP_Error Response data or WP_Error on failure
 */
function paf_api_get($endpoint, $params = array()) {
    if (!paf_is_api_configured()) {
        paf_log_api_error(
            'API_AUTH',
            'API key not configured',
            array('endpoint' => $endpoint)
        );
        return new WP_Error('no_api_key', 'API key not configured');
    }
    
    $base_url = paf_get_api_endpoint();
    $url = $base_url . $endpoint;
    
    // Add query parameters
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $args = array(
        'headers' => paf_get_api_headers(),
        'timeout' => 15
    );
    
    $response = wp_remote_get($url, $args);
    
    return paf_process_api_response($response, $endpoint, $params);
}

/**
 * Make POST request to RescueGroups API
 * 
 * @param string $endpoint API endpoint (without base URL)
 * @param array $body Request body (will be JSON encoded)
 * @param array $params Query parameters
 * @return array|WP_Error Response data or WP_Error on failure
 */
function paf_api_post($endpoint, $body = array(), $params = array()) {
    if (!paf_is_api_configured()) {
        paf_log_api_error(
            'API_AUTH',
            'API key not configured',
            array('endpoint' => $endpoint)
        );
        return new WP_Error('no_api_key', 'API key not configured');
    }
    
    $base_url = paf_get_api_endpoint();
    $url = $base_url . $endpoint;
    
    // Add query parameters
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $args = array(
        'headers' => paf_get_api_headers(),
        'body' => wp_json_encode($body),
        'timeout' => 15
    );
    
    $response = wp_remote_post($url, $args);
    
    return paf_process_api_response($response, $endpoint, array_merge($params, $body));
}

/**
 * Process API response and handle errors
 * 
 * @param array|WP_Error $response WordPress HTTP API response
 * @param string $endpoint Original endpoint
 * @param array $request_data Original request parameters
 * @return array|WP_Error Processed response or WP_Error
 */
function paf_process_api_response($response, $endpoint, $request_data) {
    // Handle WordPress HTTP errors
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        
        paf_log_api_error(
            'API_CONNECTION',
            $error_message,
            array(
                'endpoint' => $endpoint,
                'request' => $request_data
            )
        );
        
        return $response;
    }
    
    // Get response code
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // Handle HTTP errors
    if ($status_code !== 200) {
        $error_type = 'API_RESPONSE';
        
        // Categorize error types
        switch ($status_code) {
            case 401:
                $error_type = 'API_AUTH';
                break;
            case 404:
                $error_type = 'API_NOT_FOUND';
                break;
            case 429:
                $error_type = 'API_RATE_LIMIT';
                break;
            case 500:
            case 502:
            case 503:
                $error_type = 'API_SERVER_ERROR';
                break;
            case 408:
                $error_type = 'API_TIMEOUT';
                break;
        }
        
        paf_log_api_error(
            $error_type,
            "HTTP $status_code: " . wp_remote_retrieve_response_message($response),
            array(
                'endpoint' => $endpoint,
                'request' => $request_data,
                'response_body' => $body
            ),
            $status_code
        );
        
        return new WP_Error(
            $error_type,
            "API returned status code: $status_code",
            array('status' => $status_code)
        );
    }
    
    // Parse JSON response
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        paf_log_api_error(
            'API_PARSE_ERROR',
            'Failed to parse JSON response: ' . json_last_error_msg(),
            array(
                'endpoint' => $endpoint,
                'request' => $request_data,
                'response_body' => substr($body, 0, 500)
            )
        );
        
        return new WP_Error('parse_error', 'Failed to parse API response');
    }
    
    return $data;
}
```

**Testing Steps:**
1. Set API key in settings
2. Test `paf_api_get()` with valid endpoint
3. Test error logging for invalid API key
4. Verify error logs appear in database

**Deliverable:** Base API communication functions working, error logging active.

---

### 3.2 Pet Search Functions

**Objective:** Implement functions to search for dogs and cats with filters.

**File:** `includes/api-handler.php` (continued)

**Implementation:**

```php
/**
 * Search for available pets (dogs or cats)
 * 
 * @param string $pet_type 'dogs' or 'cats'
 * @param array $filters Filter parameters
 * @param int $page Page number (starts at 1)
 * @param int $limit Results per page
 * @return array|WP_Error Search results or error
 */
function paf_search_pets($pet_type, $filters = array(), $page = 1, $limit = null) {
    // Validate pet type
    if (!in_array($pet_type, array('dogs', 'cats'))) {
        return new WP_Error('invalid_type', 'Pet type must be dogs or cats');
    }
    
    // Get limit from settings if not provided
    if ($limit === null) {
        $limit = absint(get_option('paf_results_per_page', 12));
    }
    
    // Check cache first (only for search results, not detail pages)
    $cache_key = 'pet_search_' . md5(serialize(array($pet_type, $filters, $page, $limit)));
    $cache_duration = absint(get_option('paf_cache_duration', 5)) * MINUTE_IN_SECONDS;
    
    $cached = get_transient($cache_key);
    if ($cached !== false && $cache_duration > 0) {
        return $cached;
    }
    
    // Build endpoint
    $endpoint = '/public/animals/search/available/' . $pet_type . '/';
    
    // Build query parameters
    $params = array(
        'page' => absint($page),
        'limit' => absint($limit),
        'include' => 'pictures,breeds,organizations,locations'
    );
    
    // Add sort if location provided
    if (!empty($filters['location'])) {
        $params['sort'] = 'distance';
    }
    
    // Determine if we need POST (with filters) or GET (no filters)
    if (paf_has_filters($filters)) {
        $body = paf_build_filter_body($filters);
        $response = paf_api_post($endpoint, $body, $params);
    } else {
        $response = paf_api_get($endpoint, $params);
    }
    
    // Cache successful response
    if (!is_wp_error($response) && $cache_duration > 0) {
        set_transient($cache_key, $response, $cache_duration);
    }
    
    return $response;
}

/**
 * Check if filters array has any values
 * 
 * @param array $filters Filter parameters
 * @return bool True if filters exist
 */
function paf_has_filters($filters) {
    if (empty($filters)) {
        return false;
    }
    
    foreach ($filters as $value) {
        if (!empty($value)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Build filter body for POST request
 * 
 * @param array $filters Filter parameters
 * @return array Request body structure
 */
function paf_build_filter_body($filters) {
    $filter_array = array();
    
    // Location filter
    if (!empty($filters['location'])) {
        $filter_array[] = array(
            'fieldName' => 'postalcode',
            'operation' => 'equals',
            'criteria' => sanitize_text_field($filters['location'])
        );
    }
    
    // Distance filter
    if (!empty($filters['distance'])) {
        $filter_array[] = array(
            'fieldName' => 'distance',
            'operation' => 'radius',
            'criteria' => sanitize_text_field($filters['distance'])
        );
    }
    
    // Age filter
    if (!empty($filters['age'])) {
        $filter_array[] = array(
            'fieldName' => 'ageGroup',
            'operation' => 'equals',
            'criteria' => sanitize_text_field($filters['age'])
        );
    }
    
    // Sex filter
    if (!empty($filters['sex'])) {
        $filter_array[] = array(
            'fieldName' => 'sex',
            'operation' => 'equals',
            'criteria' => sanitize_text_field($filters['sex'])
        );
    }
    
    // Size filter
    if (!empty($filters['size'])) {
        $filter_array[] = array(
            'fieldName' => 'sizeGroup',
            'operation' => 'equals',
            'criteria' => sanitize_text_field($filters['size'])
        );
    }
    
    // Breed filter
    if (!empty($filters['breed'])) {
        $filter_array[] = array(
            'fieldName' => 'breeds.name',
            'operation' => 'equals',
            'criteria' => sanitize_text_field($filters['breed'])
        );
    }
    
    return array(
        'data' => array(
            'filters' => $filter_array
        )
    );
}

/**
 * Get individual pet details by ID
 * 
 * @param string $pet_id Pet ID
 * @param bool $force_refresh Whether to bypass cache
 * @return array|WP_Error Pet data or error
 */
function paf_get_pet_by_id($pet_id, $force_refresh = true) {
    // Sanitize pet ID
    $pet_id = sanitize_text_field($pet_id);
    
    // IMPORTANT: Detail pages should ALWAYS fetch fresh data
    // This ensures users never see outdated availability status
    if (!$force_refresh) {
        $cache_key = 'pet_detail_' . $pet_id;
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }
    
    // Build endpoint
    $endpoint = '/public/animals/' . $pet_id;
    
    // Build query parameters - include all related data
    $params = array(
        'include' => 'pictures,breeds,colors,organizations,locations,contacts'
    );
    
    $response = paf_api_get($endpoint, $params);
    
    // Handle 404 (pet not found/adopted) - don't log as error, this is normal
    if (is_wp_error($response) && $response->get_error_code() === 'API_NOT_FOUND') {
        return null; // Return null to indicate pet not found
    }
    
    // Only cache if not force refresh and pet is available
    if (!$force_refresh && !is_wp_error($response)) {
        $cache_key = 'pet_detail_' . $pet_id;
        set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);
    }
    
    return $response;
}

/**
 * Get related data from included array
 * 
 * @param array $animal Animal data
 * @param string $relationship_name Relationship name (e.g., 'breeds', 'pictures')
 * @param array $included Included data array from API response
 * @return array Array of related items
 */
function paf_get_related_data($animal, $relationship_name, $included) {
    if (empty($animal['relationships'][$relationship_name]['data'])) {
        return array();
    }
    
    $relationships = $animal['relationships'][$relationship_name]['data'];
    $results = array();
    
    foreach ($relationships as $rel) {
        foreach ($included as $item) {
            if ($item['type'] === $rel['type'] && $item['id'] === $rel['id']) {
                $results[] = $item;
                break;
            }
        }
    }
    
    return $results;
}

/**
 * Extract breed names from pet data
 * 
 * @param array $animal Animal data
 * @param array $included Included data array
 * @return string Comma-separated breed names
 */
function paf_get_breed_names($animal, $included) {
    $breeds = paf_get_related_data($animal, 'breeds', $included);
    $names = array();
    
    foreach ($breeds as $breed) {
        if (!empty($breed['attributes']['name'])) {
            $names[] = $breed['attributes']['name'];
        }
    }
    
    return implode(', ', $names);
}

/**
 * Get primary image URL for pet
 * 
 * @param array $animal Animal data
 * @param array $included Included data array
 * @param string $size Image size (small, medium, large)
 * @return string Image URL or placeholder
 */
function paf_get_primary_image($animal, $included, $size = 'large') {
    // Try pictureThumbnailUrl first
    if (!empty($animal['attributes']['pictureThumbnailUrl'])) {
        return $animal['attributes']['pictureThumbnailUrl'];
    }
    
    // Try pictures relationship
    $pictures = paf_get_related_data($animal, 'pictures', $included);
    
    if (!empty($pictures)) {
        // Sort by order
        usort($pictures, function($a, $b) {
            $order_a = isset($a['attributes']['order']) ? $a['attributes']['order'] : 999;
            $order_b = isset($b['attributes']['order']) ? $b['attributes']['order'] : 999;
            return $order_a - $order_b;
        });
        
        $primary = $pictures[0];
        if (!empty($primary['attributes'][$size])) {
            return $primary['attributes'][$size];
        }
    }
    
    // Return placeholder
    return PAF_PLUGIN_URL . 'assets/images/placeholder-pet.png';
}

/**
 * Get all images for pet
 * 
 * @param array $animal Animal data
 * @param array $included Included data array
 * @return array Array of image data
 */
function paf_get_all_images($animal, $included) {
    $pictures = paf_get_related_data($animal, 'pictures', $included);
    
    // Sort by order
    usort($pictures, function($a, $b) {
        $order_a = isset($a['attributes']['order']) ? $a['attributes']['order'] : 999;
        $order_b = isset($b['attributes']['order']) ? $b['attributes']['order'] : 999;
        return $order_a - $order_b;
    });
    
    return $pictures;
}
```

**Testing Steps:**
1. Test `paf_search_pets('dogs')` with no filters
2. Test with location filter: `paf_search_pets('dogs', ['location' => '32459'])`
3. Test with multiple filters
4. Test `paf_get_pet_by_id()` with valid ID
5. Test with invalid ID (should return null, not error)
6. Verify caching works for search results
7. Verify detail pages always fetch fresh data

**Deliverable:** Pet search and retrieval functions operational, caching working.

---

## Phase 4: Frontend Components

### 4.1 Shortcode Registration

**Objective:** Register three core shortcodes for plugin functionality.

**File:** `includes/shortcodes.php`

**Implementation:**

```php
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
            <p>Pet Adoption Finder is not configured. Please add your API key in the plugin settings.</p>
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
            <p>Pet ID not found.</p>
        </div>';
    }
    
    // Check if API is configured
    if (!paf_is_api_configured()) {
        return '<div class="paf-notice paf-notice-warning">
            <p>Pet Adoption Finder is not configured. Please add your API key in the plugin settings.</p>
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
            <p>Unable to load pet information. Please try again later.</p>
        </div>';
    }
    
    // Extract pet data
    $pet = isset($response['data']) ? $response['data'] : null;
    $included = isset($response['included']) ? $response['included'] : array();
    
    if (empty($pet)) {
        return '<div class="paf-notice paf-notice-error">
            <p>Pet data is invalid.</p>
        </div>';
    }
    
    // Check if pet is available
    $is_available = true;
    if (isset($pet['attributes']['isAdoptionPending']) && $pet['attributes']['isAdoptionPending']) {
        $is_available = false;
    }
    
    // Set page title for SEO
    add_filter('pre_get_document_title', function() use ($pet) {
        return esc_html($pet['attributes']['name']) . ' - Available for Adoption';
    }, 99);
    
    ob_start();
    include PAF_PLUGIN_DIR . 'templates/detail-page.php';
    return ob_get_clean();
}
```

**Testing Steps:**
1. Create test page with `[pet_filter_box]` - should render filter form
2. Create test page with `[pet_search_grid]` - should show loading state
3. Create test page with `[pet_detail]` - should show notice (no pet ID yet)
4. Test detail page with URL: `/pet/123456/`

**Deliverable:** All three shortcodes registered and rendering placeholder content.

---

### 4.2 Filter Box Template

**Objective:** Create the red sidebar filter form.

**File:** `templates/filter-box.php`

**Implementation:**

```php
<?php
/**
 * Template: filter-box.php
 * Description: Pet filter sidebar with Dogs/Cats tabs
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$default_type = isset($atts['default_type']) ? $atts['default_type'] : 'dogs';
?>

<div class="paf-filter-sidebar" id="paf-filter-sidebar">
    
    <!-- Pet Type Tabs -->
    <div class="paf-filter-tabs" role="tablist">
        <button type="button" 
                class="paf-filter-tab <?php echo $default_type === 'dogs' ? 'active' : ''; ?>" 
                data-type="dogs"
                role="tab"
                aria-selected="<?php echo $default_type === 'dogs' ? 'true' : 'false'; ?>"
                id="tab-dogs">
            Dogs
        </button>
        <button type="button" 
                class="paf-filter-tab <?php echo $default_type === 'cats' ? 'active' : ''; ?>" 
                data-type="cats"
                role="tab"
                aria-selected="<?php echo $default_type === 'cats' ? 'true' : 'false'; ?>"
                id="tab-cats">
            Cats
        </button>
        <button type="button" 
                class="paf-filter-tab <?php echo $default_type === 'either' ? 'active' : ''; ?>" 
                data-type="either"
                role="tab"
                aria-selected="<?php echo $default_type === 'either' ? 'true' : 'false'; ?>"
                id="tab-either">
            Either
        </button>
    </div>
    
    <form id="paf-filter-form" class="paf-filter-form">
        
        <!-- Hidden field for pet type -->
        <input type="hidden" id="paf-pet-type" name="pet_type" value="<?php echo esc_attr($default_type); ?>">
        
        <!-- Location Filter -->
        <div class="paf-filter-group">
            <label for="paf-location" class="paf-filter-label">Location</label>
            <div class="paf-input-wrapper">
                <input type="text" 
                       id="paf-location" 
                       name="location" 
                       class="paf-text-input" 
                       placeholder="ZIP Code"
                       maxlength="5"
                       pattern="[0-9]{5}"
                       aria-describedby="location-help">
                <svg class="paf-input-icon paf-input-icon-success" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span id="location-help" class="paf-helper-text">Enter your ZIP code to find nearby pets</span>
        </div>
        
        <!-- Distance Filter -->
        <div class="paf-filter-group">
            <label for="paf-distance" class="paf-filter-label">Distance</label>
            <div class="paf-select-wrapper">
                <select id="paf-distance" name="distance" class="paf-select">
                    <option value="">Any Distance</option>
                    <option value="10">Within 10 miles</option>
                    <option value="25">Within 25 miles</option>
                    <option value="50">Within 50 miles</option>
                    <option value="100">Within 100 miles</option>
                    <option value="200">Within 200 miles</option>
                </select>
            </div>
        </div>
        
        <!-- Breed Filter -->
        <div class="paf-filter-group">
            <label for="paf-breed" class="paf-filter-label">Breed</label>
            <div class="paf-select-wrapper">
                <select id="paf-breed" name="breed" class="paf-select" disabled>
                    <option value="">Any Breed</option>
                </select>
            </div>
            <span class="paf-helper-text">Select Dogs or Cats to enable breed filtering</span>
        </div>
        
        <!-- Age Filter -->
        <div class="paf-filter-group">
            <label for="paf-age" class="paf-filter-label">Age</label>
            <div class="paf-select-wrapper">
                <select id="paf-age" name="age" class="paf-select">
                    <option value="">Any Age</option>
                    <option value="Baby">Baby</option>
                    <option value="Young">Young</option>
                    <option value="Adult">Adult</option>
                    <option value="Senior">Senior</option>
                </select>
            </div>
        </div>
        
        <!-- Sex Filter -->
        <div class="paf-filter-group">
            <label for="paf-sex" class="paf-filter-label">Sex</label>
            <div class="paf-select-wrapper">
                <select id="paf-sex" name="sex" class="paf-select">
                    <option value="">Any Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
        </div>
        
        <!-- Size Filter -->
        <div class="paf-filter-group">
            <label for="paf-size" class="paf-filter-label">Size</label>
            <div class="paf-select-wrapper">
                <select id="paf-size" name="size" class="paf-select">
                    <option value="">Any Size</option>
                    <option value="Small">Small</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                    <option value="X-Large">Extra Large</option>
                </select>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="paf-filter-actions">
            <button type="button" class="paf-button-clear" id="paf-clear-filters">
                Clear Filters
            </button>
            <button type="submit" class="paf-button-apply">
                Apply
            </button>
        </div>
        
    </form>
    
</div>
```

**Testing Steps:**
1. Render shortcode on test page
2. Verify all form elements render correctly
3. Test tab switching (JavaScript will be added next)
4. Check accessibility with keyboard navigation

**Deliverable:** Filter box template rendering with all form elements.

---

### 4.3 Frontend JavaScript - Filter & Infinite Scroll

**Objective:** Implement AJAX filtering and infinite scroll functionality.

**File:** `assets/js/pet-finder.js`

**Implementation:**

```javascript
/**
 * File: pet-finder.js
 * Description: Frontend functionality for pet search
 */

(function($) {
    'use strict';
    
    // Pet Search Grid Controller
    const PetSearchGrid = {
        currentPage: 1,
        loading: false,
        hasMore: true,
        filters: {},
        petType: 'dogs',
        
        init: function() {
            this.setupFilterTabs();
            this.setupFilterForm();
            this.setupInfiniteScroll();
            this.setupLocationValidation();
            this.loadInitialPets();
        },
        
        setupFilterTabs: function() {
            const self = this;
            
            $('.paf-filter-tab').on('click', function() {
                const $tab = $(this);
                const type = $tab.data('type');
                
                // Update active state
                $('.paf-filter-tab').removeClass('active').attr('aria-selected', 'false');
                $tab.addClass('active').attr('aria-selected', 'true');
                
                // Update pet type
                self.petType = type;
                $('#paf-pet-type').val(type);
                
                // Enable/disable breed filter
                if (type === 'either') {
                    $('#paf-breed').prop('disabled', true);
                } else {
                    $('#paf-breed').prop('disabled', false);
                    // TODO: Load breeds for selected type
                }
                
                // Reload results
                self.currentPage = 1;
                self.hasMore = true;
                self.applyFilters(true);
            });
        },
        
        setupFilterForm: function() {
            const self = this;
            let filterTimeout;
            
            // Handle form submission
            $('#paf-filter-form').on('submit', function(e) {
                e.preventDefault();
                self.applyFilters(true);
            });
            
            // Handle filter changes (debounced)
            $('#paf-filter-form select, #paf-filter-form input[type="text"]').on('change input', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(function() {
                    self.applyFilters(true);
                }, 500);
            });
            
            // Clear filters button
            $('#paf-clear-filters').on('click', function() {
                $('#paf-filter-form')[0].reset();
                self.filters = {};
                self.applyFilters(true);
            });
        },
        
        setupLocationValidation: function() {
            $('#paf-location').on('input', function() {
                const $input = $(this);
                const value = $input.val();
                const $icon = $input.siblings('.paf-input-icon-success');
                
                // Validate ZIP code (5 digits)
                if (/^\d{5}$/.test(value)) {
                    $icon.show();
                } else {
                    $icon.hide();
                }
            });
        },
        
        setupInfiniteScroll: function() {
            const self = this;
            
            $(window).on('scroll', function() {
                if (self.shouldLoadMore()) {
                    self.loadMorePets();
                }
            });
        },
        
        shouldLoadMore: function() {
            if (this.loading || !this.hasMore) {
                return false;
            }
            
            const scrollPosition = $(window).scrollTop() + $(window).height();
            const triggerPoint = $(document).height() - 500;
            
            return scrollPosition >= triggerPoint;
        },
        
        getFilters: function() {
            return {
                location: $('#paf-location').val(),
                distance: $('#paf-distance').val(),
                breed: $('#paf-breed').val(),
                age: $('#paf-age').val(),
                sex: $('#paf-sex').val(),
                size: $('#paf-size').val()
            };
        },
        
        applyFilters: function(replace) {
            this.filters = this.getFilters();
            this.currentPage = 1;
            this.hasMore = true;
            this.loadPets(replace);
        },
        
        loadInitialPets: function() {
            this.loadPets(true);
        },
        
        loadMorePets: function() {
            if (!this.loading && this.hasMore) {
                this.currentPage++;
                this.loadPets(false);
            }
        },
        
        loadPets: function(replace) {
            const self = this;
            
            self.loading = true;
            
            if (replace) {
                $('#pet-results-grid').html('<div class="paf-loading-initial"><div class="paf-spinner"></div><p>Loading pets...</p></div>');
            } else {
                $('#loading-spinner').show();
            }
            
            $.ajax({
                url: petFinderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'paf_filter_pets',
                    nonce: petFinderAjax.nonce,
                    pet_type: self.petType,
                    filters: self.filters,
                    page: self.currentPage
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        if (replace) {
                            $('#pet-results-grid').html(data.html);
                        } else {
                            $('#pet-results-grid').append(data.html);
                        }
                        
                        // Update pagination state
                        self.hasMore = data.has_more;
                        
                        // Show end message if no more results
                        if (!self.hasMore) {
                            $('#end-message').show();
                        } else {
                            $('#end-message').hide();
                        }
                        
                        // Show no results message if empty
                        if (data.total === 0) {
                            $('#no-results').show();
                            $('#pet-results-grid').hide();
                        } else {
                            $('#no-results').hide();
                            $('#pet-results-grid').show();
                        }
                    } else {
                        console.error('Error loading pets:', response.data);
                        self.showError(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    self.showError('Unable to load pets. Please try again.');
                },
                complete: function() {
                    self.loading = false;
                    $('#loading-spinner').hide();
                }
            });
        },
        
        showError: function(message) {
            const errorHtml = '<div class="paf-notice paf-notice-error"><p>' + message + '</p></div>';
            $('#pet-results-grid').html(errorHtml);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if search grid exists
        if ($('#pet-results-grid').length > 0) {
            PetSearchGrid.init();
        }
    });
    
})(jQuery);
```

**Testing Steps:**
1. Verify filter tabs switch correctly
2. Test location validation (ZIP code format)
3. Test filter changes trigger search (debounced)
4. Test clear filters button
5. Test infinite scroll triggers at correct position
6. Verify loading states appear correctly

**Deliverable:** Filter box and infinite scroll working, pending AJAX handler implementation.

---

### 4.4 AJAX Handlers for Frontend

**Objective:** Create WordPress AJAX endpoints to handle filter requests.

**File:** `includes/ajax-handlers.php`

**Implementation:**

```php
<?php
/**
 * File: ajax-handlers.php
 * Description: AJAX endpoint handlers for frontend
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle pet filter AJAX request
 */
add_action('wp_ajax_paf_filter_pets', 'paf_handle_filter_pets');
add_action('wp_ajax_nopriv_paf_filter_pets', 'paf_handle_filter_pets');

function paf_handle_filter_pets() {
    // Verify nonce
    check_ajax_referer('pet_finder_nonce', 'nonce');
    
    // Get parameters
    $pet_type = isset($_POST['pet_type']) ? sanitize_text_field($_POST['pet_type']) : 'dogs';
    $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    
    // Sanitize filters
    $sanitized_filters = array();
    if (!empty($filters['location'])) {
        $sanitized_filters['location'] = sanitize_text_field($filters['location']);
    }
    if (!empty($filters['distance'])) {
        $sanitized_filters['distance'] = sanitize_text_field($filters['distance']);
    }
    if (!empty($filters['breed'])) {
        $sanitized_filters['breed'] = sanitize_text_field($filters['breed']);
    }
    if (!empty($filters['age'])) {
        $sanitized_filters['age'] = sanitize_text_field($filters['age']);
    }
    if (!empty($filters['sex'])) {
        $sanitized_filters['sex'] = sanitize_text_field($filters['sex']);
    }
    if (!empty($filters['size'])) {
        $sanitized_filters['size'] = sanitize_text_field($filters['size']);
    }
    
    // Handle "either" type - search both dogs and cats
    if ($pet_type === 'either') {
        // Search both, merge results, and sort by distance
        $dogs_response = paf_search_pets('dogs', $sanitized_filters, $page);
        $cats_response = paf_search_pets('cats', $sanitized_filters, $page);
        
        // Merge and process
        $response = paf_merge_pet_results($dogs_response, $cats_response);
    } else {
        // Standard search
        $response = paf_search_pets($pet_type, $sanitized_filters, $page);
    }
    
    // Handle API errors
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Unable to load pets. Please try again later.'
        ));
    }
    
    // Extract data
    $pets = isset($response['data']) ? $response['data'] : array();
    $included = isset($response['included']) ? $response['included'] : array();
    $meta = isset($response['meta']) ? $response['meta'] : array();
    
    // Generate HTML for grid items
    $html = '';
    foreach ($pets as $pet) {
        $html .= paf_render_grid_item($pet, $included);
    }
    
    // Determine if there are more results
    $has_more = false;
    if (isset($meta['pageReturned']) && isset($meta['pages'])) {
        $has_more = $meta['pageReturned'] < $meta['pages'];
    }
    
    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $has_more,
        'total' => isset($meta['count']) ? $meta['count'] : 0,
        'page' => isset($meta['pageReturned']) ? $meta['pageReturned'] : $page
    ));
}

/**
 * Render a single grid item for a pet
 * 
 * @param array $pet Pet data
 * @param array $included Included related data
 * @return string HTML for grid item
 */
function paf_render_grid_item($pet, $included) {
    $pet_id = $pet['id'];
    $attributes = $pet['attributes'];
    
    $name = isset($attributes['name']) ? esc_html($attributes['name']) : 'Unknown';
    $breed = paf_get_breed_names($pet, $included);
    $image_url = paf_get_primary_image($pet, $included, 'large');
    $detail_url = paf_get_pet_detail_url($pet_id);
    
    $age = isset($attributes['ageGroup']) ? esc_html($attributes['ageGroup']) : '';
    $sex = isset($attributes['sex']) ? esc_html($attributes['sex']) : '';
    $size = isset($attributes['sizeGroup']) ? esc_html($attributes['sizeGroup']) : '';
    $distance = isset($attributes['distance']) ? round($attributes['distance'], 1) : null;
    
    $is_pending = isset($attributes['isAdoptionPending']) && $attributes['isAdoptionPending'];
    
    ob_start();
    ?>
    <article class="paf-pet-card">
        <a href="<?php echo esc_url($detail_url); ?>" class="paf-pet-card-link">
            <div class="paf-pet-image-wrapper">
                <img src="<?php echo esc_url($image_url); ?>" 
                     alt="<?php echo esc_attr($name); ?>" 
                     class="paf-pet-image"
                     loading="lazy">
                
                <?php if ($is_pending): ?>
                    <span class="paf-pet-status-badge pending">Pending</span>
                <?php else: ?>
                    <span class="paf-pet-status-badge available">Available</span>
                <?php endif; ?>
            </div>
            
            <div class="paf-pet-content">
                <h3 class="paf-pet-name"><?php echo $name; ?></h3>
                
                <?php if (!empty($breed)): ?>
                    <p class="paf-pet-breed"><?php echo esc_html($breed); ?></p>
                <?php endif; ?>
                
                <div class="paf-pet-meta">
                    <?php if ($age): ?>
                        <span class="paf-pet-meta-item">
                            <svg class="paf-pet-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php echo $age; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($sex): ?>
                        <span class="paf-pet-meta-item">
                            <svg class="paf-pet-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <?php echo $sex; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($size): ?>
                        <span class="paf-pet-meta-item">
                            <svg class="paf-pet-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <?php echo $size; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($distance !== null): ?>
                        <span class="paf-pet-meta-item">
                            <svg class="paf-pet-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            <?php echo $distance; ?> mi
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Merge results from dogs and cats searches
 * 
 * @param array|WP_Error $dogs_response Dogs search results
 * @param array|WP_Error $cats_response Cats search results
 * @return array Merged results
 */
function paf_merge_pet_results($dogs_response, $cats_response) {
    $merged = array(
        'data' => array(),
        'included' => array(),
        'meta' => array(
            'count' => 0,
            'countReturned' => 0,
            'pageReturned' => 1,
            'pages' => 1
        )
    );
    
    // Add dogs
    if (!is_wp_error($dogs_response) && isset($dogs_response['data'])) {
        $merged['data'] = array_merge($merged['data'], $dogs_response['data']);
        $merged['included'] = array_merge($merged['included'], $dogs_response['included']);
        $merged['meta']['count'] += $dogs_response['meta']['count'];
    }
    
    // Add cats
    if (!is_wp_error($cats_response) && isset($cats_response['data'])) {
        $merged['data'] = array_merge($merged['data'], $cats_response['data']);
        $merged['included'] = array_merge($merged['included'], $cats_response['included']);
        $merged['meta']['count'] += $cats_response['meta']['count'];
    }
    
    // Sort by distance if available
    usort($merged['data'], function($a, $b) {
        $dist_a = isset($a['attributes']['distance']) ? $a['attributes']['distance'] : 9999;
        $dist_b = isset($b['attributes']['distance']) ? $b['attributes']['distance'] : 9999;
        return $dist_a - $dist_b;
    });
    
    $merged['meta']['countReturned'] = count($merged['data']);
    
    return $merged;
}
```

**Testing Steps:**
1. Load page with search grid
2. Verify initial pets load automatically
3. Test filter changes update results
4. Test tab switching (Dogs/Cats/Either)
5. Test infinite scroll loads more pets
6. Verify error handling when API fails
7. Check "no results" message displays correctly

**Deliverable:** Complete filter and infinite scroll functionality working end-to-end.

---

### 4.5 Pet Detail Page Template

**Objective:** Create comprehensive detail page template with availability handling.

**File:** `templates/detail-page.php`

**Implementation:**

```php
<?php
/**
 * Template: detail-page.php
 * Description: Pet detail page with full information
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Variables available: $pet, $included, $is_available
$attributes = $pet['attributes'];
$pet_id = $pet['id'];

$name = isset($attributes['name']) ? esc_html($attributes['name']) : 'Unknown';
$description = isset($attributes['descriptionText']) ? wp_kses_post(nl2br($attributes['descriptionText'])) : '';
$age = isset($attributes['ageGroup']) ? esc_html($attributes['ageGroup']) : '';
$sex = isset($attributes['sex']) ? esc_html($attributes['sex']) : '';
$size = isset($attributes['sizeGroup']) ? esc_html($attributes['sizeGroup']) : '';
$breed = paf_get_breed_names($pet, $included);

$images = paf_get_all_images($pet, $included);
$primary_image = !empty($images) ? $images[0] : null;

$adoption_url = isset($attributes['url']) ? esc_url($attributes['url']) : '';
$adoption_fee = isset($attributes['adoptionFee']) ? esc_html($attributes['adoptionFee']) : '';

// Organization details
$organizations = paf_get_related_data($pet, 'organizations', $included);
$org = !empty($organizations) ? $organizations[0] : null;
$org_name = $org && isset($org['attributes']['name']) ? esc_html($org['attributes']['name']) : '';
$org_url = $org && isset($org['attributes']['url']) ? esc_url($org['attributes']['url']) : '';

// Location details
$locations = paf_get_related_data($pet, 'locations', $included);
$location = !empty($locations) ? $locations[0] : null;
$city = $location && isset($location['attributes']['city']) ? esc_html($location['attributes']['city']) : '';
$state = $location && isset($location['attributes']['state']) ? esc_html($location['attributes']['state']) : '';
$distance = isset($attributes['distance']) ? round($attributes['distance'], 1) : null;

// Share URLs
$detail_url = paf_get_pet_detail_url($pet_id);
$share_text = urlencode("Meet $name - Available for adoption!");
?>

<div class="paf-detail-page">
    
    <!-- Availability Alert -->
    <?php if (!$is_available): ?>
        <div class="paf-availability-alert <?php echo isset($attributes['isAdoptionPending']) && $attributes['isAdoptionPending'] ? 'pending' : 'unavailable'; ?>">
            <svg class="paf-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <strong>Update:</strong> This pet's availability has changed.
                <?php if (isset($attributes['isAdoptionPending']) && $attributes['isAdoptionPending']): ?>
                    <strong><?php echo $name; ?></strong> has an adoption pending!
                <?php else: ?>
                    <strong><?php echo $name; ?></strong> may no longer be available.
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="paf-detail-grid">
        
        <!-- Image Gallery -->
        <div class="paf-image-gallery">
            <?php if ($primary_image): ?>
                <div class="paf-main-image">
                    <img src="<?php echo esc_url($primary_image['attributes']['large']); ?>" 
                         alt="<?php echo esc_attr($name); ?>">
                </div>
            <?php endif; ?>
            
            <?php if (count($images) > 1): ?>
                <div class="paf-thumbnails">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="paf-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                             data-index="<?php echo $index; ?>">
                            <img src="<?php echo esc_url($image['attributes']['small']); ?>" 
                                 alt="<?php echo esc_attr($name); ?> photo <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pet Information -->
        <div class="paf-pet-info">
            
            <h1 class="paf-pet-title"><?php echo $name; ?></h1>
            
            <!-- Meta Grid -->
            <div class="paf-pet-meta-grid">
                <?php if ($breed): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Breed</span>
                        <span class="paf-meta-value"><?php echo $breed; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($age): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Age</span>
                        <span class="paf-meta-value"><?php echo $age; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($sex): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Sex</span>
                        <span class="paf-meta-value"><?php echo $sex; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($size): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Size</span>
                        <span class="paf-meta-value"><?php echo $size; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($adoption_fee): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Adoption Fee</span>
                        <span class="paf-meta-value"><?php echo $adoption_fee; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($distance !== null): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Distance</span>
                        <span class="paf-meta-value"><?php echo $distance; ?> miles</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($city && $state): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Location</span>
                        <span class="paf-meta-value"><?php echo $city; ?>, <?php echo $state; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($org_name): ?>
                    <div class="paf-meta-item">
                        <span class="paf-meta-label">Rescue/Shelter</span>
                        <span class="paf-meta-value">
                            <?php if ($org_url): ?>
                                <a href="<?php echo $org_url; ?>" target="_blank" rel="noopener">
                                    <?php echo $org_name; ?>
                                </a>
                            <?php else: ?>
                                <?php echo $org_name; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <?php if ($description): ?>
                <div class="paf-pet-description">
                    <h2>About <?php echo $name; ?></h2>
                    <?php echo $description; ?>
                </div>
            <?php endif; ?>
            
            <!-- Call to Action -->
            <div class="paf-pet-actions">
                <?php if ($is_available && $adoption_url): ?>
                    <a href="<?php echo $adoption_url; ?>" 
                       class="paf-button-primary" 
                       target="_blank" 
                       rel="noopener">
                        Inquire About <?php echo $name; ?>
                    </a>
                <?php elseif ($is_available): ?>
                    <p class="paf-contact-notice">
                        Contact <?php echo $org_name ? $org_name : 'the rescue organization'; ?> 
                        for adoption information.
                    </p>
                <?php else: ?>
                    <a href="<?php echo home_url('/find-a-pet/'); ?>" 
                       class="paf-button-primary">
                        Browse Available Pets
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Social Share -->
            <div class="paf-share-section">
                <h3 class="paf-share-title">Share <?php echo $name; ?></h3>
                <div class="paf-share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($detail_url); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="paf-share-button facebook">
                        <svg class="paf-share-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </a>
                    
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($detail_url); ?>&text=<?php echo $share_text; ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="paf-share-button twitter">
                        <svg class="paf-share-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                        Twitter
                    </a>
                    
                    <a href="mailto:?subject=<?php echo $share_text; ?>&body=<?php echo urlencode($detail_url); ?>" 
                       class="paf-share-button email">
                        <svg class="paf-share-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email
                    </a>
                </div>
            </div>
            
        </div>
        
    </div>
    
    <!-- Back to Search -->
    <div class="paf-back-navigation">
        <a href="<?php echo home_url('/find-a-pet/'); ?>" class="paf-back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to All Pets
        </a>
    </div>
    
</div>

<script>
// Image gallery functionality
jQuery(document).ready(function($) {
    $('.paf-thumbnail').on('click', function() {
        const $thumbnail = $(this);
        const index = $thumbnail.data('index');
        const images = <?php echo wp_json_encode(array_map(function($img) { 
            return $img['attributes']; 
        }, $images)); ?>;
        
        // Update active state
        $('.paf-thumbnail').removeClass('active');
        $thumbnail.addClass('active');
        
        // Update main image
        $('.paf-main-image img').attr('src', images[index].large);
    });
});
</script>
```

**File:** `templates/pet-not-found.php`

```php
<?php
/**
 * Template: pet-not-found.php
 * Description: Message when pet is no longer available
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// $pet_id is available
?>

<div class="paf-pet-not-found">
    <div class="paf-empty-icon">🐾</div>
    
    <h1>Pet No Longer Available</h1>
    
    <p>We're sorry, but this pet is no longer in our system. This could mean:</p>
    
    <ul>
        <li>✅ The pet has been adopted (great news!)</li>
        <li>📋 The listing has been removed by the organization</li>
        <li>🔄 The pet's information is being updated</li>
    </ul>
    
    <div class="paf-pet-alternatives">
        <h2>Don't Give Up!</h2>
        <p>We have many other wonderful pets looking for homes. Browse our available pets to find your perfect match!</p>
        
        <a href="<?php echo home_url('/find-a-pet/'); ?>" class="paf-button-primary">
            Browse Available Pets
        </a>
    </div>
</div>
```

**Testing Steps:**
1. Access detail page with valid pet ID: `/pet/123456/`
2. Verify all pet information displays correctly
3. Test image gallery thumbnail switching
4. Test share buttons (Facebook, Twitter, Email)
5. Test with invalid pet ID - should show "not found" template
6. Verify organization and location info displays
7. Check mobile responsiveness

**Deliverable:** Complete detail page template with all features working.

---

## Phase 5: Admin Interface

### 5.1 Admin Menu Registration

**Objective:** Create WordPress admin menu and settings page structure.

**File:** `includes/admin-menu.php`

**Implementation:**

```php
<?php
/**
 * File: admin-menu.php
 * Description: WordPress admin menu registration
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register admin menu
 */
add_action('admin_menu', 'paf_register_admin_menu');
function paf_register_admin_menu() {
    add_options_page(
        'Pet Adoption Finder Settings',  // Page title
        'Pet Adoption Finder',           // Menu title
        'manage_options',                // Capability
        'pet-adoption-finder',           // Menu slug
        'paf_render_settings_page'       // Callback function
    );
}

/**
 * Render main settings page
 */
function paf_render_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
    
    ?>
    <div class="wrap paf-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- Tab Navigation -->
        <nav class="nav-tab-wrapper paf-tab-wrapper">
            <a href="?page=pet-adoption-finder&tab=settings" 
               class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-admin-generic"></span>
                Settings
            </a>
            <a href="?page=pet-adoption-finder&tab=api-log" 
               class="nav-tab <?php echo $active_tab === 'api-log' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span>
                API Error Log
            </a>
            <a href="?page=pet-adoption-finder&tab=readme" 
               class="nav-tab <?php echo $active_tab === 'readme' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-book-alt"></span>
                Documentation
            </a>
        </nav>
        
        <!-- Tab Content -->
        <div class="paf-tab-content">
            <?php
            switch ($active_tab) {
                case 'api-log':
                    paf_render_api_log_tab();
                    break;
                case 'readme':
                    paf_render_readme_tab();
                    break;
                case 'settings':
                default:
                    paf_render_settings_tab();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}
```

**Testing Steps:**
1. Log into WordPress admin
2. Navigate to Settings > Pet Adoption Finder
3. Verify settings page loads
4. Test tab navigation (all three tabs)

**Deliverable:** Admin menu registered, tab navigation working.

---

*[Due to length, I'll continue with the remaining phases in a follow-up artifact. This provides a strong foundation through Phase 5.1]*

---

## Continuation Note

This PRD continues with:
- **Phase 5.2-5.4:** Complete admin settings, API log viewer, and documentation tabs
- **Phase 6:** SEO and social meta tags
- **Phase 7:** Complete design system CSS implementation
- **Phase 8:** Testing procedures
- **Phase 9:** Deployment checklist
- **Appendices:** Quick reference guides and API documentation extracts

Would you like me to continue with the remaining phases in a second artifact?
