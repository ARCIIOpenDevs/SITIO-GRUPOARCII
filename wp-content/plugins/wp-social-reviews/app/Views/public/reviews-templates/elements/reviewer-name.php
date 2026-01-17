<?php use WPSocialReviews\App\Services\Helper; 
    $show_verified_badge = ($enable_verified_badge && $enable_verified_badge != 'false');
    $verified_badge_text = $verified_badge_tooltip_text ?: 'Verified Customer';
?>
<<?php echo esc_attr($tag); ?> <?php Helper::printInternalString(implode(' ', $attrs)); ?>>
    <span class="wpsr-reviewer-name"><?php echo esc_html($reviewer_name); ?></span>
    <?php if ($show_verified_badge) { ?>
        <span v-if="(enableVerifiedBadge !== 'false' || enableVerifiedBadge === 'true') && (isReviewerName === 'true')"
            class="wpsr-verified-review wpsr-tooltip"
            aria-label="<?php echo esc_attr($verified_badge_text); ?>"
            data-tooltip="<?php echo esc_attr($verified_badge_text); ?>">
            <div class="verified-badge-star">
                <div class="checkmark"></div>
            </div>
        </span>
    <?php } ?>
</<?php echo esc_attr($tag); ?>>