<?php
/**
 * LoginPress_Social_Login_Check
 *
 * @package LoginPress_Social_Login
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'LoginPress_Social_Login_Check' ) ) {

	/**
	 * LoginPress_Social_Login_Check
	 */
	class LoginPress_Social_Login_Check {
		/**
		 * Class constructor.
		 */
		public function __construct() {
				$this->set_redirect_to();
				$this->loginpress_check();

				if ( isset( $_REQUEST['oauth_verifier'] ) ) {// @codingStandardsIgnoreLine.
					$lp_twitter_oauth = get_option( 'loginpress_twitter_oauth' );
					if ( isset( $lp_twitter_oauth['oauth_token'] ) ) {
						$this->loginpress_on_twitter_login();
					}
				}
				if ( isset( $_GET['code'] ) && isset( $_GET['state'] )) {// @codingStandardsIgnoreLine.
					if ( isset( $_GET['lpsl_login_id'] ) )
					{
						$exploder = explode( '_', $_GET['lpsl_login_id'] );
						if ('twitter' === $exploder[0]){
							$this->loginpress_on_twitter_login();
						}
					} 
					// else {
					// 	$this->loginpress_on_twitter_login();
					// }
				}
		}

		/**
		 * Set Cookie for the `redirect_to` args
		 *
		 * @version 3.0.0
		 */
		public function set_redirect_to() {

			if ( isset( $_REQUEST['redirect_to'] ) ) {// @codingStandardsIgnoreLine.

				// 60 seconds ( 1 minute) * 20 = 20 minutes
				setcookie( 'lg_redirect_to', $_REQUEST['redirect_to'], time() + ( 60 * 20 ) ); // @codingStandardsIgnoreLine.
			}
		}
		/**
		 * Execute the specific Social login.
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 *
		 * @return void
		 */
		public function loginpress_check() {
			if ( isset( $_GET['verification'] ) ) {
				set_transient( 'loginpress_verify_status', 1, 120 );
			}
			if ( isset( $_GET['lpsl_login_id'] ) ) { // @codingStandardsIgnoreLine.
				$exploder = explode( '_', $_GET['lpsl_login_id'] ); // @codingStandardsIgnoreLine.
				if ( 'facebook' === $exploder[0] ) {
					if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
						esc_html_e( 'The Facebook SDK requires PHP version 5.4 or higher. Please notify about this error to site admin.', 'loginpress-pro' );
						die();
					}
					$this->loginpress_on_facebook_login();
				} elseif ( 'twitter' === $exploder[0] ) {
					$this->loginpress_on_twitter_login();
				} elseif ( 'gplus' === $exploder[0] ) {
					$this->loginpress_on_google_login();
				} elseif ( 'linkedin' === $exploder[0] ) {
					$this->loginpress_on_linkedin_login();
				} elseif ( 'microsoft' === $exploder[0] ) {
					$this->loginpress_on_microsoft_login();
				} elseif ( 'apple' === $exploder[0] ) {
					$this->loginpress_on_apple_login();
				} elseif ( 'github' === $exploder[0] ) {
					$this->loginpress_on_github_login();
				} elseif ( 'discord' === $exploder[0] ) {
					$this->loginpress_on_discord_login();
				} elseif ( 'wordpress' === $exploder[0] ) {
					$this->loginpress_on_wordpress_login();
				} elseif ( 'amazon' === $exploder[0] ) {
					$this->loginpress_on_amazon_login();
				} elseif ( 'pinterest' === $exploder[0] ) {
					$this->loginpress_on_pinterest_login();
				} elseif ( 'disqus' === $exploder[0] ) {
					$this->loginpress_on_disqus_login();
				} elseif ( 'reddit' === $exploder[0] ) {
					$this->loginpress_on_reddit_login();
				} elseif ( 'spotify' === $exploder[0] ) {
					$this->loginpress_on_spotify_login();
				} elseif ( 'twitch' === $exploder[0] ) {
					$this->loginpress_on_twitch_login();
				}
			}

			if ( isset( $_GET['state'] ) && false !== strpos( $_GET['state'], 'lpsl_login=microsoft' ) ) {
				$this->loginpress_on_microsoft_login();
			}
			if ( isset( $_GET['state'] ) && false !== strpos( $_GET['state'], 'pinterest' ) ) {
				$this->loginpress_on_pinterest_login();
			}
		}

		/**
		 * Login with Apple Account.
		 *
		 * @since 4.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function loginpress_on_apple_login() {
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-apple.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$response_class       = new stdClass();
			$apple_login          = new LoginPress_Apple();
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$profile              = $apple_login->apple_login( $response_class );
			// Check if the email/s are passed from the filter, it will only allow the email/s to login.
			$is_apple_restricted = apply_filters( 'loginpress_social_login_apple_domains', false );

			if ( $is_apple_restricted && is_array( $is_apple_restricted ) ) {
				if ( ! $this->loginpress_is_eligible_social_domain( $profile->email, $is_apple_restricted ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'lp_social_error' => 'true',
							),
							wp_login_url()
						)
					);
					die();
				}
			}

			if ( isset( $profile->status ) && 'SUCCESS' === $profile->status ) {
				if ( get_transient( 'loginpress_verify_status' ) ) {
					echo '<script>
						console.log("Sending verification message to parent window.");
						if (window.opener) {
							window.close();
							window.opener.postMessage("verified", window.location.origin);
							
						} else {
							console.error("No opener window found.");
						}
					</script>';
					delete_transient( 'loginpress_verify_status' );
					exit();
				}

				if ( ! empty( $profile->email ) ) {

					$result = $this->loginpress_create_result_obj( $profile, 'apple' );
					global $wpdb;
					$sha_verifier = sha1( $result->deutype . $result->deuid );
					$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s", $result->deutype, $result->deuid, $sha_verifier)); // @codingStandardsIgnoreLine.
					$user_object  = get_user_by( 'email', $profile->email );
					if ( ! $row ) {
						// check if there is already a user with the email address provided from social login.
						if ( false !== $user_object ) {
							// user already there so log him in.
							$id  = $user_object->ID;
							$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d", $id)); // @codingStandardsIgnoreLine.

							if ( ! $row ) {
								$loginpress_utilities->link_user( $id, $result );
							}
							$role = get_option( 'default_role' );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'apple_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'apple_login' );
							}
							die();
						}
						$loginpress_utilities->register_user( $result->username, $result->email );
						$user_object = get_user_by( 'email', $result->email );
						$id          = $user_object->ID;
						$role        = get_option( 'default_role' );
						$loginpress_utilities->update_usermeta( $id, $result, $role );
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object );
						}
						exit();
					} elseif ( isset( $row[0] ) && ( $row[0]->identifier === $result->deuid ) ) {
						$user_object = get_user_by( 'email', $result->email );
						$id          = $user_object->ID;
						$role        = get_option( 'default_role' );
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, 'apple_login', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'apple_login' );
						}

						exit();
					} else {
						// user not found in our database.
						echo esc_html__( 'user not found in our database', 'loginpress-pro' );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				if (isset($_REQUEST['error'])) { // @codingStandardsIgnoreLine.
					$redirect_url = isset($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : site_url(); // @codingStandardsIgnoreLine.
					$loginpress_utilities->redirect( $redirect_url );
				}
				die();
			}
		}

		/**
		 * Login with Github Account.
		 *
		 * @since 4.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function loginpress_on_github_login() {

			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$github_client_id     = $lp_social_settings['github_client_id'];
			$github_client_secret = $lp_social_settings['github_client_secret'];
			$github_redirect_uri  = $lp_social_settings['github_redirect_uri'];
			$github_app_name      = $lp_social_settings['github_app_name'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params   = array(
					'code'          => $_GET['code'],  // @codingStandardsIgnoreLine.
					'client_id'     => $github_client_id,
					'client_secret' => $github_client_secret,
					'redirect_uri'  => $github_redirect_uri,
				);
				$response = wp_remote_post(
					'https://github.com/login/oauth/access_token',
					array(
						'body'    => $params,
						'headers' => array( 'Accept' => 'application/json' ),
					)
				);
				$response = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {

					$response = wp_remote_get(
						'https://api.github.com/user/emails',
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $response['access_token'],
								'User-Agent'    => $github_app_name,
							),
						)
					);
					$profile  = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

					if ( isset( $profile[0]['email'] ) && ! empty( $profile[0]['email'] ) ) {
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								console.log("Sending verification message to parent window.");
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'github' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile[0]['email'] );
						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'github_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'github_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'github_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'github_login' );
							}

							exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Github Authentication page.
				$params = array(
					'client_id'    => $github_client_id,
					'redirect_uri' => $github_redirect_uri,
					'scope'        => 'user,user:email',
				);
				wp_redirect( 'https://github.com/login/oauth/authorize?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Handle the discord login process
		 *
		 * @since 4.0.0
		 * @version 5.0.0
		 */
		public function loginpress_on_discord_login() {

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$settings             = get_option( 'loginpress_social_logins' );
			$client_id            = $settings['discord_client_id'];
			$client_secret        = $settings['discord_client_secret'];
			$discord_redirect     = $settings['discord_redirect_uri'];
			$discord_gen_url      = $settings['discord_generated_url'];

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {

				$payload           = array(
					'code'          => $_GET['code'],
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'grant_type'    => 'authorization_code',
					'redirect_uri'  => $discord_redirect,
					'scope'         => 'identify%20email',
				);
				$payload_string    = http_build_query( $payload );
				$discord_token_url = 'https://discord.com/api/oauth2/token';
				$response          = wp_remote_post(
					$discord_token_url,
					array(
						'body' => $payload_string,
					)
				);

				$response_body = wp_remote_retrieve_body( $response );
				$response      = json_decode( $response_body, true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Execute HTTP request to retrieve the user info associated with the Discord account.
					$response      = wp_remote_get(
						'https://discord.com/api/users/@me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$response_body = wp_remote_retrieve_body( $response );
					$profile       = json_decode( $response_body, true );

					// Make sure the profile data exists.
					if ( isset( $profile['email'] ) ) {
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								console.log("Sending verification message to parent window.");
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'discord' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['email'] );
						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'discord_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'discord_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'discord_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'discord_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {

				header( 'Location: ' . $discord_gen_url );
				exit;
			}
		}

		/**
		 * Login with WordPress Account.
		 *
		 * @since 4.0.0
		 * @version 5.0.2
		 * @return void
		 */
		public function loginpress_on_wordpress_login() {

			$lp_social_settings     = get_option( 'loginpress_social_logins' );
			$wordpress_client_id     = $lp_social_settings['wordpress_client_id'];
			$wordpress_client_secret = $lp_social_settings['wordpress_client_secret'];
			$wordpress_redirect_uri  = $lp_social_settings['wordpress_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params   = array(
					'client_id'     => $wordpress_client_id,
					'redirect_uri'  => $wordpress_redirect_uri,
					'client_secret' => $wordpress_client_secret,
					'code'          => $_GET['code'],  // @codingStandardsIgnoreLine.
					'grant_type'    => 'authorization_code',
				);
				$response = wp_remote_post(
					'https://public-api.wordpress.com/oauth2/token',
					array(
						'body' => $params,
					)
				);
				$response = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {

					$response = wp_remote_get(
						'https://public-api.wordpress.com/rest/v1.1/me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$profile  = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

					// Make sure the profile data exists.
					if ( isset( $profile['email'] ) && isset( $profile['email_verified'] ) && $profile['email_verified'] === true ) {
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								console.log("Sending verification message to parent window.");
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'wordpress' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['email'] );
						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'wordpress_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'wordpress_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && (int) $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'wordpress_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'wordpress_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						if ( isset( $profile['email_verified'] ) && $profile['email_verified'] !== true ){
							add_filter( 'authenticate', array( $this, 'loginpress_wp_social_login_error' ), 40, 3 );
						} else {
							add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
						}
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to WordPress Authentication page.
				$params = array(
					'client_id'     => $wordpress_client_id,
					'response_type' => 'code',
					'redirect_uri'  => $wordpress_redirect_uri,
				);
				wp_redirect( 'https://public-api.wordpress.com/oauth2/authorize?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Microsoft Account.
		 *
		 * @version 5.0.0
		 * @return void
		 */
		public function loginpress_on_microsoft_login() {
			require_once LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/microsoft/vendor/autoload.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-microsoft.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();
			$settings             = get_option( 'loginpress_social_logins' );
			$client_id            = $settings['microsoft_app_id'];
			$client_secret        = $settings['microsoft_app_secret'];
			$callback             = $settings['microsoft_redirect_uri'];
			$scopes               = array( 'User.Read', 'offline_access' );
			$tenant               = 'common';

			$microsoft_handler = new LoginPressMicrosoftLoginHandler( $tenant, $client_id, $client_secret, $callback, $scopes );

			if ( isset( $_GET['lpsl_login_id'] ) && $_GET['lpsl_login_id'] === 'microsoft_login' ) {
				$microsoft_handler->loginpress_microsoft_login();
			} else {
				$data                    = $microsoft_handler->loginpress_handle_returning_user();
				$id                      = $data->getId();
				$name                    = $data->getDisplayName();
				$result                  = new stdClass();
				$result->status          = 'SUCCESS';
				$result->deuid           = $id;
				$result->deutype         = 'microsoft';
				$result->first_name      = $data->getGivenName();
				$result->about           = '';
				$result->gender          = '';
				$result->url             = '';
				$result->last_name       = $data->getsurname();
				$result->email           = $data->getUserPrincipalName();
				$result->username        = ( '' !== $data->getGivenName() ) ? strtolower( $data->getGivenName() ) : $data['email'];
				$result->deuimage        = get_avatar_url( $result->email, array( 'size' => 150 ) );
				$is_microsoft_restricted = apply_filters( 'loginpress_social_login_microsoft_domains', false );

				if ( $is_microsoft_restricted && is_array( $is_microsoft_restricted ) ) {
					if ( ! $this->loginpress_is_eligible_social_domain( $data->getUserPrincipalName(), $is_microsoft_restricted ) ) {
						wp_safe_redirect(
							add_query_arg(
								array(
									'lp_social_error' => 'true',
								),
								wp_login_url()
							)
						);
						die();
					}
				}
				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $result->deuid );
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.

				$user_object = get_user_by( 'email', $data->getUserPrincipalName() );
				if ( get_transient( 'loginpress_verify_status' ) ) {
					echo '<script>
						console.log("Sending verification message to parent window.");
						if (window.opener) {
							window.close();
							window.opener.postMessage("verified", window.location.origin);
							
						} else {
							console.error("No opener window found.");
						}
					</script>';
					delete_transient( 'loginpress_verify_status' );
					exit();
				}
				if ( ! $row ) {
					// check if there is already a user with the email address provided from social login already.
					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						$loginpress_utilities->_home_url( $user_object, 'microsoft_login' );
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$role        = get_option( 'default_role' );
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					$loginpress_utilities->_home_url( $user_object );
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {

						$user_object = get_user_by( 'email', $result->email );
						$id          = $user_object->ID;
						$loginpress_utilities->_home_url( $user_object, 'microsoft_login' );
						exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			}
		}

		/**
		 * Login with LinkedIn Account.
		 * Fixed the LinkedIn authorization redirection loop issue.
		 *
		 * @version 5.0.0
		 * @return void
		 */
		public function loginpress_on_linkedin_login() {

			$_settings     = get_option( 'loginpress_social_logins' );
			$client_id     = $_settings['linkedin_client_id'];      // LinkedIn client ID.
			$client_secret = $_settings['linkedin_client_secret']; // LinkedIn client secret.
			$redirect_url  = $_settings['linkedin_redirect_uri']; // Callback URL.

			if ( ! isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine.

				wp_redirect( "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$client_id}&redirect_uri={$redirect_url}&state=987654321&scope=openid%20profile%20email" );
				exit();
			} else {

				$get_access_token = wp_remote_post(
					'https://www.linkedin.com/oauth/v2/accessToken',
					array(
						'body' => array(
							'grant_type'    => 'authorization_code',
							'code'          => $_GET['code'], // @codingStandardsIgnoreLine.
							'redirect_uri'  => $redirect_url,
							'client_id'     => $client_id,
							'client_secret' => $client_secret,
						),
					)
				);

				// Check if the request failed or if the response body is invalid.
				if ( is_wp_error( $get_access_token ) || wp_remote_retrieve_response_code( $get_access_token ) !== 200 ) {
					echo '<script>alert("Invalid client secret or authorization error. Please check your credentials or contact the admin.");</script>';
					exit();
				}

				$_access_token = json_decode( $get_access_token['body'] )->access_token;

				if ( ! $_access_token ) {
					$user_login_url = apply_filters( 'login_redirect', admin_url(), site_url(), wp_signon() );
					wp_redirect( $user_login_url );
				}

				$get_user_details = wp_remote_get(
					'https://api.linkedin.com/v2/userinfo',
					array(
						'method' => 'GET', // @codingStandardsIgnoreLine.
						'timeout' => 15,
						'headers' => array( 'Authorization' => 'Bearer ' . $_access_token ),
					)
				);

				if ( ! is_wp_error( $get_user_details ) && isset( $get_user_details['response']['code'] ) && 200 === $get_user_details['response']['code'] ) {

					$light_detail_body = json_decode( wp_remote_retrieve_body( $get_user_details ) );
					$first_name        = isset( $light_detail_body->given_name ) ? $light_detail_body->given_name : '';
					$last_name         = isset( $light_detail_body->family_name ) ? $light_detail_body->family_name : '';
					$large_avatar      = isset( $light_detail_body->picture ) ? $light_detail_body->picture : '';
					$email_address     = isset( $light_detail_body->email ) ? $light_detail_body->email : '';
					$deuid             = isset( $light_detail_body->sub ) ? $light_detail_body->sub : '';
				}

				if ( empty( $email_address ) || empty( $deuid ) ) {
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
					exit;
				}
				$is_linkedin_restricted = apply_filters( 'loginpress_social_login_linkedin_domains', false );

				if ( $is_linkedin_restricted && is_array( $is_linkedin_restricted ) ) {
					if ( ! $this->loginpress_is_eligible_social_domain( $email_address, $is_linkedin_restricted ) ) {
						wp_safe_redirect(
							add_query_arg(
								array(
									'lp_social_error' => 'true',
								),
								wp_login_url()
							)
						);
						die();
					}
				}
				include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

				$loginpress_utilities = new LoginPress_Social_Utilities();

				$result = $this->loginpress_create_result_obj(
					$profile,
					'linkedin',
					array(
						'deuid'         => $deuid,
						'first_name'    => $first_name,
						'last_name'     => $last_name,
						'email_address' => $email_address,
						'large_avatar'  => $large_avatar,
					)
				);

				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $deuid );
				$identifier   = $deuid;
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.

				$user_object = get_user_by( 'email', $result->email );
				if ( get_transient( 'loginpress_verify_status' ) ) {
					echo '<script>
						console.log("Sending verification message to parent window.");
						if (window.opener) {
							window.close();
							window.opener.postMessage("verified", window.location.origin);
							
						} else {
							console.error("No opener window found.");
						}
					</script>';
					delete_transient( 'loginpress_verify_status' );
					exit();
				}

				if ( ! $row ) {
					// check if there is already a user with the email address provided from social login already.
					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						$loginpress_utilities->_home_url( $user_object );
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$role        = get_option( 'default_role' );
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					$loginpress_utilities->_home_url( $user_object );
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $deuid ) ) {

						$user_object = get_user_by( 'email', $result->email );
						$id          = $user_object->ID;
						$loginpress_utilities->_home_url( $user_object );

						exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			}
		}

		/**
		 * Login with Google Account.
		 *
		 * @version 5.0.0
		 * @return void
		 */
		public function loginpress_on_google_login() {

			$_settings                  = get_option( 'loginpress_social_logins' );
			$google_oauth_client_id     = $_settings['gplus_client_id']; // Google client ID.
			$google_oauth_client_secret = $_settings['gplus_client_secret']; // Google client secret.
			$google_oauth_redirect_uri  = $_settings['gplus_redirect_uri']; // Callback URL.
			$google_oauth_version       = 'v3';

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();
			// If the captured code param exists and is valid.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params        = array(
					'code'          => $_GET['code'],  // @codingStandardsIgnoreLine.
					'client_id'     => $google_oauth_client_id,
					'client_secret' => $google_oauth_client_secret,
					'redirect_uri'  => $google_oauth_redirect_uri,
					'grant_type'    => 'authorization_code',
				);
				$response      = wp_remote_post(
					'https://accounts.google.com/o/oauth2/token',
					array(
						'body' => $params,
					)
				);
				$response_body = wp_remote_retrieve_body( $response );
				$response      = json_decode( $response_body, true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Execute HTTP request to retrieve the user info associated with the Google account.
					$response      = wp_remote_get(
						'https://www.googleapis.com/oauth2/' . $google_oauth_version . '/userinfo',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$response_body = wp_remote_retrieve_body( $response );
					$profile       = json_decode( $response_body, true );

					// Make sure the profile data exists.
					if ( isset( $profile['email'] ) ) {

						$result = $this->loginpress_create_result_obj( $profile, 'gplus' );

						$is_google_restricted = apply_filters( 'loginpress_social_login_google_domains', false );

						if ( $is_google_restricted && is_array( $is_google_restricted ) ) {
							if ( ! $this->loginpress_is_eligible_social_domain( $result->email, $is_google_restricted ) ) {
								wp_redirect(
									add_query_arg(
										array(
											'lp_social_error' => 'true',
										),
										wp_login_url()
									)
								);
								die();
							}
						}
						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$identifier   = $profile['sub'];
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.

						$user_object = get_user_by( 'email', $profile['email'] );

						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								console.log("Sending verification message to parent window.");
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}

						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$loginpress_utilities->_home_url( $user_object, 'google_login' );
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							$loginpress_utilities->_home_url( $user_object );
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$loginpress_utilities->_home_url( $user_object, 'google_login' );

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Google Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $google_oauth_client_id,
					'redirect_uri'  => $google_oauth_redirect_uri,
					'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
					'access_type'   => 'offline',
					'prompt'        => 'consent',
				);
				wp_redirect( 'https://accounts.google.com/o/oauth2/auth?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Facebook Account.
		 *
		 * @version 5.0.0
		 */
		public function loginpress_on_facebook_login() {

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-facebook.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			$response_class         = new stdClass();
			$facebook_login         = new LoginPress_Facebook();
			$loginpress_utilities   = new LoginPress_Social_Utilities();
			$result                 = $facebook_login->facebook_login( $response_class );
			$is_facebook_restricted = apply_filters( 'loginpress_social_login_facebook_domains', false );

			if ( $is_facebook_restricted && is_array( $is_facebook_restricted ) ) {
				if ( ! $this->loginpress_is_eligible_social_domain( $result->email, $is_facebook_restricted ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'lp_social_error' => 'true',
							),
							wp_login_url()
						)
					);
					die();
				}
			}
			if ( isset( $result->status ) && 'SUCCESS' === $result->status ) {
				if ( get_transient( 'loginpress_verify_status' ) ) {
					echo '<script>
						console.log("Sending verification message to parent window.");
						if (window.opener) {
							window.close();
							window.opener.postMessage("verified", window.location.origin);
							
						} else {
							console.error("No opener window found.");
						}
					</script>';
					delete_transient( 'loginpress_verify_status' );
					exit();
				}
				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $result->deuid );
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
				$user_object  = get_user_by( 'email', $result->email );

				if ( ! isset( $row[0]->email ) && $result->email === $result->deuid . '@facebook.com' ) {
					$result->email = $result->email;

				} elseif ( $result->email === $result->deuid . '@facebook.com' ) {
					$result->email = $row[0]->email;
				}

				if ( ! $row ) {
					// check if there is already a user with the email address provided from social login already.
					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.
						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						$loginpress_utilities->_home_url( $user_object );
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$role        = get_option( 'default_role' );
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					$loginpress_utilities->_home_url( $user_object );
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {
					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$loginpress_utilities->_home_url( $user_object );
					exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			} else {
				if ( isset( $_REQUEST['error'] ) ) { // @codingStandardsIgnoreLine.

					$redirect_url = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : site_url(); // @codingStandardsIgnoreLine.
					$loginpress_utilities->redirect( $redirect_url );
				}
				die();
			}
		}

		/**
		 * Login with Twitter Account.
		 *
		 * @version 5.0.0
		 * @return void
		 */
		public function loginpress_on_twitter_login() {

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-twitter.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$response_class       = new stdClass();
			$twitter_login        = new LoginPress_Twitter();
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$login_settings       = get_option( 'loginpress_social_logins' );
			if ( isset( $login_settings['twitter_api_version'] ) && $login_settings['twitter_api_version'] == 'oauth2' ) {
				$result = $twitter_login->twitter_login_oauth2( $response_class );
			} else {
				$result = $twitter_login->twitter_login( $response_class );
			}
				$is_twitter_restricted = apply_filters( 'loginpress_social_login_twitter_domains', false );

			if ( $is_twitter_restricted && is_array( $is_twitter_restricted ) ) {
				if ( ! $this->loginpress_is_eligible_social_domain( $result->email, $is_twitter_restricted ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'lp_social_error' => 'true',
							),
							wp_login_url()
						)
					);
					die();
				}
			}
			if ( isset( $result->status ) && is_object( $result ) && 'SUCCESS' === $result->status ) {
				if ( get_transient( 'loginpress_verify_status' ) ) {
					echo '<script>
						console.log("Sending verification message to parent window.");
						if (window.opener) {
							window.close();
							window.opener.postMessage("verified", window.location.origin);
							
						} else {
							console.error("No opener window found.");
						}
					</script>';
					delete_transient( 'loginpress_verify_status' );
					exit();
				}
				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $result->deuid );
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.

				if ( ! $row ) {
					// check if there is already a user with the email address provided from social login already.
					$user_object = get_user_by( 'email', $result->email );

					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						$loginpress_utilities->_home_url( $user_object );
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$role        = get_option( 'default_role' );
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					$loginpress_utilities->_home_url( $user_object );
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {

					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$loginpress_utilities->_home_url( $user_object );
					exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			} else {
				if ( isset( $_REQUEST['denied'] ) ) { // @codingStandardsIgnoreLine.
					$redirect_url = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : site_url(); // @codingStandardsIgnoreLine.
					$loginpress_utilities->redirect( $redirect_url );
				}
				die();
			}
		}

		/**
		 * Login with Amazon Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_amazon_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$amazon_client_id     = $lp_social_settings['amazon_client_id'];
			$amazon_client_secret = $lp_social_settings['amazon_client_secret'];
			$amazon_redirect_uri  = $lp_social_settings['amazon_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'client_id'     => $amazon_client_id,
					'client_secret' => $amazon_client_secret,
					'redirect_uri'  => $amazon_redirect_uri,
				);

				$response = wp_remote_post(
					'https://api.amazon.com/auth/o2/token',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $response ), true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.amazon.com/user/profile',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['email'] ) && ! empty( $profile['email'] ) ) {

						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'amazon' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['email'] );

						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'amazon_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'amazon_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'amazon_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'amazon_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Amazon Authentication page.
				$params = array(
					'client_id'     => $amazon_client_id,
					'response_type' => 'code',
					'redirect_uri'  => $amazon_redirect_uri,
					'scope'         => 'profile',
				);

				wp_redirect( 'https://www.amazon.com/ap/oa?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Pinterest Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_pinterest_login() {
			$lp_social_settings      = get_option( 'loginpress_social_logins' );
			$pinterest_client_id     = $lp_social_settings['pinterest_client_id'];
			$pinterest_client_secret = $lp_social_settings['pinterest_client_secret'];
			$pinterest_redirect_uri  = $lp_social_settings['pinterest_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'         => 'authorization_code',
					'code'               => $_GET['code'],
					'continuous_refresh' => false,
					'scope'              => 'user_accounts:read',
					'redirect_uri'       => $pinterest_redirect_uri,
				);

				$response = wp_remote_post(
					'https://api.pinterest.com/v5/oauth/token',
					array(
						'body'    => $params,
						'headers' => array(
							'Content-Type'  => 'application/x-www-form-urlencoded',
							'Authorization' => 'Basic ' . base64_encode( $pinterest_client_id . ':' . $pinterest_client_secret ),
						),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $response ), true );
				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.pinterest.com/v5/user_account',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$profile          = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['username'] ) && ! empty( $profile['username'] ) ) {

						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}
						// Process the user profile information.
						$email  = $profile['username'] . '@pinterest.com';
						$result = $this->loginpress_create_result_obj( $profile, 'pinterest', array( 'email' => $email ) );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $email );

						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'pinterest_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'pinterest_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'pinterest_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'pinterest_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Pinterest Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $pinterest_client_id,
					'redirect_uri'  => $pinterest_redirect_uri,
					'scope'         => 'user_accounts:read',
					'state'         => 'pinterest',
				);

				wp_redirect( 'https://www.pinterest.com/oauth/?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Disqus Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_disqus_login() {
			$lp_social_settings  = get_option( 'loginpress_social_logins' );
			$disqus_api_key      = $lp_social_settings['disqus_client_id'];
			$disqus_api_secret   = $lp_social_settings['disqus_client_secret'];
			$disqus_callback_url = $lp_social_settings['disqus_callback_url'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'redirect_uri'  => $disqus_callback_url,
					'client_id'     => $disqus_api_key,
					'client_secret' => $disqus_api_secret,
				);

				$response = wp_remote_post(
					'https://disqus.com/api/oauth/2.0/access_token/',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://disqus.com/api/3.0/users/details.json?api_key=' . $disqus_api_key . '&access_token=' . $response['access_token']
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['response']['id'] ) && ! empty( $profile['response']['id'] ) ) {
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}

						$email = $profile['response']['username'] . '@disqus.com';

						$result = $this->loginpress_create_result_obj( $profile, 'disqus', array( 'email' => $email ) );
						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s  LIMIT 1",
								$result->deutype,
								$result->deuid,
								$sha_verifier
							)
						);

						$user_object = get_user_by( 'email', $email );

						if ( ! $row ) {
							if ( false !== $user_object ) {
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d  LIMIT 1", $id ) );

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								$loginpress_utilities->_home_url( $user_object, 'disqus_login', $role === 'subscriber' ? 'subscriber' : '' );
								die();
							}

							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							$loginpress_utilities->_home_url( $user_object, '', $role === 'subscriber' ? 'subscriber' : '' );
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
							$user_object = get_user_by( 'email', $result->email );
							$loginpress_utilities->_home_url( $user_object, 'disqus_login', get_option( 'default_role' ) === 'subscriber' ? 'subscriber' : '' );
							exit();
						} else {
							echo esc_html__( 'User not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Redirect to Disqus Authentication page.
				$params = array(
					'client_id'     => $disqus_api_key,
					'redirect_uri'  => $disqus_callback_url,
					'response_type' => 'code',
					'scope'         => 'read,write',
				);

				wp_redirect( 'https://disqus.com/api/oauth/2.0/authorize/?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Reddit Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_reddit_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$reddit_client_id     = $lp_social_settings['reddit_client_id'];
			$reddit_client_secret = $lp_social_settings['reddit_client_secret'];
			$reddit_redirect_uri  = $lp_social_settings['reddit_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'client_id'     => $reddit_client_id,
					'client_secret' => $reddit_client_secret,
					'redirect_uri'  => $reddit_redirect_uri,
				);

				$response = wp_remote_post(
					'https://www.reddit.com/api/v1/access_token',
					array(
						'body'    => $params,
						'headers' => array(
							'Content-Type'  => 'application/x-www-form-urlencoded',
							'Authorization' => 'Basic ' . base64_encode( $reddit_client_id . ':' . $reddit_client_secret ),
						),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $response ), true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://oauth.reddit.com/api/v1/me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['name'] ) && ! empty( $profile['name'] ) ) {
						// Process the user profile information.
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}
						// Process the user profile information.
						$email  = $profile['name'] . '@reddit.com';
						$result = $this->loginpress_create_result_obj( $profile, 'reddit', array( 'email' => $email ) );
						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $email );

						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'reddit_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'reddit_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'reddit_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'reddit_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Reddit Authentication page.
				$params = array(
					'client_id'     => $reddit_client_id,
					'response_type' => 'code',
					'redirect_uri'  => $reddit_redirect_uri,
					'scope'         => 'identity',
					'state'         => uniqid( '', true ), // Generate a unique state parameter to prevent CSRF attacks.
				);

				wp_redirect( 'https://www.reddit.com/api/v1/authorize?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Spotify Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_spotify_login() {
			$lp_social_settings    = get_option( 'loginpress_social_logins' );
			$spotify_client_id     = $lp_social_settings['spotify_client_id'];
			$spotify_client_secret = $lp_social_settings['spotify_client_secret'];
			$spotify_redirect_uri  = $lp_social_settings['spotify_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'redirect_uri'  => $spotify_redirect_uri,
					'client_id'     => $spotify_client_id,
					'client_secret' => $spotify_client_secret,
				);

				$response = wp_remote_post(
					'https://accounts.spotify.com/api/token',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $response ), true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.spotify.com/v1/me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['id'] ) && ! empty( $profile['id'] ) ) {
						// Process the user profile information.
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}
						// Process the user profile information.
						$result = $this->loginpress_create_result_obj( $profile, 'spotify' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['email'] );

						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'spotify_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'spotify_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'spotify_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'spotify_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Spotify Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $spotify_client_id,
					'redirect_uri'  => $spotify_redirect_uri,
					'scope'         => 'user-read-email user-read-private',
					'state'         => uniqid( '', true ), // Generate a unique state parameter to prevent CSRF attacks.
				);

				wp_redirect( 'https://accounts.spotify.com/authorize?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Login with Twitch Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_twitch_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$twitch_client_id     = $lp_social_settings['twitch_client_id'];
			$twitch_client_secret = $lp_social_settings['twitch_client_secret'];
			$twitch_redirect_uri  = $lp_social_settings['twitch_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'client_id'     => $twitch_client_id,
					'client_secret' => $twitch_client_secret,
					'code'          => $_GET['code'],
					'grant_type'    => 'authorization_code',
					'redirect_uri'  => $twitch_redirect_uri,
				);

				$response = wp_remote_post(
					'https://id.twitch.tv/oauth2/token',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $response ), true );

				// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.twitch.tv/helix/users',
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $response['access_token'],
								'Client-ID'     => $twitch_client_id,
							),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['data'][0]['id'] ) && ! empty( $profile['data'][0]['id'] ) ) {
						// Process the user profile information.
						if ( get_transient( 'loginpress_verify_status' ) ) {
							echo '<script>
								if (window.opener) {
									window.close();
									window.opener.postMessage("verified", window.location.origin);
									
								} else {
									console.error("No opener window found.");
								}
							</script>';
							delete_transient( 'loginpress_verify_status' );
							exit();
						}
						// Process the user profile information.
						$result = $this->loginpress_create_result_obj( $profile, 'twitch' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['data'][0]['email'] );

						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$role = get_option( 'default_role' );
								if ( $role === 'subscriber' ) {
									$loginpress_utilities->_home_url( $user_object, 'twitch_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'twitch_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$role        = get_option( 'default_role' );
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, '', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
								$role        = get_option( 'default_role' );
							if ( $role === 'subscriber' ) {
								$loginpress_utilities->_home_url( $user_object, 'twitch_login', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'twitch_login' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Twitch Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $twitch_client_id,
					'redirect_uri'  => $twitch_redirect_uri,
					'scope'         => 'user:read:email',
				);

				wp_redirect( 'https://id.twitch.tv/oauth2/authorize?' . http_build_query( $params ) );
				exit;
			}
		}

		/**
		 * Create result object for loginpress.
		 *
		 * @since 5.0.0
		 * @param array  $profile   Profile data.
		 * @param string $deutype  Type of the social login.
		 * @param array  $overrides Overrides for specific providers.
		 * @return object Result object.
		 */
		public function loginpress_create_result_obj( $profile, $deutype, $overrides = array() ) {

			// initialize the result object
			$result = new stdClass();

			// Default values
			$result->status   = 'SUCCESS';
			$result->deutype  = $deutype;
			$result->gender   = '';
			$result->url      = '';
			$result->about    = '';
			$result->deuimage = 'https://secure.gravatar.com/avatar/75d23af433e0cea4c0e45a56dba18b30?s=96&d=mm';

			// Handle provider-specific logic
			switch ( $deutype ) {
				case 'github':
					$emailToParse = isset( $profile[1]['email'] ) ? $profile[1]['email'] : '';
					preg_match( '/^(\d+)\+([^\@]+)@/', $emailToParse, $matches );
					$result->deuid      = isset( $matches[1] ) ? $matches[1] : '';
					$result->first_name = isset( $matches[2] ) ? $matches[2] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile[0]['email'] ) ? $profile[0]['email'] : '';
					$result->username   = ! empty( $matches[2] ) ? strtolower( $matches[2] ) : $profile[0]['email'];
					break;

				case 'wordpress':
					$result->deuid      = isset( $profile['ID'] ) ? $profile['ID'] : '';
					$result->first_name = isset( $profile['display_name'] ) ? $profile['display_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['display_name'] ) ? strtolower( $profile['display_name'] ) : $profile['email'];
					$result->deuimage   = $profile['avatar_URL'] ?? $result->deuimage;
					$result->deuimage   = isset( $profile['avatar_URL'] ) ? $profile['avatar_URL'] : $result->deuimage;
					break;

				case 'linkedin':
					$result->deuid         = isset( $overrides['deuid'] ) ? $overrides['deuid'] : '';
					$result->first_name    = isset( $overrides['first_name'] ) ? $overrides['first_name'] : '';
					$result->last_name     = isset( $overrides['last_name'] ) ? $overrides['last_name'] : '';
					$result->email         = ! empty( $overrides['email_address'] ) ? $overrides['email_address'] : $result->deuid . '@linkedin.com';
					$result->username      = strtolower( $result->first_name . '_' . $result->last_name );
					$result->gender        = 'N/A';
					$result->deuimage      = isset( $overrides['large_avatar'] ) ? $overrides['large_avatar'] : $result->deuimage;
					$result->error_message = '';
					break;

				case 'gplus':
					$result->deuid      = isset( $profile['sub'] ) ? $profile['sub'] : '';
					$result->first_name = isset( $profile['given_name'] ) ? $profile['given_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['given_name'] ) ? strtolower( $profile['given_name'] ) : $profile['email'];
					$result->deuimage   = isset( $profile['picture'] ) ? $profile['picture'] : $result->deuimage;
					break;

				case 'amazon':
					$result->deuid      = isset( $profile['user_id'] ) ? $profile['user_id'] : '';
					$result->first_name = isset( $profile['name'] ) ? $profile['name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['name'] ) ? strtolower( $profile['name'] ) : $profile['email'];
					break;

				case 'pinterest':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = ! empty( $profile['business_name'] ) ? $profile['business_name'] : $profile['name'];
					$result->last_name  = '';
					$result->email      = isset( $overrides['email'] ) ? $overrides['email'] : '';
					$result->username   = ! empty( $profile['username'] ) ? strtolower( $profile['username'] ) : $overrides['email'];
					$result->deuimage   = isset( $profile['profile_image'] ) ? $profile['profile_image'] : $result->deuimage;
					break;

				case 'reddit':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = ! empty( $profile['name'] ) ? $profile['name'] : $profile['subreddit']['name'];
					$result->last_name  = '';
					$result->email      = isset( $overrides['email'] ) ? $overrides['email'] : '';
					$result->username   = ! empty( $profile['name'] ) ? strtolower( $profile['name'] ) : $overrides['email'];
					$result->deuimage   = $profile['icon_img'] ?? $result->deuimage;
					$result->deuimage   = isset( $profile['icon_img'] ) ? $profile['icon_img'] : $result->deuimage;
					break;

				case 'spotify':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = isset( $profile['display_name'] ) ? $profile['display_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : $profile['display_name'] . '@spotify.com';
					$result->username   = ! empty( $profile['display_name'] ) ? strtolower( $profile['display_name'] ) : $overrides['email'];
					$result->deuimage   = isset( $profile['images'][0]['url'] ) ? $profile['images'][0]['url'] : $result->deuimage;
					break;

				case 'twitch':
					$result->deuid      = isset( $profile['data'][0]['id'] ) ? $profile['data'][0]['id'] : '';
					$result->first_name = ! empty( $profile['data'][0]['display_name'] ) ? $profile['data'][0]['display_name'] : $profile['data'][0]['login'];
					$result->last_name  = '';
					$result->email      = isset( $profile['data'][0]['email'] ) ? $profile['data'][0]['email'] : $profile['display_name'] . '@spotify.com';
					$result->username   = ! empty( $profile['data'][0]['display_name'] ) ? strtolower( $profile['data'][0]['display_name'] ) : $overrides['email'];
					$result->deuimage   = isset( $profile['data'][0]['profile_image_url'] ) ? $profile['data'][0]['profile_image_url'] : $result->deuimage;
					break;

				case 'discord':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = isset( $profile['global_name'] ) ? $profile['global_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['username'] ) ? strtolower( $profile['username'] ) : $profile['email'];
					// Extract user details.
					$user_id     = $profile['id'];
					$avatar_hash = $profile['avatar'];

					// Determine avatar format.
					$format = ( strpos( $avatar_hash, 'a_' ) === 0 ) ? 'gif' : 'png';

					// Construct the avatar URL.
					$avatar_url       = "https://cdn.discordapp.com/avatars/{$user_id}/{$avatar_hash}.{$format}";
					$result->deuimage = $profile['avatar'] ? $avatar_url : 'https://secure.gravatar.com/avatar/75d23af433e0cea4c0e45a56dba18b30?s=96&d=mm';
					break;

				case 'disqus':
					$result->deuid      = isset( $profile['response']['id'] ) ? $profile['response']['id'] : '';
					$result->first_name = isset( $profile['response']['name'] ) ? $profile['response']['name'] : '';
					$result->last_name  = '';
					$result->email      = ( $profile['response']['username'] ?? '' ) . '@disqus.com';
					$result->username   = ! empty( $profile['response']['username'] ) ? strtolower( $profile['response']['username'] ) : $result->email;
					$result->deuimage   = $profile['response']['avatar']['permalink'] ?? $result->deuimage;
					break;

				case 'apple':
					if ( is_object( $profile ) ) {
						$result->deuid      = isset( $profile->deuid ) ? $profile->deuid : '';
						$result->first_name = isset( $profile->first_name ) ? $profile->first_name : '';
						$result->last_name  = isset( $profile->last_name ) ? $profile->last_name : '';
						$result->email      = isset( $profile->email ) ? $profile->email : '';
						$result->username   = isset( $profile->username ) ? $profile->username : '';
					}
					break;
			}

			return $result;
		}


		/**
		 * Function loginpress_is_eligible_social_domain to check whether email is eligible or not.
		 *
		 * @param  mixed $email Full email address o user taken from social provider.
		 * @param  mixed $eligible_domains List of partial eligible domains.
		 *
		 * @return bool $found If string is found or not.
		 *
		 * @since 3.0.0
		 */
		public function loginpress_is_eligible_social_domain( $email, $eligible_domains ) {
			$found = false;

			foreach ( $eligible_domains as $partial ) {
				if ( strpos( $email, $partial ) !== false ) {
					$found = true;
					break;
				}
			}
			return $found;
		}

		/**
		 * Show loginpress wordpress social login error/s
		 *
		 * @param int    $user The User ID.
		 * @param string $username The Username.
		 * @param string $password The Password.
		 *
		 * @return WP_ERROR $error the Error.
		 * @since 5.0.2.
		 */
		public function loginpress_wp_social_login_error( $user, $username, $password){
			$error = new WP_Error();
			 /**
			 * Filter the error message shown when WordPress.com email is not verified.
			 *
			 * @since 5.0.2
			 *
			 * @param string $message Default error message.
			 * @param string $username Entered username.
			 */
			$message = apply_filters(
				'loginpress_wp_social_login_unverified_email_message',
				sprintf(	/* Translators: The Error Message. */
					__( '%1$sERROR%2$s: Your WordPress.com account email is not verified. Please verify your email at WordPress.com before logging in.', 'loginpress-pro' ),
					'<strong>',
					'</strong>'
				),
				$username
			);

			$error->add( 'loginpress_social_login', $message );

			return $error;
		}
	}
}
$lpsl_login_check = new LoginPress_Social_Login_Check();
