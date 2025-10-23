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
