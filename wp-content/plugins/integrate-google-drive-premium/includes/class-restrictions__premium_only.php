<?php

namespace IGD;

class Restrictions {

	private static $instance = null;
	private $limits_type = 'global';
	private $limits_data;

	public function has_reached_download_limit( $file_id = null, $limit_name = 'download' ) {

		if ( ! igd_fs()->can_use_premium_code__premium_only() ) {
			return false;
		}

		// Set user UUID
		$this->set_user_uuid();

		// Set limits data
		$limits_data = $this->get_gloabal_limits_data();

		if ( ! empty( $shortcode_data = Shortcode::get_current_shortcode() ) ) {

			$this->limits_type = $shortcode_data['id'] ?? 'global';

			$limits_data = [
				'enableDownloadLimits'     => $shortcode_data['enableDownloadLimits'] ?? false,
				'restrictionPeriod'        => $shortcode_data['restrictionPeriod'] ?? $limits_data['restrictionPeriod'] ?? 'day',
				'downloadLimits'           => $shortcode_data['downloadLimits'] ?? $limits_data['downloadLimits'] ?? 0,
				'downloadsPerFile'         => $shortcode_data['downloadsPerFile'] ?? $limits_data['downloadsPerFile'] ?? 0,
				'zipDownloadLimits'        => $shortcode_data['zipDownloadLimits'] ?? $limits_data['zipDownloadLimits'] ?? 0,
				'bandwidthLimits'          => $shortcode_data['bandwidthLimits'] ?? $limits_data['bandwidthLimits'] ?? 0,
				'limitExcludedUsers'       => $shortcode_data['limitExcludedUsers'] ?? $limits_data['limitExcludedUsers'] ?? [ 'administrator' ],
				'limitExcludeAllUsers'     => $shortcode_data['limitExcludeAllUsers'] ?? $limits_data['limitExcludeAllUsers'] ?? false,
				'limitExcludedExceptUsers' => $shortcode_data['limitExcludedExceptUsers'] ?? $limits_data['limitExcludedExceptUsers'] ?? [],
			];

		}

		$this->limits_data = $limits_data;

		// early check
		if ( empty( $this->limits_data['enableDownloadLimits'] ) ) {
			return false;
		}

		// Returns if user is excluded from limits
		if ( $this->check_limit_excluded() ) {
			return false;
		}

		// Default values for usage limits
		$default_current_usage = [
			'downloadLimits'    => 0,
			'downloadsPerFile'  => [],
			'zipDownloadLimits' => 0,
			'bandwidthLimits'   => 0,
		];

		// Load the current limits for the user.
		if ( is_user_logged_in() ) {
			$user_id       = get_current_user_id();
			$current_usage = get_user_meta( $user_id, 'igd_usage_limits', true );
		} else if ( $this->get_user_uuid() ) {
			$current_usage = get_transient( 'igd_usage_limits_' . $this->get_user_uuid() );
			$user_id       = null;
		} else {

			// Check if untraceable users are blocked from downloading.
			if ( igd_get_settings( 'blockUntraceableUsers' ) ) {
				return esc_html__( 'Log in to this site or enable cookies in your browser to download content. Then reload this page.', 'integrate-google-drive' );
			}

			$current_usage = null;
			$user_id       = null;
		}


		// Reset the limits every day
		$day_key          = date( 'Ymd' );
		$start_period_key = date( 'Ymd', strtotime( '- 1 ' . $this->limits_data['restrictionPeriod'] ) );

		// Remove usage older than one month
		if ( is_array( $current_usage ) ) {
			foreach ( $current_usage as $date => $value ) {
				if ( (string) $date < $start_period_key ) {
					unset( $current_usage[ $date ] );
				}
			}
		} else {
			$current_usage = [];
		}

		if ( empty( $current_usage ) || ! isset( $current_usage[ $day_key ] ) || ! isset( $current_usage[ $day_key ][ $this->limits_type ] ) ) {
			$current_usage = [
				$day_key => [
					$this->limits_type => $default_current_usage
				],
			];

		}

		// 0: Make totals of all limits in the set usage period
		$totals = [
			'downloadLimits'    => 0,
			'downloadsPerFile'  => [],
			'zipDownloadLimits' => 0,
			'bandwidthLimits'   => 0,
		];

		foreach ( $current_usage as $date => $usage ) {
			if ( ! isset( $usage[ $this->limits_type ] ) ) {
				continue;
			}

			if ( $date > $start_period_key ) {
				$totals['downloadLimits']    += $usage[ $this->limits_type ]['downloadLimits'];
				$totals['zipDownloadLimits'] += $usage[ $this->limits_type ]['zipDownloadLimits'];
				$totals['bandwidthLimits']   += $usage[ $this->limits_type ]['bandwidthLimits'];

				foreach ( $usage[ $this->limits_type ]['downloadsPerFile'] as $file_id => $count ) {
					if ( ! isset( $totals['downloadsPerFile'][ $file_id ] ) ) {
						$totals['downloadsPerFile'][ $file_id ] = 0;
					}
					$totals['downloadsPerFile'][ $file_id ] += $count;
				}
			}
		}

		// 1. Check if the number of downloads is reached.
		if ( 'download' === $limit_name && ! empty( $this->limits_data['downloadLimits'] )
		     && $current_usage[ $day_key ][ $this->limits_type ]['downloadLimits'] >= $this->limits_data['downloadLimits']
		) {
			$error_msg = esc_html__( 'You have reached number of downloads allowed for today. You will be able to download more content tomorrow.', 'integrate-google-drive' );

			$this->do_limit_reached_events( $file_id, $user_id, 'downloadLimits', $error_msg );

			return $error_msg;
		}

		// 2. Check if the number of downloads per file is reached.
		if ( 'download' === $limit_name && ! empty( $file_id ) && ! empty( $this->limits_data['downloadsPerFile'] ) ) {
			if (
				isset( $current_usage[ $day_key ][ $this->limits_type ]['downloadsPerFile'][ $file_id ] )
				&& $current_usage[ $day_key ][ $this->limits_type ]['downloadsPerFile'][ $file_id ] >= $this->limits_data['downloadsPerFile']
			) {
				$error_msg = esc_html__( 'You have reached number of downloads for this file allowed for today. You will be able to download more content tomorrow.', 'integrate-google-drive' );
				$this->do_limit_reached_events( $file_id, $user_id, 'downloadsPerFile', $error_msg );

				return $error_msg;
			}
		}

		// 3. Check if the bandwidth limit is reached
		if ( 'stream' === $limit_name
		     && ! empty( $this->limits_data['bandwidthLimits'] )
		     && $this->limits_data['bandwidthLimits'] > 0
		     && ( $current_usage[ $day_key ][ $this->limits_type ]['bandwidthLimits'] >= igd_return_bytes( $this->limits_data['bandwidthLimits'] . 'MB' ) )
		) {
			$error_msg = esc_html__( 'You have reached the bandwidth limit allowed for today. You will be able to download more content tomorrow.', 'integrate-google-drive' );
			$this->do_limit_reached_events( $file_id, $user_id, 'bandwidthLimits', $error_msg );

			return $error_msg;
		}

		// 4. Check if the number of ZIP downloads is reached.
		if ( 'download_zip' === $limit_name
		     && ! empty( $this->limits_data['zipDownloadLimits'] )
		     && ( $current_usage[ $day_key ][ $this->limits_type ]['zipDownloadLimits'] >= $this->limits_data['zipDownloadLimits'] )
		) {
			$error_msg = esc_html__( 'You have reached the number of ZIP downloads allowed for today. You will be able to download new ZIP files tomorrow.', 'integrate-google-drive' );
			$this->do_limit_reached_events( $file_id, $user_id, 'zipDownloadLimits', $error_msg );

			return $error_msg;
		}

		// Limit is not reached, update current usage
		if ( 'download_zip' === $limit_name ) {
			++ $current_usage[ $day_key ][ $this->limits_type ]['zipDownloadLimits'];
		} else {
			++ $current_usage[ $day_key ][ $this->limits_type ]['downloadLimits'];

			if ( ! empty( $file_id ) ) {
				if ( ! isset( $current_usage[ $day_key ][ $this->limits_type ]['downloadsPerFile'][ $file_id ] ) ) {
					$current_usage[ $day_key ][ $this->limits_type ]['downloadsPerFile'][ $file_id ] = 0;
				}

				++ $current_usage[ $day_key ][ $this->limits_type ]['downloadsPerFile'][ $file_id ];

				if ( $this->limits_data['bandwidthLimits'] > 0 ) {
					$file = App::instance()->get_file_by_id( $file_id );

					$current_usage[ $day_key ][ $this->limits_type ]['bandwidthLimits'] += $file['size'];
				}
			}

		}

		// Save the current usage for the user
		if ( is_user_logged_in() ) {
			update_user_meta( $user_id, 'igd_usage_limits', $current_usage );
		} elseif ( $this->get_user_uuid() ) {
			set_transient( 'igd_usage_limits_' . $this->get_user_uuid(), $current_usage, DAY_IN_SECONDS );
		}

		return false;
	}

