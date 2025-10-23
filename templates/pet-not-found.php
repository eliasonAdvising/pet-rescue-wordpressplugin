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

        <a href="<?php echo home_url('/'); ?>" class="paf-button-primary">
            Browse Available Pets
        </a>
    </div>
</div>
