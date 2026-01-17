<?php

namespace IGD;

class Notifications {

	private static $instance = null;

	private $files;
	private $type;
	private $notifications;

	public function __construct() {
		add_action( 'wp_ajax_igd_notification', [ $this, 'process_notification' ] );
		add_action( 'wp_ajax_nopriv_igd_notification', [ $this, 'process_notification' ] );

		// handle view and download notification
		add_action( 'wp_ajax_igd_send_view_download_notification', [ $this, 'process_view_download_notification' ] );
		add_action( 'wp_ajax_nopriv_igd_send_view_download_notification', [ $this, 'process_view_download_notification' ] );
	}

	public function process_view_download_notification() {
		$file_id = ! empty( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';

		if ( empty( $file_id ) ) {
			wp_send_json_error();
		}

		$account_id = ! empty( $_POST['accountId'] ) ? sanitize_text_field( $_POST['accountId'] ) : '';
		$file       = App::instance( $account_id )->get_file_by_id( $file_id );

		if ( ! $file ) {
			wp_send_json_error();
		}

		$this->files = [ $file ];

		$type       = ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$this->type = $type;

		$notification_email             = ! empty( $_POST['notificationEmail'] ) ? trim( strip_tags( ( $_POST['notificationEmail'] ) ) ) : '%admin_email%';
		$skip_current_user_notification = ! empty( $_POST['skipCurrentUserNotification'] ) ? filter_var( $_POST['skipCurrentUserNotification'], FILTER_VALIDATE_BOOLEAN ) : '';

		$this->notifications = [
			'notificationEmail'           => $notification_email,
			'skipCurrentUserNotification' => $skip_current_user_notification
		];

		$this->process_notification( true );

	}

	public function process_notification( $is_direct = false ) {

		// Sanitize inputs if not a direct notification
		if ( ! $is_direct ) {
			$this->sanitize_inputs();
		}

		// Ensure required fields are not empty
		if ( empty( $this->files ) || empty( $this->type ) || empty( $this->notifications ) ) {
			wp_send_json_error( [ 'message' => 'Required inputs are missing.' ] );

			return; // Exit to prevent further execution
		}

		// Retrieve recipient email address
		$recipients = $this->get_recipients( $this->notifications, $this->files );

		// Determine primary recipient (fallback to admin email if none found)
		$primary_recipient = $recipients[0] ?? get_bloginfo( 'admin_email' );

		// Extract BCC recipients (exclude the primary recipient)
		$bcc_recipients = array_slice( $recipients, 1 );
		$bcc            = ! empty( $bcc_recipients ) ? implode( ',', $bcc_recipients ) : '';

		// Construct email subject
		$subject = sprintf( '%s | %s', get_bloginfo(), $this->construct_subject() );

		// Prepare the email message
		$message = $this->prepare_message();

		// Set up email headers
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . sprintf( '%s <%s>', get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ) )
		];

