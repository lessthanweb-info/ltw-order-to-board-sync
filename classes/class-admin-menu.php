<?php
namespace LTW\Order_To_Board_Sync\Admin;

/**
 * Register submenu item for WooCommerce menu
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Admin\Page;

/**
 * Menu Class
 */
class Menu {
	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct( private Page $page ) {
        //  Register menu item
        add_action( 'admin_menu', [ $this, 'register_menu' ], 45 );
    }

    /**
	 * Register new submenu item under WooCommerce menu
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
    public function register_menu(): void {
        add_submenu_page(
			'woocommerce',
            __( 'Trello', 'ltw-order-to-board-sync' ),
            __( 'Trello', 'ltw-order-to-board-sync' ),
            'manage_options',
            'ltw-order-to-board-sync',
            [ $this->page, 'render' ]
        ); 
    }
}