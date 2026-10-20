<?php
namespace LTW\Order_To_Board_Sync\WC;

/**
 * WooCommerce order info methods
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Core\Helper;
use LTW\Order_To_Board_Sync\Core\Settings;
use LTW\Order_To_Board_Sync\Core\Logger;
use LTW\Order_To_Board_Sync\Core\Hooks;
use LTW\Order_To_Board_Sync\WC\Order_Status;

/**
 * WC Class
 */
class Order_Info {
	/**
	 * Constructor
	 *
	 * @param	void
	 * @since	1.0.0
	 *
	 */
	public function __construct( 
		private Settings $settings, 
		private Order_Status $order_status, 
		private Logger $logger
	) {

    }

	/**
	 * Gets basic info for the order.
	 *
	 * @param	object	$order	// WC_Order
	 * @return	array
	 * @since	1.0
	 *
	 */
	public function get_basic_order_info( object $order ): array {
		// Get order ID
		$data['id'] = $order->get_id();
		
		// Order status
		$data['order_status'] = $order->get_status();

		// Get full name
		$data['billing_name'] = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$data['billing_first_name'] = trim( $order->get_billing_first_name() );
		$data['billing_last_name'] = trim( $order->get_billing_last_name() );

		// Get billing company
		$data['billing_company'] = $order->get_billing_company();

		// Get billing email
		$data['billing_email'] = $order->get_billing_email();

		// Order admin URL
		$data['order_edit_url'] = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_edit_url( $data['id'] );

		// Get order date
		$data['order_create_date'] = $order->get_date_created();
		// Get order completed date
		$data['order_completed_date'] = $order->get_date_completed();
		// Get payment date if possible
		$data['order_payment_date'] = $order->get_date_paid();

		// Get total order cost
		$data['order_total'] = Helper::wc_price( $order->get_total() );

		return apply_filters( Hooks::FILTER_ORDER_BASIC_INFO, $data, $order );
	}

