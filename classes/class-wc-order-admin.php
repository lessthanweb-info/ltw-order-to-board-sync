<?php
namespace LTW\Order_To_Board_Sync\WC;

/**
 * WooCommerce order admin customizations
 *
 * Handles custom columns in order table and metabox on edit order page
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
use LTW\Order_To_Board_Sync\Core\Trello_API;
use LTW\Order_To_Board_Sync\Core\Hooks;
use LTW\Order_To_Board_Sync\Core\Logger;
use Automattic\WooCommerce\Utilities\OrderUtil;
use LTW\Order_To_Board_Sync\Core\Helper;

/**
 * WC Order Admin Class
 */
class Order_Admin {
	/**
	 * Column name for the orders list page
	 *
	 * @const	COLUMN_NAME
	 * @since	1.0.0
	 *
	 */
	protected const COLUMN_NAME = 'ltw_order_to_board_sync_data';

	/**
	 * SVG for the icon for the orders list page
	 *
	 * @var	string
	 * @since	1.0.0
	 *
	 */
	protected string $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><defs><style>.ltw-order-to-board-sync-bg{fill:#fff;opacity:0;}.ltw-order-to-board-sync-icon{fill:#3858e9;}</style></defs><g><rect class="ltw-order-to-board-sync-bg" width="24" height="24" transform="translate(24 24) rotate(180)"/><path class="ltw-order-to-board-sync-icon" d="M12,2A10,10,0,1,0,22,12,10,10,0,0,0,12,2Zm0,18a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z"/><circle class="ltw-order-to-board-sync-icon" cx="12" cy="8" r="1"/><path class="ltw-order-to-board-sync-icon" d="M12,10a1,1,0,0,0-1,1v5a1,1,0,0,0,2,0V11A1,1,0,0,0,12,10Z"/></g></svg>';

	/**
	 * Constructor
	 *
	 * @param	Settings	$settings
	 * @param	Logger		$logger
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct( 
		private Settings $settings,
		private Trello_API $trello_api,
		private Logger $logger
	) {
		$this->register_order_admin_hooks();
	}

	/**
	 * Check if HPOS is enabled and based on that add the correct hooks for WC pages
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	private function register_order_admin_hooks(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			add_filter( 'woocommerce_shop_order_list_table_columns', [ $this, 'add_custom_column' ] );
			add_action( 'woocommerce_shop_order_list_table_custom_column', [ $this, 'render_custom_column_hpos' ], 10, 2 );
		} else {
			// WooCommerce column hooks (for non-HPOS)
			add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_custom_column' ] );
			add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_custom_column_wc' ], 10, 2 );
		}

		// Add metabox on edit order page (works for both HPOS and traditional)
		add_action( 'add_meta_boxes', [ $this, 'add_order_metabox' ], 10 );
		
		// Save metabox data
		add_action( 'save_post_shop_order', [ $this, 'save_order_metabox' ], 10 );
	}

	/**
	 * Add custom column to orders table
	 *
	 * @param	array	$columns	Existing columns
	 * @return	array
	 * @since	1.0.0
	 *
	 */
	public function add_custom_column( array $columns ): array {
		if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
			$this->logger->debug( 'add_custom_column called with columns: ' . implode( ', ', array_keys( $columns ) ) );
		}

		// Add the custom column after the order total
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( $key === 'order_total' ) {
				$new_columns[ self::COLUMN_NAME ] = __( 'Trello Cards', 'ltw-order-to-board-sync' );
			}
		}

