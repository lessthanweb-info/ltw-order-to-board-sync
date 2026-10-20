<?php
namespace LTW\Order_To_Board_Sync\Core;

/**
 * Defines all available hooks for the LTW Order-to-Board Sync plugin
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
 * Hooks class
 *
 * Centralizes all filter and action hook names to prevent typos,
 * improve discoverability, and maintain consistency across the plugin.
 */
class Hooks {
	/**
	 * Filters
	 */
	
	/**
	 * Filter the basic order information before processing
	 *
	 * @param	array	$data		Order data array containing id, status, dates, etc.
	 * @param	object	$order		WC_Order object
	 * @return	array	Filtered order data
	 * @since	1.0.0
	 */
	const FILTER_ORDER_BASIC_INFO = 'ltw_order_to_board_sync_order_basic_info';
	
	/**
	 * Filter order billing address.
	 * 
	 * Array with all the billing lines.
	 *
	 * @param	array	$address	Billing address array
	 * @param	object	$order		WC_Order object
	 * @return	array
	 * @since	1.0.0
	 */
	const FILTER_ORDER_BILLING_ADDRESS = 'ltw_order_to_board_sync_order_billing_address';

    /**
	 * Filter order billing address text
	 *
	 * @param	string	$address	Formatted billing address string
	 * @param	object	$order		WC_Order object
	 * @return	string	Filtered billing address
	 * @since	1.0.0
	 */
	const FILTER_ORDER_BILLING_ADDRESS_TEXT = 'ltw_order_to_board_sync_order_billing_address_text';
	
	/**
	 * Filter order shipping address.
	 * 
	 * This is an array with all the shipping address lines.
	 *
	 * @param	array	$address	Shipping address array
	 * @param	object	$order		WC_Order object
	 * @return	array
	 * @since	1.0.0
	 */
	const FILTER_ORDER_SHIPPING_ADDRESS = 'ltw_order_to_board_sync_order_shipping_address';

	/**
	 * Filter order shipping address text
	 *
	 * @param	string	$address	Formatted shipping address string
	 * @param	object	$order		WC_Order object
	 * @return	string	Filtered shipping address
	 * @since	1.0.0
	 */
	const FILTER_ORDER_SHIPPING_ADDRESS_TEXT = 'ltw_order_to_board_sync_order_shipping_address_text';

	/**
	 * Filter shipping method array
	 *
	 * @param	string	$data	Formatted shipping method array
	 * @param	object	$order		WC_Order object
	 * @return	array
	 * @since	1.0.0
	 */
	const FILTER_ORDER_SHIPPING_METHOD = 'ltw_order_to_board_sync_order_shipping_method';

	/**
	 * Filter shipping method final text
	 *
	 * @param	string	$text	Contains text for shipping method
	 * @param	object	$order		WC_Order object
	 * @return	array
	 * @since	1.0.0
	 */
	const FILTER_ORDER_SHIPPING_METHOD_TEXT = 'ltw_order_to_board_sync_order_shipping_method_text';

	/**
	 * Filter ordered products
	 *
	 * @param	array	$products	Array with ordered products and the data we need
	 * @param	object	$order		WC_Order object
	 * @return	array
	 * @since	1.0.0
	 */
	const FILTER_ORDERED_PRODUCTS = 'ltw_order_to_board_sync_ordered_products';

	/**
	 * Filter admin notice message before display
	 *
	 * @param	string	$message	The notice message
	 * @return	string	Filtered message
	 * @since	1.0.0
	 */
	const FILTER_ADMIN_NOTICE_MESSAGE = 'ltw_order_to_board_sync_notice_message';

	/**
	 * Filter for core fields setup
	 *
	 * @param	array	$fields	List of fields under tab Cards.
	 * @return	array	Filtered fields array
	 * @since	1.0.0
	 */
	const FILTER_FIELDS_CARDS = 'ltw_order_to_board_sync_fields_cards';

	/**
	 * Filter for settings fields setup
	 *
	 * @param	array	$fields	List of fields under tab Settings.
	 * @return	array	Filtered fields array
	 * @since	1.0.0
	 */
	const FILTER_FIELDS_SETTINGS = 'ltw_order_to_board_sync_fields_settings';

