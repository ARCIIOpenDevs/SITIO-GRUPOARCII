<?php

/**
 * LifterLMS Integration
 *
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( is_plugin_inactive( 'lifterlms/lifterlms.php' ) && ! is_plugin_active_for_network( 'lifterlms/lifterlms.php' ) ) {
    exit;
}

include_once LOGINPRESS_PRO_ROOT_PATH . '/addons/limit-login-attempts/classes/class-attempts.php';
include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-recaptcha.php';

/**
 * Handles the integration of LoginPress features with the LifterLMS platform.
 *
 * @since 5.0.0
 */
class LoginPress_LifterLMS_Integration extends LoginPress_Attempts {

    /**
     * The settings array
     *
     * @var array
     */
    public $settings;

    /**
     * The settings array
     *
     * @var array
     */
    public $attempts_settings;

    /**
     * The table name
     *
     * @var string
     */
    public $llla_table;

    /**
     * Variable that Check for LoginPress settings.
     *
     * @var string
     * @since 5.0.0
     */
    public $loginpress_settings;
    /**
     * Variable that contains position of social login on lifterlms login form.
     *
     * @var string
     * @since 5.0.0
     */
    public $social_position_llms_lf;
    /**
     * Variable that contains position of social login on lifterlms register form.
     *
     * @var string
     * @since 5.0.0
     */
    public $social_position_llms_rf;
    /**
     * Variable that contains position of social login on lifterlms checkout form.
     *
     * @var string
     * @since 5.0.0
     */
    public $social_position_llms_co;

