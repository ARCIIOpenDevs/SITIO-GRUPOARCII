<?php
/**
 * Class LoginPress_Widget
 *
 * @package LoginPress Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * LoginPress_Widget class.
 *
 * @extends WP_Widget
 */
class LoginPress_Widget extends WP_Widget {

	/**
	 * Instance variable
	 *
	 * @var string
	 */
	private $instance = '';

	/**
	 * User data
	 *
	 * @var string
	 */
	private $user = null;

	/**
	 * Widget Choice
	 *
	 * @var array
	 */
	private $choices = array();

	/**
	 * Constructor
	 */
	public function __construct() {

		/* Widget settings. */
		$widget_meta = array(
			'classname'   => 'loginpress-login-widget',
			'description' => __( 'Displays a login widget in the sidebar.', 'loginpress-pro' ),
		);

		/* Create the widget. */
		parent::__construct( 'loginpress-login-widget', __( 'LoginPress: Login Widget (Classic)', 'loginpress-pro' ), $widget_meta );

		add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_widget_color_picker' ) );

	}

	/**
	 * Checks whether the widget is currently active.
	 * @since 5.0.0
	 * @return bool True if the widget is active, false otherwise.
	 */
	public function loginpress_widget_active() {
		return is_active_widget( false, false, $this->id_base );
	}

	/**
	 * [loginpress_widget_color_picker]
	 *
	 * @param int $page the page ID.
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function loginpress_widget_color_picker( $page ) {

		if ( 'widgets.php' === $page ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}
	}

	/**
	 * [widget_choices choices for WP_Widget->form() && WP_Widget->update().]
	 *
	 * @since 3.0.0
	 * @version 4.0.0
	 */
	public function widget_choices() {
		// Define choices for widget.
		$this->choices = array(
			'logged_out_title'        => array(
				'label'   => __( 'Logged-out Title', 'loginpress-pro' ),
				'default' => __( 'Login', 'loginpress-pro' ),
				'type'    => 'text',
			),
			'logged_out_links'        => array(
				'label'   => __( 'Logged-out Links (Text | href) e.g., Dashboard | %admin_url%', 'loginpress-pro' ),
				'default' => '',
				'type'    => 'textarea',
			),
			'show_lost_password_link' => array(
				'label'   => __( 'Show lost password link', 'loginpress-pro' ),
				'default' => 1,
				'type'    => 'checkbox',
			),
			'lost_password_text'      => array(
				'label'   => __( 'Lost password text', 'loginpress-pro' ),
				'default' => __( 'Lost your password?', 'loginpress-pro' ),
				'type'    => 'text',
			),
			'show_register_link'      => array(
				'label'       => __( 'Show register link', 'loginpress-pro' ),
				'default'     => 1,
				/* Translators: Register String */
				'description' => sprintf( __( '%1$sAnyone can register%2$s must be enabled.', 'loginpress-pro' ), '<a href="' . admin_url( 'options-general.php' ) . '">', '</a>' ),
				'type'        => 'checkbox',
			),
			'registeration_text'      => array(
				'label'   => __( 'Register text', 'loginpress-pro' ),
				'default' => __( 'Register', 'loginpress-pro' ),
				'type'    => 'text',
			),
			'show_rememberme'         => array(
				'label'   => __( 'Show "Remember me" checkbox', 'loginpress-pro' ),
				'default' => 1,
				'type'    => 'checkbox',
			),
			'login_redirect_url'      => array(
				'label'       => __( 'Login Redirect URL', 'loginpress-pro' ),
				'default'     => '',
				'type'        => 'text',
				'placeholder' => __( 'Current page URL', 'loginpress-pro' ),
			),
			'hr-1'                    => array(
				'type'    => 'hr',
				'default' => '',
			),
			'logged_in_title'         => array(
				'label'   => __( 'Logged-in title', 'loginpress-pro' ),
				/* Translators: Welcome String */
				'default' => __( 'Welcome %username%', 'loginpress-pro' ),
				'type'    => 'text',
			),
			'logged_in_links'         => array(
				'label'       => __( 'Logged-in Links (Text | HREF | Capability) e.g., Logout | %logout_url%', 'loginpress-pro' ),
				/* Translators: Register String */
				'description' => sprintf( __( '%1$sCapability%2$s (optional) refers to the type of user who can view the link.', 'loginpress-pro' ), '<a href="http://codex.wordpress.org/Roles_and_Capabilities">', '</a>' ),
				'default'     => __( "Dashboard | %admin_url%\nProfile | %admin_url%/profile.php\nLogout | %logout_url%", 'loginpress-pro' ),
				'type'        => 'textarea',
			),
			'show_avatar'             => array(
				'label'   => __( 'Show logged-in user avatar', 'loginpress-pro' ),
				'default' => 1,
				'type'    => 'checkbox',
			),
			'avatar_size'             => array(
				'label'   => __( 'Logged-in user avatar size', 'loginpress-pro' ),
				'default' => 38,
				'type'    => 'number',
			),
			'logout_redirect_url'     => array(
				'label'       => __( 'Logout Redirect URL', 'loginpress-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Current page URL', 'loginpress-pro' ),
				'default'     => '',
			),
			'hr-2'                    => array(
				'type'    => 'hr',
				'default' => '',
			),
			'error_short_note'        => array(
				'label'       => __( 'Error Messages', 'loginpress-pro' ),
				/* Translators: Error Message String */
				'description' => sprintf( __( 'You can change the Error messages from %1$sLoginPress Customizer%2$s', 'loginpress-pro' ), '<a href="' . admin_url( 'admin.php?page=loginpress' ) . '">', '</a>' ),
				'type'        => 'note',
				'default'     => '',
			),
			'error_bg_color'          => array(
				'label'   => __( 'Error Background Color', 'loginpress-pro' ),
				'type'    => 'color',
				'default' => '#fbb1b7',
			),
			'error_text_color'        => array(
				'label'   => __( 'Error Text Color', 'loginpress-pro' ),
				'type'    => 'color',
				'default' => '#ae121e',
			),
		);
	}

