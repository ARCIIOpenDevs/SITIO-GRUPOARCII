<?php
/**
 * Addon Name: LoginPress - Hide Login
 * Description: LoginPress is the best <code>wp-login</code> Hide Login plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to hide the wp-login.php.
 *
 * @package LoginPress
 * @category Core
 * @author WPBrigade
 * @version 3.2.0
 */

if ( ! class_exists( 'LoginPress_HideLogin' ) ) :

	/**
	 * LoginPress_HideLogin Class
	 */
	class LoginPress_HideLogin {

		private $current_page_url;
		private $settings_page_url_part;
		private $options_page_url;

		/**
		 * Class constructor
		 *
		 * @since 1.0.0
		 * @version 3.0.0
		 */
		public function __construct() {

			if ( LoginPress_Pro::addon_wrapper( 'hide-login' ) ) {
				$this->define_constants();
				$this->hooks();
			}
		}

		/**
		 * Hook into actions and filters
		 *
		 * @since  1.1.4
		 * @version 3.2.0
		 */
		public function hooks() {
			// Only add filters if the user is logged out or if the URL contains "loginpress-settings" or is "options.php".
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
			$this->current_page_url = (is_ssl() ? 'https://' : 'http://') . $host . $request_uri;
			$this->settings_page_url_part = 'loginpress-settings';
			$this->options_page_url = home_url('/wp-admin/options.php');
			add_action('plugins_loaded', array($this, 'loginpress_hidelogin_instance'), 25, 2);
			$loginpress_hidelogin = get_option('loginpress_hidelogin');
			$rename_login_slug = isset($loginpress_hidelogin['rename_login_slug']) ? $loginpress_hidelogin['rename_login_slug'] : '';
			
			if (!empty($rename_login_slug)) {
				add_filter('retrieve_password_message', array($this, 'loginpress_encode_login_url_message'), 10, 4);
				add_filter('wp_new_user_notification_email', array($this, 'loginpress_encode_new_user_login_url'), 10, 3);
			}
		}

		/**
		 * Create the instance of the plugin
		 *
		 * @since 1.0.0
		 * @version 3.0.0
		 */
		public function loginpress_hidelogin_instance() {
			//if (!is_user_logged_in() || strpos($this->current_page_url, $this->settings_page_url_part) !== false || $this->current_page_url === $this->options_page_url || wp_doing_ajax())  {
				// Makes sure the plugin is defined before trying to use it.
				if ( is_multisite() && ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'is_plugin_active' ) ) {
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
				}

				if ( is_plugin_active_for_network( 'rename-wp-login/rename-wp-login.php' ) || is_plugin_active_for_network( 'wps-hide-login/wps-hide-login.php' ) ) {
					$addons_array = get_option( 'loginpress_pro_addons' );

					$addons_array['hide-login']['is_active'] = false;
					update_option( 'loginpress_pro_addons', $addons_array );

					add_action( 'network_admin_notices', array( $this, 'admin_notices_plugin_conflict' ) );
					if ( isset( $_GET['activate'] ) ) {  // @codingStandardsIgnoreLine.
						unset( $_GET['activate'] ); // @codingStandardsIgnoreLine.
					}
					return;
				}

				if ( is_plugin_active( 'rename-wp-login/rename-wp-login.php' ) || is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {

					$addons_array = get_option( 'loginpress_pro_addons' );

					$addons_array['hide-login']['is_active'] = false;
					update_option( 'loginpress_pro_addons', $addons_array );
					add_action( 'admin_notices', array( $this, 'admin_notices_plugin_conflict' ) );
					if ( isset( $_GET['activate'] ) ) { // @codingStandardsIgnoreLine.
						unset( $_GET['activate'] ); // @codingStandardsIgnoreLine.
					}
					return;
				}

				// Call the function.
				$this->loginpress_hidelogin_loader();
			//}
		}

		/**
		 * Callback function to rawurlencode user in reset link.
		 *
		 * @param string  $message    Email message body.
		 * @param string  $key        Password reset unique key for user.
		 * @param string  $user_login The username for the user.
		 * @param WP_User $user_data  WP_User object.
		 * @since 3.2.0
		 */
		public function loginpress_encode_login_url_message( $message, $key, $user_login, $user_data ) {
			if (!is_user_logged_in() || strpos($this->current_page_url, $this->settings_page_url_part) !== false || $this->current_page_url === $this->options_page_url || wp_doing_ajax()) {
				$encode_user_login = rawurlencode( $user_login );
				$last_uname_pos    = strrpos( $message, $user_login );
				$message           = substr_replace( $message, $encode_user_login, $last_uname_pos, strlen( $user_login ) );
				return $message;
			}
		}

		/**
		 * Callback function to rawurlencode user in new user email notification link.
		 *
		 * @param string  $message    Email message body.
		 * @param WP_User $user       WP_User object.
		 * @param string  $blogname   The site title.
		 * @return string             Modified email message body.
		 * @since 3.2.0
		 */
		public function loginpress_encode_new_user_login_url( $message, $user, $blogname ) {
			if (!is_user_logged_in() || strpos($this->current_page_url, $this->settings_page_url_part) !== false || $this->current_page_url === $this->options_page_url || wp_doing_ajax()) {
				$encode_user_login  = rawurlencode( $user->user_login );
				$last_uname_pos     = strrpos( $message['message'], $user->user_login );
				$message['message'] = substr_replace( $message['message'], $encode_user_login, $last_uname_pos, strlen( $user->user_login ) );
				return $message;
			}
		}


		/**
		 * Returns the main instance of WP to prevent the need to use globals.
		 *
		 * @since  1.0.0
		 * @version 3.0.0
		 *
		 * @return object LoginPress_HideLogin_Main
		 */
		public function loginpress_hidelogin_loader() {
			include_once LOGINPRESS_HIDE_ROOT_PATH . '/classes/class-loginpress-hidelogin.php';
			return LoginPress_HideLogin_Main::instance();
		}

		/**
		 * Hide Login Conflict Notice.
		 *
		 * @return void
		 */
		public function admin_notices_plugin_conflict() {

			echo '<div class="error notice is-dismissible"><p>' . esc_html__( 'LoginPress Hide Login could not be activated because you already have another Hide Login plugin active. Please uninstall the other Hide Login Plugin to use LoginPress Hide Login', 'loginpress-pro' ) . '</p></div>';
		}


		/**
		 * Define constants for LoginPress HideLogin
		 *
		 * @since 1.1.4
		 * @version 3.0.0
		 * @return void
		 */
		private function define_constants() {

			LoginPress_Pro_Init::define( 'LOGINPRESS_HIDE_ROOT_PATH', __DIR__ );
			LoginPress_Pro_Init::define( 'LOGINPRESS_HIDE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			LoginPress_Pro_Init::define( 'LOGINPRESS_HIDE_ROOT_FILE', __FILE__ );
		}
	}

endif;

new LoginPress_HideLogin();
