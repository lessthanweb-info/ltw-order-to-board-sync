<?php
namespace LTW\Order_To_Board_Sync\Admin;

/**
 * Contains code for admin stuff.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Status as OrderConfirmationStatus;
use LTW\Order_To_Board_Sync\Core\Settings;
use LTW\Order_To_Board_Sync\Core\Fields;
use LTW\Order_To_Board_Sync\Core\Logger;
use LTW\Order_To_Board_Sync\Core\Hooks;
use LTW\Order_To_Board_Sync\Core\Trello_OAuth;
use LTW\Order_To_Board_Sync\WC\Order_Status;
use LTW\Order_To_Board_Sync\Admin\UI\Components;
use LTW\Order_To_Board_Sync\Admin\Help;
use LTW\Order_To_Board_Sync\Status\System_Info;

/**
 * Page Class
 */
class Page {
	/**
	 * Holds tabs for settings tab :)
	 *
	 * @var	array
	 * @since	1.0.0
	 *
	 */
	private array $settings_tabs_config = [];
	
	/**
	 * Holds tabs for status tab :)
	 *
	 * @var	array
	 * @since	1.0.0
	 *
	 */
	private array $status_tabs_config = [];

	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct( 
		private Order_Status $order_statuses,
		private Components $ui_components, 
		private Settings $settings, 
		private Fields $fields,
		private System_Info $system_info,
		private Logger $logger,
		private Help $help,
		private Trello_OAuth $trello_oauth
	) {
		$this->settings_tabs_config = apply_filters( Hooks::FILTER_SETTINGS_TABS, [
			'trello_api' => __( 'Trello OAuth 2.0', 'ltw-order-to-board-sync' ),
		] );

		$this->status_tabs_config = apply_filters( Hooks::FILTER_STATUS_TABS, [
			'system_info' => __( 'System Info', 'ltw-order-to-board-sync' ),
			'logs' => __( 'Logs', 'ltw-order-to-board-sync' ),
		] );

		// Ajax for deleting a single log
		add_action( 'wp_ajax_ltw_trello_delete_log', [ $this, 'delete_log' ] );

		// Ajax for deleting all logs in one go
		add_action( 'wp_ajax_ltw_trello_delete_all_logs', [ $this, 'delete_all_logs' ] );
    }

