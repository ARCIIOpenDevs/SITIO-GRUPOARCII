<?php
/**
 * LoginPress LearnDash Integration
 *
 * @since 5.0.0
 * @version 5.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( is_plugin_inactive( 'sfwd-lms/sfwd_lms.php' ) && ! is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
    exit;
}
include_once LOGINPRESS_PRO_ROOT_PATH . '/addons/limit-login-attempts/classes/class-attempts.php';
include_once LOGINPRESS_PRO_ROOT_PATH . '/addons/login-redirects/classes/class-redirects.php';
include_once(ABSPATH.'wp-admin/includes/plugin.php');

/**
 * Handles the integration of LoginPress features with the LearnDash platform.
 *
 * @since 5.0.0
 */
class LoginPress_Learndash_Integration extends LoginPress_Attempts {
    
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
     * The settings array
     *
     * @var array
     */
    public $social_settings;

     /**
     * The Addons array
     *
     * @var array
     */
    public $addons;
    /**
     * Variable that contains position of social login on learndash login form.
     *
     * @var string
     * @since 5.0.0
     */
    public $social_position_ld_lf;
    /**
     * Variable that contains position of social login on learndash register form.
     *
     * @var string
     * @since 5.0.0
     */
    public $social_position_ld_rf;
    
    // Declare a flag to track whether the first hook was triggered
    private $learndash_social_hook_triggered = false;
    /**
     * Class constructor
     *
     * @since 5.0.0
     *
     * @access public
     *
     * @return void
     */
    public function __construct() {
        global $wpdb;
        $this->llla_table = $wpdb->prefix . 'loginpress_limit_login_details';
        $this->attempts_settings = get_option( 'loginpress_limit_login_attempts' );
        $this->settings = get_option( 'loginpress_integration_settings' );
        $this->loginpress_settings = get_option( 'loginpress_captcha_settings' );
        $this->social_settings = get_option( 'loginpress_social_logins' );
        $this->addons = get_option( 'loginpress_pro_addons' );
        $this->social_position_ld_lf   = isset( $this->settings['social_position_ld_lf'] ) ? $this->settings['social_position_ld_lf'] : 'default';
        $this->social_position_ld_rf   = isset( $this->settings['social_position_ld_rf'] ) ? $this->settings['social_position_ld_rf'] : 'default';
        $this->loginpress_ld_hooks();
    }

