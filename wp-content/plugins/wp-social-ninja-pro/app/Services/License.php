<?php

namespace WPSocialReviewsPro\App\Services;

use WPSocialReviewsPro\App\Services\Libs\PluginManager\LicenseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ActionHooks Class
 * @since 1.1.4
 */
class License
{
    public function init()
    {
        $licenseManager = new LicenseManager();
        $licenseManager->initUpdater();

        add_filter('wpsr_get_license', function ($response, $request) use ($licenseManager) {
            $licenseManager->verifyRemoteLicense(true);

            $data = $licenseManager->getLicenseDetails();

            $status = $data['status'];

            if ($status == 'expired') {
                $data['renew_url'] = $licenseManager->getRenewUrl($data['license_key']);
            }

            $data['purchase_url'] = $licenseManager->getVar('purchase_url');

            unset($data['license_key']);
            return $data;
        }, 10, 2);

        add_filter('wpsr_activate_license', function ($response, $request) use ($licenseManager) {
            $licenseKey = $request->get('license_key');
            $response = $licenseManager->activateLicense($licenseKey);
            if(is_wp_error($response)) {
                return $response;
            }

            return [
                'license_data' => $response,
                'message' => __('Your license key has been successfully updated', 'wp-social-ninja-pro')
            ];
        }, 10, 2);

        add_filter('wpsr_deactivate_license', function ($response, $request) use ($licenseManager) {
            $response = $licenseManager->deactivateLicense();
            if(is_wp_error($response)) {
                return $response;
            }

            unset($response['license_key']);

            return [
                'license_data' => $response,
                'message' => __('Your license key has been successfully deactivated', 'fluentcampaign-pro')
            ];
        }, 10, 2);

        add_action('admin_init', function () use ($licenseManager) {
            $licenseMessage = $licenseManager->getLicenseMessages();
            if ($licenseMessage) {
                add_action('admin_notices', function () use ($licenseMessage) {
                    $class = 'notice notice-error fc_message';
                    $message = $licenseMessage['message'];
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
                });
            }
        });

    }
}