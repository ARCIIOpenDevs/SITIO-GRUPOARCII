<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

class WooCommerce_Downloads {

	/**
	 * @var null
	 */
	protected static $instance = null;

	public function __construct() {
		// Handle Downloads
		add_action( 'woocommerce_download_file_force', [ $this, 'do_download' ], 1 );
		add_action( 'woocommerce_download_file_xsendfile', [ $this, 'do_download' ], 1 );
		add_action( 'woocommerce_download_file_redirect', [ $this, 'do_download' ], 1 );

	}

	public function do_download( $file_url ) {

		if ( ! strpos( $file_url, 'igd-wc-download' ) ) {
			return;
		}


		$parts = parse_url( $file_url );

		if ( ! empty( $parts['query'] ) ) { //backward compatibility with old links
			$query_args = [];
			parse_str( $parts['query'], $query_args );

		} else {
			// Decode the base64 encoded path and parse it as JSON
			$query_args = json_decode( base64_decode( str_replace( '/igd-wc-download/', '', $parts['path'] ) ), true );
		}


		$file_id           = ! empty( $query_args['id'] ) ? $query_args['id'] : '';
		$account_id        = ! empty( $query_args['account_id'] ) ? $query_args['account_id'] : '';
		$type              = ! empty( $query_args['type'] ) ? $query_args['type'] : '';
		$redirect          = ! empty( $query_args['redirect'] );
		$create_permission = ! empty( $query_args['create_permission'] );

		$is_folder = 'application/vnd.google-apps.folder' == $type;

		if ( $redirect ) {
			$this->do_redirect( $file_id, $account_id, $is_folder, $create_permission );
		} else {

			$fileIdsParam  = $is_folder ? 'file_ids=' . base64_encode( json_encode( [ $file_id ] ) ) : 'id=' . $file_id . '&accountId=' . $account_id;
			$download_link = home_url( '/?igd_download=1&' . $fileIdsParam . '&ignore_limit=1' );

			wp_redirect( $download_link );

		}

		exit();

	}

	/**
	 * Redirect to the content in the Google Drive instead of downloading the file
	 *
	 * @param $file_url
	 *
	 * @return void
	 */
	public function do_redirect( $file_id, $account_id, $is_folder, $create_permission ) {


		if ( $create_permission ) {
			$order_id = wc_get_order_id_by_order_key( wc_clean( wp_unslash( $_GET['order'] ) ) );
			$order    = wc_get_order( $order_id );

			if ( isset( $_GET['email'] ) ) {
				$email_address = wp_unslash( $_GET['email'] );
			} else {
				$email_address = is_a( $order, 'WC_Order' ) ? $order->get_billing_email() : null;
			}
			if ( igd_is_gmail( $email_address ) ) {

				$has_permission = get_option( 'igd_woocommerce_download_permission_' . md5( $email_address . $file_id ), false );

				if ( ! $has_permission ) {
					$file = App::instance( $account_id )->get_file_by_id( $file_id );

					$user_data = [
						'email' => $email_address,
						'name'  => $order ? $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() : '',
					];

					if ( Permissions::instance( $account_id )->share_file( $file, $user_data ) ) {
						update_option( 'igd_woocommerce_download_permission_' . md5( $email_address . $file_id ), true );
					}
				}

			}
		}

		// Google Drive redirect
		if ( $is_folder ) {
			$redirect_url = 'https://drive.google.com/drive/folders/' . $file_id;
		} else {
			$redirect_url = 'https://drive.google.com/file/d/' . $file_id . '/view';
		}

		wp_redirect( $redirect_url );

		exit();
	}

	/**
	 * @return WooCommerce_Downloads|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

WooCommerce_Downloads::instance();