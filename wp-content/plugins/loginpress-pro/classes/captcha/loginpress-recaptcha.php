<?php
/**
 * LoginPress reCAPTCHA.
 *
 * @since 1.0.1
 * @version 5.0.1
 * @package LoginPress
 */

if ( ! class_exists( 'LoginPress_Recaptcha' ) ) {

	/**
	 * LoginPress_Recaptcha
	 */
	class LoginPress_Recaptcha {

		/**
		 * Holds the singleton instance of the class.
		 *
		 * @var static|null
		 */
		protected static $instance = null;

		/**
		 * Variable that Check for LoginPress settings.
		 *
		 * @var string
		 * @since 2.0.1
		 */
		public $loginpress_settings;

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
		public function __construct($settings, $captcha) {

			$this->loginpress_settings         = $settings;
			$this->loginpress_captcha_settings = $captcha;
			add_action( 'wp_enqueue_scripts', array( $this, 'loginpress_recaptcha_enqueue_script' ), 1 );
			$this->hooks();

		}

		/**
		 * enqueue script
		 */
		public function loginpress_recaptcha_enqueue_script(){
			wp_enqueue_script( 'loginpress_captcha_front', LOGINPRESS_PRO_DIR_URL . 'assets/js/captcha.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, true );
		}

		/**
		 * Add all hooks.
		 *
		 * @return void
		 */
		private function hooks() {
			add_action( 'loginpress_recaptcha_validate_key', array( $this, 'loginpress_recaptcha_validate_key' ), 10 );
			$captchas_enabled = isset( $this->loginpress_captcha_settings['enable_captchas'] ) ? $this->loginpress_captcha_settings['enable_captchas'] : 'off';

			if ( $captchas_enabled !== 'off' ) {
				$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';

				if ( $captchas_type === 'type_recaptcha' ) {

					// Get reCaptcha keys.
					$cap_type      = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';
					$cap_site      = isset( $this->loginpress_captcha_settings['site_key'] ) ? $this->loginpress_captcha_settings['site_key'] : '';
					$cap_secret    = isset( $this->loginpress_captcha_settings['secret_key'] ) ? $this->loginpress_captcha_settings['secret_key'] : '';
					$cap_site_v3   = isset( $this->loginpress_captcha_settings['site_key_v3'] ) ? $this->loginpress_captcha_settings['site_key_v3'] : '';
					$cap_secret_v3 = isset( $this->loginpress_captcha_settings['secret_key_v3'] ) ? $this->loginpress_captcha_settings['secret_key_v3'] : '';

					// Return from reCaptcha if PowerPack login or registration nonce set.
					if ( isset( $_POST['pp-lf-login-nonce'] ) || isset( $_POST['pp-registration-nonce'] ) || ( isset( $_POST['action'] ) && sanitize_text_field( $_POST['action'] ) === 'loginpress_widget_login_process' ) ) { // @codingStandardsIgnoreLine.
						return;
					}

					// Validate reCaptcha based on type and corresponding keys.
					if (
					( 'v2-robot' === $cap_type && ( empty( $cap_site ) || empty( $cap_secret ) ) ) ||
					( 'v3' === $cap_type && ( empty( $cap_site_v3 ) || empty( $cap_secret_v3 ) ) ) ) {
						return;
					}

					$cap_login           = isset( $this->loginpress_captcha_settings['captcha_enable']['login_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['login_form'] : false;
					$cap_comments        = isset( $this->loginpress_captcha_settings['captcha_enable']['comment_form_defaults'] ) ? $this->loginpress_captcha_settings['captcha_enable']['comment_form_defaults'] : false;
					$cap_lost            = isset( $this->loginpress_captcha_settings['captcha_enable']['lostpassword_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['lostpassword_form'] : false;
					$cap_register        = isset( $this->loginpress_captcha_settings['captcha_enable']['register_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['register_form'] : false;
					$woo_login_enable    = isset( $this->loginpress_captcha_settings['captcha_enable']['woocommerce_login_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['woocommerce_login_form'] : false;
					$woo_register_enable = isset( $this->loginpress_captcha_settings['captcha_enable']['woocommerce_register_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['woocommerce_register_form'] : false;
					$action              = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // @codingStandardsIgnoreLine.
					/* Add reCAPTCHA on login form */
					if ( $cap_login ) {

						add_action( 'login_form', array( $this, 'loginpress_recaptcha_field' ) );
						add_action( 'login_enqueue_scripts', array( $this, 'loginpress_recaptcha_script' ) );
						// For Widget
						add_action(
							'loginpress_after_login_form_widget',
							function () {
								$this->loginpress_recaptcha_field();
								$this->loginpress_recaptcha_script();
							}
						);
					}

					/* Add reCAPTCHA on Lost password form */
					if ( $cap_lost ) {
						add_action( 'lostpassword_form', array( $this, 'loginpress_recaptcha_field' ) );
						add_action( 'login_enqueue_scripts', array( $this, 'loginpress_recaptcha_script' ) );
						// For Widget
						add_action(
							'loginpress_after_lost_password_form_widget',
							function () {
								$this->loginpress_recaptcha_field();
								$this->loginpress_recaptcha_script();
							}
						);
					}

					/**
					 * Add reCAPTCHA on comments form.
					 *
					 * @since 3.0.0
					 * @version 5.0.0
					 */
					if ( $cap_comments ) {

						/* Add reCAPTCHA in comments */

						/* Add reCAPTCHA scripts for comments */
						add_action( 'comment_form', array( $this, 'loginpress_recaptcha_script' ) );

						/* Add reCAPTCHA field for comments */
						add_action( 'comment_id_fields', array( $this, 'comment_loginpress_recaptcha_field' ) );

						/* Add reCAPTCHA authentication on comments */
						add_action( 'pre_comment_on_post', array( $this, 'loginpress_recaptcha_comment' ), 10 );
					}

					/* Add reCAPTCHA on registration form */
					if ( $cap_register ) {
						add_action( 'register_form', array( $this, 'loginpress_recaptcha_field' ), 99 );
						add_action( 'login_enqueue_scripts', array( $this, 'loginpress_recaptcha_script' ) );
						// For Widget
						add_action(
							'loginpress_after_reg_form_widget',
							function () {
								$this->loginpress_recaptcha_field();
								$this->loginpress_recaptcha_script();
							}
						);
					}

					/* Authentication reCAPTCHA on login form */
					if ( ! isset( $_GET['customize_changeset_uuid'] ) && $cap_login ) {
						add_filter( 'authenticate', array( $this, 'loginpress_recaptcha_auth' ), 99, 3 );
					}

					/* Authentication reCAPTCHA on lost-password form */
					if ( ! isset( $_GET['customize_changeset_uuid'] ) && $cap_lost && isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' ) {
						add_filter( 'allow_password_reset', array( $this, 'loginpress_recaptcha_lostpassword_auth' ), 10, 2 );
					}

					/* Authentication reCAPTCHA on registration form */
					if ( ! isset( $_GET['customize_changeset_uuid'] ) && $cap_register && 'register' === $action ) {
						add_filter( 'registration_errors', array( $this, 'loginpress_recaptcha_registration_auth' ), 10, 3 );
					}
				}
			}
		}

		/**
		 * Add reCaptcha field just before the Post button
		 *
		 * @param array $default Default parameter array for reCaptcha.
		 * @since 3.0.0
		 * @return array $default all the fields.
		 */
		public function comment_loginpress_recaptcha_field( $default ) {
			$this->loginpress_recaptcha_field();

			return $default;
		}

		/**
		 * Too add the Google reCaptcha script and hidden input field in the forms.
		 *
		 * @param string $action Action of the form.
		 * @param string $element_id ID property of the form.
		 * @param string $element_class Class property of the form.
		 *
		 * @since 3.0.3
		 */
		public function loginpress_pro_recaptcha_enqueue( $action, $element_id = '', $element_class = '' ) {
			$cap_site_v3 = isset( $this->loginpress_captcha_settings['site_key_v3'] ) ? $this->loginpress_captcha_settings['site_key_v3'] : '';

			wp_enqueue_script( 'loginpress_recaptcha_v3', 'https://www.google.com/recaptcha/api.js?render=' . $cap_site_v3, array(), LOGINPRESS_PRO_VERSION, true );
			?>
			<script>
				document.addEventListener("DOMContentLoaded", function() {
					grecaptcha.ready(function() {
						grecaptcha.execute('<?php echo esc_attr( $cap_site_v3 ); ?>', { action: '<?php echo esc_attr( $action ); ?>' }).then(function(token) {
						var selector = '';
						if ( '<?php echo esc_attr( $element_id ); ?>' ) {
							selector += '#' + '<?php echo esc_attr( $element_id ); ?>';
						} else if ( '<?php echo esc_attr( $element_class ); ?>' ) {
							selector += '.' + '<?php echo esc_attr( $element_class ); ?>';
						}
						jQuery(selector).prepend('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
						});
					});
				});
			</script>
			<?php
		}

		/**
		 * [loginpress_recaptcha_script recaptcha style]
		 *
		 * @since 1.0.1
		 * @version 4.0.0
		 */
		public function loginpress_recaptcha_script() {
			$cap_type   = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';
			$cap_site   = isset( $this->loginpress_captcha_settings['site_key'] ) ? $this->loginpress_captcha_settings['site_key'] : '';
			$cap_secret = isset( $this->loginpress_captcha_settings['secret_key'] ) ? $this->loginpress_captcha_settings['secret_key'] : '';

			/**
			 * Enqueue Google reCaptcha V2 "I'm not robot" script.
			 *
			 * @since 1.0.1
			 * @version 4.0.0
			 */
			if ( 'v2-robot' === $cap_type ) {

				if ( (! isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) || empty( $this->loginpress_captcha_settings['v2_robot_verified'] ) ) ||
				      isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) && 'on' === $this->loginpress_captcha_settings['v2_robot_verified'] ) {

					if ( ! empty( $cap_site ) && ! empty( $cap_secret ) ) :

						$cap_language    = isset( $this->loginpress_captcha_settings['captcha_language'] ) ? $this->loginpress_captcha_settings['captcha_language'] : 'en';
						$recaptcha_size  = get_option( 'loginpress_customization' );
						$_recaptcha_size = ! empty( $recaptcha_size['recaptcha_size'] ) ? $recaptcha_size['recaptcha_size'] : 1;
						wp_enqueue_script( 'loginpress_recaptcha_lang', 'https://www.google.com/recaptcha/api.js?onload=recaptchaLoaded&hl=' . $cap_language, array(), LOGINPRESS_PRO_VERSION, true );
						?>
	
						<style type="text/css">
							.loginpress_recaptcha_wrapper{
								text-align: center;
							}
							body .loginpress_recaptcha_wrapper .g-recaptcha{
								display: inline-block;
								transform-origin: top left;
								transform: scale(<?php echo esc_attr( $_recaptcha_size ); ?>);
							}
							html[dir="rtl"] .g-recaptcha{
								transform-origin: top right;
							}
						</style>
						<?php
					endif;
				}
			}
		}

		/**
		 * Google reCaptcha field Callback
		 *
		 * @version 5.0.1
		 */
		public function loginpress_recaptcha_field() {

			$cap_site      = isset( $this->loginpress_captcha_settings['site_key'] ) ? $this->loginpress_captcha_settings['site_key'] : '';
			$cap_secret    = isset( $this->loginpress_captcha_settings['secret_key'] ) ? $this->loginpress_captcha_settings['secret_key'] : '';
			$cap_type      = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';
			$cap_site_v3   = isset( $this->loginpress_captcha_settings['site_key_v3'] ) ? $this->loginpress_captcha_settings['site_key_v3'] : '';
			$cap_secret_v3 = isset( $this->loginpress_captcha_settings['secret_key_v3'] ) ? $this->loginpress_captcha_settings['secret_key_v3'] : '';
			$cap_lost      = isset( $this->loginpress_captcha_settings['captcha_enable']['lostpassword_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['lostpassword_form'] : false;
			$cap_register  = isset( $this->loginpress_captcha_settings['captcha_enable']['register_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['register_form'] : false;
			$cap_login     = isset( $this->loginpress_captcha_settings['captcha_enable']['login_form'] ) ? $this->loginpress_captcha_settings['captcha_enable']['login_form'] : false;

			if ( 'v2-robot' === $cap_type &&
				( isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) && 'on' === $this->loginpress_captcha_settings['v2_robot_verified'] ||
				(! isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) || empty( $this->loginpress_captcha_settings['v2_robot_verified'] ) ) ) ) {

				$cap_theme       = isset( $this->loginpress_captcha_settings['captcha_theme'] ) ? $this->loginpress_captcha_settings['captcha_theme'] : 'light';
				$captcha_preview = '';

				if ( ! empty( $cap_site ) && ! empty( $cap_secret ) ) {

					$captcha_preview .= '<div class="loginpress_recaptcha_wrapper" id="loginpress_recaptcha_wrapper">';
					$captcha_preview .= '<div class="g-recaptcha" data-sitekey="' . htmlentities( trim( $cap_site ) ) . '" data-theme="' . $cap_theme . '"></div>';
					$captcha_preview .= '</div>';
				} // check $cap_site && $cap_secret.

				echo wp_kses_post( $captcha_preview );
			}

			/**
			 * Enqueue Google reCaptcha V3 script.
			 *
			 * @since 2.5.0
			 * @version 5.0.1
			 */
			if ( 'v3' === $cap_type ) {

				if ( ! empty( $cap_site_v3 ) && ! empty( $cap_secret_v3 ) && isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' ) :
					$this->loginpress_pro_recaptcha_enqueue( 'lostpassword', 'lostpasswordform' );
				endif;// check $cap_site_v3 && $cap_secret_v3.

				if ( ! empty( $cap_site_v3 ) && ! empty( $cap_secret_v3 ) && isset( $_GET['action'] ) && $_GET['action'] === 'register' ) :
					$this->loginpress_pro_recaptcha_enqueue( 'register', 'registerform' );
				endif;

				$integration_settings = get_option( 'loginpress_integration_settings' );
				// Make sure enable_captcha_bp is an array
				$enable_captcha_bp = isset( $integration_settings['enable_captcha_bp'] ) && is_array( $integration_settings['enable_captcha_bp'] )
				? $integration_settings['enable_captcha_bp']
				: array();
				$bp_captcha_register  = $this->loginpress_get_value( $enable_captcha_bp, 'register_bp_block' );
				$enable_captcha_edd = isset( $integration_settings['enable_captcha_edd'] ) && is_array( $integration_settings['enable_captcha_edd'] )
					? $integration_settings['enable_captcha_edd']
					: array();
				$edd_captcha_register = $this->loginpress_get_value( $enable_captcha_edd, 'register_edd_block' );
				$edd_captcha_co       = $this->loginpress_get_value( $enable_captcha_edd, 'checkout_edd_block' );
				if ( ! empty( $cap_site_v3 ) && ! empty( $cap_secret_v3 ) && $bp_captcha_register ) :
					$this->loginpress_pro_recaptcha_enqueue( 'register', 'signup-form' );
				endif;

				if ( ! empty( $cap_site_v3 ) && ! empty( $cap_secret_v3 ) && ($edd_captcha_register || $edd_captcha_co) ) :
					$this->loginpress_pro_recaptcha_enqueue( 'register', 'edd-blocks-form__register' );
					$this->loginpress_pro_recaptcha_enqueue( 'checkout', 'edd_purchase_form' );
				endif;

				if ( ! empty( $cap_site_v3 ) && ! empty( $cap_secret_v3 ) ) :
					$this->loginpress_pro_recaptcha_enqueue( 'loginpage', 'loginform' );
					$this->loginpress_pro_recaptcha_enqueue( 'loginpage', '', 'woocommerce-form-login' );
					$this->loginpress_pro_recaptcha_enqueue( 'checkoutpage', '', 'woocommerce-checkout' );
					$this->loginpress_pro_recaptcha_enqueue( 'loginpage', 'edd-blocks-form__login' );
					$this->loginpress_pro_recaptcha_enqueue( 'register', 'learndash_registerform' );
					$this->loginpress_pro_recaptcha_enqueue( 'loginpage', '', 'llms-login' );
					$this->loginpress_pro_recaptcha_enqueue( 'register', '', 'llms-new-person-form' );
				endif;

				if ( $cap_register ) {
					?>
						<script type="text/javascript">
							
							jQuery(document).ready(function () {

								jQuery('.loginpress-login-widget').each(function () {
				
									var widget = jQuery(this); // Scope to the specific widget
									widget.find('.register-link').on('click', function (e) {
										var recaptchaBadge = jQuery('.grecaptcha-badge');
										if (recaptchaBadge.length > 0) {
											recaptchaBadge.css({
												visibility: 'visible',
											});
										}
									});
								});
							});	
						</script>
					<?php
				} elseif ( ! $cap_register ) {
					?>
						<script type="text/javascript">
							jQuery(document).ready(function () {
								jQuery('.loginpress-login-widget').each(function () {
				
									var widget = jQuery(this); // Scope to the specific widget
									widget.find('.register-link').on('click', function (e) {
										var recaptchaBadge = jQuery('.grecaptcha-badge');
										if (recaptchaBadge.length > 0) {
											recaptchaBadge.css({
												visibility: 'hidden'
											})
										}
									});
								});
							});
						</script>
					<?php
				}

				if ( $cap_lost ) {
					?>
						<script type="text/javascript">
							jQuery(document).ready(function () {
								jQuery('.loginpress-login-widget').each(function () {
				
									var widget = jQuery(this); // Scope to the specific widget
									widget.find('.lost_password-link').on('click', function (e) {
										var recaptchaBadge = jQuery('.grecaptcha-badge');
										if (recaptchaBadge.length > 0) {
											recaptchaBadge.css({
												visibility: 'visible',
											});
										}
									});
								});
							});
						</script>
					<?php
				} elseif ( ! $cap_lost ) {
					?>
						<script type="text/javascript">
							jQuery(document).ready(function () {
								jQuery('.loginpress-login-widget').each(function () {
				
									var widget = jQuery(this); // Scope to the specific widget
									widget.find('.lost_password-link').on('click', function (e) {
										var recaptchaBadge = jQuery('.grecaptcha-badge');
										if (recaptchaBadge.length > 0) {
											recaptchaBadge.css({
												visibility: 'hidden'
											})
										}
									});
								});
							});
						</script>
					<?php
				}

				if ( $cap_login ) {
					?>
						<script type="text/javascript">
							jQuery(document).ready(function () {
								jQuery('.loginpress-login-widget').each(function () {
				
									var widget = jQuery(this); // Scope to the specific widget
									widget.find('.login-link').on('click', function (e) {
										var recaptchaBadge = jQuery('.grecaptcha-badge');
										if (recaptchaBadge.length > 0) {
											recaptchaBadge.css({
												visibility: 'visible'
											})
										}
									});
								});
							});
						</script>
					<?php
				} elseif ( ! $cap_login ) {
					?>
						<script type="text/javascript">
							jQuery(document).ready(function () {
								jQuery('.loginpress-login-widget').each(function () {
				
									var widget = jQuery(this); // Scope to the specific widget
									widget.find('.login-link').on('click', function (e) {
										var recaptchaBadge = jQuery('.grecaptcha-badge');
										if (recaptchaBadge.length > 0) {
											recaptchaBadge.css({
												visibility: 'hidden'
											})
										}
									});
								});
							});
						</script>
					<?php
				}

			}
		}

		/**
		 * Check isset and not empty for the parameters
		 * @param string $array The name of the array.
		 * @param string $key The key for which to check the isset and not empty.
		 * @param string $default The provided default value.
		 * @return object $user The user object.
		 * 
		 * @since 5.0.0
		 */
		function loginpress_get_value( $array, $key, $default = false ) {
			if ( is_array( $array ) && isset( $array[ $key ] ) && ! empty( $array[ $key ] ) ) {
				return $array[ $key ];
			}
			return $default;
		}

		/**
		 * ReCAPTCHA Login Authentication.
		 *
		 * @param object $user The user object.
		 * @param string $username The username.
		 * @param string $password The password.
		 * @return object $user The user object.
		 *
		 * @version 4.0.0
		 */
		public function loginpress_recaptcha_auth( $user, $username, $password ) {
			// Check if reCAPTCHA validation is required.
			// @since 4.0.0
			if ( apply_filters( 'loginpress_before_recaptcha_auth', false ) ) {

				return $user;
			}
			
			// Check if reCAPTCHA validation is required
			if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
				// Check for WooCommerce-specific field present in the request
				if ( isset( $_POST['woocommerce-login-nonce'] ) || isset( $_POST['_llms_login_user_nonce'] ) || isset( $_POST['_llms_register_person_nonce'] ) || isset( $_POST['learndash-login-form']) || isset( $_POST['edd_login_nonce']) ) {
					// This is WooCommerce login, skip reCAPTCHA validation here
					return $user;
				}
			}

			$cap_type = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';

			if ( ( isset( $_POST['g-recaptcha-response'] ) ) || ( isset( $_POST['captcha_response'] ) && ! empty( $_POST['captcha_response'] ) ) ) { // @codingStandardsIgnoreLine.

				if ( 'v3' === $cap_type ) {
					$good_score = $this->loginpress_captcha_settings['good_score'];
					$score      = $this->loginpress_v3_recaptcha_verifier();

					if ( $username && $password && $score < $good_score ) {
						return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
					}
				} else {
					$response = $this->loginpress_recaptcha_verifier();
					if ( $response->isSuccess() ) {
						return $user;
					}
					if ( $username && $password && ! $response->isSuccess() ) {
						return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
					}
				}
			} elseif ( isset( $_POST['wp-submit']) && ! isset( $_POST['g-recaptcha-response'] ) ) { // @codingStandardsIgnoreLine.
				return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
			}
			return $user;
		}

		/**
		 * Google reCaptcha on comments section authentication.
		 *
		 * @since 3.0.0
		 * @version 4.0.0
		 */
		public function loginpress_recaptcha_comment() {

			$cap_type = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';

			$error = __( '<strong>ERROR:</strong> Please verify reCAPTCHA', 'loginpress-pro' );
			if ( isset( $_POST['g-recaptcha-response'] ) ) {  // @codingStandardsIgnoreLine.
				if ( 'v3' === $cap_type ) {
					$good_score = $this->loginpress_captcha_settings['good_score'];
					$score      = $this->loginpress_v3_recaptcha_verifier();
					if ( $score < $good_score ) {
						wp_die( $error );  // @codingStandardsIgnoreLine.
					}
				} else {
					$response = $this->loginpress_recaptcha_verifier();
					if ( ! $response->isSuccess() ) {
						wp_die( $error );  // @codingStandardsIgnoreLine.
					}
				}
			} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) {
				wp_die( $error );
			}
		}

		/**
		 * Google reCaptcha V2 server side verification.
		 *
		 * @since 2.1.2
		 * @version 4.0.0
		 */
		public function loginpress_recaptcha_verifier() {

			$cap_type = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';

			$secret = isset( $this->loginpress_captcha_settings['secret_key'] ) ? $this->loginpress_captcha_settings['secret_key'] : false;

			include LOGINPRESS_PRO_ROOT_PATH . '/lib/recaptcha/src/autoload.php';

			if ( ini_get( 'allow_url_fopen' ) ) {
				$recaptcha = new \ReCaptcha\ReCaptcha( $secret );
			} else {
				$recaptcha = new \ReCaptcha\ReCaptcha( $secret, new \ReCaptcha\RequestMethod\CurlPost() );
			}
			$recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? wp_unslash( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : wp_unslash( sanitize_text_field( $_POST['captcha_response'] ) ); // @codingStandardsIgnoreLine.
			$response = $recaptcha->verify( wp_unslash( $recaptcha_response ), $this->loginpress_get_remote_ip() ); // @codingStandardsIgnoreLine.

			return $response;
		}

		/**
		 * Google reCaptcha V3 server side verification.
		 *
		 * @since 2.1.2
		 * @version 4.0.0
		 */
		public function loginpress_v3_recaptcha_verifier() {

			if ( isset( $_POST['g-recaptcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // @codingStandardsIgnoreLine.

				$v3_secret = isset( $this->loginpress_captcha_settings['secret_key_v3'] ) ? $this->loginpress_captcha_settings['secret_key_v3'] : false;

				// Build POST request:.
				$recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
				if ( isset( $_POST['g-recaptcha-response'] ) && !empty( $_POST['g-recaptcha-response'] ) ) {
					$recaptcha_response = $_POST['g-recaptcha-response'];
				} elseif ( isset( $_POST['captcha_response'] ) && !empty( $_POST['captcha_response'] ) ) {
					$recaptcha_response = $_POST['captcha_response'];
				}

				// Make and decode POST request:.
				$recaptcha = file_get_contents( $recaptcha_url . '?secret=' . $v3_secret . '&response=' . $recaptcha_response );
				$response  = json_decode( $recaptcha );
				// Take action based on the score returned:.
				if ( isset( $response->score ) && $response->score ) {
					return $response->score;
				}
			}
			// otherwise, let the spammer think that they got their message through.
			return 0;
		}

		/**
		 * [loginpress_recaptcha_lostpassword_auth reCAPTCHA Lost Password Authentication.]
		 *
		 * @param bool $allow To allow user access.
		 * @return int $user_id User ID.
		 *
		 * @version 4.0.0
		 */
		public function loginpress_recaptcha_lostpassword_auth( $allow, $user_id ) {

			$cap_type = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';

			if ( isset( $_POST['g-recaptcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // @codingStandardsIgnoreLine.

				if ( 'v3' === $cap_type ) {
					$good_score = $this->loginpress_captcha_settings['good_score'];
					$score      = $this->loginpress_v3_recaptcha_verifier();

					if ( $score > $good_score ) {
						return $allow;
					}
				} else {
					$response = $this->loginpress_recaptcha_verifier();

					if ( $response->isSuccess() ) {
						return $allow;
					}
				}
			} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) {
				return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
			}
			return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
		}

		/**
		 * [loginpress_recaptcha_registration_auth reCAPTCHA Registration Authentication.]
		 *
		 * @param array  $errors The Error/s.
		 * @param string $sanitized_user_login The sanitized user login.
		 * @param string $user_email The user email.
		 * @return array $errors The Error/s.
		 *
		 * @version 4.0.0
		 */
		public function loginpress_recaptcha_registration_auth( $errors, $sanitized_user_login, $user_email ) {

			// Check if reCAPTCHA validation is required before proceeding with registration.
			// @since 4.0.0
			if ( apply_filters( 'loginpress_before_recaptcha_reg_auth', false ) || isset( $_POST['learndash-registration-form']) ) {

				return $errors;
			}

			$cap_type = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';

			if ( isset( $_POST['g-recaptcha-response'] ) || (  isset( $_POST['captcha_response'] ) ) ) { // @codingStandardsIgnoreLine.

				if ( 'v3' === $cap_type ) {

					$good_score = $this->loginpress_captcha_settings['good_score'];
					$score      = $this->loginpress_v3_recaptcha_verifier();

					if ( $score < $good_score ) {
						return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
					}
				} else {

					$response = $this->loginpress_recaptcha_verifier();

					if ( ! $response->isSuccess() ) {
						return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
					}
				}
			} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) {
				return new WP_Error( 'recaptcha_error', $this->loginpress_recaptcha_error() );
			}

			return $errors;
		}

		/**
		 * [loginpress_get_remote_ip]
		 *
		 * @return [string] [remote address]
		 */
		public function loginpress_get_remote_ip() {

			return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}

		/**
		 * [loginpress_recaptcha_error recaptcha error message]
		 *
		 * @return [string] [Custom error message]
		 * @version 2.1.2
		 */
		public function loginpress_recaptcha_error() {

			$loginpress_settings = get_option( 'loginpress_customization' );
			$recaptcha_message   = isset( $loginpress_settings['recaptcha_error_message'] ) ? $loginpress_settings['recaptcha_error_message'] : __( '<strong>ERROR:</strong> Please verify reCAPTCHA', 'loginpress-pro' );

			$allowed_html = array(
				'a'      => array(),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'i'      => array(),
			);
			return wp_kses( $recaptcha_message, $allowed_html );
		}

		/**
		 * Main Instance
		 *
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {
				$loginpress_settings         = get_option( 'loginpress_setting' );
				$loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
				self::$instance = new self($loginpress_settings, $loginpress_captcha_settings);
			}
			return self::$instance;
		}

		/**
		 * Validate the keys for the ReCaptcha v2 and v3.
		 *
		 * @since 4.0.0
		 * @return string The code for the validation .
		 */
		public function loginpress_recaptcha_validate_key() {
			if ( ! isset( $this->loginpress_captcha_settings['captchas_type']) && isset($this->loginpress_settings['enable_repatcha']) && isset($this->loginpress_settings['enable_repatcha']) == 'on' ) {
				 echo '<script>window.location.reload();</script>';
			}

				$v2_enabled = isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) && ! empty( $this->loginpress_captcha_settings['v2_robot_verified'] ) ? $this->loginpress_captcha_settings['v2_robot_verified'] : 'off';
				?>
				<div id="recaptcha_v2"></div>
				<script>
					jQuery(document).ready(function($) {

						var $v2_enabled = '<?php echo $v2_enabled; ?>';
						var $site_key = $('input[name="loginpress_captcha_settings[site_key]"]');
						var $secret_key = $('input[name="loginpress_captcha_settings[secret_key]"]');
						var activeTheme = $('tr.captcha_theme .options li.active').attr('rel');
						var $v3_site = $('tr.site_key_v3 input');
						var $v3_secret = $('tr.secret_key_v3 input');
						var $key_type = $('tr.recaptcha_type select');
						var $captcha_type = $('tr.captchas_type select');
						var $state = 'false';

						function validate_v2( $state = 'false' ) {
							
							$('tr.validate_v2_keys').hide();
							var $submit_btn = $('#loginpress_captcha_settings #submit');
							var CaptchaType = $captcha_type.val();
							var recaptchaType = $key_type.val();
							
							if ( CaptchaType == 'type_recaptcha' ) {

								if ( recaptchaType == 'v2-robot' ) {
									$submit_btn.prop('disabled', false);
									if ( !$site_key.val() || !$secret_key.val() ) {
										$submit_btn.prop('disabled', true);
									} else {
										if ($v2_enabled != 'on' || $state == 'true' ) {
											$submit_btn.prop( 'disabled', true);
											var $recaptcha = $( '#recaptcha_v2' );
											window.___grecaptcha_cfg.clients = {};
											window.___grecaptcha_cfg.count = 0;
											$recaptcha.html( '' );

											grecaptcha.ready(function() {
												grecaptcha.render(
													'recaptcha_v2',
													{
														'sitekey':        $site_key.val(),
														'theme':          activeTheme,
														'badge':          'inline',
														'error-callback': function () {
														},
														'callback':       function () {
															$submit_btn.prop('disabled', false);

														}
													}
												);
											});

											$('#recaptcha_v2').show();
											$('tr.validate_v2_keys').show();
										}

									}
								} else if ( recaptchaType == 'v3' ) {
									$submit_btn.prop('disabled', false);
									if ( !$v3_site.val() || !$v3_secret.val() ) {
										$submit_btn.prop('disabled', true);
									} else {
										if ($v3_site.val().substring(0, 13) !== $v3_secret.val().substring(0, 13)) {
											$submit_btn.prop('disabled', true);
										}
									}
								}
							}

						}

						$site_key.on( 'input', function () {
							$('tr.site_key svg').hide();
							validate_v2( 'true' );
						});
						$secret_key.on('input', function () {
							$('tr.secret_key svg').hide();
							validate_v2( 'true' );
						});
						$v3_site.on( 'input', validate_v2);
						$v3_secret.on('input', validate_v2);
						$key_type.on( 'change' , validate_v2);

						// variable for svg 
						var CorrectIcon = `<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><title>success</title><g fill="none" class="nc-icon-wrapper"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 26c7.18 0 13-5.82 13-13S20.18 0 13 0 0 5.82 0 13s5.82 13 13 13z" fill="#22A753"/><path d="M11 14.586l6.293-6.293a1 1 0 1 1 1.414 1.414L11 17.414l-3.707-3.707a1 1 0 1 1 1.414-1.414L11 14.586z" fill="#fff"/></g></svg>`;
						var WrongIcon   = `<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><title>Error</title><g fill="none" class="nc-icon-wrapper"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 26c7.18 0 13-5.82 13-13S20.18 0 13 0 0 5.82 0 13s5.82 13 13 13z" fill="#CB2431"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.183 8.183a.625.625 0 0 0 0 .884L12.116 13l-3.933 3.933a.625.625 0 1 0 .884.884L13 13.884l3.933 3.933a.625.625 0 1 0 .884-.884L13.884 13l3.933-3.933a.625.625 0 1 0-.884-.884L13 12.116 9.067 8.183a.625.625 0 0 0-.884 0z" fill="#fff"/><path d="M8.183 9.067l-.353.354.353-.354zm0-.884L7.83 7.83l.353.353zM12.116 13l.354.354.353-.354-.353-.354-.354.354zm-3.933 3.933l-.353-.354.353.354zm0 .884l-.353.354.353-.354zM13 13.884l.354-.354-.354-.353-.354.353.354.354zm3.933 3.933l.354-.354-.354.354zm.884-.884l-.354.354.354-.354zM13.884 13l-.354-.354-.353.354.353.354.354-.354zm3.933-4.817l.354-.353-.354.353zm-.884 0l-.354-.353.354.353zM13 12.116l-.354.354.354.353.354-.353-.354-.354zM9.067 8.183l.354-.353-.354.353zm-.53.53a.125.125 0 0 1 0-.176l-.708-.708c-.439.44-.439 1.152 0 1.592l.708-.708zm3.933 3.933L8.537 8.713l-.708.708 3.934 3.933.707-.708zm-3.933 4.64l3.933-3.932-.707-.708L7.83 16.58l.707.708zm0 .177a.125.125 0 0 1 0-.176l-.708-.707c-.439.439-.439 1.151 0 1.59l.708-.707zm.176 0a.125.125 0 0 1-.176 0l-.708.707c.44.44 1.152.44 1.592 0l-.708-.707zm3.933-3.933l-3.933 3.933.708.707 3.933-3.933-.708-.707zm4.64 3.933l-3.932-3.933-.708.707 3.933 3.934.708-.708zm.177 0a.125.125 0 0 1-.176 0l-.707.707c.439.44 1.151.44 1.59 0l-.707-.707zm0-.176a.125.125 0 0 1 0 .176l.707.707c.44-.439.44-1.151 0-1.59l-.707.707zm-3.933-3.933l3.933 3.933.707-.707-3.933-3.934-.707.708zm3.933-4.64l-3.933 3.932.707.708 3.934-3.933-.708-.708zm0-.177a.125.125 0 0 1 0 .176l.707.708c.44-.44.44-1.152 0-1.591l-.707.707zm-.176 0a.125.125 0 0 1 .176 0l.707-.708a1.125 1.125 0 0 0-1.59 0l.707.708zm-3.933 3.933l3.933-3.933-.707-.708-3.934 3.934.708.707zm-4.64-3.933l3.932 3.933.708-.707L9.42 7.83l-.708.707zm-.177 0a.125.125 0 0 1 .176 0l.708-.708a1.125 1.125 0 0 0-1.591 0l.707.708z" fill="#fff"/></g></svg>`;

						if ( $v2_enabled == 'on' ) {
							$site_key.wrap('<div class="v2-input-container"></div>');
							$secret_key.wrap('<div class="v2-input-container"></div>');
							$('.v2-input-container').append(CorrectIcon);
						} else if ( $v2_enabled != 'on' ) {
							$site_key.wrap('<div class="v2-input-container"></div>');
							$secret_key.wrap('<div class="v2-input-container"></div>');
							$('.v2-input-container').append(WrongIcon);
							window.onload = function() {
								validate_v2( 'true' );
							};
						}

					});
				</script>
				<?php
		}
	}
}
?>