		if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
			$this->logger->debug( 'add_custom_column returning columns: ' . implode( ', ', array_keys( $new_columns ) ) );
		}
		return $new_columns;
	}

	/**
	 * Render data column content in orders table, non-HPOS
	 *
	 * @param	string	$column	Column name
	 * @param	integer	$post_id	Order post ID
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function render_custom_column( string $column, int $post_id ): void {
		if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
			$this->logger->debug( "render_custom_column called for column: {$column}, post_id: {$post_id}" );
		}

		if ( $column !== self::COLUMN_NAME ) {
			return;
		}

		//  Get current order info
		$order = wc_get_order( $post_id );

		//  If order info is not available, return null
		if ( ! $order ) {
			if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
				$this->logger->debug( "Order not found for post_id: {$post_id}" );
			}

			return;
		}

		// Get custom meta value
		$meta_data = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD );

		$meta_data_history = is_array( $meta_data ) && isset( $meta_data['history'] ) && is_array( $meta_data['history'] ) ? $meta_data['history'] : [];
		$last_failure = is_array( $meta_data ) && isset( $meta_data['last_failure'] ) && is_array( $meta_data['last_failure'] ) ? $meta_data['last_failure'] : [];

		if ( empty( $meta_data_history ) ) {
			echo '<em class="tips ltw-order-to-board-sync-tip-empty" data-tip="' . __( 'No cards created', 'ltw-order-to-board-sync' ) . '">' . $this->svg_icon . '</em>';
		} else {
			$card_history_count = count( $meta_data_history );

			echo '<em class="tips ltw-order-to-board-sync-tip" data-tip="' . sprintf( __( '%d cards created', 'ltw-order-to-board-sync' ), $card_history_count ) . '">' . $this->svg_icon . '</em>';
		}
	}

	/**
	 * Render data column content in orders table when HPOS is enabled
	 *
	 * @param	string	$column		Column name
	 * @param	object	$order		WC_Order object
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function render_custom_column_hpos( string $column, $order ): void {
		if ( $column !== self::COLUMN_NAME ) {
			return;
		}

		if ( ! $order ) {
			if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
				$this->logger->debug( "No order found" );
			}
			
			return;
		}

		// Get custom meta value
		$meta_data = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD );

		$meta_data_history = is_array( $meta_data ) && isset( $meta_data['history'] ) && is_array( $meta_data['history'] ) ? $meta_data['history'] : [];

		if ( empty( $meta_data_history ) ) {
			echo '<em class="tips ltw-order-to-board-sync-tip-empty" data-tip="' . __( 'No cards created', 'ltw-order-to-board-sync' ) . '">' . $this->svg_icon . '</em>';
		} else {
			$card_history_count = count( $meta_data_history );

			echo '<em class="tips ltw-order-to-board-sync-tip" data-tip="' . sprintf( __( '%d cards created', 'ltw-order-to-board-sync' ), $card_history_count ) . '">' . $this->svg_icon . '</em>';
		}
	}

	/**
	 * Render custom column content in orders table (WooCommerce non-HPOS)
	 *
	 * @param	string	$column	Column name
	 * @param	integer	$post_id	Order post ID
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function render_custom_column_wc( string $column, int $post_id ): void {
		if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
			$this->logger->debug( "render_custom_column_wc called for column: {$column}, post_id: {$post_id}" );
		}

		if ( $column !== self::COLUMN_NAME ) {
			return;
		}

		//  Get current order info
		$order = wc_get_order( $post_id );

		//  If order info is not available, return null
		if ( ! $order ) {
			if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
				$this->logger->debug( "Order not found for post_id: {$post_id}" );
			}

			return;
		}

		// Get custom meta value
		$meta_data = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD );

		$meta_data_history = is_array( $meta_data ) && isset( $meta_data['history'] ) && is_array( $meta_data['history'] ) ? $meta_data['history'] : [];

		if ( empty( $meta_data_history ) ) {
			echo '<em class="tips ltw-order-to-board-sync-tip-empty" data-tip="' . __( 'No cards created', 'ltw-order-to-board-sync' ) . '">' . $this->svg_icon . '</em>';
		} else {
			$card_history_count = count( $meta_data_history );

			echo '<em class="tips ltw-order-to-board-sync-tip" data-tip="' . sprintf( __( '%d cards created', 'ltw-order-to-board-sync' ), $card_history_count ) . '">' . $this->svg_icon . '</em>';
		}
	}

	/**
	 * Add metabox on order edit page
	 *
	 * @param	mixed	$post_or_post_type	Post object or post type string
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function add_order_metabox( $post_or_post_type ): void {
		//  Resolves to 'woocommerce_page_wc-orders'
		$screen = wc_get_page_screen_id( 'shop-order' );

		if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
			$this->logger->debug( "add_order_metabox called with: " . $screen );
		}

		add_meta_box(
			'ltw_order_to_board_sync_order_metabox',
			__( 'Trello Cards', 'ltw-order-to-board-sync' ),
			[ $this, 'render_order_metabox' ],
			$screen,
			'side',
			'default'
		);

		if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
			$this->logger->debug( 'Metabox added successfully' );
		}
	}

	/**
	 * Render metabox content on order edit page
	 *
	 * @param	object  $post   Order object
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function render_order_metabox( \Automattic\WooCommerce\Admin\Overrides\Order|\WP_Post $post ): void {
		$order_id = $post instanceof \WP_Post ? $post->ID : $post->get_id();
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Get meta value
		$meta_data = $order->get_meta( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD );

		wp_nonce_field( 'ltw_order_to_board_sync_order_metabox', 'ltw_order_to_board_sync_nonce' );
		?>
		<div class="ltw-order-to-board-sync-order-metabox">
			<?php
			$meta_data_history = is_array( $meta_data ) && isset( $meta_data['history'] ) && is_array( $meta_data['history'] ) ? $meta_data['history'] : [];

			if ( empty( $meta_data_history ) ) {
				echo '<p>' . esc_html__( 'Currently there are no cards created for this order.', 'ltw-order-to-board-sync' ). '</p>';
			} else {
				echo '<p>' . esc_html__( 'Card creation history for this order:', 'ltw-order-to-board-sync' ). '</p>';
			}

			// Render history if there is any.
			// The DIV container should always be set as we need it when adding new card and no history exists
			echo '<div class="ltw-order-to-board-sync-card-history-container">';
			echo Helper::get_trello_card_history_data( $meta_data_history )['html'];
			echo '</div>';

			echo '<hr>';

			// Now we need to add option for the admins to manually add the card.
			// Admins should be able to select board, list and status of the order. The rest is managed by the plugin settings.
			echo '<p>' . __( 'Create a card manually for this order.', 'ltw-order-to-board-sync' ) . '</p>';

			// First we need to echo board and list select boxes using WP admin markup
			?>
			<div class="ltw-order-to-board-sync ltw-order-to-board-sync-edit-order">
				<?php
				/**
				 * Add extra HTML before the fields.
				 * Order object is sent to this action.
				 *
				 * @param	object	$order	WC_Order object
				 * @since	1.0.0
				 */
				do_action( Hooks::ACTION_EDIT_ORDER_METABOX_BEFORE_FIELDS, $order );
				?>

				<p class="form-field ltw-order-to-board-sync-field">
					<label for="ltw-order-to-board-sync-board-select"><?php esc_html_e( 'Board', 'ltw-order-to-board-sync' ); ?></label>
					<select id="ltw-order-to-board-sync-board-select" name="ltw_order_to_board_sync_order[board_id]" class="regular-text ltw-order-to-board-sync-boards" data-selected="" aria-label="<?php esc_attr_e( 'Board', 'ltw-order-to-board-sync' ); ?>">
						<option value=""><?php esc_html_e( 'Loading boards...', 'ltw-order-to-board-sync' ); ?></option>
					</select>
					<span class="loader spinner"></span>
				</p>

				<p class="form-field ltw-order-to-board-sync-field">
					<label for="ltw-order-to-board-sync-list-select"><?php esc_html_e( 'List', 'ltw-order-to-board-sync' ); ?></label>
					<select id="ltw-order-to-board-sync-list-select" name="ltw_order_to_board_sync_order[list_id]" class="regular-text ltw-order-to-board-sync-lists" data-selected="" aria-label="<?php esc_attr_e( 'List', 'ltw-order-to-board-sync' ); ?>">
						<option value=""><?php esc_html_e( 'Select a board to load its lists', 'ltw-order-to-board-sync' ); ?></option>
					</select>
					<span class="loader spinner"></span>
				</p>

				<?php
				/**
				 * Add extra HTML after the fields.
				 * Order object is sent to this action.
				 *
				 * @param	object	$order	WC_Order object
				 * @since	1.0.0
				 */
				do_action( Hooks::ACTION_EDIT_ORDER_METABOX_AFTER_FIELDS, $order );
				?>

				<p class="form-field ltw-order-to-board-sync-field ltw-order-to-board-sync-actions">
					<button type="button" id="ltw-order-to-board-sync-create-card" class="button button-primary ltw-order-to-board-sync-create-card" data-order-id="<?php echo esc_attr( $order_id ); ?>" <?php echo ! empty( $last_failure ) ? ' style="display:none"' : ''; ?>><?php esc_html_e( 'Create card', 'ltw-order-to-board-sync' ); ?></button>
					<button type="button" id="ltw-order-to-board-sync-retry-card" class="button ltw-order-to-board-sync-retry-card" data-order-id="<?php echo esc_attr( $order_id ); ?>" <?php echo empty( $last_failure ) ? ' style="display:none"' : ''; ?>><?php esc_html_e( 'Retry failed card', 'ltw-order-to-board-sync' ); ?></button>
					<span class="loader spinner"></span>
				</p>

				<div class="ltw-order-to-board-sync-result" aria-live="polite"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save metabox data
	 *
	 * @param	integer	$post_id	Post ID
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function save_order_metabox( int $post_id ): void {
		//  Verify nonce
		if ( ! isset( $_POST['ltw_order_to_board_sync_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ltw_order_to_board_sync_nonce'] ) ), 'ltw_order_to_board_sync_order_metabox' ) ) {
			return;
		}

		//  Check if user has permission
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		//  Get and sanitize the input
		if ( isset( $_POST['ltw_custom_field'] ) ) {
			$custom_value = sanitize_text_field( wp_unslash( $_POST['ltw_custom_field'] ) );
			
			$order = wc_get_order( $post_id );

			if ( $order ) {
				$order->update_meta_data( LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD, $custom_value );
				//  This needs to be called for the new HPOS
				$order->save();

				if ( LTW_ORDER_TO_BOARD_SYNC_DEBUG ) {
					$this->logger->debug( sprintf( __( "Order #%d custom field updated: %s", 'ltw-order-to-board-sync' ), $post_id, $custom_value ) );
				}
			}
		}
	}
}
