<?php

namespace IGD;

class Statistics {

	private static $instance = null;

	public function __construct() {

		// Insert logs
		add_action( 'igd_insert_log', [ $this, 'insert_log' ] );

		// Insert logs by ajax
		add_action( 'wp_ajax_igd_log', [ $this, 'handle_log' ] );
		add_action( 'wp_ajax_nopriv_igd_log', [ $this, 'handle_log' ] );

		// Get logs - AJAX
		add_action( 'wp_ajax_igd_get_logs', array( $this, 'get_logs' ) );

		// Get events
		add_action( 'wp_ajax_igd_get_events', array( $this, 'get_events_logs' ) );

		// Clear statistics
		add_action( 'wp_ajax_igd_clear_statistics', [ $this, 'clear_statistics' ] );

		// Export statistics
		add_action( 'wp_ajax_igd_export_statistics', [ $this, 'export_statistics' ] );

		// Include statistics email report file
		if ( igd_get_settings( 'emailReport', false ) ) {
			require_once IGD_INCLUDES . "/class-email-report__premium_only.php";
		}
	}

	public function export_statistics() {
		if ( ! igd_user_can_access( 'statistics' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'integrate-google-drive' ) );
		}

		$start_date = ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date   = ! empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		$end_date = $end_date . ' 23:59:59';

		$type = ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'all';

