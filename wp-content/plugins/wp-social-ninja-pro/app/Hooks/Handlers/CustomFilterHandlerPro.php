<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;


use WPSocialReviews\App\Services\Helper as GlobalHelper;

class CustomFilterHandlerPro
{

    public function feedsByRandom($feeds)
    {
        if (!is_array($feeds) || count($feeds) < 2) {
            return $feeds;
        }

        $keys = array_keys($feeds);
        shuffle($keys);

        $randomFeeds = [];
        foreach ($keys as $key) {
            $randomFeeds[$key] = $feeds[$key];
        }

        return $randomFeeds;
    }

    public function includeOrExcludeFeed($hasIncludeWord, $includesWords, $post_caption)
    {
        $post_caption = trim($post_caption, " ");
        if(empty($post_caption)) {
            return false;
        }

        $hasIncludeWord = false;
        foreach ($includesWords as $includeWord) {
            if (!empty($includeWord)) {
                $modified_include_word = trim(str_replace('+', ' ',
                    urlencode(str_replace(array('#', '@'), array(' HASHTAG', ' MENTION'), strtolower($includeWord)))));

                if (preg_match('/\b' . $modified_include_word . '\b/i', $post_caption, $matches)) {
                    $hasIncludeWord = true;
                    break;
                }
            }
        }

        return $hasIncludeWord;
    }

    public function hideFeed($hidePostIds, $feedId)
    {
        $hasHidePost = false;
        foreach ($hidePostIds as $id) {
            if (!empty($id) && !empty($feedId)) {
                if ($id === $feedId) {
                    $hasHidePost = true;
                    break;
                } elseif (strpos($feedId, $id) !== false) {
                    $hasHidePost = true;
                    break;
                }
            }
        }

        return $hasHidePost;
    }

    public function updateDisplayUserOnlineStatus($settings)
    {
        $days = array(
            __('Saturday', 'wp-social-ninja-pro'),
            __('Sunday', 'wp-social-ninja-pro'),
            __('Monday', 'wp-social-ninja-pro'),
            __('Tuesday', 'wp-social-ninja-pro'),
            __('Wednesday', 'wp-social-ninja-pro'),
            __('Thursday', 'wp-social-ninja-pro'),
            __('Friday', 'wp-social-ninja-pro')
        );

        //day params
        $dataParams                    = array();
        $dataParams['dayTimeSchedule'] = isset($settings['day_time_schedule']) ? $settings['day_time_schedule'] : 'false';
        $dataParams['dayLists']        = isset($settings['day_list']) ? $settings['day_list'] : $days;

        //time params
        $dataParams['timeSchedule'] = isset($settings['time_schedule']) ? $settings['time_schedule'] : 'false';
        $dataParams['startTime']    = isset($settings['start_time']) ? $settings['start_time'] : '';
        $dataParams['endTime']      = isset($settings['end_time']) ? $settings['end_time'] : '';

        return $dataParams;
    }

    public function loadTemplateAssets($templateId)
    {
        if(!in_array($templateId, \WPSocialReviews\App\Services\Helper::$loadedTemplates)){
            (new \WPSocialReviewsPro\App\Services\TemplateCssHandler())->renderTemplateCss($templateId);
            GlobalHelper::$loadedTemplates[] = $templateId;
        }
    }

    public function loadTemplateAssetsInWpHead()
    {
        global $post;
        $post_id = isset($post) && isset($post->ID) ? $post->ID : null;

        if (!is_a($post, 'WP_Post')) {
            return;
        }

        $has_wpsn_ids = get_post_meta($post_id, '_wpsn_ids', true);
        if ($has_wpsn_ids) {
            if ($ids = GlobalHelper::getShortCodeIds($post->post_content)) {
                foreach ($ids as $id) {
                    do_action('wpsocialreviews/load_template_assets', $id);
                }
            }

        }
    }
}