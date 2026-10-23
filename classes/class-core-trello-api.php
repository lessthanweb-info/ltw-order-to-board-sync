<?php
namespace LTW\Order_To_Board_Sync\Core;

/**
 * Contains code for Trello API
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
use LTW\Order_To_Board_Sync\Core\Logger;
use LTW\Order_To_Board_Sync\Core\Hooks;
use LTW\Order_To_Board_Sync\WC\Order_Info;

/**
 * API Class
 */
class Trello_API {
	/**
	 * Set Trello API base url
	 *
	 * @var	const
	 */
	private const API_BASE_URL = 'https://api.trello.com/1/';

	/**
	 * Maximum age of a card-creation claim before another request may recover it.
	 *
	 * @var	int
	 */
	private const CARD_CREATION_CLAIM_TTL = 120;

	/**
	 * Ajax nonce
	 *
	 * @var	const
	 */
	private const API_AJAX_NONCE = 'ltw-order-to-board-sync-ajax-api-nonce';

	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0
	 *
	 */
	public function __construct( 
		private Settings $settings,
		private Order_Info $order_info,
		private Logger $logger,
		private Trello_OAuth $oauth
	) {
		// Ajax call that loads all the available boards.
		add_action( 'wp_ajax_ltw_trello_get_boards', [ $this, 'get_boards' ] );
		// Ajax call for getting available lists inside a board
		add_action( 'wp_ajax_ltw_trello_get_lists_batch', [ $this, 'get_lists_batch' ] );
		// Ajax call to create a new card for an order from admin metabox
		add_action( 'wp_ajax_ltw_trello_create_card_from_order', [ $this, 'create_card_from_order_ajax' ] );

		// Woocommerce hooks
		// When new order is created in WC from frontend
		add_action( 'woocommerce_new_order', [ $this, 'new_order' ] );

		// Hook for when status of an order is changed
		add_action( 'woocommerce_order_status_changed', [ $this, 'order_status_changed' ], 1, 4 );
    }

	/**
	 * Check if nonce is valid on each request.
	 *
	 * @param	void
	 * @return	boolean|integer	False if nonce not valid else an integer 1 or 2, depends on when it was made.
	 * @since	1.0
	 *
	 */
	private function check_ajax_nonce(): bool|int {
		return check_ajax_referer( self::API_AJAX_NONCE, 'nonce' );
    }

	/**
	 * Check user permissions
	 *
	 * @param	void
	 * @return	boolean
	 * @since	1.0
	 *
	 */
	private function check_permissions(): bool {
		return current_user_can( 'manage_woocommerce' );
    }

	/**
	 * Build the order data used by card creation.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @return	array
	 * @since	1.0.0
	 */
	private function get_order_data( object $order ): array {
		return [
			'basic_info' => $this->order_info->get_basic_order_info( $order ),
			'billing_address' => $this->order_info->get_billing_address( $order ),
			'shipping_address' => $this->order_info->get_shipping_address( $order ),
			'shipping_method' => $this->order_info->get_shipping_method_text( $order ),
			'total_costs' => $this->order_info->get_order_costs( $order ),
			'ordered_products' => $this->order_info->get_ordered_products( $order ),
		];
	}

