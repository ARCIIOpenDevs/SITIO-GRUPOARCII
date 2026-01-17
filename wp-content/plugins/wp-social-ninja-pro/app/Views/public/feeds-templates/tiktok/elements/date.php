<?php
use WPSocialReviews\Framework\Support\Arr;
$createdAt = Arr::get($feed, 'created_at', '');
?>
<span class="wpsr-tiktok-feed-time">
    <?php
    $created_at = $createdAt;
    // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
    echo sprintf(__('%s ago'), human_time_diff($created_at));
    ?>
</span>