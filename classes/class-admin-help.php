<?php
namespace LTW\Order_To_Board_Sync\Admin;

/**
 * Contains admin help documentation and subtabs.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Core\Hooks;

/**
 * Help Class
 */
class Help {
	/**
	 * Holds the Help subtabs configuration.
	 *
	 * @var	array
	 * @since	1.0.0
	 *
	 */
	private array $tabs_config = [];

	/**
	 * Constructor.
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct() {
		$this->tabs_config = apply_filters( Hooks::FILTER_HELP_TABS, [
			'setup' => __( 'Setup guidance', 'ltw-order-to-board-sync' ),
			'placeholders' => __( 'Placeholders', 'ltw-order-to-board-sync' ),
		] );
	}

	/**
	 * Get the Help subtabs configuration.
	 *
	 * @param	void
	 * @return	array
	 * @since	1.0.0
	 *
	 */
	public function get_tabs_config(): array {
		return $this->tabs_config;
	}

	/**
	 * Render Help subtab content.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function render_content(): string {
		$content = '<div class="ltw-order-to-board-sync-content pico">';
		$count = 0;

		foreach ( $this->tabs_config as $tab_key => $tab_label ) {
			$is_active = $count === 0 ? 'active' : '';
			
			$content .= '<div id="ltw-' . esc_attr( $tab_key ) . '" class="tab-content ' . $is_active . '">';

			if ( $tab_key === 'setup' ) {
				$tab_content = $this->render_setup_content();
			} elseif ( $tab_key === 'placeholders' ) {
				$tab_content = $this->render_placeholders_content();
			} else {
				$tab_content = '';
			}

			$content .= apply_filters( Hooks::FILTER_HELP_TAB_CONTENT, $tab_content, $tab_key );

			$content .= '</div>';
			$count++;
		}

		$content .= '</div>';

		return $content;
	}

	/**
	 * Render setup guidance content.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_setup_content(): string {
		ob_start();
		?>
		<h1><?php echo esc_html__( 'Setup guidance', 'ltw-order-to-board-sync' ); ?></h1>

		<p><?php echo esc_html__( 'Follow these steps to connect WooCommerce to Trello and start creating cards automatically.', 'ltw-order-to-board-sync' ); ?></p>

		<h2><?php echo esc_html__( 'Set up Trello OAuth 2.0', 'ltw-order-to-board-sync' ); ?></h2>

		<p><?php echo wp_kses_post( sprintf( __( 'Open the %s to create or configure your Trello app.', 'ltw-order-to-board-sync' ), '<a href="https://trello.com/apps/admin" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Trello apps administration page', 'ltw-order-to-board-sync' ) . '</a>' ) ); ?></p>

		<ol>
			<li><?php echo esc_html__( 'Log in to your Trello account.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'Create or select your Trello app, open its OAuth 2.0 settings, and choose the confidential client type.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'Add the exact OAuth callback URL shown in the plugin settings and enable the read:member:trello, read:board:trello, and write:board:trello scopes.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'Copy the OAuth client ID and client secret into the plugin settings. Save the settings before connecting.', 'ltw-order-to-board-sync' ); ?></li>
		</ol>

		<p><?php echo esc_html__( 'Keep the OAuth client secret private. The plugin requests offline access so it can refresh its short-lived access token automatically.', 'ltw-order-to-board-sync' ); ?></p>

		<ol>
			<li><?php echo wp_kses_post( sprintf( __( 'Open the %s tab and enter your OAuth client ID and client secret.', 'ltw-order-to-board-sync' ), '<strong>' . esc_html__( 'Settings → Trello OAuth 2.0', 'ltw-order-to-board-sync' ) . '</strong>' ) ); ?></li>
			<li><?php echo esc_html__( 'Click Connect Trello account and approve access. The plugin will verify the connection and refresh tokens automatically.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo wp_kses_post( sprintf( __( 'Open the %s tab and select a WooCommerce order status.', 'ltw-order-to-board-sync' ), '<strong>' . esc_html__( 'Cards', 'ltw-order-to-board-sync' ) . '</strong>' ) ); ?></li>
			<li><?php echo esc_html__( 'For that status, select the Trello board and list where cards should be created, configure the card fields, and enable card creation for the status.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'Save the settings, then create or update a test order so it reaches the enabled status. Check the order notes and Trello list to confirm the card was created.', 'ltw-order-to-board-sync' ); ?></li>
		</ol>

		<p><strong><?php echo esc_html__( 'Important:', 'ltw-order-to-board-sync' ); ?></strong> <?php echo esc_html__( 'Card settings are configured independently for each WooCommerce order status. Enabling cards for one status does not enable them for the other statuses.', 'ltw-order-to-board-sync' ); ?></p>

		<h4><?php echo esc_html__( 'If a card is not created', 'ltw-order-to-board-sync' ); ?></h4>

		<ul>
			<li><?php echo esc_html__( 'Check that the status reached by the order is enabled in the Cards tab and has a board and list selected. If no complete board/list destination is selected, the Logs tab records that card creation was skipped.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'Review the Status → Logs tab for the failed request. If authorization has expired or been revoked, reconnect the Trello account in the settings.', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'If the previous attempt failed, open the order and use Retry failed card creation after correcting the configuration.', 'ltw-order-to-board-sync' ); ?></li>
		</ul>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render placeholder documentation content.
	 *
	 * @param	void
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	private function render_placeholders_content(): string {
		$placeholders = [
			'[ORDER_ID]' => __( 'The WooCommerce order ID number', 'ltw-order-to-board-sync' ),
			'[ORDER_STATUS]' => __( 'The current order status (e.g., pending, processing, completed)', 'ltw-order-to-board-sync' ),
			'[NAME]' => __( 'Customer\'s full name (first name + last name)', 'ltw-order-to-board-sync' ),
			'[FIRST_NAME]' => __( 'Customer\'s first name', 'ltw-order-to-board-sync' ),
			'[LAST_NAME]' => __( 'Customer\'s last name', 'ltw-order-to-board-sync' ),
			'[COMPANY]' => __( 'Customer\'s company name', 'ltw-order-to-board-sync' ),
			'[EMAIL]' => __( 'Customer\'s email address', 'ltw-order-to-board-sync' ),
			'[ORDER_DATE]' => __( 'The date and time the order was created', 'ltw-order-to-board-sync' ),
			'[ORDER_TOTAL]' => __( 'The total order amount with currency symbol', 'ltw-order-to-board-sync' ),
		];

		ob_start();
		?>
		<h1><?php echo esc_html__( 'Using Placeholders in Card Names and Descriptions', 'ltw-order-to-board-sync' ); ?></h1>

		<p><?php echo wp_kses_post( __( 'You can use dynamic placeholders in the Card Name and Card Description fields that will be automatically replaced with actual customer and order data. This allows you to create personalized Trello cards without having to manually enter information.', 'ltw-order-to-board-sync' ) ); ?></p>

		<h2><?php echo esc_html__( 'Available Placeholders', 'ltw-order-to-board-sync' ); ?></h2>

		<p><?php echo esc_html__( 'The following placeholders are available for use:', 'ltw-order-to-board-sync' ); ?></p>

		<table class="ltw-placeholders-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Placeholder', 'ltw-order-to-board-sync' ); ?></th>
					<th><?php echo esc_html__( 'Description', 'ltw-order-to-board-sync' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $placeholders as $placeholder => $description ) : ?>
					<tr>
						<td><code><?php echo esc_html( $placeholder ); ?></code></td>
						<td><?php echo esc_html( $description ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php echo esc_html__( 'Examples', 'ltw-order-to-board-sync' ); ?></h2>

		<p><?php echo esc_html__( 'Here are some examples of how to use placeholders:', 'ltw-order-to-board-sync' ); ?></p>

		<ul>
			<li><strong><?php echo esc_html__( 'Card Name:', 'ltw-order-to-board-sync' ); ?></strong> <code>Order #[ORDER_ID] - [NAME]</code> → <em>Order #12345 - John Smith</em></li>
			<li><strong><?php echo esc_html__( 'Card Name:', 'ltw-order-to-board-sync' ); ?></strong> <code>[COMPANY] Order - [ORDER_DATE]</code> → <em>Company Ltd Order - 2026-06-08</em></li>
			<li><strong><?php echo esc_html__( 'Card Description:', 'ltw-order-to-board-sync' ); ?></strong> <code>Customer: [NAME] ([EMAIL])</code> → <em>Customer: John Smith (john@example.com)</em></li>
			<li><strong><?php echo esc_html__( 'Card Description:', 'ltw-order-to-board-sync' ); ?></strong> <code>Order Total: [ORDER_TOTAL] | Status: [ORDER_STATUS]</code> → <em>Order Total: $99.99 | Status: Processing</em></li>
		</ul>

		<h2><?php echo esc_html__( 'Tips', 'ltw-order-to-board-sync' ); ?></h2>

		<ul>
			<li><?php echo esc_html__( 'Placeholders are case-sensitive - always use uppercase placeholders like [ORDER_ID], not [order_id]', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'If a field has no data (e.g., company name is empty), the placeholder will show as empty', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'You can use multiple placeholders in a single field', 'ltw-order-to-board-sync' ); ?></li>
			<li><?php echo esc_html__( 'Placeholders work in both Card Name and Card Description fields', 'ltw-order-to-board-sync' ); ?></li>
		</ul>
		<?php

		return ob_get_clean();
	}

}