		if ( 'all' == $type ) {
			$this->export_all_data( $start_date, $end_date );
		} else if ( 'downloads' == $type ) {
			$data = $this->get_top_items( $start_date, $end_date, 'download' );

			$flattened_data = [];
			foreach ( $data as $item ) {
				$flattened_data[] = [
					__( 'File Name', 'integrate-google-drive' )       => $item['file_name'],
					__( 'File ID', 'integrate-google-drive' )         => $item['file_id'],
					__( 'Total Downloads', 'integrate-google-drive' ) => $item['total'],
				];
			}

			$file_name = 'top-downloads-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;

		} else if ( 'download_users' == $type ) {
			$data = $this->get_top_users( $start_date, $end_date, 'download' );

			$flattened_data = [];
			foreach ( $data as $user ) {
				$flattened_data[] = [
					__( 'User Name', 'integrate-google-drive' )       => $user['name'],
					__( 'User ID', 'integrate-google-drive' )         => $user['user_id'],
					__( 'Total Downloads', 'integrate-google-drive' ) => $user['count'],
				];
			}

			$file_name = 'top-download-users-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'uploads' == $type ) {
			$data = $this->get_top_items( $start_date, $end_date, 'upload' );

			$flattened_data = [];
			foreach ( $data as $item ) {
				$flattened_data[] = [
					__( 'File Name', 'integrate-google-drive' )     => $item['file_name'],
					__( 'File ID', 'integrate-google-drive' )       => $item['file_id'],
					__( 'Total Uploads', 'integrate-google-drive' ) => $item['total'],
				];
			}

			$file_name = 'top-uploads-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'upload_users' == $type ) {
			$data = $this->get_top_users( $start_date, $end_date, 'upload' );

			$flattened_data = [];
			foreach ( $data as $user ) {
				$flattened_data[] = [
					__( 'User Name', 'integrate-google-drive' )     => $user['name'],
					__( 'User ID', 'integrate-google-drive' )       => $user['user_id'],
					__( 'Total Uploads', 'integrate-google-drive' ) => $user['count'],
				];
			}

			$file_name = 'top-upload-users-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'streams' == $type ) {
			$data = $this->get_top_items( $start_date, $end_date, 'stream' );

			$flattened_data = [];
			foreach ( $data as $item ) {
				$flattened_data[] = [
					__( 'File Name', 'integrate-google-drive' )     => $item['file_name'],
					__( 'File ID', 'integrate-google-drive' )       => $item['file_id'],
					__( 'Total Streams', 'integrate-google-drive' ) => $item['total'],
				];
			}

			$file_name = 'top-streams-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'stream_users' == $type ) {
			$data = $this->get_top_users( $start_date, $end_date, 'stream' );

			$flattened_data = [];
			foreach ( $data as $user ) {
				$flattened_data[] = [
					__( 'User Name', 'integrate-google-drive' )     => $user['name'],
					__( 'User ID', 'integrate-google-drive' )       => $user['user_id'],
					__( 'Total Streams', 'integrate-google-drive' ) => $user['count'],
				];
			}

			$file_name = 'top-stream-users-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'previews' == $type ) {
			$data = $this->get_top_items( $start_date, $end_date, 'preview' );

			$flattened_data = [];
			foreach ( $data as $item ) {
				$flattened_data[] = [
					__( 'File Name', 'integrate-google-drive' )      => $item['file_name'],
					__( 'File ID', 'integrate-google-drive' )        => $item['file_id'],
					__( 'Total Previews', 'integrate-google-drive' ) => $item['total'],
				];
			}

			$file_name = 'top-previews-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'preview_users' == $type ) {
			$data = $this->get_top_users( $start_date, $end_date, 'preview' );

			$flattened_data = [];
			foreach ( $data as $user ) {
				$flattened_data[] = [
					__( 'User Name', 'integrate-google-drive' )      => $user['name'],
					__( 'User ID', 'integrate-google-drive' )        => $user['user_id'],
					__( 'Total Previews', 'integrate-google-drive' ) => $user['count'],
				];
			}

			$file_name = 'top-preview-users-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'searches' == $type ) {
			$data = $this->get_top_items( $start_date, $end_date, 'search' );

			$flattened_data = [];
			foreach ( $data as $item ) {
				$flattened_data[] = [
					__( 'Search Keyword', 'integrate-google-drive' ) => $item['file_id'],
					__( 'Total Searches', 'integrate-google-drive' ) => $item['total'],
				];
			}

			$file_name = 'top-searches-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else if ( 'search_users' == $type ) {
			$data = $this->get_top_users( $start_date, $end_date, 'search' );

			$flattened_data = [];
			foreach ( $data as $user ) {
				$flattened_data[] = [
					__( 'User Name', 'integrate-google-drive' )      => $user['name'],
					__( 'User ID', 'integrate-google-drive' )        => $user['user_id'],
					__( 'Total Searches', 'integrate-google-drive' ) => $user['count'],
				];
			}

			$file_name = 'top-search-users-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;

		} else if ( 'events' == $type ) {
			$data = $this->get_events( $start_date, $end_date, false );

			$flattened_data = [];
			foreach ( $data as $event ) {
				$flattened_data[] = [
					__( 'Type', 'integrate-google-drive' )         => $event['type'],
					__( 'File Name', 'integrate-google-drive' )    => $event['file_name'],
					__( 'File ID', 'integrate-google-drive' )      => $event['file_id'],
					__( 'FIle Type', 'integrate-google-drive' )    => $event['file_type'],
					__( 'User Name', 'integrate-google-drive' )    => $event['username'],
					__( 'User ID', 'integrate-google-drive' )      => $event['user_id'],
					__( 'Page', 'integrate-google-drive' )         => $event['page'] ?? '',
					__( 'Shortcode ID', 'integrate-google-drive' ) => $event['shortcode_id'] ?? '',
					__( 'Date', 'integrate-google-drive' )         => date( 'Y-m-d H:i a', strtotime( $event['created_at'] ) ),
				];
			}

			$file_name = 'event-logs-' . time() . '.csv';
			$file_path = sys_get_temp_dir() . '/' . $file_name;

			$this->create_csv_file( $file_path, $flattened_data );

			$file_url = 'tmp/' . $file_name;

			if ( ! is_dir( 'tmp' ) ) {
				mkdir( 'tmp', 0755, true );
			}

			if ( file_exists( $file_path ) ) {
				copy( $file_path, $file_url );
			}

			unlink( $file_path );

			wp_send_json_success( [
				'success' => true,
				'url'     => $file_url,
			] );

			exit;
		} else {
			wp_send_json_error( __( 'Invalid export type', 'integrate-google-drive' ) );
		}

	}

	public function export_all_data( $start_date, $end_date ) {
		// Top Items
		$data_top_items = [
			'download' => $this->get_top_items( $start_date, $end_date, 'download' ),
			'upload'   => $this->get_top_items( $start_date, $end_date, 'upload' ),
			'stream'   => $this->get_top_items( $start_date, $end_date, 'stream' ),
			'preview'  => $this->get_top_items( $start_date, $end_date, 'preview' ),
		];

		$flattened_data_top_items = [];
		foreach ( $data_top_items as $type => $items ) {
			foreach ( $items as $item ) {
				$flattened_data_top_items[] = [
					__( 'Type', 'integrate-google-drive' )      => $type,
					__( 'File Name', 'integrate-google-drive' ) => $item['file_name'],
					__( 'File ID', 'integrate-google-drive' )   => $item['file_id'],
					__( 'Date', 'integrate-google-drive' )      => date( 'Y-m-d H:i a', strtotime( $item['created_at'] ) ),
				];
			}
		}

		// Top Users
		$data_top_users = [
			'download' => $this->get_top_users( $start_date, $end_date, 'download' ),
			'upload'   => $this->get_top_users( $start_date, $end_date, 'upload' ),
			'stream'   => $this->get_top_users( $start_date, $end_date, 'stream' ),
			'preview'  => $this->get_top_users( $start_date, $end_date, 'preview' ),
		];

		$flattened_data_top_users = [];
		foreach ( $data_top_users as $type => $users ) {
			foreach ( $users as $user ) {
				$flattened_data_top_users[] = [
					__( 'Type', 'integrate-google-drive' )         => $type,
					__( 'User Name', 'integrate-google-drive' )    => $user['name'],
					__( 'User ID', 'integrate-google-drive' )      => $user['user_id'],
					__( 'Action Count', 'integrate-google-drive' ) => $user['count'],
				];
			}
		}

		//Event Logs
		$events = $this->get_events( $start_date, $end_date, false );

		$flattened_events = [];
		foreach ( $events as $event ) {
			$flattened_events[] = [
				__( 'Type', 'integrate-google-drive' )         => $event['type'],
				__( 'File Name', 'integrate-google-drive' )    => $event['file_name'],
				__( 'File ID', 'integrate-google-drive' )      => $event['file_id'],
				__( 'File Type', 'integrate-google-drive' )    => $event['file_type'],
				__( 'User Name', 'integrate-google-drive' )    => $event['username'],
				__( 'User ID', 'integrate-google-drive' )      => $event['user_id'],
				__( 'Page', 'integrate-google-drive' )         => $event['page'] ?? '',
				__( 'Shortcode ID', 'integrate-google-drive' ) => $event['shortcode_id'] ?? '',
				__( 'Date', 'integrate-google-drive' )         => date( 'Y-m-d H:i a', strtotime( $event['created_at'] ) ),
			];
		}

		// Generate unique file names
		$top_items_file_name  = 'top-items-' . time() . '.csv';
		$top_users_file_name  = 'top-users-' . time() . '.csv';
		$event_logs_file_name = 'event-logs-' . time() . '.csv';

		// Create temporary file paths
		$top_items_file_path  = sys_get_temp_dir() . '/' . $top_items_file_name;
		$top_users_file_path  = sys_get_temp_dir() . '/' . $top_users_file_name;
		$event_logs_file_path = sys_get_temp_dir() . '/' . $event_logs_file_name;

		// Create CSV files
		$this->create_csv_file( $top_items_file_path, $flattened_data_top_items );
		$this->create_csv_file( $top_users_file_path, $flattened_data_top_users );
		$this->create_csv_file( $event_logs_file_path, $flattened_events );

		// Create a zip file
		$zip_file_name = 'statistics-' . time() . '.zip';
		$zip_file_path = sys_get_temp_dir() . '/' . $zip_file_name;

		$zip = new \ZipArchive();

		if ( $zip->open( $zip_file_path, \ZipArchive::CREATE ) === true ) {
			$zip->addFile( $top_items_file_path, $top_items_file_name );
			$zip->addFile( $top_users_file_path, $top_users_file_name );
			$zip->addFile( $event_logs_file_path, $event_logs_file_name );
			$zip->close();
		}

		// Save the zip file in a temporary location
		$zip_file_url = 'tmp/' . $zip_file_name;
		if ( ! is_dir( 'tmp' ) ) {
			mkdir( 'tmp', 0755, true );
		}

		if ( file_exists( $zip_file_path ) ) {
			copy( $zip_file_path, $zip_file_url );
		}

		// Clean up temporary files
		unlink( $top_items_file_path );
		unlink( $top_users_file_path );
		unlink( $event_logs_file_path );
		unlink( $zip_file_path );

		// Send the zip file URL in the AJAX response
		wp_send_json_success( [
			'success' => true,
			'url'     => $zip_file_url,
		] );

		exit;
	}

	public function create_csv_file( $file_path, $data ) {
		$file = fopen( $file_path, 'w' );

		// Add header
		if ( ! empty( $data ) && is_array( $data ) ) {
			$first_row = reset( $data );
			if ( is_array( $first_row ) ) {
				fputcsv( $file, array_keys( $first_row ) );
			}
		}

		// Add data
		foreach ( $data as $row ) {
			if ( is_array( $row ) ) {
				fputcsv( $file, $row );
			}
		}

		fclose( $file );
	}

	public function clear_statistics() {

		if ( ! check_ajax_referer( 'igd', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'integrate-google-drive' ) ] );
		}

		if ( ! igd_user_can_access( 'statistics' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'integrate-google-drive' ) );
		}

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}integrate_google_drive_logs" );

		wp_send_json_success( __( 'Statistics cleared successfully', 'integrate-google-drive' ) );
	}

	public function get_logs( $start_date = null, $end_date = null, $is_email_report = false ) {

		// Skip permission check for cron jobs
		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
			if ( ! igd_user_can_access( 'statistics' ) ) {
				wp_send_json_error( __( 'You do not have permission to perform this action', 'integrate-google-drive' ) );
			}
		}

		if ( empty( $start_date ) ) {
			$start_date = ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		}

		if ( empty( $end_date ) ) {
			$end_date = ! empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		}

		$end_date = $end_date . ' 23:59:59';

		$data = [
			'downloads' => $this->get_top_items( $start_date, $end_date, 'download' ),
			'uploads'   => $this->get_top_items( $start_date, $end_date, 'upload' ),
			'streams'   => $this->get_top_items( $start_date, $end_date, 'stream' ),
			'previews'  => $this->get_top_items( $start_date, $end_date, 'preview' ),
			'searches'  => $this->get_top_items( $start_date, $end_date, 'search' ),
			'shared'    => $this->get_top_items( $start_date, $end_date, 'shared' ),

			'downloadUsers' => $this->get_top_users( $start_date, $end_date, 'download' ),
			'uploadUsers'   => $this->get_top_users( $start_date, $end_date, 'upload' ),
			'streamUsers'   => $this->get_top_users( $start_date, $end_date, 'stream' ),
			'previewUsers'  => $this->get_top_users( $start_date, $end_date, 'preview' ),
			'searchUsers'   => $this->get_top_users( $start_date, $end_date, 'search' ),
			'sharedUsers'   => $this->get_top_users( $start_date, $end_date, 'shared' ),

			'events' => $this->get_events( $start_date, $end_date ),
		];

		if ( $is_email_report ) {
			return $data;
		}

		wp_send_json_success( $data );
	}

	public function get_events_logs() {
		$start_date = ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';

		$end_date = ! empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$end_date = $end_date . ' 23:59:59';

		$page     = ! empty( $_POST['page'] ) ? sanitize_text_field( $_POST['page'] ) : 2;
		$per_page = 30;
		$offset   = ( $page - 1 ) * $per_page;

		$events = $this->get_events( $start_date, $end_date, $per_page, $offset );

		$data = [
			'complete' => empty( $events ) || count( $events ) < $per_page,
			'events'   => $events,
		];

		wp_send_json_success( $data );
	}

	public function get_top_items( $start_date, $end_date, $type ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'integrate_google_drive_logs';

		$limit = 25;

		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] !== 'igd_get_logs' ) {
			$limit = 1000;
		}

		$sql = $wpdb->prepare( "SELECT *, COUNT(id) as total FROM `$table_name`
                WHERE type = '%s' AND created_at BETWEEN '%s' AND '%s'
                GROUP BY file_id
                ORDER BY total DESC
                LIMIT $limit
                ", $type, $start_date, $end_date );

		return $wpdb->get_results( $sql, ARRAY_A );

	}

	public function get_top_users( $start_date, $end_date, $type ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'integrate_google_drive_logs';

		$sql = $wpdb->prepare( "SELECT user_id, COUNT(id) as total FROM `$table_name`
                WHERE type = '%s' AND created_at BETWEEN '%s' AND '%s'
                GROUP BY user_id
                ORDER BY total DESC
                LIMIT 25
                ", $type, $start_date, $end_date );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$data = [];

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {

				$gravatar = '<img src="' . IGD_ASSETS . '/images/user-icon.png" height="32px" />';

				if ( $result['user_id'] ) {
					$user = get_user_by( 'id', $result['user_id'] );
					$name = $user->user_login;

					// Gravatar
					if ( function_exists( 'get_wp_user_avatar_url' ) ) {
						$gravatar = get_wp_user_avatar( $user->user_email, 32 );
					} else {
						$gravatar = get_avatar( $user->user_email, 32 );
					}
				} else {
					$name = __( 'Guest', 'integrate-google-drive' );
				}

				$data[] = [
					'user_id' => $result['user_id'],
					'avatar'  => $gravatar,
					'name'    => $name,
					'count'   => $result['total']
				];
			}
		}

		return $data;
	}

	public function get_events( $start_date = '', $end_date = '', $per_page = 30, $offset = 0 ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'integrate_google_drive_logs';

		$limit_sql = "LIMIT $per_page OFFSET $offset";
		if ( empty( $per_page ) ) {
			$limit_sql = '';
		}

		$sql = $wpdb->prepare( "SELECT * FROM `$table_name`
                                        WHERE created_at BETWEEN '%s' AND '%s'
                                        ORDER BY created_at DESC $limit_sql ", $start_date, $end_date );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$data = [];

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$item = $result;

				if ( $result['user_id'] ) {
					$user             = get_user_by( 'id', $result['user_id'] );
					$item['username'] = $user->user_login;
				} else {
					$item['username'] = __( 'Guest', 'integrate-google-drive' );
				}

				$data[] = $item;
			}
		}

		return $data;
	}

	public function handle_log() {

		Ajax::instance()->check_nonce();

		$file_id    = ! empty( $_POST['file_id'] ) ? sanitize_text_field( $_POST['file_id'] ) : '';
		$account_id = ! empty( $_POST['account_id'] ) ? sanitize_text_field( $_POST['account_id'] ) : '';
		$file_name  = ! empty( $_POST['file_name'] ) ? sanitize_text_field( $_POST['file_name'] ) : '';
		$file_type  = ! empty( $_POST['file_type'] ) ? sanitize_text_field( $_POST['file_type'] ) : '';
		$type       = ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'preview';

		$this->insert_log( [
			'type'       => $type,
			'file_id'    => $file_id,
			'file_name'  => $file_name,
			'file_type'  => $file_type,
			'account_id' => $account_id,
		] );

	}

	public function insert_log( $data = [] ) {

		if ( empty( $data['file_id'] ) ) {
			return;
		}

		$file_id    = $data['file_id'];
		$type       = $data['type'] ?? 'preview';
		$account_id = $data['account_id'] ?? '';
		$created_at = $data['created_at'] ?? current_time( 'mysql' );
		$user_id    = $data['user_id'] ?? get_current_user_id();
		$referer    = wp_get_referer() ?: '';

		// Determine file name and type
		if ( $type === 'search' ) {
			//Note: treat file_id as search keyword for search type
			$file_name = '';
			$file_type = '';
		} else {
			$file_name = $data['file_name'] ?? '';
			$file_type = $data['file_type'] ?? '';

			if ( is_null( $file_name ) || is_null( $file_type ) ) {
				$file = App::instance( $account_id )->get_file_by_id( $file_id );

				if ( ! $file ) {
					return;
				}

				$file_name = $file['name'];
				$file_type = $file['type'];
			}
		}

		// Get shortcode ID
		$shortcode_data = Shortcode::get_current_shortcode();
		$shortcode_id   = ( ! empty( $shortcode_data['id'] ) && ! str_contains( $shortcode_data['id'], 'igd_' ) ) ? $shortcode_data['id'] : '';

		// Insert log entry
		global $wpdb;
		$table_name = $wpdb->prefix . 'integrate_google_drive_logs';

		$wpdb->insert(
			$table_name,
			[
				'shortcode_id' => $shortcode_id,
				'page'         => $referer,
				'type'         => $type,
				'user_id'      => $user_id,
				'file_id'      => $file_id,
				'file_name'    => $file_name,
				'file_type'    => $file_type,
				'account_id'   => $account_id,
				'created_at'   => $created_at,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ] // Corrected placeholders
		);
	}

	public static function view() { ?>
        <div id="igd-statistics" class="igd-statistics"></div>
	<?php }

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}

new Statistics();