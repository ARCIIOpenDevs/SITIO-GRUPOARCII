<?php

namespace IGD;


class Tutor {

	private static $instance = null;

	public function __construct() {

		add_filter( 'tutor_preferred_video_sources', array( $this, 'add_preferred_video_sources' ) );

		// Frontend course builder scripts
		add_action( 'tutor_before_course_builder_load', [ $this, 'enqueue_scripts' ] );
		add_action( 'tutor_should_load_dashboard_styles', [ $this, 'enqueue_scripts' ] );

		// Dashboard settings nav
		add_filter( 'tutor_dashboard/nav_items/settings/nav_items', array( $this, 'add_dashboard_settings_nav' ) );

		// Add current user account data to the localize object
		add_filter( 'igd_localize_data', [ $this, 'localize_data' ], 10, 2 );

		// Update auth state with the user_id
		add_filter( 'igd_auth_state', [ $this, 'auth_state' ] );

		//Handle instructor authorization
		add_action( 'template_redirect', [ $this, 'handle_authorization' ] );

	}

	/**
	 * Check if authorization action is set
	 */
	public function handle_authorization() {

		if ( ! $this->is_dashboard_settings_page() ) {
			return;
		}

		if ( empty( $_GET['action'] ) || 'igd-tutor-authorization' !== $_GET['action'] ) {
			return;
		}

		//check if vendor is logged in
		if ( ! is_user_logged_in() ) {
			return;
		}

		$client = Client::instance();

		$client->create_access_token();

		$redirect = tutor_utils()->get_tutor_dashboard_page_permalink( 'settings/google-drive' );


		echo '<script type="text/javascript">window.opener.parent.location.href = "' . $redirect . '"; window.close();</script>';
		die();

	}

	public function add_dashboard_settings_nav( $nav_items ) {
		$nav_items['google-drive'] = array(
			'url'   => tutor_utils()->get_tutor_dashboard_page_permalink( 'settings/google-drive' ),
			'title' => __( 'Google Drive', 'integrate-google-drive' ),
			'role'  => 'instructor',
		);

		return $nav_items;
	}

	public function localize_data( $data, $script_handle ) {

		if ( 'frontend' == $script_handle ) {
			return $data;
		}

		global $wp_query;
		$should_localize = isset( $wp_query->query_vars['tutor_dashboard_page'] );

		if ( is_admin() ) {
			if ( ! current_user_can( 'administrator' ) ) {
				$user  = wp_get_current_user();
				$roles = $user->roles;
				if ( in_array( 'tutor_instructor', $roles ) ) {
					$should_localize = true;
				}
			}
		}

		if ( $should_localize ) {
			$data['authUrl']       = Client::instance()->get_auth_url();
			$data['accounts']      = base64_encode( json_encode( Account::instance( get_current_user_id() )->get_accounts() ) );
			$data['activeAccount'] = base64_encode( json_encode( Account::instance( get_current_user_id() )->get_active_account() ) );
		}


		return $data;
	}

	public function auth_state( $state ) {
		$should_auth = false;

		if ( is_admin() ) {
			if ( ! current_user_can( 'administrator' ) ) {
				$should_auth = true;
			}
		} else {
			$should_auth = $this->is_dashboard_settings_page() || 'frontend' == tutor_utils()->get_course_builder_screen();
		}

		if ( $should_auth ) {
			$state = tutor_utils()->get_tutor_dashboard_page_permalink( 'settings/google-drive' ) . '?action=igd-tutor-authorization&user_id=' . get_current_user_id();
		}

		return $state;
	}

	public function enqueue_scripts( $is_dashboard = false ) {

		if ( ! $is_dashboard && ! is_admin() ) {
			return $is_dashboard;
		}

		Enqueue::instance()->admin_scripts( '', false );

		wp_enqueue_script( 'igd-tutor', IGD_ASSETS . '/js/tutor.js', [ 'igd-admin' ], IGD_VERSION, true );

		return $is_dashboard;
	}

	public function is_dashboard_settings_page() {
		global $wp_query;

		return isset( $wp_query->query_vars['tutor_dashboard_sub_page'] ) && 'google-drive' == $wp_query->query_vars['tutor_dashboard_sub_page'];
	}

	public function add_preferred_video_sources( $sources ) {
		$sources['google_drive'] = [
			'title' => 'Google Drive',
			'icon'  => 'tutor-icon-brand-google-drive',
		];

		return $sources;
	}


	public
	static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}

Tutor::instance();