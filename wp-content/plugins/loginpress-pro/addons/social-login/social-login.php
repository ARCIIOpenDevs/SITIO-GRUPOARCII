<?php
/**
 * Addon Name: LoginPress - Social Login
 * Description: This is a premium add-on of LoginPress WordPress plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to login using social media accounts    like Facebook, Twitter and Google/G+ etc
 *
 * @package loginpress
 * @category Core
 * @author WPBrigade
 * @version 5.0.0
 */

if ( ! class_exists( 'LoginPress_Social' ) ) :

	/**
	 * LoginPress_Social
	 */
	final class LoginPress_Social {

		/**
		 * Is short code used.
		 *
		 * @var bool
		 */
		private $is_shortcode = false;


		/**
		 * The plugin instance
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Table name
		 * 
		 * @var string
		 */
		protected $table_name;

		/**
		 * Class constructor
		 */
		public function __construct() {

			if ( LoginPress_Pro::addon_wrapper( 'social-login' ) ) {
				$this->settings = get_option( 'loginpress_social_logins' );
				global $wpdb;
				$this->table_name = $wpdb->prefix . 'loginpress_social_login_details';
				$this->define_constants();
				$this->hooks();
			}
		}

		/**
		 * The settings array
		 *
		 * @var array
		 */
		public $settings;

		/**
		 * Define LoginPress Constants
		 *
		 * @version 3.0.0
		 */
		private function define_constants() {

			LoginPress_Pro_Init::define( 'LOGINPRESS_SOCIAL_DIR_PATH', plugin_dir_path( __FILE__ ) );
			LoginPress_Pro_Init::define( 'LOGINPRESS_SOCIAL_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Hook into actions and filters
		 *
		 * @version 3.0.0
		 */
		private function hooks() {

			$enable   = isset( $this->settings['enable_social_login_links'] ) ? $this->settings['enable_social_login_links'] : '';
			$login    = isset( $enable['login'] ) ? 'login' : '';
			$register = isset( $enable['register'] ) ? 'register' : '';

			if ( 'login' === $login ) {
				add_action( 'login_form', array( $this, 'loginpress_social_login' ) );
			}
			if ( 'register' === $register ) {
				add_action( 'register_form', array( $this, 'loginpress_social_login' ) );
			}
			
			add_action( 'init', array( $this, 'loginpress_register_social_login_block' ) );
			add_action( 'admin_init', array( $this, 'loginpress_update_process_complete' ), 10, 2 );
			add_action( 'init', array( $this, 'session_init' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'settings_tab' ), 15 );
			add_filter( 'loginpress_settings_fields', array( $this, 'settings_field' ), 10 );
			add_action( 'delete_user', array( $this, 'delete_user_row' ) );
			add_filter( 'login_message', array( $this, 'loginpress_social_login_register_error' ), 100, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_social_login_admin_action_scripts' ) );
			add_action( 'login_enqueue_scripts', array( $this, 'load_login_assets' ) );
			add_action( 'login_footer', array( $this, 'login_page_custom_footer' ) );
			add_filter( 'get_avatar', array( $this, 'insert_avatar' ), 1, 5 );
			// Register AJAX actions
			add_action( 'wp_ajax_loginpress_update_verification', array( $this, 'loginpress_lpsl_settings_verification' ) );
			add_action( 'wp_ajax_loginpress_save_social_login_order', array( $this, 'loginpress_save_social_login_order' ) );

			add_shortcode( 'loginpress_social_login', array( $this, 'loginpress_social_login_shortcode' ) );
			add_filter( 'cron_schedules', array( $this, 'loginpress_add_weekly_cron_schedule' ) );
			register_activation_hook( __FILE__, array( $this, 'loginpress_create_jwt_cron' ) );
			register_deactivation_hook( __FILE__, array( $this, 'loginpress_clear_cron_job' ) );
			add_action( 'loginpress_check_jwt_token', array( $this, 'loginpress_check_jwt_token' ) );
		}

		/**
		 * LoginPress Social Login Block
		 *
		 * @since 5.0.0
		 */
		public function loginpress_register_social_login_block() {
			wp_register_script(
				'loginpress-block-social-login',
				plugins_url( '/blocks/block.js', __FILE__ ),
				[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'wp-components', 'wp-server-side-render' ],
				filemtime( plugin_dir_path( __FILE__ ) . '/blocks/block.js' )
			);
		
			// Get plugin option and parse the serialized string
			$raw_option = get_option( 'loginpress_social_logins' );
			$option_data = $raw_option;//is_string( $raw_option ) ? maybe_unserialize( $raw_option ) : [];
			//var_dump($option_data);
			wp_localize_script( 'loginpress-block-social-login', 'LoginPressSocialLogins', [
				'statuses' => [
					'apple'     => $option_data['apple_status'] ?? '',
					'google'    => $option_data['gplus_status'] ?? '',
					'facebook'  => $option_data['facebook_status'] ?? '',
					'twitter'   => $option_data['twitter_status'] ?? '',
					'linkedin'  => $option_data['linkedin_status'] ?? '',
					'microsoft' => $option_data['microsoft_status'] ?? '',
					'github'    => $option_data['github_status'] ?? '',
					'discord'   => $option_data['discord_status'] ?? '',
					'wordpress' => $option_data['wordpress_status'] ?? '',
					'amazon' 	=> $option_data['amazon_status'] ?? '',
					'pinterest' => $option_data['pinterest_status'] ?? '',
					'spotify' 	=> $option_data['spotify_status'] ?? '',
					'twitch' 	=> $option_data['twitch_status'] ?? '',
					'reddit' 	=> $option_data['reddit_status'] ?? '',
					'disqus' 	=> $option_data['disqus_status'] ?? '',
				],
			] );
		
			register_block_type( plugin_dir_path( __FILE__ ) . '/blocks/block.json', [
				'render_callback' => array( $this, 'loginpress_render_social_login_block'),
				'script' => 'loginpress-block-social-login',
			] );
		}

		/**
		 * LoginPress Social Login Block render callback
		 *
		 * @param mixed  $attributes Containing the attributes of each provider.
		 * @since 5.0.0
		 */
		public function loginpress_render_social_login_block( $attributes ) {
			$shortcode = '[loginpress_social_login';
			foreach ( $attributes as $key => $value ) {
				// Convert camelCase to snake_case (e.g., disableApple → disable_apple)
				$attr_name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
				$shortcode .= sprintf( ' %s="%s"', esc_attr( $attr_name ), $value ? 'true' : 'false' );
			}
			$shortcode .= ']';
		
			return do_shortcode( $shortcode );
		}

		/**
		 * Add social avatar to user profile.
		 *
		 * @param mixed  $avatar The Avatar.
		 * @param mixed  $id_or_email The ID or Email of user.
		 * @param int    $size The size of the avatar.
		 * @param string $default Default Avatar.
		 * @param bool   $alt Alternative.
		 *
		 * @return url $avatar the Avatar.
		 */
		public function insert_avatar( $avatar, $id_or_email, $size = 96, $default = '', $alt = false ) {
			$host             = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
			$request_uri      = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
			$current_page_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;
			$options_page_url = home_url( '/wp-admin/options-discussion.php' );
			if ( $current_page_url === $options_page_url ) {
				return $avatar;
			}
			global $wpdb;
			$user = false;
			$id   = 0;

			if ( is_numeric( $id_or_email ) ) {

				$id   = (int) $id_or_email;
				$user = get_user_by( 'id', $id );

			} elseif ( is_object( $id_or_email ) ) {

				if ( ! empty( $id_or_email->user_id ) ) {
					$id   = (int) $id_or_email->user_id;
					$user = get_user_by( 'id', $id );
				}
			} else {
				$user = get_user_by( 'email', $id_or_email );
			}

			if ( $user && is_object( $user ) ) {
				$avatar_url = $wpdb->get_results( $wpdb->prepare( "SELECT photo_url FROM `$this->table_name` WHERE user_id = %d", $id ) ); // @codingStandardsIgnoreLine.

				if ( $avatar_url ) {
					$avatar_url = $avatar_url[0]->photo_url;
					$avatar     = preg_replace( '/src=("|\').*?("|\')/i', 'src=\'' . $avatar_url . '\'', $avatar );
					$avatar     = preg_replace( '/srcset=("|\').*?("|\')/i', 'srcset=\'' . $avatar_url . '\'', $avatar );
				}
			}

			return $avatar;
		}

		/**
		 * LoginPress Addon updater
		 *
		 * @version 3.0.0
		 */
		public function init_addon_updater() {
			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {
				$updater = new LoginPress_AddOn_Updater( 2335, __FILE__, $this->version );
			}
		}


		/**
		 * Add the settings fields for the Social Login.
		 *
		 * @param array $setting_array The social login setting array.
		 *
		 * @return array An array of setting's fields and their corresponding attributes.
		 */
		public function settings_field( $setting_array ) {

			$_settings_tab = array(
				array(
					'name'    => 'enable_social_login_links',
					'label'   => __( 'Enable Social Login on', 'loginpress-pro' ),
					'desc'    => __( 'Enable Social Login on WordPress default Login and Register forms.', 'loginpress-pro' ),
					'type'    => 'multicheck',
					'options' => array(
						'login'    => __( 'Login Form', 'loginpress-pro' ),
						'register' => __( 'Register Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'        => 'social_login_button_label',
					'label'       => __( 'Social Login Button Label', 'loginpress-pro' ),
					'desc'        => __( 'Customize the label for social login buttons. Use <code>%provider%</code> to dynamically include the social provider name in the label. For example, "Login with %provider%" or "Continue with %provider%".', 'loginpress-pro' ),
					'placeholder' => 'Login with %provider%',
					'type'        => 'text',
					'std'         => 'Login with %provider%', // Default value
				),
				array(
					'name'     => 'social_login_shortcode',
					'label'    => __( 'Social Login Shortcode', 'loginpress-pro' ),
					'type'     => 'callback',
					'callback' => array( $this, 'render_social_login_html' ),
					'desc'     => __( 'Place this shortcode where you want to add social login buttons.', 'loginpress-pro' ),
				),
				// Styles tab
				array(
					'name'     => 'social_button_styles',
					'label'    => __( 'Button Styles', 'loginpress-pro' ),
					'callback' => array( $this, 'lpsl_multicheck_with_icons' ),
					'std'      => 'default',
					'options'  => array(
						'default'     => array(
							'label' => __( 'Default', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/default-social-login.svg' ) . '" alt="' . esc_attr__( 'Default width icons', 'loginpress-pro' ) . '" />', // Replace with actual SVG code
						),
						'full_width'  => array(
							'label' => __( 'Full Width', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/social-full-width.svg' ) . '" alt="' . esc_attr__( 'Full width icons', 'loginpress-pro' ) . '" />',
						),
						'icons'       => array(
							'label' => __( 'Square Icons', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/social-icons.svg' ) . '" alt="' . esc_attr__( 'Square Icons', 'loginpress-pro' ) . '" />',
						),
						'round_icons' => array(
							'label' => __( 'Round Icons', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/round-icon.svg' ) . '" alt="' . esc_attr__( 'Round Icons', 'loginpress-pro' ) . '" />',
						),
					),
				),
				array(
					'name'     => 'social_button_position',
					'label'    => __( 'Button Position', 'loginpress-pro' ),
					'callback' => array( $this, 'lpsl_multicheck_with_icons' ),
					'std'      => 'default',
					'options'  => array(
						'default'         => array(
							'label' => __( 'Default', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/below-with-seprator.svg' ) . '" alt="' . esc_attr__( 'Default', 'loginpress-pro' ) . '" />',
						),
						// 'below'             => array(
						// 'label' => __( 'Below', 'loginpress-pro' ),
						// 'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/below.svg' ) . '" alt="' . esc_attr__( 'Below', 'loginpress-pro' ) . '" />',
						// ),
						'below'           => array(
							'label' => __( 'Below', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/default.svg' ) . '" alt="' . esc_attr__( 'Below', 'loginpress-pro' ) . '" />',
						),
						'above'           => array(
							'label' => __( 'Above', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/above.svg' ) . '" alt="' . esc_attr__( 'Above', 'loginpress-pro' ) . '" />',
						),
						'above_separator' => array(
							'label' => __( 'Above with Separator', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/above-with-separtor.svg' ) . '" alt="' . esc_attr__( 'Above with Separator', 'loginpress-pro' ) . '" />',
						),
					),
				),

				// Providers Tab
				$this->get_provider_status_setting( 'facebook' ),
				array(
					'name'  => 'facebook',
					'label' => __( 'Facebook Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Facebook Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'facebook' ),
				array(
					'name'  => 'facebook_app_id',
					'label' => __( 'Facebook App ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter your Facebook App ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'facebook_app_secret',
					'label' => __( 'Facebook App Secret Key', 'loginpress-pro' ),
					'desc'  => __( 'Enter your Facebook App Secret Key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'facebook_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The Facebook callback URl */
						__( '<span id="facebook_redirect_uri"><a href="%2$s?lpsl_login_id=facebook_check">%2$s?lpsl_login_id=facebook_check</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="facebook_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span><span class="lp-callback-url"> %1$s <span class="loginpress-copy-link">', 'loginpress-pro' ),
						__( 'Click to copy and paste the callback URL under "Valid OAuth Redirect URIs" in your Facebook API settings.', 'loginpress-pro' ),
						wp_login_url(),
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'twitter' ),
				array(
					'name'  => 'twitter',
					'label' => __( 'Twitter Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Twitter Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'twitter' ),
				array(
					'name'    => 'twitter_api_version',
					'label'   => __( 'Twitter API Version', 'loginpress-pro' ),
					'desc'    => __( 'Select the Twitter API version to use.', 'loginpress-pro' ),
					'type'    => 'select',
					'options' => array(
						'oauth1' => __( 'OAuth 1.1', 'loginpress-pro' ),
						'oauth2' => __( 'OAuth 2.0', 'loginpress-pro' ),
					),
					'default' => 'oauth1',
				),
				array(
					'name'  => 'twitter_oauth_token',
					'label' => __( 'Twitter API key', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Consumer API key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitter_token_secret',
					'label' => __( 'Twitter API secret key', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Consumer API secret key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitter_callback_url',
					'label' => __( 'Twitter Callback URL', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The Twitter callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="twitter_callback_url"><a href=%2$s?lpsl_login_id=twitter_login>%2$s?lpsl_login_id=twitter_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="twitter_callback_url" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter Your Callback URL:', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'gplus' ),
				array(
					'name'  => 'gplus',
					'label' => __( 'Google Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Google Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'google' ),
				array(
					'name'  => 'gplus_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'gplus_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'gplus_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The Google callback URl */
						__( '<span class="lp-callback-url"> %1$s <span class="loginpress-copy-link"><span id="gplus_redirect_uri"><a href="%2$s?lpsl_login_id=gplus_login">%2$s?lpsl_login_id=gplus_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="gplus_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url(),
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'linkedin' ),
				array(
					'name'  => 'linkedin',
					'label' => __( 'LinkedIn Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable LinkedIn Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'linkedin' ),
				array(
					'name'  => 'linkedin_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'linkedin_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'linkedin_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The linkedin callback URl */
						__(
							'<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="linkedin_redirect_uri"><a href="%2$s?lpsl_login_id=linkedin_login">%2$s?lpsl_login_id=linkedin_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="linkedin_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
							'loginpress-pro'
						),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'microsoft' ),
				array(
					'name'  => 'microsoft',
					'label' => __( 'Microsoft Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Microsoft Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'microsoft' ),
				array(
					'name'  => 'microsoft_app_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'microsoft_app_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'microsoft_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The microsoft callback URl */
						__(
							'<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="microsoft_redirect_uri"><a href="%2$s">%2$s</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="microsoft_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
							'loginpress-pro'
						),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'github' ),
				array(
					'name'  => 'github',
					'label' => __( 'Github Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Github Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'github' ),
				array(
					'name'  => 'github_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'github_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'github_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The github callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="github_redirect_uri"><a href=%2$s?lpsl_login_id=github_login>%2$s?lpsl_login_id=github_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="github_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter Your Callback URL:', 'loginpress-pro' ),
						wp_login_url()
					),

					'type'  => 'text',
				),
				array(
					'name'  => 'github_app_name',
					'label' => __( 'Github App Name', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Github App Name.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'discord' ),
				array(
					'name'  => 'discord',
					'label' => __( 'Discord Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Discord Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'discord' ),
				array(
					'name'  => 'discord_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'discord_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'discord_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The discord callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="discord_redirect_uri"><a href=%2$s?lpsl_login_id=discord_login>%2$s?lpsl_login_id=discord_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="discord_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				array(
					'name'  => 'discord_generated_url',
					'label' => __( 'Discord Generated URL', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Discord Generated URL', 'loginpress-pro' ),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'wordpress' ),
				array(
					'name'  => 'wordpress',
					'label' => __( 'WordPress Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable WordPress Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting('WordPress'),
				array(
					'name'  => 'wordpress_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'wordpress_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'wordpress_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The wordpress callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="wordpress_redirect_uri"><a href=%2$s?lpsl_login_id=wordpress_login>%2$s?lpsl_login_id=wordpress_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="wordpress_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'amazon' ),
				array(
					'name'  => 'amazon',
					'label' => __( 'Amazon Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Amazon Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'amazon' ),
				array(
					'name'  => 'amazon_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'amazon_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'amazon_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The amazon callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="amazon_redirect_uri"><a href=%2$s?lpsl_login_id=amazon_login>%2$s?lpsl_login_id=amazon_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="amazon_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'pinterest' ),
				array(
					'name'  => 'pinterest',
					'label' => __( 'Pinterest Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Pinterest Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'pinterest' ),
				array(
					'name'  => 'pinterest_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'pinterest_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'pinterest_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The Pinterest callback URl */
						__(
							'<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="pinterest_redirect_uri"><a href="%2$s">%2$s</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="pinterest_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
							'loginpress-pro'
						),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						home_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'reddit' ),
				array(
					'name'  => 'reddit',
					'label' => __( 'Reddit Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Reddit Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'reddit' ),
				array(
					'name'  => 'reddit_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'reddit_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'reddit_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The reddit callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="reddit_redirect_uri"><a href=%2$s?lpsl_login_id=reddit_login>%2$s?lpsl_login_id=reddit_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="reddit_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'spotify' ),
				array(
					'name'  => 'spotify',
					'label' => __( 'Spotify Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Spotify Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'spotify' ),
				array(
					'name'  => 'spotify_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'spotify_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'spotify_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The spotify callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="spotify_redirect_uri"><a href=%2$s?lpsl_login_id=spotify_login>%2$s?lpsl_login_id=spotify_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="spotify_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'twitch' ),
				array(
					'name'  => 'twitch',
					'label' => __( 'Twitch Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable twitch Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'twitch' ),
				array(
					'name'  => 'twitch_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitch_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitch_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The twitch callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="twitch_redirect_uri"><a href=%2$s?lpsl_login_id=twitch_login>%2$s?lpsl_login_id=twitch_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="twitch_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'disqus' ),
				array(
					'name'  => 'disqus',
					'label' => __( 'Disqus Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Disqus Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'disqus' ),
				array(
					'name'  => 'disqus_client_id',
					'label' => __( 'API Key', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your API Key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'disqus_client_secret',
					'label' => __( 'API Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your API Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'disqus_callback_url',
					'label' => __( 'Callback URL', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The disqus callback URl */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="disqus_callback_url"><a href=%2$s?lpsl_login_id=disqus_login>%2$s?lpsl_login_id=disqus_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="disqus_callback_url" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Callback URL', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'apple' ),
				array(
					'name'  => 'apple',
					'label' => __( 'Apple Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Apple Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'apple' ),
				array(
					'name'  => 'apple_service_id',
					'label' => __( 'Service ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Service ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'apple_key_id',
					'label' => __( 'Key ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Key ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'apple_team_id',
					'label' => __( 'Team ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Team ID', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'apple_p_key',
					'label' => __( 'Private Key', 'loginpress-pro' ),
					'desc'  => __( 'Enter The Private Key From the Downloaded File', 'loginpress-pro' ),
					'type'  => 'textarea',
				),
				array(
					'name'  => 'apple_secret',
					'label' => __( 'Apple Secret', 'loginpress-pro' ),
					'desc'  => __( 'This JWT token is generated.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'     => 'provider_order',
					'label'    => __( 'Provider Order', 'loginpress-pro' ),
					'desc'     => __( 'The order of social login providers.', 'loginpress-pro' ),
					'callback' => array( $this, 'lpsl_callback_hidden' ),
				),
				array(
					'name'  => 'loginpress_info',
					'label' => '', // No label
					'desc'  => __( 'Enable 2 Facebook Login for authentication.', 'loginpress-pro' ),
					'type'  => 'html',
				),
			);

			// Return the consolidated settings array
			$_new_tabs = array( 'loginpress_social_logins' => $_settings_tab );
			return( array_merge( $_new_tabs, $setting_array ) );
		}

		/**
		 * Get the button label for a social login provider.
		 *
		 * @param string $provider The name of the provider (e.g., 'facebook', 'twitter').
		 * @return array The status setting array.
		 */
		function get_provider_button_label_setting( $provider ) {
			return array(
				'name'  => $provider === 'WordPress' ? 'wordpress_button_label' : "{$provider}_button_label",
					/* translators: Button label */
				'label' => sprintf( __( '%s Button Label', 'loginpress-pro' ), ucfirst( $provider ) ),
				'desc'  => sprintf( /* translators: Button label description */
					__( 'Customize the label for the %s login button. ', 'loginpress-pro' ),
					ucfirst( $provider )
				),
				'type'  => 'text',
				'std'   => 'Login with %provider%', // Default value
			);
		}

		/**
		 * Get the status setting for a social login provider.
		 *
		 * @param string $provider The name of the provider (e.g., 'facebook', 'twitter').
		 * @return array The status setting array.
		 */
		function get_provider_status_setting( $provider ) {
			return array(
				'name'     => "{$provider}_status",
				/* translators: The provider's status */
				'label'    => sprintf( __( '%s Status', 'loginpress-pro' ), ucfirst( $provider ) ),
				/* translators: Description of provider status setting */
				'desc'     => sprintf( __( 'The current status of %s login.', 'loginpress-pro' ), ucfirst( $provider ) ),
				'std'      => 'Not verified', // Default value
				'callback' => array( $this, 'lpsl_provider_status' ),
			);
		}

		/**
		 * Render the shortcode field with copy button
		 */
		function render_social_login_html() {
			echo '
			<div class="loginpress-socialshortcode-box">
				<input 
					type="text" 
					id="loginpress-shortcode" 
					value="[loginpress_social_login]" 
					class="description"
					readonly 
					 
				/>
				<div class="copy-email-icon-wrapper sociallogin-copy-code" id="copy-shortcode-button" data-tooltip="Copy">
					<svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_27_294)">
							<path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path>
							<path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path>
						</g>
						<defs>
							<clipPath id="clip0_27_294">
								<rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect>
							</clipPath>
						</defs>
					</svg>
				</div>
			</div>';
			echo '<p class="description">' . esc_html__( 'Place this shortcode where you want to add social login buttons.', 'loginpress-pro' ) . '</p>';
		}

		/**
		 * Social Login Admin scripts
		 *
		 * @param int $hook The page ID.
		 *
		 * @version 3.0.0
		 * @return void
		 */
		public function loginpress_social_login_admin_action_scripts( $hook ) {
			if ( 'toplevel_page_loginpress-settings' === $hook ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_style( 'loginpress-admin-social-login', plugins_url( 'assets/css/style.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
			}
		}

		/**
		 * Social Login Settings tab's
		 *
		 * @param array $loginpress_tabs The social login addon tabs.
		 *
		 * @return array The Social login setting tabs and their attributes.
		 */
		public function settings_tab( $loginpress_tabs ) {
			$new_tab = array(
				array(
					'id'         => 'loginpress_social_logins',
					'title'      => __( 'Social Login', 'loginpress-pro' ),
					'sub-title'  => __( 'Third Party login access', 'loginpress-pro' ),
					/* Translators: The Social login tabs */
					'desc'       => $this->tab_desc(),
					'video_link' => '45S3i9PJhLA',
				),
			);
			return array_merge( $loginpress_tabs, $new_tab );
		}

		/**
		 * The tab_desc description of the tab 'loginpress settings'
		 *
		 * @since 3.0.0
		 * @return html $html The tab description.
		 */
		public function tab_desc() {
			// translators: Social login addons description
			$html = sprintf( __( '%1$sSocial Login add-on allows your users to log in and register using their Facebook, Google, X (Twitter) accounts and more. By integrating these social media platforms into your login system, you can eliminate spam and bot registrations effectively.%2$s', 'loginpress-pro' ), '<p>', '</p>' );
			// translators: Tabs
			$html .= sprintf( __( '%1$s%3$sSettings%4$s %5$sStyles%4$s %6$sProviders%4$s%2$s', 'loginpress-pro' ), '<div class="loginpress-social-login-tab-wrapper">', '</div>', '<a href="#loginpress_social_login_settings" class="loginpress-social-login-tab loginpress-social-login-active">', '</a>', '<a href="#loginpress_social_login_styles" class="loginpress-social-login-tab">', '<a href="#loginpress_social_login_providers" class="loginpress-social-login-tab">' );

			return $html;
		}
		/**
		 * Callback for help tab documentation.
		 *
		 * @version 3.0.0
		 */
		public function loginpress_social_login_help_tab_callback() {

			if ( ! class_exists( 'LoginPress_Promotion_tabs' ) ) {
				include LOGINPRESS_DIR_PATH . 'classes/class-loginpress-promotion.php';
			}
			$video_html = new LoginPress_Promotion_tabs();

			$html  = '<div id="loginpress_social_login_help" class="display">';
			$html .= '<div class="loginpress-social-accordions">';
			$html .= '<a href="#loginpress-facebook-login" class="loginpress-accordions">Facebook Login <span class="dashicons dashicons-arrow-down loginpress-arrow"></span></a>';
			$html .= '<div class="loginpress-social-tabs" id="loginpress-facebook-login">
			<h2>Let\'s integrate Facebook login with LoginPress Social Login.</h2>
			<p>Following are the steps to Create an app on Facebook to use Facebook Login in a web application.</p>
			<h4>Step 1:</h4>
			<ul>
				<li>1.1 Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a> section and login to your Facebook account, if you are not logged in already. This should not be your business account.</li>
				<li>Log in with your Facebook credentials if you are not logged in.</li>
			</ul>
			<h4>Step 2:</h4>
			<ul>
				<li>2.1 If you are here (at Facebook Developer section) first time, You will be required to “Create a Facebook for Developers account”, if you dont have one</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.1.1 Click “My Apps” button.</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.1.2 Click “Create App” button.</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.1.3 Select “Build Connected Experiences” option and click Continue.</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.1.4 Fill out the form. <b>( Display Name, Contact Email )</b> and click on “Create App”.</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.1.5 Add a product to your App. In our case it\'s “Facebook Login”. Click on “Set Up” button under "Facebook Login".</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.2.6 Select the platform for this app: Here we use "web".</li>
				<li>&nbsp;&nbsp;&nbsp;&nbsp;2.2.7 Enter your web URL <strong>' . esc_html( site_url() ) . '</strong> and save the settings.</li>
			</ul>
			<h4>Step 3:</h4>
			<ul>
				<li>3.1 On Facebook for Developer\'s page, Go to <strong>Settings &gt; Basic</strong> from the left side menu of Facebook.</li>
				<li>3.2 Fill out the required fields and click "Save"
					<li>&nbsp;&nbsp;&nbsp;&nbsp;3.2.1 <strong>Contact Email</strong></li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;3.2.2 <strong>App Domain URL</strong></li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;3.2.3 <strong>Privacy Policy URL</strong> </li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;3.2.4 <strong>Data Deletion Instructions URL</strong></li>
				<li>3.3 Then select the category and press confirm button.</li>
				<li>3.4 Here you will find the App ID and App Secret.</li>
				<li>3.5 Copy that App ID & Secret ID and use it in LoginPress Social Login\'s settings.</li>
				<li>3.6 Save Plugin\'s settings.</li>
			</ul>
			<h4>Step 4:</h4>
			<ul>
				<li>4.1 On Facebook for Developer\'s page, Go to <strong>Facebook Login &gt; Settings</strong> from left side menu.</li>
				<li>4.2 Add valid OAuth redirect URIs here:
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.2.1 <strong>' . esc_html( wp_login_url() . '?lpsl_login_id=facebook_check' ) . '</strong></li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.2.2 <strong>' . esc_html( site_url() . '/admin.php?lpsl_login_id=facebook_check' ) . '</strong></li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.2.3 Click on the "Save changes" button. (If you receive a blank page after you pressed the "Save changes" button, kindly refresh the page.)</li>
				</li>
				<li>4.3 On the left side, click on the "<b>App settings</b>" tab, then click "Basic".
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.3.1 Enter your domain name to the "App Domains" field, probably: ' . site_url() . '</li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.3.2 Fill up the "Privacy Policy URL" field. Provide a publicly available and easily accessible privacy policy that explains what data you are collecting and how you will use that data.</li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.3.3 At "User Data Deletion", choose the "Data Deletion Instructions URL" option, and enter the URL of your page* with the instructions on how users can delete their accounts on your site.</li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.3.4 To comply with GDPR, you should already offer possibility to delete accounts on your site, either by the user or by the admin.</br>
					If each user has an option to delete the account: the URL should point to a guide showing the way users can delete their accounts.</li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.3.5 Select a "Category", an "App Icon". (Optional)</li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;4.3.6 Press the "Save changes" button.</li>
				</li>
			</ul>
			<h4>Step 5:</h4>
			<ul>
				<li>5.1 By default, your application only has Standard Access for the "public_profile" and "email" permissions, which means that only you can log in with it. To get Advanced Access you will need to go trough the <b>Business Verification</b>, that you can start on the "Verification" tab on the left side.</li>
				<li>5.2 Currently your app is in Development Mode which also means that people outside of your business can not use it. Once your verification is completed, click on the "Go live" tab and publish your app by clicking on the "Go live" button at the bottom right corner. Before you press it, it is recommended to check the steps listed on the "Go live" page, if you configured everything properly.</li>
				<li>5.3 After everything is done, click on the "App settings" tab, then click "Basic".</li>
				<li>5.4 At the top of the page you can find your "App ID" and you can see your "App secret" if you click on the Show button. These will be needed in plugin’s settings</li>
			<ul>

			</ul>';
			$html .= $video_html->_addon_video( 'Helping video for Facebook Authentication', '45S3i9PJhLA' ) . '</div></div>';
			$html .= '<div class="loginpress-social-accordions">';
			$html .= '<a href="#loginpress-facebook-login" class="loginpress-accordions">Twitter Login <span class="dashicons dashicons-arrow-down loginpress-arrow"></span></a>';
			$html .= '<div class="loginpress-social-tabs" id="loginpress-twitter-login">
			<h2>Let\'s integrate Twitter login with LoginPress Social Login.</h2>
			<p>Following are the steps to create an app on Twitter to use Twitter Login in a web application.</p>
			<h4>Step 1:</h4>
			<ul>
				<li>1.1 You must register your website with Twitter at <a href="https://developer.twitter.com/en/apps" target="_blank">https://developer.twitter.com/en/apps</a>.</li>
				<li>1.2 Click on “Create an App” Button and fill out the required informational fields.</li>
				<li>&nbsp;&nbsp;1.2.1 Website URL: <strong>' . esc_html( site_url() ) . '</strong></li>
				<li>&nbsp;&nbsp;1.2.2 Callback URL: <strong>' . esc_html( wp_login_url() ) . '</strong></li>
				<li>1.3 Click on "Create" button.</li>
				<li>1.4 After that, a popup will appear for “Review Developer Terms”. Read the terms and click on create button.</li>
			</ul>
			<h4>Step 2:</h4>
			<ul>
				<li>2.1 Go to “Keys and tokens” tab.</li>
				<li>2.2 Click on Regenerate to get new Keys.</li>
				<li>2.3 A prompt will appear to verify the regeneration of keys, Click Yes, regenerate.</li>
				<li>2.4 Copy these API Key and API Key Secret and use it in plugin settings.</li>
				<li>2.5 Choose the "<b>Read</b>" option at "<b>App permission</b>". If you want to get the email address as well, then don’t forget to enable the "<b>Request email from users</b>" option. In this case you also need to fill the "<b>Terms of service</b>" and the "<b>Privacy policy</b>" fields with the corresponding URLs!</li>
				<li>2.5 Save the settings and enjoy.</li>
			</ul>';
			$html .= $video_html->_addon_video( 'Helping video for Twitter Authentication', '9-JZFistVpM' ) . '</div></div>';
			$html .= '<div class="loginpress-social-accordions">';
			$html .= '<a href="#loginpress-facebook-login" class="loginpress-accordions">Google Login <span class="dashicons dashicons-arrow-down loginpress-arrow"></span></a>';
			$html .= '<div class="loginpress-social-tabs" id="loginpress-gplus-login">
			<h2>Let\'s integrate Google login with LoginPress Social Login.</h2>
			<p>Following are the steps to Create an app on Google to use Google Login in a web application.</p>
			<h4>Step 1:</h4>
			<ul>
			<li>1.1 You must register your website with Google APIs at <a href="https://console.developers.google.com/" target="_blank">https://console.developers.google.com/</a>.</li>
			<li>1.2 Click on <b>New Project</b> button and fill out the required informational field. <b>(Project Name and Location).</b></li>
				<li>&nbsp;&nbsp;1.2.1 If you have more then 1 project in Google APIs, please confirm your project from top left dropdown project list.</li>
				<li>1.3 Click on “OAuth consent screen” from the left side menu.</li>
				<li>&nbsp;&nbsp;1.3.1. For User Type choose “External”.</li>
				<li>&nbsp;&nbsp;1.3.2. Fill out the required informational fields. (Application Name, App domain links and Authorized domains).</li>
				<li>&nbsp;&nbsp;1.3.3. Your Site URL is <strong>' . esc_html( site_url() ) . '</strong></li>
				<li>&nbsp;&nbsp;1.3.4. For Scopes section leave everything as it is and click “Save and Continue” </li>
				<li>&nbsp;&nbsp;1.3.5. For Test Users section leave everything be and click “Save and Continue” </li>
			<li>1.4 Click Back to Dashboard.</li>
			</ul>
			<h4>Step 2:</h4>
			<ul>
				<li>2.1 Go to the Credentials page from left side-bar.</li>
				<li>2.2 Please select “Create Credentials” and select “OAuth client ID” from the dropdown.</li>
				<li>2.3 Select the Application type here. In our case it\'s “Web application”.</li>
				<li>2.4 Fill out the required informational fields (Name of your Application & Authorized redirect URIs) save the settings.</li>
				<li>&nbsp;&nbsp;2.4.1 Authorized redirect URIs: <strong>' . esc_html( wp_login_url() . '?lpsl_login_id=gplus_login' ) . '</strong></li>
			</ul>
			<h4>Step 3:</h4>
			<ul>
				<li>3.1 After saving the settings, a popup will appear with “OAuth Client Created” heading. Copy the <b>Client ID</b> and <b>Client Secret</b> from here and use it in our plugin setting.</li>
				<li>3.2 Save the settings and enjoy.</li>
			</ul>';
			$html .= $video_html->_addon_video( 'Helping video for Google Authentication', 'EReYVYmdyeY' ) . '</div></div>';
			$html .= '<div class="loginpress-social-accordions">';
			$html .= '<a href="#loginpress-facebook-login" class="loginpress-accordions">LinkedIn Login <span class="dashicons dashicons-arrow-down loginpress-arrow"></span></a>';
			$html .= '<div class="loginpress-social-tabs" id="loginpress-linkedin-login">
			<h2>Let\'s integrate LinkedIn login with LoginPress Social Login.</h2>
			<p>Following are the steps to create an app on Linkedin to use Signin with LinkedIn using LoginPress.</p>
			<ol>
				<li>You must register your website with LinkedIn at <a href="https://developer.linkedin.com/" target="_blank">https://developer.linkedin.com/</a></li>
				<li>Click on <a href="https://www.linkedin.com/developers/apps/new" target="_blank">My Apps</a> to Create a LinkedIn Application and fill out the required informational fields on the form.</li>
				<li>Read and agree the "API Terms of Use" then click the "Create App" button!
				You will end up in the products area. If you aren\'t already there click on the "Products" tab.</li>
				<li>Find "Sign In with LinkedIn using "<b>OpenID Connect</b>" and click "<b>Request access</b>".</li>
				<li>A modal will appear where you need to tick the "I have read and agree to these terms" checkbox and finally press the "<b>Request access</b>" button.
				Click on the "<b>Auth</b>" tab.</li>
				<li>After submitting the form, Check out the Auth tab in your newly created App. Auth tab will have Redirect URLs and Credentials.</li>
				<li>Copy this <strong>' . esc_html( wp_login_url() . '?lpsl_login_id=linkedin_login' ) . '</strong> link and paste in Authorized Redirect URLs.</li>
				<li>Copy that Client ID &amp; Client Secret from Auth Tab and paste it in plugin settings.</li>
				<li>Save the settings of Social Login.</li>
				<li>Logout from WordPress and checkout the login page again to see the LinkedIn Sign In in effect.</li>
			</ol>';
			$html .= $video_html->_addon_video( 'Helping video for LinkedIn Authentication', 'HHmG4pZ7atM' ) . '</div></div>';
			$html .= '<div class="loginpress-social-accordions">';
			$html .= '<a href="#loginpress-microsoft-login" class="loginpress-accordions">Microsoft Login <span class="dashicons dashicons-arrow-down loginpress-arrow"></span></a>';
			$html .= '<div class="loginpress-social-tabs" id="loginpress-gplus-login">
			<h2>Let\'s integrate Microsoft login with LoginPress Social Login.</h2>
			<p>Following are the steps to Create an app on Microsoft to use Microsoft Login in a web application.</p>
			<h4>Step 1:</h4>
			<ul>
				<li>1. Navigate to <a href="https://portal.azure.com/" target="_blank">https://portal.azure.com/</a></li>
				<li>2. Log in with your Microsoft Azure credentials if you are not logged in or create a new account.</li>
				<li>3. Click on the Search bar and search for “App registrations”.</li>
				<li>4. Click on “New registration”.</li>
				<li>5. Fill the “Name” field with your App Name.</li>
				<li>6. Select an option at Supported account types.</li>
				<li>7. <b>Important:</b> On our Settings tab, you will need to select the Audience (Users with a Microsoft work or school account in any organization’s Azure AD tenant (for example, multi-tenant).</li>
				<li>8. At the “Redirect URI (optional)” field, select the “Web” option as a platform.</li>
				<li>9. Add this URL <b>' . site_url( '/wp-login.php' ) . '</b>.</li>
			</ul>
			<h4>Step 2:</h4>
			<ul>
				<li>2.1 Create your App with the “Register” button.</li>
				<li>2.2 You land on the “Overview” page.</li>
				<li>2.3 Copy the “Application (client) ID”, this will be the Application (client) ID in the plugin settings.</li>
				<li>2.4 Click on the link named “Add a certificate or secret” next to the Client credentials label.</li>
				<li>2.5 Click on “New client secret”.</li>
				<li>2.6 Fill the “Description” field.</li>
				<li>2.7 Set the expiration date at the “Expires” field.</li>
				<li>2.8 Then create your Client Secret with the “Add” button.</li>
				<li>2.9 Copy the “Value”, this will be the Client secret in the plugin settings.</li>
			</ul>
			<ul>
			<h4>Step 3:</h4>
				<li>3.1 Save the settings of Social Login.</li>
				<li>3.2 Logout from WordPress and checkout the login page again to see the Microsoft Sign In in effect.</li>
			<ul>';
			$html .= '</div></div>';

			$html .= '</div>';
			echo $html; // @codingStandardsIgnoreLine.
		}


		/**
		 * Main Instance
		 *
		 * @version 3.0.0
		 * @static
		 * @see loginPress_social_loader()
		 * @return Main instance
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}


		/**
		 * Starts the session with the call of init hook.
		 *
		 * @version 3.0.0
		 */
		public function session_init() {
			if ( isset( $_GET['lpsl_login_id'] ) ) { // @codingStandardsIgnoreLine.
				if ( ! session_id() && ! headers_sent() ) {
					session_start();
				}
			}

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-social-check.php';
		}

		/** Check to see if the current page is the login/register page.
		 *
		 * @version 3.0.0
		 * @return bool
		 */
		public function is_login_page() {
			$total_pages          = array( 'wp-login.php', 'wp-register.php' );
			$translate_press_page = array( 'index.php' );

			// If TranslatePress plugin is activated add span tag on login page for "OR".
			if ( is_plugin_active( 'translatepress-multilingual/index.php' ) ) {
				$total_pages = array_merge( $total_pages, $translate_press_page );
			}
			return in_array( $GLOBALS['pagenow'], $total_pages, true );
		}

		/**
		 * Social login shortcode callback.
		 *
		 * @param array $atts attributes of shortcode.
		 * @version 3.0.0
		 */
		public function loginpress_social_login_shortcode( $atts ) {

			$atts = shortcode_atts(
				array(
					'disable_apple'      => 'false',
					'disable_google'     => 'false',
					'disable_facebook'   => 'false',
					'disable_twitter'    => 'false',
					'disable_linkedin'   => 'false',
					'disable_microsoft'  => 'false',
					'disable_github'     => 'false',
					'disable_amazon'     => 'false',
					'disable_pinterest'  => 'false',
					'disable_reddit'     => 'false',
					'disable_spotify'    => 'false',
					'disable_twitch'     => 'false',
					'disable_disqus'     => 'false',
					'disable_discord'    => 'false',
					'disable_wordpress'  => 'false',
					'display'            => 'row',
					'social_redirect_to' => 'true',
				),
				$atts
			);

			$this->is_shortcode = true;

			ob_start();
			if ( ! is_user_logged_in() ) {
				?>
				<div class="loginpress-sl-shortcode-wrapper">
					<?php $this->loginpress_social_login( $atts ); ?> 
				</div>
				<?php
			}
			return ob_get_clean();
		}

		/**
		 * Renders the HTML structure for LoginPress social login buttons.
		 *
		 * @param   array|WC_Checkout|null $checkout Optional. WC checkout object or shortcode attributes array.
		 * @since   3.0.0
		 * @version 5.0.2
		 */
		public function loginpress_social_login( $checkout = null ) {
			// Ensure $checkout is an instance of WC_Checkout to avoid errors.
			if ( class_exists( 'WC_Checkout' ) && $checkout instanceof WC_Checkout ) {
				$atts = []; // Set default attributes.
			} else {
				$atts = $checkout; // If it's an array, use it directly.
			}

			if ( ! self::check_social_api_status() || is_user_logged_in() ) {
				return null;
			}

			wp_enqueue_style( 'loginpress-social-login', plugins_url( 'assets/css/login.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
			if ( version_compare( LOGINPRESS_VERSION, '4.0.0', '>' ) ) {
				$custom_css = '
				body .social-networks a svg {
					position: absolute;
				}';
				wp_add_inline_style( 'loginpress-social-login', $custom_css );
			}
      
			$redirect_to = $this->lp_get_social_login_redirect_url( $atts );
			if ( ! strpos( $_SERVER['REQUEST_URI'], 'redirect_to' ) !== false ) {
				 $redirect_to = $_SERVER['REQUEST_URI'];
			 }

			$is_login_request = strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false;
			if ( ! $is_login_request && class_exists( 'LoginPress_HideLogin_Main' ) ) {
				$hidelogin_option = get_option( 'loginpress_hidelogin' );
				if ( ! empty( $hidelogin_option['rename_login_slug'] ) ) {
					$custom_slug = trim( $hidelogin_option['rename_login_slug'], '/' );
					// check if request contains the custom slug
					if ( strpos( $_SERVER['REQUEST_URI'], $custom_slug ) !== false ) {
						$is_login_request = true;
					}
				}
			}

			if ( $is_login_request ) {
				if ( class_exists( 'WooCommerce' ) ) {
					// WooCommerce active — redirect to My Account
					$redirect_to = wc_get_page_permalink( 'myaccount' );
				} else {
					// WooCommerce not active — redirect to home
					$redirect_to = site_url( '/' );
				}
			}

			$encoded_url = rawurlencode( $redirect_to );
			$display_style = isset( $atts['display'] ) && 'column' === $atts['display'] ? 'block loginpress-social-display-col' : 'block';
			$button_style = $this->settings['social_button_styles'] ?? 'default';
			$button_text = $this->settings['social_login_button_label'] ?? 'Login with %provider%';
			$provider_order = isset($this->settings['provider_order']) && !empty($this->settings['provider_order'])
				? (is_array($this->settings['provider_order']) 
					? $this->settings['provider_order'] 
					: json_decode($this->settings['provider_order'], true))
				: array( 'facebook', 'twitter', 'gplus', 'linkedin', 'microsoft', 'apple', 'discord', 'wordpress', 'github', 'amazon', 'pinterest', 'disqus', 'reddit', 'spotify', 'twitch' );

			echo "<div class='social-networks " . esc_attr( "$display_style loginpress-$button_style" ) . "'>";

			if ( $this->is_login_page() ) {
				$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
				echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
			}

			foreach ( $provider_order as $provider ) {
				if ( 'gplus' !== $provider ) {
					if ( true === $this->is_shortcode && 'true' === $atts[ "disable_{$provider}" ] ) {
						continue;
					}
				} elseif ( true === $this->is_shortcode && 'true' === $atts['disable_google'] ) {
					continue;
				}
				if ( isset( $this->settings[ $provider ] ) && 'on' === $this->settings[ $provider ] && ! empty( $this->settings[ "{$provider}_status" ] ) && strtolower( $this->settings[ "{$provider}_status" ] ) != 'not verified' ) {
					$client_id_key     = "{$provider}_client_id";
					$client_secret_key = "{$provider}_client_secret";
					$app_id_key        = "{$provider}_app_id";
					$app_secret_key    = "{$provider}_app_secret";

					if ( ( ! empty( $this->settings[ $client_id_key ] ) && ! empty( $this->settings[ $client_secret_key ] ) ) ||
						( ! empty( $this->settings[ $app_id_key ] ) && ! empty( $this->settings[ $app_secret_key ] ) ) ||
						( ! empty( $this->settings[ "{$provider}_oauth_token" ] ) && ! empty( $this->settings[ "{$provider}_token_secret" ] ) ) ||
						( ! empty( $this->settings[ "{$provider}_service_id" ] ) && ! empty( $this->settings[ "{$provider}_key_id" ] ) && ! empty( $this->settings[ "{$provider}_team_id" ] ) && ! empty( $this->settings[ "{$provider}_p_key" ] ) ) ) {

						$button_label_key = "{$provider}_button_label";
						if ( 'gplus' === $provider ) {
							$button_label_key = 'google_button_label';
						}
						// Replace 'gplus' with 'Google'
						$provider_display_name = ( 'gplus' === $provider ) ? 'Google' : ucfirst($provider);
						if ( $provider_display_name === 'Wordpress' ){
							$provider_display_name = 'WordPress';
						}
						$provider_button_text = !empty( $this->settings[$button_label_key] )
							? $this->settings[$button_label_key]
							: (!empty( $button_text )
								? str_replace( '%provider%', $provider_display_name, $button_text )
								: 'Login with ' . $provider_display_name );

						$login_id   = "{$provider}_login";
						$icon_class = ( 'gplus' === $provider ) ? 'icon-google-plus' : "icon-$provider";

						echo "<a href='" . esc_url_raw( wp_login_url() . "?lpsl_login_id=$login_id&state=" . base64_encode( "redirect_to=$encoded_url" ) . "&redirect_to=$redirect_to" ) . "' title='" . esc_html( $provider_button_text ) . "' rel='nofollow'>";
						echo "<div class='lpsl-icon-block $icon_class clearfix'>";
						echo "<span class='lpsl-login-text'>" . esc_html( $provider_button_text ) . '</span>';
						echo $this->get_provider_icon( $provider ); // Dynamically fetch the provider's icon.
						echo '</div></a>';
					}
				}
			}

			echo '</div>';
		}

		/**
		 * check which page to redirect after login
		 *
		 * @param array $atts The default attributes.
		 * @return string redirect url.
		 * @since 5.0.0
		 */
		public function lp_get_social_login_redirect_url( $atts = array() ) {
			
			$redirect_to = site_url() . $_SERVER['REQUEST_URI'];			
		
			return $redirect_to;
		}

		/**
		 * Get the SVG icon for the provider.
		 *
		 * @param string $provider The provider name.
		 * @return string SVG icon HTML.
		 * @since 5.0.0
		 */
		public function get_provider_icon( $provider ) {
			$icons = array(
				'facebook'  => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M30 14.5886C30 6.53154 23.2842 0 15 0C6.71572 0 0 6.53154 0 14.5886C0 21.8702 5.48531 27.9056 12.6562 29V18.8056H8.84766V14.5886H12.6562V11.3746C12.6562 7.71828 14.8957 5.69868 18.322 5.69868C19.9631 5.69868 21.6797 5.98361 21.6797 5.98361V9.57379H19.7882C17.9249 9.57379 17.3438 10.6983 17.3438 11.852V14.5886H21.5039L20.8389 18.8056H17.3438V29C24.5148 27.9056 30 21.8702 30 14.5886Z" fill="white"/>
				</svg>',

				'gplus'     => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clip-path="url(#clip0_794_154)">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M6.46319 15C6.46319 14.0257 6.62491 13.0915 6.9139 12.2154L1.85843 8.35498C0.873115 10.3554 0.318115 12.6096 0.318115 15C0.318115 17.3885 0.872646 19.6411 1.85632 21.6403L6.90897 17.7724C6.6228 16.9003 6.46319 15.9696 6.46319 15Z" fill="#FBBC05"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M15.3392 6.13641C17.4559 6.13641 19.3677 6.88641 20.8698 8.11359L25.2395 3.75C22.5767 1.4318 19.1628 0 15.3392 0C9.40298 0 4.30111 3.39469 1.85822 8.355L6.91345 12.2154C8.07829 8.67961 11.3987 6.13641 15.3392 6.13641Z" fill="#EA4335"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M15.3392 23.8634C11.3989 23.8634 8.07853 21.3202 6.91368 17.7844L1.85822 21.6441C4.30111 26.6051 9.40298 29.9998 15.3392 29.9998C19.003 29.9998 22.501 28.6988 25.1263 26.2613L20.3277 22.5516C18.9737 23.4045 17.2686 23.8634 15.3392 23.8634Z" fill="#34A853"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M29.6816 15.0001C29.6816 14.1137 29.5449 13.1591 29.3401 12.2729H15.3432V18.0683H23.4001C22.9972 20.0444 21.9008 21.5633 20.3316 22.5519L25.1302 26.2616C27.8879 23.7022 29.6816 19.8894 29.6816 15.0001Z" fill="#4285F4"/>
				</g>
				<defs>
				<clipPath id="clip0_794_154">
				<rect width="30" height="30" fill="white"/>
				</clipPath>
				</defs>
				</svg>',

				'apple'     => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M23.8808 16.4161C23.8422 12.7442 27.0521 10.9831 27.1956 10.8942C25.3915 8.40363 22.581 8.0619 21.5807 8.02301C19.1907 7.79382 16.9149 9.35223 15.7023 9.35223C14.4924 9.35223 12.6191 8.05659 10.6372 8.08959C8.03005 8.12612 5.62694 9.52133 4.2853 11.725C1.57767 16.1598 3.59264 22.7322 6.23099 26.3316C7.52084 28.0909 9.05906 30.0705 11.0772 29.9981C13.0222 29.9256 13.7561 28.8103 16.1073 28.8103C18.4587 28.8103 19.1189 29.9981 21.1763 29.961C23.2681 29.9251 24.594 28.168 25.8733 26.4016C27.3541 24.3578 27.9638 22.3786 28 22.2785C27.9544 22.2584 23.9226 20.8002 23.8808 16.4161Z" fill="white"/>
				<path d="M19.4279 5.53315C20.4598 4.06657 21.1589 2.0287 20.9688 0C19.4796 0.0704372 17.677 1.15876 16.6078 2.62393C15.6499 3.92426 14.8123 5.99453 15.0368 7.9866C16.698 8.13735 18.3924 6.99691 19.4279 5.53315Z" fill="white"/>
				</svg>',

				'linkedin'  => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clip-path="url(#clip0_798_168)">
				<path d="M28.2886 0C28.2908 0.0291377 28.2886 0.0746523 28.325 0.0684606C28.5071 0.0389586 28.6349 0.193727 28.804 0.204654C28.9106 0.21121 28.9664 0.305894 29.0443 0.356872C29.3994 0.587749 29.6839 0.903113 29.831 1.29531C29.9199 1.53238 30 1.78912 30 2.05714C29.9986 10.6756 29.9965 19.2929 29.9939 27.9093C29.9939 28.2573 29.8653 28.5775 29.6974 28.898C29.5516 29.1797 29.312 29.3499 29.1121 29.5611C28.9916 29.6889 28.797 29.7704 28.6072 29.8204C28.4552 29.8572 28.3077 29.9107 28.1673 29.9799C28.113 30.008 28.0377 29.9985 27.9717 29.9985C24.5559 29.9985 21.1397 29.9985 17.7234 29.9985C12.6931 29.9985 7.66302 29.9985 2.63298 29.9985C2.23563 29.9985 1.84883 29.9861 1.46167 29.8491C1.07108 29.7081 0.725895 29.4641 0.462626 29.1431C0.282174 28.9184 0.157378 28.6545 0.0984087 28.3725C0.018281 28.0214 7.10432e-05 27.6737 7.10432e-05 27.3204C7.10432e-05 18.9758 7.10432e-05 10.6312 7.10432e-05 2.28656C-0.00213704 1.98795 0.0471423 1.69118 0.145766 1.4093C0.273965 1.05023 0.511789 0.766556 0.788228 0.512004C1.05011 0.270929 1.35277 0.113624 1.71007 0.0750168C1.7625 0.0691892 1.78909 0.0553485 1.78291 0.0021852L28.2886 0ZM11.7224 18.3813V19.9446C11.7224 21.7447 11.7224 23.5446 11.7224 25.3445C11.7224 25.4538 11.7103 25.5317 11.8738 25.5305C13.2331 25.523 14.5924 25.525 15.9531 25.5287C16.0623 25.5287 16.0946 25.504 16.0966 25.3892C16.1366 22.8186 16.0055 20.2466 16.1632 17.6767C16.1843 17.3359 16.1937 16.9957 16.2899 16.6665C16.3231 16.5526 16.3048 16.4316 16.3766 16.3184C16.4248 16.2425 16.4638 16.161 16.4927 16.0758C16.5492 15.926 16.6237 15.7834 16.7142 15.6512C16.841 15.4509 17.0205 15.3147 17.2096 15.1713C17.5738 14.8956 17.9842 14.7726 18.4159 14.7343C18.7716 14.704 19.1337 14.7012 19.4943 14.7896C19.708 14.8457 19.9137 14.9284 20.1065 15.0362C20.3662 15.1757 20.5498 15.4323 20.7024 15.6866C20.9172 16.0442 21.005 16.4546 21.071 16.8588C21.104 17.0661 21.12 17.2759 21.1191 17.4859C21.1171 20.1042 21.1156 22.7222 21.1143 25.3401C21.1143 25.4908 21.1508 25.5295 21.3022 25.5287C22.662 25.5222 24.0217 25.5222 25.3815 25.5287C25.541 25.5287 25.5636 25.4795 25.5636 25.3358C25.5581 23.9417 25.5781 22.5477 25.5551 21.1541C25.5323 19.7604 25.6346 18.3755 25.4987 16.9859C25.4539 16.5285 25.4707 16.0613 25.4091 15.6021C25.3894 15.4582 25.4186 15.3028 25.369 15.1757C25.2714 14.9266 25.2648 14.6625 25.2018 14.4091C25.1519 14.2078 25.1126 13.9925 25.0328 13.8126C24.8846 13.4779 24.7681 13.1207 24.5386 12.8254C24.437 12.6939 24.3591 12.5439 24.2501 12.4193C24.0553 12.1969 23.8237 12.0118 23.6008 11.8166C23.2916 11.5453 22.9179 11.4113 22.5559 11.2496C22.4913 11.2209 22.4324 11.1594 22.3585 11.1634C22.1362 11.175 21.9516 11.0242 21.7312 11.0293C21.6355 11.0315 21.5146 11.053 21.4483 11.0056C21.2629 10.8731 21.0262 11.0181 20.8538 10.8702C20.2609 10.8928 19.6658 10.8145 19.078 11.0042C18.8027 11.0934 18.5109 11.1393 18.2487 11.2704C18.1343 11.3279 17.9701 11.3166 17.9037 11.3982C17.7916 11.5362 17.6291 11.5726 17.4977 11.6594C17.2278 11.8377 16.9946 12.0563 16.7507 12.262C16.4818 12.4877 16.3267 12.8111 16.1085 13.0814C16.0575 13.1448 16.0244 13.1702 15.9628 13.1575C15.8715 13.1381 15.9155 13.0581 15.9151 13.0071C15.9119 12.4739 15.9071 11.9405 15.9151 11.4073C15.9177 11.2616 15.8729 11.23 15.733 11.2306C14.4648 11.2358 13.1966 11.2358 11.9285 11.2306C11.7678 11.2306 11.7187 11.2642 11.7194 11.4346C11.725 13.7507 11.726 16.0662 11.7224 18.3813ZM8.88143 18.3897C8.88143 16.0685 8.8829 13.7475 8.88581 11.4267C8.88581 11.2606 8.83883 11.2318 8.68512 11.2325C7.34967 11.2388 6.01421 11.2388 4.67876 11.2325C4.54547 11.2325 4.5076 11.2616 4.5076 11.4004C4.51148 16.0544 4.51148 20.7085 4.5076 25.3627C4.5076 25.5036 4.54875 25.5295 4.67987 25.5291C6.01532 25.524 7.35077 25.524 8.68622 25.5291C8.84283 25.5291 8.88617 25.4967 8.88581 25.3336C8.87925 23.0195 8.87707 20.7046 8.88143 18.3897ZM4.18671 6.67397C4.1543 7.04832 4.20566 7.40228 4.39832 7.7344C4.50504 7.91831 4.56696 8.13097 4.72976 8.28064C4.92242 8.45544 5.08269 8.65937 5.30741 8.80794C5.54459 8.96786 5.8066 9.08747 6.08282 9.16186C6.38949 9.24342 6.70744 9.19828 7.0203 9.19828C7.35465 9.19828 7.65512 9.0716 7.93667 8.90044C8.19119 8.74893 8.4209 8.55915 8.61775 8.33781C8.81551 8.11385 8.93388 7.85092 9.07738 7.60076C9.23215 7.33164 9.16266 7.00243 9.27839 6.71766C9.29593 6.67433 9.30788 6.59276 9.26307 6.56362C9.18695 6.51447 9.20306 6.4471 9.21176 6.38955C9.25112 6.12518 9.13889 5.87682 9.03987 5.6667C8.84177 5.23572 8.53112 4.86612 8.14063 4.5968C7.7307 4.31959 7.2502 4.16508 6.75552 4.15142C6.52533 4.14377 6.29697 4.19731 6.06714 4.24538C5.61371 4.34079 5.2688 4.60043 4.9268 4.87902C4.88752 4.90324 4.85346 4.93506 4.82664 4.97261C4.70317 5.18455 4.50904 5.34915 4.43985 5.59897C4.38813 5.78541 4.24826 5.93326 4.21221 6.1372C4.18016 6.31818 4.20383 6.49626 4.18671 6.67397Z" fill="white"/>
				</g>
				<defs>
				<clipPath id="clip0_798_168">
				<rect width="30" height="30" fill="white"/>
				</clipPath>
				</defs>
				</svg>',

				'microsoft' => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clip-path="url(#clip0_804_198)">
				<mask id="mask0_804_198" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="30" height="30">
				<path d="M30 0H0V30H30V0Z" fill="white"/>
				</mask>
				<g mask="url(#mask0_804_198)">
				<path d="M14.2203 22.838C14.2203 25.0582 14.2203 27.2786 14.2203 29.4993C14.2203 29.9408 14.2203 29.9425 13.781 29.9425H0.427724C0.373716 29.9425 0.319303 29.938 0.265295 29.9425C0.0776901 29.9587 -0.0157063 29.8831 0.00216078 29.6867C0.00825185 29.6196 0.00216078 29.5513 0.00216078 29.4834C0.00216078 25.0559 0.00216078 20.6286 0.00216078 16.2015C0.00216078 15.6689 -0.0177367 15.7136 0.482949 15.7136H13.7546C13.8224 15.7136 13.8902 15.7189 13.9576 15.7136C14.1485 15.6974 14.2337 15.7811 14.2212 15.973C14.2147 16.0674 14.2212 16.1625 14.2212 16.2576L14.2203 22.838Z" fill="#01A4EF"/>
				<path d="M22.7864 15.6946C25.0063 15.6946 27.2262 15.6946 29.446 15.6946C29.8834 15.6946 29.9004 15.61 29.9 16.163C29.9 20.5927 29.9 25.0224 29.9 29.4522C29.9 29.5197 29.8943 29.588 29.9 29.6555C29.9179 29.8417 29.8517 29.9401 29.6499 29.9193C29.5687 29.9152 29.4874 29.9152 29.4062 29.9193H16.2089C16.1278 29.9144 16.0464 29.9144 15.9653 29.9193C15.7427 29.947 15.6769 29.838 15.6968 29.6347C15.7033 29.5676 15.6968 29.4993 15.6968 29.4314C15.6968 25.0152 15.6968 20.5991 15.6968 16.1829C15.6968 15.6214 15.6562 15.695 16.2061 15.693C18.4002 15.694 20.5937 15.6946 22.7864 15.6946Z" fill="#F2B901"/>
				<path d="M7.07105 14.2254C4.86446 14.2254 2.65814 14.2254 0.452089 14.2254C-0.0181414 14.2246 0.0131261 14.2717 0.0131261 13.7899C0.0131261 9.33363 0.0131261 4.87719 0.0131261 0.42062C0.0131261 0.352722 0.0159686 0.285231 0.0131261 0.217334C0.0074411 0.0664953 0.0699761 -0.00994028 0.22875 0.00103717C0.309964 0.00713575 0.391179 0.00103717 0.472393 0.00103717H13.7509C13.8321 0.00103717 13.9134 0.0063226 13.9946 0.00103717C14.1517 -0.00953371 14.2179 0.0624296 14.2106 0.21652C14.2069 0.297835 14.2106 0.379149 14.2106 0.460464V13.7497C14.2106 13.7903 14.2106 13.831 14.2106 13.8717C14.2053 14.2209 14.2053 14.2246 13.8524 14.225C11.5917 14.2263 9.33125 14.2265 7.07105 14.2254Z" fill="#F25022"/>
				<path d="M29.887 7.15008V13.6914C29.887 13.8 29.8813 13.9085 29.887 14.0167C29.8951 14.1752 29.8208 14.2362 29.6685 14.2265C29.5873 14.2212 29.5061 14.2265 29.4248 14.2265C24.9995 14.2265 20.5743 14.2265 16.1492 14.2265C15.6473 14.2265 15.6988 14.2826 15.6988 13.7658C15.6988 9.33744 15.6988 4.90879 15.6988 0.479862C15.6988 0.439205 15.6988 0.398548 15.6988 0.35789C15.7041 0.00742525 15.7041 0.0037661 16.0562 0.0037661C20.55 0.0037661 25.0429 0.0037661 29.5349 0.0037661C29.8817 0.000920087 29.887 0.00701866 29.887 0.366835C29.887 2.62738 29.887 4.88846 29.887 7.15008Z" fill="#7FBA00"/>
				</g>
				</g>
				<defs>
				<clipPath id="clip0_804_198">
				<rect width="30" height="30" fill="white"/>
				</clipPath>
				</defs>
				</svg>',

				'wordpress' => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M14.5249 30C6.7358 30.0092 -0.0678043 23.4553 0.000510065 14.5498C0.062499 6.68512 6.72062 -0.0352519 14.5489 0.000139152C22.5443 0.0355303 29.0189 6.77819 29 15.0492C28.9772 23.2704 22.4683 29.9947 14.5249 30ZM6.52453 8.69717C8.09323 13.5104 9.64169 18.2659 11.242 23.1682C11.3546 22.906 11.4027 22.8222 11.433 22.7265C12.2971 20.0826 13.1662 17.4401 14.0088 14.7884C14.0736 14.5191 14.0582 14.2357 13.9645 13.9757C13.5103 12.6151 12.9625 11.2847 12.5729 9.90571C12.3085 8.97244 11.9176 8.4206 10.9093 8.52022C10.5424 8.55561 10.2084 8.42191 10.2768 7.93561C10.335 7.47946 10.6765 7.43096 11.0358 7.45586C11.6456 7.49781 12.2553 7.58039 12.8639 7.57383C14.4022 7.55679 15.9393 7.50305 17.4763 7.45717C17.8356 7.44669 18.1823 7.45717 18.2442 7.9225C18.3113 8.44681 17.9469 8.53726 17.5687 8.57789C17.1904 8.61853 16.7869 8.64736 16.3137 8.688C17.8736 13.5012 19.4106 18.22 21.0009 23.1328C21.1274 22.771 21.1868 22.6085 21.2362 22.4525C21.7359 20.8324 22.2115 19.2044 22.734 17.5921C23.6196 14.8631 23.526 12.2756 21.8358 9.86639C21.5595 9.45481 21.3382 9.00639 21.178 8.53333C20.572 6.84242 21.4183 5.17641 23.1072 5.04927C23.1275 5.04927 23.1426 4.98635 23.1907 4.89722C19.6877 1.85752 15.6875 0.861322 11.2699 1.96631C8.07299 2.76851 5.56687 4.67963 3.62244 7.58039C4.01714 7.60529 4.27016 7.64593 4.51938 7.63282C5.61367 7.5817 6.70797 7.51485 7.80859 7.45062C8.17167 7.42965 8.49932 7.5581 8.45505 7.9592C8.43227 8.17286 8.14763 8.45861 7.92877 8.52546C7.51003 8.6513 7.05966 8.64081 6.52453 8.69717ZM18.8312 27.6878C17.4789 23.8616 16.1417 20.0852 14.7476 16.1411C13.404 20.1639 12.1225 24.0058 10.8258 27.9027C13.5634 28.6656 16.1758 28.6564 18.8312 27.6878ZM8.79156 26.9904L2.63567 9.57802C-0.300579 15.972 2.63567 24.1801 8.79156 26.9904ZM21.0919 26.5422C27.4338 22.8353 29.1606 14.2601 26.0852 8.94229C26.0169 9.81658 26.0688 10.8429 25.8322 11.7932C25.3856 13.6047 24.8062 15.3822 24.2281 17.1543C23.2211 20.2478 22.1698 23.332 21.0919 26.5422Z" fill="#6563FF"/>
				</svg>',

				'discord'   => '<svg width="30" height="23" viewBox="0 0 30 23" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M0 15.0484C0.047188 15.0284 0.0303314 14.9863 0.0314587 14.9529C0.0460607 14.3786 0.119081 13.8098 0.186487 13.2399C0.320318 12.1289 0.53545 11.0288 0.830209 9.9485C1.06222 9.09207 1.3401 8.24856 1.66265 7.4213C2.05693 6.41391 2.51044 5.43027 3.02086 4.47529C3.40283 3.75657 3.82973 3.06339 4.25662 2.36466C4.43222 2.05742 4.71015 1.8199 5.04301 1.69259C5.6519 1.45487 6.25294 1.2016 6.86969 0.984973C7.64822 0.711709 8.44247 0.488428 9.24122 0.291806C9.69845 0.184046 10.1646 0.100737 10.6264 0.00631149C10.6657 -0.00503329 10.7079 -0.000980855 10.7444 0.0176354C10.7807 0.0362413 10.8085 0.0679938 10.8218 0.106289C11.0757 0.556184 11.2892 1.02608 11.5038 1.49486C11.5146 1.53415 11.5405 1.56772 11.576 1.58845C11.6114 1.6092 11.6536 1.61547 11.6936 1.60595C11.9767 1.56595 12.2554 1.53041 12.5441 1.4882C12.6688 1.46931 12.7957 1.46264 12.9193 1.45598C13.3687 1.43043 13.818 1.38267 14.2742 1.37933C14.5628 1.37933 14.8527 1.34601 15.1425 1.35489C15.6469 1.37156 16.1536 1.37822 16.6558 1.42154C17.186 1.4682 17.7175 1.50708 18.242 1.60151C18.3386 1.61928 18.4285 1.62594 18.4836 1.49041C18.6554 1.05385 18.8734 0.637275 19.0857 0.218487C19.1981 0.0018632 19.1981 -0.00368936 19.4396 0.0396375C20.1967 0.172837 20.9467 0.343759 21.6864 0.551746C22.5651 0.797227 23.4299 1.08907 24.277 1.42599C24.6342 1.56595 24.9803 1.7337 25.3331 1.88477C25.4712 1.94476 25.5229 2.07695 25.5937 2.18581C26.3221 3.29477 26.9758 4.44996 27.5506 5.64392C27.8712 6.31786 28.1618 7.00397 28.4224 7.70236C28.7764 8.6438 29.0721 9.60576 29.3077 10.5828C29.4538 11.2015 29.5818 11.8247 29.6807 12.4535C29.7593 12.9519 29.8229 13.4525 29.8716 13.9553C29.9166 14.4086 29.9402 14.8641 29.9705 15.3184C30.0177 16.0294 29.9952 16.7392 29.9907 17.449C29.9907 17.9611 29.9312 18.4732 29.9009 18.9854C29.893 19.0537 29.8684 19.1191 29.8292 19.176C29.7902 19.233 29.7376 19.2796 29.6762 19.312C28.7889 19.9412 27.8678 20.5227 26.917 21.0538C25.7923 21.6681 24.6208 22.1947 23.4131 22.6289C23.0649 22.7556 22.7019 22.8433 22.3639 23H22.2683C21.6744 22.1938 21.1467 21.342 20.69 20.4528C20.654 20.3828 20.6563 20.3483 20.736 20.3173C21.0348 20.1984 21.3359 20.0829 21.6269 19.9495C22.1675 19.7061 22.6928 19.4305 23.1996 19.1242C23.0457 18.9976 22.893 18.8898 22.7604 18.7598C22.6279 18.6298 22.4919 18.6488 22.3381 18.7365C22.165 18.8298 21.9796 18.9009 21.7988 18.9776C20.8792 19.3596 19.9284 19.6632 18.9565 19.8851C18.3541 20.0237 17.7447 20.1309 17.1309 20.2062C16.7153 20.2565 16.2993 20.2935 15.8828 20.3173C15.5222 20.3394 15.1605 20.3173 14.7987 20.3228C14.2416 20.3268 13.6848 20.2964 13.1316 20.2317C12.6654 20.1817 12.1992 20.1207 11.7408 20.0329C11.0127 19.8922 10.2942 19.7068 9.58951 19.4774C8.87297 19.251 8.172 18.979 7.49094 18.6632C7.46872 18.6461 7.44079 18.6377 7.4127 18.64C7.3846 18.6423 7.3584 18.6549 7.33928 18.6754C7.19145 18.8052 7.03411 18.924 6.86857 19.0309C6.74499 19.1064 6.81464 19.1419 6.87867 19.1798C7.17301 19.3441 7.46735 19.513 7.7673 19.6629C8.24294 19.9067 8.73288 20.1222 9.23448 20.3084C9.32997 20.3439 9.31313 20.3716 9.28167 20.4339C8.95738 21.0735 8.59655 21.6944 8.20094 22.2935C8.04703 22.5301 7.86391 22.7456 7.72798 22.9955H7.63923L6.80903 22.7101C6.02945 22.443 5.26547 22.1334 4.52063 21.7825C3.83534 21.4569 3.15904 21.116 2.50409 20.7316C1.69411 20.2573 0.917832 19.7318 0.153911 19.1919C0.136802 19.1807 0.122874 19.1653 0.113445 19.1472C0.104016 19.1291 0.0993802 19.1089 0.0999808 19.0886C0.086506 18.7465 0.0280873 18.4077 0.037074 18.0655C0.037074 18.0323 0.0528033 17.99 0.00561532 17.97L0 15.0484ZM19.971 9.61639C19.8589 9.60722 19.7461 9.60722 19.6339 9.61639C19.2921 9.66817 18.9642 9.78776 18.6702 9.96788C18.3761 10.1481 18.122 10.3852 17.923 10.665C17.6386 11.0422 17.4398 11.4757 17.3402 11.9358C17.2406 12.3958 17.2424 12.8718 17.3456 13.3311C17.4823 14.0713 17.8968 14.7331 18.506 15.184C18.7977 15.413 19.1406 15.57 19.5059 15.6418C19.8712 15.7137 20.2487 15.6984 20.6068 15.5972C21.3786 15.3906 21.9336 14.8962 22.2919 14.2009C22.6071 13.6027 22.7324 12.9244 22.6515 12.2547C22.6083 11.772 22.4517 11.306 22.1943 10.8938C21.6819 10.0984 20.964 9.6141 19.971 9.61639ZM12.6867 12.6012C12.7112 12.4012 12.6995 12.1984 12.6519 12.0024C12.498 11.2937 12.1891 10.6694 11.6094 10.1906C11.3522 9.97184 11.0522 9.80766 10.7281 9.70838C10.404 9.6092 10.0627 9.57701 9.72542 9.61389C9.38815 9.65077 9.0622 9.75599 8.76784 9.92288C8.47348 10.0898 8.21703 10.3148 8.01445 10.5839C7.38084 11.3704 7.19548 12.2857 7.36287 13.2499C7.47626 13.9866 7.86763 14.6534 8.4582 15.1162C9.05249 15.5816 9.72658 15.7827 10.468 15.6316C11.3791 15.4461 12.0071 14.8752 12.3935 14.0575C12.6146 13.6046 12.7155 13.1034 12.6867 12.6012Z" fill="white"/>
				</svg>',

				'github'    => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clip-path="url(#clip0_798_166)">
				<path d="M17.795 22.2161C18.5735 22.0432 19.3166 21.935 20.0294 21.7117C22.669 20.9081 24.1003 19.0241 24.4684 16.2733C24.742 14.2768 24.5833 12.3493 23.2478 10.7167C23.0303 10.4499 23.1261 10.1899 23.1863 9.92443C23.423 8.8749 23.3313 7.85352 23.0398 6.82231C22.9112 6.36571 22.6923 6.28281 22.3023 6.34183C21.2213 6.50479 20.2498 6.97123 19.3645 7.58518C19.184 7.72215 18.9753 7.81488 18.7544 7.85628C18.5337 7.89768 18.3065 7.88664 18.0905 7.82402C16.0654 7.28733 14.0402 7.32527 12.0138 7.80715C11.7982 7.87044 11.5715 7.88349 11.3504 7.84533C11.1293 7.80717 10.9193 7.71876 10.7357 7.58658C9.87063 6.97696 8.89241 6.55704 7.86217 6.35307C7.42567 6.27298 7.14789 6.34183 7.01106 6.85743C6.73739 7.89005 6.57456 8.92407 6.89065 9.96235C7.01243 10.3599 6.93717 10.61 6.6895 10.9022C4.65204 13.3229 5.06254 19.5931 8.99789 21.3043C9.80984 21.6624 10.662 21.9153 11.5348 22.0573C11.7551 22.0924 11.9822 22.0854 12.2012 22.2259C11.8331 22.7316 11.5376 23.2515 11.4403 23.864C11.3911 24.1858 11.1887 24.3304 10.9094 24.426C9.28114 24.9641 7.97574 24.4963 7.01379 23.0042C6.50887 22.2216 5.91912 21.5755 4.96128 21.4236C4.82858 21.395 4.69214 21.3894 4.55762 21.4068C4.36606 21.4447 4.1006 21.4181 4.04176 21.664C3.98291 21.9097 4.17175 22.0601 4.3469 22.1641C5.28284 22.7261 5.81923 23.6112 6.21879 24.589C6.74013 25.8646 7.72397 26.3929 8.98558 26.5249C9.5747 26.6089 10.1736 26.5856 10.7549 26.4561C11.2105 26.3367 11.3021 26.5109 11.2858 26.9409C11.2584 27.5955 11.2762 28.2516 11.2858 28.9077C11.3063 29.8083 10.8753 30.1722 10.046 29.8911C8.31561 29.3028 6.71514 28.37 5.3362 27.1459C2.61868 24.7533 0.891826 21.7566 0.256916 18.1235C-0.39989 14.3682 0.196708 10.8052 2.11512 7.55286C5.49904 1.8264 11.5608 -1.03263 17.925 0.339985C23.8431 1.61706 27.7647 5.38506 29.4122 11.3925C30.797 16.4376 29.6928 21.0598 26.5456 25.1397C24.8187 27.3876 22.5581 28.933 19.9296 29.9024C19.1756 30.1834 18.7446 29.849 18.7418 29.0103C18.7418 27.8414 18.7583 26.6711 18.7624 25.498C18.7664 24.3249 18.6611 23.1658 17.795 22.2161Z" fill="white"/>
				</g>
				<defs>
				<clipPath id="clip0_798_166">
				<rect width="30" height="30" fill="white"/>
				</clipPath>
				</defs>
				</svg>',
				'twitter'   => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m9.344 1.56 7.32 10.424.41.582.469-.534L26.75 1.56h1.066l-9.811 11.16h-.824l.619.882L28.923 29.44h-7.267l-7.74-11.021-.41-.583-.469.535-9.732 11.07H2.238l10.504-11.948.291-.33-.253-.362L2.077 1.56zm-5.32 1.65 9.128 12.775 1.042 1.46.105.146 7.813 10.935.168.235h5.306l-.633-.885-9.574-13.401-1.148-1.606L8.865 2.56l-.167-.235H3.392z" fill="#000" stroke="#000" stroke-width="1.12"/></svg>',
				'amazon'    => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#a)"><path d="M26.314 24.696c-12.76 5.893-20.68.962-25.749-2.032-.313-.189-.847.044-.384.56C1.87 25.21 7.405 30 14.63 30c7.23 0 11.53-3.828 12.068-4.496.535-.662.157-1.027-.383-.808m3.584-1.92c-.343-.434-2.084-.514-3.18-.384-1.097.127-2.744.778-2.6 1.169.073.146.223.08.977.015.755-.074 2.873-.333 3.314.227.444.563-.675 3.248-.88 3.681-.197.433.076.545.447.256.366-.288 1.028-1.034 1.472-2.09.441-1.063.71-2.545.45-2.875" fill="#F90"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17.734 12.671c0 1.547.04 2.836-.765 4.21-.65 1.116-1.68 1.802-2.83 1.802-1.57 0-2.485-1.16-2.485-2.874 0-3.383 3.123-3.997 6.08-3.997zm4.125 9.674c-.27.235-.662.251-.967.095-1.357-1.094-1.599-1.602-2.347-2.646-2.243 2.222-3.83 2.886-6.742 2.886-3.44 0-6.12-2.06-6.12-6.185 0-3.22 1.8-5.414 4.36-6.486 2.22-.95 5.321-1.117 7.691-1.379v-.514c0-.943.075-2.06-.495-2.874-.5-.732-1.455-1.033-2.295-1.033-1.559 0-2.95.776-3.29 2.383-.07.358-.34.71-.708.726l-3.97-.413c-.333-.073-.7-.335-.609-.832C7.282 1.407 11.625 0 15.514 0c1.99 0 4.59.514 6.16 1.976 1.99 1.803 1.801 4.209 1.801 6.827v6.185c0 1.859.794 2.674 1.542 3.679.264.357.322.787-.012 1.054a177 177 0 0 0-3.135 2.635z" fill="#000008"/><path d="M26.314 24.696c-12.76 5.893-20.68.962-25.749-2.032-.313-.189-.847.044-.384.56C1.87 25.21 7.405 30 14.63 30c7.23 0 11.53-3.828 12.068-4.496.535-.662.157-1.027-.383-.808m3.584-1.92c-.343-.434-2.084-.514-3.18-.384-1.097.127-2.744.778-2.6 1.169.073.146.223.08.977.015.755-.074 2.873-.333 3.314.227.444.563-.675 3.248-.88 3.681-.197.433.076.545.447.256.366-.288 1.028-1.034 1.472-2.09.441-1.063.71-2.545.45-2.875" fill="#F90"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17.734 12.671c0 1.547.04 2.836-.765 4.21-.65 1.116-1.68 1.802-2.83 1.802-1.57 0-2.485-1.16-2.485-2.874 0-3.383 3.123-3.997 6.08-3.997zm4.125 9.674c-.27.235-.662.251-.967.095-1.357-1.094-1.599-1.602-2.347-2.646-2.243 2.222-3.83 2.886-6.742 2.886-3.44 0-6.12-2.06-6.12-6.185 0-3.22 1.8-5.414 4.36-6.486 2.22-.95 5.321-1.117 7.691-1.379v-.514c0-.943.075-2.06-.495-2.874-.5-.732-1.455-1.033-2.295-1.033-1.559 0-2.95.776-3.29 2.383-.07.358-.34.71-.708.726l-3.97-.413c-.333-.073-.7-.335-.609-.832C7.282 1.407 11.625 0 15.514 0c1.99 0 4.59.514 6.16 1.976 1.99 1.803 1.801 4.209 1.801 6.827v6.185c0 1.859.794 2.674 1.542 3.679.264.357.322.787-.012 1.054a177 177 0 0 0-3.135 2.635z" fill="#000008"/></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h30v30H0z"/></clipPath></defs></svg>',
				'pinterest' => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#a)"><g clip-path="url(#b)"><path d="M14.994 0C6.7 0 0 6.713 0 14.994c0 6.355 3.949 11.785 9.527 13.97-.136-1.185-.247-3.012.05-4.307.27-1.173 1.752-7.454 1.752-7.454s-.445-.901-.445-2.221c0-2.086 1.21-3.641 2.715-3.641 1.284 0 1.9.963 1.9 2.11 0 1.284-.814 3.209-1.246 4.998-.357 1.493.753 2.715 2.222 2.715 2.665 0 4.714-2.814 4.714-6.861 0-3.591-2.58-6.096-6.27-6.096-4.27 0-6.774 3.196-6.774 6.503 0 1.283.493 2.665 1.11 3.418a.45.45 0 0 1 .1.432c-.112.47-.371 1.493-.42 1.703-.062.272-.223.333-.506.198-1.851-.889-3.011-3.628-3.011-5.825 0-4.726 3.43-9.07 9.909-9.07 5.195 0 9.243 3.702 9.243 8.663 0 5.17-3.258 9.33-7.774 9.33-1.518 0-2.95-.79-3.431-1.729l-.938 3.567c-.333 1.308-1.246 2.937-1.864 3.937 1.407.431 2.888.666 4.443.666C23.286 30 30 23.287 30 15.006 29.988 6.713 23.274 0 14.994 0" fill="#E60019"/></g></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h30v30H0z"/></clipPath><clipPath id="b"><path fill="#fff" d="M0 0h30v30H0z"/></clipPath></defs></svg>',
				'disqus'    => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#a)" fill="#5C9AFC"><path d="M30 15.722c-.28 5.18-2.837 9.572-8.203 12.135-5.2 2.48-10.247 1.914-14.961-1.305a2.4 2.4 0 0 0-.896-.416 2.5 2.5 0 0 0-.993-.028c-1.379.21-2.764.38-4.142.593-.765.115-1.002-.097-.636-.798.103-.194.159-.41.257-.606 1.183-2.384 1.596-4.628.87-7.444-1.74-6.73 2.4-13.605 8.954-15.937C17.063-.507 24.38 2 27.982 8c1.344 2.226 1.948 4.645 2.018 7.722m-20.312-.517c0 2.044.014 4.088 0 6.136 0 .59.08.977.838.96 2.116-.044 4.235.09 6.342-.17 3.814-.471 6.43-3.171 6.51-6.709.085-3.863-2.187-6.549-6.156-7.174-2.246-.356-4.526-.18-6.791-.21-.734 0-.734.425-.734.944-.002 2.065-.01 4.144-.01 6.223"/><path d="M13.725 15.106v-2.728c0-.318-.054-.734.42-.767 1.445-.099 2.901-.134 4.05.923 1.148 1.057 1.268 2.36.904 3.74-.489 1.806-3.077 3.047-4.954 2.453-.452-.144-.418-.472-.42-.782-.007-.946 0-1.892 0-2.839"/></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h30v30H0z"/></clipPath></defs></svg>',
				'reddit'    => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#a)"><path d="M14.878 29.57c8.217 0 14.878-6.619 14.878-14.785C29.757 6.62 23.097 0 14.878 0S0 6.62 0 14.785c0 8.166 6.661 14.786 14.878 14.786" fill="#FF4500"/><path d="M24.805 14.786c0-1.2-.977-2.153-2.166-2.153-.586 0-1.119.23-1.51.6-1.49-1.059-3.533-1.747-5.805-1.835l.994-4.623 3.231.688a1.54 1.54 0 0 0 1.545 1.465c.852 0 1.545-.688 1.545-1.535s-.693-1.535-1.545-1.535c-.604 0-1.136.353-1.385.864l-3.604-.758a.38.38 0 0 0-.284.053.34.34 0 0 0-.16.247l-1.1 5.152c-2.309.07-4.386.758-5.895 1.835a2.2 2.2 0 0 0-1.51-.6c-1.207 0-2.165.97-2.165 2.152 0 .882.532 1.624 1.278 1.959a4 4 0 0 0-.053.652c0 3.317 3.888 6.017 8.682 6.017 4.793 0 8.682-2.682 8.682-6.017 0-.211-.018-.44-.054-.652a2.17 2.17 0 0 0 1.279-1.976M9.926 16.32c0-.847.693-1.535 1.545-1.535a1.54 1.54 0 0 1 1.545 1.535 1.54 1.54 0 0 1-1.545 1.535c-.852.017-1.545-.689-1.545-1.535m8.647 4.075c-1.065 1.059-3.09 1.13-3.675 1.13-.604 0-2.628-.089-3.675-1.13a.39.39 0 0 1 0-.564.4.4 0 0 1 .568 0c.674.67 2.095.9 3.107.9s2.45-.23 3.107-.9a.4.4 0 0 1 .568 0 .43.43 0 0 1 0 .564m-.284-2.523a1.54 1.54 0 0 1-1.545-1.535 1.54 1.54 0 0 1 1.545-1.535 1.54 1.54 0 0 1 1.544 1.535c0 .83-.692 1.535-1.544 1.535" fill="#fff"/></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h30v30H0z"/></clipPath></defs></svg>',
				'spotify'   => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15 1C6.716 1 0 7.492 0 15.5 0 23.509 6.716 30 15 30c8.285 0 15-6.491 15-14.5C30 7.492 23.285 1 15 1m8.873 12.855c-4.835-2.776-12.81-3.031-17.426-1.677-.742.217-1.526-.187-1.75-.904-.225-.717.193-1.474.935-1.692 5.299-1.554 14.107-1.254 19.673 1.94a1.33 1.33 0 0 1 .49 1.858c-.395.645-1.257.857-1.922.475m-.158 4.11a1.194 1.194 0 0 1-1.609.373c-4.031-2.395-10.178-3.09-14.947-1.69-.618.181-1.272-.156-1.46-.753-.186-.598.163-1.228.78-1.41 5.448-1.598 12.221-.824 16.85 1.927a1.11 1.11 0 0 1 .386 1.554m-1.836 3.95a.953.953 0 0 1-1.285.3c-3.523-2.081-7.956-2.551-13.178-1.398-.503.111-1.004-.194-1.119-.68A.9.9 0 0 1 7 19.055c5.714-1.262 10.615-.72 14.57 1.616.44.26.579.817.31 1.243" fill="#1ED760"/></svg>',
				'twitch'    => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.417 0 2 5.357v19.286h6.5V30l5.417-5.357h4.333L28 15V0zm18.416 13.929L21.5 18.214h-4.333l-3.792 3.75v-3.75H8.5V2.143h17.333z" fill="#9147FF"/><path d="M14 6h2.4v6H14zm6.6 0H23v6h-2.4z" fill="#9147FF"/></svg>',
				);

			return $icons[ $provider ] ?? '';
		}

		/**
		 * Check Social Media Status from settings API.
		 *
		 * @version 3.0.0
		 * @return boolean
		 */
		public static function check_social_api_status() {
			$options = get_option( 'loginpress_social_logins' );

			if ( ( ( isset( $options['gplus'] ) && 'on' === $options['gplus'] ) && ( ! empty( $options['gplus_client_id'] ) && ! empty( $options['gplus_client_secret'] ) ) )
			|| ( ( isset( $options['apple'] ) && 'on' === $options['apple'] ) && ( ! empty( $options['apple_service_id'] ) && ! empty( $options['apple_key_id'] ) && ! empty( $options['apple_team_id'] ) && ! empty( $options['apple_p_key'] ) ) )
			|| ( ( isset( $options['facebook'] ) && 'on' === $options['facebook'] ) && ( ! empty( $options['facebook_app_id'] ) && ! empty( $options['facebook_app_secret'] ) ) )
			|| ( ( isset( $options['github'] ) && 'on' === $options['github'] ) && ( ! empty( $options['github_client_id'] ) && ! empty( $options['github_client_secret'] ) ) )
			|| ( ( isset( $options['discord'] ) && 'on' === $options['discord'] ) && ( ! empty( $options['discord_client_id'] ) && ! empty( $options['discord_client_secret'] ) ) )
			|| ( ( isset( $options['wordpress'] ) && 'on' === $options['wordpress'] ) && ( ! empty( $options['wordpress_client_id'] ) && ! empty( $options['wordpress_client_secret'] ) ) )
			|| ( ( isset( $options['twitter'] ) && 'on' === $options['twitter'] ) && ( ! empty( $options['twitter_oauth_token'] ) && ! empty( $options['twitter_token_secret'] ) ) )
			|| ( ( isset( $options['microsoft'] ) && 'on' === $options['microsoft'] ) && ( ! empty( $options['microsoft_app_id'] ) && ! empty( $options['microsoft_app_secret'] ) ) )
			|| ( ( isset( $options['amazon'] ) && 'on' === $options['amazon'] ) && ( ! empty( $options['amazon_client_id'] ) && ! empty( $options['amazon_client_secret'] ) ) )
			|| ( ( isset( $options['pinterest'] ) && 'on' === $options['pinterest'] ) && ( ! empty( $options['pinterest_client_id'] ) && ! empty( $options['pinterest_client_secret'] ) ) )
			|| ( ( isset( $options['disqus'] ) && 'on' === $options['disqus'] ) && ( ! empty( $options['disqus_client_id'] ) && ! empty( $options['disqus_client_secret'] ) ) )
			|| ( ( isset( $options['reddit'] ) && 'on' === $options['reddit'] ) && ( ! empty( $options['reddit_client_id'] ) && ! empty( $options['reddit_client_secret'] ) ) )
			|| ( ( isset( $options['spotify'] ) && 'on' === $options['spotify'] ) && ( ! empty( $options['spotify_client_id'] ) && ! empty( $options['spotify_client_secret'] ) ) )
			|| ( ( isset( $options['twitch'] ) && 'on' === $options['twitch'] ) && ( ! empty( $options['twitch_client_id'] ) && ! empty( $options['twitch_client_secret'] ) ) )
			|| ( ( isset( $options['linkedin'] ) && 'on' === $options['linkedin'] ) && ( ! empty( $options['linkedin_client_id'] ) && ! empty( $options['linkedin_client_secret'] ) ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Include Social LoginPress script in footer.
		 *
		 * @version 3.0.0
		 */
		public function login_page_custom_footer() {

			if ( ! self::check_social_api_status() ) {
				return;
			}

			include LOGINPRESS_SOCIAL_DIR_PATH . 'assets/js/script-login.php';
		}

		/**
		 * Delete user row form the table.
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return void
		 */
		public function delete_user_row( $user_id ) {
			global $wpdb;
			$sql = "DELETE FROM `$this->table_name` WHERE `user_id` = '$user_id'";
			$wpdb->query( $sql );
		}


		/**
		 * Plugin activation for check multi site activation
		 *
		 * @param array $network_wide all networks.
		 *
		 * @return void
		 */
		public static function loginpress_social_activation( $network_wide ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
				global $wpdb;
				// Get this so we can switch back to it later.
				$current_blog = $wpdb->blogid;
				// Get all blogs in the network and activate plugin on each one.
				$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM %s", $wpdb->blogs ) ); // @codingStandardsIgnoreLine.
				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::loginpress_social_create_table();
				}
				switch_to_blog( $current_blog );
				return;
			} else {
				self::loginpress_social_create_table();
			}
		}

		/**
		 * Create DB table on plugin activation.
		 *
		 * @version 3.0.0
		 * @version 1.0.5
		 */
		public static function loginpress_social_create_table() {

			global $wpdb;
			// Create user details table.
			$table_name = "{$wpdb->prefix}loginpress_social_login_details";

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				provider_name varchar(50) NOT NULL,
				identifier varchar(255) NOT NULL,
				sha_verifier varchar(255) NOT NULL,
				email varchar(255) NOT NULL,
				email_verified varchar(255) NOT NULL,
				first_name varchar(150) NOT NULL,
				last_name varchar(150) NOT NULL,
				profile_url varchar(255) NOT NULL,
				website_url varchar(255) NOT NULL,
				photo_url varchar(255) NOT NULL,
				display_name varchar(150) NOT NULL,
				description varchar(255) NOT NULL,
				gender varchar(10) NOT NULL,
				language varchar(20) NOT NULL,
				age varchar(10) NOT NULL,
				birthday int(11) NOT NULL,
				birthmonth int(11) NOT NULL,
				birthyear int(11) NOT NULL,
				phone varchar(75) NOT NULL,
				address varchar(255) NOT NULL,
				country varchar(75) NOT NULL,
				region varchar(50) NOT NULL,
				city varchar(50) NOT NULL,
				zip varchar(25) NOT NULL,
				UNIQUE KEY id (id),
				KEY user_id (user_id),
				KEY provider_name (provider_name)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		/**
		 * Load assets on login screen.
		 *
		 * @version 3.0.0
		 */
		public function load_login_assets() {

			wp_enqueue_style( 'loginpress-social-login', plugins_url( 'assets/css/login.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
		}

		/**
		 * The Custom error message of
		 *
		 * @param string $message The error message when user registrations are failed.
		 * @return string $message Modified error message when user registrations are failed.
		 *
		 * @version 3.1.2
		 */
		public function loginpress_social_login_register_error( $message ) {
			if ( isset( $_GET['lp_social_error'] ) ) {
				/* translators: Error message for social login. */
				$default_message = sprintf( __( '%1$sERROR%2$s: This Social Provider Only Support Certain Domains Only.', 'loginpress-pro' ), '<strong>', '</strong>' );
				$message         = apply_filters( 'loginpress_social_login_domain_restrict_error_message', $default_message );
				return '<p id="login_error" class="notice notice-error">' . wp_kses_post( $message ) . '</p>';
			}
			if ( isset( $_GET['lp_social_without_reg'] ) ) {
				/* translators: Error message for social login without registration. */
				$default_message = sprintf( __( '%1$sERROR%2$s: This Social Provider Only Support Registered Users Only, Please Contact Administration', 'loginpress-pro' ), '<strong>', '</strong>' );
				$message         = apply_filters( 'loginpress_social_login_without_reg_error_message', $default_message );
				return '<p id="login_error" class="notice notice-error">' . wp_kses_post( $message ) . '</p>';
			}
			return $message;
		}

		/**
		 * Update the verification status of each provider on plugin update
		 */
		public function loginpress_update_process_complete() {
				// Fetch the serialized loginpress_social_login option.

			$social_login_data = $this->settings;
			if ( empty( $social_login_data ) ) {
				return; // No data found, nothing to process.
			}
			if ( empty( $social_login_data['apple_status'] ) && empty( $social_login_data['gplus_status'] ) && empty( $social_login_data['facebook_status'] ) && empty( $social_login_data['twitter_status'] ) && empty( $social_login_data['linkedin_status'] ) && empty( $social_login_data['microsoft_status'] ) && empty( $social_login_data['github_status'] ) && empty( $social_login_data['discord_status'] ) && empty( $social_login_data['wordpress_status'] ) ) {
				// Unserialize the data.
				$enabled_providers = maybe_unserialize( $social_login_data );

				if ( ! is_array( $enabled_providers ) ) {
					return; // Data is not in the expected format.
				}

				// Fetch all entries from the social login details table in one query.
				global $wpdb;
				$social_login_details = $wpdb->get_results( "SELECT provider_name FROM {$this->table_name}", ARRAY_A );
				// Correct "glpus" to "gplus" if found in the results.
				foreach ( $social_login_details as &$detail ) {
					if ( isset( $detail['provider_name'] ) && $detail['provider_name'] === 'glpus' ) {
						$detail['provider_name'] = 'gplus';
					}
				}
				// Convert the result to a simple array of provider names for quick lookup.
				$existing_providers = array_column( $social_login_details, 'provider_name' );
				// Iterate over enabled providers to check their statuses.
				foreach ( $enabled_providers as $provider => $settings ) {
					
					$social_login_providers = array( 'facebook', 'twitter', 'gplus', 'linkedin', 'microsoft', 'github', 'discord', 'wordpress', 'apple' );
					if ( in_array( $provider, $social_login_providers, true ) ) {
						// Check if the provider is enabled and has necessary fields populated.
						if ( ! empty( $settings ) && $settings === 'on' ) {
							$required_keys     = $this->loginpress_get_required_keys_for_provider( $provider );
							$all_fields_filled = true;

							// Check if all required fields are filled.
							foreach ( $required_keys as $key ) {
								if ( empty( $enabled_providers[ "{$provider}_{$key}" ] ) ) {
									$all_fields_filled = false;
									break;
								}
							}

							// Update the provider status.
							if ( $all_fields_filled ) {
								if ( in_array( $provider, $existing_providers, true ) ) {
									$enabled_providers[ "{$provider}_status" ] = __( 'yet to verify', 'loginpress-pro' );
								} else {
									$enabled_providers[ "{$provider}_status" ] = __( 'not verified', 'loginpress-pro' );
								}
							} else {
									$enabled_providers[ "{$provider}_status" ] = __( 'not verified', 'loginpress-pro' );
							}
						}
					}
				}
				// Ensure the data being stored is always an array.
				if ( ! is_array( $enabled_providers ) ) {
					$enabled_providers = array();
				}
				// Serialize and update the modified data.
				update_option( 'loginpress_social_logins', $enabled_providers );
			}

			// Compatibility with older versions for provider order. updating the DB array with addition of new social providers.
			$provider_order = isset( $this->settings['provider_order'] ) ? $this->settings['provider_order'] : '';

			if ( is_array( $provider_order ) ) {
				$lp_social_login_array = $provider_order;
			} elseif ( ! empty( $provider_order ) ) {
				$provider_order = trim( $provider_order, '[]"' );
				$lp_social_login_array = explode( ',', $provider_order );
				$lp_social_login_array = array_map( function( $value ) {
					return trim( $value, ' "' );
				}, $lp_social_login_array );
			} else {
				$lp_social_login_array = array();
			}
			if ( isset( $this->settings['provider_order'] ) && ! empty( $lp_social_login_array ) 
				&& ! in_array( 'amazon', $lp_social_login_array) && version_compare( LOGINPRESS_PRO_VERSION, '4.0.1', '>=' ) )
			{
				// Additional providers
				$additional_providers = array( "disqus", "reddit", "amazon", "spotify", "pinterest", "twitch" );
				$missing_providers = array_diff( $additional_providers, $lp_social_login_array );

				// If there are missing providers, add them to the array
				if ( ! empty( $missing_providers ) ) {
					$lp_social_login_array = array_merge( $lp_social_login_array, $missing_providers );
					$lpsl_settings = get_option( 'loginpress_social_logins' );
					// Update the option with the new provider order in the string format
					$lpsl_settings['provider_order'] = $lp_social_login_array;
					update_option( 'loginpress_social_logins', $lpsl_settings );
				}
			}
		}

		/**
		 * Get field description for display.
		 *
		 * @param array $args settings field args.
		 * @since 4.0.0
		 */
		public function lpsl_get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option  settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 * @return string
		 * @since 4.0.0
		 */
		public function lpsl_get_option( $option, $section, $default = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default;
		}


		/**
		 * Multicheck with icons.
		 *
		 * @param array $args settings field args.
		 * @since 4.0.0
		 */
		public function lpsl_multicheck_with_icons( $args ) {
			$value = $this->lpsl_get_option( $args['id'], $args['section'], $args['std'] );
			if ( ! isset( $value ) || empty( $value ) ) {
				// Retrieve the current value of the option.
				$options = get_option( 'loginpress_social_logins', array() );
				// Ensure the retrieved value is an array. If not, reset it to an empty array.
				if ( ! is_array( $options ) ) {
					$options = array();
				}
				$options[ $args['id'] ] = 'default';
				update_option( 'loginpress_social_logins', $options );

				// Get the updated value for use in your callback or rendering logic.
				$value = $options[ $args['id'] ];
			}
			// Ensure $value is a string (radio options save single value).
			$value = is_array( $value ) ? '' : $value;

			$html  = '<fieldset>';
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );

			// Start the container for checkboxes.
			$html .= '<div class="loginpress-multicheck-container">';

			foreach ( $args['options'] as $key => $option ) {
				$html .= '<div class="loginpress-multicheck-item">';
				$html .= '<label for="wpb-' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . '][' . esc_attr( $key ) . ']">';
				$html .= '<input type="radio" class="loginpress-radio" id="wpb-' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . '][' . esc_attr( $key ) . ']" name="' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $key ) . '" ' . checked( $value, $key, false ) . ' style="margin-right: 8px;" />';
				$html .= '<span>' . esc_html( $option['label'] ) . '</span>'; // Label now on the right of the radio button.
				if ( ! empty( $option['icon'] ) ) {
					$html .= '<div class="loginpress-icon">' . $option['icon'] . '</div>'; // SVG icon below the checkbox.
				}
				$html .= '</label>';
				$html .= '</div>';
			}

			$html .= '</div>'; // Close the container.
			$html .= $this->lpsl_get_field_description( $args );
			$html .= '</fieldset>';

			// Enqueue a script for managing single-selection behavior.
			$html .= '<script>
				document.addEventListener("DOMContentLoaded", function () {
					const radios = document.querySelectorAll("input[type=radio][name=\'' . esc_js( $args['section'] ) . '[' . esc_js( $args['id'] ) . ']\']");
					radios.forEach((radio) => {
						radio.addEventListener("change", () => {
							radios.forEach((r) => r.checked = false);
							radio.checked = true;
						});
					});
				});
			</script>';

			echo $html;
		}

		/**
		 * Displays verify settings card
		 *
		 * @param array $args settings field args
		 * @since 4.0.0
		 */
		public function lpsl_provider_status( $args ) {
			// Get the provider name from the args
			$value       = esc_attr( $this->lpsl_get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = 'hidden';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$provider    = $args['id']; // Example: "facebook_status"

			// Remove "_status" and capitalize the first letter
			$provider_name = ucfirst( str_replace( '_status', '', $provider ) );
			if ( $provider_name == 'Gplus' ) {
				$provider_name = 'Google';
			}
			// Retrieve the provider's status from the database
			$status = ! empty( $value ) ? $value : 'Not verified';
			// Normalize status for class usage
			$status_class = strtolower( str_replace( ' ', '-', $status ) );
			$heading      = '';
			$description  = '';
			$button_label = '';
			if ( $status_class == 'verified' ) {
				$heading      = __( 'App Configuration Successful', 'loginpress-pro' );
				$description  = __( 'You have successfully configured your app. This social provider is now set up correctly, allowing users to log in without issues.', 'loginpress-pro' );
				$button_label = __( 'Verify Settings Again', 'loginpress-pro' );
			} elseif ( $status_class == 'not-verified' ) {
				$heading      = __( 'Test Your App Configuration', 'loginpress-pro' );
				$description  = __( 'Before you can start letting your users register with your app, it needs to be tested. This test ensures no users have trouble with the login and regitration process. If you encounter an error during the test, review your app settings. Otherwise, your configuration is fine.', 'loginpress-pro' );
				$button_label = __( 'Verify Settings', 'loginpress-pro' );
			} elseif ( $status_class == 'yet-to-verify' ) {
				$heading      = __( 'App Verification Required', 'loginpress-pro' );
				$description  = __( 'Your app must be verified in the latest version of Loginpress Social Login. Previously configured social logins will still work, but app verification is necessary to stay compliant.', 'loginpress-pro' );
				$button_label = __( 'Verify Settings', 'loginpress-pro' );
			}
			// Generate HTML structure with button
			$html  = '<div>';
			$html .= '    <div>';
			$html .= '<div class="provider-container ' . esc_attr( $status_class ) . '"><div class="provider-description"><h3>' . $heading . '</h3><p>' . $description . '</p></div>';
			$html .= '        <button type="button" id="verify-settings" class="button button-primary loginpress-verify-provider" data-provider="' . esc_attr( $provider ) . '">';
			$html .= $button_label;
			$html .= '        </button></div>';
			$html .= sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $status, $placeholder );
			$html .= '    </div>';
			$html .= '</div>';

			echo $html;
		}

		/**
		 * Renders a hidden input with a JSON encoded value.
		 *
		 * Used for storing the provider order in the database.
		 *
		 * @param array $args Field arguments passed from the settings API.
		 * @see loginpress_get_required_keys_for_provider()
		 * @since 4.0.0
		 */
		public function lpsl_callback_hidden( $args ) {
			// Get the current value of the field
			$loginpress_social_logins = get_option( 'loginpress_social_logins' );
			$value                    = isset( $loginpress_social_logins['provider_order'] ) ? $loginpress_social_logins['provider_order'] : '';

			// Ensure the value is JSON encoded only once
			if ( is_array( $value ) ) {
				$encoded_value = json_encode( $value ); // Encode only if it's an array
			} else {
				$encoded_value = $value; // Assume it's already encoded
			}
			// Generate the HTML for the hidden input
			$html = sprintf(
				'<input type="hidden" id="%1$s[%2$s]" name="%1$s[%2$s]" value="%3$s" />',
				$args['section'],
				$args['id'],
				esc_attr( $encoded_value )
			);

			// Optionally display a description (useful for debugging)
			$html .= $this->lpsl_get_field_description( $args );

			echo $html;
		}

		/**
		 * Add a 'weekly' schedule to WP Cron.
		 *
		 * @param array $schedules WP Cron schedules.
		 * @return array
		 * @since 4.0.0
		 * @version 5.0.0
		 */
		public function loginpress_add_weekly_cron_schedule($schedules) {
			if (!isset($schedules['weekly'])) {
				$schedules['weekly'] = array(
					'interval' => WEEK_IN_SECONDS,
					'display'  => __('Once Weekly', 'loginpress-pro'),
				);
			}
			return $schedules;
		}

		/**
		 * Schedules a weekly event to check the JWT token if not already scheduled.
		 *
		 * This function ensures that an event named 'loginpress_check_jwt_token'
		 * is scheduled to run on a weekly basis using WordPress's Cron API.
		 * 
		 * @since 4.0.0
		 * @version 5.0.0
		 */
		public function loginpress_create_jwt_cron() {
			if (!wp_next_scheduled('loginpress_check_jwt_token')) {
				wp_schedule_event(time(), 'weekly', 'loginpress_check_jwt_token');
			}
		}

		/**
		 * Clears the scheduled weekly event when the plugin is deactivated.
		 *
		 * @since 4.0.0
		 * @version 5.0.0
		 */
		public function loginpress_clear_cron_job() {
			wp_clear_scheduled_hook('loginpress_check_jwt_token');
		}

		/**
		 * Checks the expiration of the Apple JWT token and regenerates it if
		 * it will expire in less than 7 days.
		 *
		 * This function is called by a scheduled weekly event to ensure the
		 * token remains valid.
		 *
		 * @since 4.0.0
		 * @version 5.0.0
		 */
		public function loginpress_check_jwt_token() {
			// Fetch the social logins option
			$social_logins = get_option( 'loginpress_social_logins' );

			if ( isset( $social_logins['apple_secret'] ) ) {
				// Decode the JWT to check expiration
				$jwt_parts = explode( '.', $social_logins['apple_secret'] );
				if ( ( is_array( $jwt_parts ) || is_object( $jwt_parts ) ) && count( $jwt_parts ) === 3 ) {
					$payload = json_decode( base64_decode( $jwt_parts[1] ), true );

					if ( isset( $payload['exp'] ) ) {
						$current_time = time();
						$expires_in   = $payload['exp'] - $current_time;
						// If the token will expire in less than 7 days, regenerate
						if ( $expires_in < WEEK_IN_SECONDS ) {
							$apple_instance = new LoginPress_Apple();
							$apple_instance->generate_token( $social_logins );
						}
					}
				}
			}
		}

		/**
		 * Get the required keys for a specific provider.
		 *
		 * @param string $provider The provider name.
		 * @return array List of required keys for the provider.
		 * @since 4.0.0
		 */
		private function loginpress_get_required_keys_for_provider( $provider ) {
			$required_fields = array(
				'facebook'  => array( 'app_id', 'app_secret' ),
				'twitter'   => array( 'oauth_token', 'token_secret' ),
				'gplus'     => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'linkedin'  => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'microsoft' => array( 'app_id', 'app_secret', 'redirect_uri' ),
				'github'    => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'discord'   => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'wordpress' => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'apple'     => array( 'service_id', 'key_id', 'team_id', 'p_key' ),
			);

			return $required_fields[ $provider ] ?? array();
		}

		/**
		 * Ajax callback for updating verification status
		 */
		function loginpress_lpsl_settings_verification() {
			// Verify nonce for security
			check_ajax_referer( 'loginpress_ajax_nonce', 'security' );

			if ( ! isset( $_POST['provider'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Provider not specified', 'loginpress-pro' ) ) );
			}

			$provider = sanitize_text_field( $_POST['provider'] );
			// Retrieve existing settings
			$social_logins = get_option( 'loginpress_social_logins', array() );

			// Ensure it's an array before updating
			if ( ! is_array( $social_logins ) ) {
				$social_logins = array();
			}

			// Update the provider's status
			$social_logins[ "{$provider}_status" ] = 'verified';

			// Save the updated array
			update_option( 'loginpress_social_logins', $social_logins );
			/* Translators: verification for specific social provider. */
			wp_send_json_success( array( 'success' => sprintf( __('Verification updated for %s', 'loginpress-pro'), $provider) ) );
		}


		/**
		 * Ajax callback for updating providers order
		 */
		function loginpress_save_social_login_order() {
			// Verify nonce for security
			check_ajax_referer( 'loginpress_ajax_nonce', 'security' );

			if ( ! isset( $_POST['loginpress_provider_order'] ) || ! is_array( $_POST['loginpress_provider_order'] ) ) {
				wp_send_json_error( 'Invalid order data' );
			}

			$order = array_map( 'sanitize_text_field', $_POST['loginpress_provider_order'] );

			// Retrieve the existing 'loginpress_social_logins' option
			$social_logins = get_option( 'loginpress_social_logins', array() );

			// Ensure $social_logins is an array
			if ( ! is_array( $social_logins ) ) {
				$social_logins = array();
			}

			// Update only the order in the 'loginpress_social_logins' option
			$social_logins['provider_order'] = $order;
      
			// Save the updated option back to the database
			update_option( 'loginpress_social_logins', $social_logins );

			// Respond with success
			wp_send_json_success( 'Order saved successfully' );
		}
	}

endif;


if ( ! function_exists( 'loginpress_social_loader' ) ) {

	/**
	 * Returns the main instance of WP to prevent the need to use globals.
	 *
	 * @version 3.0.0
	 * @return LoginPress instance of Social Login class
	 */
	function loginpress_social_loader() {
		return LoginPress_Social::instance();
	}
}

add_action( 'plugins_loaded', 'loginpress_sl_instance', 25 );

/**
 * Check if LoginPress Pro is install and active.
 *
 * @version 3.0.0
 */
function loginpress_sl_instance() {

	// Call the function.
	loginpress_social_loader();
}