	/**
	 * Filter for status fields setup
	 *
	 * @param	array	$fields	List of fields under tab Status.
	 * @return	array	Filtered fields array
	 * @since	1.0.0
	 */
	const FILTER_FIELDS_STATUS = 'ltw_order_to_board_sync_fields_status';
	
	/**
	 * Filter for card fields rows. This is required filter to be set when using FILTER_FIELDS_CARDS else the fields will not be shown.
	 *
	 * @param	array	$fields	List of fields under tab Settings.
	 * @return	array	Filtered fields array
	 * @since	1.0.0
	 */
	const FILTER_FIELDS_CARDS_ROWS = 'ltw_order_to_board_sync_fields_cards_rows';

	/**
	 * Filter the rendered HTML for each repeatable settings row.
	 *
	 * @param	string	$row_html	Rendered repeatable row HTML.
	 * @param	array	$context	Section, context, index, and row values.
	 * @return	string	Filtered row HTML.
	 * @since	1.0.0
	 */
	const FILTER_REPEATABLE_ROW_HTML = 'ltw_order_to_board_sync_repeatable_row_html';

	/**
	 * Filter the rendered HTML for a configured settings field.
	 *
	 * @param	string	$field_html	Rendered field HTML.
	 * @param	array	$field	Field definition.
	 * @param	string	$field_key	Field key.
	 * @param	string	$section_name	Settings section.
	 * @param	string	$context_key	Field context key.
	 * @param	mixed	$field_value	Current field value.
	 * @return	string	Filtered field HTML.
	 * @since	1.0.0
	 */
	const FILTER_FIELD_HTML = 'ltw_order_to_board_sync_field_html';

	/**
	 * Filter for card fields rows. This is required filter to be set when using FILTER_FIELDS_SETTINGS else the fields will not be shown.
	 *
	 * @param	array	$fields	List of fields under tab Settings.
	 * @return	array	Filtered fields array
	 * @since	1.0.0
	 */
	const FILTER_FIELDS_SETTINGS_ROWS = 'ltw_order_to_board_sync_fields_settings_rows';

	/**
	 * Filter for card fields rows. This is required filter to be set when using FILTER_FIELDS_STATUS else the fields will not be shown.
	 *
	 * @param	array	$fields	List of fields under tab Settings.
	 * @return	array	Filtered fields array
	 * @since	1.0.0
	 */
	const FILTER_FIELDS_STATUS_ROWS = 'ltw_order_to_board_sync_fields_status_rows';
	
	/**
	 * Filter Trello API GET args
	 *
	 * @param	array	$args		wp_remote_get arguments
	 * @param	string	$endpoint	Trello API endpoint
	 * @return	array	Filtered get arguments
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_API_GET_ARGS = 'ltw_order_to_board_sync_api_get_args';

	/**
	 * Filter Trello API POST args
	 *
	 * @param	array	$args		wp_remote_post arguments
	 * @param	string	$endpoint	Trello API endpoint
	 * @return	array	Filtered post arguments
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_API_POST_ARGS = 'ltw_order_to_board_sync_api_post_args';

	/**
	 * Filter Trello API request args
	 *
	 * @param	array	$args		wp_remote_request arguments
	 * @param	string	$endpoint	Trello API endpoint
	 * @return	array	Filtered request arguments
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_API_REQUEST_ARGS = 'ltw_order_to_board_sync_api_request_args';

	/**
	 * Filter the current OAuth access-token value for first-party extensions.
	 *
	 * @param	string|\WP_Error	$access_token	Current token or connection error.
	 * @return	string|\WP_Error
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_OAUTH_ACCESS_TOKEN = 'ltw_order_to_board_sync_oauth_access_token';

	/**
	 * Filter the card payload immediately before it is sent to Trello.
	 *
	 * @param	array	$card	Card payload.
	 * @param	array	$order	Order data.
	 * @param	array	$settings	Card settings.
	 * @return	array
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_CARD_DATA = 'ltw_order_to_board_sync_card_data';

	/**
	 * Filter the rendered note for an order's Trello card-history entry.
	 *
	 * @param	string	$note_text	Rendered note HTML before KSES sanitization.
	 * @param	array	$history_entry	Card history entry.
	 * @return	string
	 * @since	1.0.0
	 */
	const FILTER_CARD_HISTORY_NOTE = 'ltw_order_to_board_sync_card_history_note';

