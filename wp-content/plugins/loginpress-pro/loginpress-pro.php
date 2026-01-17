<?php
/**
 * Plugin Name: LoginPress Pro
 * Plugin URI: https://loginpress.pro?utm_source=loginpress-pro&utm_medium=plugins&utm_campaign=loginpress-home&utm_content=plugin-uri
 * Description: LoginPress Pro adds premium features in LoginPress core/free plugin.
 * Version: 5.0.2
 * Author: WPBrigade
 * Author URI: https://wpbrigade.com/?utm_source=loginpress-pro&utm_medium=plugins&utm_campaign=wpbrigade-home&utm_content=author-uri
 * License: GPLv2+
 * Text Domain: loginpress-pro
 * Domain Path: /languages
 * Requires Plugins: loginpress
 * GitHub Plugin URI: https://github.com/WPBrigade/loginpress-pro
 * 
 * @package LoginPress-pro
 */

update_option( 'loginpress_pro_license_key', 'B5E0B5F8DD8689E6ACA49DD6E6E1A930' );

add_filter('pre_http_request', function($preempt, $parsed_args, $url) {
    if ($parsed_args['method'] === 'POST' && $url === 'https://wpbrigade.com/') {
        if (isset($parsed_args['body']) && is_array($parsed_args['body'])) {
            $parsed_args['body']['edd_action'] = 'activate_license';
        } else {
            $parsed_args['body'] = array('edd_action' => 'activate_license');
        }

        $response = array(
            "headers" => array(),
            "body" => json_encode(array(
                "success" => true,
                "license" => "valid",
                "item_id" => "1837",
                "item_name" => "LoginPress Pro",
                "license_limit" => 10,
                "site_count" => 1,
                "expires" => "lifetime",
                "activations_left" => 9,
                "checksum" => "GPL001122334455AA6677BB8899CC000",
                "payment_id" => 123321,
                "customer_name" => "GPL",
                "customer_email" => "noreply@gmail.com",
                "price_id" => "9"
            )),
            "response" => array(
                "code" => 200,
                "message" => "OK"
            )
        );

        return $response;
    }

    return $preempt;
}, 10, 3);

