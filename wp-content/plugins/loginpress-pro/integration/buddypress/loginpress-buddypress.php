<?php

/**
 * buddypress Integration
 *
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the integration of LoginPress features with the BuddyPress platform.
 *
 * @since 5.0.0
 */
class LoginPress_Buddypress_Integration{

    /**
     * The settings array
     *
     * @var array
     */
    public $settings;

    /**
     * Variable that Check for LoginPress settings.
     *
     * @var string
     * @since 5.0.0
     */
    public $loginpress_settings;
    public $bp_social_position;

    /**
     * The constructor
     *
     * @since 5.0.0
     */
    public function __construct() {
        $this->settings = get_option( 'loginpress_integration_settings' );
        $this->loginpress_settings = get_option( 'loginpress_captcha_settings' );
        $this->bp_social_position = isset( $this->settings['social_position_bp'] ) ? $this->settings['social_position_bp'] : 'default';
        $this->loginpress_bp_hooks();
    }

    /**
     * Register Buddypress-related hooks for LoginPress.
     *
     * This function binds LoginPress functionality with Buddypress by hooking into
     * relevant actions and filters provided by the Buddypress plugin.
     *
     * @since 5.0.0
     */
    public function loginpress_bp_hooks() {
        
        $bp_social_register = isset( $this->settings['enable_social_login_links_bp'] ) ? $this->settings['enable_social_login_links_bp'] : '';
        
        $bp_captcha_register     = isset( $this->settings['enable_captcha_bp']['register_bp_block'] ) ? $this->settings['enable_captcha_bp']['register_bp_block'] : false;
        $captchas_enabled = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';
        $addons = get_option( 'loginpress_pro_addons' );
        if ( isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'] ) {
            if ( ! class_exists( 'LoginPress_Social' ) ) {
                require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
            }

            if ( 'off' !== $bp_social_register ) {
                if ( 'above' === $this->bp_social_position || 'above_separator' === $this->bp_social_position ) {
                    add_action( 'bp_before_register_page', array( $this, 'loginpress_social_output_above' ) );
                } elseif ( 'default' === $this->bp_social_position || 'below' === $this->bp_social_position ) {
                    add_action( 'bp_after_register_page', array( $this, 'loginpress_social_output_below' ) );
                }
            }
            
        }

        if ( $captchas_enabled !== 'off' ) {
            $captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
            if ( $captchas_type === 'type_cloudflare' ) {

                /* Cloudflare CAPTCHA Settings */
                $cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
                $cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
                $validated = isset( $this->loginpress_settings['validate_cf'] ) && $this->loginpress_settings['validate_cf'] == 'on' ? true : false;
                if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated) {
                    if ( $bp_captcha_register ) {
                        add_filter( 'bp_before_registration_submit_buttons', array($this,'loginpress_add_turnstile_to_bp_register_fields' ),9999 );
                        add_filter( 'bp_signup_validate', array($this, 'loginpress_bp_register_form_turnstile_enable'), 10, 3 );
                    }
                }
            } else if ( $captchas_type === 'type_recaptcha' ){
                /* Add reCAPTCHA on registration form */
                if ( $bp_captcha_register ) {
                    add_filter( 'bp_before_registration_submit_buttons', array($this,'loginpress_add_recaptcha_to_bp_register' ),9999 );
                }

                /* Authentication reCAPTCHA on bp registration form */
                if ( ! isset( $_GET['customize_changeset_uuid'] ) && ( $bp_captcha_register)) {
                    add_filter( 'bp_signup_validate', array($this, 'loginpress_bp_register_form_captcha_enable'),20 );
                }
            } else if ( $captchas_type === 'type_hcaptcha' ){
                $hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
                $hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

                if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
                    if ( $bp_captcha_register ) {
                        add_filter( 'bp_before_registration_submit_buttons', array($this,'loginpress_add_hcaptcha_to_bp_register_fields' ),99 );
                        add_filter( 'bp_signup_validate', array($this, 'loginpress_bp_register_form_hcaptcha_enable'), 10, 3 );
                    }
                }
            }
        }
    }

    /**
     * Adds social login above the buddypress register fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_output_above() {
        $loginpress_social = LoginPress_Social::instance();
    
        $loginpress_social->loginpress_social_login();
    
        if ( 'above_separator' === $this->bp_social_position ) {
            /**
             * Filter the separator text between social login buttons and the default form.
             *
             * @since 3.0.0
             *
             * @param string $separator_text The text displayed between social login and form. Default 'or'.
             */
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
    }
    
    /**
     * Adds social login below the buddypress register fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_output_below() {
        
        $loginpress_social = LoginPress_Social::instance();
    
        if ( 'default' === $this->bp_social_position ) {
            /**
             * Filter the separator text between social login buttons and the default form.
             *
             * @since 3.0.0
             *
             * @param string $separator_text The text displayed between social login and form. Default 'or'.
             */
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
        $loginpress_social->loginpress_social_login();
    }

      /**
     * Adds turnstile field to buddypress register fields.
     *
     * @param array $content bp register fields.
     * @return array bp register fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_turnstile_to_bp_register_fields($content = '' ) {

        /* Cloudflare CAPTCHA Settings */
        $lp_turnstile = LoginPress_Turnstile::instance();
        $lp_turnstile->loginpress_turnstile_field('bp'); // Use correct integration key
        $lp_turnstile->loginpress_turnstile_script();
        return ;
    }

    /**
     * Authenticate turnstile response on buddyboss register form.
     *
     * @param array $result current validation status of the form.
     * @since 5.0.0
     */
    public function loginpress_bp_register_form_turnstile_enable($result) {
        global $bp;
        $secret_key = isset($this->loginpress_settings['secret_key_cf']) ? $this->loginpress_settings['secret_key_cf'] : '';
        $has_error = false;
    
        if (!isset($_POST['cf-turnstile-response']) || empty($_POST['cf-turnstile-response'])) {
            bp_core_add_message(__('Please complete the Turnstile verification.', 'loginpress-pro'), 'error');
            $has_error = true;
        } else {
            $verify_response = wp_remote_post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                array(
                    'body' => array(
                        'secret' => $secret_key,
                        'response' => $_POST['cf-turnstile-response'],
                        'remoteip' => $_SERVER['REMOTE_ADDR'],
                    ),
                )
            );
    
            $response_body = wp_remote_retrieve_body($verify_response);
            $result_data = json_decode($response_body, true);
    
            if (!isset($result_data['success']) || empty($result_data['success'])) {
                $lp_turnstile = LoginPress_Turnstile::instance();
                $error_msg = $lp_turnstile->loginpress_turnstile_error();
                bp_core_add_message(__($error_msg, 'loginpress-pro'), 'error');// @codingStandardsIgnoreLine.
                $has_error = true;
            }
        }
    
        if ($has_error) {
            $bp->signup->errors['turnstile_blocked'] = 'There was a problem with Turnstile verification.';
        }
    
        return $result;
    }

    /**
     * Adds recaptcha field to buddypress register fields.
     *
     * @param array $content bp register fields.
     * @return array bp register fields with recaptcha added.
     * @since 5.0.0
     */
    public function loginpress_add_recaptcha_to_bp_register() {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        $lp_recaptcha->loginpress_recaptcha_field();
        $lp_recaptcha->loginpress_recaptcha_script();
        return;
    }

    /**
	 * Enables reCAPTCHA on the bp register form.
	 *
	 * @param mixed $result The current validation status of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_bp_register_form_captcha_enable($result) {
        $lp_recaptcha   = LoginPress_Recaptcha::instance();
        global $bp;
        $cap_type       = isset($this->loginpress_settings['recaptcha_type']) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
        $cap_permission = isset($this->loginpress_settings['enable_repatcha']) ? $this->loginpress_settings['enable_repatcha'] : 'off';
        $errors = new WP_Error();
        // Fallback error handler
        $has_error = false;
    
        if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
            bp_core_add_message(__('Please complete the reCAPTCHA verification.', 'loginpress-pro'), 'error');
            $has_error = true;
        } elseif ('v3' === $cap_type) {
            $good_score = $this->loginpress_settings['good_score'];
            $score = $lp_recaptcha->loginpress_v3_recaptcha_verifier();
    
            if ($score < $good_score) {
                bp_core_add_message(__('<strong>Error:</strong> reCAPTCHA score too low.', 'loginpress-pro'), 'error');
                $has_error = true;
            }
        } else {
            $response = $lp_recaptcha->loginpress_recaptcha_verifier();
            if (!$response->isSuccess()) {
                $error_msg = $lp_recaptcha->loginpress_recaptcha_error();
                bp_core_add_message(__($error_msg, 'loginpress-pro'), 'error'); // @codingStandardsIgnoreLine.
                $has_error = true;
            }
        }
    
        if ($has_error) {
            // Add fake field error to make BuddyPress re-display the form
            error_log("asdfs");
            $bp->signup->errors['recaptcha_blocked'] = 'There was a problem with reCAPTCHA verification.';
        }
    
        // return $errors;
    }
    

    /**
     * Adds hcaptcha field to buddypress register fields.
     *
     * @param array $fields bp register fields.
     * @return array bp register fields with hcaptcha added.
     * @since 5.0.0
     */
    public function loginpress_add_hcaptcha_to_bp_register_fields( $fields ) {

        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        $lp_hcaptcha->loginpress_hcaptcha_field();
        $lp_hcaptcha->loginpress_hcaptcha_enqueue('bp');

        return;
    }

    /**
	 * Enables hcaptcha on the bp register form.
	 *
	 * @param mixed $result The current validation status of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_bp_register_form_hcaptcha_enable($result) {
        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        global $bp;
        $hcap_secret_key = isset($this->loginpress_settings['hcaptcha_secret_key']) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
        $has_error = false;
    
        if (!isset($_POST['h-captcha-response']) || empty($_POST['h-captcha-response'])) {
            bp_core_add_message(__('Please complete the hCaptcha verification.', 'loginpress-pro'), 'error');
            $has_error = true;
        } else {
            $response = $this->lp_verify_hcaptcha($hcap_secret_key, $_POST['h-captcha-response']);
            $response_body = wp_remote_retrieve_body($response);
            $result_data = json_decode($response_body);
            
            if (!$result_data->success) {
                $error_msg = $lp_hcaptcha->loginpress_hcaptcha_error();
                bp_core_add_message(__($error_msg, 'loginpress-pro'), 'error'); // @codingStandardsIgnoreLine.
                $has_error = true;
            }
        }
    
        if ($has_error) {
            $bp->signup->errors['hcaptcha_blocked'] = 'There was a problem with hCaptcha verification.';
        }
    
        return $result;
    }

    /**
	 * Verify hcaptcha response.
	 *
	 * @param string $hcap_secret_key The hcaptcha secret key.
     * @param string $hcap_response The hcaptcha response.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    private function lp_verify_hcaptcha( $hcap_secret_key, $hcap_response ) {
        return wp_remote_post(
            'https://hcaptcha.com/siteverify',
            array(
                'timeout' => 5,
                'body'    => array(
                    'secret'   => $hcap_secret_key,
                    'response' => sanitize_text_field( $hcap_response ),
                    'remoteip' => $_SERVER['REMOTE_ADDR'],
                ),
            )
        );
    }
}

new LoginPress_Buddypress_Integration();
