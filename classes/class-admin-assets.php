<?php
namespace LTW\Order_To_Board_Sync\Admin;

/**
 * Contains code for admin stuff.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets Class
 */
class Assets {
	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct() {
        //  Enqueue scripts/styles
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
	 * Enqueue styles and scripts for the admin area for the required pages only.
	 *
	 * @param	string	$hook_suffix	The current admin page hook suffix.
	 * @return	void
	 * @since	1.0.0
	 *
	 */
    public function enqueue_scripts( string $hook_suffix ): void {
        $screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$allowed_screens = [
			'woocommerce_page_ltw-order-to-board-sync', // Trello pages
			'edit-shop_order', // Classic orders list
			'shop_order', // Classic edit order
			'woocommerce_page_wc-orders', // HPOS orders list + edit order
		];

		// This echoes assets for the plugin pages
		if ( $hook_suffix === 'woocommerce_page_ltw-order-to-board-sync' ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'ltw-order-to-board-sync-admin-pico-style', LTW_ORDER_TO_BOARD_SYNC_PLUGIN_URL . 'assets/admin/css/pico.conditional.yellow.min.css', [], LTW_ORDER_TO_BOARD_SYNC_VERSION );
			wp_enqueue_style( 'ltw-order-to-board-sync-admin-style', LTW_ORDER_TO_BOARD_SYNC_PLUGIN_URL . 'assets/admin/css/style.css', [], LTW_ORDER_TO_BOARD_SYNC_VERSION );
			wp_enqueue_script( 'ltw-order-to-board-sync-admin-functions', LTW_ORDER_TO_BOARD_SYNC_PLUGIN_URL . 'assets/admin/js/functions.js', [ 'jquery' ], LTW_ORDER_TO_BOARD_SYNC_VERSION, true );
    	}

		// Echo JS for the trello pages and as well 
		if ( in_array( $hook_suffix, $allowed_screens, true ) || in_array( $screen_id, $allowed_screens, true ) ) {
			wp_enqueue_script( 'ltw-order-to-board-sync-admin-trello', LTW_ORDER_TO_BOARD_SYNC_PLUGIN_URL . 'assets/admin/js/trello.js', [ 'jquery', 'wp-color-picker' ], LTW_ORDER_TO_BOARD_SYNC_VERSION, true );

			wp_localize_script( 'ltw-order-to-board-sync-admin-trello', 'ltw_order_to_board_sync', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ltw-order-to-board-sync-ajax-api-nonce' ),
				'texts' => [
					'logs' => __( 'Logs', 'ltw-order-to-board-sync' ),
					'no_logs_available' => __( 'Currently there are no logs available.', 'ltw-order-to-board-sync' ),
					'confirm_delete_all_logs' => __( 'Are you sure you want to delete all logs? This action cannot be undone.', 'ltw-order-to-board-sync' ),
					'error' => __( 'Error:', 'ltw-order-to-board-sync' ),
					'no_lists_found_for_board' => __( 'No lists found for board_id:', 'ltw-order-to-board-sync' ),
				],
			] );
		}

		if ( in_array( $hook_suffix, $allowed_screens, true ) || in_array( $screen_id, $allowed_screens, true ) ) {
			wp_enqueue_style( 'ltw-order-to-board-sync-order-pages', LTW_ORDER_TO_BOARD_SYNC_PLUGIN_URL . 'assets/admin/css/wc-order-pages-styles.css', [], LTW_ORDER_TO_BOARD_SYNC_VERSION );
		}
    }
}