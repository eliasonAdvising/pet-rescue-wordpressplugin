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
                    <a href="<?php echo home_url('/'); ?>"
                       class="paf-button-primary">
                        Browse Available Pets
                    </a>
                <?php endif; ?>
            </div>

            <!-- Social Share -->
            <div class="paf-share-section">
                <h3 class="paf-share-title">Share <?php echo $name; ?></h3>
                <div class="paf-share-buttons">
                    <button class="paf-share-button copy-link" data-url="<?php echo esc_url($detail_url); ?>">
                        <svg class="paf-share-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copy Link
                    </button>

                    <a href="mailto:?subject=<?php echo $share_text; ?>&body=<?php echo urlencode($detail_url); ?>"
                       class="paf-share-button email">
                        <svg class="paf-share-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email
                    </a>
                </div>
                <span class="paf-copy-feedback" style="display:none;">Link copied!</span>
            </div>

        </div>

    </div>

    <!-- Back to Search -->
    <div class="paf-back-navigation">
        <a href="<?php echo home_url('/'); ?>" class="paf-back-link">
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

    // Copy link functionality
    $('.copy-link').on('click', function() {
        const url = $(this).data('url');
        const $feedback = $('.paf-copy-feedback');

        navigator.clipboard.writeText(url).then(function() {
            $feedback.fadeIn().delay(2000).fadeOut();
        });
    });
});
</script>
