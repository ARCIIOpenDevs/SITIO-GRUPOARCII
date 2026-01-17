<?php
/**
 * Customizer Sections for LoginPress Pro.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class LoginPress_Pro_Entities {

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Hook into actions and filters
	 *
	 * @since  1.0.0
	 */
	public function hooks() {

		add_action( 'customize_register', array( $this, 'customize_pro_login_panel' ) );
	}

	/**
	 * Register plugin settings Panel in WP Customizer
	 *
	 * @param Obj $wp_customize The Customizer object.
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function customize_pro_login_panel( $wp_customize ) {

		$loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
		$captchas_enabled = isset( $loginpress_captcha_settings['enable_captchas'] ) ? $loginpress_captcha_settings['enable_captchas'] : 'off';
		if ( $captchas_enabled !== 'off' ) {
			$captchas_type = isset( $loginpress_captcha_settings['captchas_type'] ) ? $loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
		}

		/**
		 * Section for Google reCAPTCHA
		 *
		 * @since 1.0.0
		 */
		$wp_customize->add_section(
			'customize_recaptcha',
			array(
				'title'    => __( 'CAPTCHAs', 'loginpress-pro' ),
				'priority' => 24,
				'panel'    => 'loginpress_panel',
			)
		);

		if ( $captchas_enabled !== 'off' && isset( $captchas_type ) && $captchas_type === 'type_recaptcha' ) {
			$wp_customize->add_setting(
				'loginpress_customization[recaptcha_error_message]',
				array(
					'default'           => __( '<strong>ERROR:</strong> Please verify reCAPTCHA', 'loginpress-pro' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[recaptcha_error_message]',
				array(
					'label'    => __( 'reCAPTCHA Error Message:', 'loginpress-pro' ),
					'section'  => 'customize_recaptcha',
					'priority' => 5,
					'settings' => 'loginpress_customization[recaptcha_error_message]',
				)
			);
			/**
			 * Select Scale Size.
			 */
			$wp_customize->add_setting(
				'loginpress_customization[recaptcha_size]',
				array(
					'default'    => '1',
					'capability' => 'edit_theme_options',
					'transport'  => 'postMessage',
					'type'       => 'option',
				)
			);
			$wp_customize->add_control(
				'loginpress_customization[recaptcha_size]',
				array(
					'label'       => __( 'Select reCAPTCHA size:', 'loginpress-pro' ),
					'section'     => 'customize_recaptcha',
					'priority'    => 10,
					'settings'    => 'loginpress_customization[recaptcha_size]',
					'type'        => 'select',
					'description' => __( 'Size is only apply on "V2-I\'m not robot" reCAPTCHA type.', 'loginpress-pro' ),
					'choices'     => array(
						'.1' => '10%',
						'.2' => '20%',
						'.3' => '30%',
						'.4' => '40%',
						'.5' => '50%',
						'.6' => '60%',
						'.7' => '70%',
						'.8' => '80%',
						'.9' => '90%',
						'1'  => '100%',
					),
				)
			);
		} elseif ( $captchas_enabled !== 'off' && isset( $captchas_type ) && $captchas_type === 'type_hcaptcha') {
			$wp_customize->add_setting(
				'loginpress_customization[hcaptcha_error_message]',
				array(
					'default'           => __( '<strong>ERROR:</strong> Please verify hCaptcha', 'loginpress-pro' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[hcaptcha_error_message]',
				array(
					'label'    => __( 'hCaptcha Error Message:', 'loginpress-pro' ),
					'section'  => 'customize_recaptcha',
					'priority' => 5,
					'settings' => 'loginpress_customization[hcaptcha_error_message]',
				)
			);

		} elseif ( $captchas_enabled !== 'off' && isset( $captchas_type ) && $captchas_type === 'type_cloudflare') {
			$wp_customize->add_setting(
				'loginpress_customization[turnstile_error_message]',
				array(
					'default'           => __( '<strong>ERROR:</strong> Captcha verification failed. Please try again.', 'loginpress-pro' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[turnstile_error_message]',
				array(
					'label'    => __( 'CloudFlare Error Message:', 'loginpress-pro' ),
					'section'  => 'customize_recaptcha',
					'priority' => 5,
					'settings' => 'loginpress_customization[turnstile_error_message]',
				)
			);

		} else {
			$wp_customize->add_setting( "loginpress_customization[captcha_disabled_notice]", array(
				'type'			=> 'option',
				'capability'	=> 'manage_options',
				'transport'		=> 'postMessage'
			) );
	
			$wp_customize->add_control( new LoginPress_Group_Control( $wp_customize, "loginpress_customization[captcha_disabled_notice]", array(
				'settings'	=> "loginpress_customization[captcha_disabled_notice]",
				'label'		=> __( 'Captcha not Enabled', 'loginpress-pro' ),
				'section'	=> 'customize_recaptcha',
				'type'		=> 'group',
				'info_text'	=> __( 'Please Enable any of the provided captchas to change the Captcha Error Message', 'loginpress-pro' ),
				'priority'	=> 5,
			) ) );
		}

		/**
		 * Section for Google Fonts
		 *
		 * @since 2.0.0
		 */
		$wp_customize->add_section(
			'lpcustomize_google_font',
			array(
				'title'    => __( 'Google Fonts', 'loginpress-pro' ),
				'priority' => 2,
				'panel'    => 'loginpress_panel',
			)
		);

		// Add a Google Font control.
		require_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-google-font.php';
		$wp_customize->add_setting(
			'loginpress_customization[google_font]',
			array(
				'default'    => '',
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);
		$wp_customize->add_control(
			new LoginPress_Google_Fonts(
				$wp_customize,
				'loginpress_customization[google_font]',
				array(
					'label'    => __( 'Select Google Font', 'loginpress-pro' ),
					'section'  => 'lpcustomize_google_font',
					'settings' => 'loginpress_customization[google_font]',
					'priority' => 20,
				)
			)
		);

		/**
		 * Setting for reset password "Text and Hint" since 2.0.3.
		 */
		// $wp_customize->add_setting( "loginpress_customization[reset_hint_message]", array(
		// 'default'				=> __( 'Enter your new password below.', 'loginpress-pro' ),
		// 'capability'			    => 'manage_options',
		// 'type'					=> 'option',
		// 'transport'			    => 'postMessage'
		// ) );
		//
		// $wp_customize->add_control( "loginpress_customization[reset_hint_message]", array(
		// 'label'				    => __( 'Reset Password Message:', 'loginpress-pro' ),
		// 'section'				=> 'section_welcome',
		// 'priority'				=> 30,
		// 'settings'				=> "loginpress_customization[reset_hint_message]",
		// ) );

		$wp_customize->add_setting(
			'loginpress_customization[reset_hint_text]',
			array(
				'default'           => __( 'Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).', 'loginpress-pro' ),
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'type'              => 'option',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[reset_hint_text]',
			array(
				'label'       => __( 'Reset Password Hint:', 'loginpress-pro' ),
				'section'     => 'section_welcome',
				'priority'    => 32,
				'settings'    => 'loginpress_customization[reset_hint_text]',
				'type'        => 'textarea',
				'description' => __( 'You can change the Hint text that is comes on reset password page.', 'loginpress-pro' ),
			)
		);
	}
}