if ( ! class_exists( 'LoginPress_Pro_Init' ) ) :

	/**
	 * LoginPress Pro Initialization Class
	 *
	 * @version 3.0.0
	 */
	class LoginPress_Pro_Init {

		/**
		 * Version number
		 *
		 * @var string
		 */
		public $version = '5.0.2';

		/**
		 * Instance variable
		 *
		 * @var [bool] $instance
		 * @since 1.0.0
		 */
		private static $instance = null;

		/**
		 * Constructor Function
		 *
		 * @version 3.0.0
		 * @since 1.0.0
		 */
		private function __construct() {

			$this->define_constants();
			$this->hooks();
		}

		/**
		 * Define LoginPress Constants
		 */
		private function define_constants() {

			$this::define( 'LOGINPRESS_PRO_ADDONS_DIR', plugin_dir_url( __FILE__ ) . 'addons' );
			$this::define( 'LOGINPRESS_PRO_DIR_URL', plugin_dir_url( __FILE__ ) );
			$this::define( 'LOGINPRESS_PRO_ROOT_PATH', __DIR__ );
			$this::define( 'LOGINPRESS_PRO_UPGRADE_PATH', __FILE__ );
			$this::define( 'LOGINPRESS_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this::define( 'LOGINPRESS_PRO_THEME', LOGINPRESS_PRO_ROOT_PATH . '/themes/' );
			$this::define( 'LOGINPRESS_PRO_DIR_PATH', plugin_dir_path( __FILE__ ) );
			$this::define( 'LOGINPRESS_PRO_PLUGIN_ROOT', dirname( plugin_basename( __FILE__ ) ) );
			$this::define( 'LOGINPRESS_PRO_STORE_URL', 'https://WPBrigade.com' );
			$this::define( 'LOGINPRESS_PRO_PRODUCT_NAME', 'LoginPress Pro' );
			$this::define( 'LOGINPRESS_PRO_VERSION', $this->version );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string $name Name of the constant.
		 * @param string $value the value of the constant.
		 * @return void
		 */
		public static function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @version 3.0.0
		 */
		public function hooks() {

			add_action( 'plugins_loaded', array( $this, 'loginpress_instance' ), 20 );
			register_deactivation_hook( __FILE__, array( $this, 'loginpress_deactivate' ) );
			add_action( 'wp_ajax_loginpress_activate_free', array( $this, 'loginpress_plugin_activation' ) );
			add_action('admin_notices',  array( $this, 'loginpress_show_update_notice'));
		}

		/**
		 * LoginPress Instance
		 *
		 * @return void
		 */
		public function loginpress_instance() {

			add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_pro_admin_action_scripts' ) );

			// Makes sure the plugin is defined before trying to use it.
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			if ( ! class_exists( 'LoginPress' ) && file_exists( WP_PLUGIN_DIR . '/loginpress/loginpress.php' ) ) {
				add_action( 'admin_notices', array( $this, 'loginpress_activate_free_activation' ) );
			} elseif ( ! file_exists( WP_PLUGIN_DIR . '/loginpress/loginpress.php' ) ) {
				add_action( 'admin_notices', array( $this, 'lp_update_free' ) );
			}
			if ( is_multisite() && is_plugin_active_for_network( 'loginpress/loginpress.php' ) ) { // @codingStandardsIgnoreLine.
				// Plugin is activated.
			} elseif ( ! class_exists( 'LoginPress' ) ) {
				add_action( 'admin_menu', array( $this, 'loginpress_pro_register_action_page' ) );
				return;
			}

			if ( ! class_exists( 'LoginPress' ) ) {
				return;
			}

			// Add 3.0 into notice
			// delete_site_option('loginpress_pro_intro_dismiss');.
			$dismissed = isset( $_GET['loginpress_pro_intro_dismiss'] ) && ! empty( $_GET['loginpress_pro_intro_dismiss'] ) ? $_GET['loginpress_pro_intro_dismiss'] : false; // @codingStandardsIgnoreLine.

			if ( false !== get_site_option( 'loginpress_pro_intro_dismiss' ) || $dismissed ) {
				$this->loginpress_pro_notice_dismiss( 'loginpress-pro-intro-dismiss-nonce', 'loginpress_pro_intro_dismiss' );
			}

			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-setup-30.php';
			new LoginPress_Pro_Setup_30( true );

			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-main.php';
			new LoginPress_Pro();

		}

		/**
		 * Update loginpress to 4.0.0 notice on loginpress pages.
		 *
		 * @since 4.0.0
		 */
		function loginpress_show_update_notice(){
			if (isset($_GET['page']) && strpos($_GET['page'], 'loginpress') === 0 && version_compare( LOGINPRESS_VERSION, '4.0.0', '<' )) {
				$update_url = admin_url( 'update.php?action=upgrade-plugin&plugin=loginpress%2Floginpress.php&_wpnonce=' . wp_create_nonce( 'upgrade-plugin_loginpress/loginpress.php' ) );
				// Output the dismissible notification
				?>
				<div class="loginpress-notification-bar" >
					<p>You're using an outdated version of LoginPress. To unlock the settings, consider <a href="<?php echo esc_url( $update_url ); ?>" target="_self">
                    updating to the latest version
                </a>.</p>
				</div>
				<?php
			}
		}


		/**
		 * Notice if LoginPress Free is not activate.
		 *
		 * @since 3.0.6
		 */
		public function loginpress_activate_free_activation() {

			$action = 'activate';
			$slug   = 'loginpress/loginpress.php';
			$link   = wp_nonce_url(
				add_query_arg(
					array(
						'action' => $action,
						'plugin' => $slug,
					),
					admin_url( 'plugins.php' )
				),
				$action . '-plugin_' . $slug
			);

			printf(
				'<div class="notice notice-error is-dismissible">
			<p>%1$s<a href="%2$s" style="text-decoration:none">%3$s</a></p></div>',
				esc_html__( 'LoginPress Free is needed for LoginPress Pro &mdash; ', 'loginpress-pro' ),
				$link,
				esc_html__( 'Click here to activate LoginPress Free', 'loginpress-pro' )
			);
		}

		/**
		 * Check and Dismiss addon message.
		 *
		 * @param string $nonce nonce value.
		 * @param string $option option name.
		 * @since 1.1.3
		 * @version 3.0.0
		 * @return void
		 */
		private function loginpress_pro_notice_dismiss( $nonce, $option ) {

			// delete_site_option( $option );.
			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), $nonce ) ||
				! isset( $_GET[ $option ] ) ) {

				return;
			}

			add_site_option( $option, 'yes' );
		}


		/**
		 * Enqueue Admin Scripts
		 *
		 * @param [type] $hook current admin page.
		 * @return void
		 * @version 3.2.0
		 */
		public function loginpress_pro_admin_action_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' === $hook || 'users.php' === $hook ) {

				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'loginpress-admin-action', plugins_url( 'assets/js/admin-action.js', __FILE__ ), array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );

				wp_localize_script(
					'loginpress-admin-action',
					'loginpress_pro_local',
					array(
						'update_nonce' => wp_create_nonce( 'updates' ),
						'active_nonce' => wp_create_nonce( 'loginpress_active_free' ),
						'admin_url'    => admin_url( 'admin.php?page=loginpress-settings' ),
						'search_nonce' => wp_create_nonce( 'loginpress_autocomplete_search_nonce' ),
					)
				);

				wp_enqueue_style( 'loginpress-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );

			}
			wp_enqueue_style( 'loginpress-pro-admin-styles', plugins_url( 'assets/css/admin-notifications.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
		}

		/**
		 * Add Menu Page
		 *
		 * @return void
		 */
		public function loginpress_pro_register_action_page() {

			add_menu_page( __( 'LoginPress', 'loginpress-pro' ), __( 'LoginPress', 'loginpress-pro' ), 'manage_options', 'loginpress-settings', array( $this, 'loginpress_pro_main_menu' ), plugins_url( 'assets/img/icon.svg', __FILE__ ), 50 );
		}

		/**
		 * Add pro's main menu
		 *
		 * @version 3.0.0
		 * @return void
		 */
		public function loginpress_pro_main_menu() {

			include_once LOGINPRESS_PRO_ROOT_PATH . '/includes/require-free.php';
		}

		/**
		 * Update loginpress free
		 *
		 * @version 3.0.0
		 * @return void
		 */
		public function lp_update_free() {

			$action = 'install-plugin';
			$slug   = 'loginpress';
			$link   = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => $action,
						'plugin'     => $slug,
						'is_install' => true,
					),
					admin_url( 'update.php' )
				),
				$action . '_' . $slug
			);

			$is_install_request = isset( $_GET['is_install'] ) && sanitize_text_field( $_GET['is_install'] ) === '1' ? false : true;
			if ( $is_install_request ) {
				printf(
					'<div class="notice notice-error is-dismissible">
					<p>%1$s<a href="%2$s" style="text-decoration:none">%3$s</a></p></div>',
					esc_html__( 'Please update LoginPress to latest Free version to enable PRO features &mdash; ', 'loginpress-pro' ),
					esc_url( $link ),
					esc_html__( 'Install now', 'loginpress-pro' )
				);
			}
		}

		/**
		 * LoginPress Deactivation callback
		 *
		 * @return void
		 */
		public function loginpress_deactivate() {

			$selected_preset          = get_option( 'customize_presets_settings', 'minimalist' );
			$loginpress_default_theme = 'default1' === $selected_preset ? 'default1' : 'minimalist';

			update_option( 'customize_presets_settings', $loginpress_default_theme );
		}

		/**
		 * [loginpress_plugin_activation LoginPress (Free) Plugin Activation Callback]
		 *
		 * @since 2.0.7
		 * @version 2.1.6
		 */
		public function loginpress_plugin_activation() {

			check_ajax_referer( 'loginpress_active_free', '_wpnonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			$plugin = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';

			if ( ! is_plugin_active( $plugin ) ) {
				activate_plugin( $plugin );
			}

			wp_die();
		}

		/**
		 * Main Instance
		 *
		 * @since 3.0.0
		 * @static
		 * @see loginPress_pro_loader()
		 * @return Main instance
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
endif;

/**
 * Returns the main instance of WP to prevent the need to use globals.
 *
 * @since  3.0.0
 * @return LoginPress_Pro_Init
 */
function loginpress_pro_loader() {

	return LoginPress_Pro_Init::instance();
}

// Call the function.
loginpress_pro_loader();
