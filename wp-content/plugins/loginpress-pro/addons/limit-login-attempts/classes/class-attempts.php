<?php

if ( ! class_exists( 'LoginPress_Attempts' ) ) :

	/**
	 * LoginPress_Attempts
	 */
	class LoginPress_Attempts {

		/**
		 * Variable for LoginPress Limit Login Attempts table name.
		 *
		 * @var string
		 * @since 3.0.0
		 */
		protected $llla_table;

		/**
		 * Variable that Check for LoginPress Key.
		 *
		 * @var string
		 * @since 3.0.0
		 */
		public $attempts_settings;
		/**
		 * Variable to store the time of the attempt.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $attempt_time;
		/**
		 * Variable that Check for LoginPress hidelogin settings.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $loginpress_hidelogin;
		/**
		 * Variable that Check for edd block status.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $edd_block_error;
		/**
		 * Variable that stores the ip of the user.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $ip;
		/**
		 * Class constructor.
		 */
		public function __construct() {
			$this->edd_block_error = true;
				global $wpdb;
				$this->llla_table           = $wpdb->prefix . 'loginpress_limit_login_details';
				$this->attempts_settings    = get_option( 'loginpress_limit_login_attempts' );
				$this->loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
				$this->ip                   = $this->get_address();
				$is_llla_active             = get_option( 'loginpress_pro_addons' );
			if ( isset( $is_llla_active['limit-login-attempts']['is_active'] ) && $is_llla_active['limit-login-attempts']['is_active'] ) {
				$this->hooks();
			}
		}

		/** * * * * * *
		 * Action hooks.
		 * * * * * * * */
		public function hooks() {

			// add_action( 'wp_login_failed', array( $this, 'llla_login_failed' ), 999,1  );
			add_action( 'wp_loaded', array( $this, 'llla_wp_loaded' ) );
			add_action( 'init', array( $this, 'llla_check_xml_request' ) );
			add_action( 'init', array( $this, 'hide_login_integrate' ) );
			add_action( 'init', array( $this, 'loginpress_login_widget_integrate' ) ); // Integrate Widget Login Add-on.
			add_filter( 'authenticate', array( $this, 'llla_login_attempts_auth' ), 98, 3 );

			$disable_xml_rpc = isset( $this->attempts_settings['disable_xml_rpc_request'] ) ? $this->attempts_settings['disable_xml_rpc_request'] : '';

			if ( 'on' === $disable_xml_rpc ) {
				$this->disable_xml_rpc();
			}
		}

		/**
		 * LoginPress Hide login Integration with TranslatePress and LoginPress Limit Login Attempts.
		 *
		 * @return void
		 * @since  3.0.0
		 */
		public function hide_login_integrate() {

			global $pagenow, $wpdb;
			$loginpress_hidelogin = $this->loginpress_hidelogin;
			if ( 'index.php' === $pagenow && $this->llla_time() && isset( $loginpress_hidelogin['rename_login_slug'] ) ) {

				$last_attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT `datentime` FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

				$slug                 = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : '';
				$admin_url            = get_admin_url( null, '', 'admin' );
				$current_login_url    = home_url() . $slug . '/';
				$additional_login_url = home_url() . $slug;

				if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
					$url = 'https';
				} else {
					$url = 'http';
				}
				// Here append the common URL characters.
				$url .= '://';
				// Append the host(domain name, ip) to the URL.
				$url .= isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
				// Append the requested resource location to the URL.
				$url .= isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';

				if ( ( $current_login_url === $url || $admin_url === $url || $additional_login_url === $url ) && $this->llla_time() ) {
					wp_die( __( $this->loginpress_lockout_error( $last_attempt_time ) ), 403 ); // @codingStandardsIgnoreLine.
				}
			}
			$this->llla_wp_loaded();
		}

		/**
		 * Compatibility with LoginPress - Login Widget Add-On.
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function loginpress_login_widget_integrate() {
			if ( is_user_logged_in() ) {
				return null;
			}
			global $wpdb;

			$attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( $this->llla_time() && ( $last_attempt_time >= $attempts_allowed ) ) {
				add_filter( 'dynamic_sidebar_params', array( $this, 'loginpress_widget_params' ), 10 );
			}
		}

		/**
		 * Remove LoginPress - Login widgets if LoginPress - Limit Login Attempts applied.
		 *
		 * @param array $params widget parameter.
		 * @since 3.0.0
		 * @return Widgets
		 */
		public function loginpress_widget_params( $params ) {

			foreach ( $params as $param_index => $param_val ) {

				if ( isset( $param_val['widget_id'] ) && strpos( $param_val['widget_id'], 'loginpress-login-widget' ) !== false ) {
					unset( $params[ $param_index ] );
				}
			}

			return $params;
		}

		/**
		 * Check Auth if request coming from xmlrpc.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function llla_check_xml_request() {

			global $pagenow;
			if ( 'xmlrpc.php' === $pagenow ) {
				$this->llla_wp_loaded();
			}
		}

		/**
		 * Disable xml rpc request
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function disable_xml_rpc() {

			add_filter( 'xmlrpc_enabled', '__return_false' );
		}

		/**
		 * Attempts Login Authentication.
		 *
		 * @param object $user Object of the user.
		 * @param string $username username.
		 * @param string $password password.
		 * @since 3.0.0
		 * @version 3.3.0
		 */
		public function llla_login_attempts_auth( $user, $username, $password ) {

			if ( isset( $_POST['g-recaptcha-response'] ) && empty( $_POST['g-recaptcha-response'] ) ) {
				return;
			}
			if ( false === $this->edd_block_error ) {
				$this->edd_block_error = true;
				return;
			}

			if ( $user instanceof WP_User ) {
				return $user;
			}

			// Is username or password field empty?
			if ( empty( $username ) || empty( $password ) ) {

				if ( is_wp_error( $user ) ) {
					return $user;
				}

				$error = new WP_Error();

				if ( empty( $username ) ) {
					$error->add( 'empty_username', $this->limit_query( $username ) );
				}

				if ( empty( $password ) ) {
					$error->add( 'empty_password', $this->limit_query( $username ) );
				}

				return $error;
			}

			if ( ! empty( $username ) && ! empty( $password ) ) {

				$error = new WP_Error();
				global $pagenow, $wpdb;

				$whitelisted_ip = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->llla_table} WHERE ip = %s AND whitelist = %d LIMIT 1", $this->ip, 1 ) );
				if ( $whitelisted_ip >= 1 ) {
					return null;
				} else {
					// Check if user exists
					$error->add( 'llla_error', $this->limit_query( $username ) );
				}
				if ( class_exists( 'LifterLMS' ) && 'index.php' === $pagenow ) {
					/**
					 * Filter to show the Limit Login Attempts error on the LifterLMS login form.
					 *
					 * @since 5.0.0
					 */
					apply_filters( 'loginpress_llla_error_filter', false );
				}

				return $error;
			}
		}

		/**
		 * Die WordPress login on blacklist or lockout.
		 *
		 * @since  3.0.0
		 */
		public function llla_wp_loaded() {
			if ( is_user_logged_in() ) {
				return;
			}
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				// Unset the 'edd_invalid_login' error to prevent the default Easy Digital Downloads login error
				// from displaying, allowing custom error handling via LoginPress.
				edd_unset_error( 'edd_invalid_login' );
			}
			global $pagenow, $wpdb;

			$last_attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT `datentime` FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			$blacklist_check = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `blacklist` = 1", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( 'xmlrpc.php' === $pagenow && ( $this->llla_time() || $blacklist_check >= 1 ) ) {
				echo $this->loginpress_lockout_error( $last_attempt_time ); // @codingStandardsIgnoreLine.
				wp_die( '', 403 );
			}

			// limit wp-admin access.
			if ( is_admin() && $blacklist_check >= 1 ) {
				wp_die( __( 'You are not allowed to access admin panel', 'loginpress-pro' ), 403 ); // @codingStandardsIgnoreLine.
			}

			// limit wp-login.php access if blacklisted.
			if ( 'wp-login.php' === $pagenow && get_option( 'permalink_structure' ) && $blacklist_check >= 1 ) {
				wp_die( __( 'You are not allowed to access admin panel', 'loginpress-pro' ), 403 ); // @codingStandardsIgnoreLine.
			}

			// limit wp-login.php access if time remains.
			if ( 'wp-login.php' === $pagenow && $this->llla_time() && $this->loginpress_lockout_error( $last_attempt_time ) ) {
				wp_die( $this->loginpress_lockout_error( $last_attempt_time ), 403 ); // @codingStandardsIgnoreLine.
			}

			// limit WooCommerce Account access if blacklisted.
			if ( 'index.php' === $pagenow && get_option( 'permalink_structure' ) && $blacklist_check >= 1 && class_exists( 'WooCommerce' ) ) {

				remove_shortcode( 'woocommerce_my_account' );
				add_shortcode( 'woocommerce_my_account', array( $this, 'woo_blacklisted_error' ) );
			}

			// limit WooCommerce Account access if time remains.
			if ( $this->llla_time() && class_exists( 'WooCommerce' ) && 'index.php' === $pagenow ) {
				remove_shortcode( 'woocommerce_my_account' );
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form' );
				add_shortcode( 'woocommerce_my_account', array( $this, 'woo_attempt_error' ) );
			}

			// Handle EDD login forms when blacklisted.
			if ( 'index.php' === $pagenow && get_option( 'permalink_structure' ) && $blacklist_check >= 1 && class_exists( 'Easy_Digital_Downloads' ) ) {
				remove_shortcode( 'edd_login' );
				add_shortcode( 'edd_login', array( $this, 'loginpress_edd_blacklisted_error' ) );
				add_filter( 'render_block', array( $this, 'lp_render_edd_blacklisted_error_block' ), 10, 2 );
			}

			// Handle EDD login forms when lockout time remains.
			if ( $this->llla_time() && class_exists( 'Easy_Digital_Downloads' ) && 'index.php' === $pagenow ) {

				remove_shortcode( 'edd_login' );
				add_shortcode( 'edd_login', array( $this, 'loginpress_edd_attempt_error' ) );
				if ( $this->edd_block_error == true ) {
					$this->edd_block_error = false;
					add_filter( 'render_block', array( $this, 'lp_render_edd_attempt_error_block' ), 10, 2 );
				}
			}
		}

		/**
		 * Replaces the EDD login block with the blacklisted error content.
		 *
		 * @param string $block_content The original block content.
		 * @param array  $block         The block being rendered.
		 * @return string Modified block content.
		 */
		public function lp_render_edd_blacklisted_error_block( $block_content, $block ) {
			if ( isset( $block['blockName'] ) && $block['blockName'] === 'edd/login' ) {
				return $this->loginpress_edd_blacklisted_error( array() );
			}
			return $block_content;
		}

		/**
		 * Replaces the EDD login block with the login attempt error content.
		 *
		 * @param string $block_content The original block content.
		 * @param array  $block         The block being rendered.
		 * @return string Modified block content.
		 */
		public function lp_render_edd_attempt_error_block( $block_content, $block ) {
			if ( isset( $block['blockName'] ) && $block['blockName'] === 'edd/login' ) {
				return $this->loginpress_edd_attempt_error();
			}
			return $block_content;
		}

		/**
		 * Callback for error message 'EDD login blacklisted'
		 *
		 * @since 5.0.0
		 */
		public function loginpress_edd_blacklisted_error() {
			echo '<div class="edd-alert-error">';
			echo esc_html__( 'You are blacklisted to access the Login Panel', 'loginpress-pro' );
			echo '</div>';
		}

		/**
		 * Callback for error message 'EDD login attempt error'
		 *
		 * @since 5.0.0
		 */
		public function loginpress_edd_attempt_error() {
			echo '<div class="edd-alert-error">';

			global $wpdb;
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.
			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}
			echo wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) );
			echo '</div>';
		}

		/**
		 * Callback for error message 'woocommerce my-account login blacklisted'
		 *
		 * @since 2.1.0
		 */
		public function woo_blacklisted_error() {
			?>
			<div class="woocommerce-error">
				<?php
				echo esc_html__( 'You are blacklisted to access the Login Panel', 'loginpress-pro' );
				?>
			</div>
			<?php
		}

		/**
		 * Callback for error message 'woocommerce my-account login attempt'
		 *
		 * @since 2.1.0
		 */
		public function woo_attempt_error() {

			echo '<div class="woocommerce-error">';

			global $wpdb;
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.
			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}
			echo wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) );
			echo '</div>';
		}

		/**
		 * Check the limit
		 */
		public function user_limit_check() {

			global $wpdb;
			$current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
			$gate         = $this->gateway();

			$attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
			$lockout_increase  = isset( $this->attempts_settings['lockout_increase'] ) ? $this->attempts_settings['lockout_increase'] : '';
			$minutes_lockout   = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}

			$lockout_time = $current_time - ( $minutes_lockout * 60 );
			$attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s", $this->ip, $lockout_time ) ); // @codingStandardsIgnoreLine.

			return array(
				'attempts_allowed'  => $attempts_allowed,
				'lockout_increase'  => $lockout_increase,
				'minutes_lockout'   => $minutes_lockout,
				'last_attempt_time' => $last_attempt_time,
				'lockout_time'      => $lockout_time,
				'attempt_time'      => $attempt_time,
			);
		}

		/**
		 * Callback for error message 'llla_error'
		 *
		 * @param string $username username.
		 * @return string $error.
		 * @since  3.0.0
		 * @version 3.3.0
		 */
		public function limit_query( $username ) {

			global $wpdb;
			$current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
			$gate         = $this->gateway();
			$error        = new WP_Error();

			$attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
			$lockout_increase  = isset( $this->attempts_settings['lockout_increase'] ) ? $this->attempts_settings['lockout_increase'] : '';
			$minutes_lockout   = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}

			$lockout_time = $current_time - ( $minutes_lockout * 60 );

			$attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s", $this->ip, $lockout_time ) ); // @codingStandardsIgnoreLine.
			if ( $attempt_time + 1 < $attempts_allowed ) {
				// 0 Attempts overhead solution.
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$this->llla_table} (ip, username, datentime, gateway) values (%s, %s, %s, %s)", $this->ip, $username, $current_time, $gate ) ); // @codingStandardsIgnoreLine.

				return $this->loginpress_attempts_error( $attempt_time );

			} else {
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$this->llla_table} (ip, username, datentime, gateway) values (%s, %s, %s, %s)", $this->ip, $username, $current_time, $gate ) ); // @codingStandardsIgnoreLine.
				wp_die( $this->loginpress_lockout_error( $last_attempt_time ), 403 );

			}
		}

		/**
		 * Lockout error message.
		 *
		 * @param string $last_attempt_time time of the last attempt.
		 * @since  3.0.0
		 * @version 3.3.0
		 * @return string $lockout_message Custom error message
		 */
		public function loginpress_lockout_error( $last_attempt_time ) {

			$current_time    = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
			$time            = intval( $current_time - $last_attempt_time );
			$count           = (int) ( $time / 60 ) % 60;    // To get minutes.
			$minutes_set     = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
			$lockout_message = isset( $this->attempts_settings['lockout_message'] ) ? sanitize_text_field( $this->attempts_settings['lockout_message'] ) : '';
			$message         = '';

			if ( $count < $minutes_set ) {

				$remain = $minutes_set - $count;

				if ( empty( $lockout_message ) ) {
					$minute = 'minutes';

					if ( $remain === 1 ) {
						$minute = 'minute';
					}
					$message = sprintf( // translators: Default lockout message
						__( '%1$sError:%2$s Too many failed attempts. You are locked out for %3$s %4$s.', 'loginpress-pro' ),
						'<strong>',
						'</strong>',
						$remain,
						$minute
					); // @codingStandardsIgnoreLine
					return $message;
				} else {
					$lockout_message = str_replace( '%TIME%', $remain . ' Minutes', $lockout_message );
					$message         = sprintf( // translators: Custom lockout message
						__( '%1$sError:%2$s %3$s', 'loginpress-pro' ),
						'<strong>',
						'</strong>',
						$lockout_message
					); // @codingStandardsIgnoreLine
					return $message;
				}
			}
			return $message;
		}

		/**
		 * LoginPress Limit Login Attempts Time Checker.
		 *
		 * @return boolean
		 * @since  3.0.0
		 */
		public function llla_time() {
			if ( is_user_logged_in() ) {
				return;
			}
			global $wpdb;
			$current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.

			$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
			$lockout_increase = isset( $this->attempts_settings['lockout_increase'] ) ? $this->attempts_settings['lockout_increase'] : '';
			$minutes_lockout  = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';

			$lockout_time = $current_time - ( $minutes_lockout * 60 );
			if ( ! isset( $this->attempt_time ) || $this->attempt_time === null ) {
				$this->attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s AND `whitelist` = 0", $this->ip, $lockout_time ) ); // @codingStandardsIgnoreLine.
			}
			// 0 Attempts overhead solution.
			if ( $this->attempt_time < $attempts_allowed ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Attempts error message
		 *
		 * @param int $count counter.
		 * @return string [Custom error message]
		 * @since 1.0.0
		 */
		public function loginpress_attempts_error( $count ) {

			$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) ? intval( $this->attempts_settings['attempts_allowed'] ) : '';

			$remains = $attempts_allowed - $count - 1;
			/* Translators: The attempts. */
			$lockout_message = sprintf( __( '%1$sERROR:%2$s You have only %3$s attempts', 'loginpress-pro' ), '<strong>', '</strong>', $remains );

			// Check if the EDD class exists.
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				// translators: Modify the message without "ERROR" for EDD.
				$lockout_message = sprintf( __( 'You have only %3$s attempts remaining.', 'loginpress-pro' ), '<strong>', '</strong>', $remains );
			} else {
				// translators: Default message with "ERROR".
				$lockout_message = sprintf( __( '%1$sERROR:%2$s You have only %3$s attempts', 'loginpress-pro' ), '<strong>', '</strong>', $remains );
			}

			/**
			 * LoginPress limit Login Attempts Custom Error Message for the specific Attempt
			 *
			 * @param string $attempt_message The default Limit Login Attempts Error message.
			 * @param int    $count           The number of attempt from the user.
			 * @param int    $remaining       The remaining attempts of the users.
			 *
			 * @version 3.0.0
			 * @return array $llla_attempt_args the modified arguments.
			 */

			$llla_attempt_message = apply_filters( 'loginpress_attempt_error', $lockout_message, $count, $remains );
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				if ( $remains >= 1 ) {
					edd_set_error( 'loginpress-pro', $llla_attempt_message );
				}
			}

			$allowed_html = array(
				'a'      => array(),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'i'      => array(),
			);

			return wp_kses( $llla_attempt_message, $allowed_html );
		}

		/**
		 * Check the gateway.
		 *
		 * @return string
		 * @since  3.0.0
		 */
		public function gateway() {
			/**
			* Apply a filter to allow passing different login gateways (e.g., LifterLMS, LearnDash, etc.).
			*
			* @param string|bool $gateway Default is false. Should be replaced with custom gateway string.
			* @since 5.0.0
			*/
			$gateway_passed = apply_filters( 'loginpress_gateway_passed', false );
			if ( isset( $_POST['woocommerce-login-nonce'] ) ) {
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-login-nonce'] ) ), 'woocommerce-login' );
			}
			if ( isset( $_POST['woocommerce-login-nonce'] ) ) {
				$gateway = esc_html__( 'WooCommerce', 'loginpress-pro' );
			} elseif ( isset( $GLOBALS['wp_xmlrpc_server'] ) && is_object( $GLOBALS['wp_xmlrpc_server'] ) ) {
				$gateway = esc_html__( 'XMLRPC', 'loginpress-pro' );
			} elseif ( isset( $_POST['edd_login_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd_login_nonce'] ) ), 'edd-login' ) ) { // Check for EDD login form nonce
				$gateway = esc_html__( 'EDD Login', 'loginpress-pro' );
			} elseif ( $gateway_passed ) {
				$gateway = $gateway_passed;
			} else {
				$gateway = esc_html__( 'WP Login', 'loginpress-pro' );
			}

			return $gateway;
		}

		/**
		 * Get correct remote address
		 *
		 * @param string $type_name The address type.
		 *
		 * @return string
		 * @since  3.1.1
		 */
		public function get_address( $type_name = '' ) {

			$ip_address = '';
			if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );
			} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ) );
			} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) && ! empty( $_SERVER['HTTP_FORWARDED'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) );
			} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			} else {
				$ip_address = 'UNKNOWN';
			}

			return $ip_address;
		}
	}

endif;

new LoginPress_Attempts();