		// Include BCC header if there are BCC recipients
		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . $bcc;
		}

		// Send the email
		if ( $this->send_email( $primary_recipient, $subject, $message, $headers ) ) {
			wp_send_json_success( [ 'message' => 'Notification sent successfully.' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to send notification.' ] );
		}
	}

	private function sanitize_inputs() {
		$files         = ! empty( $_POST['files'] ) ? igd_sanitize_array( $_POST['files'] ) : [];
		$type          = ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$notifications = ! empty( $_POST['notifications'] ) ? igd_sanitize_array( $_POST['notifications'] ) : [];

		$this->files         = $files;
		$this->type          = $type;
		$this->notifications = $notifications;
	}

	public static function get_recipients( $notifications, $files = [] ) {
		if ( empty( $notifications['notificationEmail'] ) ) {
			return [];
		}

		$recipient_str = $notifications['notificationEmail'];
		$recipient_arr = array_map( 'trim', explode( ',', $recipient_str ) );

		// Replace placeholders
		$recipient_arr = self::replace_email_placeholders( $recipient_arr, $files );

		// Optionally skip current user
		if ( ! empty( $notifications['skipCurrentUserNotification'] ) ) {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$user_email    = get_user_by( 'id', $user_id )->user_email ?? '';
				$recipient_arr = array_diff( $recipient_arr, [ $user_email ] );
			}
		}

		return array_unique( $recipient_arr );
	}

	private static function replace_email_placeholders( $recipient_arr, $files = [] ) {
		$user_id = get_current_user_id();

		// Replace %admin_email%
		if ( ( $key = array_search( '%admin_email%', $recipient_arr ) ) !== false ) {
			unset( $recipient_arr[ $key ] );
			$recipient_arr[] = get_option( 'admin_email' );
		}

		// Replace %user_email%
		if ( ( $key = array_search( '%user_email%', $recipient_arr ) ) !== false ) {
			unset( $recipient_arr[ $key ] );
			if ( $user_id ) {
				$recipient_arr[] = get_user_by( 'id', $user_id )->user_email ?? '';
			}
		}

		// Replace %linked_user_email%
		if ( ( $key = array_search( '%linked_user_email%', $recipient_arr ) ) !== false ) {
			unset( $recipient_arr[ $key ] );
			$recipient_arr = array_merge( $recipient_arr, self::get_linked_user_emails( $files ) );
		}

		return $recipient_arr;
	}

	private static function get_linked_user_emails( $files ) {
		$linked_emails     = [];
		$uniqueParents     = [];
		$uniqueParentFiles = [];

		foreach ( $files as $file ) {
			if ( ! empty( $file['parents'][0] ) ) {
				$parent = $file['parents'][0];
				if ( ! isset( $uniqueParents[ $parent ] ) ) {
					$uniqueParents[ $parent ] = true;
					$uniqueParentFiles[]      = $file;
				}
			}
		}

		foreach ( $uniqueParentFiles as $file ) {
			$parent_folders = igd_get_all_parent_folders( $file );

			$linked_users = get_users( [
				'meta_key'     => 'igd_folders',
				'meta_compare' => 'EXISTS',
			] );

			foreach ( $linked_users as $linked_user ) {
				$folders = maybe_unserialize( get_user_meta( $linked_user->ID, 'igd_folders', true ) );

				if ( is_array( $folders ) ) {
					foreach ( $folders as $folder ) {
						if ( $folder['id'] === $file['id'] || self::is_parent_folder_assigned( $folder, $parent_folders ) ) {
							$linked_emails[] = $linked_user->user_email;
							break;
						}
					}
				}
			}
		}

		return $linked_emails;
	}

	private static function is_parent_folder_assigned( $folder, $parent_folders ) {
		foreach ( $parent_folders as $parent_folder ) {
			if ( $folder['id'] === $parent_folder['id'] ) {
				return true;
			}
		}

		return false;
	}

	private function get_user_name() {
		$user_id = get_current_user_id();

		return $user_id ? get_user_by( 'id', $user_id )->user_login : __( 'An anonymous user', 'integrate-google-drive' );
	}

	private function get_file_name() {
		return ( count( $this->files ) == 1 ) ? $this->files[0]['name'] : __( 'file', 'integrate-google-drive' );
	}

	private function construct_subject() {
		// get username
		$user_name = $this->get_user_name();

		$ext = $this->get_file_name();

		switch ( $this->type ) {

			case 'upload':
				$upload_folder_id   = ! empty( $this->files[0]['parents'] ) ? $this->files[0]['parents'][0] : '';
				$account_id         = $this->files[0]['accountId'];
				$upload_folder_name = ! empty( $upload_folder_id ) ? App::instance( $account_id )->get_file_by_id( $upload_folder_id )['name'] : '';

				if ( count( $this->files ) > 1 ) {
					/* translators: %1$s: number of files, %2$s: folder name */
					$ext = sprintf( __( '%1$s files to %2$s.', 'integrate-google-drive' ), count( $this->files ), $upload_folder_name );
				} else {
					/* translators: %1$s: file name, %2$s: folder name */
					$ext = sprintf( __( '%1$s file to %2$s.', 'integrate-google-drive' ), $this->get_file_name(), $upload_folder_name );
				}

				/* translators: %1$s: user name, %2$s: file name */

				return sprintf( __( 'User %1$s uploaded %2$s', 'integrate-google-drive' ), $user_name, $ext );

			case 'delete':

				if ( count( $this->files ) > 1 ) {
					/* translators: %1$s: number of files */
					$ext = sprintf( __( '(%1$s) file(s)', 'integrate-google-drive' ), count( $this->files ) );
				}

				/* translators: %1$s: user name, %2$s: file name */

				return sprintf( __( 'User %1$s deleted %2$s', 'integrate-google-drive' ), $user_name, $ext );

			case 'search':
				$ext = sanitize_text_field( $_POST['keyword'] );

				/* translators: %1$s: user name, %2$s: file name */

				return sprintf( __( 'User %1$s searched for %2$s', 'integrate-google-drive' ), $user_name, $ext );

			case 'play':

				/* translators: %1$s: user name, %2$s: file name */
				return sprintf( __( 'User %1$s played %2$s', 'integrate-google-drive' ), $user_name, $ext );

			case 'view':

				/* translators: %1$s: user name, %2$s: file name */
				return sprintf( __( 'User %1$s viewed %2$s', 'integrate-google-drive' ), $user_name, $ext );

			default:
				if ( count( $this->files ) > 1 ) {
					/* translators: %1$s: number of files */
					$ext = sprintf( __( '(%1$s) file(s)', 'integrate-google-drive' ), count( $this->files ) );
				}

				/* translators: %1$s: user name, %2$s: file name */

				return sprintf( __( 'User %1$s downloaded %2$s', 'integrate-google-drive' ), $user_name, $ext );
		}
	}

	private function prepare_message() {
		ob_start();

		$subject   = get_bloginfo() . ' | ' . $this->construct_subject();
		$user_name = $this->get_user_name();
		$type      = $this->type;
		$files     = $this->files;

		include_once IGD_INCLUDES . '/views/notification-email__premium_only.php';

		return ob_get_clean();
	}

	private function send_email( $to, $subject, $message, $headers ) {
		if ( ! empty( $to ) ) {
			return wp_mail( $to, $subject, $message, $headers );
		}

		return false;
	}

	public static function view() { ?>
        <div id="igd-notifications" class="igd-notifications"></div>
	<?php }

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}

Notifications::instance();