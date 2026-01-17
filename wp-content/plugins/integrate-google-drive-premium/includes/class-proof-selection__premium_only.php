<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

class Proof_Selections {

	/**
	 * The single instance of the class.
	 *
	 * @var Proof_Selections
	 */
	protected static $instance = null;

	private $table;

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of Proof_Selections is loaded or can be loaded.
	 *
	 * @return Proof_Selections - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	} // End instance()


	/**
	 * Constructor.
	 */
	public function __construct() {

		global $wpdb;
		$this->table = $wpdb->prefix . 'integrate_google_drive_selections';

		// Get selections
		add_action( 'wp_ajax_igd_get_selections', array( $this, 'get_selections_callback' ) );

		// Delete selection
		add_action( 'wp_ajax_igd_delete_selection', array( $this, 'delete_selection' ) );

		// IGD email required
		add_action( 'wp_ajax_igd_email_required', [ $this, 'handle_email_required' ] );
		add_action( 'wp_ajax_nopriv_igd_email_required', [ $this, 'handle_email_required' ] );

		// Send photo proof selection
		add_action( 'wp_ajax_igd_photo_proof', [ $this, 'handle_proof_submission' ] );
		add_action( 'wp_ajax_nopriv_igd_photo_proof', [ $this, 'handle_proof_submission' ] );

		// photo proof download
		add_action( 'wp_ajax_igd_proof_download', [ $this, 'proof_download' ] );
		add_action( 'wp_ajax_nopriv_igd_proof_download', [ $this, 'proof_download' ] );

	}

	public function handle_email_required() {

		Ajax::instance()->check_nonce();
		Ajax::instance()->set_current_shortcode_data();

		$shortcode_id = isset( $_POST['shortcodeId'] ) ? sanitize_text_field( $_POST['shortcodeId'] ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid email address.', 'integrate-google-drive' ) );
		}

		// Set secure cookie
		$cookie_options = [
			'expires'  => null,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Strict',
		];

		setcookie( 'igd_user_email', $email, $cookie_options );
		$_COOKIE['igd_user_email'] = $email; // Ensure it's available immediately in runtime

		$shortcode_data = Shortcode::get_shortcode_data( $shortcode_id );

		// Get selection data
		$selection = Proof_Selections::instance()->get_selections( [
			'email'        => $email,
			'shortcode_id' => $shortcode_id,
		] );

		$selection = reset( $selection );

		if ( ! empty( $selection ) ) {
			$shortcode_data['selection'] = $selection;
		}

		$content = Shortcode::instance()->render_shortcode( [], $shortcode_data );

		wp_send_json_success( $content );
	}

	public function handle_proof_submission() {

		Ajax::instance()->check_nonce();

		$shortcode_id = sanitize_text_field( $_REQUEST['shortcodeId'] ?? '' );

		if ( empty( $shortcode_id ) ) {
			wp_send_json_error( __( 'Invalid shortcode ID', 'integrate-google-drive' ) );
		}

		Ajax::instance()->set_current_shortcode_data();

		$selection = ! empty( $_POST['selection'] ) ? igd_sanitize_array( $_POST['selection'] ) : false;
		$message   = ! empty( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
		$selected  = ! empty( $_POST['selected'] ) ? igd_sanitize_array( $_POST['selected'] ) : [];

		// Prepare user info
		$user = [
			'id'    => '',
			'name'  => '',
			'email' => '',
		];

		if ( ! empty( $selection['user'] ) ) {
			$user = $selection['user'];
		} elseif ( is_user_logged_in() ) {
			$current_user  = wp_get_current_user();
			$user['id']    = $current_user->ID;
			$user['name']  = $current_user->display_name;
			$user['email'] = $current_user->user_email;
		} elseif ( isset( $_COOKIE['igd_user_email'] ) ) {
			$user['email'] = sanitize_email( $_COOKIE['igd_user_email'] );
		}

		// Insert or update selection in database
		$selection_id = $this->insert_selection( $shortcode_id, $selected, $user, $message, $selection );

		$shortcode_data = Shortcode::get_current_shortcode();

		// Send notification email if enabled
		if ( ! empty( $shortcode_data['notifications'] ) && ! empty( $shortcode_data['notifications']['proofNotification'] ) ) {

			$shortcode_title = $shortcode_data['title'];
			$shortcode_type  = $shortcode_data['type'];

			$recipients        = Notifications::get_recipients( $shortcode_data['notifications'], $shortcode_data['folders'] );
			$primary_recipient = $recipients[0] ?? get_bloginfo( 'admin_email' );
			$bcc               = implode( ',', array_slice( $recipients, 1 ) );

			$subject = $selection
				? sprintf( __( 'Proof selection has been updated for the "%s" module', 'integrate-google-drive' ), $shortcode_title )
				: sprintf( __( 'New proof selection submitted for the "%s" module', 'integrate-google-drive' ), $shortcode_title );

			ob_start();
			include IGD_INCLUDES . '/views/photo-proof-email__premium_only.php';
			$content = ob_get_clean();

			$headers = [
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . sprintf( '%s <%s>', get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ) ),
			];

			if ( ! empty( $user['email'] ) ) {
				$headers[] = 'Reply-To: ' . $user['email'];
			}
			if ( ! empty( $bcc ) ) {
				$headers[] = 'Bcc: ' . $bcc;
			}

			wp_mail( $primary_recipient, $subject, $content, $headers );
		}

		wp_send_json_success();
	}

	public function proof_download() {
		// Validate nonce
		//if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'igd_photo_proof_download' ) ) {
		//	wp_send_json_error( __( 'Invalid nonce', 'integrate-google-drive' ) );
		//}

		// Validate selection ID
		$selection_id = isset( $_REQUEST['selection_id'] ) ? intval( $_REQUEST['selection_id'] ) : 0;

		if ( ! $selection_id ) {
			wp_send_json_error( __( 'No selection ID provided', 'integrate-google-drive' ) );
		}

		// Fetch selection
		$selection = $this->get_selections( [ 'id' => $selection_id ] );
		if ( empty( $selection ) ) {
			wp_send_json_error( __( 'Selection not found', 'integrate-google-drive' ) );
		}

		$selection = $selection[0];
		$files     = $selection['files'] ?? [];


		// Output headers
		$headers = [
			'Selection ID',
			'Shortcode ID',
			'User ID',
			'User Name',
			'User Email',
			'Message',
			'Date',
			'File ID',
			'File Name',
			'File Description',
			'Tag',
			'File URL',
		];

		$output = fopen( 'php://output', 'w' );
		ob_start();

		// Write header row
		fputcsv( $output, $headers );

		// Write data rows
		foreach ( $files as $index => $file ) {
			fputcsv( $output, [
				$index === 0 ? $selection['id'] : '',
				$index === 0 ? $selection['shortcode_id'] : '',
				$index === 0 ? ( $selection['user']['id'] ?? '' ) : '',
				$index === 0 ? ( $selection['user']['name'] ?? '' ) : '',
				$index === 0 ? ( $selection['user']['email'] ?? '' ) : '',
				$index === 0 ? preg_replace( "/\r\n|\n|\r/", ' ', $selection['message'] ?? '' ) : '',
				$index === 0 ? ( $selection['created_at'] ?? '' ) : '',

				$file['id'] ?? '',
				$file['name'] ?? '',
				$file['description'] ?? '',
				$file['reviewTag']['label'] ?? '',
				$file['webViewLink'] ?? '',
			] );
		}

		// Finish output
		$content = ob_get_clean();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="selection-' . $selection_id . '.csv"' );
		header( 'Content-Length: ' . strlen( $content ) );

		echo $content;
		exit;
	}


	/**
	 * Delete selection.
	 */
	public function delete_selection() {
		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( 'No ID provided' );
		}

		global $wpdb;

		$id = intval( $_POST['id'] );

		$deleted = $wpdb->delete(
			$this->table,
			array(
				'id' => $id,
			),
			array(
				'%d',
			)
		);

		if ( $deleted ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( 'Failed to delete selection' );
		}
	}

	/**
	 * Ajax proof selection.
	 */
	public function insert_selection( $shortcode_id, $selected, $user, $message, $selection = false ) {

		$files = [];

		foreach ( $selected as $file ) {
			$files[] = [
				'id'            => $file['id'],
				'accountId'     => $file['accountId'],
				'name'          => $file['name'],
				'description'   => $file['description'],
				'size'          => $file['size'],
				'type'          => $file['type'],
				'extension'     => $file['extension'],
				'thumbnailLink' => $file['thumbnailLink'],
				'iconLink'      => $file['iconLink'],
				'webViewLink'   => $file['webViewLink'],
				'reviewTag'     => $file['reviewTag'] ?? '',
				'permissions'   => $file['permissions'],
			];
		}

		global $wpdb;

		$page = isset( $_REQUEST['page'] ) ? esc_url( $_REQUEST['page'] ) : '';

		if ( ! empty( $selection['id'] ) ) {
			$wpdb->update(
				$this->table,
				array(
					'files'      => maybe_serialize( $files ),
					'message'    => $message,
					'page'       => $page,
					'updated_at' => current_time( 'mysql' ),
				),
				array(
					'id' => $selection['id'],
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array(
					'%d'
				)
			);
		} else {

			$wpdb->insert(
				$this->table,
				array(
					'shortcode_id' => $shortcode_id,
					'user_id'      => $user['id'],
					'email'        => $user['email'],
					'files'        => maybe_serialize( $files ),
					'message'      => $message,
					'page'         => $page,
				),
				array(
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);
		}

		return ! empty( $selection['id'] ) ? $selection['id'] : $wpdb->insert_id;
	}

	public function get_selections( $args = [] ) {
		global $wpdb;

		$where_clauses = [];
		$values        = [];

		// Map allowed columns
		$allowed_columns = [
			'id'           => '%d',
			'shortcode_id' => '%d',
			'user_id'      => '%d',
			'email'        => '%s',
			'tag'          => '%s',
		];

		foreach ( $allowed_columns as $column => $format ) {
			if ( isset( $args[ $column ] ) ) {
				$where_clauses[] = "{$column} = {$format}";
				$values[]        = $args[ $column ];
			}
		}

		$query = "SELECT * FROM {$this->table} ";

		$where_sql = implode( ' AND ', $where_clauses );

		if ( ! empty( $where_sql ) ) {
			$query .= "WHERE {$where_sql} ";
		}

		$selections = ! empty( $values )
			? $wpdb->get_results( $wpdb->prepare( $query, ...$values ), ARRAY_A )
			: $wpdb->get_results( $query, ARRAY_A );


		if ( empty( $selections ) ) {
			return [];
		}

		foreach ( $selections as &$selection ) {
			$selection['files'] = maybe_unserialize( $selection['files'] );

			if ( ! empty( $selection['user_id'] ) ) {
				$user = get_userdata( $selection['user_id'] );

				if ( $user ) {
					$selection['user'] = [
						'id'    => $user->ID,
						'name'  => $user->display_name,
						'email' => $user->user_email,
					];
				}
			} else {
				$selection['user'] = [
					'id'    => '',
					'name'  => '',
					'email' => $selection['email'],
				];
			}

		}

		return $selections;
	}

	public function get_selections_callback() {

		$selections = $this->get_selections();

		wp_send_json_success( $selections );
	}


	public static function view() {
		echo '<div id="proof-selections-view" class="proof-selections-view"></div>';
	}


}

Proof_Selections::instance();