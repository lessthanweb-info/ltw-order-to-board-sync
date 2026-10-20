<?php
/**
 * Uninstall cleanup for LTW Order-to-Board Sync.
 *
 * @package LTW_Order_To_Board_Sync
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ltw_order_to_board_sync_settings' );
delete_option( 'ltw_order_to_board_sync_setup_complete' );
delete_option( 'ltw_order_to_board_sync_oauth_client_secret' );
delete_option( 'ltw_order_to_board_sync_trello_oauth_tokens' );
delete_option( 'ltw_order_to_board_sync' );
delete_transient( 'ltw_order_to_board_sync_boards' );

global $wpdb;

$card_claim_option_prefix = $wpdb->esc_like( 'ltw_order_to_board_sync_card_claim_' ) . '%';

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$card_claim_option_prefix
	)
);
