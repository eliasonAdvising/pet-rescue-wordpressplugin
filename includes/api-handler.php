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
        'timeout' => 30
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
        'timeout' => 30,
        'method' => 'POST'
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
                'response_body' => substr($body, 0, 500)
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
    $cache_key = 'paf_search_' . md5(serialize(array($pet_type, $filters, $page, $limit)));
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
 * @return array|WP_Error|null Pet data, error, or null if not found
 */
function paf_get_pet_by_id($pet_id, $force_refresh = true) {
    // Sanitize pet ID
    $pet_id = sanitize_text_field($pet_id);

    // IMPORTANT: Detail pages should ALWAYS fetch fresh data
    // This ensures users never see outdated availability status
    if (!$force_refresh) {
        $cache_key = 'paf_pet_detail_' . $pet_id;
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
        $cache_key = 'paf_pet_detail_' . $pet_id;
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

/**
 * Get breeds list from API for dropdown
 *
 * @param string $pet_type 'dogs' or 'cats'
 * @return array|WP_Error Array of breed names or error
 */
function paf_get_breeds_list($pet_type) {
    // Check cache first (breeds rarely change)
    $cache_key = 'paf_breeds_' . $pet_type;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $endpoint = '/public/animals/breeds/' . $pet_type . '/';
    $response = paf_api_get($endpoint);

    if (is_wp_error($response)) {
        return $response;
    }

    // Extract breed names
    $breeds = array();
    if (!empty($response['data'])) {
        foreach ($response['data'] as $breed) {
            if (!empty($breed['attributes']['name'])) {
                $breeds[] = $breed['attributes']['name'];
            }
        }
    }

    // Sort alphabetically
    sort($breeds);

    // Cache for 24 hours
    set_transient($cache_key, $breeds, 24 * HOUR_IN_SECONDS);

    return $breeds;
}