	/**
	 * Make a GET request to the Trello API.
	 *
	 * @param	string	$endpoint	Trello endpoint like members/me
	 * @param	array	$query_args
	 * @return	array|\WP_Error	WP_Error if JSON is not valid or incorrect in some way else return array full of data :)
	 * @since	1.0
	 *
	 */
	private function get( string $endpoint, array $query_args = [] ): array|\WP_Error {
		// Empty endpoint should not be allowed!
		if ( $endpoint === '' ) {
			return new \WP_Error(
				'trello_api_no_endpoint_set',
				__( 'No endpoint set for GET Trello API.', 'ltw-order-to-board-sync' )
			);
		}

		// Build query params
		$access_token = $this->oauth->get_access_token();
		
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$url = add_query_arg( apply_filters( HOOKS::FILTER_TRELLO_API_GET_ARGS, $query_args, $endpoint ), (string) self::API_BASE_URL . $endpoint );
		
		$request_args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
				'Accept' => 'application/json',
			],
			'timeout' => 30,
			'sslverify' => true,
		];

		$response = wp_remote_get( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) === 401 ) {
			$access_token = $this->oauth->get_access_token( true );

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$request_args['headers']['Authorization'] = 'Bearer ' . $access_token;
			$response = wp_remote_get( $url, $request_args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		// Handle HTTP error codes
		if ( $code === 401 ) {
			return new \WP_Error( 'trello_oauth_unauthorized', __( 'Trello rejected the OAuth token after refresh. Reconnect your Trello account in the plugin settings.', 'ltw-order-to-board-sync' ), [ 'status_code' => $code ] );
		}

		if ( $code >= 400 ) {
			return new \WP_Error(
				'trello_api_http_error',
				sprintf( __( 'Trello API returned error %d: %s', 'ltw-order-to-board-sync' ), $code, $body ),
				[ 'status_code' => $code ]
			);
		}

		// Empty body
		if ( empty( $body ) ) {
			return new \WP_Error( 
				'trello_api_no_body', 
				__( 'No body found on GET request.', 'ltw-order-to-board-sync' ) 
			);
		}

		// Trello specific error messages
		// Decode JSON
    	$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'trello_api_json_error',
				'Invalid JSON response: ' . json_last_error_msg()
			);
		}

		return apply_filters( HOOKS::FILTER_TRELLO_API_GET_RESPONSE, $data, $endpoint );
	}

	/**
	 * Make a POST request to the Trello API.
	 *
	 * @param	string	$endpoint	Trello endpoint like 'cards', 'boards/{id}/cards', etc.
	 * @param	array	$body	Data to send in the request body (will be JSON encoded).
	 * @param	array	$query_args	Additional query parameters.
	 * @return	array|\WP_Error
	 * @since	1.0
	 */
	private function post( string $endpoint, array $body = [], array $query_args = [] ): array|\WP_Error {
		// Empty endpoint should not be allowed!
		if ( $endpoint === '' ) {
			return new \WP_Error(
				'trello_api_no_endpoint_set',
				__( 'No endpoint set for POST Trello API.', 'ltw-order-to-board-sync' )
			);
		}
		
		// Build query params
		$access_token = $this->oauth->get_access_token();

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$url = add_query_arg( apply_filters( HOOKS::FILTER_TRELLO_API_POST_ARGS, $query_args, $endpoint ), (string) self::API_BASE_URL . $endpoint );
		
		$request_args = [
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			],
			'body' => wp_json_encode( $body ),
			'timeout' => 30,
			'sslverify' => true,
		];
		$response = wp_remote_post( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) === 401 ) {
			$access_token = $this->oauth->get_access_token( true );
			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$request_args['headers']['Authorization'] = 'Bearer ' . $access_token;
			$response = wp_remote_post( $url, $request_args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $status_code === 401 ) {
			return new \WP_Error( 'trello_oauth_unauthorized', __( 'Trello rejected the OAuth token after refresh. Reconnect your Trello account in the plugin settings.', 'ltw-order-to-board-sync' ), [ 'status_code' => $status_code ] );
		}

		// Handle HTTP error responses
		if ( $status_code >= 400 ) {
			return new \WP_Error(
				'trello_api_http_error',
				sprintf( __( 'Trello API error %d: %s', 'ltw-order-to-board-sync' ), $status_code, $response_body )
			);
		}

		if ( empty( $response_body ) ) {
			return new \WP_Error( 'trello_api_no_body', __( 'No body returned from Trello API.', 'ltw-order-to-board-sync' ) );
		}

		$data = json_decode( $response_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'trello_api_json_error',
				'Invalid JSON response: ' . json_last_error_msg()
			);
		}

		return apply_filters( HOOKS::FILTER_TRELLO_API_POST_RESPONSE, $data, $endpoint );
	}

	/**
	 * Make a PUT request to the Trello API.
	 *
	 * @param	string	$endpoint	Trello endpoint like 'cards/{id}', 'lists/{id}', etc.
	 * @param	array	$body	Data to send in the request body (will be JSON encoded).
	 * @param	array	$query_args	Additional query parameters.
	 * @return	array|\WP_Error
	 * @since	1.0
	 */
	private function put( string $endpoint, array $body = [], array $query_args = [] ): array|\WP_Error {
		// Empty endpoint should not be allowed!
		if ( $endpoint === '' ) {
			return new \WP_Error(
				'trello_api_no_endpoint_set',
				__( 'No endpoint set for PUT Trello API.', 'ltw-order-to-board-sync' )
			);
		}
		
		// Build query params
		$access_token = $this->oauth->get_access_token();

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$url = add_query_arg( apply_filters( HOOKS::FILTER_TRELLO_API_REQUEST_ARGS, $query_args, $endpoint ), (string) self::API_BASE_URL . $endpoint );
		
		$request_args = [
			'method' => 'PUT',
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			],
			'body' => wp_json_encode( $body ),
			'timeout' => 30,
			'sslverify' => true,
		];

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) === 401 ) {
			$access_token = $this->oauth->get_access_token( true );

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$request_args['headers']['Authorization'] = 'Bearer ' . $access_token;

			$response = wp_remote_request( $url, $request_args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $status_code === 401 ) {
			return new \WP_Error( 'trello_oauth_unauthorized', __( 'Trello rejected the OAuth token after refresh. Reconnect your Trello account in the plugin settings.', 'ltw-order-to-board-sync' ), [ 'status_code' => $status_code ] );
		}

		// Handle HTTP error responses
		if ( $status_code >= 400 ) {
			return new \WP_Error(
				'trello_api_http_error',
				sprintf( __( 'Trello API error %d: %s', 'ltw-order-to-board-sync' ), $status_code, $response_body )
			);
		}

		if ( empty( $response_body ) ) {
			return new \WP_Error( 'trello_api_no_body', __( 'No body returned from Trello API.', 'ltw-order-to-board-sync' ) );
		}

		$data = json_decode( $response_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'trello_api_json_error',
				'Invalid JSON response: ' . json_last_error_msg()
			);
		}

		return apply_filters( HOOKS::FILTER_TRELLO_API_REQUEST_RESPONSE, $data, $endpoint );
	}

	/**
	 * Loop over order statuses and based on settings, create new card for Trello.
	 *
	 * @param	object	$order
	 * @return	boolean
	 * @since	1.0
	 *
	 */
	private function set_order_data( object $order, string $event = 'status', string $old_status = '', string $new_status = '' ): bool {
		// Get settings
		$settings = $this->settings->get_all();

		if ( empty( $settings['cards'] ) || ! is_array( $settings['cards'] ) ) {
			return false;
		}

		// Now let's loop over settings and match and active statuses
		$created = false;

		foreach ( $settings['cards'] as $card_key => $card ) {
			// In setting "$card_key" statuses are prefixed with "wc-", at least the default ones.
			// So remove the prefix if "wc-" prefix is found
			$current_card_key = str_starts_with( $card_key, 'wc-' ) ? substr( $card_key, 3 ) : $card_key;

			if ( $current_card_key === $order->get_status() && isset( $card['enabled'] ) && $card['enabled'] == 'on' ) {
				$destinations = $this->get_card_destinations( $card );

				// Avoid silently skipping an enabled status when no complete board/list destination is configured.
				if ( empty( $destinations ) ) {
					$this->logger->warning(
						sprintf(
							__( 'Skipped Trello card creation for order %d because status %s is enabled but has no complete board and list destination selected.', 'ltw-order-to-board-sync' ),
							$order->get_id(),
							$order->get_status()
						)
					);

					return false;
				}

				// Merge arrays
				$order_data = $this->get_order_data( $order );

				// Let extensions resolve destinations before card-creation claims and requests begin.
				$destinations = apply_filters( Hooks::FILTER_CARD_DESTINATIONS, $destinations, $order, $card );

				if ( ! is_array( $destinations ) ) {
					$this->logger->error(
						sprintf(
							__( 'Skipped Trello card creation for order %d because the destination filter did not return an array.', 'ltw-order-to-board-sync' ),
							$order->get_id()
						)
					);

					return false;
				}

				$resolved_destinations = [];

				foreach ( $destinations as $destination ) {
					if (
						! is_array( $destination )
						|| ! isset( $destination['board_id'], $destination['list_id'] )
						|| ! is_scalar( $destination['board_id'] )
						|| ! is_scalar( $destination['list_id'] )
					) {
						$this->logger->error(
							sprintf(
								__( 'Skipped an invalid Trello destination for order %d because a board or list was missing.', 'ltw-order-to-board-sync' ),
								$order->get_id()
							)
						);

						continue;
					}

					$destination['board_id'] = sanitize_text_field( (string) $destination['board_id'] );
					$destination['list_id'] = sanitize_text_field( (string) $destination['list_id'] );

					if ( $destination['board_id'] === '' || $destination['list_id'] === '' ) {
						$this->logger->error(
							sprintf(
								__( 'Skipped an invalid Trello destination for order %d because a board or list was empty after sanitization.', 'ltw-order-to-board-sync' ),
								$order->get_id()
							)
						);

						continue;
					}

					$resolved_destinations[] = $destination;
				}

				$destinations = $resolved_destinations;

				if ( empty( $destinations ) ) {
					$this->logger->warning(
						sprintf(
							__( 'Skipped Trello card creation for order %d because no destination was resolved for status %s.', 'ltw-order-to-board-sync' ),
							$order->get_id(),
							$order->get_status()
						)
					);

					return false;
				}

				foreach ( $destinations as $destination ) {
					$card_settings = array_merge( $card, $destination );

					$attempt_created = $this->create_card_for_destination( $order, $order_data, $card_settings, $event, $old_status, $new_status );
					$created = $attempt_created || $created;
				}
			}
		}

		return $created;
	}

	/**
	 * Create a card for one destination while holding an atomic claim.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @param	array	$order_data	Order data used to create the card.
	 * @param	array	$card_settings	Card settings for this destination.
	 * @param	string	$event	Creation event source.
	 * @param	string	$old_status	Previous order status.
	 * @param	string	$new_status	New order status.
	 * @return	bool	Whether a card was created.
	 * @since	1.0.0
	 */
	private function create_card_for_destination( object $order, array $order_data, array $card_settings, string $event, string $old_status, string $new_status ): bool {
		$status = (string) $order->get_status();
		$board_id = (string) ( $card_settings['board_id'] ?? '' );
		$list_id = (string) ( $card_settings['list_id'] ?? '' );
		$claim = $this->acquire_card_creation_claim( (int) $order->get_id(), $status, $board_id, $list_id );

		if ( $claim === false ) {
			$this->logger->info(
				sprintf(
					__( 'Skipped overlapping Trello card creation for order %d, status %s, destination %s/%s, event %s.', 'ltw-order-to-board-sync' ),
					$order->get_id(),
					$status,
					$board_id,
					$list_id,
					$event
				)
			);

			return false;
		}

		try {
			// Recheck history only after claiming so overlapping hooks cannot both pass the duplicate check.
			$has_card = $this->has_card_for_destination( $order, $status, $board_id, $list_id );

			if ( is_wp_error( $has_card ) ) {
				$this->logger->error(
					sprintf(
						__( 'Could not verify the saved Trello card for order %d, status %s, destination %s/%s, event %s: %s', 'ltw-order-to-board-sync' ),
						$order->get_id(),
						$status,
						$board_id,
						$list_id,
						$event,
						$has_card->get_error_message()
					),
					$has_card->get_error_data()
				);

				return false;
			}

			if ( $has_card ) {
				$this->logger->info(
					sprintf(
						__( 'Skipped Trello card creation for order %d because a card already exists for status %s and destination %s/%s (event %s).', 'ltw-order-to-board-sync' ),
						$order->get_id(),
						$status,
						$board_id,
						$list_id,
						$event
					)
				);

				return false;
			}

			// Keep the existing extension hooks immediately before the outbound card request.
			if ( $event === 'new' ) {
				do_action( Hooks::ACTION_ORDER_NEW_BEFORE_SEND, $order, $order_data, $card_settings );
			} else {
				do_action( Hooks::ACTION_ORDER_STATUS_CHANGED_BEFORE_SEND, $order->get_id(), $old_status, $new_status, $order, $order_data, $card_settings );
			}

			$created = $this->create_new_card( $order_data, $card_settings );

			if ( ! $created ) {
				$this->record_card_failure( $order, $card_settings );

				return false;
			}

			$this->clear_card_failure( $order );

			return true;
		} finally {
			// Release the claim even when an extension hook or API helper throws.
			$this->release_card_creation_claim( (int) $order->get_id(), $status, $board_id, $list_id, $claim );
		}
	}

	/**
	 * Atomically claim card creation for an order status and destination.
	 *
	 * @param	int	$order_id	WooCommerce order ID.
	 * @param	string	$status	Order status.
	 * @param	string	$board_id	Trello board ID.
	 * @param	string	$list_id	Trello list ID.
	 * @return	string|false	Claim token or false when another request owns the claim.
	 * @since	1.0.0
	 */
	private function acquire_card_creation_claim( int $order_id, string $status, string $board_id, string $list_id ): string|false {
		global $wpdb;

		// Hash the complete idempotency key to keep the option name short and avoid raw IDs in option names.
		$key = hash( 'sha256', wp_json_encode( [ $order_id, $status, $board_id, $list_id ] ) );
		$option_name = 'ltw_order_to_board_sync_card_claim_' . $key;
		$claim = time() . ':' . wp_generate_uuid4();

		// The unique option_name index makes this insert the cross-request atomic claim.
		if ( add_option( $option_name, $claim, '', false ) ) {
			return $claim;
		}

		// Read directly from the database so an object-cache entry cannot hide the current owner.
		$existing_claim = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$option_name
			)
		);

		if ( ! is_string( $existing_claim ) ) {
			$this->logger->error(
				sprintf(
					__( 'Could not acquire the Trello card creation claim for order %d, status %s, destination %s/%s.', 'ltw-order-to-board-sync' ),
					$order_id,
					$status,
					$board_id,
					$list_id
				),
				[ 'database_error' => $wpdb->last_error ]
			);

			return false;
		}

		// Allow time for the sequence of Trello requests, then recover claims left by a terminated request.
		$claim_parts = explode( ':', $existing_claim, 2 );
		$created_at = isset( $claim_parts[0] ) ? absint( $claim_parts[0] ) : 0;

		if ( $created_at === 0 || time() - $created_at < self::CARD_CREATION_CLAIM_TTL ) {
			return false;
		}

		// Delete only the exact expired claim so concurrent stale-claim recovery cannot remove a newer owner.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
				$option_name,
				$existing_claim
			)
		);

		if ( $deleted !== 1 ) {
			return false;
		}

		// Clear both option caches before inserting the replacement claim.
		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		if ( add_option( $option_name, $claim, '', false ) ) {
			$this->logger->warning(
				sprintf(
					__( 'Reclaimed a stale Trello card creation claim for order %d, status %s, destination %s/%s.', 'ltw-order-to-board-sync' ),
					$order_id,
					$status,
					$board_id,
					$list_id
				)
			);

			return $claim;
		}

		$this->logger->info(
			sprintf(
				__( 'Another request acquired the Trello card creation claim for order %d, status %s, destination %s/%s during stale-claim recovery.', 'ltw-order-to-board-sync' ),
				$order_id,
				$status,
				$board_id,
				$list_id
			)
		);

		return false;
	}

	/**
	 * Release a card-creation claim only when this request still owns it.
	 *
	 * @param	int	$order_id	WooCommerce order ID.
	 * @param	string	$status	Order status.
	 * @param	string	$board_id	Trello board ID.
	 * @param	string	$list_id	Trello list ID.
	 * @param	string	$claim	Claim token returned by acquire_card_creation_claim().
	 * @return	void
	 * @since	1.0.0
	 */
	private function release_card_creation_claim( int $order_id, string $status, string $board_id, string $list_id, string $claim ): void {
		global $wpdb;

		// Recreate the same key used during acquisition and compare the token to avoid releasing another request's claim.
		$key = hash( 'sha256', wp_json_encode( [ $order_id, $status, $board_id, $list_id ] ) );
		$option_name = 'ltw_order_to_board_sync_card_claim_' . $key;
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
				$option_name,
				$claim
			)
		);

		if ( $deleted === false ) {
			$this->logger->error(
				sprintf(
					__( 'Could not release the Trello card creation claim for order %d, status %s, destination %s/%s.', 'ltw-order-to-board-sync' ),
					$order_id,
					$status,
					$board_id,
					$list_id
				),
				[ 'database_error' => $wpdb->last_error ]
			);

			return;
		}

		// The claim may have expired and been replaced; clear caches only after the conditional delete.
		if ( $deleted === 1 ) {
			wp_cache_delete( $option_name, 'options' );
			wp_cache_delete( 'notoptions', 'options' );
		}
	}

	/**
	 * Get configured Board/List destinations, including legacy settings.
	 *
	 * @param	array	$card	Card settings.
	 * @return	array
	 * @since	1.0.0
	 */
	private function get_card_destinations( array $card ): array {
		$destinations = [];

		if ( ! empty( $card['board_lists'] ) && is_array( $card['board_lists'] ) ) {
			foreach ( $card['board_lists'] as $destination ) {
				if ( ! is_array( $destination ) ) {
					continue;
				}

				$board_id = sanitize_text_field( (string) ( $destination['board_id'] ?? '' ) );
				$list_id = sanitize_text_field( (string) ( $destination['list_id'] ?? '' ) );

				if ( '' !== $board_id && '' !== $list_id ) {
					$destination['board_id'] = $board_id;
					$destination['list_id'] = $list_id;
					$destinations[] = $destination;
				}
			}
		} elseif ( ! empty( $card['board_id'] ) || ! empty( $card['list_id'] ) ) {
			$board_id = sanitize_text_field( (string) ( $card['board_id'] ?? '' ) );
			$list_id = sanitize_text_field( (string) ( $card['list_id'] ?? '' ) );

			if ( '' !== $board_id && '' !== $list_id ) {
				$destinations[] = [
					'board_id' => $board_id,
					'list_id' => $list_id,
				];
			}
		}

		return $destinations;
	}

	/**
	 * Hook for when a new order is created via admin area or via frontend.
	 * 
	 * This should get executed only once
	 *
	 * @param	integer	$order_id	// WC order ID
	 * @return	boolean
	 * @since	1.0
	 *
	 */
	public function new_order( int $order_id ): bool {
		// Skip all order processing until a Trello account is connected.
		if ( ! $this->oauth->is_connected() ) {
			return false;
		}

		// Get order object
		$order = wc_get_order( $order_id );

		// Order does not exist?
		if ( ! $order ) {
			$this->logger->error( 
				sprintf( __( 'Order with the ID %d does not exist.', 'ltw-order-to-board-sync' ), $order_id )
			);

			return false;
		}

		if ( $this->set_order_data( $order, 'new' ) ) {
			do_action( Hooks::ACTION_ORDER_NEW_AFTER_SEND, $order );
		}

		return true;
	}

	/**
	 * Hook for when a new order is created via admin area or via frontend
	 *
	 * @param	integer	$order_id	// WC order ID
	 * @param	string	$old_status
	 * @param	string	$new_status
	 * @param	object	$order
	 * @return	boolean
	 * @since	1.0
	 *
	 */
	public function order_status_changed( int $order_id, string $old_status, string $new_status, object $order ): bool {
		// Skip all order processing until a Trello account is connected.
		if ( ! $this->oauth->is_connected() ) {
			return false;
		}

		// If old and new statuses are the same, just quit!
		if ( $old_status === $new_status ) {
			$this->logger->error( 
				sprintf( 
					__( 'Card was not created for order ID %d as there was no status change. Old status was %s and updated status is %s.', 'ltw-order-to-board-sync' ),
					$order_id,
					$old_status,
					$new_status
				)
			);
			
			return false;
		}

		// Check that the $order object is not false
		if ( ! $order ) {
			$this->logger->error( 
				sprintf( __( 'Order with the ID %d does not exist.', 'ltw-order-to-board-sync' ), $order_id )
			);

			return false;
		}

		$created = $this->set_order_data( $order, 'status', $old_status, $new_status );

		do_action( Hooks::ACTION_ORDER_STATUS_CHANGED, $order_id, $old_status, $new_status, $order );

		if ( $created ) {
			do_action( Hooks::ACTION_ORDER_STATUS_CHANGED_AFTER_SEND, $order_id, $old_status, $new_status, $order );
		}

		return true;
	}

	/**
	 * Fetch all the available boards from Trello.
	 *
	 * @param	bool	$use_cache	Whether to use the transient cache.
	 * @return	array|\WP_Error
	 * @since	1.0
	 *
	 */
	public function fetch_boards( bool $use_cache = true ): array|\WP_Error {
		if ( $use_cache ) {
			$boards_cache = get_transient( 'ltw_order_to_board_sync_boards' );

			if ( ! empty( $boards_cache ) ) {
				return $boards_cache;
			}
		}

		$result = $this->get( 'members/me/boards', [
			'fields' => 'id,name',
		] );

		if ( ! is_wp_error( $result ) ) {
			set_transient( 'ltw_order_to_board_sync_boards', $result, HOUR_IN_SECONDS * 12 );
		}

		return $result;
	}

	/**
	 * Get's a list of all available boards from Trello.
	 * 
	 * This is called via AJAX when the page loads so that we don't interrupt the page load time.
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0
	 *
	 */
	public function get_boards(): void {
		$this->check_ajax_nonce();

		if ( ! $this->check_permissions() ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'ltw-order-to-board-sync' ) ] );
		}

		$result = $this->fetch_boards();

		if ( is_wp_error( $result ) ) {
			$this->logger->error( $result->get_error_message(), $result->get_error_data() );

			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'message' => __( 'We found some boards!', 'ltw-order-to-board-sync' ), 'boards' => $result ] );
	}

	/**
	 * Fetch all lists for the provided board IDs.
	 *
	 * @param	array	$board_ids
	 * @return	array|\WP_Error
	 * @since	1.0
	 *
	 */
	public function fetch_lists( array $board_ids ): array|\WP_Error {
		if ( empty( $board_ids ) ) {
			return new \WP_Error( 'trello_api_no_board_ids_provided', __( 'No board IDs provided', 'ltw-order-to-board-sync' ) );
		}

		$board_ids = array_values(
			array_filter(
				array_map( 'sanitize_text_field', $board_ids ),
				static fn( string $board_id ): bool => (bool) preg_match( '/^[0-9a-fA-F]{24}$/', $board_id )
			)
		);

		if ( empty( $board_ids ) ) {
			return new \WP_Error( 'trello_api_invalid_board_ids', __( 'No valid board IDs provided.', 'ltw-order-to-board-sync' ) );
		}
		
		$all_lists = [];
		
		foreach ( $board_ids as $board_id ) {
			$lists = $this->get( "boards/{$board_id}/lists" );
		
			if ( is_wp_error( $lists ) ) {
				$this->logger->error( $lists->get_error_message(), $lists->get_error_data() );
				return $lists;
			}
		
			$all_lists[ $board_id ] = $lists;
		}
		
		return $all_lists;
	}

	/**
	 * Get's a list of all available lists within a board from Trello.
	 * 
	 * This is called via AJAX when the page loads so that we don't interrupt the page load time.
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0
	 *
	 */
	public function get_lists_batch(): void {
		$this->check_ajax_nonce();

		if ( ! $this->check_permissions() ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'ltw-order-to-board-sync' ) ] );
		}

		$board_ids = isset( $_POST['board_ids'] ) ? ( array ) $_POST['board_ids'] : [];

		$result = $this->fetch_lists( $board_ids );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'message' => __( 'We found some lists!', 'ltw-order-to-board-sync' ), 'lists' => $result ] );
	}

	/**
	 * AJAX handler to create a Trello card for an order from admin metabox
	 *
	 * Expects POST: nonce, order_id, board_id, list_id
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function create_card_from_order_ajax(): void {
		$this->check_ajax_nonce();

		if ( ! $this->check_permissions() ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'ltw-order-to-board-sync' ) ] );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$board_id = isset( $_POST['board_id'] ) ? sanitize_text_field( wp_unslash( $_POST['board_id'] ) ) : '';
		$list_id = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '';

		if ( $order_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order ID.', 'ltw-order-to-board-sync' ) ] );
		}

		if ( empty( $board_id ) || empty( $list_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Board ID and List ID are required.', 'ltw-order-to-board-sync' ) ] );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Order with ID %d not found.', 'ltw-order-to-board-sync' ), $order_id ) ] );
		}

		// Determine card settings from plugin settings for this order status
		$settings = $this->settings->get_all();
		$card_settings = [];

		if ( ! empty( $settings['cards'] ) && is_array( $settings['cards'] ) ) {
			foreach ( $settings['cards'] as $card_key => $card ) {
				$current_card_key = str_starts_with( $card_key, 'wc-' ) ? substr( $card_key, 3 ) : $card_key;

				if ( $current_card_key === $order->get_status() && isset( $card['enabled'] ) && $card['enabled'] === 'on' ) {
					$card_settings = $card;
					
					break;
				}
			}

			// If none matched, try to use the first enabled card as a fallback
			if ( empty( $card_settings ) ) {
				foreach ( $settings['cards'] as $card_key => $card ) {
					if ( isset( $card['enabled'] ) && $card['enabled'] === 'on' ) {
						$card_settings = $card;

						break;
					}
				}
			}
		}

		// Ensure we have board/list set from user input
		$card_settings['board_id'] = $board_id;
		$card_settings['list_id'] = $list_id;

		$order_status = (string) $order->get_status();
		$claim = $this->acquire_card_creation_claim( $order_id, $order_status, $board_id, $list_id );

		if ( $claim === false ) {
			wp_send_json_error( [ 'message' => __( 'Card creation for this order status and destination is already in progress. Please try again shortly.', 'ltw-order-to-board-sync' ) ] );
		}

		$response = [
			'success' => false,
			'message' => __( 'Failed to create card. Check logs for details.', 'ltw-order-to-board-sync' ),
		];

		try {
			// Build order data and verify history while holding the same claim used by automatic creation.
			$order_data = $this->get_order_data( $order );
			$has_card = $this->has_card_for_destination( $order, $order_status, $board_id, $list_id );

			if ( is_wp_error( $has_card ) ) {
				$this->logger->error(
					sprintf(
						__( 'Could not verify the saved Trello card for order %d, status %s, destination %s/%s: %s', 'ltw-order-to-board-sync' ),
						$order_id,
						$order_status,
						$board_id,
						$list_id,
						$has_card->get_error_message()
					),
					$has_card->get_error_data()
				);

				$response['message'] = __( 'Could not verify whether a Trello card already exists. Please try again later.', 'ltw-order-to-board-sync' );
			} elseif ( $has_card ) {
				$response['message'] = __( 'A Trello card already exists for this order status and destination.', 'ltw-order-to-board-sync' );
			} else {
				// Use the shared card creator so automatic and manual requests persist history consistently.
				$created = $this->create_new_card( $order_data, $card_settings );

				if ( $created ) {
					$this->clear_card_failure( $order );

					// Read the saved history after creation so the metabox response includes the new card.
					$meta_data = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD );
					$meta_data_history = is_array( $meta_data ) && isset( $meta_data['history'] ) && is_array( $meta_data['history'] ) ? $meta_data['history'] : [];

					$response = [
						'success' => true,
						'message' => __( 'Card created successfully.', 'ltw-order-to-board-sync' ),
						'html' => Helper::get_trello_card_history_data( $meta_data_history )['html'],
					];
				} else {
					$this->record_card_failure( $order, $card_settings );
				}
			}
		} finally {
			// Release the shared claim before returning any response to the browser.
			$this->release_card_creation_claim( $order_id, $order_status, $board_id, $list_id, $claim );
		}

		if ( $response['success'] ) {
			unset( $response['success'] );

			wp_send_json_success( $response );
		}

		unset( $response['success'] );

		wp_send_json_error( $response );
	}

	/**
	 * Determine whether an order already has a card for a status and destination.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @param	string	$status	Order status.
	 * @param	string	$board_id	Trello board ID.
	 * @param	string	$list_id	Trello list ID.
	 * @return	bool|\WP_Error
	 * @since	1.0.0
	 */
	private function has_card_for_destination( object $order, string $status, string $board_id, string $list_id ): bool|\WP_Error {
		return $this->has_card_in_history( $order, $status, $board_id, $list_id );
	}

	/**
	 * Check matching order history against Trello and mark confirmed-deleted cards.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @param	string	$status	Order status.
	 * @param	string|null	$board_id	Optional destination board ID.
	 * @param	string|null	$list_id	Optional destination list ID.
	 * @return	bool|\WP_Error	True when a matching card still exists, otherwise false or an error.
	 * @since	1.0.0
	 */
	private function has_card_in_history( object $order, string $status, ?string $board_id = null, ?string $list_id = null ): bool|\WP_Error {
		// Hook callbacks may hold an order instance loaded before another callback saved card history.
		$current_order = wc_get_order( $order->get_id() );

		if ( ! $current_order ) {
			return new \WP_Error(
				'ltw_order_to_board_sync_order_missing',
				sprintf(
					__( 'Could not reload order %d while checking for an existing Trello card.', 'ltw-order-to-board-sync' ),
					$order->get_id()
				)
			);
		}

		$meta = $current_order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, true );
		$meta = is_array( $meta ) ? $meta : [];
		$history = is_array( $meta ) && isset( $meta['history'] ) && is_array( $meta['history'] ) ? $meta['history'] : [];
		$history_updated = false;

		foreach ( $history as $index => $entry ) {
			if ( is_array( $entry )
				&& isset( $entry['status'] )
				&& $entry['status'] === $status
				&& ( $board_id === null || ( isset( $entry['board_id'] ) && $entry['board_id'] === $board_id ) )
				&& ( $list_id === null || ( isset( $entry['list_id'] ) && $entry['list_id'] === $list_id ) )
			) {
				// A confirmed deletion remains in history for audit and cannot be restored.
				if ( ! empty( $entry['deleted_in_trello'] ) ) {
					continue;
				}

				// Legacy entries without an ID cannot be verified, so keep them blocking rather than risk duplicates.
				if ( empty( $entry['card_id'] ) || ! is_string( $entry['card_id'] ) ) {
					$this->save_card_history_if_changed( $current_order, $meta, $history, $history_updated );

					return true;
				}

				$card_id = $entry['card_id'];
				$card = $this->get( 'cards/' . rawurlencode( $card_id ), [ 'fields' => 'id,closed' ] );

				if ( is_wp_error( $card ) ) {
					$is_not_found = $card->get_error_code() === 'trello_api_http_error' && is_array( $card->get_error_data() ) && ( $card->get_error_data()['status_code'] ?? 0 ) === 404;

					if ( ! $is_not_found ) {
						$this->save_card_history_if_changed( $current_order, $meta, $history, $history_updated );

						return $card;
					}

					// Only Trello's definitive 404 response is evidence that the stored card was deleted.
					$history[ $index ]['deleted_in_trello'] = true;
					$history[ $index ]['deleted_at'] = time();

					unset( $history[ $index ]['archived_in_trello'], $history[ $index ]['archived_at'] );

					$history_updated = true;

					continue;
				}

				if ( ! isset( $card['id'] ) || $card['id'] !== $card_id ) {
					$this->save_card_history_if_changed( $current_order, $meta, $history, $history_updated );

					return new \WP_Error(
						'trello_api_invalid_card_response',
						__( 'Trello returned an unexpected response while checking a saved card.', 'ltw-order-to-board-sync' )
					);
				}

				// Archived cards are retained in history but should not block a replacement card.
				if ( ! empty( $card['closed'] ) ) {
					if ( empty( $entry['archived_in_trello'] ) ) {
						$history[ $index ]['archived_in_trello'] = true;
						$history[ $index ]['archived_at'] = time();
						$history_updated = true;
					}

					continue;
				}

				// If an archived card was restored in Trello, it becomes an active duplicate again.
				if ( ! empty( $entry['archived_in_trello'] ) ) {
					unset( $history[ $index ]['archived_in_trello'], $history[ $index ]['archived_at'] );
					$history_updated = true;
				}

				$this->save_card_history_if_changed( $current_order, $meta, $history, $history_updated );

				return true;
			}
		}

		$this->save_card_history_if_changed( $current_order, $meta, $history, $history_updated );

		return false;
	}

	/**
	 * Persist order history only when a Trello card was newly marked as deleted.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @param	array	$meta	Order Trello metadata.
	 * @param	array	$history	Updated card history.
	 * @param	bool	$history_updated	Whether the history changed.
	 * @return	void
	 * @since	1.0.0
	 */
	private function save_card_history_if_changed( object $order, array $meta, array $history, bool $history_updated ): void {
		// Avoid order writes when all matching Trello cards are still present.
		if ( ! $history_updated ) {
			return;
		}

		$meta['history'] = $history;
		
		$order->update_meta_data( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, $meta );
		$order->save();
	}

	/**
	 * Store the latest failed card attempt for manual retry.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @param	array	$settings	Card settings.
	 * @return	void
	 * @since	1.0.0
	 */
	private function record_card_failure( object $order, array $settings ): void {
		$meta = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, true );

		$meta = is_array( $meta ) ? $meta : [];

		$meta['last_failure'] = [
			'status' => $order->get_status(),
			'board_id' => $settings['board_id'] ?? '',
			'list_id' => $settings['list_id'] ?? '',
			'date' => time(),
		];

		$order->update_meta_data( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, $meta );

		$order->save();
	}

	/**
	 * Remove the failed attempt marker after a successful retry.
	 *
	 * @param	object	$order	WooCommerce order.
	 * @return	void
	 * @since	1.0.0
	 */
	private function clear_card_failure( object $order ): void {
		$meta = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, true );

		if ( ! is_array( $meta ) || ! isset( $meta['last_failure'] ) ) {
			return;
		}

		unset( $meta['last_failure'] );

		$order->update_meta_data( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, $meta );
		
		$order->save();
	}

	/**
	 * Get a single board info from Trello
	 *
	 * @param	string	$board_id	Valid board ID from Trello
	 * @return	array|\WP_Error
	 * @since	1.0
	 *
	 */
	public function get_single_board_info( string $board_id ): array|\WP_Error {
		// Get a list of available boards
		$result = $this->get( 'boards/' . $board_id );

		if ( is_wp_error( $result) ) {
			$this->logger->error( $result->get_error_message(), $result->get_error_data() );

			return new \WP_Error( 'trello_api_get_single_board_info', __( 'Failed getting single board info.', 'ltw-order-to-board-sync' ) );
		}

		return $result;
	}

	/**
	 * Get a single list info from Trello
	 *
	 * @param	string	$list_id	Valid list ID from Trello
	 * @return	array|\WP_Error
	 * @since	1.0
	 *
	 */
	public function get_single_list_info( string $list_id ): array|\WP_Error {
		// Get a list of available lists
		$result = $this->get( 'lists/' . $list_id, [
			'fields' => 'name',
		] );

		if ( is_wp_error( $result) ) {
			$this->logger->error( $result->get_error_message(), $result->get_error_data() );

			return new \WP_Error( 'trello_api_get_single_list_info', __( 'Failed getting single list info.', 'ltw-order-to-board-sync' ) );
		}

		return $result;
	}

	/**
	 * Replace order placeholders in a string with actual order data.
	 *
	 * Supported placeholders:
	 * [ORDER_ID], [ORDER_STATUS], [NAME], [FIRST_NAME], [LAST_NAME], [COMPANY],
	 * [EMAIL], [ORDER_DATE], [ORDER_TOTAL]
	 *
	 * @param	string	$text	Text with placeholders like [NAME], [ORDER_ID], etc.
	 * @param	array	$order	Order data array containing basic_info and other order details
	 * @return	string	Text with placeholders replaced with actual values
	 * @since	1.0
	 *
	 */
	private function replace_order_placeholders( string $text, array $order ): string {
		if ( empty( $text ) || empty( $order ) || empty( $order['basic_info'] ) ) {
			return $text;
		}

		$basic_info = $order['basic_info'];

		// Build replacements array
		$replacements = [
			'[ORDER_ID]' => $basic_info['id'],
			'[ORDER_STATUS]' => $basic_info['order_status'],
			'[NAME]' => $basic_info['billing_name'],
			'[FIRST_NAME]' => $basic_info['billing_first_name'],
			'[LAST_NAME]' => $basic_info['billing_last_name'],
			'[COMPANY]' => $basic_info['billing_company'],
			'[EMAIL]' => $basic_info['billing_email'],
			'[ORDER_DATE]' => $basic_info['order_create_date'] ? $basic_info['order_create_date']->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '',
			'[ORDER_TOTAL]' => $basic_info['order_total'],
		];

		// Replace all placeholders
		$text = str_replace( array_keys( $replacements ), array_values( $replacements ), $text );

		return $text;
	}

	/**
	 * Creates a new Trello card with provided info
	 *
	 * @param	array	$order
	 * @param	array	$card_settings
	 * @return	boolean
	 * @since	1.0
	 *
	 */
	public function create_new_card( array $order, array $card_settings = [] ): bool {
		// If $order is empty, stop here and log the error
		if ( empty( $order ) ) {
			$this->logger->error( __( 'Cannot post to Trello if $order has no order data in "create_new_card method".', 'ltw-order-to-board-sync' ) );
			
			return false;
		}

		if ( empty( $card_settings['board_id'] ) || empty( $card_settings['list_id'] ) ) {
			$this->logger->error( __( 'Board and list are required before creating a Trello card.', 'ltw-order-to-board-sync' ) );

			return false;
		}
		
		// Trello requires that the board ID is 24 chars long and contains 0-9 and a-z
		if ( ! preg_match( '/^[0-9a-fA-F]{24}$/', $card_settings['board_id'] ) ) {
			$this->logger->error(
				sprintf( 
					__( 'Board ID <strong>%s</strong> is not in a valid format. Please check the selected board for order status <strong>%s</strong>.', 'ltw-order-to-board-sync' ),
					$card_settings['board_id'],
					$order['basic_info']['order_status']
					)
				);
				
			return false;
		}
				
		// Let's get board ID as well as it will be needed down the line.
		$board_info = $this->get_single_board_info( $card_settings['board_id'] );

		if ( is_wp_error( $board_info ) ) {
			return false;
		}

		// Trello requires that the list_id is 24 chars long and contains 0-9 and a-z
		if ( ! preg_match( '/^[0-9a-fA-F]{24}$/', $card_settings['list_id'] ) ) {
			$this->logger->error(
				sprintf( 
					__( 'List ID <strong>%s</strong> is not in a valid format. Please check the selected list for order status <strong>%s</strong>.', 'ltw-order-to-board-sync' ),
					$card_settings['list_id'],
					$order['basic_info']['order_status']
				)
			);
			
			return false;
		}

		// List ID is required!
		$card['idList'] = $card_settings['list_id'];

		// Card name, use default if empty.
		// Card name can be formatted as user wants so we need to replace the placeholders with actual content.
		$card_name = $card_settings['card_name'] ?? '';
		$card['name'] = $card_name ? $this->replace_order_placeholders( $card_name, $order ) : sprintf( __( 'Order #%d', 'ltw-order-to-board-sync' ), $order['basic_info']['id'] );

		// Build description sections separately so extensions can insert or reorder them.
		$description_sections = [];

		$description_sections['card_description'] = $this->replace_order_placeholders( $card_settings['card_description'] ?? '', $order );

		if ( ! empty( $description_sections['card_description'] ) ) {
			// If we already have default card description then we need to add a line breaks and divider
			$description_sections['card_description'] .= Helper::new_line( 2 );
			$description_sections['card_description'] .= "---" . Helper::new_line();
		}

		// First thing we always add is the order ID.
		$description_sections['order'] = "# " . __( 'Order', 'ltw-order-to-board-sync' ) . " #{$order['basic_info']['id']}" . Helper::new_line( 3 );

		// Including billing address if turned on
		$description_sections['billing_address'] = '';

		if ( ! empty( $card_settings['billing_address'] ) && ! empty( $order['billing_address'] ) ) {
			$description_sections['billing_address'] .= "**" . __( 'Billing address', 'ltw-order-to-board-sync' ) . "**" . Helper::new_line( 2 );
			$description_sections['billing_address'] .= $order['billing_address'] . Helper::new_line( 2 );
			$description_sections['billing_address'] .= "---" . Helper::new_line();
		}

		// Including shipping address if turned on and different from billing address
		$description_sections['shipping_address'] = '';

		if ( ! empty( $card_settings['shipping_address'] ) && ! empty( $order['shipping_address'] ) ) {
			$description_sections['shipping_address'] .= "**" . __( 'Shipping address', 'ltw-order-to-board-sync' ) . "**" . Helper::new_line( 2 );
			$description_sections['shipping_address'] .= $order['shipping_address'] . Helper::new_line( 2 );
			$description_sections['shipping_address'] .= "---" . Helper::new_line();
		}

		// Include shipping method and costs
		$description_sections['shipping_method'] = '';

		if ( ! empty( $card_settings['shipping_method'] ) ) {
			$description_sections['shipping_method'] .= "**" . __( 'Shipping method and costs', 'ltw-order-to-board-sync' ) . "**" . Helper::new_line( 2 );
			$description_sections['shipping_method'] .= $order['shipping_method'];
		}

		// Include a basic summary of the products in the order.
		$description_sections['ordered_products'] = '';

		if ( ! empty( $card_settings['ordered_products'] ) && ! empty( $order['ordered_products'] ) ) {
			$description_sections['ordered_products'] .= "**" . __( 'Products', 'ltw-order-to-board-sync' ) . "**" . Helper::new_line( 2 );

			foreach ( $order['ordered_products'] as $product ) {
				$product_name = $product['name'] ?? '';
				$product_quantity = absint( $product['quantity'] ?? 0 );
				$product_price = $product['price'] ?? '';

				if ( $product_name === '' || $product_quantity === 0 ) {
					continue;
				}

				$description_sections['ordered_products'] .= sprintf(
					'* %s × %d%s',
					$product_name,
					$product_quantity,
					$product_price !== '' ? ' (' . $product_price . ')' : ''
				) . Helper::new_line();
			}

			$description_sections['ordered_products'] .= Helper::new_line();
			$description_sections['ordered_products'] .= "---" . Helper::new_line();
		}

		// Include total costs
		if ( ! empty( $card_settings['total_costs'] ) ) {
			$description_sections['total_costs'] = "**" . __( 'Total costs', 'ltw-order-to-board-sync' ) . "**" . Helper::new_line( 2 );
			$description_sections['total_costs'] .= $order['total_costs'];
		}

		$description_sections = apply_filters( Hooks::FILTER_TRELLO_CARD_DESCRIPTION_SECTIONS, $description_sections, $order, $card_settings );

		if ( ! is_array( $description_sections ) ) {
			$this->logger->error( __( 'Card description sections must be returned as an array.', 'ltw-order-to-board-sync' ) );

			return false;
		}

		foreach ( $description_sections as $section_id => $section_content ) {
			if ( ! is_string( $section_id ) || ! is_string( $section_content ) ) {
				$this->logger->error( __( 'Card description sections must have string IDs and string content.', 'ltw-order-to-board-sync' ) );

				return false;
			}
		}

		$card['desc'] = implode( '', array_values( $description_sections ) );

		// Check if supplied date is in valid format, do allow empty date!
		if ( ! empty( $card_settings['card_due_date'] ) && ! Helper::validate_date( $card_settings['card_due_date'] ) ) {
			$this->logger->error(
				sprintf( 
					__( 'Due date format is invalid for order status <strong>%s</strong>. Please try resaving the Cards page.', 'ltw-order-to-board-sync' ),
					$order['basic_info']['order_status']
				)
			);
			
			return false;
		}

		if ( ! empty( $card_settings['card_due_date'] ) ) {
			// Set card due date.
			// Change the format to the required one by Trello which is yyyy-MM-ddTHH:mm
			$card['due'] = Helper::get_trello_date_format( $card_settings['card_due_date'] );
		}

		// Set URL to the order in the admin area if option is selected
		if ( ! empty( $card_settings['include_order_url'] ) ) {
			$card['urlSource'] = $order['basic_info']['order_edit_url'];
		}

		// Set the position of the card, top or bottom
		if ( ! empty( $card_settings['card_position'] ) ) {
			$card['pos'] = $card_settings['card_position'];
		}

		// Make the request to Trello
		$card = apply_filters( Hooks::FILTER_TRELLO_CARD_DATA, $card, $order, $card_settings );
		$result = $this->post( 'cards', $card );

		if ( is_wp_error( $result) ) {
			// If something went wrong, log it!
			$this->logger->error( $result->get_error_message(), $result->get_error_data() );

			return false;
		} else {
			// First let's check if the card ID is available.
			$card_id = $result['id'] ?? '';

			if ( ! $card_id ) {
				// For some reason card id what not sent back, maybe we should check for error from API?
				$this->logger->error( __( 'No card ID found.', 'ltw-order-to-board-sync' ) );

				return false;
			} else {
				// Now let's update a card with color option.
				if ( ! empty( $card_settings['card_color'] ) ) {
					$this->set_card_color( $card_id, $card_settings );
				}
			}

			// Get order info
			$order_info = wc_get_order( $order['basic_info']['id'] );
			
			// Load the existing data if there is any
			$order_meta = $order_info->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, true );

			if ( empty( $order_meta ) || ! is_array( $order_meta ) ) {
				$order_meta = [];
			}

			// Get list name
			$list_name_result = $this->get_single_list_info( $result['idList'] );

			if ( is_wp_error( $list_name_result ) ) {
				$this->logger->error( $list_name_result->get_error_message(), $list_name_result->get_error_data() );

				// Just set it to empty and voila..
				$list_name = '';
			} else {
				$list_name = $list_name_result['name'];
			}

			// Add another entry to the history array
			$history_entry = [
				'board_id' => $board_info['id'],
				'board_name' => $board_info['name'],
				'list_id' => $result['idList'],
				'card_id' => $card_id,
				'list_name' => $list_name,
				'status' => $order['basic_info']['order_status'],
				'date' => time(),
			];
			$order_meta['history'][] = $history_entry;

			// Save data into order meta
			$order_info->update_meta_data( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, $order_meta );
			$order_info->save();

			do_action( Hooks::ACTION_CARD_CREATED, $order_info, $history_entry, $result );

			if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
				$this->logger->debug( __( 'Updated order meta history', 'ltw-order-to-board-sync' ), $order_meta['history'] );
			}

			$this->logger->info( __( 'Succesfully created card.', 'ltw-order-to-board-sync' ) );
		}

		return true;
	}

	/**
	 * Change Trello card color
	 *
	 * @param	string	$card_id	Trello card id which is 24 chars long
	 * @param	array	$card_settings
	 * @return	void
	 * @since	1.0
	 *
	 */
	private function set_card_color( string $card_id, array $card_settings = [] ): void {
		$update_card = [
			'cover' => [
				'color' => $card_settings['card_color'],
				'brightness' => 'light',
				'size' => 'normal',
				'idAttachment' => null,
				'idUploadedBackground' => null,
			]
		];

		// Update the card with the new color settings
		$result = $this->put( 'cards/' . $card_id, $update_card );

		// Just in case something goes wrong while updating the card, let's log it!
		if ( is_wp_error( $result ) ) {
			$this->logger->error( $result->get_error_message(), $result->get_error_data() );
		} else {
			// Used for debugging purposes only..
			if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
				$this->logger->debug( __( 'Succesfully updated card.', 'ltw-order-to-board-sync' ), $result );
			}
		}
	}

	/**
	 * Change Trello card color
	 *
	 * @param	string	$card_id	Trello card id which is 24 chars long
	 * @param	array	$card_settings
	 * @return	void
	 * @since	1.0
	 *
	 */
	private function set_card( string $card_id, array $card_settings = [] ): void {
		$update_card = [
			'cover' => [
				'color' => $card_settings['card_color'],
				'brightness' => 'light',
				'size' => 'normal',
				'idAttachment' => null,
				'idUploadedBackground' => null,
			]
		];

		// Update the card with the new color settings
		$result = $this->put( 'cards/' . $card_id, $update_card );

		// Just in case something goes wrong while updating the card, let's log it!
		if ( is_wp_error( $result ) ) {
			$this->logger->error( $result->get_error_message(), $result->get_error_data() );
		} else {
			// Used for debugging purposes only..
			if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
				$this->logger->debug( __( 'Succesfully updated card.', 'ltw-order-to-board-sync' ), $result );
			}
		}
	}
}