	/**
	 * Filter the ordered, named description sections before they are combined.
	 *
	 * Core section IDs are card_description, order, ordered_products,
	 * billing_address, shipping_address, shipping_method, and total_costs.
	 * Each section value must be a string, and array order determines output order.
	 *
	 * @param	array	$sections	Ordered map of section IDs to description text.
	 * @param	array	$order	Order data.
	 * @param	array	$settings	Card settings.
	 * @return	array
	 * @since	1.2.0
	 */
	const FILTER_TRELLO_CARD_DESCRIPTION_SECTIONS = 'ltw_order_to_board_sync_card_description_sections';
	
	/**
	 * Filter Trello API GET response
	 *
	 * @param	array	$response	Decoded API response
	 * @param	string	$endpoint	Trello API endpoint
	 * @return	array	Filtered response
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_API_GET_RESPONSE = 'ltw_order_to_board_sync_api_get_response';

	/**
	 * Filter Trello API POST response
	 *
	 * @param	array	$response	Decoded API response
	 * @param	string	$endpoint	Trello API endpoint
	 * @return	array	Filtered response
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_API_POST_RESPONSE = 'ltw_order_to_board_sync_api_post_response';

	/**
	 * Filter Trello API REQUEST response
	 *
	 * @param	array	$response	Decoded API response
	 * @param	string	$endpoint	Trello API endpoint
	 * @return	array	Filtered response
	 * @since	1.0.0
	 */
	const FILTER_TRELLO_API_REQUEST_RESPONSE = 'ltw_order_to_board_sync_api_request_response';
	
	/**
	 * Filter plugin settings before saving
	 *
	 * @param	array	$settings	Settings array
	 * @return	array	Filtered settings
	 * @since	1.0.0
	 */
	const FILTER_SETTINGS_BEFORE_SAVE = 'ltw_order_to_board_sync_settings_before_save';
	
	/**
	 * Filter plugin settings after retrieval
	 *
	 * @param	array	$settings	Settings array
	 * @return	array	Filtered settings
	 * @since	1.0.0
	 */
	const FILTER_SETTINGS_AFTER_LOAD = 'ltw_order_to_board_sync_settings_after_load';

	/**
	 * Filter tabs configuration for settings
	 *
	 * @param	array	$settings	Settings array
	 * @return	array	Filtered settings
	 * @since	1.0.0
	 */
	const FILTER_SETTINGS_TABS = 'ltw_order_to_board_sync_settings_tabs';

	/**
	 * Filter tabs configuration for settings
	 *
	 * @param	array	$settings	Settings array
	 * @return	array	Filtered settings
	 * @since	1.0.0
	 */
	const FILTER_STATUS_TABS = 'ltw_order_to_board_sync_status_tabs';

	/**
	 * Filter tabs configuration for Help.
	 *
	 * @param	array	$tabs	Help tabs configuration
	 * @return	array	Filtered Help tabs
	 * @since	1.0.0
	 */
	const FILTER_HELP_TABS = 'ltw_order_to_board_sync_help_tabs';

	/**
	 * Filter the content rendered for a Help tab.
	 *
	 * @param	string	$content	Help tab content.
	 * @param	string	$tab_key	Help tab key.
	 * @return	string	Filtered Help tab content.
	 * @since	1.0.0
	 */
	const FILTER_HELP_TAB_CONTENT = 'ltw_order_to_board_sync_help_tab_content';

	/**
	 * Filter the order statuses available in the Cards settings.
	 *
	 * Free passes WooCommerce's built-in statuses. Extensions can add statuses
	 * and must return the complete associative status key and label list.
	 *
	 * @param	array	$order_statuses	Order status keys and labels.
	 * @return	array	Filtered order statuses.
	 * @since	1.0.0
	 */
	const FILTER_ORDER_STATUSES = 'ltw_order_to_board_sync_order_statuses';

