<?php
namespace LTW\Order_To_Board_Sync\Admin;

/**
 * Handles admin notices
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
 * Notices Class
 */
class Notices {
	/**
	 * Transient key for storing notices
	 *
	 * @var	string
	 * @since	1.0.0
	 *
	 */
	private string $transient_key = 'ltw_order_to_board_sync_admin_notices';

	/**
	 * Notice types
	 *
	 * @var	array
	 * @since	1.0.0
	 *
	 */
	private array $notice_types = [ 
		'success', 
		'error', 
		'warning', 
		'info' 
	];

	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'display' ] );
	}

	/**
	 * Add a notice
	 *
	 * @param	string $message The notice message
	 * @param	string $type The notice type (success, error, warning, info)
	 * @param	bool   $dismissible Whether the notice can be dismissed
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function add( string $message, string $type = 'info', bool $dismissible = true ): void {
		if ( ! in_array( $type, $this->notice_types, true ) ) {
			$type = 'info';
		}

		$notices = $this->get_notices();

		$notices[] = [
			'message' => $message,
			'type' => $type,
			'dismissible' => $dismissible,
		];

		set_transient( $this->transient_key, $notices, LTW_ORDER_TO_BOARD_SYNC_HOUR_IN_SECONDS );
	}

	/**
	 * Add success notice
	 *
	 * @param	string $message The notice message
	 * @param	bool   $dismissible Whether the notice can be dismissed
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function success( string $message, bool $dismissible = true ): void {
		$this->add( $message, 'success', $dismissible );
	}

	/**
	 * Add error notice
	 *
	 * @param	string $message The notice message
	 * @param	bool   $dismissible Whether the notice can be dismissed
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function error( string $message, bool $dismissible = true ): void {
		$this->add( $message, 'error', $dismissible );
	}

	/**
	 * Add warning notice
	 *
	 * @param	string $message The notice message
	 * @param	bool   $dismissible Whether the notice can be dismissed
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function warning( string $message, bool $dismissible = true ): void {
		$this->add( $message, 'warning', $dismissible );
	}

	/**
	 * Add info notice
	 *
	 * @param	string $message The notice message
	 * @param	bool   $dismissible Whether the notice can be dismissed
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function info( string $message, bool $dismissible = true ): void {
		$this->add( $message, 'info', $dismissible );
	}

	/**
	 * Display all notices
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function display(): void {
		$notices = $this->get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			$this->render_notice( $notice );
		}

		$this->clear();
	}

	/**
	 * Render a single notice
	 *
	 * @param	array $notice The notice data
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	private function render_notice( array $notice ): void {
		$type = $notice['type'];
		$message = $notice['message'];
		$dismissible = $notice['dismissible'] ? 'is-dismissible' : '';

		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> <?php echo esc_attr( $dismissible ); ?>">
			<p>
				<?php
				/**
				 * Allow escaping of notice message
				 *
				 * @param	string $message The notice message
				 * @since	1.0.0
				 */
				echo wp_kses_post( apply_filters( Hooks::FILTER_ADMIN_NOTICE_MESSAGE, $message ) );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get all notices from transient
	 *
	 * @param	void
	 * @return	array
	 * @since	1.0.0
	 *
	 */
	private function get_notices(): array {
		$notices = get_transient( $this->transient_key );

		return is_array( $notices ) ? $notices : [];
	}

	/**
	 * Clear all notices
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function clear(): void {
		delete_transient( $this->transient_key );
	}
}