	/**
	 * Patch_string function.
	 *
	 * @param mixed $text the string.
	 *
	 * @since 3.0.0
	 */
	public function patch_string( $text ) {

		if ( $this->user ) {
			$text = str_replace(
				array( '%username%', '%userid%', '%firstname%', '%lastname%', '%name%', '%avatar%' ),
				array(
					ucwords( $this->user->display_name ),
					$this->user->ID,
					$this->user->first_name,
					$this->user->last_name,
					trim( $this->user->first_name . ' ' . $this->user->last_name ),
					get_avatar( $this->user->ID, 38 ),
				),
				$text
			);

		}

		$logout_redirect = wp_logout_url( empty( $this->instance['logout_redirect_url'] ) ? $this->redirect_url() : $this->instance['logout_redirect_url'] );

		$text = str_replace(
			array( '%admin_url%', '%logout_url%' ),
			array( untrailingslashit( admin_url() ), $logout_redirect ),
			$text
		);

		$text = do_shortcode( $text );

		return $text;
	}


	/**
	 * LoginPress_widget_link function.
	 *
	 * @param string $show status of user.
	 * @param array  $links links to be shown.
	 *
	 * @return void
	 */
	public function loginpress_widget_link( $show = 'logged_in', $links = array() ) {

		if ( ! is_array( $links ) ) {

			$raw_links = array_map( 'trim', explode( "\n", $links ) );
			$links     = array();
			foreach ( $raw_links as $link ) {

				$link     = array_map( 'trim', explode( '|', $link ) );
				$link_cap = '';

				if ( count( $link ) === 3 ) {

					list( $link_text, $link_href, $link_cap ) = $link;
				} elseif ( count( $link ) === 2 ) {

					list( $link_text, $link_href ) = $link;
				} else {

					continue;
				}

				// Check capability.
				if ( ! empty( $link_cap ) ) {

					if ( ! current_user_can( strtolower( $link_cap ) ) ) {

						continue;
					}
				}

				$links[ sanitize_title( $link_text ) ] = array(
					'text' => $link_text,
					'href' => $link_href,
				);
			}
		}

		if ( 'logged_out' === $show ) {

			if ( get_option( 'users_can_register' ) && ! empty( $this->instance['show_register_link'] ) && '1' === $this->instance['show_register_link'] ) {

				$register_text = isset( $this->instance['registeration_text'] ) ? $this->instance['registeration_text'] : __( 'Register', 'loginpress-pro' );

				if ( ! is_multisite() ) {

					$links['register'] = array(
						'text' => $register_text,
						'href' => apply_filters( 'loginpress_widget_register_url', site_url( 'wp-login.php?action=register', 'login' ) ),
					);
				} else {

					$links['register'] = array(
						'text' => $register_text,
						'href' => apply_filters( 'loginpress_widget_register_url', site_url( 'wp-signup.php', 'login' ) ),
					);
				}
			} // endif; show_register_link.
			if ( ! empty( $this->instance['show_lost_password_link'] ) && '1' === $this->instance['show_lost_password_link'] ) {

				$lost_link_text         = isset( $this->instance['lost_password_text'] ) ? $this->instance['lost_password_text'] : __( 'Lost your password?', 'loginpress-pro' );
				$links['lost_password'] = array(
					'text' => $lost_link_text,
					'href' => apply_filters( 'loginpress_widget_lost_password_url', wp_lostpassword_url() ),
				);
			} // endif; show_lost_password_link.
		} // endif; logged_out.

		if ( ! empty( $links ) && is_array( $links ) && count( $links ) > 0 ) {

			echo '<ul class="pagenav loginpress_widget_links">';
			foreach ( $links as $id => $link ) {

				echo '<li class="' . esc_attr( $id ) . '-link"><a href="' . esc_url( $this->patch_string( $link['href'] ) ) . '">' . wp_kses_post( $this->patch_string( $link['text'] ) ) . '</a></li>';
			}
			echo '</ul>';
		}
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args Widget Args.
	 * @param array $instance Widget Instance.
	 */
	public function widget( $args, $instance ) {

		// Record $instance.
		$this->instance = $instance;

		// Get user.
		if ( is_user_logged_in() ) {
			$this->user = get_user_by( 'id', get_current_user_id() );
		}

		$defaults = array(
			/* Translators: Welcome user */
			'logged_in_title'  => ! empty( $instance['logged_in_title'] ) ? $instance['logged_in_title'] : __( 'Welcome %username%', 'loginpress-pro' ),
			'logged_out_title' => ! empty( $instance['logged_out_title'] ) ? $instance['logged_out_title'] : __( 'Login', 'loginpress-pro' ),
			'show_avatar'      => isset( $instance['show_avatar'] ) ? $instance['show_avatar'] : 1,
			'avatar_size'      => isset( $instance['avatar_size'] ) ? $instance['avatar_size'] : 38,
			'logged_in_links'  => ! empty( $instance['logged_in_links'] ) ? $instance['logged_in_links'] : array(),
			'logged_out_links' => ! empty( $instance['logged_out_links'] ) ? $instance['logged_out_links'] : array(),
		);

		$args = array_merge( $defaults, $args );

		extract( $args ); // @codingStandardsIgnoreLine.

		echo wp_kses_post( $before_widget );

		// Logged in user.
		if ( is_user_logged_in() ) {

			$logged_in_title = $this->replace_tags( $logged_in_title );

			if ( $logged_in_title ) {
				echo wp_kses_post( $before_title . $logged_in_title . $after_title );
			}

			if ( '1' === $show_avatar ) {
				echo '<div class="avatar_container">' . get_avatar( $this->user->ID, $args['avatar_size'] ) . '</div>';
			}

			$this->loginpress_widget_link( 'logged_in', $logged_in_links );

			// Logged out user.
		} else {

			$logged_out_title = $this->replace_tags( $logged_out_title );

			if ( $logged_out_title ) {
				echo wp_kses_post( $before_title . $logged_out_title . $after_title );
			}

			$this->loginpress_login_form( $instance );

			 // Render Register Form (hidden by default)
			 $this->loginpress_register_form( $instance );
			 $this->loginpress_lost_password_form($instance);

			 // Toggle links to switch between login and register
			 echo '<ul class="pagenav loginpress_widget_links">';
			 echo '<li class="login-link" style="display:none;"><a href="#" id="show_login">' . esc_html('Login') . '</a></li>';
			 echo '</ul>';

			$this->loginpress_widget_link( 'logged_out', $logged_out_links );
		}

		echo wp_kses_post( $after_widget );
	}

	/**
	 * Replace_tags function.
	 *
	 * @param mixed $text the tag name.
	 *
	 * @return mixed $text
	 */
	public function replace_tags( $text ) {
		if ( $this->user ) {
			$text = str_replace(
				array( '%username%', '%userid%', '%firstname%', '%lastname%', '%name%' ),
				array(
					ucwords( $this->user->display_name ),
					$this->user->ID,
					$this->user->first_name,
					$this->user->last_name,
					trim( $this->user->first_name . ' ' . $this->user->last_name ),
				),
				$text
			);
		}
		return $text;
	}

	/**
	 * [loginpress_login_form description]
	 *
	 * @param array $instance the instance.
	 *
	 * @version 4.0.0
	 * @return void
	 */
	public function loginpress_login_form( $instance ) {
		// Record $instance.
		$this->instance     = $instance;
		$loginpress_setting = get_option( 'loginpress_setting' );
		$redirect           = empty( $instance['login_redirect_url'] ) ? $this->redirect_url() : $instance['login_redirect_url'];
		$show_remember_me   = ! isset( $this->instance['show_rememberme'] ) || ! empty( $this->instance['show_rememberme'] );
		$value_remember_me = isset($loginpress_setting['auto_remember_me']) && $loginpress_setting['auto_remember_me'] === 'on' ? true : false;
		$login_order        = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : '';
		if ( 'username' === $login_order ) {
			$label = __( 'Username', 'loginpress-pro' );
		} elseif ( 'email' === $login_order ) {
			$label = __( 'Email Address', 'loginpress-pro' );
		} else {
			$label = __( 'Username or Email Address', 'loginpress-pro' );
		}

		$login_form_args = array(
			'echo'           => false,
			'redirect'       => esc_url( $redirect ),
			'label_username' => $label,
			'label_password' => __( 'Password', 'loginpress-pro' ),
			'label_remember' => __( 'Remember Me', 'loginpress-pro' ),
			'label_log_in'   => __( 'Login &rarr;', 'loginpress-pro' ),
			'remember'       => $show_remember_me,
			'value_remember' => $value_remember_me,
		);
		

		 // Add reCaptcha field between the Password and Remember Me fields using the 'login_form_middle' filter.
		 add_filter( 'login_form_middle', array($this, 'loginpress_add_captcha_field') );
	
		// Output the login form.
		echo wp_login_form( $login_form_args );
	}

	/**
	 * Renders the captcha in the login form.
	 *
	 * @param array $content The widget content.
	 *
	 * @since 4.0.0
	 */

	function loginpress_add_captcha_field( $content ) {
		ob_start();
		do_action( 'loginpress_after_login_form_widget' ); // Insert reCaptcha field
		$after_login_form_middle = ob_get_clean();
		return $content . $after_login_form_middle; // Append the reCaptcha field after the password input.
	}

	/**
	 * Renders the registration form in the widget.
	 *
	 * @param array $instance The widget instance.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function loginpress_register_form( $instance ) {

		$loginpress_setting       = get_option( 'loginpress_setting' );
		$enable_password_strength = isset( $loginpress_setting['enable_pass_strength'] ) ? $loginpress_setting['enable_pass_strength'] : 'off';
		$enable_pass_strength     = isset( $loginpress_setting['enable_pass_strength_forms'] ) ? $loginpress_setting['enable_pass_strength_forms'] : 'off';
		$register                 = isset( $enable_pass_strength['register'] ) ? $enable_pass_strength['register'] : false;
		$strength_meter_enable    = isset( $loginpress_setting['password_strength_meter'] ) ? $loginpress_setting['password_strength_meter'] : 'off';
		$loginpress_setting = get_option( 'loginpress_social_logins' );
		$loginpress_general_settings = get_option('loginpress_setting');
		$output = '';
	
		// Render the registration form (hidden by default)
		$output .= '<form id="registerform" class="loginpress-register-widget" style="display:none;" action="' . esc_url(wp_registration_url()) . '" method="post">';
		$output .= '<p class="login-username">
						<label for="user_login">' . __('Username', 'loginpress-pro') . '</label>
						<input type="text" name="user_login" id="user_name" class="input">
					</p>';
		$output .= '<p class="login-email">
						<label for="user_email">' . __('Email Address', 'loginpress-pro') . '</label>
						<input type="email" name="user_email" id="user_email" class="input" >
					</p>';
		if ( isset($loginpress_general_settings['enable_reg_pass_field']) && $loginpress_general_settings['enable_reg_pass_field'] === 'on'  ) {
			$output .= '<p class="login-password">
                    <label for="user_pass">' . __('Password', 'loginpress-pro') . '</label>
                    <input type="password" name="user_pass" id="user_pass" class="input" >
                </p>';
			$output .= '<p class="login-confirm-password" >
							<label for="user_confirm_pass">' . __('Confirm Password', 'loginpress-pro') . '</label>
							<input type="password" name="user_confirm_pass" id="user_confirm_pass" class="input" >
						</p>';
		}
		
		ob_start();
		do_action('loginpress_after_reg_form_widget'); // Insert reCaptcha field
		if ( 'on' == $strength_meter_enable && $register ) {
		?>
		<span id="pass-strength-result" style=" padding: 3px 15px; width:100%; display:block;"></span> 
		<?php
		}
		if ( 'on' == $enable_password_strength && $register ) { ?>
			<p class="hint-custom-reg" style="padding: 5px;">
				<?php echo LoginPress_Password_Strength::loginpress_hint_creator(); ?>
			</p>
		<?php }
		$output .= ob_get_clean(); // Append reCaptcha field to the $output string
		$output .= '
					<input type="submit" name="wp-submit" id="wp-submit-register" class="button button-primary" style="padding-top: 10px;" value="' . esc_attr__( 'Register', 'loginpress-pro' ) . '">
				';

					 // Conditionally add the hidden div for social login links
		if ( isset( $loginpress_setting['enable_social_login_links']['register'] ) && $loginpress_setting['enable_social_login_links']['register'] ) {
			if ( class_exists( 'LoginPress_Social' ) && true === apply_filters( 'loginpress_social_widget', true ) ) {
				$loginPressWidget = new LoginPress_Login_Widget();
				$output .= $loginPressWidget->loginpress_social_login(); // Output the social login HTML
			}
		}
		$output .= '</form>';
	
		echo $output;
	}

	/**
	 * Renders the lost password form in the widget.
	 *
	 * @param array $instance The widget instance.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function loginpress_lost_password_form( $instance ) {
		$output = '';
	
		// Render the lost password form (hidden by default).
		$output .= '<form id="lostpasswordform" class="loginpress-lost-password-widget" style="display:none;" action="' . esc_url( wp_lostpassword_url() ) . '" method="post">';
		
		// Form title and description.
		$output .= '<p>' . __('Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'loginpress-pro') . '</p>';
		
		// Username or email input field.
		$output .= '<p class="login-username-or-email">
						<label for="user_login">' . __('Username or Email Address', 'loginpress-pro') . '</label>
						<input type="text" name="user_login" id="username" class="input">
					</p>';
		ob_start();
		do_action('loginpress_after_lost_password_form_widget'); // Insert reCaptcha field.
		$output .= ob_get_clean(); // Append reCaptcha field to the $output string.
		// Submit button for password reset
		$output .= '<p class="login-submit" style="padding-top: 10px;">
                <input type="submit" name="wp-submit" id="wp-submit-lostpass" class="button button-primary" value="' . esc_attr__( 'Get New Password', 'loginpress-pro' ) . '">
            </p>';

		
		$output .= '</form>';
	
		echo $output;
	}

	/**
	 * Redirect_url function
	 *
	 * @return url the page URL
	 */
	private function redirect_url() {

		$page_url  = force_ssl_admin() ? 'https://' : 'http://';
		$page_url .= isset( $_SERVER['HTTP_HOST'] ) ? esc_attr( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // @codingStandardsIgnoreLine.
		$page_url .= isset( $_SERVER['REQUEST_URI'] ) ? esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // @codingStandardsIgnoreLine.

		return esc_url_raw( $page_url );
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array $instance
	 */
	public function update( $new_instance, $old_instance ) {
		$this->widget_choices();

		foreach ( $this->choices as $name => $option ) {
			if ( 'hr' === $option['type'] ) {
				continue;
			}

			if ( 'error_short_note' === $name ) {
				continue;
			}

			$instance[ $name ] = wp_strip_all_tags( stripslashes( $new_instance[ $name ] ) );
		}
		return $instance;
	}

	/**
	 * Check for editor screen.
	 *
	 * @since 3.0.0
	 * @return boolean
	 */
	private function is_editor_screen() {
		if ( isset( $_GET['fl_builder'] ) ) { // @codingStandardsIgnoreLine.
			return true;
		}

		return false;
	}


	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance the form instance.
	 * @version 3.0.0
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$this->widget_choices();

		foreach ( $this->choices as $name => $option ) {

			if ( 'hr' === $option['type'] ) {
				echo '<hr style="border: 1px solid #ddd; margin: 1em 0" />';
				continue;
			}

			if ( ! isset( $instance[ $name ] ) ) {
				$instance[ $name ] = $option['default'];
			}

			if ( empty( $option['placeholder'] ) ) {
				$option['placeholder'] = '';
			}

			echo '<p>';

			switch ( $option['type'] ) {

				case 'text':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>" value="<?php echo esc_attr( $instance[ $name ] ); ?>" />
					<?php
					break;

				case 'number':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<input type="number" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>" value="<?php echo esc_attr( $instance[ $name ] ); ?>" />
					<?php
					break;

				case 'checkbox':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" <?php checked( $instance[ $name ], 1 ); ?> value="1" /> <?php echo wp_kses_post( $option['label'] ); ?></label>
					<?php
					break;

				case 'textarea':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<textarea class="widefat" cols="20" rows="3" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>"><?php echo esc_textarea( $instance[ $name ] ); ?></textarea>
					<?php
					break;

				case 'note':
					echo '<label for="' . esc_attr( $this->get_field_id( $name ) ) . '">' . wp_kses_post( $option['label'] ) . ':</label>';
					break;

				case 'color':
					if ( ! $this->is_editor_screen() ) :
						?>
						<script type="text/javascript">
							(function($) {
								$(function() {
									$(document).ready(function($) {
										$('.color-picker').wpColorPicker();
										$('.color-picker').on('focus', function(){
											var parent = $(this).parent();
											parent.find('.wp-color-result').click();
										});
									});
								});
							})(jQuery);
						</script>
					<?php endif; ?>

					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" style="display:block;"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<input class="widefat color-picker" type="text" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" value="<?php echo esc_attr( $instance[ $name ] ); ?>" />
					<?php
					break;
			}

			if ( ! empty( $option['description'] ) ) {
				echo '<span class="description" style="display:block; padding-top:.25em">' . wp_kses_post( $option['description'] ) . '</span>';
			}

			echo '</p>';
		}
	}
}