	/**
	 * Filter Cards tab information text.
	 *
	 * @param	string	$text	Existing Cards tab information text.
	 * @return	string	Filtered Cards tab information text.
	 * @since	1.0.0
	 */
	const FILTER_CARDS_INFO_TEXT = 'ltw_order_to_board_sync_cards_info_text';
	
	/**
	 * Actions
	 */

	/**
	 * Action fired on single order edit right before the fields
	 *
	 * @param	object	$order			WC_Order object
	 * @param	array	$order_data		Prepared order data
	 * @param	array	$card_settings	Card settings
	 * @since	1.0.0
	 */
	const ACTION_EDIT_ORDER_METABOX_BEFORE_FIELDS = 'ltw_order_to_board_sync_edit_order_metabox_before_fields';

	/**
	 * Action fired on single order edit right after the fields and before the button
	 *
	 * @param	object	$order			WC_Order object
	 * @param	array	$order_data		Prepared order data
	 * @param	array	$card_settings	Card settings
	 * @since	1.0.0
	 */
	const ACTION_EDIT_ORDER_METABOX_AFTER_FIELDS = 'ltw_order_to_board_sync_edit_order_metabox_after_fields';
	
	/**
	 * Action fired when a new order is created and before sending to Trello
	 *
	 * @param	object	$order			WC_Order object
	 * @param	array	$card_data		The prepared card data
	 * @since	1.0.0
	 */
	const ACTION_ORDER_NEW_BEFORE_SEND = 'ltw_order_to_board_sync_order_new_before_send';

	/**
	 * Action fired after a newly created Trello card has been saved to order history.
	 *
	 * This fires for automatic creation and manual admin creation.
	 *
	 * @param	object	$order			WooCommerce order.
	 * @param	array	$history_entry	Saved card history entry.
	 * @param	array	$trello_response	Trello card creation response.
	 * @since	1.0.0
	 */
	const ACTION_CARD_CREATED = 'ltw_order_to_board_sync_card_created';
	
	/**
	 * Action fired after a new order has been sent to Trello
	 *
	 * @param	object	$order			WC_Order object
	 * @param	array	$trello_response API response from Trello
	 * @since	1.0.0
	 */
	const ACTION_ORDER_NEW_AFTER_SEND = 'ltw_order_to_board_sync_order_new_after_send';
	
	/**
	 * Action fired when order status changes and before sending to Trello
	 *
	 * @param	int		$order_id		Order ID
	 * @param	string	$old_status		Previous order status
	 * @param	string	$new_status		New order status
	 * @param	object	$order			WC_Order object
	 * @since	1.0.0
	 */
	const ACTION_ORDER_STATUS_CHANGED_BEFORE_SEND = 'ltw_order_to_board_sync_order_status_changed_before_send';
	
	/**
	 * Action fired after order status change has been sent to Trello
	 *
	 * @param	int		$order_id		Order ID
	 * @param	string	$old_status		Previous order status
	 * @param	string	$new_status		New order status
	 * @param	object	$order			WC_Order object
	 * @since	1.0.0
	 */
	const ACTION_ORDER_STATUS_CHANGED_AFTER_SEND = 'ltw_order_to_board_sync_order_status_changed_after_send';

	/**
	 * Action fired for every valid WooCommerce order status transition.
	 *
	 * @param	int		$order_id		Order ID.
	 * @param	string	$old_status		Previous order status.
	 * @param	string	$new_status		New order status.
	 * @param	object	$order			WC_Order object.
	 * @since	1.0.0
	 */
	const ACTION_ORDER_STATUS_CHANGED = 'ltw_order_to_board_sync_order_status_changed';

	/**
	 * Action fired when plugin settings are updated
	 *
	 * @param	array	$old_settings	Previous settings
	 * @param	array	$new_settings	New settings
	 * @since	1.0.0
	 */
	const ACTION_SETTINGS_UPDATED = 'ltw_order_to_board_sync_settings_updated';
	
	/**
	 * Action fired when an error occurs in the plugin
	 *
	 * @param	string	$error_message	Error message
	 * @param	string	$error_context	Context where error occurred (e.g., 'api_call', 'order_sync')
	 * @param	mixed	$error_data		Additional error data
	 * @since	1.0.0
	 */
	const ACTION_ERROR_OCCURRED = 'ltw_order_to_board_sync_error_occurred';
}
