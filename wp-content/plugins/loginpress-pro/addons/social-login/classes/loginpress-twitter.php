<?php
/**
 * Twitter Login
 *
 * @package LoginPress Social Login
 */

use Abraham\TwitterOAuth\TwitterOAuth;
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'LoginPress_Twitter' ) ) {

	/**
	 * LoginPress_Twitter
	 */
	class LoginPress_Twitter {
		/**
		 * Twitter Login oauth 2.0.
		 *
		 * @return array $response The Twitter login response.
		 */
		 public function twitter_login_oauth2() {
			require LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/twitter/autoload.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
		
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$request = $_REQUEST;
			$response = new stdClass();
		
			$login_settings = get_option('loginpress_social_logins');
			$client_id = $login_settings['twitter_oauth_token'];  // Twitter API v2 Client ID
			$client_secret = $login_settings['twitter_token_secret'];  
			$callback_url = $login_settings['twitter_callback_url']; 
			// Handle Twitter Redirect with Authorization Code
			if (isset($request['code']) && isset($request['state'])) {
				$code = $request['code'];
				$returned_state = $request['state'];
		
				error_log('$code: ' . print_r($code, true));
		
				// Validate State Parameter (CSRF Protection)
				$stored_state = get_transient('loginpress_twitter_auth_state');
				if (!$stored_state || $stored_state !== $returned_state) {
					$response->status = 'ERROR';
					$response->error_message =  __('Invalid state parameter. Authentication request may have been tampered with.', 'loginpress-pro');
					return $response;
				}
		
				// Retrieve Code Verifier for PKCE
				$code_verifier = get_transient('loginpress_twitter_code_verifier');
				if (!$code_verifier) {
					$response->status = 'ERROR';
					$response->error_message = __('Session expired. Please try logging in again.', 'loginpress-pro');
					return $response;
				}
		
				// Exchange Code for Access Token (FIXED)
				$token_url = "https://api.twitter.com/2/oauth2/token";
				$auth_header = base64_encode($client_id . ':' . $client_secret);
		
				$body = [
					'code'          => $code,
					'grant_type'    => 'authorization_code',
					'client_id '    => $client_id,
					'redirect_uri'  => $callback_url,
					'code_verifier' => $code_verifier,
				];
		
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $token_url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, [
					'Authorization: Basic ' . $auth_header,
					'Content-Type: application/x-www-form-urlencoded'
				]);
				$token_response = json_decode(curl_exec($ch), true);
				curl_close($ch);
				error_log('token_response ' . print_r($token_response, true));
		
				// Validate Access Token Response
				if (isset($token_response['access_token'])) {
					$access_token = $token_response['access_token'];
					error_log('Access Token: ' . $access_token);
		
					// Fetch User Info
					$user_url = "https://api.twitter.com/2/users/me?user.fields=id,name,profile_image_url,username";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $user_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, [
						"Authorization: Bearer $access_token"
					]);
					$user_response = json_decode(curl_exec($ch), true);
					curl_close($ch);
					error_log('user_response ' . print_r($user_response, true));
					if (isset($user_response['data'])) {
						
						$user_profile = $user_response['data'];
		
						$email = $user_profile['email'] ?? $user_profile['username'] . '@twitter.com';
						$username = strtolower($user_profile['username']);
						$wp_user = get_user_by('email', $email);
		
						$response->status = 'SUCCESS';
						$response->deuid = $user_profile['id'];
						$response->deutype = 'twitter';
						$response->username = $username;
						$response->email = $email;
						$response->first_name = explode(' ', $user_profile['name'], 2)[0];
						$response->last_name = explode(' ', $user_profile['name'], 2)[1] ?? '';
						$response->deuimage = $user_profile['profile_image_url'];
						$response->location = $user_profile['location'] ?? '';
					} else {
						$response->status = 'ERROR';
						$response->error_message = __('Failed to fetch user profile.', 'loginpress-pro');
					}
				} else {
					$response->status = 'ERROR';
					$response->error_message = __('Failed to retrieve access token.', 'loginpress-pro');
				}
			} else {
				// Generate Twitter Login URL
				$authorize_url = "https://twitter.com/i/oauth2/authorize";
				$code_verifier = bin2hex(random_bytes(32));  // PKCE Code Verifier
				set_transient('loginpress_twitter_code_verifier', $code_verifier, 300); // Store for 5 minutes
				$code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
		
				// Generate State Parameter for Security
				$state = bin2hex(random_bytes(16));
				set_transient('loginpress_twitter_auth_state', $state, 300); 
		
				$query_params = [
					'response_type'   => 'code',
					'client_id'       => $client_id,
					'redirect_uri'    => $callback_url,
					'scope'           => 'tweet.read users.read offline.access',
					'state'           => $state,  // Include CSRF protection
					'code_challenge'  => $code_challenge,
					'code_challenge_method' => 'S256',
				];
				error_log('query ' . print_r($query_params, true));
				$auth_url = $authorize_url . '?' . http_build_query($query_params);
				error_log('auth_url ' . print_r($auth_url, true));
		
				$loginpress_utilities->redirect($auth_url);
			}
		
			return $response;
		}	
		
		/**
		 * Twitter Login oauth 1.1.
		 *
		 * @return array $response The Twitter login response.
		 */
		public function twitter_login() {

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			require LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/twitter/autoload.php';
			$loginpress_utilities = new LoginPress_Social_Utilities();

			$request      = $_REQUEST; // @codingStandardsIgnoreLine.
			$site         = $loginpress_utilities->loginpress_site_url();
			$callback_url = $loginpress_utilities->loginpress_callback_url();
			$response     = new stdClass();

			$lp_twitter_oauth = get_option( 'loginpress_twitter_oauth' );
			$login_settings   = get_option( 'loginpress_social_logins' );

			if ( isset( $_REQUEST['oauth_verifier'], $_REQUEST['oauth_token'] ) && $_REQUEST['oauth_token'] == $lp_twitter_oauth['oauth_token'] ) { // @codingStandardsIgnoreLine.
				$request_token                       = array();
				$request_token['oauth_token']        = $lp_twitter_oauth['oauth_token'];
				$request_token['oauth_token_secret'] = $lp_twitter_oauth['oauth_token_secret'];

				$connection   = new TwitterOAuth( $login_settings['twitter_oauth_token'], $login_settings['twitter_token_secret'], $request_token['oauth_token'], $request_token['oauth_token_secret'] );
				$access_token = $connection->oauth( 'oauth/access_token', array( 'oauth_verifier' => $_REQUEST['oauth_verifier'] ) ); // @codingStandardsIgnoreLine.

				update_option( 'loginpress_twitter_access', $access_token );
			}

			if ( ! isset( $request['oauth_token'] ) && ! isset( $request['oauth_verifier'] ) ) {
				// Get identity from user and redirect browser to OpenID Server.
				if ( ! isset( $request['oauth_token'] ) || '' === $request['oauth_token'] ) {
					$twitter_obj = new TwitterOAuth( $login_settings['twitter_oauth_token'], $login_settings['twitter_token_secret'] );

					try {
						$request_token = $twitter_obj->oauth( 'oauth/request_token', array( 'oauth_verifier' => $login_settings['twitter_callback_url'] ) );
					} catch ( Exception $e ) {
						echo esc_html( $e );
					}

					$_SESSION['oauth_token']        = $request_token['oauth_token'];
					$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

					$session_array = array(
						'oauth_token'        => $_SESSION['oauth_token'],
						'oauth_token_secret' => $_SESSION['oauth_token_secret'],
					);
					update_option( 'loginpress_twitter_oauth', $session_array );

					$url = $twitter_obj->url( 'oauth/authorize', array( 'oauth_token' => $request_token['oauth_token'] ) );
					/* If last connection failed don't display authorization link. */
					if ( $url ) :
						try {
							$loginpress_utilities->redirect( $url );
						} catch ( Exception $e ) {
							$response->status        = 'ERROR';
							$response->error_code    = 2;
							$response->error_message = 'Could not get AuthorizeUrl.';
						}
					endif;
				} else {
					$response->status        = 'ERROR';
					$response->error_code    = 2;
					$response->error_message = 'INVALID AUTHORIZATION';
				}
			} elseif ( isset( $request['oauth_token'] ) && isset( $request['oauth_verifier'] ) ) {
				/* Create TwitterAuth object with app key/secret and token key/secret from default phase */
				$access_token = get_option( 'loginpress_twitter_access' );
				// $twitter_obj = new TwitterOAuth( 'uz8VOy2P7xNNexJRqvnhdtYl1', 'edFTzF16znmVuEnvqnxKp2jAnk42p0vp5OSCYDYuAdXSiNOXIX', $access_token['oauth_token'], $access_token['oauth_token_secret'] );
				$twitter_obj = new TwitterOAuth( $login_settings['twitter_oauth_token'], $login_settings['twitter_token_secret'], $access_token['oauth_token'], $access_token['oauth_token_secret'] );
				/* Remove no longer needed request tokens */
				$params = array(
					'include_email'    => 'true',
					'include_entities' => 'true',
					'skip_status'      => 'true',
				);

				$user_profile = $twitter_obj->get( 'account/verify_credentials', $params );

				/* Request access twitterObj from twitter */
				$response->status        = 'SUCCESS';
				$response->deuid         = $user_profile->id;
				$response->deutype       = 'twitter';
				$response->name          = explode( ' ', $user_profile->name, 2 );
				$response->first_name    = $response->name[0];
				$response->last_name     = ( isset( $response->name[1] ) ) ? $response->name[1] : '';
				$response->deuimage      = $user_profile->profile_image_url_https;
				$response->email         = isset( $user_profile->email ) ? $user_profile->email : $user_profile->screen_name . '@twitter.com';
				$response->username      = ( '' !== $user_profile->screen_name ) ? strtolower( $user_profile->screen_name ) : $user_email;
				$response->url           = $user_profile->url;
				$response->about         = isset( $user_profile->description ) ? $user_profile->description : '';
				$response->gender        = isset( $user_profile->gender ) ? $user_profile->gender : 'N/A';
				$response->location      = $user_profile->location;
				$response->error_message = '';
			} else { // User Canceled your Request.
				$response->status        = 'ERROR';
				$response->error_code    = 1;
				$response->error_message = 'USER CANCELED REQUEST';
			}
			return $response;
		}
	}
}
