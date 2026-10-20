<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the plugin's class autoloader.
 */
spl_autoload_register(
	/**
	 * Loads a namespaced plugin class from the classes directory.
	 *
	 * @param	string	$class_name	Fully qualified class name to load.
	 * @return	void
	 */
	static function ( string $class_name ): void {
		// Limit this autoloader to classes belonging to the plugin namespace.
		$prefix = 'LTW\\Order_To_Board_Sync\\';
		$namespace_position = strpos( $class_name, $prefix );

		if ( $namespace_position !== 0 ) {
			return;
		}

		// Build the class filename by converting each namespace segment to kebab-case.
		$base_dir = __DIR__ . '/classes/';
		$relative_class = substr( $class_name, strlen( $prefix ) );
		$parts = explode( '\\', $relative_class );
		$kebab_parts = [];

		foreach ( $parts as $part ) {
			$part = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $part );
			$part = str_replace( '_', '-', $part );
			$kebab_parts[] = strtolower( $part );
		}

		// Only require a matching file; missing classes remain available to other autoloaders.
		$file_name = 'class-' . implode( '-', $kebab_parts ) . '.php';
		$full_path = $base_dir . $file_name;
		$class_file_exists = file_exists( $full_path );

		if ( $class_file_exists === true ) {
			require $full_path;
		}
	}
);
