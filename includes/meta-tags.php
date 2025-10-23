<?php
/**
 * File: meta-tags.php
 * Description: SEO and social sharing meta tags
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta tags to pet detail pages
 */
add_action('wp_head', 'paf_add_detail_meta_tags');
function paf_add_detail_meta_tags() {
    $pet_id = get_query_var('pet_id');

    if (empty($pet_id)) {
        return;
    }

    // Get pet data
    $response = paf_get_pet_by_id($pet_id, true);

    if (is_wp_error($response) || $response === null) {
        return;
    }

    $pet = isset($response['data']) ? $response['data'] : null;
    $included = isset($response['included']) ? $response['included'] : array();

    if (empty($pet)) {
        return;
    }

    $attributes = $pet['attributes'];
    $name = isset($attributes['name']) ? esc_attr($attributes['name']) : 'Adoptable Pet';
    $description = isset($attributes['descriptionText']) ? esc_attr(wp_trim_words($attributes['descriptionText'], 30)) : '';
    $image_url = paf_get_primary_image($pet, $included, 'large');
    $detail_url = paf_get_pet_detail_url($pet_id);
    $breed = paf_get_breed_names($pet, $included);

    // Build description
    $meta_description = $name;
    if ($breed) {
        $meta_description .= ' - ' . $breed;
    }
    if ($description) {
        $meta_description .= '. ' . $description;
    }

    ?>
    <!-- Pet Adoption Finder Meta Tags -->

    <!-- General Meta -->
    <meta name="description" content="<?php echo esc_attr($meta_description); ?>">
    <meta name="keywords" content="pet adoption, adopt <?php echo esc_attr(strtolower($name)); ?>, <?php echo esc_attr($breed); ?>, adoptable pets">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
    <meta property="og:title" content="<?php echo esc_attr($name); ?> - Available for Adoption">
    <meta property="og:description" content="<?php echo esc_attr($meta_description); ?>">
    <meta property="og:image" content="<?php echo esc_url($image_url); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?php echo esc_url($detail_url); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($name); ?> - Available for Adoption">
    <meta name="twitter:description" content="<?php echo esc_attr($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($image_url); ?>">

    <!-- Pinterest -->
    <meta property="og:image:alt" content="<?php echo esc_attr($name); ?> - Adoptable Pet">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "<?php echo esc_js($name); ?>",
        "description": "<?php echo esc_js($meta_description); ?>",
        "image": "<?php echo esc_url($image_url); ?>",
        "url": "<?php echo esc_url($detail_url); ?>",
        "offers": {
            "@type": "Offer",
            "availability": "https://schema.org/InStock",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>

    <!-- End Pet Adoption Finder Meta Tags -->
    <?php
}

/**
 * Filter page title for pet detail pages
 */
add_filter('pre_get_document_title', 'paf_filter_page_title', 99);
function paf_filter_page_title($title) {
    $pet_id = get_query_var('pet_id');

    if (empty($pet_id)) {
        return $title;
    }

    // Get pet data (use cached version for performance)
    $response = paf_get_pet_by_id($pet_id, false);

    if (is_wp_error($response) || $response === null) {
        return $title;
    }

    $pet = isset($response['data']) ? $response['data'] : null;

    if (empty($pet)) {
        return $title;
    }

    $name = isset($pet['attributes']['name']) ? esc_html($pet['attributes']['name']) : 'Adoptable Pet';

    return $name . ' - Available for Adoption | ' . get_bloginfo('name');
}
