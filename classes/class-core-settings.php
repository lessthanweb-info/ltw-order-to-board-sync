<?php
namespace LTW\Order_To_Board_Sync\Core;

/**
 * Contains code for settings.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Core\Fields;

/**
 * Settings Class
 */
class Settings {
	/**
	 * Option used to persist successful first-time setup.
	 *
	 * @var	string
	 * @since	1.0.0
	 *
	 */
	private const SETUP_COMPLETE_OPTION = 'ltw_order_to_board_sync_setup_complete';
	public const OAUTH_CLIENT_SECRET_OPTION = 'ltw_order_to_board_sync_oauth_client_secret';

	/**
	 * Constructor
	 *
	 * @param	Fields	$fields
	 * @return	void
	 * @since	1.0
	 *
	 */
	public function __construct( private Fields $fields ) {

	}

	/**
	 * Save settings to the database.
	 * 
	 * Validates and sanitizes data before saving to options table.
	 *
	 * @param	array $data	The settings data to save
	 * @return	array|\WP_Error	Saved data on success, WP_Error on validation failure
	 * @since	1.0
	 *
	 */
	public function save( array $data ): array|\WP_Error {
		// If no data, return error
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'no_data', __( 'No settings data provided.', 'ltw-order-to-board-sync' ) );
		}

		// Filter data before saving to DB and before sanitazion and validation
		$data = apply_filters( HOOKS::FILTER_SETTINGS_BEFORE_SAVE, $data );
		$old_settings = $this->get_all();
		$old_client_secret = (string) get_option( self::OAUTH_CLIENT_SECRET_OPTION, '' );

		// An empty password input means the stored OAuth client secret should remain unchanged.
		if (
			isset( $data['settings']['trello_api'] )
		) {
			if ( ! isset( $data['settings']['trello_api']['oauth_client_id'] ) ) {
				$data['settings']['trello_api']['oauth_client_id'] = $this->get_setting_value( $old_settings, 'settings', 'trello_api', 'oauth_client_id' );
			}

			if ( ! isset( $data['settings']['trello_api']['oauth_client_secret'] ) || $data['settings']['trello_api']['oauth_client_secret'] === '' ) {
				$data['settings']['trello_api']['oauth_client_secret'] = $old_client_secret;
			}
		}

		// Validate and sanitize fields before saving
		$validated = $this->fields->validate_and_sanitize( $data );

		// If validation failed, return the error
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$new_client_secret = $old_client_secret;
		if ( isset( $validated['settings']['trello_api']['oauth_client_secret'] ) ) {
			$new_client_secret = $this->get_setting_value( $validated, 'settings', 'trello_api', 'oauth_client_secret' );
			unset( $validated['settings']['trello_api']['oauth_client_secret'] );
			update_option( self::OAUTH_CLIENT_SECRET_OPTION, $new_client_secret, false );
		}

		// Save to database
		update_option( LTW_ORDER_TO_BOARD_SYNC_SETTINGS, $validated );

		if ( isset( $data['settings']['trello_api'] ) && (
			$this->get_setting_value( $old_settings, 'settings', 'trello_api', 'oauth_client_id' ) !== $this->get_setting_value( $validated, 'settings', 'trello_api', 'oauth_client_id' )
			|| $old_client_secret !== $new_client_secret
		) ) {
			delete_option( 'ltw_order_to_board_sync_trello_oauth_tokens' );
			$this->reset_setup_complete();
		}

		do_action( HOOKS::ACTION_SETTINGS_UPDATED, $old_settings, $validated );

		return $validated;
	}

	/**
	 * Get all saved settings as an array.
	 *
	 * @param	void
	 * @return	array
	 * @since	1.0
	 *
	 */
	public function get_all(): array {
		$settings = get_option( LTW_ORDER_TO_BOARD_SYNC_SETTINGS, [] );

		$settings = apply_filters( HOOKS::FILTER_SETTINGS_AFTER_LOAD, $settings );

		return $settings;
	}

	/**
	 * Get single setting.
	 *
	 * @param	string	$section	Section like "cards", "settings",..
	 * @param	string	$key	Key of the setting like "pending-payment", "trello_api",...
	 * @param	string	$name	Field name
	 * @return	string
	 * @since	1.0
	 *
	 */
	public function get( string $section = '', string $key = '', string $name = '' ): string {
		$settings = $this->get_all();

		return $this->get_setting_value( $settings, $section, $key, $name );
	}

	/**
	 * Get the stored Trello OAuth client secret.
	 *
	 * @return	string
	 * @since	1.0.0
	 */
	public function get_oauth_client_secret(): string {
		return (string) get_option( self::OAUTH_CLIENT_SECRET_OPTION, '' );
	}

	/**
	 * Check whether the first-time setup has been completed.
	 *
	 * @return	bool
	 * @since	1.0.0
	 *
	 */
	public function is_setup_complete(): bool {
		return (bool) get_option( self::SETUP_COMPLETE_OPTION, false );
	}

	/**
	 * Mark the first-time setup as completed.
	 *
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function mark_setup_complete(): void {
		update_option( self::SETUP_COMPLETE_OPTION, true );
	}

	/**
	 * Reset the first-time setup completion state.
	 *
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function reset_setup_complete(): void {
		delete_option( self::SETUP_COMPLETE_OPTION );
	}

	/**
	 * Get a scalar setting value from a settings array.
	 *
	 * @param	array	$settings	Settings data.
	 * @param	string	$section	Settings section.
	 * @param	string	$key	Settings key.
	 * @param	string	$name	Setting name.
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function get_setting_value( array $settings, string $section, string $key, string $name ): string {
		if ( isset( $settings[ $section ][ $key ][ $name ] ) && is_scalar( $settings[ $section ][ $key ][ $name ] ) ) {
			return (string) $settings[ $section ][ $key ][ $name ];
		}

		return '';
	}
}