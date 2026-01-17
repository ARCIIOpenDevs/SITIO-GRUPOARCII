<?php
/**
 * LoginPress Integration Class.
 *
 * Handles the integration of different LoginPress features with other plugins.
 * This includes initialization, settings access, and third-party service hooks
 * such as Turnstile, reCAPTCHA, or hCaptcha.
 *
 * @since 5.0.0
 * @package LoginPress
 */

if ( ! class_exists( 'LoginPress_Integration' ) ) {

	/**
	 * LoginPress_Integration
	 */
	class LoginPress_Integration {

		/**
		 * Variable that Check for LoginPress settings.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $loginpress_settings;

		/**
		 *  Variable that Check for Captcha settings.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $loginpress_captcha_settings;

		/**
		 *  Variable that contains the image of social login position.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $loginpress_social_position_image;

		/**
		 * Class Constructor
		 */
		public function __construct() {
            if ( is_plugin_active( 'lifterlms/lifterlms.php' ) || is_plugin_active_for_network( 'lifterlms/lifterlms.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integration/lifterlms/loginpress-lifterlms.php';
			}
            if ( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) || is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integration/learndash/loginpress-learndash.php';
			}
			if ( 
				( is_plugin_active( 'buddypress/bp-loader.php' ) || is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ) 
				&& ! is_plugin_active( 'buddyboss-platform/bp-loader.php' ) 
			) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integration/buddypress/loginpress-buddypress.php';
			}
			if ( is_plugin_active( 'buddyboss-platform/bp-loader.php' ) || is_plugin_active_for_network( 'buddyboss-platform/bp-loader.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integration/buddyboss/loginpress-buddyboss.php';
			}
			if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) || is_plugin_active_for_network( 'easy-digital-downloads/easy-digital-downloads.php.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integration/edd/loginpress-edd.php';
			}
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integration/woocommerce/loginpress-woocommerce.php';
			}

			$this->loginpress_settings         = get_option( 'loginpress_setting' );
			$this->loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
			$this->loginpress_social_position_image = '<img src="'.LOGINPRESS_PRO_DIR_URL.'addons/social-login/assets/img/below-with-seprator.svg" alt="Default" class="lp-hover-image">';
			$this->hooks();
		}

		/**
		 * Register Integration-related hooks for LoginPress.
		 * @since 5.0.0
		 * @return void
		 */
		private function hooks() {

			add_filter( 'loginpress_settings_tab', array( $this, 'integrations_tab' ), 20 );
			add_filter( 'loginpress_settings_fields', array( $this, 'integration_settings_field' ), 10, 1 );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_init', array( $this, 'loginpress_pro_admin_init' ), 7 );
			
            
            $captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';

			if ( ! class_exists( 'LoginPress_Recaptcha' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-recaptcha.php';
				new LoginPress_Recaptcha($this->loginpress_settings, $this->loginpress_captcha_settings);
			}	else {
				LoginPress_Recaptcha::instance();
			}
			
			if ( ! class_exists( 'LoginPress_Hcaptcha' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-hcaptcha.php';
				new LoginPress_Hcaptcha( $this->loginpress_captcha_settings);
			} else {
				LoginPress_Hcaptcha::instance();
			}

			if ( ! class_exists( 'LoginPress_Turnstile' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-turnstile.php';
				new LoginPress_Turnstile( $this->loginpress_captcha_settings);
			}	else {
				LoginPress_Turnstile::instance();
			}
				

		}

        /**
         * Load CSS and JS files at admin side on loginpress-settings page only.
         *
         * @param string $hook the Page ID.
         * @since 5.0.0
         *
         * @return void
         */
        public function admin_scripts( $hook ) {
			if ( 'toplevel_page_loginpress-settings' === $hook ) {
			
				wp_enqueue_script( 'loginpress_integration', LOGINPRESS_PRO_DIR_URL . '/integration/assets/js/main.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, true );
				wp_enqueue_style( 'loginpress_integration_css', LOGINPRESS_PRO_DIR_URL . '/integration/assets/css/main.css', LOGINPRESS_PRO_VERSION, false );
				wp_localize_script(
					'loginpress_integration',
					'loginpress_redirect_sociallogins',
					array(
						'group_nonce' => wp_create_nonce( 'loginpress-group-redirects-nonce' ),
						'translate'  => array(
							// translators: Label for LearnDash group search field in Login Redirect addon.
								_x( 'Search Group', 'The label Text of Login Redirect addon learndash group search field', 'loginpress-pro' ),
								// translators: Description text for LearnDash group tab's search field in Login Redirect addon.
								_x( 'Search group For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific learndash group tab\'s search', 'loginpress-pro' ),
							),
					)
				);
		
				wp_localize_script(
					'loginpress_integration',
					'loginpress_redirect_learndash',
					array(
						'translate'  => array(
							// translators: Description text for Specific Roles tab's search field in Login Redirect addon.
							_x( 'Search Role For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific Roles tab\'s search', 'loginpress-pro' ),
							// translators: Search data
							sprintf( _x( '%1$sSearch user\'s data from below the list%2$s', 'Search Label on Data tables', 'loginpress-pro' ), '<p class="description">', '</p>' ),
							// translators: Placeholder text for the search keyword field for autologin users.
							_x( 'Enter keyword', 'The search keyword for the autologin users.', 'loginpress-pro' ),
						),
					)
				);
				wp_localize_script(
					'loginpress_integration',
					'loginpress_integration_data',
					array(
						'plugins' => array(
							'woocommerce' => array(
								'description' => esc_html__( 'Quick, secure logins for your WooCommerce store.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'woocommerce/woocommerce.php' ),
							),
							'edd' => array(
								'description' => esc_html__( 'Secure digital purchases with login enhancements.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'easy-digital-downloads/easy-digital-downloads.php' ),
							),
							'buddypress' => array(
								'description' => esc_html__( 'Boost community logins with social and captcha support.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'buddypress/bp-loader.php' ),
							),
							'buddyboss' => array(
								'description' => esc_html__( 'Hassle-free login experience for your BuddyBoss community.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'buddyboss-platform/bp-loader.php' ),
							),
							'lifterlms' => array(
								'description' => esc_html__( 'Let students log in easily and securely.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'lifterlms/lifterlms.php' ),
							),
							'learndash' => array(
								'description' => esc_html__( 'Simplify learning access with our login tools.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'sfwd-lms/sfwd_lms.php' ),
							),
						),
					),
				);
				wp_localize_script(
					'loginpress_integration',
					'loginpress_integration_translations',
					array(
						'learnMore' 		=> _x( 'Configuration Guide', 'Link to documentation', 'loginpress-pro' ),
						'back'              => esc_html__( 'Back', 'loginpress-pro' ),
						'messageFirst'      => esc_html__( 'Activate', 'loginpress-pro' ),
						'messageLast'       => esc_html__( 'to proceed', 'loginpress-pro' ),
						'configure'         => esc_html__( 'Configure', 'loginpress-pro' ),
						'helpCenter'        => esc_html__( 'Help Center', 'loginpress-pro' ),
						'followGuide'       => esc_html__( 'Follow our step-by-step guide for', 'loginpress-pro' ),
						'integrationGuide'  => esc_html__( 'Integration Guide', 'loginpress-pro' ),
					)
				);
			}
    
        }

		/**
		 * Check the status of each plugin
		 *
		 * @param string $plugin_file Plugins main file.
		 *
		 * @since 5.0.0
		 * @return string The status of the provider plugin.
		 */
		public function loginpress_get_plugin_status( $plugin_file ) {
			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		
			if ( ! file_exists( $plugin_path ) ) {
				return esc_html__( 'learn-more', 'loginpress-pro' );
			}
		
			if ( ! is_plugin_active( $plugin_file ) ) {
				return esc_html__( 'not-active', 'loginpress-pro' );
			}
		
			// Specific handling to prevent false positives between BuddyPress and BuddyBoss
			if ( $plugin_file === 'buddypress/bp-loader.php' && class_exists( 'LoginPress_Buddyboss_Integration' ) ) {
				return esc_html__( 'not-active', 'loginpress-pro' ); // BuddyBoss is active, not BuddyPress
			}
		
			return esc_html__( 'active', 'loginpress-pro' );
		}

		/**
		 * reCaptcha Settings tab's
		 *
		 * @param array $loginpress_tabs The setting tabs.
		 *
		 * @since 5.0.0
		 * @return array The reCaptcha setting tabs and their attributes.
		 */
		public function integrations_tab( $loginpress_tabs ) {
			$new_tab = array(
				array(
					'id'         => 'loginpress_integration_settings',
					'title'      => esc_html__( 'Integrations', 'loginpress-pro' ),
					'sub-title'  => esc_html__( 'Integration with other plugins', 'loginpress-pro' ),
					/* Translators: The Captcha tabs */
					'desc'       => $this->tab_desc(),
					// 'video_link' => '26dUFdX2srU',
				),
			);
			return array_merge( $loginpress_tabs, $new_tab );
		}

		/**
		 * The tab_desc description of the tab 'Captcha Settings'
		 *
		 * @since 5.0.0
		 * @return html $html The tab description.
		 */
		public function tab_desc() {
			$html = wp_kses_post(
				sprintf( // translators: Loginpress Integration tab
				__( '%1$s LoginPress integrates with the most popular WordPress plugins to enhance your login experience. Our Social Login, CAPTCHA and Limit Login Attempts features among others are easily integrated into these platforms, helping you streamline user access and enhance security. %2$s', 'loginpress-pro' ),
				'<p>',
				'</p>'
			) );
			
			return $html;
		}

		/**
		 * Add the settings fields for the Social Login.
		 *
		 * @since 5.0.0
		 * @param array $setting_array The social login setting array.
		 *
		 * @return array An array of setting's fields and their corresponding attributes.
		 */
		public function integration_settings_field( $setting_array ) {

			$apply_recaptcha_to = array();

			//if ( class_exists( 'woocommerce' ) ) {

            $woo_recaptcha_options = array(
                'woocommerce_login_form'    => esc_html__( 'WooCommerce Login Form', 'loginpress-pro' ),
                'woocommerce_register_form' => esc_html__( 'WooCommerce Register Form', 'loginpress-pro' ),
				'woocommerce_checkout_form' => esc_html__( 'WooCommerce Checkout Form', 'loginpress-pro' ),
            );
			$button_position_options = array(
				'default' => esc_html__( 'Default (Show below the separator)', 'loginpress-pro' ),
				'below'   => esc_html__( 'Below (Show below the fields)', 'loginpress-pro' ),
				'above'   => esc_html__( 'Above (Show above the fields)', 'loginpress-pro' ),
				'above_separator' => esc_html__( 'Above with Separator (Show above the separator)', 'loginpress-pro' ),
			);

            $apply_recaptcha_to = array_merge( $apply_recaptcha_to, $woo_recaptcha_options );

			$_new_tabs = array(
				array(
					'name'    => 'enable_captcha_woo',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => $apply_recaptcha_to,
				),
                array(
					'name'    => 'enable_social_woo_lf',
					'label'   => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on WooCommerce login form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_woo_lf",
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_woo_rf',
					'label'   => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on WooCommerce register form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_woo_rf",
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_woo_co',
					'label'   => esc_html__( 'Checkout Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on WooCommerce checkout form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_woo_co",
					'label'   => esc_html__( 'Button Position on Checkout Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
                array(
					'name'    => 'enable_captcha_ld',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ). '<hr>',
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => array(
                        'login_learndash'    => 'Login Form',
                        'register_learndash' => 'Register Form',
                    ),
				),
				array(
					'name'    => 'enable_social_ld_lf',
					'label'   => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on LearnDash login form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_ld_lf",
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_ld_rf',
					'label'   => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on LearnDash register form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_ld_rf",
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_ld_qf',
					'label'   => esc_html__( 'Quiz', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on LearnDash Quiz.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_ld_qf",
					'label'   => esc_html__( 'Button Position on Quiz', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => array(
						'below'   => esc_html__( 'Below (Show below the fields)', 'loginpress-pro' ),
						'above'   => esc_html__( 'Above (Show above the fields)', 'loginpress-pro' ),
					),
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_captcha_llms',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ). '<hr>',
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => array(
						'lifter_login_form' => esc_html__( 'LifterLMS Login Form', 'loginpress-pro' ),
						'lifter_register_form' => esc_html__( 'LifterLMS Register Form', 'loginpress-pro' ),
						'lifter_lostpassword_form' => esc_html__( 'LifterLMS Lost Password Form', 'loginpress-pro' ),
						'lifter_purchase_form' => esc_html__( 'LifterLMS Purchase Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'    => 'enable_social_llms_lf',
					'label'   => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on LifterLMS login form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_llms_lf",
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_llms_rf',
					'label'   => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on LifterLMS register form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_llms_rf",
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_llms_co',
					'label'   => esc_html__( 'Purchase Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on LifterLMS purchase form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_llms_co",
					'label'   => esc_html__( 'Button Position on Purchase Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_captcha_bp',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ). '<hr>',
					'type'    => 'multicheck',
					'options' => array(
                        'register_bp_block' => esc_html__('Register Form', 'loginpress-pro'),
                    ),
				),
				array(
					'name'    => 'enable_social_login_links_bp',
					'label'   => esc_html__( 'Enable Social Login on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'BuddyPress Register Form', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_bp",
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image,
				),
				array(
					'name'    => 'enable_captcha_bb',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ). '<hr>',
					'type'    => 'multicheck',
					'options' => array(
                        'register_bb_block' => esc_html__('Register Form', 'loginpress-pro'),
                    ),
				),
				array(
					'name'    => 'enable_social_login_links_bb',
					'label'   => esc_html__( 'Enable Social Login on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'BuddyBoss Register Form', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_bb",
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image,
				),
				array(
					'name'    => 'enable_captcha_edd',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ) ,
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ). '<hr>',
					'type'    => 'multicheck',
					'options' => array(
						'login_edd_block' => esc_html__('Login Form', 'loginpress-pro'),
                        'register_edd_block' => esc_html__('Register Form', 'loginpress-pro'),
						'checkout_edd_block' => esc_html__('Checkout Form', 'loginpress-pro'),
                    ),
				),
				array(
					'name'    => 'enable_social_edd_lf',
					'label'   => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on EDD login form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_edd_lf",
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_edd_rf',
					'label'   => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on EDD register form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_edd_rf",
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
				array(
					'name'    => 'enable_social_edd_co',
					'label'   => esc_html__( 'Checkout Form', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Enable to add Social Login on EDD checkout form.', 'loginpress-pro' ),
					'type'    => 'checkbox',
				),
				array(
					'name'    => "social_position_edd_co",
					'label'   => esc_html__( 'Button Position on Checkout Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed
				),
			);

			$_new_tabs = array( 'loginpress_integration_settings' => $_new_tabs );
			return array_merge( $setting_array, $_new_tabs );
		}

		/**
		 * Initialize admin settings for LoginPress Pro .
		 *
		 * @since 5.0.0
		 */
		public function loginpress_pro_admin_init() {
			$captchas_enabled = isset( $this->loginpress_captcha_settings['enable_captchas'] ) ? $this->loginpress_captcha_settings['enable_captchas'] : 'off';
			//$loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
			if ( defined( 'LOGINPRESS_PRO_VERSION' ) && version_compare( LOGINPRESS_PRO_VERSION, '4.0.0', '>=' ) && $captchas_enabled !== 'off' ) {
				$captcha_settings = $this->loginpress_captcha_settings;
				$integration_settings = get_option( 'loginpress_integration_settings', array() );
				if ( ! empty($integration_settings)){
					return;
				}
				if ( ! is_array( $integration_settings ) ) {
					$integration_settings = array(); // fallback to empty array
				}
				// Check if migration has already been done
				// if ( get_option( 'woocommerce_captcha_migrated' ) ) {
				// 	return;
				// }

				// WooCommerce CAPTCHA settings keys
				$woocommerce_keys = [ 'woocommerce_login_form', 'woocommerce_register_form' ];
				$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
				// Check if WooCommerce CAPTCHA settings exist
				$woocommerce_captcha_settings = [];
				if ( $captchas_type === 'type_hcaptcha' ){
					if ( isset( $captcha_settings['hcaptcha_enable'] ) ) {
						foreach ( $woocommerce_keys as $key ) {
							if ( isset( $captcha_settings['hcaptcha_enable'][ $key ] ) ) {
								$woocommerce_captcha_settings[ $key ] = $captcha_settings['hcaptcha_enable'][ $key ];
							}
						}
					}
				} else if ( $captchas_type === 'type_cloudflare' ){
					if ( isset( $captcha_settings['captcha_enable_cf'] ) ) {
						foreach ( $woocommerce_keys as $key ) {
							if ( isset( $captcha_settings['captcha_enable_cf'][ $key ] ) ) {
								$woocommerce_captcha_settings[ $key ] = $captcha_settings['captcha_enable_cf'][ $key ];
							}
						}
					}
				} else {
					if ( isset( $captcha_settings['captcha_enable'] ) ) {
						foreach ( $woocommerce_keys as $key ) {
							if ( isset( $captcha_settings['captcha_enable'][ $key ] ) ) {
								$woocommerce_captcha_settings[ $key ] = $captcha_settings['captcha_enable'][ $key ];
							}
						}
					}
				}
				// If there are WooCommerce CAPTCHA settings, migrate them
				if ( ! empty( $woocommerce_captcha_settings ) ) {
					// Add settings to integration options
					$integration_settings['enable_captcha_woo'] = $woocommerce_captcha_settings;

					// Remove WooCommerce settings from captcha settings
					foreach ( $woocommerce_keys as $key ) {
						unset( $captcha_settings['captcha_enable'][ $key ] );
					}

					// Save the updated settings
					update_option( 'loginpress_integration_settings', $integration_settings );
					update_option( 'loginpress_captcha_settings', $captcha_settings );

					// Set a flag to prevent re-running
					// update_option( 'woocommerce_captcha_migrated', true );
				}
			}
		}
    }
}