    /**
	 * Register new submenu item under WooCommerce menu
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
    public function render(): void {
        echo '<div class="wrap ltw-order-to-board-sync">';

			echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

			if ( ! $this->settings->is_setup_complete() ) {
				echo $this->render_setup_message();
			}

			echo $this->render_form();

			echo '<div class="ltw-order-to-board-sync-top-tabs">
				<div class="top-tab active" data-top-id="cards">' . __( 'Cards', 'ltw-order-to-board-sync' ) . '</div>
				<div class="top-tab" data-top-id="settings">' . __( 'Settings', 'ltw-order-to-board-sync' ) . '</div>
				<div class="top-tab" data-top-id="status">' . __( 'Status', 'ltw-order-to-board-sync' ) . '</div>
				<div class="top-tab" data-top-id="help">' . __( 'Help', 'ltw-order-to-board-sync' ) . '</div>
			</div>';

			echo '<div class="ltw-order-to-board-sync-main-content">';

				echo '<div class="ltw-order-to-board-sync-top-content ltw-order-to-board-sync-top-content-cards active">';

					echo '<div class="ltw-order-to-board-sync-container">';

						// Render card tabs
						echo $this->render_cards_tabs();

						// Render card tab content
						echo $this->render_cards_tab_content();

					echo '</div>';	// ltw-order-to-board-sync-container

				echo '</div>';	// ltw-order-to-board-sync-top-content

				echo '<div class="ltw-order-to-board-sync-top-content ltw-order-to-board-sync-top-content-settings">';

					echo '<div class="ltw-order-to-board-sync-container">';

						// Render settings tabs
						echo $this->render_tabs( $this->settings_tabs_config );

						echo $this->render_tab_content( $this->settings_tabs_config, 'settings' );

					echo '</div>';	// ltw-order-to-board-sync-container

				echo '</div>';	// ltw-order-to-board-sync-top-content

				echo '<div class="ltw-order-to-board-sync-top-content ltw-order-to-board-sync-top-content-status">';

					echo '<div class="ltw-order-to-board-sync-container">';

						// Render status tabs
						echo $this->render_tabs( $this->status_tabs_config );

						echo $this->render_tab_content( $this->status_tabs_config, 'status' );

					echo '</div>';	// ltw-order-to-board-sync-container

				echo '</div>';	// ltw-order-to-board-sync-top-content

				echo '<div class="ltw-order-to-board-sync-top-content ltw-order-to-board-sync-top-content-help">';

					echo '<div class="ltw-order-to-board-sync-container">';

						// Render help tabs
						echo $this->render_tabs( $this->help->get_tabs_config() );

						echo $this->help->render_content();

					echo '</div>';	// ltw-order-to-board-sync-container

				echo '</div>';  // ltw-order-to-board-sync-top-content

        	echo '</div>';	// main-content

			echo $this->render_form( true );

        echo '</div>';	// wrap
    }

	/**
	 * Renders form.
	 * 
	 * $end = true means it will echo the closing part of the form.
	 *
	 * @param	boolean $end
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_form( bool $end = false ): string {
		$form = '';

		if ( ! $end ) {
			$form .= '<form id="ltw-order-to-board-sync-form" method="post" action="admin-post.php">';
			$form .= '<div>';
			$form .= wp_nonce_field( 'ltw_order_to_board_sync_save_settings', 'ltw_order_to_board_sync_settings', true, false );
			$form .= '<input type="hidden" name="action" value="save_settings">';
			$form .= '<input type="hidden" id="ltw-order-to-board-sync-page-url" name="ltw_order_to_board_sync_url_page" value="' . admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME ) . '">';
			$form .= '<input type="hidden" id="ltw-order-to-board-sync-current-page" name="ltw_order_to_board_sync_current_page" value="' . admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME ) . '">';
			$form .= '</div>';
		} else {
			$form .= '</form>';	
		}

		return $form;
	}

	/**
	 * Render tabs for the "Cards" tab.
	 * 
	 * Tabs are built based on WC order statuses.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_cards_tabs(): string {
		// Get a list of all WC order statuses
		$order_statuses = $this->order_statuses->get_list_of_order_statuses();

		// Now we shall build the side navigation based on the WC order statuses
		$tabs = '<div class="ltw-order-to-board-sync-tabs">';
			$tabs .= '<ul class="tabs-nav">';

			if ( ! empty( $order_statuses ) ) {
				$count = 0;

				// Loop over WC order statuses
				foreach ( $order_statuses as $os_key => $order_status ) {
					// First one is always active
					$is_active = $count === 0 ? 'active' : '';

					$tabs .= '<li><a href="#" class="tab-link ' . $is_active . '" data-tab-id="ltw-' . esc_attr( $os_key ) . '">' . esc_html( $order_status ) . '</a></li>';

					$count++;
				}
			} else {
				$tabs .= '<li>' . __( 'No order statuses found.', 'ltw-order-to-board-sync' ) . '</li>';
			}

			$tabs .= '</ul>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 * Render a single field based on type and configuration
	 *
	 * @param	array	$field			Field configuration
	 * @param	string	$field_key		Field key/name
	 * @param	string	$section_name	Section name (cards, settings, etc.)
	 * @param	string	$context_key	Context key (order status key, tab key, etc.)
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_field( array $field, string $field_key, string $section_name, string $context_key, array $overrides = [] ): string {
		$field_html = '';
		$field_label = $field['label'] ?? '';
		$field_id = $overrides['id'] ?? $context_key . '-' . $field['html_id'];
		$field_section = $overrides['section'] ?? '[' . $section_name . '][' . $context_key . ']';
		$field_name = $field['name'] ?? '';
		$field_value = $overrides['value'] ?? ( $field_name !== '' ? $this->settings->get( $section_name, $context_key, $field_name ) : '' );
		if ( ! empty( $field['sensitive'] ) ) {
			$field_value = '';
		}

		if ( $section_name === 'cards' && $field_name === 'enabled' ) {
			$order_statuses = $this->order_statuses->get_list_of_order_statuses();
			$status_label = $order_statuses[ $context_key ] ?? ucfirst( str_replace( [ 'wc-', '_' ], [ '', ' ' ], $context_key ) );
			$field_label = sprintf( __( 'Create a Trello card for %s orders', 'ltw-order-to-board-sync' ), $status_label );
		}

		if ( $field['type'] == 'btn_save' ) {
			$field_html = $this->ui_components->btn_save( [
				'id' => $field_id,
				'name' => $field_name,
				'value' => $field['value'] ?? '',
			] );
		} else if ( $field['type'] == 'paragraph' ) {
			$field_html = $this->ui_components->paragraph( [
				'text' => $field['text'],
			] );
		} else if ( $field['type'] == 'separator' ) {
			$field_html = $this->ui_components->separator( [
				'id' => $field_id,
			] );
		} else if ( $field['type'] == 'switch' ) {
			$field_html = $this->ui_components->switch( [
				'section' => $field_section,
				'id' => $field_id,
				'name' => $field_name,
				'label' => $field_label,
				'note' => $field['note'] ?? '',
				'checked' => $field_value,
			] );
		} else if ( in_array( $field['type'], [ 'text', 'password' ], true ) ) {
			$field_html = $this->ui_components->text( [
				'section' => $field_section,
				'id' => $field_id,
				'name' => $field_name,
				'label' => $field['label'],
				'value' => $field_value,
				'note' => $field['note'] ?? '',
				'type' => $field['type'],
			] );
		} else if ( $field['type'] == 'oauth_controls' ) {
			$field_html = $this->trello_oauth->render_admin_controls();
		} else if ( $field['type'] == 'select' ) {
			$field_html = $this->ui_components->select( [
				'section' => $field_section,
				'id' => $field_id,
				'name' => $field_name,
				'options' => $field['options'],
				'selected' => $field_value,
				'label' => $field['label'],
				'note' => $field['note'] ?? '',
				'classes' => $field['classes'],
				'loader' => $field['loader'] ?? '',
			] );
		} else if ( $field['type'] == 'color' ) {
			$field_html = $this->ui_components->color( [
				'section' => '[' . $section_name . '][' . $context_key . ']',
				'id' => $context_key . '-' . $field['html_id'],
				'name' => $field_name,
				'label' => $field['label'],
				'classes' => $field['classes'],
				'selected' => $field['selected'],
			] );
		} else if ( $field['type'] == 'accordion' ) {
			$field_html = $this->ui_components->accordion( [
				'section' => '[' . $section_name . '][' . $context_key . ']',
				'id' => $context_key . '-' . $field['html_id'],
				'label' => $field['label'],
				'text' => $field['text'],
			] );
		} else if ( $field['type'] == 'date' ) {
			$field_html = $this->ui_components->date( [
				'section' => '[' . $section_name . '][' . $context_key . ']',
				'id' => $context_key . '-' . $field['html_id'],
				'name' => $field_name,
				'label' => $field['label'],
				'classes' => $field['classes'],
			] );
		} else if ( $field['type'] == 'time' ) {
			$field_html = $this->ui_components->time( [
				'section' => '[' . $section_name . '][' . $context_key . ']',
				'id' => $context_key . '-' . $field['html_id'],
				'name' => $field_name,
				'label' => $field['label'],
				'classes' => $field['classes'],
			] );
		} else if ( $field['type'] == 'textarea' ) {
			$field_html = $this->ui_components->textarea( [
				'section' => '[' . $section_name . '][' . $context_key . ']',
				'id' => $context_key . '-' . $field['html_id'],
				'name' => $field_name,
				'label' => $field['label'],
				'text' => $field_value,
			] );
		} else if ( $field['type'] == 'number_with_select' ) {
			$field_html = $this->ui_components->number_with_select( [
				'section' => '[' . $section_name . '][' . $context_key . ']',
				'id' => $context_key . '-' . $field['html_id'],
				'name' => $field_name,
				'options' => $field['options'],
				'selected' => $field_value,
				'value' => $field_value,
				'label' => $field['label'],
			] );
		} else if ( $field['type'] == 'log' ) {
			// Log field or content block as it should be called
			// is a special one as it renders the whole page.
			$field_html = $this->render_log_content();
		} else if ( $field['type'] == 'system_info' ) {
			// Render system info
			$field_html = $this->system_info->render_full_page();
		}

		return apply_filters( Hooks::FILTER_FIELD_HTML, $field_html, $field, $field_key, $section_name, $context_key, $field_value );
	}

	/**
	 * Render form fields as configured rows
	 *
	 * @param	array	$field_config		Field configuration array
	 * @param	array	$rows_config		Row configuration array
	 * @param	string	$section_name		Section name (cards, settings)
	 * @param	string	$context_key		Context key (order status key, tab key)
	 * @param	string|null	$section_filter	Optional section key to filter fields by (e.g., 'trello_api')
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_rows( array $field_config, array $rows_config, string $section_name, string $context_key, ?string $section_filter = null ): string {
		$content = '';

		foreach ( $rows_config as $row ) {
			if ( ! empty( $row['repeatable'] ) ) {
				$content .= $this->render_board_list_rows( $field_config, $row, $section_name, $context_key );
				continue;
			}
			$row_fields = [];

			foreach ( $row['fields'] as $field_name ) {
				if ( isset( $field_config[ $field_name ] ) ) {
					$field = $field_config[ $field_name ];

					// If section filter is provided, check if field belongs to that section
					// Fields without a 'section' key are shown in all sections
					if ( $section_filter !== null ) {
						$field_section = $field['section'] ?? null;

						// Skip field if it has a section and doesn't match the filter
						if ( $field_section !== null && $field_section !== $section_filter ) {
							continue;
						}
					}

					$row_fields[] = $this->render_field( $field, $field_name, $section_name, $context_key );
				}
			}

			if ( ! empty( $row_fields ) ) {
				$content .= $this->ui_components->row( [
					'fields' => $row_fields
				] );
			}
		}

		return $content;
	}

	/**
	 * Render board/list destination rows using the configured field definitions.
	 *
	 * @param	array	$field_config	Field configuration.
	 * @param	array	$row	Row configuration.
	 * @param	string	$section_name	Section name.
	 * @param	string	$context_key	Context key.
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_board_list_rows( array $field_config, array $row, string $section_name, string $context_key ): string {
		$settings = $this->settings->get_all();
		$values = $settings[ $section_name ][ $context_key ]['board_lists'] ?? [];

		if ( empty( $values ) ) {
			$values = [
				[
					'board_id' => $settings[ $section_name ][ $context_key ]['board_id'] ?? '',
					'list_id' => $settings[ $section_name ][ $context_key ]['list_id'] ?? '',
				],
			];
		}

		$content = '<fieldset class="ltw-order-to-board-sync-board-lists" data-context="' . esc_attr( $context_key ) . '">';
		$content .= '<legend><strong>' . esc_html__( 'Board and List destinations', 'ltw-order-to-board-sync' ) . '</strong></legend><div class="ltw-order-to-board-sync-board-list-rows">';

		foreach ( array_values( $values ) as $index => $value ) {
			$row_content = '';
			$row_groups = $row['groups'] ?? [
				[
					'fields' => $row['fields'] ?? [],
				],
			];

			foreach ( $row_groups as $group ) {
				if ( ! is_array( $group ) || empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
					continue;
				}

				$row_fields = [];

				foreach ( $group['fields'] as $field_name ) {
					if ( ! isset( $field_config[ $field_name ] ) ) {
						continue;
					}

					// Render each group independently to let extensions organize repeatable fields into readable rows.
					$row_fields[] = $this->render_field(
						$field_config[ $field_name ],
						$field_name,
						$section_name,
						$context_key,
						[
							'section' => '[' . $section_name . '][' . $context_key . '][board_lists][' . $index . ']',
							'id' => $context_key . '-board-list-' . $index . '-' . $field_config[ $field_name ]['html_id'],
							'value' => $value[ $field_name ] ?? '',
						]
					);
				}

				if ( empty( $row_fields ) ) {
					continue;
				}

				$group_content = $this->ui_components->row( [ 'fields' => $row_fields ] );
				$group_label = is_scalar( $group['label'] ?? '' ) ? sanitize_text_field( (string) ( $group['label'] ?? '' ) ) : '';
				$group_description = is_scalar( $group['description'] ?? '' ) ? sanitize_text_field( (string) ( $group['description'] ?? '' ) ) : '';
				$full_width_class = ! empty( $group['full_width'] ) ? ' ltw-order-to-board-sync-destination-group-full-width' : '';

				if ( $group_label !== '' ) {
					$row_content .= '<fieldset class="ltw-order-to-board-sync-destination-group"><legend><strong>' . esc_html( $group_label ) . '</strong></legend>';

					if ( $group_description !== '' ) {
						$row_content .= '<p class="ltw-order-to-board-sync-destination-group-description">' . esc_html( $group_description ) . '</p>';
					}

					$row_content .= $group_content . '</fieldset>';
				} elseif ( $full_width_class !== '' ) {
					$row_content .= '<div class="ltw-order-to-board-sync-destination-group' . esc_attr( $full_width_class ) . '">' . $group_content . '</div>';
				} else {
					$row_content .= $group_content;
				}
			}

			$row_html = '<div class="ltw-order-to-board-sync-board-list-row" data-index="' . esc_attr( (string) $index ) . '">' . $row_content . '</div>';
			
			$content .= apply_filters(
				Hooks::FILTER_REPEATABLE_ROW_HTML,
				$row_html,
				[
					'section' => $section_name,
					'context' => $context_key,
					'index' => $index,
					'values' => $value,
					'fields' => $row['fields'] ?? [],
				]
			);
		}

		$add_destination_label = is_scalar( $row['add_button_label'] ?? '' )
			? sanitize_text_field( (string) ( $row['add_button_label'] ?? '' ) )
			: '';
		$add_destination_label = $add_destination_label !== '' ? $add_destination_label : esc_html__( 'Add New board/list', 'ltw-order-to-board-sync' );

		return $content . '</div><button type="button" class="button ltw-order-to-board-sync-add-board-list">' . esc_html( $add_destination_label ) . '</button></fieldset>';
	}

	/**
	 * Render content containers for "Cards" tabs.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_cards_tab_content(): string {
		// List of all WC order statuses
		$order_statuses = $this->order_statuses->get_list_of_order_statuses();

		$content = '<div class="ltw-order-to-board-sync-content pico">';

		// Card destinations cannot be configured until a Trello account is connected.
		if ( ! $this->trello_oauth->is_connected() ) {
			$settings_url = admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME . '#settings' );
			$content .= '<aside class="ltw-order-to-board-sync-connect-prompt">';
				$content .= '<h2>' . esc_html__( 'Connect Trello to configure card settings', 'ltw-order-to-board-sync' ) . '</h2>';
				$content .= '<p>' . esc_html__( 'Connect a Trello account before choosing boards and lists for your order statuses.', 'ltw-order-to-board-sync' ) . '</p>';
				$content .= '<a class="button button-primary" href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Open Trello settings', 'ltw-order-to-board-sync' ) . '</a>';
			$content .= '</aside>';

			return $content . '</div>';
		}

			$content .= '<div class="ltw-order-to-board-sync-info-panel">';
				$cards_info_text = __( 'These settings are applied per WooCommerce order status. Choose the Trello board and list for each status, then enable card creation only for the order statuses that should send cards to Trello.', 'ltw-order-to-board-sync' );
				$cards_info_text = apply_filters( Hooks::FILTER_CARDS_INFO_TEXT, $cards_info_text );
				$content .= '<p>' . esc_html( $cards_info_text ) . '</p>';
			$content .= '</div>';

		if ( ! defined( 'LTW_ORDER_TO_BOARD_SYNC_PRO_VERSION' ) ) {
			$pro_url = 'https://www.lessthanweb.com/product/order-to-board-sync-pro/';

			$content .= '<aside class="ltw-order-to-board-sync-pro-promo">';
				$content .= '<div>';
					$content .= '<h2>' . esc_html__( 'Need more destinations?', 'ltw-order-to-board-sync' ) . '</h2>';
					$content .= '<p>' . esc_html__( 'Upgrade to LTW Order-to-Board Sync Pro to send cards for each order status to multiple Trello boards and lists, and to use custom order statuses registered by extensions or custom code.', 'ltw-order-to-board-sync' ) . '</p>';
				$content .= '</div>';
				$content .= '<a class="button button-secondary" href="' . esc_url( $pro_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Explore Pro', 'ltw-order-to-board-sync' ) . '</a>';
			$content .= '</aside>';
		}

		if ( ! empty( $order_statuses ) ) {
			$count = 0;

			// Loop over WC order statuses
			foreach ( $order_statuses as $os_key => $order_status ) {
				// First tab container is always active aka first shown
				$is_active = $count === 0 ? 'active' : '';

				// Start of tab content
				$content .= '<div id="ltw-' . esc_attr( $os_key ) . '" class="tab-content ' . $is_active . '">';

					$content .= '<h2>' . esc_html( $order_status ) . '</h2>';
					$content .= '<p class="ltw-order-to-board-sync-status-note">' . sprintf( __( 'Settings for %s orders only. When this status is reached, the plugin can create a Trello card using the board and list configured below.', 'ltw-order-to-board-sync' ), esc_html( $order_status ) ) . '</p>';
					
					// Get "cards" field configuration and row configuration
					$all_fields = $this->fields->get_fields();
					$field_config = $all_fields['cards'];
					$rows_config = $all_fields['rows']['cards'] ?? [];

					// Render fields as configured rows
					if ( ! empty( $rows_config ) ) {
						$content .= $this->render_rows( $field_config, $rows_config, 'cards', $os_key, 'cards' );
					}

				// End of tab content
				$content .= '</div>';

				$count++;
			}
		} else {
			$content .= '<li>' . __( 'No order statuses found.', 'ltw-order-to-board-sync' ) . '</li>';
		}

		$content .= '</div>';

		return $content;
	}

	/**
	 * Render tabs for the general pages.
	 * Most of other pages use the same format for rendering tabs and fields.. Cards is a special one so it uses different render methods.
	 *
	 * @param	array	$tabs_config	Contains tab configuration
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_tabs( array $tabs_config = [] ): string {
		$tabs = '<div class="ltw-order-to-board-sync-tabs">';
			$tabs .= '<ul class="tabs-nav">';

			$count = 0;

			foreach ( $tabs_config as $tabs_key => $tab ) {
				// First one is always active
				$is_active = $count === 0 ? 'active' : '';

				$tabs .= '<li><a href="#" class="tab-link ' . $is_active . '" data-tab-id="ltw-' . esc_attr( $tabs_key ) . '">' . esc_html( $tab ) . '</a></li>';

				$count++;
			}

			$tabs .= '</ul>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 * Render content for general pages.
	 *
	 * @param	array	$tabs_config	Contains tab configuration
	 * @param	string	$section	Section from which to take the fields from
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_tab_content( array $tabs_config = [], string $section = '' ): string {
		$content = '<div class="ltw-order-to-board-sync-content pico">';

		$count = 0;

		foreach ( $tabs_config as $tabs_key => $tab ) {
			// First tab container is always active aka first shown
			$is_active = $count === 0 ? 'active' : '';

			// Start of tab content
			$content .= '<div id="ltw-' . esc_attr( $tabs_key ) . '" class="tab-content ' . $is_active . '">';

				$content .= '<h2>' . esc_html( $tab ) . '</h2>';
				
				// Get field configuration and row configuration
				$all_fields = $this->fields->get_fields();
				$field_config = $all_fields[ $section ];
				$rows_config = $all_fields['rows'][ $section ] ?? [];

				// Render fields as configured rows, filtering by section (tab key)
				if ( ! empty( $rows_config ) ) {
					$content .= $this->render_rows( $field_config, $rows_config, $section, $tabs_key, $tabs_key );
				}

			// End of tab content
			$content .= '</div>';

			$count++;
		}

		$content .= '</div>';

		return $content;
	}

	/**
	 * Render the first-time setup message.
	 *
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_setup_message(): string {
		$settings_url = admin_url( 'admin.php?page=' . LTW_ORDER_TO_BOARD_SYNC_PAGE_NAME . '#settings' );

		return '<aside id="ltw-order-to-board-sync-setup-message" class="pico ltw-order-to-board-sync-setup-message" aria-labelledby="ltw-order-to-board-sync-setup-title">'
			. '<div class="ltw-order-to-board-sync-setup-header">'
				. '<span class="ltw-order-to-board-sync-setup-badge">' . esc_html__( 'Getting started', 'ltw-order-to-board-sync' ) . '</span>'
				. '<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>'
			. '</div>'
			. '<h2 id="ltw-order-to-board-sync-setup-title">' . esc_html__( 'Connect your store to Trello', 'ltw-order-to-board-sync' ) . '</h2>'
			. '<p class="ltw-order-to-board-sync-setup-intro">' . esc_html__( "You're almost ready to start creating Trello cards from WooCommerce orders.", 'ltw-order-to-board-sync' ) . '</p>'
			. '<ol class="ltw-order-to-board-sync-setup-steps">'
				. '<li>' . esc_html__( 'Create a confidential OAuth 2.0 client in your Trello app settings.', 'ltw-order-to-board-sync' ) . '</li>'
				. '<li>' . esc_html__( 'Save the client ID and secret in the Trello OAuth 2.0 settings.', 'ltw-order-to-board-sync' ) . '</li>'
				. '<li>' . esc_html__( 'Connect your Trello account and approve the requested access.', 'ltw-order-to-board-sync' ) . '</li>'
			. '</ol>'
			. '<footer>'
				. '<a class="button button-primary" href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Start setup', 'ltw-order-to-board-sync' ) . '</a>'
				. '<small>' . esc_html__( 'OAuth tokens are used only to communicate with Trello and are never added to cards or logs.', 'ltw-order-to-board-sync' ) . '</small>'
			. '</footer>'
			. '</aside>';
	}

	/**
	 * Render content containers for "Status" tabs.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_log_content(): string {
		$logs = $this->logger->get_logs();

		$content = '';

		$content .= '<div class="ltw-logs-container-wrapper">';

		// If there are no logs then just show a simple note "no logs".
		// If we have logs then we shall list them one by one, last one first and so on..
		if ( empty( $logs ) ) {
			$content .= '<p>' . __( 'Currently there are no logs available.', 'ltw-order-to-board-sync' ) . '</p>';
		} else {
			$content .= '<div class="ltw-logs-container">';
			
			// Get "log" field configuration
			$field_config = $this->fields->get_fields()['status'];
			
			// Reverse array so that the latest is at the top
			$logs = array_reverse( $logs );

			// We are going to show only last 50 logs.
			$log_count = 1;

			foreach ( $logs as $key => $log ) {
				if ( $log_count >= 50 ) {
					break;
				}

				// Build the log list
				// REMOVE THIS AS ITS NOT NEEDED
				$content .= $this->ui_components->log( [
					'section' => '[log][log-' . $key . ']',
					'id' => $field_config['log']['html_id'] . '-' . $key,
					'header' => $log['level'] . ' - ' . \date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), absint( $log['timestamp'] ) ),
					'body' => $log['message'],
					'type' => $log['level'],
				] );

				$log_count++;
			}

			$content .= '</div>';

			// Add "Delete All" button at the end of the logs list
			$content .= $this->render_delete_all_logs_btn();
		}
		
		$content .= '</div>'; // Close ltw-logs-container-wrapper

		return $content;
	}

	/**
	 * Handle AJAX request to delete a single log
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function delete_log(): void {
		// Check nonce
		check_ajax_referer( 'ltw-order-to-board-sync-ajax-api-nonce', 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'ltw-order-to-board-sync' ) ] );
		}

		// Get log ID from POST data
		$log_id = isset( $_POST['log_id'] ) ? absint( filter_var( wp_unslash( $_POST['log_id'] ), FILTER_SANITIZE_NUMBER_INT ) ) : '';

		if ( ! is_int( $log_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid log ID.', 'ltw-order-to-board-sync' ) ] );
		} else {
			// Delete the actual log and return true and if it doesn't exist show WP_Error
			$deleted_log = $this->logger->delete_log( $log_id );

			if ( is_wp_error( $deleted_log ) ) {
				wp_send_json_error( [ 'message' => $deleted_log->get_error_message() ] );
			} else {
				wp_send_json_success( [ 'message' => __( 'Log deleted successfully.', 'ltw-order-to-board-sync' ) ] );
			}
		}
	}

	/**
	 * Handle AJAX request to delete all logs in one go
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function delete_all_logs(): void {
		// Check nonce
		check_ajax_referer( 'ltw-order-to-board-sync-ajax-api-nonce', 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'ltw-order-to-board-sync' ) ] );
		}

		// Delete all the logs in one go
		$deleted_all_logs = $this->logger->delete_all();

		if ( $deleted_all_logs ) {
			wp_send_json_success( [ 'message' => __( 'All logs deleted successfully.', 'ltw-order-to-board-sync' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Unable to delete all logs.', 'ltw-order-to-board-sync' ) ] );
		}
	}

	/**
	 * Render "Delete All" button that is on the bottom of the Logs page.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_delete_all_logs_btn(): string {
		return '<button type="button" class="button ltw-order-to-board-sync-delete-all-logs" id="ltw-order-to-board-sync-delete-all-logs">'
			. esc_html__( 'Delete All', 'ltw-order-to-board-sync' )
			. '</button>';
	}
}