<?php
namespace LTW\Order_To_Board_Sync\Admin;

/**
 * Handles admin settings form submission and processing.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Core\Settings;
use LTW\Order_To_Board_Sync\Admin\Notices;

/**
 * Settings_Handler Class
 */
class Settings_Handler {
	/**
	 * Constructor
	 *
	 * @param	Settings    $settings
	 * @param	Notices $notices
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct( private Settings $settings, private Notices $notices ) {
		add_action( 'admin_post_save_settings', [ $this, 'handle_save' ] );
	}

	/**
	 * Handle settings form submission.
	 *
	 * Verifies nonce, extracts data from $_POST, validates and saves settings,
	 * displays appropriate admin notice, and redirects back to settings page.
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save these settings.', 'ltw-order-to-board-sync' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['ltw_order_to_board_sync_settings'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ltw_order_to_board_sync_settings'] ) ), 'ltw_order_to_board_sync_save_settings' ) ) {
			wp_die( __( 'Sorry, your nonce did not verify.', 'ltw-order-to-board-sync' ) );
		}

		// Get URL redirection value
		$url_redirect_to = isset( $_POST['ltw_order_to_board_sync_current_page'] ) && ! empty( $_POST['ltw_order_to_board_sync_current_page'] ) ? ( string ) $_POST['ltw_order_to_board_sync_current_page'] : '';

		// Get settings data from POST
		$data = isset( $_POST['ltw_order_to_board_sync'] ) && ! empty( $_POST['ltw_order_to_board_sync'] ) ? ( array ) $_POST['ltw_order_to_board_sync'] : [];

		// Save settings and get result.
        // Array on success and WP_Error on issue..
		$result = $this->settings->save( $data );

		// Check if there was an error
		if ( is_wp_error( $result ) ) {
			$this->notices->error( $result->get_error_message() );
		} else {
			$this->notices->success( __( 'Settings saved successfully!', 'ltw-order-to-board-sync' ) );
		}

		// Redirect back to settings page
		if ( ! empty( $url_redirect_to ) ) {
			wp_safe_redirect( $url_redirect_to );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME ) );
		}
		
        die();
	}
}
