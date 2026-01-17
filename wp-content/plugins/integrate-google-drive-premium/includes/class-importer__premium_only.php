<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

class Importer {
	/**
	 * @var null
	 */
	protected static $instance = null;

	public function __construct() {

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			include_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		add_action( 'wp_ajax_igd_import_media', array( $this, 'import' ) );
		add_action( 'wp_ajax_igd_cancel_import', array( $this, 'cancel_import' ) );

	}

	public function import() {

		if ( empty( $_POST['file'] ) ) {
			wp_send_json_error( [ 'error' => 'No file to import', ] );
		}

		$file = igd_sanitize_array( $_POST['file'] );

		$this->download_and_store_file_in_chunks( $file );

		wp_send_json_success( [
			'success' => true,
			'file'    => $file,
		] );

	}

	public function cancel_import() {

		if ( empty( $_POST['file'] ) ) {
			wp_send_json_error( [ 'error' => 'No file specified for cancellation' ] );
		}

		$file       = igd_sanitize_array( $_POST['file'] );
		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['path'] . '/' . sanitize_file_name( $file['name'] );

		// Try to find attachment ID by file path or GUID
		global $wpdb;
		$attachment_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
			$upload_dir['url'] . '/' . sanitize_file_name( $file['name'] )
		) );

		if ( $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}

		// Just in case, delete the physical file manually
		if ( file_exists( $file_path ) ) {
			( unlink( $file_path ) );
		} else {
			error_log( 'File does not exist.' );
		}

		// Set cancellation flag (optional, for import stopping)
		set_transient( 'igd_import_cancel_' . $file['id'], true, 5 * MINUTE_IN_SECONDS );

		wp_send_json_success();
	}


	public function download_and_store_file_in_chunks( $file ) {
		$upload_dir = wp_upload_dir();

		$id         = $file['id'];
		$account_id = $file['accountId'];
		$name       = sanitize_file_name( $file['name'] );
		$type       = $file['type'];
		$extension  = igd_mime_to_ext( $type );

		// Check if the name already contains the extension
		if ( substr( strtolower( $name ), - strlen( $extension ) ) !== strtolower( $extension ) ) {
			$name .= '.' . $extension;
		}

		$file_path    = $upload_dir['path'] . '/' . $name;
		$download_url = home_url( "/?igd_download=1&id=$id&accountId=$account_id&ignore_limit=1" );

		// Set a stream context with a lower timeout and larger buffer
		$context     = stream_context_create( [ 'http' => [ 'timeout' => 60 ] ] );
		$source      = fopen( $download_url, 'rb', false, $context );
		$destination = fopen( $file_path, 'wb' );

		if ( ! $source || ! $destination ) {
			return false;
		}

		// At the start of import, optionally allow script to stop on disconnect
		ignore_user_abort( false );

		// Read file in chunks and write them immediately to avoid memory exhaustion
		while ( ! feof( $source ) ) {

			// Check cancellation flag
			if ( get_transient( 'igd_import_cancel_' . $file['id'] ) ) {
				// Close streams
				fclose( $source );
				fclose( $destination );

				// Optionally delete partial file
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}

				// Delete the transient so it doesn't persist
				delete_transient( 'igd_import_cancel_' . $file['id'] );

				// Stop execution and optionally send error or return false
				wp_send_json_error( [ 'error' => 'Import cancelled by user.' ] );
				exit; // Important to stop script here
			}

			$chunk = fread( $source, $this->get_chunk_size() );
			fwrite( $destination, $chunk );
		}

		fclose( $source );
		fclose( $destination );

		// Create an attachment for the file
		$file_type = wp_check_filetype( basename( $file_path ) );

		$attachment = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $file_path ),
			'post_mime_type' => $file_type['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $file_path );

		// You may need to include this file for the following function.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Generate metadata and update the attachment.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	private function get_chunk_size() {
		switch ( igd_get_settings( 'serverThrottle' ) ) {
			case 'medium':
				$chunk_size = 1024 * 1024 * 3;

				break;

			case 'high':
				$chunk_size = 1024 * 1024 * 2;

				break;

			case 'low':
				$chunk_size = 1024 * 1024 * 4;

				break;
			case 'off':
			default:
				$chunk_size = 1024 * 1024 * 5;

				break;
		}

		return min( igd_get_free_memory_available() - ( 1024 * 1024 * 5 ), $chunk_size ); // Chunks size or less if memory isn't sufficient;
	}

	/**
	 * @return Importer|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Importer::instance();