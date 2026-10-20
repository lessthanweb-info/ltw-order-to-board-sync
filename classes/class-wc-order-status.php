<?php
namespace LTW\Order_To_Board_Sync\WC;

/**
 * 	WooCommerce compatibility declarations
 *
 * 	@author		lessthanweb
 * 	@package 	LTW_Order_To_Board_Sync
 * 	@since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Core\Hooks;

/**
 * WC Class
 */
class Order_Status {
	/**
	 * WooCommerce's built-in order status keys.
	 *
	 * @var	array
	 * @since	1.0.0
	 *
	 */
	private const DEFAULT_ORDER_STATUS_KEYS = [
		'wc-pending' => true,
		'wc-processing' => true,
		'wc-on-hold' => true,
		'wc-completed' => true,
		'wc-cancelled' => true,
		'wc-refunded' => true,
		'wc-failed' => true,
	];

	/**
	 *	Constructor
	 *
	 * 	@param	void
	 * 	@return	void
	 * 	@since	1.0.0
	 *
	 */
	public function __construct() {

    }

    /**
	 * 	Get the built-in WC order statuses and allow extensions to modify the list.
	 *
	 * 	@param	void
	 * 	@return	array
	 * 	@since	1.0.0
	 *
	 */
    public function get_list_of_order_statuses(): array {
		$order_statuses = array_intersect_key( wc_get_order_statuses(), self::DEFAULT_ORDER_STATUS_KEYS );

		return apply_filters( HOOKS::FILTER_ORDER_STATUSES, $order_statuses );
    }
}