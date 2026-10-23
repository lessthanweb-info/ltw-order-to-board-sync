<?php
/*
 * LTW Order-to-Board Sync for WooCommerce and Trello
 *
 * @package           LTW_Order_To_Board_Sync
 * @author            lessthanweb
 * @copyright         2026 lessthanweb
 * @license           GPL v2 or later
 *
 * @wordpress-plugin
 * Plugin Name:       LTW Order-to-Board Sync for WooCommerce and Trello
 * Plugin URI:        https://www.lessthanweb.com/order-to-board-sync-plugin
 * Description:       Automatically create Trello cards from WooCommerce orders with configurable, per-status card details.
 * Version:           1.1.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Requires Plugins:  woocommerce
 * Author:            lessthanweb
 * Author URI:        https://www.lessthanweb.com/order-to-board-sync-plugin
 * Text Domain:       ltw-order-to-board-sync
 * Domain Path:       /languages
 * 
 * WC requires at least: 8.0
 * WC tested up to: 8.3
 * 
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace LTW\Order_To_Board_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_VERSION', '1.1.0' );

/**
 * Production or dev mode?
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_DEBUG', false );

/**
 * Option name for storing the plugin settings.
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_SETTINGS', 'ltw_order_to_board_sync_settings' );

/**
 * Admin page query string name
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME', 'ltw-order-to-board-sync' );

/**
 * Hour in seconds, mainly used for setting the transient expire time
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_HOUR_IN_SECONDS', 3600 );

/**
 * Set plugin path and url
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LTW_ORDER_TO_BOARD_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LTW_ORDER_TO_BOARD_SYNC_PLUGIN_FILENAME', basename( __FILE__ ) );

/**
 * Meta field key for storing Trello cards data on orders
 *
 * @since	1.0.0
 *
 */
define( 'LTW_ORDER_TO_BOARD_SYNC_ORDER_META_FIELD', 'ltw_order_to_board_sync_cards' );

/**
 * Autoload classes
 *
 * @since	1.0.0
 *
 */
require_once LTW_ORDER_TO_BOARD_SYNC_PLUGIN_DIR . 'autoload.php';

use LTW\Order_To_Board_Sync\Core;
use LTW\Order_To_Board_Sync\WC;
use LTW\Order_To_Board_Sync\Admin;
use LTW\Order_To_Board_Sync\Status;

/**
 * LTW Order-to-Board Sync plugin bootstrap
 */
class LTW_Order_To_Board_Sync {
    /**
     * Constructor
     *
     * @param	void
     * @return	void
     * @since	1.0.0
     *
     */
    public function __construct() {
        //  Logger
        $logger = Core\Logger::get_instance();
        
        //  Config of all the admin fields
        $fields = new Core\Fields();

        //  Deals with saving/reading settings
        $settings = new Core\Settings( $fields );

        //  Handles Trello OAuth authorization and token refresh.
        $trello_oauth = new Core\Trello_OAuth( $settings, $logger );
        
        //  Lists all available order statuses
        $order_status = new WC\Order_Status();

        //  System info
        $system_info = new Status\System_Info();
        
        //  WC order info methods
        $order_info = new WC\Order_Info( $settings, $order_status, $logger );
        
        //  Trello API
        $trello_api = new Core\Trello_API( $settings, $order_info, $logger, $trello_oauth );

        //  WC order admin customizations
        new WC\Order_Admin( $settings, $trello_api, $logger );

        //  Admin area only
        if ( is_admin() ) {
            //  Rendering admin notices
            $notices = new Admin\Notices();

            //  Deals with settings and admin notices
            new Admin\Settings_Handler( $settings, $notices );

            //  Echoes required assets
            new Admin\Assets();

            //  UI components
            $ui_components = new Admin\UI\Components();

            //  Help documentation
            $help = new Admin\Help();

            //  Renders admin page
            $page = new Admin\Page( $order_status, $ui_components, $settings, $fields, $system_info, $logger, $help, $trello_oauth );

            //  Registers admin submenu
            new Admin\Menu( $page );
        }
    }
}

/**
 * Register HPOS compatibility before WooCommerce fires before_woocommerce_init.
 *
 */
new WC\Support();

add_action(
	'init',
	static function (): void {
		new LTW_Order_To_Board_Sync();
	},
	10
);

/**
 * Check if we have Woocommerce installed and active.
 *
 */
if ( is_admin() ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( class_exists( 'WooCommerce' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p>' . esc_html__( 'LTW Order-to-Board Sync requires WooCommerce to be installed and active.', 'ltw-order-to-board-sync' ) . '</p></div>';
		}
	);
}