<?php
namespace LTW\Order_To_Board_Sync\Core;

use WP_Error;

/**
 * A logger class that handles database logging throughout the application.
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since	    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {
	/**
	 * Singleton instance
	 *
	 * @var	Logger
	 */
	private static $instance = null;

	/**
	 * Option name for storing logs
	 *
	 * @var	string
	 */
	private $option_name = 'ltw_order_to_board_sync_logs';

	/**
	 * Log levels
	 *
	 * @var	array
	 */
	private $log_levels = array( 'debug', 'info', 'warning', 'error' );

	/**
	 * Maximum logs to keep in database
	 *
	 * @var	int
	 */
	private $max_logs = 500;

	/**
	 * Get singleton instance
	 *
	 * @param	void
	 * @return	Logger
	 * @since	1.0
	 * 
	 */
	public static function get_instance(): Logger {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0
	 * 
	 */
	private function __construct() {

	}

	/**
	 * Prevent cloning
	 * 
	 * @param	void
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing
	 * 
	 * @param	void
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function __wakeup() {}

	/**
	 * Log a message
	 *
	 * @param	string	$message The message to log.
	 * @param	string	$level The log level (debug, info, warning, error).
	 * @param	mixed	$data Additional data to log (optional).
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function log( $message, $level = 'info', $data = null ): void {
		if ( ! in_array( $level, $this->log_levels, true ) ) {
			$level = 'info';
		}

		$entry = $this->format_entry( $message, $level, $data );

		$this->write_to_database( $entry );
	}

	/**
	 * Log debug message
	 *
	 * @param	string	$message The message to log.
	 * @param	mixed	$data Additional data to log (optional).
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function debug( $message, $data = null ): void {
		$this->log( $message, 'debug', $data );
	}

	/**
	 * Log info message
	 *
	 * @param	string	$message The message to log.
	 * @param	mixed	$data Additional data to log (optional).
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function info( $message, $data = null ): void {
		$this->log( $message, 'info', $data );
	}

	/**
	 * Log warning message
	 *
	 * @param		string	$message The message to log.
	 * @param		mixed	$data Additional data to log (optional).
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function warning( $message, $data = null ): void {
		$this->log( $message, 'warning', $data );
	}

	/**
	 * Log error message
	 *
	 * @param	string	$message The message to log.
	 * @param	mixed	$data Additional data to log (optional).
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function error( $message, $data = null ): void {
		$this->log( $message, 'error', $data );
	}

	/**
	 * Format a log entry
	 *
	 * @param	string	$message The message to log.
	 * @param	string	$level The log level.
	 * @param	mixed	$data Additional data.
	 * @return	array	The formatted log entry.
	 * @since	1.0
	 * 
	 */
	private function format_entry( $message, $level, $data ): array {
		return array(
			'timestamp' => time(),
			'level' => strtoupper( $level ),
			'message' => $message,
			'data' => ! is_null( $data ) ? $data : null,
		);
	}

	/**
	 * Write entry to database
	 *
	 * @param	array	$entry The entry to write.
	 * @return	void
	 * @since	1.0
	 * 
	 */
	private function write_to_database( $entry ): void {
		$logs = get_option( $this->option_name, [] );

		// Ensure $logs is an array
		if ( ! is_array( $logs ) ) {
			$logs = [];
		}

		// Add the new entry
		$logs[] = $entry;

		// Keep only the latest logs (limit to max_logs)
		if ( count( $logs ) > $this->max_logs ) {
			$logs = array_slice( $logs, -$this->max_logs );
		}

		// Save to database
		update_option( $this->option_name, $logs );
	}

	/**
	 * Clear the logs
	 * 
	 * @param	void
	 * @return	void
	 * @since	1.0
	 * 
	 */
	public function clear(): void {
		delete_option( $this->option_name );
	}

	/**
	 * Get all logs
	 *
	 * @param	void
	 * @return	mixed
	 * @since	1.0
	 */
	public function get_logs(): mixed {
		return get_option( $this->option_name, [] );
	}

	/**
	 * Get the option name
	 *
	 * @param	void
	 * @return	mixed
	 * @since	1.0
	 * 
	 */
	public function get_option_name(): mixed {
		return $this->option_name;
	}

	/**
	 * Delete a single log by key
	 *
	 * @param	int	$log_key	The key of the log to delete
	 * @return	boolean|WP_Error
	 * @since	1.0
	 * 
	 */
	public function delete_log( int $log_key ): bool|\WP_Error {
		$logs = $this->get_logs();

		if ( isset( $logs[ $log_key ] ) ) {
			unset( $logs[ $log_key ] );

			update_option( $this->option_name, $logs );

			return true;
		} else {
			return new \WP_Error( 'log_id_not_found', __( 'Log ID not found!', 'ltw-order-to-board-sync' ) );
		}
	}

	/**
	 * Delete all logs in one go :)
	 *
	 * @param	void
	 * @return	boolean
	 * @since	1.0
	 * 
	 */
	public function delete_all(): bool {
		return delete_option( $this->option_name );
	}
}