	/**
	 * Gets billing address for the order.
	 *
	 * @param	object	$order	// WC_Order
	 * @return	string
	 * @since	1.0
	 *
	 */
	public function get_billing_address( object $order ): string {
		$billing_text = '';

		// Set billing address only if there is any
		if ( $order->has_billing_address() ) {
			// Array with billing data can be modified via the filter
			$billing_data = apply_filters( Hooks::FILTER_ORDER_BILLING_ADDRESS, 
				[
					[
						'text' => __( 'First name:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_first_name(),
					],
					[
						'text' => __( 'Last name:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_last_name(),
					],
					[
						'text' => __( 'Company:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_company(),
					],
					[
						'text' => __( 'Address:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
					],
					[
						'text' => __( 'City:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_city(),
					],
					[
						'text' => __( 'State:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_state(),
					],
					[
						'text' => __( 'Post code:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_billing_postcode(),
					],
					[
						'text' => __( 'Country:', 'ltw-order-to-board-sync' ),
						'value' => WC()->countries->countries[ $order->get_billing_country() ],
					],
				],
			$order );

			// Now let's return only items that have any value
			foreach( $billing_data as $billing ) {
				if ( trim( $billing['value'] ) != '' ) {
					$billing_text .= $billing['text'] . ' ' . $billing['value'] . Helper::new_line();
				}
			}
		}

		return apply_filters( Hooks::FILTER_ORDER_BILLING_ADDRESS_TEXT, $billing_text, $order );
	}

	/**
	 * Gets shipping address for the order.
	 *
	 * @param	object	$order	// WC_Order
	 * @return	string
	 * @since	1.0
	 *
	 */
	public function get_shipping_address( object $order ): string {
		$shipping_text = '';

		// Set shipping address only if there is any
		if ( $order->has_shipping_address() ) {
			$shipping_data = apply_filters( Hooks::FILTER_ORDER_SHIPPING_ADDRESS,
				[
					[
						'text' => __( 'First name:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_first_name(),
					],
					[
						'text' => __( 'Last name:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_last_name(),
					],
					[
						'text' => __( 'Company:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_company(),
					],
					[
						'text' => __( 'Address:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
					],
					[
						'text' => __( 'City:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_city(),
					],
					[
						'text' => __( 'State:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_state(),
					],
					[
						'text' => __( 'Post code:', 'ltw-order-to-board-sync' ),
						'value' => $order->get_shipping_postcode(),
					],
					[
						'text' => __( 'Country:', 'ltw-order-to-board-sync' ),
						'value' => WC()->countries->countries[ $order->get_shipping_country() ],
					],
				],
			$order );

			// Now let's return only items that have any value
			foreach( $shipping_data as $shipping ) {
				if ( trim( $shipping['value'] ) != '' ) {
					$shipping_text .= $shipping['text'] . ' ' . $shipping['value'] . Helper::new_line();
				}
			}
		}

		return apply_filters( Hooks::FILTER_ORDER_SHIPPING_ADDRESS_TEXT, $shipping_text, $order );
	}

	/**
	 * Gets shipping method.
	 * 
	 * It takes all shipping methods from current order and calculates costs of shipping.
	 *
	 * @param	object	$order	// WC_Order
	 * @return	array
	 * @since	1.0
	 *
	 */
	private function get_shipping_method( object $order ): array {
		$data = [];

		// Get all shipping merthods for the current order
		$shipping_methods = $order->get_shipping_methods();

		// Build the array with shipping info
		$data = [
			'has_shipping' => $shipping_methods,
			'shipping_methods' => [],	// This will be filled with each shipping item
			'total_shipping' => (float) $order->get_shipping_total(),
			'total_tax' => (float) $order->get_shipping_tax(),
			'total' => (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(),
		];

		// Get all shipping items
		foreach ( $shipping_methods as $item_id => $item ) {
			$method_data = [
				'item_id' => $item_id,
				'name' => $item->get_name(), // Shipping method title
				'method_id' => $item->get_method_id(), // e.g. 'flat_rate', 'free_shipping'
				'instance_id' => $item->get_instance_id(),
				'cost' => (float) $item->get_total(),
				'tax' => (float) $item->get_total_tax(),
				'total' => (float) $item->get_total() + (float) $item->get_total_tax(),
				'taxes' => $item->get_taxes(), // Detailed tax data
				'meta_data' => $item->get_meta_data(), // Any additional meta
			];

			$data['shipping_methods'][] = $method_data;
		}

		return apply_filters( Hooks::FILTER_ORDER_SHIPPING_METHOD, $data, $order );
	}

	/**
	 * Create final text version for shipping method so that it can
	 * be called directly in trello api code.
	 *
	 * @param	object	$order
	 * @return	string
	 * @since	1.0
	 *
	 */
	public function get_shipping_method_text( object $order ): string {
		$data = $this->get_shipping_method( $order );

		$text = '';

		// Now let's build the text.
		if ( ! empty( $data['has_shipping'] ) && ! empty( $data['shipping_methods'] ) ) {
			/*
			Final text should be:
			Shipping methods:
			- name
			- cost
			- tax
			- total

			After that it should display the totals:
			- total shipping
			- total tax
			- total
			*/

			$shipping_lines = [];

			// First create a list of shipping methods.
			// By default WC supports only one but using plugin you can have multiple so
			// let's include that.
			if ( ! empty( $data['shipping_methods'] ) ) {
				foreach( $data['shipping_methods'] as $method ) {
					$shipping_lines[] = sprintf(
						__( '%s → %s (cost: %s, tax: %s) %s%s%s', 'ltw-order-to-board-sync' ),
						$method['name'],
						Helper::wc_price( $method['total'] ),
						Helper::wc_price( $method['cost'] ),
						Helper::wc_price( $method['tax'] ),
						Helper::new_line(),
						'---',	// This is markdown for trello to insert a line
						Helper::new_line( 2 )
					);
				}

				$text .= implode( Helper::new_line(), $shipping_lines );
			}

			// Now let's inlcude the totals
			$text .= __( 'Total shipping:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $data['total_shipping'] ) . Helper::new_line();
			$text .= __( 'Total tax:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $data['total_tax'] ) . Helper::new_line();
			$text .= __( 'Total:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $data['total'] ) . Helper::new_line();
		}

		return apply_filters( Hooks::FILTER_ORDER_SHIPPING_METHOD, $text, $order );
	}

	/**
	 * Gets order costs.
	 * 
	 * Just displays the total order costs with taxes and discounts if available.
	 * 
	 * It also includes shipping costs as a total.
	 *
	 * @param	object	$order	// WC_Order
	 * @return	string
	 * @since	1.0
	 *
	 */
	public function get_order_costs( object $order ): string {
		$text = '';

		$text .= __( 'Subtotal:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $order->get_subtotal() ) . Helper::new_line();
		$text .= __( 'Discount:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $order->get_discount_total() ) . Helper::new_line();
		$text .= __( 'Shipping:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $order->get_shipping_total() ) . Helper::new_line();
		$text .= __( 'Tax:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $order->get_total_tax() ) . Helper::new_line();
		$text .= __( 'Total:', 'ltw-order-to-board-sync' ) . ' ' . Helper::wc_price( $order->get_total() ) . Helper::new_line();

		return $text;
	}

	/**
	 * Get a list of ordered products with product id, name and quantity.
	 *
	 * @param	object	$order	// WC_Order
	 * @return	array
	 * @since	1.0
	 *
	 */
	public function get_ordered_products( object $order ): array {
		$products = [];

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$products[] = [
				'product_id' => $item->get_product_id(),
				'name' => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'price' => Helper::wc_price( (float) $item->get_total() ),
			];
		}

		return apply_filters( HOOKS::FILTER_ORDERED_PRODUCTS, $products, $order );
	}
}