    /**
     * Register LearnDash-related hooks for LoginPress.
     *
     * This function binds LoginPress functionality with LearnDash by hooking into
     * relevant actions and filters provided by the LearnDash plugin.
     * Useful for customizing or enhancing the LearnDash login and registration flows.
     *
     * @since 5.0.0
     * @version 5.0.1
     */
    public function loginpress_ld_hooks() {
        global $register_learnDash;
        global $register_block;
        if(is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) || is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' )) {
            $groups = new WP_Query(array( 'post_type' => 'groups' ));
            // add_filter('social_tab_concatenation_ld', array($this,'social_tab_concatenation_ld_callback'), 10, 1);
            if ($groups->have_posts()) {
                add_filter('loginpress_login_redirects_tabs', array($this,'html_concatenation_with_learndash_callback'), 10, 1);	
            }
        }
        add_filter('loginpress_redirects_structure_html_before', array( $this, 'loginpress_login_redirects_callback' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        
        if ( isset( $this->addons['limit-login-attempts']['is_active'] ) && $this->addons['limit-login-attempts']['is_active'] ) {
            if(!is_user_logged_in()){
                add_action( 'wp_loaded', array( $this, 'llla_wp_loaded_learndash' ) );
            }
        }
        add_action( 'wp_ajax_loginpress_login_redirects_group_update', array( $this, 'loginpress_login_redirects_update_group' ) );
        add_action( 'wp_ajax_loginpress_login_redirects_group_delete', array( $this, 'loginpress_login_redirects_delete_group' ) );
        add_filter( 'loginpress_redirects_structure_html_after', array( $this, 'loginpress_learndash_groups_html_callback' ) );
        add_action( 'admin_footer', array( $this, 'loginpress_learndash_autocomplete_js' ) );
        add_action( 'wp_loaded', array($this, 'loginpress_group_priority_callback') );
        add_filter( 'login_redirect', array( $this, 'loginpress_redirects_after_login_ld' ), 11, 3 );
        add_action( 'clear_auth_cookie', array( $this, 'loginpress_redirects_after_logout' ), 11 );
        

        $login_learnDash   = isset( $this->settings['enable_social_ld_lf'] ) ? $this->settings['enable_social_ld_lf'] : '';
        $register_learnDash   = isset( $this->settings['enable_social_ld_rf'] ) ? $this->settings['enable_social_ld_rf'] : '';
        $quiz_learnDash   = isset( $this->settings['enable_social_ld_qf'] ) ? $this->settings['enable_social_ld_qf'] : '';
        $social_position_ld_qf   = isset( $this->settings['social_position_ld_qf'] ) ? $this->settings['social_position_ld_qf'] : 'below';

       
        if ( isset( $this->addons['social-login']['is_active'] ) && $this->addons['social-login']['is_active'] ) {
            if ( ! class_exists( 'LoginPress_Social' ) ) {
                require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
            }
            
            add_action('learndash-register-modal-register-form-before', array($this,'loginpress_social_login_learndash_register'), 10);
            add_action('learndash_registration_form', array($this, 'loginpress_learndash_register_widget'), 10);

            if ( ! is_user_logged_in() && 'off' !== $quiz_learnDash ) {

                if ( 'above' === $social_position_ld_qf ) {
                    add_action( 'learndash-quiz-actual-content-before', array( $this, 'loginpress_ld_quiz_above_callback' ) );
                } elseif ( 'below' === $social_position_ld_qf ) {
                    add_action( 'learndash-quiz-actual-content-after', array( $this, 'loginpress_ld_quiz_below_callback' ) );
                }
            }
            
            if ( 'off' !== $login_learnDash ) {
            
                if ( 'default' === $this->social_position_ld_lf || 'below' === $this->social_position_ld_lf ) {
                    add_filter( 'login_form_bottom', array( $this, 'loginpress_login_form_bottom_ld_widget_callback' ) );
                } else {
                    add_filter( 'login_form_top', array( $this, 'loginpress_login_form_bottom_ld_widget_callback' ) );
                }
            }

        }
        $ld_login        = isset( $this->settings['enable_captcha_ld']['login_learndash'] ) ? $this->settings['enable_captcha_ld']['login_learndash'] : false;
        $ld_register     = isset( $this->settings['enable_captcha_ld']['register_learndash'] ) ? $this->settings['enable_captcha_ld']['register_learndash'] : false;
        $ld_purchase     = isset( $this->settings['enable_captcha_ld']['register_block'] ) ? $this->settings['enable_captcha_ld']['register_block'] : false;
        $ld_lostpass     = isset( $this->settings['enable_captcha_ld']['login_ld_block'] ) ? $this->settings['enable_captcha_ld']['login_ld_block'] : false;
        $captchas_enabled = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';
        if ( $captchas_enabled !== 'off' ) {
            $captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
            if ( $captchas_type === 'type_cloudflare' ) {
                $loginpress_turnstile = LoginPress_Turnstile::instance();

                /* Cloudflare CAPTCHA Settings */
                $cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
                $cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
                $validated = isset( $this->loginpress_settings['validate_cf'] ) && $this->loginpress_settings['validate_cf'] == 'on' ? true : false;
                if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated) {
                    if ( $ld_login ) {
                        add_filter( 'login_form_middle', array($this,'loginpress_add_turnstile_to_ld_login_fields' ),99 );
                        add_filter( 'wp_authenticate_user',  array($this, 'loginpress_ld_login_form_turnstile_enable'), 9 );
                    }
                    $cap_register        = isset( $this->loginpress_settings['captcha_enable_cf']['register_form'] ) ? $this->loginpress_settings['captcha_enable_cf']['register_form'] : false;
                    if ( $ld_register && !$cap_register) {
                        add_action( 'register_form', array($this,'loginpress_add_turnstile_to_ld_register_fields' ),99);
                        add_filter( 'registration_errors', array($this, 'loginpress_ld_login_form_turnstile_enable'), 96, 3 );
                    }
                }
            } else if ( $captchas_type === 'type_recaptcha' ){

                /* Add reCAPTCHA on ld login form */
                if ( $ld_login ) {
                    add_filter( 'login_form_middle', array($this,'loginpress_add_recaptcha_to_ld_login_fields' ),99 );
                }
                $cap_register        = isset( $this->loginpress_settings['captcha_enable']['register_form'] ) ? $this->loginpress_settings['captcha_enable']['register_form'] : false;

                /* Add reCAPTCHA on registration form */
                if ( $ld_register && !$cap_register) {
                    add_action( 'register_form', array( $this, 'loginpress_add_recaptcha_to_ld_register' ),99 );
                }

                /* Authentication reCAPTCHA on ld login form */
                if ( ! isset( $_GET['customize_changeset_uuid'] ) && $ld_login ) {
                    add_action( 'wp_authenticate_user',  array($this, 'loginpress_ld_login_form_captcha_enable'), 9 );
                }

                /* Authentication reCAPTCHA on ld purchase and registration form */
                if ( ! isset( $_GET['customize_changeset_uuid'] ) && ( $ld_register)) {
                    add_filter( 'registration_errors', array($this, 'loginpress_ld_register_form_captcha_enable'), 10, 3 );
                }


            } else if ( $captchas_type === 'type_hcaptcha' ){
                $hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
                $hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

                if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
                    if ( $ld_login ) {
                        add_filter( 'login_form_middle', array($this,'loginpress_add_hcaptcha_to_ld_login_fields' ),99 );
                        add_action( 'wp_authenticate_user',  array($this, 'loginpress_ld_login_form_hcaptcha_enable') );
                    }
                    $cap_register        = isset( $this->loginpress_settings['hcaptcha_enable']['register_form'] ) ? $this->loginpress_settings['hcaptcha_enable']['register_form'] : false;
                    if ( $ld_register && !$cap_register) {
                        add_filter( 'register_form', array($this,'loginpress_add_hcaptcha_to_ld_register_fields' ),99 );
                        add_filter( 'registration_errors', array($this,'loginpress_ld_register_form_hcaptcha_enable' ),99, 3 );
                    } 
                }
            }
        }
    }


    /**
     * Callback to display social login above LearnDash quiz content.
     *
     * @since 5.0.0
     */
    public function loginpress_ld_quiz_above_callback() {
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    }

    /**
     * Callback to display social login below LearnDash quiz content.
     *
     * @since 5.0.0
     */
    public function loginpress_ld_quiz_below_callback() {
        $loginpress_social = LoginPress_Social::instance();
        $loginpress_social->loginpress_social_login();
    }

    /**
     * Load CSS and JS files at admin side on loginpress-settings page only.
     *
     * @since  5.0.0
     *
     * @return void
     */
    public function admin_scripts( ) {
        if ( isset( $this->addons['social-login']['is_active'] ) && $this->addons['social-login']['is_active'] ) {
            wp_enqueue_style( 'loginpress-social-login', LOGINPRESS_SOCIAL_DIR_PATH . 'assets/css/login.css', array(), LOGINPRESS_PRO_VERSION );
        }
            // wp_enqueue_style( 'loginpress-social-login', plugins_url( 'addons/social-login/assets/css/login.css', dirname(dirname(__FILE__) )), array(), LOGINPRESS_PRO_VERSION );
        // wp_enqueue_style( 'loginpress_login_redirect_stlye', LOGINPRESS_PRO_DIR_URL . '/integration/learndash/assets/css/style.css', array(), LOGINPRESS_PRO_VERSION );
        // wp_enqueue_script( 'loginpress_learndash_redirect_js', LOGINPRESS_PRO_DIR_URL . '/integration/learndash/assets/js/main.js', array( 'jquery', 'loginpress_datatables_js' ), LOGINPRESS_PRO_VERSION, false );
        wp_localize_script(
            'loginpress_learndash_redirect_js',
            'loginpress_redirect_sociallogins',
            array(
                'group_nonce' => wp_create_nonce( 'loginpress-group-redirects-nonce' ),
                // translators: Learndash search group.
                'translate'  => array(
						_x( 'Search Group', 'The label Text of Login Redirect addon learndash group search field', 'loginpress-pro' ),
						_x( 'Search group For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific learndash group tab\'s search', 'loginpress-pro' ),
					),
            )
        );

        wp_localize_script(
            'loginpress_learndash_redirect_js',
            'loginpress_redirect',
            array(
                // translators: Learndash search role.
                'translate'  => array(
                    _x( 'Search Role For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific Roles tab\'s search', 'loginpress-pro' ),
                    // translators: Search data
                    sprintf( _x( '%1$sSearch user\'s data from below the list%2$s', 'Search Label on Data tables', 'loginpress-pro' ), '<p class="description">', '</p>' ),
                    _x( 'Enter keyword', 'The search keyword for the autologin users.', 'loginpress-pro' ),
                ),
            )
        );

    }

    /**
     * Concatenate HTML for LearnDash group tab in the Login Redirects setting page.
     *
     * @param string $html The HTML to be concatenated.
     *
     * @return string The concatenated HTML.
     *
     * @since  5.0.0
     */
    public function html_concatenation_with_learndash_callback($html) {
        // translators: Learndash tab in login redirect
        $html .= sprintf( __( ' %1$sLearnDash Groups%2$s ', 'loginpress-pro' ), '<a href="#loginpress_login_redirect_learndash_groups" class="loginpress-redirects-tab">', '</a>', );
        return $html;
    }

    /**
     * login redirects callback.
     *
     *
     *
     * @since  5.0.0
     */
    public function loginpress_login_redirects_callback($html) {
        $html .= sprintf( '<input type="%1$s" name="%2$s" id="%2$s" value="" placeholder="%3$s" %4$s', 'text', 'loginpress_redirect_learndash_group_search', __( 'Type Group', 'loginpress-pro' ), '/>' );
        return $html;
    }
    
    /**
     * This function will be called on wp_loaded hook, check if Learndash is active and if index.php is current page.
     *
     * @since  5.0.0
     *
     * @return void
     */
    public function llla_wp_loaded_learndash() {
        global $pagenow;
        if ('index.php' === $pagenow && is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) || is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
            add_filter('learndash_alert_message', array($this,'learndash_alert_message_callback'),10,3);
        }
    }

    /**
     *  It will check the current user's IP attempts and if user reached the limit, it will return the error message.
     *
     * @param string $error_message
     * @param string $type
     * @param string $icon
     *
     * @return string $messages The error message which will be shown to the user.
     *
     * @since  5.0.0
     */
    public function learndash_alert_message_callback($error_message,$type,$icon) {
        if ( ! isset( $_GET['login'] )){
            return $error_message;
        }
        global $wpdb;
        $ip           = $this->get_address();
        $current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
        $attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
        $minutes_lockout   = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
        $lockout_time = $current_time - ( intval($minutes_lockout) * 60 );
        $attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s", $ip, $lockout_time ) ); // @codingStandardsIgnoreLine.
        if ( $attempt_time < $attempts_allowed ) {

            $error_message = wp_kses_post($this->loginpress_attempts_error( -1 + $attempt_time ));

        }
        $messages = $error_message;
        
        return $messages;
    }

    /**
     *  It will check the current user's IP attempts and if user reached the limit, it will return the error message.
     *
     * @param string $error_message
     * @param string $type
     * @param string $icon
     *
     * @return string $messages The error message which will be shown to the user.
     *
     * @since  5.0.0
     */
    public function learndash_alert_message_captcha_callback($error_message,$type,$icon) {
        return new \WP_Error(
            'captcha_error',
            __( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
        );
    }

    /**
     * A callback function that will show the LearnDash groups table html.
     *
     * @param string $html The html which will be shown on the page.
     *
     * @return string $html The modified html.
     *
     * @since  5.0.0
     */
    public function loginpress_learndash_groups_html_callback($html) {

        $html .= '<table id="loginpress_login_redirect_learndash_groups" class="loginpress_login_redirect_learndash_groups">
        <thead><tr>
            <th class="loginpress_log_userName">' . esc_html__( 'Group', 'loginpress-pro' ) . '</th>
            <th class="loginpress_login_redirect">' . esc_html__( 'Login URL', 'loginpress-pro' ) . '</th>
            <th class="loginpress_logout_redirect">' . esc_html__( 'Logout URL', 'loginpress-pro' ) . '</th>
            <th class="loginpress_action">' . esc_html__( 'Action', 'loginpress-pro' ) . '</th>
        </tr></thead>';

        $loginpress_redirect_group = get_option( 'loginpress_redirects_group' );

        if ( ! empty( $loginpress_redirect_group ) ) {
            
            foreach ( $loginpress_redirect_group as $group => $value ) {

                $html .= '<tr id="loginpress_redirects_group_' . $group . '" data-login-redirects-group="' . $group . '"><td class="loginpress_user_name"><div class="lp-tbody-cell group-name-value">' . $value['name'] . '</div></td><td class="loginpress_login_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr($value['login']) . '" id="loginpress_login_redirects_url"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr($value['logout']) . '" id="loginpress_logout_redirects_url"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><button type="button" class="button loginpress-redirects-group-update" value="' . esc_html__( 'Update', 'loginpress-pro' ) . '" ></button> <button type="button" class="button loginpress-redirects-group-delete"  value="' . esc_html__( 'Delete', 'loginpress-pro' ) . '" ></button></div></td><input type="hidden" placeholder="10" value="'. esc_attr($value['priority']) .'" id="loginpress_group_order"/></tr>';
            }
        }		

        $html .= '</table>';

        return $html;
    }

    /**
     * Get the users list and Saved it in footer that will use for autocomplete in search.
     *
     * @since 5.0.0
     */
    public function loginpress_learndash_autocomplete_js() {

        /**
         * Check to apply the script only on the LoginPress Settings page.
         *
         * @since 5.0.0
         */
        $current_screen = get_current_screen();

        if ( isset( $current_screen->base ) && ( 'toplevel_page_loginpress-settings' !== $current_screen->base ) ) {
            return;
        }

        $groups = get_posts([
            'post_type' => 'groups',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        $group_data = array();
        foreach( $groups as $group => $value ) {
            if ( is_object( $value ) ) {
                $group_data[$group]['label'] = $value->post_title;
                $group_data[$group]['value'] = $value->post_name;
            }
        }

        ?>
        <script type="text/javascript">
            var redirect_group;
            jQuery(document).ready( function($) {

                var groups = <?php echo wp_json_encode( array_values( $group_data ) ); ?>;
                if ( jQuery( 'input[name="loginpress_redirect_learndash_group_search"]' ).length > 0 ) {
                    jQuery( 'input[name="loginpress_redirect_learndash_group_search"]' ).autocomplete({
                        
                        source: groups,
                        minLength: 1,
                        select: function( event, ui ) {
                            
                            var name = ui.item.label;
                            var value = ui.item.value;
                            if ( $( '#loginpress_redirects_group_' + value ).length == 0 ) {
                                $('#loginpress_login_redirect_learndash_groups .dataTables_empty').hide();
                                var get_html = $('<tr id="loginpress_redirects_group_' + value + '" data-login-redirects-group="' + value + '"><td class="loginpress_user_name"><div class="lp-tbody-cell group-name-value">' + name + '</div></td><td class="loginpress_login_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_login_redirects_url"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_logout_redirects_url"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><input type="button" class="button loginpress-redirects-group-update" value="<?php echo esc_html__( 'Update', 'loginpress-pro' ); ?>" /> <input type="button" class="button loginpress-redirects-group-delete" value="<?php echo esc_html__( 'Delete', 'loginpress-pro' ); ?>" /></div></td><input type="number" style="display:none" placeholder="10" value="" id="loginpress_group_order"/></tr>');

                                if ( $('#loginpress_redirects_group_' + value ).length == 0 ) {
                                    redirect_group.row.add(get_html[0]).draw();
                                    $( '#loginpress_redirects_group_'+value ).find('td:first-child').addClass('dtfc-fixed-left');
                                    $( '#loginpress_redirects_group_'+value ).find('td:last-child').addClass('dtfc-fixed-right');
                                }
                            } else {
                                $( '#loginpress_redirects_group_' + value ).addClass( 'loginpress_user_highlighted' );
                                setTimeout(function(){
                                    $( '#loginpress_redirects_group_' + value ).removeClass( 'loginpress_user_highlighted' );
                                }, 3000 );
                            }
                        }
                    });	
                }
            });
        </script>
        <?php
    }

    /**
     * LoginPress_redirects_learndash_group.
     *
     * @since 5.0.0
     */
    public function loginpress_login_redirects_update_group() {

        check_ajax_referer( 'loginpress-group-redirects-nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No cheating, huh!', 'loginpress-pro' ) );
        }
        if ( isset( $_POST['logout'] ) && isset( $_POST['login'] ) && isset( $_POST['group'] ) && isset( $_POST['priority'] ) && isset( $_POST['value'] ) ) {
            $loginpress_logout = esc_url_raw( wp_unslash( $_POST['logout'] ) );
            $value =  wp_unslash( $_POST['value'] );
            $loginpress_login  = esc_url_raw( wp_unslash( $_POST['login'] ) );
            $group             = sanitize_text_field( wp_unslash( $_POST['group'] ) );
            $priority          = intval($_POST['priority']) ;
            if(  empty( $priority ) || $priority <= 0 ) {
                $priority = 10;
            }
            $check_group       = get_option( 'loginpress_redirects_group' );
            $add_group         = array(
                $value => array(
                    'login'  => $loginpress_login,
                    'logout' => $loginpress_logout,
                    'priority' => $priority,
                    'name' => $group,
                ),
            );

            if ( $check_group && ! in_array( $group, $check_group, true ) ) {
                $redirect_groups = array_merge( $check_group, $add_group );
            } else {
                $redirect_groups = $add_group;
            }

            update_option( 'loginpress_redirects_group', $redirect_groups, true );
        }
        wp_die();
    }

    /**
     * Handles AJAX request to delete a LearnDash group redirect setting.
     *
     * Verifies security nonce, checks user capabilities, and removes the specified group
     * from the 'loginpress_redirects_group' option in the database.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function loginpress_login_redirects_delete_group() {

        check_ajax_referer( 'loginpress-group-redirects-nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No cheating, huh!' );
        }
        if ( isset( $_POST['group'] ) ) {
            $group       = sanitize_text_field( wp_unslash( $_POST['group'] ) );
            $check_group = get_option( 'loginpress_redirects_group' );

            if ( isset( $check_group[ $group ] ) ) {

                $check_group[ $group ] = null;
                $check_group          = array_filter( $check_group );

                update_option( 'loginpress_redirects_group', $check_group, true );
            }
        }
        wp_die();
    }

    /**
     * Sort by priority
     *
     * @since 5.0.0
     * @return bool
     */
    public function loginpress_sort_by_priority( $group_a, $group_b ) {
        if ($group_a['priority'] === $group_b['priority']) {
            return 1;
        } else {
            return $group_b['priority'] <=> $group_a['priority'];
        }
    }

    /**
     * This updates the 'loginpress_redirects_group' option with the modified value.
     *
     * @return void
     */
    public function loginpress_group_priority_callback() {
        $check_group = get_option( 'loginpress_redirects_group' );

        /**
         * Filter the group redirects data before saving it to the database.
         *
         * Allows developers to modify the 'loginpress_redirects_group' data
         * before it's persisted to the options table.
         *
         * @since 5.0.0
         *
         * @param array $check_group Array of redirect groups and their data.
         */
        $modified_group = apply_filters( 'loginpress_redirects_group_filter', $check_group );
        update_option( 'loginpress_redirects_group', $modified_group );
    }

    /**
     * This function determines if WordPress's local URL limitation should be bypassed.
     *
     * @param string $redirect_to where to redirect.
     * @param string $requested_redirect_to requested redirect.
     * @param object $user user object.
     * @return string
     * @since  5.0.0
     */
    public function loginpress_redirects_after_login_ld( $redirect_to, $requested_redirect_to, $user ) {

        $loginpress_redirects = new LoginPress_Set_Login_Redirect();
        if ( isset( $user->ID ) ) {
            $user_redirects_url = $loginpress_redirects->loginpress_redirect_url( $user->ID, 'loginpress_login_redirects_url' );
            $role_redirects_url = get_option( 'loginpress_redirects_role' );
            $group_redirect_url = get_option( 'loginpress_redirects_group' );

            if ( isset( $user->roles ) && is_array( $user->roles ) ) {

                if ( empty( $user_redirects_url ) && empty( $role_redirects_url ) && ! empty( $group_redirect_url ) ) {
                    
                    $groups = get_posts([
                        'post_type' => 'groups',
                        'post_status' => 'publish',
                        'numberposts' => -1
                    ]);

                    $group_data = [];
                    foreach ($groups as $group) {
                        $group_name = $group->post_name;
                        $group_id = $group->ID;
                        $user_in_groups = learndash_is_user_in_group($user->ID, $group_id);
                        if (isset($group_redirect_url[$group_name]) && $user_in_groups) {
                            $group_data[$group_name]['login'] = isset($group_redirect_url[$group_name]['login']) && ! empty($group_redirect_url[$group_name]['login']) ? $group_redirect_url[$group_name]['login'] : $redirect_to;
                            $group_data[$group_name]['priority'] = $group_redirect_url[$group_name]['priority'];
                        }
                    }
                    if (empty($group_data)) {
                        return $redirect_to;
                    }
                    usort($group_data, array($this, 'loginpress_sort_by_priority'));
                    $highest_priority_group = reset($group_data);
                    $login_url = $highest_priority_group['login'];

                    if ( $loginpress_redirects->is_inner_link( $login_url ) ) {
                        return $login_url;
                    }

                    $loginpress_redirects->loginpress_safe_redirects( $user->ID, $user->name, $user, $login_url );
                }
            } else {
                return $redirect_to;
            }
        }
        return $redirect_to;
    }

    /**
     * Handles user logout redirects based on priority: user-specific, role-based, or LearnDash group-based settings..
     *
     * @return void
     * @since  5.0.0
     */
    public function loginpress_redirects_after_logout() {

        $user_id = get_current_user_id();
        $loginpress_redirects = new LoginPress_Set_Login_Redirect();
        $user_redirects_url = $loginpress_redirects->loginpress_redirect_url( $user_id, 'loginpress_login_redirects_url' );
        $role_redirects_url = get_option( 'loginpress_redirects_role' );
        $group_redirect_url = get_option( 'loginpress_redirects_group' );

        if ( 0 !== $user_id ) {


            if ( empty( $user_redirects_url ) && empty( $role_redirects_url ) && ! empty( $group_redirect_url  ) ) {
                $groups = get_posts([
                    'post_type' => 'groups',
                    'post_status' => 'publish',
                    'numberposts' => -1
                ]);
                $user_group_ids = learndash_get_users_group_ids( $user_id );
                $group_data = [];

                foreach ($groups as $group) {
                    $group_name = $group->post_name;
                    $group_id = $group->ID;
                    $user_in_groups = learndash_is_user_in_group($user_id, $group_id);
                    if (isset($group_redirect_url[$group_name]) && $user_in_groups) {
                        $group_data[$group_name]['logout'] = isset($group_redirect_url[$group_name]['logout']) && ! empty($group_redirect_url[$group_name]['logout']) ? $group_redirect_url[$group_name]['logout'] : '/';
                        $group_data[$group_name]['priority'] = $group_redirect_url[$group_name]['priority'];
                    }

                }
                if (empty($group_data)) {
                    wp_safe_redirect( '/' );
                    exit;
                }
                usort($group_data, array($this, 'loginpress_sort_by_priority'));
                $highest_priority_group = reset($group_data);
                $logout_url = $highest_priority_group['logout'];
                wp_safe_redirect( $logout_url );
                exit;
            }
        }
    }

    /**
     * Modifies the registration form for LearnDash by adding or removing the social login action.
     *
     * @global string $register_learnDash Flag to determine whether to register LearnDash or not.
     * @return void
     * @since  5.0.0
     */
    public function loginpress_social_login_learndash_register(){
        $this->learndash_social_hook_triggered = true;
        global $register_learnDash;
        $social_p_ld_rf = $this->social_position_ld_rf;
        $loginpress_social = LoginPress_Social::instance();
        if ( $register_learnDash !== 'off' ) {
            if ( ! has_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) ) ) {
                add_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) );
            }
            add_action( 'wp_footer', function () use ( $social_p_ld_rf ) {
                $this->loginpress_output_social_login_position_script( $social_p_ld_rf );
            });
            
        } else {
            remove_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) );
        }
    }

    /**
     * Modifies the registration form for LearnDash by adding or removing the social login action.
     *
     * @global string $register_learnDash Flag to determine whether to register LearnDash or not.
     * @return void
     * @since  5.0.0
     */
    public function loginpress_learndash_register_widget(){
        if ($this->learndash_social_hook_triggered){
            return;
        }
       
        //$this->learndash_social_hook_triggered = true;
        global $register_learnDash;
        $social_p_ld_rf = $this->social_position_ld_rf;
        $loginpress_social = LoginPress_Social::instance();
        if ( $register_learnDash !== 'off' ) {
            if ( ! has_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) ) ) {
                
                $loginpress_social->loginpress_social_login();
            }
            add_action( 'wp_footer', function () use ( $social_p_ld_rf ) {
                $this->loginpress_output_social_login_position_script( $social_p_ld_rf );
            });
           
        } else {
            remove_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) );
        }
    }

    /**
     * Modifies the registration form for LearnDash by adding or removing the social login action.
     *
     * @return void
     * @since  5.0.0
     */
    public function loginpress_output_social_login_position_script( $position = 'default' ) {
        $separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
        $separator_text = esc_html( $separator_text );
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var formSelectors = ['#learndash_registerform', '#registerform'];
            var position = '<?php echo esc_js( $position ); ?>';
    
            formSelectors.forEach(function(selector) {
                var form = document.querySelector(selector);
                if (!form) return;
    
                var socialDiv    = form.querySelector('.social-networks');
                var submitButton = form.querySelector('input[type="submit"], #wp-submit');
                var usernameField = form.querySelector('input[name="user_login"], input[name="username"]');
    
                if (!socialDiv) return;
    
                switch(position) {
                    case 'default':
                        if (submitButton) {
                            var separator = document.createElement('span');
                            separator.className = 'social-sep';
                            separator.innerHTML = `<span><?php echo $separator_text; ?></span>`;
                            var submitWrapper = submitButton.parentElement;
                            submitWrapper.insertAdjacentElement('afterend', separator);
                            separator.insertAdjacentElement('afterend', socialDiv);
                        }
                        break;
    
                    case 'below':
                        if (submitButton) {
                            var submitWrapper = submitButton.parentElement;
                            submitWrapper.insertAdjacentElement('afterend', socialDiv);
                        }
                        break;
    
                    case 'above':
                        if (usernameField) {
                            var usernameWrapper = usernameField.parentElement;
                            usernameWrapper.insertAdjacentElement('beforebegin', socialDiv);
                        }
                        break;
    
                    case 'above_separator':
                        if (usernameField) {
                            var separator = document.createElement('span');
                            separator.className = 'social-sep';
                            separator.innerHTML = `<span><?php echo $separator_text; ?></span>`;
                            var usernameWrapper = usernameField.parentElement;
                            usernameWrapper.insertAdjacentElement('beforebegin', separator);
                            separator.insertAdjacentElement('beforebegin', socialDiv);
                        }
                        break;
                }
            });
        });
        </script>
        <?php
    }    

    /**
     * Generates the HTML for the social login buttons at the bottom of the login form.
     *
     * @return string The HTML for the social login buttons.
     * @since  5.0.0
     */
    public function loginpress_login_form_bottom_ld_widget_callback() {
        
            $redirect_to = site_url() . $_SERVER['REQUEST_URI']; // @codingStandardsIgnoreLine.
			$encoded_url = rawurlencode( $redirect_to );
			$social_login_class = LoginPress_Social::instance();
			// if( (!isset( $this->settings['enable_social_login_links']['login'] )) && ( !isset( $this->settings['enable_social_login_links']['register'] ) ))
			// {
			// 	return;
			// }
			
			$button_style = isset($this->social_settings['social_button_styles']) ? $this->social_settings['social_button_styles'] : '';
			$button_text = $this->social_settings['social_login_button_label'] ?? 'Login with %provider%';
			$provider_order = isset($this->social_settings['provider_order']) && !empty($this->social_settings['provider_order'])
				? (is_array($this->social_settings['provider_order']) 
					? $this->social_settings['provider_order'] 
					: json_decode($this->social_settings['provider_order'], true))
				: ['facebook', 'twitter', 'gplus', 'linkedin', 'microsoft', 'apple', 'discord', 'wordpress', 'github'];
			
			$html =  "<div class='social-networks block " . esc_attr( "loginpress-$button_style" ) . "'>";
			if ( $this->social_position_ld_lf == 'default' ) {
				$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
				$html .=  "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
			}
			
			foreach ( $provider_order as $provider ) {
				// if($provider != 'gplus'){
				// 	if ( true === $this->is_shortcode  ) {
				// 		continue;
				// 	}
				// }
				// else if (true === $this->is_shortcode ){
				// 	continue;
				// }
				if ( isset( $this->social_settings[$provider] ) && 'on' === $this->social_settings[$provider] && !empty($this->social_settings["{$provider}_status"]) && strtolower( $this->social_settings["{$provider}_status"] ) != "not verified" ) {
					$client_id_key = "{$provider}_client_id";
					$client_secret_key = "{$provider}_client_secret";
					$app_id_key = "{$provider}_app_id";
					$app_secret_key = "{$provider}_app_secret";

		
					if ( (! empty( $this->social_settings[$client_id_key] ) && ! empty( $this->social_settings[$client_secret_key] )) ||
						 (! empty( $this->social_settings[$app_id_key] ) && ! empty( $this->social_settings[$app_secret_key] )) || 
						 (! empty( $this->social_settings["{$provider}_oauth_token"] ) && ! empty( $this->social_settings["{$provider}_token_secret"] )) ||
						 (! empty( $this->social_settings["{$provider}_service_id"] ) && ! empty( $this->social_settings["{$provider}_key_id"] ) && ! empty( $this->social_settings["{$provider}_team_id"] ) && ! empty( $this->social_settings["{$provider}_p_key"] )) ) {
						
						$button_label_key = "{$provider}_button_label";
						if($provider == 'gplus'){
							$button_label_key = "google_button_label";
						}
						// Replace 'gplus' with 'Google'
						$provider_display_name = ($provider === 'gplus') ? 'Google' : ucfirst($provider);

						$provider_button_text = !empty( $this->social_settings[$button_label_key] )
							? $this->social_settings[$button_label_key]
							: (!empty( $button_text )
								? str_replace( '%provider%', $provider_display_name, $button_text )
								: 'Login with ' . $provider_display_name );
						
						$login_id = "{$provider}_login";
						$icon_class = ( $provider === 'gplus' ) ? 'icon-google-plus' :"icon-$provider";
						
						$html .=  "<a href='" . esc_url_raw( wp_login_url() . "?lpsl_login_id=$login_id&state=" . base64_encode( "redirect_to=$encoded_url" ) . "&redirect_to=$redirect_to" ) . "' title='" . esc_html( $provider_button_text ) . "' rel='nofollow'>";
						$html .=  "<div class='lpsl-icon-block $icon_class clearfix'>";
						$html .=  "<span class='lpsl-login-text'>" . esc_html( $provider_button_text ) . "</span>";
						$html .=  $social_login_class->get_provider_icon( $provider ); // Dynamically fetch the provider's icon.
						$html .=  "</div></a>";
					}
				}
			}
			
			$html .=  "</div>";
            if ( $this->social_position_ld_lf == 'above_separator') {
				$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
				$html .=  "<span class='social-sep'><span>" . esc_html( $separator_text ) . "</span></span>";
			}
			return $html;
    }

     /**
     * Adds reCAPTCHA field to ld login fields.
     *
     * @param array $fields ld login fields.
     * @return array ld login fields with reCAPTCHA added.
     * @since 5.0.0
     */
    public function loginpress_add_recaptcha_to_ld_login_fields( $fields ) {

        ob_start();
        
            
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        $lp_recaptcha->loginpress_recaptcha_field();
        $lp_recaptcha->loginpress_recaptcha_script();
        $recaptcha_field = ob_get_clean();
        

    
        return $recaptcha_field;
    }

    /**
     * Adds reCAPTCHA field to ld register fields.
     *
     * @return array ld register fields with reCAPTCHA added.
     * @since 5.0.0
     */
    public function loginpress_add_recaptcha_to_ld_register() {
        //ob_start();
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        $lp_recaptcha->loginpress_recaptcha_field();
        $lp_recaptcha->loginpress_recaptcha_script();

        // $recaptcha_field = ob_get_clean();
    }
    
    /**
     * recaptcha script callback.
     *
     * @since 5.0.0
     */
    public function ld_form_script_callback () {
        $LP_Recaptcha_instance = LoginPress_Recaptcha::instance();
        add_action( 'wp_head', array( $LP_Recaptcha_instance, 'loginpress_recaptcha_script' ) );
    }

    /**
     * Enable reCAPTCHA on ld login form.
     *
     * This function checks if reCAPTCHA is enabled and if the user has entered a valid response.
     * If the response is invalid, it adds a filter to display an error message.
     *
     * @since 5.0.0
     * @param array $user An array containing the user's login credentials.
     * @return void
     */
    public function loginpress_ld_login_form_captcha_enable( $user ) {
        if (is_wp_error($user) || ! isset($_POST['learndash-login-form'])) {
            return $user;
        }
    
        if ( ! $user instanceof WP_User ) {
            return new WP_Error( 'invalid_user', __( '<strong>Error:</strong> Invalid user data.', 'loginpress-pro' ) );
        }
        if ( $user ) {
            $lp_recaptcha = LoginPress_Recaptcha::instance();
            $cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';

            if (  isset( $_POST['g-recaptcha-response'] ) && ! empty( $_POST['g-recaptcha-response'] ) ) { // @codingStandardsIgnoreLine.

                if ( 'v3' === $cap_type ) {

                    $good_score = $this->loginpress_settings['good_score'];
                    $score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();
                    if (  $score < $good_score ) {
                       
                            return new \WP_Error(
                                'captcha_error',
                                __( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
                            );
                       
                    }
                    return $user;
                } else {
                    $response = $lp_recaptcha->loginpress_recaptcha_verifier();
                    if ( $response->isSuccess() ) {
                        return $user;
                    }
                    if(  ! $response->isSuccess() ) {
                            return new \WP_Error(
                                'captcha_error',
                                __( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
                            );
                        //}
                    }
                }
            } else {
                return new \WP_Error(
                    'captcha_error',
                    __( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
                );
            }
        } else {
            return $user;
        }
    }

    /**
     * llla error callback.
     *
     * @since 5.0.0
     */
    public function llla_login_ldlms_error_callback( $err ) {
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
    public function llla_login_ld_turnstile_error_callback( $err,$type,$icon ) {
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
    public function llla_login_ldlms_hcaptcha_error_callback( $err ) {
        $lp_recaptcha = LoginPress_Hcaptcha::instance();
        $err -> remove( 'login-error' );
        $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_hcaptcha_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
        return $err;
    }

	/**
	 * Enables reCAPTCHA on the ld register form.
	 *
	 * @param mixed $valid The current validation status of the form.
	 * @param array $posted_data The data submitted via the form.
	 * @param string $location The location of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_ld_register_form_captcha_enable($errors, $posted_data, $location) {
        $lp_recaptcha = LoginPress_Recaptcha::instance();
        static $executed = false;
        if (is_wp_error($errors) || ! isset($_POST['learndash-registration-form']) ) {
            return $errors;
        }
        // Prevent duplicate execution
        if ( $executed ) {
            add_filter('learndash_registration_errors', array($this,'learndash_alert_message_captcha_callback'),10,3);
            return $errors;
        }
        $executed = true;
        $cap_type = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';

			if ( isset( $_POST['g-recaptcha-response'] ) || (  isset( $_POST['captcha_response'] ) ) ) { // @codingStandardsIgnoreLine.

				if ( 'v3' === $cap_type ) {

					$good_score = $this->loginpress_settings['good_score'];
					$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();
					if ( $score < $good_score ) {
                        add_filter('learndash_registration_errors', array($this,'learndash_alert_message_captcha_callback'),10,3);
						return new WP_Error( 'recaptcha_error', $lp_recaptcha->loginpress_recaptcha_error() );
					}
				} else {

					$response = $lp_recaptcha->loginpress_recaptcha_verifier();
					if ( ! $response->isSuccess() ) {
                        add_filter('learndash_registration_errors', array($this,'learndash_alert_message_captcha_callback'),10,3);
						return new WP_Error( 'recaptcha_error', $lp_recaptcha->loginpress_recaptcha_error() );
					}
				}
			} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) {
                learndash_get_template_part(
                    'modules/alert.php',
                    array(
                        'type'    => 'warning',
                        'icon'    => 'alert',
                        'message' => 'Captcha verification failed',
                    ),
                    true
                );
                add_filter('learndash_registration_errors_after', array($this,'learndash_alert_message_captcha_callback'),99,3);
				return new WP_Error( 'recaptcha_error', $lp_recaptcha->loginpress_recaptcha_error() );
			}

			return $errors;
    }

    
    /**
     * Adds turnstile field to ld login fields.
     *
     * @param array $fields ld login fields.
     * @return array ld login fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_turnstile_to_ld_login_fields($content = '' ) {
        ob_start();

        /* Cloudflare CAPTCHA Settings */
        $lp_turnstile = LoginPress_Turnstile::instance();
        $lp_turnstile->loginpress_turnstile_field('learndash'); // Use correct integration key
        $lp_turnstile->loginpress_turnstile_script();
        
        $turnstile_field = ob_get_clean();

        return $turnstile_field;
    }


    /**
     * Adds turnstile field to ld login fields.
     *
     * @param array $fields ld login fields.
     * @return array ld login fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_turnstile_to_ld_register_fields( $fields ) {



        /* Cloudflare CAPTCHA Settings */
        $lp_turnstile = LoginPress_Turnstile::instance();
        $lp_turnstile->loginpress_turnstile_field('learndash');
        $lp_turnstile->loginpress_turnstile_script();

    }

    public function loginpress_ld_login_form_turnstile_enable( $creds ) {
        // Skip if already an error
        if (is_wp_error($creds) || ( ! isset($_POST['learndash-login-form']) && ! isset($_POST['learndash-registration-form']) )) {
            return $creds;
        }

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
            
            return new \WP_Error(
                'captcha_error',
                __( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
            );
        }
        else{
            return $creds;
        }
    }


    /**
	 * Enables turnstile on the ld register form.
	 *
	 * @param mixed $valid The current validation status of the form.
	 * @param array $posted_data The data submitted via the form.
	 * @param string $location The location of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function ld_register_form_turnstile_enable($valid, $posted_data, $location) {
        $ld_register     = isset( $this->settings['enable_captcha_ld']['register_learndash'] ) ? $this->settings['enable_captcha_ld']['register_learndash'] : false;
        if ( $location === 'registration' &&  $ld_register === false  ) {
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
                return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
            }
            else{
                return $valid;
            }
        } else {
            return $valid;
        }
    }


    /**
     * Adds turnstile field to ld login fields.
     *
     * @param array $fields ld login fields.
     * @return array ld login fields with turnstile added.
     * @since 5.0.0
     */
    public function loginpress_add_hcaptcha_to_ld_login_fields( $fields ) {

        ob_start();

        /* Cloudflare CAPTCHA Settings */
        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        $lp_hcaptcha->loginpress_hcaptcha_field();
        $lp_hcaptcha->loginpress_hcaptcha_enqueue('ld');
        $hcaptcha_field = ob_get_clean();

        return $hcaptcha_field;
    }

    /**
     * Enable HCAPTCHA on ldLMS login form.
     *
     * This function checks if HCAPTCHA is enabled and if the user has entered a valid response.
     * If the response is invalid, it adds a filter to display an error message.
     *
     * @since 5.0.0
     * @param array $creds An array containing the user's login credentials.
     * @return void
     */
    public function loginpress_ld_login_form_hcaptcha_enable( $creds ) {
        // Skip if already an error
        if (is_wp_error($creds) || ! isset($_POST['learndash-login-form'])) {
            return $creds;
        }

        if ( $creds ) {
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
                    return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
				}else{
                    return $creds;
                }
			} elseif ( (isset( $_POST['wp-submit']) || isset( $_POST['login'] )) && ! isset( $_POST['h-captcha-response'] ) ) {
                return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
			}
            return $creds;
        }
    }

    /**
     * Adds hcaptcha field to ld login fields.
     *
     * @param array $fields ld login fields.
     * @return array ld login fields with hcaptcha added.
     * @since 5.0.0
     */
    public function loginpress_add_hcaptcha_to_ld_register_fields( $fields ) {

        /* Cloudflare CAPTCHA Settings */
        $lp_hcaptcha = LoginPress_Hcaptcha::instance();
        $lp_hcaptcha->loginpress_hcaptcha_field();
        $lp_hcaptcha->loginpress_hcaptcha_enqueue('ld');
        
    }

    /**
	 * Enables reCAPTCHA on the ld register form.
	 *
	 * @param mixed $valid The current validation status of the form.
	 * @param array $posted_data The data submitted via the form.
	 * @param string $location The location of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
    public function loginpress_ld_register_form_hcaptcha_enable($valid, $posted_data, $location) {
        if ( ! isset($_POST['learndash-registration-form']) ) {
            return $valid;
        }
        static $executed = false;

        // Prevent duplicate execution
        if ( $executed ) {
            add_filter('learndash_registration_errors', array($this,'learndash_alert_message_captcha_callback'),10,3);
            return $valid;
        }
        $executed = true;
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
					add_filter('learndash_registration_errors_after', array( $this, 'llla_login_ldlms_hcaptcha_error_callback' ) );

					return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
				}
			} elseif ( ! isset( $_POST['h-captcha-response'] ) ) {
					add_filter('learndash_registration_errors_after', array( $this, 'llla_login_ldlms_hcaptcha_error_callback' ) );

				return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
			}
        } else {
            //return $valid;
        }
        return $valid;
    }

}

new LoginPress_Learndash_Integration();