	/**
	 * Check if the module login form should display
	 *
	 * @param $shortcode_data
	 *
	 * @return false|void
	 */
	public function should_password_protected( $shortcode_data ) {

		if ( empty( $shortcode_data['enablePasswordProtection'] ) || empty( $shortcode_data['password'] ) ) {
			return false;
		}

		if ( ! empty( $_REQUEST['module_pass'] ) ) {
			if ( $_REQUEST['module_pass'] == $shortcode_data['password'] ) {
				return false;
			}
		}

		return ! get_transient( 'igd_module_password_' . $shortcode_data['id'] . '_' . $this->get_user_uuid() );
	}

	public function set_module_password( $shortcode_id ) {

		if ( ! $this->get_user_uuid() ) {
			return;
		}

		set_transient( 'igd_module_password_' . $shortcode_id . '_' . $this->get_user_uuid(), true, DAY_IN_SECONDS );
	}

	public function get_gloabal_limits_data() {
		return [
			'enableDownloadLimits'     => igd_get_settings( 'enableDownloadLimits', false ),
			'downloadLimits'           => igd_get_settings( 'downloadLimits', 0 ),
			'downloadsPerFile'         => igd_get_settings( 'downloadsPerFile', 0 ),
			'bandwidthLimits'          => igd_get_settings( 'bandwidthLimits', 0 ),
			'limitExcludedUsers'       => igd_get_settings( 'limitExcludedUsers', [ 'administrator' ] ),
			'limitExcludeAllUsers'     => igd_get_settings( 'limitExcludeAllUsers', false ),
			'limitExcludedExceptUsers' => igd_get_settings( 'limitExcludedExceptUsers', [] ),
		];
	}