    /**
     * The constructor
     *
     * @since 5.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->llla_table = $wpdb->prefix . 'loginpress_limit_login_details';
        $this->attempts_settings = get_option( 'loginpress_limit_login_attempts' );
        $this->settings = get_option( 'loginpress_integration_settings' );
        $this->loginpress_settings = get_option( 'loginpress_captcha_settings' );
        $this->social_position_llms_lf   = isset( $this->settings['social_position_llms_lf'] ) ? $this->settings['social_position_llms_lf'] : 'default';
        $this->social_position_llms_rf   = isset( $this->settings['social_position_llms_rf'] ) ? $this->settings['social_position_llms_rf'] : 'default';
        $this->social_position_llms_co   = isset( $this->settings['social_position_llms_co'] ) ? $this->settings['social_position_llms_co'] : 'default';
        $this->loginpress_llms_hooks();
    }

    /**
     * Register LifterLMS-related hooks for LoginPress.
     *
     * This function binds LoginPress functionality with LifterLMS by hooking into
     * relevant actions and filters provided by the LifterLMS plugin.
     * Useful for customizing or enhancing the LifterLMS login and registration flows.
     *
     * @since 5.0.0
     */
    public function loginpress_llms_hooks() {
        
        $addons = get_option( 'loginpress_pro_addons' );
        if ( isset( $addons['limit-login-attempts']['is_active'] ) && $addons['limit-login-attempts']['is_active'] ) {
            add_filter( 'loginpress_llla_error_filter', array( $this, 'apply_filter_llms_login_errors_callback' ) );
            add_action( 'wp_loaded', array( $this, 'llla_llms_wp_loaded' ) );
        }
        $enable_lifterlms   = isset( $this->settings['enable_social_login_links_lifterlms'] ) ? $this->settings['enable_social_login_links_lifterlms'] : '';
        $lifterlms_purchase_reg = isset( $enable_lifterlms['lifterlms_purchase_reg'] ) ? $enable_lifterlms['lifterlms_purchase_reg'] : '';
        $login_lifterlms_block = isset( $enable_lifterlms['login_lifterlms_block'] ) ? $enable_lifterlms['login_lifterlms_block'] : '';
        $register_lifterlms_block = isset( $enable_lifterlms['register_lifterlms_block'] ) ? $enable_lifterlms['register_lifterlms_block'] : '';
       
        $enable_social_llms_lf   = isset( $this->settings['enable_social_llms_lf'] ) ? $this->settings['enable_social_llms_lf'] : '';
        $enable_social_llms_rf   = isset( $this->settings['enable_social_llms_rf'] ) ? $this->settings['enable_social_llms_rf'] : '';
        $enable_social_llms_co   = isset( $this->settings['enable_social_llms_co'] ) ? $this->settings['enable_social_llms_co'] : '';
        if ( isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'] ) {
            if ( ! class_exists( 'LoginPress_Social' ) ) {
                require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
            }
            
            // Checkout Form Social
            if ( 'off' !== $enable_social_llms_co ) {
                if ( 'above' === $this->social_position_llms_co || 'above_separator' === $this->social_position_llms_co ) {
                    add_action( 'llms_checkout_footer_before', [ $this, 'loginpress_social_checkout_above' ] );
                } elseif ( 'default' === $this->social_position_llms_co || 'below' === $this->social_position_llms_co ) {
                    add_action( 'llms_checkout_footer_after', [ $this, 'loginpress_social_checkout_below' ] );
                }
            }

            // Login Form Social
            if ( 'off' !== $enable_social_llms_lf ) {
                if ( 'above' === $this->social_position_llms_lf || 'above_separator' === $this->social_position_llms_lf ) {
                    add_action( 'lifterlms_login_form_start', [ $this, 'loginpress_social_login_above' ] );
                } elseif ( 'default' === $this->social_position_llms_lf || 'below' === $this->social_position_llms_lf ) {
                    add_action( 'lifterlms_login_form_end', [ $this, 'loginpress_social_login_below' ] );
                }
            }

            // Register Form Social
            if ( 'off' !== $enable_social_llms_rf ) {
                if ( 'above' === $this->social_position_llms_rf || 'above_separator' === $this->social_position_llms_rf ) {
                    add_action( 'lifterlms_register_form_start', [ $this, 'loginpress_social_register_above' ] );
                } elseif ( 'default' === $this->social_position_llms_rf || 'below' === $this->social_position_llms_rf ) {
                    add_action( 'lifterlms_register_form_end', [ $this, 'loginpress_social_register_below' ] );
                }
            }

        }
        $lifter_login        = isset( $this->settings['enable_captcha_llms']['lifter_login_form'] ) ? $this->settings['enable_captcha_llms']['lifter_login_form'] : false;
        $lifter_register     = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
        $lifter_purchase     = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
        $lifter_lostpass     = isset( $this->settings['enable_captcha_llms']['lifter_lostpassword_form'] ) ? $this->settings['enable_captcha_llms']['lifter_lostpassword_form'] : false;
        $captchas_enabled = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';

        if ( $captchas_enabled !== 'off' ) {
            $captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
            if ( $captchas_type === 'type_cloudflare' ) {

                /* Cloudflare CAPTCHA Settings */
                $cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
                $cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
                $validated = isset( $this->loginpress_settings['validate_cf'] ) && $this->loginpress_settings['validate_cf'] == 'on' ? true : false;
                if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated) {
                    if ( $lifter_login ) {
                        add_filter( 'lifterlms_person_login_fields', array($this,'loginpress_add_turnstile_to_lifter_login_fields' ),99 );
                        add_action( 'lifterlms_login_credentials',  array($this, 'loginpress_llms_login_form_turnstile_enable') );
                    }
                    if ( $lifter_register ) {
                        add_filter( 'lifterlms_before_registration_button', array($this,'loginpress_add_turnstile_to_lifter_register_fields' ),99 );
                        add_filter( 'lifterlms_user_registration_data', array($this, 'loginpress_llms_register_form_turnstile_enable'), 10, 3 );
                    }
                    if ( $lifter_purchase ) {
                        add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_add_turnstile_to_lifter_register_fields' ) );
                        add_filter( 'lifterlms_user_registration_data', array($this, 'loginpress_llms_register_form_turnstile_enable'), 10, 3 );
                    }
                    if ( $lifter_lostpass ) {
                        add_filter( 'lifterlms_lost_password_fields', array($this,'loginpress_add_turnstile_to_lifter_lostpass_fields' ) );
                        add_action( 'lostpassword_post', array($this,'loginpress_auth_turnstile_lostpassword_llms'), 10, 2 );
                    }
                }
            } else if ( $captchas_type === 'type_recaptcha' ){

                /* Add reCAPTCHA on LifterLMS login form */
                if ( $lifter_login ) {
                    add_filter( 'lifterlms_person_login_fields', array($this,'loginpress_add_recaptcha_to_lifter_login_fields' ),99 );
                    add_action( 'llms_before_person_login_form', array( $this, 'loginpress_llms_form_script_callback' ) );
                }

                /* Add reCAPTCHA on registration form */
                if ( $lifter_register ) {
                    add_action( 'lifterlms_before_registration_button', array( $this, 'loginpress_add_recaptcha_to_lifter_register' ) );
                    add_action( 'lifterlms_before_person_register_form', array( $this, 'loginpress_llms_form_script_callback' ) );
                }

                /* Add reCAPTCHA on purchase form */
                if ( $lifter_purchase ) {
                    add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_add_recaptcha_to_lifter_register' ) );
                    add_action( 'lifterlms_pre_checkout_form', array( $this, 'loginpress_llms_form_script_callback' ) );
                }

                /* Add reCAPTCHA on LifterLMS lost password form */
                if ( $lifter_lostpass ) {
                    add_filter( 'lifterlms_lost_password_fields', array($this,'loginpress_add_recaptcha_to_lifter_lostpass_fields' ) );
                    add_action( 'lifterlms_lost_do_action', array( $this, 'loginpress_llms_form_script_callback' ) );
                }

                /* Authentication reCAPTCHA on LifterLMS login form */
                if ( ! isset( $_GET['customize_changeset_uuid'] ) && $lifter_login ) {
                    add_action( 'lifterlms_login_credentials',  array($this, 'loginpress_llms_login_form_captcha_enable') );
                }

                /* Authentication reCAPTCHA on LifterLMS purchase and registration form */
                if ( ! isset( $_GET['customize_changeset_uuid'] ) && ( $lifter_purchase || $lifter_register)) {
                    add_filter( 'lifterlms_user_registration_data', array($this, 'loginpress_llms_register_form_captcha_enable'), 10, 3 );
                }

                /* Authentication reCAPTCHA on LifterLMS lost-password form */
                if ( ! isset( $_GET['customize_changeset_uuid'] ) && $lifter_lostpass ) {
                    add_action( 'lostpassword_post', array($this,'loginpress_auth_recaptcha_lostpassword_llms'), 10, 2 );
                }
            } else if ( $captchas_type === 'type_hcaptcha' ){
                $hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
                $hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

                if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
                    if ( $lifter_login ) {
                        add_filter( 'lifterlms_person_login_fields', array($this,'loginpress_add_hcaptcha_to_lifter_login_fields' ),99 );
                        add_action( 'lifterlms_login_credentials',  array($this, 'loginpress_llms_login_form_hcaptcha_enable') );
                    }
                    if ( $lifter_register ) {
                        add_filter( 'lifterlms_before_registration_button', array($this,'loginpress_add_hcaptcha_to_lifter_register_fields' ),99 );
                        add_filter( 'lifterlms_user_registration_data', array($this, 'loginpress_llms_register_form_hcaptcha_enable'), 10, 3 );
                    }
                    if ( $lifter_purchase ) {
                        add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_add_hcaptcha_to_lifter_register_fields' ) );
                        add_filter( 'lifterlms_user_registration_data', array($this, 'loginpress_llms_register_form_hcaptcha_enable'), 10, 3 );
                    }
                    if ( $lifter_lostpass ) {
                        add_filter( 'lifterlms_lost_password_fields', array($this,'loginpress_add_hcaptcha_to_lifter_lostpass_fields' ) );
                        add_action( 'lostpassword_post', array($this,'loginpress_auth_hcaptcha_lostpassword_llms'), 10, 2 );
                    }
                }
            }
        }
    }

    /**
     * Adds social login above the lifterlms checkout fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_checkout_above() {
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    
        if ( 'above_separator' === $this->social_position_llms_co ) {
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
    }
    
    /**
     * Adds social login below the lifterlms checkout fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_checkout_below() {
        if ( 'default' === $this->social_position_llms_co ) {
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    }
    
    /**
     * Adds social login above the lifterlms login fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_login_above() {
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    
        if ( 'above_separator' === $this->social_position_llms_lf ) {
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
    }
    
    /**
     * Adds social login below the lifterlms login fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_login_below() {
        if ( 'default' === $this->social_position_llms_lf ) {
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    }
    
    /**
     * Adds social login above the lifterlms register fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_register_above() {
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    
        if ( 'above_separator' === $this->social_position_llms_rf ) {
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
    }
    
    /**
     * Adds social login below the lifterlms register fields.
     *
     * @since 5.0.0
     */
    public function loginpress_social_register_below() {
        if ( 'default' === $this->social_position_llms_rf ) {
            $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
            echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
        }
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    }
    

    public function apply_filter_llms_login_errors_callback() {
        add_filter('lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_callback' ) );
    }

    /**
     * Attempts Login Authentication.
     *
     * @param object $user Object of the user.
     * @param string $username username.
     * @param string $password password.
     * @since 5.0.0
     */
    public function llms_login_attempts_auth_callback( $user, $username, $password ) {

        if ( isset( $_POST['g-recaptcha-response'] ) && empty( $_POST['g-recaptcha-response'] ) ) {
            return;
        }

        if ( $user instanceof WP_User ) {
            return $user;
        }

        if ( ! empty( $username ) && ! empty( $password ) ) {

            $error = new WP_Error();
            global $pagenow, $wpdb;

            $ip             = $this->get_address();
            $whitelisted_ip = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->llla_table} WHERE ip = %s AND whitelist = %d", $ip, 1 ) );

            if ( $whitelisted_ip >= 1 ) {
                return;
            } elseif ( 'index.php' === $pagenow  && class_exists( 'LifterLMS' ) ) {
                
            } else {
                $error->add( 'llla_error', $this->limit_query( $username, $password ) );
            }

            return $error;
        }
    }

    /**
     * Filter callback to add our login error messages to the LLMS login form.
     *
     * @param WP_Error $err Error object containing login errors.
     * @return WP_Error
     * @since 5.0.0
     */
    public function llla_login_lifterlms_callback(  $err ) {
        if ( isset( $_POST['llms_login'] ) && isset( $_POST['llms_password'] ) ) {
            $username = $_POST['llms_login'];
            $password = $_POST['llms_password'];
        }
        $err -> remove( 'login-error' );
        $err -> add( 'login-error', __( wp_kses_post(  $this->limit_query( $username, $password ) ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
        return $err;
    }

    /**
     * LLMS Login Form Attempt Checker.
     *
     * @since 5.0.0
     */
    public function llla_llms_wp_loaded() {

        global $pagenow, $wpdb;
        $ip = $this->get_address();
        $blacklist_check = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `blacklist` = 1", $ip ) ); // @codingStandardsIgnoreLine.
        $current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
        $attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
        $minutes_lockout  = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
        $lockout_time = $current_time - ( $minutes_lockout * 60 );
        $attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s AND `whitelist` = 0", $ip, $lockout_time ) );

        // limit LifterLMS Account access if attempts exceed.
        if ( 'index.php' === $pagenow  && class_exists( 'LifterLMS' ) && $attempt_time === $attempts_allowed ) {
            add_action('llms_before_person_login_form', array($this, 'lifterlms_attempt_error'));
            add_action('lifterlms_login_form_end', array($this, 'lifterlms_attempt_error_return'));

        }

        // limit LifterLMS Account access if blacklisted.
        if ( 'index.php' === $pagenow  && class_exists( 'LifterLMS' ) && get_option( 'permalink_structure' ) && $blacklist_check >= 1 ) {
            add_action('llms_before_person_login_form', array($this, 'lifterlms_blacklist_callback'));
        }

        // retrieving the gateway to saved in db.
        if ( isset( $_POST['_llms_login_user_nonce'] ) ) {
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_llms_login_user_nonce'] ) ), 'llms_login_user' );
            add_filter( 'loginpress_gateway_passed', array( $this, 'loginpress_gaeteway_passed_filter' ) );
        }

    }

    /**
     * @since 5.0.0
     * 
     * Handles LifterLMS attempt error.
     *
     * Retrieves the last attempt time for the current IP address and displays an error message.
     *
     * @return void
     */
    public function lifterlms_attempt_error() {
        global $wpdb;
        $ip = $this->get_address();

        $last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $ip ) ); // @codingStandardsIgnoreLine.
        if ( $last_attempt_time ) {
            $last_attempt_time = $last_attempt_time->datentime;
        }
        echo '<div class="llms-notice">';
        echo wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) );
        echo '</div>';
    }

    /**
     * attempts error callback.
     *
     * @since 5.0.0
     */
    public function lifterlms_attempt_error_return() {
        ?>
        <style>
        .llms-person-login-form-wrapper, .llms-error {
            display: none;
        }
        </style>
        
        <?php

    }

    /**
     * blacklist error callback.
     *
     * @since 5.0.0
     */
    public function lifterlms_blacklist_callback() {
        wp_die( __( 'You are blacklisted to access the Login Panel', 'loginpress-pro' ), 403 ); // @codingStandardsIgnoreLine.
    }

    /**
     * @since 5.0.0
     * 
     * Return LifterLMS as the gateway name in the loginpress db.
     *
     * @param string $gateway The current gateway name.
     *
     * @return string The modified gateway name.
     */
    public function loginpress_gaeteway_passed_filter( $gateway ) {
        $gateway = esc_html__( 'lifterlms', 'loginpress-pro' );
        return $gateway;
    }

    /**
     * Adds reCAPTCHA field to Lifter login fields.
     *
     * @param array $fields Lifter login fields.
     * @return array Lifter login fields with reCAPTCHA added.
     * @since 5.0.0
     */
    public function loginpress_add_recaptcha_to_lifter_login_fields( $fields ) {
        $cap_type = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
        if ($cap_type === 'v2-robot'){
            ob_start();        
            $lp_recaptcha = LoginPress_Recaptcha::instance();
            $lp_recaptcha->loginpress_recaptcha_field();
            
            $recaptcha_field = ob_get_clean();
        } else {
            $lp_recaptcha = LoginPress_Recaptcha::instance();
            // $lp_recaptcha->loginpress_recaptcha_field();
            
            $recaptcha_field = $lp_recaptcha->loginpress_recaptcha_field();
        }
        //ob_start();        
        
        
        $recaptcha_field = array(
            'id'          => 'recaptcha-lifter',
            'type'        => 'html',
            'description' =>  $recaptcha_field,
            
        );
        array_splice( $fields, count($fields) - 3, 0, array($recaptcha_field) );
    
        return $fields;
    }
    
    /**
     * Adds reCAPTCHA field to Lifter register fields.
     *
     * @param array $fields Lifter register fields.
     * @return array Lifter register fields with reCAPTCHA added.
     * @since 5.0.0
     */
    public function loginpress_add_recaptcha_to_lifter_register() {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        // $lp_recaptcha->loginpress_recaptcha_field();
        
        $recaptcha_field = $lp_recaptcha->loginpress_recaptcha_field();

        llms_form_field(
            array(
            'id'          => 'recaptcha-lifter',
            'type'        => 'html',
            'description' =>  $recaptcha_field,
            
            )
        );
    }

    /**
     * Adds reCAPTCHA field to Lifter register fields.
     *
     * @param array $fields Lifter register fields.
     * @return array Lifter register fields with reCAPTCHA added.
     * @since 5.0.0
     */
    public function loginpress_add_recaptcha_to_lifter_lostpass_fields( $fields ) {
        ob_start();
        $LP_Recaptcha_instance = LoginPress_Recaptcha::instance();
        $LP_Recaptcha_instance->loginpress_recaptcha_field();
        $recaptcha_field = ob_get_clean();
        
        $recaptcha_field =
                array(
                array(
                'id'          => 'recaptcha-lifter',
                'type'        => 'html',
                'description' =>  $recaptcha_field,
                
                ),
                array(
                'id'          => 'recaptcha-lifter2',
                'type'        => 'hidden',
                'value'		  => do_action( 'lifterlms_lost_do_action' ),
                ),
            );
        array_splice( $fields, count($fields) - 1, 0, $recaptcha_field );
    
        return $fields;
    }
    
    /**
     * recaptcha script callback.
     *
     * @since 5.0.0
     */
    public function loginpress_llms_form_script_callback () {
        $LP_Recaptcha_instance = LoginPress_Recaptcha::instance();
	    $LP_Recaptcha_instance->loginpress_recaptcha_script();
    }

    /**
     * Enable reCAPTCHA on LifterLMS login form.
     *
     * This function checks if reCAPTCHA is enabled and if the user has entered a valid response.
     * If the response is invalid, it adds a filter to display an error message.
     *
     * @since 5.0.0
     * @param array $creds An array containing the user's login credentials.
     * @return void
     */
    public function loginpress_llms_login_form_captcha_enable( $creds ) {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        // remove_filter( 'authenticate', array( $lp_recaptcha, 'loginpress_recaptcha_auth' ));
        if ( $creds ) {
            
            $username = $creds['user_login'];
            $password = $creds['user_password'];
            $cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
            $cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

            if ( $cap_permission || (  isset( $_POST['g-recaptcha-response'] ) && ! empty( $_POST['g-recaptcha-response'] ) ) ) { // @codingStandardsIgnoreLine.

                if ( 'v3' === $cap_type ) {

                    $good_score = $this->loginpress_settings['good_score'];
                    $score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

                    if ( $username && $password && $score < $good_score ) {
                        add_filter('lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_error_callback' ) );
                    }
                } else {
                    $response = $lp_recaptcha->loginpress_recaptcha_verifier();
                    if ( $response->isSuccess() ) {
                        return $creds;
                    }
                    if( $username && $password && ! $response->isSuccess() ) {
                        add_filter('lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_error_callback' ) );
                    }
                }
            }
        } else {
            return $creds;
        }
    }

    /**
     * recaptcha error callback.
     *
     * @since 5.0.0
     */
    public function llla_login_lifterlms_error_callback( $err ) {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        $err -> remove( 'login-error' );
        $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_recaptcha_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
        return $err;
    }

    /**
     * turnstile error callback.
     *
     * @since 5.0.0
     */
    public function llla_login_lifterlms_turnstile_error_callback( $err ) {
        $lp_recaptcha = LoginPress_Turnstile::instance();
        $err -> remove( 'login-error' );
        $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_turnstile_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
        return $err;
    }

    /**
     * hcaptcha error callback.
     *
     * @since 5.0.0
     */
    public function llla_login_lifterlms_hcaptcha_error_callback( $err ) {
        $lp_recaptcha = LoginPress_Hcaptcha::instance();
        $err -> remove( 'login-error' );
        $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_hcaptcha_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
        return $err;
    }

	/**
	 * Enables reCAPTCHA on the LLMS register form.
	 *
	 * @param mixed $valid The current validation status of the form.
	 * @param array $posted_data The data submitted via the form.
	 * @param string $location The location of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_llms_register_form_captcha_enable($valid, $posted_data, $location) {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        $lifter_register     = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
        $lifter_purchase     = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
        if ( $location === 'registration' &&  $lifter_register === false  ) {
            return;
        }
        if ( $location === 'checkout' && $lifter_purchase === false ) {
            return;
        }
        if ( $posted_data ) {

            $cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
            $cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

            if ( $cap_permission || (  isset( $_POST['g-recaptcha-response'] ) && ! empty( $_POST['g-recaptcha-response'] ) ) ) { // @codingStandardsIgnoreLine.

                if ( 'v3' === $cap_type ) {

                    $good_score = $this->loginpress_settings['good_score'];
                    $score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

                    if ( $score < $good_score ) {
                        return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
                    }
                } else {
                    $response = $lp_recaptcha->loginpress_recaptcha_verifier();
                    if ( $response->isSuccess() ) {
                        return $valid;
                    }
                    if( ! $response->isSuccess() ) {
                        return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
                    }
                }
            }
        } else {
            return $valid;
        }
    }


    /**
     * Authenticate reCAPTCHA on the llms lost password page.
     *
     * @param WP_Error $err The error object.
     * @param WP_User  $user The user object.
     * @return WP_Error The error object with reCAPTCHA validation result.
     * @since 5.0.0
     */
    public function loginpress_auth_recaptcha_lostpassword_llms($err, $user) {
        if ( $user ) {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
            $cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
            $cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

            if ( $cap_permission || (  isset( $_POST['g-recaptcha-response'] ) && ! empty( $_POST['g-recaptcha-response'] ) ) ) { // @codingStandardsIgnoreLine.

                if ( 'v3' === $cap_type ) {

                    $good_score = $this->loginpress_settings['good_score'];
                    $score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

                    if ( $score < $good_score ) {
                        $err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
                        return $err;
                    }
                } else {
                    $response = $lp_recaptcha->loginpress_recaptcha_verifier();
                    if ( $response->isSuccess() ) {
                        return $err;
                    }
                    if( ! $response->isSuccess() ) {
                        $err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
                        return $err;
                    }
                }
            }
        } else {
            return $err;
        }
    }
    
    /**
     * Adds turnstile field to Lifter login fields.
     *
     * @param array $fields Lifter login fields.
     * @return array Lifter login fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_turnstile_to_lifter_login_fields( $fields ) {
        ob_start();

        /* Cloudflare CAPTCHA Settings */
        $lp_turnstile = LoginPress_Turnstile::instance();
        $lp_turnstile->loginpress_turnstile_field('lifterlms');
        $lp_turnstile->loginpress_turnstile_script();
        $turnstile_field = ob_get_clean();
        
        $turnstile_field = array(
            'id'          => 'turnstile-lifter',
            'type'        => 'html',
            'description' =>  $turnstile_field,
            
        );
        array_splice( $fields, count($fields) - 3, 0, array($turnstile_field) );

        return $fields;
    }


    /**
     * Adds turnstile field to Lifter login fields.
     *
     * @param array $fields Lifter login fields.
     * @return array Lifter login fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_turnstile_to_lifter_register_fields( $fields ) {

        ob_start();

        /* Cloudflare CAPTCHA Settings */
        $lp_turnstile = LoginPress_Turnstile::instance();
        $lp_turnstile->loginpress_turnstile_field('lifterlms');
        $lp_turnstile->loginpress_turnstile_script();
        $turnstile_field = ob_get_clean();
        
        llms_form_field(
            array(
            'id'          => 'turnstile-lifter',
            'type'        => 'html',
            'description' =>  $turnstile_field,
            
            )
        );
    }

    public function loginpress_llms_login_form_turnstile_enable( $creds ) {

        // Retrieve the secret key from the plugin settings.
        $secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
        // Sanitize the Turnstile response from the form submission.
        $response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';



        // Verify the Turnstile response with Cloudflare's siteverify API.
        $verify_response = wp_remote_post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            array(
                'body' => array(
                    'secret'   => $secret_key,         // Your secret key.
                    'response' => $response,           // Captcha response from user.
                    'remoteip' => $_SERVER['REMOTE_ADDR'], // User's IP address.
                ),
            )
        );

        // Retrieve and decode the API response.
        $response_body = wp_remote_retrieve_body( $verify_response );
        $result        = json_decode( $response_body, true );
        if ( empty( $result['success'] ) ) {
            add_filter('lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_turnstile_error_callback' ) );
            //return $creds;
        }
        else{
            return $creds;
        }
    }

     /**
     * Adds turnstile field to Lifter lost fields.
     *
     * @param array $fields Lifter lost fields.
     * @return array Lifter lost fields with hcaptcha added.
     * @since 5.0.0
     */
    public function loginpress_add_turnstile_to_lifter_lostpass_fields( $fields ) {

        ob_start();
        $lp_turnstile = LoginPress_Turnstile::instance();
        $lp_turnstile->loginpress_turnstile_field('lifterlms');
        $lp_turnstile->loginpress_turnstile_script();
        $turnstile_field = ob_get_clean();
        
        $turnstile_field =
                array(
                array(
                'id'          => 'turnstile-lifter',
                'type'        => 'html',
                'description' =>  $turnstile_field,
                
                ),
                array(
                'id'          => 'turnstile-lifter2',
                'type'        => 'hidden',
                'value'		  => do_action( 'lifterlms_lost_do_action' ),
                ),
            );
        array_splice( $fields, count($fields) - 1, 0, $turnstile_field );
    
        return $fields;
    }

    /**
	 * Enables turnstile on the LLMS register form.
	 *
	 * @param mixed $valid The current validation status of the form.
	 * @param array $posted_data The data submitted via the form.
	 * @param string $location The location of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_llms_register_form_turnstile_enable($valid, $posted_data, $location) {
        $lifter_register     = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
        $lifter_purchase     = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
        if ( $location === 'registration' &&  $lifter_register === false  ) {
            return;
        }
        if ( $location === 'checkout' && $lifter_purchase === false ) {
            return;
        }
        if ( $posted_data ) {

            // Retrieve the secret key from the plugin settings.
            $secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
            // Sanitize the Turnstile response from the form submission.
            $response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';

            // If no response is received, return a captcha error.
            if ( ! $response ) {
                // if ( ( $form_type !== 'login' || $form_type !== 'register' ) && strpos( $_SERVER['REQUEST_URI'], '/wp-login.php' ) === false ) {
                    return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
                // }
            }

            // Verify the Turnstile response with Cloudflare's siteverify API.
            $verify_response = wp_remote_post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                array(
                    'body' => array(
                        'secret'   => $secret_key,         // Your secret key.
                        'response' => $response,           // Captcha response from user.
                        'remoteip' => $_SERVER['REMOTE_ADDR'], // User's IP address.
                    ),
                )
            );

            // Retrieve and decode the API response.
            $response_body = wp_remote_retrieve_body( $verify_response );
            $result        = json_decode( $response_body, true );
            if ( empty( $result['success'] ) ) {
                return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
            }
            else{
                return $valid;
            }
        } else {
            return $valid;
        }
    }

    /**
     * Authenticate turnstile on the llms lost password page.
     *
     * @param WP_Error $err The error object.
     * @param WP_User  $user The user object.
     * @return WP_Error The error object with turnstlie validation result.
     * @since 5.0.0
     */
    public function loginpress_auth_turnstile_lostpassword_llms($err, $user) {
        if ( $user ) {
            // Retrieve the secret key from the plugin settings.
            $secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
            // Sanitize the Turnstile response from the form submission.
            $response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';

            // If no response is received, return a captcha error.
            if ( ! $response ) {
                // if ( ( $form_type !== 'login' || $form_type !== 'register' ) && strpos( $_SERVER['REQUEST_URI'], '/wp-login.php' ) === false ) {
                    return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
                // }
            }

            // Verify the Turnstile response with Cloudflare's siteverify API.
            $verify_response = wp_remote_post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                array(
                    'body' => array(
                        'secret'   => $secret_key,         // Your secret key.
                        'response' => $response,           // Captcha response from user.
                        'remoteip' => $_SERVER['REMOTE_ADDR'], // User's IP address.
                    ),
                )
            );

            // Retrieve and decode the API response.
            $response_body = wp_remote_retrieve_body( $verify_response );
            $result        = json_decode( $response_body, true );
            if ( empty( $result['success'] ) ) {
                $err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify Hcaptcha', 'loginpress-pro' ) );
                return $err;
            }
            else{
                return $err;
            }
        } else {
            return $err;
        }
    }

    /**
     * Adds turnstile field to Lifter login fields.
     *
     * @param array $fields Lifter login fields.
     * @return array Lifter login fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_hcaptcha_to_lifter_login_fields( $fields ) {

        ob_start();

        /* LoginPress_Hcaptcha CAPTCHA Settings */
        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        $lp_hcaptcha->loginpress_hcaptcha_field();
        $lp_hcaptcha->loginpress_hcaptcha_enqueue("llms");
        $hcaptcha_field = ob_get_clean();
        
        $hcaptcha_field = array(
            'id'          => 'hcaptcha-lifter',
            'type'        => 'html',
            'description' =>  $hcaptcha_field,
            
        );
        array_splice( $fields, count($fields) - 3, 0, array($hcaptcha_field) );

        return $fields;
    }

    /**
     * Enable HCAPTCHA on LifterLMS login form.
     *
     * This function checks if HCAPTCHA is enabled and if the user has entered a valid response.
     * If the response is invalid, it adds a filter to display an error message.
     *
     * @since 5.0.0
     * @param array $creds An array containing the user's login credentials.
     * @return void
     */
    public function loginpress_llms_login_form_hcaptcha_enable( $creds ) {
        if ( $creds ) {
            $lp_recaptcha = LoginPress_Hcaptcha::instance();
            $hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
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

				if ( ! $result->success ) {
					add_filter('lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_hcaptcha_error_callback' ) );
				}else{
                    return $creds;
                }
			} elseif ( (isset( $_POST['wp-submit']) || isset( $_POST['login'] )) && ! isset( $_POST['h-captcha-response'] ) ) {
				add_filter('lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_hcaptcha_error_callback' ) );
			}
            //return $creds;
        } else {
            return $creds;
        }
    }

    /**
     * Adds hcaptcha field to Lifter login fields.
     *
     * @param array $fields Lifter login fields.
     * @return array Lifter login fields with hcaptcha added.
     * @since 5.0.0
     */
    public function loginpress_add_hcaptcha_to_lifter_register_fields( $fields ) {

        ob_start();

        /* Cloudflare CAPTCHA Settings */
        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        $lp_hcaptcha->loginpress_hcaptcha_field();
        $lp_hcaptcha->loginpress_hcaptcha_enqueue("llms-reg");
        $hcaptcha_field = ob_get_clean();
        
        llms_form_field(
            array(
            'id'          => 'hcaptcha-lifter',
            'type'        => 'html',
            'description' =>  $hcaptcha_field,
            
            )
        );
    }

    /**
	 * Enables reCAPTCHA on the LLMS register form.
	 *
	 * @param mixed $valid The current validation status of the form.
	 * @param array $posted_data The data submitted via the form.
	 * @param string $location The location of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_llms_register_form_hcaptcha_enable($valid, $posted_data, $location) {
        $lifter_register     = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
        $lifter_purchase     = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
        if ( $location === 'registration' &&  $lifter_register === false  ) {
            return;
        }
        if ( $location === 'checkout' && $lifter_purchase === false ) {
            return;
        }
        if ( $posted_data ) {

            $hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
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
					return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
				}
			} elseif ( ! isset( $_POST['h-captcha-response'] ) ) {
				return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
			}
        } else {
            return $valid;
        }
    }

     /**
     * Adds hcaptcha field to Lifter lost fields.
     *
     * @param array $fields Lifter lost fields.
     * @return array Lifter lost fields with hcaptcha added.
     * @since 5.0.0
     */
    public function loginpress_add_hcaptcha_to_lifter_lostpass_fields( $fields ) {

        ob_start();
        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        $lp_hcaptcha->loginpress_hcaptcha_field();
        $lp_hcaptcha->loginpress_hcaptcha_enqueue();
        $hcaptcha_field = ob_get_clean();
        
        $hcaptcha_field =
                array(
                array(
                'id'          => 'hcaptcha-lifter',
                'type'        => 'html',
                'description' =>  $hcaptcha_field,
                
                ),
                array(
                'id'          => 'hcaptcha-lifter2',
                'type'        => 'hidden',
                'value'		  => do_action( 'lifterlms_lost_do_action' ),
                ),
            );
        array_splice( $fields, count($fields) - 1, 0, $hcaptcha_field );
    
        return $fields;
    }

    /**
     * Authenticate turnstile on the llms lost password page.
     *
     * @param WP_Error $err The error object.
     * @param WP_User  $user The user object.
     * @return WP_Error The error object with turnstlie validation result.
     * @since 5.0.0
     */
    public function loginpress_auth_hcaptcha_lostpassword_llms($err, $user) {
        if ( $user ) {
            
			$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
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
                    $err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify Hcaptcha', 'loginpress-pro' ) );
                    return $err;
				}
			} elseif ( ! isset( $_POST['h-captcha-response'] ) ) {
                $err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify Hcaptcha', 'loginpress-pro' ) );
                return $err;
			}
        } else {
            return $err;
        }
    }
}

new LoginPress_LifterLMS_Integration();
