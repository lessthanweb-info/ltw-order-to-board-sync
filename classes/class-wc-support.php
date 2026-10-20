<?php
namespace LTW\Order_To_Board_Sync\WC;

/**
 * WooCommerce compatibility declarations
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
 * WC Class
 */
class Support {
	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct() {
        add_action( 'before_woocommerce_init', [ $this, 'declare_support' ] );
    }

    /**
	 * Declares support for HPOS in WooCommerce.
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
    public function declare_support(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
				'custom_order_tables', 
				LTW_ORDER_TO_BOARD_SYNC_PLUGIN_DIR . LTW_ORDER_TO_BOARD_SYNC_PLUGIN_FILENAME, 
				true
			);
        }
    }
}