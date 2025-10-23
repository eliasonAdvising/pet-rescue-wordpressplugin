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
            'message' => 'Unable to load pets. Please try again later. Error: ' . $response->get_error_message()
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
        if (isset($dogs_response['included'])) {
            $merged['included'] = array_merge($merged['included'], $dogs_response['included']);
        }
        if (isset($dogs_response['meta']['count'])) {
            $merged['meta']['count'] += $dogs_response['meta']['count'];
        }
    }

    // Add cats
    if (!is_wp_error($cats_response) && isset($cats_response['data'])) {
        $merged['data'] = array_merge($merged['data'], $cats_response['data']);
        if (isset($cats_response['included'])) {
            $merged['included'] = array_merge($merged['included'], $cats_response['included']);
        }
        if (isset($cats_response['meta']['count'])) {
            $merged['meta']['count'] += $cats_response['meta']['count'];
        }
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

/**
 * Load breeds for selected pet type (AJAX)
 */
add_action('wp_ajax_paf_load_breeds', 'paf_load_breeds_ajax');
add_action('wp_ajax_nopriv_paf_load_breeds', 'paf_load_breeds_ajax');

function paf_load_breeds_ajax() {
    check_ajax_referer('pet_finder_nonce', 'nonce');

    $pet_type = isset($_POST['pet_type']) ? sanitize_text_field($_POST['pet_type']) : 'dogs';

    if (!in_array($pet_type, array('dogs', 'cats'))) {
        wp_send_json_error(array('message' => 'Invalid pet type'));
    }

    $breeds = paf_get_breeds_list($pet_type);

    if (is_wp_error($breeds)) {
        wp_send_json_error(array('message' => 'Unable to load breeds'));
    }

    wp_send_json_success(array('breeds' => $breeds));
}