	public function check_limit_excluded() {

		$excluded_users  = $this->limits_data['limitExcludedUsers'] ?? [ 'administrator' ];
		$excluded_all    = filter_var( $this->limits_data['limitExcludeAllUsers'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$excluded_except = $this->limits_data['limitExcludedExceptUsers'] ?? [];

		$excluded_user_roles = array_filter( $excluded_users, 'is_string' );
		$except_user_roles   = array_filter( $excluded_except, 'is_string' );


		$current_user = wp_get_current_user();

		// if excluded is enabled and user is not in the exception list
		if ( $excluded_all && ! in_array( $current_user->ID, $excluded_except ) && empty( array_intersect( $current_user->roles, $except_user_roles ) ) ) {
			return true;
		}

		// if the excluded users list contains 'everyone' or the user's role or the user's ID
		if ( in_array( 'everyone', $excluded_user_roles ) || ! empty( array_intersect( $current_user->roles, $excluded_user_roles ) ) || in_array( $current_user->ID, $excluded_users ) ) {
			return true;
		}

		// if no users specified and either excluded_all is true with no exceptions or excluded_all is false
		if ( $excluded_all && empty( $except_users ) ) {
			return true;
		}

		return false;
	}

	private function do_limit_reached_events( $file_id, $user_id, $limit_type, $error_message ) {
		do_action( 'igd_usage_limits_reached', $file_id, $user_id, $limit_type, $error_message );

		if ( igd_get_settings( 'limitsNotificationEmail' ) && ! empty( $user_id ) ) {
			$this->send_notification( $user_id, $limit_type, $error_message );
		}
	}

	private function send_notification( $user_id, $limit_type, $error_message ) {
		$has_send_email = get_transient( "igd_limits_notification_{$user_id}_$limit_type" );

		if ( ! empty( $has_send_email ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		if ( empty( $user ) ) {
			return;
		}

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_option( 'blogname' ) . ' <' . get_option( 'admin_email' ) . '>',
		];

		$admin_subject = get_bloginfo() . ' | ' . sprintf( esc_html__( 'User %s has reached the download limitation', 'integrate-google-drive' ), $user->display_name );
		$user_subject  = get_bloginfo() . ' | ' . esc_html__( 'You have reached the download limitation', 'integrate-google-drive' );

		$recipients = igd_get_settings( 'limitsNotificationEmail', '%admin_email%' );
		$recipients = explode( ',', $recipients );

		if ( ! empty( $recipients ) ) {

			foreach ( $recipients as $recipient ) {
				$is_user_recipient = $recipient == '%user_email%';
				$subject           = $is_user_recipient ? $user_subject : $admin_subject;

				$recipient = str_replace(
					[ '%admin_email%', '%user_email%' ],
					[ get_option( 'admin_email' ), $user->user_email ],
					$recipient );

				ob_start();
				include IGD_INCLUDES . '/views/limits-email__premium_only.php';
				$content = ob_get_clean();

				wp_mail( $recipient, $subject, $content, $headers );
			}
		}

		set_transient( "igd_limits_notification_{$user_id}_$limit_type", true, DAY_IN_SECONDS );

	}

	public function set_user_uuid() {

		// don't need to set the cookie if the user is logged in
		if ( is_user_logged_in() ) {
			return;
		}

		$cookie_name = 'IGD_UUID';

		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			$uuid = wp_generate_uuid4();
			setcookie( $cookie_name, $uuid, time() + 60 * 60 * 24 * 365, COOKIEPATH, COOKIE_DOMAIN );
		}

	}

	public function get_user_uuid() {

		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}

		if ( ! isset( $_COOKIE['IGD_UUID'] ) ) {
			$this->set_user_uuid();
		}

		return ! empty( $_COOKIE['IGD_UUID'] ) ? sanitize_text_field( $_COOKIE['IGD_UUID'] ) : null;
	}

	public static function display_error( $limit_message ) {
		// Define the error message components
		$image_url   = IGD_ASSETS . '/images/access-denied.png';
		$image_tag   = '<img width="100" src="' . esc_url( $image_url ) . '" style="display: block; margin: 0 auto 20px;" >';
		$title       = '<h3 class="placeholder-title" style="color: #d9534f; text-align: center; font-size: 24px; margin-bottom: 10px;">' . __( 'Usage Limit Reached', 'integrate-google-drive' ) . '</h3>';
		$description = '<p class="placeholder-description" style="text-align: center; font-size: 16px; color: #555;">' . $limit_message . '</p>';

		// Combine the components into the final error message
		$error_message = '<div class="igd-usage-limit-message" style="max-width: 500px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">' . $image_tag . $title . $description . '</div>';

		// Display the error message and terminate the script
		die( $error_message );
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}