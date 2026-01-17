<?php
/**
 * LoginPress hcaptcha.
 *
 * @since 4.0.0
 * @package LoginPress
 */

if ( ! class_exists( 'LoginPress_Hcaptcha' ) ) {

	/**
	 * LoginPress_Hcaptcha
	 */
	class LoginPress_Hcaptcha{

		/**
		 * Holds the singleton instance of the class.
		 *
		 * @var static|null
		 */
		protected static $instance = null;

		/**
		 *  Variable that Check for Captcha settings.
		 *
		 * @var string
		 * @since 4.0.0
		 */
		public $loginpress_captcha_settings;

		/**
		 * Class Constructor
		 */
		public function __construct($captcha) {
			$this->loginpress_captcha_settings = $captcha;
			$this->hooks();
		}

		/**
		 * Add all hooks.
		 *
		 * @return void
		 */
		private function hooks() {

            add_action( 'loginpress_hcaptcha_validate_key', array( $this, 'loginpress_hcaptcha_validate_key' ), 10 );
			$captchas_enabled = isset( $this->loginpress_captcha_settings['enable_captchas'] ) ? $this->loginpress_captcha_settings['enable_captchas'] : 'off';

			if ( $captchas_enabled !== 'off' ) {
				$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
                if ( $captchas_type === 'type_hcaptcha' ) {
					// Hcaptcha.
					$hcap_site_key   = isset( $this->loginpress_captcha_settings['hcaptcha_site_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_site_key'] : '';
					$hcap_secret_key = isset( $this->loginpress_captcha_settings['hcaptcha_secret_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_secret_key'] : '';

					if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_captcha_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_captcha_settings['hcaptcha_verified'] ) {

						$hcaptcha_login    = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['login_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['login_form'] : 'off';
						$hcaptcha_lost     = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['lostpassword_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['lostpassword_form'] : 'off';
						$hcaptcha_reg      = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['register_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['register_form'] : 'off';
						$hcaptcha_wc_login = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['woocommerce_login_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['woocommerce_login_form'] : 'off';
						$hcaptcha_wc_reg   = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['woocommerce_register_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['woocommerce_register_form'] : 'off';
						$hcaptcha_comment  = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['comment_form_defaults'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['comment_form_defaults'] : 'off';
						$hcaptcha_type     = isset( $this->loginpress_captcha_settings['hcaptcha_type'] ) ? $this->loginpress_captcha_settings['hcaptcha_type'] : 'normal';
						$action            = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // @codingStandardsIgnoreLine.

						if ( 'off' !== $hcaptcha_login ) {
							add_action( 'login_form', array( $this, 'loginpress_hcaptcha_field' ) );
							add_action( 'login_enqueue_scripts', array( $this, 'loginpress_hcaptcha_enqueue' ) );
							// For Widget
							add_action(
								'loginpress_after_login_form_widget',
								function () {
									$this->loginpress_hcaptcha_field();
									$this->loginpress_hcaptcha_enqueue();
								}
							);
						}

						if ( 'off' !== $hcaptcha_lost ) {
							add_action( 'lostpassword_form', array( $this, 'loginpress_hcaptcha_field' ) );
							add_action( 'login_enqueue_scripts', array( $this, 'loginpress_hcaptcha_enqueue' ) );
							// For Widget
							add_action(
								'loginpress_after_lost_password_form_widget',
								function () {
									$this->loginpress_hcaptcha_field();
									$this->loginpress_hcaptcha_enqueue();
								}
							);
						}

						if ( 'off' !== $hcaptcha_reg ) {
							add_action( 'register_form', array( $this, 'loginpress_hcaptcha_field' ) );
							add_action( 'login_enqueue_scripts', array( $this, 'loginpress_hcaptcha_enqueue' ) );
							// For Widget
							add_action(
								'loginpress_after_reg_form_widget',
								function () {
									$this->loginpress_hcaptcha_field();
									$this->loginpress_hcaptcha_enqueue();
								}
							);
						}

						if ( 'off' !== $hcaptcha_wc_login && ( $hcaptcha_type !== 'invisible'  && !isset($_POST['captcha_response'])) === true ) {
							add_action( 'woocommerce_login_form', array( $this, 'loginpress_hcaptcha_field' ) );
							add_action( 'woocommerce_login_form', array( $this, 'loginpress_hcaptcha_enqueue' ) );
							add_filter( 'authenticate', array( $this, 'loginpress_hcaptcha_auth' ), 99, 3 );
						}

						if ( 'off' !== $hcaptcha_wc_reg && ( $hcaptcha_type !== 'invisible'  && !isset($_POST['captcha_response'])) === true ) {
							add_action( 'woocommerce_register_form', array( $this, 'loginpress_hcaptcha_field' ) );
							add_action( 'woocommerce_register_form', array( $this, 'loginpress_hcaptcha_enqueue' ) );
							add_filter( 'woocommerce_register_post', array( $this, 'loginpress_hcaptcha_registration_auth' ), 10, 3 );
						}

						if ( 'off' !== $hcaptcha_comment && ( $hcaptcha_type !== 'invisible' ) === true  && !isset($_POST['captcha_response'])) {
							add_action( 'comment_id_fields', array( $this, 'loginpress_hcaptcha_comment_field' ) );
							add_action( 'comment_form', array( $this, 'loginpress_hcaptcha_enqueue' ) );
							add_action( 'pre_comment_on_post', array( $this, 'loginpress_hcaptcha_comment' ), 10 );

						}

						/* Authentication hCaptcha on login form */
						if ( ! isset( $_GET['customize_changeset_uuid'] ) && 'off' !== $hcaptcha_login && !isset($_POST['captcha_response']) ) {
							add_filter( 'authenticate', array( $this, 'loginpress_hcaptcha_auth' ), 99, 3 );
						}

						/* Authentication hCaptcha on lost-password form */
						if ( ! isset( $_GET['customize_changeset_uuid'] ) && 'off' !== $hcaptcha_lost && isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' && !isset($_POST['captcha_response'])) {
							add_filter( 'allow_password_reset', array( $this, 'loginpress_hcaptcha_lostpassword_auth' ), 10, 2 );
						}

						/* Authentication hCaptcha on registration form */
						if ( ! isset( $_GET['customize_changeset_uuid'] ) && 'off' !== $hcaptcha_reg && 'register' === $action && !isset($_POST['captcha_response'])) {
							add_filter( 'register_post', array( $this, 'loginpress_hcaptcha_registration_auth' ), 10, 3 );
						}
					}
				}
            }
        }

        /**
		 * Render the hCaptcha field.
		 *
		 * @since 4.0.0
		 */
		public function loginpress_hcaptcha_field() {
			
			$hcap_site_key  = isset( $this->loginpress_captcha_settings['hcaptcha_site_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_site_key'] : '';
			$hcaptcha_type  = isset( $this->loginpress_captcha_settings['hcaptcha_type'] ) ? $this->loginpress_captcha_settings['hcaptcha_type'] : 'normal';
			$hcaptcha_theme = isset( $this->loginpress_captcha_settings['hcaptcha_theme'] ) ? $this->loginpress_captcha_settings['hcaptcha_theme'] : 'light';
			if ( $hcaptcha_type === 'invisible' ) {
				$onSubmit = 'onSubmit';
			} else {
				$onSubmit = '';
			}
			?>
			<div class="h-captcha-container">
				<div
					class="h-captcha"
					data-sitekey="<?php echo esc_attr( $hcap_site_key ); ?>"
					data-theme="<?php echo esc_attr( $hcaptcha_theme ); ?>"
					data-size="<?php echo esc_attr( $hcaptcha_type ); ?>"
					data-callback ="<?php echo $onSubmit; ?>">
				</div>
			</div>
			<?php
		}

		/**
		 * Add reCaptcha field just before the Post button
		 *
		 * @param array $default Default parameter array for reCaptcha.
		 * @since 4.0.0
		 * @return array $default all the fields.
		 */
		public function loginpress_hcaptcha_comment_field( $default ) {
			$this->loginpress_hcaptcha_field();

			return $default;
		}

		/**
		 * Enqueue hCaptcha script based on the settings.
		 * 
		 * @param string $form The source or context of the form triggering the enqueue. 
		 *                            Possible values: 'ld' (LearnDash), 'woo' (WooCommerce), 
		 *                            'bp' (BuddyBoss), 'edd' (Easy Digital Downloads), 
		 *                            'llms' (LifterLMS), 'llms-reg' (LifterLMS Register), etc.
		 * @since 	4.0.0
		 * @version 5.0.0
		 */
		public function loginpress_hcaptcha_enqueue($form = '') {
			
			$hcap_site_key   = isset( $this->loginpress_captcha_settings['hcaptcha_site_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_site_key'] : '';
			$hcap_secret_key = isset( $this->loginpress_captcha_settings['hcaptcha_secret_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_secret_key'] : '';
			$cap_language    = isset( $this->loginpress_captcha_settings['hcaptcha_language'] ) ? $this->loginpress_captcha_settings['hcaptcha_language'] : 'en';
			$hcaptcha_type   = isset( $this->loginpress_captcha_settings['hcaptcha_type'] ) ? $this->loginpress_captcha_settings['hcaptcha_type'] : 'normal';
			$hcaptcha_login  = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['login_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['login_form'] : 'off';
			$hcaptcha_lost   = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['lostpassword_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['lostpassword_form'] : 'off';
			$hcaptcha_reg    = isset( $this->loginpress_captcha_settings['hcaptcha_enable']['register_form'] ) ? $this->loginpress_captcha_settings['hcaptcha_enable']['register_form'] : 'off';
			if ( $hcaptcha_type !== 'invisible' && ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) ) {
				wp_enqueue_script( 'loginpress_hcaptcha_lang', 'https://js.hcaptcha.com/1/api.js?hl=' . $cap_language, array(), LOGINPRESS_PRO_VERSION, true );
			} else {
				wp_enqueue_script( 'loginpress_hcaptcha_lang', 'https://js.hcaptcha.com/1/api.js?hl=' . $cap_language, array(), LOGINPRESS_PRO_VERSION, true );

				if ( ! wp_script_is( 'loginpress_hcaptcha_submit', 'enqueued' ) ) {
					wp_enqueue_script( 'loginpress_hcaptcha_submit', LOGINPRESS_PRO_DIR_URL . 'assets/js/hcaptcha.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, true );
				
					$integ_settings = get_option( 'loginpress_integration_settings' );
					$woo_captcha_login     = isset( $integ_settings['enable_captcha_woo']['woocommerce_login_form'] ) ? $integ_settings['enable_captcha_woo']['woocommerce_login_form'] : false;
					$woo_captcha_register     = isset( $integ_settings['enable_captcha_woo']['woocommerce_register_form'] ) ? $integ_settings['enable_captcha_woo']['woocommerce_register_form'] : false;	
					$enabled_forms = array(
						'hcaptcha_login' => $hcaptcha_login,
						'hcaptcha_lost'  => $hcaptcha_lost,
						'hcaptcha_reg'   => $hcaptcha_reg,
					);
					if( $woo_captcha_login ){
						$enabled_forms['hcaptcha_woo_log'] = 'woo_log';
					}
					if( $woo_captcha_register ){
						$enabled_forms['hcaptcha_woo_reg'] = 'woo_reg';
					}
				} else {
					// Already enqueued before? Then pull existing localized data if possible
					$enabled_forms = wp_scripts()->get_data( 'loginpress_hcaptcha_submit', 'data' );
				
					if ( $enabled_forms ) {
						$enabled_forms = json_decode( str_replace( 'var enabled_form = ', '', rtrim( $enabled_forms, ';' ) ), true );
					} else {
						$enabled_forms = array();
					}
				}
			
				switch ( $form ) {
					case 'ld':
						$enabled_forms['hcaptcha_ld'] = 'ld';
						break;
					case 'woo':
						$enabled_forms['hcaptcha_woo_co'] = 'woo_co';
						break;
					case 'bp':
						$enabled_forms['hcaptcha_bp_signup'] = 'bp_signup';
						break;
					case 'edd':
						$enabled_forms['hcaptcha_edd_log'] = 'edd';
						break;
					case 'llms':
						$enabled_forms['hcaptcha_llms_log'] = 'llms';
						break;
					case 'llms-reg':
						$enabled_forms['hcaptcha_llms_reg'] = 'llms_reg';
						break;
					default:
						break;
				}
				
				wp_localize_script( 'loginpress_hcaptcha_submit', 'enabled_form', $enabled_forms );
			}
		}

		/**
		 * hCaptcha verification on login form.
		 *
		 * @param WP_User|WP_Error $user     The user object or WP_Error on failure.
		 * @param string           $username The username.
		 * @param string           $password The password.
		 *
		 * @since 4.0.0
		 * @return WP_User|WP_Error $user     The user object or WP_Error on failure.
		 */
		public function loginpress_hcaptcha_auth( $user, $username, $password ) {
			if (  isset( $_POST['_llms_login_user_nonce'] ) || isset( $_POST['learndash-login-form'] ) ) {
				// This is lifterlms/learndash login, skip reCAPTCHA validation here as they are been validated in their own classes
				return $user;
			}
			$hcap_secret_key = isset( $this->loginpress_captcha_settings['hcaptcha_secret_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_secret_key'] : '';
			if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) {
                $post_response =  isset( $_POST['h-captcha-response']) ? $_POST['h-captcha-response'] : $_POST['captcha_response'];
				$response = wp_remote_post(
					'https://hcaptcha.com/siteverify',
					array(
						'body' => array(
							'secret'   => $hcap_secret_key,
							'response' => sanitize_text_field( $post_response ),
							'remoteip' => $_SERVER['REMOTE_ADDR'],
						),
					)
				);

				$response_body = wp_remote_retrieve_body( $response );
				$result        = json_decode( $response_body );
				if ( $username && $password && ! $result->success ) {
					return new WP_Error( 'hcaptcha_error', $this->loginpress_hcaptcha_error() );
				}
			} elseif ( (isset( $_POST['wp-submit']) || isset( $_POST['login'] )) && ! isset( $_POST['h-captcha-response'] ) ) {
				return new WP_Error( 'hcaptcha_error', $this->loginpress_hcaptcha_error() );
			}
			return $user;
		}

		/**
		 * hCaptcha verification on lost password form.
		 *
		 * @param bool $allow To allow user access.
		 * @param int  $user_id User ID.
		 *
		 * @since 4.0.0
		 * @return int|WP_Error $user_id User ID or WP_Error on failure.
		 */
		public function loginpress_hcaptcha_lostpassword_auth( $allow, $user_id ) {

			$hcap_secret_key = isset( $this->loginpress_captcha_settings['hcaptcha_secret_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_secret_key'] : '';
			$cap_response    = isset( $_POST['captcha_response'] ) ? $_POST['captcha_response'] : '';
			if ( isset( $_POST['h-captcha-response'] ) || $cap_response ) {
				$response = wp_remote_post(
					'https://hcaptcha.com/siteverify',
					array(
						'body' => array(
							'secret'   => $hcap_secret_key,
							'response' => $cap_response ? $cap_response : sanitize_text_field( $_POST['h-captcha-response'] ),
							'remoteip' => $_SERVER['REMOTE_ADDR'],
						),
					)
				);

				$response_body = wp_remote_retrieve_body( $response );
				$result        = json_decode( $response_body );

				if ( ! $result->success ) {
					return new WP_Error( 'hcaptcha_error', $this->loginpress_hcaptcha_error() );
				}
			} elseif ( ! isset( $_POST['h-captcha-response'] ) ) {
				return new WP_Error( 'hcaptcha_error', $this->loginpress_hcaptcha_error() );
			}

			return $allow;
		}

		/**
		 * hCaptcha verification for registration form.
		 *
		 * @param string   $sanitized_user_login User's username after it has been sanitized.
		 * @param string   $user_email User's email.
		 * @param WP_Error $errors A WP_Error object containing any errors encountered during registration.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function loginpress_hcaptcha_registration_auth( $sanitized_user_login, $user_email, $errors ) {
			$hcap_secret_key = isset( $this->loginpress_captcha_settings['hcaptcha_secret_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_secret_key'] : '';
			if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) {
				$response = wp_remote_post(
					'https://hcaptcha.com/siteverify',
					array(
						'body' => array(
							'secret'   => $hcap_secret_key,
							'response' => sanitize_text_field( $_POST['h-captcha-response'] ),
							'remoteip' => $_SERVER['REMOTE_ADDR'],
						),
					)
				);

				$response_body = wp_remote_retrieve_body( $response );
				$result        = json_decode( $response_body );

				if ( ! $result->success ) {
					$errors->add( 'hcaptcha_error', $this->loginpress_hcaptcha_error() );
				}
			} elseif ( ! isset( $_POST['h-captcha-response'] ) ) {
				$errors->add( 'hcaptcha_error', $this->loginpress_hcaptcha_error() );
			}
		}

		/**
		 * hCaptcha verification for comments form.
		 *
		 * @since 4.0.0
		 */
		public function loginpress_hcaptcha_comment() {

			$hcap_secret_key = isset( $this->loginpress_captcha_settings['hcaptcha_secret_key'] ) ? $this->loginpress_captcha_settings['hcaptcha_secret_key'] : '';
			$error    =  $this->loginpress_hcaptcha_error();
			if ( isset( $_POST['h-captcha-response'] ) ) {  // @codingStandardsIgnoreLine.
				$response = wp_remote_post(
					'https://hcaptcha.com/siteverify',
					array(
						'body' => array(
							'secret'   => $hcap_secret_key,
							'response' => sanitize_text_field( $_POST['h-captcha-response'] ),
							'remoteip' => $_SERVER['REMOTE_ADDR'],
						),
					)
				);

				$response_body = wp_remote_retrieve_body( $response );
				$result        = json_decode( $response_body );

				if ( ! $result->success ) {

					wp_die( $error );  // @codingStandardsIgnoreLine.
				}
			} elseif ( ! isset( $_POST['h-captcha-response'] ) ) {
				wp_die( $error );
			}
		}

		/**
		 * [loginpress_hcaptcha_error hcaptcha error message]
		 *
		 * @return [string] [Custom error message]
		 * @version 4.0.0
		 */
		public function loginpress_hcaptcha_error() {

			$loginpress_settings = get_option( 'loginpress_customization' );
			$hcaptcha_message   = isset( $loginpress_settings['hcaptcha_error_message'] ) ? $loginpress_settings['hcaptcha_error_message'] : __( '<strong>ERROR:</strong> Please verify hCaptcha', 'loginpress-pro' );

			$allowed_html = array(
				'a'      => array(),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'i'      => array(),
			);
			return wp_kses( $hcaptcha_message, $allowed_html );
		}

		/**
		 * Main Instance
		 *
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {
				$loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
				self::$instance = new self( $loginpress_captcha_settings);
			}
			return self::$instance;
		}

        /**
		 * Validate the keys for the hcaptcha.
		 *
		 * @since 4.0.0
		 * @return string The code for the validation .
		 */
        public function loginpress_hcaptcha_validate_key() {

			$hcaptcha_verified = isset( $this->loginpress_captcha_settings['hcaptcha_verified'] ) && ! empty( $this->loginpress_captcha_settings['hcaptcha_verified'] ) ? $this->loginpress_captcha_settings['hcaptcha_verified'] : 'off';
				?>
				<div id="hcaptcha_container"></div>
				<script>
					jQuery(document).ready(function($) {

						var $hcaptcha_enabled = '<?php echo $hcaptcha_verified; ?>';
						var $site_key = $('input[name="loginpress_captcha_settings[hcaptcha_site_key]"]');
						var $secret_key = $('input[name="loginpress_captcha_settings[hcaptcha_secret_key]"]');
						var activeTheme = $('tr.hcaptcha_theme .options li.active').attr('rel');
						var $captcha_type = $('tr.captchas_type select');
						var $state = 'false';

						function validate_hcaptcha( $state = 'false' ) {

							$('tr.validate_hcaptcha_keys').hide();
							var $submit_btn = $('#loginpress_captcha_settings #submit');

							$submit_btn.prop('disabled', false);
							var CaptchaType = $captcha_type.val();
							
							if ( CaptchaType == 'type_hcaptcha' ) {

								if ( !$site_key.val() || !$secret_key.val() ) {
									$submit_btn.prop('disabled', true);
								} else {
									if ( $state == 'true' ) {
										$submit_btn.prop( 'disabled', true);

										$(document).ready(function () {
											$('#hcaptcha_container').html('');
											hcaptcha.render('hcaptcha_container', {
												sitekey: $site_key.val(),
												theme: activeTheme,
												size: 'normal',
												'callback': function (token) {
													// Enable the submit button after hCaptcha is solved
													$submit_btn.prop('disabled', false);
												},
											});
										});

										$('#hcaptcha_container').show();
										$('tr.validate_hcaptcha_keys').show();
									}

								}
							}

						}

						validate_hcaptcha();
						$site_key.on( 'input', function () {
							$('tr.hcaptcha_site_key svg').hide();
							validate_hcaptcha( 'true' );
						});
						$secret_key.on('input', function () {
							$('tr.hcaptcha_secret_key svg').hide();
							validate_hcaptcha( 'true' );
						});

						// variable for svg
						var CorrectIcon = `<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><title>success</title><g fill="none" class="nc-icon-wrapper"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 26c7.18 0 13-5.82 13-13S20.18 0 13 0 0 5.82 0 13s5.82 13 13 13z" fill="#22A753"/><path d="M11 14.586l6.293-6.293a1 1 0 1 1 1.414 1.414L11 17.414l-3.707-3.707a1 1 0 1 1 1.414-1.414L11 14.586z" fill="#fff"/></g></svg>`;
						var WrongIcon   = `<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><title>Error</title><g fill="none" class="nc-icon-wrapper"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 26c7.18 0 13-5.82 13-13S20.18 0 13 0 0 5.82 0 13s5.82 13 13 13z" fill="#CB2431"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.183 8.183a.625.625 0 0 0 0 .884L12.116 13l-3.933 3.933a.625.625 0 1 0 .884.884L13 13.884l3.933 3.933a.625.625 0 1 0 .884-.884L13.884 13l3.933-3.933a.625.625 0 1 0-.884-.884L13 12.116 9.067 8.183a.625.625 0 0 0-.884 0z" fill="#fff"/><path d="M8.183 9.067l-.353.354.353-.354zm0-.884L7.83 7.83l.353.353zM12.116 13l.354.354.353-.354-.353-.354-.354.354zm-3.933 3.933l-.353-.354.353.354zm0 .884l-.353.354.353-.354zM13 13.884l.354-.354-.354-.353-.354.353.354.354zm3.933 3.933l.354-.354-.354.354zm.884-.884l-.354.354.354-.354zM13.884 13l-.354-.354-.353.354.353.354.354-.354zm3.933-4.817l.354-.353-.354.353zm-.884 0l-.354-.353.354.353zM13 12.116l-.354.354.354.353.354-.353-.354-.354zM9.067 8.183l.354-.353-.354.353zm-.53.53a.125.125 0 0 1 0-.176l-.708-.708c-.439.44-.439 1.152 0 1.592l.708-.708zm3.933 3.933L8.537 8.713l-.708.708 3.934 3.933.707-.708zm-3.933 4.64l3.933-3.932-.707-.708L7.83 16.58l.707.708zm0 .177a.125.125 0 0 1 0-.176l-.708-.707c-.439.439-.439 1.151 0 1.59l.708-.707zm.176 0a.125.125 0 0 1-.176 0l-.708.707c.44.44 1.152.44 1.592 0l-.708-.707zm3.933-3.933l-3.933 3.933.708.707 3.933-3.933-.708-.707zm4.64 3.933l-3.932-3.933-.708.707 3.933 3.934.708-.708zm.177 0a.125.125 0 0 1-.176 0l-.707.707c.439.44 1.151.44 1.59 0l-.707-.707zm0-.176a.125.125 0 0 1 0 .176l.707.707c.44-.439.44-1.151 0-1.59l-.707.707zm-3.933-3.933l3.933 3.933.707-.707-3.933-3.934-.707.708zm3.933-4.64l-3.933 3.932.707.708 3.934-3.933-.708-.708zm0-.177a.125.125 0 0 1 0 .176l.707.708c.44-.44.44-1.152 0-1.591l-.707.707zm-.176 0a.125.125 0 0 1 .176 0l.707-.708a1.125 1.125 0 0 0-1.59 0l.707.708zm-3.933 3.933l3.933-3.933-.707-.708-3.934 3.934.708.707zm-4.64-3.933l3.932 3.933.708-.707L9.42 7.83l-.708.707zm-.177 0a.125.125 0 0 1 .176 0l.708-.708a1.125 1.125 0 0 0-1.591 0l.707.708z" fill="#fff"/></g></svg>`;

						if ( $hcaptcha_enabled == 'on' ) {
							$site_key.wrap('<div class="hcaptcha-input-container"></div>');
							$secret_key.wrap('<div class="hcaptcha-input-container"></div>');
							$('.hcaptcha-input-container').append(CorrectIcon);
						} else if ( $hcaptcha_enabled != 'on' ) {
							$site_key.wrap('<div class="hcaptcha-input-container"></div>');
							$secret_key.wrap('<div class="hcaptcha-input-container"></div>');
							$('.hcaptcha-input-container').append(WrongIcon);
						}

					});
				</script>
				<?php

		}
    }
}